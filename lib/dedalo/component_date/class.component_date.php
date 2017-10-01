<?php
/*
* CLASS COMPONENT DATE
 Encargado de guardar y gestionar las fechas de tipo absoluto, como por ejemplo '2012-11-07 17:33:49' that have a json format:
 {
	*    "year": 2012,
	*    "month": 11,
	*    "day": 07,
	*    "hour": 17,
	*    "minute": 33,
	*    "second": 49
	 }
 Debe verificar el formato antes de guardar y a la hora de mostrarse, además de proporcionar la lógica de las búsquedas para localizar años, rangos, etc..
 Podría incorporar un calendario desplegable para seleccionar la fecha de forma normalizada..
*/
class component_date extends component_common {
	
	# Overwrite __construct var lang passed in this component
	protected $lang = DEDALO_DATA_NOLAN;


	# American data format
	public static $ar_american = array('lg-eng','lg-angl','lg-ango','lg-meng');


	
	/**
	* __CONSTRUCT
	*/
	function __construct($tipo=null, $parent=null, $modo='edit', $lang=DEDALO_DATA_NOLAN, $section_tipo=null) {
		
		# Force always DEDALO_DATA_NOLAN
		$lang = $this->lang;

		# Creamos el componente normalmente
		parent::__construct($tipo, $parent, $modo, $lang, $section_tipo);

		if(SHOW_DEBUG===true) {
			if ($this->RecordObj_dd->get_traducible()==='si') {
				throw new Exception("Error Processing Request. Wrong component lang definition. This component $tipo (".get_class().") is not 'traducible'. Please fix this ASAP", 1);				
			}
		}
	}//end __construct



	/**
	* SAVE OVERRIDE
	* Overwrite component_common method 
	*/
	public function Save() {
			
		# Dato
		$dato = $this->dato;

		//dump(!is_array($dato), ' is array ++ '.to_string());
		# DELETING DATE
		if (empty($dato)) {
			# Salvamos de forma estándar un valor vacío
			return parent::Save();
		}

		# DATO FORMAT VERIFY
		if ( !is_array($dato) ) {
			if(SHOW_DEBUG===true) {
				#dump($dato,'$dato');
				#throw new Exception("Dato is not string!", 1);
				error_log("Bad date format:".to_string($dato));
			}
			return false;
		}

		# add_time to dato always
		foreach ($dato as $key => $current_dato) {
			$this->dato[$key] = self::add_time( $current_dato );
		}

			
		# From here, save normally
		return parent::Save();
	}//end Save



	/**
	* GET_DATO
	* Dato change to object with year, month, day, hour, minute, second separated in key->value like
	* {
	*    "year": -500000,
	*    "month": 10,
	*    "day": 3,
	*    "hour": 19,
	*    "minute": 56,
	*    "second": 43
	* }
	*/
	public function get_dato() {
		
		$dato = parent::get_dato();
		
		# Compatibility with version 4.0.14 to 4.6 dedalo instalations
		if (is_object($dato) && !empty(get_object_vars($dato)) ) {
			$safe_dato=array();
			
			$safe_dato[] = $dato;
			
			$dato = $safe_dato;
			$this->set_dato($dato);
			$this->Save();
		}

		if(SHOW_DEBUG===true) {
			if ( !is_null($dato) && !is_array($dato)  ) {
				#dump( $dato, "WRONG TYPE of dato. tipo: $this->tipo - section_tipo: $this->section_tipo - section_id: $this->parent");
			}
		}
		return (array)$dato;

		# Compatibility old dedalo instalations before 4.014
		/*if (is_string($dato)) {
			$dd_date    = new dd_date();
			$this->dato = (object)$dd_date->get_date_from_timestamp( $dato );
			$this->Save();
			$dato = parent::get_dato();
		}*/
		#dump( $dato, ' dato get_dato ++ '.to_string());

	}//end get_dato



	/**
	* SET_DATO
	*/
	public function set_dato( $dato ) {

		if (is_string($dato)) {
			$dato = json_decode($dato);
		}
		if (is_null($dato) || empty($dato)) {
			$dato = array();
		}

		# Compatibility with version 4.0.14 to 4.7 dedalo instalations
		if (is_object($dato) && !empty(get_object_vars($dato)) ) {
			$safe_dato		= array();
			$safe_dato[] 	= $dato;
			$dato 			= $safe_dato;
		}
		

		# Remove empy objects
		$clean_dato = array(); 
		foreach ((array)$dato as $key => $value_obj) {
			$ar_vars = get_object_vars($value_obj);
				//dump($ar_vars, ' ar_vars ++ '.to_string());
			if(!empty($ar_vars)) {
				$clean_dato[] = $value_obj;
			}
		}

		#debug_log(__METHOD__." dato ".to_string($dato), logger::DEBUG);
		parent::set_dato( (array)$clean_dato );
	}//end set_dato



