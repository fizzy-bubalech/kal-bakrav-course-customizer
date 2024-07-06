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

    public function __construct(ASTCC_Quiz_Handler $quiz_handler = null) {
        $this->quiz_handler = $quiz_handler ?? new ASTCC_Quiz_Handler();
    }

    public function eval_on_page_expressions($content) {
        return preg_replace_callback('/%%\s*(.*?)\s*%%/', [$this,'process_expression'], $content);
    }
    
    public function process_expression($matches) {
        $allowed_functions = [
            'handle_most_recent_quiz_completion' => $this->quiz_handler->handle_most_recent_quiz_completion()
        ];
        $expression = trim($matches[1]);
        $decoded_expression = html_entity_decode($expression, ENT_QUOTES, 'UTF-8');
        
        if ($this->has_function_call($decoded_expression,$allowed_functions)) {
            return $this->handle_function_call($decoded_expression,$allowed_functions);
        }

        try {

            $variables = $this->extract_variables($decoded_expression);
            $variable_values = $this->fetch_variables_from_db($variables);
            $result = $this->evaluate_expression($decoded_expression, $variable_values);
        return $this->format_result($result);
        } catch (SyntaxError $e) {
            return 'Error: Invalid expression';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
        
        
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
        foreach ($function_array as $func_name => $func_call) {
            if (strpos($input_string, $func_name) !== false) {
                // Function name found in string, execute the function
                return $func_call;
            }
        }
        // No matching function found
        return null;
    }
    
    
    public function extract_variables($expression) {
        $expressionLanguage = new ExpressionLanguage();
        $parsed = $expressionLanguage->parse($expression, []);
        $variables = [];
    
        $extract = function($node) use (&$extract, &$variables) {
            if ($node instanceof \Symfony\Component\ExpressionLanguage\Node\NameNode) {
                $variables[] = $node->attributes['name'];
            } elseif ($node instanceof \Symfony\Component\ExpressionLanguage\Node\Node) {
                foreach ($node->nodes as $subNode) {
                    $extract($subNode);
                }
            }
        };
    
        $extract($parsed->getNodes());
        return array_unique($variables);
    }
    
    public function fetch_variables_from_db($variables) {
        global $wpdb;
        $variable_values = [];
    
        foreach ($variables as $variable) {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT value FROM your_variables_table WHERE name = %s",
                $variable
            ));
    
            if ($value === null) {
                throw new Exception("Variable '$variable' not found in database");
            }
    
            $variable_values[$variable] = is_numeric($value) ? floatval($value) : $value;
        }
    
        return $variable_values;
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


