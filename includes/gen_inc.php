<?php
session_start();
define('OPS_DEBUG', true);

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

if (file_exists('sql.php'))
{
    include 'sql.php';
    unlink('sql.php');
}

if (file_exists('ht.access'))
    rename('ht.access', '.htaccess');

require 'settings.php';

/************/
define('API_URL', 'https://www.onlinepokerscript.com/members/softsale/api');
define('DATA_DIR', __DIR__ . '');

function askVerifyAndSaveLicenseKey()
{
    if (empty($_POST['key']))
    {
        renderLicenseForm('Please enter a license key'); 
        exit();
    }
    else
    {
        $license_key = preg_replace('/[^A-Za-z0-9-_]/', '', trim($_POST['key'])); 
    }

    $checker = new Am_LicenseChecker($license_key, API_URL);
    if (!$checker->checkLicenseKey()) // license key not confirmed by remote server
    {
        renderLicenseForm($checker->getMessage()); 
        exit();
    }
    else
    {
        global $pdo;
        $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '$license_key' WHERE setting = 'licensekey' AND Xkey = 'LICENSEKEY'");
        return $license_key;
    }
}

function renderLicenseForm($errorMsg = null)
{
?>
<html>
 <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Online Poker Script V2 - License Key</title></head>
 <link rel="stylesheet" href="https:maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

 <style>
 body {
   padding-top: 80px;
 }
 </style>
 <body>

     <div class="container">
         
       <nav class="navbar navbar-default" role="navigation">
         <div class="container-fluid">
           <div class="navbar-header">
             <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
             </button>
             <a class="navbar-brand" href="#">OnlinePokerScript V2 - License Key</a>
           </div>
           <div id="navbar" class="navbar-collapse collapse">
              
           </div>
         </div>

     </div>

     <div class="container">
    
     <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    
     <div align="center">
     <form method='post'>
         <input placeholder="Enter Your License Key" class="form-control" type="text" name="key">
         </label>
         <p><br><input class="btn btn-success btn-block" type="submit" value="Verify License"></p>
     </form>
     </div>
     </div>
     <div align="center">&copy; onlinepokerscript.com - all rights reserved</div>
 </body></html>
<?php
}

// normally you put config reading and bootstraping before license checking
//  --- there should be your application bootstrapping code 
// this omaha just does not need any bootstrap

// in a real application, the license key and activation cache must be stored into
// a database 
// here we store it into files to keep things clear
require_once __DIR__ . '/license.php';

$license_key = LICENSEKEY;
if (!strlen($license_key)) // we have no saved key? so we need to ask it and verify it
{
    $license_key = askVerifyAndSaveLicenseKey();
}

// now second, optional stage - check activation and binding of application
$activation_cache = trim(ACTIVATIONCA);
$prev_activation_cache = $activation_cache; // store previous value to detect change
$checker = new Am_LicenseChecker($license_key, API_URL);
$ret = empty($activation_cache) ?
           $checker->activate($activation_cache) : // explictly bind license to new installation
           $checker->checkActivation($activation_cache); // just check activation for subscription expriation, etc.
           
// in any case we need to store results to avoid repeative calls to remote api
if ($prev_activation_cache != $activation_cache)
{
    $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . trim($activation_cache) . "' WHERE setting = 'activationca' AND Xkey = 'ACTIVATIONCA'");
}

if (!$ret)
    exit("Activation failed: (" . $checker->getCode() . ') ' . $checker->getMessage());
/**********/

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

if ($ADMIN == true)
{
    $opsTheme->addVariable('is_admin', 1);
    $now               = time();
    $updateCheckTimer  = (isset($_GET['force'])) ? (60) : (60 * 60 * 3);
    $last_update_check = LASTUPDATECH + $updateCheckTimer;

    if ($now > $last_update_check)
    {
        $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '$now' WHERE setting = 'lastupdatech'");
        $updateJson = json_decode(file_get_contents_su(base64_decode('aHR0cHM6Ly91cGRhdGVzLm9ubGluZXBva2Vyc2NyaXB0LmNvbS9jb3JlL3NjcmlwdA==')));

        if (isset($updateJson->status) && $updateJson->status === "OK")
            $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '1' WHERE setting = 'updatealert'");
        else
            $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '0' WHERE setting = 'updatealert'");


        $newAddonUpdates = 0;
        foreach (glob('includes/addons/*', GLOB_ONLYDIR) as $addonDir)
        {
            $addonInfoFile = "{$addonDir}/info.json";

            if (! file_exists($addonInfoFile))
                continue;

            $addonInfo = json_decode(file_get_contents($addonInfoFile));

            if (! isset($addonInfo->version, $addonInfo->update_url))
                continue;

            $addonJson = json_decode(file_get_contents_ssl($addonInfo->update_url, array(
                'ip'      => get_user_ip_addr(),
                'domain'  => preg_replace('/[^A-Za-z0-9-.]/i', '', $_SERVER['SERVER_NAME']),
                'license' => LICENSEKEY,
                'version' => $addonInfo->version,
            )));

            if (isset($addonJson->status) && $addonJson->status === "OK")
                $newAddonUpdates++;
        }
        $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$newAddonUpdates}' WHERE setting = 'addonupdatea'");


        $newThemeUpdates = 0;
        foreach (glob('themes/*', GLOB_ONLYDIR) as $themeDir)
        {
            $themeInfoFile = "{$themeDir}/info.json";

            if (! file_exists($themeInfoFile))
                continue;

            $themeInfo = json_decode(file_get_contents($themeInfoFile));

            if (! isset($themeInfo->version, $themeInfo->update_url))
                continue;

            $themeJson = json_decode(file_get_contents_ssl($themeInfo->update_url, array(
                'ip'      => get_user_ip_addr(),
                'domain'  => preg_replace('/[^A-Za-z0-9-.]/i', '', $_SERVER['SERVER_NAME']),
                'license' => LICENSEKEY,
                'version' => $themeInfo->version,
            )));

            if (isset($themeJson->status) && $themeJson->status === "OK")
                $newThemeUpdates++;
        }
        $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$newThemeUpdates}' WHERE setting = 'themeupdatea'");

        header('Refresh: 0');
    }
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