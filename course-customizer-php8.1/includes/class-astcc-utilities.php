<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}
use DateTime;
use DateTimeZone;

class ASTCC_Utilities
{
    public function is_quiz_metric_assessment($quiz_post_id)
    {
        /*
            Queries wp_posts table for the post title of a post with the id of $quiz_post_id.
            Then it checks if quiz post title has the words 'quiz assessment' or 'אומדן מדדים' in the post title.
        */

        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
            $quiz_post_id
        );
        $post_title = $wpdb->get_var($query);
        $metric_assessment_hebrew = "אומדן מדדים";
        $metric_assessment_english = "metric assessment";

        if (
            $post_title !== null &&
            (strpos($post_title, $metric_assessment_hebrew) !== false ||
                strpos($post_title, $metric_assessment_english) !== false)
        ) {
            error_log("! ! ! IS METRIC ASSESSMENT ! ! !");
            return true;
        }
        return false;
    }
    function validate_quiz_answers($result_data, $is_time)
    {
        // Remove any extra backslashes and quotes
        $result_data = stripslashes(trim($result_data, '"'));

        error_log(
            "Validating answer: " .
                $result_data .
                ", is_time: " .
                ($is_time ? "true" : "false")
        );

        if (!$is_time) {
            // For non-time answers, check if it's a valid integer between 1 and 9999
            if (!preg_match('/^\d+$/', $result_data)) {
                error_log("Invalid non-time answer: not a valid integer");
                return false;
            }
            $result_int = intval($result_data);
            $is_valid = $result_int >= 1 && $result_int <= 9999;
            error_log(
                "Non-time answer validation result: " .
                    ($is_valid ? "valid" : "invalid")
            );
        } else {
            // For time answers, check if it's in the format MM:SS or HH:MM:SS
            $is_valid = preg_match(
                '/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/',
                $result_data
            );
            error_log(
                "Time answer validation result: " .
                    ($is_valid ? "valid" : "invalid")
            );
        }

        return $is_valid;
    }

    public function is_quiz_data_invalid($quiz_data)
    {
        //If there are no questions in the quiz_data or if they aren't formatted correctly returns true
        return isset($quiz_data["questions"]) == false ||
            is_array($quiz_data["questions"]) == false;
    }

    public function time_to_seconds($time_string)
    {
        $parts = explode(":", $time_string);
        $seconds = 0;
        error_log("Converting time to seconds: " . $time_string);

        if (count($parts) == 3) {
            // Format is HH:MM:SS
            $seconds = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
        } elseif (count($parts) == 2) {
            // Format is MM:SS
            $seconds = $parts[0] * 60 + $parts[1];
        } else {
            error_log("Invalid time format: " . $time_string);
            return false;
        }

        error_log("Converted to seconds: " . $seconds);
        return $seconds;
    }

    public function current_date_time()
    {
        // Set the timezone to Jerusalem
        $timezone = new DateTimeZone("Asia/Jerusalem");

        // Create a new DateTime object with the current time in the Jerusalem timezone
        $jerusalemTime = new DateTime("now", $timezone);

        // Format the date and time as a string suitable for database storage
        $dateTime = $jerusalemTime->format("Y-m-d H:i:s");
        return $dateTime;
    }

    public function current_time()
    {
        // Set the timezone to Jerusalem
        $timezone = new DateTimeZone("Asia/Jerusalem");

        // Create a new DateTime object with the current time in the Jerusalem timezone
        $jerusalemTime = new DateTime("now", $timezone);

        // Format the date and time as a string suitable for database storage
        $time = $jerusalemTime->format("H:i:s");
        return $time;
    }
}
