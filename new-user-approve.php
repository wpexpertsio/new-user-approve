<?php
/*
 Plugin Name: New User Approve
 Plugin URI: http://www.picklewagon.com/wordpress/new-user-approve/
 Description: Allow administrators to approve users once they register. Only approved users will be allowed to access the blog. For support, please go to the <a href="http://wordpress.org/support/plugin/new-user-approve">support forums</a> on wordpress.org.
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

class pw_new_user_approve {
	/**
	 * @var string $plugin_id unique identifier used for localization and other functions
	 */
	var $plugin_id = 'new-user-approve';

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

		register_activation_hook( __FILE__,		array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__,	array( $this, 'deactivation' ) );

        add_action( 'plugins_loaded', array( $this, 'include_files' ) );

		add_filter( 'new_user_approve_admin_approve_active', '__return_false' );
		//add_filter( 'new_user_approve_confirmation_active', '__return_false' );
		add_filter( 'query_vars', array( $this, 'query_var' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
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
		$exit_msg = sprintf( __( 'New User Approve requires WordPress %s or newer.', $this->plugin_id ), $min_wp_version );
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
        require_once( __DIR__ . '/includes/admin-approve.php' );
		require_once( __DIR__ . '/includes/email-confirm.php' );
    }

	public function query_var( $vars ) {
		array_push( $vars, 'new_user_approve' );
		return $vars;
	}

	public function add_rewrite_rules( $rules ) {
		$newrules = array();
		$newrules['new-user-approve/(activate)/?$'] = 'index.php?new_user_approve=$matches[1]' ;
		
		return $newrules + $rules ;
	}

} // End Class

function pw_new_user_approve() {
    return pw_new_user_approve::instance();
}

pw_new_user_approve();
