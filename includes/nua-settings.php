<?php 

class nua_settings {
	private static $instance;

	/**
	 * Returns the main instance.
	 *
	 * @return pw_new_user_approve_admin_approve
	 */
	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new nua_settings();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_nua_settings' ) );
	}


	function register_nua_settings() {
		//register our settings
		register_setting( 'nua-settings-group', 'prevent_approval_email' );
	}

}// End Class

function nua_settings() {
	return nua_settings::instance();
}

nua_settings();