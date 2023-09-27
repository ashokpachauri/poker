<?php
require ('sec_inc.php');

header('Content-Type: text/javascript');

echo $addons->get_hooks(array(), array(
    'page'     => 'includes/player_move.php',
    'location'  => 'page_start'
));

if ($gID == '' || $gID == 0)
{
    die();
}

// Action
$action = (isset($_GET['action'])) ? addslashes($_GET['action']) : '';
$time   = time();


if ($action === 'hidecards')
{
    $pdo->query("UPDATE " . DB_PLAYERS . " SET show_cards = 0 WHERE username = '{$plyrname}'");
    die();
}
elseif ($action === 'showcards')
{
    $pdo->query("UPDATE " . DB_PLAYERS . " SET show_cards = 1 WHERE username = '{$plyrname}'");
    die();
}


$tpq_sql = "SELECT * FROM " . DB_POKER . " WHERE gameID = " . $gameID;
$tpq = $pdo->prepare($tpq_sql);
$tpq->execute();
$tpr = $tpq->fetch(PDO::FETCH_ASSOC);
$player = getplayerid($plyrname);
$tomove = $tpr['move'];
$addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/player_move.php',
        'location'  => 'tpr_variables'
    )
);

$moneyPrefix = $addons->get_hooks(
    array(
        'content' => MONEY_PREFIX,
        'table'   => $tpr
    ),
    array(
        'page'     => 'general',
        'location' => 'money_prefix'
    )
);

$lastmove   = $tpr['lastmove'];
$dealer     = $tpr['dealer'];
$hand       = $tpr['hand'];
$tablepot   = $tpr['pot'];
$tablebet   = $tpr['bet'];
$tablelimit = $tpr['tablelimit'];
$tabletype  = $tpr['tabletype'];
$lastbet    = $tpr['lastbet'];
$numleft    = get_num_left();
$sbamount   = $tpr['sbamount'];
$bbamount   = $tpr['bbamount'];
$playerpot  = intval($tpr['p' . $player . 'pot']);
$playerbet  = $tpr['p' . $player . 'bet'];

if ( $action === 'rebuy' )
{
    $maxbuyin = $tablelimit == 0 ? (intval($bbamount) * 100) : $tablelimit;
    $ppot     = intval($playerpot);

    if ( $ppot < $maxbuyin )
    {
        $diff    = $maxbuyin - $ppot;
        $current = ops_get_statpot();

        if ( $diff > 0 && $current !== false && $current > 0 )
        {
            $newstat = ( $diff > $current ) ? $current : $diff;
            $newpot  = $ppot + $newstat;

            $pdo->query("UPDATE " . DB_POKER . " SET p{$player}pot = '{$newpot}' WHERE gameID = " . $gameID);
            ops_subtract_statpot( $newstat );

            $moneyPrefix = $addons->get_hooks(
                array(
                    'content' => MONEY_PREFIX,
                    'table'   => $tpr
                ),
                array(
                    'page'     => 'general',
                    'location' => 'money_prefix'
                )
            );
            ?>
            jQuery("#player-<?php echo $player; ?>-info").find(".poker__user-money").find("p").text("<?php echo money($newpot, true, $moneyPrefix) ?>");
            <?php
        }
    }
    die();
}

