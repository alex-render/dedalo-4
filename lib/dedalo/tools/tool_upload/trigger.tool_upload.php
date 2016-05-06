<?php
# SESSION LIFETIME
# Set session_duration_hours before load 'config' file (override default value)
$session_duration_hours = 24;

require_once( dirname(dirname(dirname(__FILE__))) .'/config/config4.php');

if(login::is_logged()!==true) {
	$string_error = "Auth error: please login";
	print dd_error::wrap_error($string_error);
	die();
}


require_once( DEDALO_LIB_BASE_PATH . '/media_engine/class.AVObj.php');
require_once( DEDALO_LIB_BASE_PATH . '/media_engine/class.PosterFrameObj.php');
require_once( DEDALO_LIB_BASE_PATH . '/media_engine/class.Ffmpeg.php');


# set vars
$vars = array('mode','SID','quality','tipo','parent','section_tipo');
	foreach($vars as $name) $$name = common::setVar($name);


# mode
	if(empty($mode)) exit("<span class='error'> Trigger: Error Need mode..</span>");


# UPLOAD FILE GENÉRICO
if( $mode=='upload_file' ) {
	
	#dump($_REQUEST, '$_REQUEST');die();
	#trigger_error("Necesita parent!!! desde la llamada js");

	if(!$SID) 			exit('Error SID not defined (2)');
	if(!$quality) 		exit('Error: quality not defined');
	if(!$tipo) 			exit('Error: tipo not defined');
	if(!$parent) 		exit('Error: parent not defined');
	if(!$section_tipo) 	exit('Error: section_tipo not defined');

	$component_name = RecordObj_dd::get_modelo_name_by_tipo($tipo, true);
	$component_obj 	= component_common::get_instance($component_name, $tipo, $parent, 'edit', DEDALO_DATA_LANG, $section_tipo);
	$tool_upload 	= new tool_upload($component_obj);
		#dump($tool_upload,'$tool_upload');

	$response = $tool_upload->upload_file( $quality );
		#dump($response, ' response ++ '.to_string());

	# Save component for update component 'valor_list'
	$component_obj->Save();
		

	echo json_encode($response);
	exit();
}







die("Sorry. Mode ($mode) not supported")
?>