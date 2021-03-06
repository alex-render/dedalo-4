<?php
require_once(DEDALO_LIB_BASE_PATH . '/db/class.RecordDataBoundObject.php');
#require_once(DEDALO_LIB_BASE_PATH . '/db/class.RecordObj_descriptors.php');
require_once(DEDALO_LIB_BASE_PATH . '/ts/class.Tesauro.php');


class RecordObj_ts extends RecordDataBoundObject {
	
	# FIELDS
	protected $terminoID;
	protected $parent;
	protected $modelo;
	protected $esmodelo;
	protected $esdescriptor;
	protected $visible ;
	protected $norden ;
	protected $usableIndex ;
	protected $traducible ;
	protected $relaciones ;
	protected $propiedades ;
	
	protected $prefijo ;
	
	# FIELDS EXTERNAL
	protected $filtroTerminos ;
	
	# OPTIONAL ESPECIFIC LOADS	
	#protected $ar_recursive_childrens_of_this 	= array();
	#protected $ar_parents_cache 				= array();
	#protected $ar_reels_of_this 				= array();
	
	
	function __construct($terminoID=NULL, $prefijo=false) {
		
		if( !empty($terminoID) ) {

			$prefix = Tesauro::terminoID2prefix($terminoID);
			
			if( is_int($prefix) ) {
				
				# CASO GENERAL	
				$id = substr($terminoID,2);
				$this->set_prefijo($prefix);
				$this->set_ID(intval($id));
							
			}else{
				
				# CASO LENGUAJES
				$id	= $this->terminoID2id($terminoID);
			}
			
			$this->set_terminoID($terminoID);
		
		}else if(strlen($prefijo)===2) {
			
			$id = NULL;
			$this->set_prefijo($prefijo);				
		
		}else{			
			
			$msg = "This record ts not exists ! [terminoID:$terminoID, prefijo:$prefijo] id:"; if(isset($_REQUEST['id'])) $msg .= $_REQUEST['id']; 

			if(SHOW_DEBUG===true) {
				 # LOGGER
        		logger::$obj['error']->log_message("$msg", logger::ERROR, __METHOD__); 
        		throw new Exception("Error Processing Request $msg", 1);
			} 
			exit($msg);
		}
		
		# PREFIX TEST
		if(!$this->prefijo) {

			$msg = " Table not defined with prefijo:$prefijo ";
			if(SHOW_DEBUG===true) {
				 # LOGGER
        		logger::$obj['error']->log_message("$msg", logger::ERROR, __METHOD__); 
        		throw new Exception("Error Processing Request $msg", 1); 
			}
			exit($msg);
		}
				
		parent::__construct($id);				
	}	
	
	# DEFINETABLENAME : define current table (tr for this obj)
	protected function defineTableName() {		
		return ('jer_'.$this->prefijo);	#echo ' jer_'.$this->current_table.' ';
	}	
	# DEFINEPRIMARYKEYNAME : define PrimaryKeyName (id)
	protected function definePrimaryKeyName() {
		return ('id');	
	}	
	# DEFINERELATIONMAP : array of pairs db field name, obj property name like fieldName => propertyName
	protected function defineRelationMap() {		
		return (array(
			# db fieldn ame					# property name
			"id" 							=> "ID",
			"terminoID"						=> "terminoID",
			"parent" 						=> "parent",
			"modelo" 						=> "modelo",
			"esmodelo" 						=> "esmodelo",
			"esdescriptor" 					=> "esdescriptor",
			"visible" 						=> "visible",
			"norden" 						=> "norden",
			"usableIndex" 					=> "usableIndex",
			"traducible" 					=> "traducible",
			"relaciones" 					=> "relaciones",
			"propiedades" 					=> "propiedades",
			));
	}



	# GET_JERARQUIA_TYPE	
	function get_jerarquia_type() {	
				
		# STATIC CACHE
		static $get_jerarquia_type_data;		
		if(isset($this->terminoID) && isset($get_jerarquia_type_data[$this->terminoID])) return $get_jerarquia_type_data[$this->terminoID];
		
		require_once(DEDALO_ROOT . '/jer/class.RecordObj_jer.php');
		
		$arguments["strPrimaryKeyName"]	= 'tipo';
		$arguments["alpha2"]			= strtoupper( $this->get_prefijo() );	
		
		$RecordObj_jer					= new RecordObj_jer(NULL);
		$ar_id							= $RecordObj_jer->search($arguments);
			#dump($ar_id,"get_jerarquia_type : ar_id - arguments:".print_r($arguments,true));
		
		$jerarquia_type					= $ar_id[0];
		
		# STORE CACHE DATA
		$get_jerarquia_type_data[$this->terminoID] = $jerarquia_type;
		
		return $jerarquia_type ;
	}


	
	/**
	* TERMINOID2ID : resolve id from terminoID, lang
	*/
	public function terminoID2id($terminoID) {
		
		# JER_LG : LANGS LIKE 'lg-365'
		if(strpos($terminoID,'-')!==false) {
			
			$ar_parts 		= explode('-',$terminoID);
			$this->prefijo	= $ar_parts[0];

			$arguments=array();
			$arguments['terminoID'] = $terminoID;
			$RecordObj_ts			= new RecordObj_ts(NULL, $this->prefijo);
			$ar_id					= $RecordObj_ts->search($arguments);
			
			if(isset($ar_id[0]))	return $ar_id[0];
		
		# DEFAULT : NORMAL CASE LIKE 'tc126'
		}else{
			
			$this->prefijo	= Tesauro::terminoID2prefix($terminoID);
			return intval(substr($terminoID, 2));
		}
		
		return null;

	}//end terminoID2id



