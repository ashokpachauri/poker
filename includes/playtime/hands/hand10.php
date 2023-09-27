<?php
if (! $delayed)
    die();

if ($diff < $movetimer && $gamestatus == 'live' && $autostatus == 'live')
    die();

if ($tablebet > $autobet)
{
    $msg = $autoname . ' ' . GAME_PLAYER_FOLDS;
    $pdo->query("UPDATE " . DB_STATS . " SET fold_r = fold_r + 1 WHERE player = '{$autoname}' ");
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', p{$autoplayer}bet = 'F{$autobet}', move = {$nextup}, hand = 11, lastmove = $timenow  WHERE gameID = " . $gameID);
    poker_log($autoname, GAME_PLAYER_FOLDS, $gameID);
}
else
{
    $msg    = $autoname . ' ' . GAME_PLAYER_CHECKS;
    $result = $pdo->query("UPDATE " . DB_STATS . " SET checked = checked + 1 WHERE player  = '{$autoname}'");
    $result = $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', move = {$nextup}, hand = 11, lastmove = $timenow WHERE gameID = " . $gameID);
    poker_log($autoname, GAME_PLAYER_CHECKS, $gameID);
}

hand_hook();
