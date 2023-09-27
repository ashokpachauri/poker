<?php
if ($gamestatus == 'allfold')
{
    $msg = $autoname . ' ' . GAME_MSG_ALLFOLD;
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', hand = 14, lastmove = $timenow WHERE gameID = {$gameID}");
    hand_hook();
    poker_log($autoname, GAME_MSG_ALLFOLD, $gameID);
    die();
}
elseif ($gamestatus == 'showdown' && ($checkbets == true || $lipl == 0))
{
    $msg    = GAME_MSG_SHOWDOWN;
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', hand = 13, lastmove = $timenow WHERE gameID = {$gameID}");
    hand_hook();
    die();
}

if ($autostatus == 'allin')
{
    $msg = $autoname . ' ' . GAME_MSG_PLAYER_ALLIN;
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', move = {$nextup}, lastmove = $timenow WHERE gameID = " . $gameID);
    poker_log($autoname, GAME_MSG_PLAYER_ALLIN, $gameID);
    die();
}
