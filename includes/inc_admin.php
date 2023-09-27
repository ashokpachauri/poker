<?php
include_once 'MysqlBackup.class.php';

if ($valid == false)
{
    header('Location: login.php');
}

if ($ADMIN == false)
{
    header('Location: index.php');
}

$action = (isset($_POST['action'])) ? addslashes($_POST['action']) : '';


$addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/inc_admin.php',
        'location'  => 'admin_post'
    )
);


if (isset($_POST['player']))
    $usr = addslashes($_POST['player']);

if ( $action === 'deflang' )
{
    $deflang = preg_replace('/[^A-Za-z0-9_-]/i', '', $_POST['deflangp']);
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$deflang}' WHERE setting = 'deflang'");

    header('Location: ?admin=settings');
    die();
}

if ($action === 'lang-strings')
{
    $themeFolders = glob( BASEDIR . '/themes/*', GLOB_ONLYDIR );
    foreach ( $themeFolders as $themeFolder )
    {
        $phpCode   = '<?php' . "\n";
        $tHashes   = array();
        $themeName = basename($themeFolder);

        if ($themeName === 'settings')
            continue;

        $pagesFolder = $themeFolder . '/html/pages';
        $partsFolder = $themeFolder . '/html/parts';

        $pages = array_merge( glob( $pagesFolder . '/*.html' ), glob( $partsFolder . '/*.html' ) );

        foreach ( $pages as $page )
        {
            $content = file_get_contents($page);
            preg_match_all('/\{\_\_\((.*?)\)\}/', $content, $matches);
            
            foreach ( $matches[1] as $tString )
            {
                $tString = str_replace("'", "\'", $tString);

                if ( isset($tHashes[md5($tString)]) )
                    continue;
                
                $phpCode .= '__(\'' . $tString . '\', \'theme-' . $themeName . '\');' . "\n";
                $tHashes[ md5($tString) ] = 1;
            }
        }

        file_put_contents( $themeFolder . '/strings.php', $phpCode );
    }

    $addonFolders = glob( BASEDIR . '/includes/addons/*', GLOB_ONLYDIR );
    foreach ( $addonFolders as $addonFolder )
    {
        $phpCode   = '<?php' . "\n";
        $tHashes   = array();
        $addonSlug = basename($addonFolder);

        if ($addonSlug === 'settings')
            continue;

        if ( !file_exists( $addonFolder . '/theme' ) )
            continue;

        $pagesFolder = $addonFolder . '/theme/html/pages';
        $partsFolder = $addonFolder . '/theme/html/parts';

        $pages = array_merge( glob( $pagesFolder . '/*.html' ), glob( $partsFolder . '/*.html' ) );

        foreach ( $pages as $page )
        {
            $content = file_get_contents($page);
            preg_match_all('/\{\_\_\((.*?)\)\}/', $content, $matches);
            
            foreach ( $matches[1] as $tString )
            {
                $tString = str_replace("'", "\'", $tString);
                
                if ( isset($tHashes[md5($tString)]) )
                    continue;
                
                $phpCode .= '__(\'' . $tString . '\', \'addon-' . $addonSlug . '\');' . "\n";
                $tHashes[ md5($tString) ] = 1;
            }
        }

        file_put_contents( $addonFolder . '/theme/strings.php', $phpCode );
    }

    header('Location: ?admin=settings');
    die();
}

