<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

use CourseCustomizer\ASTCC_Utilities;
use CourseCustomizer\ASTCC_Database_Manager;
use CourseCustomizer\ASTCC_Expression_Evaluator;
use WpProQuiz_Model_Question;

class ASTCC_Quiz_Handler
{
    private ASTCC_Utilities $utilities;
    private ASTCC_Expression_Evaluator $expression_evaluator;
    private ASTCC_Database_Manager $database_manager;
    private array $exercise_cache = [];
    private array $post_cache = [];

    public function __construct(
        ASTCC_Utilities $utilities = null,
        ASTCC_Database_Manager $database_manager = null,
        ASTCC_Expression_Evaluator $expression_evaluator = null
    ) {
        $this->utilities = $utilities ?? new ASTCC_Utilities();
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
        $this->expression_evaluator =
            $expression_evaluator ?? new ASTCC_Expression_Evaluator();
    }

    public function handle_quiz_submission($quiz_data, $user)
    {
        error_log("Entered handle quiz submission");
        $user_id = $user->ID;
        $statistic_ref_id = $quiz_data["statistic_ref_id"];

        $questions = $quiz_data["questions"];

        $handled_and_valid = $this->handle_submitted_quiz_question_and_answers(
            $questions,
            $statistic_ref_id,
            $user_id
        );

        if (!$handled_and_valid) {
            error_log("Answers were invalid");
            return $this->handle_invalid_quiz_result($statistic_ref_id);
        }
        error_log("Answers were valid");

        return json_encode([
            "message" =>
                "Metric Assessment completed successfully. All entries were found valid and recorded by the system. " .
                $this->utilities->current_time(),
        ]);
    }

    private function handle_submitted_quiz_question_and_answers(
        $questions,
        $statistic_ref_id,
        $user_id
    ) {
        $results = [];
        $quiz_results_valid = true;
        $user_answers = $this->get_all_user_answers_from_statistics(
            $statistic_ref_id
        );

        foreach ($questions as $question_obj) {
            $question_post_id = $question_obj->getQuestionPostId();

            $user_answer = $user_answers[$question_post_id] ?? null;
            // Remove any extra backslashes and quotes
            $user_answer = stripslashes(trim($user_answer, '"'));
            error_log("User's answer in quiz handler: " . $user_answer);

            $result = $this->result_from_question_object(
                $user_id,
                $statistic_ref_id,
                $question_obj,
                $question_post_id,
                $user_answer
            );

            $is_time = $this->is_time_question($question_post_id);

            $is_valid_result = $this->utilities->validate_quiz_answers(
                $result["result"],
                $is_time
            );

            if (!$is_valid_result) {
                $this->database_manager->update_quiz_statistic_answer_data_invalid(
                    $statistic_ref_id,
                    $question_post_id
                );
                $quiz_results_valid = false;
            } else {
                $result["result"] = $is_time
                    ? $this->utilities->time_to_seconds($result["result"])
                    : intval($result["result"]);
            }
            $results[] = $result;
        }

        if ($quiz_results_valid) {
            $this->database_manager->insert_results_into_wp_results_table(
                $results
            );
            return true;
        }
        return false;
    }

    private function handle_invalid_quiz_result($statistic_ref_id)
    {
        $invalid_answers = $this->database_manager->check_for_invalid_answers(
            $statistic_ref_id
        );
        $current_time = $this->utilities->current_time();

        if ($invalid_answers > 0) {
            $this->database_manager->remove_invalid_quiz_answers(
                $statistic_ref_id
            );
            $message =
                "Metric Assessment completed unsuccessfully. Some entries were invalid, none recorded. Please retake the assessment. " .
                $current_time;
        } else {
            $message =
                "Metric Assessment completed successfully. All entries were found valid and recorded by the system. " .
                $current_time;
        }

        return json_encode(["message" => $message]);
    }

    public function result_from_question_object(
        $user_id,
        $statistic_ref_id,
        $question_obj,
        $question_post_id,
        $user_answer
    ) {
        if (!($question_obj instanceof WpProQuiz_Model_Question)) {
            return null;
        }

        $exercise = $this->exercise_from_question_post($question_post_id);

        return [
            "user_id" => $user_id,
            "exercise_id" => $exercise["exercise_id"],
            "result" => $user_answer,
            "is_metric" => 1,
            "result_date" => $this->utilities->current_date_time(),
        ];
    }

    public function is_time_question($question_post_id)
    {
        $exercise = $this->exercise_from_question_post($question_post_id);
        return $exercise["is_time"];
    }

    public function exercise_from_question_post($question_post_id)
    {
        if (!isset($this->exercise_cache[$question_post_id])) {
            $question_post = $this->get_post($question_post_id);
            $question_post_content = $question_post["post_content"];
            $variable = $this->expression_evaluator->extract_variables(
                $question_post_content
            );
            $variable = preg_replace('/_dbtype$/', "", $variable[0]);
            $this->exercise_cache[
                $question_post_id
            ] = $this->database_manager->get_exercise_by_name($variable);
        }
        return $this->exercise_cache[$question_post_id];
    }

    private function get_post($post_id)
    {
        if (!isset($this->post_cache[$post_id])) {
            $this->post_cache[$post_id] = $this->database_manager->get_post(
                $post_id
            );
        }
        return $this->post_cache[$post_id];
    }

    private function get_all_user_answers_from_statistics($statistic_ref_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "learndash_pro_quiz_statistic";

        $query = $wpdb->prepare(
            "SELECT question_post_id, answer_data FROM $table_name WHERE statistic_ref_id = %d",
            $statistic_ref_id
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $user_answers = [];
        foreach ($results as $row) {
            $answer_data = maybe_unserialize($row["answer_data"]);
            $user_answers[
                $row["question_post_id"]
            ] = $this->process_answer_data($answer_data);
        }

        return $user_answers;
    }

    private function process_answer_data($answer_data)
    {
        if (is_array($answer_data)) {
            $selected_choices = array_keys($answer_data, 1);
            return "Selected choices: " .
                implode(
                    ", ",
                    array_map(function ($i) {
                        return $i + 1;
                    }, $selected_choices)
                );
        } elseif (
            is_string($answer_data) &&
            strpos($answer_data, "graded_id") !== false
        ) {
            $graded_id = json_decode($answer_data, true)["graded_id"];
            $essay_post = get_post($graded_id);
            return $essay_post
                ? $essay_post->post_content
                : "Essay answer not found";
        } else {
            // Clean the answer data
            return stripslashes(trim($answer_data, '"'));
        }
    }
}
