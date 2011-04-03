<?php
/*
Plugin Name: eShop Order Emailer
Plugin URI: http://www.paulswebsolutions.com
Description: Email a CSV report of successful eShop orders each day.
Version: 1.1
Author: Paul's Web Solutions
Author URI: http://www.paulswebsolutions.com/
*/

DEFINE( 'EORDEM_SETTINGS', '_eordem_settings' );

// Load classes/functions
require_once( 'view.php' );

if ( !class_exists( 'eordem_controller' ) ) {

	class eordem_controller {

		var $view;
		var $settings;
		var $fields;

		function eordem_controller( ) {

			$settings = array( // Defaults
				'recipients' => '',
				'hour_of_day' => '',
				'fields' => array( )
			);

			$this->fields = array( 'id', 'checkid', 'status', 'first_name', 'last_name', 'full_name', 'company', 'email', 'phone', 'address', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'reference', 'transid', 'comments', 'thememo', 'edited', 'paidvia', 'order_items', 'before', 'after' );

			add_option( EORDEM_SETTINGS, $settings ); // Does nothing if option already exists

			if ( isset( $_POST['recipients'] ) && isset( $_POST['hour_of_day'] ) ) {
				$this->settings['recipients'] = $_POST['recipients'];
				$this->settings['hour_of_day'] = $_POST['hour_of_day'];
				$this->settings['fields'] = $_POST['fields'];
				$this->save_settings( $this->settings );
			}

			$this->settings = get_option( EORDEM_SETTINGS );

			// CST = 'America/Chicago' as required
			//date_default_timezone_set( 'America/Chicago' );

			if ( isset( $_POST['sendnow'] ) ) {
				$this->send_email( );
			} elseif ( isset( $_POST['reset'] ) ) {
				$this->reset( );
			} elseif ( isset( $_POST['recipients'] ) && isset( $_POST['hour_of_day'] ) ) {
				wp_clear_scheduled_hook( 'eordem_send' );

				$start_time = strtotime( date( 'd M Y' ) ) - (int)(get_option( 'gmt_offset' ) * 3600) + $this->settings['hour_of_day'] - 900;
				wp_schedule_event( $start_time, 'daily', 'eordem_send' );
			}



			$this->view = new eordem_view( );

		}

		function setup( ) {
			$f = $this->order_query();
			$this->view->page( 'setup', array_merge( $this->settings, array( 'eshop_fields' => $this->fields, 'recent_orders' => $f ) ) );
		}

		function save_settings( $settings ) {
			update_option( EORDEM_SETTINGS, $settings );
		}

		function menu( ) {
			add_submenu_page( 'tools.php', 'eShop Emailer', 'eShop Emailer', 'administrator', 'eordem', array( $this, 'setup' ) );
		}

		function send_email( ) {
			$attachment = $this->get_report( );
			$success = $this->send_message( $this->settings['recipients'], get_bloginfo( 'admin_email' ), 'eShop Orders', 'A report of orders completed in the last 24 hours is attached.', $attachment );
			if ( is_file( $attachment ) ) {
				unlink( $attachment );
			}
		}

		function reset( ) {
			global $wpdb;
			$pws_orders_emailed_table = $wpdb->prefix . 'pws_eshop_orders_emailed';
			$sql = $wpdb->prepare( "DELETE FROM $pws_orders_emailed_table" );
			$wpdb->query( $sql );
		}

		function order_query( ) {
			global $wpdb;
			$orders_table = $wpdb->prefix . 'eshop_orders';
			$order_items_table = $wpdb->prefix . 'eshop_order_items';
			$pws_orders_emailed_table = $wpdb->prefix . 'pws_eshop_orders_emailed';
			// This query checks the last two days for successful orders that have not yet been emailed via CSV (as recorded in 
			// the pws_eshop_orders_emailed table
			$sql = "SELECT o.*,oi.item_id, oi.item_qty, oi.optname, oi.weight, s.code FROM $orders_table o LEFT JOIN $order_items_table oi ON o.checkid = oi.checkid, wp_eshop_states s WHERE s.id = o.state AND o.id NOT IN (SELECT eshop_order_id FROM $pws_orders_emailed_table ) AND o.status = 'Completed'";
// not needed for now: edited BETWEEN SYSDATE() - INTERVAL 2 DAY AND SYSDATE(),
			$results = $wpdb->get_results( $sql, ARRAY_A );

			foreach( $results as $r ) {
				extract( $r );
				if ( !isset( $data[$checkid] ) ) {
					$r['full_name'] = "$first_name $last_name";
					$r['state'] = $code;
					$r['ship_postcode'] = $code;
					$r['order_items'] = "$item_qty x $optname ($item_id)";
					$r['address'] = empty($address2) ? $address1 : "$address1\n$address2";
					foreach( $r as $k => $v ) {
						if ( in_array( $k, $this->fields ) ) {
							$data[$checkid][$k] = $v;
						}
					}
				} else {
					if ( $item_id != 'Shipping' ) {
						$data[$checkid]['order_items'] .= "\n$item_qty x $optname ($item_id)";
					}
				}
			}

			$before = array( );
			$after = array( );
			if ( $data ) {
				if ( !empty( $this->settings['fields']['before'] ) && isset($this->settings['fields']['before']['checked']) ) {
					$temp = explode( ',', $this->settings['fields']['before']['display'] );
					foreach( $temp as $t ) {
						$keyval = explode( '=', $t );
						$before[$keyval[0]] = $keyval[1];
					}
				}
				if ( !empty( $this->settings['fields']['after'] ) && isset($this->settings['fields']['after']['checked']) ) {
					$temp = explode( ',', $this->settings['fields']['after']['display'] );
					foreach( $temp as $t ) {
						$keyval = explode( '=', $t );
						$after[$keyval[0]] = $keyval[1];
					}
				}
				foreach( $data as $d ) {
					foreach( $this->settings['fields'] as $k => $v ) {
						if ( isset( $v['checked'] ) ) {
							if ( $k != 'before' && $k != 'after' ) {
								$display = empty( $v['display'] ) ? $k : $v['display'];
								$middle[$display] = $d[$k];
							}
						}
					}
					$processed[$d['id']] = array_merge( $before, $middle, $after );
				}
				return $processed;
			}
			return NULL;
		}


		function get_report( ) {
			global $wpdb;
			$pws_orders_emailed_table = $wpdb->prefix . 'pws_eshop_orders_emailed';
			$fullpath = '';
			$results = $this->order_query( );

			$fullpath = dirname( __FILE__ ) . '/eshop-orders.csv';
			if ( is_file( $fullpath ) ) {
				unlink( $fullpath );
			}
			$fp = @fopen( $fullpath, 'w' );
			if ( $fp ) {
				if ( !empty( $results ) ) {
					$first = true;
					foreach ( $results as $k => $row ) {
						if ( $first ) {
							$fwrite = fputcsv( $fp, array_keys( $row ) );
							$first = false;
						}
						$fwrite = fputcsv( $fp, array_values( $row ) );
						$wpdb->insert( $pws_orders_emailed_table, array( 'eshop_order_id' => $k ) );
					}
				} else {
					$fwrite = fwrite( $fp, 'No orders' );
				}
				fclose( $fp );
			}
			return $fullpath;
		}

		function activation( ) {
			$this->add_table( );
		}

		function deactivation( ) {
			$timestamp = wp_next_scheduled( 'eordem_send' );
			wp_unschedule_event( $timestamp, 'eordem_send' );
			wp_clear_scheduled_hook( 'eordem_send' );
		}

		function send_message( $to, $from, $subject, $body, $attachment ) {

			require_once( WP_CONTENT_DIR . '/../wp-includes/class-phpmailer.php' );
			$mail = new PHPMailer(false);

			if ( preg_match( '/10\.1\.1\.3/', $_SERVER['SERVER_NAME'] ) ) {  // This is for dev testing only
				$mail->IsSMTP();
				$mail->Host = 'mail.internode.on.net';
				$mail->SMTPAuth = false;
			}
			$mail->Subject = $subject;
			$mail->From = $from;
			$mail->FromName = get_bloginfo( 'name' );
			$recipients = explode( ',', $to );
			foreach( $recipients as $recipient ) {
				$mail->AddAddress( $recipient );
			}
			$mail->AddAttachment( $attachment, 'orders.csv' );
			$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
			$mail->CharSet = "utf-8";
			$mail->MsgHTML($body);
			$successful = $mail->Send();
			return $successful;
		}

		function add_table( ) {
			global $wpdb;
			$table_name = $wpdb->prefix . "pws_eshop_orders_emailed";

			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE " . $table_name . " (
					id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eshop_order_id int(11) NOT NULL
				);";
				$wpdb->query( $sql );
			}
		}

		function init( ) {
			wp_enqueue_style( 'eordem_styles' );
		}
	}
}

// Main
if ( class_exists( 'eordem_controller' ) ) {
	$eordem_controller = new eordem_controller( );

	register_activation_hook( __FILE__, array( $eordem_controller, 'activation' ) );
	register_deactivation_hook( __FILE__, array( $eordem_controller, 'deactivation' ) );

	wp_register_style ( 'eordem_styles', '/wp-content/plugins/eshop-order-emailer/css/styles.css' );

	add_action( 'init', array( $eordem_controller, 'init' ) );

	add_action( 'admin_print_styles', array( $eordem_controller, 'init' ) );

	add_action( 'admin_menu', array( $eordem_controller, 'menu' ) );
	add_action( 'eordem_send', array( $eordem_controller, 'send_email' ) );
}
?>
