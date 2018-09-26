<?php

/*
Plugin Name: PMPro Default Post Membership Level
Plugin URI: https://github.com/Carimus/pmpro-default-level
Description: Set the default PMPro "Require Membership" settings on new posts (including custom post types).
Version: 0.6
Author: Carimus
Author URI: https://carimus.com
License: MIT
*/

$pmdef_defaults = [
    'levels_by_post_type' => [],
];
$pmdef_options_parent = 'options-general.php';
$pmdef_admin_actions = [
    'reset_all_for_post_type' => 'pmdef_tool_reset_all_for_post_type',
    'reset_all'               => 'pmdef_tool_reset_all',
];
$pmdef_menu_slug = 'pmdef';

/**
 * Add the PMPro Default Levels menu to the admin backend.
 */
function pmdef_add_menu()
{
    global $pmdef_options_parent, $pmdef_menu_slug;
    add_submenu_page(
        $pmdef_options_parent,
        'PMPro Default Levels',
        'PMPro Default Levels',
        'manage_options',
        $pmdef_menu_slug,
        'pmdef_generate_menu'
    );
}

add_action('admin_menu', 'pmdef_add_menu');

/**
 * Register our settings with WP
 */
function pmdef_register_settings()
{
    register_setting('pmdef_options', 'pmdef_options', 'pmdef_sanitize_settings');
}

/**
 * Gets all post types we're interested in.
 *
 * @return string[] Post type slugs
 */
function pmdef_get_post_types()
{
    return array_values(get_post_types([], 'names'));
}

/**
 * Sanitize a settings value which we're expecting to be an array of integer level IDs.
 *
 * @param mixed $value
 *
 * @return array
 */
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

