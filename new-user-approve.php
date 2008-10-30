<?php
/*
Plugin Name: New User Approve
Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve
Description: This plugin allows administrators to approve users once they register. Only approved users will be allowed to access the blog.
Author: Josh Harrison
Version: 1.0
Author URI: http://www.picklewagon.com
*/ 

// get the directory where this plugin is located
define('PW_NEWUSER_APPROVE_DIR', basename(dirname(__FILE__)));

// this file
define('PW_NEWUSER_APPROVE_FILE', PW_NEWUSER_APPROVE_DIR . '/' . basename(__FILE__));

// create the admin page in the users tab
function pw_approve_add_admin_pages() {
	add_submenu_page('profile.php', 'Approve New Users', 'Approve New Users', 'edit_users', __FILE__, 'pw_approve_admin');
}

// create the view for the admin interface
function pw_approve_admin() {
	// Query the users table
	$wp_user_search = new WP_User_Search($_GET['usersearch'], $_GET['userspage']);

	// Make the user objects
	foreach ($wp_user_search->get_results() as $userid) {
		$user = new WP_User($userid);
		$status = get_usermeta($userid, 'pw_user_status');
		if ($status == '') {
			update_usermeta($userid, 'pw_user_status', 'pending');
			$status = get_usermeta($userid, 'pw_user_status');
		}
		$user_status[$status][] = $user;
	}
	
	if (isset($_GET['user']) && isset($_GET['status'])) {
		echo '<div id="message" class="updated fade"><p>User successfully updated.</p></div>';
	}
?>
	<div class="wrap">
		<h2>User Registration Approval</h2>
		<br />
		<div id="pw_approve_tabs">
			<ul>
				<li><a href="#pw_pending_users"><span>Users Pending Approval</span></a></li>
				<li><a href="#pw_approved_users"><span>Approved Users</span></a></li>
				<li><a href="#pw_denied_users"><span>Denied Users</span></a></li>
			</ul>
			<div id="pw_pending_users">
				<?php pw_approve_table($user_status, 'pending', true, true); ?>
			</div>
			<div id="pw_approved_users">
				<?php pw_approve_table($user_status, 'approved', false, true); ?>
			</div>
			<div id="pw_denied_users">
				<?php pw_approve_table($user_status, 'denied', true, false); ?>
			</div.
		</div>
	</div>
	
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($) {
        $('#pw_approve_tabs > ul').tabs({ fx: { opacity: 'toggle' } });
  });
  //]]>
</script>
<?php
}

// the table that shows the registered users grouped by status
function pw_approve_table($users, $status, $approve, $deny) {
	if (count($users[$status]) > 0) {
?>
<table class="widefat">
<tbody>
<tr class="thead">
	<th><?php _e('ID') ?></th>
	<th><?php _e('Username') ?></th>
	<th><?php _e('Name') ?></th>
	<th><?php _e('E-mail') ?></th>
<?php if ($approve && $deny) { ?>
	<th colspan="2" style="text-align: center"><?php _e('Actions') ?></th>
<?php } else { ?>
	<th style="text-align: center"><?php _e('Actions') ?></th>
<?php } ?>
</tr>
</tbody>
<?php
	// show each of the users
	$row = 1;
	foreach ($users[$status] as $user) {
		$class = ($row % 2) ? '' : ' class="alternate"';
		?><tr <?php echo $class; ?>>
			<td><?php echo $user->ID; ?></td>
			<td><?php echo $user->user_login; ?></td>
			<td><?php echo $user->first_name." ".$user->last_name; ?></td>
			<td><?php echo $user->user_email; ?></td>
			<?php if ($approve) { ?>
			<td align="center"><a href="<?php echo get_settings('siteurl') . "/wp-admin/users.php?page=".PW_NEWUSER_APPROVE_FILE."&user=".$user->ID."&status=approve"; ?>"><?php _e('Approve') ?></a></td>
			<?php } ?>
			<?php if ($deny) { ?>
			<td align="center"><a href="<?php echo get_settings('siteurl') . "/wp-admin/users.php?page=".PW_NEWUSER_APPROVE_FILE."&user=".$user->ID."&status=deny"; ?>"><?php _e('Deny') ?></a></td>
			<?php } ?>
		</tr><?php
		$row++;
	}
?>
</table>
<?php
	} else {
		echo "<p>There are no users with a status of $status</p>";
	}
}

