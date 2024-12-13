<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class ASTCC_Admin
{
    private $database_manager;

    /**
     * Constructor for the ASTCC_Admin class.
     *
     * @param ASTCC_Database_Manager|null $database_manager The database manager instance.
     */
    public function __construct(ASTCC_Database_Manager $database_manager = null)
    {
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
    }

    /**
     * Adds the plugin admin menu and submenus.
     *
     * @return void
     */
    public function add_plugin_admin_menu()
    {
        add_menu_page(
            "Course Customizer Settings",
            "Course Customizer",
            "manage_options",
            "course-customizer-settings",
            [$this, "display_plugin_admin_page"],
            "dashicons-welcome-learn-more",
            100
        );

        add_submenu_page(
            "course-customizer-settings",
            "Manage Exercises",
            "Exercises",
            "manage_options",
            "course-customizer-exercises",
            [$this, "display_exercises_page"]
        );

        add_submenu_page(
            "course-customizer-settings",
            "View Results",
            "Results",
            "manage_options",
            "course-customizer-results",
            [$this, "display_results_page"]
        );
    }

    /**
     * Displays the main plugin admin page.
     *
     * @return void
     */
    public function display_plugin_admin_page()
    {
        include_once plugin_dir_path(dirname(__FILE__)) .
            "admin/partials/course-customizer-admin-display.php";
    }

    /**
     * Displays the exercises management page.
     *
     * @return void
     */
    public function display_exercises_page()
    {
        if (isset($_POST["add_exercise"])) {
            $exercise_name = sanitize_text_field($_POST["exercise_name"]);
            $is_time = isset($_POST["is_time"]) ? 1 : 0;
            $min = isset($_POST["min"]) ? intval($_POST["min"]) : 0;
            $max = isset($_POST["max"]) ? intval($_POST["max"]) : 0;
            $this->database_manager->add_exercise($exercise_name, $is_time, $min, $max);
        } elseif (isset($_POST["delete_exercise"])) {
            $exercise_id = intval($_POST["exercise_id"]);
            $this->database_manager->delete_exercise($exercise_id);
        }

        $style_handle = "exercise-admin-style";
        $style_filename = "exercise-admin-style.css";
        $style_dir_path = "admin/css/";

        // Get the full server path to the CSS file
        $file_path = plugin_dir_path(dirname(__FILE__)) . $style_dir_path . $style_filename;

        // Get file modification time for version
        $version = file_exists($file_path) ? filemtime($file_path) : '1.0';

        wp_enqueue_style(
            $style_handle,
            plugins_url($style_dir_path . $style_filename, dirname(__FILE__)),
            array(),
            $version
        );

        $script_handle = "course-customizer-exercises";
        $script_filename = "course-customizer-exercises.js";
        $script_dir_path = "admin/js/";
        $script_path = plugin_dir_path(dirname(__FILE__)) . $script_dir_path . $script_filename;
        $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0';
        wp_enqueue_script(
            $script_handle,
            plugins_url($script_dir_path . $script_filename, dirname(__FILE__)),
            array(), // Removed jQuery dependency
            $script_version,
            true
        );

        // Add inline editing nonce and AJAX URL
        wp_localize_script($script_handle, 'exerciseAdminObj', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('exercise_inline_edit_nonce'),
            'strings' => array(
                'saveFailed' => __('Failed to save. Please try again.', 'your-text-domain'),
                'confirmDelete' => __('Are you sure you want to delete this exercise?', 'your-text-domain')
            )
        ));
        $exercises = $this->database_manager->get_all_exercises();
        include_once plugin_dir_path(dirname(__FILE__)) .
            "admin/partials/course-customizer-exercises-display.php";
    }

    /**
     * AJAX handler for updating exercise minimum value
     *
     * @return void
     */
    public function handle_inline_edit()
    {
        // Verify nonce
        check_ajax_referer('exercise_inline_edit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        // Validate and sanitize input
        $exercise_id = isset($_POST['exercise_id']) ? intval($_POST['exercise_id']) : 0;
        $min_value = isset($_POST['min_value']) ? floatval($_POST['min_value']) : 0;

        if (!$exercise_id) {
            wp_send_json_error(array('message' => 'Invalid exercise ID'));
        }

        // Update the exercise
        $result = $this->database_manager->update_exercise_min($exercise_id, $min_value);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Successfully updated',
                'new_value' => $min_value
            ));
        } else {
            wp_send_json_error(array('message' => 'Update failed'));
        }
    }

    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function register_ajax_handlers()
    {
        add_action('wp_ajax_update_exercise_min', array($this, 'handle_inline_edit'));
    }
    /**
     * Displays the results page.
     *
     * @return void
     */
    public function display_results_page()
    {
        if (isset($_POST["add_result"])) {
            $user_id = intval($_POST["user_id"]);
            $exercise_id = intval($_POST["exercise_id"]);
            $result = intval($_POST["result"]);
            $result_date = sanitize_text_field($_POST["result_date"]);
            $is_metric = isset($_POST["is_metric"]) ? 1 : 0;
            $this->database_manager->add_result(
                $user_id,
                $exercise_id,
                $result,
                $result_date,
                $is_metric
            );
        } elseif (isset($_POST["delete_result"])) {
            $result_id = intval($_POST["result_id"]);
            $this->database_manager->delete_result($result_id);
        }

        $results = $this->database_manager->get_all_results();
        $users = $this->database_manager->get_all_users();
        $exercises = $this->database_manager->get_all_exercises();

        $js_file_url =
            plugin_dir_url(dirname(__FILE__)) .
            "admin/js/course-customizer-results.js";
        $version = file_exists($js_file_path)
            ? filemtime($js_file_path)
            : "1.0";

        wp_enqueue_script(
            "course-customizer-results",
            $js_file_url,
            ["jquery"],
            $version,
            true
        );
        wp_localize_script(
            "course-customizer-results",
            "course_customizer_ajax",
            [
                "ajax_url" => admin_url("admin-ajax.php"),
                "nonce" => wp_create_nonce("course_customizer_results_nonce"),
            ]
        );

        include_once plugin_dir_path(dirname(__FILE__)) .
            "admin/partials/course-customizer-results-display.php";
    }

    /**
     * Handles AJAX requests for filtered results.
     *
     * @return void
     */
    public function handle_ajax_request()
    {
        check_ajax_referer("course_customizer_results_nonce", "nonce");

        $user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : null;
        $exercise_id = isset($_GET["exercise_id"])
            ? intval($_GET["exercise_id"])
            : null;
        $search = isset($_GET["search"])
            ? sanitize_text_field($_GET["search"])
            : "";
        $order_by = isset($_GET["order_by"])
            ? sanitize_sql_orderby($_GET["order_by"])
            : "result_date";
        $order = isset($_GET["order"])
            ? (strtoupper($_GET["order"]) === "ASC"
                ? "ASC"
                : "DESC")
            : "DESC";

        $results = $this->database_manager->get_filtered_results(
            $user_id,
            $exercise_id,
            $search,
            $order_by,
            $order
        );

        wp_send_json($results);
    }

    /**
     * Handles AJAX requests for deleting results.
     *
     * @return void
     */
    public function handle_delete_result()
    {
        check_ajax_referer("course_customizer_results_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error("Unauthorized user");
            return;
        }

        $result_id = isset($_POST["result_id"])
            ? intval($_POST["result_id"])
            : 0;

        if ($result_id === 0) {
            wp_send_json_error("Invalid result ID");
            return;
        }

        $deleted = $this->database_manager->delete_result($result_id);

        if ($deleted) {
            wp_send_json_success("Result deleted successfully");
        } else {
            wp_send_json_error("Failed to delete result");
        }
    }

    /**
     * Initializes AJAX handlers.
     *
     * @return void
     */
    public function init_ajax_handlers()
    {
        add_action("wp_ajax_get_filtered_results", [
            $this,
            "handle_ajax_request",
        ]);
        add_action("wp_ajax_nopriv_get_filtered_results", [
            $this,
            "handle_ajax_request",
        ]);
        add_action("wp_ajax_delete_result", [$this, "handle_delete_result"]);
    }
}
