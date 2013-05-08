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
			add_action( 'register_post',					array( $this, 'create_new_user' ), 10, 3 );

			// Filters
			add_filter( 'template_include',					array( $this, 'activation_template' ) );
		} else {
			add_action( 'init',								array( $this, 'remove_unverified_role' ) );
		}
	}

	public function is_module_active() {
		$default = true;

		// Multisite installs take care of email confirmation for us
		if ( is_multisite() )
			$default = false;

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

	/**
	 * Create a new user after the registration has been validated. Normally,
	 * when a user registers, an email is sent to the user containing their
	 * username and password. A confirmation email will be sent to the user instead
	 * asking for the user to confirm the email address.
	 *
	 * @uses register_post
	 * @param string $user_login
	 * @param string $user_email
	 * @param object $errors
	 */
	public function create_new_user( $user_login, $user_email, $errors ) {
		if ( $errors->get_error_code() ) {
			return;
		}

		if ( ! pw_new_user_approve_admin_approve()->is_module_active() ) {
			// create the user
			$user_pass = wp_generate_password( 12, false );
			$user_id = wp_create_user( $user_login, $user_pass, $user_email );
			if ( ! $user_id ) {
				$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
				return;
			}

			$activation_key = wp_hash( $user_id );
			update_user_meta( $user_id, 'activation_key', $activation_key );

			if ( apply_filters( 'new_user_approve_signup_send_activation_key', true ) ) {
				$this->new_user_confirmation( $user_id, $user_email, $activation_key );
			}
		}
	}

	public function new_user_confirmation( $user_id, $user_email, $key ) {
		$user = get_userdata( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$activate_url = add_query_arg( array( 'key' => $key ), $this->get_activation_link() );
		$activate_url = esc_url( $activate_url );
	}

	public function get_activation_link() {
		if ( get_option('permalink_structure') ) {
			$link = trailingslashit( home_url() . '/new-user-approve/activate' );
		} else {
			$link = home_url() . '/index.php?new_user_approve=activate';
		}

		return apply_filters( 'pw_new_user_approve_activation_link', $link );
	}

	public function activation_template( $template ) {
		if ( get_query_var( 'new_user_approve' ) != 'activate' )
			return $template;

		if ( $overridden_template = locate_template( 'new-user-approve/activate.php' ) ) {
			// locate_template() returns path to file
			// if either the child theme or the parent theme have overridden the template
			$template = $overridden_template;
		} else {
			// If neither the child nor parent theme have overridden the template,
			// we load the template from the 'templates' sub-directory of the directory this file is in
			$template = pw_new_user_approve()->get_plugin_dir() . 'user/activate.php';
		}

		return $template;
	}

}

function pw_new_user_approve_confirmation() {
	return pw_new_user_approve_confirmation::instance();
}

pw_new_user_approve_confirmation();
