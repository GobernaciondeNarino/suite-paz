/* global SPZ_FRONTEND, SPZ */
/**
 * Suite PAZ · Frontend hydrator.
 *
 * For every .spz-chart[data-view][data-type][data-seccion] on the page:
 *  1. Fetch the render payload from the REST endpoint (WP) or a JSON URL (harness).
 *  2. Call SPZ.renderer.render( el, { view: payload, type, options } ).
 *  3. If data-toolbar="1" and data-actions is non-empty, build a .spz-toolbar
 *     with action buttons (detalle/compartir/datos/imagen/descarga/cambiar).
 *
 * WordPress mode:
 *   Reads window.SPZ_FRONTEND = { restUrl, nonce, i18n: { empty, error, loading } }
 *   (provided by wp_localize_script in class-spz-plugin.php).
 *   REST URL: /wp-json/suite-paz/v1/render?seccion=…&view=…&type=…
 *
 * Harness / standalone mode (no WP):
 *   If a container has a [data-spz-src] attribute pointing to a JSON file,
 *   the hydrator fetches that file directly.
 *   Expose SPZ.frontend.buildToolbar(el, payload) for harness Test 11.
 *
 * Toolbar actions:
 *   detalle   → modal with chart metadata (tipo/label, categoría, dims, medidas, filas).
 *   compartir → navigator.share or copy URL to clipboard; transient flash.
 *   datos     → triggers the existing .spz-verdatos panel (Fix-Task 6 reuse).
 *   imagen    → SVG → PNG (canvas 2×, white bg) download; omitted for tabla.
 *   descarga  → download {view, data} as JSON.
 *   cambiar   → <select> of compatible types; re-fetches and re-renders on change (WP only).
 *
 * Adapted from tic-suite assets/js/frontend.js (TSG → SPZ).
 *
 * @package SuitePaz
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.spz-chart[data-view][data-type][data-seccion]' ).forEach( initChart );
		document.querySelectorAll( '.spz-module[data-modulo][data-id][data-seccion]' ).forEach( initModule );
	} );

	// ------------------------------------------------------------------
	// Per-chart initialisation
	// ------------------------------------------------------------------

	function initChart( el ) {
		const viewId  = el.getAttribute( 'data-view' );
		const type    = el.getAttribute( 'data-type' );
		const seccion = el.getAttribute( 'data-seccion' ) || 'dni';

		if ( ! viewId || ! type ) {
			return;
		}

		// Options forwarded to the renderer.
		const legend      = el.getAttribute( 'data-legend' ) !== '0';
		const legendStyle = el.getAttribute( 'data-legend-style' ) || 'text';
		const xTitle      = el.getAttribute( 'data-x-title' ) || '';
		const yTitle      = el.getAttribute( 'data-y-title' ) || '';
		const opts        = { legend, legendStyle, xTitle, yTitle };

		// Resolve the fetch URL.
		const src = el.getAttribute( 'data-spz-src' );
		let fetchUrl;

		if ( src ) {
			// Standalone / harness: direct JSON file.
			fetchUrl = src;
		} else if ( typeof SPZ_FRONTEND !== 'undefined' && SPZ_FRONTEND.restUrl ) {
			// WordPress: REST endpoint.
			fetchUrl = SPZ_FRONTEND.restUrl
				+ '?seccion=' + encodeURIComponent( seccion )
				+ '&view='    + encodeURIComponent( viewId )
				+ '&type='    + encodeURIComponent( type );
		} else {
			// Neither configured — skip silently.
			return;
		}

		// Show loading placeholder.
		el.innerHTML = '<div class="spz-chart__loading">' + i18n( 'loading' ) + '</div>';

		const headers = {};
		if ( typeof SPZ_FRONTEND !== 'undefined' && SPZ_FRONTEND.nonce ) {
			headers[ 'X-WP-Nonce' ] = SPZ_FRONTEND.nonce;
		}

		fetch( fetchUrl, { headers: headers, credentials: 'same-origin' } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.json();
			} )
			.then( function ( payload ) {
				if ( ! payload ) {
					el.innerHTML = '<p class="spz-empty">' + i18n( 'empty' ) + '</p>';
					return;
				}

				// Empty data guard.
				const hasData = Array.isArray( payload.data )
					? payload.data.length > 0
					: ( payload.datos || payload.municipios || payload.items || [] ).length > 0;

				if ( ! hasData ) {
					el.innerHTML = '<p class="spz-empty">' + i18n( 'empty' ) + '</p>';
					return;
				}

				el.innerHTML = '';

				if ( ! window.SPZ || ! window.SPZ.renderer ) {
					el.innerHTML = '<p class="spz-empty">' + i18n( 'error' ) + '</p>';
					return;
				}

				window.SPZ.renderer.render( el, { view: payload, type: type, options: opts } );

				// Build toolbar if data-toolbar is enabled.
				// renderer.render() is async but calls attachVerDatos() synchronously
				// before its first await, so .spz-verdatos is already in the DOM here.
				buildToolbar( el, payload );
			} )
			.catch( function ( err ) {
				console.error( '[SPZ frontend]', err );
				el.innerHTML = '<p class="spz-empty">' + i18n( 'error' ) + '</p>';
			} );
	}

	// ------------------------------------------------------------------
	// Per-module initialisation
	// ------------------------------------------------------------------

	function initModule( el ) {
		const id      = el.getAttribute( 'data-id' );
		const seccion = el.getAttribute( 'data-seccion' ) || 'dni';

		if ( ! id ) {
			return;
		}

		// Resolve the fetch URL.
		// Standalone / harness: prefer a direct data-spz-src attribute.
		// WordPress: call /render without type (module path returns raw JSON).
		const src = el.getAttribute( 'data-spz-src' );
		let fetchUrl;

		if ( src ) {
			fetchUrl = src;
		} else if ( typeof SPZ_FRONTEND !== 'undefined' && SPZ_FRONTEND.restUrl ) {
			fetchUrl = SPZ_FRONTEND.restUrl
				+ '?seccion=' + encodeURIComponent( seccion )
				+ '&view='    + encodeURIComponent( id );
		} else {
			return;
		}

		const headers = {};
		if ( typeof SPZ_FRONTEND !== 'undefined' && SPZ_FRONTEND.nonce ) {
			headers[ 'X-WP-Nonce' ] = SPZ_FRONTEND.nonce;
		}

		fetch( fetchUrl, { headers: headers, credentials: 'same-origin' } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.modulo ) {
					el.innerHTML = '<p class="spz-empty">' + i18n( 'empty' ) + '</p>';
					return;
				}

				if ( ! window.SPZ || ! window.SPZ.modules ) {
					el.innerHTML = '<p class="spz-empty">' + i18n( 'error' ) + '</p>';
					return;
				}

				window.SPZ.modules.render( el, payload );
			} )
			.catch( function ( err ) {
				console.error( '[SPZ modules]', err );
				el.innerHTML = '<p class="spz-empty">' + i18n( 'error' ) + '</p>';
			} );
	}

	// ------------------------------------------------------------------
	// Toolbar builder
	// ------------------------------------------------------------------

	/**
	 * Build and insert the action toolbar for a chart element.
	 *
	 * Reads data-toolbar and data-actions from el.
	 * Inserts .spz-toolbar as previous sibling of el.
	 * Also hides any standalone .spz-verdatos button when datos is an action.
	 *
	 * @param {Element} el      The .spz-chart container.
	 * @param {object}  rawPayload  Fetch payload (REST format preferred; seed format normalised).
	 */
	function buildToolbar( el, rawPayload ) {
		const toolbarAttr = el.getAttribute( 'data-toolbar' );
		const toolbarOn   = toolbarAttr === '1' || toolbarAttr === 'true';
		if ( ! toolbarOn ) {
			return;
		}

		const actionsAttr = el.getAttribute( 'data-actions' ) || '';
		const actions     = actionsAttr.split( ',' ).map( function ( s ) { return s.trim(); } ).filter( Boolean );
		if ( ! actions.length ) {
			return;
		}

		// Normalize payload: seed format → REST format so chart/view/compatible are available.
		let payload = rawPayload;
		if ( rawPayload && ! rawPayload.chart && window.SPZ && window.SPZ.renderer ) {
			const typeHint = el.getAttribute( 'data-type' ) || '';
			payload = window.SPZ.renderer.buildPayload( rawPayload, typeHint );
		}
		if ( ! payload ) {
			return;
		}

		const parent = el.parentNode;
		if ( ! parent ) {
			return;
		}

		// Remove any existing toolbar (e.g. after cambiar type-swap rebuild).
		const existingToolbar = parent.querySelector( '.spz-toolbar[data-spz-toolbar]' );
		if ( existingToolbar ) {
			existingToolbar.parentNode.removeChild( existingToolbar );
		}

		const toolbar = document.createElement( 'div' );
		toolbar.className = 'spz-toolbar';
		toolbar.setAttribute( 'data-spz-toolbar', '1' );
		toolbar.setAttribute( 'role', 'toolbar' );
		toolbar.setAttribute( 'aria-label', 'Acciones del gráfico' );

		const chartKey = ( payload.chart && payload.chart.key ) || '';

		actions.forEach( function ( action ) {
			// Skip imagen for native tabla (no SVG).
			if ( action === 'imagen' && chartKey === 'tabla' ) {
				return;
			}

			if ( action === 'cambiar' ) {
				const lbl = document.createElement( 'label' );
				lbl.className = 'spz-action spz-action--select';
				lbl.title = 'Cambiar tipo de gráfico';
				const icon = document.createElement( 'span' );
				icon.className = 'spz-action__label';
				icon.textContent = '⇄ Tipo'; // ⇄
				const sel = document.createElement( 'select' );
				sel.className = 'spz-action__select';
				sel.setAttribute( 'data-spz-type-selector', '1' );
				sel.setAttribute( 'aria-label', 'Tipo de gráfico' );
				lbl.appendChild( icon );
				lbl.appendChild( sel );
				toolbar.appendChild( lbl );
			} else {
				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'spz-action';
				btn.setAttribute( 'data-spz-action', action );
				const lbl = actionLabel( action );
				btn.setAttribute( 'aria-label', lbl );
				btn.title = lbl;
				btn.innerHTML = '<span class="spz-action__label">' + escHtml( lbl ) + '</span>';

				( function ( capturedAction, capturedBtn ) {
					capturedBtn.addEventListener( 'click', function () {
						runAction( el, capturedAction, payload, capturedBtn );
					} );
				} )( action, btn );

				toolbar.appendChild( btn );
			}
		} );

		// Insert toolbar immediately before the chart element.
		parent.insertBefore( toolbar, el );

		// Populate the cambiar type selector.
		populateTypeSelector( el, toolbar, payload );

		// When datos is an action, hide the standalone .spz-verdatos button
		// (already inserted by renderer.js/attachVerDatos) to avoid duplicate affordance.
		if ( actions.indexOf( 'datos' ) !== -1 ) {
			let sibling = el.nextElementSibling;
			while ( sibling ) {
				if ( sibling.classList && sibling.classList.contains( 'spz-verdatos' ) ) {
					sibling.hidden = true;
					break;
				}
				sibling = sibling.nextElementSibling;
			}
		}
	}

	// ------------------------------------------------------------------
	// Cambiar (chart-type selector)
	// ------------------------------------------------------------------

	function populateTypeSelector( el, toolbar, payload ) {
		const sel = toolbar.querySelector( '[data-spz-type-selector]' );
		if ( ! sel ) {
			return;
		}

		const compatible = Array.isArray( payload.compatible ) ? payload.compatible : [];
		if ( ! compatible.length ) {
			// Hide the cambiar control if there are no alternatives.
			const wrap = sel.closest( '.spz-action--select' );
			if ( wrap ) {
				wrap.hidden = true;
			}
			return;
		}

		const currentKey = payload.chart && payload.chart.key;
		sel.innerHTML = compatible.map( function ( c ) {
			const selAttr = c.key === currentKey ? ' selected' : '';
			return '<option value="' + escAttr( c.key ) + '"' + selAttr + '>' + escHtml( c.label ) + '</option>';
		} ).join( '' );

		// Clone to remove any prior listener, then re-attach.
		const fresh = sel.cloneNode( true );
		sel.parentNode.replaceChild( fresh, sel );
		fresh.addEventListener( 'change', function ( ev ) {
			onTypeSelectChange( el, ev.target.value, payload );
		} );
	}

	async function onTypeSelectChange( el, newType, currentPayload ) {
		const currentKey = currentPayload.chart && currentPayload.chart.key;
		if ( ! newType || newType === currentKey ) {
			return;
		}

		const seccion = el.getAttribute( 'data-seccion' ) || 'dni';
		const viewId  = el.getAttribute( 'data-view' ) || ( currentPayload.view && currentPayload.view.id ) || '';

		// Harness / standalone mode: no REST URL available — abort gracefully.
		if ( typeof SPZ_FRONTEND === 'undefined' || ! SPZ_FRONTEND.restUrl ) {
			return;
		}

		const prevHtml = el.innerHTML;
		el.classList.add( 'is-loading' );
		el.innerHTML = '<div class="spz-chart__loading">' + i18n( 'loading' ) + '</div>';

		const fetchUrl = SPZ_FRONTEND.restUrl
			+ '?seccion=' + encodeURIComponent( seccion )
			+ '&view='    + encodeURIComponent( viewId )
			+ '&type='    + encodeURIComponent( newType );

		const headers = {};
		if ( SPZ_FRONTEND.nonce ) {
			headers[ 'X-WP-Nonce' ] = SPZ_FRONTEND.nonce;
		}

		try {
			const r = await fetch( fetchUrl, { headers: headers, credentials: 'same-origin' } );
			if ( ! r.ok ) {
				throw new Error( 'HTTP ' + r.status );
			}
			const payload = await r.json();
			if ( ! payload || ! payload.data || ! payload.data.length ) {
				el.innerHTML = '<p class="spz-empty">' + i18n( 'empty' ) + '</p>';
				el.classList.remove( 'is-loading' );
				return;
			}

			el.setAttribute( 'data-type', newType );
			el.innerHTML = '';
			el.classList.remove( 'is-loading' );

			const opts = {
				legend:      el.getAttribute( 'data-legend' ) !== '0',
				legendStyle: el.getAttribute( 'data-legend-style' ) || 'text',
				xTitle:      el.getAttribute( 'data-x-title' ) || '',
				yTitle:      el.getAttribute( 'data-y-title' ) || '',
			};

			window.SPZ.renderer.render( el, { view: payload, type: newType, options: opts } );
			// Rebuild toolbar with updated payload (re-populates cambiar select).
			buildToolbar( el, payload );
		} catch ( err ) {
			console.error( '[SPZ toolbar] swap error', err );
			el.innerHTML = prevHtml;
			el.classList.remove( 'is-loading' );
		}
	}

	// ------------------------------------------------------------------
	// Action dispatcher
	// ------------------------------------------------------------------

	function runAction( el, action, payload, btn ) {
		switch ( action ) {
			case 'detalle':
				openDetalle( el, payload );
				break;
			case 'compartir':
				shareUrl( el, btn );
				break;
			case 'datos':
				openDatos( el );
				break;
			case 'imagen':
				exportImage( el, payload );
				break;
			case 'descarga':
				downloadData( el, payload );
				break;
		}
	}

	// ------------------------------------------------------------------
	// Actions
	// ------------------------------------------------------------------

	function openDetalle( el, payload ) {
		const view  = payload.view  || {};
		const chart = payload.chart || {};
		const dims  = ( view.dimensions || [] ).map( humanize ).map( escHtml ).join( ', ' ) || '—';
		const meas  = ( view.measures   || [] ).map( humanize ).map( escHtml ).join( ', ' ) || '—';
		const html  =
			'<p class="spz-modal__lede">' + escHtml( view.name || '' ) + '</p>' +
			'<dl class="spz-modal__dl">' +
				'<dt>Tipo de gráfico</dt><dd>' + escHtml( chart.label || chart.key || '' ) + '</dd>' +
				'<dt>Categoría</dt><dd>' + escHtml( view.category || '—' ) + '</dd>' +
				'<dt>Dimensiones</dt><dd>' + dims + '</dd>' +
				'<dt>Medidas</dt><dd>' + meas + '</dd>' +
				'<dt>Filas</dt><dd>' + ( ( payload.data || [] ).length ) + '</dd>' +
			'</dl>';
		showModal( el, 'Detalle del gráfico', html );
	}

	function openDatos( el ) {
		// Reuse the .spz-verdatos button inserted by renderer.js (attachVerDatos).
		// The button may be hidden (when toolbar has datos action) but .click() still fires.
		let sibling = el.nextElementSibling;
		while ( sibling ) {
			if ( sibling.classList && sibling.classList.contains( 'spz-verdatos' ) ) {
				sibling.click();
				return;
			}
			sibling = sibling.nextElementSibling;
		}
		// Fallback: toggle an existing panel directly.
		const panel = el.parentNode && el.parentNode.querySelector( '.spz-datapanel' );
		if ( panel ) {
			if ( panel.hasAttribute( 'hidden' ) ) {
				panel.removeAttribute( 'hidden' );
			} else {
				panel.setAttribute( 'hidden', '' );
			}
		}
	}

	function shareUrl( el, btn ) {
		const elId = el.id || el.getAttribute( 'data-view' ) || '';
		const url  = window.location.origin + window.location.pathname + ( elId ? '#' + elId : '' );
		const done = function () { flashButton( btn, 'URL copiada' ); };
		if ( navigator.share ) {
			navigator.share( { title: document.title, url: url } ).catch( function () {
				copyText( url ).then( done ).catch( function () { flashButton( btn, 'Error al copiar' ); } );
			} );
		} else {
			copyText( url ).then( done ).catch( function () { flashButton( btn, 'Error al copiar' ); } );
		}
	}

	function exportImage( el, payload ) {
		const svg = el.querySelector( 'svg' );
		if ( ! svg ) {
			// Native tabla has no SVG — skip gracefully.
			return;
		}
		const clone = svg.cloneNode( true );
		clone.setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
		const w = svg.clientWidth  || 800;
		const h = svg.clientHeight || 600;
		clone.setAttribute( 'width',  w );
		clone.setAttribute( 'height', h );

		const xml  = new XMLSerializer().serializeToString( clone );
		const blob = new Blob( [ xml ], { type: 'image/svg+xml;charset=utf-8' } );
		const url  = URL.createObjectURL( blob );
		const img  = new Image();

		img.onload = function () {
			const canvas = document.createElement( 'canvas' );
			canvas.width  = w * 2;
			canvas.height = h * 2;
			const ctx = canvas.getContext( '2d' );
			ctx.fillStyle = '#ffffff';
			ctx.fillRect( 0, 0, canvas.width, canvas.height );
			ctx.drawImage( img, 0, 0, canvas.width, canvas.height );
			URL.revokeObjectURL( url );
			canvas.toBlob( function ( pngBlob ) {
				if ( ! pngBlob ) {
					return;
				}
				const viewId = ( payload.view && payload.view.id ) || el.getAttribute( 'data-view' ) || 'chart';
				const type   = ( payload.chart && payload.chart.key ) || el.getAttribute( 'data-type' ) || 'chart';
				triggerDownload( pngBlob, viewId + '-' + type + '.png' );
			}, 'image/png' );
		};
		img.onerror = function () {
			URL.revokeObjectURL( url );
			const viewId = ( payload.view && payload.view.id ) || el.getAttribute( 'data-view' ) || 'chart';
			triggerDownload( blob, viewId + '.svg' );
		};
		img.src = url;
	}

	function downloadData( el, payload ) {
		const obj    = { view: payload.view || {}, data: payload.data || [] };
		const blob   = new Blob( [ JSON.stringify( obj, null, 2 ) ], { type: 'application/json;charset=utf-8' } );
		const viewId = ( payload.view && payload.view.id ) || el.getAttribute( 'data-view' ) || 'datos';
		triggerDownload( blob, viewId + '.json' );
	}

	function triggerDownload( blob, filename ) {
		const a   = document.createElement( 'a' );
		const url = URL.createObjectURL( blob );
		a.href     = url;
		a.download = filename;
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
		setTimeout( function () { URL.revokeObjectURL( url ); }, 1000 );
	}

	// ------------------------------------------------------------------
	// Modal (detalle)
	// ------------------------------------------------------------------

	function getOrCreateModal( el ) {
		const parent = el.parentNode;
		if ( ! parent ) {
			return null;
		}
		let modal = parent.querySelector( '.spz-modal[data-spz-modal]' );
		if ( modal ) {
			return modal;
		}

		const titleId = 'spz-modal-t-' + Math.random().toString( 36 ).slice( 2 );
		modal = document.createElement( 'div' );
		modal.className = 'spz-modal';
		modal.setAttribute( 'data-spz-modal', '1' );
		modal.setAttribute( 'hidden', '' );
		modal.innerHTML =
			'<div class="spz-modal__backdrop" data-spz-modal-close="1"></div>' +
			'<div class="spz-modal__panel" role="dialog" aria-modal="true" aria-labelledby="' + escHtml( titleId ) + '">' +
				'<header class="spz-modal__header">' +
					'<h3 id="' + escHtml( titleId ) + '" class="spz-modal__title"></h3>' +
					'<button type="button" class="spz-modal__close" data-spz-modal-close="1" aria-label="Cerrar">✕</button>' +
				'</header>' +
				'<div class="spz-modal__body"></div>' +
			'</div>';

		parent.appendChild( modal );

		// Wire close controls.
		modal.querySelectorAll( '[data-spz-modal-close]' ).forEach( function ( closeEl ) {
			closeEl.addEventListener( 'click', function () { closeModal( el ); } );
		} );
		document.addEventListener( 'keydown', function ( ev ) {
			if ( ( ev.key === 'Escape' || ev.key === 'Esc' ) && ! modal.hasAttribute( 'hidden' ) ) {
				closeModal( el );
			}
		} );

		return modal;
	}

	function showModal( el, title, bodyHtml ) {
		const modal = getOrCreateModal( el );
		if ( ! modal ) {
			return;
		}
		modal.querySelector( '.spz-modal__title' ).textContent = title;
		modal.querySelector( '.spz-modal__body' ).innerHTML    = bodyHtml;
		modal.removeAttribute( 'hidden' );
		modal.classList.add( 'is-open' );
	}

	function closeModal( el ) {
		const parent = el && el.parentNode;
		if ( ! parent ) {
			return;
		}
		const modal = parent.querySelector( '.spz-modal[data-spz-modal]' );
		if ( ! modal ) {
			return;
		}
		modal.setAttribute( 'hidden', '' );
		modal.classList.remove( 'is-open' );
	}

	// ------------------------------------------------------------------
	// Utilities
	// ------------------------------------------------------------------

	function flashButton( btn, text ) {
		if ( ! btn ) {
			return;
		}
		const original = btn.innerHTML;
		btn.innerHTML = '<span class="spz-action__label">' + escHtml( text ) + '</span>';
		btn.classList.add( 'is-success' );
		setTimeout( function () {
			btn.innerHTML = original;
			btn.classList.remove( 'is-success' );
		}, 1600 );
	}

	function copyText( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			try {
				const ta = document.createElement( 'textarea' );
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.left = '-9999px';
				document.body.appendChild( ta );
				ta.select();
				document.execCommand( 'copy' );
				document.body.removeChild( ta );
				resolve();
			} catch ( e ) {
				reject( e );
			}
		} );
	}

	function humanize( key ) {
		return String( key || '' )
			.replace( /[_\-]+/g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim()
			.replace( /^./, function ( c ) { return c.toUpperCase(); } );
	}

	function escHtml( s ) {
		return String( s == null ? '' : s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#39;' );
	}

	function escAttr( s ) {
		return String( s == null ? '' : s ).replace( /[^a-z0-9_\-]/gi, '' );
	}

	function actionLabel( action ) {
		const labels = {
			detalle:   'Detalle',
			compartir: 'Compartir',
			datos:     'Datos',
			imagen:    'Imagen',
			descarga:  'Descarga',
			cambiar:   'Tipo',
		};
		return labels[ action ] || action;
	}

	// ------------------------------------------------------------------
	// i18n helper
	// ------------------------------------------------------------------

	function i18n( key ) {
		const defaults = {
			loading: 'Cargando…',
			empty:   'Sin datos disponibles.',
			error:   'No fue posible cargar el gráfico.',
		};
		if ( typeof SPZ_FRONTEND !== 'undefined' && SPZ_FRONTEND.i18n && SPZ_FRONTEND.i18n[ key ] ) {
			return SPZ_FRONTEND.i18n[ key ];
		}
		return defaults[ key ] || key;
	}

	// ------------------------------------------------------------------
	// Public API (for harness and external callers)
	// ------------------------------------------------------------------

	window.SPZ = window.SPZ || {};
	window.SPZ.frontend = {
		/** Build and insert the action toolbar for a given .spz-chart element. */
		buildToolbar: buildToolbar,
		/** Expose initChart for manual invocation (e.g. dynamically added elements). */
		initChart: initChart,
	};
} )();
