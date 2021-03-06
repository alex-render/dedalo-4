
<?php
/*
* CLASS COMPONENT_CALCULATION
*/

class component_calculation extends component_common {
	
	# GET DATO
	public function get_dato() {
		$dato = parent::get_dato();
		
		return $dato;
	}

	# SET_DATO
	public function set_dato($dato) {
		parent::set_dato( $dato );			
	}
	
	/**
	* SAVE OVERRIDE
	* Overwrite component_common method to set always lang to config:DEDALO_DATA_NOLAN before save
	*/
	public function Save() {

		# Dato candidate to save
		$dato = $this->dato;

		# Unactive
		logger_backend_activity::$enable_log = false;	# Disable logging activity and time machine # !IMPORTANT
		RecordObj_time_machine::$save_time_machine_version = false; # Disable logging activity and time machine # !IMPORTANT

		# Save		
		$result = parent::Save();
		
		# Reactivate
		logger_backend_activity::$enable_log = true;	# Disable logging activity and time machine # !IMPORTANT
		RecordObj_time_machine::$save_time_machine_version = true; # Disable logging activity and time machine # !IMPORTANT

		return $result;
	}#end Save


	/**
	* GET VALOR
	* LIST:
	* GET VALUE . DEFAULT IS GET DATO . OVERWRITE IN EVERY DIFFERENT SPECIFIC COMPONENT
	*/
	public function get_valor() {

		$valor = self::get_dato();

		if(is_array($valor)){
			$formula = $this->get_JSON_formula();
			if($formula != false){
				if(isset($formula[0]->data->separator_fields)) {
					$separator_fields = $formula[0]->data->separator_fields;
				}else{
					$separator_fields = ' | ';
				}
			}else{
				$separator_fields = ' | ';
			}
			
			#$valor = implode($separator_fields,$valor);
			$ar_values = array();
			foreach ($valor as $key => $current_value) {
				if (is_object($current_value) || is_array($current_value)) {
					$current_value = json_encode($current_value, JSON_PRETTY_PRINT);
				}
				$ar_values[] = $current_value;
			}
			$valor = implode($separator_fields, $ar_values);
		}
		

		if(SHOW_DEBUG===true) {
			if (!is_null($valor) && !is_string($valor) && !is_numeric($valor)) {
				$msg = "WARNING: CURRENT 'valor' in $this->tipo is NOT valid string. Type is:\"".gettype($valor).'" - valor:'.to_string($valor);
				trigger_error($msg);
				debug_log(__METHOD__." ".$msg, logger::WARNING);
				dump(debug_backtrace(), 'get_valor debug_backtrace() ++ '.to_string());
			}
		}
			
		if(!is_array($valor)) return $valor;

		return "<em>No string value</em>";
	}//end get_valor


	/**
	* GET_JSON_FORMULA
	* @return $propiedades->formula;
	*/
	public function get_JSON_formula() {

		$propiedades = $this->get_propiedades();

		if(empty($propiedades->formula)){
			return false;
		}else{
			return $propiedades->formula;
		}
		
	}//end get_JSON_formula

