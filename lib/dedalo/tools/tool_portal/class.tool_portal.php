<?php
/*
* CLASS TOOL RELATION
*/
require_once( dirname(dirname(dirname(__FILE__))) .'/config/config4.php');


class tool_portal extends tool_common {
	
	# media component
	protected $component_obj ;

	# Tag label selected in component_text_area .Received when load inspector info trigger is called like [/index-n-1]
	public $selected_tagName ;

	public $button_row ;


	
	public function __construct($component_obj, $modo='button') {
		
		# Fix modo
		$this->modo = $modo;

		# Fix current media component
		$this->component_obj = $component_obj;
	}


	
	
	

	
	













	
	
}

?>