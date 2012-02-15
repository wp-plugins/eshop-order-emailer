<?php

if ( !class_exists( 'ordersModel' ) ) {

class ordersModel extends pwsModel_1_0 {

	const PM_RECIPIENT_KEY = 'supplier_email';
	const EORDEM_TABLE_SUFFIX = 'pws_eordem';

	public function __construct( $pluginSettings ) {
		$this->fields = Array( 
			'id', 'checkid', 'status', 'first_name', 'last_name', 'full_name', 'company', 
			'email', 'phone', 'full_address', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'country_name',
			'ship_name', 'ship_company', 'ship_phone', 'ship_address', 'full_ship_address', 'ship_city', 'ship_state', 
			'ship_postcode', 'ship_country', 'ship_country_name','reference', 'transid', 'comments', 'thememo', 'edited', 'paidvia',
			'order_items', 'admin_note', 'affiliate'  
		);

		parent::__construct( $pluginSettings );
	}

	public function resetEmailedOrders( ) {
		$pws_orders_emailed_table = $this->db->prefix . self::EORDEM_TABLE_SUFFIX;

		$sql = "DELETE FROM `$pws_orders_emailed_table`";

		return $this->db->query( $sql );

	}

	public function getNewOrders( ) {
		$orders_table = $this->db->prefix . 'eshop_orders';
		$order_items_table = $this->db->prefix . 'eshop_order_items';
		$eshop_states = $this->db->prefix . 'eshop_states';
		$eshop_countries = $this->db->prefix . 'eshop_countries';
		$pws_orders_emailed_table = $this->db->prefix . self::EORDEM_TABLE_SUFFIX;
		
		$sql = 	"
			SELECT 	
				o.*, 
				CONCAT( o.first_name, ' ', o.last_name ) as 'full_name', 
				oi.item_id, 
				oi.item_qty, 
				oi.optname, 
				oi.weight, 
				oi.post_id, 
				s.code as 'state', 
				s2.code as 'ship_state', 
				c.country as 'country_name', 
				c2.country as 'ship_country_name', 
				CONCAT( o.address1, ' ', o.address2, ' ', o.city, ' ', s.code, ' ', o.zip, ' ', c.country ) as 'full_address',
				CONCAT( o.ship_address, ' ', o.ship_city, ' ', s2.code, ' ', o.ship_postcode, ' ', c2.country ) as 'full_ship_address',
				CONCAT( oi.item_qty, ' x ', oi.optname, ' (', oi.item_id, ')' ) as 'order_items', 
				oi.optsets  
			FROM 
				`$orders_table` o 
				LEFT JOIN `$order_items_table` oi ON ( o.checkid = oi.checkid ) 
				LEFT JOIN  `$eshop_states` s ON ( o.state = s.id ) 
				LEFT JOIN `$eshop_states` s2 ON ( o.ship_state = s2.id ) 
				LEFT JOIN `$eshop_countries` c ON ( o.country = c.code ) 
				LEFT JOIN `$eshop_countries` c2 ON ( o.ship_country = c2.code ) 
			WHERE 
				o.id NOT IN ( SELECT eshop_order_id FROM $pws_orders_emailed_table ) AND 
				o.status = 'Completed' AND
				oi.post_id != 0 AND
				oi.item_id != 'Shipping'
			";
		error_log( $sql );
		$results = $this->db->get_results( $sql, ARRAY_A );
		return $results;
	}

	private function cleanOptSets( $optset_html ) {
		$optset_html = str_replace( Array( "\r\n", "\r", "\n" ), '', $optset_html );
		$stripped = explode( '</span>', $optset_html );
		if ( is_array( $stripped ) ) {
			foreach ( $stripped as $html ) {
				$stripped = strip_tags( $html );
				if ( !empty( $stripped ) ) $clean[] = $stripped;
			}
		}
		if ( is_array( $clean ) ) {
			for ( $i = 0; $i < count( $clean ); $i++ ) {
				$key = $clean[$i];
				$i++;
				$val = $clean[$i];
				$optsets[] = "$key:$val";
			}
		}
		if ( is_array( $optsets ) ) $optsets = implode( ',', $optsets );
		return $optsets;
	}

	public function filterFields( $orders ) {
		if ( is_array( $orders ) ) {
			foreach( $orders as $order ) {
				$recipients = get_post_meta( $order['post_id'], self::PM_RECIPIENT_KEY, TRUE );
				if ( empty( $recipients ) ) $recipients = 'admin_email';

				$order['recipients'] = $recipients;
				$temp = Array( );
				foreach( $order as $key => $val ) {
					if ( in_array( $key, $this->fields ) ) $temp[$key] = $val;
				}
				$name_address_key = $order['full_name'] . $order['ship_address'] . $order['ship_city'];
				$optsets = $this->cleanOptSets( $order['optsets'] );
				if ( !empty( $optsets ) ) {
					$optsets = ' [' . $optsets . ']';
					$order['order_items'] .= $optsets;
				}
				if ( isset( $data[$recipients][$name_address_key] ) ) {
					$data[$recipients][$name_address_key]['order_items'] .= "\r\n{$order['order_items']}";
					$data[$recipients][$name_address_key]['order_ids'][$order['id']] = $order['checkid'];
				} else {
					$data[$recipients][$name_address_key] = $temp;
					$data[$recipients][$name_address_key]['order_ids'][$order['id']] = $order['checkid'];
					$data[$recipients][$name_address_key]['order_items'] = $order['order_items'];
				}
				unset( $data[$recipients][$name_address_key]['id'] );
				unset( $data[$recipients][$name_address_key]['checkid'] );
			}
			return $data;
		}
	}
	
	public function prepareCSV( $data ) {
		$prepared_csv = Array( );
		if ( is_array( $data ) ) {
			foreach( $data as $customer => $d ) {
				$formatted_row = $this->formatReportRow( $d );
				if ( !empty( $formatted_row ) ) {
					$prepared_csv[] = $formatted_row;
				}
			}
		}

		return $prepared_csv;
	}

	private function formatReportRow( $row ) {
		$options = $this->config['plugin_options'];
		$formatted_row = Array( );
		if ( is_array( $row ) && !empty( $row ) && !empty( $options['csv_fields'] ) ) {
			foreach( $options['csv_fields'] as $item ) {
				extract( $item );
				$name = strtolower( $name );
				if ( $name == 'custom' ) {
					list( $k, $v ) = explode( '=', $text );
					$formatted_row[$k] = $v;
				} else {
					$formatted_row[$text] = $row[str_replace( ' ', '_', $name )];
				}
			}
		}
		return $formatted_row;
	}

	public function markSent( $results ) {
		
		$pws_orders_emailed_table = $this->db->prefix . self::EORDEM_TABLE_SUFFIX;

		if ( is_array( $results ) ) {
			foreach( $results as $r ) {
				$supplier_email = get_post_meta( $r['post_id'], 'supplier_email', TRUE );
				$recipients = ( $supplier_email ) ? $supplier_email : get_bloginfo( 'admin_email' );
				$this->db->insert( $pws_orders_emailed_table, Array( 
					'eshop_order_id' => $r['id'],
					'post_id' => $r['post_id'],
					'recipients' => $recipients,
					'email_sent' => date( 'Y-m-d H:i:s' )
					)
				);
			}
		}
	}

	public function getDailySummary( ) {

		$orders_table = $this->db->prefix . 'eshop_orders';
		$order_items_table = $this->db->prefix . 'eshop_order_items';
		$eshop_states = $this->db->prefix . 'eshop_states';
		$pws_orders_emailed_table = $this->db->prefix . self::EORDEM_TABLE_SUFFIX;

		$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 86400 );

		$sql = 	"
			SELECT oe.eshop_order_id, oe.post_id, oe.recipients, oe.email_sent, o.first_name, o.last_name, CONCAT( oi.item_qty, ' x ', oi.optname, ' (', oi.item_id, ')' ) as 'order_items', oi.optsets
			FROM `$pws_orders_emailed_table` oe, `$orders_table` o, `$order_items_table` oi
			WHERE
				oe.eshop_order_id = o.id AND
				o.checkid = oi.checkid AND
			       	oe.post_id = oi.post_id AND
				oi.item_id != 'Shipping' AND	
				oe.email_sent > DATE( '$date' )
			";

		error_log( $sql );
		$results = $this->db->get_results( $sql, ARRAY_A );
		if ( is_array( $results ) ) {
			foreach( $results as &$row ) {
				$optsets = '';
				if ( !empty( $row['optsets'] ) ) $optsets = $this->cleanOptSets( $row['optsets'] );
				unset( $row['optsets'] );
				if ( !empty( $optsets ) ) {
					$optsets = ' [' . $optsets . ']';
					$row['order_items'] .= $optsets;
				}
			}
		}
		return $results;
	}
}

}
?>
