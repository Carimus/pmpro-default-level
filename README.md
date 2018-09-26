# PMPro Default Level

A WordPress plugin and addon for [Paid Memberships Pro](https://paidmembershipspro.com/) that allows you to
set the default PMPro level requirements for new pages, posts, or any custom post type.

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

 - [ ] Support different access level settings per (custom) post type.
 - [ ] Support dynamically generated posts as well.
 - [ ] Explicitly support Gutenburg