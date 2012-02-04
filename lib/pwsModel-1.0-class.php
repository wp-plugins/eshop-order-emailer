<?php

if ( !class_exists( 'pwsModel_1_0' ) ) {

class pwsModel_1_0 {

	public function __construct( $config ) {
		global $wpdb;
		$this->db = $wpdb;
		$this->config = $config;
	}
}

}
?>
