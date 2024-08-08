<?php

namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

require_once __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Exception;
use DateTime;

class ASTCC_Expression_Evaluator
{
    private $allowed_functions;
    private $database_manager;

    public function __construct(ASTCC_Database_Manager $database_manager = null)
    {
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
        $this->allowed_functions = [
            "test_output" => $this->test_output(),
        ];
    }

    public function eval_on_page_expressions($content)
    {
        error_log("Evaluating on page expressions");
        return preg_replace_callback(
            "/%%\s*(.*?)\s*%%/",
            [$this, "process_expression"],
            $content
        );
    }

    public function process_expression($match)
    {
        $expression = trim($match[1]);
        $decoded_expression = html_entity_decode(
            $expression,
            ENT_QUOTES,
            "UTF-8"
        );

        if (
            $this->has_function_call(
                $decoded_expression,
                $this->allowed_functions
            )
        ) {
            error_log("Detected function call within expression");
            return $this->handle_function_call(
                $decoded_expression,
                $this->allowed_functions
            );
        }

        try {
            $variables = $this->extract_variables($decoded_expression);

            if ($this->is_dbtype_variable($variables)) {
                return "";
            }

            $variables_and_values = $this->fetch_variables_from_db($variables);
            $has_time_based = $this->has_time_based_exercise(
                $variables_and_values
            );

            $result = $this->evaluate_expression(
                $decoded_expression,
                $variables_and_values
            );

            return $this->format_result($result, $has_time_based);
        } catch (SyntaxError $e) {
            return "Error: Invalid expression";
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
        return "Error: Unknown";
    }

    public function is_dbtype_variable($variables)
    {
        foreach ($variables as $variable) {
            if (strpos($variable, "_dbtype")) {
                error_log("Detected _dbtype");
                return true;
            }
        }
        return false;
    }

    public function test_output()
    {
        return "WORKED";
    }

    public function has_function_call($expression, $allowed_functions)
    {
        foreach ($allowed_functions as $func_name => $func_call) {
            if (strpos($expression, $func_name) !== false) {
                return true;
            }
        }
        return false;
    }
    function handle_function_call($input_string, $function_array)
    {
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

    public function extract_variables($expression)
    {
        $variables = [];
        preg_match_all(
            '/\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/',
            $expression,
            $matches
        );
        $variables = $matches[1];
        error_log(print_r($matches, true));

        return array_unique($variables);
    }

    public function fetch_variables_from_db($variables)
    {
        $variables_and_values = [];

        foreach ($variables as $variable) {
            $exercise = $this->database_manager->get_exercise_by_name(
                $variable
            );
            if ($exercise === null) {
                throw new Exception(
                    "Variable '$variable' not found in database."
                );
            }
            $exercise_id = $exercise["exercise_id"];
            $is_time = $exercise["is_time"];

            $value = $this->database_manager->get_latest_result_by_exercise_id(
                $exercise_id
            );

            if ($value === null) {
                throw new Exception(
                    "Metric entry '$variable' not found for user."
                );
            }
            $variables_and_values[$variable] = is_numeric($value)
                ? floatval($value)
                : $value;
        }

        return $variables_and_values;
    }
    public function has_time_based_exercise($variables_and_values)
    {
        foreach ($variables_and_values as $variable => $value) {
            $exercise = $this->database_manager->get_exercise_by_name(
                $variable
            );
            if ($exercise !== null && $exercise["is_time"]) {
                return true;
            }
        }
        return false;
    }

    public function evaluate_expression($expression, $variable_values)
    {
        $expressionLanguage = new ExpressionLanguage();
        $stripped_expression = str_replace('$', "", $expression);

        // Convert DateTime objects to seconds for evaluation
        foreach ($variable_values as $key => $value) {
            if ($value instanceof DateTime) {
                $variable_values[$key] = $value->getTimestamp();
            }
        }

        try {
            $result = $expressionLanguage->evaluate(
                $stripped_expression,
                $variable_values
            );

            return $result;
        } catch (SyntaxError $e) {
            return "Error: Invalid expression syntax.";
        } catch (Exception $e) {
            return "Error: Illegal expression.";
        }
    }

    public function format_result($result, $is_time)
    {
        if ($is_time) {
            // Format time as HH:MM:SS
            $time = new DateTime("@$result");
            return $time->format("H:i:s");
        }
        return is_numeric($result) ? $result : strval($result);
    }
}
