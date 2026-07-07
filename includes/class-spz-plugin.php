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
	 * One data provider per sección, keyed by sección slug.
	 *
	 * @var array<string, SPZ_Data_Provider>
	 */
	private array $data_providers = [];

	// Tarea 6 cablea SPZ_Shortcode.
	// Tarea 8 cablea SPZ_Admin y SPZ_Rest_Api.

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
		foreach ( array_keys( self::SECCIONES ) as $sec ) {
			$this->data_providers[ $sec ] = new SPZ_Data_Provider( $this->security, $sec );
		}
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

		// Assets (registered now; enqueued conditionally by shortcode/admin — Tarea 6/8).
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Tarea 6 añade: $this->shortcode->register();
		// Tarea 8 añade: if ( is_admin() ) { $this->admin->register(); }
		// Tarea 8 añade: add_action( 'rest_api_init', [ $this->rest_api, 'register_routes' ] );
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
		// Tarea 7 crea la tabla wp_spz_views aquí.
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
	 * (SPZ_Shortcode handles the conditional enqueue — Tarea 6).
	 */
	public function enqueue_public_assets(): void {
		wp_register_script(
			'd3plus',
			SPZ_D3PLUS_URL,
			[],
			SPZ_D3PLUS_VERSION,
			true
		);

		// Tarea 6 registra spz-renderer y spz-frontend aquí.
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