	##
	# SAVE
	public function Save() {
		
		# Salva para crear el registro y obtener un id válido		
		$id = parent::Save();
		
		# SI ES UN REGISTRO NUEVO, NO TIENE CREADO EL 'terminoID'.
		# SE ASIGNA terminoID EN FUNCION DEL PREFIJO Y EL ID (autoincrement) CREADO		
		$terminoID = self::get_terminoID();
		if( empty($terminoID) && is_numeric($id) ) {
						
			# Lo componemos y volvemos a salvar
			$terminoID	= $this->get_prefijo() . $id ;
			self::set_terminoID($terminoID);
			self::set_norden(1);

			parent::Save();
			//error_log("-->".__METHOD__." Warning: second save triggered");
		}
		
		return $id;

	}//end Save

	

	/**
	* GET_DESCRIPTOR_DATO_BY_TIPO
	* Get termino dato by tipo ('termino','def','obs') STATIC VERSION
	*/
	public static function get_descriptor_dato_by_tipo($terminoID, $lang, $tipo, $fallback=false) {

		# Verify : En casos como por ejemplo, al resolver el modelo de un término relacionado que no tiene modelo asignado, el terminoID estará vacío.
		# Esto no es un error pero debemos evitar resolverlo. 
		if(empty($terminoID)) {
			return null;
		}
		/*
		#
		# DEDALO_CACHE_MANAGER : var
		$cache_var='get_descriptor_dato_by_tipo_'.$terminoID.'-'.$lang.'-'.$tipo.'-'.intval($fallback);
		if(DEDALO_CACHE_MANAGER && strpos($terminoID, 'dd')===0 && cache::exists($cache_var)) {
			#error_log("Readed cache: $cache_var ");
			return cache::get($cache_var);
		}
		*/
		# STATIC CACHE
		static $descriptor_dato_by_tipo_stat_data;
		$uid = $terminoID.'-'.$lang.'-'.$tipo.'-'.intval($fallback);	#dump($uid,'$uid');	
		if(isset($descriptor_dato_by_tipo_stat_data[$uid])) {
			#error_log("Returned data from cache get_descriptor_dato_by_tipo uid:$uid");		
			return $descriptor_dato_by_tipo_stat_data[$uid];
		}
		#dump(""," from terminoID:$terminoID, lang:$lang, tipo:$tipo, fallback:$fallback");
		
		$matrix_table			= RecordObj_descriptors::get_matrix_table_from_tipo($terminoID);
		$RecordObj_descriptors	= new RecordObj_descriptors($matrix_table, NULL, $terminoID, $lang, $tipo, $fallback);		
		$dato					= (string)$RecordObj_descriptors->get_dato();		
		
		# STATIC CACHE
		$descriptor_dato_by_tipo_stat_data[$uid] = $dato;

		/*
		#
		# DEDALO_CACHE_MANAGER : Lo metemos en cache
		if(DEDALO_CACHE_MANAGER && strpos($terminoID, 'dd')===0) {
			cache::set($cache_var, $dato);
			#error_log("Added cache: $cache_var ");
		}
		*/	
		return $dato;

	}//end get_descriptor_dato_by_tipo



	# GET_TERMINO_BY_TIPO STATIC VERSION
	public static function get_termino_by_tipo($terminoID, $lang=NULL, $from_cache=false, $fallback=true) {
		#$from_cache=false;
		$cache_uid = $terminoID.'_'.$lang;
		if ($from_cache===true && isset($_SESSION['dedalo4']['config']['termino_by_tipo'][$cache_uid])) {
			#error_log("From cache $terminoID.$lang");
			return $_SESSION['dedalo4']['config']['termino_by_tipo'][$cache_uid];
		}

		// CATCH CALL
		if(SHOW_DEBUG===true) {
			if (strpos($terminoID, 'lg-')===0) {
				#dump(debug_backtrace(), 'debug_backtrace() ++ '.to_string());
				debug_log(__METHOD__." CATCH CALL TO get_termino_by_tipo FOR LANG: ".to_string($terminoID), logger::ERROR);
			}
		}
		

		$tipo = 'termino';
		$result = self::get_descriptor_dato_by_tipo($terminoID, $lang, $tipo, $fallback);
		if (substr($terminoID, 0,2)==='dd') {
			$_SESSION['dedalo4']['config']['termino_by_tipo'][$cache_uid] = $result;
		}		
		return $result;
	}
	# GET NOTES STATIC VERSION
	public static function get_notes_by_tipo($terminoID, $lang=false) {
		
		$tipo = 'notes';
		return self::get_descriptor_dato_by_tipo($terminoID, $lang, $tipo);
	}
	# GET DEF STATIC VERSION
	public static function get_def_by_tipo($terminoID, $lang=false) {
		
		$tipo = 'def';
		return self::get_descriptor_dato_by_tipo($terminoID, $lang, $tipo);
	}
	# GET OBS STATIC VERSION
	public static function get_obs_by_tipo($terminoID, $lang=false) {
				
		$tipo = 'obs';
		return self::get_descriptor_dato_by_tipo($terminoID, $lang, $tipo);
	}

	

