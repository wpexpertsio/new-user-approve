=== Plugin Name ===
Contributors: picklewagon
Donate link: http://picklewagon.com/wordpress/new-user-approve/donate
Tags: users, registration
Requires at least: 3.2.1
Tested up to: 3.3.2
Stable tag: 1.3.3

New User Approve is a Wordpress plugin that allows a blog administrator to 
approve a user before they are able to access and login to the blog.

== Description ==

In a normal Wordpress blog, once a new user registers, the user is created in 
the database. Then an email is sent to the new user with their login 
credentials. Very simple. As it should be.

The New User Approve plugin modifies the registration process. When a user 
registers for the blog, the user gets created and then an email gets sent to 
the administrators of the site. An administrator then is expected to either 
approve or deny the registration request. An email is then sent to the user 
indicating whether they were approved or denied. If the user was approved, 
the email will include the login credentials. Until a user is approved, the 
user will not be able to login to the site.

== Installation ==

1. Upload new-user-approve to the wp-content/plugins directory
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

== Screenshots ==

1. The backend to manage approving and denying users.

== Changelog ==

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

= 1.3 =
This version fixes some issues when authenticating users. Requires at least WordPress 3.1.

= 1.3.1 =
Download version 1.3.1 immediately! A bug was found in version 1.3 that allows a user to login without using password.

= 1.3.2 =
Download version 1.3.2 immediately! A bug was found in version 1.3 that allows a user to login using any password.

== Other Notes ==

= Translations =
The plugin has been prepared to be translated. If you want to help to translate the plugin to your language, please have a look at the localization/new-user-approve.pot file which contains all defintions and may be used with a gettext editor like Poedit (Windows). More information can be found on the [Codex](http://codex.wordpress.org/Translating_WordPress).

* Belarusian translation by [Fat Cow](http://www.fatcow.com/)
* Danish translation by [GeorgWP](http://wordpress.org/support/profile/georgwp)
* French translation by [Philippe Scoffoni](http://philippe.scoffoni.net/)
* Greek translation by [Leftys](http://alt3rnet.info/)
* Polish translation by [pik256](http://wordpress.org/support/profile/1271256)
* German translation by Christoph Ploedt
* Spanish translation by [Eduardo Aranda](http://sinetiks.com/)
* Dutch translation by [Ronald Moolenaar](http://profiles.wordpress.org/moolie/)
* Italian translation by [Pierfrancesco Marsiaj](http://profiles.wordpress.org/pierinux/)
* Czech translation by [GazikT](http://profiles.wordpress.org/gazikt/)
* Catalan translation by [xoanet](http://profiles.wordpress.org/xoanet/)
* Romanian translation by [Web Hosting Geeks](http://webhostinggeeks.com/)