	/**
	* RESOLVE_DATA_FOR_FORMULA
	* @return 
	*/
	public function resolve_data_for_formula($data) {

		if(!isset($data)) return false;

		$data_resolved = new StdClass;

		//set the section tipo
		switch ($data->section_tipo) {
			case 'current':
				$section_tipo = $this->section_tipo;
				break;
			default:
				$section_tipo = $data->section_tipo ;
		}

		//set the section id
		switch ($data->section_id) {
			case 'current':
				$section_id = $this->parent;

				foreach ($data->component_tipo as $component_tipo) {
					$component 		= new RecordObj_dd($component_tipo);
					$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

					if($component->get_traducible() === 'no'){
						$lang = DEDALO_DATA_NOLAN;
					}else{
						$lang = DEDALO_DATA_LANG;
					}

					$current_componet = component_common::get_instance($modelo_name,
																		 $component_tipo,
																		 $section_id,
																		 'edit',
																		 $lang,
																		 $section_tipo);
					$data_resolved->$component_tipo = $current_componet->get_valor();
				}// end foreach ($data->component_tipo as $component_tipo)
				break;

			case 'all':
			/*
				$search_options_session_key = 'section_'.$this->section_tipo.$this->component_tipo;
					#dump($_SESSION['dedalo4']['config']['search_options'][$search_options_session_key], ' _SESSION[] ++ '.to_string());
				$current_options = $_SESSION['dedalo4']['config']['search_options'][$search_options_session_key];


				if (isset($_SESSION['dedalo4']['config']['sum_total'][$search_options_session_key])) {
					
					# Precalculated value
					$total = $_SESSION['dedalo4']['config']['sum_total'][$search_options_session_key];

				}else{
			*/
					foreach ($data->component_tipo as $component_tipo) {
						$component 		= new RecordObj_dd($component_tipo);
						$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

						if($component->get_traducible() === 'no'){
							$lang = DEDALO_DATA_NOLAN;
						}else{
							$lang = DEDALO_DATA_LANG;
						}

						$search_options = new StdClass;
							$search_options->section_tipo   = $section_tipo;
							$search_options->component_tipo = $component_tipo;
						
						$data_resolved->$component_tipo = $this->get_sum_from_component_tipo($search_options);
					}

					# Store for speed
					#$_SESSION['dedalo4']['config']['sum_total'][$search_options_session_key] = $total;
				#}
				break;

			case 'search_session':				

				foreach ($data->component_tipo as $component_tipo) {
						$component 		= new RecordObj_dd($component_tipo);
						$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

						if($component->get_traducible() === 'no'){
							$lang = DEDALO_DATA_NOLAN;
						}else{
							$lang = DEDALO_DATA_LANG;
						}

						$search_options = new StdClass;
							$search_options->section_tipo   = $section_tipo;
							$search_options->component_tipo = $component_tipo;							
						
						if($data->value ==='value'){
							$data_resolved->$component_tipo = $this->get_values_from_component_tipo($search_options, $data);
								#dump($data_resolved, ' data_resolved'.to_string());
						}else if($data->value ==='sum'){
							$data_resolved->$component_tipo = $this->get_sum_from_component_tipo($search_options);
						}
						
					}
				break;

			default:
				$section_id = $data->section_id;
				break;
		}


		//set the section id
		switch ($data->filter) {
			case true:
				$section_id = $this->parent;

				foreach ($data->component_tipo as $component_tipo) {

						$component 		= new RecordObj_dd($component_tipo);
						$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($component_tipo,true);

						if($component->get_traducible() === 'no'){
							$lang = DEDALO_DATA_NOLAN;
						}else{
							$lang = DEDALO_DATA_LANG;
						}

						$current_componet 	= component_common::get_instance($modelo_name,
																		$component_tipo,
																		$section_id,
																		'edit',
																		$lang,
																		$this->section_tipo);
						$ar_filter_search 	= $current_componet->get_dato();
						
						$ar_result = array();
	
						if(is_array($ar_filter_search)) foreach ($ar_filter_search as $filter_search) {
							$result = new StdClass;	
							if(empty($filter_search )){
								continue;
							}
							
							$search_base = search::get_records_data($filter_search->search_base);
							$search_table = $filter_search->search_map->search_base;
							$search_map = $filter_search->search_map;
							
							$result->search_name = $search_map->search_name;
							$result->search_layer = $search_map->search_layer;
							$result->search_type = $search_map->search_type;
							$result->data = array();

							foreach ($search_base->result as $key => $current_result) {
								foreach ($current_result as $current_key => $current_record) {
									$result_base = new StdClass;
									$result_base->$search_table= array();

									foreach ($current_record as $field => $value) {
										if($value_test = json_decode($value)){
											$value = $value_test;
										}
										
										if($field == 'section_id'){
											#$result_base->$search_table->$field= new StdClass;
											$result_base->$search_table[] = (int)$value;
										}

										if(isset($search_map->$field)){
											if(!empty($value)){
												switch ($search_map->$field->dato) {
													case 'locator':

														foreach ((array)$value as $current_locator) {
																if(isset($current_locator->section_id)){
																	$field_name = $search_map->$field->name;
																	$result_base->$field_name[] = (int)$current_locator->section_id;
																}

																if(isset($search_map->$field->subquery)){
																	$search_locator = array();
																	$subquery_index = $search_map->$field->subquery - 1;
																	$subquery = $filter_search->subqueries[$subquery_index];
																	$search_locator[] = $current_locator;
																	$subquery->filter_by_locator = $search_locator;

																 	$subquery = search::get_records_data($subquery);

																 	foreach ($subquery->result as $record) {
																 		foreach($record as $current_subquery_record){
																	 		foreach ($current_subquery_record as $key_field => $current_field_value) {
																	 			$result_subquery_final = array();
																	 			if (isset($search_map->$key_field)){
																	 				$map_field_name = $search_map->$key_field->name;
																					$current_field_value = json_decode($current_field_value);
																				
																	 				switch (isset($search_map->$key_field->action)) {
																	 					case 'convert_to_term_id':
																		 					if(!isset($current_field_value)){
																		 							continue;
																		 					}

																	 						foreach ($current_field_value as $current_result) {
																	 							$result_subquery_final[] = $current_result->section_tipo.'_'.$current_result->section_id;
																	 						}
																	 						break;
																	 					default:
																	 						$result_subquery_final = $current_field_value;
																	 						break;
																	 				}


																	 				$result_base->$map_field_name = $result_subquery_final;
																	 			}
																	 		}
																	 	}
																 	}
																}
															}
														break;

													case 'text_area':
														$map_field_name = $search_map->$field->name;
														
														if (isset($search_map->$field->action)) {
															$result_base->$map_field_name = call_user_func($search_map->$field->action,$value);
														}

													default:
														# code...
														break;
												}

												
											}
										}
									}
								$result->data[] = $result_base;
								}
							}
							$ar_result[] = $result;
						}
					}

			$data_resolved->$component_tipo = $ar_result;
			#dump($data_resolved , ' var ++ '.to_string());
		}



		// set the value of true variable
		switch (true) {
			case isset($data->true) && isset($data->true->ar_locators):

				$ar_locators = json_decode( str_replace("'", '"', $data->true->ar_locators) );
				
				$options = new stdClass();
					$options->lang 				= DEDALO_DATA_LANG;	
					$options->data_to_be_used 	= 'valor';
					$options->ar_locators 		= $ar_locators;
					$options->separator_rows 	= isset($data->true->separator_rows) ? $data->true->separator_rows : false;
					$options->separator_fields 	= isset($data->true->separator_fields) ? $data->true->separator_fields : false;

					$valor_from_ar_locators 	= $this->get_valor_from_ar_locators($options);
						#dump($valor_from_ar_locators, ' valor_from_ar_locators');$valor_from_ar_locators->result
					$data_resolved->true = $valor_from_ar_locators->result;
				break;
			case isset($data->true):
				$data_resolved->true = $data->true;
				break;
			}

		// set the value of false variable
		switch (true) {
			case isset($data->false) && isset($data->false->ar_locators):
				$ar_locators = json_decode( str_replace("'", '"', $data->false->ar_locators) );
				
				$options = new stdClass();
					$options->lang 				= DEDALO_DATA_LANG;	
					$options->data_to_be_used 	= 'valor';
					$options->ar_locators 		= $ar_locators;
					$options->separator_rows 	= isset($data->false->separator_rows) ? $data->false->separator_rows : false;
					$options->separator_fields 	= isset($data->false->separator_fields) ? $data->false->separator_fields : false;

				$valor_from_ar_locators 	= $this->get_valor_from_ar_locators($options);
						#dump($valor_from_ar_locators, ' valor_from_ar_locators');$valor_from_ar_locators->result
				$data_resolved->false = $valor_from_ar_locators->result;
				break;
			case isset($data->flase):
				$data_resolved->false = $data->true;
				break;
		}

		//set the filter
		// NEED TO BE DEFINED
		
		return $data_resolved;
	}//end resolve_data_for_formula


