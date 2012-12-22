<?php
/*
 Plugin Name: New User Approve
 Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve/
 Description: This plugin allows administrators to approve users once they register. Only approved users will be allowed to access the blog. For support, please go to the <a href="http://wordpress.org/support/plugin/new-user-approve">support forums</a> on wordpress.org.
 Author: Josh Harrison
 Version: 1.4.2
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

if ( ! class_exists( 'pw_new_user_approve' ) ) {
class pw_new_user_approve {
	/**
	 * @var string $plugin_id unique identifier used for localization and other functions
	 */
	var $plugin_id = 'new-user-approve';

	var $_admin_page = 'new-user-approve-admin';

	// Class Functions
	/**
	 * PHP 4 Compatible Constructor
	 */
	public function pw_new_user_approve() {
		$this->__construct();
	}

	/**
	 * PHP 5 Constructor
	 */
	public function __construct() {
		// Load up the localization file if we're using WordPress in a different language
		// Just drop it in this plugin's "localization" folder and name it "new-user-approve-[value in wp-config].mo"
		load_plugin_textdomain( $this->plugin_id, false, dirname( plugin_basename( __FILE__ ) ) . '/localization' );

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

		// Actions
		add_action( 'admin_menu', array( $this, 'admin_menu_link' ) );
		add_action( 'admin_footer', array( $this, 'admin_scripts_footer' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'process_input' ) );
		add_action( 'register_post', array( $this, 'send_approval_email' ), 10, 3 );
		add_action( 'lostpassword_post', array( $this, 'lost_password' ) );
		add_action( 'user_register', array( $this, 'add_user_status' ) );
		add_action( 'new_user_approve_approve_user', array( $this, 'approve_user' ) );
		add_action( 'new_user_approve_deny_user', array( $this, 'deny_user' ) );
		add_action( 'rightnow_end', array( $this, 'dashboard_stats' ) );
		add_action( 'user_register', array( $this, 'delete_new_user_approve_transient' ), 11 );
		add_action( 'new_user_approve_approve_user', array( $this, 'delete_new_user_approve_transient' ), 11 );
		add_action( 'new_user_approve_deny_user', array( $this, 'delete_new_user_approve_transient' ), 11 );
		add_action( 'deleted_user', array( $this, 'delete_new_user_approve_transient' ) );

		// Filters
		add_filter( 'registration_errors', array( $this, 'show_user_pending_message' ), 10, 1 );
		add_filter( 'login_message', array( $this, 'welcome_user' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'authenticate_user' ), 10, 2 );
	}

	/**
	 * Require WordPress 3.2.1 on activation
	 * 
	 * @uses register_activation_hook
	 */
	public function activation_check() {
		global $wp_version;

		$min_wp_version = '3.2.1';
		$exit_msg = sprintf( __( 'New User Approve requires WordPress %s or newer.', $this->plugin_id ), $min_wp_version );
		if ( version_compare( $wp_version, $min_wp_version, '<=' ) ) {
			exit( $exit_msg );
		}
	}

	/**
	 * Enqueue any javascript and css needed for the plugin
	 */
	public function init() {
		if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_style( 'pw-admin-ui-tabs', plugins_url( 'ui.tabs.css', __FILE__ ) );
		}
	}
	
	/**
	 * Add the new menu item to the users portion of the admin menu
	 */
	function admin_menu_link() {
		$cap = apply_filters( 'new_user_approve_minimum_cap', 'edit_users' );
		$this->user_page_hook = add_users_page( __( 'Approve New Users', $this->plugin_id ), __( 'Approve New Users', $this->plugin_id ), $cap, $this->_admin_page, array( $this, 'approve_admin' ) );
	}
	
	/**
	 * Output the javascript in the footer to display the tabs
	 */
	public function admin_scripts_footer() {
		global $wp_db_version;

		if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) {
			$page_id = ( $wp_db_version >= 10851 ) ? '#pw_approve_tabs' : '#pw_approve_tabs > ul';
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

	public function dashboard_stats() {
		$user_status = $this->get_user_statuses();
?>
			<div>
				<p><span style="font-weight:bold;"><a href="users.php?page=<?php print $this->_admin_page ?>"><?php _e( 'Users', $this->plugin_id ); ?></a></span>:
				<?php foreach ( $user_status as $status => $users ) print count( $users ) . " $status&nbsp;&nbsp;&nbsp;"; ?>
				</p>
			</div>
<?php
	}

	/**
	 * Create the view for the admin interface
	 */
	public function approve_admin() {
		if ( isset( $_GET['user'] ) && isset( $_GET['status'] ) ) {
			echo '<div id="message" class="updated fade"><p>'.__( 'User successfully updated.', $this->plugin_id ).'</p></div>';
		}
?>
		<div class="wrap">
			<h2><?php _e( 'User Registration Approval', $this->plugin_id ); ?></h2>

			<h3><?php _e( 'User Management', $this->plugin_id ); ?></h3>
			<div id="pw_approve_tabs">
				<ul>
					<li><a href="#pw_pending_users"><span><?php _e( 'Users Pending Approval', $this->plugin_id ); ?></span></a></li>
					<li><a href="#pw_approved_users"><span><?php _e( 'Approved Users', $this->plugin_id ); ?></span></a></li>
					<li><a href="#pw_denied_users"><span><?php _e( 'Denied Users', $this->plugin_id ); ?></span></a></li>
				</ul>
				<div id="pw_pending_users">
					<?php $this->user_table( 'pending' ); ?>
				</div>
				<div id="pw_approved_users">
					<?php $this->user_table( 'approved' ); ?>
				</div>
				<div id="pw_denied_users">
					<?php $this->user_table( 'denied' ); ?>
				</div>
			</div>
		</div>
<?php
	}

	/**
	 * Output the table that shows the registered users grouped by status
	 * 
	 * @param string $status the filter to use for which the users will be queried. Possible values are pending, approved, or denied.
	 */
	public function user_table( $status ) {
		global $current_user;
		
		$approve = ( 'denied' == $status || 'pending' == $status );
		$deny = ( 'approved' == $status || 'pending' == $status );
		
		$user_status = $this->get_user_statuses();
		$users = $user_status[$status];

		if ( count( $users ) > 0 ) {
		?>
<table class="widefat">
	<thead>
		<tr class="thead">
			<th><?php _e( 'Username', $this->plugin_id ); ?></th>
			<th><?php _e( 'Name', $this->plugin_id ); ?></th>
			<th><?php _e( 'E-mail', $this->plugin_id ); ?></th>
		<?php if ( 'pending' == $status ) { ?>
			<th colspan="2" style="text-align: center"><?php _e( 'Actions', $this->plugin_id ); ?></th>
		<?php } else { ?>
			<th style="text-align: center"><?php _e( 'Actions', $this->plugin_id ); ?></th>
		<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php
		// show each of the users
		$row = 1;
		foreach ( $users as $user ) {
			$class = ( $row % 2 ) ? '' : ' class="alternate"';
			$avatar = get_avatar( $user->user_email, 32 );
			if ( $approve ) {
				$approve_link = get_option( 'siteurl' ) . '/wp-admin/users.php?page=' . $this->_admin_page . '&user=' . $user->ID . '&status=approve';
				$approve_link = wp_nonce_url( $approve_link, 'pw_new_user_approve_action_' . get_class( $this ) );
			}
			if ( $deny ) {
				$deny_link = get_option( 'siteurl' ) . '/wp-admin/users.php?page=' . $this->_admin_page . '&user=' . $user->ID . '&status=deny';
				$deny_link = wp_nonce_url( $deny_link, 'pw_new_user_approve_action_' . get_class( $this ) );
			}
			if ( current_user_can( 'edit_user', $user->ID ) ) {
				if ($current_user->ID == $user->ID) {
					$edit_link = 'profile.php';
				} else {
					$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( esc_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$user->ID" ) );
				}
				$edit = '<strong><a href="' . $edit_link . '">' . $user->user_login . '</a></strong><br />';
			} else {
				$edit = '<strong>' . $user->user_login . '</strong>';
			}

			?><tr <?php echo $class; ?>>
				<td><?php echo $avatar . ' ' . $edit; ?></td>
				<td><?php echo get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true ); ?></td>
				<td><a href="mailto:<?php echo $user->user_email; ?>" title="<?php _e('email:', $this->plugin_id) ?> <?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a></td>
				<?php if ( $approve ) { ?>
				<td align="center"><a href="<?php echo $approve_link; ?>" title="<?php _e( 'Approve', $this->plugin_id ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Approve', $this->plugin_id ); ?></a></td>
				<?php } ?>
				<?php if ( $deny ) { ?>
				<td align="center"><a href="<?php echo $deny_link; ?>" title="<?php _e( 'Deny', $this->plugin_id ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Deny', $this->plugin_id ); ?></a></td>
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
			if ( $status == 'approved' ) {
				$status_i18n = __( 'approved', $this->plugin_id );
			} else if ( $status == 'denied' ) {
				$status_i18n = __( 'denied', $this->plugin_id );
			} else if ( $status == 'pending' ) {
				$status_i18n = __( 'pending', $this->plugin_id );
			}

			echo '<p>'.sprintf( __( 'There are no users with a status of %s', $this->plugin_id ), $status_i18n ) . '</p>';
		}
	}

	/**
	 * Send an email to the admin to request approval
	 */
	public function send_approval_email( $user_login, $user_email, $errors ) {
		if ( ! $errors->get_error_code() ) {
			/* check if already exists */
			$user_data = get_user_by( 'login', $user_login );
			if ( ! empty( $user_data ) ){
				$errors->add( 'registration_required' , __( 'User name already exists', $this->plugin_id ), 'message' );
			} else {
				/* send email to admin for approval */
				$message  = sprintf( __( '%1$s (%2$s) has requested a username at %3$s', $this->plugin_id ), $user_login, $user_email, get_option( 'blogname' ) ) . "\r\n\r\n";
				$message .= get_option( 'siteurl' ) . "\r\n\r\n";
				$message .= sprintf( __( 'To approve or deny this user access to %s go to', $this->plugin_id ), get_option( 'blogname' ) ) . "\r\n\r\n";
				$message .= get_option( 'siteurl' ) . '/wp-admin/users.php?page=' . $this->_admin_page . "\r\n";
				
				$message = apply_filters( 'new_user_approve_request_approval_message', $message, $user_login, $user_email );
				
				$subject = sprintf( __( '[%s] User Approval', $this->plugin_id ), get_option( 'blogname' ) );
				$subject = apply_filters( 'new_user_approve_request_approval_subject', $subject );

				// send the mail
				wp_mail( get_option( 'admin_email' ), $subject, $message );

				// create the user
				$user_pass = wp_generate_password();
				$user_id = wp_create_user( $user_login, $user_pass, $user_email );
			}
		}
	}

	/**
	 * Admin approval of user
	 */
	public function approve_user() {
		global $wpdb;

		$user_id = (int) $_GET['user'];
		$user = new WP_User( $user_id );

		$bypass_password_reset = apply_filters( 'new_user_approve_bypass_password_reset', false );
		
		if ( ! $bypass_password_reset ) {
			// reset password to know what to send the user
			$new_pass = wp_generate_password();
			$data = array(
				'user_pass' => md5($new_pass),
				'user_activation_key' => '',
			);
			$where = array(
				'ID' => $user->ID,
			);
			$wpdb->update($wpdb->users, $data, $where, array( '%s', '%s' ), array( '%d' ) );
		}

		wp_cache_delete( $user->ID, 'users' );
		wp_cache_delete( $user->user_login, 'userlogins' );

		// send email to user telling of approval
		$user_login = stripslashes( $user->user_login );
		$user_email = stripslashes( $user->user_email );

		// format the message
		$message  = sprintf( __( 'You have been approved to access %s', $this->plugin_id ), get_option( 'blogname' ) ) . "\r\n";
		$message .= sprintf( __( 'Username: %s', $this->plugin_id ), $user_login ) . "\r\n";
		if ( ! $bypass_password_reset ) {
			$message .= sprintf( __( 'Password: %s', $this->plugin_id ), $new_pass ) . "\r\n";
		}
		$message .= get_option( 'siteurl' ) . "/wp-login.php\r\n";

		$message = apply_filters( 'new_user_approve_approve_user_message', $message, $user );
		
		$subject = sprintf( __( '[%s] Registration Approved', $this->plugin_id ), get_option( 'blogname' ) );
		$subject = apply_filters( 'new_user_approve_approve_user_subject', $subject );
		
		// send the mail
		@wp_mail( $user_email, $subject, $message );

		// change usermeta tag in database to approved
		update_user_meta( $user->ID, 'pw_user_status', 'approved' );
		
		do_action( 'new_user_approve_user_approved', $user );
	}

	/**
	 * Admin denial of user
	 */
	public function deny_user() {
		$user_id = (int) $_GET['user'];
		$user = new WP_User( $user_id );

		// send email to user telling of denial
		$user_email = stripslashes( $user->user_email );

		// format the message
		$message = sprintf( __( 'You have been denied access to %s', $this->plugin_id ), get_option( 'blogname' ) );
		$message = apply_filters( 'new_user_approve_deny_user_message', $message, $user );
		
		$subject = sprintf( __( '[%s] Registration Denied', $this->plugin_id ), get_option( 'blogname' ) );
		$subject = apply_filters( 'new_user_approve_deny_user_subject', $subject );

		// send the mail
		@wp_mail( $user_email, $subject, $message );

		// change usermeta tag in database to denied
		update_user_meta( $user->ID, 'pw_user_status', 'denied' );
		
		do_action( 'new_user_approve_user_denied', $user );
	}

	/**
	 * Display a message to the user after they have registered
	 */
	public function show_user_pending_message($errors) {
		if ( ! empty( $_POST['redirect_to'] ) ) {
			// if a redirect_to is set, honor it
			wp_safe_redirect( $_POST['redirect_to'] );
			exit();
		}
		
		// if there is an error already, let it do it's thing
		if ( $errors->get_error_code() )
			return $errors;
		
		$message  = sprintf( __( 'An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approve or deny your request.', $this->plugin_id ) );
		$message .= sprintf( __( 'You will receive an email with instructions on what you will need to do next. Thanks for your patience.', $this->plugin_id ) );
		$message = apply_filters( 'new_user_approve_pending_message', $message );

		$errors->add( 'registration_required', $message, 'message' );

		$success_message = __( 'Registration successful.', $this->plugin_id );
		$success_message = apply_filters( 'new_user_approve_registration_message', $success_message );
		
		if ( function_exists( 'login_header' ) ) {
			login_header( __( 'Pending Approval', $this->plugin_id ), '<p class="message register">' . $success_message . '</p>', $errors );
			login_footer();
			
			// an exit is necessay here so the normal process for user registration doesn't happen
			exit();
		}
	}

	/**
	 * Accept input from admin to modify a user
	 */
	public function process_input() {
		if ( ( isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) && isset( $_GET['status'] ) ) {
			$valid_request = check_admin_referer( 'pw_new_user_approve_action_' . get_class( $this ) );

			if ( $valid_request ) {
				if ( $_GET['status'] == 'approve' ) {
					do_action( 'new_user_approve_approve_user' );
				}

				if ( $_GET['status'] == 'deny' ) {
					do_action( 'new_user_approve_deny_user' );
				}
			}
		}
	}

	/**
	 * Only give a user their password if they have been approved
	 */
	public function lost_password() {
		$is_email = strpos( $_POST['user_login'], '@' );
		if ( $is_email === false ) {
			$username = sanitize_user( $_POST['user_login'] );
			$user_data = get_user_by( 'login', trim( $username ) );
		} else {
			$email = is_email( $_POST['user_login'] );
			$user_data = get_user_by( 'email', $email );
		}

		if ( $user_data->pw_user_status && $user_data->pw_user_status != 'approved' ) {
			wp_redirect( 'wp-login.php' );
			exit();
		}

		return;
	}

	/**
	 * Add message to login page saying registration is required.
	 * 
	 * @param string $message
	 * @return string
	 */
	public function welcome_user($message) {
		if ( ! isset( $_GET['action'] ) ) {
			$welcome = sprintf( __( 'Welcome to %s. This site is accessible to approved users only. To be approved, you must first register.', $this->plugin_id ), get_option( 'blogname' ) );
			$welcome = apply_filters( 'new_user_approve_welcome_message', $welcome );
			
			if ( ! empty( $welcome ) ) {
				$message .= '<p class="message">' . $welcome . '</p>';
			}
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'register' && ! $_POST ) {
			$instructions = sprintf( __( 'After you register, your request will be sent to the site administrator for approval. You will then receive an email with further instructions.', $this->plugin_id ) );
			$instructions = apply_filters( 'new_user_approve_register_instructions', $instructions );
			
			if ( ! empty( $instructions ) ) {
				$message .= '<p class="message">' . $instructions . '</p>';
			}
		}

		return $message;
	}

	/**
	 * Determine if the user is good to sign inbased on their status
	 * 
	 * @param array $userdata
	 * @param string $password
	 */
	public function authenticate_user( $userdata, $password ) {
		$status = get_user_meta( $userdata->ID, 'pw_user_status', true );

		if ( empty( $status ) ) {
			// the user does not have a status so let's assume the user is good to go
			return $userdata;
		}

		$message = false;
		switch ( $status ) {
			case 'pending':
				$pending_message = __( '<strong>ERROR</strong>: Your account is still pending approval.' );
				$pending_message = apply_filters( 'new_user_approve_pending_error', $pending_message );
				
				$message = new WP_Error( 'pending_approval', $pending_message );
				break;
			case 'denied':
				$denied_message = __( '<strong>ERROR</strong>: Your account has been denied access to this site.' );
				$denied_message = apply_filters( 'new_user_approve_denied_error', $denied_message );
				
				$message = new WP_Error( 'denied_access', $denied_message );
				break;
			case 'approved':
				$message = $userdata;
				break;
		}

		return $message;
	}

	/**
	 * Give the user a status
	 * @param int $user_id
	 */
	public function add_user_status( $user_id ) {
		$status = 'pending';
		if ( isset( $_REQUEST['action'] ) && 'createuser' == $_REQUEST['action'] ) {
			$status = 'approved';
		}
		update_user_meta( $user_id, 'pw_user_status', $status );
	}

	/**
	 * Get a status of all the users and save them using a transient
	 */
	public function get_user_statuses() {
		$valid_stati = array( 'pending', 'approved', 'denied' );
		$user_status = get_transient( 'new_user_approve_user_statuses' );
		
		if ( false === $user_status ) {
			$user_status = array();
			
			foreach ( $valid_stati as $status ) {
				// Query the users table
				if ( $status != 'approved' ) {
					// Query the users table
					$query = array(
						'meta_key' => 'pw_user_status',
						'meta_value' => $status,
					);
					$wp_user_search = new WP_User_Query( $query );
				} else {
					$users = get_users( 'blog_id=1' );
					$approved_users = array();
					foreach( $users as $user ) {
						$the_status = get_user_meta( $user->ID, 'pw_user_status', true );
						
						if ( $the_status == 'approved' || empty( $the_status ) ) {
							$approved_users[] = $user->ID;
						}
					}
					
					// get all approved users and any user without a status
					$query = array( 'include' => $approved_users );
					$wp_user_search = new WP_User_Query( $query );
				}
				
				$user_status[$status] = $wp_user_search->get_results();
				
				set_transient( 'new_user_approve_user_statuses', $user_status );
			}
		}
		
		foreach ( $valid_stati as $status ) {
			$user_status[$status] = apply_filters( 'new_user_approve_user_status', $user_status[$status], $status );
		}
		
		return $user_status;
	}
	
	public function delete_new_user_approve_transient() {
		delete_transient( 'new_user_approve_user_statuses' );
	}
	
} // End Class
} // End if class exists statement

// instantiate the class
if ( class_exists( 'pw_new_user_approve' ) ) {
	$pw_new_user_approve = new pw_new_user_approve();
}
