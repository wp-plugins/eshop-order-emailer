<?php

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

?>

<form method="POST" action="">
<fieldset class="eordem_fieldset">
<legend class="eordem_legend"><h3>SETUP</h3></legend>
<p>This plugin will email a CSV report of your successful eShop orders each day. Separate multiple email addresses with a comma.</p>
<br/>
<?php

$html = '<table class="widefat" style="width:60%"><thead><tr><th>Use?</th><th>DB Name</th><th>Display Name</th></thead><tbody>';
foreach( $eshop_fields as $f ) {
	unset( $checked );
	unset( $display );
	unset( $name );
	if ( $fields[$f] ) {
		extract( $fields[$f] );
		$ticked = ( isset( $checked ) ) ? "checked='checked'" : "";
		$html .= "<tr><td><input type='checkbox' name='fields[$f][checked]' $ticked/></td><td><strong>$f:</strong></td><td><input type='input' name='fields[$f][display]' value='$display'/></td></tr>";
	} else {
		$html .= "<tr><td><input type='checkbox' name='fields[$f][checked]'/></td><td><strong>$f:</strong></td><td><input type='input' name='fields[$f][display]' value=''/></td></tr>";
	}
}

$html .= "</tbody></table>";

echo $html;
?>
<br/>
<br/>
<table class="widefat" style="width:60%"><thead><tr><th colspan="2">Email Settings</th></tr></thead><tbody>


<tr>
<th><label><strong>Recipients:</strong></label></th><td><input name="recipients" type="input" value="<?php echo $recipients; ?>"/><br/></td>
</tr>
<tr>
<th><label><strong>Hour Of Day:</strong></label></th><td>
	<select name="hour_of_day">
		<?php echo $hour_options;	?>
	</select>
</td>
<tr/>
<th><label><strong>Current Time:</strong></label></th>
<td><?php echo date( 'H:i:s d M Y', strtotime( current_time('mysql') ) );	?></td>
<tr/>

</tbody>
</table>

<div>
<p class='submit'>
<input type="submit" value="Save"/>
<input type="submit" name="reset" value="Reset"/>
<input type="submit" name="sendnow" value="Send Now"/>
</p>
</fieldset>
</form>

<ul>
<li><strong>Save:</strong> Store your settings and schedule the emailer to run each day.</li>
<li><strong>Reset:</strong> The plugin remembers each order that has been emailed and will never send an order twice.  This button allows you to reset the memory and start again from the beginning (every order ever completed will be included in the next email!)</li>
<li><strong>Send Now:</strong> All the completed orders that haven't been sent will be sent immediately instead of waiting for the next scheduled run.  After pressing reset, this will send every order ever completed (potentially several years worth, so be careful).</li>
<li><strong>Before/After fields:</strong> These fields can be used to add extra columns with default values before and after the eShop columns in the spreadsheet.  The correct format is:<blockquote>Column One=Value One,This is the heading=This is the value,Another heading=another value</blockquote>Just cut and paste the above line into the before field to see what it does.</li>
<li><strong>Reliability:</strong> Wordpress Cron is not reliable and there's no guaratee you'll receive your spreadsheet at the time you specify.  If you want reliable service, please consider <a href="http://codecanyon.net/item/improved-cron/176543?ref=paulswebsolutions"/>Improved Cron</a></li>
</ul>
</div>
