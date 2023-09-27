<?php
$landLobbies = array(
	array('value' => '0', 'label' => __( 'No', 'core' )),
	array('value' => '1', 'label' => __( 'Yes', 'core' )),
);
$opsTheme->addVariable('land_lobbies', $landLobbies);

$emailModes = array(
	array('value' => '0', 'label' => __( 'Off', 'core' )),
	array('value' => '1', 'label' => __( 'On', 'core' )),
);
$opsTheme->addVariable('email_modes', $emailModes);


$appModes = array(
	array('value' => '0', 'label' => __( 'Automatic', 'core' )),
	array('value' => '1', 'label' => __( 'Email Approval', 'core' )),
	array('value' => '2', 'label' => __( 'Administrator Approval', 'core' )),
);
$opsTheme->addVariable('app_modes', $appModes);


$ipChecks = array(
	array('value' => '0', 'label' => __( 'Off', 'core' )),
	array('value' => '1', 'label' => __( 'On', 'core' )),
);
$opsTheme->addVariable('ip_checks', $ipChecks);


$memModes = array(
	array('value' => '0', 'label' => __( 'Off', 'core' )),
	array('value' => '1', 'label' => __( 'On', 'core' )),
);
$opsTheme->addVariable('mem_modes', $memModes);


$deletes = array(
	array('value' => '30',    'label' => __( 'After 30 days of inactivity', 'core' )),
	array('value' => '60',    'label' => __( 'After 60 days of inactivity', 'core' )),
	array('value' => '90',    'label' => __( 'After 90 days of inactivity', 'core' )),
	array('value' => '180',   'label' => __( 'After 180 days of inactivity', 'core' )),
	array('value' => 'never', 'label' => __( 'Never', 'core' )),
);
$opsTheme->addVariable('deletes', $deletes);


$alwaysFolds = array(
	array('value' => 'yes', 'label' => __( 'Yes', 'core' )),
	array('value' => 'no',  'label' => __( 'No', 'core' )),
);
$opsTheme->addVariable('alwaysfolds', $alwaysFolds);


$isStraddles = array(
	array('value' => 'yes', 'label' => __( 'Yes', 'core' )),
	array('value' => 'no',  'label' => __( 'No', 'core' )),
);
$opsTheme->addVariable('isstraddles', $isStraddles);


$websocketlabels = array(
	array('value' => '0', 'label' => __( 'No', 'core' )),
	array('value' => '1', 'label' => __( 'Yes', 'core' )),
);
$opsTheme->addVariable('websocketlabels', $websocketlabels);


$stakeSizes = array(
	array('value' => 'tiny', 'label' => __( 'Tiny Stakes [$10+]', 'core' )),
	array('value' => 'low',  'label' => __( 'Low Stakes [$100+]', 'core' )),
	array('value' => 'med',  'label' => __( 'Medium Stakes [$1000+]', 'core' )),
	array('value' => 'high', 'label' => __( 'High Rollers [$10k+]', 'core' )),
);
$opsTheme->addVariable('stakesizes', $stakeSizes);


$renews = array(
	array('value' => '0', 'label' => __( 'Off', 'core' )),
	array('value' => '1', 'label' => __( 'On', 'core' )),
);
$opsTheme->addVariable('renews', $renews);


$kickTimers = array(
	array('value' => '3',  'label' => __( '3 mins', 'core' )),
	array('value' => '5',  'label' => __( '5 mins', 'core' )),
	array('value' => '7',  'label' => __( '7 mins', 'core' )),
	array('value' => '10', 'label' => __( '10 mins', 'core' )),
	array('value' => '15', 'label' => __( '15 mins', 'core' )),
);
$opsTheme->addVariable('kicktimers', $kickTimers);


$moveTimers = array(
	array('value' => '10', 'label' => __( 'Turbo', 'core' )),
	array('value' => '15', 'label' => __( 'Fast', 'core' )),
	array('value' => '20', 'label' => __( 'Normal', 'core' )),
	array('value' => '27', 'label' => __( 'Slow', 'core' )),
);
$opsTheme->addVariable('movetimers', $moveTimers);


$showdowns = array(
	array('value' => '3',  'label' => __( '3 secs', 'core' )),
	array('value' => '5',  'label' => __( '5 secs', 'core' )),
	array('value' => '7',  'label' => __( '7 secs', 'core' )),
	array('value' => '10', 'label' => __( '10 secs', 'core' )),
);
$opsTheme->addVariable('showdowns', $showdowns);


