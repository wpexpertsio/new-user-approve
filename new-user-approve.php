<?php
/*
 Plugin Name: New User Approve
 Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve/
 Description: Allow administrators to approve users once they register. Only approved users will be allowed to access the blog. For support, please go to the <a href="http://wordpress.org/support/plugin/new-user-approve">support forums</a> on wordpress.org.
 Author: Josh Harrison
 Version: 1.5.5
 Author URI: http://picklewagon.com/
 */

class pw_new_user_approve {

    /**
     * The only instance of pw_new_user_approve.
     *
     * @var pw_new_user_approve
     */
    private static $instance;

    /**
     * Returns the main instance.
     *
     * @return pw_new_user_approve
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new pw_new_user_approve();
        }
        return self::$instance;
    }

	private function __construct() {
		// Load up the localization file if we're using WordPress in a different language
		// Just drop it in this plugin's "localization" folder and name it "new-user-approve-[value in wp-config].mo"
		load_plugin_textdomain( 'new-user-approve', false, dirname( plugin_basename( __FILE__ ) ) . '/localization' );

		register_activation_hook( __FILE__,		array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__,	array( $this, 'deactivation' ) );

        // Actions
        add_action( 'wp_loaded', array( $this, 'admin_loaded' ) );
        add_action( 'rightnow_end',	array( $this, 'dashboard_stats' ) );
        add_action( 'user_register', array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'new_user_approve_approve_user', array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'new_user_approve_deny_user', array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'deleted_user',	array( $this, 'delete_new_user_approve_transient' ) );
        add_action( 'register_post', array( $this, 'request_admin_approval_email' ), 10, 3 );
        add_action( 'register_post', array( $this, 'create_new_user' ), 10, 3 );
        add_action( 'lostpassword_post', array( $this, 'lost_password' ) );
        add_action( 'user_register', array( $this, 'add_user_status' ) );
        add_action( 'new_user_approve_approve_user', array( $this, 'approve_user' ) );
        add_action( 'new_user_approve_deny_user', array( $this, 'deny_user' ) );

        // Filters
        add_filter( 'wp_authenticate_user',	array( $this, 'authenticate_user' ) );
        add_filter( 'registration_errors', array( $this, 'show_user_pending_message' ) );
        add_filter( 'login_message', array( $this, 'welcome_user' ) );
        add_filter( 'new_user_approve_validate_status_update', array( $this, 'validate_status_update' ), 10, 3 );
	}

    public function get_plugin_url() {
        return plugin_dir_url( __FILE__ );
    }

	public function get_plugin_dir() {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Require a minimum version of WordPress on activation
	 * 
	 * @uses register_activation_hook
	 */
	public function activation() {
		global $wp_version;

		$min_wp_version = '3.5.1';
		$exit_msg = sprintf( __( 'New User Approve requires WordPress %s or newer.', 'new-user-approve' ), $min_wp_version );
		if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
			exit( $exit_msg );
		}
		
