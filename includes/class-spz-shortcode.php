<?php
/**
 * Shortcode handler.
 *
 * Registered shortcodes:
 *   [spz_grafico view type seccion height title theme]
 *   [spz_kpi      id seccion]
 *   [spz_compare  id seccion]
 *   [spz_timeline id seccion]
 *   [spz_logro    id seccion]
 *   [spz_seccion  id]
 *
 * Chart shortcode ([spz_grafico]):
 *   The server emits only a lightweight placeholder div with data-* attrs;
 *   the actual chart is rendered client-side by assets/js/frontend.js, which
 *   fetches the view payload from the REST endpoint. This keeps the HTML free of
 *   inline JSON (cache-safe) and avoids exposing raw data in the page source.
 *
 * Module shortcodes ([spz_kpi], [spz_compare], [spz_timeline], [spz_logro]):
 *   Each emits <div class="spz-module" data-modulo data-id data-seccion>.
 *   frontend.js finds these, fetches the payload from REST /render, and calls
 *   SPZ.modules.render(el, payload). Each verifies the target view is actually
 *   a module of the declared type.
 *
 * Section shortcode ([spz_seccion]):
 *   Lists all views / modules in the sección in order (modules first, then
 *   chart views). Useful for building a full section page with a single tag.
 *
 * Adapted from class-tsg-shortcode.php (tic-suite).
 * Changes: TSG_ → SPZ_, project → seccion, added module shortcodes.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Shortcode
 */
class SPZ_Shortcode {

	private SPZ_Plugin $plugin;
	private SPZ_Chart_Types $chart_types;
	private SPZ_Security $security;
	private SPZ_Modules $modules;

	/**
	 * Whether we rendered at least one shortcode on this request.
	 * Used to enqueue assets lazily in wp_footer.
	 */
	private bool $needs_assets = false;

	public function __construct(
		SPZ_Plugin $plugin,
		SPZ_Chart_Types $chart_types,
		SPZ_Security $security,
		SPZ_Modules $modules
	) {
		$this->plugin      = $plugin;
		$this->chart_types = $chart_types;
		$this->security    = $security;
		$this->modules     = $modules;
	}

	/**
	 * Register all shortcodes and the footer hook for lazy asset enqueue.
	 */
	public function register(): void {
		add_shortcode( 'spz_grafico',  [ $this, 'render_grafico' ] );
		add_shortcode( 'spz_kpi',      [ $this, 'render_kpi' ] );
		add_shortcode( 'spz_compare',  [ $this, 'render_compare' ] );
		add_shortcode( 'spz_timeline', [ $this, 'render_timeline' ] );
		add_shortcode( 'spz_logro',    [ $this, 'render_logro' ] );
		add_shortcode( 'spz_seccion',  [ $this, 'render_seccion' ] );
		add_action( 'wp_footer', [ $this, 'maybe_enqueue_assets' ] );
	}

	// -------------------------------------------------------------------------
	// Chart shortcode
	// -------------------------------------------------------------------------

