<?php

if ( !class_exists( 'pwsPlugin_1_0' ) ) {

	class pwsPlugin_1_0 {

		private $menus = Array( );
		protected $config = Array( 'plugin_options' => Array( ), 'constants' => Array( ) );

		protected function __construct( ) {

			#$this->init_debug( );
			$this->init_constants( );
			$this->init_plugin_options( );

			if ( method_exists( $this, 'afterOptionsLoaded' ) ) {
				$this->afterOptionsLoaded( );
			}
			$this->init_default_hooks( );
			$this->init_controllers( );

		} // End function

		private function init_constants( ) { 
			if ( !empty( $this->constants ) ) {
				$this->config['constants'] = $this->constants;
			}

		} // End function

		private function init_plugin_options( ) {
			if ( !empty( $this->defaultOptions ) ) {
				extract( $this->config['constants'] );
				$settings = get_option( $options_key );
				$settings = shortcode_atts( $this->defaultOptions, $settings );
				if ( isset( $_POST[$options_key] ) ) {
					$settings = shortcode_atts( $this->defaultOptions, $_POST[$options_key] );
				}
				update_option( $options_key, $settings );
				$this->config['plugin_options'] = $settings;
			}
		} # End function

		public function init_default_hooks( ) {

			$filepath = $this->config['constants']['plugin_folder'] . '/' . $this->config['constants']['plugin_name'] . '.php';
			register_activation_hook( $filepath, array( $this, 'activation' ) );
			register_deactivation_hook( $filepath, array( $this, 'deactivation' ) );

			# Init admin menus
			add_action( 'admin_menu', array( $this, 'init_menus' ) );

			# Base url for jquery ajax calls
			add_action( 'wp_head', array( $this, 'pws_base_url' ) );

			# Add settings link to plugin page
			add_filter( 'plugin_action_links', array( $this, 'addPluginSettingsLink' ), 10, 2 );
			
			# Add extra cron schedules
			add_filter( 'cron_schedules', array( $this, 'initCronSchedules' ) );

			# Add cronjobs 
			# add_action( 'wp_loaded', Array( $this, 'initCronJobs' ) );
			if ( !empty( $this->cronJobs ) ) {
				foreach( $this->cronJobs as $key => $cronjob ) {
					$name = "{$this->config['constants']['plugin_name']}_$key";
					add_action( $name, $cronjob['callback'] );
				}
			}

		} # End function

		private function init_controllers( ) {
			if ( !empty( $this->controllerNames ) ) {
				extract( $this->config['constants'] );
				foreach( $this->controllerNames as $controllerName ) {
					require_once( "{$plugin_folder}/controllers/$controllerName.controller.php" );
					$className = "{$controllerName}Controller";
					$this->controllers[$controllerName] = new $className( $this->config );
					$this->addMenus( $controllerName, $this->controllers[$controllerName]->getMenus( ) );
				}
			}
		}

		private function addMenus( $controller, $menus ) {

			if ( !empty( $menus ) ) {
				foreach( $menus as $menu ) {
					$menu['controller'] = $controller;
					if ( isset( $menu['type'] ) && $menu['type'] == 'main' ) {
						$this->menus['main'] = $menu;
						if ( isset( $menu['settingspage'] ) && $menu['settingspage'] ) $this->pluginSettingsLinkDetails = $menu;
					} else {
						$this->menus['submenus'][] = $menu;
						if ( isset( $menu['settingspage'] ) && $menu['settingspage'] ) $this->pluginSettingsLinkDetails = $menu;
					}
				}	

			}

		}

		public function init_menus( ) {
			if ( !empty( $this->menus ) ) {
				if ( !function_exists( 'add_menu_page' ) ) exit;
				if ( !function_exists( 'add_submenu_page' ) ) exit;
				# Assumes only one top level menu and unlimited submenus
				$position = 100;
				extract( $this->menus['main'] );
				$mainMenuSlug = "{$this->config['constants']['plugin_name']}_{$controller}_{$menuSlug}";
				add_menu_page( 
					__( $pageTitle ), 
					__( $menuTitle ), 
					$capability, 
					$mainMenuSlug,
					array( $this->controllers[$controller], 'loadPage' ),
					$iconUrl,
					$position	
				);
				if ( !empty( $this->menus['submenus'] ) ) {		
					foreach( $this->menus['submenus'] as $submenu ) {
						extract( $submenu );
						$subMenuSlug = "{$this->config['constants']['plugin_name']}_{$controller}_{$menuSlug}";
						add_submenu_page( 
							$mainMenuSlug,
							__( $pageTitle ), 
							__( $menuTitle ), 
							$capability, 
							$subMenuSlug, 
							array( $this->controllers[$controller], 'loadPage' )
						);
					}
				}
			}
		}

		public function initCronSchedules( $schedules ) {
			$existingSchedules = Array( );
			if ( !empty( $this->cronSchedules ) ) {
				foreach( $schedules as $schedule ) {
					$existingSchedules[] = $schedule['interval'];
				}

				foreach( $this->cronSchedules as $name => $data ) {
					if ( !in_array( $interval, $existingSchedules ) ) {
						$schedules[$name] = Array( 
							'interval' => $data['interval'],
							'display' => $data['display']
						);
					}
				}
			}
			return $schedules;
		}

		public function initCronJobs( ) {
			if ( !empty( $this->cronJobs ) ) {
				foreach( $this->cronJobs as $key => $cronjob ) {
					$name = "{$this->config['constants']['plugin_name']}_$key";
					$gmt_offset = (int)(get_option( 'gmt_offset' ) * 3600 );
					wp_clear_scheduled_hook( $name );
					wp_schedule_event( $cronjob['start'] - $gmt_offset, $cronjob['frequency'], $name );
				}
			}

		}

		private function removeCronJobs( ) {
			if ( !empty( $this->cronJobs ) ) {
				foreach( $this->cronJobs as $key => $cronjob ) {
					$name = "{$this->config['constants']['plugin_name']}_$key";
					wp_clear_scheduled_hook( $name );
				}			
			}
		}

		private function init_debug( ) {
			add_action( 'cron_schedules', create_function( '', 'var_dump( current_filter() );' ) );
			add_action( 'wp_loaded', create_function( '', 'var_dump( current_filter() );' ) );
		}

		private function init_shortcodes( ) {

			add_shortcode( $this->class_prefix . '_search', array( $this, $this->class_prefix . '_search_sc' ) );

		} // End function

		public function activation( ) {
			$this->initCronJobs( );
			# Check for any activation code in child classes
			if ( method_exists( $this, 'activate' ) ) $this->activate( );

		} # End function

		public function deactivation( ) {
			$this->removeCronJobs( );
			# Check for any deactivation code in child classes
			if ( method_exists( $this, 'deactivate' ) ) $this->deactivate( );

		} # End function

		public function pws_base_url( ) {
			$baseurl = get_bloginfo('url');
			echo "<script>var pws_base_url = '$baseurl';</script>";
		}

		public function addPluginSettingsLink( $links, $file ) {
			if ( !empty( $this->pluginSettingsLinkDetails ) ) {
				extract( $this->config['constants'] );
				if ( $file == basename( $plugin_folder ) . "/{$plugin_name}.php" ) {
					extract( $this->pluginSettingsLinkDetails );
					$settings_link = "<a href='admin.php?page={$plugin_name}_{$controller}_{$menuSlug}'>Settings</a>";
					$links = array_merge( $links, array( $settings_link ) );
				}
			}
			return $links;

		} // End function

		public function getModel( $name ) {
			$filepath = "{$this->config['constants']['plugin_folder']}/models/{$name}.model.php";
			if ( file_exists( $filepath ) ) {
				require_once( $filepath );
				$class_name = "{$name}Model";
				$instance = new $class_name( $this->config );
				return $instance;
			}
		}

	} // End class
} // End if

?>
