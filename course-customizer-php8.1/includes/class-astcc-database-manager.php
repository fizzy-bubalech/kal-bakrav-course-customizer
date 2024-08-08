<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class ASTCC_Database_Manager
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // Function to create custom database tables
    public function custom_create_db_tables()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        // SQL query to create the exercises table
        $sql_exercises = "
        CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}exercises (
            exercise_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            exercise_name VARCHAR(255) NOT NULL,
            is_time TINYINT(1) NOT NULL,
            PRIMARY KEY (exercise_id)
        ) $charset_collate;";

        // SQL query to create the results table
        $sql_results = "
        CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}results (
            result_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            exercise_id BIGINT(20) NOT NULL,
            result INT(11) NOT NULL,
            result_date DATETIME NOT NULL,
            is_metric TINYINT(1) NOT NULL,
            PRIMARY KEY (result_id),
            CONSTRAINT {$this->wpdb->prefix}fk_user FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID) ON DELETE CASCADE,
            CONSTRAINT {$this->wpdb->prefix}fk_exercise FOREIGN KEY (exercise_id) REFERENCES {$this->wpdb->prefix}exercises(exercise_id) ON DELETE CASCADE
        ) $charset_collate;";

        // Execute the SQL queries to create tables
        include_once ABSPATH . "wp-admin/includes/upgrade.php";
        $exercises_result = dbDelta($sql_exercises);
        $results_result = dbDelta($sql_results);

        // Log the results of table creation
        error_log(
            "Exercises table creation result: " .
                print_r($exercises_result, true)
        );
        error_log(
            "Results table creation result: " . print_r($results_result, true)
        );

        // Check for any database errors
        if (!empty($this->wpdb->last_error)) {
            error_log("Database Error: " . $this->wpdb->last_error);
        }
    }

    // Function to check and log database information
    public function check_database_info()
    {
        // Get and log the MariaDB version
        $version = $this->wpdb->get_var("SELECT VERSION()");
        error_log("MariaDB Version: " . $version);

        // Check for InnoDB support
        $engines = $this->wpdb->get_results("SHOW ENGINES", ARRAY_A);
        $innodb_support = "No";
        foreach ($engines as $engine) {
            if (
                $engine["Engine"] === "InnoDB" &&
                ($engine["Support"] === "YES" ||
                    $engine["Support"] === "DEFAULT")
            ) {
                $innodb_support = "Yes";
                break;
            }
        }
        error_log("InnoDB Support: " . $innodb_support);
    }

    public function get_exercise_by_id(int $exercise_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}exercises WHERE exercise_id = %d",
            $exercise_id
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }
    public function get_exercise_by_name(string $exercise_name)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}exercises WHERE exercise_name = %s",
            $exercise_name
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    public function get_post(int $post_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}posts WHERE ID = %d",
            $post_id
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    public function insert_results_into_wp_results_table($results)
    {
        $table_name = $this->wpdb->prefix . "results";

        foreach ($results as $item) {
            $this->wpdb->insert(
                $table_name,
                [
                    "user_id" => $item["user_id"],
                    "exercise_id" => $item["exercise_id"],
                    "result" => $item["result"],
                    "result_date" => $item["result_date"],
                    "is_metric" => $item["is_metric"],
                ],
                [
                    "%d", // user_id
                    "%d", // exercise_id
                    "%d", // result
                    "%s", // result_date
                    "%d", // is_metric
                ]
            );

            if ($this->wpdb->last_error) {
                // Handle error
                error_log("Database insert error: " . $this->wpdb->last_error);
            }
        }
    }
    public function update_quiz_statistic_answer_data_invalid(
        $statistic_ref_id,
        $question_post_id
    ) {
        $table_name = $this->wpdb->prefix . "learndash_pro_quiz_statistic";

        $updated = $this->wpdb->update(
            $table_name,
            ["answer_data" => "invalid"], // Data to update
            [
                "statistic_ref_id" => $statistic_ref_id,
                "question_post_id" => $question_post_id,
            ], // Where clause
            ["%s"], // Data format for 'answer_data'
            ["%d", "%d"] // Data format for where clause
        );

        if ($updated === false) {
            // Error handling
            error_log(
                "Failed to update answer_data: " . $this->wpdb->last_error
            );
        }
    }
    public function add_exercise($exercise_name, $is_time)
    {
        $table_name = $this->wpdb->prefix . "exercises";

        $this->wpdb->insert(
            $table_name,
            [
                "exercise_name" => $exercise_name,
                "is_time" => $is_time,
            ],
            ["%s", "%d"]
        );

        if ($this->wpdb->last_error) {
            error_log("Database insert error: " . $this->wpdb->last_error);
        }
    }

    public function get_all_exercises()
    {
        $table_name = $this->wpdb->prefix . "exercises";
        return $this->wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    public function get_all_results()
    {
        $table_name = $this->wpdb->prefix . "results";
        return $this->wpdb->get_results(
            "
            SELECT r.*, e.exercise_name, u.display_name as user_name
            FROM $table_name r
            JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
            JOIN {$this->wpdb->prefix}users u ON r.user_id = u.ID
            ORDER BY r.result_date DESC
        ",
            ARRAY_A
        );
    }
    public function get_latest_result_by_exercise_id(int $exercise_id)
    {
        $current_user_id = get_current_user_id();

        $query = $this->wpdb->prepare(
            "SELECT result
            FROM {$this->wpdb->prefix}results
            WHERE user_id = %d
            AND exercise_id = %d
            ORDER BY result_date DESC",
            $current_user_id,
            $exercise_id
        );
        $result = $this->wpdb->get_var($query);
        return $result;
    }

    public function delete_exercise($exercise_id)
    {
        $table_name = $this->wpdb->prefix . "exercises";

        $this->wpdb->delete(
            $table_name,
            ["exercise_id" => $exercise_id],
            ["%d"]
        );

        if ($this->wpdb->last_error) {
            error_log("Database delete error: " . $this->wpdb->last_error);
            return false;
        }
        return true;
    }

    public function add_result(
        $user_id,
        $exercise_id,
        $result,
        $result_date,
        $is_metric
    ) {
        $table_name = $this->wpdb->prefix . "results";

        $this->wpdb->insert(
            $table_name,
            [
                "user_id" => $user_id,
                "exercise_id" => $exercise_id,
                "result" => $result,
                "result_date" => $result_date,
                "is_metric" => $is_metric,
            ],
            ["%d", "%d", "%d", "%s", "%d"]
        );

        if ($this->wpdb->last_error) {
            error_log("Database insert error: " . $this->wpdb->last_error);
            return false;
        }
        return true;
    }

    public function delete_result($result_id)
    {
        $table_name = $this->wpdb->prefix . "results";

        $this->wpdb->delete($table_name, ["result_id" => $result_id], ["%d"]);

        if ($this->wpdb->last_error) {
            error_log("Database delete error: " . $this->wpdb->last_error);
            return false;
        }
        return true;
    }

    public function get_all_users()
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT ID, display_name FROM {$this->wpdb->prefix}users",
                ARRAY_A
            )
        );
    }
    public function check_for_invalid_answers($statistic_ref_id)
    {
        $table_name = $this->wpdb->prefix . "learndash_pro_quiz_statistic";

        // Check for invalid answers
        $invalid_answers = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE statistic_ref_id = %d AND answer_data = 'invalid'",
                $statistic_ref_id
            )
        );
        error_log(
            "Checking quiz with statistic_ref_id: $statistic_ref_id. Invalid answers found: $invalid_answers"
        );
        return $invalid_answers;
    }
    public function remove_invalid_quiz_answers($statistic_ref_id)
    {
        $table_name = $this->wpdb->prefix . "learndash_pro_quiz_statistic";

        error_log("Removing bad quiz answer entries from DATABASE");
        $this->wpdb->delete(
            $table_name,
            ["statistic_ref_id" => $statistic_ref_id],
            ["%d"]
        );
        $this->wpdb->delete(
            $this->wpdb->prefix . "learndash_pro_quiz_statistic_ref",
            ["statistic_ref_id" => $statistic_ref_id],
            ["%d"]
        );
    }
}
