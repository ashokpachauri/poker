<?php
// addon hook for before sidebar
$sidebar = $addons->get_hooks(array(), array(
	'page'     => 'general',
	'location'  => 'leftbar_before'
));

$sidebar .= ops_main_menu();

// addon hook for after sidebar
$sidebar .= $addons->get_hooks(array(), array(
	'page'     => 'general',
	'location'  => 'leftbar_after'
));

$opsTheme->addVariable('sidebar', $sidebar);
?>