<?php

namespace CourseCustomizer; 


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class ASTCC_Utilities{

    public function is_quiz_metric_assessment($quiz_post_id){
        /*
            Queries wp_posts table for the post title of a post with the id of $quiz_post_id. 
            Then it checks if quiz post title has the words 'quiz assessment' or 'אומדן מדדים' in the post title. 
        */
    
        global $wpdb;
        $query = $wpdb->prepare("SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $quiz_post_id);
        $post_title = $wpdb->get_var($query);
        $metric_assessment_hebrew = 'אומדן מדדים';
        $metric_assessment_english = 'metric assessment';
    
        if ($post_title !== null && (strpos($post_title, $metric_assessment_hebrew) !== false || strpos($post_title, $metric_assessment_english) !== false)) {
            error_log("! ! ! IS METRIC ASSESSMENT ! ! !");
            return true;
        } 
        return false; 
    }
    function validate_quiz_answers($result_data, $is_time) {
        $is_valid = true;
    
        $result_data = intval($result_data);
    
        if (!$is_time) {
            // If not time, check if result_data is between 1 and 120
            $is_valid = ($result_data > 1 && $result_data <= 9999);
            error_log("ast_kbcc_validate_quiz_answers line 108 result_data : $result_data");
        } else {
            // If time, check if result_data is between 1 and 9999
            error_log("ast_kbcc_validate_quiz_answers line 110: result_data : $result_data");
            $is_valid = ($result_data > 1 && $result_data <= 9999);
        }
    
        return $is_valid;
    }
}