	/**
	* GET MODELO NAME (CURRENT OBJ)
	* Alias of $this->get_termino_by_tipo($modelo_tipo)
	*/
	public function get_modelo_name() {		
		$lang=null;		
		return $this->get_termino_by_tipo($this->get_modelo(),$lang,true,false);
	}



	/**
	* GET_TIEMPO
	* @return string $tiempo
	*/
	public function get_tiempo() {
		$tipo = 'tiempo';
		$lang = isset($this->lang) ? $this->lang : false;
		return $tiempo = self::get_descriptor_dato_by_tipo($this->terminoID, $lang, $tipo);
	}#end get_tiempo



	/**
	* GET_geolocalizacion
	* @return string $geolocalizacion
	*/
	public function get_geolocalizacion() {
		$tipo = 'geolocalizacion';
		$lang = isset($this->lang) ? $this->lang : false;
		return $geolocalizacion = self::get_descriptor_dato_by_tipo($this->terminoID, $lang, $tipo);
	}#end get_geolocalizacion



	/**
	* GET_nomenclator_code
	* @return string $nomenclator_code
	*/
	public function get_nomenclator_code() {
		$tipo = 'nomenclator_code';
		$lang = isset($this->lang) ? $this->lang : false;
		return $nomenclator_code = self::get_descriptor_dato_by_tipo($this->terminoID, $lang, $tipo);
	}#end get_nomenclator_code



	/**
	* GET_ALTITUDE 
	* @return string $altitude
	*/
	public function get_altitude() {
		$tipo = 'altitude';
		$lang = isset($this->lang) ? $this->lang : false;
		return $altitude = self::get_descriptor_dato_by_tipo($this->terminoID, $lang, $tipo);
	}#end get_altitude



	/**
	* GET_MODELO_NAME_BY_TIPO (STATIC)
	*/
	public static function get_modelo_name_by_tipo($tipo, $from_cache=false) {
		
		#if ($from_cache && isset($_SESSION['dedalo4']['config']['modelo_name_by_tipo'][$tipo])) {			
		#	return $_SESSION['dedalo4']['config']['modelo_name_by_tipo'][$tipo];
		#}

		$RecordObj_ts	= new RecordObj_ts($tipo);
		$modelo_name 	= (string)$RecordObj_ts->get_modelo_name();
		#if (substr($tipo, 0,2)=='dd') {
		#	$_SESSION['dedalo4']['config']['modelo_name_by_tipo'][$tipo] = $modelo_name;
		#}
		return $modelo_name;

	}//end get_modelo_name_by_tipo
	

	/**
	* GET_AR_TERMINOID_BY_MODELO_NAME (STATIC)
	*/
	public static function get_ar_terminoID_by_modelo_name($modelo_name, $prefijo='dd') {
	
		if(SHOW_DEBUG===true) {
			$start_time = start_time();
		}

		# STATIC CACHE
		static $ar_terminoID_by_modelo_name;
		$unic_string = $modelo_name.'-'.$prefijo;		
		if(isset($ar_terminoID_by_modelo_name[$unic_string])) return $ar_terminoID_by_modelo_name[$unic_string];
		

		# 1 Despejamos el terminoID del modelo (ejemplo : 'area_root') que es el parent en matrix_descriptors
		$arguments=array();
		$arguments['strPrimaryKeyName']	= 'parent';
		$arguments['dato']				= (string)$modelo_name;
		$arguments['tipo']				= 'termino';			
		$matrix_table					= RecordObj_descriptors::get_matrix_table_from_tipo($prefijo);	# 'matrix_descriptors_'.$prefijo;#
		$RecordObj_descriptors			= new RecordObj_descriptors($matrix_table, NULL);	#dump($arguments,"$modelo_name -$matrix_table");		
		$ar_result						= $RecordObj_descriptors->search($arguments);		#dump($ar_result,'modelo_terminoID',"terminoID_by_modelo_name de: $modelo_name");
			
		/**
		* ARREGLO 2-2-2013
		*/
		# Recorremos los resultados para verificar que son modelo
		# Así obtenemos exclusivamente los téminos que SI son modelo
		if (!empty($ar_result)) {
		
			$ar_modelo_terminoID = array();
			foreach ($ar_result as $terminoID) {
				
				$RecordObj_ts	= new RecordObj_ts($terminoID);
				$esmodelo		= $RecordObj_ts->get_esmodelo($arguments);
				# Excluimos a los propios modelos del array
				if ($esmodelo=='si') {
					# Verfificado
					$ar_modelo_terminoID[] = $terminoID;
				}
			}
			foreach ($ar_modelo_terminoID as $modelo_terminoID) {
				
				$arguments=array();
				$arguments['strPrimaryKeyName']	= 'terminoID';
				$arguments['modelo']			= $modelo_terminoID;
				$RecordObj_ts					= new RecordObj_ts(NULL,$prefijo);
				$ar_result						= $RecordObj_ts->search($arguments);
			}
		}

		# STATIC CACHE
		$ar_terminoID_by_modelo_name[$unic_string] = $ar_result;
			#dump($ar_result,'$ar_result');
		
		if(SHOW_DEBUG===true) {
			#$GLOBALS['log_messages'] .= exec_time($start_time, __METHOD__, $ar_result );
		}

		return $ar_result;

	}//end get_ar_terminoID_by_modelo_name


	
	# MODELOS ARRAY
	# Se usa en  el selector de tesauro edit para asignar modelo
	public function get_ar_all_modelos() {
		
		# STATIC CACHE
		static $ar_all_modelos_data;		
		if(isset($this->terminoID) && isset($ar_all_modelos_data[$this->terminoID])) return $ar_all_modelos_data[$this->terminoID];
				
		$ar_all_modelos = array();		#echo " this->terminoID:".$this->get_terminoID() ." - ".$this->terminoID()."<hr>";			
		
		# SEARCH		
		$arguments=array();
		$arguments['esmodelo']	= 'si';
		$RecordObj_ts			= new RecordObj_ts(NULL,$this->prefijo);
		$ar_id					= (array)$RecordObj_ts->search($arguments);		
		
		foreach($ar_id as $id) {
			$ar_all_modelos[] = $this->prefijo.$id ;
		}		
				
		# STORE CACHE DATA
		$ar_all_modelos_data[$this->terminoID]	= $ar_all_modelos;
		
		return $ar_all_modelos;

	}//end get_ar_all_modelos
	
	

