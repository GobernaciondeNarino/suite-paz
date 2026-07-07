<?php
/**
 * Plugin Name:       Suite PAZ
 * Plugin URI:        https://github.com/GobernaciondeNarino/suite-paz
 * Description:       Publica los datos del proyecto de Paz de Nariño como gráficos, mapas y módulos mediante shortcodes, con editor de datos en el panel.
 * Version:           1.1.7
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gobernación de Nariño
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       suite-paz
 * Domain Path:       /languages
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

// Hard-stop direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Plugin constants.
// -----------------------------------------------------------------------------
define( 'SPZ_VERSION', '1.1.7' );
define( 'SPZ_D3PLUS_VERSION', '3.1.4' );
// NOTE: must use the /full/ bundle — /umd/d3plus-core.js expects 30+
// peer deps to already be on window and fails silently with window.d3plus
// set to an empty object. The /full/ build embeds every dependency.
define( 'SPZ_D3PLUS_URL', 'https://cdn.jsdelivr.net/npm/@d3plus/core@' . SPZ_D3PLUS_VERSION . '/umd/d3plus-core.full.js' );
define( 'SPZ_PLUGIN_FILE', __FILE__ );
define( 'SPZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPZ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SPZ_MIN_CAPABILITY', 'manage_options' );
define( 'SPZ_REST_NAMESPACE', 'suite-paz/v1' );
define( 'SPZ_NONCE_ACTION', 'spz_nonce_action' );
define( 'SPZ_DATA_DIR', SPZ_PLUGIN_DIR . 'data/' );

// -----------------------------------------------------------------------------
// PSR-4-ish autoloader for plugin classes.
// Maps SPZ_Foo_Bar → includes/class-spz-foo-bar.php
// -----------------------------------------------------------------------------
spl_autoload_register(
	static function ( string $class ): void {
		if ( strpos( $class, 'SPZ_' ) !== 0 ) {
			return;
		}
		$file = SPZ_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// Core bootstrap (autoloader must be registered first so SPZ_Security resolves).
require_once SPZ_PLUGIN_DIR . 'includes/class-spz-plugin.php';

// Activation / deactivation hooks.
register_activation_hook( __FILE__, [ 'SPZ_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SPZ_Plugin', 'deactivate' ] );

// Kickoff.
add_action(
	'plugins_loaded',
	static function (): void {
		SPZ_Plugin::instance()->run();
	}
);
