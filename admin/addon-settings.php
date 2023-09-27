<?php
if (! isset($_GET['id']))
{
	header('Location: admin.php?admin=addons');
	die();
}

$addonsDir         = 'addons';
$settingsDir       = $addonsDir . '/settings';

$id                = preg_replace('/[^A-Za-z0-9_-]/i', '', $_GET['id']);
$addonInitFile     = 'includes/' . $addonsDir   . '/' . $id . '/init.php';
$addonConfigFile   = 'includes/' . $addonsDir   . '/' . $id . '/config.json';
$addonSettingsFile = 'includes/' . $settingsDir . '/' . $id . '.json';

if (! file_exists($addonInitFile) || ! file_exists($addonConfigFile))
{
	header('Location: admin.php?admin=addons');
	die();
}

if (isset($_POST['update_settings']))
{
	$post = $_POST;
	unset($post['update_settings']);
	file_put_contents($addonSettingsFile, json_encode($post));
}

$addonConfig   = json_decode(file_get_contents($addonConfigFile), true);
$addonSettings = (file_exists($addonSettingsFile)) ? json_decode(file_get_contents($addonSettingsFile), true) : array();

$configNum = count($addonConfig);
$configNi  = 0;
?>

<form name="edittable" method="post" action="admin.php?admin=addon-settings&id=<?php echo $id; ?>">
    <?php
    $noCloseTags = array('input');

foreach ($addonConfig as $config)
{
    $tag = '';
    if (isset($config['tag']))
    {
        $tag = $config['tag'];
        unset($config['tag']);
    }

    $type = '';
    if (isset($config['type']))
    {
        $type = $config['type'];
        unset($config['type']);
    }

    $label = '';
    if (isset($config['label']))
    {
        $label = $config['label'];
        unset($config['label']);
    }

    $hint = '';
    if (isset($config['hint']))
    {
        $hint = $config['hint'];
        unset($config['hint']);
    }

    $tooltip  = '';
    $tipPlace = 'top';

    if (isset($config['tooltip']))
    {
        $tooltip = $config['tooltip'];
        unset($config['tooltip']);

        if ($configNi < 2)
            $tipPlace = 'bottom';
    }

    $class = '';
    if (isset($config['class']))
    {
        $class = $config['class'];
        unset($config['class']);
    }

    $name = '';
    if (isset($config['name']))
    {
        $name = $config['name'];
    }

    $defaultValue = '';
    if (isset($config['default-value']))
    {
        $defaultValue = $config['default-value'];
        unset($config['default-value']);
    }

    $value = (isset($addonSettings[$name])) ? $addonSettings[$name] : '';
    if ($value != '')
    {
        $config['value'] = $value;
    }

    $attributes = '';
    foreach ($config as $attrName => $attrValue)
    {
        if (! is_array($attrValue))
            $attributes .= $attrName . '="' . $attrValue . '" ';
    }

    if ( isset($config['value']) )
        unset($config['value']);

    if ($type !== "checkbox")
    {
        echo $label;

        if (strlen($tooltip) > 0)
        {
            echo '&nbsp;<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="' . $tipPlace . '" title="' . $tooltip . '"></i>';
        }
    }

    if ($tag === 'select')
    {
        ?>
        <select class="form-control <?php echo $class; ?>" <?php echo $attributes; ?>>
            <option><?php echo __( 'Choose', 'core' ); ?>:</option>

            <?php
            if (isset($config['options']) && is_array($config['options']))
            {
                $options = $config['options'];

                foreach ($options as $optValue => $optTitle)
                {
                    $optSelected = '';

                    if ($optValue == $value) $optSelected = 'selected';
                    ?>
                    <option value="<?php echo $optValue; ?>"<?php echo $optSelected; ?>><?php echo $optTitle; ?></option>
                    <?php
                }
            }
            ?>
        </select>
        <?php
    }
    else if ($type === 'checkbox')
    {
        $checked = "";

        if ($value == "on" || $value == $config['check-value'])
            $checked = "checked";

        ?>
        <div class="row">
            <div class="col-6">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" <?php echo $attributes; ?> id="<?php echo $name; ?>" <?php echo $checked; ?>>
                    <label class="custom-control-label" for="<?php echo $name; ?>"><?php echo $label; ?></label>
                    <?php if (strlen($tooltip) > 0)
                    {
                        echo '&nbsp;<i class="fas fa-question-circle" data-toggle="tooltip" data-placement="' . $tipPlace . '" title="' . $tooltip . '"></i>';
                    } ?>
                </div>
            </div>
        </div>
        <?php

    }
    elseif (in_array($tag, $noCloseTags))
    {
        ?>
        <<?php echo $tag; ?> class="form-control <?php echo $class; ?>" <?php echo $attributes; ?>>
        <?php
    }
    else
    {
    ?>
    <<?php echo $tag; ?> class="form-control <?php echo $class; ?>" <?php echo $attributes; ?>><?php echo $value; ?></<?php echo $tag; ?>>
<?php
    }

    if (strlen($hint) > 0)
    {
?>
    <small class="text-muted"><?php echo $hint; ?></small><br>
<?php
    }

    $configNi++;

    if ($configNi != $configNum)
        echo '<br>';
}
?>
</form>
<button type="submit" name="submit_btn" class="btn btn-success mt-3"><?php echo BUTTON_SAVE; ?></button>
<a href="admin.php?admin=tables" class="btn btn-warning mt-3 ml-2"><?php echo BUTTON_BACK; ?></a>

<script type="text/javascript">
    jQuery(function()
    {
        jQuery('[data-toggle="tooltip"]').tooltip({
        });
    });

    jQuery("button[name='submit_btn']").click(function()
    {
        var form = jQuery("form[name='edittable']");
        form.find("input[type='checkbox']").each(function()
        {
            var checkbox = jQuery(this);

            if (checkbox.is(':checked'))
                checkbox.attr('value', checkbox.attr('check-value'));
            else
                checkbox.after('<input type="checkbox" name="' + checkbox.attr('name') + '" value="' + checkbox.attr('uncheck-value') + '" checked style="display:none;">').remove();
        });

        form.append('<input type="hidden" name="update_settings" value="yes">').submit();
    });
</script>