<?php
/**
 * Admin screen: chart builder with sección switcher.
 *
 * @package SuitePaz
 * @var array<array<string,mixed>> $views     View summaries for the current sección.
 * @var array<string,string>       $secciones All secciones (slug → label).
 * @var string                     $seccion   Current sección slug.
 * @var string                     $label     Human label for the current sección.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap spz-wrap" id="spz-builder-wrap" data-spz-seccion="<?php echo esc_attr( $seccion ); ?>">
	<header class="spz-header">
		<div class="spz-header__title">
			<span class="dashicons dashicons-chart-area" aria-hidden="true"></span>
			<h1>
				<?php esc_html_e( 'Suite PAZ', 'suite-paz' ); ?>
				<span class="spz-header__sub">· <?php esc_html_e( 'Constructor', 'suite-paz' ); ?></span>
			</h1>
		</div>
		<p class="spz-header__lede">
			<?php esc_html_e( 'Selecciona una sección, elige una vista y un tipo de gráfico compatible, ajusta las opciones y obtén el shortcode para insertarlo en cualquier página.', 'suite-paz' ); ?>
		</p>
	</header>

	<!-- Sección switcher -->
	<div class="spz-form-inline spz-seccion-bar">
		<label for="spz-seccion-select">
			<span class="dashicons dashicons-category" aria-hidden="true"></span>
			<?php esc_html_e( 'Sección:', 'suite-paz' ); ?>
		</label>
		<select id="spz-seccion-select" class="spz-select" data-spz-builder-seccion>
			<?php foreach ( $secciones as $slug => $name ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $seccion, $slug ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<span class="spz-seccion-bar__label"><?php echo esc_html( $label ); ?></span>
	</div>

	<section class="spz-grid">
		<!-- Panel 1: view picker -->
		<aside class="spz-card spz-card--views" aria-labelledby="spz-views-heading">
			<h2 id="spz-views-heading" class="spz-card__title">
				<span class="dashicons dashicons-database" aria-hidden="true"></span>
				<?php esc_html_e( 'Vistas', 'suite-paz' ); ?>
			</h2>
			<ul class="spz-views-list" role="listbox" aria-label="<?php esc_attr_e( 'Vistas disponibles', 'suite-paz' ); ?>">
				<?php foreach ( $views as $v ) : ?>
					<?php if ( ! empty( $v['is_module'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li>
						<button
							type="button"
							class="spz-view-item"
							data-view-id="<?php echo esc_attr( $v['id'] ); ?>"
							role="option"
							aria-selected="false"
						>
							<span class="spz-view-item__name"><?php echo esc_html( $v['name'] ); ?></span>
							<span class="spz-view-item__meta">
								<span class="spz-pill spz-pill--<?php echo esc_attr( $v['category'] ?? 'categorical' ); ?>">
									<?php echo esc_html( $v['category'] ?? '' ); ?>
								</span>
								<span class="spz-view-item__rows"><?php echo absint( $v['rows'] ?? 0 ); ?> <?php esc_html_e( 'filas', 'suite-paz' ); ?></span>
							</span>
							<span class="spz-view-item__desc"><?php echo esc_html( $v['description'] ?? '' ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>

		<!-- Panel 2: chart type picker -->
		<section class="spz-card spz-card--types" aria-labelledby="spz-types-heading">
			<h2 id="spz-types-heading" class="spz-card__title">
				<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
				<?php esc_html_e( 'Tipo de gráfico', 'suite-paz' ); ?>
			</h2>
			<div id="spz-chart-types" class="spz-chart-types" data-empty="<?php esc_attr_e( 'Selecciona primero una vista.', 'suite-paz' ); ?>">
				<p class="spz-empty"><?php esc_html_e( 'Selecciona primero una vista.', 'suite-paz' ); ?></p>
			</div>
		</section>

		<!-- Panel 3: preview + options + shortcode -->
		<section class="spz-card spz-card--preview" aria-labelledby="spz-preview-heading">
			<h2 id="spz-preview-heading" class="spz-card__title">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<?php esc_html_e( 'Vista previa', 'suite-paz' ); ?>
			</h2>

			<fieldset class="spz-options" id="spz-options">
				<legend class="spz-options__legend"><?php esc_html_e( 'Opciones', 'suite-paz' ); ?></legend>

				<div class="spz-options__row">
					<label class="spz-switch">
						<input type="checkbox" data-spz-opt="legend" checked />
						<span class="spz-switch__track"></span>
						<span class="spz-switch__label"><?php esc_html_e( 'Mostrar leyenda', 'suite-paz' ); ?></span>
					</label>

					<label class="spz-select-inline">
						<span><?php esc_html_e( 'Estilo:', 'suite-paz' ); ?></span>
						<select data-spz-opt="legend_style">
							<option value="text"><?php esc_html_e( 'Texto', 'suite-paz' ); ?></option>
							<option value="icons"><?php esc_html_e( 'Iconos', 'suite-paz' ); ?></option>
						</select>
					</label>
				</div>

				<div class="spz-options__row">
					<label class="spz-switch">
						<input type="checkbox" data-spz-opt="toolbar" checked />
						<span class="spz-switch__track"></span>
						<span class="spz-switch__label"><?php esc_html_e( 'Barra de acciones', 'suite-paz' ); ?></span>
					</label>
				</div>

				<div class="spz-options__row spz-options__axes">
					<label class="spz-text-inline">
						<span><?php esc_html_e( 'Eje X:', 'suite-paz' ); ?></span>
						<input type="text" data-spz-opt="x_title" placeholder="<?php esc_attr_e( 'auto', 'suite-paz' ); ?>" />
					</label>
					<label class="spz-text-inline">
						<span><?php esc_html_e( 'Eje Y:', 'suite-paz' ); ?></span>
						<input type="text" data-spz-opt="y_title" placeholder="<?php esc_attr_e( 'auto', 'suite-paz' ); ?>" />
					</label>
				</div>

				<div class="spz-options__row spz-options__actions">
					<span class="spz-options__label"><?php esc_html_e( 'Acciones:', 'suite-paz' ); ?></span>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="detalle" checked /><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><?php esc_html_e( 'Detalle', 'suite-paz' ); ?></label>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="compartir" checked /><span class="dashicons dashicons-share" aria-hidden="true"></span><?php esc_html_e( 'Compartir', 'suite-paz' ); ?></label>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="datos" checked /><span class="dashicons dashicons-editor-table" aria-hidden="true"></span><?php esc_html_e( 'Datos', 'suite-paz' ); ?></label>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="imagen" checked /><span class="dashicons dashicons-format-image" aria-hidden="true"></span><?php esc_html_e( 'Imagen', 'suite-paz' ); ?></label>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="descarga" checked /><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Descarga', 'suite-paz' ); ?></label>
					<label class="spz-chip"><input type="checkbox" data-spz-action-opt="cambiar" checked /><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e( 'Cambiar tipo', 'suite-paz' ); ?></label>
				</div>
			</fieldset>

			<div id="spz-preview" class="spz-preview" aria-live="polite">
				<p class="spz-empty"><?php esc_html_e( 'Aquí aparecerá tu gráfico.', 'suite-paz' ); ?></p>
			</div>

			<div class="spz-shortcode-box" hidden>
				<label for="spz-shortcode-input">
					<span class="dashicons dashicons-shortcode" aria-hidden="true"></span>
					<?php esc_html_e( 'Shortcode', 'suite-paz' ); ?>
				</label>
				<div class="spz-shortcode-row">
					<input type="text" id="spz-shortcode-input" readonly value="" />
					<button type="button" class="button button-primary" id="spz-copy-btn">
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
						<?php esc_html_e( 'Copiar', 'suite-paz' ); ?>
					</button>
				</div>
				<p class="spz-hint"><?php esc_html_e( 'Pégalo en cualquier página o entrada de WordPress.', 'suite-paz' ); ?></p>
			</div>

			<div class="spz-shortcode-box" id="spz-analisis-box" hidden>
				<label for="spz-analisis-input">
					<span class="dashicons dashicons-editor-quote" aria-hidden="true"></span>
					<?php esc_html_e( 'Análisis ciudadano', 'suite-paz' ); ?>
				</label>
				<div class="spz-shortcode-row">
					<input type="text" id="spz-analisis-input" readonly value="" />
					<button type="button" class="button" id="spz-copy-analisis-btn">
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
						<?php esc_html_e( 'Copiar', 'suite-paz' ); ?>
					</button>
				</div>
				<p class="spz-hint"><?php esc_html_e( 'Muestra el párrafo de análisis ciudadano del elemento.', 'suite-paz' ); ?></p>
			</div>
			<script>
			/* Sync [spz_analisis] shortcode whenever admin.js updates the main shortcode input. */
			( function () {
				'use strict';
				document.addEventListener( 'DOMContentLoaded', function () {
					var mainBox     = document.querySelector( '.spz-shortcode-box' );
					var mainInput   = document.getElementById( 'spz-shortcode-input' );
					var analisisBox = document.getElementById( 'spz-analisis-box' );
					var analisisIn  = document.getElementById( 'spz-analisis-input' );
					var copyBtn     = document.getElementById( 'spz-copy-analisis-btn' );
					if ( ! mainInput || ! analisisBox || ! analisisIn ) { return; }

					/* Intercept programmatic .value = ... assignments from admin.js. */
					var proto = Object.getOwnPropertyDescriptor( HTMLInputElement.prototype, 'value' );
					Object.defineProperty( mainInput, 'value', {
						get: proto.get,
						set: function ( val ) {
							proto.set.call( this, val );
							syncAnalisis( val );
						},
						configurable: true,
					} );

					/* Hide the analysis box whenever the main shortcode box is hidden. */
					if ( mainBox ) {
						new MutationObserver( function ( muts ) {
							muts.forEach( function ( m ) {
								if ( m.attributeName === 'hidden' ) {
									if ( mainBox.hidden ) {
										analisisBox.hidden = true;
									} else {
										syncAnalisis( mainInput.value );
									}
								}
							} );
						} ).observe( mainBox, { attributes: true } );
					}

					function syncAnalisis( sc ) {
						if ( ! sc ) { analisisBox.hidden = true; return; }
						var viewMatch    = sc.match( /view="([^"]+)"/ );
						var idMatch      = sc.match( /\bid="([^"]+)"/ );
						var seccionMatch = sc.match( /seccion="([^"]+)"/ );
						var id      = viewMatch ? viewMatch[ 1 ] : ( idMatch ? idMatch[ 1 ] : '' );
						var seccion = seccionMatch ? seccionMatch[ 1 ] : '';
						if ( ! id ) { analisisBox.hidden = true; return; }
						analisisIn.value = '[spz_analisis id="' + id + '"' +
							( seccion ? ' seccion="' + seccion + '"' : '' ) + ']';
						analisisBox.hidden = false;
					}

					if ( copyBtn ) {
						copyBtn.addEventListener( 'click', function () {
							var text = analisisIn.value;
							if ( ! text ) { return; }
							if ( navigator.clipboard && navigator.clipboard.writeText ) {
								navigator.clipboard.writeText( text ).then( onCopied );
							} else {
								var ta = document.createElement( 'textarea' );
								ta.value = text;
								ta.style.cssText = 'position:absolute;left:-9999px;top:0';
								document.body.appendChild( ta );
								ta.select();
								try { document.execCommand( 'copy' ); onCopied(); } catch ( e ) { /* silent */ }
								document.body.removeChild( ta );
							}
						} );
					}

					function onCopied() {
						if ( ! copyBtn ) { return; }
						var orig = copyBtn.innerHTML;
						copyBtn.innerHTML =
							'<span class="dashicons dashicons-yes" aria-hidden="true"></span> <?php echo esc_js( esc_html__( 'Copiado', 'suite-paz' ) ); ?>';
						copyBtn.classList.add( 'is-success' );
						setTimeout( function () {
							copyBtn.innerHTML = orig;
							copyBtn.classList.remove( 'is-success' );
						}, 1800 );
					}
				} );
			}() );
			</script>
		</section>
	</section>
</div>
