<?php
$dir     = (isset($_GET['dir'])) ? strtoupper(addslashes($_GET['dir'])) : 'ASC';
$col     = (isset($_GET['col'])) ? addslashes($_GET['col']) : DB_STATS . '.rank,' . DB_PLAYERS . '.ID';
$searchQ = '';

if (isset($_GET['q']) && !empty($_GET['q']))
{
	$sQ = $_GET['q'];
	$searchQ = "AND " . DB_PLAYERS . ".username LIKE '%{$sQ}%'";
}

$addons->get_hooks(array(), array(
    'page'     => 'admin/members.php',
    'location'  => 'tabs_left'
));

$query       = "SELECT " . DB_STATS . ".rank, " . DB_STATS . ".player, " . DB_PLAYERS . ".ID 
FROM " . DB_PLAYERS . " 
LEFT JOIN " . DB_STATS . " ON " . DB_PLAYERS . ".username = " . DB_STATS . ".player 
GROUP BY " . DB_STATS . ".rank, " . DB_STATS . ".player, " . DB_PLAYERS . ".ID 
ORDER BY " . DB_STATS . ".rank, " . DB_PLAYERS . ".ID ASC";
# echo $query; die;
$total_query = $pdo->query($query);
$total_pages = $total_query->fetch(PDO::FETCH_ASSOC);
$total       = $total_query->rowCount();

/* Setup vars for query. */
$page       = (isset($_GET['page'])) ? ((int) $_GET['page']) : 1;
$limit      = 10; 	//how many items to show per page

if ($page)
	$start = ($page - 1) * $limit;      //first item to display on this page
else
	$start = 0; //if no page var is given, set start to 0


/* Setup page vars for display. */
if ($page == 0)
	$page = 1;					//if no page var is given, default to 1.

$rows = '';
$plq  = $pdo->query(str_replace('SELECT ', 'SELECT ' . DB_PLAYERS . '.*,', $query) . " LIMIT {$start}, {$limit}");

while ( $plr = $plq->fetch(PDO::FETCH_ASSOC) )
{
	$plr['datecreated'] = date("m-d-Y", $plr['datecreated']);
	if (strlen($plr['email']) < 1)
		$plr['email'] = '-';

	$pname = $plr['username'];
	$pban = $plr['banned'];
	$pdate = $plr['datecreated'];
	$pip = $plr['ipaddress'];
	$prank = $plr['rank'];
	$papprove = $plr['approve'];
	$pemail = $plr['email'];

	$opsTheme->addVariable('player', $plr);

	if ($papprove == 1)
		$plr['approval'] = $opsTheme->viewPart('admin-members-row-approve');
	else
		$plr['approval'] = $opsTheme->viewPart('admin-members-row-active');

	if ($plyrname != $pname)
	{
		if ($pban == 0)
			$plr['ban'] = $opsTheme->viewPart('admin-members-row-ban');
		else
			$plr['ban'] = $opsTheme->viewPart('admin-members-row-unban');

		$plr['delete'] = $opsTheme->viewPart('admin-members-row-delete');
	}

	$plr['reset'] = $opsTheme->viewPart('admin-members-row-reset');
	$opsTheme->addVariable('player', $plr);

	$rows .= $opsTheme->viewPart('admin-members-row-each');
}
$opsTheme->addVariable('rows', $rows);
$opsTheme->addVariable('pagination', OPS_pagination(array(
	'page'       => $page,
	'start'      => $start,
	'limit'      => $limit,
	'total'      => $total,
	'adjacent'   => 3,
	'targetPage' => 'admin.php?admin=members'
)));
add_members_tab('members', __( 'All Members', 'core' ), $opsTheme->viewPart('admin-members-tabpanel-default'));

$addons->get_hooks(array(), array(
    'page'     => 'admin/members.php',
    'location'  => 'tabs_right'
));

/**/
$navHtml      = '';
$tabpanelHtml = '';

foreach ($membersNavs as $mNavId => $mNav)
{
	$opsTheme->addVariable('tab', array(
		'id'   => $mNavId,
		'name' => $mNav['name'],
		'html' => $mNav['html']
	));

	$navHtml .= $opsTheme->viewPart('admin-members-tab');
	$tabpanelHtml .= $opsTheme->viewPart('admin-members-tabpanel');
}

$opsTheme->addVariable('members', array(
	'tabs'      => $navHtml,
	'tabpanels' => $tabpanelHtml
));
/**/

echo $opsTheme->viewPage('admin-members');

/* Functions */
function add_members_tab($id, $name, $html)
{
	if (! isset($GLOBALS['membersNavs']))
		$GLOBALS['membersNavs'] = array();

	global $membersNavs;
	$membersNavs[$id] = array(
		'name' => $name,
		'html' => $html
	);

	return true;
}
?>