<?php
/*
Plugin Name: eShop Order Emailer
Plugin URI: http://www.paulswebsolutions.com
Description: Automatically email eShop orders to your suppliers or a fulfillment center.
Version: 2.0.1
Author: Paul's Web Solutions
Author URI: http://www.paulswebsolutions.com/

	LICENSE

	Copyright 2012  Paul's Web Solutions  (email : paul@paulswebsolutions.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

# Init

$plugin_name = 'eordem';

# Load libraries

foreach( glob( dirname( __FILE__ ) . '/lib/*class.php' ) as $filename ) {
	require_once( $filename );
}

# Main class

if ( !class_exists( $plugin_name ) ) {

	class eordem extends pwsPlugin_1_0 {

		public function __construct( $plugin_name ) {

			$this->constants = Array(
				'plugin_name' => $plugin_name,
				'plugin_display_name' => 'eShop Order Emailer',
				'plugin_menu_display_name' => 'Order Emailer',
				'options_key' => "{$plugin_name}_plugin_options",
				'plugin_folder' => dirname( __FILE__ ),
				'plugin_url' => plugins_url( '', __FILE__ )
			);

			$this->defaultOptions = Array(
				'recipients' => get_bloginfo( 'admin_email' ),
				'hour_of_day' => '0',
				'summary_hour_of_day' => '0',
				'csv_fields' => Array( ),
				'use_supplier_email' => TRUE,
				'frequency' => 'daily',
				'summary_frequency' => 'daily',
				'email_subject' => 'Orders From ' . get_bloginfo( 'name' )

			);

			$this->controllerNames = Array(
				'settings',
				'credits'
			);

			$this->cronSchedules = Array(
				'two_minutes' => Array( 'interval' => 120, 'display' => 'Every Two Minutes' ),
				'two_hours' => Array( 'interval' => 7200, 'display' => 'Every Two Hours' ),
				'four_hours' => Array( 'interval' => 28800, 'display' => 'Every Four Hours' )
			);
			parent::__construct( $plugin_name );
		}

		public function afterOptionsLoaded( ) {
			$send_emails_timestamp = strtotime( date( 'Y-m-d' ) ) + $this->config['plugin_options']['hour_of_day'];
			$daily_summary_timestamp = strtotime( date( 'Y-m-d' ) ) + $this->config['plugin_options']['summary_hour_of_day'];
			$send_emails_frequency = $this->config['plugin_options']['frequency'];
			$daily_summary_frequency = $this->config['plugin_options']['summary_frequency'];

			$this->cronJobs = Array(
				'notify_suppliers' => Array( 
					'start' => $send_emails_timestamp, 
					'frequency' => $send_emails_frequency, 
					'callback' => Array( &$this, 'notify_suppliers' )
		       		),
				'daily_summary' => Array( 
					'start' => $daily_summary_timestamp, 
					'frequency' => $daily_summary_frequency, 
					'callback' => Array( &$this, 'daily_summary' ) 
				)
			);

			if ( isset( $_POST[$this->config['constants']['options_key']] ) ) {
				add_action( 'wp_loaded', Array( $this, 'initCronJobs' ) );
			}
			add_action( "{$plugin_name}_notify_suppliers", Array( $this, 'notify_suppliers' ) );
			add_action( "{$plugin_name}_delete_all", Array( $this, 'delete_all' ) );

		}

		public function activate( ) {
			global $wpdb;
			$table_name = $wpdb->prefix . "pws_eordem";

			if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
				$sql = 	"
					CREATE TABLE `$table_name` (
					id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eshop_order_id int(11) NOT NULL,
					post_id int(11) NOT NULL,
					recipients text NOT NULL,
					email_sent datetime NOT NULL
					);
					";
				$wpdb->query( $sql );
			}
			$this->convertOldOptions( );
			# Important to notify immediately on activation to populate the sent already table.  If we didn't do this
			# then it would be possible for orders from years ago to be re-sent and potentially cause a lot of problems.
			$this->notify_suppliers( TRUE );
		}

		private function convertOldOptions( ) {
			define( 'OLD_OPTIONS_KEY', '_eordem_settings' );

			$old_options = get_option( OLD_OPTIONS_KEY );
			$new_options = get_option( $this->constants['options_key'] );
			if ( $old_options ) {
				foreach( $old_options['fields'] as $field_name => $field_values ) {
					extract( $field_values );
					$field_name = ucwords( preg_replace( '/_/', ' ', $field_name ) );

					if ( $checked == 'on' && $field_name != 'Before' && $field_name != 'After' ) {
						$new_field_options[] = Array( 'name' => $field_name, 'text' => $display );
					}
					if ( $field_name == 'Before' && $checked == 'on' ) {
						$custom_fields = explode( ',', $display );
						foreach( $custom_fields as $cf ) {
							$before_options[] = Array( 'name' => 'Custom', 'text' => $cf );
						}
					}
					if ( $field_name == 'After' && $checked == 'on' ) {
						$custom_fields = explode( ',', $display );
						foreach( $custom_fields as $cf ) {
							$after_options[] = Array( 'name' => 'Custom', 'text' => $cf );
						}
					}
				}
			}	

			$new_options['csv_fields'] = array_merge( $before_options, $new_field_options, $after_options );

			update_option( $this->constants['options_key'], $new_options );

			delete_option( OLD_OPTIONS_KEY );

		}
		
		public function notify_suppliers( $send_to_admin_only = FALSE ) {
			
			$ordersModel = $this->getModel( 'orders' );
			
			$orders = $ordersModel->getNewOrders( );
			$filtered = $ordersModel->filterFields( $orders );
			if ( is_array( $filtered ) ) {
				foreach( $filtered as $recipients => $customer ) {
					$fulfillment_groups[$recipients] = $ordersModel->prepareCSV( $customer );
				}
			}
			if ( !empty( $fulfillment_groups ) ) {
				if ( $send_to_admin_only ) {
					$combined = Array( );
					foreach( $fulfillment_groups as $recipients => $orders ) {
						$combined = array_merge( $combined, $orders );
					}
					$fulfillment_groups = Array( 'admin_email' => $combined );
				}
				$folder = dirname( WP_CONTENT_DIR ) . "/tmp";
				if ( !file_exists( $folder ) ) mkdir( $folder );
				$filepath = "$folder/attachment.csv";
				$email_was_sent = FALSE;
				foreach( $fulfillment_groups as $recipients => $fulfillment_group ) {
					if ( !empty( $fulfillment_group ) && $this->createCSV( $fulfillment_group, $filepath ) ) {
						if ( $recipients == 'admin_email' || $send_to_admin_only ) $recipients = $this->config['plugin_options']['recipients'];
						$subject = $this->config['plugin_options']['email_subject'];
						$this->sendEmail( 
							$recipients, 
							get_bloginfo( 'admin_email' ), 
							$subject, 
							'Orders file attached.', 
							$filepath,
							FALSE
						);
						$email_was_sent = TRUE;
					}
				}
				if ( $email_was_sent ) $ordersModel->markSent( $orders );
				if ( file_exists( $filepath ) ) unlink( $filepath );
			}
		}

		private function createCSV( $data, $filepath ) {

			$csv = new pwsXSV( );
			//error_log( print_r( $data, TRUE ) );
			return $csv->save( $data, $filepath, TRUE, TRUE );
		}

		private function sendEmail( $to, $from, $subject, $body, $attachment, $send_to_log = FALSE ) {

			require_once( WP_CONTENT_DIR . '/../wp-includes/class-phpmailer.php' );
			$mail = new PHPMailer( FALSE );

			if ( preg_match( '/10\.1\.1\.3/', $_SERVER['SERVER_NAME'] ) ) {  // This is for dev testing only
				$mail->IsSMTP();
				$mail->Host = 'mail.internode.on.net';
				$mail->SMTPAuth = false;
			}
			$subject = htmlspecialchars_decode( $subject );
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
			if ( $send_to_log ) {
				error_log( print_r( $to, TRUE ) );
			} else {
				$successful = $mail->Send();
			}
			return $successful;
		}

		public function daily_summary( ) {
			$ordersModel = $this->getModel( 'orders' );
			$summary = $ordersModel->getDailySummary( );
			$folder = dirname( WP_CONTENT_DIR ) . "/tmp";
			if ( !file_exists( $folder ) ) mkdir( $folder );
			$filepath = "$folder/dailysummary.csv";
			if ( !empty( $summary ) && $this->createCSV( $summary, $filepath ) ) {
				$recipients = $this->config['plugin_options']['recipients'];
				$subject = 'Daily Summary';
				$this->sendEmail( 
					$recipients, 
					get_bloginfo( 'admin_email' ), 
					$subject, 
					'Daily summary of orders attached.', 
					$filepath,
					FALSE	
				);
				if ( file_exists( $filepath ) ) unlink( $filepath );
			}
		}
	}
}

# Go time

$main = new $plugin_name( $plugin_name );

?>