	/**
	* GET_AR_CHILDRENS_OF_THIS
	* Get array of terms (terminoID) with parent = $this->terminoID
	* @return array 
	*/
	public function get_ar_childrens_of_this($esdescriptor='si', $esmodelo=NULL, $order_by='norden') {
			
		# COMPROBACIÓN
		if(!$this->terminoID || !$this->prefijo) return false;		

		# STATIC CACHE
		static $ar_childrens_of_this_stat_data;
		$key = $this->terminoID.'_'.$esmodelo.'_'.$order_by;
		if(isset($ar_childrens_of_this_stat_data[$key])) {
			#error_log("Returned from cache get_ar_childrens_of_this - $key");
			return $ar_childrens_of_this_stat_data[$key];
		}			
		
		# SEARCH
		$arguments=array();
		$arguments['strPrimaryKeyName']	= 'terminoID';
		$arguments['parent']			= $this->terminoID;
		
		if( !empty($esdescriptor) && ($esdescriptor=='si' || $esdescriptor=='no') )
			$arguments['esdescriptor']	= $esdescriptor;
		
		if( !empty($esmodelo) && ($esmodelo=='si' || $esmodelo=='no') )
			$arguments['esmodelo']		= $esmodelo;
		
		if (!empty($order_by)) {
			$arguments['order_by_asc']	= $order_by;
		}			
				
		$ar_childrens_of_this = (array)$this->search($arguments);
				
		# STORE CACHE DATA
		$ar_childrens_of_this_stat_data[$key] = $ar_childrens_of_this;
		
		return $ar_childrens_of_this ;
	}//end get_ar_childrens_of_this



	/**
	* GET_AR_CHILDRENS
	*/
	public static function get_ar_childrens($tipo, $order_by='norden') {
		
		# STATIC CACHE
		static $get_ar_childrens_data;
		$key = $tipo.'_'.$order_by;
		if(isset($get_ar_childrens_data[$key])) {
			if(SHOW_DEBUG===true) {
				#error_log("Returned cache value for get_ar_childrens $key");
			}			
			return $get_ar_childrens_data[$key];
		}
		
		$ar_childrens = array();	
		
		# SEARCH
		$arguments=array();
		$arguments['strPrimaryKeyName']	= 'terminoID';
		$arguments['parent']			= $tipo;
		if($order_by)
		$arguments['order_by_asc']		= $order_by;
		$RecordObj_ts					= new RecordObj_ts($tipo);
		$ar_childrens					= $RecordObj_ts->search($arguments);
		# STORE CACHE DATA
		$get_ar_childrens_data[$key] = $ar_childrens;
		#$get_ar_childrens_data[$key] = ['ts2885','ts2887'];

		return $ar_childrens;

	}//end get_ar_childrens



	# CHILDRENS RECURSIVE ARRAY
	# SACA TODOS LOS HIJOS DEL TERMINO ACTUAL RECURSIVAMENTE
	public function get_ar_recursive_childrens_of_this($terminoID, $is_recursion=0) {
		
		# IMPORTANTE: NO HACER CACHE DE ESTE MÉTODO (AFECTA A COMPONENT_FILTER_MASTER)
				
		# creamos una instancia independiente de RecordObj_ts y sacamos los hijos directos		
		$ar_childrens_of_this 	= array();	# reset value every cycle
		$RecordObj_ts 			= new RecordObj_ts($terminoID);
		$ar_childrens_of_this 	= (array)$RecordObj_ts->get_ar_childrens_of_this(null, null, null);
				
		foreach($ar_childrens_of_this as $children_terminoID) {
			
			# Add current element		
			$this->ar_recursive_childrens_of_this[] = $children_terminoID;		

			# Recursion
			$this->get_ar_recursive_childrens_of_this($children_terminoID, 1);
		}

		if(isset($this->ar_recursive_childrens_of_this)) return $this->ar_recursive_childrens_of_this;

	}//end get_ar_recursive_childrens_of_this



