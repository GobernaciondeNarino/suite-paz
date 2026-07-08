<?php
/**
 * Admin menus and screen renderers.
 *
 * Top-level menu: "Suite PAZ" (slug: suite-paz).
 * Submenus:
 *   - Constructor   → suite-paz          (top-level landing)
 *   - Shortcodes    → suite-paz-shortcodes
 *   - Editar datos  → suite-paz-editor
 *   - Ajustes       → suite-paz-settings
 *
 * Security model (identical to tic-suite TSG_Admin):
 *  - Every screen callback calls guard() which checks current_user_can('manage_options').
 *  - All template echos use esc_html()/esc_attr()/esc_url().
 *  - Editor save/reset use nonces (verified by SPZ_Security::verify_nonce() at the
 *    REST layer; the nonce is stored in SPZ_ADMIN.nonce via wp_localize_script).
 *
 * Adapted from includes/class-tsg-admin.php (tic-suite).
 * Changes: TSG_ → SPZ_, project → seccion, separate submenus → single builder with
 * JS-driven sección switcher, "Datos de vista" (read-only) → "Editar datos" (editable),
 * added Settings screen.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Admin
 */
class SPZ_Admin {

	private SPZ_Plugin $plugin;
	private SPZ_Chart_Types $chart_types;
	private SPZ_Security $security;

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
	 * Register admin hooks. Called from SPZ_Plugin::run() when is_admin().
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register the top-level menu and all submenus.
	 */
	public function register_menu(): void {
		// Top-level menu — landing page is the builder.
		add_menu_page(
			__( 'Suite PAZ', 'suite-paz' ),
			__( 'Suite PAZ', 'suite-paz' ),
			'manage_options',
			'suite-paz',
			[ $this, 'render_builder' ],
			'dashicons-chart-area',
			58
		);

		// First submenu replaces the auto-duplicate WordPress creates.
		add_submenu_page(
			'suite-paz',
			__( 'Constructor', 'suite-paz' ),
			__( 'Constructor', 'suite-paz' ),
			'manage_options',
			'suite-paz',
			[ $this, 'render_builder' ]
		);

		add_submenu_page(
			'suite-paz',
			__( 'Shortcodes', 'suite-paz' ),
			__( 'Shortcodes', 'suite-paz' ),
			'manage_options',
			'suite-paz-shortcodes',
			[ $this, 'render_shortcodes' ]
		);

		add_submenu_page(
			'suite-paz',
			__( 'Editar datos', 'suite-paz' ),
			__( 'Editar datos', 'suite-paz' ),
			'manage_options',
			'suite-paz-editor',
			[ $this, 'render_editor' ]
		);

		add_submenu_page(
			'suite-paz',
			__( 'Ajustes', 'suite-paz' ),
			__( 'Ajustes', 'suite-paz' ),
			'manage_options',
			'suite-paz-settings',
			[ $this, 'render_settings' ]
		);
	}

	// ------------------------------------------------------------------
	// Shared security guard.
	// ------------------------------------------------------------------

