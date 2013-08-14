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
        if ( ! isset( self::$instance ) ) {
            self::$instance = new pw_new_user_approve_user_list();
        }
        return self::$instance;
    }

    private function __construct() {
        // Actions
        add_action( 'load-users.php', array( $this, 'update_action' ) );
        // Filters
        add_filter( 'user_row_actions', array( $this, 'user_table_actions' ), 10, 2 );
        add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'status_column' ), 10, 3 );
    }

    public function update_action() {
        if ( isset( $_GET['action'] ) && ( in_array( $_GET['action'], array( 'approve', 'deny' ) ) ) ) {
            check_admin_referer( 'new-user-approve' );

            $status = sanitize_key( $_GET['action'] );
            $user = absint( $_GET['user'] );

            pw_new_user_approve()->update_user_status( $user, $status );

            wp_redirect( admin_url( 'users.php' ) );
        }
    }

    public function user_table_actions( $actions, $user ) {
        if ( $user->ID == get_current_user_id() )
            return $actions;

        $user_status = pw_new_user_approve()->get_user_status( $user->ID );

        $approve_link = wp_nonce_url( add_query_arg( array( 'action' => 'approve', 'user' => $user->ID ) ), 'new-user-approve' );
        $deny_link = wp_nonce_url( add_query_arg( array( 'action' => 'deny', 'user' => $user->ID ) ), 'new-user-approve' );

        $approve_action = '<a href="' . esc_url( $approve_link ) . '">' . __( 'Approve', 'new-user-approve' ) . '</a>';
        $deny_action = '<a href="' . esc_url( $deny_link ) . '">' . __( 'Deny', 'new-user-approve' ) . '</a>';

        if ( $user_status == 'pending' ) {
            $actions[] = $approve_action;
            $actions[] = $deny_action;
        }

        return $actions;
    }

    public function add_column( $columns ) {
        $the_columns['pw_user_status'] = 'Status';

        $newcol = array_slice( $columns, 0, -1 );
        $newcol = array_merge( $newcol, $the_columns );
        $columns = array_merge( $newcol, array_slice( $columns, 1 ) );

        return $columns;
    }

    public function status_column( $val, $column_name, $user_id ) {
        switch ( $column_name ) {
            case 'pw_user_status' :
                return pw_new_user_approve()->get_user_status( $user_id );
                break;

            default:
        }

        return $val;
    }
}

function pw_new_user_approve_user_list() {
    return pw_new_user_approve_user_list::instance();
}

pw_new_user_approve_user_list();
