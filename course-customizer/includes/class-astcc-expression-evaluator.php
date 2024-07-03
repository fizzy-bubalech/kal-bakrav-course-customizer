<?php

namespace CourseCustomizer; 


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Exception;

class ASTCC_Expression_Evaluator{

    public function eval_on_page_expressions($content) {
        return preg_replace_callback('/%%\s*(.*?)\s*%%/', [$this,'process_expression'], $content);
    }
    
    public function process_expression($matches) {
        $expression = trim($matches[1]);
        
        if ($this->has_function_call($expression)) {
            return $this->handle_function_call($expression);
        }
        
        $variables = $this->extract_variables($expression);
        $variable_values = $this->fetch_variables_from_db($variables);
        $result = $this->evaluate_expression($expression, $variable_values);
        return $this->format_result($result);
    }
    
    public function has_function_call($expression) {
        $allowed_functions = ['custom_func1', 'custom_func2']; // Add your custom functions here
        foreach ($allowed_functions as $func) {
            if (strpos($expression, $func . '(') !== false) {
                return true;
            }
        }
        return false;
    }
    
    public function handle_function_call($expression) {
        // This is a simplified example. You'll need to implement proper parsing and security measures.
        $result = eval("return $expression;");
        return $result;
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


