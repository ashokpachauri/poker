<?php
require('sec_inc.php');
header('Content-Type: text/javascript');

echo $addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/auto_chat.php',
        'location'  => 'page_start'
    )
);

$time = time();

$cq = $pdo->query("SELECT * FROM " . DB_LIVECHAT . " WHERE gameID = " . $gameID);
$cr = $cq->fetch(PDO::FETCH_ASSOC);
$lastLog = '';

if ($cr['updatescreen'] > $time)
{
    $i    = 1;
    $chat = '';

    while ($i < 6)
    {
        $cThis = $cr['c' . $i];

        if (empty($cThis))
        {
            $i++;
            continue;
        }

        $lMsg = "<log>{$cThis}</log>";
        $lXml = simplexml_load_string($lMsg);

        $opsTheme->addVariable('chatter', array(
            'id'     => (int) $lXml->user->id,
            'name'   => (string) $lXml->user->name,
            'avatar' => (string) $lXml->user->avatar,
        ));
        $opsTheme->addVariable('message', (string) $lXml->message);
        $lastLog = $opsTheme->viewPart('poker-log-message');

        $chat .= $lastLog;
        $i++;
    }
?>
var chatxt = '<?php echo $chat; ?>';
document.getElementById('chatdiv').innerHTML = chatxt;

<?php if (!empty($cThis)) { ?>
if (typeof(document.getElementById('tablelog')) != 'undefined')
{
    document.getElementById('tablelog').innerHTML = '<?php echo $lastLog; ?>';
}
<?php
    }
}

$ucq = $pdo->query("SELECT * FROM " . DB_USERCHAT . " WHERE gameID = {$gameID}");

if ( !$ucq->rowCount() )
    die;

$ucr = $ucq->fetch(PDO::FETCH_ASSOC);

if ($ucr['updatescreen'] > $time)
{
    $i    = 1;
    $chat = '';

    while ($i < 6)
    {
        $cThis = $ucr['c' . $i];

        if (empty($cThis))
        {
            $i++;
            continue;
        }

        $uMsg = "<chat>{$cThis}</chat>";
        $uXml = simplexml_load_string($uMsg);

        $opsTheme->addVariable('chatter', array(
            'id'     => (int) $uXml->user->id,
            'name'   => (string) $uXml->user->name,
            'avatar' => (string) $uXml->user->avatar,
        ));
        $opsTheme->addVariable('message', (string) $uXml->message);

        if ($uXml->user->name == $plyrname)
            $chat .= $opsTheme->viewPart('poker-chat-message-me');
        else
            $chat .= $opsTheme->viewPart('poker-chat-message-other');

        $i++;
    }
?>
var userchatxt = '<?php echo $chat; ?>';
document.getElementById('userchatdiv').innerHTML = userchatxt;

if (userchatxt != '')
    document.getElementById("aud-chat").play();
<?php
}
