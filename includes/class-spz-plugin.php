<?php
/**
 * Main plugin bootstrap.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Plugin
 *
 * Wires every sub-module of the plugin once WordPress is ready. Manages
 * one instance per sección (dni, seguridad, convivencia, estrategia,
 * transformaciones) of the PAZ project data.
 */
final class SPZ_Plugin {

	/**
	 * Sección registry — slug → human label. The first one is the default.
	 *
	 * @var array<string, string>
	 */
	public const SECCIONES = [
		'dni'              => 'Diálogo, Negociación e Implementación',
		'seguridad'        => 'Seguridad Territorial',
		'convivencia'      => 'Convivencia Ciudadana',
		'estrategia'       => 'La Estrategia 2026',
		'transformaciones' => 'Transformaciones Territoriales',
	];

	public const DEFAULT_SECCION = 'dni';

	/**
	 * Singleton instance.
	 *
	 * @var SPZ_Plugin|null
	 */
	private static ?SPZ_Plugin $instance = null;

	/**
	 * Security helper, available to all sub-modules.
	 *
	 * @var SPZ_Security
	 */
	public SPZ_Security $security;

	/**
	 * Chart-types registry (15 d3plus types).
	 *
	 * @var SPZ_Chart_Types
	 */
	public SPZ_Chart_Types $chart_types;

	/**
	 * PAZ native module registry (kpi, compare, timeline, logro).
	 *
	 * @var SPZ_Modules
	 */
	public SPZ_Modules $modules;

	/**
	 * DB override store — shared by all data providers.
	 *
	 * @var SPZ_Data_Store
	 */
	public SPZ_Data_Store $store;

	/**
	 * One data provider per sección, keyed by sección slug.
	 *
	 * @var array<string, SPZ_Data_Provider>
	 */
	private array $data_providers = [];

	/**
	 * Shortcode handler.
	 *
	 * @var SPZ_Shortcode
	 */
	public SPZ_Shortcode $shortcode;

	/**
	 * REST API controller.
	 *
	 * @var SPZ_Rest_Api
	 */
	public SPZ_Rest_Api $rest_api;

	// Tarea 8 cablea SPZ_Admin.

	/**
	 * Get the singleton.
	 */
	public static function instance(): SPZ_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor. Wires security, chart types, and one data provider
	 * per sección. Shortcode/REST/admin are added in later tasks.
	 */
	private function __construct() {
		$this->security    = new SPZ_Security();
		$this->chart_types = new SPZ_Chart_Types();
		$this->modules     = new SPZ_Modules();
		$this->store       = new SPZ_Data_Store();
		foreach ( array_keys( self::SECCIONES ) as $sec ) {
			$this->data_providers[ $sec ] = new SPZ_Data_Provider( $this->security, $sec, $this->store );
		}
		$this->shortcode = new SPZ_Shortcode( $this, $this->chart_types, $this->security, $this->modules );
		$this->rest_api  = new SPZ_Rest_Api( $this, $this->chart_types, $this->security );
	}

	/**
	 * Return the data provider for the given sección slug.
	 * Falls back to the default sección when the slug is null or unknown.
	 *
	 * @param string|null $seccion Sección slug.
	 */
	public function data_provider( ?string $seccion = null ): SPZ_Data_Provider {
		$slug = $this->normalize_seccion( $seccion );
		return $this->data_providers[ $slug ];
	}

	/**
	 * Whitelist a sección slug against SECCIONES — returns the default slug
	 * when the input is unknown or null.
	 *
	 * @param string|null $seccion Raw sección slug.
	 * @return string Validated slug.
	 */
	public function normalize_seccion( ?string $seccion ): string {
		$slug = sanitize_key( (string) ( $seccion ?? self::DEFAULT_SECCION ) );
		return isset( self::SECCIONES[ $slug ] ) ? $slug : self::DEFAULT_SECCION;
	}

	/**
	 * List every sección as [ slug => label, … ].
	 *
	 * @return array<string, string>
	 */
	public function secciones(): array {
		return self::SECCIONES;
	}

