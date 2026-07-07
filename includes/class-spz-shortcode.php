<?php
/**
 * Shortcode handler: [spz_grafico view="..." type="..." ...]
 *
 * The server emits only a lightweight placeholder div with data-* attrs;
 * the actual chart is rendered client-side by /assets/js/frontend.js, which
 * fetches the view payload from the REST endpoint. This keeps the HTML free of
 * inline JSON (cache-safe) and avoids exposing raw data in the page source.
 *
 * Supported attributes:
 *
 *   view     (required) — view id (see /data/views/<seccion>/*.json)
 *   type     (required) — chart key (see SPZ_Chart_Types::is_valid_type)
 *   seccion             — sección slug (whitelist), default 'dni'
 *   height              — pixel height, 160..1600, default 420
 *   title               — caption shown above the chart (optional)
 *   theme               — CSS theme class suffix, default 'suite-paz'
 *
 * Adapted from class-tsg-shortcode.php (tic-suite).
 * Changes: TSG_ → SPZ_, project → seccion, simplified to 6 core attrs,
 * removed toolbar/modal/actions (reserved for later tasks), kept security model.
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

	/**
	 * Whether we rendered at least one shortcode on this request.
	 * Used to enqueue assets lazily in wp_footer.
	 */
	private bool $needs_assets = false;

	public function __construct(
		SPZ_Plugin $plugin,
		SPZ_Chart_Types $chart_types,
		SPZ_Security $security
	) {
		$this->plugin      = $plugin;
		$this->chart_types = $chart_types;
		$this->security    = $security;
	}

	/**
	 * Register the shortcode and the footer hook for lazy asset enqueue.
	 */
	public function register(): void {
		add_shortcode( 'spz_grafico', [ $this, 'render_grafico' ] );
		add_action( 'wp_footer', [ $this, 'maybe_enqueue_assets' ] );
	}

	// -------------------------------------------------------------------------
	// Shortcode callback
	// -------------------------------------------------------------------------

	/**
	 * Render the [spz_grafico] shortcode.
	 *
	 * Returns a <div class="spz-chart" data-*> placeholder; the renderer
	 * (frontend.js) hydrates it after fetching /suite-paz/v1/render via REST.
	 * No view data is ever embedded in the HTML output.
	 *
	 * @param array|string $atts    Shortcode attributes (WP passes '' when none provided).
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

		// --- Sanitize all attributes ---
		$view_id    = $this->security->sanitize_slug( (string) $atts['view'] );
		$type_raw   = sanitize_key( (string) $atts['type'] );
		$chart_type = $this->chart_types->is_valid_type( $type_raw ) ? $type_raw : '';
		$seccion    = $this->plugin->normalize_seccion( (string) $atts['seccion'] );
		$height     = max( 160, min( 1600, absint( $atts['height'] ) ) );
		$title      = sanitize_text_field( (string) $atts['title'] );
		$theme      = sanitize_html_class( (string) $atts['theme'], 'suite-paz' );

		// Both view and type are required.
		if ( '' === $view_id || '' === $chart_type ) {
			return sprintf(
				'<div class="spz-empty" role="note">%s</div>',
				esc_html__( 'Shortcode inválido: se requieren los atributos "view" y "type".', 'suite-paz' )
			);
		}

		// Flag assets needed; they will be enqueued in wp_footer.
		$this->needs_assets = true;

		// Build the chart container (no inline data).
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

		// Optionally wrap with a figure+caption when a title is provided.
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
		wp_enqueue_script( 'spz-frontend' );
		wp_enqueue_style( 'spz-frontend' );
	}
}
