<?php
define('BASEDIR', __DIR__);
$pagename = (isset($_GET['pagename'])) ? $_GET['pagename'] : 'index';

if (! in_array($pagename, array('approve', 'logout')))
	require('includes/gen_inc.php');

$frontpage = "frontpages/{$pagename}.php";

if (file_exists($frontpage))
	include $frontpage;
else
{
	echo $addons->get_hooks(
		array(
			'page' => $pagename
		),
		array(
			'page'     => 'general',
			'location'  => 'frontpage'
		)
	);
}

die();
?>