<?php
foreach (glob(__DIR__ . '/*', GLOB_ONLYDIR) as $addonFolder)
{
    if ($addonFolder === 'settings')
        continue;

    $initFile       = $addonFolder . '/init.php';
	$activateFile   = $addonFolder . '/activated.html';

    $installFile    = $addonFolder . '/install.php';
	$installCheck   = $addonFolder . '/installed.html';

    $updateFile    = $addonFolder . '/sql.php';

    if (! file_exists($initFile) || ! file_exists($activateFile))
        continue;
    
    if (file_exists($installFile))
    {
    	if (! file_exists($installCheck))
    	{
    		require $installFile;
            file_put_contents($installCheck, 1);
    	}
    }

    if (file_exists($updateFile))
    {
        include $updateFile;
        unlink($updateFile);
    }

    require $initFile;
}
