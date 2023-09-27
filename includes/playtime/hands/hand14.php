<?php
$showdowntimer = ($showshort == true) ? 1 : SHOWDOWN;

if ($diff < $showdowntimer)
    die();

echo $addons->get_hooks(array(), array(
    'page'     => 'includes/auto_move.php',
    'location'  => 'hand_14'
));

$proc   = GAME_MSG_PROCESSING;
$result = $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$proc}', move = {$nextup}, hand = 15, lastmove = $timenow WHERE gameID = {$gameID}");
hand_hook();
