<?php

/**
 * Class pw_new_user_approve_admin_approve
 * Admin must approve all new users
 */

class pw_new_user_approve_admin_approve {

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
		if ( !isset( self::$instance ) ) {
			self::$instance = new pw_new_user_approve_admin_approve();
		}
		return self::$instance;
	}

	private function __construct() {
		// Actions
		add_action( 'admin_menu', array( $this, 'admin_menu_link' ) );
		add_action( 'admin_init', array( $this, 'process_input' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'admin_init', array( $this, 'notice_ignore' ) );
		add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Add the new menu item to the users portion of the admin menu
	 *
	 * @uses admin_menu
	 */
	function admin_menu_link() {
		$show_admin_page = apply_filters( 'new_user_approve_show_admin_page', true );

		if ( $show_admin_page ) {
			$cap = apply_filters( 'new_user_approve_minimum_cap', 'edit_users' );
			$hook = add_users_page( __( 'Approve New Users', 'new-user-approve' ), __( 'Approve New Users', 'new-user-approve' ), $cap, $this->_admin_page, array( $this, 'approve_admin' ) );

			add_action( 'load-' . $hook, array( $this, 'admin_enqueue_scripts' ) );
		}
	}

	/**
	 * Create the view for the admin interface
	 */
	public function approve_admin() {
		require_once( pw_new_user_approve()->get_plugin_dir() . '/admin/templates/approve.php' );
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

		$user_status = pw_new_user_approve()->get_user_statuses();
		$users = $user_status[$status];

		if ( count( $users ) > 0 ) {
			?>
			<table class="widefat">
				<thead>
				<tr class="thead">
					<th><?php _e( 'Username', 'new-user-approve' ); ?></th>
					<th><?php _e( 'Name', 'new-user-approve' ); ?></th>
					<th><?php _e( 'E-mail', 'new-user-approve' ); ?></th>
					<?php if ( 'pending' == $status ) { ?>
						<th colspan="2" style="text-align: center"><?php _e( 'Actions', 'new-user-approve' ); ?></th>
					<?php } else { ?>
						<th style="text-align: center"><?php _e( 'Actions', 'new-user-approve' ); ?></th>
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
						if ( isset( $_REQUEST['tab'] ) )
							$approve_link = add_query_arg( array( 'tab' => esc_attr( $_REQUEST['tab'] ) ), $approve_link );
						$approve_link = wp_nonce_url( $approve_link, 'pw_new_user_approve_action_' . get_class( $this ) );
					}
					if ( $deny ) {
						$deny_link = get_option( 'siteurl' ) . '/wp-admin/users.php?page=' . $this->_admin_page . '&user=' . $user->ID . '&status=deny';
						if ( isset( $_REQUEST['tab'] ) )
							$deny_link = add_query_arg( 'tab', esc_attr( $_REQUEST['tab'] ), $deny_link );
						$deny_link = wp_nonce_url( $deny_link, 'pw_new_user_approve_action_' . get_class( $this ) );
					}

					if ( current_user_can( 'edit_user', $user->ID ) ) {
						if ( $current_user->ID == $user->ID ) {
							$edit_link = 'profile.php';
						} else {
							$edit_link = add_query_arg( 'wp_http_referer', urlencode( esc_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$user->ID" );
						}
						$edit = '<strong><a href="' . esc_url( $edit_link ) . '">' . esc_html( $user->user_login ) . '</a></strong>';
					} else {
						$edit = '<strong>' . esc_html( $user->user_login ) . '</strong>';
					}

					?>
					<tr <?php echo $class; ?>>
					<td><?php echo $avatar . ' ' . $edit; ?></td>
					<td><?php echo get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true ); ?></td>
					<td><a href="mailto:<?php echo $user->user_email; ?>"
						   title="<?php _e( 'email:', 'new-user-approve' ) ?> <?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a>
					</td>
					<?php if ( $approve && $user->ID != get_current_user_id() ) { ?>
						<td align="center"><a href="<?php echo esc_url( $approve_link ); ?>"
											  title="<?php _e( 'Approve', 'new-user-approve' ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Approve', 'new-user-approve' ); ?></a>
						</td>
					<?php } ?>
					<?php if ( $deny && $user->ID != get_current_user_id() ) { ?>
						<td align="center"><a href="<?php echo esc_url( $deny_link ); ?>"
											  title="<?php _e( 'Deny', 'new-user-approve' ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Deny', 'new-user-approve' ); ?></a>
						</td>
					<?php } ?>
					<?php if ( $user->ID == get_current_user_id() ) : ?>
						<td colspan="2">&nbsp;</td>
					<?php endif; ?>
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
				$status_i18n = __( 'approved', 'new-user-approve' );
			} else if ( $status == 'denied' ) {
				$status_i18n = __( 'denied', 'new-user-approve' );
			} else if ( $status == 'pending' ) {
				$status_i18n = __( 'pending', 'new-user-approve' );
			}

			echo '<p>' . sprintf( __( 'There are no users with a status of %s', 'new-user-approve' ), $status_i18n ) . '</p>';
		}
	}

	/**
	 * Accept input from admin to modify a user
	 *
	 * @uses init
	 */
	public function process_input() {
		if ( ( isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) && isset( $_GET['status'] ) ) {
			$valid_request = check_admin_referer( 'pw_new_user_approve_action_' . get_class( $this ) );

			if ( $valid_request ) {
				$status = sanitize_key( $_GET['status'] );
				$user_id = absint( $_GET['user'] );

				pw_new_user_approve()->update_user_status( $user_id, $status );
			}
		}
	}

	/**
	 * Display a notice on the legacy page that notifies the user of the new interface.
	 *
	 * @uses admin_notices
	 */
	public function admin_notice() {
		$screen = get_current_screen();

		if ( $screen->id == 'users_page_new-user-approve-admin' ) {
			$user_id = get_current_user_id();

			// Check that the user hasn't already clicked to ignore the message
			if ( !get_user_meta( $user_id, 'pw_new_user_approve_ignore_notice' ) ) {
				echo '<div class="updated"><p>';
				printf( __( 'You can now update user status on the <a href="%1$s">users admin page</a>. | <a href="%2$s">Hide Notice</a>', 'new-user-approve' ), admin_url( 'users.php' ), add_query_arg( array( 'new-user-approve-ignore-notice' => 1 ) ) );
				echo "</p></div>";
			}
		}
	}

	/**
	 * If user clicks to ignore the notice, add that to their user meta
	 *
	 * @uses admin_init
	 */
	public function notice_ignore() {
		if ( isset( $_GET['new-user-approve-ignore-notice'] ) && '1' == $_GET['new-user-approve-ignore-notice'] ) {
			$user_id = get_current_user_id();
			add_user_meta( $user_id, 'pw_new_user_approve_ignore_notice', '1', true );
		}
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'post' );
	}

	public function add_meta_boxes() {
		add_meta_box( 'nua-approve-admin', __( 'Approve Users', 'new-user-approve' ), array( $this, 'metabox_main' ), 'users_page_new-user-approve-admin', 'main', 'high' );
		add_meta_box( 'nua-updates', __( 'Updates', 'new-user-approve' ), array( $this, 'metabox_ajax' ), 'users_page_new-user-approve-admin', 'side', 'default', array( 'url' => 'http://newuserapprove.com/wp-json/posts/52' ) );
		add_meta_box( 'nua-support', __( 'Support', 'new-user-approve' ), array( $this, 'metabox_ajax' ), 'users_page_new-user-approve-admin', 'side', 'default', array( 'url' => 'http://newuserapprove.com/wp-json/posts/54' ) );
		add_meta_box( 'nua-feedback', __( 'Feedback', 'new-user-approve' ), array( $this, 'metabox_ajax' ), 'users_page_new-user-approve-admin', 'side', 'default', array( 'url' => 'http://newuserapprove.com/wp-json/posts/56' ) );
	}

	public function metabox_main() {
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'pending_users';
?>
		<h3 class="nav-tab-wrapper" style="padding-bottom: 0; border-bottom: none;">
			<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=pending_users' ) ); ?>"
				class="nav-tab<?php echo $active_tab == 'pending_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Users Pending Approval', 'new-user-approve' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=approved_users' ) ); ?>"
			   class="nav-tab<?php echo $active_tab == 'approved_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Approved Users', 'new-user-approve' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=denied_users' ) ); ?>"
			   class="nav-tab<?php echo $active_tab == 'denied_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Denied Users', 'new-user-approve' ); ?></span></a>
		</h3>

<?php if ( $active_tab == 'pending_users' ) : ?>
	<div id="pw_pending_users">
		<?php $this->user_table( 'pending' ); ?>
	</div>
<?php elseif ( $active_tab == 'approved_users' ) : ?>
	<div id="pw_approved_users">
		<?php $this->user_table( 'approved' ); ?>
	</div>
<?php
elseif ( $active_tab == 'denied_users' ) : ?>
	<div id="pw_denied_users">
		<?php $this->user_table( 'denied' ); ?>
	</div>
<?php endif;
	}

	public function metabox_ajax( $post, $metabox = array() ) {
		$response = wp_remote_get( $metabox['args']['url'] );
		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$details = json_decode( $body );
			print $details->content;
		}
	}

}

function pw_new_user_approve_admin_approve() {
	return pw_new_user_approve_admin_approve::instance();
}

pw_new_user_approve_admin_approve();