	/**
	* GET_AR_RECURSIVE_CHILDRENS : Static version
	* No hay aumento de velocidad apreciable entre la versión estática y dinámica. Sólo una reducción de unos 140 KB en el consumo de memoria
	*/
	public static function get_ar_recursive_childrens($terminoID, $is_recursion=false) {

		$ar_resolved=array();
		
		if($is_recursion) {
			#array_push($ar_resolved, $terminoID);
			$ar_resolved[] = $terminoID;
		}

		#$ar_childrens  = (array)RecordObj_ts::get_ar_childrens($terminoID);
		$RecordObj_ts 	= new RecordObj_ts($terminoID);
		$ar_childrens 	= (array)$RecordObj_ts->get_ar_childrens_of_this('si',null,null);


		foreach($ar_childrens as $current_terminoID) {

			# Recursion
			$ar_resolved = 	array_merge( $ar_resolved, (array)RecordObj_ts::get_ar_recursive_childrens($current_terminoID,true) );
		}
		
		return $ar_resolved;

	}//end get_ar_recursive_childrens
	
	

	/**
	* GET_AR_PARENTS_OF_THIS
	*/
	public function get_ar_parents_of_this($ksort=true) {
		
		# STATIC CACHE
		static $ar_parents_of_this_data;		
		if(isset($this->terminoID) && isset($ar_parents_of_this_data[$this->terminoID])) 	 return $ar_parents_of_this_data[$this->terminoID];
		
		$ar_parents_of_this = array();	
		$parent				= $this->get_parent();					if(!$parent) return $ar_parents_of_this;
		$parent_inicial		= $parent;	
		$parent_zero		= trim($this->get_prefijo().'0');

		do {			
			if( strpos($parent, $parent_zero)===false  ) { // $parent != $parent_zero
				$ar_parents_of_this[]	= $parent;				
			}			
			
			$RecordObj_ts			= new RecordObj_ts($parent);	
			$parent 				= $RecordObj_ts->get_parent();		#dump($parent); #die();	

			#$esdescriptor			= $RecordObj_ts->get_esdescriptor(); #if($esdescriptor!='si' && $parent!=$parent_zero) die( __METHOD__ ."<span class='error'> Error. this parent is not descriptor ! (parent:$parent, esdescriptor:$esdescriptor) </span>");				
		
		} while ( !empty($parent) && ($parent != $parent_zero) && $parent != $parent_inicial );
		
		
		# ordenamos a la inversa los padres
		#echo " ksort:";dump($ksort);
		if($ksort==true) krsort($ar_parents_of_this);
		
		# STORE CACHE DATA
		$ar_parents_of_this_data[$this->terminoID] = $ar_parents_of_this;
		
		return $ar_parents_of_this;

	}//end get_ar_parents_of_this

	
	
	/**
	* GET_AR_SIBLINGS_OF_THIS [HERMANOS]
	*/
	public function get_ar_siblings_of_this() {
		
		# STATIC CACHE
		static $ar_siblings_of_this_data;		
		if(isset($this->terminoID) && isset($ar_siblings_of_this_data[$this->terminoID])) return $ar_siblings_of_this_data[$this->terminoID];
		
		$ar_siblings_of_this		= array();
		
		$arguments["parent"]		= $this->get_parent();
		$arguments["esdescriptor"]	= 'si';		
		$ar_id						= $this->search($arguments);		
		
		foreach($ar_id as $id) {			
			$ar_siblings_of_this[]	= $this->get_prefijo() . $id;
		}
		
		# STORE CACHE DATA
		$ar_siblings_of_this_data[$this->terminoID] = $ar_siblings_of_this;
		
		return $ar_siblings_of_this;

	}//end get_ar_siblings_of_this
	

	
	# NUMERO DE HIJOS DESCRIPTORES
	function get_n_hijos_descriptores() {	
		
		# STATIC CACHE
		static $get_n_hijos_descriptores_data;		
		if(isset($this->terminoID) && isset($get_n_hijos_descriptores_data[$this->terminoID])) return $get_n_hijos_descriptores_data[$this->terminoID];
		
		$arguments["parent"]		= $this->terminoID;
		$arguments["esdescriptor"]	= 'si';		
		$ar_id						= $this->search($arguments);		
		
		$n_hijos_descriptores		= count($ar_id);		#echo $n_hijos_descriptores ."<hr>";
		
		# STORE CACHE DATA
		$get_n_hijos_descriptores_data[$this->terminoID] = $n_hijos_descriptores;	
		
		return $n_hijos_descriptores;
	}
	


	# NUMERO DE HIJOS
	function get_n_hijos() {	
				
		# STATIC CACHE
		static $get_n_hijos_data;		
		if(isset($this->terminoID) && isset($get_n_hijos_data[$this->terminoID])) return $get_n_hijos_data[$this->terminoID];
		
		$arguments["parent"]	= $this->terminoID;	
		$ar_id					= $this->search($arguments);		
		
		$n_hijos				= count($ar_id);
		
		# STORE CACHE DATA
		$get_n_hijos_data[$this->terminoID] = $n_hijos;	
		
		return $n_hijos;
	}
	
	
	
	/**
	* GET_RELACIONES
	* Get relaciones as array (FROM JSON)
	* Devuelve array en formato:
	*	Array
	*    (
	*       [0] => dd296,
	* 		[1] => dd298
	*   )
	*/
	public function get_relaciones() {
		
		$relaciones = null;

		$dato = parent::get_relaciones();
		
		if (!empty($dato)) {
			#$relaciones = $dato; 	
			$relaciones = json_decode($dato, true);
		}
		#dump($dato,'dato en get_relaciones');

		return (array)$relaciones;	
	}
	