/**
 * Sanitize the full settings array.
 *
 * The returned array is guaranteed to have the `levels_by_post_type` property which will be an assoicative array
 * of post types as keys and arrays of membership level IDs as values.
 *
 * This will migrate the old "levels" to the new "levels_by_post_type" which will be persisted when the
 * admin saves the settings the first time after updating from v0.3 to v0.4
 *
 * @param mixed $settings
 *
 * @return array
 */
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

    foreach (pmdef_get_post_types() as $post_type) {
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

/**
 * Get the sanitized options.
 *
 * @see \pmdef_sanitize_settings()
 *
 * @return array
 */
function pmdef_get_opts()
{
    global $pmdef_defaults;
    return array_merge([], $pmdef_defaults, pmdef_sanitize_settings(get_option('pmdef_options', [])));
}

/**
 * Get the admin-ajax.php URL for a specific pmdef action.
 *
 * @see \$pmdef_admin_actions
 *
 * @param string $action
 *
 * @return string
 */
function pmdef_admin_action_url($action)
{
    global $pmdef_admin_actions;
    return add_query_arg(['action' => $pmdef_admin_actions[$action]], admin_url('admin-ajax.php'));
}

/**
 * Generate the HTML/CSS/JS for the settings page.
 */
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
        <?php if (WP_DEBUG): ?>
            <label id="pmdef_debug_options_output_label" for="pmdef_debug_options_output">Current Settings</label>
            <textarea id="pmdef_debug_options_output" readonly="readonly"><?php echo json_encode(
                    $current,
                    JSON_PRETTY_PRINT
                ); ?></textarea>
        <?php endif; ?>
            <form method="post" action="options.php" novalidate="novalidate">
                <?php settings_fields('pmdef_options') ?>
                <table class="form-table">
                    <tbody>
                    <?php foreach ($post_types as $post_type_idx => $post_type):
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
                                                <?php if (in_array(
                                                    $level->id,
                                                    $current['levels_by_post_type'][$post_type]
                                                )): ?>
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
                                        <?php foreach ($post_types as $post_type_for_copy): ?>
                                            <option value="<?php echo esc_attr(
                                                sanitize_html_class($post_type_for_copy)
                                            ); ?>">
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

            <h3 id="pmdef-tools">Tools</h3>

            <p>
                <strong>IMPORTANT NOTE:</strong> Please save any changes above before using these tools.
            </p>

        <?php
        if (isset($_GET['pmdef_tools_message'])):
        $msg_type = (isset($_GET['pmdef_tools_message_type']) && !empty($_GET['pmdef_tools_message_type']))
            ? $_GET['pmdef_tools_message_type']
            : 'default';
        ?>
            <div class="pmdef-tools-message pmdef-tools-message-<?php echo $msg_type; ?>">
                <?php echo $_GET['pmdef_tools_message']; ?>
            </div>
        <?php endif; ?>

            <form method="post"
                  action="<?php echo pmdef_admin_action_url('reset_all'); ?>"
                  novalidate="novalidate">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            Reset all posts of all types to defaults.
                        </th>
                        <td>
                            <input type="submit" name="submit" value="Reset All"
                                   class="button button-primary pmdef-double-confirm">
                            <p>
                                This will loop through ALL POSTS of any post type and change their access level
                                to match the defaults that have been saved on this page.
                            </p>
                            <p>
                                <strong>This action is irreversible!</strong>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>

            <form method="post"
                  action="<?php echo pmdef_admin_action_url('reset_all_for_post_type'); ?>"
                  novalidate="novalidate">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            Reset all posts of type to defaults.
                        </th>
                        <td>
                            <select name="pmdef_post_type">
                                <option value="">Select a post type</option>
                                <?php foreach ($post_types as $post_type): ?>
                                    <option value="<?php echo esc_attr(
                                        $post_type
                                    ); ?>"><?php echo $post_type; ?></option>
                                <?php endforeach; ?>
                            </select>
                            &nbsp;
                            <input type="submit" name="submit" value="Reset"
                                   class="button button-primary pmdef-double-confirm">
                            <p>
                                This will loop through all posts of the selected post type and change their access level
                                to match the defaults that have been saved on this page.
                            </p>
                            <p>
                                <strong>This action is irreversible!</strong>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>

            <script>
                (function ($, global) {
                    global.pmdefSettings = {
                        postTypes: <?php echo json_encode($sanitized_post_types); ?>,
                        copySettings: function (sourcePostType, destinationPostType) {
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
                            $.each(global.pmdefSettings.postTypes, function (idx, postType) {
                                if (postType !== sourcePostType) {
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
                    $(window.document).on('click', '.pmdef-double-confirm', function (event) {
                        event.preventDefault();
                        var $button = $(event.target);
                        var textMethod = $button.attr('type') === 'submit' ? 'val' : 'text';
                        var originalText = $button[textMethod]();
                        $button[textMethod]('Are you sure?');
                        $button.removeClass('pmdef-double-confirm');
                        window.setTimeout(function () {
                            $button[textMethod](originalText);
                            $button.addClass('pmdef-double-confirm');
                        }, 5000)
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

                .pmdef-tools-message {
                    display: block;
                    box-sizing: border-box;
                    width: 100%;
                    padding: 10px;
                    font-weight: bold;
                }

                .pmdef-tools-message..pmdef-tools-message-default {
                    background: white;
                    border-left: black 5px solid;
                    color: black;
                }

                .pmdef-tools-message.pmdef-tools-message-error {
                    background: pink;
                    border-left: red 5px solid;
                    color: red;
                }

                .pmdef-tools-message.pmdef-tools-message-success {
                    background: lightgreen;
                    border-left: lime 5px solid;
                    color: green;
                }
            </style>
        <?php endif; ?>
    </div>

<?php }

/**
 * Generate the HTML/CSS/JS to inject in the new/edit posts page.
 */
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
        foreach ($allLevels as $level) {
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

/**
 * Determine if a user is logged in and whether or not they are an administrator.
 *
 * @return bool
 */
function pmdef_user_is_admin()
{
    $current_user = wp_get_current_user();
    return $current_user && $current_user->exists() && current_user_can('manage_options');
}

/**
 * If the user is not an administrator, die and exit with an error message.
 */
function pmdef_ensure_user_is_admin()
{
    if (!pmdef_user_is_admin()) {
        wp_die('Only administrators can perform this action.');
        exit;
    }
}

/**
 * Redirect the user to the settings page with a message and exit immediately.
 *
 * @param string $message The message.
 * @param null|string $type Can be "default", "success", or "error"
 */
function pmdef_admin_redirect_with_message($message, $type = null)
{
    global $pmdef_options_parent, $pmdef_menu_slug;
    wp_redirect(
        add_query_arg(
            [
                'page'                     => $pmdef_menu_slug,
                'pmdef_tools_message'      => urlencode($message),
                'pmdef_tools_message_type' => urlencode($type),
            ],
            admin_url($pmdef_options_parent)
        ) . '#pmdef-tools'
    );
    exit;
}

/**
 * Changes the PMPro access levels for a post.
 *
 * Note: For this to apply to custom post types, pmrpo-cpt must be installed.
 *
 * @see \pmpro_page_save()
 * @see wp-content/plugins/paid-memberships-pro/includes/metaboxes.php
 *
 * @param int|string $post_id The ID of the post to change.
 * @param int|array|bool $level_ids The ID(s) of the membership levels to change to. Set to empty array, null, or false
 *      to clear all levels.
 *
 * @return bool True if the levels were changed; False if no change was needed.
 * @throws \Exception If an error occurred or arguments are bad.
 */
function pmdef_set_post_levels($post_id, $level_ids)
{
    global $wpdb;

    if (!isset($wpdb->pmpro_memberships_pages)) {
        throw new \Exception(
            'PMPro is not installed or your version is not supported. ' .
            'Could not find $wpdb->pmpro_memberships_pages.'
        );
    }

    if (is_numeric($level_ids)) {
        $level_ids = [intval($level_ids)];
    } elseif (is_array($level_ids)) {
        foreach ($level_ids as $level_idx => $level_id) {
            $level_ids[$level_idx] = intval($level_id);
        }
    } elseif (is_null($level_ids) || $level_ids === false) {
        $level_ids = [];
    } else {
        throw new \Exception('Invalid $level_ids argument supplied to pmdef_set_post_levels');
    }

    $post_id = intval($post_id);

    $existing_level_rows = $wpdb->get_results(
        "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '$post_id'",
        ARRAY_A
    );
    $existing_levels = [];
    foreach ($existing_level_rows as $existing_level_row) {
        $existing_levels[] = intval($existing_level_row['membership_id']);
    }
    if ($existing_levels === $level_ids) {
        return false;
    } else {
        $wpdb->query("DELETE FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = '$post_id'");
        foreach ($level_ids as $level_id) {
            $wpdb->query(
                "INSERT INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id) " .
                "VALUES('" . $level_id . "', '" . $post_id . "')"
            );
        }
        return true;
    }
}

/**
 * Updates many posts' PMPro access levels to the provided level IDs.
 *
 * @see \pmdef_set_post_levels()
 *
 * @param int|string|int[]|string[] $post_ids The ID(s) of the post to change.
 * @param int|array|bool $level_ids The ID(s) of the membership levels to change to. Set to empty array, null, or false
 *      to clear all levels.
 *
 * @return array An array with 4 int values: updated, unchanged, failed, total
 */
function pmdef_set_posts_levels($post_ids, $level_ids)
{
    if (is_numeric($post_ids)) {
        $post_ids = [intval($post_ids)];
    }

    $status = [
        'updated'   => 0,
        'unchanged' => 0,
        'failed'    => 0,
        'total'     => 0,
    ];

    foreach ($post_ids as $post_id) {
        try {
            $result = pmdef_set_post_levels($post_id, $level_ids);
            $status[$result ? 'updated' : 'unchanged']++;
        } catch (\Exception $exception) {
            $status['failed']++;
            error_log('[pmdef] Failed to update membership level to defaults for post with ID ' . $post_id);
            error_log(var_export($exception, true));
        }
        $status['total']++;
    }

    return $status;
}

/**
 * Updates all posts belonging to a post type to have PMPro access levels matching the provided level IDs.
 *
 * @see \pmdef_set_posts_levels()
 *
 * @param string $post_type The post type for which to query posts.
 * @param int|array|bool $level_ids The ID(s) of the membership levels to change to. Set to empty array, null, or false
 *      to clear all levels.
 *
 * @return array
 */
function pmdef_set_posts_levels_for_post_type($post_type, $level_ids)
{
    $query = new WP_Query(
        [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'nopaging'       => true,
            'fields'         => 'ids',
        ]
    );

    return pmdef_set_posts_levels($query->get_posts(), $level_ids);
}

/**
 * Redirect the user with a message based on the results of resetting many posts to their default levels. Exits
 * immediately.
 *
 * @see \pmdef_set_posts_levels()
 * @see \pmdef_set_posts_levels_for_post_type()
 *
 * @param array $results The results returned by one of the `pmdef_set_posts_levels*` functions.
 */
function pmdef_reset_admin_redirect_with_message($results)
{
    if ($results['failed'] > 0) {
        pmdef_admin_redirect_with_message(
            'Updated ' . $results['updated'] . ' posts with the new levels. ' . $results['unchanged'] .
            ' posts required no changes. ' . $results['failed'] . ' posts failed to update, however. Please see' .
            ' your server\'s error logs for more details.',
            'error'
        );
    } else {
        pmdef_admin_redirect_with_message(
            'Updated ' . $results['updated'] . ' posts with the new levels.' . (
            $results['unchanged'] > 0
                ? (' ' . $results['unchanged'] . ' posts required no changes. ')
                : '') .
            ' No failures.',
            'success'
        );
    }
}

/**
 * The action handler for resetting all posts in a certain post type to their default levels.
 */
function pmdef_action_reset_all_for_post_type()
{
    global $wpdb;

    pmdef_ensure_user_is_admin();

    if (!isset($wpdb->pmpro_memberships_pages)) {
        pmdef_admin_redirect_with_message(
            'PMPro is not installed or your version is not supported. ' .
            'Could not find $wpdb->pmpro_memberships_pages.',
            'error'
        );
    }

    if (!isset($_POST['pmdef_post_type']) || empty($_POST['pmdef_post_type'])) {
        pmdef_admin_redirect_with_message('Please select a post type to reset.', 'error');
    }

    $post_type = $_POST['pmdef_post_type'];

    $options = pmdef_get_opts();

    if (!isset($options['levels_by_post_type'][$post_type])) {
        pmdef_admin_redirect_with_message(
            'Defaults are not configured for post type or it doesn\'t exist: ' . $post_type,
            'error'
        );
    }

    $default_levels = $options['levels_by_post_type'][$post_type];

    $results = pmdef_set_posts_levels_for_post_type($post_type, $default_levels);

    pmdef_reset_admin_redirect_with_message($results);
}

add_action('wp_ajax_' . $pmdef_admin_actions['reset_all_for_post_type'], 'pmdef_action_reset_all_for_post_type');

/**
 * The action handler for resetting all posts to their default post types.
 */
function pmdef_action_reset_all()
{
    global $wpdb;

    pmdef_ensure_user_is_admin();

    if (!isset($wpdb->pmpro_memberships_pages)) {
        pmdef_admin_redirect_with_message(
            'PMPro is not installed or your version is not supported. ' .
            'Could not find $wpdb->pmpro_memberships_pages.',
            'error'
        );
    }

    $options = pmdef_get_opts();

    $results = [];

    foreach ($options['levels_by_post_type'] as $post_type => $default_levels) {
        $post_type_results = pmdef_set_posts_levels_for_post_type($post_type, $default_levels);
        foreach ($post_type_results as $key => $result_val) {
            if (!isset($results[$key])) {
                $results[$key] = 0;
            }
            $results[$key] += $result_val;
        }
    }

    pmdef_reset_admin_redirect_with_message($results);
}

add_action('wp_ajax_' . $pmdef_admin_actions['reset_all'], 'pmdef_action_reset_all');
