<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

enum SQLtypes: string
{

    case CHAR = "CHAR";
    case VARCHAR = "VARCHAR";
    case BINARY = "BINARY";
    case VARBINARY = "VARBINARY";
    case TEXT = "TEXT";
    case BLOB = "BLOB";
    case BIT = "BIT";
    case TINYINT = "TINYINT";
    case INT = "INT";
}
enum ExerciseTypes: string {
    case TIME = "TIME";
    case COUNT = "COUNT";
    case TEXT = "TEXT";
}

class ASTCC_Database_Manager
{
    /** @var \wpdb */
    private $wpdb;

    /**
     * Constructor for ASTCC_Database_Manager.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Function to create custom database tables.
     *
     * @return void
     */
    public function custom_create_db_tables(): void
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
            result TEXT NOT NULL,
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

        // Check for any database errors
        if (!empty($this->wpdb->last_error)) {
        }
    }


    public function update_exercise(string $exercise_name = null, bool $is_time = null, int $min = null, int $max = null, int $exercise_id)
    {
        $exercise = $this->get_exercise_by_id($exercise_id);
        $data = [
            'exercise_name' => $exercise_name ?? $exercise["exercise_name"],
            'is_time' => $is_time ?? $exercise["is_time"],
            'min' => $min ?? $exercise["min"],
            'max' => $max ?? $exercise["max"],
        ];
        $where = [
            "exercise_id" => $exercise_id,
        ];
        $data_format = [
            "%s",
            "%d",
            "%d",
            "%d",
        ];
        $where_format = [
            "%d"
        ];

        $this->update_custom_table_entry("exercises", $data, $where, $data_format, $where_format);
    }

    public function update_exercise_min(int $min = null, int $exercise_id)
    {
        $exercise = $this->get_exercise_by_id($exercise_id);
        $data = [
            'min' => $min ?? $exercise["min"],
        ];
        $where = [
            "exercise_id" => $exercise_id,
        ];
        $data_format = [
            "%d",
        ];
        $where_format = [
            "%d"
        ];

        $this->update_custom_table_entry("exercises", $data, $where, $data_format, $where_format);
    }

    function update_custom_table_entry($table_name, $data, $where, $data_format = null, $where_format = null)
    {
        $wpdb = $this->wpdb;

        // Ensure table name has the WordPress prefix
        if (strpos($table_name, $wpdb->prefix) !== 0) {
            $table_name = $wpdb->prefix . $table_name;
        }

        // Validate table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return new WP_Error('invalid_table', "Table $table_name does not exist.");
        }

        // Validate data is not empty
        if (empty($data) || !is_array($data)) {
            return new WP_Error('invalid_data', 'Update data must be provided as an array.');
        }

        // Validate where clause is not empty
        if (empty($where) || !is_array($where)) {
            return new WP_Error('invalid_where', 'Where conditions must be provided as an array.');
        }

        try {
            // Perform the update
            $result = $wpdb->update(
                $table_name,
                $data,
                $where,
                $data_format,
                $where_format
            );

            if ($result === false) {
                return new WP_Error('update_failed', 'Database update failed: ' . $wpdb->last_error);
            }

            return $result;
        } catch (Exception $e) {
            return new WP_Error('update_error', $e->getMessage());
        }
    }



    public function add_column(string $table_name, string $column_name, SQLtypes $type, int $size = 1, mixed $default = "NULL")
    {
      /*@ Add column if it does not exist and also populate it in all existing rows in the table with the default provided. */
        $dbname = $this->wpdb->dbname;

        $marks_table_name = $this->wpdb->prefix . $table_name;


        $is_col = $this->wpdb->get_results("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `table_name` = '{$marks_table_name}' AND `TABLE_SCHEMA` = '{$dbname}' AND `COLUMN_NAME` = '{$column_name}'");
        $type_size = "{$type->value}" . "({$size})";
        $no_size_types = ['TEXT', 'DATE', 'DATETIME', 'TIMESTAMP', 'BOOLEAN', 'TINYINT'];
        if (empty($is_col)) {
          
            if ($default instanceof \BackedEnum) {
                $default_value = $default->value;
            } else {
                $default_value = $default;
            }

            // 2. Format the value for the SQL query
            $default_sql_value = 'NULL'; // Default to the NULL keyword

            if (is_string($default_value) && strtoupper($default_value) !== 'NULL') {
                // It's a string (like 'COUNT'), so quote it safely
                $default_sql_value = $this->wpdb->prepare('%s', $default_value);
            } elseif (is_numeric($default_value)) {
                // It's a number (like 1), so use it directly
                $default_sql_value = $default_value;
            }
            // If it was the string 'NULL', it remains 'NULL' from the initial assignment.

            // 3. Build the query with the correctly formatted default value
            $add_column = "ALTER TABLE `{$marks_table_name}` ADD `{$column_name}` {$type_size} NULL DEFAULT {$default_sql_value};";

            $this->wpdb->query($add_column);

            if($column_name == "exercise_type"){
              $this->populate_execersie_type_column();
              error_log("ADDING EXERCISE TYPE COLUMN AND POPULATING IT");
            }
        }
    }


    public function change_result_datatype_in_result_table(){
      $table_name = $this->wpdb->prefix . 'results';
      $column_name = "result";
      $sql = "ALTER TABLE $table_name MODIFY COLUMN $column_name TEXT";
      $result = $this->wpdb->query( $sql );
      if ( $result === false ) {
          error_log( "SQL Error: " . $wpdb->last_error );
      }
    }
    public function add_min_max_columns(): void
    {
        /*@ Add min and max columns if not exist */
        $this->add_column("exercises", "min", SQLtypes::INT, 5, 1);
        $this->add_column("exercises", "max", SQLtypes::INT, 5, 86400);
    }

    public function add_exercise_type_column(): void
    {
        /*@ Add Exercise Types column if it does not exist */
      $this->add_column("exercises", "exercise_type", SQLtypes::VARCHAR, 10, ExerciseTypes::COUNT);
      $this->change_result_datatype_in_result_table();

    }
    public function add_exercise_description_column(): void
    {
      /*@ Add Exercise Description column if it does not exist*/
      $this->add_column("exercises", "exercise_description", SQLtypes::VARCHAR, 300, "");
    }
    public function populate_execersie_type_column(): void
    {
        /* @ Populate the exercise type column based on the is time-based column */
        
        // Get the full table name
        $full_table_name = $this->wpdb->prefix . "exercises";

        // Get the string values from the enum
        $time_type = ExerciseTypes::TIME->value;   // "TIME"
        $count_type = ExerciseTypes::COUNT->value; // "COUNT"

        // Prepare the SQL query
        // This query updates all rows at once using a CASE statement.
        // If is_time is 1 (true), set exercise_type to "TIME".
        // Otherwise (if is_time is 0 or NULL), set it to "COUNT".
        $sql = $this->wpdb->prepare(
            "UPDATE %i SET exercise_type = CASE WHEN is_time = 1 THEN %s ELSE %s END",
            $full_table_name,
            $time_type,
            $count_type
        );

        // Execute the query
        $this->wpdb->query($sql);
    }

    /**
     * Function to check and log database information.
     *
     * @return void
     */
    public function check_database_info(): void
    {
        // Get and log the MariaDB version
        $version = $this->wpdb->get_var("SELECT VERSION()");
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
    }

    /**
     * Get exercise by ID.
     *
     * @param int $exercise_id The exercise ID.
     * @return array|null The exercise data or null if not found.
     */
    public function get_exercise_by_id(int $exercise_id): ?array
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}exercises WHERE exercise_id = %d",
            $exercise_id
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Get an array of exercises by ID.
     *
     * @param array $exercise_ids The exercises' IDs.
     * @return array|null The exercise data or null if not found.
     */
    public function get_exercises_by_id(array $exercise_ids): ?array
    {
        if(empty($exercise_ids)) return [];
        $integer_ids = array_map('intval', $exercise_ids);
        $placeholders = implode(', ', array_fill(0, count($integer_ids), '%d'));
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}exercises WHERE exercise_id IN ($placeholders)",
            $integer_ids
        );
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    /**
     * Get exercise by name.
     *
     * @param string $exercise_name The exercise name.
     * @return array|null The exercise data or null if not found.
     */
    public function get_exercise_by_name(string $exercise_name): ?array
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}exercises WHERE exercise_name = %s",
            $exercise_name
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Get post by ID.
     *
     * @param int $post_id The post ID.
     * @return array|null The post data or null if not found.
     */
    public function get_post(int $post_id): ?array
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}posts WHERE ID = %d",
            $post_id
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Insert results into wp_results table.
     *
     * @param array $results The results to insert.
     * @return bool True if all inserts were successful, false otherwise.
     */
    public function insert_results_into_wp_results_table(array $results): bool
    {
        $table_name = $this->wpdb->prefix . "results";
        $success = true;

        foreach ($results as $item) {
            $inserted = $this->add_result(
                $item["user_id"],
                $item["exercise_id"],
                $item["result"],
                $item["result_date"],
                $item["is_metric"]
            );

            if (!$inserted) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if user has completed a specific quiz
     *
     * @param int $user_id The ID of the user to check
     * @param int $quiz_id The ID of the quiz to check
     * @return bool True if completed, false otherwise
     */
    function check_quiz_completion($user_id = null, $quiz_id = null)
    {
        // If no user_id provided, get current user
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        // If no quiz_id or user_id, return false
        if (empty($quiz_id) || empty($user_id)) {
            return false;
        }

        // Check if the quiz exists
        $quiz = get_post($quiz_id);
        if (!$quiz || 'sfwd-quiz' !== $quiz->post_type) {
            return false;
        }

        // Get the latest quiz attempt data
        $quiz_progress = get_user_meta($user_id, '_sfwd-quizzes', true);

        if (!empty($quiz_progress) && is_array($quiz_progress)) {
            foreach ($quiz_progress as $attempt) {
                if (
                    isset($attempt['quiz']) &&
                    $attempt['quiz'] == $quiz_id &&
                    isset($attempt['pass']) &&
                    $attempt['pass'] == 1
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Update quiz statistic answer data to invalid.
     *
     * @param int $statistic_ref_id The statistic reference ID.
     * @param int $question_post_id The question post ID.
     * @return void
     */
    public function update_quiz_statistic_answer_data_invalid(
        int $statistic_ref_id,
        int $question_post_id
    ): void {
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

    /**
     * Add a new exercise.
     *
     * @param string $exercise_name The name of the exercise.
     * @param bool $is_time Whether the exercise is time-based.
     * @return void
     */
    public function add_exercise(string $exercise_name, bool $is_time, int $min, int $max, string $exercise_type, string $exercise_description): void
    {
        $table_name = $this->wpdb->prefix . "exercises"; 
        $this->wpdb->insert(
            $table_name,
            [
                "exercise_name" => $exercise_name,
                "is_time" => $is_time,
                "min" => $min,
                "max" => $max,
                "exercise_type" => $exercise_type,
                "exercise_description" => $exercise_description,
            ],
            ["%s", "%d"]
        );

        if ($this->wpdb->last_error) {
        }
    }

    /**
     * Get question from ID.
     *
     * @param int $question_id The question ID.
     * @return array|null The question data or null if not found.
     */
    public function get_question_from_id(int $question_id): ?array
    {
        $question = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}learndash_pro_quiz_question WHERE id = %d",
                $question_id
            ),
            ARRAY_A
        );
        return $question;
    }

    /**
     * Get all exercises.
     *
     * @return array An array of all exercises.
     */
    public function get_all_exercises(): array
    {
        $table_name = $this->wpdb->prefix . "exercises";
        return $this->wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    /**
     * Get all results.
     *
     * @return array An array of all results.
     */
    public function get_all_results(): array
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

    /**
     * Get latest result by exercise ID.
     *
     * @param int $exercise_id The exercise ID.
     * @return string|null The latest result or null if not found.
     */
    public function get_latest_result_by_exercise_id(int $exercise_id): ?string
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

    /**
     * Delete an exercise.
     *
     * @param int $exercise_id The exercise ID to delete.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function delete_exercise(int $exercise_id)
    {
        $table_name = $this->wpdb->prefix . "exercises";

        $this->wpdb->delete(
            $table_name,
            ["exercise_id" => $exercise_id],
            ["%d"]
        );

        if ($this->wpdb->last_error) {
            error_log("Could not delete exercise: " . $this->wpdb->last_error);
            return false;
        }
        error_log("Deleted Exercise: " . $exercise_id);
        return true;
    }

    /**
     * Add a new result.
     *
     * @param int $user_id The user ID.
     * @param int $exercise_id The exercise ID.
     * @param int $result The result value.
     * @param string $result_date The result date.
     * @param bool $is_metric Whether the result is metric.
     * @return bool True if added successfully, false otherwise.
     */
    public function add_result(
        int $user_id,
        int $exercise_id,
        mixed $result,
        string $result_date,
        bool $is_metric
    ): bool {
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
            ["%d", "%d", "%s", "%s", "%d"]
        );

        if ($this->wpdb->last_error) {
            return false;
        }
        return true;
    }

    /**
     * Delete a result.
     *
     * @param int $result_id The result ID to delete.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function delete_result(int $result_id): bool
    {
        $table_name = $this->wpdb->prefix . "results";

        $this->wpdb->delete($table_name, ["result_id" => $result_id], ["%d"]);

        if ($this->wpdb->last_error) {
            return false;
        }
        return true;
    }

    /**
     * Get all users.
     *
     * @return array An array of all users.
     */
    public function get_all_users(): array
    {
        return $this->wpdb->get_results(
            "SELECT ID, display_name FROM {$this->wpdb->prefix}users",
            ARRAY_A
        );
    }

    /**
     * Check for invalid answers.
     *
     * @param int $statistic_ref_id The statistic reference ID.
     * @return int The number of invalid answers.
     */
    public function check_for_invalid_answers(int $statistic_ref_id): int
    {
        $table_name = $this->wpdb->prefix . "learndash_pro_quiz_statistic";

        // Check for invalid answers
        $invalid_answers = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE statistic_ref_id = %d AND answer_data = 'invalid'",
                $statistic_ref_id
            )
        );

        return $invalid_answers;
    }

    /**
     * Remove invalid quiz answers.
     *
     * @param int $statistic_ref_id The statistic reference ID.
     * @return void
     */
    public function remove_invalid_quiz_answers(int $statistic_ref_id): void
    {
        $table_name = $this->wpdb->prefix . "learndash_pro_quiz_statistic";

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

    /**
     * Get questions from quiz ID.
     *
     * @param int $quiz_id The quiz ID.
     * @return array An array of questions.
     */
    public function get_questions_from_quiz_id(int $quiz_id): array
    {
        $questions = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}learndash_pro_quiz_question WHERE quiz_id = %d",
                $quiz_id
            ),
            ARRAY_A
        );
        return $questions;
    }

    /**
     * Get question IDs from quiz ID.
     *
     * @param int $quiz_id The quiz ID.
     * @return array An array of question IDs.
     */
    public function get_questions_ids_from_quiz_id(int $quiz_id): array
    {
      $question_ids = learndash_get_quiz_questions($quiz_id);
        
      error_log("Questions in db class = [". join(", ", $question_ids)."]");

        return $question_ids;
    }

    /**
     * Get filtered results.
     *
     * @param int|null $user_id The user ID.
     * @param int|null $exercise_id The exercise ID.
     * @param string $search The search term.
     * @param string $order_by The column to order by.
     * @param string $order The order direction.
     * @return array An array of filtered results.
     */
    public function get_filtered_results(
        ?int $user_id = null,
        ?int $exercise_id = null,
        string $search = "",
        string $order_by = "result_date",
        string $order = "DESC"
    ): array {
        $where_clauses = [];
        $where_values = [];

        if ($user_id) {
            $where_clauses[] = "r.user_id = %d";
            $where_values[] = $user_id;
        }

        if ($exercise_id) {
            $where_clauses[] = "r.exercise_id = %d";
            $where_values[] = $exercise_id;
        }

        if ($search) {
            $where_clauses[] =
                "(u.display_name LIKE %s OR e.exercise_name LIKE %s)";
            $where_values[] = "%{$search}%";
            $where_values[] = "%{$search}%";
        }

        $where_sql = $where_clauses
            ? "WHERE " . implode(" AND ", $where_clauses)
            : "";

        // Sanitize order_by and order
        $allowed_columns = [
            "result_id",
            "user_name",
            "exercise_name",
            "result",
            "result_date",
            "is_metric",
        ];
        $order_by = in_array($order_by, $allowed_columns)
            ? $order_by
            : "result_date";
        $order = strtoupper($order) === "ASC" ? "ASC" : "DESC";

        $query = "SELECT r.*, e.exercise_name, e.exercise_type, u.display_name as user_name
                      FROM {$this->wpdb->prefix}results r
                      JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
                      JOIN {$this->wpdb->prefix}users u ON r.user_id = u.ID
                      $where_sql
                      ORDER BY $order_by $order";

        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        return $this->wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get exercises results.
     *
     * @param array $exercises An array of exercise names.
     * @param int|null $user_id The user ID.
     * @return array An array of exercise results.
     */
    public function get_exercises_results(
        array $exercises,
        ?int $user_id = null
    ): array {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $results = [];

        foreach ($exercises as $exercise_name) {
            $query = $this->wpdb->prepare(
                "SELECT r.result, r.result_date, e.exercise_type
                    FROM {$this->wpdb->prefix}results r
                    JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
                    WHERE r.user_id = %d AND e.exercise_name = %s
                    ORDER BY r.result_date DESC",
                $user_id,
                $exercise_name
            );
            $exercise_results = $this->wpdb->get_results($query, ARRAY_A);
            $results[$exercise_name] = $exercise_results;
        }

        return $results;
    }

    public function get_exercises_results_by_id(array $exercise_ids): array {
        
        if (empty($exercise_ids)) {
            return [];
        }

        $integer_ids = array_map('intval', $exercise_ids);

        $placeholders = implode(', ', array_fill(0, count($integer_ids), '%d'));

        // We select the exercise_id so we can group the results in PHP.
        $query = $this->wpdb->prepare(
            "SELECT r.exercise_id, r.result, r.result_date, e.exercise_type, r.user_id
             FROM {$this->wpdb->prefix}results r
             JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
             WHERE e.exercise_id IN ($placeholders)
             ORDER BY r.exercise_id, r.result_date DESC",
            $integer_ids
        );

        $all_results = $this->wpdb->get_results($query, ARRAY_A);

        // This ensures you get a key for every ID, even if it has no results.
        $results = array_fill_keys($integer_ids, []);

        // 7. Group the flat results by their exercise_id
        if ($all_results) {
            foreach ($all_results as $row) {
                $id = (int) $row['exercise_id'];
                // We don't need to include the exercise_id in the sub-array
                unset($row['exercise_id']); 
                $results[$id][] = $row;
            }
        }

        return $results;
    }
    public function update_results_to_realistic_values(): void
    {
        $table_name = $this->wpdb->prefix . "results";
        $exercises = $this->get_all_exercises();

        foreach ($exercises as $exercise) {
            $exercise_id = $exercise["exercise_id"];
            $is_time = $exercise["is_time"];

            // Get all results for this exercise
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM $table_name WHERE exercise_id = %d",
                    $exercise_id
                ),
                ARRAY_A
            );

            foreach ($results as $result) {
                $new_result = $this->generate_realistic_result($is_time);

                // Update the result in the database
                $this->wpdb->update(
                    $table_name,
                    ["result" => $new_result],
                    ["result_id" => $result["result_id"]],
                    ["%d"],
                    ["%d"]
                );
            }
        }
    }

    public function get_filtered_user_results(
        int $user_id,
        ?int $exercise_id = null,
        string $search = "",
        string $order_by = "result_date",
        string $order = "DESC",
        int $page = 1,
        int $per_page = 10
    ): array {
        $where_clauses = ["r.user_id = %d"];
        $where_values = [$user_id];

        if ($exercise_id) {
            $where_clauses[] = "r.exercise_id = %d";
            $where_values[] = $exercise_id;
        }

        if ($search) {
            $where_clauses[] = "(e.exercise_name LIKE %s OR r.result LIKE %s)";
            $where_values[] = "%{$search}%";
            $where_values[] = "%{$search}%";
        }

        $where_sql = "WHERE " . implode(" AND ", $where_clauses);

        // Sanitize order_by and order
        $allowed_columns = [
            "exercise_name",
            "result",
            "result_date",
            "is_metric",
        ];
        $order_by = in_array($order_by, $allowed_columns)
            ? $order_by
            : "result_date";
        $order = strtoupper($order) === "ASC" ? "ASC" : "DESC";

        // Calculate offset
        $offset = ($page - 1) * $per_page;

        // Get total count
        $count_query = "SELECT COUNT(*)
                            FROM {$this->wpdb->prefix}results r
                            JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
                            $where_sql";
        $total_count = $this->wpdb->get_var(
            $this->wpdb->prepare($count_query, $where_values)
        );

        // Get paginated results
        $query = "SELECT r.*, e.exercise_name, e.is_time
                      FROM {$this->wpdb->prefix}results r
                      JOIN {$this->wpdb->prefix}exercises e ON r.exercise_id = e.exercise_id
                      $where_sql
                      ORDER BY $order_by $order
                      LIMIT %d OFFSET %d";

        $where_values[] = $per_page;
        $where_values[] = $offset;

        $prepared_query = $this->wpdb->prepare($query, $where_values);
        $results = $this->wpdb->get_results($prepared_query, ARRAY_A);

        return [
            "results" => $results,
            "total_count" => $total_count,
        ];
    }
}
