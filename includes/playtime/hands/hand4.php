<?php
if (! $delayed)
    die();

$logic = ($dealer == getplayerid($plyrname)) ? true : false;
$logic = $addons->get_hooks(
    array(
        'state' => $logic,
        'content' => $logic,
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location'  => 'hand4_logic'
));

if ($logic)
{
    $msg = GAME_MSG_DEAL_CARDS;
    deal(10, $gameID, $game_style);
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', move = {$nextup}, hand = 5, lastmove = $timenow WHERE gameID = {$gameID}");
    poker_log('', $msg, $gameID);
    hand_hook();
}
elseif ((time() - 3) > $lastmove)
{
    $msg = GAME_MSG_DEAL_CARDS;
    deal(10, $gameID, $game_style);
    $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', move = {$nextup}, hand = 5, lastmove = $timenow WHERE gameID = {$gameID}");
    poker_log('', $msg, $gameID);
    hand_hook();
}
