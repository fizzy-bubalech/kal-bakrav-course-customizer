<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}


class ASTCC_Shotcodes
{

    private $database_manager;
    public function __construct(ASTCC_Database_Manager $database_manager = null)
    {
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
        $this->add_all_shortcodes();
    }


    public function add_all_shortcodes()
    {

        add_shortcode("quiz_completed_redirect", [$this, "quiz_completed_redirect_shortcode"]);
        add_shortcode("required_quiz", [$this, "required_quiz_shortcode"]);

        add_shortcode("user_display_name", [$this, "shortcode_user_display_name"]);
    }

    function quiz_completed_redirect_shortcode($atts)
    {

        // Define default attributes
        $defaults = array(
            'url' => '#', // Default URL if none provided
        );
        $href = null;
        if (isset($atts[2])) {
            $href = $atts[2];
        }
        // Parse and merge the attributes
        $atts = shortcode_atts($defaults, $atts, 'quiz_completed_redirect');

        // Get the URL from attributes
        $url = $atts['url'];
        if (isset($href)) {

            // Extract URL from the href attribute
            if (preg_match('/href="([^"]+)"/', $href, $matches)) {
                $url = $matches[1];
            }
        }

        $url = esc_url_raw(trim($url));
        if (!wp_validate_redirect($url, false)) {
            $url = home_url();
        }

        error_log('Processed URL: ' . $url);

        // Store the URL in WordPress options with the current user ID
        $user_id = get_current_user_id();
        update_option('quiz_completion_redirect_' . $user_id, $url);

        return ''; // Return empty string as we don't need to output anything
    }

    // A shortcode for the current user's diplay name
    public function shortcode_user_display_name()
    {
        $user = wp_get_current_user();
        $display_name = $user->display_name;
        return $display_name;
    }
    function required_quiz_shortcode($atts)
    {
        $atts = shortcode_atts(
            array(
                'quiz_id' => 0,
            ),
            $atts,
            'required_quiz'
        );

        $quiz_id = intval($atts['quiz_id']);
        $completed = $this->database_manager->check_quiz_completion(null, $quiz_id);

        if (!$completed) {
            // Get the quiz permalink
            $quiz_url = get_permalink($quiz_id);

            // Check if quiz URL exists
            if (!$quiz_url) {
                return '<div class="quiz-status error">Quiz not found.</div>';
            }

            // Add JavaScript redirect
            $output = '<script type="text/javascript">';
            $output .= 'window.location.href = "' . esc_url($quiz_url) . '";';
            $output .= '</script>';

            // Fallback message in case JavaScript is disabled
            $output .= '<div class="quiz-status not-completed">';
            $output .= 'Quiz not completed. ';
            $output .= '<a href="' . esc_url($quiz_url) . '">Click here</a> if you are not automatically redirected.';
            $output .= '</div>';

            return $output;
        }

        return;
    }
}