	/**
	* SET_RELACIONES
	* Set relaciones as json string
	*/
	public function set_relaciones($ar_relaciones) {
		if(is_array($ar_relaciones)) {
			$ar_relaciones = array_values($ar_relaciones); // Reset keys for sure json array format
		}

		$this->get_relaciones(); // Force update var relaciones
		
		#$result = parent::set_relaciones( json_encode($ar_relaciones) );
		$result = parent::set_relaciones( $ar_relaciones );
			#dump($this->get_relaciones(), ' get_relaciones ++ '.to_string());
			
			
		return $result;
	}



	/**
	* REMOVE_ELEMENT_FROM_AR_TERMINOS_RELACIONADOS
	* @param string $terminoID_to_unlink
	*	Like 'ts254'
	* @return bool
	*/
	public function remove_element_from_ar_terminos_relacionados($terminoID_to_unlink) {
		
		# Recorremos los elementos en terminos relacionados para este objeto
		$ar_relaciones = (array)$this->get_relaciones();
		
		# eliminamos del array el valor recibido		
		foreach ($ar_relaciones as $key => $current_terminoID) {
			if ($current_terminoID==$terminoID_to_unlink) {
				unset($ar_relaciones[$key]);
			}
		}
		#$ar_relaciones = array_values($ar_relaciones); // Reset keys for sure json array format
		
		# guardamos el resultado
		$this->set_relaciones($ar_relaciones);
		
		return true;

	}//end remove_element_from_ar_terminos_relacionados



	/**
	* ADD_ELEMENT_TO_AR_TERMINOS_RELACIONADOS
	* @param string $terminoID_to_unlink
	*	Like 'ts254'
	* @return bool
	*/
	public function add_element_to_ar_terminos_relacionados( $terminoID ) {
		
		# Recorremos los elementos en terminos relacionados para este objeto
		$ar_relaciones = (array)$this->get_relaciones();
		
		if (!in_array($terminoID, $ar_relaciones)) {
			$ar_relaciones[] = $terminoID;
		}		
		#$ar_relaciones = array_values($ar_relaciones); // Reset keys for sure json array format
		
		# guardamos el resultado
		$this->set_relaciones($ar_relaciones);
		
		return true;

	}#end add_element_to_ar_terminos_relacionados



	/**
	* GET_AR_TERMINOS_RELACIONADOS
	* rr_terminos_relacionados json_version
	* En modo 'simple' devuelve sólo un array de 'terminoID'
	*/
	public static function get_ar_terminos_relacionados($terminoID, $cache=false) {

		#if(SHOW_DEBUG===true) $start_time = start_time();
		# NO HACER CACHE EN ESTE MÉTODO	
		$RecordObj_ts				= new RecordObj_ts($terminoID);
		$ar_terminos_relacionados	= (array)$RecordObj_ts->get_relaciones();		
		
		return $ar_terminos_relacionados;	
	}

