<?php

namespace CourseCustomizer; 


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__.'/../vendor/autoload.php';


use CourseCustomizer\ASTCC_Quiz_Handler;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Exception;

class ASTCC_Expression_Evaluator{
    private $quiz_handler;
    private $allowed_functions;
    private $database_manager;

    public function __construct(ASTCC_Quiz_Handler $quiz_handler = null, ASTCC_Database_Manager $database_manager = null) {
        $this->quiz_handler = $quiz_handler ?? new ASTCC_Quiz_Handler();
        $this->database_manager = $database_manager ?? new ASTCC_Database_Manager();
        $this->allowed_functions = [
            'handle_most_recent_quiz_completion' => $this->quiz_handler->handle_most_recent_quiz_completion(),
            'test_output' => $this->test_output()
        ];

    }

    public function eval_on_page_expressions($content) {
        error_log("Evaluating on page expressions");
        return preg_replace_callback('/%%\s*(.*?)\s*%%/', [$this,'process_expression'], $content);
    }
    
    public function process_expression($match) {
        error_log("Processing expressions:". implode(" - ",$match));
        $expression = trim($match[1]);
        $decoded_expression = html_entity_decode($expression, ENT_QUOTES, 'UTF-8');
        
        if ($this->has_function_call($decoded_expression,$this->allowed_functions)) {
            error_log("Detected function call within expression");
            return $this->handle_function_call($decoded_expression,$this->allowed_functions);
        }

        try {

            $variables = $this->extract_variables($decoded_expression);
            $variables_and_values = $this->fetch_variables_from_db($variables);
            $result = $this->evaluate_expression($decoded_expression, $variables_and_values);
            return $this->format_result($result);
        } catch (SyntaxError $e) {
            return 'Error: Invalid expression';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
        return "Error: Unknown";
        
        
    }
    
    public function test_output(){
        return 'WORKED';
    }
    
    public function has_function_call($expression,$allowed_functions) {

        foreach ($allowed_functions as $func_name => $func_call) {
            if (strpos($expression, $func_name ) !== false) {
                return true;
            }
        }
        return false;
    }
    function handle_function_call($input_string, $function_array) {
        error_log("handling function call");
        foreach ($function_array as $func_name => $func_call) {
            if (strpos($input_string, $func_name) !== false) {
                // Function name found in string, execute the function
                error_log("Executing $func_name function call");

                return $func_call;
            }
        }
        // No matching function found
        return null;
    }
    
    
    public function extract_variables($expression) {

        $variables = [];
        preg_match_all('/\s*\$(.*?)(\s|$)/', $expression, $matches);
        $variables = $matches[1];
        
    
        return array_unique($variables);
    }
    
    public function fetch_variables_from_db($variables) {
        global $wpdb;
        $variables_and_values = [];
        $current_user_id = get_current_user_id();
        $table_name = 'results';
    
        foreach ($variables as $variable) {
            $exercise_id = $this->database_manager->get_exercise_from_name($variable)["exercise_id"];
            $query = $wpdb->prepare(
                "SELECT result 
                FROM {$wpdb->prefix}%s 
                WHERE user_id = %d 
                AND exercise_id = %d 
                ORDER BY result_date DESC",
                $table_name,
                $current_user_id,
                $exercise_id
            );
            $value = $wpdb->get_var($query);
    
            if ($value === null) {
                throw new Exception("Variable '$variable' not found in database");
            }
    
            $variables_and_values[$variable] = is_numeric($value) ? floatval($value) : $value;
        }
    
        return $variables_and_values;
    }
    
    public function evaluate_expression($expression, $variable_values) {
        $expressionLanguage = new ExpressionLanguage();
        try {
            return $expressionLanguage->evaluate($expression, $variable_values);
        } catch (SyntaxError $e) {
            return 'Error: Invalid expression';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    
    public function format_result($result) {
        return is_numeric($result) ? $result : strval($result);
    }
    



}


