<?php

if ( !class_exists( 'pwsForm_1_0' ) ) {

	class pwsForm_1_0 {

		var $unique;
		var $form_key;

		function __construct( $unique ) {
			$this->unique = $unique;
			$this->form_key = $this->getFormKey( );
		} // End function

		function getFormKey( ) {
			return $this->unique . '_form';
		}

		function generate( $fields ) {
			if ( is_array( $fields ) ) {
				$html = '';
				foreach( $fields as $key => $field ) {
					$value = '';
					extract( $field );
					switch ( $type ) {
						case 'text':
							$html .= "<tr><td><label for='$key'>$name:</label></td><td><input type='text' name='{$this->form_key}[$key]' id='$key' value='$value'/></td></tr>";
							break;;
						case 'textarea':
							$html .= "<tr><td><label for='$key'>$name:</label></td><td><textarea type='text' name='{$this->form_key}[$key]' id='$key'>$value</textarea></td></tr>";
							break;;
						case 'checkbox':
							$checked = ( $value == 'on' ) ? 'checked="checked"' : '';
							$html .= "<tr><td><label for='$key'>$name:</label></td><td><input type='checkbox' name='{$this->form_key}[$key]' id='$key' $checked /></td></tr>";
							break;;
						case 'image_upload':
							$html .= "<tr><td><label for='$key'>$name:</label></td><td><input type='text' name='{$this->form_key}[$key]' id='$key' value='$value' size='70'/> <input class='{$this->unique}_upload_image_button' type='button' alt='Upload Image' title='Upload Image' value='Upload Image'/></td></tr>";
							break;;
						case 'select':
							if ( is_array( $data ) ) {
								foreach( $data as $k => $v ) {
									$selected = ( $value == $v ) ? ' selected="selected"' : '';
									$select .= "<option value='$v'$selected>$k</option>";
								}
							}
							$select = "<select name='{$this->form_key}[$key]' id='$key'>$select</select>";
							$html .= "<tr><td><label for='$key'>$name:</label></td><td>$select</td></tr>";
							break;;
						case 'wp_page_dropdown':
							$args = array( 'selected' => $value, 'echo' => FALSE, 'name' => "{$this->form_key}[$key]" , 'id' => $key );
							$select = wp_dropdown_pages( $args );
							$html .= "<tr><td><label for='$key'>$name:</label></td><td>$select</td></tr>";
							break;;
							
						case 'courses_dropdown':
							$html_args = array( 'selected' => $value, 'echo' => FALSE, 'name' => "{$this->form_key}[$key]" , 'id' => $key );
							$select = $this->dropdown_courses( $html_args );
							$html .= "<tr><td><label for='$key'>$name:</label></td><td>$select</td></tr>";
							break;
						case 'ajax_add':
							$sub_panel = '';
							foreach( $data as $k => $v ) {
								$sub_panel .= "<label for='{$key}_{$v['name']}{$k}'>{$v['display']}</label><input type='text' name='{$this->form_key}[$key][{$v['name']}{$k}' value='{$v['value']}'/>";
							}
							$html .= "<tr><td colspan='2'><div id='$key' class='{$this->unique}_ajax_meta_box'>{$sub_panel}(<a id='{$this->unique}_ajax_add' href=''>add</a>|<a id='{$this->unique}_ajax_remove' href=''>remove</a>)</div></td></tr>";
							break;;
						case 'date':
							if ( !empty( $value ) )	$value = date( 'j M Y', $value );
							$html .= "<tr><td><label for='$key'>$name:</label></td><td><input type='text' class='{$this->unique}_datepicker' name='{$this->form_key}[$key]' id='{$key}' value='$value'/></td></tr>";
							break;;

					} // End switch
				}	// End foreach
			} // End if
			$id = $this->unique . '_table';
			return "<table id='$id'><tbody>$html</tbody></table>";
		}

		function dropdown_courses( $html_args ) {
			$args = array(
				'post_type' => 'sumixcourse',
				'order_by' => 'post_title',
				'order' => 'ASC',
				'posts_per_page' => -1
			);

			$query = new WP_Query( $args );
			$output = '';
			global $post;
			while( $query->have_posts() ) : $query->the_post();
				$id = $post->ID;
				$title = get_the_title();
				$selected = ( $html_args['selected'] == $id ) ? 'selected' : '';
				$output .= "<option value='$id'$selected>$title</option>";
			endwhile;
			
			return "<select name='{$html_args['name']}' id='{$html_args['id']}'>$output</select>";
			
		}

	} // End class

} // End if

?>