	/* DESACTIVA PORQUE NO SE EXPERIMENTA INCREMENTO DE VELOCIDAD... 
	public static function get_ar_recursive_childrens_of_this_static($terminoID) {

		#static $ar_recursive_childrens_of_this_static;
		#if(isset($ar_recursive_childrens_of_this_static[$terminoID])) return $ar_recursive_childrens_of_this_static ;

		$RecordObj_ts 					= new RecordObj_ts($terminoID);
		$ar_recursive_childrens_of_this = $RecordObj_ts->get_ar_recursive_childrens_of_this($terminoID);

		# Store in cache
		#$ar_recursive_childrens_of_this_static = $ar_recursive_childrens_of_this;

		return $ar_recursive_childrens_of_this;
	}
	*/


	
	# GET TERMINO ID BY MODELNAME
	# devuelve el terminoID del termino relacionado (especificar relacion) de modelo name dado
	# ej. para saber los términos relacionados de modelo 'filter'
	static public function get_ar_terminoID_by_modelo_name_and_relation($tipo, $modelo_name, $relation_type) {
		
		$result	= array();
		
		if(empty($tipo)) return $result;

		# acepta también arrays como entrada de tipo
		if(is_array($tipo) && isset($tipo[0])) $tipo = $tipo[0];	
		
		# STATIC CACHE		
		static $get_ar_terminoID_by_modelo_name_and_relation_data;	
		$name	= $tipo.'_'.$modelo_name.'_'.$relation_type; 			#dump($name,"CACHE KEY $name");
		if(isset($get_ar_terminoID_by_modelo_name_and_relation_data[$name])) return $get_ar_terminoID_by_modelo_name_and_relation_data[$name];

		#if(SHOW_DEBUG===true) $start_time = start_time();
		
		#dump($tipo);	#dump(debug_backtrace());
		
		switch($relation_type) {
			
			case 'children' :
				
					# Obtenemos los hijos
					$RecordObj_ts	= new RecordObj_ts($tipo);
					$ar_childrens	= $RecordObj_ts->get_ar_childrens_of_this();		#dump($ar_childrens,$section);
					
					# los recorremos para filtrar por modelo
					if(is_array($ar_childrens)) foreach($ar_childrens as $terminoID) {
						
						$RecordObj_ts			= new RecordObj_ts($terminoID);
						$modelo					= $RecordObj_ts->get_modelo();

						if(empty($modelo)) {
							$name = RecordObj_ts::get_termino_by_tipo($terminoID);
							throw new Exception("Error Processing Request. Modelo is empty. Please define modelo for this component $terminoID ($name)", 1);							
						}
						$current_modelo_name	= $RecordObj_ts->get_termino_by_tipo($modelo);	#dump($modelo_name);
						
						if(strpos($current_modelo_name, $modelo_name) !== false) {
							 $result[] = $terminoID;
							 #break;
						}
					}
					break;

			case 'children_recursive' :
				
					# Obtenemos los hijos
					$RecordObj_ts	= new RecordObj_ts($tipo);
					$ar_childrens	= $RecordObj_ts->get_ar_recursive_childrens_of_this($tipo);		#dump($ar_childrens);
					
					# los recorremos para filtrar por modelo
					if(is_array($ar_childrens)) foreach($ar_childrens as $terminoID) {
						
						$RecordObj_ts			= new RecordObj_ts($terminoID);
						$modelo					= $RecordObj_ts->get_modelo();

						if(empty($modelo)) {
							$name = RecordObj_ts::get_termino_by_tipo($terminoID);
							throw new Exception("Error Processing Request. Modelo is empty. Please define modelo for this component $terminoID ($name)", 1);							
						}
						$current_modelo_name	= $RecordObj_ts->get_termino_by_tipo($modelo);	#dump($modelo_name);
						
						if(strpos($current_modelo_name, $modelo_name) !== false) {
							 $result[] = $terminoID;
							 #break;
						}
					}
					break;
					
			case 'termino_relacionado' :
				
					# Obtenemos los tr
					$ar_terminos_relacionados	= self::get_ar_terminos_relacionados($tipo, $cache=true);	#dump($ar_terminos_relacionados);
					
					# los recorremos para filtrar por modelo
					if(is_array($ar_terminos_relacionados)) foreach($ar_terminos_relacionados as $terminoID) {
						
						$RecordObj_ts			= new RecordObj_ts($terminoID);
						$modelo					= $RecordObj_ts->get_modelo();

						if(empty($modelo)) {
							$name = RecordObj_ts::get_termino_by_tipo($terminoID);
							throw new Exception("Error Processing Request. Modelo is empty. Please define modelo for this component $terminoID ($name)", 1);							
						}
						$current_modelo_name	= $RecordObj_ts->get_termino_by_tipo($modelo);		#dump($modelo_name);
						
						if(strpos($current_modelo_name, $modelo_name) !== false) {
							 $result[] = $terminoID;
							 #break;
						}
					}
					break;

			case 'parent' :
				
					# Obtenemos los padres
					$RecordObj_ts				= new RecordObj_ts($tipo);
					$ar_parents	= $RecordObj_ts->get_ar_parents_of_this();	#dump($ar_parents,'ar_parents');die();
					
					# los recorremos para filtrar por modelo
					if(is_array($ar_parents)) foreach($ar_parents as $terminoID) {
						
						$RecordObj_ts			= new RecordObj_ts($terminoID);
						$modelo					= $RecordObj_ts->get_modelo();

						if(empty($modelo)) {
							$name = RecordObj_ts::get_termino_by_tipo($terminoID);
							throw new Exception("Error Processing Request. Modelo is empty. Please define modelo for this component $terminoID ($name)", 1);							
						}
						$current_modelo_name	= $RecordObj_ts->get_termino_by_tipo($modelo);		#dump($modelo_name);
						
						if($current_modelo_name == $modelo_name) {
							 $result[] = $terminoID;
							 #break;
						}
					}
					break;
					
			default : 
					throw new Exception("relation_type [$relation_type] not defined!", 1);
					break;
		}		
		
		# STORE CACHE DATA
		$get_ar_terminoID_by_modelo_name_and_relation_data[$name] = $result ;

		#if(SHOW_DEBUG===true) $GLOBALS['log_messages'] .= exec_time($start_time, __METHOD__, $result );
		
		return $result;
	}
	
	

	




	/**
	* GET_AR_CHILDRENS_WITH_OPTIONS
	* Get array of terms (terminoID) with parent = $this->terminoID and filter options
	* @return array $ar_childrens_with_options
	* @see class.diffusion.php
	*/
	public function get_ar_childrens_with_options( $request_options ) {
			
		# COMPROBACIÓN
		if(!$this->terminoID || !$this->prefijo) return false;

		$options = new stdClass();
			$options->visible  		= null;
			$options->esmodelo 		= null;
			$options->esdescriptor 	= null;
			foreach ($request_options as $key => $value) {if (property_exists($options, $key)) $options->$key = $value;}				
		
		# SEARCH
		$arguments=array();
		$arguments['strPrimaryKeyName']	= 'terminoID';
		$arguments['parent']			= $this->terminoID;

		if( !empty($options->visible) )
			$arguments['visible']		= $options->visible;

		if( !empty($options->esdescriptor) )
			$arguments['esdescriptor']	= $options->esdescriptor;
		
		if( !empty($options->esmodelo) )
			$arguments['esmodelo']		= $options->esmodelo;					
				
		$ar_childrens_with_options = (array)$this->search($arguments);	
		
		return $ar_childrens_with_options;

	}//end get_ar_childrens_with_options
	
	
	/**
	* GET_AR_RECURSIVE_CHILDRENS_WITH_OPTIONS
	* @return array $ar_recursive_childrens_with_options
	* @see class.diffusion.php
	*/
	public function get_ar_recursive_childrens_with_options($terminoID, $is_recursion=0, $options=null) {
		
		static $ar_recursive_childrens_with_options ;

		if ($is_recursion==0) {
			$ar_recursive_childrens_with_options = array(); // reset on first loop
		}

		# creamos una instancia independiente de RecordObj_ts y sacamos los hijos directos		
		$ar_childrens_of_this 	= array();	# reset value every cycle
		$RecordObj_ts 			= new RecordObj_ts($terminoID);
		$ar_childrens_of_this 	= (array)$RecordObj_ts->get_ar_childrens_with_options( $options );
				
		foreach($ar_childrens_of_this as $children_terminoID) {
			
			# Add current element		
			$ar_recursive_childrens_with_options[] = $children_terminoID;		

			# Recursion
			$this->get_ar_recursive_childrens_with_options( $children_terminoID, 1, $options );
		}

		return $ar_recursive_childrens_with_options;
	}
	
	
	