		// since the right version of WordPress is being used, run a hook
		do_action( 'new_user_approve_activate' );
	}
	
	/**
	 * @uses register_deactivation_hook
	 */
	public function deactivation() {
		do_action( 'new_user_approve_deactivate' );
	}

    /**
     * Makes it possible to disable the user admin integration. Must happen after
     * WordPress is loaded.
     *
     * @uses wp_loaded
     */
    public function admin_loaded() {
        $user_admin_integration = apply_filters( 'new_user_approve_user_admin_integration', true );
        if ( $user_admin_integration ) {
            require_once( dirname( __FILE__ ) . '/includes/user-list.php' );
        }

        $legacy_panel = apply_filters( 'new_user_approve_user_admin_legacy', true );
        if ( $legacy_panel ) {
            require_once( dirname( __FILE__ ) . '/includes/admin-approve.php' );
        }
    }

    /**
     * Get the status of a user.
     *
     * @param int $user_id
     * @return string the status of the user
     */
    public function get_user_status( $user_id ) {
        $user_status = get_user_meta( $user_id, 'pw_user_status', true );

        if ( empty( $user_status ) )
            $user_status = 'approved';

        return $user_status;
    }

    /**
     * Update the status of a user. The new status must be either 'approve' or 'deny'.
     *
     * @param int $user
     * @param string $status
     */
    public function update_user_status( $user, $status ) {
        $user_id = absint( $user );
        if ( ! $user_id )
            return;

        if ( ! in_array( $status, array( 'approve', 'deny' ) ) )
            return;

        $do_update = apply_filters( 'new_user_approve_validate_status_update', true, $user_id, $status );

        if ( !$do_update )
            return;

        // where it all happens
        do_action( 'new_user_approve_' . $status . '_user', $user_id );
        do_action( 'new_user_approve_user_status_update', $user_id, $status );
    }

    /**
     * Get the valid statuses. Anything outside of the returned array is an invalid status.
     *
     * @return array
     */
    public function get_valid_statuses() {
        return array( 'pending', 'approved', 'denied' );
    }

    /**
     * Only validate the update if the status has been updated to prevent unnecessary update
     * and especially emails.
     *
     * @param bool $do_update
     * @param int $user_id
     * @param string $status either 'approve' or 'deny'
     */
    public function validate_status_update( $do_update, $user_id, $status ) {
        $current_status = pw_new_user_approve()->get_user_status( $user_id );

        if ( $status == 'approve' )
            $new_status = 'approved';
        else
            $new_status = 'denied';

        if ( $current_status == $new_status )
            $do_update = false;

        return $do_update;
    }

    /**
     * Determine if the user is good to sign in based on their status.
     *
     * @uses wp_authenticate_user
     * @param array $userdata
     */
    public function authenticate_user( $userdata ) {
        $status = $this->get_user_status( $userdata->ID );

        if ( empty( $status ) ) {
            // the user does not have a status so let's assume the user is good to go
            return $userdata;
        }

        $message = false;
        switch ( $status ) {
            case 'pending':
                $pending_message = __( '<strong>ERROR</strong>: Your account is still pending approval.', 'new-user-approve' );
                $pending_message = apply_filters( 'new_user_approve_pending_error', $pending_message );

                $message = new WP_Error( 'pending_approval', $pending_message );
                break;
            case 'denied':
                $denied_message = __( '<strong>ERROR</strong>: Your account has been denied access to this site.', 'new-user-approve' );
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
     * Get a status of all the users and save them using a transient
     */
    public function get_user_statuses() {
        $valid_stati = $this->get_valid_statuses();
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
                    // get all approved users and any user without a status
                    $query = array(
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => 'pw_user_status',
                                'value' => 'approved',
                                'compare' => '='
                            ),
                            array(
                                'key' => 'pw_user_status',
                                'value' => '',
                                'compare' => 'NOT EXISTS'
                            ),
                        ),
                    );
                    $wp_user_search = new WP_User_Query( $query );
                }

                $user_status[$status] = $wp_user_search->get_results();
            }

            set_transient( 'new_user_approve_user_statuses', $user_status );
        }

        foreach ( $valid_stati as $status ) {
            $user_status[$status] = apply_filters( 'new_user_approve_user_status', $user_status[$status], $status );
        }

        return $user_status;
    }

    /**
     * Delete the transient storing all of the user statuses.
     *
     * @uses user_register
     * @uses deleted_user
     * @uses new_user_approve_approve_user
     * @uses new_user_approve_deny_user
     */
    public function delete_new_user_approve_transient() {
        delete_transient( 'new_user_approve_user_statuses' );
    }

    /**
     * Display the stats on the WP dashboard. Will show 1 line with a count
     * of users and their status.
     *
     * @uses rightnow_end
     */
    public function dashboard_stats() {
        $user_status = $this->get_user_statuses();
        ?>
        <div>
            <p><span style="font-weight:bold;"><a href="<?php echo apply_filters( 'new_user_approve_dashboard_link', 'users.php' ); ?>"><?php _e( 'Users', 'new-user-approve' ); ?></a></span>:
            <?php foreach ( $user_status as $status => $users ) :
                print count( $users ) . " " . __( $status, 'new-user-approve' ) . "&nbsp;&nbsp;&nbsp;";
            endforeach; ?>
            </p>
        </div>
        <?php
    }

    /**
     * The default notification message that is sent to site admin when requesting approval.
     *
     * @return string
     */
    public function default_notification_message() {
        $message  = __( 'USERNAME (USEREMAIL) has requested a username at SITENAME', 'new-user-approve' ) . "\n\n";
        $message .= "SITEURL\n\n";
        $message .= __( 'To approve or deny this user access to SITENAME go to', 'new-user-approve' ) . "\n\n";
        $message .= "ADMINURL\n\n";

        return $message;
    }

    /**
     * Send an email to the admin to request approval. If there are already errors,
     * just go back and let core do it's thing.
     *
     * @uses register_post
     * @param string $user_login
     * @param string $user_email
     * @param object $errors
     */
    public function request_admin_approval_email( $user_login, $user_email, $errors ) {
        if ( $errors->get_error_code() ) {
            return $errors;
        }

        // The blogname option is escaped with esc_html on the way into the database in sanitize_option
        // we want to reverse this for the plain text arena of emails.
        $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

        $default_admin_url = admin_url( 'users.php?s&pw-status-query-submit=Filter&new_user_approve_filter=pending&paged=1' );
        $admin_url = apply_filters( 'new_user_approve_admin_link', $default_admin_url );

        /* send email to admin for approval */
        $message = apply_filters( 'new_user_approve_request_approval_message_default', $this->default_notification_message() );

        $message = str_replace( 'USERNAME', $user_login, $message );
        $message = str_replace( 'USEREMAIL', $user_email, $message );
        $message = str_replace( 'SITENAME', $blogname, $message );
        $message = str_replace( 'SITEURL', get_option( 'siteurl' ), $message );
        $message = str_replace( 'ADMINURL', $admin_url, $message );

        $message = apply_filters( 'new_user_approve_request_approval_message', $message, $user_login, $user_email );

        $subject = sprintf( __( '[%s] User Approval', 'new-user-approve' ), $blogname );
        $subject = apply_filters( 'new_user_approve_request_approval_subject', $subject );

        $to = apply_filters( 'new_user_approve_email_admins', array( get_option( 'admin_email' ) ) );
        $to = array_unique( $to );

        // send the mail
        wp_mail( $to, $subject, $message, $this->email_message_headers() );
    }

    /**
     * Create a new user after the registration has been validated. Normally,
     * when a user registers, an email is sent to the user containing their
     * username and password. The email does not get sent to the user until
     * the user is approved when using the default behavior of this plugin.
     *
     * @uses register_post
     * @param string $user_login
     * @param string $user_email
     * @param object $errors
     */
    public function create_new_user( $user_login, $user_email, $errors ) {
        if ( $errors->get_error_code() ) {
            return $errors;
        }

        // create the user
        $user_pass = wp_generate_password( 12, false );
        $user_id = wp_create_user( $user_login, $user_pass, $user_email );
        if ( ! $user_id ) {
            $errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
            return $errors;
        }
    }

    /**
     * Admin approval of user
     *
     * @uses new_user_approve_approve_user
     */
    public function approve_user( $user_id ) {
        $user = new WP_User( $user_id );

        // password should only be reset for users that:
        // * have never logged in
        // * are just approved for the first time

        // If the password has already been reset for this user,
        // $password_reset will be a unix timestamp
        $password_reset = get_user_meta( $user_id, 'pw_user_approve_password_reset' );

        // Get the current user status. By default each user is given a pending
        // status when the user is created (with this plugin activated). If the
        // user was created while this plugin was not active, the user will not
        // have a status set.
        $user_status = get_user_meta( $user_id, 'pw_user_status' );

        // Default behavior is to reset password
        $bypass_password_reset = false;

        // if no status is set, don't reset password
        if ( empty( $user_status ) )
            $bypass_password_reset = true;

        // if the password has already been reset, absolutely bypass
        if ( !empty( $password_reset ) )
            $bypass_password_reset = true;

        $bypass_password_reset = apply_filters( 'new_user_approve_bypass_password_reset', $bypass_password_reset );

        if ( ! $bypass_password_reset ) {
            global $wpdb;

            // reset password to know what to send the user
            $new_pass = wp_generate_password( 12, false );
            $data = array(
                'user_pass' => md5( $new_pass ),
                'user_activation_key' => '',
            );
            $where = array(
                'ID' => $user->ID,
            );
            $wpdb->update( $wpdb->users, $data, $where, array( '%s', '%s' ), array( '%d' ) );

            // Set up the Password change nag.
            update_user_option( $user->ID, 'default_password_nag', true, true );

            // Set this meta field to track that the password has been reset by
            // the plugin. Don't reset it again.
            update_user_meta( $user->ID, 'pw_user_approve_password_reset', time() );
        }

        wp_cache_delete( $user->ID, 'users' );
        wp_cache_delete( $user->data->user_login, 'userlogins' );

        // send email to user telling of approval
        $user_login = stripslashes( $user->data->user_login );
        $user_email = stripslashes( $user->data->user_email );

        // format the message
        $message  = sprintf( __( 'You have been approved to access %s', 'new-user-approve' ), get_option( 'blogname' ) ) . "\r\n";
        $message .= sprintf( __( 'Username: %s', 'new-user-approve' ), $user_login ) . "\r\n";
        if ( ! $bypass_password_reset ) {
            $message .= sprintf( __( 'Password: %s', 'new-user-approve' ), $new_pass ) . "\r\n";
        }
        $message .= wp_login_url() . "\r\n";

        $message = apply_filters( 'new_user_approve_approve_user_message', $message, $user );

        $subject = sprintf( __( '[%s] Registration Approved', 'new-user-approve' ), get_option( 'blogname' ) );
        $subject = apply_filters( 'new_user_approve_approve_user_subject', $subject );

        // send the mail
        wp_mail( $user_email, $subject, $message, $this->email_message_headers() );

        // change usermeta tag in database to approved
        update_user_meta( $user->ID, 'pw_user_status', 'approved' );

        do_action( 'new_user_approve_user_approved', $user );
    }

    /**
     * Admin denial of user
     *
     * @uses new_user_approve_deny_user
     */
    public function deny_user( $user_id ) {
        $user = new WP_User( $user_id );

        // send email to user telling of denial
        $user_email = stripslashes( $user->user_email );

        // format the message
        $message = sprintf( __( 'You have been denied access to %s', 'new-user-approve' ), get_option( 'blogname' ) );
        $message = apply_filters( 'new_user_approve_deny_user_message', $message, $user );

        $subject = sprintf( __( '[%s] Registration Denied', 'new-user-approve' ), get_option( 'blogname' ) );
        $subject = apply_filters( 'new_user_approve_deny_user_subject', $subject );

        // send the mail
        @wp_mail( $user_email, $subject, $message, $this->email_message_headers() );

        // change usermeta tag in database to denied
        update_user_meta( $user->ID, 'pw_user_status', 'denied' );

        do_action( 'new_user_approve_user_denied', $user );
    }

    public function email_message_headers() {
        $admin_email = get_option( 'admin_email' );
        if ( empty( $admin_email ) )
            $admin_email = 'support@' . $_SERVER['SERVER_NAME'];

        $from_name = get_option( 'blogname' );

        $headers = array(
            "From: \"{$from_name}\" <{$admin_email}>\n",
            "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n",
        );

        $headers = apply_filters( 'new_user_approve_email_header', $headers );

        return $headers;
    }

    /**
     * Display a message to the user after they have registered
     *
     * @uses registration_errors
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

        $message  = sprintf( __( 'An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approve or deny your request.', 'new-user-approve' ) );
        $message .= ' ';
        $message .= sprintf( __( 'You will receive an email with instructions on what you will need to do next. Thanks for your patience.', 'new-user-approve' ) );
        $message = apply_filters( 'new_user_approve_pending_message', $message );

        $errors->add( 'registration_required', $message, 'message' );

        $success_message = __( 'Registration successful.', 'new-user-approve' );
        $success_message = apply_filters( 'new_user_approve_registration_message', $success_message );

        login_header( __( 'Pending Approval', 'new-user-approve' ), '<p class="message register">' . $success_message . '</p>', $errors );
        login_footer();

        // an exit is necessary here so the normal process for user registration doesn't happen
        exit();
    }

    /**
     * Only give a user their password if they have been approved
     *
     * @uses lostpassword_post
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
    }

    /**
     * Add message to login page saying registration is required.
     *
     * @uses login_message
     * @param string $message
     * @return string
     */
    public function welcome_user($message) {
        if ( ! isset( $_GET['action'] ) ) {
            $welcome = sprintf( __( 'Welcome to %s. This site is accessible to approved users only. To be approved, you must first register.', 'new-user-approve' ), get_option( 'blogname' ) );
            $welcome = apply_filters( 'new_user_approve_welcome_message', $welcome );

            if ( ! empty( $welcome ) ) {
                $message .= '<p class="message register">' . $welcome . '</p>';
            }
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'register' && ! $_POST ) {
            $instructions = sprintf( __( 'After you register, your request will be sent to the site administrator for approval. You will then receive an email with further instructions.', 'new-user-approve' ) );
            $instructions = apply_filters( 'new_user_approve_register_instructions', $instructions );

            if ( ! empty( $instructions ) ) {
                $message .= '<p class="message register">' . $instructions . '</p>';
            }
        }

        return $message;
    }

    /**
     * Give the user a status
     *
     * @uses user_register
     * @param int $user_id
     */
    public function add_user_status( $user_id ) {
        $status = 'pending';

        // This check needs to happen when a user is created in the admin
        if ( isset( $_REQUEST['action'] ) && 'createuser' == $_REQUEST['action'] ) {
            $status = 'approved';
        }
        update_user_meta( $user_id, 'pw_user_status', $status );
    }

} // End Class

function pw_new_user_approve() {
    return pw_new_user_approve::instance();
}

pw_new_user_approve();
