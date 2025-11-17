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

    /**
     * Constructor for ASTCC_Expression_Evaluator
     *
     * @param ASTCC_Database_Manager|null $database_manager
     */
    public function __construct(ASTCC_Database_Manager $database_manager = null)
    {
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
        $this->allowed_functions = [
            "test_output" => $this->test_output(),
            "int" => $this->format_int(),
        ];
        add_shortcode("eval", [$this, "eval_on_page_shortcode"]);
    }

    /**
     * Evaluate expressions on page
     *
     * @param string $content
     * @return string
     */
    public function eval_on_page_expressions($content)
    {
        return preg_replace_callback(
            "/%%\s*(.*?)\s*%%/",
            [$this, "process_expression"],
            $content
        );
    }

    /**
     * Shortcode for evaluating expressions on page
     *
     * @param array $atts
     * @param string|null $content
     * @return string
     */
    public function eval_on_page_shortcode($atts, $content = null)
    {
        if ($content == null) {
            $a = shortcode_atts(["formula" => ""], $atts);
            $expression = $a["formula"];
        } else {
            $expression = $content;
        }

        return $this->process_expression([0, $expression]);
    }

    /**
     * Process the expression
     *
     * @param array $match
     * @return string
     */
    public function process_expression($match, $format_int = false)
    {
        $expression = trim($match[1]);
        $decoded_expression = html_entity_decode(
            $expression,
            ENT_QUOTES,
            "UTF-8"
        );

        // Add debug output
        $debug = "<!-- Debug: Expression = " . htmlspecialchars($decoded_expression) . " -->\n";

        if (
            $this->has_function_call(
                $decoded_expression,
                $this->allowed_functions
            )
        ) {
            return $debug . "<!--Function call detected-->";
        }

        try {
            $variables = $this->extract_variables($decoded_expression);
            $debug .= "<!-- Debug: Variables = " . print_r($variables, true) . " -->\n";

            if ($this->is_dbtype_variable($variables)) {
                return $debug . "<!-- dbtype variable detected-->";
            }

            $variables_and_values = $this->fetch_variables_from_db($variables);
            $debug .= "<!-- Debug: Values = " . print_r($variables_and_values, true) . " -->\n";

            $has_time_based = $this->has_time_based_exercise(
                $variables_and_values
            );
            $debug .= "<!-- Debug: Is time based = " . ($has_time_based ? 'true' : 'false') . " -->\n";

            $result = $this->evaluate_expression(
                $decoded_expression,
                $variables_and_values
            );
            $debug .= "<!-- Debug: Result = " . print_r($result, true) . " -->\n";

            return $debug . $this->format_result($result, $has_time_based && !$format_int);
        } catch (SyntaxError $e) {
            return $debug . "<!-- Error: Invalid expression " . $e . "-->";
        } catch (Exception $e) {
            return $debug . "<!--Error: " . $e->getMessage() . "-->";
        }
        return $debug . "<!--Error: Unknown-->";
    }

    /**
     * Check if variable is a dbtype variable
     *
     * @param array $variables
     * @return bool
     */
    public function is_dbtype_variable($variables)
    {
        foreach ($variables as $variable) {
            if (strpos($variable, "_dbtype")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test output function
     *
     * @return string
     */
    public function test_output()
    {
        return "WORKED";
    }

    /**
     * Check if expression has a function call
     *
     * @param string $expression
     * @param array $allowed_functions
     * @return bool
     */
    public function has_function_call($expression, $allowed_functions)
    {
        foreach ($allowed_functions as $func_name => $func_call) {
            // Check for function name followed by opening parenthesis
            if (preg_match('/\b' . preg_quote($func_name) . '\s*\(/', $expression)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle function call in expression
     *
     * @param string $input_string
     * @param array $function_array
     * @return string|null
     */
    function handle_function_call($input_string, $function_array)
    {
        foreach ($function_array as $func_name => $func_call) {
            if (preg_match("/int\s*\((.*?)\)/", $input_string)) {
                preg_match_all(
                    "/int\s*\((.*?)\)/",
                    $input_string,
                    $matches,
                    PREG_SET_ORDER
                );
                error_log("PREG_MATCH_ALL RESULT:" . print_r($matches, true));
                return $this->process_expression([0, $matches[0][1]], true);
            } else if (strpos($input_string, $func_name) !== false) {
                // Function name found in string, execute the function
                return $func_call;
            }
        }
        // No matching function found
        return null;
    }

    /**
     * Extract variables from expression
     *
     * @param string $expression
     * @return array
     */
    public function extract_variables($expression)
    {
        $variables = [];
        preg_match_all(
            '/\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/',
            $expression,
            $matches
        );
        $variables = $matches[1];

        return array_unique($variables);
    }

    /**
     * Fetch variables from database
     *
     * @param array $variables
     * @return array
     * @throws Exception
     */
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
            $exercise_type = $exercise["exercise_type"];
            $value = $this->database_manager->get_latest_result_by_exercise_id(
                $exercise_id
            );

            if ($value === null) {
                throw new Exception(
                    "Metric entry '$variable' not found for user."
                );
            }
            $variables_and_values[$variable] = is_numeric($value) ? round($value, 0) : round(strval($value, 0));
        }

        return $variables_and_values;
    }

    /**
     * Check if there's a time-based exercise
     *
     * @param array $variables_and_values
     * @return bool
     */
    public function has_time_based_exercise($variables_and_values)
    {
        foreach ($variables_and_values as $variable => $value) {
            $exercise = $this->database_manager->get_exercise_by_name(
                $variable
            );
            if ($exercise !== null && $exercise["exercise_type"] == "TIME") {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluate the expression
     *
     * @param string $expression
     * @param array $variable_values
     * @return mixed
     */
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
            return "Error: Invalid expression syntax. " . $e;
        } catch (Exception $e) {
            return "Error: Illegal expression.";
        }
    }

    public function format_int() {}

    /**
     * Format the result
     *
     * @param mixed $result
     * @param bool $is_time
     * @return string
     */
    public function format_result($result, $is_time)
    {
        if ($is_time) {
            // Format time as HH:MM:SS
            $result = round(strval($result));
            $time = new DateTime("@$result");
            $formatted_time = $time->format("H:i:s");
            // Check if the formatted time is valid
            if ($formatted_time === "00:00:00") {
                return "Invalid time";
            }
            return $formatted_time;
        }
        return is_numeric($result) ? round($result, 0) : round(strval($result, 0));
    }
}