	/**
	* APPLY_FORMULA
	* @return 
	*/
	public function preprocess_formula() {
		$formula 	= $this->get_JSON_formula();
			//dump($formula, ' formula ++ '.to_string());
		
		foreach ($formula as $current_formula) {
			$data 		= $this->resolve_data_for_formula($current_formula->data);
			$rules 		= $current_formula->rules;
		}
		$preprocess_formula 		= new StdClass;
		$preprocess_formula->data 	= $data;
		$preprocess_formula->rules 	= $rules;

		#dump($preprocess_formula, ' preprocess_formula ++ '.to_string());

		return $preprocess_formula;	
		
	}//end apply_formula


	/**
	* GET_SUM_FROM_COMPONENT_TIPO
	* @return 
	*/
	public function get_sum_from_component_tipo__DEPECATED($search_options) {

		$options = new stdClass();
			$options->section_tipo 		= $search_options->section_tipo;
			#$options->section_real_tipo = $current_options->section_real_tipo;
			#$options->json_field 		= $current_options->json_field;
			$options->modo 				= 'list';
			$options->matrix_table 		= $search_options->matrix_table;
			#$options->limit 			= 0;	//$current_options->limit_list;
			#$options->full_count 		= false; //$current_options->full_count;
			#$options->offset 			= 0;	//$current_options->offset_list;
			$options->sql_columns 		= "a.id";
			$options->query_wrap 		 = "\n SELECT SUM( CAST( a.datos#>>'{components, $search_options->component_tipo, dato, lg-nolan}' AS REAL )) AS total";
			$options->query_wrap 		.= "\n FROM \"$search_options->matrix_table\" a";
			$options->query_wrap 		.= " WHERE a.id IN (%s);";
		
		$rows_data = search::get_records_data($options);
			#dump($rows_data, ' $rows_data ++ '.to_string());

		$total = isset($rows_data->result[0][0]['total']) ? $rows_data->result[0][0]['total'] : 0;
			#dump($total, ' total ++ '.to_string());

		return $total;		
	}//end get_sum_from_component_tipo



