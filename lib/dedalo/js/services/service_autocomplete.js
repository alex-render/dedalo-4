"use strict"
/**
* SERVICE_AUTOCOMPLETE
* Used as service by component_autocomplete, component_autocomplete_hi, component_relation_parent, component_relation_children, component_relation_related
* 
*/
var service_autocomplete = new function() {



	/**
	* INIT
	* @return bool
	*/
	this.init = function(request_options) {
	
		const self = this
		
		const options = {
			component_js    	 : null,
			autocomplete_wrapper : null,

		}
		for(let key in request_options) {
			if (options.hasOwnProperty(key)) { options[key] = request_options[key] }
		}		

		const wrapper = options.autocomplete_wrapper
			if (!wrapper) {
				alert("Error. Invalid wrapper");
				return false
			}

		self.build_autocomplete_dom({
			parent 		 : wrapper,
			component_js : options.component_js
		})


		return true
	};//end init



	/**
	* BUILD_AUTOCOMPLETE_DOM
	* Create the html of input search autocomplete
	* @return dom object input_obj
	*/
	this.build_autocomplete_dom = function(options) {

		const self = this
		
		const input_obj = common.create_dom_element({
			element_type 	: "input",
			class_name 	 	: "css_autocomplete_hi_search_field autocomplete_input",
			parent 			: options.parent		
		})
		input_obj.setAttribute("type", "text")
		input_obj.setAttribute("placeholder", "Find...")
		input_obj.setAttribute("autocomplete", "off")
		input_obj.setAttribute("autocorrect", "off")

		// <input class="css_autocomplete_hi_search_field" type="text" placeholder="Find..." 
		// data-id_wrapper="wrapper__hierarchy92_1_lg-nolan_edit__ts1" data-tipo="hierarchy92" data-hierarchy_types="[]" data-hierarchy_sections="[&quot;ca1&quot;,&quot;co1&quot;,&quot;es1&quot;,&quot;fr1&quot;,&quot;gr1&quot;,&quot;gt1&quot;,&quot;sv1&quot;,&quot;xk1&quot;,&quot;ru1&quot;,&quot;af1&quot;]" 
		// onfocus="component_autocomplete_hi.activate(this)" tabindex="1" autocomplete="off" autocorrect="off">

		input_obj.addEventListener("click", function(e){
			// Activate
			self.activate(input_obj, options.component_js)
		}, false)
		

		return input_obj
	};//end build_autocomplete_dom



 	/**
	* ACTIVATE
	* This method is invoked when user clicks on input text field of current component
	*/
	let cache = {};
	this.activate = function( input_obj, component_js ) {
	
		const self = this

		// wrap_div . From component wrapper
			const wrap_div = find_ancestor(input_obj, 'wrap_component')
				if (wrap_div===null) {
					if(SHOW_DEBUG) console.log(input_obj);
					return alert("component_relation_related:activate: Sorry: wrap_div dom element not found")
				}

		// VARS
			const tipo 				 = wrap_div.dataset.tipo
			const component_info 	 = (wrap_div.dataset.component_info) ? JSON.parse(wrap_div.dataset.component_info) : {}
			const propiedades 	 	 = component_info.propiedades || {}

			// Filter from propiedades (is descriptor for example)
			//const filter_custom 	 = propiedades.source.filter_custom ? propiedades.source.filter_custom : false			
			
			const distinct_values 	 = propiedades.distinct_values || false
			const relation_type 	 = wrap_div.dataset.relation_type || false
			const search_tipos 		 = wrap_div.dataset.search_tipos || false



		// HIERARCHY_SECTIONS . Default from wrapper dataset if not exists component function			
			const hierarchy_sections = wrap_div.dataset.hierarchy_sections || [] // typeof component_js["get_hierarchy_sections"]==="function" ? component_js.get_hierarchy_sections(wrap_div) : []
		
		// Custom events defined in propiedades
			let custom_events = []		
			if (propiedades.custom_events) {
				custom_events = propiedades.custom_events
			}
			if(SHOW_DEBUG===true) {
				console.log("[component_autocomplete_hi.activate] custom_events:",custom_events)
			}

		
		$(input_obj).autocomplete({
			delay 		: 250,
			minLength 	: component_js.min_length || 1,
			source: function( request, response ) {

				// Cache
				//const uid = tipo + '_' + request.term
				//if ( uid in cache ) {
				//	if(SHOW_DEBUG===true) {
				//		//console.log("From cache!!: ",uid);;
				//	}					
				//	response(cache[uid])
				//	return;
				//}

				// Component default behavior
				self.on_source({
					request 	 		: request,
					response 	 		: response,
					wrap_div 	 		: wrap_div,
					input_obj 	 		: input_obj,
					component_js 		: component_js,
					cache 		 		: cache,

					hierarchy_sections  : hierarchy_sections,
					distinct_values 	: distinct_values,
					relation_type 		: relation_type,
					search_tipos 		: search_tipos
				})
			},
			// When a option is selected in list
			select: function( event, ui ) {
				
				const custom_events_select = custom_events.filter(item => item.hasOwnProperty("select"))
				if (custom_events_select.length>0) {

					// Custom behavior
					for (let i = 0; i < custom_events_select.length; i++) {
						let fn = custom_events_select[i].select
						component_js[fn]({
							event 		 : event,
							ui 			 : ui,
							input_obj 	 : this,
							params 		 : custom_events_select[i].params || {},
							component_js : component_js,
							wrap_div 	 : wrap_div
						})
					}
					
				}else{

					// Default behavior
					self.on_select({
						event 		 : event,
						ui 			 : ui,
						input_obj 	 : this,
						params 		 : {},
						component_js : component_js,
						wrap_div 	 : wrap_div
					})
				}
			},
			// When a option is focus in list
			focus: function( event, ui ) {
				event.preventDefault(); // prevent set selected value to autocomplete input
			},
			change: function( event, ui ) {
				this.value = ''
			},
			response: function( event, ui ) {
			}
		});//end $(this).autocomplete({	

		return true	
	};//end this.activate



	/**
	* ON_SOURCE
	* Used by all autocomplete callers of component_autocomplete_hi (parent, children, relation, autocomplete_hi, ..)
	* @return promise js_promise
	*/
	this.on_source = function(options) {
		
		const self = this

		let start = new Date().getTime();

		const request 				= options.request
		const response				= options.response
		const wrap_div				= options.wrap_div
		const input_obj 			= options.input_obj
		const tipo 					= wrap_div.dataset.tipo
		const from_component_tipo	= wrap_div.dataset.from_component_tipo || wrap_div.dataset.tipo
		const term  				= request.term
		const component_js 			= options.component_js

		const component_info 	 	= (wrap_div.dataset.component_info) ? JSON.parse(wrap_div.dataset.component_info) : {}
		const propiedades 	 	 	= component_info.propiedades || {}
		// Filter from propiedades (is descriptor for example)
		const filter_custom 	 	= (propiedades.source && propiedades.source.filter_custom) ? propiedades.source.filter_custom : false

		const cache 				= options.cache
	
		//if (term===' ') {
		//	response([])
		//}

		//const section_tipo 	= wrap_div.dataset.section_tipo
		//const divisor 		= wrap_div.dataset.divisor

		// HIERARCHY_SECTIONS . Default from wrapper dataset if not exists component function
		let hierarchy_sections = []
		if (typeof component_js["get_hierarchy_sections"]==="function") {
			// Custom method. Normally when toponymy filter is used
			hierarchy_sections = component_js.get_hierarchy_sections(wrap_div)
		}else{
			// Fallback to basic method (get data from dataset)
			hierarchy_sections = self.get_hierarchy_sections(wrap_div)
		}
		
		const distinct_values 	= options.distinct_values
		const relation_type 	= options.relation_type
		const search_tipos 		= options.search_tipos
		

		const trigger_url  = component_js.autocomplete_trigger_url
		const trigger_vars = {
			mode 				: 'autocomplete',
			string_to_search 	: term,
			from_component_tipo : from_component_tipo,
			top_tipo 			: page_globals.top_tipo,
			
			hierarchy_sections 	: hierarchy_sections,
			distinct_values 	: distinct_values,
			relation_type 		: relation_type,
			search_tipos 		: search_tipos,
			filter_custom 		: filter_custom
		}; console.log("[service_autocomplete.on_source] trigger_vars", trigger_vars, trigger_url);  //return
		
		// Ajax call to trigger
		const js_promise = common.get_json_data(trigger_url, trigger_vars).then(function(response_data) {
				if(SHOW_DEBUG===true) {
					const end = new Date().getTime(); const time = end - start;
					//console.log("Time for "+term+": "+time+"ms");
					console.log("[service_autocomplete] response_data", response_data, "Total: "+time+" ms");
				}

				// fix received search_query_object (from freeze)
				component_js.search_query_object = response_data.search_query_object
				
				// Format result for use in jquery label / value
				const label_value = self.convert_data_to_label_value(response_data.result)

				// Cache result
				//if (label_value && label_value.length>0) {
				//	const uid  = tipo + '_' + term + '_' + hierarchy_sections.join('')
				//	cache[uid] = label_value
				//}

				response(label_value)
				
			}, function(error) {
				console.error("[service_autocomplete] Failed get_json!", error);
			});

		return js_promise
	};//end on_source



	/**
	* ON_SELECT
	* Default action on autocomplete select event
	* @return bool true
	*/
	this.on_select = function(options) {
	
		const self = this

		// Prevent set selected value to autocomplete input
		options.event.preventDefault()
		// Clean input value
		options.input_obj.value = ''

		// Default behavior
		const ui 	= options.ui
		const label = ui.item.label
		const value = JSON.parse(ui.item.value)

		const wrap_div = options.wrap_div
			if (!wrap_div) {
				alert("[on_select] Error on select wrapper");
				return false
			}

		// Add locator (and saves in edit mode)
		options.component_js.add_locator(value, wrap_div, ui)

		// Blur input
		options.input_obj.blur()

		return true
	};//end on_select



	/**
	* CONVERT_DATA_TO_LABEL_VALUE
	* @return array result
	*/
	this.convert_data_to_label_value = function(data) {
		
		const result = []
		const len 	 = data.length
		for (var i = 0; i < len; i++) {
			
			const obj = {
				label 	 : data[i].label, // Label with additional info (parents, model, etc.)
				value 	 : data[i].key,   // Locator stringnified
				original : data[i].value  // Original value without parents etc
			}
			result.push(obj)
		}

		// Sort result by original property
		result.sort(function(a,b) {return (a.label.toLowerCase() > b.label.toLowerCase()) ? 1 : ((b.label.toLowerCase() > a.label.toLowerCase()) ? -1 : 0);} );

		
		return result
	};//end convert_data_to_label_value



	/**
	* GET_HIERARCHY_SECTIONS
	* Generic method. If you need something more, override in component js this method
	* @return array of checked hierarchy_sections sections
	*/
	this.get_hierarchy_sections = function(wrap_div) {
		
		// Get from wrapper dataset
		const selected_values = JSON.parse(wrap_div.dataset.hierarchy_sections)
		

		return selected_values
	}//end get_hierarchy_sections



}//end class