if ($action == 'createtable')
{
    $sql = 'INSERT INTO ' . DB_POKER . ' SET ';
    $updates = array();

    $skip = array('action', 'gameID', 'Submit');
    $skipnumbers = array('tablelow', 'pot', 'bet', 'lastmove');

    $addons->get_hooks(array(), array(
        'page'     => 'includes/inc_admin.php',
        'location'  => 'create_table_start'
    ));

    foreach ($_POST as $key => $value)
    {
        if (in_array( $key, $skip ))
        {
            continue;
        }

        if ($key === 'tablename')
            $value = str_replace(array('"', "'"), array('&quot;', '&apos;'), $value);

        if ($key == 'startdate')
        {
            $updates[] = $key . ' = TIMESTAMP("' . str_replace( 'T', ' ', $value ) . '")';
            continue;
        }
        
        if ( $key == 'sbamount' ) {
            $updates[] = $key . ' = ' . transfer_to($value);
            continue;
        }  
        
        if ( $key == 'bbamount' ) {
            $updates[] = $key . ' = ' . transfer_to($value);
            continue;
        }                

        if (in_array( $key, $skipnumbers))
        {
            $updates[] = $key . ' = ' . $value;
            continue;
        }

        $updates[] = $key . ' = "' . $value . '"';
    }

    $updates[] = "hand = -1";
    $updates[] = "lastupdated = " . time();
    $sql      .= implode(',', $updates);

    $result = $pdo->query( $sql );

    OPS_sitelog($plyrname, sprintf( __( "%s created a new table %s", 'core' ), $plyrname, str_replace(array('"', "'"), array('&quot;', '&apos;'), $_POST['tablename']) ) );

    $addons->get_hooks(
        array(
            'content' => $pdo->lastInsertId()
        ),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'after_create_table'
        )
    );
}

$delete = (isset($_GET['delete'])) ? addslashes($_GET['delete']) : '';

if (is_numeric($delete) && isset($delete))
{
    $result = $pdo->exec("delete from  " . DB_POKER . " where gameID = " . $delete);

    $result = $pdo->exec("delete from " . DB_LIVECHAT . " where gameID = " . $delete);

    $result = $pdo->exec("update " . DB_PLAYERS . " set vID = 0, gID = 0 where vID = " . $delete);
}

if ($action == 'install')
{
	$dir  = getcwd();
    $path = $dir . "/images/tablelayout/";
    $ext  = pathinfo($_FILES['uploaded_file']['name'], PATHINFO_EXTENSION);
    $nam  = basename( $_FILES['uploaded_file']['name'], '.png' );
    $path = $path . basename( $_FILES['uploaded_file']['name']);

    if ($ext === 'png')
    {
        if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $path))
        {
            $msg = "<div class='alert alert-success'>" . sprintf( __( 'The style %s has been added!', 'core' ), basename( $_FILES['uploaded_file']['name'], '.png') ) . "</div>";
        }
        else
        {
            $msg = "<div class='alert alert-danger'>" . __( 'There was an error, please try again!', 'core' ) . "</div>";
        }

        $lic = (isset($_POST['lic'])) ? addslashes($_POST['lic']) : '';
        $sq  = $pdo->prepare("select style_name from styles where style_name = '" . $nam . "'");
        $sq->execute();

        if ($sq->rowCount() > 0)
        {
            $msg = ADMIN_MSG_STYLE_INSTALLED;
        }
        elseif ($nam == '' || $lic == '')
        {
            $msg = ADMIN_MSG_MISSING_DATA;
        }
        else
        {
            $result = $pdo->exec("insert into " . DB_STYLES . " set style_name = '$nam', style_lic = '$lic'");
            OPS_sitelog($plyrname, sprintf( __( "%s added a new table style.", 'core' ), $plyrname ));
            header('Location: admin.php?admin=styles&success=true');
            die();
        }
    }
}

if (!isset($usr))
{    
    $usr = (isset($_GET['delete']))  ? addslashes($_GET['delete']) : '';
}

