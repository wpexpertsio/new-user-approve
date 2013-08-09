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

    }
}

function pw_new_user_approve_user_list() {
    return pw_new_user_approve_user_list::instance();
}

pw_new_user_approve_user_list();