	/**
	 * Shared capability guard for every admin screen callback.
	 * Dies with a translated message when the current user lacks manage_options.
	 */
	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'suite-paz' ) );
		}
	}

	// ------------------------------------------------------------------
	// Screen callbacks.
	// ------------------------------------------------------------------

	/**
	 * Constructor screen — 3-panel builder (sección picker, chart types, preview).
	 */
	public function render_builder(): void {
		$this->guard();
		$seccion  = isset( $_GET['seccion'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? $this->plugin->normalize_seccion( sanitize_key( wp_unslash( $_GET['seccion'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: SPZ_Plugin::DEFAULT_SECCION;
		$dp       = $this->plugin->data_provider( $seccion );
		$views    = $dp->list_views();
		$secciones = $this->plugin->secciones();
		$label    = $secciones[ $seccion ];
		include SPZ_PLUGIN_DIR . 'templates/admin/builder.php';
	}

	/**
	 * Shortcodes gallery screen — all views/modules per sección with copyable shortcodes.
	 */
	public function render_shortcodes(): void {
		$this->guard();
		$seccion  = isset( $_GET['seccion'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? $this->plugin->normalize_seccion( sanitize_key( wp_unslash( $_GET['seccion'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: SPZ_Plugin::DEFAULT_SECCION;
		$dp        = $this->plugin->data_provider( $seccion );
		$views     = $dp->list_views();
		$secciones = $this->plugin->secciones();
		$label     = $secciones[ $seccion ];
		include SPZ_PLUGIN_DIR . 'templates/admin/shortcodes.php';
	}

	/**
	 * Data editor screen — editable form for any view/module via REST.
	 */
	public function render_editor(): void {
		$this->guard();
		$seccion   = isset( $_GET['seccion'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? $this->plugin->normalize_seccion( sanitize_key( wp_unslash( $_GET['seccion'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: SPZ_Plugin::DEFAULT_SECCION;
		$secciones = $this->plugin->secciones();
		$label     = $secciones[ $seccion ];
		include SPZ_PLUGIN_DIR . 'templates/admin/data-editor.php';
	}

	/**
	 * Settings screen — plugin configuration options.
	 */
	public function render_settings(): void {
		$this->guard();

		// Handle settings form save.
		if (
			isset( $_POST['spz_settings_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spz_settings_nonce'] ) ), 'spz_save_settings' )
		) {
			// Sanitize palette: split on comma/newline, validate hex, dedupe.
			$palette_raw  = sanitize_textarea_field( wp_unslash( $_POST['palette'] ?? '' ) );
			$palette_parts = preg_split( '/[\r\n,]+/', $palette_raw, -1, PREG_SPLIT_NO_EMPTY );
			$palette_clean = [];
			$palette_seen  = [];
			foreach ( $palette_parts as $color ) {
				$color = trim( $color );
				// Expand 3-digit hex to 6-digit.
				if ( preg_match( '/^#([0-9a-fA-F]{3})$/', $color, $m ) ) {
					$c     = $m[1];
					$color = '#' . $c[0] . $c[0] . $c[1] . $c[1] . $c[2] . $c[2];
				}
				if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
					continue;
				}
				$color_lc = strtolower( $color );
				if ( isset( $palette_seen[ $color_lc ] ) ) {
					continue;
				}
				$palette_seen[ $color_lc ] = true;
				$palette_clean[]            = $color_lc;
			}
			// If result is empty, fall back to the 24 defaults.
			if ( empty( $palette_clean ) ) {
				$palette_clean = SPZ_DEFAULT_PALETTE;
			}

			$tl_raw = sanitize_key( (string) ( $_POST['timeline_default'] ?? 'auto' ) );
			$saved = [
				'allow_shortcode_roles' => array_map( 'sanitize_key', (array) ( $_POST['allow_shortcode_roles'] ?? [] ) ),
				'default_theme'         => sanitize_key( (string) ( $_POST['default_theme'] ?? 'suite-paz' ) ),
				'enable_cache'          => ! empty( $_POST['enable_cache'] ),
				'cache_ttl'             => absint( $_POST['cache_ttl'] ?? 600 ),
				'palette'               => $palette_clean,
				'timeline_default'      => in_array( $tl_raw, [ 'auto', 'on', 'off' ], true ) ? $tl_raw : 'auto',
			];
			update_option( 'spz_settings', $saved );
			add_settings_error( 'spz_settings', 'saved', __( 'Ajustes guardados.', 'suite-paz' ), 'success' );
		}

		$settings = wp_parse_args(
			(array) get_option( 'spz_settings', [] ),
			[
				'allow_shortcode_roles' => [ 'administrator', 'editor' ],
				'default_theme'         => 'suite-paz',
				'enable_cache'          => true,
				'cache_ttl'             => 600,
				'palette'               => SPZ_DEFAULT_PALETTE,
				'timeline_default'      => 'auto',
			]
		);

		include SPZ_PLUGIN_DIR . 'templates/admin/settings.php';
	}
}
