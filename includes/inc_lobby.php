<?php
if ( LANDLOBBY == 0 && $valid == false )
{
	header('Location: login.php');
}

$is_omaha = false;
$is_omaha = $addons->get_hooks(
    array(
        'state' => $is_omaha,
        'content' => $is_omaha,
    ),
    array(
        'page'     => 'includes/inc_lobby.php',
        'location'  => 'omaha_logic'
    ));

$gameID = (isset($_GET['gameID']))  ? addslashes($_GET['gameID'])  : '';
$gameID = (isset($_POST['gameID'])) ? addslashes($_POST['gameID']) : $gameID;

if ($gameID != '')
{
    $gq = $pdo->prepare("SELECT * FROM " . DB_POKER . " WHERE gameID = ?");
    $gq->execute(array($gameID));

    if ($gq->rowCount() == 1)
    {
        $tabler = $gq->fetch(PDO::FETCH_ASSOC);

        if ($tabler['gamestyle'] == 'o' && $is_omaha == false)
        {
            echo "<script type='text/javascript'>alert('" . __( 'Please install Omaha hold em add-on.', 'core' ) . "');</script>";
            return;
        }

        $enterGame = $addons->get_hooks(
            array(
                'content' => true
            ),
            array(
                'page'     => 'includes/inc_lobby.php',
                'location'  => 'enter_game_logic'
            )
        );
        if ($enterGame)
        {
            $gamepass = randomcode(10);
            $passQ    = $pdo->prepare("UPDATE `" . DB_PLAYERS . "` SET `gamepass` = ?, `vID` = ? WHERE `username` = ?");
            $passQ->execute([ $gamepass, $gameID, $plyrname ]);

            header('Location: poker.php');
            die();
        }
    }

    header('Location: lobby.php');
    die();
}
?>