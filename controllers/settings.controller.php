<?php

if ( !class_exists( 'settingsController' ) ) {

class settingsController extends pwsController_1_0 {
	
	public function __construct( $pluginSettings ) {

		$this->config = $pluginSettings;

		$this->stylesheets = Array(
			'settings_styles' => 'settings_styles.css'
		);

		$this->javascripts = Array(
			'settings_js' => 'settings_script.js'
		);
		
		extract( $pluginSettings['constants'] );

		$title = ucfirst( $plugin_menu_display_name );
		$this->menus = Array(
			Array(
				'type' => 'main',
				'settingspage' => TRUE,
				'pageTitle' => $title,
				'menuTitle' => $title,
				'capability' => 'administrator',
				'menuSlug' => 'main',
				'iconUrl' => "$plugin_url/img/mailbox_small.png",
				'position' => '100'
			),
			Array(
				'type' => 'sub',
				'settingspage' => TRUE,
				'pageTitle' => $title,
				'menuTitle' => 'Settings',
				'capability' => 'administrator',
				'menuSlug' => 'main',
				'iconUrl' => '',
				'position' => '100'
			)
		);

		parent::__construct( $pluginSettings );
	}

	public function main( ) {

		if ( isset( $_POST['reset'] ) ) {
			$plugin_name = $this->config['constants']['plugin_name'];
			$filepath = "{$this->config['constants']['plugin_folder']}/models/orders.model.php";
			require_once( $filepath );
			$ordersModel = new ordersModel( $this->config );
			$ordersModel->resetEmailedOrders( );
			do_action( "{$plugin_name}_notify_suppliers", TRUE );
		} elseif ( isset( $_POST['sendnow'] ) ) {
			do_action( "{$plugin_name}_notify_suppliers", FALSE );
		}

		$this->eshop_fields = Array( 
			'Status', 
			'First_name', 
			'Last_name', 
			'Full_name', 
			'Company', 
			'Email', 
			'Phone', 
			'Full_address', 
			'Address1', 
			'Address2', 
			'City', 
			'State', 
			'Zip', 
			'Country', 
			'Country_name',
			'Ship_name',
			'Ship_company',
			'Ship_phone',
			'Ship_address',
			'Full_ship_address', 
			'Ship_city',
			'Ship_state',
			'Ship_postcode',
			'Ship_country',
			'Ship_country_name',
			'Reference', 
			'Transid', 
			'Comments', 
			'Thememo', 
			'Edited', 
			'Paidvia', 
			'Order_items',
		       	'Admin_note',
			'Affiliate',	
			'Custom'
	       	);

	}

}

}

?>
