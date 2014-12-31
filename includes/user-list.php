<?php

class pw_new_user_approve_user_list {

	/**
	 * The only instance of pw_new_user_approve_user_list.
	 *
	 * @var pw_new_user_approve_user_list
	 */
	private static $instance;

	/**
	 * Returns the main instance.
	 *
	 * @return pw_new_user_approve_user_list
	 */
	public static function instance() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new pw_new_user_approve_user_list();
		}
		return self::$instance;
	}

	private function __construct() {
		// Actions
		add_action( 'load-users.php', array( $this, 'update_action' ) );
		add_action( 'restrict_manage_users', array( $this, 'status_filter' ) );
		add_action( 'pre_user_query', array( $this, 'filter_by_status' ) );
		add_action( 'admin_footer-users.php', array( $this, 'admin_footer' ) );
		add_action( 'load-users.php', array( $this, 'bulk_action' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'show_user_profile', array( $this, 'profile_status_field' ) );
		add_action( 'edit_user_profile', array( $this, 'profile_status_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile_status_field' ) );
		add_action( 'admin_menu', array( $this, 'pending_users_bubble' ), 999 );

		// Filters
		add_filter( 'user_row_actions', array( $this, 'user_table_actions' ), 10, 2 );
		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'status_column' ), 10, 3 );
	}

	/**
	 * Update the user status if the approve or deny link was clicked.
	 *
	 * @uses load-users.php
	 */
	public function update_action() {
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'approve', 'deny' ) ) && !isset( $_GET['new_role'] ) ) {
			check_admin_referer( 'new-user-approve' );

			$sendback = remove_query_arg( array( 'approved', 'denied', 'deleted', 'ids', 'pw-status-query-submit', 'new_role' ), wp_get_referer() );
			if ( !$sendback )
				$sendback = admin_url( 'users.php' );

			$wp_list_table = _get_list_table( 'WP_Users_List_Table' );
			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			$status = sanitize_key( $_GET['action'] );
			$user = absint( $_GET['user'] );

			pw_new_user_approve()->update_user_status( $user, $status );

			if ( $_GET['action'] == 'approve' ) {
				$sendback = add_query_arg( array( 'approved' => 1, 'ids' => $user ), $sendback );
			} else {
				$sendback = add_query_arg( array( 'denied' => 1, 'ids' => $user ), $sendback );
			}

			wp_redirect( $sendback );
			exit;
		}
	}

	/**
	 * Add the approve or deny link where appropriate.
	 *
	 * @uses user_row_actions
	 * @param array $actions
	 * @param object $user
	 * @return array
	 */
	public function user_table_actions( $actions, $user ) {
		if ( $user->ID == get_current_user_id() )
			return $actions;

		$user_status = pw_new_user_approve()->get_user_status( $user->ID );

		$approve_link = add_query_arg( array( 'action' => 'approve', 'user' => $user->ID ) );
		$approve_link = remove_query_arg( array( 'new_role' ), $approve_link );
		$approve_link = wp_nonce_url( $approve_link, 'new-user-approve' );

		$deny_link = add_query_arg( array( 'action' => 'deny', 'user' => $user->ID ) );
		$deny_link = remove_query_arg( array( 'new_role' ), $deny_link );
		$deny_link = wp_nonce_url( $deny_link, 'new-user-approve' );

		$approve_action = '<a href="' . esc_url( $approve_link ) . '">' . __( 'Approve', 'new-user-approve' ) . '</a>';
		$deny_action = '<a href="' . esc_url( $deny_link ) . '">' . __( 'Deny', 'new-user-approve' ) . '</a>';

		if ( $user_status == 'pending' ) {
			$actions[] = $approve_action;
			$actions[] = $deny_action;
		} else if ( $user_status == 'approved' ) {
			$actions[] = $deny_action;
		} else if ( $user_status == 'denied' ) {
			$actions[] = $approve_action;
		}

		return $actions;
	}

	/**
	 * Add the status column to the user table
	 *
	 * @uses manage_users_columns
	 * @param array $columns
	 * @return array
	 */
	public function add_column( $columns ) {
		$the_columns['pw_user_status'] = __( 'Status', 'new-user-approve' );

		$newcol = array_slice( $columns, 0, -1 );
		$newcol = array_merge( $newcol, $the_columns );
		$columns = array_merge( $newcol, array_slice( $columns, 1 ) );

		return $columns;
	}

	/**
	 * Show the status of the user in the status column
	 *
	 * @uses manage_users_custom_column
	 * @param string $val
	 * @param string $column_name
	 * @param int $user_id
	 * @return string
	 */
	public function status_column( $val, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'pw_user_status' :
				$status = pw_new_user_approve()->get_user_status( $user_id );
				if ( $status == 'approved' ) {
					$status_i18n = __( 'approved', 'new-user-approve' );
				} else if ( $status == 'denied' ) {
					$status_i18n = __( 'denied', 'new-user-approve' );
				} else if ( $status == 'pending' ) {
					$status_i18n = __( 'pending', 'new-user-approve' );
				}
				return $status_i18n;
				break;

			default:
		}

		return $val;
	}

	/**
	 * Add a filter to the user table to filter by user status
	 *
	 * @uses restrict_manage_users
	 */
	public function status_filter() {
		$filter_button = submit_button( __( 'Filter', 'new-user-approve' ), 'button', 'pw-status-query-submit', false, array( 'id' => 'pw-status-query-submit' ) );
		$filtered_status = ( isset( $_GET['new_user_approve_filter'] ) ) ? esc_attr( $_GET['new_user_approve_filter'] ) : '';

		?>
		<label class="screen-reader-text"
			   for="new_user_approve_filter"><?php _e( 'View all users', 'new-user-approve' ); ?></label>
		<select id="new_user_approve_filter" name="new_user_approve_filter" style="float: none; margin: 0 0 0 15px;">
			<option value=""><?php _e( 'View all users', 'new-user-approve' ); ?></option>
			<?php foreach ( pw_new_user_approve()->get_valid_statuses() as $status ) : ?>
				<option
					value="<?php echo esc_attr( $status ); ?>"<?php selected( $status, $filtered_status ); ?>><?php echo esc_html( $status ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php echo apply_filters( 'new_user_approve_filter_button', $filter_button ); ?>
		<style>
			#pw-status-query-submit {
				float: right;
				margin: 2px 0 0 5px;
			}
		</style>
	<?php
	}

	/**
	 * Modify the user query if the status filter is being used.
	 *
	 * @uses pre_user_query
	 * @param $query
	 */
	public function filter_by_status( $query ) {
		global $wpdb;

		if ( !is_admin() ) {
			return;
		}

		$screen = get_current_screen();
		if ( isset( $screen ) && 'users' != $screen->id ) {
			return;
		}

		if ( isset( $_GET['new_user_approve_filter'] ) && $_GET['new_user_approve_filter'] != '' ) {
			$filter = esc_attr( $_GET['new_user_approve_filter'] );

			$query->query_from .= " INNER JOIN {$wpdb->usermeta} wp_usermeta ON ( {$wpdb->users}.ID = wp_usermeta.user_id )";

			if ( 'approved' == $filter ) {
				$query->query_fields = "DISTINCT SQL_CALC_FOUND_ROWS {$wpdb->users}.ID";
				$query->query_from .= " LEFT JOIN {$wpdb->usermeta} AS mt1 ON ({$wpdb->users}.ID = mt1.user_id AND mt1.meta_key = 'pw_user_status')";
				$query->query_where .= " AND ( ( wp_usermeta.meta_key = 'pw_user_status' AND CAST(wp_usermeta.meta_value AS CHAR) = 'approved' ) OR mt1.user_id IS NULL )";
			} else {
				$query->query_where .= " AND ( (wp_usermeta.meta_key = 'pw_user_status' AND CAST(wp_usermeta.meta_value AS CHAR) = '{$filter}') )";
			}
		}
	}

	/**
	 * Use javascript to add the ability to bulk modify the status of users.
	 *
	 * @uses admin_footer-users.php
	 */
	public function admin_footer() {
		$screen = get_current_screen();

		if ( $screen->id == 'users' ) : ?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('<option>').val('approve').text('<?php _e( 'Approve', 'new-user-approve' )?>').appendTo("select[name='action']");
					$('<option>').val('approve').text('<?php _e( 'Approve', 'new-user-approve' )?>').appendTo("select[name='action2']");

					$('<option>').val('deny').text('<?php _e( 'Deny', 'new-user-approve' )?>').appendTo("select[name='action']");
					$('<option>').val('deny').text('<?php _e( 'Deny', 'new-user-approve' )?>').appendTo("select[name='action2']");
				});
			</script>
		<?php endif;
	}

	/**
	 * Process the bulk status updates
	 *
	 * @uses load-users.php
	 */
	public function bulk_action() {
		$screen = get_current_screen();

		if ( $screen->id == 'users' ) {

			// get the action
			$wp_list_table = _get_list_table( 'WP_Users_List_Table' );
			$action = $wp_list_table->current_action();

			$allowed_actions = array( 'approve', 'deny' );
			if ( !in_array( $action, $allowed_actions ) ) {
				return;
			}

			// security check
			check_admin_referer( 'bulk-users' );

			// make sure ids are submitted
			if ( isset( $_REQUEST['users'] ) ) {
				$user_ids = array_map( 'intval', $_REQUEST['users'] );
			}

			if ( empty( $user_ids ) ) {
				return;
			}

			$sendback = remove_query_arg( array( 'approved', 'denied', 'deleted', 'ids', 'new_user_approve_filter', 'pw-status-query-submit', 'new_role' ), wp_get_referer() );
			if ( !$sendback ) {
				$sendback = admin_url( 'users.php' );
			}

			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			switch ( $action ) {
				case 'approve':
					$approved = 0;
					foreach ( $user_ids as $user_id ) {
						pw_new_user_approve()->update_user_status( $user_id, 'approve' );
						$approved++;
					}

					$sendback = add_query_arg( array( 'approved' => $approved, 'ids' => join( ',', $user_ids ) ), $sendback );
					break;

				case 'deny':
					$denied = 0;
					foreach ( $user_ids as $user_id ) {
						pw_new_user_approve()->update_user_status( $user_id, 'deny' );
						$denied++;
					}

					$sendback = add_query_arg( array( 'denied' => $denied, 'ids' => join( ',', $user_ids ) ), $sendback );
					break;

				default:
					return;
			}

			$sendback = remove_query_arg( array( 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ), $sendback );

			wp_redirect( $sendback );
			exit();
		}
	}

	/**
	 * Show a message on the users page if a status has been updated.
	 *
	 * @uses admin_notices
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( $screen->id != 'users' ) {
			return;
		}

		$message = null;

		if ( isset( $_REQUEST['denied'] ) && (int) $_REQUEST['denied'] ) {
			$denied = esc_attr( $_REQUEST['denied'] );
			$message = sprintf( _n( 'User denied.', '%s users denied.', $denied, 'new-user-approve' ), number_format_i18n( $denied ) );
		}

		if ( isset( $_REQUEST['approved'] ) && (int) $_REQUEST['approved'] ) {
			$approved = esc_attr( $_REQUEST['approved'] );
			$message = sprintf( _n( 'User approved.', '%s users approved.', $approved, 'new-user-approve' ), number_format_i18n( $approved ) );
		}

		if ( !empty( $message ) ) {
			echo '<div class="updated"><p>' . $message . '</p></div>';
		}
	}

	/**
	 * Display the dropdown on the user profile page to allow an admin to update the user status.
	 *
	 * @uses show_user_profile
	 * @uses edit_user_profile
	 * @param object $user
	 */
	public function profile_status_field( $user ) {
		if ( $user->ID == get_current_user_id() ) {
			return;
		}

		$user_status = pw_new_user_approve()->get_user_status( $user->ID );
		?>
		<table class="form-table">
			<tr>
				<th><label for="new_user_approve_status"><?php _e( 'Access Status', 'new-user-approve' ); ?></label>
				</th>
				<td>
					<select id="new_user_approve_status" name="new_user_approve_status">
						<?php if ( $user_status == 'pending' ) : ?>
							<option value=""><?php _e( '-- Status --', 'new-user-approve' ); ?></option>
						<?php endif; ?>
						<?php foreach ( array( 'approved', 'denied' ) as $status ) : ?>
							<option
								value="<?php echo esc_attr( $status ); ?>"<?php selected( $status, $user_status ); ?>><?php echo esc_html( $status ); ?></option>
						<?php endforeach; ?>
					</select>
					<span
						class="description"><?php _e( 'If user has access to sign in or not.', 'new-user-approve' ); ?></span>
					<?php if ( $user_status == 'pending' ) : ?>
						<br/><span
							class="description"><?php _e( 'Current user status is <strong>pending</strong>.', 'new-user-approve' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	<?php
	}

	/**
	 * Save the user status when updating from the user profile.
	 *
	 * @uses edit_user_profile_update
	 * @param int $user_id
	 * @return bool
	 */
	public function save_profile_status_field( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		if ( !empty( $_POST['new_user_approve_status'] ) ) {
			$new_status = esc_attr( $_POST['new_user_approve_status'] );

			if ( $new_status == 'approved' )
				$new_status = 'approve'; else if ( $new_status == 'denied' )
				$new_status = 'deny';

			pw_new_user_approve()->update_user_status( $user_id, $new_status );
		}
	}

	/**
	 * Add bubble for number of users pending to the user menu
	 *
	 * @uses admin_menu
	 */
	public function pending_users_bubble() {
		global $menu;

		$users = pw_new_user_approve()->get_user_statuses();

		// Count Number of Pending Members
		$pending_users = count( $users['pending'] );

		// Make sure there are pending members
		if ( $pending_users > 0 ) {
			// Locate the key of
			$key = $this->recursive_array_search( 'users.php', $menu );

			// Not found, just in case
			if ( ! $key ) {
				return;
			}

			// Modify menu item
			$menu[$key][0] .= sprintf( '<span class="update-plugins count-%1$s" style="background-color:white;color:black;margin-left:5px;"><span class="plugin-count">%1$s</span></span>', $pending_users );
		}
	}

	/**
	 * Recursively search the menu array to determine the key to place the bubble.
	 * 
	 * @param $needle
	 * @param $haystack
	 * @return bool|int|string
	 */
	public function recursive_array_search( $needle, $haystack ) {
		foreach ( $haystack as $key => $value ) {
			$current_key = $key;
			if ( $needle === $value || ( is_array( $value ) && $this->recursive_array_search( $needle, $value ) !== false ) ) {
				return $current_key;
			}
		}
		return false;
	}
}

function pw_new_user_approve_user_list() {
	return pw_new_user_approve_user_list::instance();
}

pw_new_user_approve_user_list();
