<?php
require('includes/inc_rankings.php');

echo $addons->get_hooks(array(),
	array(
		'page'     => 'general',
		'location' => 'html_start'
	)
);

$addons->get_hooks(array(), array(

    'page'     => 'rankings.php',
    'location'  => 'page_start'

));

$leaderboard = ops_ranking();

$opsTheme->addVariable('ranklist',   $leaderboard['content']);
$opsTheme->addVariable('pagination', $leaderboard['pagination']);

include 'templates/header.php';

echo $addons->get_hooks(array(), array(
    'page'     => 'rankings.php',
    'location'  => 'html_start'
));

echo $opsTheme->viewPage('rankings');

echo $addons->get_hooks(array(), array(

    'page'     => 'rankings.php',
    'location'  => 'html_end'

));

include 'templates/footer.php';
?>