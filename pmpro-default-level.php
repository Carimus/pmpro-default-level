<?php

/*
Plugin Name: PMPro Default Post Membership Level
Plugin URI: https://github.com/Carimus/pmpro-default-level
Description: Set the default PMPro "Require Membership" settings on new posts (including custom post types).
Version: 0.4
Author: Carimus
Author URI: https://carimus.com
License: MIT
*/

$pmdef_defaults = [
    'levels_by_post_type' => [],
];
$pmdef_options_parent = 'options-general.php';

function pmdef_add_menu()
{
    global $pmdef_options_parent;
    add_submenu_page(
        $pmdef_options_parent,
        'PMPro Default Levels',
        'PMPro Default Levels',
        'manage_options',
        'pmdef',
        'pmdef_generate_menu'
    );
}

add_action('admin_menu', 'pmdef_add_menu');

function pmdef_register_settings()
{
    register_setting('pmdef_options', 'pmdef_options', 'pmdef_sanitize_settings');
}

function pmdef_get_post_types()
{
    return array_values(get_post_types([], 'names'));
}

function pmdef_sanitze_settings_list_of_levels($value)
{
    if (is_array($value)) {
        $newValue = [];
        foreach ($value as $level) {
            $newValue[] = intval($level);
        }
        return $newValue;
    } elseif (is_numeric($value)) {
        return [intval($value)];
    } else {
        return [];
    }
}

function pmdef_sanitize_settings($settings)
{
    if (!is_array($settings)) {
        $settings = [];
    }

    if (isset($settings['levels']) && !isset($settings['levels_by_post_type'])) {
        $old_levels = pmdef_sanitze_settings_list_of_levels($settings['levels']);

        $settings = [
            'levels_by_post_type' => [
                'post' => $old_levels,
                'page' => $old_levels,
            ]
        ];
    }

    if (!isset($settings['levels_by_post_type']) || !is_array($settings['levels_by_post_type'])) {
        $settings['levels_by_post_type'] = [];
    }

    foreach(pmdef_get_post_types() as $post_type) {
        if (!isset($settings['levels_by_post_type'][$post_type])) {
            $settings['levels_by_post_type'][$post_type] = [];
        } else {
            $settings['levels_by_post_type'][$post_type] = pmdef_sanitze_settings_list_of_levels(
                $settings['levels_by_post_type'][$post_type]
            );
        }
    }

    return $settings;
}

add_action('admin_init', 'pmdef_register_settings');

function pmdef_get_opts()
{
    global $pmdef_defaults;
    return array_merge([], $pmdef_defaults, pmdef_sanitize_settings(get_option('pmdef_options', [])));
}

