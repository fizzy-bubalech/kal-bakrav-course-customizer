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

    public function get_exercise_from_id(int $exercise_id){
        global $wpdb;
        $results_table_name = 'exercises';
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}%s WHERE exercise_id = %d", $results_table_name, $exercise_id);
        return $wpdb->get_row($query, ARRAY_A);
        
    }
    public function get_exercise_from_name(int $exercise_name){
        global $wpdb;
        $results_table_name = 'exercises';
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}%s WHERE exercise_name = %d", $results_table_name, $exercise_name);
        return $wpdb->get_row($query, ARRAY_A);
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
    public function add_exercise($exercise_name, $is_time) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exercises';
    
        $wpdb->insert(
            $table_name,
            array(
                'exercise_name' => $exercise_name,
                'is_time' => $is_time
            ),
            array('%s', '%d')
        );
    
        if ($wpdb->last_error) {
            error_log('Database insert error: ' . $wpdb->last_error);
        }
    }
    
    public function get_all_exercises() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exercises';
        return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }
    
    public function get_all_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'results';
        return $wpdb->get_results("
            SELECT r.*, e.exercise_name, u.display_name as user_name 
            FROM $table_name r
            JOIN {$wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
            JOIN {$wpdb->prefix}users u ON r.user_id = u.ID
            ORDER BY r.result_date DESC
        ", ARRAY_A);
    }
    public function delete_exercise($exercise_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exercises';
    
        $wpdb->delete(
            $table_name,
            array('exercise_id' => $exercise_id),
            array('%d')
        );
    
        if ($wpdb->last_error) {
            error_log('Database delete error: ' . $wpdb->last_error);
            return false;
        }
        return true;
    }
    
    public function add_result($user_id, $exercise_id, $result, $result_date, $is_metric) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'results';
    
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'exercise_id' => $exercise_id,
                'result' => $result,
                'result_date' => $result_date,
                'is_metric' => $is_metric
            ),
            array('%d', '%d', '%d', '%s', '%d')
        );
    
        if ($wpdb->last_error) {
            error_log('Database insert error: ' . $wpdb->last_error);
            return false;
        }
        return true;
    }
    
    public function delete_result($result_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'results';
    
        $wpdb->delete(
            $table_name,
            array('result_id' => $result_id),
            array('%d')
        );
    
        if ($wpdb->last_error) {
            error_log('Database delete error: ' . $wpdb->last_error);
            return false;
        }
        return true;
    }
    
    public function get_all_users() {
        global $wpdb;
        return $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->prefix}users", ARRAY_A);
    }

}
