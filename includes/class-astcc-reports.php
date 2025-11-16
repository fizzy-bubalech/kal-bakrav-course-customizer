<?php
namespace CourseCustomizer;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class ASTCC_Reports 
{

  public function __construct(ASTCC_Database_Manager $database_manager = null){
    
    $this->database_manager = $database_manager ?? new ASTCC_Database_Manager();
     
  }

}

