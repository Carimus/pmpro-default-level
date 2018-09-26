<?php

/*
Plugin Name: PMPro Default Post Membership Level
Plugin URI: https://github.com/Carimus/pmpro-default-level
Description: Set the default PMPro "Require Membership" settings on new posts (including custom post types).
Version: 0.3
Author: Carimus
Author URI: https://carimus.com
License: MIT
*/

$pmdef_defaults = [
    'levels' => []
];
$pmdef_options_parent = 'options-general.php';

function pmdef_add_menu()
{
    global $pmdef_options_parent;
    add_submenu_page(
        $pmdef_options_parent,
        'PMPro Default Level',
        'PMPro Default Level',
        'manage_options',
        'pmdef',
        'pmdef_generate_menu'
    );
}

add_action('admin_menu', 'pmdef_add_menu');

function pmdef_register_settings()
{
    register_setting('pmdef_options', 'pmdef_options', 'pmdef_validate_settings');
}

function pmdef_validate_settings($settings)
{
    if (!is_array($settings)) {
        $settings = [];
    }
    if (isset($settings['levels'])) {
        if (is_array($settings['levels'])) {
            foreach ($settings['levels'] as $idx => $level) {
                $settings['levels'][$idx] = intval($level);
            }
        } elseif (is_numeric($settings['levels'])) {
            $settings['levels'] = [intval($settings['levels'])];
        }
    }
    return $settings;
}

add_action('admin_init', 'pmdef_register_settings');

function pmdef_get_opts()
{
    global $pmdef_defaults;
    return array_merge([], $pmdef_defaults, get_option('pmdef_options', []));
}

function pmdef_generate_menu()
{

    $pmpro_levels = pmpro_getAllLevels(true);
    $current = pmdef_get_opts();

    ?>

    <div class="wrap">
        <h2>PMPro Default Level</h2>
        <form method="post" action="options.php" novalidate="novalidate">
            <?php settings_fields('pmdef_options') ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th><label>Default Levels</label></th>
                    <td>
                        <fieldset>
                            <?php foreach ($pmpro_levels as $level): ?>
                                <label>
                                    <input type="checkbox" name="pmdef_options[levels][]"
                                           value="<?php echo $level->id; ?>"
                                        <?php if (in_array($level->id, $current['levels'])): ?>
                                            checked="checked"
                                        <?php endif; ?>>
                                    <?php echo $level->name; ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

<?php }

function pmdef_admin_script()
{
    global $pmdef_options_parent;

    $current_screen = get_current_screen();

    if ($current_screen && $current_screen->base === 'post') {
        $is_new = $current_screen->action === 'add';
        $options = pmdef_get_opts();
        $levels = json_encode($options['levels']);
        ?>
        <!-- pmdef script start -->
        <script type="text/javascript">
            (function ($, global) {
                'use strict';
                var levels = JSON.parse('<?php echo $levels; ?>');

                global.pmdef = {
                    overrideWithDefaults: function ($postbox) {
                        $.each(levels, function (idx, level) {
                            var $checkbox = $('#in-membership-level-' + level, $postbox);
                            if ($checkbox.length) {
                                $checkbox.prop('checked', true);
                            }
                        });
                    }
                };

                $(window.document).on('click', '#pmdef_reset_btn', function (event) {
                    event.preventDefault();
                    var $postbox = $('#pmpro_page_meta');
                    if ($postbox.length) {
                        global.pmdef.overrideWithDefaults($postbox)
                    }
                });

                $(function () {
                    var $postbox = $('#pmpro_page_meta');
                    if ($postbox.length) {
                        var settingsLink = '(<a href="/wp-admin/<?php echo $pmdef_options_parent; ?>?page=pmdef">' +
                            'Settings' +
                            '</a>)';
                        <?php if($is_new): ?>
                        global.pmdef.overrideWithDefaults($postbox);
                        $('.inside', $postbox).append(
                            '<p><strong>Defaults have been set by PMPro Default Level</strong> ' + settingsLink + '</p>'
                        );
                        <?php else: ?>
                        $('.inside', $postbox).append(
                            '<p><strong>' +
                            '<a href="#" id="pmdef_reset_btn">Click here</a>' +
                            ' to override with defaults according to PMPro Default Level</strong> ' +
                            settingsLink +
                            '</p>'
                        );
                        <?php endif; ?>
                    }
                });
            })(jQuery, window);
        </script>
        <!-- pmdef script end -->
    <?php }
}

add_action('admin_footer', 'pmdef_admin_script', 100);