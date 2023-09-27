<?php
require('sec_inc.php');
header('Content-Type: text/javascript');

if ($gID == '' || $gID == 0)
    die();

echo $addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/auto_move.php',
        'location'  => 'page_start'
    )
);

$time    = time();
$timenow = $time;

$pdo->query("UPDATE " . DB_PLAYERS . " SET timetag = {$timenow} WHERE username = '{$plyrname}' AND gID = {$gameID}");

$tpq          = $pdo->query("SELECT * FROM " . DB_POKER . " WHERE gameID = $gameID");
$tpr          = $tpq->fetch(PDO::FETCH_ASSOC);
$hand         = $tpr['hand'];
$autoplayer   = $tpr['move'];
$movetimer    = MOVETIMER;
$lmovetimer   = KICKTIMER;
$distimer     = DISCONNECT;
$kick         = $time - ($lmovetimer * 60);
$timekick     = $time - $distimer;
$lastmove     = $tpr['lastmove'];
$diff         = $time - $lastmove;
$dealer       = $tpr['dealer'];
$tablepot     = $tpr['pot'];
$tabletype    = $tpr['tabletype'];
$tablelimit   = $tpr['tablelimit'];
$sbamount     = $tpr['sbamount'];
$bbamount     = $tpr['bbamount'];
$tablebet     = $tpr['bet'];
$lastbet      = $tpr['lastbet'];
$game_style   = ($tpr['gamestyle'] == 'o') ? GAME_OMAHA : GAME_TEXAS;
$statpotfield = $addons->get_hooks(
    array(
        'content' => 'winpot',
        'table'   => $tpr
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'stat_pot_field'
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

$delayed   = ($lastmove < $time) ? true : false;
$interplay = (is_interplay($hand) || is_straddling($hand)) ? true : false;

$opsTheme->addVariable('movetimer', $movetimer);

echo $addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'tpr_variables'
    )
);

if ($hand < 0 || $autoplayer < 1)
    die();

$showshort = false;
$allpl     = 0;