	/**
	* GET_DATE_MODE
	* Calculate date_mode from format of current 'dato'
	* @return string
	*/
	public function get_date_mode() {

		$propiedades = $this->get_propiedades();

		if (isset($propiedades->date_mode)) {
					$date_mode = $propiedades->date_mode; // Default from structure if is defined
				}else{
					$date_mode = 'date'; // Default
		}

		/*
		$dato 		 = $this->get_dato();
		switch (true) {
			#case isset($dato->start):
			case is_object($dato) && property_exists($dato, 'start'):
				$date_mode = 'range';
				break;
			#case isset($dato->period):
			case is_object($dato) && property_exists($dato, 'period'):
				$date_mode = 'period';
				break;			
			default:
				if (isset($propiedades->date_mode)) {
					$date_mode = $propiedades->date_mode; // Default from structure if is defined
				}else{
					$date_mode = 'date'; // Default
				}
				break;
		}*/
		return $date_mode;
	}//end get_date_mode



	/**
	* GET_DATE_NOW
	* Get current full date (with hours, minutes and seconds) as dd_date object
	* @return object dd_date 
	*/
	public static function get_date_now() {
		
		$date = new DateTime();

		$dato = new dd_date();
			$dato->set_year( 	$date->format('Y') );	// 	 $date->format('Y-m-d H:i:s'); # Default as DB format 
			$dato->set_month( 	$date->format('m') );
			$dato->set_day( 	$date->format('d') );
			$dato->set_hour( 	$date->format('H') );
			$dato->set_minute( 	$date->format('i') );
			$dato->set_second( 	$date->format('s') );

		return (object)$dato;
	}//end get_date_now	



	/**
	* GET VALOR (Ojo: Se usa para ordenar, por lo que mantiene el formato DB. Para visualizar usar 'get_valor_local()')
	* Dato formated as timestamp '2012-11-07 17:33:49'
	*/
	public function get_valor() {

		#$previous_modo = $this->get_modo();
		#$this->set_modo('list'); // Force list mode
		#$valor = $this->get_html();
		# Restore modo after 
		#$this->set_modo($previous_modo);

		$ar_dato 		= $this->get_dato();
		$propiedades	= $this->get_propiedades();
		$ar_valor		= array();	
		$valor			= '';	
		$date_mode 		= $this->get_date_mode();
		foreach ($ar_dato as $key => $current_dato) {
			$ar_valor[$key] ='';
			switch ($date_mode) {

				case 'range':
					# Start
					$valor_start = '';
					if(isset($current_dato->start)) {
						$dd_date	= new dd_date($current_dato->start);
						/*
						$valor_start= isset($propiedades->method->get_valor_local) 
									? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
									: component_date::get_valor_local( $dd_date, false );
									*/
						if(isset($current_dato->start->day)) {
							$valor_start = $dd_date->get_dd_timestamp("Y-m-d");
						}else{
							$valor_start = $dd_date->get_dd_timestamp("Y-m");
							if(isset($current_dato->start->month)) {
							}else{
								$valor_start = $dd_date->get_dd_timestamp("Y");
							}
						}
						
						$ar_valor[$key] .= $valor_start;
					}

					# End
					$valor_end = '';
					if(isset($current_dato->end)) {
						$dd_date	= new dd_date($current_dato->end);
						/*
						$valor_end 	= isset($propiedades->method->get_valor_local) 
									? component_date::get_valor_local( $dd_date, reset($propiedades->method->get_valor_local) ) 
									: component_date::get_valor_local( $dd_date, false );
						*/

						if(isset($current_dato->end->day)) {
								$valor_end = $dd_date->get_dd_timestamp("Y-m-d");
							}else{
								if(isset($current_dato->end->month)) {
									$valor_end = $dd_date->get_dd_timestamp("Y-m");
								}else{
									$valor_end = $dd_date->get_dd_timestamp("Y");
								}
							}
						$ar_valor[$key] .= ' <> '. $valor_end;
					}
					#$valor .= $valor_start .' <> '. $valor_end;
					break;

				case 'period':
					$valor_year	= $valor_month = $valor_day = '';
					if(!empty($current_dato->period)) {
						$dd_date = new dd_date($current_dato->period);
						# Year
						$valor_year	= isset($dd_date->year) ? $dd_date->year : '';
						# Month
						$valor_month= isset($dd_date->month) ? $dd_date->month : '';
						# Day
						$valor_day	= isset($dd_date->day) ? $dd_date->day : '';
					}
					if(!empty($valor_year)) {
						$ar_valor[$key] .= $valor_year;
					}
					if(!empty($valor_month)) {
						$ar_valor[$key] .= '-'.$valor_month;
					}
					if(!empty($valor_day)) {
						$ar_valor[$key] .= '-'.$valor_day;
					}
					break;

				case 'date':
				default:
					if(!empty($current_dato)) {
						$dd_date		= new dd_date($current_dato);
						$ar_valor[$key] = $dd_date->get_dd_timestamp("Y-m-d");
					}
					break;
			}
		}
		$valor = implode('|',$ar_valor);
		return (string)$valor;
	}//end get_valor



