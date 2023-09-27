<?php
$players  = '';
$playersQ = $pdo->query("SELECT username FROM " . DB_PLAYERS);
while ($playerF = $playersQ->fetch(PDO::FETCH_ASSOC))
{
	$opsTheme->addVariable('opt', array(
		'value' => $playerF['username'],
		'text'  => $playerF['username']
	));
	$players .= $opsTheme->viewPart('admin-sitelog-opt-each');
}

$opsTheme->addVariable('options', array(
	'players' => $players
));

$whereSql = (isset($_GET['player']) && !empty($_GET['player'])) ? " WHERE player = '{$_GET['player']}' " : '';


$totalQ = $pdo->query("SELECT COUNT(ID) AS num FROM sitelog {$whereSql}");
$totalF = $totalQ->fetch(PDO::FETCH_ASSOC);
$total  = $totalF['num'];

$page = (isset($_GET['page'])) ? ((int) $_GET['page']) : 1;
$page = ($page < 1) ? 1 : $page;

$limit = 15;
$start = ($page - 1) * $limit;

$targetPage = (isset($_GET['player']) && !empty($_GET['player'])) ? 'admin.php?admin=sitelog&player=' . $_GET['player'] : 'admin.php?admin=sitelog';

$logView = '';
$logs    = $pdo->query("SELECT * FROM sitelog {$whereSql} ORDER BY ID DESC LIMIT $start, $limit");
while ($log = $logs->fetch(PDO::FETCH_ASSOC))
{
	$opsTheme->addVariable('log', array(
		'id'      => $log['ID'],
		'report'  => $log['log'],
		'time'    => date('d F Y, h:i:s A', strtotime($log['dt']))
	));

	$logView .= $opsTheme->viewPart('admin-sitelog-each');
}

$opsTheme->addVariable('logs', $logView);
$opsTheme->addVariable('pagination', OPS_pagination(array(
	'page'       => $page,
	'start'      => $start,
	'limit'      => $limit,
	'total'      => $total,
	'adjacent'   => 3,
	'targetPage' => $targetPage
)));
echo $opsTheme->viewPage('admin-sitelog');
