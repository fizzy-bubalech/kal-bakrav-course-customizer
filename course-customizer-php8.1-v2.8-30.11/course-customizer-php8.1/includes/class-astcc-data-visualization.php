<?php
namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class ASTCC_Data_Visualization
{
    /**
     * Constructor for ASTCC_Data_Visualization class.
     *
     * @param ASTCC_Database_Manager|null $database_manager The database manager instance.
     * @return void
     */
    public function __construct(ASTCC_Database_Manager $database_manager = null)
    {
        $this->database_manager =
            $database_manager ?? new ASTCC_Database_Manager();
        add_shortcode("data_visualization", [$this, "display_data_graph"]);
        add_shortcode("user_results_table", [
            $this,
            "display_user_results_table",
        ]);
        add_action("wp_ajax_get_user_filtered_results", [
            $this,
            "get_user_filtered_results",
        ]);
        add_action("wp_ajax_nopriv_get_user_filtered_results", [
            $this,
            "get_user_filtered_results",
        ]);
    }

    /**
     * Display data graph for given exercises.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the data graph.
     */
    public function display_data_graph($atts)
    {
        $a = shortcode_atts(["exercises" => ""], $atts);
        $exercises = preg_split(
            "/\s*,\s*/",
            $a["exercises"],
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $data = $this->database_manager->get_exercises_results($exercises);

        // Prepare data for Chart.js
        $regular_chart_data = [];
        $time_chart_data = [];
        $colors = [
            "rgb(75, 192, 192)",
            "rgb(255, 99, 132)",
            "rgb(255, 205, 86)",
            "rgb(54, 162, 235)",
            "rgb(153, 102, 255)",
        ];

        // Calculate the start date for 12 weeks ago
        $twelve_weeks_ago = strtotime("-12 weeks");
        $current_week = date("W");

        foreach ($data as $exercise_name => $exercise_results) {
            $is_time = $exercise_results[0]["is_time"] ?? false;
            $chart_data = [];

            // Sort results by date
            usort($exercise_results, function ($a, $b) {
                return strtotime($a["result_date"]) -
                    strtotime($b["result_date"]);
            });

            foreach ($exercise_results as $result) {
                $result_time = strtotime($result["result_date"]);
                if ($result_time >= $twelve_weeks_ago) {
                    $week_number = date("W", $result_time);
                    $day_of_week = date("w", $result_time);
                    $x_value = intval($week_number) + $day_of_week / 7;

                    if ($is_time) {
                        $minutes = floor(floatval($result["result"]) / 60);
                        $seconds = fmod(floatval($result["result"]), 60);
                        $y_value = $minutes + $seconds / 100;
                    } else {
                        $y_value = floatval($result["result"]);
                    }

                    $chart_data[] = [
                        "x" => $x_value,
                        "y" => $y_value,
                    ];
                }
            }

            if ($is_time) {
                $time_chart_data[] = [
                    "label" => $exercise_name,
                    "data" => $chart_data,
                    "borderColor" =>
                        $colors[count($time_chart_data) % count($colors)],
                    "tension" => 0.1,
                ];
            } else {
                $regular_chart_data[] = [
                    "label" => $exercise_name,
                    "data" => $chart_data,
                    "borderColor" =>
                        $colors[count($regular_chart_data) % count($colors)],
                    "tension" => 0.1,
                ];
            }
        }

        $regular_chart_data_json = json_encode($regular_chart_data);
        $time_chart_data_json = json_encode($time_chart_data);

        // Add Canvas for Charts
        $output =
            '<canvas id="regularDataChart" width="400" height="200"></canvas>';
        $output .=
            '<canvas id="timeDataChart" width="400" height="200"></canvas>';

        // Add Chart.js library and initialize charts
        $output .=
            '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var regularCtx = document.getElementById("regularDataChart").getContext("2d");
            var timeCtx = document.getElementById("timeDataChart").getContext("2d");
            var regularChartData = ' .
            $regular_chart_data_json .
            ';
            var timeChartData = ' .
            $time_chart_data_json .
            ';

            new Chart(regularCtx, {
                type: "scatter",
                data: {
                    datasets: regularChartData
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: "linear",
                            ticks: {
                                stepSize: 1,
                                callback: function(value, index, values) {
                                    return "Week " + Math.floor(value);
                                }
                            },
                            title: {
                                display: true,
                                text: "Week of the Year"
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: "Result"
                            }
                        }
                    }
                }
            });

            new Chart(timeCtx, {
                type: "scatter",
                data: {
                    datasets: timeChartData
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: "linear",
                            ticks: {
                                stepSize: 1,
                                callback: function(value, index, values) {
                                    return "Week " + Math.floor(value);
                                }
                            },
                            title: {
                                display: true,
                                text: "Week of the Year"
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value, index, values) {
                                    var minutes = Math.floor(value);
                                    var seconds = Math.round((value - minutes) * 100);
                                    return minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                                }
                            },
                            title: {
                                display: true,
                                text: "Time (minutes:seconds)"
                            }
                        }
                    }
                }
            });
        });
        </script>';

        return $output;
    }

    /**
     * Display user results table.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the user results table.
     */
    public function display_user_results_table($atts)
    {
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return "<p>Please log in to view your results.</p>";
        }

        $exercises = $this->database_manager->get_all_exercises();

        wp_enqueue_script(
            "user-results-table",
            plugin_dir_url(__FILE__) . "js/user-results-table.js",
            ["jquery"],
            "1.0",
            true
        );
        wp_localize_script("user-results-table", "user_results_ajax", [
            "ajax_url" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("user_results_nonce"),
        ]);

        // Enqueue WordPress admin styles
        wp_enqueue_style("list-tables");
        wp_enqueue_style("dashicons");

        ob_start();
        ?>
            <style>
                .user-results-container {
                    margin-top: 20px;
                }
                .user-results-container .tablenav {
                    margin: 6px 0 4px;
                    height: auto;
                }
                .user-results-container .tablenav .actions {
                    padding: 0;
                }
                .user-results-container .search-box {
                    float: right;
                    margin: 0;
                }
                .user-results-container .wp-list-table {
                    border-spacing: 0;
                    width: 100%;
                    clear: both;
                }
                .user-results-container .wp-list-table td,
                .user-results-container .wp-list-table th {
                    padding: 8px 10px;
                }
                .user-results-container .wp-list-table thead th {
                    border-bottom: 1px solid #ccd0d4;
                }
                .user-results-container .wp-list-table tfoot th {
                    border-top: 1px solid #ccd0d4;
                }
                .user-results-container .wp-list-table .sorting-indicator {
                    display: none;
                    visibility: hidden;
                }
                .user-results-container .wp-list-table .sorting-indicator:before {
                    content: "\f142";
                    font: normal 20px/1 dashicons;
                    padding: 0;
                    color: #666;
                    display: inline-block;
                    vertical-align: middle;
                }
                .user-results-container .wp-list-table .sorted.asc .sorting-indicator:before {
                    content: "\f142";
                }
                .user-results-container .wp-list-table .sorted.desc .sorting-indicator:before {
                    content: "\f140";
                }
                .user-results-container .tablenav .tablenav-pages {
                                float: right;
                            }
                            .user-results-container .tablenav .tablenav-pages .pagination-links {
                                margin-left: 10px;
                            }
                            .user-results-container .tablenav .tablenav-pages .pagination-links .page-numbers {
                                padding: 0 5px;
                            }
                        </style>
                        <div class="wrap user-results-container">

                            <div class="tablenav top">
                                <!-- ... previous filter controls ... -->
                                <div class="tablenav-pages">
                                    <span class="displaying-num"></span>
                                    <span class="pagination-links">
                                        <a class="first-page button" href="#">«</a>
                                        <a class="prev-page button" href="#">‹</a>
                                        <span class="paging-input">
                                            <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                                            <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1">
                                            <span class="tablenav-paging-text"> of <span class="total-pages"></span></span>
                                        </span>
                                        <a class="next-page button" href="#">›</a>
                                        <a class="last-page button" href="#">»</a>
                                    </span>
                                </div>
                            </div>
                <table class="wp-list-table widefat fixed striped" id="user-results-table">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-exercise sortable" data-sort="exercise_name">
                                <span>Exercise</span>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th scope="col" class="manage-column column-result sortable" data-sort="result">
                                <span>Result</span>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th scope="col" class="manage-column column-date sortable" data-sort="result_date">
                                <span>Date</span>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th scope="col" class="manage-column column-metric sortable" data-sort="is_metric">
                                <span>Is Metric</span>
                                <span class="sorting-indicator"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Results will be dynamically populated here -->
                    </tbody>

                </table>
            </div>
            <?php return ob_get_clean();
    }

    /**
     * Get filtered user results via AJAX.
     *
     * @return void
     */
    public function get_user_filtered_results()
    {
        check_ajax_referer("user_results_nonce", "nonce");

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error("User not logged in");
            return;
        }

        $exercise_id = isset($_GET["exercise_id"])
            ? intval($_GET["exercise_id"])
            : null;
        $search = isset($_GET["search"])
            ? sanitize_text_field($_GET["search"])
            : "";
        $order_by = isset($_GET["order_by"])
            ? sanitize_sql_orderby($_GET["order_by"])
            : "result_date";
        $order = isset($_GET["order"])
            ? (strtoupper($_GET["order"]) === "ASC"
                ? "ASC"
                : "DESC")
            : "DESC";
        $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
        $per_page = 10;

        $result_data = $this->database_manager->get_filtered_user_results(
            $current_user_id,
            $exercise_id,
            $search,
            $order_by,
            $order,
            $page,
            $per_page
        );

        wp_send_json([
            "success" => true,
            "data" => [
                "results" => $result_data["results"],
                "total_count" => $result_data["total_count"],
            ],
            "page" => $page,
            "per_page" => $per_page,
        ]);
    }
}
