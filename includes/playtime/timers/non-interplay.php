<?php
for ($i = 1; $i < 11; $i++)
{
    $opsTheme->addVariable('seat_number', $i);
    echo $opsTheme->viewPart('poker-player-timer-stop-js');
}
