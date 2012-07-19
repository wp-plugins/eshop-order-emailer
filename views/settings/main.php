<?php

extract( $this->pluginData['constants'] );
extract( $this->pluginData['plugin_options'] );

$twelve = array( 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 );
$seconds = 0;
foreach( array( 'am', 'pm' ) as $ampm ) {
	foreach( $twelve as $hour ) {
		if ( $seconds == $hour_of_day ) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}
		$text = $hour . ' ' . $ampm;
		$hour_options .= "<option value='$seconds' $selected>$text</option>";
		$seconds += 3600; // Add another hour
	}
}

$seconds = 0;
foreach( array( 'am', 'pm' ) as $ampm ) {
	foreach( $twelve as $hour ) {
		if ( $seconds == $summary_hour_of_day ) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}
		$text = $hour . ' ' . $ampm;
		$summary_hour_options .= "<option value='$seconds' $selected>$text</option>";
		$seconds += 3600; // Add another hour
	}
}

if ( $_SERVER['HTTP_HOST'] == '10.1.1.3' ) {
	$frequencies = Array(
		'Instant' => 'instant',
		'Every 2 Mins (for testing)' => 'two_minutes',
		'Hourly' => 'hourly',
		'Every 2 Hours' => 'two_hours',
	);
}

$frequencies['Instant'] = 'instant';
$frequencies['Every 4 Hours'] = 'four_hours';
$frequencies['Every 12 Hours'] = 'twicedaily';
$frequencies['Every 24 Hours'] = 'daily';

foreach( $frequencies as $label => $value ) {
	$selected = ( $value == $frequency ) ? 'selected' : '';
	$frequency_options .= "<option value='$value' $selected>$label</option>";
}

$summary_frequencies = $frequencies;

foreach( $summary_frequencies as $label => $value ) {
	$selected = ( $value == $summary_frequency ) ? 'selected' : '';
	$summary_frequency_options .= "<option value='$value' $selected>$label</option>";
}

$current_time = date( 'H:i:s d M Y', strtotime( current_time( 'mysql' ) ) );

$field_options = '';
foreach( $this->eshop_fields as $f ) {
	$nice = preg_replace( '/_/', ' ', $f );
	$nice = ucwords( $nice );
	$fields_options .= "<option value='$f'>$nice</option>";
}

$json_data = Array( );
if ( is_array( $csv_fields ) ) {
	for ( $i=0; $i<count( $csv_fields ); $i++ ) {
		extract( $csv_fields[$i] );
		$json_data[] = Array( 'name' => $name, 'text' => $text );
	}
}
$json_data = json_encode( $json_data );

$html = "
	<script type='text/javascript'>
var rows = Array( );
rows = $json_data;
	</script>
	<h3>Settings</h3>
	<form method='POST' action='#'>
	<fieldset>
		<table class='widefat' style='width:80%'>
			<thead>
				<tr><th colspan='2'>Email Settings</th></tr>
			</thead>
			<tbody>
				<tr>
					<th><label><strong>Recipients:</strong></label></th>
					<td><input name='{$options_key}[recipients]' type='input' value='$recipients'/><br/></td>
				</tr>
				<tr>
					<th><label><strong>Frequency:</strong></label></th>
					<td>
						<select name='{$options_key}[frequency]'>
							$frequency_options
						</select>
					</td>
				<tr/>
				<tr>
					<th><label><strong>Start At:</strong></label></th>
					<td>
						<select name='{$options_key}[hour_of_day]'>
							$hour_options
						</select>
					</td>
				<tr/>
				<tr>
					<th><label><strong>Summary Frequency:</strong></label></th>
					<td>
						<select name='{$options_key}[summary_frequency]'>
							$summary_frequency_options
						</select>
					</td>
				<tr/>
				<tr>
					<th><label><strong>Summary Start At:</strong></label></th>
					<td>
						<select name='{$options_key}[summary_hour_of_day]'>
							$summary_hour_options
						</select>
					</td>
				<tr/>
				<tr>
					<th><label><strong>Current Time:</strong></label></th>
					<td>$current_time</td>
				<tr/>
			</tbody>
		</table>
		<br/>
		<table class='widefat' style='width:80%'>
			<thead>
				<tr><th colspan='3'>Report Settings</th></tr>
			</thead>
			<tbody>
			<tr>
				<td><strong>Field Name</strong></td>
				<td><strong>Display Text</strong></td>
				<td><strong>Actions</strong></td>
			</tr>
			<tr>
				<td><select id='eordem_settings_field_select'>$fields_options</select></td>
				<td><input type='text' id='eordem_settings_display_text'/></td>
				<td><input type='button' id='eordem_settings_add_button' value='Add'/></td>
			</tr>
			$report_html
			</tbody>
			<tbody id='eordem_settings_report_tbody'>
			</tbody>
		</table>
		
			<p>
				<input type='submit' value='Save'/>
				<input type='submit' name='reset' value='Reset'/>
				<input type='submit' name='sendnow' value='Send Now'/>
			</p>
	</fieldset>
	</form>
	";
echo $html;
?>
