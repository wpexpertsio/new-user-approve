=== Plugin Name ===
Contributors: picklewagon
Donate link: http://picklewagon.com/wordpress/new-user-approve/donate
Tags: users, registration, sign up, user management, login
Requires at least: 3.5.1
Tested up to: 4.1
Stable tag: 1.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

New User Approve allows a site administrator to approve a user before they
are able to login to the site.

== Description ==

On a normal WordPress site, once a new user registers, the user is created in
the database. Then an email is sent to the new user with their login 
credentials. Very simple. As it should be.

The New User Approve plugin modifies the registration process. When a user 
registers for the site, the user gets created and then an email gets sent to
the administrators of the site. An administrator then is expected to either 
approve or deny the registration request. An email is then sent to the user 
indicating whether they were approved or denied. If the user has been approved,
the email will include the login credentials. Until a user is approved, the 
user will not be able to login to the site.

Only approved users will be allowed to login to site. Users waiting for approval
as well as denied users will not be able to login to site.

A user's status can be updated even after the initial approval/denial.

Each user that exists before New User Approve has been activated will be treated as
an approved user.

Default WordPress registration process:

1. User registers.
2. User is shown message to check email.
3. Login credentials are sent to new user in an email.
4. User logs in to site using login credentials.
5. Admin is notified of new user sign up via email.

WordPress registration process with New User Approve plugin activated:

1. User registers for access to site.
2. User is shown message to wait for approval.
3. Admin is notified of new user sign up via email.
4. Admin goes to wp-admin to approve or deny new user.
5. Email is sent to user. If approved, email will include login credentials.
6. User logs in to site using login credentials.