	/**
	* GET VALOR LOCAL
	* Convert internal dato formated as timestamp '2012-11-07 17:33:49' to current lang data format like '07-11-2012 17:33:49'
	*/
	public static function get_valor_local( $dd_date, $full=false ) {
		$valor_local= '';
		$separator  = dd_date::$separator;

		switch (true) {
			case (empty($dd_date->month) && empty($dd_date->day) ):
				$date_format	= "Y";
				break;
			case ( empty($dd_date->day) && !empty($dd_date->month) ):
				$date_format	= "m{$separator}Y";
				break;
			default:
				$date_format	= "d{$separator}m{$separator}Y";
				break;
		}
		#$date_format	= "d-m-Y";	# TODO: change order when use english lang ?? ...
		$valor_local 	= $dd_date->get_dd_timestamp($date_format, $padding=false);
			#dump($valor_local, ' valor_local ++ '.to_string());		
		#debug_log(__METHOD__." valor_local: $valor_local ".to_string($valor_local), logger::WARNING);


		return (string)$valor_local;
	}//end get_valor_local


	/**
	* GET_VALOR_EXPORT
	* Return component value sended to export data
	* @return string $valor
	*/
	public function get_valor_export( $valor=null, $lang=DEDALO_DATA_LANG, $quotes, $add_id ) {
		
		if (is_null($valor)) {
			$dato = $this->get_dato();				// Get dato from DB
		}else{
			$this->set_dato( json_decode($valor) );	// Use parsed json string as dato
		}

		$valor = $this->get_valor($lang);
		#$valor = strip_tags($valor); // Removes the span tag used in list mode
		/*
		$previous_modo = $this->get_modo();
		$this->set_modo('list'); // Force list mode
		$valor = $this->get_html();
		# Restore modo after 
		$this->set_modo($previous_modo);
		*/
		if(SHOW_DEBUG===true) {
			#return "DATE: ".$valor;
		}
		return (string)$valor;
	}//end get_valor_export



	/**
	* GET_DATO_AS_TIMESTAMP
	* Get current component dato and create a standar timestamp string
	* using dd_date class call
	* DEPRECATED 22-08-2017
	* @return string $timestamp
	*/
	public function get_dato_as_timestamp_DEPRECATED() {
		$dato 	 	= $this->get_dato();
		$dd_date 	= new dd_date($dato);
		$timestamp 	= $dd_date->get_dd_timestamp(); // $date_format="Y-m-d H:i:s"

		return (string)$timestamp;
	}//end get_dato_as_timestamp



	/**
	* GET TIMESTAMP
	* @return current time formated for saved to SQL timestamp field
	*	like 2013-01-22 22:33:29 ('Y-m-d H:i:s')
	*	DateTime is avaliable for PHP >=5.3.0
	*/
	public static function get_timestamp_now_for_db( $offset=null ) {

		$date = new DateTime();

		switch (true) {

			case !empty($offset) :

				$offset_key 	= key($offset);
				$offset_value 	= $offset[$offset_key];
				$date->$offset_key(new DateInterval($offset_value));		# Formated like: P10D (10 days)
				$timestamp = $date->format('Y-m-d H:i:s'); 	# Default as DB format 
				break;
			
			default:
				$timestamp 	= $date->format('Y-m-d H:i:s'); # Default as DB format 
				break;
		}
		#dump($timestamp,'$timestamp ');

		return $timestamp;
	}//end get_timestamp_now_for_db



