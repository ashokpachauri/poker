<?php
$currentUpdNum = (int) ADDONUPDATEA;

// Install new addon
if (isset($_POST['install_addon'], $_POST['download_url']))
{
    $downloadUrl = $_POST['download_url'];
    $addonDir    = 'includes/addons/';
    $filename    = basename($downloadUrl);
    $zipFile     = $addonDir . $filename;

    if (file_put_contents( $zipFile, file_get_contents_ssl($downloadUrl)))
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFile) === true)
        {
            $zip->extractTo($addonDir);
			$zip->close();
            unlink($zipFile);

            echo '<meta http-equiv="refresh" content="0;url=?admin=addons&install=success">';
        }
        else
            echo '<meta http-equiv="refresh" content="0;url=?admin=addons&install=failed">';
    }
}

// Upload new addon
if (isset($_POST['upload_addon']) && is_uploaded_file($_FILES['addon_zip_file']['tmp_name']))
{
	$file = $_FILES['addon_zip_file'];
	$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
	$done = false;

	if ($extension === 'zip')
	{
		$addonDir = 'includes/addons/';
		$zip = new ZipArchive;
		
		if ($zip->open($file['tmp_name']) === TRUE)
		{
			$zip->extractTo($addonDir);
			$zip->close();
			$done = true;

			echo '<meta http-equiv="refresh" content="0;url=?admin=addons&upload=success">';
		}
		else
			echo '<meta http-equiv="refresh" content="0;url=?admin=addons&upload=failed">';
	}
}


// Activate / Deactivate Addons
if (isset($_POST['change_addon_status'], $_POST['addon']))
{
	$addonDir = 'includes/addons/' . str_replace('/[^A-Za-z0-9_-]/i', '', $_POST['addon']);
	$activateFile = $addonDir . '/activated.html';

	if (file_exists($addonDir . '/init.php'))
	{
		if (isset($_POST['activate']))
		{
			file_put_contents($activateFile, true);

			if (! file_exists('includes/addons/settings')) mkdir('includes/addons/settings');

			$addonSettingsFile = str_replace('includes/addons', 'includes/addons/settings', $addonDir) . '.json';
			if (! file_exists($addonSettingsFile))
			{
				$addonConfigFile   = $addonDir   . '/config.json';
				if (file_exists($addonConfigFile))
				{
					$settings = array();
					$configs  = json_decode(file_get_contents($addonConfigFile), true);

					foreach ($configs as $config)
					{
						$settings[$config['name']] = (isset($config['default'])) ? $config['default'] : '';
					}

					if (! file_exists('includes/addons/settings')) mkdir('includes/addons/settings');
					file_put_contents($addonSettingsFile, json_encode($settings));
				}
			}
		}
		elseif (isset($_POST['deactivate']))
		{
			unlink($activateFile);
		}
	}
}


// Update Addon
if (isset($_GET['update_addon']))
{
	$addon     = $_GET['update_addon'];
	$addonDir  = 'includes/addons/' . str_replace('/[^A-Za-z0-9_-]/i', '', $addon);
	$infoFile  = $addonDir . '/info.json';
	$zipFile   = $addonDir . '/update.zip';
	$addonPath = realpath($addonDir);

	if (file_exists($infoFile))
	{
		$addonInfo       = json_decode(file_get_contents($infoFile));
		$addonUpdateUrl  = $addonInfo->update_url;
		$addonUpdateJson = json_decode(file_get_contents_ssl($addonUpdateUrl, array(
			'ip'       => get_user_ip_addr(),
	        'domain'   => preg_replace('/[^A-Za-z0-9-.]/i', '', $_SERVER['SERVER_NAME']),
	        'license'  => LICENSEKEY,
	        'version'  => $addonInfo->version,
	        'download' => true
		)));

		if (isset($addonUpdateJson->status) && $addonUpdateJson->status === "OK")
		{
			if (file_put_contents($zipFile, file_get_contents_ssl($addonUpdateJson->url)))
			{
				$zip = new ZipArchive;
    			
    			if ($zip->open($zipFile) === true)
				{
					$addonInfo->version = $addonUpdateJson->version;
					file_put_contents($infoFile, json_encode($addonInfo));

					$zip->extractTo($addonPath);
					$zip->close();

					unlink($zipFile);

					$currentUpdNum--;
					$pdo->exec("UPDATE " . DB_SETTINGS . " SET Xvalue = '{$currentUpdNum}' WHERE setting = 'addonupdatea'");
					echo '<meta http-equiv="refresh" content="0;url=?admin=addons">';
				}
			}
		}
	}
}
elseif (isset($_GET['delete_addon']))
{
	$addon     = $_GET['delete_addon'];
	$addonDir  = 'includes/addons/' . str_replace('/[^A-Za-z0-9_-]/i', '', $addon);
	$addonPath = realpath($addonDir);

    $it = new RecursiveDirectoryIterator( $addonPath, RecursiveDirectoryIterator::SKIP_DOTS );
    $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $files as $file )
    {
        if ($file->isDir())
            rmdir($file->getRealPath());
        else
            unlink($file->getRealPath());
    }
    rmdir($addonDir);

    header('Location: ?admin=addons');
}
elseif (isset($_COOKIE['upd-addons']))
{
    $updAddons = array_values(json_decode($_COOKIE['upd-addons'], true));

    if (count($updAddons) < 1)
    {
        unset($_COOKIE['upd-addons']);
        setcookie( 'upd-addons', json_encode(array()), time() - 60 );
        header("Location: ?admin=addons");
        die();
    }

    $updAddon = $updAddons[0];

    unset($updAddons[0]);
    $updAddons = array_values($updAddons);
    setcookie( 'upd-addons', json_encode($updAddons), time() + (60 * 60) );

    header("Location: ?admin=addons&update_addon=" . $updAddon);
    die();
}
elseif (isset($_COOKIE['del-addons']))
{
    $delAddons = array_values(json_decode($_COOKIE['del-addons'], true));

    if (count($delAddons) < 1)
    {
        unset($_COOKIE['del-addons']);
        setcookie( 'del-addons', json_encode(array()), time() - 60 );
        header("Location: ?admin=addons");
        die();
    }

    $delAddon = $delAddons[0];

    unset($delAddons[0]);
    $delAddons = array_values($delAddons);
    setcookie( 'del-addons', json_encode($delAddons), time() + (60 * 60) );

    header("Location: ?admin=addons&delete_addon=" . $delAddon);
    die();
}

