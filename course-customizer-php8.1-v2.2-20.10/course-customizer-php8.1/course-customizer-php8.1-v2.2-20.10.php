<?php

/**
 * Plugin Name: Course Customizer php8.1
 * Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
 * Version: 0.2.2
 * Author: AST
 */

define("COURSE_CUSTOMIZER_VERSION", "0.2.2");

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

require_once __DIR__ . "/includes/class-astcc-database-manager.php";
require_once __DIR__ . "/includes/class-astcc-utilities.php";
require_once __DIR__ . "/includes/class-astcc-quiz-handler.php";
require_once __DIR__ . "/includes/class-astcc-expression-evaluator.php";
require_once __DIR__ . "/includes/class-astcc-admin.php";
require_once __DIR__ . "/includes/class-astcc-data-visualization.php";

class Course_Customizer
{
    private static $instance = null;
    public $database_manager;
    public $quiz_handler;
    public $utilities;
    public $expression_evaluator;
    public $admin;
    public $data_visualization;

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
        add_shortcode("url_redirect", [$this, "url_redirect_shortcode"]);
    }

    function url_redirect_shortcode($atts)
    {
        // Define default attributes
        $defaults = array(
            'url' => '#', // Default URL if none provided
        );

        // Parse and merge the attributes
        $atts = shortcode_atts($defaults, $atts, 'url_redirect');

        // Sanitize the URL
        $url = esc_url($atts['url']);

        // Generate a unique ID for this instance of the shortcode
        $unique_id = 'url_redirect_' . uniqid();

        // Generate the HTML and JavaScript
        $output = '<div id="' . $unique_id . '" style="min-height: 1px;"></div>';
        $output .= '<script type="text/javascript">';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        $output .= '  var element = document.getElementById("' . $unique_id . '");';
        $output .= '  var observer = new IntersectionObserver(function(entries) {';
        $output .= '    if (entries[0].isIntersecting) {';
        $output .= '      window.location.href = "' . $url . '";';
        $output .= '    }';
        $output .= '  }, { threshold: [0.1] });';
        $output .= '  observer.observe(element);';
        $output .= '});';
        $output .= '</script>';

        // Fallback for users with JavaScript disabled
        $output .= '<noscript>';
        $output .= '<p>Please click <a href="' . $url . '">here</a> to be redirected.</p>';
        $output .= '</noscript>';

        return $output;
    }

    public function enqueue_answer_checker_script()
    {
        $script_handle = "answer-checker-and-save";
        $script_filename = "answer-checker-and-save.js";

        wp_enqueue_script(
            $script_handle,
            plugin_dir_url(__FILE__) . "js/{$script_filename}",
            ["jquery"],
            time(),
            true
        );

        wp_localize_script($script_handle, "myAjax", [
            "ajaxurl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("my_ajax_nonce"),
        ]);
    }

    public function activation_notice()
    {
        echo '<div class="notice notice-success"><p>Course Customizer plugin activated!</p></div>';
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
            wp_send_json_success("Quiz results saved successfully");
        }

        wp_die();
    }

    /**
     * Get database variables via AJAX.
     *
     * @return void
     */
    public function get_db_variables()
    {
        check_ajax_referer("my_ajax_nonce", "nonce");

        $exercises = $this->database_manager->get_all_exercises();
        $db_variables = [];

        foreach ($exercises as $exercise) {
            $db_variables[$exercise["exercise_name"]] = [
                "exercise_id" => $exercise["exercise_id"],
                "is_time" => $exercise["is_time"],
            ];
        }

        wp_send_json_success($db_variables);
    }

    /**
     * Get questions is_time property via AJAX.
     *
     * @return void
     */
    public function get_questions_is_time()
    {
        check_ajax_referer("my_ajax_nonce", "nonce");

        if (!isset($_POST["quiz_id"])) {
            wp_send_json_error("The quiz ID is missing or null");
            return;
        }

        $quiz_id = json_decode(stripslashes($_POST["quiz_id"]), true);

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
}

/**
 * Initialize the Course_Customizer plugin.
 *
 * @return Course_Customizer The singleton instance of the Course_Customizer class.
 */
function course_customizer()
{
    return Course_Customizer::get_instance();
}

course_customizer();
