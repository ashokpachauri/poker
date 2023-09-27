<?php
if ($game_style == GAME_TEXAS)
{
    $cardq = $pdo->prepare("select card1, card2, card3, card4, card5, p1card1, p1card2, p2card1, p2card2, p3card1, p3card2, p4card1, p4card2, p5card1, p5card2, p6card1, p6card2, p7card1, p7card2, p8card1, p8card2, p9card1, p9card2, p10card1, p10card2 from " . DB_POKER . " WHERE gameID = " . $gameID);
}
else
{
    $cardq = $pdo->prepare("select card1, card2, card3, card4, card5, p1card1, p1card2, p1card3, p1card4, p2card1, p2card2, p2card3, p2card4, p3card1, p3card2, p3card3, p3card4, p4card1, p4card2, p4card3, p4card4, p5card1, p5card2, p5card3, p5card4, p6card1, p6card2, p6card3, p6card4, p7card1, p7card2, p7card3, p7card4, p8card1, p8card2, p8card3, p8card4, p9card1, p9card2, p9card3, p9card4, p10card1, p10card2, p10card3, p10card4 from " . DB_POKER . " WHERE gameID = " . $gameID);
}

$cardq->execute();
$cardr = $cardq->fetch(PDO::FETCH_ASSOC);
$tablecards = array(
    decrypt_card($cardr['card1']),
    decrypt_card($cardr['card2']),
    decrypt_card($cardr['card3']),
    decrypt_card($cardr['card4']),
    decrypt_card($cardr['card5'])
);

$multiwin = find_winners($game_style);
$winners  = (($multiwin[1] == '') ? 1 : 2);
$thiswin  = evaluatewin($multiwin[0], $game_style);
$thiswin  = addslashes($thiswin);

if ($winners > 1)
{
    $msg = GAME_MSG_SPLIT_POT_RESULT . ' ' . $thiswin;
    poker_log('', $msg, $gameID);
}
else
{
    $playersThatDidNotFold = $np - $fo;

    if ($playersThatDidNotFold == 1)
    {
        $msg = get_name($multiwin[0]) . GAME_MSG_ALLFOLD;
        poker_log( get_name($multiwin[0]), GAME_MSG_ALLFOLD, $gameID);

        // if (! jQuery("#player-$multiwin[0]").find(".poker__user-photo").hasClass("winner-boy"))
        //     jQuery("#player-$multiwin[0]").find(".poker__user-photo").addClass("winner-boy");
    }
    else
    {
        $msg = get_name($multiwin[0]) . ' wins the hand with a' . $thiswin;
        poker_log( get_name($multiwin[0]), 'wins the hand with a' . $thiswin, $gameID);
        
        // if (! jQuery("#player-$multiwin[0]").find(".poker__user-photo").hasClass("winner-boy"))
        //     jQuery("#player-$multiwin[0]").find(".poker__user-photo").addClass("winner-boy");
    }
}

$result = $pdo->query("UPDATE " . DB_POKER . " SET msg = '{$msg}', hand = 14, lastmove = " . ($timenow) . "  WHERE gameID = {$gameID}");
hand_hook();
