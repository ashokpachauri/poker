<?php
require('includes/inc_poker.php');

$addons->get_hooks(array(), array(
    'page'     => 'poker.php',
    'location'  => 'page_start'
));

if (! isset($tablestyle))
    $tablestyle = 'table_green';

// Table type
switch ($tabletype)
{
    case 'c':
        $ttype = CASHGAMES;
        break;
    
    case 's':
        $ttype = SITNGO;
        break;
    
    default:
        $ttype = TOURNAMENT;
        break;
}

// Blinds amount
if ($sbamount != 0)
{
    $SB = $sbamount;
    $BB = $bbamount;

    $getblinds = money_small($sbamount, true, $moneyPrefix) . '/' . money_small($bbamount, true, $moneyPrefix);
}
else
{
    if ($tabletype != 't')
        $blindmultiplier = 4;

    switch ($tablelimit)
    {
        case 25000:
            $tablemultiplier = 2;
            break;
        
        case 50000:
            $tablemultiplier = 4;
            break;
        
        case 100000:
            $tablemultiplier = 8;
            break;
        
        case 250000:
            $tablemultiplier = 20;
            break;
        
        case 500000:
            $tablemultiplier = 40;
            break;
        
        case 1000000:
            $tablemultiplier = 80;
            break;
        
        default:
            $tablemultiplier = 1;
            break;
    }

    if ($tabletype == 't')
    {
    	$SB = money_small( (25 * $tablemultiplier), true, $moneyPrefix) . '-' . money_small( (25 * $tablemultiplier * 9), true, $moneyPrefix);
    	$BB = money_small( (50 * $tablemultiplier), true, $moneyPrefix) . '-' . money_small( (50 * $tablemultiplier * 9), true, $moneyPrefix);
    }
    else
    {
    	$SB = money_small( (100 * $tablemultiplier), true, $moneyPrefix);	
    	$BB = money_small( (200 * $tablemultiplier), true, $moneyPrefix);
    }

    $getblinds = $SB . '/' . $BB;
}

// Buy in amount
$buyin = ($tabletype == 't') ? money_small($tablelimit, true, $moneyPrefix) : money_small($min, true, $moneyPrefix) . '/' . money_small($tablelimit, true, $moneyPrefix);	


// Page title
$title  = stripslashes($tablename) . ' - ';
$title .= $ttype.' - '.$buyin;
//$title .= ($tabletype == 't') ? ' + ' . money_small( $tr['rake'], true, $moneyPrefix ) : '';


$opsTheme->addVariable('player', stripslashes($plyrname));
$opsTheme->addVariable('tablename', stripslashes($tablename));
$opsTheme->addVariable('tabletype', $ttype);
$opsTheme->addVariable('tableid', stripslashes($tableid));
$opsTheme->addVariable('tablestyle', $tablestyle);
$opsTheme->addVariable('blinds', $getblinds);
$opsTheme->addVariable('buyinamount', $buyin);

$tableLog = $addons->get_hooks(
    array(
        'content' => ''
    ),
    array(
        'page'      => 'poker.php',
        'location'  => 'table_log',
    )
);
$opsTheme->addVariable('table', array(
    'name'        => stripslashes($tablename),
    'type'        => $ttype,
    'id'          => stripslashes($tableid),
    'style'       => $tablestyle,
    'blinds'      => $getblinds,
    'small_blind' => $SB,
    'big_blind'   => $BB,
    'log'         => $tableLog,
));

$seatHtml = '';
for ($i = 1; $i < 11; $i++)
{
    $opsTheme->addVariable('seat_number', $i);

    $seatCircle = poker_seat_circle_html($i);
    $opsTheme->addVariable('seat_circle', $seatCircle);

    $seatHtml .= $addons->get_hooks(
        array(
            'index'   => $i,
            'content' => $opsTheme->viewPart('poker-player-each'),
        ),

        array(
            'page'      => 'poker.php',
            'location'  => 'each_seat',
        )
    );
}
$opsTheme->addVariable('players', $seatHtml);

// Table Cards
for ($i = 1; $i < 6; $i++)
    $opsTheme->addVariable('card' . $i, $opsTheme->viewPart('poker-card-table-hidden'));


$dealerchat = '';
$cq    = $pdo->prepare("SELECT * FROM " . DB_LIVECHAT . " WHERE gameID = " . $tableid);
$cq->execute();

if ($cq->rowCount() > 0)
{
    $cr    = $cq->fetch(PDO::FETCH_ASSOC);
    $time  = time();

    if ($cr['updatescreen'] < $time)
    {
        $i    = 1;
        $chat = '';

        for ($i = 1; $i < 6; $i++):
            $cThis = $cr['c' . $i];

            if (empty($cThis))
                continue;

            $lMsg = "<log>{$cThis}</log>";
            $lXml = simplexml_load_string($lMsg);

            $opsTheme->addVariable('chatter', array(
                'id'     => intval($lXml->user->id),
                'name'   => strval($lXml->user->name),
                'avatar' => strval($lXml->user->avatar),
            ));
            $opsTheme->addVariable('message', strval($lXml->message));

            $chat .= $opsTheme->viewPart('poker-log-message');
        endfor;

        $dealerchat .= $chat;
    }
}
$opsTheme->addVariable('dealerchat', $dealerchat);


// $userchat = '';
// $ucq      = $pdo->prepare("SELECT * FROM " . DB_USERCHAT . " WHERE gameID = " . $tableid);
// $ucq->execute();
// if ($ucq->rowCount() > 0)
// {
//     $ucr      = $ucq->fetch(PDO::FETCH_ASSOC);
//     $time     = time();

//     if ($ucr['updatescreen'] < $time)
//     {
//         $i    = 1;
//         $chat = '';

//         while ($i < 6)
//         {
//             $cThis = $ucr['c' . $i];

//             if (empty($cThis))
//             {
//                 $i++;
//                 continue;
//             }

//             $uMsg = "<chat>{$cThis}</chat>";
//             $uXml = simplexml_load_string($uMsg);

//             $opsTheme->addVariable('chatter', array(
//                 'id'     => (int) $uXml->user->id,
//                 'name'   => (string) $uXml->user->name,
//                 'avatar' => (string) $uXml->user->avatar,
//             ));
//             $opsTheme->addVariable('message', (string) $uXml->message);

//             if ($uXml->user->name == $plyrname)
//                 $chat .= $opsTheme->viewPart('poker-chat-message-me');
//             else
//                 $chat .= $opsTheme->viewPart('poker-chat-message-other');
            
//             $i++;
//         }

//         $userchat .= $chat;
//     }
// }
// $opsTheme->addVariable('userchat', $userchat);

$opsTheme->addVariable('checkboxes', $opsTheme->viewPart('poker-button-checkboxes'));

$opsTheme->addVariable('sound', array(
    'card'      => soundPath('card'),
    'chat'      => soundPath('chat'),
    'check'     => soundPath('check'),
    'chips'     => soundPath('chips'),
    'deal'      => soundPath('deal'),
    'flop'      => soundPath('flop'),
    'fold'      => soundPath('fold'),
    'shuffle'   => soundPath('shuffle'),
    'timerleft' => soundPath('timerleft'),
));

include 'templates/header.php';

echo $addons->get_hooks(array(), array(

    'page'     => 'poker.php',
    'location'  => 'html_start'

));

echo $opsTheme->viewPage('poker');

echo $addons->get_hooks(array(), array(

    'page'     => 'poker.php',
    'location'  => 'html_end'

));

include 'templates/footer.php';
?>