// send an email to the admin to request approval
function pw_approve_request_approval_email() {
	global $user_login, $user_email;
	
	/* send email to admin for approval */
	$message = __($user_login.' ('.$user_email.') has requested a username at '.get_settings('blogname')) . "\r\n\r\n";
	$message .= get_option('siteurl') . "\r\n\r\n";
	$message .= __('To approve or deny this user access to '.get_settings('blogname'). ' go to') . "\r\n\r\n";
	$message .= get_settings('siteurl') . "/wp-admin/users.php?page=".PW_NEWUSER_APPROVE_FILE."\r\n";

	// send the mail
	@wp_mail(get_settings('admin_email'), sprintf(__('[%s] User Approval'), get_settings('blogname')), $message);
	
	// create the user
	$user_pass = wp_generate_password();
	$user_id = wp_create_user($user_login, $user_pass, $user_email);
	
	update_usermeta($user_id, 'pw_user_status', 'pending');
}

// admin approval of user 
function pw_approve_approve_user() {
	global $wpdb;
	
	$user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID = ".$_GET['user']);
	
	// reset password
	$new_pass = substr(md5(uniqid(microtime())), 0, 7);
	$wpdb->query("UPDATE $wpdb->users SET user_pass = MD5('$new_pass'), user_activation_key = '' WHERE user_login = '$user->user_login'");
	wp_cache_delete($user->ID, 'users');
	wp_cache_delete($user->user_login, 'userlogins');
	
	// send email to user telling of approval
	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);
	
	// format the message
	$message  = sprintf(__('You have been approved to access %s \r\n'), get_settings('blogname'));
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $new_pass) . "\r\n";
	$message .= get_settings('siteurl') . "/wp-login.php\r\n";

	// send the mail
	@wp_mail($user_email, sprintf(__('[%s] Registration Approved'), get_settings('blogname')), $message);
	
	// change usermeta tag in database to approved
	update_usermeta($user->ID, 'pw_user_status', 'approved');
}

// admin denial of user 
function pw_approve_deny_user() {
	global $wpdb;
	
	$user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID = ".$_GET['user']);
	
	// send email to user telling of denial
	$user_email = stripslashes($user->user_email);
	
	// format the message
	$message = sprintf(__('You have been denied access to %s'), get_settings('blogname'));

	// send the mail
	@wp_mail($user_email, sprintf(__('[%s] Registration Denied'), get_settings('blogname')), $message);
	
	// change usermeta tag in database to denied
	update_usermeta($user->ID, 'pw_user_status', 'denied');
}

// display a message to the user if they have not been approved
function pw_approve_errors() {
	global $errors;
	
	if ( $errors->get_error_code() )
		return $errors;
	
	$message = "An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approve or deny your request.";
	$message .= "You will receive an email with instructions on what you will need to do next. Thanks for your patience.";
	
	$errors->add('registration_required', __($message), 'message');
	
	login_header(__('Pending Approval'), '<p class="message register">' . __("Registration successful.") . '</p>', $errors);
	
	echo "<body></html>";
	exit();
}

// accept input from admin to modify a user
function pw_approve_process_input() {
	if ($_GET['page'] == PW_NEWUSER_APPROVE_FILE && isset($_GET['status'])) {
		if ($_GET['status'] == 'approve') {
			pw_approve_approve_user();
		}
	
		if ($_GET['status'] == 'deny') {
			pw_approve_deny_user();
		}
		//wp_redirect(get_settings('siteurl').'/wp-admin/users.php?page='.PW_NEWUSER_APPROVE_FILE);
	}
}

// only give a user their password if they have been approved
function pw_approve_lost_password() {
	$username = sanitize_user($_POST['user_login']);
	$user_data = get_userdatabylogin(trim($username));
	if ($user_data->pw_user_status != 'approved') {
		wp_redirect('wp-login.php');
		exit();		
	}
	
	return;
}

function pw_approve_show_message($message) {
	if (!isset($_GET['action'])) {
		$message .= '<p class="message">Welcome to the '.bloginfo('name').'. This site is accessible to approved users only. To be approved, you must first register.</p>';
	}
	
	if ($_GET['action'] == 'register' && !$_POST) {
		$message .= '<p class="message">After you register, your request will be sent to the site administrator for approval. You will then receive an email with further instructions.</p>';
	}
	
	return $message;
}

function pw_approve_init() {
	if($_GET['page'] == PW_NEWUSER_APPROVE_FILE) {
		wp_enqueue_script('jquery-ui-tabs');
	}
}

function pw_approve_add_css() {
	if($_GET['page'] == PW_NEWUSER_APPROVE_FILE) {
		echo '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/'.PW_NEWUSER_APPROVE_DIR.'/ui.tabs.css'.'" type="text/css" />';
	}
}

if (function_exists('add_action')) {
	add_action('admin_menu', 'pw_approve_add_admin_pages');
	add_action('register_post', 'pw_approve_request_approval_email');
	add_action('init', 'pw_approve_process_input');
	add_action('lostpassword_post', 'pw_approve_lost_password');
	add_filter('registration_errors', 'pw_approve_errors');
	add_filter('login_message', 'pw_approve_show_message');
	add_action('init', 'pw_approve_init');
	add_action('admin_head', 'pw_approve_add_css');
}
?>