if ($usr != '' && !is_numeric($usr))
{
    $uq = $pdo->query("select email from " . DB_PLAYERS . " where username = '" . $usr . "'");

    if ($uq->rowCount()):
        $ur = $uq->fetch(PDO::FETCH_ASSOC);
        $em = $ur['email'];
    endif;

    if ($action == 'ban' && isset($em))
    {
        if ($em != '')
            $result = $pdo->exec("update " . DB_PLAYERS . " set banned = '1' where email = '" . $em . "'");
        $result = $pdo->exec("update " . DB_PLAYERS . " set banned = '1' where username = '" . $usr . "'");

        OPS_sitelog($plyrname, sprintf( __( "%s banned user %s.", 'core' ), $plyrname, $usr ));
    }
    elseif ($action == 'unban' && isset($em))
    {
        if ($em != '')
            $result = $pdo->exec("update " . DB_PLAYERS . " set banned = 0 where email = '" . $em . "'");
        $result = $pdo->exec("update " . DB_PLAYERS . " set banned = 0 where username = '" . $usr . "'");

        OPS_sitelog($plyrname, sprintf( __( "%s unbanned user %s.", 'core' ), $plyrname, $usr ));
    }
    elseif ($action == 'reset')
    {
        $result = $pdo->exec("UPDATE " . DB_STATS . " SET winpot = 0, rank = '', gamesplayed = 0, tournamentswon = 0, tournamentsplayed = 0, handsplayed = 0, handswon = 0, bet = 0, checked = 0, called = '0', allin = '0', fold_pf = '0', fold_f = '0', fold_t = '0', fold_r = 0 WHERE player = '" . $usr . "'");

        OPS_sitelog($plyrname, sprintf( __( "%s reset the stats for user %s.", 'core' ), $plyrname, $usr ));
    }
    elseif ($action == 'approve')
    {
        $result = $pdo->exec("update " . DB_PLAYERS . " set approve = 0 where username = '" . $usr . "'");
        OPS_sitelog($plyrname, sprintf( __( "%s approved user %s.", 'core' ), $plyrname, $usr ));
    }
    elseif ($action == 'delete')
    {
        $result = $pdo->exec("delete from " . DB_PLAYERS . " where username = '" . $usr . "'");
        $result = $pdo->exec("delete from  " . DB_STATS . " where player = '" . $usr . "'");

        OPS_sitelog($plyrname, sprintf( __( "%s deleted user %s.", 'core' ), $plyrname, $usr ));

        if (file_exists('images/avatars/' . $usr . '.jpg'))
        {
            unlink('images/avatars/' . $usr . '.jpg');
        }
    }
}

if ($action == 'update')
{
    $title = (isset($_POST['title'])) ? addslashes($_POST['title']) : '';
    if ($title == '')
    {
        $title = __( 'Texas Holdem Poker', 'core' );
    }

    $landlobby = (isset($_POST['landlobby'])) ? addslashes($_POST['landlobby']) : 0;
    if ($landlobby != 1)
    {
        $landlobby = 0;
    }

    $emailmode = (isset($_POST['emailmode'])) ? addslashes($_POST['emailmode']) : '';
    if ($emailmode != 1)
    {
        $emailmode = 0;
    }

    $ipcheck = (isset($_POST['ipcheck'])) ? addslashes($_POST['ipcheck']) : '';
    if ($ipcheck != 0)
    {
        $ipcheck = 1;
    }

    $appmode = (isset($_POST['appmode'])) ? addslashes($_POST['appmode']) : '';
    if (($appmode != 1) && ($appmode != 2))
    {
        $appmode = 0;
    }

    if ($appmode == 1)
    {
        $emailmode = 1;
    }

    $memmode   = (isset($_POST['memmode'])) ? addslashes($_POST['memmode']) : '';

    $deletearray = array(
        30,
        60,
        90,
        180,
        'never'
    );
    $delete      = (isset($_POST['delete'])) ? addslashes($_POST['delete']) : '';

    if (!in_array($delete, $deletearray))
        $delete = 90;

    $alwaysfoldarray = array(
        'yes',
        'no'
    );
    $alwaysfold      = (isset($_POST['alwaysfold'])) ? addslashes($_POST['alwaysfold']) : '';

    if (!in_array($alwaysfold, $alwaysfoldarray))
        $alwaysfold = 'no';
    
    
    $isstraddlearray = array(
        'yes',
        'no'
    );
    $isstraddle      = (isset($_POST['isstraddle'])) ? addslashes($_POST['isstraddle']) : '';

    if (!in_array($isstraddle, $isstraddlearray))
        $isstraddle = 'no';
        
    
    $usewebsockets      = (isset($_POST['usewebsockets'])) ? intval($_POST['usewebsockets']) : 0;
    $websocketAddr      = (isset($_POST['websocket_addr'])) ? $_POST['websocket_addr'] : '';
    $websocketPort      = (isset($_POST['websocket_port'])) ? preg_replace('/[^0-9]/', '', $_POST['websocket_port']) : '';
    

    $sess = (isset($_POST['session']))   ? addslashes($_POST['session'])   : '';

    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $title . "' WHERE setting = 'title'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $appmode . "' WHERE setting = 'appmod'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $emailmode . "' WHERE setting = 'emailmod'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $landlobby . "' WHERE setting = 'landlobby'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $ipcheck . "' WHERE setting = 'ipcheck'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $memmode . "' WHERE setting = 'memmod'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $delete . "' WHERE setting = 'deletetimer'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $alwaysfold . "' WHERE setting = 'alwaysfold'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $isstraddle . "' WHERE setting = 'isstraddle'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $usewebsockets . "' WHERE setting = 'usewebsockets'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $websocketAddr . "' WHERE setting = 'websocket_addr'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $websocketPort . "' WHERE setting = 'websocket_port'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $sess . "' WHERE setting = 'session'");

    $pdo->query("UPDATE " . DB_POKER . " SET lastupdated = " . time());
    
    OPS_sitelog($plyrname, sprintf( __( "%s updated Basic settings.", 'core' ), $plyrname ));

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'settings_update_basic'
        )
    );

    header('Location: admin.php?admin=settings&ud=1');
}

