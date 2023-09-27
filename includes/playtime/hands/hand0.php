<?php
if (! $delayed)
    die();

if (! isset($_SESSION['allin_this_hand']) || ! $_SESSION['allin_this_hand'])
{
    $_SESSION['cont_allin_count']     = 0;
    $_SESSION['cont_allin_win_count'] = 0;
}

$_SESSION['allin_this_hand'] = false;

if (! isset($_SESSION['nofold_handsplayed']))
    $_SESSION['nofold_handsplayed'] = 0;
else
    $_SESSION['nofold_handsplayed']++;

if (! isset($_SESSION['noleave_handsplayed']))
    $_SESSION['noleave_handsplayed'] = 0;
else
    $_SESSION['noleave_handsplayed']++;

if (! isset($_SESSION['nofold_handswon']))
    $_SESSION['nofold_handswon'] = 0;

if (! isset($_SESSION['noleave_handswon']))
    $_SESSION['noleave_handswon'] = 0;

if ($_SESSION['nofold_handsplayed'] > $usestats['nofold_handsplayed'])
    $pdo->query("UPDATE " . DB_STATS . " SET nofold_handsplayed = {$_SESSION['nofold_handsplayed']} WHERE player = '{$plyrname}'");

if ($_SESSION['noleave_handsplayed'] > $usestats['noleave_handsplayed'])
    $pdo->query("UPDATE " . DB_STATS . " SET noleave_handsplayed = {$_SESSION['noleave_handsplayed']} WHERE player = '{$plyrname}'");

/**/
for ($i = 1; $i < 11; $i++)
{
    $usr   = $tpr['p' . $i . 'name'];
    $ufold = substr($ubet, 0, 1);

    if ($usr !== $plyrname)
        continue;
    
    $lastchips = $_SESSION['lastchips'];
    $mychips   = $tpr['p' . $i . 'pot'];
    $mybet     = $tpr['p' . $i . 'bet'];
    $ratio     = floatval(number_format($mychips / $_SESSION['startpot'], 2));

    if ($mychips > $lastchips)
    {
        $_SESSION['chipswon']  = $_SESSION['chipswon'] + ($mychips - $lastchips);
        $earnedChips           = $mychips - $lastchips;

        if (isset($_SESSION['went_allin']) && $earnedChips > $usestats['max_allin_chipswon'])
        {
            $pdo->query("UPDATE " . DB_STATS . " SET max_allin_chipswon = {$earnedChips} WHERE player = '{$plyrname}'");
            unset($_SESSION['went_allin']);
        }
    }
    elseif ($mychips < $lastchips)
        $_SESSION['chipswon'] = $_SESSION['chipswon'] - ($lastchips - $mychips);
    
    if ($_SESSION['chipswon'] > $usestats['max_chipswon'])
        $pdo->query("UPDATE " . DB_STATS . " SET max_chipswon = {$_SESSION['chipswon']} WHERE player = '{$plyrname}'");
    
    if ($ratio > $usestats['max_multiplypotratio'])
        $pdo->query("UPDATE " . DB_STATS . " SET max_multiplypotratio = {$ratio} WHERE player = '{$plyrname}'");

    $_SESSION['lastchips'] = $mychips;
    break;
}

$nxtdeal = nextdealer($dealer);

if ($nxtdeal == '')
    die();

$msg = get_name($nxtdeal) . ' ' . GAME_MSG_DEALER_BUTTON;
poker_log( get_name($nxtdeal), GAME_MSG_DEALER_BUTTON, $gameID );

$result_sql = $addons->get_hooks(
    array(
        'content' => "UPDATE " . DB_POKER . " SET msg = '{$msg}', lastmove = $timenow, dealer = {$nxtdeal}, move = {$nxtdeal}, bet = 0, lastbet = 0, pot = 0, p1bet = '0', p2bet = '0', p3bet = '0', p4bet = '0', p5bet = '0', p6bet = '0', p7bet = '0', p8bet = '0', p9bet = '0', p10bet = '0', hand = 1  WHERE gameID = {$gameID}"
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'hand0_sql'
));
$result     = $pdo->query($result_sql);

$pdo->query("UPDATE " . DB_PLAYERS . " SET show_cards = 1 WHERE username = '{$plyrname}'");
?>
jQuery(".poker__showcards-btn").removeClass("poker__showcards-btn").addClass("poker__hidecards-btn").text("Hide Cards");
<?php
hand_hook();
