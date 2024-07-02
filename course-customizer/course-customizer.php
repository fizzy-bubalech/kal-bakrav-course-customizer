<?php
/*
Plugin Name: Course Customizer
Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
Version: 0.1
Author: AST
*/

// Display an admin notice when the plugin is activated

use Symfony\Component\CssSelector\Node\FunctionNode;

add_action('admin_notices', function() {
    echo '<div class="notice notice-success"><p>Course Customizer plugin activated!</p></div>';
});

// Function to create custom database tables
function custom_create_db_tables() {
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

    // Verify if the tables were created successfully
    $exercises_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}exercises'") === "{$wpdb->prefix}exercises";
    $results_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}results'") === "{$wpdb->prefix}results";

    error_log("Exercises table exists: " . ($exercises_exists ? 'Yes' : 'No'));
    error_log("Results table exists: " . ($results_exists ? 'Yes' : 'No'));
}

// Register the function to run when the plugin is activated
register_activation_hook( __FILE__, 'custom_create_db_tables' );

// Register functions to run when the plugin is deactivated or uninstalled
#register_deactivation_hook( __FILE__, 'custom_drop_db_tables' );
#register_uninstall_hook( __FILE__, 'custom_drop_db_tables' );

// Function to drop custom database tables
function custom_drop_db_tables() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}results, {$wpdb->prefix}exercises " );
    error_log("! ! ! DROPPED TABLES EXERCISES AND RESULTS ! ! !");
}

function is_quiz_metric_assessment($quiz_post_id){
    /*
        Queries wp_posts table for the post title of a post with the id of $quiz_post_id. 
        Then it checks if quiz post title has the words 'quiz assessment' or 'אומדן מדדים' in the post title. 
    */

    global $wpdb;
    $query = $wpdb->prepare("SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $quiz_post_id);
    $post_title = $wpdb->get_var($query);
    $metric_assessment_hebrew = 'אומדן מדדים';
    $metric_assessment_english = 'metric assessment';

    if ($post_title !== null && (strpos($post_title, $metric_assessment_hebrew) !== false || strpos($post_title, $metric_assessment_english) !== false)) {
        error_log("! ! ! IS METRIC ASSESSMENT ! ! !");
        return true;
    } 
    return false; 
}

add_action('learndash_quiz_completed', 'get_learndash_quiz_data', 10, 2);

function get_learndash_quiz_data($quiz_data, $user) {

    $user_id = $user->ID;
    $quiz_post_id = $quiz_data['quiz'];
    
    error_log("! ! ! get_learndash_quiz_data quiz_data: ". print_r($quiz_data,true));

    //if(is_quiz_metric_assessment($quiz_post_id) == false) return; //Checks if quiz is a metric assessment and if not returns.

    $results = array();

    if (isset($quiz_data['questions']) && is_array($quiz_data['questions'])) {
        foreach ($quiz_data['questions'] as $question_id => $question_obj) {
            if ($question_obj instanceof WpProQuiz_Model_Question) {
                $question_post_id = $question_obj->getQuestionPostId();
                $question_text = $question_obj->getQuestion();

                // Get user's answer from the statistic data
                $user_answer = get_user_answer_from_statistics($quiz_data['statistic_ref_id'], $question_post_id);

                error_log("! ! ! USER'S ANSWER: $user_answer ! ! !");

                $results[] = array(
                    'user_id' => $user_id,
                    'exercise_id' => 1,
                    'result' => $user_answer,
                    'is_metric' => 1,
                    'result_date' => date('Y-m-d H:i:s'),
                );
            }
        }
    }

    // Log the final data
    error_log('Question Answer Pairs: ' . print_r($results, true));

    // You can now use $question_answer_pairs array as needed
    // For example, you could send it to an external API, or store it in the database
}


/*
function insert_result_into_wp_results_table($result){
    global $wpdb;

    $table_name = $wpdb->prefix . 'results';

    foreach ($result as $item) {
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
*/
function get_user_answer_from_statistics($statistic_ref_id, $question_post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'learndash_pro_quiz_statistic';
    
    $query = $wpdb->prepare(
        "SELECT answer_data FROM $table_name WHERE statistic_ref_id = %d AND question_post_id = %d",
        $statistic_ref_id,
        $question_post_id
    );

    $result = $wpdb->get_var($query);
    error_log("! ! ! get_user_answer_from_statistics statistic_ref_id: $statistic_ref_id! ! !");
    error_log("! ! ! get_user_answer_from_statistics question_post_id: $question_post_id! ! !");
    error_log("! ! ! get_user_answer_from_statistics result: $result! ! !");

    if ($result) {
        $answer_data = maybe_unserialize($result);
        if (is_array($answer_data)) {
            // Multi-choice question
            $selected_choices = array_keys($answer_data, 1);
            return 'Selected choices: ' . implode(', ', array_map(function($i) { return $i + 1; }, $selected_choices));
        } elseif (is_string($answer_data) && strpos($answer_data, 'graded_id') !== false) {
            // Essay question
            $graded_id = json_decode($answer_data, true)['graded_id'];
            $essay_post = get_post($graded_id);
            return $essay_post ? $essay_post->post_content : 'Essay answer not found';
        } else {
            return $answer_data;
        }
    }

    return 'Not available';
}


// Function to check and log database information
function check_database_info() {
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

// Run the database info check when plugins are loaded
add_action('plugins_loaded', 'check_database_info');
