<?php

if ( !class_exists( 'pwsCustomType_1_0' ) ) {

	class pwsCustomType_1_0 {

		var $uname_single;
		var $uname_plural;
		var $lname_single;
		var $lname_plural;
		var $labels = array( );
		var $args = array( );
		var $columns = array( );
		var $meta_data = array( );

		function __construct( $name_single, $name_plural ) {

			$this->uname_single = ucfirst( $name_single );
			$this->lname_single = strtolower( $name_single );

			$this->uname_plural = ucfirst( $name_plural );
			$this->lname_plural = strtolower( $name_plural );

			// Labels
			$this->labels = array(
				'name' => _x( $this->uname_plural, 'post type general name'),
				'singular_name' => _x( $this->uname_single, 'post type singular name'),
				'add_new' => _x('Add New', $this->lname_single),
				'add_new_item' => __('Add New '.$this->uname_single),
				'edit_item' => __('Edit '.$this->uname_single),
				'new_item' => __('New '.$this->uname_single),
				'view_item' => __('View '.$this->uname_single),
				'search_items' => __('Search '.$this->uname_plural),
				'not_found' =>  __('No '.$this->lname_plural.' found'),
				'not_found_in_trash' => __('No '.$this->lname_plural.' found in Trash'), 
				'parent' => 'Parent', // This is necessary for hierarchical custom types to allow setting of parent (won't show until 2 published items created)
				'parent_item_colon' => ''
			);

			// Config
			$this->args = array(
				'labels' => $this->labels,
				'exclude_from_search' => true,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true, 
				'_builtin' => false, // It's a custom post type, not a builtin one
				'query_var' => true,
				'rewrite' => true,
				'capability_type' => 'post',
				'hierarchical' => true,
				'menu_position' => null,
				'supports' => array('title','editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes'),
				'register_meta_box_cb' => array( $this, 'setup_meta_box' ), 
				'taxonomies' => array('category', 'post_tag'),
				'has_archive' => false,
				'can_export' => false,
				'show_in_nav_menus' => true,
			);
		}

		function init( ) { // Manual initialization to allow modification of variables defined during construction
			
			// Run Wordpress Init functions
			add_action( 'init', array( $this, 'wordpress_init' ) );

			// Customize the messages displayed in the edit screens
			add_filter('post_updated_messages', array( $this, 'edit_screen_messages' ) );

			// Display contextual help for custom post type
			add_action( 'contextual_help', array( $this, 'contextual_help_text' ), 10, 3 );

			// Alters the displayed columns
			add_filter( 'manage_edit-headline_columns', array( $this, 'display_custom_columns' ) );

			// save the custom fields
			add_action('save_post', array( $this, 'save_meta' ), 1, 2);

			// Custom columns
			add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );

			// Manage Edit Custom Type Columns
			add_action( "manage_edit-{$this->lname_single}_columns", array( $this, 'manage_edit_cpt_columns' ) );

			// Manage Edit CTP Sortable Columns
			add_action( "manage_edit-{$this->lname_single}_sortable_columns", array( $this, 'manage_edit_cpt_sortable_columns' ) );

			// Manage Edit CTP Sortable Columns
			add_action( "manage_{$this->lname_single}_posts_custom_column", array( $this, 'manage_cpt_custom_column' ) );

		}


		function wordpress_init( ) {

			// This function must be run after Wordpress init
			register_post_type( strtolower( $this->uname_single ), $this->args );

		} // End function

		function edit_screen_messages( $messages ) {

			global $post, $post_ID;

			$messages[ $this->lname_single ] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __($this->uname_single . ' updated. <a href="%s">View ' . $this->lname_single . '</a>'), esc_url( get_permalink($post_ID) ) ),
				2 => __( 'Custom field updated.' ),
				3 => __( 'Custom field deleted.' ),
				4 => __( $this->uname_single . ' updated.' ),
				/* translators: %s: date and time of the revision */
				5 => isset($_GET['revision']) ? sprintf( __( $this->uname_single . ' restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( $this->uname_single . ' published. <a href="%s">View ' . $this->lname_single . '</a>'), esc_url( get_permalink($post_ID) ) ),
				7 => __( $this->uname_single . ' saved.'),
				8 => sprintf( __( $this->uname_single . ' submitted. <a target="_blank" href="%s">Preview ' . $this->lname_single . '</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
				9 => sprintf( __( $this->uname_single . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . $this->lname_single . '</a>'),
				  // translators: Publish box date format, see http://php.net/date
				  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
				10 => sprintf( __( $this->uname_single . ' draft updated. <a target="_blank" href="%s">Preview ' . $this->lname_single . '</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			);

			return $messages;
		}

		function contextual_help_text( $contextual_help, $screen_id, $screen ) { 
			//$contextual_help .= var_dump($screen); // use this to help determine $screen->id
			if ('headline' == $screen->id ) {
				$contextual_help = '<p>' . __('Make your changes below, then click "Update"') . '</p>' ;
			} elseif ( 'edit-headline' == $screen->id ) {
				$contextual_help = '<p>' . __('This screen is used to manage your Eqentia headlines.') . '</p>' ;
			}
			return $contextual_help;
		}

		function display_custom_columns( $columns ) {
print_r( $columns );
			return $this->columns;
		}

		function setup_meta_box( ) {
			$name_meta = $this->lname_single . '_meta';
			add_meta_box( $name_meta, $this->uname_single . ' Meta', array( $this, 'display_meta' ), $this->lname_single, 'normal', 'high' );
		}

		function manage_edit_cpt_columns( $columns ) {

			if ( is_array( $columns ) ) {
				foreach( $columns as $k => $v ) {
					if ( $k != 'date' ) {
						$new_columns[$k] = $v;
					}
				}
			}

			$last['date'] = 'Date';

			if ( is_array( $this->display_columns ) ) {
				foreach( $this->display_columns as $k => $v ) {
					$middle_columns[$k] = $v['name'];
				}
			}

			$columns = array_merge( $new_columns, $middle_columns, $last );

			return $columns;
		} // End function

		function manage_edit_cpt_sortable_columns( $columns ) {

			if ( is_array( $this->display_columns ) ) {
				foreach( $this->display_columns as $k => $v ) {
					if ( $v['sortable'] ) {
						$columns[$k] = $v['name'];
					}
				}
			}
			return $columns;
		}


		function manage_cpt_custom_column( $column ) {

			global $post, $wpdb;
			$data_type =  $this->display_columns[$column]['type'];

			switch ( $data_type ) {
				case 'custom':
					$meta_key = $this->lname_single . '_' . $column;
					$val = get_post_meta( $post->ID, $meta_key, true );

					if ( ( (string) (int) $val === $val) && ( $val <= PHP_INT_MAX ) && ( $val >= ~PHP_INT_MAX ) ) {
						echo date( 'd M Y', $val );
					} else {
						echo $val;
					}
					break;;
				case 'builtin':
					echo $post->$column;
					break;;
				case 'taglist':
					$tags = wp_get_post_tags( $post->ID );
					foreach ( $tags as $t ) {
						$tag_links[] = sprintf( '<a href="edit.php?tag=%s&post_type=headline">%s</a>', $t->slug, $t->name );
					}
					if ( !empty( $tag_links ) ) {
						echo implode( ', ', $tag_links );
					} else {
						echo 'No tags';
					}
					break;;
				case 'catlist':
					$cat_ids = wp_get_post_categories( $post->ID );
					foreach ( $cat_ids as $c ) {
						$cat = get_category( $c );
						$cat_links[] = sprintf( '<a href="edit.php?category_name=%s&post_type=headline">%s</a>', $cat->slug, $cat->name );
					}
					if ( !empty( $cat_links ) ) {
						echo implode( ', ', $cat_links );
					} else {
						echo 'Uncategorized';
					}
					break;;
			} // End switch
		} // End function

		// Custom meta fields in edit screen

		function display_meta( ) {

			global $sumixCalendar_form, $post;

			// Nonce
			echo '<input type="hidden" name="meta_noncename" id="meta_noncename" value="' .	wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';

			$custom_fields = get_post_custom( $post->ID );
			foreach( $custom_fields as $k => $v ) {
				$match = preg_replace( '/' . $this->lname_single . '_/', '', $k );

				if ( isset( $this->meta_data[$match] ) ) {
					$this->meta_data[$match]['value'] = $v[0];
				}
			}

			// Output form
			echo $sumixCalendar_form->generate( $this->meta_data, 'meta_data_box' );

		}

		function save_meta( $post_id, $post ) {
			global $sumixCalendar_form;

			$form_key = $sumixCalendar_form->getFormKey( );

			$form_data = $_POST[$form_key];

			if ( $post->post_type == $this->lname_single ) {
				// Verify save is authorised
				$nonce = ( isset( $_POST['meta_noncename'] ) ) ? $_POST['meta_noncename'] : '';
				if ( !wp_verify_nonce( $_POST['meta_noncename'], plugin_basename( __FILE__ ) )) {
					return $post->ID;
				}
				if ( !current_user_can( 'edit_post', $post->ID ) ) {
					return $post->ID;
				}

				if( $post->post_type == 'revision' ) return; // Don't store custom data twice

				if ( is_array( $form_data ) ) {
					foreach( $form_data as $key => $meta_val ) {
						$meta_key = $this->lname_single . '_' . $key;
						if ( $this->meta_data[$key]['type'] == 'date' ) {
							$meta_val = strtotime( $meta_val );
						}
						update_post_meta( $post->ID, $meta_key, $meta_val );
					}
				}
			}
		}

		function add_meta_item( $name, $display_name, $type = 'text', $data = '' ) {
			$this->meta_data[$name] = array( 'name' => $display_name, 'type' => $type, 'data' => $data );
		}

	} // End class
} // End if

?>