	/**
	* TIMESTAMP TO EUROPEAN DATE
	* @param $timestamp
	* @param $seconds (default false)
	* Convert DB timestamp to date (American or European date) like '2013-04-23 19:47:05' to 23-04-2013 19:47:05 
	*/
	public static function timestamp_to_date($timestamp, $full=true) {

		if (empty($timestamp) || strlen($timestamp)<10) {
			return null;
		}

		$ano  	= substr($timestamp, 0, 4);
		$mes 	= substr($timestamp, 5, 2);
		$dia   	= substr($timestamp, 8, 2);
		$hora 	= substr($timestamp, 11, 2);
		$min 	= substr($timestamp, 14, 2);
		$sec 	= substr($timestamp, 17, 2);
		/*
		if (in_array(DEDALO_APPLICATION_LANG, self::$ar_american)) {
			# American format month/day/year
			$date	= $mes . '-' .$dia . '-' .$ano ;
		}else{
			# European format day.month.year
			$date	= $dia . '-' .$mes . '-' .$ano ;
		}
		*/
		$date	= $dia . '-' .$mes . '-' .$ano ;

		if($full===true) {
			$date	.= ' ' .$hora . ':' .$min . ':' .$sec ;			
		}
				
		return $date;
	}//end timestamp_to_date
	


	/**
	* GET_EJEMPLO
	*/
	protected function get_ejemplo() {
		/*
		if (in_array(DEDALO_APPLICATION_LANG, self::$ar_american)) {
			# American format month/day/year
			$format = 'MM-DD-YYYY';
		}else{
			# European format day.month.year
			$format = 'DD-MM-YYYY';
		}
		*/
		$format = 'DD'.dd_date::$separator.'MM'.dd_date::$separator.'YYYY';
		return $format;
	}//end get_ejemplo



	/**
	* GET_STATS_VALUE_RESOLVED
	*/
	public static function get_stats_value_resolved( $tipo, $current_stats_value, $stats_model ,$stats_propiedades=NULL ) {
		
		$caller_component = get_called_class();

			#dump($stats_propiedades,'stats_propiedades '.$caller_component);

		#if($caller_component=='component_autocomplete_ts') 		
		#dump($current_stats_value ,'$current_stats_value '.$tipo ." $caller_component");

		foreach ($current_stats_value as $current_dato => $value) {

			# PROPIEDADES 'year_only' : Return only year as '1997'
			if($stats_propiedades->context_name==='year_only') {
				$current_dato = date("Y", strtotime($current_dato));
			}
			
			if( empty($current_dato) ) {

				$current_dato = 'nd';
				$ar_final[$current_dato] = $value;

			}else if($current_dato==='nd') {

				$ar_final[$current_dato] = $value;

			}else{

				$current_component = component_common::get_instance($caller_component,$tipo,NULL,'stats');
				$current_component->set_dato($current_dato);

				$valor = $current_component->get_valor();
					#dump($valor,'valor '.$caller_component. " - current_dato:$current_dato");

				$ar_final[$valor] = $value;
			}			

		}//end foreach
		
		$label 		= RecordObj_dd::get_termino_by_tipo( $tipo ).':'.$stats_model;
		$ar_final 	= array($label => $ar_final );
			#dump($ar_final,'$ar_final '.$caller_component . " ".print_r($current_stats_value,true));
		
		return $ar_final;
	}//end get_stats_value_resolved



	/*
	* GET_METHOD
	* Return the result of the method calculation into the component 
	*/
	public function get_method( $param ){
		switch ($param) {
			case 'Today':
				//return self::get_timestamp_now_for_db();
				return self::get_date_now();
				break;
			
			default:
				return false;
				break;
		}
	}//end get_method



