<?php
/**
 * PAZ native module registry.
 *
 * Registers the six PAZ module types (kpi, compare, timeline, logro, diagrama, estrategia) and
 * provides validation helpers consumed by shortcode handlers and the REST API.
 *
 * Module JSON files carry a top-level `"modulo"` key (detected by
 * SPZ_Data_Provider) and are returned with `is_module => true`.  The
 * shortcodes emit `.spz-module[data-modulo][data-id][data-seccion]`
 * placeholders that frontend.js hydrates by calling SPZ.modules.render().
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Modules
 */
class SPZ_Modules {

	/**
	 * Registered module types, in canonical order.
	 *
	 * @var string[]
	 */
	private const TYPES = [ 'kpi', 'compare', 'timeline', 'logro', 'diagrama', 'estrategia' ];

	/**
	 * Return all registered module type keys.
	 *
	 * @return string[]
	 */
	public function types(): array {
		return self::TYPES;
	}

	/**
	 * Return true when $type is a known module type key.
	 *
	 * @param string $type Module type to validate.
	 * @return bool
	 */
	public function is_valid( string $type ): bool {
		return in_array( $type, self::TYPES, true );
	}
}
