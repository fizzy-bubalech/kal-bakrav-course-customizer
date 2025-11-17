<?php

/**
 * Plugin Name: Course Customizer php8.1
 * Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
 * Version: 0.2.11
 * Author: AST
 */

define("COURSE_CUSTOMIZER_VERSION", "0.2.11");

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

require_once __DIR__ . "/includes/class-astcc-database-manager.php";
require_once __DIR__ . "/includes/class-astcc-utilities.php";
require_once __DIR__ . "/includes/class-astcc-quiz-handler.php";
require_once __DIR__ . "/includes/class-astcc-expression-evaluator.php";
require_once __DIR__ . "/includes/class-astcc-admin.php";
require_once __DIR__ . "/includes/class-astcc-data-visualization.php";
require_once __DIR__ . "/includes/class-astcc-shortcodes.php";
require_once __DIR__ . "/includes/class-astcc-reports.php";

class Course_Customizer
{
    private static $instance = null;
    public $database_manager;
    public $quiz_handler;
    public $report_maker;
    public $utilities;
    public $expression_evaluator;
    public $admin;
    public $data_visualization;
    public $quiz_completion_redirect_url;
    public $shortcode_manager;

    private function __construct()
    {
        $this->database_manager = new \CourseCustomizer\ASTCC_Database_Manager();
        $this->utilities = new \CourseCustomizer\ASTCC_Utilities();
        $this->expression_evaluator = new \CourseCustomizer\ASTCC_Expression_Evaluator(
            $this->database_manager
        );
        $this->quiz_handler = new \CourseCustomizer\ASTCC_Quiz_Handler(
            $this->utilities,
            $this->database_manager,
            $this->expression_evaluator
        );
        $this->admin = new \CourseCustomizer\ASTCC_Admin(
            $this->database_manager
        );
        $this->admin->init_ajax_handlers();

        $this->data_visualization = new \CourseCustomizer\ASTCC_Data_Visualization(
            $this->database_manager
        );
        $this->shortcode_manager = new \CourseCustomizer\ASTCC_Shotcodes(
            $this->database_manager
        );
        $this->shortcode_manager = new \CourseCustomizer\ASTCC_Reports(
            $this->database_manager
        );

        $this->init_hooks();
    }