if ($action == 'update2')
{
    $kickarray = array(
        3,
        5,
        7,
        10,
        15
    );
    $kick      = (isset($_POST['kick'])) ? addslashes($_POST['kick']) : '';

    if ( !in_array($kick, $kickarray) )
    {
        $kick = 5;
    }

    $movearray = array(
        10,
        15,
        20,
        27
    );
    $move      = (isset($_POST['move'])) ? addslashes($_POST['move']) : '';

    if ( !in_array($move, $movearray) )
    {
        $move = 20;
    }

    $showdownarray = array(
        3,
        4,
        5,
        7,
        10
    );
    $showdown      = (isset($_POST['showdown'])) ? addslashes($_POST['showdown']) : '';

    if ( !in_array($showdown, $showdownarray) )
    {
        $showdown = 7;
    }

    $waitarray = array(
        0,
        10,
        15,
        20,
        25
    );
    $wait      = (isset($_POST['wait'])) ? addslashes($_POST['wait']) : '';

    if (!in_array($wait, $waitarray))
    {
        $wait = 20;
    }

    $disconarray = array(
        15,
        30,
        60,
        90,
        120
    );
    $discon      = (isset($_POST['disconnect'])) ? addslashes($_POST['disconnect']) : '';

    if (!in_array($discon, $disconarray))
    {
        $discon = 60;
    }

    $straddletimerarray = array(
        5,
        7,
        10,
        12,
        15,
        20,
        30
    );
    $straddletimer = (isset($_POST['straddletimer'])) ? addslashes($_POST['straddletimer']) : '';

    if (!in_array($straddletimer, $straddletimerarray))
    {
        $straddletimer = 5;
    }

    $raisebuttons = (isset($_POST['raisebuttons']) && is_array($_POST['raisebuttons'])) ? $_POST['raisebuttons'] : array();

    $tmrleftsound = (isset($_POST['tmrleftsound'])) ? $_POST['tmrleftsound'] : 'off';
    if (! in_array($tmrleftsound, array('on', 'off')))
        $tmrleftsound = 'off';
    
    $playbfrcards = (isset($_POST['playbfrcards'])) ? $_POST['playbfrcards'] : 'no';
    if (! in_array($playbfrcards, array('yes', 'no')))
        $playbfrcards = 'no';

    $sess      = (isset($_POST['session']))   ? addslashes($_POST['session'])   : '';

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $kick . "' WHERE setting = 'kicktimer'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $showdown . "' WHERE setting = 'showtimer'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $move . "' WHERE setting = 'movetimer'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $wait . "' WHERE setting = 'waitimer'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $sess . "' WHERE setting = 'session'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $discon . "' WHERE setting = 'disconnect'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $straddletimer . "' WHERE setting = 'straddletimr'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . json_encode($raisebuttons) . "' WHERE setting = 'raisebutton'");

    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $tmrleftsound . "' WHERE setting = 'tmrleftsound'");
    
    $result = $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $playbfrcards . "' WHERE setting = 'playbfrcards'");

    $pdo->query("UPDATE " . DB_POKER . " SET lastupdated = " . time());

    OPS_sitelog($plyrname, sprintf( __( "%s updated Detailed settings.", 'core' ), $plyrname ));

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'settings_update_detailed'
        )
    );

    header('Location: admin.php?admin=settings&ud=1');
}