function pmdef_generate_menu()
{
    ?>
    <div class="wrap">
        <h2>PMPro Default Levels</h2>
        <?php
        if (!function_exists('pmpro_getAllLevels')): ?>

            <div class="notice notice-error">
                Ensure <a href="https://www.paidmembershipspro.com">Paid Memberships Pro</a> 1.8.5.6+ is
                installed and activated.
            </div>

            <strong>Come back here after installing and/or updating PMPro.</strong>

        <?php else:

            $pmpro_levels = pmpro_getAllLevels(true);
            $current = pmdef_get_opts();
            $post_types = pmdef_get_post_types();
            $sanitized_post_types = [];

            ?>
            <p>
                Choose the default levels to check for new posts for each post type.
            </p>
            <p>
                <strong>Important note:</strong> you must have the paid
                <a href="https://www.paidmembershipspro.com/add-ons/custom-post-type-membership-access/"><code>pmpro-cpt</code></a>
                PMPro addon installed and activated to be able to control access for custom post types in the first
                place.
            </p>
            <?php if(WP_DEBUG): ?>
            <label id="pmdef_debug_options_output_label" for="pmdef_debug_options_output">Current Settings</label>
            <textarea id="pmdef_debug_options_output" readonly="readonly"><?php echo json_encode($current, JSON_PRETTY_PRINT); ?></textarea>
            <?php endif; ?>
            <form method="post" action="options.php" novalidate="novalidate">
                <?php settings_fields('pmdef_options') ?>
                <table class="form-table">
                    <tbody>
                    <?php foreach($post_types as $post_type_idx => $post_type):
                        $sanitized_post_type = esc_attr(sanitize_html_class($post_type));
                        $sanitized_post_types[] = $sanitized_post_type;
                        ?>
                        <tr>
                            <th><code><?php echo $post_type; ?></code></th>
                            <td>
                                <fieldset>
                                    <?php foreach ($pmpro_levels as $level):
                                        $sanitized_level = esc_attr(sanitize_html_class($level->id));
                                        $cb_classes = [
                                            "pmdef_options_checkbox",
                                            "pmdef_options_checkbox_post_type_" . $sanitized_post_type,
                                            "pmdef_options_checkbox_level_" . $sanitized_level
                                        ];
                                        ?>
                                        <label>
                                            <input type="checkbox"
                                                   class="<?php echo implode(" ", $cb_classes); ?>"
                                                   data-pmdef-post-type="<?php echo $sanitized_post_type; ?>"
                                                   data-pmdef-level="<?php echo $sanitized_level; ?>"
                                                   name="pmdef_options[levels_by_post_type][<?php echo $post_type; ?>][]"
                                                   value="<?php echo $level->id; ?>"
                                                <?php if (in_array($level->id, $current['levels_by_post_type'][$post_type])): ?>
                                                    checked="checked"
                                                <?php endif; ?>>
                                            <?php echo $level->name; ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </fieldset>
                            </td>
                            <td>
                                <p>
                                    <a href="#"
                                       class="pmdef_options_post_type_override_submit pmdef_options_post_type_override_submit_<?php echo $sanitized_post_type; ?>"
                                       data-pmdef-post-type="<?php echo $sanitized_post_type; ?>">
                                        Click here
                                    </a>
                                    to copy settings from:
                                    <select class="pmdef_options_post_type_override pmdef_options_post_type_override_<?php echo $sanitized_post_type; ?>"
                                            data-pmdef-post-type="<?php echo $sanitized_post_type; ?>">
                                        <option value="">select post type</option>
                                        <?php foreach($post_types as $post_type_for_copy): ?>
                                            <option value="<?php echo esc_attr(sanitize_html_class($post_type_for_copy)); ?>">
                                                <?php echo $post_type_for_copy; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>.
                                </p>
                                <p>
                                    <a href="#"
                                       class="pmdef_options_post_type_override_all_submit pmdef_options_post_type_override_all_submit_<?php echo $sanitized_post_type; ?>"
                                       data-pmdef-post-type="<?php echo $sanitized_post_type; ?>">
                                        Click here
                                    </a>
                                    to copy these settings to all other post types.
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
                (function($, global) {
                    global.pmdefSettings = {
                        postTypes: <?php echo json_encode($sanitized_post_types); ?>,
                        copySettings: function(sourcePostType, destinationPostType) {
                            $('.pmdef_options_checkbox_post_type_' + sourcePostType).each(function (idx, cb) {
                                var $sourceCb = $(cb);
                                var sourceLevelId = $sourceCb.data('pmdef-level');
                                var $destinationCb = $(
                                    '.pmdef_options_checkbox_post_type_' + destinationPostType +
                                    '.pmdef_options_checkbox_level_' + sourceLevelId
                                );
                                if ($destinationCb.length) {
                                    $destinationCb.prop('checked', $sourceCb.prop('checked'))
                                }
                            });
                        }
                    };
                    $(window.document).on('click', '.pmdef_options_post_type_override_submit', function (event) {
                        event.preventDefault();
                        var $destinationLink = $(event.target);
                        var destinationPostType = $destinationLink.data('pmdef-post-type');
                        var $sourceDropdown = $('.pmdef_options_post_type_override_' + destinationPostType);
                        var sourcePostType = $sourceDropdown.val();
                        if (!sourcePostType) {
                            alert('Select a post type to copy settings from in the dropdown first.');
                        } else if (sourcePostType && destinationPostType) {
                            global.pmdefSettings.copySettings(sourcePostType, destinationPostType);
                            $sourceDropdown.val('');
                        } else {
                            console.error(
                                'Could not determine sourcePostType ("%s") or destinationPostType ("%s")',
                                sourcePostType,
                                destinationPostType
                            );
                        }
                    });
                    $(window.document).on('click', '.pmdef_options_post_type_override_all_submit', function (event) {
                        event.preventDefault();
                        var $sourceLink = $(event.target);
                        var sourcePostType = $sourceLink.data('pmdef-post-type');
                        if (sourcePostType) {
                            $.each(global.pmdefSettings.postTypes, function(idx, postType) {
                                if(postType !== sourcePostType) {
                                    global.pmdefSettings.copySettings(sourcePostType, postType);
                                }
                            });
                        } else {
                            console.error(
                                'Could not determine sourcePostType ("%s")',
                                sourcePostType
                            );
                        }
                    });
                })(jQuery, window);
            </script>
            <style>
                #pmdef_debug_options_output_label {
                    font-weight: bold;
                    margin-bottom: 10px;
                    display: block;
                }
                #pmdef_debug_options_output {
                    width: 100%;
                    min-height: 300px;
                }
            </style>
        <?php endif; ?>
    </div>

<?php }

function pmdef_admin_script()
{
    global $pmdef_options_parent;

    $current_screen = get_current_screen();

    if ($current_screen && $current_screen->base === 'post' && function_exists('pmpro_getAllLevels')) {
        $is_new = $current_screen->action === 'add';
        $post_type = $current_screen->post_type ?: 'post';
        $options = pmdef_get_opts();
        $allLevels = pmpro_getAllLevels();
        $allLevelIds = [];
        foreach($allLevels as $level) {
            $allLevelIds[] = intval($level->id);
        }
        $allLevelIds = json_encode($allLevelIds);
        $defaultLevels = json_encode($options['levels_by_post_type'][$post_type]);
        ?>
        <!-- pmdef script start -->
        <script type="text/javascript">
            (function ($, global) {
                'use strict';
                var allLevels = <?php echo $allLevelIds; ?>;
                var defaultLevels = <?php echo $defaultLevels; ?>;

                global.pmdef = {
                    overrideWithDefaults: function ($postbox) {
                        $.each(allLevels, function (idx, level) {
                            var $checkbox = $('#in-membership-level-' + level, $postbox);
                            if ($checkbox.length) {
                                $checkbox.prop('checked', defaultLevels.indexOf(level) !== -1);
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
                            '<p><strong>Defaults have been set by PMPro Default Level</strong> ' + settingsLink +
                            '</p><p><strong><a href="#" id="pmdef_reset_btn">Click here</a> to reset to the defaults.' +
                            '</strong>' +
                            '</p>'
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