	/**
	* GET_SEARCH_QUERY
	* Build search query for current component . Overwrite for different needs in other components 
	* (is static to enable direct call from section_records without construct component)
	* Params
	* @param string $json_field . JSON container column Like 'dato'
	* @param string $search_tipo . Component tipo Like 'dd421'
	* @param string $tipo_de_dato_search . Component dato container Like 'dato' or 'valor'
	* @param string $current_lang . Component dato lang container Like 'lg-spa' or 'lg-nolan'
	* @param string $search_value . Value received from search form request Like 'paco'
	* @param string $comparison_operator . SQL comparison operator Like 'ILIKE'
	*
	* @see class.section_records.php get_rows_data filter_by_search
	* @return string $search_query . POSTGRE SQL query (like 'datos#>'{components, oh21, dato, lg-nolan}' ILIKE '%paco%' )
	*/
	public static function get_search_query( $json_field, $search_tipo, $tipo_de_dato_search, $current_lang, $search_value, $comparison_operator='=') {
		
		if ( empty($search_value) ) {
			return false;
		}

		$json_field = 'a.'.$json_field; // Add 'a.' for mandatory table alias search

		$search_query='';
		switch (true) {
			
			case ($search_tipo===DEDALO_ACTIVITY_WHEN):
				
				if ($comparison_operator==='=') {				
					$search_value = str_replace('/', '-', $search_value);
					$ar_parts = explode('-', $search_value);
						#dump($ar_parts, ' ar_parts ++ '.to_string());
					if (isset($ar_parts[0])) {
						$year = $ar_parts[0];
						$ar_filter[] = "extract(year FROM date) = $year";
					}
					if (isset($ar_parts[1])) {
						$month = $ar_parts[1];
						$ar_filter[] = "extract(month FROM date) = $month";
					}
					if (isset($ar_parts[2])) {
						$day = $ar_parts[2];
						$ar_filter[] = "extract(day FROM date) = $day";
					}
					if (!empty($ar_filter)) {
						$search_query = implode(' AND ', $ar_filter);
					}				
				}//end if ($comparison_operator=='=') {

				
				#$search_query = " CAST($json_field#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}' AS DATE) $comparison_operator CAST('$search_value' AS DATE)";
				#$search_query = "CAST(date AS DATE) $comparison_operator '$search_value' ";
				break;

			default:

				#$date_mode = component_date::get_date_mode_static($search_tipo);
					#dump($date_mode, ' date_mode ++ '.to_string());
				#dump($search_value, ' search_value ++ '.to_string());

				/*

			
					 SELECT a.id, a.section_id, a.section_tipo,
					 a.datos#>>'{components, rsc224, dato, lg-nolan}' AS rsc224 
					 FROM "matrix" a 
					  WHERE a.id IN (SELECT a.id FROM "matrix" a WHERE  a.section_id IS NOT NULL 
					-- filter_by_section_tipo -- 
					 AND (a.section_tipo = 'rsc205')  AND (
					-- filter_by_search --

					 -- filter_by_search rsc224 component_date 
					a.id IN (SELECT a.id 
					  FROM "matrix" a, jsonb_array_elements(a.datos#>'{components, rsc224, dato, lg-nolan}') as rsc224
					  WHERE rsc224#>>'{period,time}' = '64281600'
					OR
					(
					rsc224#>>'{start,time}' <= '64281600' AND 
					rsc224#>>'{end,time}' >= '64281600'
					)
					OR
					rsc224#>>'{start,time}' = '64281600' AND
					rsc224#>>'{end,time}' IS NULL
					)
					) 
					 ORDER BY a.section_id ASC  
					 LIMIT 10)  
					 ORDER BY a.section_id ASC ;
				*/



				// DD_DATE
				$dd_date = new dd_date();
				$dd_date->set_date_from_input_field( $search_value );	
				$time 	 = dd_date::convert_date_to_seconds($dd_date);
					#dump($dd_date, ' dd_date ++ time: '.$time." - search_value: ".to_string($search_value));
				$search_value = $time;
				
				// Search in date
				$aditional_path 	 = 'time';
				#$comparison_operator = '='; // Force always 
				
				$search_query  .= " a.id IN (SELECT a.id FROM";				

				$search_query  .= "\n  jsonb_array_elements($json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang ."}') as $search_tipo";

				/*
				$search_query .= "\n  case when (jsonb_typeof({$json_field}#>'{components, $search_tipo, dato, $current_lang}') = 'array' AND {$json_field}#>'{components, $search_tipo, dato, $current_lang}' != '[]')";
				$search_query .= "\n  then jsonb_array_elements({$json_field}#>'{components, $search_tipo, dato, $current_lang}')";
				$search_query .= "\n  else {$json_field}#>'{components, $search_tipo, dato, $current_lang}'";
				$search_query .= "\n  end as {$search_tipo}";	 // _array_elements		
				#$search_query .= "\n  ,{$json_field}#>'{components, $search_tipo, $tipo_de_dato_search, $current_lang}' as $search_tipo";
				*/

				$search_query .= "\n  WHERE\n  $search_tipo#>'{".$aditional_path."}' $comparison_operator '$search_value'";

				#$search_query  = " $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' $comparison_operator '$search_value' ";

				// Search in ranges
				$aditional_path= 'start, time';
				#$search_query .= " \n OR (\n  CAST($json_field#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' AS integer) <= $search_value";

				$search_query .= " \n  OR (\n  $search_tipo#>'{".$aditional_path."}' <= '$search_value'";
				#$search_query .= " \n OR (\n  $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' <= '$search_value'";
				
				$aditional_path= 'end, time';
				#$search_query .= " AND \n  CAST($json_field#>>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' AS integer) >= $search_value )";
				$search_query .= " AND \n  $search_tipo#>'{".$aditional_path."}' >= '$search_value')";
				#$search_query .= " AND \n  $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' >= '$search_value' )";
				
				$aditional_path= 'start, time';
				$search_query .= " \n  OR (\n  $search_tipo#>'{".$aditional_path."}' = '$search_value'";
				#$search_query .= " \n OR (\n  $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' = '$search_value' ";
				$aditional_path= 'end, time';
				$search_query .= " AND \n  $search_tipo#>'{".$aditional_path."}' IS NULL)";
				#$search_query .= " AND \n $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' IS NULL )";
				
				$aditional_path= 'period, time';
				$search_query .= " \n  OR (\n  $search_tipo#>'{".$aditional_path."}' = '$search_value')";
				#$search_query .= " \n OR (\n  $json_field#>'{components, $search_tipo, $tipo_de_dato_search, ". $current_lang . $aditional_path . "}' = '$search_value' ";

				$search_query .= "\n )";
				break;
		}

