<?php
// $a = 5;

// switch ($a) {
//     case 5:
//         echo 'five';
//     case 7:
//         echo 'seven';
//     case 9:
//         echo 'nine';
//     case 11:
//         echo 'eleven';
//         echo 'odd';
//         break;
    
//     default:
//         echo 'even';
//         break;
// }

$pdo->query(
    "UPDATE " . DB_POKER . "
    SET
        move = 0,
        lastmove = " . time() . ",
        dealer = 0,
        msg = '',
        hand = -1,
        bet = 0,
        pot = 0,

        p1name = '',
        p1bet = '0',
        p1pot = '0',
        p1card1 = '',
        p1card2 = '',

        p2name = '',
        p2bet = '0',
        p2pot = '0',
        p2card1 = '',
        p2card2 = '',

        p3name = '',
        p3bet = '0',
        p3pot = '0',
        p3card1 = '',
        p3card2 = '',

        p4name = '',
        p4bet = '0',
        p4pot = '0',
        p4card1 = '',
        p4card2 = '',

        p5name = '',
        p5bet = '0',
        p5pot = '0',
        p5card1 = '',
        p5card2 = '',

        p6name = '',
        p6bet = '0',
        p6pot = '0',
        p6card1 = '',
        p6card2 = '',

        p7name = '',
        p7bet = '0',
        p7pot = '0',
        p7card1 = '',
        p7card2 = '',

        p8name = '',
        p8bet = '0',
        p8pot = '0',
        p8card1 = '',
        p8card2 = '',

        p9name = '',
        p9bet = '0',
        p9pot = '0',
        p9card1 = '',
        p9card2 = '',

        p10name = '',
        p10bet = '0',
        p10pot = '0',
        p10card1 = '',
        p10card2 = ''
    WHERE gameID = {$gameID}"
);

// $v1 = 10;

// $a1 = [
//     'a' => 1,
//     'b' => &$v1
// ];

// function bal($a, &$v1)
// {
    
// }

// bal('a', 20);
// var_dump($v1);
// var_dump($a1);