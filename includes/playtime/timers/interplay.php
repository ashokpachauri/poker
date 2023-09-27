<?php
$counter = ($lastmove + $movetimer) - $time;

if ($counter < 0)
    $counter = 0;

$opsTheme->addVariable('timer', array(
    'total' => $movetimer,
    'left'  => $counter
));

$i = 1;
while ($i < 11)
{
    $opsTheme->addVariable('seat_number', $i);

    if ($i == $autoplayer)
    {
        if ($tpr["p{$i}name"] === $plyrname)
        {
            if (TMRLEFTSOUND === 'on')
                $opsTheme->addVariable('play', array('timerleft' => 'yes'));
            else
                $opsTheme->addVariable('play', array('timerleft' => 'no'));
        }
        else
            $opsTheme->addVariable('play', array('timerleft' => 'no'));

        echo $opsTheme->viewPart('poker-player-timer-start-js');
    }
    else
    {
        $opsTheme->addVariable('play', array('timerleft' => 'no'));
        echo $opsTheme->viewPart('poker-player-timer-stop-js');
    }

    $i++;
}

echo $addons->get_hooks(array(), array(
    'page'     => 'includes/auto_move.php',
    'location' => 'after_opponent_timer_js'
));