    /**
     * Get the singleton instance of the class.
     *
     * @return Course_Customizer The singleton instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks()
    {

        register_activation_hook(__FILE__, [
            $this->database_manager,
            "custom_create_db_tables",
        ]);
        $priority = PHP_INT_MAX;

        add_action("admin_notices", [$this, "activation_notice"]);

        add_filter(
            "learndash_content",
            [$this->expression_evaluator, "eval_on_page_expressions"],
            $priority,
            1
        );
        add_filter(
            "elementor/frontend/the_content",
            [$this->expression_evaluator, "eval_on_page_expressions"],
            $priority,
            1
        );
        add_action("admin_menu", [$this->admin, "add_plugin_admin_menu"]);
        add_action("wp_enqueue_scripts", [
            $this,
            "enqueue_answer_checker_script",
        ]);

        add_action("wp_enqueue_scripts", [
            $this,
            "enqueue_course_page_script",
        ]);
        add_action('wp_enqueue_scripts', [$this, 'course_page_style']);
        add_action("wp_ajax_ajax_validate_quiz_answers", [
            $this,
            "ajax_validate_quiz_answers",
        ]);
        add_action("wp_ajax_nopriv_ajax_validate_quiz_answers", [
            $this,
            "ajax_validate_quiz_answers",
        ]);
        add_action("wp_ajax_save_quiz_results", [$this, "save_quiz_results"]);
        add_action("wp_ajax_nopriv_save_quiz_results", [
            $this,
            "save_quiz_results",
        ]);

        add_action("wp_ajax_get_db_variables", [$this, "get_db_variables"]);
        add_action("wp_ajax_nopriv_get_db_variables", [
            $this,
            "get_db_variables",
        ]);

        add_action("wp_ajax_get_questions_is_time", [
            $this,
            "get_questions_is_time",
        ]);
        add_action("wp_ajax_nopriv_get_questions_is_time", [
            $this,
            "get_questions_is_time",
        ]);

        add_action("wp_ajax_get_questions_exercise_properties", [
            $this,
            "get_questions_exercise_properties",
        ]);
        add_action("wp_ajax_nopriv_get_questions_exercise_properties", [
            $this,
            "get_questions_exercise_properties",
        ]);
        $this->database_manager->add_min_max_columns();
        $this->database_manager->add_exercise_type_column();
        $this->database_manager->add_exercise_description_column();
    }

    public function enqueue_answer_checker_script()
    {
        $script_handle = "answer-checker-and-save";
        $script_filename = "answer-checker-and-save.js";
        $post_type = get_post_type();
        // Create an array of our target post types
        $quiz_related_types = array(
            'sfwd-quiz',
            'sfwd-question',
            'sfwd-essays'
        );

        if (!in_array($post_type, $quiz_related_types)) return;
        static $script_enqueued = false;
        if ($script_enqueued) {
            return;
        }
        wp_enqueue_script(
            $script_handle,
            plugin_dir_url(__FILE__) . "js/{$script_filename}",
            ["jquery"],
            filemtime(plugin_dir_path(__FILE__) . "js/answer-checker-and-save.js"),
            true
        );

        wp_localize_script($script_handle, "myAjax", [
            "ajaxurl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("my_ajax_nonce"),
        ]);
        $script_enqueued = true;
    }
    public function enqueue_course_page_script()
    {
        $script_handle = "course-page-script";
        $script_filename = "course-page-script.js";
        $script_dir = "includes/js/";
        $post_type = get_post_type();
        // Create an array of our target post types
        $quiz_related_types = array(
            'sfwd-lessons',
            'sfwd-topic',
        );

        if (!in_array($post_type, $quiz_related_types)) return;
        static $script_enqueued = false;
        if ($script_enqueued) {
            return;
        }
        wp_enqueue_script(
            $script_handle,
            plugin_dir_url(__FILE__) . "{$script_dir}{$script_filename}",
            ["jquery"],
            filemtime(plugin_dir_path(__FILE__) . "{$script_dir}.{$script_filename}"),
            true
        );

        wp_localize_script($script_handle, "myAjax", [
            "ajaxurl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("my_ajax_nonce"),
        ]);
        $script_enqueued = true;
    }
    public function course_page_style()
    {
        $post_type = get_post_type();
        if ($post_type != "sfwd-courses") {
            error_log("didn't detect a course page");
            return;
        }
        static $style_enqueued = false;
        if ($style_enqueued) return;
        $style_handle = "course-style";
        $style_filename = "course-style.css";
        wp_enqueue_style($style_handle, plugins_url("includes/css/{$style_filename}", __FILE__), filemtime(plugin_dir_path(__FILE__) . "includes/css/{$style_filename}"));
        $style_enqueued = false;
    }

    public function activation_notice()
    {
        // Add a unique ID to the notice div for targeting with JavaScript
        echo '<div id="course-customizer-notice" class="notice notice-success is-dismissible"><p>Course Customizer plugin activated!</p></div>';

        // Add inline JavaScript to handle the auto-dismiss
        echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var notice = document.getElementById("course-customizer-notice");
                if (notice) {
                    // Add fade-out effect
                    notice.style.transition = "opacity 0.5s ease-in-out";
                    notice.style.opacity = "0";
                    
                    // Remove element after fade completes
                    setTimeout(function() {
                        notice.remove();
                    }, 500);
                }
            }, 5000); // 5000 milliseconds = 5 seconds
        });
    </script>';
    }

    /**
     * Validate quiz answers via AJAX.
     *
     * @return void
     */
    public function ajax_validate_quiz_answers()
    {
        if (!isset($_POST["userAnswer"]) || !isset($_POST["question_id"])) {
            wp_send_json_error(["message" => "Missing required parameters"]);
            wp_die();
        }

        $user_answer = sanitize_text_field($_POST["userAnswer"]);
        $question_id = intval($_POST["question_id"]);

        if (!$question_id) {
            wp_send_json_error(["message" => "Invalid question ID"]);
            wp_die();
        }

        $is_time = $this->quiz_handler->is_time_question_from_question_id(
            $question_id
        );
        $is_valid = $this->utilities->validate_quiz_answers(
            $user_answer,
            $is_time
        );

        wp_send_json_success(["is_valid" => $is_valid]);
        wp_die();
    }

