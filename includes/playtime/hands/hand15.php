<?php
$logic = ($autoplayer == getplayerid($plyrname)) ? true : false;
$logic = $addons->get_hooks(
    array(
        'state' => $logic,
        'content' => $logic,
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location'  => 'hand15_logic'
));
if ($logic)
{
    $cardq = $pdo->prepare("SELECT * FROM " . DB_POKER . " WHERE gameID = " . $gameID);

    $cardq->execute();
    $cardr      = $cardq->fetch(PDO::FETCH_ASSOC);
    $tablecards = array(
        decrypt_card($cardr['card1']),
        decrypt_card($cardr['card2']),
        decrypt_card($cardr['card3']),
        decrypt_card($cardr['card4']),
        decrypt_card($cardr['card5'])
    );

    $multiwin   = find_winners($game_style);
    $i          = 0;

    while ($multiwin[$i] != '')
    {
        $usr    = get_name($multiwin[$i]);
        $result = $pdo->query("UPDATE " . DB_STATS . " SET handswon = handswon+1 WHERE player = '{$usr}' ");

        if ($usr === $plyrname)
        {
            $_SESSION['nofold_handswon']++;
            $_SESSION['noleave_handswon']++;

            if (isset($_SESSION['went_allin']) && $_SESSION['allin_this_hand'])
            {
                $_SESSION['cont_allin_win_count']++;
            }

            if ($_SESSION['nofold_handswon'] > $usestats['nofold_handswon'])
                $pdo->query("UPDATE " . DB_STATS . " SET nofold_handswon = {$_SESSION['nofold_handswon']} WHERE player = '{$plyrname}'");
            
            if ($_SESSION['noleave_handswon'] > $usestats['noleave_handswon'])
                $pdo->query("UPDATE " . DB_STATS . " SET noleave_handswon = {$_SESSION['noleave_handswon']} WHERE player = '{$plyrname}'");
        }

        ?>
        if (! jQuery("#player-<?php echo $multiwin[$i]; ?>").find(".poker__user-photo").hasClass("winner-boy"))
            jQuery("#player-<?php echo $multiwin[$i]; ?>").find(".poker__user-photo").addClass("winner-boy");
        <?php
        $i++;
    }

    distpot($game_style);
    $result = $pdo->query("UPDATE " . DB_POKER . " SET hand = 0, pot = 0, lastmove = " . ($timenow) . " WHERE gameID = {$gameID}");
    hand_hook();
}
