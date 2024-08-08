<?php
/*
Plugin Name: Course Customizer
Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
Version: 0.1.4
Author: AST
*/
define("COURSE_CUSTOMIZER_VERSION", "0.1.4");

// Display an admin notice when the plugin is activated

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

// Include class files
//require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . "/includes/class-astcc-database-manager.php";
require_once __DIR__ . "/includes/class-astcc-utilities.php";
require_once __DIR__ . "/includes/class-astcc-quiz-handler.php";
require_once __DIR__ . "/includes/class-astcc-expression-evaluator.php";
require_once __DIR__ . "/includes/class-astcc-admin.php";

class Course_Customizer
{
    private static $instance = null;
    public $database_manager;
    public $quiz_handler;
    public $utilities;
    public $expression_evaluator;
    public $admin;

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

        $this->init_hooks();
    }

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
        // register_deactivation_hook(__FILE__, array($this->database_manager, 'drop_tables'));
        $priority = PHP_INT_MAX;

        add_action("admin_notices", [$this, "activation_notice"]);
        // add_action('plugins_loaded', [$this->database_manager, 'check_database_info']);
        add_action(
            "learndash_quiz_submitted",
            [$this->quiz_handler, "handle_quiz_submission"],
            10,
            2
        );
        /*
        add_filter(
            "the_content",
            [$this->expression_evaluator, "eval_on_page_expressions"],
            $priority
        );*/
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
    }

    public function enqueue_answer_checker_script()
    {
        wp_enqueue_script(
            "answer-checker",
            plugin_dir_url(__FILE__) . "js/answer-checker.js",
            ["jquery"],
            time(),
            true
        );
        wp_localize_script("answer-checker", "myAjax", [
            "ajaxurl" => admin_url("admin-ajax.php"),
        ]);
    }

    public function activation_notice()
    {
        echo '<div class="notice notice-success"><p>Course Customizer plugin activated!</p></div>';
    }

    public function ajax_validate_quiz_answers()
    {
        if (
            !isset($_POST["userAnswer"]) ||
            !isset($_POST["post_question_id"])
        ) {
            wp_send_json_error(["message" => "Missing required parameters"]);
            wp_die();
        }

        $user_answer = sanitize_text_field($_POST["userAnswer"]);
        $question_post_id = intval($_POST["post_question_id"]);

        if (!$question_post_id) {
            wp_send_json_error(["message" => "Invalid question ID"]);
            wp_die();
        }

        $is_time = $this->quiz_handler->is_time_question($question_post_id);
        $is_valid = $this->utilities->validate_quiz_answers(
            $user_answer,
            $is_time
        );

        wp_send_json_success(["is_valid" => $is_valid]);
        wp_die();
    }
}

// Initialize the plugin
function course_customizer()
{
    return Course_Customizer::get_instance();
}

course_customizer();