    /**
     * Save quiz results via AJAX.
     *
     * @return void
     */
    public function save_quiz_results()
    {
        if (headers_sent()) {
            wp_send_json_error("Headers already sent");
            return;
        }

        if (!isset($_POST["quizData"])) {
            wp_send_json_error("No quiz data received");
            return;
        }

        $quiz_data = json_decode(stripslashes($_POST["quizData"]), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error("Invalid JSON data received");
            return;
        }

        $results = [];
        $user_id = get_current_user_id();

        foreach ($quiz_data as $questionId => $user_answer) {
            $exercise = $this->quiz_handler->exercise_from_question_id(
                $questionId
            );
            if ($exercise === null) {
                wp_send_json_error("Invalid question ID: " . $questionId);
                return;
            }

            $exercise_id = $exercise["exercise_id"];
            $current_time = $this->utilities->current_date_time();
            $is_time = $exercise["is_time"];

            $result_value = $is_time
                ? $this->utilities->time_to_seconds($user_answer)
                : intval($user_answer);

            $results[] = [
                "user_id" => $user_id,
                "exercise_id" => $exercise_id,
                "result" => $result_value,
                "is_metric" => 1,
                "result_date" => $current_time,
            ];
        }

        $insert_result = $this->database_manager->insert_results_into_wp_results_table(
            $results
        );

        if ($insert_result === false) {
            wp_send_json_error("Error saving quiz results");
        } else {
            // Get the stored redirect URL for this user
            $redirect_url = get_option('quiz_completion_redirect_' . $user_id);

            // Clean up the stored URL after retrieving it
            delete_option('quiz_completion_redirect_' . $user_id);

            wp_send_json_success([
                "message" => "Quiz results saved successfully",
                "redirect_url" => $redirect_url
            ]);
        }
        wp_die();
    }

    /**
     * Get database variables via AJAX.
     *
     * @return void
     */
    public function get_db_variables(): void
    {
        check_ajax_referer("my_ajax_nonce", "nonce");

        $exercises = $this->database_manager->get_all_exercises();
        $db_variables = [];

        foreach ($exercises as $exercise) {
            $db_variables[$exercise["exercise_name"]] = [
                "exercise_id" => $exercise["exercise_id"],
                "is_time" => $exercise["is_time"],
                "min" => $exercise["min"],
                "max" => $exercise["max"],
                "exercise_type" => $exercise["exercise_type"]
            ];
        }

        wp_send_json_success($db_variables);
    }

    /**
     * Get questions is_time property via AJAX.
     *
     * @return void
     */
    public function get_questions_is_time(): void
    {
        check_ajax_referer("my_ajax_nonce", "nonce");

        if (!isset($_POST["quiz_id"])) {
            wp_send_json_error("The quiz ID is missing or null");
            return;
        }

        $quiz_id = json_decode(json: stripslashes(string: $_POST["quiz_id"]), associative: true);

        $questions_ids = $this->database_manager->get_questions_ids_from_quiz_id(
            $quiz_id
        );

        foreach ($questions_ids as $question_id) {
            $is_time = $this->quiz_handler->is_time_question_from_question_id(
                $question_id
            );
            $questions_ids[$question_id] = [
                "is_time" => $is_time,
            ];
        }

        wp_send_json_success($questions_ids);
    }

    /**
     * Get questions exercise properties from the db, is_time, min, and max via AJAX.
     *
     * @return void
     */
    public function get_questions_exercise_properties(): void
    {
        check_ajax_referer("my_ajax_nonce", "nonce");

        if (!isset($_POST["quiz_id"])) {
            wp_send_json_error("The quiz ID is missing or null");
            return;
        }

        $quiz_id = json_decode(json: stripslashes(string: $_POST["quiz_id"]));

        $questions_ids = $this->database_manager->get_questions_ids_from_quiz_id(
            $quiz_id
        );

        foreach ($questions_ids as $question_id) {
            $question_exercise = $this->quiz_handler->exercise_from_question_id(
                $question_id
            );
            $questions_ids[$question_id] = [
                "is_time" => $question_exercise["is_time"],
                "min" => $question_exercise["min"],
                "max" => $question_exercise["max"],
                "exercise_type" => $question_exercise["exercise_type"],
            ];
        }

        wp_send_json_success($questions_ids);
    }
}

/**
 * Initialize the Course_Customizer plugin.
 *
 * @return Course_Customizer The singleton instance of the Course_Customizer class.
 */
function course_customizer(): Course_Customizer
{
    return Course_Customizer::get_instance();
}

course_customizer();
