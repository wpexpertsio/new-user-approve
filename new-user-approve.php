<?php
/*
 Plugin Name: New User Approve
 Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve/
 Description: Allow administrators to approve users once they register. Only approved users will be allowed to access the blog. For support, please go to the <a href="http://wordpress.org/support/plugin/new-user-approve">support forums</a> on wordpress.org.
 Author: Josh Harrison
 Version: 1.5 alpha
 Author URI: http://www.picklewagon.com/
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

        add_action( 'plugins_loaded', array( $this, 'include_files' ) );
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

		$min_wp_version = '3.2.1';
		$exit_msg = sprintf( __( 'New User Approve requires WordPress %s or newer.', 'new-user-approve' ), $min_wp_version );
		if ( version_compare( $wp_version, $min_wp_version, '<=' ) ) {
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

    public function include_files() {
        require_once( dirname( __FILE__ ) . '/includes/admin-approve.php' );
        require_once( dirname( __FILE__ ) . '/includes/user-list.php' );
    }

    public function get_user_status( $user_id ) {
        $user_status = get_user_meta( $user_id, 'pw_user_status', true );

        if ( empty( $user_status ) )
            $user_status = 'approved';

        return $user_status;
    }

} // End Class

function pw_new_user_approve() {
    return pw_new_user_approve::instance();
}

pw_new_user_approve();
