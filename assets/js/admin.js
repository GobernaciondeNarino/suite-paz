/* global SPZ_ADMIN, SPZRenderer */
/**
 * Suite PAZ — admin script.
 *
 * Handles two independent screens:
 *
 * 1. BUILDER  (#spz-builder-wrap)
 *    Sección select → REST /views → views list → chart types → live preview
 *    → generated [spz_grafico …] shortcode (copyable).
 *
 * 2. EDITOR   (#spz-editor-wrap)
 *    Sección select + view select → REST /export → editable form
 *    → POST /save (Guardar), POST /reset (Restablecer), download (Exportar JSON).
 *
 * All REST URLs are built from SPZ_ADMIN.restBase (no hardcoded paths).
 * Nonces are taken from SPZ_ADMIN.nonce.
 *
 * Adapted from assets/js/admin.js (tic-suite / TSG_Admin).
 * Changes: TSG_ADMIN → SPZ_ADMIN, project → seccion, tsg- → spz- selectors,
 * tsg_grafico → spz_grafico, added full EDITOR module (tic-suite had read-only).
 *
 * @package SuitePaz
 */
( function () {
	'use strict';

	// Guard — SPZ_ADMIN must be localized by wp_localize_script.
	if ( typeof SPZ_ADMIN === 'undefined' ) {
		return;
	}

	const DEFAULT_SECCION = 'dni';
	const DEFAULT_ACTIONS = [ 'detalle', 'compartir', 'datos', 'imagen', 'descarga', 'cambiar' ];

	const $  = ( sel, root ) => ( root || document ).querySelector( sel );
	const $$ = ( sel, root ) => Array.from( ( root || document ).querySelectorAll( sel ) );

	// ============================================================
	// REST helpers
	// ============================================================

	function restUrl( path ) {
		// SPZ_ADMIN.restBase is the base URL (e.g. https://…/wp-json/suite-paz/v1)
		// path starts with '/' e.g. '/views'
		return SPZ_ADMIN.restBase.replace( /\/$/, '' ) + path;
	}

	async function restGet( path ) {
		const res = await fetch( restUrl( path ), {
			headers:     { 'X-WP-Nonce': SPZ_ADMIN.nonce },
			credentials: 'same-origin',
		} );
		if ( ! res.ok ) {
			throw new Error( 'HTTP ' + res.status );
		}
		return res.json();
	}

	async function restPost( path, body ) {
		const res = await fetch( restUrl( path ), {
			method:      'POST',
			headers:     {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   SPZ_ADMIN.nonce,
			},
			credentials: 'same-origin',
			body:        JSON.stringify( body ),
		} );
		if ( ! res.ok ) {
			const err = await res.json().catch( () => ( {} ) );
			throw new Error( err.message || 'HTTP ' + res.status );
		}
		return res.json();
	}

	// ============================================================
	// Utility
	// ============================================================

	function escapeHtml( str ) {
		return String( str == null ? '' : str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#39;' );
	}
	function escapeAttr( str ) {
		return String( str == null ? '' : str ).replace( /[^a-z0-9\-_.:]/gi, '' );
	}

	function copyToClipboard( text, srcBtn ) {
		const i18n    = SPZ_ADMIN.i18n || {};
		const restore = () => {
			if ( ! srcBtn ) { return; }
			const orig = srcBtn.innerHTML;
			srcBtn.innerHTML = '<span class="dashicons dashicons-yes" aria-hidden="true"></span> ' + escapeHtml( i18n.copied || 'Copiado' );
			srcBtn.classList.add( 'is-success' );
			setTimeout( () => {
				srcBtn.innerHTML = orig;
				srcBtn.classList.remove( 'is-success' );
			}, 1800 );
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( restore ).catch( fallback );
		} else {
			fallback();
		}
		function fallback() {
			const ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.setAttribute( 'readonly', '' );
			ta.style.cssText = 'position:absolute;left:-9999px;top:0';
			document.body.appendChild( ta );
			ta.select();
			try { document.execCommand( 'copy' ); restore(); } catch ( e ) { /* silent */ }
			document.body.removeChild( ta );
		}
	}

	// ============================================================
	// BUILDER module
	// ============================================================

	const builder = ( function () {
		const state = {
			seccion:     DEFAULT_SECCION,
			viewId:      null,
			chartKey:    null,
			view:        null,
			compatible:  [],
			lastPayload: null,
			options: {
				legend:       true,
				legend_style: 'text',
				toolbar:      true,
				actions:      DEFAULT_ACTIONS.slice(),
				x_title:      '',
				y_title:      '',
				timeline:     'auto',
			},
		};

		const els = {};

		function init() {
			const wrap = $( '#spz-builder-wrap' );
			if ( ! wrap ) { return; }

			state.seccion = wrap.getAttribute( 'data-spz-seccion' ) || DEFAULT_SECCION;

			els.seccionSelect  = $( '[data-spz-builder-seccion]', wrap );
			els.viewList       = $( '.spz-views-list', wrap );
			els.typesWrap      = $( '#spz-chart-types', wrap );
			els.preview        = $( '#spz-preview', wrap );
			els.shortcodeBox   = $( '.spz-shortcode-box', wrap );
			els.shortcodeInput = $( '#spz-shortcode-input', wrap );
			els.copyBtn        = $( '#spz-copy-btn', wrap );
			els.options        = $( '#spz-options', wrap );

			if ( els.seccionSelect ) {
				els.seccionSelect.addEventListener( 'change', onSeccionChange );
			}

			if ( els.viewList ) {
				$$( '.spz-view-item', els.viewList ).forEach( ( btn ) => {
					btn.addEventListener( 'click', () => onSelectView( btn ) );
				} );
			}

			if ( els.copyBtn ) {
				els.copyBtn.addEventListener( 'click', () => {
					copyToClipboard( els.shortcodeInput.value, els.copyBtn );
				} );
			}

			wireOptionsUI();
		}

		// ------ Sección change (JS-driven view refresh) ------

		async function onSeccionChange() {
			const sec = els.seccionSelect.value;
			if ( ! sec ) { return; }
			state.seccion  = sec;
			state.viewId   = null;
			state.chartKey = null;
			state.lastPayload = null;

			if ( els.shortcodeBox ) { els.shortcodeBox.hidden = true; }
			if ( els.preview )      { els.preview.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.loading || 'Cargando…' ) + '</p>'; }
			if ( els.typesWrap )    { els.typesWrap.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.selectView || 'Selecciona primero una vista.' ) + '</p>'; }

			try {
				const views = await restGet( '/views?seccion=' + encodeURIComponent( sec ) );
				renderViewList( views );
			} catch ( err ) {
				if ( els.viewList ) {
					els.viewList.innerHTML = '<li><p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.error || 'Error al cargar vistas.' ) + '</p></li>';
				}
			}
		}

		function renderViewList( views ) {
			if ( ! els.viewList ) { return; }
			// Filter out modules.
			const filtered = ( views || [] ).filter( ( v ) => ! v.is_module );
			if ( ! filtered.length ) {
				els.viewList.innerHTML = '<li><p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.noViews || 'Sin vistas en esta sección.' ) + '</p></li>';
				return;
			}
			els.viewList.innerHTML = filtered.map( ( v ) => `
				<li>
					<button type="button" class="spz-view-item" data-view-id="${ escapeAttr( v.id ) }" role="option" aria-selected="false">
						<span class="spz-view-item__name">${ escapeHtml( v.name ) }</span>
						<span class="spz-view-item__meta">
							<span class="spz-pill spz-pill--${ escapeAttr( v.category || 'categorical' ) }">${ escapeHtml( v.category || '' ) }</span>
							<span class="spz-view-item__rows">${ parseInt( v.rows, 10 ) || 0 } ${ escapeHtml( SPZ_ADMIN.i18n.rows || 'filas' ) }</span>
						</span>
						<span class="spz-view-item__desc">${ escapeHtml( v.description || '' ) }</span>
					</button>
				</li>
			` ).join( '' );
			$$( '.spz-view-item', els.viewList ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => onSelectView( btn ) );
			} );
		}

		// ------ Options UI ------

		function wireOptionsUI() {
			if ( ! els.options ) { return; }

			$$( '[data-spz-opt]', els.options ).forEach( ( input ) => {
				const handler = () => {
					const key = input.getAttribute( 'data-spz-opt' );
					state.options[ key ] = input.type === 'checkbox' ? input.checked : input.value;
					onOptionsChange();
				};
				input.addEventListener( 'change', handler );
				if ( input.type === 'text' ) { input.addEventListener( 'input', handler ); }
			} );

			$$( '[data-spz-action-opt]', els.options ).forEach( ( input ) => {
				input.addEventListener( 'change', () => {
					const action = input.getAttribute( 'data-spz-action-opt' );
					if ( input.checked ) {
						if ( ! state.options.actions.includes( action ) ) {
							state.options.actions = DEFAULT_ACTIONS.filter(
								( a ) => a === action || state.options.actions.includes( a )
							);
						}
					} else {
						state.options.actions = state.options.actions.filter( ( a ) => a !== action );
					}
					onOptionsChange();
				} );
			} );
		}

		function onOptionsChange() {
			if ( state.lastPayload ) { renderPreview( state.lastPayload ); }
			if ( state.chartKey )   { renderShortcode(); }
		}

		// ------ View picker ------

		async function onSelectView( btn ) {
			$$( '.spz-view-item', els.viewList ).forEach( ( el ) => {
				el.classList.remove( 'is-active' );
				el.setAttribute( 'aria-selected', 'false' );
			} );
			btn.classList.add( 'is-active' );
			btn.setAttribute( 'aria-selected', 'true' );

			state.viewId      = btn.dataset.viewId;
			state.chartKey    = null;
			state.lastPayload = null;

			els.typesWrap.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.loading || 'Cargando…' ) + '</p>';
			els.preview.innerHTML   = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.loading || 'Cargando…' ) + '</p>';
			if ( els.shortcodeBox ) { els.shortcodeBox.hidden = true; }

			try {
				const payload = await restGet(
					'/views/' + encodeURIComponent( state.viewId ) + '?seccion=' + encodeURIComponent( state.seccion )
				);
				state.view       = payload.view;
				state.compatible = payload.compatible || [];
				renderChartTypes();
				els.preview.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.selectChart || 'Selecciona un tipo de gráfico.' ) + '</p>';
			} catch ( err ) {
				els.typesWrap.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.noCompatible || 'Sin gráficos compatibles.' ) + '</p>';
			}
		}

		function renderChartTypes() {
			if ( ! state.compatible.length ) {
				els.typesWrap.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.noCompatible || 'Sin gráficos compatibles.' ) + '</p>';
				return;
			}
			els.typesWrap.innerHTML = '';
			state.compatible.forEach( ( ct ) => {
				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'spz-type-card';
				btn.dataset.typeKey = ct.key;
				btn.innerHTML = `
					<span class="dashicons dashicons-${ escapeAttr( ct.icon || 'chart-area' ) }" aria-hidden="true"></span>
					<span class="spz-type-card__label">${ escapeHtml( ct.label ) }</span>
					<span class="spz-type-card__desc">${ escapeHtml( ct.description || '' ) }</span>
				`;
				btn.addEventListener( 'click', () => onSelectChart( ct.key, btn ) );
				els.typesWrap.appendChild( btn );
			} );
		}

		// ------ Chart render + shortcode ------

		async function onSelectChart( key, btn ) {
			$$( '.spz-type-card', els.typesWrap ).forEach( ( el ) => el.classList.remove( 'is-active' ) );
			btn.classList.add( 'is-active' );
			state.chartKey = key;
			els.preview.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.loading || 'Cargando…' ) + '</p>';

			try {
				const url = '/render?seccion=' + encodeURIComponent( state.seccion ) +
					'&view=' + encodeURIComponent( state.viewId ) +
					'&type=' + encodeURIComponent( key );
				state.lastPayload = await restGet( url );
				renderPreview( state.lastPayload );
				renderShortcode();
			} catch ( err ) {
				els.preview.innerHTML = '<p class="spz-empty">' + escapeHtml( SPZ_ADMIN.i18n.error || 'No se pudo cargar el gráfico.' ) + '</p>';
			}
		}

		function renderPreview( payload ) {
			const opts        = state.options;
			const containerId = 'spz-preview-canvas';
			const showToolbar = opts.toolbar && opts.actions.length > 0;
			const actions     = opts.actions.map( toolbarButtonHtml ).join( '' );

			els.preview.innerHTML = `
				<figure id="spz-preview-figure" class="spz-figure spz-preview__figure"
						data-view="${ escapeAttr( state.viewId ) }"
						data-type="${ escapeAttr( state.chartKey ) }">
					<figcaption class="spz-figure__title">${ escapeHtml( ( state.view && state.view.name ) || '' ) }</figcaption>
					${ showToolbar ? `<div class="spz-toolbar">${ actions }</div>` : '' }
					<div id="${ containerId }" class="spz-chart spz-preview__canvas" style="height:440px;min-height:440px;"></div>
				</figure>
			`;

			// Use the frontend renderer (window.SPZ.renderer) which is enqueued on the builder screen.
			if ( window.SPZ && window.SPZ.renderer && typeof window.SPZ.renderer.render === 'function' ) {
				window.SPZ.renderer.render(
					document.getElementById( containerId ),
					{ view: payload, type: state.chartKey, options: { legend: opts.legend, legendStyle: opts.legend_style || 'text', xTitle: opts.x_title || '', yTitle: opts.y_title || '' } }
				);
			}

			// Wire type-selector if present.
			const sel = $( '[data-spz-type-selector="1"]', els.preview );
			if ( sel ) {
				sel.innerHTML = state.compatible.map( ( c ) => {
					const isSelected = c.key === state.chartKey ? ' selected' : '';
					return `<option value="${ escapeAttr( c.key ) }"${ isSelected }>${ escapeHtml( c.label ) }</option>`;
				} ).join( '' );
				sel.addEventListener( 'change', ( ev ) => onPreviewTypeChange( ev.target.value ) );
			}
		}

		async function onPreviewTypeChange( newType ) {
			if ( ! newType || newType === state.chartKey ) { return; }
			const card = $( `.spz-type-card[data-type-key="${ newType }"]`, els.typesWrap );
			if ( card ) {
				$$( '.spz-type-card', els.typesWrap ).forEach( ( el ) => el.classList.remove( 'is-active' ) );
				card.classList.add( 'is-active' );
			}
			state.chartKey = newType;
			try {
				const url = '/render?seccion=' + encodeURIComponent( state.seccion ) +
					'&view=' + encodeURIComponent( state.viewId ) +
					'&type=' + encodeURIComponent( newType );
				state.lastPayload = await restGet( url );
				renderPreview( state.lastPayload );
				renderShortcode();
			} catch ( err ) { /* silent — preview just keeps the old state */ }
		}

		const ACTION_META = {
			detalle:   { icon: 'info-outline',  label: 'Detalle' },
			compartir: { icon: 'share',         label: 'Compartir' },
			datos:     { icon: 'editor-table',  label: 'Datos' },
			imagen:    { icon: 'format-image',  label: 'Imagen' },
			descarga:  { icon: 'download',      label: 'Descarga' },
		};

		function toolbarButtonHtml( action ) {
			if ( action === 'cambiar' ) {
				return `<label class="spz-action spz-action--select" title="${ escapeHtml( SPZ_ADMIN.i18n.changeChart || 'Cambiar tipo' ) }">
					<span class="dashicons dashicons-update" aria-hidden="true"></span>
					<span class="spz-action__label">${ escapeHtml( SPZ_ADMIN.i18n.typeLabel || 'Tipo' ) }</span>
					<select class="spz-action__select" data-spz-type-selector="1"></select>
				</label>`;
			}
			const meta = ACTION_META[ action ];
			if ( ! meta ) { return ''; }
			return `<button type="button" class="spz-action" disabled title="${ escapeHtml( meta.label ) }">
				<span class="dashicons dashicons-${ meta.icon }" aria-hidden="true"></span>
				<span class="spz-action__label">${ escapeHtml( meta.label ) }</span>
			</button>`;
		}

		function renderShortcode() {
			const opts  = state.options;
			const parts = [
				`view="${ state.viewId }"`,
				`type="${ state.chartKey }"`,
			];
			// Emit seccion only when it's not the default.
			if ( state.seccion && state.seccion !== DEFAULT_SECCION ) {
				parts.push( `seccion="${ state.seccion }"` );
			}
			parts.push( 'height="420"' );
			parts.push( `title="${ String( ( state.view && state.view.name ) || '' ).replace( /"/g, "'" ) }"` );
			if ( opts.legend === false )                                  { parts.push( 'legend="false"' ); }
			if ( opts.legend && opts.legend_style !== 'text' )            { parts.push( `legend_style="${ opts.legend_style }"` ); }
			if ( opts.toolbar === false )                                  { parts.push( 'toolbar="false"' ); }
			if ( opts.toolbar ) {
				const same = opts.actions.length === DEFAULT_ACTIONS.length &&
					opts.actions.every( ( a, i ) => a === DEFAULT_ACTIONS[ i ] );
				if ( ! same && opts.actions.length > 0 ) { parts.push( `actions="${ opts.actions.join( ',' ) }"` ); }
			}
			if ( opts.x_title ) { parts.push( `x_title="${ String( opts.x_title ).replace( /"/g, "'" ) }"` ); }
			if ( opts.y_title ) { parts.push( `y_title="${ String( opts.y_title ).replace( /"/g, "'" ) }"` ); }
			if ( opts.timeline && opts.timeline !== 'auto' ) { parts.push( `timeline="${ opts.timeline }"` ); }

			els.shortcodeInput.value = `[spz_grafico ${ parts.join( ' ' ) }]`;
			if ( els.shortcodeBox ) { els.shortcodeBox.hidden = false; }
		}

		return { init };
	} )();

	// ============================================================
	// EDITOR module
	// ============================================================

	const editor = ( function () {
		const state = {
			seccion:  DEFAULT_SECCION,
			slug:     '',
			payload:  null,
			source:   '',
		};

		const els = {};
		const i18n = SPZ_ADMIN.i18n || {};

		function init() {
			const wrap = $( '#spz-editor-wrap' );
			if ( ! wrap ) { return; }

			state.seccion = wrap.getAttribute( 'data-spz-seccion' ) || DEFAULT_SECCION;

			els.seccionSelect = $( '[data-spz-editor-seccion]', wrap );
			els.viewSelect    = $( '[data-spz-editor-view]', wrap );
			els.formArea      = $( '#spz-editor-form', wrap );
			els.feedback      = $( '#spz-editor-feedback', wrap );
			els.sourceEl      = $( '#spz-editor-source', wrap );
			els.sourceLabel   = $( '#spz-source-label', wrap );
			els.btnSave       = $( '#spz-btn-save', wrap );
			els.btnExport     = $( '#spz-btn-export', wrap );
			els.btnReset      = $( '#spz-btn-reset', wrap );

			if ( els.seccionSelect ) {
				els.seccionSelect.addEventListener( 'change', onSeccionChange );
			}
			if ( els.viewSelect ) {
				els.viewSelect.addEventListener( 'change', onViewChange );
			}
			if ( els.btnSave )   { els.btnSave.addEventListener( 'click', onSave ); }
			if ( els.btnExport ) { els.btnExport.addEventListener( 'click', onExport ); }
			if ( els.btnReset )  { els.btnReset.addEventListener( 'click', onReset ); }

			// Load initial views for the default sección.
			loadViews( state.seccion );
		}

		async function loadViews( sec ) {
			if ( els.viewSelect ) {
				els.viewSelect.disabled = true;
				els.viewSelect.innerHTML = '<option value="">' + escapeHtml( i18n.loading || 'Cargando…' ) + '</option>';
			}
			try {
				const views = await restGet( '/views?seccion=' + encodeURIComponent( sec ) );
				populateViewSelect( views );
			} catch ( err ) {
				if ( els.viewSelect ) {
					els.viewSelect.innerHTML = '<option value="">' + escapeHtml( i18n.error || 'Error al cargar.' ) + '</option>';
				}
			}
		}

		function populateViewSelect( views ) {
			if ( ! els.viewSelect ) { return; }
			const opts = [ '<option value="">' + escapeHtml( i18n.selectView || '— Selecciona —' ) + '</option>' ];
			( views || [] ).forEach( ( v ) => {
				const label = v.is_module ? ( '[' + escapeHtml( v.modulo || 'módulo' ) + '] ' + escapeHtml( v.name || v.id ) )
				                          : escapeHtml( v.name || v.id );
				opts.push( `<option value="${ escapeAttr( v.id ) }">${ label }</option>` );
			} );
			els.viewSelect.innerHTML = opts.join( '' );
			els.viewSelect.disabled  = false;
		}

		async function onSeccionChange() {
			const sec = els.seccionSelect.value;
			if ( ! sec ) { return; }
			state.seccion = sec;
			state.slug    = '';
			state.payload = null;
			resetForm();
			disableActionButtons();
			await loadViews( sec );
		}

		async function onViewChange() {
			const slug = els.viewSelect.value;
			if ( ! slug ) {
				state.slug    = '';
				state.payload = null;
				resetForm();
				disableActionButtons();
				return;
			}
			state.slug = slug;
			showFeedback( '', '' );
			setFormLoading();

			try {
				const resp = await restGet(
					'/export?seccion=' + encodeURIComponent( state.seccion ) +
					'&slug='           + encodeURIComponent( slug )
				);
				state.payload = resp.payload;
				state.source  = resp.source || 'seed';
				renderEditorForm( resp.payload );
				enableActionButtons();
				showSourceBadge( resp.source );
			} catch ( err ) {
				els.formArea.innerHTML = '<p class="spz-empty">' + escapeHtml( i18n.error || 'Error al cargar los datos.' ) + '</p>';
			}
		}

		function resetForm() {
			if ( els.formArea ) {
				els.formArea.innerHTML = '<p class="spz-empty">' + escapeHtml( i18n.selectView || 'Selecciona una vista para comenzar.' ) + '</p>';
			}
			hideSourceBadge();
		}

		function setFormLoading() {
			if ( els.formArea ) {
				els.formArea.innerHTML = '<p class="spz-empty spz-loading">' + escapeHtml( i18n.loading || 'Cargando datos…' ) + '</p>';
			}
		}

		function disableActionButtons() {
			[ els.btnSave, els.btnExport, els.btnReset ].forEach( ( b ) => { if ( b ) { b.disabled = true; } } );
		}

		function enableActionButtons() {
			[ els.btnSave, els.btnExport, els.btnReset ].forEach( ( b ) => { if ( b ) { b.disabled = false; } } );
		}

		function showSourceBadge( source ) {
			if ( ! els.sourceEl || ! els.sourceLabel ) { return; }
			const isOverride = source === 'override';
			els.sourceLabel.textContent = isOverride
				? ( i18n.sourceOverride || 'Datos: override en BD' )
				: ( i18n.sourceSeed     || 'Datos: semilla JSON' );
			els.sourceEl.className = 'spz-source-badge spz-source-badge--' + ( isOverride ? 'override' : 'seed' );
			els.sourceEl.hidden = false;
		}

		function hideSourceBadge() {
			if ( els.sourceEl ) { els.sourceEl.hidden = true; }
		}

		// ------ Form builder ------

		function renderEditorForm( payload ) {
			if ( ! els.formArea || ! payload ) { return; }

			// Module shape: has 'modulo' key.
			if ( payload.modulo ) {
				els.formArea.innerHTML = buildModuleForm( payload );
			} else if ( payload.municipios || payload.datos || Array.isArray( payload.data ) ) {
				// Geographic / tabular view: editable table.
				els.formArea.innerHTML = buildTableForm( payload );
			} else {
				// Fallback: generic key-value form.
				els.formArea.innerHTML = buildGenericForm( payload );
			}
		}

		/**
		 * Build an editable table for geographic/tabular views.
		 * One row per record, one input per measure column.
		 */
		function buildTableForm( payload ) {
			const rows  = payload.municipios || payload.datos || payload.data || [];
			if ( ! rows.length ) {
				return '<p class="spz-empty">' + escapeHtml( i18n.noData || 'Sin datos.' ) + '</p>';
			}
			const keys     = Object.keys( rows[ 0 ] );
			const numKeys  = keys.filter( ( k ) => {
				const v = rows[ 0 ][ k ];
				return typeof v === 'number';
			} );
			const dimKeys  = keys.filter( ( k ) => ! numKeys.includes( k ) );

			let html = '<div class="spz-table-wrap spz-editor-table-wrap">';
			html += '<table class="widefat striped spz-data-table spz-editor-table">';
			html += '<thead><tr>';
			dimKeys.forEach( ( k ) => { html += `<th>${ escapeHtml( k ) }</th>`; } );
			numKeys.forEach( ( k ) => { html += `<th class="spz-th-edit"><span class="dashicons dashicons-edit" aria-hidden="true"></span>${ escapeHtml( k ) }</th>`; } );
			html += '</tr></thead><tbody>';

			rows.forEach( ( row, ri ) => {
				html += '<tr>';
				dimKeys.forEach( ( k ) => {
					html += `<td>${ escapeHtml( String( row[ k ] ?? '' ) ) }</td>`;
				} );
				numKeys.forEach( ( k ) => {
					const val = row[ k ] ?? '';
					html += `<td><input type="number" step="any"
						class="spz-cell-input"
						data-row="${ ri }" data-key="${ escapeAttr( k ) }"
						value="${ escapeHtml( String( val ) ) }" /></td>`;
				} );
				html += '</tr>';
			} );
			html += '</tbody></table></div>';
			return html;
		}

		/**
		 * Build an editable form for native modules (kpi, compare, timeline, logro).
		 */
		function buildModuleForm( payload ) {
			const modType = payload.modulo || '';
			let html = `<div class="spz-module-form spz-card">`;
			html += `<h2 class="spz-card__title"><span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>${ escapeHtml( modType ) }</h2>`;

			// KPI / Compare
			if ( modType === 'kpi' || modType === 'compare' ) {
				html += fieldRow( 'titulo',  payload.titulo  || '', i18n.labelTitle || 'Título', 'text' );
				html += fieldRow( 'unidad',  payload.unidad  || '', i18n.labelUnit  || 'Unidad', 'text' );
				if ( modType === 'kpi' ) {
					html += fieldRow( 'valor',   payload.valor   != null ? payload.valor : '',   'Valor actual', 'number' );
				}
				if ( modType === 'compare' ) {
					html += fieldRow( 'from_v', ( payload['from'] && payload['from'].v != null ) ? payload['from'].v : '', 'Valor anterior (from.v)', 'number' );
					html += fieldRow( 'to_v',   ( payload['to']   && payload['to'].v   != null ) ? payload['to'].v   : '', 'Valor actual (to.v)',    'number' );
					html += fieldRow( 'delta',  payload.delta  != null ? payload.delta : '', 'Delta (%)', 'number' );
				}
				html += fieldRow( 'fuente',  payload.fuente  || '', i18n.labelSource || 'Fuente', 'text' );
			}

			// Timeline
			if ( modType === 'timeline' ) {
				html += fieldRow( 'titulo',  payload.titulo  || '', i18n.labelTitle || 'Título', 'text' );
				const eventos = payload.eventos || [];
				html += `<div class="spz-field-group"><label><strong>${ escapeHtml( i18n.labelEvents || 'Eventos' ) }</strong></label>`;
				if ( eventos.length ) {
					html += '<ol class="spz-timeline-editor">';
					eventos.forEach( ( ev, idx ) => {
						html += `<li class="spz-timeline-editor__item">
							<label>${ escapeHtml( i18n.labelDate || 'Fecha' ) }:
								<input type="text" class="spz-cell-input" data-evt="${ idx }" data-ekey="fecha" value="${ escapeHtml( ev.fecha || '' ) }" />
							</label>
							<label>${ escapeHtml( i18n.labelText || 'Texto' ) }:
								<input type="text" class="spz-cell-input" data-evt="${ idx }" data-ekey="texto" value="${ escapeHtml( ev.texto || '' ) }" />
							</label>
						</li>`;
					} );
					html += '</ol>';
				} else {
					html += '<p class="spz-hint">' + escapeHtml( i18n.noEvents || 'Sin eventos.' ) + '</p>';
				}
				html += '</div>';
			}

			// Logro
			if ( modType === 'logro' ) {
				html += fieldRow( 'titulo',    payload.titulo    || '', i18n.labelTitle   || 'Título', 'text' );
				html += fieldRow( 'texto',     payload.texto     || '', i18n.labelText    || 'Texto', 'text' );
				html += fieldRow( 'fuente',    payload.fuente    || '', i18n.labelSource  || 'Fuente', 'text' );
			}

			html += '</div>';
			return html;
		}

		function fieldRow( key, value, label, type ) {
			const inputType = type === 'number' ? 'number' : 'text';
			const step      = type === 'number' ? ' step="any"' : '';
			return `<div class="spz-field-row">
				<label for="spz-field-${ escapeAttr( key ) }"><strong>${ escapeHtml( label ) }:</strong></label>
				<input type="${ inputType }"${ step } id="spz-field-${ escapeAttr( key ) }"
					class="spz-cell-input" data-field="${ escapeAttr( key ) }"
					value="${ escapeHtml( String( value ) ) }" />
			</div>`;
		}

		/**
		 * Generic form for unknown payload shapes.
		 */
		function buildGenericForm( payload ) {
			let html = '<div class="spz-generic-form spz-card">';
			Object.entries( payload ).forEach( ( [ key, val ] ) => {
				if ( typeof val === 'object' ) { return; } // skip nested
				const type = typeof val === 'number' ? 'number' : 'text';
				html += fieldRow( key, String( val ), key, type );
			} );
			html += '</div>';
			return html;
		}

		// ------ Collect edited payload from DOM ------

		function collectPayload() {
			if ( ! state.payload ) { return null; }
			const edited = JSON.parse( JSON.stringify( state.payload ) );

			// Table form inputs.
			$$( '.spz-cell-input[data-row][data-key]', els.formArea ).forEach( ( inp ) => {
				const ri  = parseInt( inp.dataset.row, 10 );
				const key = inp.dataset.key;
				if ( isNaN( ri ) || ! key ) { return; }
				const dataKey = edited.municipios ? 'municipios' : edited.datos ? 'datos' : 'data';
				const arr     = edited[ dataKey ];
				if ( arr && arr[ ri ] ) {
					const n = parseFloat( inp.value );
					arr[ ri ][ key ] = isNaN( n ) ? inp.value : n;
				}
			} );

			// Timeline event inputs.
			$$( '.spz-cell-input[data-evt][data-ekey]', els.formArea ).forEach( ( inp ) => {
				const idx  = parseInt( inp.dataset.evt, 10 );
				const ekey = inp.dataset.ekey;
				if ( isNaN( idx ) || ! ekey ) { return; }
				if ( ! edited.eventos || ! edited.eventos[ idx ] ) { return; }
				edited.eventos[ idx ][ ekey ] = inp.value;
			} );

			// Module field inputs.
			$$( '.spz-cell-input[data-field]', els.formArea ).forEach( ( inp ) => {
				const key = inp.dataset.field;
				if ( ! key ) { return; }
				// Handle nested compare fields: from_v → from.v, to_v → to.v.
				if ( key === 'from_v' ) {
					if ( ! edited['from'] ) { edited['from'] = {}; }
					const fv = parseFloat( inp.value );
					edited['from'].v = ! isNaN( fv ) ? fv : inp.value;
				} else if ( key === 'to_v' ) {
					if ( ! edited['to'] ) { edited['to'] = {}; }
					const tv = parseFloat( inp.value );
					edited['to'].v = ! isNaN( tv ) ? tv : inp.value;
				} else {
					const n = parseFloat( inp.value );
					edited[ key ] = inp.type === 'number' && ! isNaN( n ) ? n : inp.value;
				}
			} );

			// Strip keys disallowed by the module whitelist.
			const editedModType = edited.modulo || '';
			if ( editedModType && editedModType !== 'kpi' ) { delete edited.valor; }
			delete edited.subtitulo;

			return edited;
		}

		// ------ Actions ------

		async function onSave() {
			const payload = collectPayload();
			if ( ! payload ) { return; }
			els.btnSave.disabled = true;
			showFeedback( i18n.saving || 'Guardando…', 'info' );

			try {
				await restPost( '/save', {
					seccion: state.seccion,
					slug:    state.slug,
					payload,
				} );
				state.payload = payload;
				state.source  = 'override';
				showFeedback( i18n.saved || 'Datos guardados correctamente.', 'success' );
				showSourceBadge( 'override' );
			} catch ( err ) {
				showFeedback( ( i18n.saveError || 'Error al guardar: ' ) + err.message, 'error' );
			} finally {
				els.btnSave.disabled = false;
			}
		}

		function onExport() {
			const payload = collectPayload();
			if ( ! payload ) { return; }
			const json = JSON.stringify( payload, null, 2 );
			const blob = new Blob( [ json ], { type: 'application/json' } );
			const url  = URL.createObjectURL( blob );
			const a    = document.createElement( 'a' );
			a.href     = url;
			a.download = state.seccion + '-' + state.slug + '.json';
			document.body.appendChild( a );
			a.click();
			document.body.removeChild( a );
			URL.revokeObjectURL( url );
		}

		async function onReset() {
			const i18nConfirm = i18n.resetConfirm || '¿Restablecer los datos originales? Esta acción eliminará el override guardado.';
			if ( ! window.confirm( i18nConfirm ) ) { return; }

			els.btnReset.disabled = true;
			showFeedback( i18n.resetting || 'Restableciendo…', 'info' );

			try {
				await restPost( '/reset', {
					seccion: state.seccion,
					slug:    state.slug,
				} );
				// Reload seed data.
				const resp = await restGet(
					'/export?seccion=' + encodeURIComponent( state.seccion ) +
					'&slug='           + encodeURIComponent( state.slug )
				);
				state.payload = resp.payload;
				state.source  = resp.source;
				renderEditorForm( resp.payload );
				showFeedback( i18n.reset || 'Datos restablecidos a la semilla original.', 'success' );
				showSourceBadge( resp.source );
			} catch ( err ) {
				showFeedback( ( i18n.resetError || 'Error al restablecer: ' ) + err.message, 'error' );
			} finally {
				els.btnReset.disabled = false;
			}
		}

		function showFeedback( msg, type ) {
			if ( ! els.feedback ) { return; }
			if ( ! msg ) { els.feedback.hidden = true; return; }
			els.feedback.textContent = msg;
			els.feedback.className   = 'spz-feedback spz-feedback--' + ( type || 'info' );
			els.feedback.hidden      = false;
		}

		return { init };
	} )();

	// ============================================================
	// Gallery copy buttons (shortcodes screen)
	// ============================================================

	function wireGalleryCopyButtons() {
		$$( '[data-spz-copy]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				const row   = btn.closest( '.spz-shortcode-row' );
				const input = row && row.querySelector( 'input' );
				if ( input ) { copyToClipboard( input.value, btn ); }
			} );
		} );
	}

	// ============================================================
	// Bootstrap
	// ============================================================

	document.addEventListener( 'DOMContentLoaded', function () {
		builder.init();
		editor.init();
		wireGalleryCopyButtons();
	} );

} )();