if ($action === 'straddle')
{
    $pdo->query("UPDATE " . DB_POKER . " SET do_straddle = {$player} WHERE gameID = " . $gameID);

    $allpl = 0;
    $np    = 0;
    $bu    = 0;

    for ($i = 1; $i < 11; $i++)
    {
        $usr   = $tpr['p' . $i . 'name'];
        $upot  = $tpr['p' . $i . 'pot'];
        $ubet  = $tpr['p' . $i . 'bet'];
        $ufold = substr($ubet, 0, 1);

        if (empty($usr))
            continue;

        $np++;

        if (($ubet == 0 || $ufold == 'F') && $upot == 0)
            $bu++;
    }

    $allpl = $np - $bu;

    $blindmultiplier = ($tabletype === 't') ? (11 - $allpl) : 4;
    switch ($tablelimit)
    {
        case 25000:
            $tablemultiplier = 2;
            break;
        
        case 50000:
            $tablemultiplier = 4;
            break;
        
        case 100000:
            $tablemultiplier = 8;
            break;
        
        case 250000:
            $tablemultiplier = 20;
            break;
        
        case 500000:
            $tablemultiplier = 40;
            break;
        
        case 1000000:
            $tablemultiplier = 80;
            break;
        
        default:
            $tablemultiplier = 1;
            break;
    }

    $blindmultiplier = $addons->get_hooks(
        array(
            'content' => $blindmultiplier
        ),
        array(
            'page'     => 'includes/auto_move.php',
            'location' => 'blind_multiplier'
        )
    );
    $tablemultiplier = $addons->get_hooks(
        array(
            'content' => $tablemultiplier
        ),
        array(
            'page'     => 'includes/auto_move.php',
            'location' => 'table_multiplier'
        )
    );

    $oBB = ($sbamount != 0) ? $bbamount : (50 * $blindmultiplier * $tablemultiplier);
    $BB = $addons->get_hooks(
        array(
            'content' => $oBB
        ),
        array(
            'page'     => 'includes/auto_move.php',
            'location' => 'big_blind'
        )
    );

    $action = $BB * 2;

    $goallin = false;
    $process = false;
    $nextup  = nextplayer($player);
    $newr    = '';

    $pdo->query("update " . DB_STATS . " set bet = bet+1 where player  = '$plyrname' ");
    $diff = ($tablebet - $playerbet);
    $checkbet = ($diff + $action);
    if ($checkbet >= $playerpot)
    {
        $goallin = true;
    }
    else
    {
        $process = true;
        $pbet = $tablebet + $action;
        $tablepot = $tablepot + $checkbet;
        $potleft = $playerpot - $checkbet;
        $tablebet2 = $tablebet + $action;
    }

    $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_RAISES . ' ' . money_small($action, true, $moneyPrefix);
    poker_log($plyrname, GAME_PLAYER_RAISES . ' ' . money_small($action, true, $moneyPrefix), $gameID);

    if ($goallin == true)
    {
        $process = true;
        $diff = ($tablebet - $playerbet);
        $raise = $playerpot - $diff;
        $tablepot = $tablepot + $playerpot;
        $tablebet2 = (($raise > 0) ? ($tablebet + $raise) : $tablebet);
        $pbet = $playerbet + $playerpot;
        $potleft = 0;

        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_GOES_ALLIN;
        poker_log($plyrname, GAME_PLAYER_GOES_ALLIN, $gameID);
    }

    if ($process == true)
    {
        $lastbet = ($tablebet2 > $tablebet || $lastbet == 0) ? $player . '|' . $tablebet : $lastbet;
        $result = $pdo->exec("update " . DB_POKER . " set msg = '$msg', pot = $tablepot, bet = $tablebet2, lastbet = '$lastbet', p" . $player . "bet = '$pbet', hand = " . HAND_STRADDLE_START . ", move = {$nextup}, lastmove = " . ($time + 1) . " , p" . $player . "pot = '$potleft' " . $newr . "where gameID = " . $gameID);
        $result = $pdo->exec("update " . DB_PLAYERS . " set  lastmove = $time where username = '$plyrname' ");
    }
}


// All doable actions

$max = (int) $playerpot;

/*$numactions = array();
for ($ni = 1; $ni <= $max; $ni++)
{ 
    $numactions[] = $ni;
}*/


$actions_array = array(
    'check',
    'call',
    'allin',
    'fold',
    'start'
);

// Actions you can do after game starts

$betactions_array = array(
    'check',
    'call',
    'allin',
    'fold'
);

// if action is not in the list, die

if (in_array($action, $actions_array) || ($action >= 1 && $action <= $max)){}
else
    die();

// if action is a bet

$isbet = (in_array($action, $betactions_array) || ($action >= 1 && $action <= $max)) ? true : false;

// if player doesn't exist

if ($player == '')
{
    die();
}

// get number of players

$numplayers = get_num_players();

// if action is start the game
echo $addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/player_move.php',
        'location'  => 'beginning_of_action'
    )
);


if ($hand < 0 && $numplayers > 1 && $action == 'start')
{
    $msg        = GAME_STARTING;
    $result_sql = $addons->get_hooks(
        array(
            'content' => "UPDATE " . DB_POKER . " SET hand = 0, msg = '$msg', move = $player, dealer = $player WHERE gameID = $gameID"
        ),
        array(
            'page'     => 'includes/player_move.php',
            'location'  => 'start_sql1'
        )
    );
    $pdo->query($result_sql);
    $pdo->query("UPDATE " . DB_PLAYERS . " SET lastmove = " . ($time + 1) . " WHERE username = '$plyrname'");

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/player_move.php',
            'location'  => 'game_start'
        )
    );
    die();
}


// if it's not player's move, die

if ($tomove != $player)
{
    die();
}

$process = false;

if (substr($playerbet, 0, 1) == 'F')
{
    die();
}

// if player's pot is empty, die

if ($playerpot == 0)
{
    die();
}

