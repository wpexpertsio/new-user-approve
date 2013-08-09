<?php

/**
 * Class pw_new_user_approve_admin_approve
 * Admin must approve all new users
 */

class pw_new_user_approve_admin_approve {

	private $unapproved_role = 'pw_unapproved';

    var $_admin_page = 'new-user-approve-admin';

    /**
     * The only instance of pw_new_user_approve_admin_approve.
     *
     * @var pw_new_user_approve_admin_approve
     */
    private static $instance;

    /**
     * Returns the main instance.
     *
     * @return pw_new_user_approve_admin_approve
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new pw_new_user_approve_admin_approve();
        }
        return self::$instance;
    }

    public function __construct() {
        // Actions
        add_action( 'admin_menu',						array( $this, 'admin_menu_link' ) );
        add_action( 'admin_footer',						array( $this, 'admin_scripts_footer' ) );
        add_action( 'init',								array( $this, 'init' ) );
        add_action( 'init',								array( $this, 'process_input' ) );
        add_action( 'register_post',					array( $this, 'request_admin_approval_email' ), 10, 3 );
        add_action( 'register_post',					array( $this, 'create_new_user' ), 10, 3 );
        add_action( 'lostpassword_post',				array( $this, 'lost_password' ) );
        add_action( 'user_register',					array( $this, 'add_user_status' ) );
        add_action( 'new_user_approve_approve_user',	array( $this, 'approve_user' ) );
        add_action( 'new_user_approve_deny_user',		array( $this, 'deny_user' ) );
        add_action( 'rightnow_end',						array( $this, 'dashboard_stats' ) );
        add_action( 'user_register',					array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'new_user_approve_approve_user',	array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'new_user_approve_deny_user',		array( $this, 'delete_new_user_approve_transient' ), 11 );
        add_action( 'deleted_user',						array( $this, 'delete_new_user_approve_transient' ) );
        add_action( 'new_user_approve_deactivate',		array( $this, 'remove_unapproved_role' ) );
        add_action( 'init',								array( $this, 'add_unapproved_role' ) );

        // Filters
        add_filter( 'registration_errors',				array( $this, 'show_user_pending_message' ) );
        add_filter( 'login_message',					array( $this, 'welcome_user' ) );
        add_filter( 'wp_authenticate_user',				array( $this, 'authenticate_user' ), 10, 2 );
        add_filter( 'user_row_actions',                 array( $this, 'user_table_actions' ), 10, 2 );
    }

    public function add_unapproved_role() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		$role_exists = array_key_exists( $this->unapproved_role, $wp_roles->get_names() );

		if ( !$role_exists ) {
        	// the capabilities array is empty so unapproved users can't do anything
        	add_role( $this->unapproved_role, __( 'Unapproved', pw_new_user_approve()->plugin_id ), array() );
		}
    }

    public function remove_unapproved_role() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		$role_exists = array_key_exists( $this->unapproved_role, $wp_roles->get_names() );

		if ( $role_exists )
        	remove_role( $this->unapproved_role );
    }

    /**
     * Enqueue any javascript and css needed for the plugin
     */
    public function init() {
        if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) {
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_style( 'pw-admin-ui-tabs', pw_new_user_approve()->get_plugin_url() . 'ui.tabs.css' );
        }
    }

    /**
     * Add the new menu item to the users portion of the admin menu
     */
    function admin_menu_link() {
        $cap = apply_filters( 'new_user_approve_minimum_cap', 'edit_users' );
        $this->user_page_hook = add_users_page( __( 'Approve New Users', pw_new_user_approve()->plugin_id ), __( 'Approve New Users', pw_new_user_approve()->plugin_id ), $cap, $this->_admin_page, array( $this, 'approve_admin' ) );
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
            <p><span style="font-weight:bold;"><a href="users.php?page=<?php print $this->_admin_page ?>"><?php _e( 'Users', pw_new_user_approve()->plugin_id ); ?></a></span>:
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
            echo '<div id="message" class="updated fade"><p>'.__( 'User successfully updated.', pw_new_user_approve()->plugin_id ).'</p></div>';
        }
        ?>
        <div class="wrap">
            <h2><?php _e( 'User Registration Approval', pw_new_user_approve()->plugin_id ); ?></h2>

            <h3><?php _e( 'User Management', pw_new_user_approve()->plugin_id ); ?></h3>
            <div id="pw_approve_tabs">
                <ul>
                    <li><a href="#pw_pending_users"><span><?php _e( 'Users Pending Approval', pw_new_user_approve()->plugin_id ); ?></span></a></li>
                    <li><a href="#pw_approved_users"><span><?php _e( 'Approved Users', pw_new_user_approve()->plugin_id ); ?></span></a></li>
                    <li><a href="#pw_denied_users"><span><?php _e( 'Denied Users', pw_new_user_approve()->plugin_id ); ?></span></a></li>
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
                    <th><?php _e( 'Username', pw_new_user_approve()->plugin_id ); ?></th>
                    <th><?php _e( 'Name', pw_new_user_approve()->plugin_id ); ?></th>
                    <th><?php _e( 'E-mail', pw_new_user_approve()->plugin_id ); ?></th>
                    <?php if ( 'pending' == $status ) { ?>
                        <th colspan="2" style="text-align: center"><?php _e( 'Actions', pw_new_user_approve()->plugin_id ); ?></th>
                    <?php } else { ?>
                        <th style="text-align: center"><?php _e( 'Actions', pw_new_user_approve()->plugin_id ); ?></th>
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

                    $the_link = admin_url( sprintf( 'users.php?page=%s&user=%s&status=%s', $this->_admin_page, $user->ID, $status ) );
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
                    <td><a href="mailto:<?php echo $user->user_email; ?>" title="<?php _e('email:', pw_new_user_approve()->plugin_id) ?> <?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a></td>
                    <?php if ( $approve ) { ?>
                        <td align="center"><a href="<?php echo $approve_link; ?>" title="<?php _e( 'Approve', pw_new_user_approve()->plugin_id ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Approve', pw_new_user_approve()->plugin_id ); ?></a></td>
                    <?php } ?>
                    <?php if ( $deny ) { ?>
                        <td align="center"><a href="<?php echo $deny_link; ?>" title="<?php _e( 'Deny', pw_new_user_approve()->plugin_id ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Deny', pw_new_user_approve()->plugin_id ); ?></a></td>
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
                $status_i18n = __( 'approved', pw_new_user_approve()->plugin_id );
            } else if ( $status == 'denied' ) {
                $status_i18n = __( 'denied', pw_new_user_approve()->plugin_id );
            } else if ( $status == 'pending' ) {
                $status_i18n = __( 'pending', pw_new_user_approve()->plugin_id );
            }

            echo '<p>'.sprintf( __( 'There are no users with a status of %s', pw_new_user_approve()->plugin_id ), $status_i18n ) . '</p>';
        }
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

        /* send email to admin for approval */
        $message  = sprintf( __( '%1$s (%2$s) has requested a username at %3$s', pw_new_user_approve()->plugin_id ), $user_login, $user_email, $blogname ) . "\r\n\r\n";
        $message .= get_option( 'siteurl' ) . "\r\n\r\n";
        $message .= sprintf( __( 'To approve or deny this user access to %s go to', pw_new_user_approve()->plugin_id ), $blogname ) . "\r\n\r\n";
        $message .= get_option( 'siteurl' ) . '/wp-admin/users.php?page=' . $this->_admin_page . "\r\n";

        $message = apply_filters( 'new_user_approve_request_approval_message', $message, $user_login, $user_email );

        $subject = sprintf( __( '[%s] User Approval', pw_new_user_approve()->plugin_id ), $blogname );
        $subject = apply_filters( 'new_user_approve_request_approval_subject', $subject );

        // send the mail
        wp_mail( get_option( 'admin_email' ), $subject, $message );
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
        if ( empty( $password_reset ) )
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
        wp_cache_delete( $user->user_login, 'userlogins' );

        // send email to user telling of approval
        $user_login = stripslashes( $user->user_login );
        $user_email = stripslashes( $user->user_email );

        // format the message
        $message  = sprintf( __( 'You have been approved to access %s', pw_new_user_approve()->plugin_id ), get_option( 'blogname' ) ) . "\r\n";
        $message .= sprintf( __( 'Username: %s', pw_new_user_approve()->plugin_id ), $user_login ) . "\r\n";
        if ( ! $bypass_password_reset ) {
            $message .= sprintf( __( 'Password: %s', pw_new_user_approve()->plugin_id ), $new_pass ) . "\r\n";
        }
        $message .= wp_login_url() . "\r\n";

        $message = apply_filters( 'new_user_approve_approve_user_message', $message, $user );

        $subject = sprintf( __( '[%s] Registration Approved', pw_new_user_approve()->plugin_id ), get_option( 'blogname' ) );
        $subject = apply_filters( 'new_user_approve_approve_user_subject', $subject );

        // send the mail
        wp_mail( $user_email, $subject, $message );

        // change usermeta tag in database to approved
        update_user_meta( $user->ID, 'pw_user_status', 'approved' );

        do_action( 'new_user_approve_user_approved', $user );
    }

    /**
     * Admin denial of user
     */
    public function deny_user( $user_id ) {
        $user = new WP_User( $user_id );

        // send email to user telling of denial
        $user_email = stripslashes( $user->user_email );

        // format the message
        $message = sprintf( __( 'You have been denied access to %s', pw_new_user_approve()->plugin_id ), get_option( 'blogname' ) );
        $message = apply_filters( 'new_user_approve_deny_user_message', $message, $user );

        $subject = sprintf( __( '[%s] Registration Denied', pw_new_user_approve()->plugin_id ), get_option( 'blogname' ) );
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

        $message  = sprintf( __( 'An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approve or deny your request.', pw_new_user_approve()->plugin_id ) );
        $message .= ' ';
        $message .= sprintf( __( 'You will receive an email with instructions on what you will need to do next. Thanks for your patience.', pw_new_user_approve()->plugin_id ) );
        $message = apply_filters( 'new_user_approve_pending_message', $message );

        $errors->add( 'registration_required', $message, 'message' );

        $success_message = __( 'Registration successful.', pw_new_user_approve()->plugin_id );
        $success_message = apply_filters( 'new_user_approve_registration_message', $success_message );

        login_header( __( 'Pending Approval', pw_new_user_approve()->plugin_id ), '<p class="message register">' . $success_message . '</p>', $errors );
        login_footer();

        // an exit is necessary here so the normal process for user registration doesn't happen
        exit();
    }

    /**
     * Accept input from admin to modify a user
     */
    public function process_input() {
        if ( ( isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) && isset( $_GET['status'] ) ) {
            $valid_request = check_admin_referer( 'pw_new_user_approve_action_' . get_class( $this ) );

            if ( $valid_request ) {
                $status = $_GET['status'];
                $user_id = (int) $_GET['user'];

                do_action( 'new_user_approve_' . $status . '_user', $user_id );
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
    }

    /**
     * Add message to login page saying registration is required.
     *
     * @param string $message
     * @return string
     */
    public function welcome_user($message) {
        if ( ! isset( $_GET['action'] ) ) {
            $welcome = sprintf( __( 'Welcome to %s. This site is accessible to approved users only. To be approved, you must first register.', pw_new_user_approve()->plugin_id ), get_option( 'blogname' ) );
            $welcome = apply_filters( 'new_user_approve_welcome_message', $welcome );

            if ( ! empty( $welcome ) ) {
                $message .= '<p class="message register">' . $welcome . '</p>';
            }
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'register' && ! $_POST ) {
            $instructions = sprintf( __( 'After you register, your request will be sent to the site administrator for approval. You will then receive an email with further instructions.', pw_new_user_approve()->plugin_id ) );
            $instructions = apply_filters( 'new_user_approve_register_instructions', $instructions );

            if ( ! empty( $instructions ) ) {
                $message .= '<p class="message register">' . $instructions . '</p>';
            }
        }

        return $message;
    }

    /**
     * Determine if the user is good to sign in based on their status
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

        // This check needs to happen when a user is created in the admin
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

    public function user_table_actions( $actions, $user_object ) {
        if ( $user_object->ID == get_current_user_id() )
            return $actions;

        $approve_link = wp_nonce_url( add_query_arg( array( 'action' => 'approve', 'user' => $user_object->ID ) ), 'new-user-approve' );
        $deny_link = wp_nonce_url( add_query_arg( array( 'action' => 'deny', 'user' => $user_object->ID ) ), 'new-user-approve' );

        if ( in_array( 'pw_unapproved', $user_object->roles ) )
            $actions[] = '<a href="' . $approve_link . '">Approve</a>';
        else
            $actions[] = '<a href="' . $deny_link . '">Deny</a>';

        return $actions;
    }

}

function pw_new_user_approve_admin_approve() {
    return pw_new_user_approve_admin_approve::instance();
}

pw_new_user_approve_admin_approve();
