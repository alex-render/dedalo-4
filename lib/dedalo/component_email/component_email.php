<?php
	
	# CONTROLLER	
	
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$section_tipo			= $this->get_section_tipo();
	$modo					= $this->get_modo();		
	$dato 					= $this->get_dato();				
	$label 					= $this->get_label();				
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();
	$permissions			= $this->get_component_permissions();
	$ejemplo				= "example: 'user@server.org'";
	$html_title				= "Info about $tipo";
	$valor					= $this->get_valor();				
	$lang					= $this->get_lang();
	$lang_name				= $this->get_lang_name();
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);
	$valor_string			= $dato;	
	$file_name				= $modo;

	
	if($permissions===0) return null;
	
	switch($modo) {
		
		case 'edit'	:
				$propiedades	= $this->get_propiedades();
				# Verify component content record is inside section record filter
				if ($this->get_filter_authorized_record()===false) return NULL ;				
				
				$id_wrapper 	= 'wrapper_'.$identificador_unico;
				$input_name 	= "{$tipo}_{$parent}";	
				$component_info = $this->get_component_info('json');	
				break;

		case 'tool_time_machine' :			
				$id_wrapper = 'wrapper_'.$identificador_unico.'_tm';
				$input_name = "{$tipo}_{$parent}_tm";
				# Force modo
				$file_name 	= 'edit';
				break;				
						
		case 'list_tm' :
				$file_name = 'list';
						
		case 'list'	:				
				break;
						
		case 'list_of_values'	:				
				break;

		case 'relation'	:
				# Force modo list
				$file_name 	= 'list';				
				break;	
		
		case 'search':
				# dato is injected by trigger search wen is needed
				$dato = isset($this->dato) ? $this->dato : null;
				
				$ar_comparison_operators = $this->build_search_comparison_operators();
				$ar_logical_operators 	 = $this->build_search_logical_operators();

				# Search input name (var search_input_name is injected in search -> records_search_list.phtml)
				# and recovered in component_common->get_search_input_name()
				# Normally is section_tipo + component_tipo, but when in portal can be portal_tipo + section_tipo + component_tipo
				$search_input_name = $this->get_search_input_name();	
				break;
						
		case 'simple':				
				break;
				
		case 'print':				
				break;				
	}
	
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->modo</div>";
	}
?>