# PMPro Default Level

A WordPress plugin and addon for [Paid Memberships Pro](https://paidmembershipspro.com/) that allows you to
set the default PMPro level requirements for new pages, posts, or any custom post type.

## Features

 - Set the default level per post type (i.e. different post types can have different defaults)
 - Easily copy settings from and to post types if you have a lot of them
 - Reset level requirements for an existing post to the configured defaults.
 - Reset level requirements for new post to the configured defaults after changing but before saving.
 - Set `WP_DEBUG` to `true` for debug output on the "PMPro Default Levels" settings page.

## Prerequisites

 - PHP 5.4+
 - The [`paid-memberships-pro`](https://paidmembershipspro.com/) WP plugin must be installed and activated.
    - Tested with versions `1.8.5.6` and `1.9.5.4`. Should work with all recent versions.
 - The [`pmpro-cpt`](https://www.paidmembershipspro.com/add-ons/custom-post-type-membership-access/) paid PMPro
   add-on must be installed and activated if you want to set the default access level for custom post types as
   well.

## Caveats

 - Only effects new posts created via the editor in the WP backend. I.e. does not affect posts created
   dynamically e.g. by other plugins.
    - Gutenburg support has not been tested. It should work if gutenburg is backwards compatible with custom
      admin meta boxes which is what PMPro uses for the "Require Membership" box

## TODO

 - [ ] Support dynamically generated posts as well.
 - [ ] Explicitly support Gutenburg

## Release notes

### `v0.5`

 - Updated project license.

### `v0.4`

 - Added support to for setting defaults per custom post type.
 - Added ability to reset new post to defaults after changing but before saving.

### `v0.3`

 - Initial release; extracted from internal project
 - Cleaned up syntax and styling
 - Also show on edit page w/ link to reset

## License

This project is licensed under the [MIT open source license](https://opensource.org/licenses/MIT).

[Read the full license here](./LICENSE)
