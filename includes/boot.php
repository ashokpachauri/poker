<?php
define('OPS_DEBUG', false);

if (OPS_DEBUG === true)
{
    ini_set('display_errors', true);
    ini_set("log_errors", 1);
    ini_set("error_log", __DIR__ . "/../php-error.log");
    error_reporting(E_ALL);
}
else
{
    ini_set('display_errors', false);
    error_reporting(0);
}

require __DIR__ . '/configure.php';
require __DIR__ . '/connect.php';

require __DIR__ . '/tables.php';
require __DIR__ . '/3rd/vendor/autoload.php';

require __DIR__ . '/settings.php';

/* THEME */
if (! isset($opsTheme))
	require __DIR__ . '/Theme.class.php';
/* THEME */


if (! isset($addons))
{
	require __DIR__ . '/Addon.class.php';

	$addonSettings = array();
	$addons        = new \OPSAddon();

	require __DIR__ . '/addons/autoloader.php';

	echo $addons->get_hooks(array(), array(
		'page'     => 'boot',
		'location' => 'start'
	));
}

require __DIR__ . '/poker_inc.php';
require __DIR__ . '/language.php';
