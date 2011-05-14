<?php
/*
 Plugin Name: New User Approve
 Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve/
 Description: This plugin allows administrators to approve users once they register. Only approved users will be allowed to access the blog.
 Author: Josh Harrison
 Version: 1.3
 Author URI: http://www.picklewagon.com/
 */
 
/**  Copyright 2009
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if (!class_exists('pw_new_user_approve')) {
	class pw_new_user_approve {
		/**
		 * @var string The options string name for this plugin
		 */
		var $options_name = 'pw_new_user_approve_options';

		/**
		 * @var string $plugin_id unique identifier used for localization and other functions
		 */
		var $plugin_id = 'new-user-approve';

		/**
		 * @var string $pluginurl The url to this plugin
		 */
		var $pluginurl = '';
		
		/**
		 * @var string $pluginpath The path to this plugin
		 */
		var $pluginpath = '';

		/**
		 * @var array $options Stores the options for this plugin
		 */
		var $options = array();

		// Class Functions
		/**
		 * PHP 4 Compatible Constructor
		 */
		function pw_new_user_approve() {
			$this->__construct();
		}

		/**
		 * PHP 5 Constructor
		 */
		function __construct() {
			// Load up the localization file if we're using WordPress in a different language
			// Just drop it in this plugin's "localization" folder and name it "new-user-approve-[value in wp-config].mo"
			load_plugin_textdomain($this->plugin_id, false, dirname(plugin_basename(__FILE__)) . '/localization');

			// Constants setup
			$this->pluginurl  = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
			$this->pluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';

			// Initialize the options
			$this->get_options();

			register_activation_hook(__FILE__, array(&$this, 'activation_check'));
			
			// Actions
			add_action('admin_menu',          array(&$this, 'admin_menu_link'));
			add_action('admin_footer',        array(&$this, 'admin_scripts_footer'));
			add_action('admin_init',          array(&$this, 'admin_init'));
			add_action('init',                array(&$this, 'init'));
			add_action('register_post',       array(&$this, 'send_approval_email'), 10, 3);
			add_action('init',                array(&$this, 'process_input'));
			add_action('lostpassword_post',   array(&$this, 'lost_password'));
			add_filter('registration_errors', array(&$this, 'show_user_message'), 10, 1);
			add_filter('login_message',       array(&$this, 'welcome_user'));
			//add_action('rightnow_end', array(&$this, 'dashboard_stats')); // still too slow
			
			// Filters
			add_filter('plugin_action_links', array(&$this, 'filter_plugin_actions'), 10, 2);
			add_filter('registration_errors', array(&$this, 'show_user_message'), 10, 1);
			add_filter('login_message', array(&$this, 'welcome_user'));
			add_filter('screen_layout_columns', array(&$this, 'screen_layout_columns'), 10, 2);
		}
		
		function activation_check() {
			global $wp_version;
			
			$min_wp_version = '2.8.4';
			$exit_msg = sprintf( __('New User Approve requires WordPress %s or newer.', $this->localizationDomain), $min_wp_version );
			if (version_compare($wp_version, $min_wp_version, '<=')) {
				exit($exit_msg);
			}
		}
		
		function screen_layout_columns($columns, $screen) {
			if ($screen == $this->option_page_hook) {
				$columns[$screen] = 2;
			}
			return $columns;
		}

		/**
		 * Retrieves the plugin options from the database.
		 */
		function get_options() {
			// Don't forget to set up the default options
			if (!$theOptions = get_option($this->options_name)) {
				$theOptions = array(
					'default' => 'options'
				);
				update_option($this->options_name, $theOptions);
			}
			$this->options = $theOptions;
		}
		
		/**
		 * @desc Saves the admin options to the database.
		 */
		function save_admin_options(){
			update_option($this->options_name, $this->options);
		}

		/**
		 * @desc Adds the options subpanel
		 */
		function admin_menu_link() {
			$this->user_page_hook = add_users_page( __('Approve New Users', $this->plugin_id), __('Approve New Users', $this->plugin_id), 'edit_users', basename(__FILE__), array(&$this, 'approve_admin'));
			$this->option_page_hook = add_options_page(__('Approve New Users', $this->plugin_id), __('Approve New Users', $this->plugin_id), 'manage_options', 'approve-user-config', array(&$this, 'admin_options_page'));
			
			add_filter('plugin_action_links', array(&$this, 'filter_plugin_actions'), 10, 2);
			
			add_action('load-' . $this->option_page_hook, array(&$this, 'load_options_page'));
		}

		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		function filter_plugin_actions($links, $file) {
			static $this_plugin;
			if (!$this_plugin) {
				$this_plugin = plugin_basename(__FILE__);	
			}

			if ($file == $this_plugin){
				$settings_link = '<a href="users.php?page=' . basename(__FILE__) . '">' . __('Settings', $this->plugin_id) . '</a>';
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}

		function admin_init() {
			register_setting($this->options_name, $this->plugin_id, array(&$this, 'options_validate'));
			add_settings_section($this->plugin_id . 'main', 'Main Settings', array(&$this, 'main_section_text'), $this->plugin_id);
			//add_settings_field('plugin_text_string', 'Plugin Text Input', 'plugin_setting_string', 'plugin', 'plugin_main');
		}
		
		function options_validate() {
			
		}
		
		function main_section_text() {
			
		}
		
		function admin_scripts_footer() {
			global $wp_db_version;
			
			if (WP_ADMIN && $_GET['page'] == basename(__FILE__)) {
				$page_id = ($wp_db_version >= 10851) ? '#pw_approve_tabs' : '#pw_approve_tabs > ul';
?>
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($) {
        $('<?php echo $page_id; ?>').tabs({ fx: { opacity: 'toggle' } });
  });
  //]]>
</script>
<?php
			}
		}
		
		function dashboard_stats() {
			// Query the users table
			$wp_user_search = new PW_User_Search($_GET['usersearch'], $_GET['userspage']);
			$user_status = array();
		
			// Make the user objects
			foreach ($wp_user_search->get_results() as $userid) {
				$user = new WP_User($userid);
				$status = get_usermeta($userid, 'pw_user_status');
				if ($status == '') { // user was created in admin
					update_usermeta($userid, 'pw_user_status', 'approved');
					$status = get_usermeta($userid, 'pw_user_status');
				}
				if ($user_status[$status] == null) {
					$user_status[$status] = 0;
				}
				$user_status[$status] += 1;
			}
?>
			<div>
				<p><span style="font-weight:bold;"><a href="users.php?page=<?php print basename(__FILE__) ?>"><?php _e('Users', $this->plugin_id) ?></a></span>: 
				<?php foreach($user_status as $status =>$count) print "$count $status&nbsp;&nbsp;"; ?>
				</p>
			</div>
<?php
		}
		
		/**
		 * @desc create the view for the admin interface
		 */
		function approve_admin() {
			global $current_user;
			
			$user_status = array();
			
			// Query the users table
			if (isset($_GET['usersearch']) && isset($_GET['userspage'])) {
				$wp_user_search = new PW_User_Search($_GET['usersearch'], $_GET['userspage']);
			
				// Make the user objects
				foreach ($wp_user_search->get_results() as $userid) {
					$user = wp_cache_get($userid, 'pw_user_status_cache');
					
					if (!$user) {
						$user = new WP_User($userid);
						$status = get_usermeta($userid, 'pw_user_status');
						if ($status == '') { // user was created in admin
							update_usermeta($userid, 'pw_user_status', 'approved');
							$status = get_usermeta($userid, 'pw_user_status');
						}
						$user->status = $status;
						wp_cache_add($userid, $user, 'pw_user_status_cache');
					}
					
					$user_status[$status][] = $user;
				}
			}
			
			if (isset($_GET['user']) && isset($_GET['status'])) {
				echo '<div id="message" class="updated fade"><p>'.__('User successfully updated.', $this->plugin_id).'</p></div>';
			}
?>
			<div class="wrap">
				<h2><?php _e('User Registration Approval', $this->plugin_id) ?></h2>
				
				<h3><?php _e('User Management', $this->plugin_id) ?></h3>
				<div id="pw_approve_tabs">
					<ul>
						<li><a href="#pw_pending_users"><span><?php _e('Users Pending Approval', $this->plugin_id) ?></span></a></li>
						<li><a href="#pw_approved_users"><span><?php _e('Approved Users', $this->plugin_id) ?></span></a></li>
						<li><a href="#pw_denied_users"><span><?php _e('Denied Users', $this->plugin_id) ?></span></a></li>
					</ul>
					<div id="pw_pending_users">
						<?php $this->approve_table($user_status, 'pending', true, true); ?>
					</div>
					<div id="pw_approved_users">
						<?php $this->approve_table($user_status, 'approved', false, true); ?>
					</div>
					<div id="pw_denied_users">
						<?php $this->approve_table($user_status, 'denied', true, false); ?>
					</div>
				</div>
			</div>
<?php
		}

		/**
		 * @desc the table that shows the registered users grouped by status
		 */
		function approve_table($users, $status, $approve, $deny) {
			if (isset($users[$status]) && count($users[$status]) > 0) {
		?>
<table class="widefat">
	<thead>
		<tr class="thead">
			<th><?php _e('ID', $this->plugin_id) ?></th>
			<th><?php _e('Username', $this->plugin_id) ?></th>
			<th><?php _e('Name', $this->plugin_id) ?></th>
			<th><?php _e('E-mail', $this->plugin_id) ?></th>
		<?php if ($approve && $deny) { ?>
			<th colspan="2" style="text-align: center"><?php _e('Actions', $this->plugin_id) ?></th>
		<?php } else { ?>
			<th style="text-align: center"><?php _e('Actions', $this->plugin_id) ?></th>
		<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php
			// show each of the users
			$row = 1;
			foreach ($users[$status] as $user) {
				$class = ($row % 2) ? '' : ' class="alternate"';
				$avatar = get_avatar( $user->user_email, 32 );
				if ($approve) {
					$approve_link = get_option('siteurl').'/wp-admin/users.php?page='.basename(__FILE__).'&user='.$user->ID.'&status=approve';
					$approve_link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($approve_link, 'plugin-name-action_' . get_class($this)) : $approve_link;
				}
				if ($deny) {
					$deny_link = get_option('siteurl').'/wp-admin/users.php?page='.basename(__FILE__).'&user='.$user->ID.'&status=deny';
					$deny_link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($deny_link, 'plugin-name-action_' . get_class($this)) : $deny_link;
				}
				if ( current_user_can( 'edit_user', $user->ID ) ) {
					if ($current_user->ID == $user->ID) {
						$edit_link = 'profile.php';
					} else {
						$edit_link = clean_url( add_query_arg( 'wp_http_referer', urlencode( clean_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$user->ID" ) );
					}
					$edit = '<strong><a href="' . $edit_link . '">' . $user->user_login . '</a></strong><br />';
				} else {
					$edit = '<strong>' . $user->user_login . '</strong>';
				}
	
				?><tr <?php echo $class; ?>>
					<td><?php echo $user->ID; ?></td>
					<td><?php echo $avatar." ".$edit; ?></td>
					<td><?php echo $user->first_name." ".$user->last_name; ?></td>
					<td><a href="mailto:<?php echo $user->user_email; ?>" title="<?php _e('email:', $this->plugin_id) ?> <?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a></td>
					<?php if ($approve) { ?>
					<td align="center"><a href="<?php echo $approve_link; ?>" title="<?php _e('Approve', $this->plugin_id) ?> <?php echo $user->user_login; ?>"><?php _e('Approve', $this->plugin_id) ?></a></td>
					<?php } ?>
					<?php if ($deny) { ?>
					<td align="center"><a href="<?php echo $deny_link; ?>" title="<?php _e('Deny', $this->plugin_id) ?> <?php echo $user->user_login; ?>"><?php _e('Deny', $this->plugin_id) ?></a></td>
					<?php } ?>
				</tr><?php
				$row++;
			}
		?>
	</tbody>
</table>
		<?php
			} else {
				$status_i18n = $status;
				if ($status == 'approved') {
					$status_i18n = __('approved', $this->plugin_id);
				} else if ($status == 'denied') {
					$status_i18n = __('denied', $this->plugin_id);
				} else if ($status == 'pending') {
					$status_i18n = __('pending', $this->plugin_id);
				}
					
				echo '<p>'.sprintf(__('There are no users with a status of %s', $this->plugin_id), $status_i18n).'</p>';
			}
		}
		
		/**
		 * @desc send an email to the admin to request approval
		 */
		function send_approval_email($user_login, $user_email, $errors) {
			if (!$errors->get_error_code()) {
				/* check if already exists */
				$user_data = get_userdatabylogin($user_login);
        		if (!empty($user_data)){
					$errors->add('registration_required' , __('User name already exists', $this->plugin_id), 'message');
        		} else {
					/* send email to admin for approval */
        			$message  = sprintf(__('%1$s (%2$s) has requested a username at %3$s', $this->plugin_id), $user_login, $user_email, get_option('blogname')) . "\r\n\r\n";
					$message .= get_option('siteurl') . "\r\n\r\n";
					$message .= sprintf(__('To approve or deny this user access to %s go to', $this->plugin_id), get_option('blogname')) . "\r\n\r\n";
					$message .= get_option('siteurl') . "/wp-admin/users.php?page=".basename(__FILE__)."\r\n";
				
					// send the mail
					@wp_mail(get_option('admin_email'), sprintf(__('[%s] User Approval', $this->plugin_id), get_option('blogname')), $message);
					
					// create the user
					$user_pass = wp_generate_password();
					$user_id = wp_create_user($user_login, $user_pass, $user_email);
					
					update_usermeta($user_id, 'pw_user_status', 'pending');
				}
			}
		}
		
		/**
		 * @desc admin approval of user
		 */
		function approve_user() {
			global $wpdb;
			
			$user_id = (int) $_GET['user'];
			$user = new WP_User( $user_id );
			
			// reset password to know what to send the user
			$new_pass = wp_generate_password();
			$data = array(
				'user_pass' => md5($new_pass),
				'user_activation_key' => '',
			);
			$where = array(
				'ID' => $user->ID,
			);
			$wpdb->update($wpdb->users, $data, $where, array('%s', '%s'), array('%d'));
			
			wp_cache_delete($user->ID, 'users');
			wp_cache_delete($user->user_login, 'userlogins');
			
			// send email to user telling of approval
			$user_login = stripslashes($user->user_login);
			$user_email = stripslashes($user->user_email);
			
			// format the message
			$message  = sprintf(__('You have been approved to access %s', $this->plugin_id), get_option('blogname')) . "\r\n";
			$message .= sprintf(__('Username: %s', $this->plugin_id), $user_login) . "\r\n";
			$message .= sprintf(__('Password: %s', $this->plugin_id), $new_pass) . "\r\n";
			$message .= get_option('siteurl') . "/wp-login.php\r\n";
		
			// send the mail
			@wp_mail($user_email, sprintf(__('[%s] Registration Approved', $this->plugin_id), get_option('blogname')), $message);
			
			// change usermeta tag in database to approved
			update_usermeta($user->ID, 'pw_user_status', 'approved');
		}

		/**
		 * @desc admin denial of user
		 */
		function deny_user() {
			$user_id = (int) $_GET['user'];
			$user = new WP_User( $user_id );
			
			// send email to user telling of denial
			$user_email = stripslashes($user->user_email);
			
			// format the message
			$message = sprintf(__('You have been denied access to %s', $this->plugin_id), get_option('blogname'));
		
			// send the mail
			@wp_mail($user_email, sprintf(__('[%s] Registration Denied', $this->plugin_id), get_option('blogname')), $message);
			
			// change usermeta tag in database to denied
			update_usermeta($user->ID, 'pw_user_status', 'denied');
		}
		
		/**
		 * @desc display a message to the user if they have not been approved
		 */
		function show_user_message($errors) {
			if ( $errors->get_error_code() )
				return $errors;
			
			$message  = sprintf(__('An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approve or deny your request.', $this->plugin_id));
			$message .= sprintf(__('You will receive an email with instructions on what you will need to do next. Thanks for your patience.', $this->plugin_id));
			
			$errors->add('registration_required', $message, 'message');
			
			if (function_exists('login_header')) {
				login_header(__('Pending Approval', $this->plugin_id), '<p class="message register">' . __("Registration successful.", $this->plugin_id) . '</p>', $errors);
			}
			
			echo "<body></html>";
			exit();
		}
		
		/**
		 * @desc accept input from admin to modify a user
		 */
		function process_input() {
			if ((isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) && isset($_GET['status'])) {
				$valid_request = check_admin_referer('plugin-name-action_' . get_class($this));

				if ($valid_request) {
					if ($_GET['status'] == 'approve') {
						$this->approve_user();
					}
					
					if ($_GET['status'] == 'deny') {
						$this->deny_user();
					}
				}
			}
		}
	
		/**
		 * @desc only give a user their password if they have been approved
		 */
		function lost_password() {
			$is_email = strpos($_POST['user_login'], '@');
			if ($is_email === false) {
				$username = sanitize_user($_POST['user_login']);
				$user_data = get_userdatabylogin(trim($username));
			} else {
				$email = is_email($_POST['user_login']);
				$user_data = get_user_by_email($email);
			}
			 
			if ($user_data->pw_user_status != 'approved') {
				wp_redirect('wp-login.php');
				exit();		
			}
			
			return;
		}
		
		function welcome_user($message) {
			if (!isset($_GET['action'])) {
				$inside = sprintf(__('Welcome to %s. This site is accessible to approved users only. To be approved, you must first register.', $this->plugin_id), get_option('blogname'));
				$message .= '<p class="message">' . $inside . '</p>';
			}
			
			if ($_GET['action'] == 'register' && !$_POST) {
				$inside = sprintf(__('After you register, your request will be sent to the site administrator for approval. You will then receive an email with further instructions.', $this->plugin_id));
				$message .= '<p class="message">' . $inside . '</p>';
			}
			
			return $message;
		}
		
		function init() {
			if (WP_ADMIN && isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
				wp_enqueue_script('jquery-ui-tabs');
				wp_enqueue_style('pw-admin-ui-tabs', $this->pluginurl.'ui.tabs.css');
			}
		}
		
		public function load_options_page() {
			wp_enqueue_script('post');
			add_meta_box('new-user-approve-options-div', __('Options', $this->plugin_id), array(&$this, 'options_meta_box'), $this->option_page_hook, 'normal', 'high');
			add_meta_box('new-user-approve-news-div', __('Latest News', $this->plugin_id), array(&$this, 'news_meta_box'), $this->option_page_hook, 'side', 'low');
			add_meta_box('new-user-approve-share-div', __('Share', $this->plugin_id), array(&$this, 'share_meta_box'), $this->option_page_hook, 'side', 'low');
			add_meta_box('new-user-approve-support-div', __('Support', $this->plugin_id), array(&$this, 'support_meta_box'), $this->option_page_hook, 'side', 'low');
			add_meta_box('new-user-approve-donate-div', __('Donate', $this->plugin_id), array(&$this, 'donate_meta_box'), $this->option_page_hook, 'side', 'low');
		}
		
		public function news_meta_box() {
?>
			<ul>
				<li><img src="<?php echo $this->pluginurl ?>images/rss.png" alt="" /> <a href="http://picklewagon.com/feed"><?php _e('Subscribe to Picklewagon', $this->plugin_id) ?></a></li>
				<li><img src="<?php echo $this->pluginurl ?>images/rss.png" alt="" /> <a href="http://picklewagon.com/tag/new-user-approve/feed"><?php _e('Subscribe to updates specific to this plugin', $this->plugin_id) ?></a></li>
			</ul>
<?php
		}
		
		public function share_meta_box() {
?>
			<p><a href="http://wordpress.org/extend/plugins/new-user-approve/">Rate this plugin on WordPress.org</a></p>
			<p>If you are using this plugin in a production environment, <a href="mailto:newuserapproveplugin@picklewagon.com">let me know</a> so I can link to you.</p>
<?php
		}
		
		public function support_meta_box() {
?>
			<p><?php _e('If you have any problems with this plugin or good ideas for improvements or new features, please talk about them in the <a href="http://wordpress.org/tags/yahoo-boss">Support forums</a>.', $this->plugin_id) ?></p>
<?php
		}
		
		public function donate_meta_box() {
			$donate = __('I\'ve spent a lot of time programming and maintaining this plugin. If it helps you make money, please donate to show your appreciation.', $this->plugin_id);
			
			echo '<p>'.$donate.'</p>';
			echo '<p><a href="http://picklewagon.com/wordpress/new-user-approve/donate">'.__('Donate', $this->plugin_id).'</a></p>';
		}

		function options_meta_box() {
?>
			<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
				<tr valign="top">
					<th scope="row"><label for="{plugin_slug}_path"><?php _e('Option 1:', $this->plugin_id); ?></label></th>
					<td><input name="{plugin_slug}_path" type="text" id="{plugin_slug}_path" size="45" value="<?php echo $this->options['{plugin_slug}_path'] ;?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="{plugin_slug}_allowed_groups"><?php _e('Option 2:', $this->plugin_id); ?></th>
					<td><input name="{plugin_slug}_allowed_groups" type="text" id="{plugin_slug}_allowed_groups" value="<?php echo $this->options['{plugin_slug}_allowed_groups'] ;?>" />
					</td>
				</tr>
				<tr valign="top">
					<th><label for="{plugin_slug}_enabled"><?php _e('CheckBox #1:', $this->plugin_id); ?></label></th>
					<td><input type="checkbox" id="{plugin_slug}_enabled" name="{plugin_slug}_enabled" <?=($this->options['{plugin_slug}_enabled']==true)?'checked="checked"':''?>></td>
				</tr>
			</table>
<?php
		}
		
		function admin_options_page() {
			global $screen_layout_columns;
?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2>Approve New Users Settings</h2>
				<?php if ( $this->message ) : ?>
				<div id="message" class="updated"><p><?php echo $this->message; ?></p></div>
				<?php endif; ?>
				<form method="post" id="approve_new_users_options">
				<input type="hidden" name="approve_new_users_options_nonce" id="approve_new_users_options_nonce" value="<?php echo wp_create_nonce('approv-new-users-options-edit') ?>" />
				<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes( $this->option_page_hook, 'side', null ); ?>
					</div>
					<div id="post-body">
						<div id="post-body-content">
							<?php do_meta_boxes( $this->option_page_hook, 'normal', null ); ?>
							<p class="submit"><input type="submit" class="button-primary" name="pw_search_save" value="<?php _e('Save Changes', 'pw_yahoo_boss') ?>" /></p>
						</div>
					</div>
				</div>
				</form>
			</div>
<?php
		}
	} // End Class
} // End if class exists statement

if (!class_exists('WP_User_Search')) {
	if (version_compare($wp_version, "3.1", '>=')) {
    	require_once(ABSPATH . 'wp-admin/includes/deprecated.php');
	} else {
		require_once(ABSPATH . 'wp-admin/includes/user.php');
	}
}
class PW_User_Search extends WP_User_Search {
	var $users_per_page = 999999999;
}

// instantiate the class
if (class_exists('pw_new_user_approve')) {
	$pw_new_user_approve = new pw_new_user_approve();
}
?>