	/**
	* GET_SUM_FROM_COMPONENT_TIPO
	* @return 
	*/
	public function get_sum_from_component_tipo($search_options) {

		#dump($search_options, ' search_options ++ '.to_string());		

		$current_section_tipo 	= $search_options->section_tipo;
		$current_tipo 		  	= $search_options->component_tipo;	
		$modelo_name 			= RecordObj_dd::get_modelo_name_by_tipo($current_tipo,true);

		$RecordObj_dd 	= new RecordObj_dd($current_tipo);
		$traducible 	= $RecordObj_dd->get_traducible();
		$lang 			= $traducible==='si' ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN;

		# section_id filter
		$section_id_filter = '';
		if (isset($search_options->section_id)) {

			$section_id_filter = '
			{
				"q": "'.$search_options->section_id.'",
                "path": [
                    {
                        "modelo": "component_section_id"
                    }
                ],
                "component_path": [
                    "section_id"
                ]
			}
			';
		}

		$search_query_object = json_decode('{
		    "id": "sum_from_component_tipo",
		    "modo": "list",
		    "section_tipo": ["'.$current_section_tipo.'"],
		    "limit": 0,
		    "parsed" : false,
		    "filter": {
		        "$and": [
		            {
		                "q": "*",
		                "q_operator": null,
		                "path": [
		                    {
		                        "section_tipo": "'.$current_section_tipo.'",
		                        "component_tipo": "'.$current_tipo.'",
		                        "modelo": "'.$modelo_name.'",
		                        "name": "Sum",
		                        "lang": "'.$lang.'"	
		                    }
		                ]
		            }'.$section_id_filter.'
		        ]
		    },
		    "select": [
		        {
		            "path": [
		                {
		                    "section_tipo": "'.$current_section_tipo.'",
		                    "component_tipo": "'.$current_tipo.'",
		                    "modelo": "'.$modelo_name.'",
		                    "name": "Sum",
		                    "selector": "dato",
		                    "lang": "'.$lang.'"	            		
		                }
		            ]
		        }
		    ]
		}');
		#dump($search_query_object, ' $search_query_object ++ '.to_string()); exit();
		#dump(null, ' search_query_object ++ '.json_encode($search_query_object, JSON_PRETTY_PRINT)); #exit(); // , JSON_UNESCAPED_UNICODE | JSON_HEX_APOS


		# Search records
		$search_development2 = new search_development2($search_query_object);
		$search_result 		 = $search_development2->search();
		$ar_records 		 = $search_result->ar_records;

		$ar_values = [];
		foreach ($ar_records as $key => $row) {			
			$value = $row->{$current_tipo};
			$ar_values[] = (int)$value; 
		}

		$total = array_sum($ar_values);

		return $total;
	}//end get_sum_from_component_tipo



