<?php

namespace CourseCustomizer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class ASTCC_Admin {
    private $database_manager;

    public function __construct(ASTCC_Database_Manager $database_manager = null) {
        $this->database_manager = $database_manager ?? new ASTCC_Database_Manager();
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Course Customizer Settings',
            'Course Customizer',
            'manage_options',
            'course-customizer-settings',
            [$this, 'display_plugin_admin_page'],
            'dashicons-welcome-learn-more',
            100
        );

        add_submenu_page(
            'course-customizer-settings',
            'Manage Exercises',
            'Exercises',
            'manage_options',
            'course-customizer-exercises',
            [$this, 'display_exercises_page']
        );

        add_submenu_page(
            'course-customizer-settings',
            'View Results',
            'Results',
            'manage_options',
            'course-customizer-results',
            [$this, 'display_results_page']
        );
    }

    public function display_plugin_admin_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/course-customizer-admin-display.php';
    }

    public function display_exercises_page() {
        if (isset($_POST['add_exercise'])) {
            $exercise_name = sanitize_text_field($_POST['exercise_name']);
            $is_time = isset($_POST['is_time']) ? 1 : 0;
            $this->database_manager->add_exercise($exercise_name, $is_time);
        } elseif (isset($_POST['delete_exercise'])) {
            $exercise_id = intval($_POST['exercise_id']);
            $this->database_manager->delete_exercise($exercise_id);
        }
    
        $exercises = $this->database_manager->get_all_exercises();
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/course-customizer-exercises-display.php';
    }
    
    public function display_results_page() {
        if (isset($_POST['add_result'])) {
            $user_id = intval($_POST['user_id']);
            $exercise_id = intval($_POST['exercise_id']);
            $result = intval($_POST['result']);
            $result_date = sanitize_text_field($_POST['result_date']);
            $is_metric = isset($_POST['is_metric']) ? 1 : 0;
            $this->database_manager->add_result($user_id, $exercise_id, $result, $result_date, $is_metric);
        } elseif (isset($_POST['delete_result'])) {
            $result_id = intval($_POST['result_id']);
            $this->database_manager->delete_result($result_id);
        }
    
        $results = $this->database_manager->get_all_results();
        $users = $this->database_manager->get_all_users();
        $exercises = $this->database_manager->get_all_exercises();
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/course-customizer-results-display.php';
    }

    public function register_and_build_fields() {
        // You can add any additional settings fields here if needed
    }
}