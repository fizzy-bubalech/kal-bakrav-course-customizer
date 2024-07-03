<?php
/*
Plugin Name: Course Customizer
Description: Adds custom database tables for storing additional data and custom filters to inject user result data into courses.
Version: 0.1
Author: AST
*/

// Display an admin notice when the plugin is activated

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Include class files
//require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/includes/class-astcc-database-manager.php';
require_once __DIR__ . '/includes/class-astcc-utilities.php';
require_once __DIR__ . '/includes/class-astcc-quiz-handler.php';
require_once __DIR__ . '/includes/class-astcc-expression-evaluator.php';



class Course_Customizer {
    private static $instance = null;
    public $database_manager;
    public $quiz_handler;
    public $utilities;
    public $expression_evaluator;

    private function __construct() {
        $this->database_manager = new \CourseCustomizer\ASTCC_Database_Manager();
        $this->utilities = new \CourseCustomizer\ASTCC_Utilities();
        $this->quiz_handler = new \CourseCustomizer\ASTCC_Quiz_Handler($this->utilities, $this->database_manager);
        $this->expression_evaluator = new \CourseCustomizer\ASTCC_Expression_Evaluator();

    
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this->database_manager, 'custom_create_db_tables'));
        // register_deactivation_hook(__FILE__, array($this->database_manager, 'drop_tables'));

        add_action('admin_notices', [$this, 'activation_notice']);
        add_action('plugins_loaded', [$this->database_manager, 'check_database_info']);
        add_action('learndash_quiz_submitted', [$this->quiz_handler, 'handle_quiz_submission'], 10, 2);
        add_filter('the_content', [$this->expression_evaluator, 'eval_on_page_expressions'], 20);
    }

    public function activation_notice() {
        echo '<div class="notice notice-success"><p>Course Customizer plugin activated!</p></div>';
    }

}

// Initialize the plugin
function course_customizer() {
    return Course_Customizer::get_instance();
}

course_customizer();
    