[Fork New User Approve on Github](https://github.com/picklewagon/new-user-approve)

[newuserapprove.com](http://newuserapprove.com/)

== Installation ==

1. Upload new-user-approve to the wp-content/plugins directory or download from
the WordPress backend (Plugins -> Add New -> search for 'new user approve')
2. Activate the plugin through the Plugins menu in WordPress
3. No configuration necessary.

== Frequently Asked Questions ==

= Why am I not getting the emails when a new user registers? =

The New User Approve plugin uses the functions provided by WordPress to send
email. Make sure your host is setup correctly to send email if this happens.

= How do I customize the email address and/or name when sending notifications to users? =

This is not a function of the plugin but of WordPress. WordPress provides the
*wp_mail_from* and *wp_mail_from_name* filters to allow you to customize this.
There are also a number of plugins that provide a setting to change this to 
your liking.

* [wp mail from](http://wordpress.org/extend/plugins/wp-mailfrom/)
* [Mail From](http://wordpress.org/extend/plugins/mail-from/)

= Why is the password reset when approving a user? =

The password is generated again because, by default, the user will not be aware
of their password. By generating a new password, the email that notifies the
user can also give them the new password just like the email does when receiving
your password on a regular WordPress install. At approval time, it is impossible
to retrieve the user's password.

There is a filter available (new_user_approve_bypass_password_reset) to turn off
this feature.

= What happens to the user's status after the plugin is deactivated? =

If you deactivate the plugin, their status doesn't matter. The status that the
plugin uses is only used by the plugin. All users will be allowed to login as long
as they have their username and passwords.

== Screenshots ==

1. The backend to manage approving and denying users. This is an alternative to approving users.
2. Integration with WordPress Users admin page.
3. Filter users by status.
4. Approve or deny users using the bulk edit feature in WordPress.
5. Custom messages on the login screen.

== Changelog ==

= 1.7.2 =
* tested with WordPress 4.1
* fix translation bug
* add bubble to user menu for pending users
 * Courtesy of [howdy_mcgee](https://wordpress.org/support/profile/howdy_mcgee)
 * https://wordpress.org/support/topic/get-number-of-pending-users#post-5920371

= 1.7.1 =
* fix code causing PHP notices
* don't show admin notice for registration setting if S2Member plugin is active
* fix issue causing empty password in approval email
* update translation files

= 1.7 =
* email/message tags
* refactor messages
* send admin approval email after the user has been created
* tested with WordPress 4.0
* finish updates in preparation of option addon plugin

= 1.6 =
* improve actions and filters
* refactor messages to make them easier to override
* show admin notice if the membership setting is turned off
* fix bug preventing approvals/denials when using filter
* add sidebar in admin to help with support
* unit tests
* shake the login form when attempting to login as unapproved user
* updated French translation

= 1.5.8 =
* tested with WordPress 3.9
* fix bug preventing the notice from hiding on legacy page

= 1.5.7 =
* fix bug that was preventing bulk approval/denials

= 1.5.6 =
* add more translations

= 1.5.5 =
* allow approval from legacy page

= 1.5.4 =
* fix bug that prevents emails from being sent to admins

= 1.5.3 =
* add filter for link to approve/deny users
* add filter for adding more email addresses to get notifications
* fix bug that prevents users to be approved and denied when requested
* fix bug that prevents the new user email from including a password
* fix bug that prevents search results from showing when searching users

= 1.5.2 =
* fix link to approve new users in email to admin
* fix bug with sending emails to new approved users

= 1.5.1 =
* fix bug when trying to install on a site with WP 3.5.1

= 1.5 =
* add more logic to prevent unwanted password resets
* add more translations
* minor bug fixes
* use core definition of tabs
* user query updates (requires 3.5)
* add status attribute to user profile page
* integration with core user table (bulk approve, filtering, etc.)
* tested with WordPress 3.6
* set email header when sending email
* more filters and actions

= 1.4.2 =
* fix password recovery bug if a user does not have an approve-status meta field
* add more translations
* tested with WordPress 3.5

= 1.4.1 =
* delete transient of user statuses when a user is deleted

= 1.4 =
* add filters
* honor the redirect if there is one set when registering
* add actions for when a user is approved or denied
* add a filter to bypass password reset
* add more translations
* add user counts by status to dashboard
* store the users by status in a transient

= 1.3.4 =
* remove unused screen_layout_columns filter
* tested with WordPress 3.4

= 1.3.3 =
* fix bug showing error message permanently on login page

= 1.3.2 =
* fix bug with allowing wrong passwords

= 1.3.1 =
* add czech, catalan, romanian translations
* fix formatting issues in readme.txt
* add a filter to modify who has access to approve and deny users
* remove deprecated function calls when a user resets a password
* don't allow a user to login without a password

= 1.3 =
* use the User API to retrieve a user instead of querying the db
* require at least WordPress 3.1
* add validate_user function to fix authentication problems
* add new translations
* get rid of plugin errors with WP_DEBUG set to true

= 1.2.6 =
* fix to include the deprecated code for user search

= 1.2.5 =
* add french translation

= 1.2.4 =
* add greek translation

= 1.2.3 =
* add danish translation

= 1.2.2 =
* fix localization to work correctly
* add polish translation

= 1.2.1 =
* check for the existence of the login_header function to make compatible with functions that remove it
* added "Other Notes" page in readme.txt with localization information.
* added belarusian translation files

= 1.2 =
* add localization support
* add a changelog to readme.txt
* remove plugin constants that have been defined since 2.6
* correct the use of db prepare statements/use prepare on all SQL statements
* add wp_enqueue_style for the admin style sheet

= 1.1.3 =
* replace calls to esc_url() with clean_url() to make plugin compatible with versions less than 2.8

= 1.1.2 =
* fix the admin ui tab interface for 2.8
* add a link to the users profile in the admin interface
* fix bug when using email address to retrieve lost password
* show blog title correctly on login screen
* use get_option() instead of get_settings()

= 1.1.1 =
* fix approve/deny links
* fix formatting issue with email to admin to approve user

= 1.1 =
* correctly display error message if registration is empty
* add a link to the options page from the plugin dashboard
* clean up code
* style updates
* if a user is created through the admin interface, set the status as approved instead of pending
* add avatars to user management admin page
* improvements to SQL used
* verify the user does not already exist before the process is started
* add nonces to approve and deny actions
* temporary fix for pagination bug

== Upgrade Notice ==

= 1.5.3 =
Download version 1.5.3 immediately! Some bugs have been fixed that have been affecting how the plugin worked.

= 1.5 =
A long awaited upgrade that includes better integration with WordPress core. Requires at least WordPress 3.5.

= 1.3 =
This version fixes some issues when authenticating users. Requires at least WordPress 3.1.

= 1.3.1 =
Download version 1.3.1 immediately! A bug was found in version 1.3 that allows a user to login without using password.

= 1.3.2 =
Download version 1.3.2 immediately! A bug was found in version 1.3 that allows a user to login using any password.

== Other Notes ==

The code for this plugin is also available at Github - https://github.com/picklewagon/new-user-approve. Pull requests welcomed.

= Filters =
* *new_user_approve_user_status* - modify the list of users shown in the tables
* *new_user_approve_request_approval_message* - modify the request approval message
* *new_user_approve_request_approval_subject* - modify the request approval subject
* *new_user_approve_approve_user_message* - modify the user approval message
* *new_user_approve_approve_user_subject* - modify the user approval subject
* *new_user_approve_deny_user_message* - modify the user denial message
* *new_user_approve_deny_user_subject* - modify the user denial subject
* *new_user_approve_pending_message* - modify message user sees after registration
* *new_user_approve_registration_message* - modify message after a successful registration
* *new_user_approve_register_instructions* - modify message that appears on registration screen
* *new_user_approve_pending_error* - error message shown to pending users when attempting to log in
* *new_user_approve_denied_error* - error message shown to denied users when attempting to log in

= Actions =
* *new_user_approve_user_approved* - after the user has been approved
* *new_user_approve_user_denied* - after the user has been denied
* *new_user_approve_approve_user* - when the user has been approved
* *new_user_approve_deny_user* - when the user has been denied

= Translations =
The plugin has been prepared to be translated. If you want to help to translate the plugin to your language, please have a look at the localization/new-user-approve.pot file which contains all definitions and may be used with a gettext editor like Poedit (Windows). More information can be found on the [Codex](http://codex.wordpress.org/Translating_WordPress).

When sending me your translation files, please send me your wordpress.org username as well.

* Belarussian translation by [Fat Cow](http://www.fatcow.com/)
* Brazilian Portuguese translation by [leogermani](http://profiles.wordpress.org/leogermani/)
* Catalan translation by [xoanet](http://profiles.wordpress.org/xoanet/)
* Croatian translation by Nik
* Czech translation by [GazikT](http://profiles.wordpress.org/gazikt/)
* Danish translation by [GeorgWP](http://wordpress.org/support/profile/georgwp)
* Dutch translation by [Ronald Moolenaar](http://profiles.wordpress.org/moolie/)
* Estonian translation by (Rait Huusmann)(http://profiles.wordpress.org/raitulja/)
* Finnish translation by Tonttu-ukko
* French translation by [Philippe Scoffoni](http://philippe.scoffoni.net/)
* German translation by Christoph Ploedt
* Greek translation by [Leftys](http://alt3rnet.info/)
* Hebrew translation by [Udi Burg](http://blog.udiburg.com)
* Hungarian translation by Gabor Varga
* Italian translation by [Pierfrancesco Marsiaj](http://profiles.wordpress.org/pierinux/)
* Lithuanian translation by [Ksaveras](http://profiles.wordpress.org/xawiers)
* Persian translation by [alimir](http://profiles.wordpress.org/alimir)
* Polish translation by [pik256](http://wordpress.org/support/profile/1271256)
* Romanian translation by [Web Hosting Geeks](http://webhostinggeeks.com/)
* Russian translation by [Alexey](http://wordpress.org/support/profile/asel)
* Serbo-Croation translation by [Web Hosting Hub](http://www.webhostinghub.com/)
* Slovakian translation by Boris Gereg
* Spanish translation by [Eduardo Aranda](http://sinetiks.com/)
* Swedish translation by [Per Bj&auml;levik](http://pastis.tauzero.se)
