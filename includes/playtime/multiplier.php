<?php
$blindmultiplier = ($tabletype === 't') ? (11 - $allpl) : 4;
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

$blindmultiplier = $addons->get_hooks(
    array(
        'content' => $blindmultiplier
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'blind_multiplier'
    )
);
$tablemultiplier = $addons->get_hooks(
    array(
        'content' => $tablemultiplier
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'table_multiplier'
    )
);

$oSB = ($sbamount != 0) ? $sbamount : (25 * $blindmultiplier * $tablemultiplier);
$oBB = ($sbamount != 0) ? $bbamount : (50 * $blindmultiplier * $tablemultiplier);

$SB = $addons->get_hooks(
    array(
        'content' => $oSB
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'small_blind'
    )
);
$BB = $addons->get_hooks(
    array(
        'content' => $oBB
    ),
    array(
        'page'     => 'includes/auto_move.php',
        'location' => 'big_blind'
    )
);