	/**
	* UPDATE_TOPONOMY_CENTRAL_SYNC_JER
	* @return 
	*/
	public static function update_toponomy_central_sync_jer( $prefix, $parent, $esmodelo ) {

		$created_terminoID = null;

		$jerarquia_tipo = (int)Jerarquia::get_tipo_from_prefix( $prefix );
		if ( $jerarquia_tipo==2 ) {

			$options = new stdClass();
				$options->update_type 	= 'jer';
				$options->user_id 		= $_SESSION['dedalo4']['auth']['user_id'];
				$options->user_name 	= $_SESSION['dedalo4']['auth']['username'];
				$options->entity 		= DEDALO_ENTITY;
				$options->parent 		= $parent;
				$options->prefix 		= $prefix;
				$options->esmodelo  	= $esmodelo;

			$option_json = json_encode($options);

			$url = TOPONOMY_CENTRAL_SYNC .'?options='. rawurlencode($option_json);

			try {
			
				# SERVER REQUEST
				if( !$response = file_get_contents( $url ) ) {
					#dump($response, ' response ++ '.to_string());
					echo "<span class=\"error\">Error. Unable connect to server</span>";
					exit();
				}

				if ( !$response = json_decode($response) ) {
					echo "<span class=\"error\">Error. Unable decode server response</span>";
					exit();
				}

				if ($response->result==true) {

					# Server has create a new record. We use this created terminoID for local instalation
					$created_terminoID = $response->terminoID;
					debug_log(__METHOD__." Created terminoID $created_terminoID successufully ".to_string(), logger::DEBUG);

				}else{

					echo "Error. ".$response->msg;
					debug_log(__METHOD__." $response->msg ".to_string(), logger::DEBUG);
					exit();
				}

			} catch (Exception $e) {
				
				exit("<span class=\"error\"> Sorry. a problem was found on create new ts element (TOPONOMY_CENTRAL_SYNC) </span>");
			}

		}//end 	if (isset($datos_grupo->tipo) && $datos_grupo->tipo==2) {

		return $created_terminoID;
		
	}#end update_toponomy_central_sync_jer



	/**
	* UPDATE_TOPONOMY_CENTRAL_SYNC_DESCRIPTORS
	* @return 
	*/
	public static function update_toponomy_central_sync_descriptors( $termino, $parent, $lang, $tipo ) {

		$prefix = substr($parent,0,2);		
		$jerarquia_tipo = (int)Jerarquia::get_tipo_from_prefix( $prefix );
		if ( $jerarquia_tipo==2 ) {

			$options = new stdClass();
				$options->update_type 	= 'descriptors';
				$options->user_id 		= $_SESSION['dedalo4']['auth']['user_id'];
				$options->user_name 	= $_SESSION['dedalo4']['auth']['username'];
				$options->entity 		= DEDALO_ENTITY;
				$options->prefix 		= $prefix;	
				$options->parent 		= $parent;							
				$options->termino  		= $termino;
				$options->lang  		= $lang;
				$options->tipo  		= $tipo;

			$option_json = json_encode($options);

			$url = TOPONOMY_CENTRAL_SYNC .'?options='. rawurlencode($option_json);

			try {
			
				# SERVER REQUEST
				if( !$response = file_get_contents( $url ) ) {
					#dump($response, ' response ++ '.to_string());
					echo "<span class=\"error\">Error. Unable connect to server</span>";
					exit();
				}

				if ( !$response = json_decode($response) ) {
					echo "<span class=\"error\">Error. Unable decode server response</span>";
					exit();
				}

				if ($response->result==true) {
					
					debug_log(__METHOD__." Update descriptor with parent $parent successufully ".to_string(), logger::DEBUG);
					return true;

				}else{

					echo "Error. ".$response->msg;
					debug_log(__METHOD__." $response->msg ".to_string(), logger::DEBUG);
					exit();
				}

			} catch (Exception $e) {
				
				exit("<span class=\"error\"> Sorry. a problem was found on update ts element (TOPONOMY_CENTRAL_SYNC) </span>");
			}

		}//end 	if (isset($datos_grupo->tipo) && $datos_grupo->tipo==2) {

		return false;

	}#end update_toponomy_central_sync_descriptors
	
	
	

	
	
	
}
?>