	/**
	 * Register hooks. In v0.1.0 only text-domain and asset registration
	 * are set up here; shortcode/REST/admin hooks are added in later tasks.
	 */
	public function run(): void {
		load_plugin_textdomain( 'suite-paz', false, dirname( SPZ_PLUGIN_BASENAME ) . '/languages' );

		// Shortcode [spz_grafico] + lazy asset enqueue hook.
		$this->shortcode->register();

		// REST API routes.
		add_action( 'rest_api_init', [ $this->rest_api, 'register_routes' ] );

		// Assets (scripts/styles registered here; actually enqueued by the
		// shortcode on-demand via SPZ_Shortcode::maybe_enqueue_assets).
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Tarea 8 añade: if ( is_admin() ) { $this->admin->register(); }
		// Tarea 8 añade: add_filter( 'plugin_action_links_' . SPZ_PLUGIN_BASENAME, [...] );
	}

	/**
	 * Activation hook: seed default options and ensure data directory exists.
	 */
	public static function activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		add_option(
			'spz_settings',
			[
				'allow_shortcode_roles' => [ 'administrator', 'editor' ],
				'default_theme'         => 'suite-paz',
				'enable_cache'          => true,
				'cache_ttl'             => 600,
			]
		);
		if ( ! file_exists( SPZ_DATA_DIR ) ) {
			wp_mkdir_p( SPZ_DATA_DIR );
		}
		// Create (or upgrade) the DB override table.
		( new SPZ_Data_Store() )->create_table();
	}

	/**
	 * Deactivation hook.
	 */
	public static function deactivate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		wp_cache_flush();
	}

	/**
	 * Register/enqueue public (frontend) assets.
	 * d3plus is registered but only loaded on pages with an SPZ shortcode
	 * (SPZ_Shortcode handles the conditional enqueue — Task 5).
	 */
	public function enqueue_public_assets(): void {
		// d3plus CDN bundle — full build required (peer deps embedded).
		wp_register_script(
			'spz-d3plus',
			SPZ_D3PLUS_URL,
			[],
			SPZ_D3PLUS_VERSION,
			true
		);

		// d3plus renderer (SPZ.renderer.render).
		wp_register_script(
			'spz-renderer',
			SPZ_PLUGIN_URL . 'assets/js/renderer.js',
			[ 'spz-d3plus' ],
			SPZ_VERSION,
			true
		);

		// Frontend hydrator — fetches REST payload and calls the renderer.
		wp_register_script(
			'spz-frontend',
			SPZ_PLUGIN_URL . 'assets/js/frontend.js',
			[ 'spz-d3plus', 'spz-renderer', 'spz-modules' ],
			SPZ_VERSION,
			true
		);

		// PAZ native module renderer (SPZ.modules.render).
		wp_register_script(
			'spz-modules',
			SPZ_PLUGIN_URL . 'assets/js/modules.js',
			[],
			SPZ_VERSION,
			true
		);

		// Frontend stylesheet.
		wp_register_style(
			'spz-frontend',
			SPZ_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			SPZ_VERSION
		);

		// Localize the frontend script with WordPress-side config.
		// restUrl includes /render so frontend.js can append ?seccion=&view=&type=
		// topojsonUrl injects the real WP plugin path so renderer.js resolves the map.
		wp_localize_script(
			'spz-frontend',
			'SPZ_FRONTEND',
			[
				'restUrl'     => esc_url_raw( rest_url( SPZ_REST_NAMESPACE . '/render' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'topojsonUrl' => esc_url_raw( SPZ_PLUGIN_URL . 'data/topo/narino_municipios.topojson' ),
				'pluginUrl'   => esc_url_raw( SPZ_PLUGIN_URL ),
				'i18n'        => [
					'loading' => __( 'Cargando gráfico…', 'suite-paz' ),
					'error'   => __( 'No fue posible cargar el gráfico.', 'suite-paz' ),
					'empty'   => __( 'Sin datos disponibles.', 'suite-paz' ),
				],
			]
		);
	}

	/**
	 * Enqueue admin assets only on SPZ admin screens.
	 * Full implementation in Tarea 8; stub is safe to register nothing.
	 *
	 * @param string $hook Current admin screen hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, 'suite-paz' ) === false ) {
			return;
		}
		// Tarea 8 enqueue d3plus, spz-renderer, spz-admin, spz-admin CSS.
	}
}