	/**
	* GET_SUM_FROM_COMPONENT_TIPO
	* @return 
	*/
	public function get_values_from_component_tipo__OLD($search_options, $data) {

		$search_sesion = $_SESSION['dedalo4']['config']['search_options']['section_'.$search_options->section_tipo];


		$options = clone $search_sesion;		

		#$options = new stdClass();
			$options->section_tipo 		= $search_options->section_tipo;			
			#$options->section_real_tipo = $current_options->section_real_tipo;
			#$options->json_field 		= $current_options->json_field;
			$options->modo 				= 'list';
			$options->matrix_table 		= $search_options->matrix_table;
			$options->limit 			= 0;	//$current_options->limit_list;
			#$options->full_count 		= false; //$current_options->full_count;
			$options->offset 			= 0;	//$current_options->offset_list;
			$options->layout_map		= array($search_options->section_tipo=>array($search_options->component_tipo));

			# Change the session key
			$options->search_options_session_key = 'calculation_'.$search_options->component_tipo;


			if(isset($data->component_filter_dato)) {

				$component_filter_dato = $data->component_filter_dato;

				foreach ($component_filter_dato as $search) {
					foreach ($search as $component_tipo => $search_value) {

						$search_name 	= $search_options->section_tipo .'_'. $component_tipo;
						$current_value 	= json_encode($search_value);

						if($options->filter_by_search===false || !property_exists($options, "filter_by_search")){
							$options->filter_by_search = new stdClass();
						}

						$options->filter_by_search->$search_name = $current_value;
							#dump($options->filter_by_search, ' options->filter_by_search'.to_string());
					}
				}					
			}

			$options->sql_columns 		= "a.id";
			$options->query_wrap 		 = "\n SELECT a.datos#>>'{components, $search_options->component_tipo, dato, lg-nolan}' AS value ";
			$options->query_wrap 		.= "\n FROM \"$search_options->matrix_table\" a";
			$options->query_wrap 		.= "\n WHERE a.id IN (%s);";
		
			#dump($options, ' options'.to_string());
		$rows_data = search::get_records_data($options);
			#dump($rows_data, ' $rows_data ++ '.to_string()); #die();

		$value = array();

		foreach ($rows_data->result as $value1) {
			foreach ($value1 as $value2) {
				$value[] = $value2['value'];
			}
		}
		#$value = isset($rows_data->result[0][0]['value']) ? $rows_data->result[0][0]['value'] : '';
			#dump($value, ' value ++ '.to_string()); 
			#die();


		return $value;		
	}//end get_sum_from_component_tipo



