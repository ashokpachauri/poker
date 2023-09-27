<?php
$confs              = array();
$confQ = $pdo->query("SELECT setting, Xkey, Xvalue FROM " . DB_SETTINGS);
while ($confF = $confQ->fetch(PDO::FETCH_ASSOC))
{
    $confs[$confF['setting']] = $confF['Xvalue'];
    define($confF['Xkey'], stripslashes($confF['Xvalue']));
}

// Adjust stakes according to admin settings
$smallbetfunc = 0;

if (STAKESIZE == 'tiny')
{
	$smallbetfunc = 1;
}

if (STAKESIZE == 'low')
{
	$smallbetfunc = 2;
}

if (STAKESIZE == 'med')
{
	$smallbetfunc = 3;
}

define( 'SMALLBETFUNC', $smallbetfunc );


//
define ('HAND_DEAL_CARDS', 4);

define ('HAND_INTERPLAY_START', 5);
define ('HAND_INTERPLAY_END', 11);

define ('HAND_STRADDLE_START', 25);
define ('HAND_STRADDLE_END', 29);
?>