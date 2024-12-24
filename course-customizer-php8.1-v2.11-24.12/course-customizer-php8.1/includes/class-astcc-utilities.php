<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}
use DateTime;
use DateTimeZone;

class ASTCC_Utilities
{
    /**
     * Checks if a quiz is a metric assessment.
     *
     * @param int $quiz_post_id The ID of the quiz post.
     * @return bool True if the quiz is a metric assessment, false otherwise.
     */
    public function is_quiz_metric_assessment($quiz_post_id)
    {
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
            return true;
        }
        return false;
    }

    /**
     * Validates quiz answers.
     *
     * @param string $result_data The answer data to validate.
     * @param bool $is_time Whether the answer is a time value.
     * @return bool True if the answer is valid, false otherwise.
     */
    function validate_quiz_answers($result_data, $is_time)
    {
        $result_data = stripslashes(trim($result_data, '"'));

        if (!$is_time) {
            if (!preg_match('/^\d+$/', $result_data)) {
                return false;
            }
            $result_int = intval($result_data);
            $is_valid = $result_int >= 1 && $result_int <= 9999;
        } else {
            $is_valid = preg_match(
                '/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/',
                $result_data
            );
        }

        return $is_valid;
    }

    /**
     * Checks if quiz data is invalid.
     *
     * @param array $quiz_data The quiz data to check.
     * @return bool True if the quiz data is invalid, false otherwise.
     */
    public function is_quiz_data_invalid($quiz_data)
    {
        return isset($quiz_data["questions"]) == false ||
            is_array($quiz_data["questions"]) == false;
    }

    /**
     * Converts a time string to seconds.
     *
     * @param string $time_string The time string to convert.
     * @return int|bool The time in seconds, or false if the format is invalid.
     */
    public function time_to_seconds($time_string)
    {
        $parts = explode(":", $time_string);
        $seconds = 0;

        if (count($parts) == 3) {
            $seconds = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
        } elseif (count($parts) == 2) {
            $seconds = $parts[0] * 60 + $parts[1];
        } else {
            return false;
        }

        return $seconds;
    }

    /**
     * Gets the current date and time in Jerusalem timezone.
     *
     * @return string The current date and time in 'Y-m-d H:i:s' format.
     */
    public function current_date_time()
    {
        $timezone = new DateTimeZone("Asia/Jerusalem");
        $jerusalemTime = new DateTime("now", $timezone);
        $dateTime = $jerusalemTime->format("Y-m-d H:i:s");
        return $dateTime;
    }

    /**
     * Gets the current time in Jerusalem timezone.
     *
     * @return string The current time in 'H:i:s' format.
     */
    public function current_time()
    {
        $timezone = new DateTimeZone("Asia/Jerusalem");
        $jerusalemTime = new DateTime("now", $timezone);
        $time = $jerusalemTime->format("H:i:s");
        return $time;
    }
}