	/**
	* GET_VALUES_FROM_COMPONENT_TIPO
	* @return 
	*/
	public function get_values_from_component_tipo($search_options, $data) {

		#dump($search_options, ' search_options ++ '.to_string());
		#dump($data, ' data ++ '.to_string());


		$current_section_tipo = $search_options->section_tipo;
		$current_tipo 		  = $search_options->component_tipo;

		$RecordObj_dd 	= new RecordObj_dd($current_tipo);
		$traducible 	= $RecordObj_dd->get_traducible();
		$lang 			= $traducible==='si' ? DEDALO_DATA_LANG : DEDALO_DATA_NOLAN; 

		if(!isset($_SESSION['dedalo4']['config']['search_options'][$current_section_tipo])) {

			#$q_op 	 = '$and';
			#$filter_obj = new stdClass();
				#$filter_obj->{$q_op} = [];

			$search_query_object = new stdClass();
				$search_query_object->id  	   		= 'new_temp';
				$search_query_object->section_tipo 	= $current_section_tipo;
				$search_query_object->filter  		= [];
				$search_query_object->select  		= [];

		}else{

			$search_query_object  = clone $_SESSION['dedalo4']['config']['search_options'][$current_section_tipo]->search_query_object;
		}
		
			#dump($search_query_object, ' search_query_object ++ '.to_string());

		# Select
		$select = [];
		$path   = search_development2::get_query_path($current_tipo, $current_section_tipo, false);
		$element = new stdClass();
			$element->path = $path;
			$element->component_path = ["components",$current_tipo,"dato",$lang];
		
		$select[] = $element;

		$search_query_object->select 	 = $select;
		$search_query_object->limit  	 = 0;
		$search_query_object->offset 	 = 0;
		$search_query_object->parsed 	 = false;
		$search_query_object->full_count = false;		


		# Filter element optional
		if(isset($data->component_filter_dato)) {

			$q_op 	 = '$and';
			$q_op_or = '$or';
			
			if (!empty($search_query_object->filter)) {
			
				$current_filter = json_decode(json_encode($search_query_object->filter));

				$filter_obj = new stdClass();
					$filter_obj->{$q_op} = [$current_filter];
				
				$search_query_object->filter = $filter_obj;
			}			

			$component_filter_dato = $data->component_filter_dato;
			foreach ($component_filter_dato as $search) {
				foreach ($search as $current_component_tipo => $q) {

					$path    = search_development2::get_query_path($current_component_tipo, $current_section_tipo, false);
					$element = new stdClass();
						$element->path = $path;
						$element->q    = $q;					

					if (isset($search_query_object->filter->{$q_op})) {
						$search_query_object->filter->{$q_op}[] = $element;
					}else{

						$filter_element = new stdClass();
							$filter_element->{$q_op}[] = $element;

						$current_filter = json_decode(json_encode($search_query_object->filter));
						if (isset($current_filter->{$q_op_or}) && !empty($current_filter->{$q_op_or})) {
							$filter_element->{$q_op}[] = $current_filter;
						}					

						$search_query_object->filter = $filter_element;
					}					
				}
			}					
		}
		#dump($search_query_object, ' search_query_object ++ '.to_string()); #exit();
		#dump(null, ' search_query_object ++ '.json_encode($search_query_object, JSON_PRETTY_PRINT)); exit();
		
		# Search records
		$search_development2 = new search_development2($search_query_object);
		$search_result 		 = $search_development2->search();
		$ar_records 		 = $search_result->ar_records;


		$ar_values = [];
		foreach ($ar_records as $key => $row) {

			$component_dato = $row->{$current_tipo};

			$ar_values[] = $component_dato;
		}


		return $ar_values;
	}//end get_values_from_component_tipo



