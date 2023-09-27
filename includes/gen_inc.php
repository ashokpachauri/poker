<?php
session_start();
define('OPS_DEBUG', false);

if (isset($_GET['debug']) || OPS_DEBUG === true)
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

require 'configure.php';
require 'connect.php';

require 'tables.php';
require '3rd/vendor/autoload.php';

require 'settings.php';

$plyrname  = (isset($_SESSION['playername'])) ? addslashes($_SESSION['playername']) : '';
$SGUID     = (isset($_SESSION['SGUID']))      ? addslashes($_SESSION['SGUID'])      : '';

/* THEME */
$themeCFN = 'Theme.class.php';
$themeCF  = $themeCFN;
if (!file_exists($themeCF)) $themeCF = 'includes/' . $themeCF;
if (!file_exists($themeCF)) $themeCF = '../' . $themeCF;
if (!file_exists($themeCF)) die();
require($themeCF);
/* THEME */

/* ADDON */
$addonClassFileName = 'Addon.class.php';
$addonClassFile     = $addonClassFileName;

if (!file_exists($addonClassFile))
    $addonClassFile = 'includes/' . $addonClassFile;
    
if (!file_exists($addonClassFile))
    $addonClassFile = '../' . $addonClassFile;
    
if (!file_exists($addonClassFile))
    die();

require($addonClassFile);
$addonDir      = str_replace($addonClassFileName, '', $addonClassFile) . 'addons';
$addonSettings = array();
$addons        = new \OPSAddon();
require($addonDir . '/autoloader.php');

echo $addons->get_hooks(array(), array(
    'page'     => 'includes/gen_inc.php',
    'location'  => 'start'
));

require 'poker_inc.php';
/* ADDON */

$valid     = false;
$ADMIN     = false;
$gID       = '';

$opsTheme->addVariable('is_admin', 0);
$opsTheme->addVariable('is_logged', 0);

if ($plyrname != '' && $SGUID != '')
{
    $idq    = $pdo->prepare("select * from " . DB_PLAYERS . " where username = '" . $plyrname . "' and GUID = '" . $SGUID . "' ");
    $idq->execute();

    if ($idq->rowCount())
    {
        $idr    = $idq->fetch(PDO::FETCH_ASSOC);
        
        $gID    = $idr['gID'];
        $pID    = $idr['ID'];
        $gameID = $idr['vID'];

        $idr['avatar_url'] = 'images/avatars/' . $idr['avatar'];

        $opsTheme->addVariable('user', $idr);

        if ($idq->rowCount() == 1 && $idr['banned'] != 1)
        {
            $valid = true;
        }

        $siteadmin    = ADMIN_USERS;
        $sitecurrency = MONEY_PREFIX;

        if ($plyrname != '')
        {
            $time     = time();
            $admins   = array();
            $adminraw = explode(',', $siteadmin);
            $i        = 0;

            foreach ($adminraw as $i => $value)
            {
                $admins[$i] = trim($value);
            }

            if (in_array($plyrname, $admins))
            {
                $ADMIN = true;
            }

            $getstats = $pdo->prepare("select * from ".DB_STATS." where player = '{$plyrname}' ");
            $getstats->execute();
            $usestats = $getstats->fetch(PDO::FETCH_ASSOC);

            $current_chipcount = $usestats['winpot'];
            $current_money = money($usestats['winpot']);

            $opsTheme->addVariable('current_chipcount', $current_chipcount);
            $opsTheme->addVariable('current_money',     $current_money);
            $opsTheme->addVariable('username', $plyrname);
            $opsTheme->addVariable('is_logged', 1);
            
            $opsTheme->addVariable('sitecurrency', $sitecurrency);
        }
    }
}
else
{
    $opsTheme->addVariable('user', array(
        'avatar_url' => 'images/avatars/avatar.jpg',
        'username' => 'guest'
    ));
    $opsTheme->addVariable('current_money', '0');
}


if (isset($_SESSION[SESSNAME]) && $_SESSION[SESSNAME] != '' && MEMMOD == 1 && $plyrname == '')
{
    $time     = time();
    $sessname = addslashes($_SESSION[SESSNAME]);
    $usrq     = $pdo->prepare("select username from " . DB_PLAYERS . " where sessname = '" . $sessname . "' "); $usrq->execute();

    if ($usrq->rowCount() == 1)
    {
        $usrr = $usrq->fetch(PDO::FETCH_ASSOC);
        $usr  = $usrr['username'];
        $GUID = randomcode(32);

        $_SESSION['playername'] = $usr;
        $_SESSION['SGUID'] = $GUID;

        $ip     = $_SERVER['REMOTE_ADDR'];
        $result = $pdo->exec("update " . DB_PLAYERS . " set ipaddress = '" . $ip . "', lastlogin = " . $time . " , GUID = '" . $GUID . "' where username = '" . $usr . "' ");
        $valid  = true;
    } 
}

// Language
if ($plyrname != '' && isset($_GET['lang']))
{
    $lang = preg_replace('/[^A-Za-z0-9]/i', '', $_GET['lang']);

    if ( file_exists( BASEDIR . '/languages/' . $lang . '.po' ) )
    {
        $pdo->query("UPDATE " . DB_PLAYERS . " SET userlang = '$lang' WHERE username = '{$plyrname}'");
        header("Location: ?");
    }
}

$language      		= (isset($idr['userlang']) && !empty($idr['userlang'])) ? $idr['userlang'] : DEFLANG;
$languageName  		= $languages[$language];
$languagesHtml 		= '';

$opsTheme->addVariable('language', array(
    'name' => $languageName
));

foreach( $languages as $lang_id => $lang_text )
{
    $contentFile = ($lang_id === $language) ? 'lang-each-active' : 'lang-each';

    $opsTheme->addVariable('lng', array(
        'id'   => $lang_id,
        'name' => $lang_text,
        'url'  => '?lang=' . $lang_id,
    ));

    $languagesHtml .= $opsTheme->viewPart($contentFile);
}

$opsTheme->addVariable('languages', array(
    'html' => $languagesHtml
));

require 'language.php';

$time     = time();
$tq       = $pdo->prepare("SELECT waitimer FROM " . DB_PLAYERS . " WHERE username = ?");
$tq->execute(array($plyrname));

if ( $tq->rowCount() )
{
    $tr       = $tq->fetch(PDO::FETCH_ASSOC);
    $waitimer = $tr['waitimer'];
}

/*if ($waitimer > $time)
{
    header('Location sitout.php');
}*/

$tableTypes = $addons->get_hooks(
    array(
        'content' => array(
            array( 'value' => 'c', 'label' => CASHGAMES ),
            array( 'value' => 's', 'label' => SITNGO ),
            array( 'value' => 't', 'label' => TOURNAMENT )
        )
    ),
    array(
        'page'     => 'general',
        'location'  => 'table_types'
    )
);
$tournamentTypes = $addons->get_hooks(
    array(
        'content' => array(
            array( 'value' => 'r', 'label' => __( 'Regular', 'core' ) )
        )
    ),
    array(
        'page'     => 'general',
        'location'  => 'tournament_types'
    )
);

define( 'VALID',  isset($valid) ? $valid : false );
define( 'ADMIN',  isset($ADMIN) ? $ADMIN : false );
define( 'GAMEID', isset($gID) ? $gID : 0 );
define( 'GUID',   isset($GUID) ? $GUID : 0 );
?>