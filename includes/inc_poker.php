<?php

if ($valid == false)
    header('Location: login.php');
if (empty($gameID))
    header('Location: lobby.php');

$time       = time();
$sql        = $addons->get_hooks(
    array(
        'content' => "SELECT * FROM " . DB_POKER . " WHERE gameID = " . $gameID
    ),
    array(
        'page'      => 'includes/inc_poker.php',
        'location'  => 'tq_sql',
    )
);
$tq           = $pdo->query($sql);
$tr           = $tq->fetch(PDO::FETCH_ASSOC);
$tablehand    = $tr['hand'];
$tableid      = $tr['gameID'];
$tablename    = $tr['tablename'];
$tabletype    = $tr['tabletype'];
$tablelimit   = $tr['tablelimit'];
$tablestyle   = $tr['tablestyle'];
$sbamount	  = $tr['sbamount'];
$bbamount	  = $tr['bbamount'];
$statpotfield = $addons->get_hooks(
    array(
        'content' => 'winpot',
        'table'   => $tr
    ),
    array(
        'page'     => 'includes/inc_poker.php',
        'location' => 'stat_pot_field'
    )
);
$moneyPrefix = $addons->get_hooks(
    array(
        'content' => MONEY_PREFIX,
		'table'   => $tr
    ),
    array(
        'page'     => 'general',
        'location' => 'money_prefix'
    )
);


$isPlaying = false;
$mySeat    = 0;
for ($pi = 1; $pi < 11; $pi++)
{ 
    if ($tr["p{$pi}name"] == $plyrname)
    {
        $mySeat    = $pi;
        $isPlaying = true;
        break;
    }
}

$addons->get_hooks(array(),
    array(
        'page'     => 'includes/inc_poker.php',
        'location' => 'after_tablestyle_var',
    )
);

$tomove = $tr['move'];
$min    = $tr['tablelow'];
$sq     = $pdo->query("SELECT style_name, style_lic FROM styles WHERE style_name = '{$tablestyle}'");

$officialstylepack = 'normal';

$action = (isset($_GET['action'])) ? $_GET['action'] : '';

$addons->get_hooks(array(),
    array(
        'page'     => 'includes/inc_poker.php',
        'location' => 'actions',
    )
);

$leave = ($action === 'leave' && ($isPlaying === false || $tablehand < 3)) ? true : false;
$leave = $addons->get_hooks(
    array(
        'content' => $leave
    ),
    array(
        'page'     => 'includes/inc_poker.php',
        'location' => 'leave_bool',
    )
);
    
if ($leave && false)
{
    $_SESSION['nofold_handsplayed']  = 0;
    $_SESSION['nofold_handswon']     = 0;
    $_SESSION['noleave_handsplayed'] = 0;
    $_SESSION['noleave_handswon']    = 0;
    
    $tpq          = $pdo->query("SELECT * FROM " . DB_POKER . " WHERE gameID = " . $gameID);
    $tpr          = $tpq->fetch(PDO::FETCH_ASSOC);
    $i            = 1;
    $player       = '';
    $playernum    = 0;

    while ($i < 11)
    {
        if (strlen($tpr['p' . $i . 'name']) > 0)
            $playernum++;

        if ($tpr['p' . $i . 'name'] == $plyrname)
        {
            $player = $i;
            $pot    = $tpr['p' . $i . 'pot'];
        }
        $i++;
    }

    $exitTable = ($player != '') ? true : false;
    $exitTable = $addons->get_hooks(
        array(
            'content' => $exitTable
        ),
        array(
            'page'     => 'includes/inc_poker.php',
            'location' => 'exit_table_bool',
        )
    );

    if ($exitTable)
    {
        poker_log($plyrname, __( 'leaves the table', 'core' ), $gameID);

        $winpot = $pot;
        $winpot = $addons->get_hooks(
            array(
                'content' => $winpot,
            ),
            array(
                'page'     => 'includes/inc_poker.php',
                'location' => 'winpot_var',
            )
        );

        $addToBank = ($tpr['tabletype'] !== 't' || $playernum === 1) ? true : false;
        $addToBank = $addons->get_hooks(
            array(
                'content' => $addToBank
            ),
            array(
                'page'     => 'includes/inc_poker.php',
                'location' => 'add_to_bank_bool',
            )
        );
        
        if ($addToBank)
        {
            $statsq   = $pdo->query("select $statpotfield from " . DB_STATS . " where player = '{$plyrname}'");
            $statsr   = $statsq->fetch(PDO::FETCH_ASSOC);
            $winnings = $statsr[$statpotfield];
            $winpot  += $winnings;
            $result   = $pdo->query("update " . DB_STATS . " set $statpotfield = {$winpot} where player  = '{$plyrname}'");

            OPS_sitelog($plyrname, sprintf( __( "%s left the table with %s. New bank amount: %s.", 'core' ), $plyrname, money($pot, true, $moneyPrefix), money($winpot, true, $moneyPrefix) ));
        }
        
        if ($tomove == $player)
        {
            $nxtp   = nextplayer($player);
            $result = $pdo->query("update " . DB_POKER . " set p" . $player . "name = '', p" . $player . "bet = '', p" . $player . "pot = '', move = {$nxtp}, lastmove = " . $time . "  where gameID = " . $gameID);
        }
        else
        {
            $result = $pdo->query("update " . DB_POKER . " set p" . $player . "name = '', p" . $player . "bet = '', p" . $player . "pot = '', lastmove = " . $time . " where gameID = " . $gameID);
        }

        $addons->get_hooks(
            array(
                'db'     => $pdo,
                'game'   => $tpr,
                'player' => $plyrname,
            ),
            array(
                'page'      => 'includes/inc_poker.php',
                'location'  => 'if_player_exists',
            )
        );

        $wait    = (int) WAITIMER;
        $setwait = $time + $wait;

        $pdo->query("UPDATE " . DB_PLAYERS . " SET waitimer = {$setwait} WHERE username = '{$plyrname}'");
        $pdo->query("UPDATE " . DB_PLAYERS . " SET gID = 0, vID = 0 WHERE username = '{$plyrname}'");

        header("Location: sitout.php");
        die();
    }

    header("Location: lobby.php");
    die();
}
?>