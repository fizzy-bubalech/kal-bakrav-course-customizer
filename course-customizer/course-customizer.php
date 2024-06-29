<?php
/*
Plugin Name: Course Customizer
Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
Version: 0.1
Author: AST
*/

function custom_create_db_tables() {
    /* 
    creates two new tables, results and exercises, using the $wpdb variable. 
    */
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE {$wpdb->prefix}exercises (
        exercise_id INT NOT NULL AUTO_INCREMENT,
        exercise_name STR NOT NULL,
        is_time BOOLEAN NOT NULL,
        PRIMARY KEY (exercise_id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}results (
        result_id INT NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        exercise_id INT NOT NULL,
        result INT NOT NULL,
        result_date DATETIME NOT NULL,
        is_metric BOOLEAN NOT NULL,
        PRIMARY KEY (result_id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
        FOREIGN KEY (exercise_id) REFERENCES {$wpdb->prefix}exercises(exercise_id) ON DELETE CASCADE
    ) $charset_collate;";

    dbDelta( $sql );
}

register_activation_hook( __FILE__, 'custom_create_db_tables' );

register_deactivation_hook( __FILE__, 'custom_drop_db_tables' );
register_uninstall_hook( __FILE__, 'custom_drop_db_tables' );

function custom_drop_db_tables() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}exercises, {$wpdb->prefix}results" );
}

