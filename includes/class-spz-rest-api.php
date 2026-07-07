<?php
/**
 * REST API controller.
 *
 * Routes:
 *  GET  /suite-paz/v1/render?seccion=&view=&type=  → public render payload
 *  GET  /suite-paz/v1/views?seccion=               → list views (admin)
 *
 * Every route accepts `seccion` (default: 'dni') that scopes the data
 * provider. Valid values are the keys of SPZ_Plugin::SECCIONES.
 *
 * Security model (mirrors tic-suite exactly):
 *  - /render: permission_callback returns true (public read); parameters are
 *    sanitized and whitelist-validated inside the callback before any data
 *    is returned. Invalid parameters get a 400/404/409 error response, never
 *    raw data. No write access exists on this route.
 *  - /views:  permission_callback requires manage_options capability (admin).
 *
 * Adapted from class-tsg-rest-api.php (tic-suite).
 * Changes: TSG_ → SPZ_, project → seccion, namespace suite-paz/v1,
 * compatible_with_view() → compatible_for(), pluginUrl constant.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Rest_Api
 */
class SPZ_Rest_Api {

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
	 * Register all REST routes. Hooked to rest_api_init.
	 */
	public function register_routes(): void {
		$seccion_arg = [
			'required'          => false,
			'default'           => SPZ_Plugin::DEFAULT_SECCION,
			'sanitize_callback' => 'sanitize_key',
		];

		// GET /suite-paz/v1/views?seccion= — admin only.
		register_rest_route(
			SPZ_REST_NAMESPACE,
			'/views',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'list_views' ],
				'permission_callback' => [ $this->security, 'rest_admin_permission' ],
				'args'                => [ 'seccion' => $seccion_arg ],
			]
		);

		// GET /suite-paz/v1/render?seccion=&view=&type= — public, whitelist-guarded.
		register_rest_route(
			SPZ_REST_NAMESPACE,
			'/render',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'render_payload' ],
				'permission_callback' => [ $this->security, 'rest_public_permission' ],
				'args'                => [
					'seccion' => $seccion_arg,
					'view'    => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					'type'    => [
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $value ) {
							$v = sanitize_key( (string) $value );
							// Allow empty type (module requests do not supply a type).
							return '' === $v || $this->chart_types->is_valid_type( $v );
						},
					],
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Resolve and whitelist the seccion param from a REST request.
	 */
	private function seccion_from( WP_REST_Request $request ): string {
		return $this->plugin->normalize_seccion( (string) $request->get_param( 'seccion' ) );
	}

	// -------------------------------------------------------------------------
	// Route callbacks
	// -------------------------------------------------------------------------

	/**
	 * GET /suite-paz/v1/views — return all view slugs for a sección (admin).
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function list_views( WP_REST_Request $request ): WP_REST_Response {
		$seccion = $this->seccion_from( $request );
		$dp      = $this->plugin->data_provider( $seccion );
		return new WP_REST_Response( $dp->list_views(), 200 );
	}

	/**
	 * GET /suite-paz/v1/render — return the render payload for a view+type.
	 *
	 * Response shape (consumed by frontend.js → renderer.js):
	 *   {
	 *     chart:      { key, class, label }
	 *     view:       { id, name, category, dimensions, measures, edges }
	 *     data:       [ ... ]          ← row array for d3plus
	 *     mapping:    { groupBy, x, y, value, size, … }
	 *     compatible: [ { key, label, icon, class, description }, … ]
	 *     seccion:    string
	 *   }
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function render_payload( WP_REST_Request $request ) {
		$seccion    = $this->seccion_from( $request );
		$dp         = $this->plugin->data_provider( $seccion );
		$view_id    = $this->security->sanitize_slug( (string) $request->get_param( 'view' ) );
		$type_raw   = sanitize_key( (string) $request->get_param( 'type' ) );
		$chart_type = $this->chart_types->is_valid_type( $type_raw ) ? $type_raw : '';

		if ( '' === $view_id ) {
			return new WP_Error(
				'spz_bad_request',
				__( 'Parámetro inválido: se requiere "view".', 'suite-paz' ),
				[ 'status' => 400 ]
			);
		}

		$view = $dp->get_view( $view_id );
		if ( empty( $view ) ) {
			return new WP_Error(
				'spz_view_not_found',
				__( 'Vista no encontrada.', 'suite-paz' ),
				[ 'status' => 404 ]
			);
		}

		// Native module — return the raw module JSON directly (no chart type needed).
		if ( ! empty( $view['is_module'] ) ) {
			return new WP_REST_Response( $view, 200 );
		}

		// Chart view — type is now required.
		if ( '' === $chart_type ) {
			return new WP_Error(
				'spz_bad_request',
				__( 'Parámetro inválido: se requiere "type" para vistas de gráfico.', 'suite-paz' ),
				[ 'status' => 400 ]
			);
		}

		$compatible_keys = wp_list_pluck( $this->chart_types->compatible_for( $view ), 'key' );
		if ( ! in_array( $chart_type, $compatible_keys, true ) ) {
			return new WP_Error(
				'spz_incompatible',
				__( 'El tipo de gráfico solicitado no es compatible con la vista.', 'suite-paz' ),
				[ 'status' => 409 ]
			);
		}

		$chart_def = $this->chart_types->get( $chart_type );
		$payload   = [
			'chart'      => [
				'key'   => $chart_type,
				'class' => $chart_def['d3plus_class'] ?? '',
				'label' => $chart_def['label'] ?? '',
			],
			'view'       => [
				'id'         => $view['id'],
				'name'       => $view['name'],
				'category'   => $view['category'],
				'dimensions' => $view['dimensions'],
				'measures'   => $view['measures'],
				'edges'      => $view['edges'] ?? [],
			],
			'data'       => $view['data'],
			'mapping'    => $this->build_mapping( $view, $chart_type ),
			'compatible' => $this->chart_types->compatible_for( $view ),
			'seccion'    => $seccion,
		];

		return new WP_REST_Response( $payload, 200 );
	}

	// -------------------------------------------------------------------------
	// Mapping builder
	// -------------------------------------------------------------------------

	/**
	 * Build the d3plus field mapping for a given view + chart type.
	 *
	 * @param array  $view       Normalised view definition from the data provider.
	 * @param string $chart_type Chart type key (already validated).
	 * @return array<string, mixed>
	 */
	private function build_mapping( array $view, string $chart_type ): array {
		$dims     = $view['dimensions'] ?? [];
		$measures = $view['measures'] ?? [];

		$mapping = [
			'groupBy' => $dims,
			'x'       => $dims[0] ?? '',
			'y'       => $measures[0] ?? '',
			'value'   => $measures[0] ?? '',
			'size'    => $measures[0] ?? '',
		];

		switch ( $chart_type ) {
			case 'sankey':
				$mapping['nodes'] = $dims;
				$mapping['links'] = $view['edges'] ?? [];
				break;

			case 'network':
			case 'rings':
				$mapping['nodes'] = array_map(
					static function ( $row ) {
						if ( is_array( $row ) && ! isset( $row['id'] ) ) {
							$first    = reset( $row );
							$row['id'] = is_scalar( $first ) ? (string) $first : '';
						}
						return $row;
					},
					$view['data']
				);
				$mapping['links'] = $view['edges'] ?? [];
				break;

			case 'priestley':
				$mapping['start'] = $view['dimensions'][0] ?? '';
				$mapping['end']   = $view['dimensions'][1] ?? '';
				break;

			case 'geomap':
				$mapping['topojson']    = SPZ_PLUGIN_URL . 'data/topo/narino_municipios.topojson';
				$mapping['topojsonId']  = 'id';
				$mapping['topojsonKey'] = 'objects.municipios';
				$mapping['join']        = 'municipio';
				break;
		}

		return $mapping;
	}
}
