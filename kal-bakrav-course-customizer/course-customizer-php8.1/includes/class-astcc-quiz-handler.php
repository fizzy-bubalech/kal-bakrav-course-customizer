<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

use CourseCustomizer\ASTCC_Utilities;
use CourseCustomizer\ASTCC_Database_Manager;
use CourseCustomizer\ASTCC_Expression_Evaluator;

class ASTCC_Quiz_Handler
{
    private ASTCC_Utilities $utilities;
    private ASTCC_Expression_Evaluator $expression_evaluator;
    private ASTCC_Database_Manager $database_manager;
    private array $exercise_cache = [];
    private array $post_cache = [];
    private array $question_cache = [];

    /**
     * Constructor for ASTCC_Quiz_Handler
     *
     * @param ASTCC_Utilities|null $utilities
     * @param ASTCC_Database_Manager|null $database_manager
     * @param ASTCC_Expression_Evaluator|null $expression_evaluator
     */
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

    /**
     * Check if a question is a time question
     *
     * @param int $question_post_id
     * @return bool
     */
    public function is_time_question($question_post_id)
    {
        $exercise = $this->exercise_from_question_post($question_post_id);
        return $exercise["is_time"];
    }

    /**
     * Check if a question is a time question using question ID
     *
     * @param int $question_id
     * @return bool
     */
    public function is_time_question_from_question_id($question_id)
    {
        $exercise = $this->exercise_from_question_id($question_id);
        return $exercise["is_time"];
    }

    /**
     * Get exercise from question ID
     *
     * @param int $question_id
     * @return array|null
     */
    public function exercise_from_question_id($question_id)
    {
        if (!isset($this->exercise_cache[$question_id])) {
            $question = $this->get_question($question_id);
            if ($question === null) {
                return null;
            }
            $question_text = $question["question"];
            $match = preg_match_all(
                '/(\[eval formula=(["\'`])(.*?)\2\])/',
                $question_text,
                $matches
            );

            if (!$match && empty($matches[1])) {
                $match = preg_match_all(
                    "/(%%\s*(.*?)\s*%%)/",
                    $question_text,
                    $matches
                );
            }

            if ($match && !empty($matches[1])) {
                $expression = trim($matches[1][0]);
                $decoded_expression = html_entity_decode(
                    $expression,
                    ENT_QUOTES,
                    "UTF-8"
                );
                $variable = $this->expression_evaluator->extract_variables(
                    $decoded_expression
                );
                if (!empty($variable)) {
                    $variable = preg_replace('/_dbtype$/', "", $variable[0]);
                    $this->exercise_cache[
                        $question_id
                    ] = $this->database_manager->get_exercise_by_name(
                        $variable
                    );
                }
            }
        }
        return $this->exercise_cache[$question_id] ?? null;
    }

    /**
     * Get exercise from question post
     *
     * @param int $question_post_id
     * @return array|null
     */
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

    /**
     * Get post by ID
     *
     * @param int $post_id
     * @return array|null
     */
    private function get_post($post_id)
    {
        if (!isset($this->post_cache[$post_id])) {
            $this->post_cache[$post_id] = $this->database_manager->get_post(
                $post_id
            );
        }
        return $this->post_cache[$post_id];
    }

    /**
     * Get question by ID
     *
     * @param int $question_id
     * @return array|null
     */
    private function get_question($question_id)
    {
        if (!isset($this->question_cache[$question_id])) {
            $this->question_cache[
                $question_id
            ] = $this->database_manager->get_question_from_id($question_id);
        }
        return $this->question_cache[$question_id];
    }
}