if ($action == 'smtp')
{
    $smtp_on      = (isset($_POST['smtp_on']))      ? addslashes($_POST['smtp_on'])      : 'no';
    $smtp_host    = (isset($_POST['smtp_host']))    ? addslashes($_POST['smtp_host'])    : '';
    $smtp_port    = (isset($_POST['smtp_port']))    ? addslashes($_POST['smtp_port'])    : '';
    $smtp_encrypt = (isset($_POST['smtp_encrypt'])) ? addslashes($_POST['smtp_encrypt']) : 'none';
    $smtp_auth    = (isset($_POST['smtp_auth']))    ? addslashes($_POST['smtp_auth'])    : 'no';
    $smtp_user    = (isset($_POST['smtp_user']))    ? addslashes($_POST['smtp_user'])    : '';
    $smtp_pass    = (isset($_POST['smtp_pass']))    ? addslashes($_POST['smtp_pass'])    : '';

    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_on}' WHERE setting = 'smtp_on'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_host}' WHERE setting = 'smtp_host'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_port}' WHERE setting = 'smtp_port'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_encrypt}' WHERE setting = 'smtp_encrypt'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_auth}' WHERE setting = 'smtp_auth'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_user}' WHERE setting = 'smtp_user'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$smtp_pass}' WHERE setting = 'smtp_pass'");

    OPS_sitelog($plyrname, sprintf( __( "%s updated SMTP settings.", 'core' ), $plyrname ));

    $addons->get_hooks(array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'settings_update_smtp'
        )
    );
    header('Location: admin.php?admin=settings&ud=1');
}

if ($action == 'currency')
{
    $ssizearray = array(
        'tiny',
        'low',
        'med',
        'high'
    );
    $ssize = (isset($_POST['stakesize'])) ? addslashes($_POST['stakesize']) : '';

    if (! in_array($ssize, $ssizearray))
        $ssize = med;

    $renewbutton = (isset($_POST['renew'])) ? addslashes($_POST['renew']) : '';
    
    if ($renewbutton != 0)
        $renewbutton = 1;

    $money_prefix = (isset($_POST['money_prefix'])) ? addslashes($_POST['money_prefix']) : '$';
    $money_decima = (isset($_POST['money_decima'])) ? addslashes($_POST['money_decima']) : '.';
    $money_thousa = (isset($_POST['money_thousa'])) ? addslashes($_POST['money_thousa']) : '.';
    $admin_users  = (isset($_POST['admin_users']))  ? addslashes($_POST['admin_users'])  : 'admin';
    $reg_winpot   = (isset($_POST['reg_winpot']))   ? addslashes($_POST['reg_winpot'])   : '1000';

    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $ssize . "' WHERE setting = 'stakesize'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '" . $renewbutton . "' WHERE setting = 'renew'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$money_prefix}' WHERE setting = 'money_prefix'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$money_decima}' WHERE setting = 'money_decima'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$money_thousa}' WHERE setting = 'money_thousa'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$admin_users}' WHERE setting = 'admin_users'");
    $pdo->query("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$reg_winpot}' WHERE setting = 'reg_winpot'");

    OPS_sitelog($plyrname, sprintf( __( "%s updated Currency settings.", 'core' ), $plyrname ));

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location' => 'settings_update_currency'
        )
    );

    header('Location: admin.php?admin=settings&ud=1');
}

