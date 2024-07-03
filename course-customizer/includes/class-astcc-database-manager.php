<?php


namespace CourseCustomizer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class ASTCC_Database_Manager {
     

    // Function to create custom database tables
    public function custom_create_db_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create the exercises table
        $sql_exercises = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}exercises (
            exercise_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            exercise_name VARCHAR(255) NOT NULL,
            is_time TINYINT(1) NOT NULL,
            PRIMARY KEY (exercise_id)
        ) $charset_collate;";

        // SQL query to create the results table
        $sql_results = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}results (
            result_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            exercise_id BIGINT(20) NOT NULL,
            result INT(11) NOT NULL,
            result_date DATETIME NOT NULL,
            is_metric TINYINT(1) NOT NULL,
            PRIMARY KEY (result_id),
            CONSTRAINT {$wpdb->prefix}fk_user FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            CONSTRAINT {$wpdb->prefix}fk_exercise FOREIGN KEY (exercise_id) REFERENCES {$wpdb->prefix}exercises(exercise_id) ON DELETE CASCADE
        ) $charset_collate;";

        // Execute the SQL queries to create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $exercises_result = dbDelta($sql_exercises);
        $results_result = dbDelta($sql_results);

        // Log the results of table creation
        error_log("Exercises table creation result: " . print_r($exercises_result, true));
        error_log("Results table creation result: " . print_r($results_result, true));

        // Check for any database errors
        if (!empty($wpdb->last_error)) {
            error_log("Database Error: " . $wpdb->last_error);
        }
    }

    // Function to check and log database information
    public function check_database_info() {
        global $wpdb;
        
        // Get and log the MariaDB version
        $version = $wpdb->get_var("SELECT VERSION()");
        error_log("MariaDB Version: " . $version);

        // Check for InnoDB support
        $engines = $wpdb->get_results("SHOW ENGINES", ARRAY_A);
        $innodb_support = 'No';
        foreach ($engines as $engine) {
            if ($engine['Engine'] === 'InnoDB' && ($engine['Support'] === 'YES' || $engine['Support'] === 'DEFAULT')) {
                $innodb_support = 'Yes';
                break;
            }
        }
        error_log("InnoDB Support: " . $innodb_support);
    }

    
    public function insert_results_into_wp_results_table($results){
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'results';
    
        foreach ($results as $item) {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $item['user_id'],
                    'exercise_id' => $item['exercise_id'],
                    'result' => $item['result'],
                    'result_date' => $item['result_date'],
                    'is_metric' => $item['is_metric']
                ),
                array(
                    '%d', // user_id
                    '%d', // exercise_id
                    '%d', // result
                    '%s', // result_date
                    '%d'  // is_metric
                )
            );
    
            if ($wpdb->last_error) {
                // Handle error
                error_log('Database insert error: ' . $wpdb->last_error);
            }
        }
    
    }
    public function update_quiz_statistic_answer_data_invalid($statistic_ref_id, $question_post_id){
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_pro_quiz_statistic'; 
    
        $updated = $wpdb->update(
            $table_name,
            array('answer_data' => 'invalid'), // Data to update
            array(
                'statistic_ref_id' => $statistic_ref_id,
                'question_post_id' => $question_post_id
            ), // Where clause
            array('%s'), // Data format for 'answer_data'
            array('%d', '%d') // Data format for where clause
        );
    
        if ($updated === false) {
            // Error handling
            error_log("Failed to update answer_data: " . $wpdb->last_error);
        }
    }

    




}