	/**
	 * Render the [spz_grafico] shortcode.
	 *
	 * Returns a <div class="spz-chart" data-*> placeholder; the renderer
	 * (frontend.js) hydrates it after fetching /suite-paz/v1/render via REST.
	 * No view data is ever embedded in the HTML output.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Inner content (ignored).
	 * @return string HTML output.
	 */
	public function render_grafico( $atts, $content = null ): string {
		$atts = shortcode_atts(
			[
				'view'    => '',
				'type'    => '',
				'seccion' => SPZ_Plugin::DEFAULT_SECCION,
				'height'  => '420',
				'title'   => '',
				'theme'   => 'suite-paz',
			],
			is_array( $atts ) ? $atts : [],
			'spz_grafico'
		);

		$view_id    = $this->security->sanitize_slug( (string) $atts['view'] );
		$type_raw   = sanitize_key( (string) $atts['type'] );
		$chart_type = $this->chart_types->is_valid_type( $type_raw ) ? $type_raw : '';
		$seccion    = $this->plugin->normalize_seccion( (string) $atts['seccion'] );
		$height     = max( 160, min( 1600, absint( $atts['height'] ) ) );
		$title      = sanitize_text_field( (string) $atts['title'] );
		$theme      = sanitize_html_class( (string) $atts['theme'], 'suite-paz' );

		if ( '' === $view_id || '' === $chart_type ) {
			return sprintf(
				'<div class="spz-empty" role="note">%s</div>',
				esc_html__( 'Shortcode inválido: se requieren los atributos "view" y "type".', 'suite-paz' )
			);
		}

		$this->needs_assets = true;

		$chart_div = sprintf(
			'<div class="spz-chart" data-view="%s" data-type="%s" data-seccion="%s" data-height="%d" style="min-height:%dpx;" aria-label="%s" role="img"><div class="spz-chart__loading">%s</div></div>',
			esc_attr( $view_id ),
			esc_attr( $chart_type ),
			esc_attr( $seccion ),
			$height,
			$height,
			esc_attr( $title ?: __( 'Gráfico Suite PAZ', 'suite-paz' ) ),
			esc_html__( 'Cargando…', 'suite-paz' )
		);

		if ( '' !== $title ) {
			return sprintf(
				'<figure class="spz-figure spz-theme-%s"><figcaption class="spz-figure__title">%s</figcaption>%s</figure>',
				esc_attr( $theme ),
				esc_html( $title ),
				$chart_div
			);
		}

		return $chart_div;
	}

	// -------------------------------------------------------------------------
	// Module shortcodes
	// -------------------------------------------------------------------------

	/**
	 * Emit a generic module placeholder after validating id + seccion + type.
	 *
	 * @param array|string $atts       Raw shortcode attributes.
	 * @param string       $modulo_key Expected module type (kpi|compare|timeline|logro).
	 * @param string       $tag        Shortcode tag name (for error messages).
	 * @return string HTML placeholder or error div.
	 */
	private function render_module( $atts, string $modulo_key, string $tag ): string {
		$atts = shortcode_atts(
			[
				'id'      => '',
				'seccion' => SPZ_Plugin::DEFAULT_SECCION,
			],
			is_array( $atts ) ? $atts : [],
			$tag
		);

		$id      = $this->security->sanitize_slug( (string) $atts['id'] );
		$seccion = $this->plugin->normalize_seccion( (string) $atts['seccion'] );

		if ( '' === $id ) {
			return sprintf(
				'<div class="spz-empty" role="note">%s</div>',
				esc_html__( 'Shortcode inválido: se requiere el atributo "id".', 'suite-paz' )
			);
		}

		// Verify the view exists and is the declared module type.
		$view = $this->plugin->data_provider( $seccion )->get_view( $id );
		if ( empty( $view ) || empty( $view['is_module'] ) || ( $view['modulo'] ?? '' ) !== $modulo_key ) {
			return sprintf(
				'<div class="spz-empty" role="note">%s</div>',
				esc_html(
					sprintf(
						/* translators: 1: view id, 2: module type */
						__( 'Módulo "%1$s" no encontrado o no es del tipo "%2$s".', 'suite-paz' ),
						$id,
						$modulo_key
					)
				)
			);
		}

		$this->needs_assets = true;

		return sprintf(
			'<div class="spz-module" data-modulo="%s" data-id="%s" data-seccion="%s" role="region" aria-label="%s"><div class="spz-chart__loading">%s</div></div>',
			esc_attr( $modulo_key ),
			esc_attr( $id ),
			esc_attr( $seccion ),
			esc_attr( sanitize_text_field( (string) ( $view['titulo'] ?? $id ) ) ),
			esc_html__( 'Cargando…', 'suite-paz' )
		);
	}