$adminview = (isset($_GET['admin'])) ? addslashes($_GET['admin']) : '';

if ( $action == 'edittable' )
{
    $sql = 'update ' . DB_POKER . ' SET ';
    $updates = array();

    $skip = array('action', 'gameID', 'tablegame', 'Submit');
    $skipnumbers = array('tablelow', 'pot', 'bet', 'lastmove');

    $addons->get_hooks(array(), array(
        'page'     => 'includes/inc_admin.php',
        'location'  => 'edit_table_start'
    ));

    foreach ($_POST as $key => $value)
    {
        if (in_array( $key, $skip ))
        {
            continue;
        }

        if ($key === 'tablename')
            $value = str_replace(array('"', "'"), array('&quot;', '&apos;'), $value);

        if ($key == 'startdate')
        {
            $updates[] = $key . ' = TIMESTAMP("' . str_replace( 'T', ' ', $value ) . '")';
            continue;
        }
        
        if ( $key == 'sbamount' ) {
            $updates[] = $key . ' = ' . transfer_to($value);
            continue;
        }  
        
        if ( $key == 'bbamount' ) {
            $updates[] = $key . ' = ' . transfer_to($value);
            continue;
        }                

        if (in_array( $key, $skipnumbers))
        {
            $updates[] = $key . ' = ' . $value;
            continue;
        }

        $updates[] = $key . ' = "' . $value . '"';
    }

    $updates[] = 'lastupdated = "' . time() . '"';
    $sql .= implode(',', $updates);
    $sql .= " where gameID = {$_POST['gameID']}";

    $result = $pdo->exec( $sql );

    $tQ = $pdo->query("SELECT tablename FROM " . DB_POKER . " WHERE gameID = {$_POST['gameID']}");
    $tF = $tQ->fetch(PDO::FETCH_ASSOC);
    OPS_sitelog($plyrname, sprintf( __( "%s edited table %s", 'core' ), $plyrname, $tF['tablename'] ));

    header('Location: admin.php?admin=tables&ud=1');
}

if ( $action == 'editmember' )
{
    $sql = 'UPDATE ' . DB_PLAYERS . ' SET ';
    $updates = array();
    $skip = array('ID', 'action');
    $skipnumbers = array('datecreated', 'lastlogin', 'banned', 'approve', 'lastmove', 'waitimer', 'vID', 'gID', 'timetag');

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'editmember_before_update'
        )
    );

    foreach ($_POST as $key => $value) {
        if ( in_array( $key, $skip ) )
            continue;

        if ( $key == 'startdate' ) {
            $updates[] = $key . ' = TIMESTAMP("' . str_replace( 'T', ' ', $value ) . '")';
            continue;
        }

        if ( in_array( $key, $skipnumbers ) )
        {
            $updates[] = $key . ' = ' . $value;
            continue;
        }

        $updates[] = $key . ' = "' . $value . '"';
    }

    $sql .= implode(',', $updates);
    $sql .= " WHERE username = '" . $_POST['username'] . "'";

    $result = $pdo->exec( $sql );
    OPS_sitelog($plyrname, sprintf( __( "%s edited user %s", 'core' ), $plyrname, $_POST['username'] ));

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/inc_admin.php',
            'location'  => 'editmember_after_update'
        )
    );

    header('Location: admin.php?admin=members&ud=1');
}

