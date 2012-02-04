<?php

if ( !class_exists( 'creditsController' ) ) {

class creditsController extends pwsController_1_0 {
	
	public function __construct( $pluginSettings ) {

		$this->stylesheets = Array(
			'credits_styles' => 'credits_styles.css',
		);
		
		extract( $pluginSettings['constants'] );

		$title = 'Credits/Instructions';
		$this->menus = Array(
			Array(
				'type' => 'sub',
				'settingspage' => FALSE,
				'pageTitle' => $title,
				'menuTitle' => $title,
				'capability' => 'administrator',
				'menuSlug' => 'main',
				'iconUrl' => '',
				'position' => '100'
			),
		);

		parent::__construct( $pluginSettings );
	}

	public function main( ) {

	}

}

}

?>
