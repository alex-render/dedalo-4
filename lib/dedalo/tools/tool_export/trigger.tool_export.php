<?php
$start_time=microtime(1);
set_time_limit ( 259200 );  // 3 dias
$session_duration_hours = 72;
include( dirname(dirname(dirname(__FILE__))) .'/config/config4.php');
# TRIGGER_MANAGER. Add trigger_manager to receive and parse requested data
common::trigger_manager();



/**
* EXPORT_DATA
*
*/
function export_data($json_data) {
	global $start_time;

	# Write session to unlock session file
	session_write_close();

	$response = new stdClass();
		$response->result 	= false;
		$response->msg 		= 'Error. Request failed ['.__FUNCTION__.']';

	$vars = array('section_tipo','columns','encoding','data_format');
		foreach($vars as $name) {
			$$name = common::setVarData($name, $json_data);
			# DATA VERIFY
			#if ($name==='top_tipo' || $name==='top_id') continue; # Skip non mandatory
			if (empty($$name)) {
				$response->msg = 'Trigger Error: ('.__FUNCTION__.') Empty '.$name.' (is mandatory)';
				return $response;
			}
		}		

	$tool_export  = new tool_export($section_tipo,'edit',$data_format);

	$layout_map = tool_export::columns_to_layout_map($columns, $section_tipo);
		#dump($layout_map, ' $layout_map ++ '.to_string($columns));
		if (empty($layout_map)) {			
			$response->msg = "Error: layout_map is empty";
			return $response;
		}

	// Get records to export
	$records = $tool_export->get_records( $layout_map );
		#dump($records, ' records ++ '.to_string());

	// Result parsed as final string
	$result_string = $tool_export->export_to('csv', $records, $encoding);
		#dump($result, ' result ++ '.to_string());

	// Write result to file (UTF8)
	$write_result = $tool_export->write_result( $result_string );
		#dump($write_result, ' write_result ++ '.to_string());
	
	
	if ($write_result->result===true ) {

		# Build excel version (ISO-8859-1)
		// Write result to file (excel ISO-8859-1)
		$change_encodig_to_ISO  = $tool_export->change_encoding_from_uft8($result_string,'ISO-8859-1');
		$write_result_ISO 		= $tool_export->write_result($change_encodig_to_ISO, 'excel_');

		#
		# GET CSV FILE AS TABLE
		$table = tool_export::read_csv_file_as_table( $write_result->path, true, null, false );		 
		
		$response->result 	= true;						// E.g. 'ok'
		$response->msg 		= $write_result->msg;		// E.g. 'Exported successfully'
		$response->url 		= $write_result->url; 		// E.g. 'http://mydomain/path/file.csv'
		$response->url_excel= $write_result_ISO->url; 	// E.g. 'http://mydomain/path/excel_file.csv'
		$response->table 	= $table; 					// Table is created reading exported file

	}else{

		$response->msg 		= 'Error on write file: '.$write_result->msg;		
	}

	# Debug
	if(SHOW_DEBUG===true) {
		$debug = new stdClass();
			$debug->exec_time	= exec_time_unit($start_time,'ms')." ms";
			foreach($vars as $name) {
				$debug->{$name} = $$name;
			}

		$response->debug = $debug;
	}


	return (object)$response;
}//end export_data



?>