 ---
  Security Audit — Course Customizer Plugin

  CRITICAL

  1. Missing CSRF Protection on Admin Forms

  File: admin/partials/course-customizer-exercises-display.php:5-35
  File: admin/partials/course-customizer-results-display.php:5-49

  Add/delete exercise and add/delete result forms have no nonce fields. Attacker can forge POST requests to add/delete exercises or results via CSRF.

  <form method="post" action="">
      <!-- NO wp_nonce_field() -->
      <input name="exercise_name" ...>

  2. No Nonce Verification on Form Submission

  File: includes/class-astcc-admin.php:115-126

  display_exercises_page() processes $_POST["add_exercise"] and $_POST["delete_exercise"] without verifying any nonce. Same for display_results_page() at line 224-240.

  if (isset($_POST["add_exercise"])) {
      // No check_admin_referer() or wp_verify_nonce()
      $exercise_name = sanitize_text_field($_POST["exercise_name"]);
      ...
  }

  3. No Capability Check on Form Processing

  File: includes/class-astcc-admin.php:115-126 and 224-240

  No current_user_can() check before adding/deleting exercises or results. Relies solely on menu page visibility — but with CSRF or direct POST, any authenticated user could trigger these.

  4. Unauthenticated AJAX Endpoints Expose Data

  File: course-customizer-php8.1.php:119,124,130,139,148

  All AJAX handlers registered with wp_ajax_nopriv_*:
  - save_quiz_results — unauthenticated users can save results (user_id = 0)
  - get_db_variables — exposes all exercise data to unauthenticated users
  - get_questions_is_time / get_questions_exercise_properties — expose internal quiz metadata
  - get_filtered_results (class-astcc-admin.php:352-355) — exposes ALL user results to unauthenticated visitors

  5. Open Redirect via redirect_url

  File: js/answer-checker-and-save.js:489-491

  window.location.href = data.data.redirect_url;

  Server returns redirect_url from wp_options — set via shortcode. If attacker can manipulate the shortcode content or stored option, they control the redirect destination for any user.

  ---
  HIGH

  6. SQL Injection Risk in add_column()

  File: includes/class-astcc-database-manager.php:215-217

  Column name and table name injected directly into ALTER TABLE statement. Not user-input today but no escaping:

  $add_column = "ALTER TABLE `{$marks_table_name}` ADD `{$column_name}` {$type_size} NULL DEFAULT {$default_sql_value};";
  $this->wpdb->query($add_column);

  7. Expression Evaluation = Code Execution Risk

  File: includes/class-astcc-expression-evaluator.php:284-308

  Symfony ExpressionLanguage evaluates user-controlled expressions extracted from page content. If any user with content editing privileges (editor role) inserts malicious expressions, they
  get evaluated server-side. The variable stripping (str_replace('$', "")) is the only protection.

  8. Sensitive Debug Info Leaked to Frontend

  File: includes/class-astcc-expression-evaluator.php:87-107

  Debug output written as HTML comments visible to anyone viewing page source:

  $debug = "<!-- Debug: Expression = " . htmlspecialchars($decoded_expression) . " -->\n";
  $debug .= "<!-- Debug: Variables = " . print_r($variables, true) . " -->\n";
  $debug .= "<!-- Debug: Values = " . print_r($variables_and_values, true) . " -->\n";

  Exposes: expression logic, variable names, database values, time-based flags.

  9. Excessive error_log() Dumping Sensitive Data

  File: course-customizer-php8.1.php:320-322

  error_log('POST data: ' . print_r($_POST, true));

  Dumps entire POST body (including nonce, quiz data) to server logs. Throughout codebase: class-astcc-quiz-handler.php:63,96,100,106,112,116,123 and many more. Potential log injection and
  information disclosure.

  ---
  MEDIUM

  10. XSS in Exercises Display

  File: admin/partials/course-customizer-exercises-display.php:57-60

  Several columns output without escaping:

  <td class="trigger inline-cell"><?php echo $exercise['min'] ?>...
  <td><?php echo $exercise['max'] ?></td>
  <td><?php echo $exercise['exercise_type'] ?></td>
  <td><?php echo $exercise['exercise_description'] ?></td>

  Should use esc_html().

  11. Shared Nonce Name Across Different Actions

  File: course-customizer-php8.1.php:184,214

  Both scripts use wp_create_nonce("my_ajax_nonce") — same nonce for validate answers, save results, get DB variables, get question properties. Single nonce leak compromises all endpoints.

  12. save_quiz_results Allows User ID 0 Writes

  File: course-customizer-php8.1.php:319

  $user_id = get_current_user_id(); // Returns 0 for unauthenticated

  No check if user is logged in. Writes result with user_id = 0 to DB for any unauthenticated visitor.

  13. Option Pollution via Shortcode

  File: includes/class-astcc-shortcodes.php:61

  update_option('quiz_completion_redirect_' . $user_id, $url);

  Every page load with shortcode creates/updates an option per user. With many users = wp_options table bloat. Should use user meta or transient.

  14. External CDN Scripts Loaded Without SRI

  File: includes/class-astcc-data-visualization.php:130
  File: admin/partials/course-customizer-reports-display.php:33-37

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/dual-listbox/dist/dual-listbox.min.js"></script>

  No Subresource Integrity (SRI) hash. CDN compromise = XSS on your site.

  15. Incomplete Format Specifiers in add_exercise()

  File: includes/class-astcc-database-manager.php:491-492

  $this->wpdb->insert($table_name, [...6 columns...], ["%s", "%d"]);

  Only 2 format specifiers for 6 columns. $wpdb->insert won't properly type-cast remaining columns.

  ---
  LOW

  16. handle_ajax_request() Lacks Capability Check

  File: includes/class-astcc-admin.php:278-306

  Returns filtered results to any authenticated user. No current_user_can() check — any subscriber can query all users' results.

  17. Schema Migration on Every Page Load

  File: course-customizer-php8.1.php:152-155

  $this->database_manager->add_min_max_columns();
  $this->database_manager->add_exercise_type_column();
  $this->database_manager->add_exercise_description_column();

  Runs ALTER TABLE checks on every request. Performance hit + unnecessary INFORMATION_SCHEMA queries.

  18. user_display_name Shortcode Returns Unescaped

  File: includes/class-astcc-shortcodes.php:67-72

  return $display_name; // No esc_html()

  If display name contains HTML, XSS via shortcode output.

  19. DOM-Based XSS in Popup

  File: js/answer-checker-and-save.js:334

  popup.textContent = message;

  Safe here (textContent), but message comes from validation which returns Hebrew strings. Currently safe but fragile pattern.

  20. Inline JavaScript in activation_notice()

  File: course-customizer-php8.1.php:240-256

  Echoes raw <script> tag. Works but bypasses WordPress script enqueue system, breaks CSP policies.

  ---
  Summary

  ┌──────────┬───────┐
  │ Severity │ Count │
  ├──────────┼───────┤
  │ Critical │ 5     │
  ├──────────┼───────┤
  │ High     │ 4     │
  ├──────────┼───────┤
  │ Medium   │ 6     │
  ├──────────┼───────┤
  │ Low      │ 5     │
  └──────────┴───────┘

  Top priorities: Add nonces + capability checks to admin forms, remove nopriv from AJAX endpoints that shouldn't be public, strip debug HTML comments from production, and add esc_html() to
  template outputs.

