<?php
	
	# CONTROLLER
	
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$section_tipo			= $this->get_section_tipo();
	$modo					= $this->get_modo();	
	$label 					= $this->get_label();
	$required				= $this->get_required();
	$propiedades			= $this->get_propiedades();
	$debugger				= $this->get_debugger();
	$permissions			= $this->get_component_permissions();
	$ejemplo				= NULL;
	$html_title				= "Info about $tipo";
	$ar_tools_obj			= $this->get_ar_tools_obj();
	$dato 					= $this->get_dato(); // !!
	$valor_string			= $dato;
	$lang					= $this->get_lang();
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);

	if($permissions===0) return null;
	
	# Verify component content record is inside section record filter
	if ($this->get_filter_authorized_record()===false) return NULL;

	$file_name				= $modo;
	$from_modo				= $modo;
	
	switch($modo) {		

		case 'edit_in_list':

			// Fix always edit as modo / filename
			$modo 			= 'portal_list';
			if (empty($dato)) {
				$file_name	= 'edit';
			}else{
				$file_name	= 'list';
			}

			$wrap_style 	= '';
			// Dont break here. Continue as modo edit		

		case 'edit'	:
		case 'tool_description':

			if ($modo==='tool_description') {
				$file_name	= 'edit';
			}
			
			# Custom propiedades external dato 
			if(isset($propiedades->source->mode) && $propiedades->source->mode==='external'){
				$this->set_dato_external(false);	// Forces update dato with calculated external dato
			}	

			$ar_target_section_tipo 	 = $this->get_ar_target_section_tipo();
			$ar_target_section_tipo_json = json_encode($ar_target_section_tipo);
			
			$tipo_to_search			= $this->get_tipo_to_search();
			$ar_valor 				= $this->get_valor($lang,'array');				
			#$valor  				= implode('<br>',$ar_valor);
			$ar_labels = array_map(function($element){
				return $element->label;
			},$ar_valor);				
			$valor 					= implode('<br>', $ar_labels);

		
			$id_wrapper 			= 'wrapper_'.$identificador_unico;
			$input_name 			= "{$tipo}_{$parent}";
			$component_info 		= $this->get_component_info('json');
			$dato_json 				= json_handler::encode($dato);
				#dump($dato_json, ' dato_json ++ '.to_string());

			#get the change modo from portal list to edit
			$from_modo_requested = common::get_request_var('from_modo');
			if ($from_modo_requested!==false) {
				$from_modo = $from_modo_requested;
			}

			#
			# SEMANTIC NODES
			$semantic_nodes = $this->get_semantic_nodes();
			if ( !empty($this->semantic_nodes) ) {
				# JS/CSS ADD
				js::$ar_url[]  = DEDALO_LIB_BASE_URL."/tools/tool_semantic_nodes/js/tool_semantic_nodes.js";
				css::$ar_url[] = DEDALO_LIB_BASE_URL."/tools/tool_semantic_nodes/css/tool_semantic_nodes.css";
			}

			/*$in_time_machine =  (isset($_REQUEST['m']) && $_REQUEST['m']=='tool_time_machine') || 
								(isset($_REQUEST['mode']) && safe_xss($_REQUEST['mode'])==='load_preview_component') ? true : false;

			*/

			#get the change in_time_machine
			$var_requested_m 	= common::get_request_var('m');
			$var_requested 		= common::get_request_var('mode');
			$in_time_machine =  (!empty($var_requested_m) && $var_requested_m==='tool_time_machine') || 
								(!empty($var_requested) && $var_requested==='load_preview_component') ? true : false;


			# FIlTER_BY_LIST (Propiedades option)
			$filter_by_list = false; // Default
			if (isset($propiedades->source->filter_by_list)) {
				$filter_by_list = $propiedades->source->filter_by_list;
			}
			$json_filter_by_list = json_encode($filter_by_list);

			#$referenced_tipo = $this->get_referenced_tipo();
			
			# SEARCH_FIELDS
			$search_fields 		= $this->get_search_fields($tipo);
				#dump($search_fields, ' $search_fields ++ '.to_string($tipo));
			$search_fields_json = json_encode($search_fields);
								
			#$terminoID_valor = reset($fields); // Select first field tipo

			# Limit
			$limit = isset($propiedades->limit) ? (int)$propiedades->limit : 0;
			
			# Divisor
			$divisor = $this->get_divisor();

			# SEARCH_QUERY_OBJECT
			$query_object_options = new stdClass();
				$query_object_options->q 	 			= null;
				$query_object_options->limit  			= 40;
				$query_object_options->offset 			= 0;
				$query_object_options->section_tipo 	= $section_tipo;
			$search_query_object 		= $this->build_search_query_object($query_object_options);
				# skip_projects_filter true on edit mode
				$search_query_object->skip_projects_filter 	= true;
			$json_search_query_object 	= json_encode( $search_query_object, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS );
				#dump($search_query_object, ' search_query_object ++ '.to_string());
				#dump(json_encode( $search_query_object, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), '$search_query_object ++ '.to_string());

			#$filter_by_list_component_tipo = isset($propiedades->source->filter_by_list->component_tipo) ? $propiedades->source->filter_by_list->component_tipo : null;
				#dump($filter_by_list_component_tipo, ' filter_by_list_component_tipo ++ '.to_string($propiedades));
			
			#
			# DATAFRAME MANAGER	
			$ar_dataframe_obj = array();
			$ar_dataframe = isset($propiedades->dataframe) ? $propiedades->dataframe : false;
			if($ar_dataframe!==false){
				foreach ($dato as $key => $value) {
					foreach ($ar_dataframe as $current_dataframe) {
						if ($current_dataframe->tipo!==false) {
							$dataframe_obj = new dataframe($current_dataframe->tipo, $current_dataframe->type, $this, 'dataframe_edit', $key);
							$ar_dataframe_obj[] = $dataframe_obj;
							//dump($ar_dataframe_obj[0]->get_html(), ' $ar_dataframe_obj[$i]->get_html(); ++ '.to_string());
						}	
					}
				}
			}
			break;

		case 'tool_time_machine' :	
			return null;
			$id_wrapper = 'wrapper_'.$identificador_unico.'_tm';
			$input_name = "{$tipo}_{$parent}_tm";
			$file_name 	= 'edit';
			break;	
					
		case 'search':
				# dato is injected by trigger search wen is needed
				$dato = isset($this->dato) ? $this->dato : [];

				# Limit
				$limit = 1;

				$component_info 	= $this->get_component_info('json');
				$in_time_machine 	= false;

				# search_query_object
				$query_object_options = new stdClass();
					$query_object_options->q 	  				= null;
					$query_object_options->limit  				= 40;
					$query_object_options->offset 				= 0;
					$query_object_options->skip_projects_filter = false; // false in search mode
				$search_query_object 		= $this->build_search_query_object($query_object_options);
				$json_search_query_object 	= json_encode( $search_query_object, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS);

				# FIlTER_BY_LIST (Propiedades option)
				$filter_by_list = false; // Default
				if (isset($propiedades->source->filter_by_list)) {
					$filter_by_list = $propiedades->source->filter_by_list;
				}
				$json_filter_by_list = json_encode($filter_by_list);
				
				$referenced_tipo			 	= $this->get_referenced_tipo();
				#$ar_list_of_values			 	= $this->get_ar_list_of_values(DEDALO_DATA_LANG, null); // $this->get_ar_list_of_values( $lang, null, $this->ar_referenced_section_tipo, $filter_custom );
				$ar_valor 						= $this->get_valor($lang,'array');	
				#$valor  						= implode('<br>',$ar_valor);
				$ar_labels = array_map(function($element){
					return $element->label;
				},$ar_valor);				
				$valor 							= implode('<br>', $ar_labels);				

				$id_wrapper 				 	= 'wrapper_'.$identificador_unico;
				$ar_target_section_tipo 	 	= $this->get_ar_target_section_tipo();		
				$ar_target_section_tipo_json 	= json_encode($ar_target_section_tipo);
				$tipo_to_search					= $this->get_tipo_to_search();
				$dato_json 						= json_handler::encode($dato);

				//$ar_comparison_operators 	 	= $this->build_search_comparison_operators();
				//$ar_logical_operators 	 	 	= $this->build_search_logical_operators();

				# q_operator is injected by trigger search2
				$q_operator = isset($this->q_operator) ? $this->q_operator : null;

				# Search input name (var search_input_name is injected in search -> records_search_list.phtml)
				# and recovered in component_common->get_search_input_name()
				# Normally is section_tipo + component_tipo, but when in portal can be portal_tipo + section_tipo + component_tipo
				$search_input_name = $this->get_search_input_name();
					#dump($search_input_name, ' $search_input_name ++ '.to_string());

				# FIlTER_BY_LIST (Propiedades option)
				$filter_by_list = false; // Default
				if (isset($propiedades->source->filter_by_list)) {
					$filter_by_list = $propiedades->source->filter_by_list;
				}

				# SEARCH_FIELDS
				$search_fields 		= $this->get_search_fields($tipo);
				$search_fields_json = json_encode($search_fields);
				
				# Divisor
				$divisor = $this->get_divisor();
				break;

		case 'dummy' :
			$file_name = 'search';
			break;

		case 'list_tm' :
			$file_name = 'list';
		case 'portal_list':
			$id_wrapper					 = 'wrapper_'.$identificador_unico;
			$tipo_to_search				 = $this->get_tipo_to_search();	
			$ar_target_section_tipo 	 = $this->get_ar_target_section_tipo();
			$ar_target_section_tipo_json = json_encode($ar_target_section_tipo);

			$dato_json 					 = json_handler::encode($dato);
			$component_info 			 = $this->get_component_info('json');			

			$valor		= $this->get_valor($lang,'string');
			$ar_valor	= $this->get_valor($lang,'array');

			$file_name 	= 'list';
			break;
				
		case 'list'	:
			# Return direct value for store in 'valor_list'				
			$valor = $this->get_valor($lang,'string');
			echo (string)$valor; 	# Like "Catarroja, L'Horta Sud, Valencia/València, Comunidad Valenciana, España"
			return; // NOT load html file
			break;

		case 'relation':
			return NULL;
			# Force file_name to 'list'
			$file_name  = 'list';
			break;

		case 'edit_content':
			$ar_valor = [];

			$file_name = 'edit';
			break;

		case 'tool_description9999':
	
			// Fix always edit as modo / filename
			if (empty($dato)) {
				
				$this->set_modo('edit');
				$html = $this->get_html();
				echo $html;			
				return; // Stop here

			}else{
				$file_name	= $modo;
			}
	
			$id_wrapper 					= 'wrapper_'.$identificador_unico;
			$ar_related_component_tipo 		= common::get_ar_related_by_model('component_',$tipo,false);
			$ar_target_section_tipo 	 	= $this->get_ar_target_section_tipo();
			$ar_target_section_tipo_json 	= json_encode($ar_target_section_tipo);
			# dump($ar_related_component_tipo, ' ar_related_component_tipo ++ '.to_string());

			$tipo_to_search					= $this->get_tipo_to_search();
			$dato_json 						= json_handler::encode($dato);
			$component_info 			 	= $this->get_component_info('json');
				
			break;

		case 'print' :
			$valor = $this->get_valor($lang,'string');
			break;

	}
	
		
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if( !include($page_html) ) {
		echo "<div class=\"error\">Invalid mode $this->modo</div>";
	}
?>