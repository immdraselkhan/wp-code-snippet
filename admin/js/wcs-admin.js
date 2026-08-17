( function () {
	'use strict';

	if ( ! window.wp || ! wp.element || ! wp.components ) {
		return;
	}

	var h = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;
	var C = wp.components;
	var Button = C.Button;
	var TextControl = C.TextControl;
	var TextareaControl = C.TextareaControl;
	var ToggleControl = C.ToggleControl;
	var SelectControl = C.SelectControl;
	var RadioControl = C.RadioControl;
	var CheckboxControl = C.CheckboxControl;
	var Panel = C.Panel;
	var PanelBody = C.PanelBody;
	var Notice = C.Notice;
	var Modal = C.Modal;
	var Spinner = C.Spinner;
	var Snackbar = C.Snackbar;
	var SearchControl = C.SearchControl;
	var BaseControl = C.BaseControl;

	var MODE_MAP = {
		php: 'application/x-httpd-php',
		html: 'htmlmixed',
		css: 'css',
		js: 'javascript'
	};

	var TYPE_OPTIONS = [
		{ label: 'PHP', value: 'php' },
		{ label: 'HTML', value: 'html' },
		{ label: 'CSS', value: 'css' },
		{ label: 'JavaScript', value: 'js' }
	];

	var LOCATIONS = {
		php: [
			{ label: 'Run Everywhere', value: 'run_everywhere', help: 'Runs like functions.php on both the frontend and wp-admin.' },
			{ label: 'Frontend Only', value: 'frontend_only', help: 'Runs only for frontend requests.' },
			{ label: 'Admin Only', value: 'admin_only', help: 'Runs only inside wp-admin.' }
		],
		nonphp: [
			{ label: 'Site Header', value: 'wp_head', help: 'Outputs in wp_head before </head>.' },
			{ label: 'Site Footer', value: 'wp_footer', help: 'Outputs in wp_footer before </body>.' },
			{ label: 'Admin Header', value: 'admin_head', help: 'Outputs in admin_head.' },
			{ label: 'Admin Footer', value: 'admin_footer', help: 'Outputs in admin_footer.' },
			{ label: 'Login Page', value: 'login_head', help: 'Outputs in login_head.' },
			{ label: 'Shortcode', value: 'shortcode', help: 'Outputs only where its shortcode is inserted.' }
		]
	};

	var CONDITION_FIELDS = {
		request_context: { group: 'Request', label: 'Request Context', operators: [ 'equals', 'not_equals' ], options: { frontend:'Frontend', admin:'Admin', login:'Login page', ajax:'AJAX', rest:'REST API', cron:'WP-Cron' } },
		request_method: { group: 'Request', label: 'Request Method', operators: [ 'equals', 'not_equals' ], options: { GET:'GET', POST:'POST', PUT:'PUT', PATCH:'PATCH', DELETE:'DELETE', HEAD:'HEAD' } },
		url: { group: 'Request', label: 'Current URL', operators: [ 'contains', 'not_contains', 'equals', 'not_equals', 'starts_with', 'ends_with', 'regex' ], placeholder: '/checkout or example.com/path' },
		query_string: { group: 'Request', label: 'Query String', operators: [ 'contains', 'not_contains', 'equals', 'regex', 'exists', 'not_exists' ], placeholder: 'utm_source=google' },
		query_param: { group: 'Request', label: 'Query Parameter', operators: [ 'equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists' ], placeholder: 'key=value' },
		referrer: { group: 'Request', label: 'Referrer URL', operators: [ 'contains', 'not_contains', 'equals', 'starts_with', 'ends_with', 'regex', 'exists', 'not_exists' ], placeholder: 'google.com' },
		cookie: { group: 'Request', label: 'Cookie', operators: [ 'equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists' ], placeholder: 'cookie_name=value' },
		page_type: { group: 'WordPress Content', label: 'Page Type', operators: [ 'equals', 'not_equals' ], options: { front_page:'Front page', blog_home:'Blog home', singular:'Any singular', archive:'Any archive', category:'Category archive', tag:'Tag archive', taxonomy:'Taxonomy archive', search:'Search results', '404':'404 page', feed:'Feed' } },
		post_type: { group: 'WordPress Content', label: 'Post Type', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'post, page, product' },
		post_id: { group: 'WordPress Content', label: 'Post / Page / Product ID', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: '12, 45, 88' },
		post_status: { group: 'WordPress Content', label: 'Post Status', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'publish, draft, private' },
		post_author: { group: 'WordPress Content', label: 'Post Author ID', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: '1, 5, 12' },
		taxonomy_term: { group: 'WordPress Content', label: 'Taxonomy Term', operators: [ 'equals', 'not_equals', 'contains', 'not_contains' ], placeholder: 'taxonomy=term-slug' },
		template: { group: 'WordPress Content', label: 'Page Template', operators: [ 'equals', 'not_equals', 'contains', 'not_contains' ], placeholder: 'templates/landing.php' },
		logged_in: { group: 'User', label: 'Login State', operators: [ 'equals', 'not_equals' ], options: { yes:'Logged in', no:'Logged out' } },
		user_role: { group: 'User', label: 'User Role', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'administrator, editor, customer' },
		user_id: { group: 'User', label: 'User ID', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: '1, 25, 88' },
		capability: { group: 'User', label: 'User Capability', operators: [ 'exists', 'not_exists' ], placeholder: 'manage_woocommerce' },
		locale: { group: 'User', label: 'Locale / Language', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'en_US, bn_BD' },
		device: { group: 'User', label: 'Device', operators: [ 'equals', 'not_equals' ], options: { desktop:'Desktop / tablet', mobile:'Mobile' } },
		weekday: { group: 'Date & Time', label: 'Day of Week', operators: [ 'equals', 'not_equals' ], options: { monday:'Monday', tuesday:'Tuesday', wednesday:'Wednesday', thursday:'Thursday', friday:'Friday', saturday:'Saturday', sunday:'Sunday' } },
		date: { group: 'Date & Time', label: 'Date', operators: [ 'equals', 'before', 'after' ], placeholder: '2026-08-17' },
		time: { group: 'Date & Time', label: 'Time', operators: [ 'equals', 'before', 'after', 'between' ], placeholder: '09:00 or 09:00-17:30' },
		woocommerce: { group: 'WooCommerce', label: 'WooCommerce Screen', operators: [ 'equals', 'not_equals' ], options: { shop:'Shop', product:'Single product', product_category:'Product category', product_tag:'Product tag', cart:'Cart', checkout:'Checkout', account:'My Account', order_received:'Order received' } },
		woo_product_type: { group: 'WooCommerce', label: 'Product Type', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'simple, variable, grouped, external' },
		woo_category: { group: 'WooCommerce', label: 'Product Category', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'solar-panel, inverter' },
		woo_cart_product: { group: 'WooCommerce', label: 'Cart Contains Product ID', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: '123, 456' },
		woo_cart_category: { group: 'WooCommerce', label: 'Cart Contains Category', operators: [ 'in', 'not_in', 'equals', 'not_equals' ], placeholder: 'battery, inverter' }
	};

	var OPERATOR_LABELS = {
		equals: 'Equals', not_equals: 'Does not equal', in: 'Is any of', not_in: 'Is none of',
		contains: 'Contains', not_contains: 'Does not contain', starts_with: 'Starts with', ends_with: 'Ends with',
		regex: 'Matches regex', exists: 'Exists / is true', not_exists: 'Does not exist / is false', before: 'Before', after: 'After', between: 'Between'
	};

	function post( payload ) {
		var data = new URLSearchParams();
		Object.keys( payload ).forEach( function ( key ) {
			var value = payload[ key ];
			if ( Array.isArray( value ) ) {
				value.forEach( function ( item ) { data.append( key + '[]', item ); } );
			} else {
				data.append( key, value == null ? '' : value );
			}
		} );
		return fetch( wcsAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		} ).then( function ( response ) {
			return response.text().then( function ( text ) {
				var json;
				try { json = JSON.parse( text ); } catch ( e ) { throw new Error( 'Invalid AJAX response (HTTP ' + response.status + ').' ); }
				if ( ! json.success ) {
					throw new Error( json.data && json.data.message ? json.data.message : wcsAdmin.strings.error );
				}
				return json.data;
			} );
		} );
	}

	function viewFromLocation() {
		var params = new URLSearchParams( window.location.search );
		var page = params.get( 'page' ) || 'wp-code-snippet';
		if ( page === 'wp-code-snippet-settings' ) { return { view: 'settings', params: {} }; }
		if ( page === 'wp-code-snippet-add' ) {
			var id = parseInt( params.get( 'id' ) || '0', 10 ) || 0;
			return { view: id ? 'edit' : 'add', params: { id: id } };
		}
		return {
			view: 'list',
			params: {
				status: params.get( 'status' ) || '',
				search: params.get( 's' ) || '',
				paged: parseInt( params.get( 'paged' ) || '1', 10 ) || 1
			}
		};
	}

	function buildUrl( view, params ) {
		params = params || {};
		var page = view === 'settings' ? 'wp-code-snippet-settings' : ( view === 'add' || view === 'edit' ? 'wp-code-snippet-add' : 'wp-code-snippet' );
		var url = wcsAdmin.baseUrl + '?page=' + page;
		if ( params.id ) { url += '&id=' + encodeURIComponent( params.id ); }
		if ( params.status ) { url += '&status=' + encodeURIComponent( params.status ); }
		if ( params.search ) { url += '&s=' + encodeURIComponent( params.search ); }
		if ( params.paged && params.paged > 1 ) { url += '&paged=' + encodeURIComponent( params.paged ); }
		return url;
	}

	function legacyConditions( saved ) {
		if ( typeof saved === 'string' ) {
			try { saved = JSON.parse( saved ); } catch ( e ) { saved = {}; }
		}
		if ( saved && Array.isArray( saved.rules ) ) { return saved; }
		var out = { version: 2, scope: ( saved && saved.scope ) || 'include', logic: 'and', rules: [] };
		if ( ! saved || ! saved.rule || saved.rule === 'everywhere' ) { return out; }
		if ( saved.rule === 'specific' ) {
			if ( saved.post_types && saved.post_types.length ) { out.rules.push( { field: 'post_type', operator: 'in', value: saved.post_types.join( ',' ) } ); }
			if ( saved.post_ids && saved.post_ids.length ) { out.rules.push( { field: 'post_id', operator: 'in', value: saved.post_ids.join( ',' ) } ); }
		} else if ( saved.rule === 'device' ) {
			out.rules.push( { field: 'device', operator: 'equals', value: saved.device || 'mobile' } );
		} else if ( saved.rule === 'url_contains' ) {
			out.rules.push( { field: 'url', operator: 'contains', value: saved.url_value || '' } );
		} else if ( saved.rule === 'user_role' ) {
			if ( saved.logged_in && saved.logged_in !== 'any' ) { out.rules.push( { field: 'logged_in', operator: 'equals', value: saved.logged_in === 'in' ? 'yes' : 'no' } ); }
			if ( saved.roles && saved.roles.length ) { out.rules.push( { field: 'user_role', operator: 'in', value: saved.roles.join( ',' ) } ); }
		}
		return out;
	}

	function conditionGroups() {
		var order = [ 'Request', 'WordPress Content', 'User', 'Date & Time', 'WooCommerce' ];
		var groups = {};
		order.forEach( function ( group ) { groups[ group ] = []; } );
		Object.keys( CONDITION_FIELDS ).forEach( function ( key ) {
			var cfg = CONDITION_FIELDS[ key ];
			if ( ! groups[ cfg.group ] ) { groups[ cfg.group ] = []; }
			groups[ cfg.group ].push( { label:cfg.label, value:key } );
		} );
		return order.map( function ( group ) { return { label:group, options:groups[ group ] || [] }; } ).filter( function ( group ) { return group.options.length; } );
	}

	function GroupedConditionSelect( props ) {
		return h( BaseControl, { label:'Condition', hideLabelFromVision:true, className:'wcs-grouped-condition-control' },
			h( 'select', {
				className:'components-select-control__input',
				value:props.value || '',
				onChange:function ( event ) { props.onChange( event.target.value ); },
				'aria-label':'Condition'
			},
				h( 'option', { value:'' }, 'Select a condition' ),
				conditionGroups().map( function ( group ) {
					return h( 'optgroup', { key:group.label, label:group.label }, group.options.map( function ( option ) {
						return h( 'option', { key:option.value, value:option.value }, option.label );
					} ) );
				} )
			)
		);
	}

	function operatorOptions( field ) {
		var cfg = CONDITION_FIELDS[ field ];
		if ( ! cfg ) { return [ { label: 'Select a comparison', value: '' } ]; }
		return cfg.operators.map( function ( op ) { return { label: OPERATOR_LABELS[ op ] || op, value: op }; } );
	}

	function valueOptions( field ) {
		var cfg = CONDITION_FIELDS[ field ];
		if ( ! cfg || ! cfg.options ) { return null; }
		return [ { label: 'Select a value', value: '' } ].concat( Object.keys( cfg.options ).map( function ( key ) { return { label: cfg.options[ key ], value: key }; } ) );
	}

	function validateJavaScript( code ) {
		try { new Function( code ); return { valid: true, message: 'JavaScript syntax is valid.' }; } catch ( e ) { return { valid: false, message: e.message || 'JavaScript syntax error.' }; }
	}

	function validateBalancedText( code, label ) {
		var stack = [], quote = '', escaped = false, comment = '';
		var pairs = { '}': '{', ']': '[', ')': '(' };
		for ( var i = 0; i < code.length; i++ ) {
			var c = code[ i ], n = code[ i + 1 ] || '';
			if ( comment === 'line' ) { if ( c === '\n' ) { comment = ''; } continue; }
			if ( comment === 'block' ) { if ( c === '*' && n === '/' ) { comment = ''; i++; } continue; }
			if ( quote ) { if ( escaped ) { escaped = false; continue; } if ( c === '\\' ) { escaped = true; continue; } if ( c === quote ) { quote = ''; } continue; }
			if ( c === '/' && n === '*' ) { comment = 'block'; i++; continue; }
			if ( c === '"' || c === "'" ) { quote = c; continue; }
			if ( c === '{' || c === '[' || c === '(' ) { stack.push( c ); }
			else if ( pairs[ c ] && stack.pop() !== pairs[ c ] ) { return { valid: false, message: 'Unexpected "' + c + '".' }; }
		}
		if ( quote ) { return { valid: false, message: 'Unclosed string.' }; }
		if ( comment === 'block' ) { return { valid: false, message: 'Unclosed block comment.' }; }
		if ( stack.length ) { return { valid: false, message: 'Unclosed "' + stack[ stack.length - 1 ] + '".' }; }
		return { valid: true, message: label + ' structure is valid.' };
	}

	function validateHtml( code ) {
		var voidTags = { area:1, base:1, br:1, col:1, embed:1, hr:1, img:1, input:1, link:1, meta:1, param:1, source:1, track:1, wbr:1 };
		var stack = [], re = /<\/?([a-zA-Z][\w:-]*)\b[^>]*>/g, m;
		while ( ( m = re.exec( code ) ) ) {
			var raw = m[ 0 ], tag = m[ 1 ].toLowerCase();
			if ( voidTags[ tag ] || /\/>$/.test( raw ) ) { continue; }
			if ( raw[ 1 ] === '/' ) {
				if ( stack.pop() !== tag ) { return { valid: false, message: 'Mismatched closing tag </' + tag + '>.' }; }
			} else { stack.push( tag ); }
		}
		if ( stack.length ) { return { valid: false, message: 'Unclosed <' + stack[ stack.length - 1 ] + '> tag.' }; }
		return { valid: true, message: 'HTML structure is valid.' };
	}

	function Header( props ) {
		return h( 'div', { className: 'wcs-app-header' },
			h( 'div', { className: 'wcs-app-heading' },
				props.back ? h( Button, { variant: 'secondary', onClick: props.onBack, className: 'wcs-back-button' }, '←' ) : null,
				h( 'div', null,
					h( 'h1', null, props.title ),
					props.subtitle ? h( 'p', null, props.subtitle ) : null
				)
			),
			props.actions ? h( 'div', { className: 'wcs-header-actions' }, props.actions ) : null
		);
	}

	function Loading() {
		return h( 'div', { className: 'wcs-loading' }, h( Spinner ), h( 'span', null, 'Loading…' ) );
	}

	function EmptyState( props ) {
		return h( 'div', { className: 'wcs-empty' },
			h( 'h2', null, 'No snippets found' ),
			h( 'p', null, 'Try a different filter or search, or create a new snippet.' ),
			h( Button, { variant: 'primary', onClick: props.onAdd }, 'Add New Snippet' )
		);
	}

	function CodeEditor( props ) {
		var ref = useRef( null );
		var cmRef = useRef( null );
		useEffect( function () {
			if ( ! ref.current ) { return; }
			if ( wp.codeEditor && window.jQuery ) {
				var settings = window.jQuery.extend( true, {}, wcsCodeEditor || {} );
				settings.codemirror = settings.codemirror || {};
				settings.codemirror.mode = MODE_MAP[ props.type ];
				var instance = wp.codeEditor.initialize( ref.current, settings );
				if ( instance && instance.codemirror ) {
					cmRef.current = instance.codemirror;
					cmRef.current.on( 'change', function ( cm ) { props.onChange( cm.getValue() ); } );
				}
			}
			return function () {
				if ( cmRef.current && cmRef.current.toTextArea ) {
					try { cmRef.current.toTextArea(); } catch ( e ) {}
				}
				cmRef.current = null;
			};
		}, [] );
		useEffect( function () {
			if ( cmRef.current ) { cmRef.current.setOption( 'mode', MODE_MAP[ props.type ] ); cmRef.current.refresh(); }
		}, [ props.type ] );
		return h( 'textarea', {
			ref: ref,
			className: 'wcs-code-textarea',
			defaultValue: props.value,
			onChange: function ( e ) { if ( ! cmRef.current ) { props.onChange( e.target.value ); } }
		} );
	}

	function CodeTypeSwitcher( props ) {
		return h( 'div', { className:'wcs-code-type-switcher', role:'group', 'aria-label':'Snippet type' },
			TYPE_OPTIONS.map( function ( item ) {
				return h( Button, {
					key:item.value,
					variant:props.value === item.value ? 'primary' : 'secondary',
					className:'wcs-code-type-button',
					onClick:function () { props.onChange( item.value ); }
				}, item.label );
			} )
		);
	}

	function ValidationBar( props ) {
		var label = props.state === 'checking' ? 'Checking…' : ( props.state === 'valid' ? 'Valid' : 'Needs attention' );
		return h( 'div', { className:'wcs-validation-bar is-' + props.state, role:props.state === 'invalid' ? 'alert' : 'status' },
			h( 'strong', null, label ),
			h( 'span', null, props.message )
		);
	}

	function PlacementPicker( props ) {
		return h( 'div', { className:'wcs-placement-picker', role:'radiogroup', 'aria-label':'Run location' },
			props.options.map( function ( item ) {
				var selected = props.value === item.value;
				return h( Button, {
					key:item.value,
					variant:'secondary',
					className:'wcs-placement-option' + ( selected ? ' is-selected' : '' ),
					'aria-pressed':selected,
					onClick:function () { props.onChange( item.value ); }
				},
					h( 'span', { className:'wcs-placement-copy' },
						h( 'span', { className:'wcs-placement-title-row' },
							h( 'span', { className:'wcs-placement-option-label' }, item.label ),
							selected ? h( 'span', { className:'wcs-placement-selected' }, h( 'span', { className:'wcs-placement-selected-icon', 'aria-hidden':'true' }, '✓' ), h( 'span', null, 'Selected' ) ) : null
						),
						h( 'span', { className:'wcs-placement-option-help' }, item.help )
					)
				);
			} )
		);
	}

	function ConditionRow( props ) {
		var rule = props.rule;
		var cfg = CONDITION_FIELDS[ rule.field ];
		var valueOpts = valueOptions( rule.field );
		var noValue = rule.operator === 'exists' || rule.operator === 'not_exists';
		function patch( changes ) { props.onChange( Object.assign( {}, rule, changes ) ); }
		return h( 'div', { className:'wcs-rule-row' },
			h( 'div', { className:'wcs-rule-number', 'aria-hidden':'true' }, String( props.index + 1 ) ),
			h( GroupedConditionSelect, {
				value:rule.field || '',
				onChange:function ( field ) {
					var next = CONDITION_FIELDS[ field ];
					patch( { field:field, operator:next ? next.operators[ 0 ] : '', value:'' } );
				}
			} ),
			h( SelectControl, {
				label:'Comparison', hideLabelFromVision:true, value:rule.operator || '', options:operatorOptions( rule.field ), disabled:! rule.field,
				onChange:function ( operator ) { patch( { operator:operator, value:( operator === 'exists' || operator === 'not_exists' ) ? '' : rule.value } ); }
			} ),
			noValue ? h( 'div', { className:'wcs-rule-value-empty' }, 'No value required' ) : ( valueOpts ?
				h( SelectControl, { label:'Value', hideLabelFromVision:true, value:rule.value || '', options:valueOpts, disabled:! rule.field, onChange:function ( value ) { patch( { value:value } ); } } ) :
				h( TextControl, { label:'Value', hideLabelFromVision:true, value:rule.value || '', disabled:! rule.field, placeholder:cfg ? cfg.placeholder : 'Value', onChange:function ( value ) { patch( { value:value } ); } } )
			),
			h( Button, { variant:'tertiary', isDestructive:true, className:'wcs-rule-remove', onClick:props.onRemove, 'aria-label':'Remove condition ' + ( props.index + 1 ) }, 'Remove' )
		);
	}

	function Conditions( props ) {
		var conditions = props.value || { scope:'include', logic:'and', rules:[] };
		var rules = conditions.rules || [];
		var enabled = rules.length > 0;
		function update( patch ) { props.onChange( Object.assign( {}, conditions, patch ) ); }
		function updateRule( index, rule ) { var next = rules.slice(); next[ index ] = rule; update( { rules:next } ); }
		function removeRule( index ) { update( { rules:rules.filter( function ( _, i ) { return i !== index; } ) } ); }
		function addRule() { update( { rules:rules.concat( [ { field:'', operator:'', value:'' } ] ) } ); }
		function setEnabled( checked ) { update( { rules:checked ? ( rules.length ? rules : [ { field:'', operator:'', value:'' } ] ) : [] } ); }

		return h( 'div', { className:'wcs-logic-box' },
			h( 'div', { className:'wcs-logic-heading' },
				h( 'div', null,
					h( 'strong', null, 'Conditional logic' ),
					h( 'p', null, enabled ? 'Only run this placement when the rules below match.' : 'No extra rules. This snippet uses the selected placement everywhere it applies.' )
				),
				h( ToggleControl, { label:'Enable', hideLabelFromVision:true, checked:enabled, onChange:setEnabled } )
			),
			enabled ? h( Fragment, null,
				h( 'div', { className:'wcs-logic-sentence' },
					h( SelectControl, { label:'Action', hideLabelFromVision:true, value:conditions.scope || 'include', options:[ { label:'Run this snippet', value:'include' }, { label:'Do not run this snippet', value:'exclude' } ], onChange:function ( scope ) { update( { scope:scope } ); } } ),
				h( 'span', null, 'when' ),
				h( SelectControl, { label:'Match', hideLabelFromVision:true, value:conditions.logic || 'and', options:[ { label:'all', value:'and' }, { label:'any', value:'or' } ], onChange:function ( logic ) { update( { logic:logic } ); } } ),
				h( 'span', null, 'of these conditions match:' )
			),
				h( 'div', { className:'wcs-rule-table' },
					h( 'div', { className:'wcs-rule-header', 'aria-hidden':'true' },
					h( 'span', null, '#' ), h( 'span', null, 'Condition' ), h( 'span', null, 'Comparison' ), h( 'span', null, 'Value' ), h( 'span', null, '' )
				),
				rules.map( function ( rule, index ) { return h( ConditionRow, { key:index, index:index, rule:rule, onChange:function ( next ) { updateRule( index, next ); }, onRemove:function () { removeRule( index ); } } ); } )
			),
			 h( Button, { variant:'secondary', onClick:addRule }, 'Add condition' )
		) : null
		);
	}

	function EditScreen( props ) {
		var original = props.data.snippet;
		var statePair = useState( Object.assign( {}, original, { conditions: legacyConditions( original.conditions || {} ) } ) );
		var snippet = statePair[ 0 ], setSnippet = statePair[ 1 ];
		var tabPair = useState( 'code' );
		var tab = tabPair[ 0 ], setTab = tabPair[ 1 ];
		var savingPair = useState( false );
		var saving = savingPair[ 0 ], setSaving = savingPair[ 1 ];
		var validationPair = useState( { state:'checking', message:'Checking syntax…' } );
		var validation = validationPair[ 0 ], setValidation = validationPair[ 1 ];
		var validationTimer = useRef( null );
		var validationSeq = useRef( 0 );

		function patch( changes ) { setSnippet( function ( current ) { return Object.assign( {}, current, changes ); } ); }

		useEffect( function () {
			clearTimeout( validationTimer.current );
			validationTimer.current = setTimeout( function () {
				var code = snippet.code || '';
				if ( snippet.type === 'php' ) {
					var seq = ++validationSeq.current;
					setValidation( { state:'checking', message:'Checking PHP syntax…' } );
					post( { action:'wcs_validate_php', nonce:wcsAdmin.nonce, code:code } ).then( function ( result ) {
						if ( seq !== validationSeq.current ) { return; }
						setValidation( { state:result.valid ? 'valid' : 'invalid', message:result.valid ? 'PHP syntax is valid.' : ( result.message || 'PHP syntax error.' ) } );
					} ).catch( function ( err ) {
						if ( seq === validationSeq.current ) { setValidation( { state:'invalid', message:err.message } ); }
					} );
					return;
				}
				var result = snippet.type === 'js' ? validateJavaScript( code ) : ( snippet.type === 'html' ? validateHtml( code ) : validateBalancedText( code, 'CSS' ) );
				setValidation( { state:result.valid ? 'valid' : 'invalid', message:result.message } );
			}, 300 );
			return function () { clearTimeout( validationTimer.current ); };
		}, [ snippet.code, snippet.type ] );

		var locations = snippet.type === 'php' ? LOCATIONS.php : LOCATIONS.nonphp;
		if ( ! locations.some( function ( item ) { return item.value === snippet.location; } ) ) {
			setTimeout( function () { patch( { location: locations[ 0 ].value } ); }, 0 );
		}
		var currentLocation = locations.filter( function ( item ) { return item.value === snippet.location; } )[ 0 ] || locations[ 0 ];

		function save() {
			if ( saving ) { return; }
			setSaving( true );
			post( {
				action:'wcs_save_snippet', nonce:wcsAdmin.nonce, snippet_id:snippet.id || 0,
				title:snippet.title || '', description:snippet.description || '', code:snippet.code || '', type:snippet.type,
				location:snippet.location, conditions:JSON.stringify( snippet.conditions || {} ), priority:snippet.priority || 10,
				shortcode_tag:snippet.shortcode_tag || '', status:snippet.status === 'active' ? 'active' : 'inactive'
			} ).then( function ( result ) {
				var next = Object.assign( {}, snippet, { id: result.id, status: result.status } );
				setSnippet( next );
				props.toast( result.noticeText || 'Snippet saved.' );
				if ( ! original.id && result.id ) {
					window.history.replaceState( {}, '', buildUrl( 'edit', { id: result.id } ) );
				}
			} ).catch( function ( err ) { props.toast( err.message ); } ).finally( function () { setSaving( false ); } );
		}

		return h( Fragment, null,
			h( Header, { back:true, onBack:function () { props.navigate( 'list', {} ); }, title:snippet.id ? 'Edit Snippet' : 'Add New Snippet', subtitle:'Built with WordPress Block Editor components. Code is validated while you type.' } ),
			snippet.error_message ? h( 'div', { style:{ marginBottom:'20px' } }, h( Notice, { status:'error', isDismissible:false }, h( 'strong', null, 'This snippet was automatically deactivated. ' ), snippet.error_message ) ) : null,
			h( 'div', { className:'wcs-edit-layout' },
				h( 'div', { className:'wcs-edit-main' },
					h( Panel, null,
						h( PanelBody, { title:'Snippet details', initialOpen:true },
							h( 'div', { className:'wcs-snippet-details-fields' },
								h( TextControl, { label:'Snippet title', value:snippet.title || '', onChange:function ( value ) { patch( { title:value } ); } } ),
								h( TextareaControl, { label:'Description', help:'Optional.', value:snippet.description || '', rows:3, onChange:function ( value ) { patch( { description:value } ); } } )
							)
						)
					),
					h( 'div', { className:'wcs-tab-buttons' },
						h( Button, { variant:tab === 'code' ? 'primary' : 'secondary', onClick:function () { setTab( 'code' ); } }, 'Code' ),
						h( Button, { variant:tab === 'placement' ? 'primary' : 'secondary', onClick:function () { setTab( 'placement' ); } }, 'Placement & Logic' )
				),
				tab === 'code' ? h( Panel, null,
					h( PanelBody, { title:'Code', initialOpen:true },
						h( 'div', { className:'wcs-code-toolbar' },
							h( 'div', null,
								h( 'span', { className:'wcs-section-label' }, 'Snippet type' ),
								h( CodeTypeSwitcher, { value:snippet.type, onChange:function ( type ) { patch( { type:type } ); } } )
							),
							h( 'span', { className:'wcs-code-help' }, snippet.type === 'php' ? 'PHP opening tag is optional.' : 'Validated while you type.' )
						),
						h( 'div', { className:'wcs-editor-shell' },
							h( CodeEditor, { value:snippet.code || '', type:snippet.type, onChange:function ( code ) { patch( { code:code } ); } } )
						),
						h( ValidationBar, { state:validation.state, message:validation.message } )
					)
				) : h( Panel, null,
					h( PanelBody, { title:'Placement & Logic', initialOpen:true },
						h( 'div', { className:'wcs-placement-section' },
							h( 'div', { className:'wcs-placement-section-head' },
								h( 'strong', null, 'Run location' ),
								h( 'span', null, 'Choose where this snippet is allowed to run.' )
							),
							h( PlacementPicker, { options:locations, value:snippet.location, onChange:function ( location ) { patch( { location:location } ); } } )
						),
						snippet.location === 'shortcode' ? h( 'div', { className:'wcs-shortcode-field' }, h( TextControl, { label:'Shortcode tag', help:'Use letters, numbers, hyphens or underscores. Example: [my_snippet]', value:snippet.shortcode_tag || '', onChange:function ( value ) { patch( { shortcode_tag:value.replace( /[^a-zA-Z0-9_-]/g, '' ) } ); } } ) ) : null,
						h( Conditions, { value:snippet.conditions, onChange:function ( conditions ) { patch( { conditions:conditions } ); } } )
					)
				)
			),
				h( 'div', { className:'wcs-edit-sidebar' },
					h( Panel, null,
						h( PanelBody, { title:'Publish', initialOpen:true },
							h( ToggleControl, { label:'Active', help:snippet.status === 'active' ? 'This snippet is enabled.' : 'This snippet is disabled.', checked:snippet.status === 'active', onChange:function ( checked ) { patch( { status:checked ? 'active' : 'inactive' } ); } } ),
							h( TextControl, { label:'Priority', type:'number', min:1, max:999, help:'Lower numbers run first when snippets share a hook.', value:String( snippet.priority || 10 ), onChange:function ( value ) { patch( { priority:parseInt( value || '10', 10 ) || 10 } ); } } ),
							h( 'div', { className:'wcs-publish-actions' },
								h( Button, { variant:'tertiary', onClick:function () { props.navigate( 'list', {} ); } }, 'Cancel' ),
								h( Button, { variant:'primary', isBusy:saving, disabled:saving, onClick:save }, snippet.id ? 'Update Snippet' : 'Create Snippet' )
							)
						)
					)
				)
			)
		);
	}

	function ListScreen( props ) {
		var data = props.data;
		var params = props.params || {};
		var selectedPair = useState( [] );
		var selected = selectedPair[ 0 ], setSelected = selectedPair[ 1 ];
		var bulkPair = useState( '' );
		var bulk = bulkPair[ 0 ], setBulk = bulkPair[ 1 ];
		var confirmPair = useState( null );
		var confirm = confirmPair[ 0 ], setConfirm = confirmPair[ 1 ];
		var searchPair = useState( params.search || '' );
		var search = searchPair[ 0 ], setSearch = searchPair[ 1 ];

		useEffect( function () {
			var timer = setTimeout( function () {
				if ( search !== ( params.search || '' ) ) { props.navigate( 'list', { status:params.status || '', search:search, paged:1 }, true ); }
			}, 450 );
			return function () { clearTimeout( timer ); };
		}, [ search ] );

		function refresh() { props.navigate( 'list', params, false ); }
		function toggleStatus( item ) {
			post( { action:'wcs_toggle_status', nonce:wcsAdmin.nonce, id:item.id } ).then( function ( result ) {
				props.toast( result.status === 'active' ? 'Snippet activated.' : 'Snippet deactivated.' ); refresh();
			} ).catch( function ( err ) { props.toast( err.message ); } );
		}
		function runDelete( ids ) {
			if ( ids.length === 1 ) {
				post( { action:'wcs_delete_snippet', nonce:wcsAdmin.nonce, id:ids[ 0 ] } ).then( function () { props.toast( 'Snippet deleted.' ); refresh(); } ).catch( function ( err ) { props.toast( err.message ); } );
			} else {
				post( { action:'wcs_bulk_action', nonce:wcsAdmin.nonce, bulk:'delete', ids:ids } ).then( function () { props.toast( 'Snippets deleted.' ); refresh(); } ).catch( function ( err ) { props.toast( err.message ); } );
			}
		}
		function applyBulk() {
			if ( ! bulk || ! selected.length ) { return; }
			if ( bulk === 'delete' ) { setConfirm( { ids:selected } ); return; }
			post( { action:'wcs_bulk_action', nonce:wcsAdmin.nonce, bulk:bulk, ids:selected } ).then( function ( result ) {
				props.toast( result.skipped && result.skipped.length ? 'Some snippets were skipped because of syntax errors.' : 'Bulk action applied.' ); refresh();
			} ).catch( function ( err ) { props.toast( err.message ); } );
		}

		var filters = [ [ '', 'All', data.total ], [ 'active', 'Active', data.active ], [ 'inactive', 'Inactive', data.inactive ], [ 'auto-deactivated', 'Errors', data.errors ] ];
		return h( Fragment, null,
			h( Header, {
				title:'WP Code Snippet', subtitle:'Manage PHP, HTML, CSS and JavaScript snippets without editing theme files.',
				actions:[
					h( Button, { key:'settings', variant:'secondary', onClick:function () { props.navigate( 'settings', {} ); } }, 'Settings' ),
					h( Button, { key:'add', variant:'primary', onClick:function () { props.navigate( 'add', {} ); } }, 'Add New Snippet' )
				]
			} ),
			h( Panel, null,
				h( PanelBody, { title:'Snippets', initialOpen:true },
					h( 'div', { className:'wcs-list-toolbar' },
						h( 'div', { className:'wcs-filter-buttons' }, filters.map( function ( f ) {
							return h( Button, { key:f[ 0 ] || 'all', variant:( params.status || '' ) === f[ 0 ] ? 'primary' : 'secondary', onClick:function () { props.navigate( 'list', { status:f[ 0 ], search:params.search || '', paged:1 } ); } }, f[ 1 ] + ' (' + f[ 2 ] + ')' );
						} ) ),
						h( SearchControl, { value:search, onChange:setSearch, placeholder:'Search snippets…' } )
					),
					h( 'div', { className:'wcs-bulk-toolbar' },
						h( SelectControl, { label:'Bulk action', hideLabelFromVision:true, value:bulk, options:[ { label:'Bulk actions', value:'' }, { label:'Activate', value:'activate' }, { label:'Deactivate', value:'deactivate' }, { label:'Delete', value:'delete' } ], onChange:setBulk } ),
						h( Button, { variant:'secondary', disabled:! bulk || ! selected.length, onClick:applyBulk }, 'Apply' ),
						selected.length ? h( 'span', { className:'description' }, selected.length + ' selected' ) : null
					),
					! data.items.length ? h( EmptyState, { onAdd:function () { props.navigate( 'add', {} ); } } ) : h( 'div', { className:'wcs-snippet-list' },
						data.items.map( function ( item ) {
							var checked = selected.indexOf( parseInt( item.id, 10 ) ) !== -1;
							return h( 'div', { key:item.id, className:'wcs-snippet-row' },
								h( CheckboxControl, { checked:checked, onChange:function ( value ) {
									var id = parseInt( item.id, 10 ); setSelected( value ? selected.concat( [ id ] ) : selected.filter( function ( x ) { return x !== id; } ) );
								} } ),
								h( 'div', { className:'wcs-snippet-main' },
									h( Button, { variant:'link', className:'wcs-snippet-title', onClick:function () { props.navigate( 'edit', { id:item.id } ); } }, item.title || 'Untitled snippet' ),
									item.description ? h( 'div', { className:'description' }, item.description ) : null,
									item.error_message && item.status === 'auto-deactivated' ? h( Notice, { status:'error', isDismissible:false }, item.error_message ) : null
								),
								h( 'code', { className:'wcs-type-code' }, String( item.type || '' ).toUpperCase() ),
								h( 'span', { className:'wcs-location-text' }, String( item.location || 'run_everywhere' ).replace( /_/g, ' ' ) ),
								h( ToggleControl, { label:item.status === 'active' ? 'Active' : 'Inactive', checked:item.status === 'active', disabled:item.status === 'auto-deactivated', onChange:function () { toggleStatus( item ); } } ),
								h( 'div', { className:'wcs-row-actions' },
									h( Button, { variant:'secondary', onClick:function () { props.navigate( 'edit', { id:item.id } ); } }, 'Edit' ),
									h( Button, { variant:'tertiary', isDestructive:true, onClick:function () { setConfirm( { ids:[ parseInt( item.id, 10 ) ], title:String( item.title || 'Untitled snippet' ) } ); } }, 'Delete' )
								)
							);
						} )
					),
					data.totalPages > 1 ? h( 'div', { className:'wcs-pagination' },
						h( Button, { variant:'secondary', disabled:data.paged <= 1, onClick:function () { props.navigate( 'list', Object.assign( {}, params, { paged:data.paged - 1 } ) ); } }, 'Previous' ),
						h( 'span', null, 'Page ' + data.paged + ' of ' + data.totalPages ),
						h( Button, { variant:'secondary', disabled:data.paged >= data.totalPages, onClick:function () { props.navigate( 'list', Object.assign( {}, params, { paged:data.paged + 1 } ) ); } }, 'Next' )
					) : null
				)
			),
			confirm ? h( Modal, {
				title:confirm.ids.length > 1 ? 'Delete snippets' : 'Delete snippet',
				onRequestClose:function () { setConfirm( null ); },
				className:'wcs-confirm-modal',
				shouldCloseOnClickOutside:true
			},
				h( 'div', { className:'wcs-confirm-body' },
					h( 'p', { className:'wcs-confirm-question' },
						confirm.ids.length > 1
							? 'Delete ' + confirm.ids.length + ' selected snippets?'
							: 'Delete “' + String( confirm.title || 'this snippet' ) + '”?'
					),
					h( 'p', { className:'wcs-confirm-help' }, 'This action cannot be undone.' )
				),
				h( 'div', { className:'wcs-confirm-footer' },
					h( Button, { variant:'secondary', onClick:function () { setConfirm( null ); } }, 'Cancel' ),
					h( Button, { variant:'primary', isDestructive:true, onClick:function () { var ids = confirm.ids; setConfirm( null ); runDelete( ids ); } }, confirm.ids.length > 1 ? 'Delete snippets' : 'Delete snippet' )
				)
			) : null
		);
	}

	function SettingsScreen( props ) {
		var pair = useState( props.data.settings );
		var settings = pair[ 0 ], setSettings = pair[ 1 ];
		var savingPair = useState( false );
		var saving = savingPair[ 0 ], setSaving = savingPair[ 1 ];
		function patch( key, value ) { setSettings( Object.assign( {}, settings, ( function () { var x = {}; x[ key ] = value ? 1 : 0; return x; } )() ) ); }
		function save() {
			setSaving( true );
			var payload = { action:'wcs_save_settings', nonce:wcsAdmin.nonce };
			if ( settings.safe_mode ) { payload.safe_mode = 1; }
			if ( settings.disable_editor_php ) { payload.disable_editor_php = 1; }
			if ( settings.load_frontend_css ) { payload.load_frontend_css = 1; }
			post( payload )
				.then( function ( result ) { props.toast( result.noticeText || 'Settings saved.' ); } )
				.catch( function ( err ) { props.toast( err.message ); } )
				.finally( function () { setSaving( false ); } );
		}
		return h( Fragment, null,
			h( Header, { back:true, onBack:function () { props.navigate( 'list', {} ); }, title:'Settings', subtitle:'Global behavior for WP Code Snippet.' } ),
			h( Panel, null,
				h( PanelBody, { title:'Safety', initialOpen:true }, h( ToggleControl, { label:'Safe Mode', help:'Syntax-check PHP before activation and automatically disable snippets that fail.', checked:!! settings.safe_mode, onChange:function ( value ) { patch( 'safe_mode', value ); } } ) ),
				h( PanelBody, { title:'Frontend Output', initialOpen:true }, h( ToggleControl, { label:'Load CSS/JS snippets on the frontend', help:'Turn this off temporarily while debugging frontend output.', checked:!! settings.load_frontend_css, onChange:function ( value ) { patch( 'load_frontend_css', value ); } } ) ),
				h( PanelBody, { title:'Access', initialOpen:true }, h( ToggleControl, { label:'Disable the PHP editor', help:'Restrict this installation to HTML, CSS and JavaScript snippets.', checked:!! settings.disable_editor_php, onChange:function ( value ) { patch( 'disable_editor_php', value ); } } ) )
		),
			h( 'div', { className:'wcs-settings-actions' }, h( Button, { variant:'primary', isBusy:saving, disabled:saving, onClick:save }, 'Save Settings' ) )
		);
	}

	function App() {
		var initial = viewFromLocation();
		var viewPair = useState( initial.view );
		var view = viewPair[ 0 ], setView = viewPair[ 1 ];
		var paramsPair = useState( initial.params );
		var params = paramsPair[ 0 ], setParams = paramsPair[ 1 ];
		var dataPair = useState( null );
		var data = dataPair[ 0 ], setData = dataPair[ 1 ];
		var loadingPair = useState( true );
		var loading = loadingPair[ 0 ], setLoading = loadingPair[ 1 ];
		var toastPair = useState( '' );
		var toast = toastPair[ 0 ], setToast = toastPair[ 1 ];

		function load( nextView, nextParams, push ) {
			nextParams = nextParams || {};
			setView( nextView ); setParams( nextParams ); setLoading( true );
			post( Object.assign( { action:'wcs_get_data', nonce:wcsAdmin.nonce, view:nextView }, nextParams ) ).then( function ( result ) {
				setData( result ); setLoading( false );
				if ( push !== false ) { window.history.pushState( { view:nextView, params:nextParams }, '', buildUrl( nextView, nextParams ) ); }
			} ).catch( function ( err ) { setLoading( false ); setToast( err.message ); } );
		}

		useEffect( function () {
			load( initial.view, initial.params, false );
			function pop() { var p = viewFromLocation(); load( p.view, p.params, false ); }
			window.addEventListener( 'popstate', pop );
			return function () { window.removeEventListener( 'popstate', pop ); };
		}, [] );

		var body = loading || ! data ? h( Loading ) : ( view === 'list' ? h( ListScreen, { data:data, params:params, navigate:load, toast:setToast } ) : ( view === 'settings' ? h( SettingsScreen, { data:data, navigate:load, toast:setToast } ) : h( EditScreen, { key:( data.snippet && data.snippet.id ) || 'new', data:data, navigate:load, toast:setToast } ) ) );
		return h( Fragment, null,
			body,
			toast ? h( 'div', { className:'wcs-snackbar-region' }, h( Snackbar, { onRemove:function () { setToast( '' ); } }, toast ) ) : null
		);
	}

	function renderFatal( root, error ) {
		if ( ! root ) { return; }
		var message = error && error.message ? error.message : 'The admin interface could not be loaded.';
		root.innerHTML = '<div class="notice notice-error inline"><p><strong>WP Code Snippet UI could not load.</strong> ' + String( message ).replace( /[&<>"']/g, function ( ch ) { return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[ ch ]; } ) + '</p><p><button type="button" class="button button-primary" id="wcs-reload-ui">Reload interface</button></p></div>';
		var reload = document.getElementById( 'wcs-reload-ui' );
		if ( reload ) { reload.addEventListener( 'click', function () { window.location.reload(); } ); }
	}

	function mount() {
		var root = document.getElementById( 'wcs-block-app' );
		if ( ! root ) { return; }
		root.innerHTML = '<div class="wcs-loading-fallback"><span class="spinner is-active"></span><span>Loading WP Code Snippet…</span></div>';
		try {
			if ( ! window.wp || ! wp.element || ! wp.components || ! wp.element.render ) {
				throw new Error( 'Required WordPress Block Editor components are not available on this screen.' );
			}
			wp.element.render( h( App ), root );
		} catch ( error ) {
			renderFatal( root, error );
		}
	}

	window.addEventListener( 'error', function ( event ) {
		var root = document.getElementById( 'wcs-block-app' );
		if ( root && ! root.children.length ) { renderFatal( root, event.error || new Error( event.message || 'JavaScript error.' ) ); }
	} );

	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', mount ); }
	else { mount(); }
} )();