	/**
	 * [spz_kpi id seccion] — KPI count-up card.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_kpi( $atts ): string {
		return $this->render_module( $atts, 'kpi', 'spz_kpi' );
	}

	/**
	 * [spz_compare id seccion] — before→after comparison card.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_compare( $atts ): string {
		return $this->render_module( $atts, 'compare', 'spz_compare' );
	}

	/**
	 * [spz_timeline id seccion] — events timeline.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_timeline( $atts ): string {
		return $this->render_module( $atts, 'timeline', 'spz_timeline' );
	}

	/**
	 * [spz_logro id seccion] — achievement card.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_logro( $atts ): string {
		return $this->render_module( $atts, 'logro', 'spz_logro' );
	}

	// -------------------------------------------------------------------------
	// Section shortcode
	// -------------------------------------------------------------------------

	/**
	 * [spz_seccion id] — render all modules and views in a sección in order.
	 *
	 * Modules are emitted as .spz-module placeholders (hydrated by modules.js).
	 * Chart views are emitted as .spz-chart placeholders (hydrated by renderer.js)
	 * using the first compatible chart type. Views with no compatible type (e.g.
	 * "strategy"/"radial" tipo_grafico_sugerido) are skipped with a data-only note.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_seccion( $atts ): string {
		$atts = shortcode_atts(
			[ 'id' => SPZ_Plugin::DEFAULT_SECCION ],
			is_array( $atts ) ? $atts : [],
			'spz_seccion'
		);

		$seccion = $this->plugin->normalize_seccion( (string) $atts['id'] );
		$dp      = $this->plugin->data_provider( $seccion );
		$items   = $dp->list_views();

		if ( empty( $items ) ) {
			return sprintf(
				'<div class="spz-empty" role="note">%s</div>',
				esc_html__( 'Esta sección no tiene vistas disponibles.', 'suite-paz' )
			);
		}

		$this->needs_assets = true;
		$out = '<div class="spz-seccion" data-seccion="' . esc_attr( $seccion ) . '">';

		foreach ( $items as $item ) {
			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );
			if ( '' === $id ) {
				continue;
			}

			if ( ! empty( $item['is_module'] ) ) {
				// Native module placeholder.
				$modulo = sanitize_key( (string) ( $item['modulo'] ?? '' ) );
				if ( $this->modules->is_valid( $modulo ) ) {
					$out .= sprintf(
						'<div class="spz-module" data-modulo="%s" data-id="%s" data-seccion="%s" role="region" aria-label="%s"><div class="spz-chart__loading">%s</div></div>',
						esc_attr( $modulo ),
						esc_attr( $id ),
						esc_attr( $seccion ),
						esc_attr( sanitize_text_field( (string) ( $item['name'] ?? $id ) ) ),
						esc_html__( 'Cargando…', 'suite-paz' )
					);
				}
			} else {
				// Chart view — pick first compatible type.
				$view       = $dp->get_view( $id );
				$compatible = $view ? $this->chart_types->compatible_for( $view ) : [];
				if ( empty( $compatible ) ) {
					// No compatible chart type (e.g. strategy/radial views).
					continue;
				}
				$first_type = sanitize_key( (string) ( $compatible[0]['key'] ?? '' ) );
				if ( '' === $first_type ) {
					continue;
				}
				$out .= sprintf(
					'<div class="spz-chart" data-view="%s" data-type="%s" data-seccion="%s" style="min-height:420px;" role="img" aria-label="%s"><div class="spz-chart__loading">%s</div></div>',
					esc_attr( $id ),
					esc_attr( $first_type ),
					esc_attr( $seccion ),
					esc_attr( sanitize_text_field( (string) ( $item['name'] ?? $id ) ) ),
					esc_html__( 'Cargando…', 'suite-paz' )
				);
			}
		}

		$out .= '</div>';
		return $out;
	}

	// -------------------------------------------------------------------------
	// Lazy asset enqueue
	// -------------------------------------------------------------------------

	/**
	 * Enqueue frontend assets only when at least one shortcode was rendered.
	 * Called on wp_footer. Scripts/styles were registered in
	 * SPZ_Plugin::enqueue_public_assets(); this call just marks them for output.
	 */
	public function maybe_enqueue_assets(): void {
		if ( ! $this->needs_assets ) {
			return;
		}
		wp_enqueue_script( 'spz-modules' );
		wp_enqueue_script( 'spz-frontend' );
		wp_enqueue_style( 'spz-frontend' );
	}
}
