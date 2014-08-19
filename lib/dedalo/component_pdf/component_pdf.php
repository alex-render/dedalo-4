<?php
	
	# CONTROLLER

	$id 					= $this->get_id();
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$modo					= $this->get_modo();		
	$dato 					= $this->get_dato();
	$dato_reference_lang 	= NULL;
	$traducible 			= $this->get_traducible(); 
	$label 					= $this->get_label();			
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();	
	$permissions			= common::get_permissions($tipo); 	
	$ejemplo				= $this->get_ejemplo();
	$html_title				= "Info about $id";		
	$html_tools				= '';
	$valor					= $this->get_valor();				
	$lang					= $this->get_lang();
	$lang_name				= $this->get_lang_name();
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);


	# Verify component content record is inside section record filter
	if ($this->get_filter_authorized_record()===false) return NULL ;

	$file_name				= $modo;
	$pdf_id 				= $this->get_pdf_id();
	$quality				= $this->get_quality();
	$pdf_url				= $this->get_pdf_url();
	$file_exists 			= $this->get_file_exists();

	#$media_width 	= '97%';
	#$media_height 	= 400;

	
	switch($modo) {

		case 'edit'	:	$ar_css		= $this->get_ar_css();
						$id_wrapper = 'wrapper_'.$identificador_unico;
						
		
						# THUMB . Change temporally modo to get html
						$this->modo 		= 'thumb';
						$pdf_thumb_html 	= $this->get_html();
						
						# restore modo
						$this->modo 	= 'edit';
						break;
		

		case 'player':	break;

		case 'thumb':	# Only show pdf icon						 
						break;
		
		case 'portal_list':
		case 'list_tm':
						$file_name = 'list';
		case 'list':	
						return "under construction";
						break;

		case 'search':	return NULL;		
						break;
											
	}
	
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if (!file_exists($page_html)) {
		throw new Exception("Error Processing Request. Mode <b>$file_name</b> is not valid! (2) ", 1);		
	}
	include($page_html);
?>