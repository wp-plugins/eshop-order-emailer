<?php

echo 	"
	<div id='{$plugin_name}_wrapper'>
	<img id='{$plugin_name}_icon' src='{$plugin_url}/img/mailbox.jpg'/>
	<h2 id='{$plugin_name}_title'>{$plugin_display_name}</h2>
	<br style='clear:left'/>
	";

require_once( "{$plugin_folder}/views/{$this->controller}/{$this->method}.php" );

echo "</div>";

?>
