<?php
/**
 * Admin screen: shortcode gallery (sección-scoped).
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

$registry      = SPZ_Plugin::instance()->chart_types;
$data_provider = SPZ_Plugin::instance()->data_provider( $seccion );
$secciones     = SPZ_Plugin::instance()->secciones();
?>
<div class="wrap spz-wrap" data-spz-seccion="<?php echo esc_attr( $seccion ); ?>">
	<header class="spz-header">
		<div class="spz-header__title">
			<span class="dashicons dashicons-shortcode" aria-hidden="true"></span>
			<h1>
				<?php esc_html_e( 'Galería de shortcodes', 'suite-paz' ); ?>
				<span class="spz-header__sub">· <?php echo esc_html( $label ); ?></span>
			</h1>
		</div>
		<p class="spz-header__lede">
			<?php esc_html_e( 'Cada vista aparece acompañada de sus gráficos y módulos compatibles. Copia el shortcode y pégalo en cualquier página o entrada.', 'suite-paz' ); ?>
		</p>
	</header>

	<form method="get" class="spz-form-inline">
		<input type="hidden" name="page" value="suite-paz-shortcodes" />
		<label for="spz-seccion-select"><?php esc_html_e( 'Sección:', 'suite-paz' ); ?></label>
		<select name="seccion" id="spz-seccion-select" class="spz-select" onchange="this.form.submit()">
			<?php foreach ( $secciones as $slug => $name ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $seccion, $slug ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</form>

	<?php if ( empty( $views ) ) : ?>
		<p class="spz-empty"><?php esc_html_e( 'No hay vistas registradas todavía en esta sección.', 'suite-paz' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $views as $summary ) :
		$full = $data_provider->get_view( $summary['id'] );
		if ( empty( $full ) ) {
			continue;
		}

		// Module shortcodes.
		if ( ! empty( $full['is_module'] ) ) :
			$modulo_type = isset( $full['modulo'] ) ? sanitize_key( $full['modulo'] ) : 'kpi';
			$tag         = 'spz_' . $modulo_type;
			$shortcode   = sprintf(
				'[%s id="%s" seccion="%s"]',
				esc_attr( $tag ),
				esc_attr( $summary['id'] ),
				esc_attr( $seccion )
			);
			?>
			<details class="spz-view-block" open>
				<summary class="spz-view-block__summary">
					<span class="spz-view-block__name"><?php echo esc_html( $summary['name'] ?? $summary['id'] ); ?></span>
					<span class="spz-pill spz-pill--module"><?php echo esc_html( $modulo_type ); ?></span>
				</summary>
				<p class="spz-view-block__desc"><?php echo esc_html( $full['descripcion'] ?? $full['description'] ?? '' ); ?></p>
				<div class="spz-shortcode-grid">
					<article class="spz-shortcode-card">
						<header class="spz-shortcode-card__header">
							<span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>
							<h3><?php echo esc_html( $modulo_type ); ?></h3>
						</header>
						<p class="spz-shortcode-card__desc">
							<?php
							printf(
								/* translators: %s: module type */
								esc_html__( 'Módulo PAZ de tipo %s.', 'suite-paz' ),
								'<strong>' . esc_html( $modulo_type ) . '</strong>'
							);
							?>
						</p>
						<div class="spz-shortcode-row">
							<input
								type="text"
								readonly
								value="<?php echo esc_attr( $shortcode ); ?>"
								aria-label="<?php esc_attr_e( 'Shortcode', 'suite-paz' ); ?>"
							/>
							<button type="button" class="button spz-copy-btn" data-spz-copy>
								<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
								<?php esc_html_e( 'Copiar', 'suite-paz' ); ?>
							</button>
						</div>
					</article>
				</div>
			</details>
		<?php else :
			// Regular view — list compatible chart types.
			$compatible   = $registry->compatible_for( $full );
			$seccion_attr = ( 'dni' === $seccion ) ? '' : sprintf( ' seccion="%s"', esc_attr( $seccion ) );
			?>
			<details class="spz-view-block" open>
				<summary class="spz-view-block__summary">
					<span class="spz-view-block__name"><?php echo esc_html( $summary['name'] ?? $summary['id'] ); ?></span>
					<span class="spz-pill spz-pill--<?php echo esc_attr( $summary['category'] ?? 'categorical' ); ?>"><?php echo esc_html( $summary['category'] ?? '' ); ?></span>
					<span class="spz-view-block__meta"><?php echo absint( $summary['rows'] ?? 0 ); ?> <?php esc_html_e( 'filas', 'suite-paz' ); ?></span>
				</summary>
				<p class="spz-view-block__desc"><?php echo esc_html( $summary['description'] ?? '' ); ?></p>

				<?php if ( empty( $compatible ) ) : ?>
					<p class="spz-empty"><?php esc_html_e( 'Ningún gráfico es compatible con esta vista.', 'suite-paz' ); ?></p>
				<?php else : ?>
					<div class="spz-shortcode-grid">
						<?php foreach ( $compatible as $chart ) :
							$shortcode = sprintf(
								'[spz_grafico view="%s" type="%s"%s height="420" title="%s"]',
								esc_attr( $summary['id'] ),
								esc_attr( $chart['key'] ),
								$seccion_attr,
								esc_attr( str_replace( '"', "'", $summary['name'] ?? $summary['id'] ) )
							);
							?>
							<article class="spz-shortcode-card">
								<header class="spz-shortcode-card__header">
									<span class="dashicons dashicons-<?php echo esc_attr( $chart['icon'] ?? 'chart-area' ); ?>" aria-hidden="true"></span>
									<h3><?php echo esc_html( $chart['label'] ); ?></h3>
								</header>
								<p class="spz-shortcode-card__desc"><?php echo esc_html( $chart['description'] ?? '' ); ?></p>
								<div class="spz-shortcode-row">
									<input
										type="text"
										readonly
										value="<?php echo esc_attr( $shortcode ); ?>"
										aria-label="<?php esc_attr_e( 'Shortcode', 'suite-paz' ); ?>"
									/>
									<button type="button" class="button spz-copy-btn" data-spz-copy>
										<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
										<?php esc_html_e( 'Copiar', 'suite-paz' ); ?>
									</button>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<h4 class="spz-data-heading">
						<span class="dashicons dashicons-editor-table" aria-hidden="true"></span>
						<?php esc_html_e( 'Muestra de datos', 'suite-paz' ); ?>
					</h4>
					<div class="spz-table-wrap">
						<?php
						$fields = array_merge( $full['dimensions'] ?? [], $full['measures'] ?? [] );
						if ( ! empty( $fields ) && ! empty( $full['data'] ) ) :
						?>
						<table class="widefat striped spz-data-table">
							<thead>
								<tr>
									<?php foreach ( $fields as $field ) : ?>
										<th><?php echo esc_html( (string) $field ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_slice( $full['data'], 0, 20 ) as $row ) : ?>
									<tr>
										<?php foreach ( $fields as $field ) : ?>
											<td><?php echo esc_html( (string) ( $row[ $field ] ?? '' ) ); ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php if ( count( $full['data'] ) > 20 ) : ?>
							<p class="spz-hint">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: number of hidden rows */
										__( '… y %d filas más.', 'suite-paz' ),
										count( $full['data'] ) - 20
									)
								);
								?>
							</p>
						<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</details>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
