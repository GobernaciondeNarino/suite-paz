<?php
/**
 * Chart types registry.
 *
 * Defines the 15 supported d3plus chart types, their d3plus class name,
 * the view categories they can render, and the minimum field requirements.
 *
 * Adapted from class-tsg-chart-types.php (tic-suite).
 * Changes: TSG_ → SPZ_, text domain, added tree + box_whisker (15 total),
 * renamed compatible_with_view() → compatible_for(), added is_valid_type(),
 * compatible_for() returns [] for modules and non-standard tipo_grafico_sugerido.
 *
 * The 15 types (d3plus v3):
 *   bar, stacked_bar, line, area, stacked_area, pie, donut, treemap,
 *   geomap, network, tree, sankey, rings, box_whisker, priestley.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Chart_Types
 */
class SPZ_Chart_Types {

	/**
	 * Internal registry keyed by type slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $types;

	public function __construct() {
		$this->types = $this->build_registry();
	}

	// -------------------------------------------------------------------------
	// Public API.
	// -------------------------------------------------------------------------

	/**
	 * Return all registered chart types.
	 *
	 * Each entry contains:
	 *   label, icon, d3plus_class, categories, requires, description.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		return $this->types;
	}

	/**
	 * Whether a chart type slug is registered.
	 *
	 * @param string $type Chart type slug.
	 */
	public function is_valid_type( string $type ): bool {
		return isset( $this->types[ $type ] );
	}

	/**
	 * Get a single chart type definition by slug.
	 *
	 * @param string $type Chart type slug.
	 * @return array<string, mixed>
	 */
	public function get( string $type ): array {
		return $this->types[ $type ] ?? [];
	}

	/**
	 * Lightweight JSON-safe version of the registry for JS localisation.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all_for_js(): array {
		$out = [];
		foreach ( $this->types as $key => $def ) {
			$out[] = [
				'key'         => $key,
				'label'       => $def['label'],
				'icon'        => $def['icon'],
				'class'       => $def['d3plus_class'],
				'categories'  => $def['categories'],
				'description' => $def['description'],
			];
		}
		return $out;
	}

	/**
	 * Given a view definition, return compatible chart type entries.
	 *
	 * Returns an empty array when:
	 *   - The view is a module (is_module === true).
	 *   - The view has a non-empty `tipo_grafico_sugerido` that is not one of
	 *     the 15 registered type slugs (e.g. "strategy", "radial"); this handles
	 *     the estrategia sección views gracefully without erroring.
	 *   - No registered type matches the view's category + field requirements.
	 *
	 * @param array $view Normalised view definition.
	 * @return array<int, array<string, mixed>>
	 */
	public function compatible_for( array $view ): array {
		// Modules have no chart representation.
		if ( ! empty( $view['is_module'] ) ) {
			return [];
		}

		// Non-standard tipo_grafico_sugerido (e.g. "strategy", "radial") signals
		// that this view is not intended for generic d3plus rendering.
		$hint = (string) ( $view['tipo_grafico_sugerido'] ?? '' );
		if ( '' !== $hint && ! $this->is_valid_type( $hint ) ) {
			return [];
		}

		$view_category = (string) ( $view['category'] ?? '' );
		$dimensions    = count( $view['dimensions'] ?? [] );
		$measures      = count( $view['measures'] ?? [] );
		$has_edges     = ! empty( $view['edges'] );
		$has_range     = ! empty( $view['temporal_range'] );

		$compatible = [];
		foreach ( $this->types as $key => $def ) {
			if ( $view_category && ! in_array( $view_category, $def['categories'], true ) ) {
				continue;
			}
			if ( $dimensions < (int) ( $def['requires']['dimensions'] ?? 0 ) ) {
				continue;
			}
			if ( $measures < (int) ( $def['requires']['measures'] ?? 0 ) ) {
				continue;
			}
			if ( ! empty( $def['requires']['edges'] ) && ! $has_edges ) {
				continue;
			}
			if ( ! empty( $def['requires']['range'] ) && ! $has_range ) {
				continue;
			}
			$compatible[] = [
				'key'         => $key,
				'label'       => $def['label'],
				'icon'        => $def['icon'],
				'class'       => $def['d3plus_class'],
				'description' => $def['description'],
			];
		}

		return $compatible;
	}

	// -------------------------------------------------------------------------
	// Registry builder.
	// -------------------------------------------------------------------------