		if (empty($search_query)) {
			$search_query='id>0';
		}
		
		if(SHOW_DEBUG===true) {
			#dump($search_query, ' search_query ++ '.to_string());
			$search_query = "\n -- filter_by_search [".__METHOD__."] $search_tipo ". get_called_class() ." \n".$search_query;			
		}
		return $search_query;
	}//end get_search_query



	/**
	* GET_SEARCH_ORDER
	* Overwrite as needed
	* @return string $order_direction
	*/
	public static function get_search_order($json_field, $search_tipo, $tipo_de_dato_order, $current_lang, $order_direction) {

		$tipo_de_dato_order = 'dato';

		$RecordObj_dd 	= new RecordObj_dd($search_tipo);
		$propiedades 	= $RecordObj_dd->get_propiedades();

		$propiedades = json_decode($propiedades);

		//dump($propiedades	, ' propiedades ++ '.to_string());

		if (isset($propiedades->date_mode)) {
					$date_mode = $propiedades->date_mode; // Default from structure if is defined
				}else{
					$date_mode = 'date'; // Default
		}

		switch ($date_mode) {
				case 'range':
					$order_by_resolved  = "a.$json_field#>'{components, $search_tipo, $tipo_de_dato_order, $current_lang}'->0->'start'->'time' ".$order_direction;
				break;

				case 'period':
					$order_by_resolved  = "a.$json_field#>'{components, $search_tipo, $tipo_de_dato_order, $current_lang}'->0->'period'->'time' ".$order_direction;
				break;

				case 'date':
					$order_by_resolved  = "a.$json_field#>'{components, $search_tipo, $tipo_de_dato_order, $current_lang}'->0->'time' ".$order_direction;
				default:
			}


		
		return (string)$order_by_resolved;
	}//end get_search_order



	/**
	* BUILD_SEARCH_COMPARISON_OPERATORS 
	* @return object stdClass $search_comparison_operators
	*/
	public function build_search_comparison_operators( $comparison_operators=array('=','!=','>','<','>=','<=') ) {
		$search_comparison_operators = new stdClass();

		#
		# Overwrite defaults with 'propiedades'->SQL_comparison_operators
			if(SHOW_DEBUG===true) {
				#dump($this->propiedades, " this->propiedades ".to_string());;
			}		
			if(isset($this->propiedades->SQL_comparison_operators)) {
				$comparison_operators = (array)$this->propiedades->SQL_comparison_operators;
			}


		foreach ($comparison_operators as $current) {
			# Get the name of the operator in current lang 
			$operator = operator::get_operator($current);
			$search_comparison_operators->$current = $operator;
		}
		return (object)$search_comparison_operators;

	}//end build_search_comparison_operators



	/**
	* ADD_TIME
	* Recoge el current dato recibido (de tipo stdClass) y lo usa para crear un objeto dd_date al que inyecta
	* el time (seconds) calculado.
	* Retorna el objeto dd_date creado
	* @return object dd_date $dato
	*/
	public static function add_time( $current_dato ) {

		if(empty($current_dato)) return $current_dato;
		
		// Period date mode
		if( isset($current_dato->period) ) {
			$dd_date = new dd_date($current_dato->period);
			$time 	 = dd_date::convert_date_to_seconds($dd_date);
			if (isset($current_dato->period->time) && $current_dato->period->time!=$time) {
				debug_log(__METHOD__." Unequal time seconds value: current: ".to_string($current_dato->period->time).", calculated: $time. Used calculated time.", logger::WARNING);
			}
			$dd_date->set_time( $time );
			$current_dato->period = $dd_date;
		}

		// Range date mode
		if( isset($current_dato->start) ) {
			$dd_date = new dd_date($current_dato->start);
			$time 	 = dd_date::convert_date_to_seconds($dd_date);
			if (isset($current_dato->start->time) && $current_dato->start->time!=$time) {
				debug_log(__METHOD__." Unequal time seconds value: current: ".to_string($current_dato->start->time).", calculated: $time. Used calculated time.", logger::WARNING);
			}
			$dd_date->set_time( $time );
			$current_dato->start = $dd_date;
		}
		if( isset($current_dato->end) ) {
			$dd_date = new dd_date($current_dato->end);
			$time 	 = dd_date::convert_date_to_seconds($dd_date);
			if (isset($current_dato->end->time) && $current_dato->end->time!=$time) {
				debug_log(__METHOD__." Unequal time seconds value: current: ".to_string($current_dato->end->time).", calculated: $time. Used calculated time.", logger::WARNING);
			}
			$dd_date->set_time( $time );
			$current_dato->end = $dd_date;
		}		

		// Default date mode
		if( isset($current_dato->year) ) {

			$dd_date = new dd_date($current_dato); 
			$time 	 = dd_date::convert_date_to_seconds($dd_date);			
			if (isset($current_dato->time) && $current_dato->time!=$time) {
				debug_log(__METHOD__." Unequal time seconds value: current: ".to_string($current_dato->time).", calculated: $time. Used calculated time.", logger::WARNING);
			}
			$dd_date->set_time( $time );
			$current_dato = $dd_date;
		}
		// Time date mode
		else if( isset($current_dato->hour) ) {
			$dd_date = new dd_date($current_dato); 
			$time 	 = dd_date::convert_date_to_seconds($dd_date);			
			
			if (isset($current_dato->time) && $current_dato->time!=$time) {
				debug_log(__METHOD__." Unequal time seconds value: current: ".to_string($current_dato->time).", calculated: $time. Used calculated time.", logger::WARNING);
			}
			$dd_date->set_time( $time );
			$current_dato = $dd_date;
		}


		return (object)$current_dato;
	}//end add_time



	/**
	* UPDATE_DATO_VERSION
	* @return 
	*/
	public static function update_dato_version($update_version, $dato_unchanged, $reference_id) {

		$update_version = implode(".", $update_version);

		switch ($update_version) {

			case '4.7.0':
				if (!empty($dato_unchanged) && is_object($dato_unchanged) ) {
					#dump($dato_unchanged, ' dato_unchanged ++ '.to_string($reference_id)); #die();

					$new_dato = [];
					$new_dato = $dato_unchanged;					
						#dump($new_dato, ' new_dato ++ '. $reference_id.' -> '.to_string($dato_unchanged));						

					$response = new stdClass();
					$response->result = 1;
					$response->new_dato = $new_dato;
					$response->msg = "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";
					return $response;

				}else{
					$response = new stdClass();
					$response->result = 2;
					$response->msg = "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)." 
					return $response;
				}	

			break;
			case '4.0.14':
				if (!empty($dato_unchanged) && is_object($dato_unchanged) ) {
					#dump($dato_unchanged, ' dato_unchanged ++ '.to_string($reference_id)); #die();

					$new_dato = component_date::add_time($dato_unchanged);					
						#dump($new_dato, ' new_dato ++ '. $reference_id.' -> '.to_string($dato_unchanged));						

					$response = new stdClass();
					$response->result = 1;
					$response->new_dato = $new_dato;
					$response->msg = "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";
					return $response;

				}else{
					$response = new stdClass();
					$response->result = 2;
					$response->msg = "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)." 
					return $response;
				}				
				break;

			case '4.0.10':
				#$dato = $this->get_dato_unchanged();
					
				# Compatibility old dedalo instalations
				if (is_string($dato_unchanged) && !empty($dato_unchanged)) {
						#dump($dato, ' dato '.to_string($this->parent).' '. to_string($this->section_tipo));
					$dd_date    = new dd_date();
					$new_dato 	= (object)$dd_date->get_date_from_timestamp( $dato_unchanged );
											
					$response = new stdClass();
					$response->result =1;
					$response->new_dato = $new_dato;
					$response->msg = "[$reference_id] Dato is changed from ".to_string($dato_unchanged)." to ".to_string($new_dato).".<br />";
					return $response;
					

				}else{
					$response = new stdClass();
					$response->result = 2;
					$response->msg = "[$reference_id] Current dato don't need update.<br />";	// to_string($dato_unchanged)." 
					return $response;
				}
				break;
			case '4.0.10':
				$result = true;
				return $result;
				break;
			default:
				# code...
				break;
		}		
	}//end update_dato_version



	/**
	* RENDER_LIST_VALUE
	* Overwrite for non default behaviour
	* Receive value from section list and return proper value to show in list
	* Sometimes is the same value (eg. component_input_text), sometimes is calculated (e.g component_portal)
	* @param string $value
	* @param string $tipo
	* @param int $parent
	* @param string $modo
	* @param string $lang
	* @param string $section_tipo
	* @param int $section_id
	*
	* @return string $list_value
	*/
	public static function render_list_value($value, $tipo, $parent, $modo, $lang, $section_tipo, $section_id, $current_locator=null, $caller_component_tipo=null) {
	
		if($section_tipo===DEDALO_ACTIVITY_SECTION_TIPO) {
			# nothing to do. Value is final value
		}else{
			$component 	= component_common::get_instance(__CLASS__,
														 $tipo,
													 	 $parent,
													 	 $modo,
														 DEDALO_DATA_NOLAN,
													 	 $section_tipo);
			
			# Use already query calculated values for speed
			#$dato = json_handler::decode($value);
			#$component->set_dato($dato);

			$component->set_identificador_unico($component->get_identificador_unico().'_'.$section_id.'_'.$caller_component_tipo); // Set unic id for build search_options_session_key used in sessions
			
			$value = $component->get_html();
			#$value = $component->get_valor();
		}

		return $value;		
	}//end render_list_value



	/**
	* GET_DIFFUSION_VALUE
	* Overwrite component common method
	* Calculate current component diffusion value for target field (usually a mysql field)
	* Used for diffusion_mysql to unify components diffusion value call
	* @return string $diffusion_value
	*
	* @see class.diffusion_mysql.php
	*/
	public function get_diffusion_value( $lang=null ) {

		$diffusion_value = '';		
		$ar_dato 			 = $this->get_dato();
		$date_mode 		 = $this->get_date_mode();
		$ar_diffusion_values = array();
		foreach ($ar_dato as $dato) {
			switch ($date_mode) {
				case 'range':
					$ar_date=array();
					// start
					if (isset($dato->start) && isset($dato->start->year)) {
						$dd_date 		= new dd_date($dato->start);
						$timestamp 		= $dd_date->get_dd_timestamp("Y-m-d H:i:s");
						$ar_date[] 		= $timestamp;
					}				
					// end
					if (isset($dato->end) && isset($dato->end->year)) {
						$dd_date 		= new dd_date($dato->end);
						$timestamp 		= $dd_date->get_dd_timestamp("Y-m-d H:i:s");
						$ar_date[] 		= $timestamp;
					}
					$ar_diffusion_values[] = implode(',',$ar_date);
					break;

				case 'period':
					// Not defined yet
					break;

				case 'date':
				default:
					$dd_date 		 = new dd_date($dato);
					$timestamp 		 = $dd_date->get_dd_timestamp("Y-m-d H:i:s");
					$ar_diffusion_values[] = $timestamp;
					break;
			}
		}

		#$diffusion_value = implode('|',$ar_diffusion_values);

		# NOTA
		# Para publicación, NO está solucionado el acso en que hay más de ina fecha... ejem.. VALORAR ;-)
		$diffusion_value = reset($ar_diffusion_values); // Temporal !!
	
		# Force null on empty to avoid errors on mysql save value invalid format
		# Only valid dates or null area accepted
		if (empty($diffusion_value)) {
			$diffusion_value = null;
		}

		return $diffusion_value;
	}//end get_diffusion_value



	/**
	* GET_VALOR_LIST_HTML_TO_SAVE
	* Usado por section:save_component_dato
	* Devuelve a section el html a usar para rellenar el 'campo' 'valor_list' al guardar
	* Por defecto será el html generado por el componente en modo 'list', pero en algunos casos
	* es necesario sobre-escribirlo, como en component_portal, que ha de resolverse obigatoriamente en cada row de listado
	*
	* En este caso, usaremos únicamente el valor en bruto devuelto por el método 'get_dato_unchanged'
	*
	* @see class.section.php
	* @return mixed $result
	*/
	public function get_valor_list_html_to_save() {
		#$result = $this->get_dato_unchanged();
		$result = $this->get_valor();
		
		return $result;
	}//end get_valor_list_html_to_save



}
?>