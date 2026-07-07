/* global SPZ_FRONTEND, SPZ */
/**
 * Suite PAZ · Frontend hydrator.
 *
 * For every .spz-chart[data-view][data-type][data-seccion] on the page:
 *  1. Fetch the render payload from the REST endpoint (WP) or a JSON URL (harness).
 *  2. Call SPZ.renderer.render( el, { view: payload, type, options } ).
 *
 * WordPress mode:
 *   Reads window.SPZ_FRONTEND = { restUrl, nonce, i18n: { empty, error, loading } }
 *   (provided by wp_localize_script in class-spz-shortcode.php, Task 5).
 *   REST URL: /wp-json/suite-paz/v1/render?seccion=…&view=…&type=…
 *
 * Harness / standalone mode (no WP):
 *   If a container has a [data-spz-src] attribute pointing to a JSON file,
 *   the hydrator fetches that file directly and passes it to the renderer.
 *   If neither SPZ_FRONTEND nor data-spz-src is present the container is skipped.
 *
 * Adapted from tic-suite assets/js/frontend.js.
 * Namespace: TSG → SPZ.  REST namespace: tic-suite/v1 → suite-paz/v1.
 * Simplified: no toolbar/modal/type-selector (those are Task 5 / 6).
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
} )();
