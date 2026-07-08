/* global d3plus */
/**
 * Suite PAZ · Renderer d3plus v3.
 *
 * Exposes window.SPZ.renderer.render( el, { view, type, options } ).
 *
 * `el`   — DOM element (or element id string) for the chart container.
 * `view` — Either:
 *   (a) A raw PAZ seed JSON straight from data/views/<sec>/*.json:
 *       { vista, titulo, municipios|datos|data: [...] }
 *   (b) A pre-built REST payload (from suite-paz/v1/render):
 *       { chart: {key, class, label}, view: {id, name, dimensions, measures, ...},
 *         data: [...], mapping: {...} }
 * `type`    — Chart type key (e.g. 'geomap', 'bar'). Overrides seed hint when set.
 * `options` — { legend, legendStyle, xTitle, yTitle }
 *
 * Topojson URL config (set before render):
 *   window.SPZ.config.topojsonUrl  default: '../data/topo/narino_municipios.topojson'
 *
 * Adapted from tic-suite assets/js/renderer.js.
 * Namespace: TSG → SPZ. Interface: render(containerId, payload, opts) → render(el, {view,type,options}).
 *
 * @package SuitePaz
 */
( function () {
	'use strict';

	// ------------------------------------------------------------------
	// d3plus class map (v3.1.4 bundle exports — verified).
	// stacked_bar: BarChart + .stacked(true) (no StackedBarChart in v3).
	// ------------------------------------------------------------------
	const CLASS_MAP = {
		bar:          'BarChart',
		stacked_bar:  'BarChart',
		line:         'LinePlot',
		area:         'AreaPlot',
		stacked_area: 'StackedArea',
		pie:          'Pie',
		donut:        'Donut',
		treemap:      'Treemap',
		geomap:       'Geomap',
		network:      'Network',
		tree:         'Tree',
		sankey:       'Sankey',
		rings:        'Rings',
		box_whisker:  'BoxWhisker',
		priestley:    'Priestley',
	};

	// Suite PAZ chart color palette.
	// Prefers the value localized from PHP (SPZ_FRONTEND.palette) when available;
	// falls back to the 24-color default that matches SPZ_DEFAULT_PALETTE in PHP.
	const PALETTE = (
		typeof SPZ_FRONTEND !== 'undefined' &&
		Array.isArray( SPZ_FRONTEND.palette ) &&
		SPZ_FRONTEND.palette.length
	) ? SPZ_FRONTEND.palette : [
		'#844e80', '#ff7300', '#ffc53b', '#3eba6a', '#0080c3', '#e74c3c',
		'#9b59b6', '#1abc9c', '#348AFB', '#e84393', '#fdcb6e', '#2ecc71',
		'#00cec9', '#0984e3', '#6c5ce7', '#d63031', '#e17055', '#ff4757',
		'#2ed573', '#1e90ff', '#ffa502', '#ff6b81', '#70a1ff', '#78e08f',
	];

	// Data key candidates when extracting rows from a PAZ seed JSON.
	const ROW_KEYS = [ 'datos', 'data', 'municipios', 'items', 'rows' ];

	// es-CO number formatters.
	const NF_DECIMAL = new Intl.NumberFormat( 'es-CO', { maximumFractionDigits: 2 } );
	const NF_INT     = new Intl.NumberFormat( 'es-CO', { maximumFractionDigits: 0 } );
	const NF_PCT     = new Intl.NumberFormat( 'es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 } );

	// ------------------------------------------------------------------
	// HTML-escape helper (used by table builder and renderTable).
	// ------------------------------------------------------------------
	function escHtml( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' } )[ c ];
		} );
	}

	// ------------------------------------------------------------------
	// dataTable( rows, columns ) → HTML string for <table class="spz-tabla">
	// Exposed as SPZ.util.dataTable so Fix-Task 6 "Ver datos" can reuse it.
	// ------------------------------------------------------------------
	function dataTable( rows, columns ) {
		if ( ! Array.isArray( rows ) || ! rows.length ) {
			return '<p class="spz-empty">Sin datos.</p>';
		}
		// Compute columns from the union of keys if not provided.
		if ( ! Array.isArray( columns ) || ! columns.length ) {
			const keySet = Object.create( null );
			rows.forEach( function ( row ) {
				Object.keys( row || {} ).forEach( function ( k ) { keySet[ k ] = true; } );
			} );
			columns = Object.keys( keySet );
		}
		let html = '<div class="spz-tabla-wrap"><table class="spz-tabla"><thead><tr>';
		columns.forEach( function ( col ) {
			html += '<th>' + escHtml( col ) + '</th>';
		} );
		html += '</tr></thead><tbody>';
		rows.forEach( function ( row ) {
			html += '<tr>';
			columns.forEach( function ( col ) {
				const val   = row[ col ];
				const isNum = typeof val === 'number';
				let display;
				if ( val == null || val === '' ) {
					display = '&mdash;';
				} else if ( isNum ) {
					display = escHtml( val.toLocaleString( 'es-CO' ) );
				} else {
					display = escHtml( String( val ) );
				}
				html += '<td' + ( isNum ? ' class="spz-num"' : '' ) + '>' + display + '</td>';
			} );
			html += '</tr>';
		} );
		html += '</tbody></table></div>';
		return html;
	}

	// ------------------------------------------------------------------
	// attachVerDatos( el, dataForPanel, meta )
	// Appends a branded "Ver datos" button + collapsible data panel
	// as siblings of `el` after each chart/table/module render.
	// meta: { title, descripcion, fuente, columns }
	// Exposed as SPZ.util.attachVerDatos so modules.js can reuse it.
	// ------------------------------------------------------------------
	function attachVerDatos( el, dataForPanel, meta ) {
		if ( ! el || ! el.parentNode ) {
			return;
		}
		// Guard: only attach once per element.
		if ( el.dataset && el.dataset.spzVd ) {
			return;
		}
		if ( el.dataset ) {
			el.dataset.spzVd = '1';
		}

		meta = meta || {};
		var uid = 'spz-dp-' + Math.random().toString( 36 ).slice( 2 );

		// Build inner HTML for the panel body.
		var bodyHtml = '';
		var src = meta.descripcion || meta.fuente || '';
		if ( src ) {
			bodyHtml += '<p class="spz-datapanel__src">Fuente: ' + escHtml( src ) + '</p>';
		}
		if ( Array.isArray( dataForPanel ) && dataForPanel.length ) {
			bodyHtml += dataTable( dataForPanel, meta.columns || null );
		} else if ( dataForPanel && typeof dataForPanel === 'object' && ! Array.isArray( dataForPanel ) ) {
			bodyHtml += '<dl class="spz-datapanel__dl">';
			Object.keys( dataForPanel ).forEach( function ( k ) {
				var v = dataForPanel[ k ];
				if ( v == null || v === '' ) {
					return;
				}
				bodyHtml += '<dt>' + escHtml( String( k ) ) + '</dt><dd>' + escHtml( String( v ) ) + '</dd>';
			} );
			bodyHtml += '</dl>';
		} else {
			bodyHtml += '<p class="spz-empty">Sin datos.</p>';
		}

		var panelTitle = escHtml( meta.title || 'Datos de la vista' );

		// Create button.
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'spz-verdatos';
		btn.setAttribute( 'aria-expanded', 'false' );
		btn.setAttribute( 'aria-controls', uid );
		btn.innerHTML = '<span class="spz-verdatos__icon" aria-hidden="true">&#9638;</span><span>Ver datos</span>';

		// Create panel.
		var panel = document.createElement( 'div' );
		panel.className = 'spz-datapanel';
		panel.id = uid;
		panel.setAttribute( 'hidden', '' );
		panel.setAttribute( 'role', 'region' );
		panel.setAttribute( 'aria-label', 'Datos: ' + ( meta.title || '' ) );
		panel.innerHTML =
			'<div class="spz-datapanel__header">' +
				'<span class="spz-datapanel__title">' + panelTitle + '</span>' +
				'<button type="button" class="spz-datapanel__close" aria-label="Cerrar panel">&#x2715;</button>' +
			'</div>' +
			'<div class="spz-datapanel__body">' + bodyHtml + '</div>';

		// Insert button then panel immediately after el.
		var ref = el.nextSibling;
		el.parentNode.insertBefore( btn, ref );
		el.parentNode.insertBefore( panel, btn.nextSibling );

		// Toggle helpers.
		function onKey( e ) {
			if ( e.key === 'Escape' || e.key === 'Esc' ) {
				closePanel();
			}
		}
		function openPanel() {
			panel.removeAttribute( 'hidden' );
			btn.setAttribute( 'aria-expanded', 'true' );
			document.addEventListener( 'keydown', onKey );
			var closeBtn = panel.querySelector( '.spz-datapanel__close' );
			if ( closeBtn ) {
				closeBtn.focus();
			}
		}
		function closePanel() {
			panel.setAttribute( 'hidden', '' );
			btn.setAttribute( 'aria-expanded', 'false' );
			document.removeEventListener( 'keydown', onKey );
			btn.focus();
		}

		btn.addEventListener( 'click', function () {
			if ( panel.hasAttribute( 'hidden' ) ) {
				openPanel();
			} else {
				closePanel();
			}
		} );

		var closeEl = panel.querySelector( '.spz-datapanel__close' );
		if ( closeEl ) {
			closeEl.addEventListener( 'click', closePanel );
		}
	}

	// ------------------------------------------------------------------
	// Renderer object
	// ------------------------------------------------------------------
	const Renderer = {

		// ==============================================================
		// Public entry point
		// ==============================================================

		/**
		 * Render a d3plus chart or map.
		 *
		 * @param {Element|string} el      Container DOM element or its id.
		 * @param {object}         params  { view, type, options }
		 */
		async render( el, params ) {
			// Normalise element reference.
			if ( typeof el === 'string' ) {
				el = document.getElementById( el );
			}
			if ( ! el ) {
				return;
			}

			const { view: rawView, type: typeHint, options } = params || {};
			if ( ! rawView ) {
				el.innerHTML = '<p class="spz-empty">Sin datos para renderizar.</p>';
				return;
			}

			el.innerHTML = '';

			const opts = Object.assign(
				{ legend: true, legendStyle: 'text', xTitle: '', yTitle: '' },
				options || {}
			);

			// Build the normalised payload.
			const payload = this.buildPayload( rawView, typeHint );

			// Native table renderer — intercept before d3plus instantiation.
			// Triggered when: type==='tabla', chart.class==='' (REST native), or chart.class==='native'.
			if ( payload && payload.chart &&
				( payload.chart.key === 'tabla' ||
				  payload.chart.class === '' ||
				  payload.chart.class === 'native' ) ) {
				this.renderTable( el, payload );
				attachVerDatos( el, payload.data || [], {
					title:       ( payload.view && payload.view.name )        || 'Datos',
					descripcion: ( payload.view && payload.view.description ) || '',
					columns:     ( payload.mapping && payload.mapping.columns ) || null,
					fuente:      payload.fuente || '',
				} );
				return;
			}

			if ( ! payload || ! payload.chart || ! payload.chart.class ) {
				el.innerHTML = `<p class="spz-empty">Tipo de gráfico no soportado: ${ escHtml( String( typeHint || '(desconocido)' ) ) }</p>`;
				return;
			}

			// Ensure an id so d3plus can find the container.
			if ( ! el.id ) {
				el.id = 'spz-chart-' + Math.random().toString( 36 ).slice( 2 );
			}

			// Attach "Ver datos" button/panel as a sibling of el (independent of d3plus loading).
			attachVerDatos( el, payload.data || [], {
				title:       ( payload.view && payload.view.name )        || 'Datos',
				descripcion: ( payload.view && payload.view.description ) || '',
				columns:     ( payload.mapping && payload.mapping.columns ) || null,
				fuente:      payload.fuente || '',
			} );

			// Wait for the d3plus bundle.
			try {
				await this.waitForD3plus();
			} catch ( e ) {
				el.innerHTML = '<p class="spz-empty">d3plus no disponible.</p>';
				return;
			}

			// Resolve constructor.
			const Ctor = window.d3plus[ payload.chart.class ];
			if ( typeof Ctor !== 'function' ) {
				el.innerHTML = `<p class="spz-empty">Tipo de gráfico no soportado: ${ payload.chart.class }</p>`;
				return;
			}

			try {
				const viz = new Ctor();

				viz
					.select( '#' + el.id )
					.detectResize( true )
					.legend( Boolean( opts.legend ) );

				if ( typeof viz.legendPosition === 'function' ) {
					viz.legendPosition( 'bottom' );
				}

				if ( opts.legend && opts.legendStyle === 'icons' && typeof viz.legendConfig === 'function' ) {
					viz.legendConfig( {
						label: () => '',
						shapeConfig: {
							labelConfig: { fontSize: () => 0, padding: 0 },
							width: 24,
							height: 24,
						},
						padding: 4,
					} );
				}

				this.configure( viz, payload, opts );
				this.applyTimeline( viz, payload );
				this.applyAxes( viz, payload, opts );
				this.applyTooltip( viz, payload );
				this.applyShape( viz );

				// d3plus v3 defers rendering for off-screen elements via its
				// own IntersectionObserver.  We add an explicit observer so the
				// render() call happens precisely when the container enters the
				// viewport — guaranteeing bars/paths actually appear.
				const rect    = el.getBoundingClientRect();
				const visible = rect.width > 0 && rect.height > 0
					&& rect.bottom > 0 && rect.top < window.innerHeight;

				const doRender = () => {
					viz.render();
					window.requestAnimationFrame( () => {
						try {
							if ( typeof viz.resize === 'function' ) {
								viz.resize();
							}
						} catch ( _e ) { /* noop */ }
					} );
				};

				if ( visible || ! ( 'IntersectionObserver' in window ) ) {
					doRender();
				} else {
					// Defer until the container scrolls into the viewport.
					const obs = new IntersectionObserver( ( entries ) => {
						if ( entries[ 0 ].isIntersecting ) {
							obs.disconnect();
							doRender();
						}
					}, { threshold: 0.01 } );
					obs.observe( el );
				}
			} catch ( err ) {
				console.error( '[SPZ] render error', err );
				el.innerHTML = '<p class="spz-empty">No fue posible renderizar el gráfico.</p>';
			}
		},

		// ==============================================================
		// Lifecycle helpers
		// ==============================================================

		waitForD3plus( timeoutMs ) {
			if ( timeoutMs === undefined ) {
				timeoutMs = 8000;
			}
			return new Promise( function ( resolve, reject ) {
				if ( window.d3plus ) {
					return resolve();
				}
				const start = Date.now();
				const tick  = function () {
					if ( window.d3plus ) {
						return resolve();
					}
					if ( Date.now() - start > timeoutMs ) {
						return reject( new Error( 'd3plus timeout' ) );
					}
					setTimeout( tick, 80 );
				};
				tick();
			} );
		},

		// ==============================================================
		// Payload builder
		// ==============================================================

		/**
		 * Build a normalised renderer payload from either a raw seed JSON
		 * or an already-built REST payload.
		 *
		 * @param {object} raw       Input view data.
		 * @param {string} typeHint  Chart type key requested by caller.
		 * @returns {object}         Normalised payload.
		 */
		buildPayload( raw, typeHint ) {
			// (b) Already a pre-built REST payload — detect by presence of raw.chart + raw.data array.
			// Native types (tabla) have chart.class==='' so we cannot require it to be truthy.
			if ( raw && raw.chart && raw.chart.key && Array.isArray( raw.data ) ) {
				// Allow typeHint to override the chart type (d3plus types only).
				if ( typeHint && typeHint !== raw.chart.key ) {
					const cls = CLASS_MAP[ typeHint ];
					if ( cls ) {
						return Object.assign( {}, raw, {
							chart: Object.assign( {}, raw.chart, { key: typeHint, class: cls } ),
						} );
					}
				}
				return raw;
			}

			// (a) Raw seed JSON — normalise.
			const data    = this.extractRows( raw );
			const fields  = this.inferFields( data[ 0 ] || {} );
			const dims    = fields.dimensions;
			const measures = fields.measures;

			const chartType  = typeHint || ( raw && raw.tipo_grafico_sugerido ) || 'bar';
			const chartClass = CLASS_MAP[ chartType ] || '';

			// Resolve topojson URL from config, then relative fallback.
			const topojsonUrl = ( window.SPZ && window.SPZ.config && window.SPZ.config.topojsonUrl )
				|| '../data/topo/narino_municipios.topojson';

			const mapping = {
				groupBy: dims,
				x:       dims[ 0 ] || '',
				y:       measures[ 0 ] || '',
				value:   measures[ 0 ] || '',
				size:    measures[ 0 ] || '',
			};

			if ( chartType === 'geomap' ) {
				mapping.topojson    = topojsonUrl;
				mapping.topojsonId  = 'id';
				mapping.topojsonKey = 'objects.municipios';
				mapping.join        = 'municipio';
			}

			return {
				chart: {
					key:   chartType,
					class: chartClass,
					label: chartType,
				},
				view: {
					id:          raw.vista || 'view',
					name:        raw.titulo || '',
					description: raw.descripcion || '',
					category:    raw.categoria || this.inferCategory( dims ),
					dimensions:  dims,
					measures:    measures,
					edges:       [],
				},
				data:    data,
				mapping: mapping,
			};
		},

		/**
		 * Pull the row array from a PAZ seed JSON.
		 *
		 * @param {object} raw Decoded seed JSON.
		 * @returns {Array}
		 */
		extractRows( raw ) {
			if ( ! raw || typeof raw !== 'object' ) {
				return [];
			}
			for ( let i = 0; i < ROW_KEYS.length; i++ ) {
				const key = ROW_KEYS[ i ];
				const arr = raw[ key ];
				if ( Array.isArray( arr ) && arr.length && typeof arr[ 0 ] === 'object' && arr[ 0 ] !== null ) {
					return arr;
				}
			}
			return [];
		},

		/**
		 * Infer dimensions and measures from the first data row.
		 * Uses typeof (not isNaN) so DIVIPOLA strings like "52001" stay as dimensions.
		 *
		 * @param {object} row First data row.
		 * @returns {{ dimensions: string[], measures: string[] }}
		 */
		inferFields( row ) {
			const dimensions = [];
			const measures   = [];
			const keys = Object.keys( row || {} );
			for ( let i = 0; i < keys.length; i++ ) {
				const k = keys[ i ];
				const v = row[ k ];
				if ( typeof v === 'number' ) {
					measures.push( k );
				} else if ( typeof v === 'string' || typeof v === 'boolean' ) {
					dimensions.push( k );
				}
			}
			return { dimensions, measures };
		},

		/**
		 * Infer category from dimension names (mirrors SPZ_Data_Provider logic).
		 *
		 * @param {string[]} dims Dimension field names.
		 * @returns {string}
		 */
		inferCategory( dims ) {
			const lower = dims.map( ( d ) => String( d ).toLowerCase() );
			const geoWords  = [ 'municipio', 'municipios', 'departamento', 'departamentos', 'municipio_id' ];
			const dateHints = [ 'fecha', 'mes', 'anio', 'año', 'year', 'date', 'periodo', 'vigencia' ];

			for ( let i = 0; i < lower.length; i++ ) {
				if ( geoWords.indexOf( lower[ i ] ) !== -1 ) {
					return 'geographic';
				}
			}
			for ( let i = 0; i < lower.length; i++ ) {
				for ( let j = 0; j < dateHints.length; j++ ) {
					if ( lower[ i ].indexOf( dateHints[ j ] ) !== -1 ) {
						return 'temporal';
					}
				}
			}
			return 'categorical';
		},

		// ==============================================================
		// Per-chart configuration
		// ==============================================================

		configure( viz, payload ) {
			const { mapping, view, chart } = payload;
			const dims     = view.dimensions || [];
			const measures = view.measures   || [];

			const data = this.filterMeaningful( payload.data, chart.key, measures );
			payload._filteredData = data;

			switch ( chart.key ) {
				case 'bar':
					viz
						.data( data )
						.groupBy( dims[ 0 ] )
						.x( dims[ 0 ] )
						.y( measures[ 0 ] );
					break;

				case 'stacked_bar': {
					const long = this.reshapeWideToLong( data, dims, measures );
					viz
						.data( long )
						.groupBy( [ '_metric', dims[ 0 ] ] )
						.x( dims[ 0 ] )
						.y( '_value' )
						.stacked( true );
					break;
				}

				case 'line':
				case 'area': {
					const yearCols = this.detectYears( measures );
					if ( yearCols.length >= 2 ) {
						const yearMeasures = measures.filter( ( m ) => /_(20\d{2})$/.test( m ) );
						const long = [];
						( data || [] ).forEach( ( row ) => {
							yearCols.forEach( ( y ) => {
								const col = yearMeasures.find( ( m ) => m.endsWith( '_' + y ) );
								if ( ! col ) {
									return;
								}
								long.push( Object.assign( {}, row, {
									_year:  String( y ),
									_value: Number( row[ col ] ) || 0,
								} ) );
							} );
						} );
						viz
							.data( long )
							.groupBy( dims[ 0 ] )
							.x( '_year' )
							.y( '_value' );
					} else {
						viz
							.data( data )
							.groupBy( dims[ 1 ] || dims[ 0 ] )
							.x( dims[ 0 ] )
							.y( measures[ 0 ] );
					}
					break;
				}

				case 'stacked_area': {
					const long = this.reshapeWideToLong( data, dims, measures );
					viz
						.data( long )
						.groupBy( '_metric' )
						.x( dims[ 0 ] )
						.y( '_value' );
					break;
				}

				case 'pie':
				case 'donut':
					viz
						.data( data )
						.groupBy( dims[ 0 ] )
						.value( measures[ 0 ] );
					break;

				case 'treemap':
					viz
						.data( data )
						.groupBy( [ dims[ 0 ] ] )
						.sum( measures[ 0 ] );
					break;

				case 'priestley': {
					const years = this.detectYears( measures );
					if ( years.length >= 2 ) {
						const firstY = String( Math.min.apply( null, years ) );
						const lastY  = String( Math.max.apply( null, years ) );
						const totalM = measures.find( ( m ) => /^total$/i.test( m ) ) || measures[ 0 ];
						const pData  = ( data || [] ).map( ( row ) => Object.assign( {}, row, {
							_start: firstY,
							_end:   lastY,
						} ) );
						viz
							.data( pData )
							.groupBy( dims[ 0 ] );
						this.safeCall( viz, 'start', '_start' );
						this.safeCall( viz, 'end', '_end' );
						if ( typeof viz.value === 'function' ) {
							viz.value( totalM );
						}
					} else {
						viz.data( data ).groupBy( dims[ 0 ] );
						if ( typeof viz.start === 'function' && measures[ 0 ] ) {
							viz.start( measures[ 0 ] );
						}
						if ( typeof viz.end === 'function' && measures[ 1 ] ) {
							viz.end( measures[ 1 ] );
						}
					}
					break;
				}

				case 'network': {
					const nodes = this.buildNodes( data, dims[ 0 ] );
					this.safeCall( viz, 'nodes', nodes );
					this.safeCall( viz, 'links', mapping.links || view.edges || [] );
					viz.groupBy( dims[ 0 ] );
					if ( measures[ 0 ] ) {
						viz.size( measures[ 0 ] );
					}
					break;
				}

				case 'rings': {
					const nodes = this.buildNodes( data, dims[ 0 ] );
					this.safeCall( viz, 'nodes', nodes );
					this.safeCall( viz, 'links', mapping.links || view.edges || [] );
					if ( nodes.length && typeof viz.center === 'function' ) {
						viz.center( nodes[ 0 ].id );
					}
					break;
				}

				case 'sankey': {
					const hasEdges = ( view.edges && view.edges.length > 0 )
						|| ( mapping.links && mapping.links.length > 0 );
					if ( hasEdges ) {
						const nodes = this.buildNodes( data, dims[ 0 ] );
						this.safeCall( viz, 'nodes', nodes );
						this.safeCall( viz, 'links', mapping.links || view.edges || [] );
					} else {
						const dim     = dims[ 0 ];
						const measure = measures[ 0 ];
						const nodes   = [ { id: 'Total' } ];
						const links   = [];
						( data || [] ).forEach( ( row ) => {
							const label = String( row[ dim ] || '' );
							const val   = Number( row[ measure ] ) || 0;
							if ( ! label || val <= 0 ) {
								return;
							}
							nodes.push( { id: label } );
							links.push( { source: 'Total', target: label, value: val } );
						} );
						this.safeCall( viz, 'nodes', nodes );
						this.safeCall( viz, 'links', links );
					}
					break;
				}

				case 'geomap': {
					const joinField = mapping.join || dims[ 0 ] || 'municipio';
					const normData  = ( data || [] ).map( ( row ) => Object.assign(
						{},
						row,
						{ _municipio_id: Renderer.normalizeMuni( row[ joinField ] ) }
					) );
					viz
						.data( normData )
						.groupBy( '_municipio_id' )
						.colorScale( measures[ 0 ] );

					// No CARTO / OSM basemap — only Nariño polygons.
					if ( typeof viz.tiles === 'function' ) {
						viz.tiles( false );
					}
					// Transparent ocean so the container background shows through.
					if ( typeof viz.ocean === 'function' ) {
						viz.ocean( 'transparent' );
					}
					// Polygons with no matching data get a soft neutral fill.
					if ( typeof viz.topojsonFill === 'function' ) {
						viz.topojsonFill( '#fffcf3' );
					}
					if ( typeof viz.topojson === 'function' && mapping.topojson ) {
						viz.topojson( mapping.topojson );
					}
					if ( typeof viz.topojsonId === 'function' && mapping.topojsonId ) {
						viz.topojsonId( mapping.topojsonId );
					}
					if ( typeof viz.topojsonKey === 'function' && mapping.topojsonKey ) {
						viz.topojsonKey( mapping.topojsonKey );
					}
					if ( typeof viz.topojsonFilter === 'function' ) {
						viz.topojsonFilter( () => true );
					}
					if ( typeof viz.label === 'function' ) {
						viz.label( ( d ) => d[ joinField ] || d._municipio_id || '' );
					}
					break;
				}

				default:
					if ( dims[ 0 ] ) {
						viz.groupBy( dims[ 0 ] );
					}
					if ( measures[ 0 ] ) {
						viz.y( measures[ 0 ] );
					}
			}
		},

		// ==============================================================
		// Timeline
		// ==============================================================

		applyTimeline( viz, payload ) {
			if ( typeof viz.time !== 'function' ) {
				return;
			}
			const { chart, view } = payload;
			const SKIP = [ 'stacked_bar', 'stacked_area', 'line', 'area', 'priestley', 'sankey', 'network', 'rings', 'geomap' ];
			if ( SKIP.indexOf( chart.key ) !== -1 ) {
				return;
			}
			const measures = view.measures || [];
			const years    = this.detectYears( measures );
			if ( years.length < 2 ) {
				return;
			}

			const dims    = view.dimensions || [];
			const rawData = payload._filteredData || payload.data || [];
			const long    = [];
			const yearMeasures = measures.filter( ( m ) => /_(20\d{2})$/.test( m ) );

			( rawData.length ? rawData : ( payload.data || [] ) ).forEach( ( row ) => {
				years.forEach( ( y ) => {
					const col = yearMeasures.find( ( m ) => m.endsWith( '_' + y ) );
					if ( ! col ) {
						return;
					}
					long.push( Object.assign( {}, row, {
						_year:  String( y ),
						_value: Number( row[ col ] ) || 0,
					} ) );
				} );
			} );

			if ( long.length ) {
				viz.data( long );
				viz.time( '_year' );
				viz.timeline( true );

				if ( typeof viz.y === 'function' && typeof viz.x === 'function' ) {
					viz.x( dims[ 0 ] || '_year' );
					viz.y( '_value' );
					viz.groupBy( dims[ 0 ] || '_year' );
				}
				if ( typeof viz.value === 'function' && ( chart.key === 'pie' || chart.key === 'donut' ) ) {
					viz.value( '_value' );
				}
				if ( typeof viz.sum === 'function' && chart.key === 'treemap' ) {
					viz.sum( '_value' );
				}
			}
		},

		detectYears( measures ) {
			const years = new Set();
			( measures || [] ).forEach( ( m ) => {
				const match = String( m ).match( /_(20\d{2})$/ );
				if ( match ) {
					years.add( Number( match[ 1 ] ) );
				}
			} );
			return Array.from( years ).sort();
		},

		// ==============================================================
		// Axes
		// ==============================================================

		applyAxes( viz, payload, opts ) {
			if ( typeof viz.xConfig !== 'function' || typeof viz.yConfig !== 'function' ) {
				return;
			}

			const { chart, view } = payload;
			const dims     = view.dimensions || [];
			const measures = view.measures   || [];

			const NO_AXES = [ 'pie', 'donut', 'treemap', 'geomap', 'network', 'rings', 'sankey', 'tree' ];
			if ( NO_AXES.indexOf( chart.key ) !== -1 ) {
				return;
			}

			const years   = this.detectYears( measures );
			const hasTime = years.length >= 2 && [ 'stacked_bar', 'stacked_area', 'priestley' ].indexOf( chart.key ) === -1;

			let xField, yField;
			switch ( chart.key ) {
				case 'stacked_bar':
				case 'stacked_area':
					xField = dims[ 0 ];
					yField = '_value';
					break;
				case 'priestley':
					xField = dims[ 0 ];
					yField = '';
					break;
				case 'line':
				case 'area':
					xField = hasTime ? '_year' : dims[ 0 ];
					yField = hasTime ? '_value' : measures[ 0 ];
					break;
				default:
					xField = dims[ 0 ];
					yField = hasTime ? '_value' : measures[ 0 ];
			}

			let xTitle, yTitle;
			if ( hasTime ) {
				const yearMeasures = measures.filter( ( m ) => /_(20\d{2})$/.test( m ) );
				const baseName     = yearMeasures.length
					? yearMeasures[ 0 ].replace( /_(20\d{2})$/, '' )
					: measures[ 0 ];
				xTitle = ( opts && opts.xTitle ) || (
					chart.key === 'line' || chart.key === 'area'
						? 'Vigencia'
						: this.autoAxisTitle( dims[ 0 ] )
				);
				yTitle = ( opts && opts.yTitle ) || this.autoAxisTitle( baseName );
			} else {
				xTitle = ( opts && opts.xTitle ) || this.autoAxisTitle( xField );
				yTitle = ( opts && opts.yTitle ) || (
					( chart.key === 'stacked_bar' || chart.key === 'stacked_area' )
						? this.measureGroupTitle( measures )
						: this.autoAxisTitle( yField )
				);
			}

			const titleConfig = {
				fontFamily: () => 'inherit',
				fontSize:   () => 13,
				fontWeight: () => 600,
				fontColor:  () => '#1E2233',
			};

			viz.xConfig( { title: xTitle, titleConfig: titleConfig } );
			viz.yConfig( { title: yTitle, titleConfig: titleConfig } );
		},

		autoAxisTitle( field ) {
			if ( ! field || field === '_value' ) {
				return 'Valor';
			}
			const lower = String( field ).toLowerCase();
			let unit  = '';
			let clean = String( field );

			if ( /millones[_ ]?cop$/.test( lower ) ) {
				unit  = '(Millones COP)';
				clean = clean.replace( /[_ ]?millones[_ ]?cop$/i, '' );
			} else if ( /_cop$/.test( lower ) || /\bcop\b/.test( lower ) ) {
				unit  = '(COP)';
				clean = clean.replace( /[_ ]?cop$/i, '' );
			} else if ( /(_pct|pct_|porcentaje|cobertura_pct|participacion_pct)/.test( lower ) ) {
				unit  = '(%)';
				clean = clean.replace( /[_ ]?(pct|porcentaje)/gi, '' );
			}

			const label = this.humanizeKey( clean ) || this.humanizeKey( field );
			return unit ? label + ' ' + unit : label;
		},

		measureGroupTitle( measures ) {
			const filtered = ( measures || [] ).filter( ( m ) => ! /^(total|pct_|participacion|cobertura)|(_pct|_total)$/i.test( m ) );
			const sample   = filtered.length ? filtered[ 0 ] : ( ( measures || [] )[ 0 ] || '' );
			if ( /cop|inversion/i.test( sample ) ) {
				return 'Valor (Millones COP)';
			}
			return 'Cantidad';
		},

		// ==============================================================
		// Tooltip
		// ==============================================================

		applyTooltip( viz, payload ) {
			if ( typeof viz.tooltipConfig !== 'function' ) {
				return;
			}

			const { chart, view } = payload;
			const dims     = view.dimensions || [];
			const measures = view.measures   || [];

			const titleAccessor = this.tooltipTitleAccessor( chart.key, dims, view );
			const tbody         = this.tooltipTbody( chart.key, dims, measures );

			viz.tooltipConfig( {
				title: titleAccessor,
				tbody: tbody,
				titleStyle: {
					'max-width':      '260px',
					'font-size':      '14px',
					'font-weight':    '600',
					'color':          '#1E2233',
					'border-bottom':  '1px solid #e2e8f0',
					'padding-bottom': '6px',
					'margin-bottom':  '4px',
				},
				tbodyStyle: {
					'font-size': '12px',
					'color':     '#334155',
				},
				tdStyle: {
					'padding':        '2px 4px',
					'vertical-align': 'top',
				},
				background:   '#ffffff',
				border:       '1px solid #cbd5e1',
				borderRadius: '8px',
				padding:      '12px 14px',
			} );
		},

		tooltipTitleAccessor( chartKey, dims, view ) {
			const dim = dims[ 0 ];
			switch ( chartKey ) {
				case 'stacked_bar':
				case 'stacked_area':
					return ( d ) => {
						const left  = d && d._metric ? d._metric : '';
						const right = d && dim && d[ dim ] !== undefined ? d[ dim ] : '';
						return [ left, right ].filter( Boolean ).join( ' — ' ) || ( view.name || '' );
					};
				case 'geomap':
					return ( d ) => ( d && ( d.municipio || d[ dim ] ) ) || '';
				case 'pie':
				case 'donut':
				case 'treemap':
				case 'box_whisker':
				case 'bar':
				case 'tree':
					return ( d ) => ( d && dim && d[ dim ] !== undefined ? String( d[ dim ] ) : ( view.name || '' ) );
				case 'line':
				case 'area':
					return ( d ) => {
						const xField = dims[ 0 ];
						const groupField = dims[ 1 ] || dims[ 0 ];
						const xv = d && xField ? d[ xField ] : '';
						const gv = d && groupField && groupField !== xField ? d[ groupField ] : '';
						return [ gv, xv ].filter( ( v ) => v !== undefined && v !== '' ).join( ' · ' ) || ( view.name || '' );
					};
				case 'network':
				case 'rings':
				case 'sankey':
					return ( d ) => ( d && ( d.id || ( dim && d[ dim ] ) ) ) || '';
				default:
					return ( d ) => ( d && dim && d[ dim ] !== undefined ? String( d[ dim ] ) : ( view.name || '' ) );
			}
		},

		tooltipTbody( chartKey, dims, measures ) {
			const rows = [];
			const self = this;

			if ( chartKey === 'stacked_bar' || chartKey === 'stacked_area' ) {
				if ( dims[ 0 ] ) {
					rows.push( [ this.humanizeLabel( dims[ 0 ] ), ( d ) => self.formatCell( d && d[ dims[ 0 ] ] ) ] );
				}
				rows.push( [ 'Métrica', ( d ) => ( d && d._metric ) || '—' ] );
				rows.push( [ 'Valor',   ( d ) => self.formatValue( '_value', d && d._value ) ] );
				return rows;
			}

			dims.forEach( ( field ) => {
				rows.push( [
					this.humanizeLabel( field ),
					( ( f ) => ( d ) => self.formatCell( d && d[ f ] ) )( field ),
				] );
			} );
			measures.forEach( ( field ) => {
				rows.push( [
					this.humanizeLabel( field ),
					( ( f ) => ( d ) => self.formatValue( f, d && d[ f ] ) )( field ),
				] );
			} );
			return rows;
		},

		humanizeLabel( field ) {
			if ( ! field ) {
				return '';
			}
			const lower = String( field ).toLowerCase();
			let unit    = '';
			let clean   = String( field );

			if ( /millones[_ ]?cop$/.test( lower ) ) {
				unit  = '(Millones COP)';
				clean = clean.replace( /[_ ]?millones[_ ]?cop$/i, '' );
			} else if ( /_cop$|\bcop\b/.test( lower ) ) {
				unit  = '(COP)';
				clean = clean.replace( /[_ ]?cop$/i, '' );
			} else if ( /(_pct|pct_|porcentaje)/.test( lower ) ) {
				unit  = '(%)';
				clean = clean.replace( /[_ ]?(pct|porcentaje)/gi, '' );
			}
			const label = this.humanizeKey( clean ) || this.humanizeKey( field );
			return unit ? label + ' ' + unit : label;
		},

		formatValue( field, value ) {
			if ( value === null || value === undefined || value === '' ) {
				return '—';
			}
			if ( typeof value !== 'number' && isNaN( Number( value ) ) ) {
				return this.formatCell( value );
			}
			const num   = Number( value );
			const lower = String( field || '' ).toLowerCase();
			if ( /pct|porcentaje|cobertura|participacion/.test( lower ) ) {
				return NF_PCT.format( num ) + ' %';
			}
			if ( Number.isInteger( num ) ) {
				return NF_INT.format( num );
			}
			return NF_DECIMAL.format( num );
		},

		formatCell( v ) {
			if ( v === null || v === undefined || v === '' ) {
				return '—';
			}
			if ( typeof v === 'number' ) {
				return Number.isInteger( v ) ? NF_INT.format( v ) : NF_DECIMAL.format( v );
			}
			if ( typeof v === 'boolean' ) {
				return v ? 'Sí' : 'No';
			}
			return String( v );
		},

		// ==============================================================
		// Shape
		// ==============================================================

		applyShape( viz ) {
			if ( typeof viz.shapeConfig === 'function' ) {
				viz.shapeConfig( {
					fill: ( _d, i ) => PALETTE[ i % PALETTE.length ],
				} );
			}
		},

		// ==============================================================
		// Data helpers
		// ==============================================================

		filterMeaningful( rows, chartKey, measures ) {
			if ( ! Array.isArray( rows ) || ! rows.length ) {
				return rows || [];
			}
			if ( ! Array.isArray( measures ) || ! measures.length ) {
				return rows;
			}
			if ( chartKey === 'network' || chartKey === 'rings' || chartKey === 'sankey' ) {
				return rows;
			}

			let relevant;
			if ( chartKey === 'stacked_bar' || chartKey === 'stacked_area' ) {
				const stackable = measures.filter(
					( m ) => ! /^(total|pct_|participacion|cobertura)|(_pct|_total)$/i.test( m )
				);
				relevant = stackable.length >= 2 ? stackable : measures.slice( 0, 3 );
			} else {
				const yearMeasures = measures.filter( ( m ) => /_(20\d{2})$/.test( m ) );
				if ( yearMeasures.length >= 2 ) {
					relevant = yearMeasures;
				} else {
					relevant = [ measures[ 0 ] ];
				}
			}

			return rows.filter( ( row ) => {
				return relevant.some( ( m ) => {
					const n = Number( row && row[ m ] );
					return Number.isFinite( n ) && n !== 0;
				} );
			} );
		},

		reshapeWideToLong( data, dims, measures ) {
			const self = this;
			const stackable = ( measures || [] ).filter(
				( m ) => ! /^(total|pct_|participacion|cobertura)|(_pct|_total)$/i.test( m )
			);
			const useMeasures = stackable.length >= 2 ? stackable : ( measures || [] ).slice( 0, 3 );
			const out = [];
			( data || [] ).forEach( ( row ) => {
				useMeasures.forEach( ( m ) => {
					out.push( Object.assign( {}, row, {
						_metric: self.humanizeKey( m ),
						_value:  Number( row[ m ] ) || 0,
					} ) );
				} );
			} );
			return out;
		},

		humanizeKey( key ) {
			if ( ! key ) {
				return '';
			}
			return String( key )
				.replace( /[_\-]+/g, ' ' )
				.replace( /\s+/g, ' ' )
				.trim()
				.replace( /^./, ( c ) => c.toUpperCase() );
		},

		normalizeMuni( value ) {
			if ( value === null || value === undefined ) {
				return '';
			}
			return String( value )
				.normalize( 'NFD' )
				.replace( /[\u0300-\u036f]/g, '' )
				.toUpperCase()
				.trim()
				.replace( /\s+/g, ' ' );
		},

		buildNodes( data, field ) {
			const seen = new Set();
			const out  = [];
			( data || [] ).forEach( ( row ) => {
				const id = row.id || row[ field ];
				if ( ! id || seen.has( id ) ) {
					return;
				}
				seen.add( id );
				out.push( Object.assign( { id }, row ) );
			} );
			return out;
		},

		safeCall( viz, method, value ) {
			if ( typeof viz[ method ] === 'function' ) {
				try {
					viz[ method ]( value );
				} catch ( err ) {
					console.warn( '[SPZ] viz.' + method + '() failed', err );
				}
			}
		},

		// ==============================================================
		// Native table renderer (no d3plus)
		// ==============================================================

		/**
		 * Render payload.data as a branded <table class="spz-tabla">.
		 * Columns come from mapping.columns or are inferred from the row keys.
		 *
		 * @param {Element} el      Container element.
		 * @param {object}  payload Normalised renderer payload.
		 */
		renderTable( el, payload ) {
			const data    = payload.data    || [];
			const mapping = payload.mapping || {};
			const columns = mapping.columns || null;
			el.innerHTML  = dataTable( data, columns );
		},
	};

	// ------------------------------------------------------------------
	// Expose as window.SPZ.renderer + window.SPZ.util.dataTable
	// ------------------------------------------------------------------
	window.SPZ = window.SPZ || {};
	window.SPZ.config      = window.SPZ.config || {};
	window.SPZ.renderer    = Renderer;
	window.SPZ.util                = window.SPZ.util || {};
	window.SPZ.util.dataTable      = dataTable;
	window.SPZ.util.attachVerDatos = attachVerDatos;
} )();
