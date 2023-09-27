<?php
if (! $delayed)
    die();

require 'playtime/multiplier.php';

if ($autopot > $SB)
{
    $msg  = $autoname . ' ' . GAME_MSG_SMALL_BLIND . ' ' . money_small($SB, true, $moneyPrefix);
    $npot = $autopot - $SB;
    $nbet = $SB;
    poker_log($autoname, GAME_MSG_SMALL_BLIND . ' ' . money_small($SB, true, $moneyPrefix), $gameID);
}
else
{
    $msg  = $autoname . ' ' . GAME_PLAYER_GOES_ALLIN;
    $npot = 0;
    $nbet = $SB;
    poker_log($autoname, GAME_PLAYER_GOES_ALLIN, $gameID);
}

$result = $pdo->query("UPDATE " . DB_POKER . " SET pot = pot + {$nbet}, msg = '{$msg}', move = {$nextup}, p{$autoplayer}pot = '{$npot}', p{$autoplayer}bet = '{$nbet}', hand = 3, lastmove = $timenow WHERE gameID = {$gameID}");
hand_hook();