	/**
	 * Build and return the full chart-type registry.
	 *
	 * Keys per entry:
	 *   label        — Human-readable name (translatable).
	 *   icon         — Dashicon slug for admin UI.
	 *   d3plus_class — d3plus v3 constructor name.
	 *   categories   — View categories this type can render.
	 *   requires     — Minimum field counts / flags required.
	 *   description  — Short description (translatable).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function build_registry(): array {
		return [
			'bar'         => [
				'label'        => __( 'Barras', 'suite-paz' ),
				'icon'         => 'chart-bar',
				'd3plus_class' => 'BarChart',
				'categories'   => [ 'categorical', 'statistical', 'geographic', 'hierarchical', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Comparación de categorías con una métrica.', 'suite-paz' ),
			],
			'stacked_bar' => [
				'label'        => __( 'Barras apiladas', 'suite-paz' ),
				'icon'         => 'chart-bar',
				'd3plus_class' => 'BarChart', // renderer must call .stacked(true) to distinguish from plain bar.
				'categories'   => [ 'categorical', 'statistical', 'geographic', 'hierarchical', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 2 ],
				'description'  => __( 'Descomposición de categorías en sub-grupos.', 'suite-paz' ),
			],
			'line'        => [
				'label'        => __( 'Líneas', 'suite-paz' ),
				'icon'         => 'chart-line',
				'd3plus_class' => 'LinePlot',
				'categories'   => [ 'temporal', 'statistical', 'categorical', 'geographic', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 2 ],
				'description'  => __( 'Evolución entre vigencias o series.', 'suite-paz' ),
			],
			'area'        => [
				'label'        => __( 'Área', 'suite-paz' ),
				'icon'         => 'chart-area',
				'd3plus_class' => 'AreaPlot',
				'categories'   => [ 'temporal', 'geographic' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 2 ],
				'description'  => __( 'Volumen acumulado entre vigencias.', 'suite-paz' ),
			],
			'stacked_area' => [
				'label'        => __( 'Área apilada', 'suite-paz' ),
				'icon'         => 'chart-area',
				'd3plus_class' => 'StackedArea',
				'categories'   => [ 'temporal', 'geographic' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 2 ],
				'description'  => __( 'Participación de grupos entre vigencias.', 'suite-paz' ),
			],
			'pie'         => [
				'label'        => __( 'Pastel', 'suite-paz' ),
				'icon'         => 'chart-pie',
				'd3plus_class' => 'Pie',
				'categories'   => [ 'categorical', 'geographic', 'hierarchical', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Composición porcentual de una categoría.', 'suite-paz' ),
			],
			'donut'       => [
				'label'        => __( 'Dona', 'suite-paz' ),
				'icon'         => 'chart-pie',
				'd3plus_class' => 'Donut',
				'categories'   => [ 'categorical', 'geographic', 'hierarchical', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Composición porcentual con centro libre.', 'suite-paz' ),
			],
			'treemap'     => [
				'label'        => __( 'Treemap', 'suite-paz' ),
				'icon'         => 'grid-view',
				'd3plus_class' => 'Treemap',
				'categories'   => [ 'categorical', 'hierarchical', 'geographic', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Jerarquías rectangulares proporcionales.', 'suite-paz' ),
			],
			'geomap'      => [
				'label'        => __( 'Mapa coroplético', 'suite-paz' ),
				'icon'         => 'location-alt',
				'd3plus_class' => 'Geomap',
				'categories'   => [ 'geographic' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Distribución geográfica por municipio.', 'suite-paz' ),
			],
			'network'     => [
				'label'        => __( 'Red', 'suite-paz' ),
				'icon'         => 'share',
				'd3plus_class' => 'Network',
				'categories'   => [ 'network' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1, 'edges' => true ],
				'description'  => __( 'Grafo de nodos y relaciones.', 'suite-paz' ),
			],
			'tree'        => [
				'label'        => __( 'Árbol', 'suite-paz' ),
				'icon'         => 'networking',
				'd3plus_class' => 'Tree',
				'categories'   => [ 'hierarchical', 'categorical' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Estructura jerárquica ramificada (árbol).', 'suite-paz' ),
			],
			'sankey'      => [
				'label'        => __( 'Sankey', 'suite-paz' ),
				'icon'         => 'randomize',
				'd3plus_class' => 'Sankey',
				'categories'   => [ 'categorical', 'network', 'hierarchical' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1, 'edges' => true ],
				'description'  => __( 'Flujo de valores entre categorías o nodos.', 'suite-paz' ),
			],
			'rings'       => [
				'label'        => __( 'Anillos', 'suite-paz' ),
				'icon'         => 'marker',
				'd3plus_class' => 'Rings',
				'categories'   => [ 'network' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1, 'edges' => true ],
				'description'  => __( 'Relaciones radiales desde un nodo focal.', 'suite-paz' ),
			],
			'box_whisker' => [
				'label'        => __( 'Caja y bigotes', 'suite-paz' ),
				'icon'         => 'analytics',
				'd3plus_class' => 'BoxWhisker',
				'categories'   => [ 'statistical', 'categorical', 'temporal', 'social' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 1 ],
				'description'  => __( 'Distribución estadística con cuartiles.', 'suite-paz' ),
			],
			'priestley'   => [
				'label'        => __( 'Línea temporal (Priestley)', 'suite-paz' ),
				'icon'         => 'clock',
				'd3plus_class' => 'Priestley',
				'categories'   => [ 'geographic', 'categorical', 'temporal' ],
				'requires'     => [ 'dimensions' => 1, 'measures' => 2 ],
				'description'  => __( 'Barras horizontales para periodos o comparación de vigencias.', 'suite-paz' ),
			],
		];
	}
}
