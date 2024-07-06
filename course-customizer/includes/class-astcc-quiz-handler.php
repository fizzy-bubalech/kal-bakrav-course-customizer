<?php

namespace CourseCustomizer; 


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use CourseCustomizer\ASTCC_Utilities;
use DateTime;
use DateTimeZone;
use WpProQuiz_Model_Question;


class ASTCC_Quiz_Handler{

    /** @var Utilities */
    private ASTCC_Utilities $utilities;
    private ASTCC_Database_Manager $database_manager;

    public function __construct(ASTCC_Utilities $utilities = null, ASTCC_Database_Manager $database_manager = null) {
        $this->utilities = $utilities ?? new ASTCC_Utilities();
        $this->database_manager = $database_manager ?? new ASTCC_Database_Manager();
    }

    public function handle_quiz_submission($quiz_data, $user){

        $user_id = $user->ID;
        $quiz_post_id = $quiz_data['quiz'];
        $statistic_ref_id = $quiz_data['statistic_ref_id'];
    
        //if($this->utilities->is_quiz_metric_assessment($quiz_post_id) == false) return; //Checks if quiz is a metric assessment and if not returns.
    
        $results = [];
        $quiz_results_valid = true;
    
        if (isset($quiz_data['questions']) == false || is_array($quiz_data['questions']) == false) {
            return;
        }
        foreach ($quiz_data['questions'] as $question_obj) {
            $question_post_id = $question_obj->getQuestionPostId();
            
            $result_and_is_valid_array = $this->get_learndash_question_result($user_id, $statistic_ref_id, $question_obj, $question_post_id);
            $is_valid_result = $result_and_is_valid_array[1];
            $result = $result_and_is_valid_array[0];
            if(!$is_valid_result){
                $this->database_manager->update_quiz_statistic_answer_data_invalid($statistic_ref_id, $question_post_id);
                $quiz_results_valid = false;
            }
            $results[] = $result;
            
        }
    
        if($quiz_results_valid) $this->database_manager->insert_results_into_wp_results_table($results);
    
        // error_log('Question Answer Pairs: ' . print_r($results, true));
        
        
    }

    
    public function get_learndash_question_result($user_id, $statistic_ref_id, $question_obj, $question_post_id) {
        if (($question_obj instanceof WpProQuiz_Model_Question) == false) {
            return;
        }
         
        // Get user's answer from the statistic data and convert to int 
        $user_answer = intval($this->get_user_answer_from_statistics($statistic_ref_id, $question_post_id));

        $is_valid_result = $this->utilities->validate_quiz_answers($user_answer, false); //CHANGE FALSE TO  $is_time FROM EXERCISE TABLE. TODO

        // Set the timezone to Jerusalem
        $timezone = new DateTimeZone('Asia/Jerusalem');

        // Create a new DateTime object with the current time in the Jerusalem timezone
        $jerusalemTime = new DateTime('now', $timezone);

        // Format the date and time as a string suitable for database storage
        $dateTimeForDB = $jerusalemTime->format('Y-m-d H:i:s');

        $result = [
            'user_id' => $user_id,
            'exercise_id' => 1,
            'result' => $user_answer,
            'is_metric' => 1,
            'result_date' => $dateTimeForDB
        ];
        return [$result, $is_valid_result];
        
    }

    public function handle_most_recent_quiz_completion()
    {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Get the most recent statistic_ref_id for the current user
        $statistic_ref_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT statistic_ref_id 
                FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref 
                WHERE user_id = %d 
                ORDER BY create_time DESC 
                LIMIT 1",
                $current_user_id
            )
        );

        if (!$statistic_ref_id) {
            return "No recent quiz completion found for the current user.";
        }

        $invalid_answers = $this->check_for_invalid_answers($statistic_ref_id);
        if ($invalid_answers > 0) {
            $this->remove_invalid_quiz_answers($statistic_ref_id);
            return "Metric Assessment completed successfully. All entries were found valid and recorded by the system.";
        } 
        return "Metric Assessment completed un-successfully. Some entries were invalid, none recorded. Please retake the assessment.";
    }


    public function check_for_invalid_answers($statistic_ref_id){
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_pro_quiz_statistic';

    
        // Check for invalid answers
        $invalid_answers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE statistic_ref_id = %d AND answer_data = 'invalid'", $statistic_ref_id));
        error_log("Checking quiz with statistic_ref_id: $statistic_ref_id. Invalid answers found: $invalid_answers");
        return $invalid_answers;
        

    }

    public function remove_invalid_quiz_answers($statistic_ref_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_pro_quiz_statistic';
        $wpdb->delete(
            $table_name,
            array('statistic_ref_id' => $statistic_ref_id),
            array('%d')
        );
        $wpdb->delete(
            $wpdb->prefix . 'learndash_pro_quiz_statistic_ref',
            array('statistic_ref_id' => $statistic_ref_id),
            array('%d')
        );
       }



    

    function get_user_answer_from_statistics($statistic_ref_id, $question_post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_pro_quiz_statistic';
        
        $query = $wpdb->prepare(
            "SELECT answer_data FROM $table_name WHERE statistic_ref_id = %d AND question_post_id = %d",
            $statistic_ref_id,
            $question_post_id
        );
    
        $result = $wpdb->get_var($query);
        
        if(!$result){
            return 'Not available';
        }
    
        $answer_data = maybe_unserialize($result);
        if (is_array($answer_data)) {
            // Multi-choice question
            $selected_choices = array_keys($answer_data, 1);
            return 'Selected choices: ' . implode(', ', array_map(function($i) { return $i + 1; }, $selected_choices));
        } elseif (is_string($answer_data) && strpos($answer_data, 'graded_id') !== false) {
            // Essay question
            $graded_id = json_decode($answer_data, true)['graded_id'];
            $essay_post = get_post($graded_id);
            return $essay_post ? $essay_post->post_content : 'Essay answer not found';
        }
        else return $answer_data;
    
    }
}