<?php

if ( !class_exists( 'pwsController_1_0' ) ) {

class pwsController_1_0 {

	private $pluginData = Array( );

	protected function __construct( $pluginData ) {
		
		$this->pluginData = $pluginData;

		$this->name = substr( get_class( $this ), 0, -10 );

		list( $this->plugin, $this->controller, $this->method ) = explode( '_', $_GET['page'] );

		add_action( 'init', Array( $this, 'initHook' ) );

	}

	public function initHook( ) {

		if ( $this->controller == $this->name ) {
			# Load model
			$model_file_path = "{$this->pluginData['constants']['plugin_folder']}/models/{$this->controller}.model.php";
			if ( file_exists( $model_file_path ) ) {
				require_once( $model_file_path );
				$class_name = "{$this->controller}Model";
				$this->model = new $class_name( );
			}
			# Load stylesheets
			$this->initCSS( );
			# Load javascript
			$this->initJS( );
		}

		# Global Stylesheet
		$general_stylesheet = "/css/plugin_styles.css";
		if ( file_exists( $this->pluginData['constants']['plugin_folder'] . $general_stylesheet ) ) {
			$key = "{$plugin_name}_styles";
			wp_register_style( $key, $this->pluginData['constants']['plugin_url'] . $general_stylesheet );
			wp_enqueue_style( $key );
		}
	}

	public function loadPage( ) {

		$this->{$this->method}( );

		extract( $this->pluginData['constants'] );

		require_once( "{$plugin_folder}/views/layout.php" );
	}

	private function initCSS( ) {
		if ( !empty( $this->stylesheets ) ) {
			extract( $this->pluginData['constants'] );

			foreach( $this->stylesheets as $key => $url ) {
				if ( !preg_match( '/http:\/\//', $url ) ) $url = "{$plugin_url}/css/$url";
				wp_register_style( $key, $url );
				wp_enqueue_style( $key );
			}
		}
	}

	private function initJS( ) {
		if ( !empty( $this->javascripts ) ) {
			extract( $this->pluginData['constants'] );
			foreach( $this->javascripts as $key => $url ) {
				if ( !preg_match( '/http:\/\//', $url ) ) $url = "{$plugin_url}/js/$url";
				if ( !empty( $url ) ) wp_register_script( $key, $url );
				wp_enqueue_script( $key );
			}
		}
	}

	public function getMenus( ) {
		return $this->menus;
	}
}

}


?>
