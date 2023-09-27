<?php
if (! $delayed)
    die();

$logic = $addons->get_hooks(
    array(
        'content' => $checkpass
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location'  => 'straddle_logic'
));
$logic = $addons->get_hooks(
    array(
        'content' => $logic
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location'  => 'hand16_logic'
));
if ($logic)
{
    $nextup = nextplayer($dealer);
    $lbet   = $nextup . '|' . $tablebet;

    $msg    = GAME_MSG_DEAL_FLOP;
    $result = $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', lastbet = '{$lbet}', move = {$nextup}, hand = 4, lastmove = $timenow WHERE gameID = '{$gameID}' ");
    
    hand_hook();
    poker_log('', $msg, $gameID);
}
else
{
    if ($diff < $movetimer && $gamestatus == 'live' && $autostatus == 'live')
        die();

    if ($tablebet > $autobet)
    {
        $msg = $autoname . ' ' . GAME_PLAYER_FOLDS;
        $pdo->query("UPDATE " . DB_STATS . " SET fold_pf = fold_pf + 1 WHERE player  = '{$autoname}'");
        $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', p{$autoplayer}bet = 'F{$autobet}', move = {$nextup}, lastmove = $timenow  WHERE gameID = " . $gameID);
        poker_log($autoname, GAME_PLAYER_FOLDS, $gameID);
    }
    else
    {
        $msg    = $autoname . ' ' . GAME_PLAYER_CHECKS;
        $result = $pdo->query("UPDATE " . DB_STATS . " SET checked = checked + 1 WHERE player  = '{$autoname}'");
        $result = $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', move = {$nextup}, lastmove = $timenow WHERE gameID = " . $gameID);
        poker_log($autoname, GAME_PLAYER_CHECKS, $gameID);
    }
}
