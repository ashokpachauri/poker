<?php
if ( LANDLOBBY == 1 )
{
	include __DIR__ . '/lobby.php';
	die();
}

require('includes/inc_index.php');

$addons->get_hooks(array(), array(
	'page'     => 'index.php',
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
	'page'     => 'index.php',
	'location'  => 'html_start'
));


include 'includes/scores.php';
$opsTheme->addVariable('scoreboard', $scoreboard);

// addon hook for index page content
$lhpc = $addons->get_hooks(
	array(
		'content' => $opsTheme->viewPart('logged-home-content')
	),
	array(
		'page'     => 'index.php',
		'location'  => 'main_content'
	)
);
$opsTheme->addVariable('logged_home_content', $lhpc);

echo $opsTheme->viewPage('logged-home');
echo $addons->get_hooks(array(), array(

	'page'     => 'index.php',
	'location'  => 'html_end'

));
include 'templates/footer.php';
?>