if ( $action == 'editmemberchips' )
{
    $winpot = (int) $_POST['winpot'];
    $winpot = transfer_to($winpot);
    $player = $_POST['player'];

    $playerStatQ = $pdo->query("SELECT winpot FROM " . DB_STATS . " WHERE player = '{$player}'");
    $playerStat  = $playerStatQ->fetch(PDO::FETCH_ASSOC);
    $playerPot   = $playerStat['winpot'];

    $math    = ($winpot > $playerPot) ? 'added' : 'subtracted';
    $mathNum = ($winpot > $playerPot) ? ($winpot - $playerPot) : ($playerPot - $winpot);

    OPS_sitelog($plyrname, sprintf( __( "%s %s %s in %s\'s bank. Old amount: %s. New amount: %s.", 'core' ), $plyrname, $math, money($mathNum), $player, money($playerPot), money($winpot) ));

    $pdo->query("UPDATE " . DB_STATS . " SET winpot = {$winpot} WHERE player = '{$player}'");
    header('Location: admin.php?admin=members&ud=1');
}



// updates
if (isset($_GET['download_update']))
{
    $updateJson = json_decode(file_get_contents_su(base64_decode('aHR0cHM6Ly91cGRhdGVzLm9ubGluZXBva2Vyc2NyaXB0LmNvbS9jb3JlL3NjcmlwdA=='), true));

    if (! isset($updateJson->status) || $updateJson->status !== "OK")
        return false;
    
    if (file_put_contents('update-' . $updateJson->version . '.zip', file_get_contents_ssl($updateJson->url)))
    {
        header("Content-type: application/json; charset=utf-8");
        echo json_encode(array(
            'status' => 'OK'
        ));
        exit();
    }
}

if (isset($_GET['extract_update']))
{
    $updateJson = json_decode(file_get_contents_su(base64_decode('aHR0cHM6Ly91cGRhdGVzLm9ubGluZXBva2Vyc2NyaXB0LmNvbS9jb3JlL3NjcmlwdA==')));

    if (! isset($updateJson->status) || $updateJson->status !== "OK")
        return false;

    $version = $updateJson->version;
    $zipFile = "update-$version.zip";
    
    if ( file_exists($zipFile) )
    {
        $dir = realpath('');
        $zip = new ZipArchive;
        
        if ($zip->open($zipFile) === true)
        {
            $zip->extractTo($dir);
            $zip->close();

            $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '$version' WHERE setting = 'scriptversio' AND Xkey = 'SCRIPTVERSIO'");
            $pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '0' WHERE setting = 'updatealert' AND Xkey = 'UPDATEALERT'");

            unlink($zipFile);
            OPS_sitelog($plyrname, sprintf( __( "%s updated the script version to %s", 'core' ), $plyrname, $version ));

            header("Content-type: application/json; charset=utf-8");
            echo json_encode(array(
                'status' => 'OK'
            ));
            exit();
        }
    }
}

if (isset($_GET['create_backup']))
{
    $dir = realpath('');

    try
    {
        $dbBackup = new MysqlBackup("mysql:host={$host};dbname={$db}", $ln, $pw);
        $dbBackup->start('backup.sql');
    }
    catch (\Exception $e)
    {
        error_log($e->getMessage());
    }

    if (! file_exists('backups'))
        mkdir('backups');

    $zip = new ZipArchive;

    if ($zip->open('backups/backup-' . time() . '.zip', ZipArchive::CREATE) === true)
    {
        rename('.htaccess', 'ht.access');

        foreach (rglob($dir . '/*') as $file)
        {
            $zip->addFile($file);
        }
        $zip->close();

        rename('ht.access', '.htaccess');
        unlink($dir . '/backup.sql');
        header('Location: admin.php?admin=updates');
    }
}


function rglob($pattern, $flags = 0)
{
    $files = array_filter(glob($pattern, $flags), 'is_file');

    foreach ( glob(dirname($pattern) . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
        if (preg_match('/^.*?\/backups$/i', $dir)) continue;
        $files = array_merge($files, rglob($dir . '/' . basename($pattern), $flags));
    }

    return $files;
}

?>