if (isset($_POST['update_addons']) && is_array($_POST['update_addons']))
{
    $updAddons = $_POST['update_addons'];
    setcookie( 'upd-addons', json_encode($updAddons), time() + (60 * 60) );
    die();
}
elseif (isset($_POST['delete_addons']) && is_array($_POST['delete_addons']))
{
    $delAddons = $_POST['delete_addons'];
    setcookie( 'del-addons', json_encode($delAddons), time() + (60 * 60) );
    die();
}

// Install
$installs    = json_decode(file_get_contents_su('https://updates.onlinepokerscript.com/addons.php'));
$installHtml = '';

if (isset($installs->status) && $installs->status === 'OK')
{
	foreach ($installs->addons as $addon)
	{
		$installed = (file_exists('includes/addons/' . $addon->slug)) ? true : false;

		$opsTheme->addVariable('addon', $addon);
		$addon->button = ($installed) ? $opsTheme->viewPart('admin-addon-install-each-btn-disabled') : $opsTheme->viewPart('admin-addon-install-each-btn');

		$opsTheme->addVariable('addon', $addon);
		$installHtml .= $opsTheme->viewPart('admin-addon-install-each');
	}
}

$opsTheme->addVariable('installs', $installHtml);

// Upload
if (class_exists('ZipArchive'))
	$upload = $opsTheme->viewPart('admin-addon-upload');
else
	$upload = '<p>You need to enable Zip extension in your server</p>';

$opsTheme->addVariable('upload', $upload);

$rows         = '';
$addonFolders = glob('includes/addons/*', GLOB_ONLYDIR);

foreach ($addonFolders as $addonFolder)
{
	$infos			 = (object) array();
	$addonName		 = str_replace('includes/addons/', '', $addonFolder);
	$addonInitFile	 = $addonFolder . '/init.php';
	$addonInfoFile	 = $addonFolder . '/info.json';
	$addonConfigFile = $addonFolder . '/config.json';

	if (! file_exists($addonInitFile))
		continue;

	if (file_exists($addonInfoFile))
	{
		$addonInfo = file_get_contents($addonInfoFile);
		$infos     = json_decode($addonInfo);
	}

	$empty = '<small>empty</small>';

	$infos->id          = $addonName;
	$infos->name	    = (isset($infos->name))		   ? $infos->name		 : $empty;
	$infos->description = (isset($infos->description)) ? $infos->description : $empty;
	$infos->author	    = (isset($infos->author))	   ? $infos->author	     : $empty;
	$infos->version     = (isset($infos->version))	   ? $infos->version	 : $empty;

	$opsTheme->addVariable('addon', $infos);

	$activated = false;
	$update    = false;

	if (file_exists($addonFolder . '/activated.html'))
		$activated = true;

	if ($currentUpdNum > 0 && isset($infos->update_url))
	{
		$addonUpdateUrl  = $infos->update_url;
		$addonUpdateJson = json_decode(file_get_contents_ssl($addonUpdateUrl, array(
			'ip'      => get_user_ip_addr(),
	        'domain'  => preg_replace('/[^A-Za-z0-9-.]/i', '', $_SERVER['SERVER_NAME']),
	        'license' => LICENSEKEY,
	        'version' => $infos->version,
		)));

		if (isset($addonUpdateJson->status) && $addonUpdateJson->status === "OK")
			$update = true;
	}

	if ($activated)
		$infos->activate = $opsTheme->viewPart('admin-addon-deactivate-button');
	else
		$infos->activate = $opsTheme->viewPart('admin-addon-activate-button');

	if ($activated && file_exists($addonConfigFile))
		$infos->settings = $opsTheme->viewPart('admin-addon-settings-button');

	if ($update)
		$infos->update = $opsTheme->viewPart('admin-addon-update-button');
	else
		$infos->update = '<span>Up to date</span>';

	$opsTheme->addVariable('addon', $infos);
	$rows .= $opsTheme->viewPart('admin-addon-row-each');
}

$opsTheme->addVariable('rows', $rows);
echo $opsTheme->viewPage('admin-addons');
?>