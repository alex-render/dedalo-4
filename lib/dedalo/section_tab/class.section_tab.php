<?php
/*
* CLASS SECTION GROUP
*/


class section_tab extends common {
	
	
	protected $tipo;
	protected $modo;
	protected $lang;
	protected $id;		# id matrix of current section
	
	# STRUCTURE DATA
	protected $RecordObj_ts;
	protected $modelo;
	protected $norden;
	protected $label;
	
	protected $ar_tab_html;

	
	function __construct($tipo, $modo, $ar_tab_html, $id_section=NULL) {

		$this->tipo = $tipo;
		$this->modo = $modo;
		$this->lang = DEDALO_DATA_LANG;

		$this->ar_tab_html = $ar_tab_html;

		$this->id = $id_section;
		
		$this->load_structure_data();	
	}
	
	
}
?>