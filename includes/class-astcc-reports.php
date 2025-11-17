<?php
namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class ASTCC_Reports 
{

  public function __construct(ASTCC_Database_Manager $database_manager = null){
    
    $this->database_manager = $database_manager ?? new ASTCC_Database_Manager();
     
    add_action( 'admin_post_download_admin_report', [ $this, 'handle_report_download' ] );
  }

  public function handle_report_download() {

          if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'download_admin_report_nonce' ) ) {
              wp_die( 'Security check failed!' );
          }

          if ( ! current_user_can( 'manage_options' ) ) { 
              wp_die( 'You do not have permission to perform this action.' );
          }

          $raw_selected_exercises = isset( $_POST['exercises'] ) ? (array) $_POST['exercises'] : [];
          $sanitized_selected_exercises = array_map('sanitize_text_field', $raw_selected_exercises);
          $selected_exercises_ids = array_map('intval', $sanitized_selected_exercises);
          $selected_exercises_db = $this->database_manager->get_exercises_by_id($selected_exercises_ids);
    
          $selected_exercises = array_column($selected_exercises_db, null, 'exercise_id');
          $selected_exercises_names = array_column($selected_exercises, "exercise_name");
          $spreadsheet = new Spreadsheet();
          $sheet= $spreadsheet->getActiveSheet();
          $sheet->setTitle( 'דו"ח סקר מתאמנים' );
          $all_results_of_selected_exercises = $this->database_manager->get_exercises_results_by_id($selected_exercises_ids);
          $flat_results = array_merge(...array_values($all_results_of_selected_exercises));
          $all_user_id_from_results = array_column($flat_results, "user_id");
          $unique_user_ids_from_result = array_unique($all_user_id_from_results);
          if ( empty( $unique_user_ids_from_result ) ) {
            wp_die( 'No results found for the selected exercises.' );
          }
          $users = get_users([
            'include' => $unique_user_ids_from_result,
            'fields'  => ['ID', 'display_name'] 
          ]);
          $names_by_id = array_column($users, 'display_name', 'ID');
          // Add headers
          $sheet->setCellValue( 'A1', '' );
          // $sheet->setCellValue('B1', 'Other Data'); // etc.
          $user_row_map = [];
          $row = 2;
          foreach($names_by_id as $user_id => $display_name){
            $sheet->setCellValue( 'A'.strval($row), $display_name);
            $user_row_map[$user_id] = $row;
            $row++;
          }
          $col = 2; // Start on col B
          foreach ( $all_results_of_selected_exercises as $exercise_id => $results ) {
              $exercise = $selected_exercises[$exercise_id];
              if($results == []) continue;
              $cell_value = (empty($exercise['exercise_description']) ? $exercise['exercise_name'] : $exercise['exercise_description']);
              $col_letter = Coordinate::stringFromColumnIndex($col);
              $sheet->setCellValue( $col_letter.'1', $cell_value);
              foreach($results as $result){
                  
                $sheet->setCellValue( $col_letter.$user_row_map[$result['user_id']], $result['result']);
              }
              $col++;

          }
          
          // (If you fetched full $report_data, you would loop through that here)

          // 5. Send Headers to Browser
          $filename = 'admin-report-' . date( 'Y-m-d_H-i-s' ) . '.xlsx';

          // Clear any previous output buffering
          if ( ob_get_contents() ) {
              ob_end_clean();
          }
          
          // Set headers to force download
          header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
          header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
          header( 'Cache-Control: max-age=0' );
          header( 'Expires: 0' );
          header( 'Pragma: public' );


          // 6. Save to 'php://output'
          // This special stream writes directly to the browser response
          $writer = new Xlsx( $spreadsheet );
          $writer->save( 'php://output' );

          // 7. Stop execution
          // This is crucial to prevent WordPress from adding any HTML footer
          exit;
      }
}