	/**
	* RESOLVE_QUERY_OBJECT_SQL
	* @return object $query_object
	*/
	public static function resolve_query_object_sql($query_object) {
		
    	# Always set fixed values
		$query_object->type = 'string';

		$q = $query_object->q;
		$q = pg_escape_string(stripslashes($q));

        switch (true) {
        	# IS NULL
			case ($q==='!*'):
				$operator = 'IS NULL';
				$q_clean  = '';
				$query_object->operator = $operator;
    			$query_object->q_parsed	= $q_clean;
    			$query_object->unaccent = false;

				$clone = clone($query_object);
	    			$clone->operator = '~*';
	    			$clone->q_parsed = '\'.*""\'';

				$logical_operator = '$or';
    			$new_query_json = new stdClass;    			
	    			$new_query_json->$logical_operator = [$query_object, $clone];
    			# override
    			$query_object = $new_query_json ;

				break;
			# IS NOT NULL
			case ($q==='*'):
				$operator = 'IS NOT NULL';
				$q_clean  = '';
				$query_object->operator = $operator;
    			$query_object->q_parsed	= $q_clean;
    			$query_object->unaccent = false;

				$clone = clone($query_object);
	    			//$clone->operator = '!=';
	    			$clone->operator = '!~';
	    			$clone->q_parsed = '\'.*""\'';


				$logical_operator ='$and';
    			$new_query_json = new stdClass;    			
    				$new_query_json->$logical_operator = [$query_object, $clone];    

				# override
    			$query_object = $new_query_json ;
				break;
			# IS DIFFERENT			
			case (strpos($q, '!=')===0):
				$operator = '!=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator = '!~';
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
    			$query_object->unaccent = false;
				break;
			# IS SIMILAR
			case (strpos($q, '=')===0):
				$operator = '=';
				$q_clean  = str_replace($operator, '', $q);
				$query_object->operator = '~';
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
    			$query_object->unaccent = true;
				break;
			# NOT CONTAIN
			case (strpos($q, '-')===0):
				$operator = '!~*';
				$q_clean  = str_replace('-', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;	
			# CONTAIN				
			case (substr($q, 0, 1)==='*' && substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;
			# ENDS WITH
			case (substr($q, 0, 1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'".*\'';
    			$query_object->unaccent = true;
				break;
			# BEGINS WITH
			case (substr($q, -1)==='*'):
				$operator = '~*';
				$q_clean  = str_replace('*', '', $q);
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*"'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;
			# LITERAL
			case (substr($q, 0, 1)==='\"' && substr($q, -1)==='\"'):
				$operator = '~';
				$q_clean  = str_replace('\"', '', $q);
				$query_object->operator = $operator;
				$query_object->q_parsed	= '\'.*"'.$q_clean.'".*\'';
				$query_object->unaccent = false;
				break;
			# CONTAIN
			default:
				$operator = '~*';
				$q_clean  = $q;
				$query_object->operator = $operator;
    			$query_object->q_parsed	= '\'.*".*'.$q_clean.'.*\'';
    			$query_object->unaccent = true;
				break;			
		}//end switch (true) {		
       

        return $query_object;
	}//end resolve_query_object_sql

	/**
	* SEARCH_OPERATORS_INFO
	* Return valid operators for search in current component
	* @return array $ar_operators
	*/
	public function search_operators_info() {
		
		$ar_operators = [
			'*' 	 => 'no_vacio', // not null
			'!*' 	 => 'campo_vacio', // null	
			'=' 	 => 'similar_a',
			'!=' 	 => 'distinto_de',
			'-' 	 => 'no_contiene',
			'*text*' => 'contiene',
			'text*'  => 'empieza_con',
			'*text'  => 'acaba_con',
			'"text"' => 'literal',
		];

		return $ar_operators;
	}//end search_operators_info



}
?>