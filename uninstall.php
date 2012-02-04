<?php

if ( WP_UNINSTALL_PLUGIN ) {

	global $wpdb;
	$table = $wpdb->prefix . 'pws_eordem';
	$sql = "DROP TABLE `$table`";
	$wpdb->query( $sql );

	delete_option( 'eordem_plugin_options' );
}

?>
