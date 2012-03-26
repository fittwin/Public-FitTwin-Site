uc_recurring_subscription
~~~~~~~~~~~~~~~~~~~~~~~~~

uc_recurring_subscription is a drupal module that integrates recurring payments
and roles and provides a set of features specifically designed for managing a
membership/subscription website.

INSTALL
~~~~~~~
This module requires a patch to ubercart, more detail on the issue can be found
in the following issue:
http://drupal.org/node/488422

The patch required for this module is included in this project download under:
module/uc_recurring_subscription/ubercart_api.patch

To apply:
1. Copy the ubercart_api.patch file to ubercart module directory.
2. Patch by running the command:
patch -p0 < ubercart_api.patch
3. You shouldn't get any errors if it applies correctly.

