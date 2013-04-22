<?php

/**
 * Class pw_new_user_approve_confirmation
 * Get an email confirmation from new users
 */

class pw_new_user_approve_confirmation {

	private $unverified_role = 'pw_unverified';

	/**
	 * The only instance of pw_new_user_approve_confirmation.
	 *
	 * @var pw_new_user_approve_confirmation
	 */
	private static $instance;

	/**
	 * Returns the main instance.
	 *
	 * @return pw_new_user_approve_confirmation
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new pw_new_user_approve_confirmation();
		}
		return self::$instance;
	}

	public function __construct() {
		if ( $this->is_module_active() ) {
			// Actions
			add_action( 'new_user_approve_deactivate',		array( $this, 'remove_unverified_role' ) );
			add_action( 'init',								array( $this, 'add_unverified_role' ) );

			// Filters
		} else {
			add_action( 'init',								array( $this, 'remove_unverified_role' ) );
		}
	}

	public function is_module_active() {
		$default = true;
		$is_active = (bool) apply_filters( 'new_user_approve_confirmation_active', $default );

		return $is_active;
	}

	public function add_unverified_role() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		$role_exists = array_key_exists( $this->unverified_role, $wp_roles->get_names() );

		if ( !$role_exists ) {
			// the capabilities array is empty so unverified users can't do anything
			add_role( $this->unverified_role, __( 'Unverified', pw_new_user_approve()->plugin_id ), array() );
		}
	}

	public function remove_unverified_role() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		$role_exists = array_key_exists( $this->unverified_role, $wp_roles->get_names() );

		if ( $role_exists ) {
			remove_role( $this->unverified_role );
		}
	}

}

function pw_new_user_approve_confirmation() {
	return pw_new_user_approve_confirmation::instance();
}

pw_new_user_approve_confirmation();
