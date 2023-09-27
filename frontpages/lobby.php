<?php
require('includes/inc_lobby.php');

$addons->get_hooks(array(), array(
    'page'     => 'lobby.php',
    'location'  => 'page_start'
));

include 'templates/header.php';

echo $addons->get_hooks(array(),
	array(
		'page'     => 'general',
		'location' => 'html_start'
	)
);

echo $addons->get_hooks(array(), array(
    'page'     => 'lobby.php',
    'location'  => 'html_start'
));

$addons->get_hooks(array(), array(
    'page'     => 'lobby.php',
    'location'  => 'tabs_left'
));
add_lobby_tab('tables', 'All', $opsTheme->viewPart('lobby-gamelist-tabpanel'));
add_lobby_tab('tournamentsc', 'Tournaments', $opsTheme->viewPart('lobby-tournaments-tabpanel'));
$addons->get_hooks(array(), array(
    'page'     => 'lobby.php',
    'location'  => 'tabs_right'
));

$navHtml      = '';
$tabpanelHtml = '';

foreach ($lobbyNavs as $lNavId => $lNav)
{
	$opsTheme->addVariable('tab', array(
		'id'   => $lNavId,
		'name' => $lNav['name'],
		'html' => $lNav['html']
	));

	$navHtml .= $opsTheme->viewPart('lobby-tab');
	$tabpanelHtml .= $opsTheme->viewPart('lobby-tabpanel');
}

$opsTheme->addVariable('lobby', array(
	'tabs'      => $navHtml,
	'tabpanels' => $tabpanelHtml
));

echo $opsTheme->viewPage('lobby');

echo $addons->get_hooks(array(), array(
    'page'     => 'lobby.php',
    'location'  => 'html_end'
));

include 'templates/footer.php';

/* Functions */
function add_lobby_tab($id, $name, $html)
{
	if (! isset($GLOBALS['lobbyNavs']))
		$GLOBALS['lobbyNavs'] = array();

	global $lobbyNavs, $opsTheme;

	$opsTheme->addVariable('tab', array(
		'id'   => $id,
		'name' => $name,
		'html' => $html
	));

	$lobbyNavs[$id] = array(
		'name' => $name,
		'html' => $html
	);

	return true;
}
?>