$waitTimers = array(
	array('value' => '0',  'label' => __( 'None', 'core' )),
	array('value' => '10', 'label' => __( '10 secs', 'core' )),
	array('value' => '15', 'label' => __( '15 secs', 'core' )),
	array('value' => '20', 'label' => __( '20 secs', 'core' )),
	array('value' => '25', 'label' => __( '25 secs', 'core' )),
);
$opsTheme->addVariable('waittimers', $waitTimers);


$disconnects = array(
	array('value' => '15',  'label' => __( '15 secs', 'core' )),
	array('value' => '30',  'label' => __( '30 secs', 'core' )),
	array('value' => '60',  'label' => __( '60 secs', 'core' )),
	array('value' => '90',  'label' => __( '90 secs', 'core' )),
	array('value' => '120', 'label' => __( '120 secs', 'core' )),
);
$opsTheme->addVariable('disconnects', $disconnects);


$straddletimer = array(
	array('value' => '5',  'label' => __( '5 secs', 'core' )),
	array('value' => '7',  'label' => __( '7 secs', 'core' )),
	array('value' => '10',  'label' => __( '10 secs', 'core' )),
	array('value' => '12',  'label' => __( '12 secs', 'core' )),
	array('value' => '15',  'label' => __( '15 secs', 'core' )),
	array('value' => '20',  'label' => __( '20 secs', 'core' )),
	array('value' => '30',  'label' => __( '30 secs', 'core' ))
);
$opsTheme->addVariable('straddletimers', $straddletimer);


$raisebuttons = array(
	array('value' => '2xBB', 'label' => __( '2 big blinds', 'core' )),
	array('value' => '1xBB',   'label' => __( '1 big blind', 'core' )),
	array('value' => '1xPOT',  'label' => __( 'Pot', 'core' )),
	array('value' => '0.5xPOT',  'label' => __( '1/2 Pot', 'core' )),
);
$opsTheme->addVariable('raisebuttons', $raisebuttons);
$opsTheme->addVariable('raisebutton',  json_decode(RAISEBUTTON, true));


$tmrleftsounds = array(
	array('value' => 'on',  'label' => __( 'On', 'core' )),
	array('value' => 'off', 'label' => __( 'Off', 'core' ))
);
$opsTheme->addVariable('tmrleftsounds', $tmrleftsounds);


$playbfrcards = array(
	array('value' => 'yes', 'label' => __( 'Yes', 'core' )),
	array('value' => 'no',  'label' => __( 'No', 'core' ))
);
$opsTheme->addVariable('playbfrcards', $playbfrcards);


$inputsBasic  = $opsTheme->viewPart('admin-settings-basics');
$inputsBasic .= $addons->get_hooks(
    array(),
    array(
        'page'     => 'admin/settings.php',
        'location'  => 'basic_inputs'
    )
);

$inputsDetailed  = $opsTheme->viewPart('admin-settings-detailed');
$inputsDetailed .= $addons->get_hooks(
    array(),
    array(
        'page'     => 'admin/settings.php',
        'location'  => 'detailed_inputs'
    )
);

$inputsSmtp  = $opsTheme->viewPart('admin-settings-smtp');
$inputsSmtp .= $addons->get_hooks(
    array(),
    array(
        'page'     => 'admin/settings.php',
        'location'  => 'smtp_inputs'
    )
);

$inputsCurrency  = $opsTheme->viewPart('admin-settings-currency');
$inputsCurrency .= $addons->get_hooks(
    array(),
    array(
        'page'     => 'admin/settings.php',
        'location'  => 'currency_inputs'
    )
);

$opsTheme->addVariable('settings', array(
	'inputs' => array(
		'basic'    => $inputsBasic,
		'detailed' => $inputsDetailed,
		'smtp'     => $inputsSmtp,
		'currency' => $inputsCurrency,
	),
));


$languageName  = $languages[DEFLANG];
$langOpt = '';

foreach( $languages as $lang_id => $lang_text )
{
    $opsTheme->addVariable('lng', array(
        'id'   	   => $lang_id,
        'name' 	   => $lang_text,
        'url'  	   => '?lang=' . $lang_id,
        'selected' => ($lang_id == DEFLANG) ? 'selected' : '',
    ));

    $langOpt .= $opsTheme->viewPart('lang-opt');
}
$opsTheme->addVariable('languages', array(
	'options' => $langOpt
));


echo $opsTheme->viewPage('admin-settings');
?>