if ($autoplayer > 0)
{
    $nextup = nextplayer($autoplayer);

    $autoname   = $tpr['p' . $autoplayer . 'name'];
    $autopot    = $tpr['p' . $autoplayer . 'pot'];
    $autobet    = $tpr['p' . $autoplayer . 'bet'];
    $autofold   = substr($autobet, 0, 1);
    $autostatus = 'live';

    if ($autopot == 0 && $autobet > 0 && $interplay)
        $autostatus = 'allin';

    if ($autofold == 'F' && $interplay)
        $autostatus = 'fold';

    if ($autopot == 0 && ($autobet == 0 || $autostatus == 'fold'))
        $autostatus = 'bust';

    $np = 0;
    $ai = 0;
    $fo = 0;
    $bu = 0;

    for ($i = 1; $i < 11; $i++)
    {
        $usr   = $tpr['p' . $i . 'name'];
        $upot  = $tpr['p' . $i . 'pot'];
        $ubet  = $tpr['p' . $i . 'bet'];
        $ufold = substr($ubet, 0, 1);

        if (empty($usr))
            continue;

        $np++;

        if ($upot == 0 && $ubet > 0 && $ufold != 'F' && ($hand > 4 && $hand < 15))
            $ai++;

        if ($ufold == 'F' && $upot > 0 && ($hand > 4 && $hand < 15))
            $fo++;

        if (($ubet == 0 || $ufold == 'F') && $upot == 0)
            $bu++;

        $tkick   = false;
        $ttq_sql = $addons->get_hooks(
            array(
                'content' => "SELECT gID, timetag, lastmove, banned FROM " . DB_PLAYERS . " WHERE username = '{$usr}'"
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'ttq_sql'
            )
        );
        $ttq = $pdo->query($ttq_sql);
        $ttr = $ttq->fetch(PDO::FETCH_ASSOC);

        if ($ttr['timetag'] < $timekick || ($ttr['lastmove'] < $kick && $hand > 5) || ($ttr['banned'] == 1) || ($ttr['gID'] != $gameID))
            $tkick = true;

        $tkick = $addons->get_hooks(
            array(
                'state'   => $tkick,
                'content' => $tkick,
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'player_tkick'
            )
        );

        $busted = ($upot == 0 && ($ubet == 0 || $ufold == 'F')) ? true : false;
        $busted = $addons->get_hooks(
            array(
                'content' => $busted
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'busted_bool'
            )
        );

        $kickPlayer = ($busted || $tkick) ? true : false;
        
        $addons->get_hooks(
            array(),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'each_player'
            )
        );

        if (! $kickPlayer)
            continue;

        // Kick Player
        $pdo->query("UPDATE " . DB_POKER . " SET p{$i}name = '', p{$i}bet = '', p{$i}pot = '', lastmove = {$timenow} WHERE gameID = {$gameID}");
        $pdo->query("UPDATE " . DB_PLAYERS . " SET gID = 0 WHERE username = '{$usr}'");

        /* --- Add To Bank */
        $addToBank = true;
        $addToBank = $addons->get_hooks(
            array(
                'content' => $addToBank
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location'  => 'add_to_bank_bool'
            )
        );

        if ($addToBank)
            $pdo->query("UPDATE " . DB_STATS . " SET $statpotfield = $statpotfield + $upot WHERE player = '{$usr}'");
        /* Add To Bank --- */

        if ($tkick == true)
        {
            poker_log($usr, GAME_MSG_LOST_CONNECTION, $gameID);

            if ($i == $dealer)
            {
                $nxtdeal = nextdealer($dealer);

                if ($nxtdeal != '')
                {
                    $pdo->query("UPDATE " . DB_POKER . " SET lastmove = {$timenow}, dealer = {$nxtdeal} WHERE gameID = {$gameID}");
                    poker_log( get_name($nxtdeal), GAME_MSG_DEALER_BUTTON, $gameID );
                }
            }

            if ($_SESSION['playername'] == $usr)
            {
                $wait    = (int) WAITIMER;
                $setwait = $time + $wait;
                $result  = $pdo->query("UPDATE " . DB_PLAYERS . " SET waitimer = {$setwait} WHERE username = '{$plyrname}'");
                $url     = 'sitout.php';

                echo 'parent.document.location.href = "' . $url . '";';
            }
        }
        else
        {
            poker_log($usr, GAME_MSG_PLAYER_BUSTED, $gameID);
            echo $addons->get_hooks(
                array(
                    'index' => $i
                ),
                array(
                    'page'     => 'includes/auto_move.php',
                    'location'  => 'after_player_busted'
                )
            );
        }

        $addons->get_hooks(
            array(),
            array(
                'page'     => 'includes/auto_move.php',
                'location' => 'after_kick'
            )
        );
    }

    $checkbets = check_bets();
    $lastman   = '';
    $allpl     = $np - $bu;

    if ($allpl == 1)
        $lastman = last_player();

    $nfpl       = $allpl - $fo;
    $lipl       = $nfpl - $ai;
    $gamestatus = 'live';

    if ($interplay)
    {
        if ($nfpl == 1 && $allpl > 1)
            $gamestatus = 'allfold';

        if ($lipl < 2 && $allpl > 1 && $checkbets == true && $ai > 0)
            $gamestatus = 'showdown';
    }
    else
    {
        if ($nfpl == 1 && $allpl > 1)
            $showshort = true;
    }

    // do this
    if ($allpl == 1 && $hand >= 0)
    {
        $winpot = $tpr["p{$lastman}pot"] + $tablepot;
        $logger  = '';
        $msg    = GAME_MSG_PLAYERS_JOINING;

        if ($tabletype === 't' && $allpl == 1)
        {
            if ($autoname != '')
            {
                $logger = $autoname;
                $msg    = GAME_MSG_WON_TOURNAMENT;
            }

            $pdo->query("UPDATE " . DB_STATS . " SET tournamentswon = tournamentswon + 1 WHERE player  = '{$autoname}'");
        }

        $msg = $addons->get_hooks(
            array(
                'content' => $msg
            ),
            array(
                'page'     => 'includes/auto_move.php',
                'location'  => 'log_message_1player'
            )
        );

        $pdo->query("UPDATE " . DB_POKER . " SET p{$lastman}bet = '0', p{$lastman}pot = '{$winpot}', move = 0, lastmove = $timenow, dealer = 0, msg = '{$msg}', hand = -1, bet = 0, pot = 0 WHERE gameID = {$gameID}");
        hand_hook();

        poker_log($logger, $msg, $gameID);
        die();
    }

    if ($autoname == '' || $autostatus == 'bust' && $allpl > 1)
    {
        $pdo->query("UPDATE " . DB_POKER . " SET move = {$nextup}, lastmove = $timenow WHERE gameID = " . $gameID);
        die();
    }

    $checkpass    = ($autoplayer == last_bet() && $checkbets == true && $gamestatus == 'live') ? true : false;
    $postShowdown = ($hand === 0 || (in_array($hand, array(5, 7, 9, 11)) && $checkpass)) ? true : false;
    $preShowdown  = ($postShowdown) ? false : true;

    if ($preShowdown)
        include 'playtime/showdown.php';
}

$cp2 = ($checkpass) ? 'yes' : 'no';

// Timers
if ($interplay) // when playes can perform actions such as bet/check/fold
    require 'playtime/timers/interplay.php';
else
    require 'playtime/timers/non-interplay.php';


// Hands
require "playtime/hands/hand{$hand}.php";

if ($postShowdown)
    include 'playtime/showdown.php';


// Page End
echo $addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'page_end'
    )
);

function hand_hook()
{
    global $addons, $hand;
    $addons->get_hooks(
        array(
            'content' => $hand
        ),
        array(
            'page'     => 'includes/auto_move.php',
            'location'  => 'hand_change'
        )
    );
}
?>