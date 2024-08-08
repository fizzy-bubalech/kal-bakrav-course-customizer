<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Function to drop custom database tables
function ast_kbcc_custom_drop_db_tables() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}results, {$wpdb->prefix}exercises " );
    error_log("! ! ! DROPPED TABLES EXERCISES AND RESULTS ! ! !");
}


ast_kbcc_custom_drop_db_tables();