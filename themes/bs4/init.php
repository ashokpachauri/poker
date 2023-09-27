<?php
/* Styles --- */
if (isset($_GET['pagename']) && $_GET['pagename'] === 'poker')
{
	$opsTheme->addCss('libs.min');
	$opsTheme->addCss('poker.min');
	$opsTheme->addCss('modal');
	$opsTheme->addCss('buttons');
	$opsTheme->addCss('more');
	$opsTheme->addCss('//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/fontawesome.min.css');
	$opsTheme->addCss('//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/solid.min.css');
	$opsTheme->addCss('//cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
}
else
{
	$opsTheme->addCss('bootstrap');
	$opsTheme->addCss('//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/fontawesome.min.css');
	$opsTheme->addCss('//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/solid.min.css');
	$opsTheme->addCss('custom');
	$opsTheme->addCss('style.min');
}
/* --- Styles */


/* Scripts --- */
if ($_GET['pagename'] === 'poker')
{
	$opsTheme->addJs('jquery-3.4.1', 'header');
	
	$opsTheme->addJs('libs.min',     'footer');
	$opsTheme->addJs('poker',        'footer');
	$opsTheme->addJs('bootstrap',    'footer');
}
else
{
	$opsTheme->addJs('jquery-3.4.1', 'header');
	$opsTheme->addJs('//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', 'header');
	$opsTheme->addJs('bootstrap',    'header');
}
/* --- Scripts */
?>