// if everything is okay
$everythingOkayLogic = ($player == $tomove && $numplayers > 1 && (is_straddling($hand) || (is_interplay($hand) && $isbet == true))) ? true : false;
$everythingOkayLogic = $addons->get_hooks(
    array(
        'state'   => $everythingOkayLogic,
        'content' => $everythingOkayLogic,
    ),
    array(
        'page'     => 'includes/player_move.php',
        'location' => 'everything_okay_logic'
    )
);
if ($everythingOkayLogic)
{
    $goallin = false;
    $nextup = nextplayer($player);
    $newr = '';
    if ($hand == 6)
    {
        $newr = ", hand = 7 ";
    }

    if ($hand == 8)
    {
        $newr = ", hand = 9 ";
    }

    if ($hand == 10)
    {
        $newr = ", hand = 11 ";
    }

    if ($hand == 26)
    {
        //$newr = ", hand = 27 ";
    }

    if ($action == 'allin')
    {
        $result = $pdo->exec("update " . DB_STATS . " set allin = allin+1 where player  = '$plyrname' ");
        $goallin = true;
    }
    elseif ($action == 'fold')
    {
        $_SESSION['nofold_handsplayed'] = 0;
        $_SESSION['nofold_handswon'] = 0;
        
        if ($hand < 6)
        {
            $result = $pdo->exec("update " . DB_STATS . " set fold_pf = fold_pf+1 where player  = '$plyrname' ");
        }
        elseif ($hand < 8)
        {
            $result = $pdo->exec("update " . DB_STATS . " set fold_f = fold_f+1 where player  = '$plyrname' ");
        }
        elseif ($hand < 10)
        {
            $result = $pdo->exec("update " . DB_STATS . " set fold_t = fold_t+1 where player  = '$plyrname' ");
        }
        else
        {
            $result = $pdo->exec("update " . DB_STATS . " set fold_r = fold_r+1 where player  = '$plyrname' ");
        }

        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_FOLDS;

        $result = $pdo->exec("update " . DB_POKER . " set  msg = '$msg', p" . $player . "bet = 'F$playerbet', move = $nextup $newr , lastmove = " . ($time + 1) . "  where gameID = " . $gameID);
        $result = $pdo->exec("update " . DB_PLAYERS . " set  lastmove = " . ($time + 1) . " where username = '$plyrname' ");

        poker_log($plyrname, GAME_PLAYER_FOLDS, $gameID);
    }
    elseif ($action == 'check')
    {
        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_CHECKS;

        $result = $pdo->exec("update " . DB_STATS . " set checked = checked+1 where player  = '$plyrname' ");
        $result = $pdo->exec("update " . DB_POKER . " set msg = '$msg', move = $nextup $newr , lastmove = " . ($time + 1) . " where gameID = " . $gameID);
        $result = $pdo->exec("update " . DB_PLAYERS . " set  lastmove = " . ($time + 1) . " where username = '$plyrname' ");

        poker_log($plyrname, GAME_PLAYER_CHECKS, $gameID);
    }
    elseif ($action == 'call')
    {
        $result  = $pdo->exec("update " . DB_STATS . " set called = called+1 where player  = '$plyrname' ");
        $process = true;
        $callbet = $tablebet - $playerbet;
        if ($playerpot <= $callbet)
        {
            $goallin = true;
        }
        else
        {
            $potleft = $playerpot - $callbet;
            $tablepot = $tablepot + $callbet;
            $pbet = $tablebet;
            $tablebet2 = $tablebet;
        }

        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_CALLS . ' ' . money_small($callbet, true, $moneyPrefix);
        poker_log($plyrname, GAME_PLAYER_CALLS . ' ' . money_small($callbet, true, $moneyPrefix), $gameID);
    }
    elseif ($action >= $playerpot)
    {
        $result = $pdo->exec("update " . DB_STATS . " set allin = allin+1 where player  = '$plyrname' ");
        $goallin = true;
    }
    else
    {
        $result = $pdo->exec("update " . DB_STATS . " set bet = bet+1 where player  = '$plyrname' ");
        $diff = ($tablebet - $playerbet);
        $checkbet = ($diff + $action);
        if ($checkbet >= $playerpot)
        {
            $goallin = true;
        }
        else
        {
            $process = true;
            $pbet = $tablebet + $action;
            $tablepot = $tablepot + $checkbet;
            $potleft = $playerpot - $checkbet;
            $tablebet2 = $tablebet + $action;
        }

        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_RAISES . ' ' . money_small($action, true, $moneyPrefix);
        poker_log($plyrname, GAME_PLAYER_RAISES . ' ' . money_small($action, true, $moneyPrefix), $gameID);
    }

    if ($goallin == true)
    {
        $process = true;
        $diff = ($tablebet - $playerbet);
        $raise = $playerpot - $diff;
        $tablepot = $tablepot + $playerpot;
        $tablebet2 = (($raise > 0) ? ($tablebet + $raise) : $tablebet);
        $pbet = $playerbet + $playerpot;
        $potleft = 0;

        $_SESSION['went_allin']      = true;
        $_SESSION['allin_this_hand'] = true;

        $_SESSION['cont_allin_count']++;
        $_SESSION['cont_allin_win_count']++;

        $msg = '<span class="chatName">' . $plyrname . '</span> ' . GAME_PLAYER_GOES_ALLIN;
        poker_log($plyrname, GAME_PLAYER_GOES_ALLIN, $gameID);
    }

    if ($process == true)
    {
        $lastbet = ($tablebet2 > $tablebet || $lastbet == 0) ? $player . '|' . $tablebet : $lastbet;
        $result = $pdo->exec("update " . DB_POKER . " set msg = '$msg', pot = $tablepot, bet = $tablebet2, lastbet = '$lastbet', p" . $player . "bet = '$pbet', move = $nextup, lastmove = " . ($time + 1) . " , p" . $player . "pot = '$potleft' " . $newr . "where gameID = " . $gameID);
        $result = $pdo->exec("update " . DB_PLAYERS . " set  lastmove = $time where username = '$plyrname' ");
    }

    $addons->get_hooks(
        array(),
        array(
            'page'     => 'includes/player_move.php',
            'location'  => 'after_move'
        )
    );
}

?>