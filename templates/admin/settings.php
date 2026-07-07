<?php
/**
 * Admin screen: plugin settings.
 *
 * @package SuitePaz
 * @var array<string,mixed> $settings Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

settings_errors( 'spz_settings' );
?>
<div class="wrap spz-wrap">
	<header class="spz-header">
		<div class="spz-header__title">
			<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
			<h1>
				<?php esc_html_e( 'Suite PAZ', 'suite-paz' ); ?>
				<span class="spz-header__sub">· <?php esc_html_e( 'Ajustes', 'suite-paz' ); ?></span>
			</h1>
		</div>
		<p class="spz-header__lede">
			<?php esc_html_e( 'Configura el comportamiento del plugin: caché, roles y tema por defecto.', 'suite-paz' ); ?>
		</p>
	</header>

	<form method="post" class="spz-settings-form spz-card">
		<?php wp_nonce_field( 'spz_save_settings', 'spz_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>

				<!-- Enable cache -->
				<tr>
					<th scope="row">
						<label for="spz-enable-cache"><?php esc_html_e( 'Caché de datos', 'suite-paz' ); ?></label>
					</th>
					<td>
						<label class="spz-switch">
							<input type="checkbox" id="spz-enable-cache" name="enable_cache" value="1"
								<?php checked( ! empty( $settings['enable_cache'] ) ); ?> />
							<span class="spz-switch__track"></span>
							<span class="spz-switch__label"><?php esc_html_e( 'Activar caché de vistas (recomendado)', 'suite-paz' ); ?></span>
						</label>
					</td>
				</tr>

				<!-- Cache TTL -->
				<tr>
					<th scope="row">
						<label for="spz-cache-ttl"><?php esc_html_e( 'TTL de caché (segundos)', 'suite-paz' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							id="spz-cache-ttl"
							name="cache_ttl"
							value="<?php echo absint( $settings['cache_ttl'] ?? 600 ); ?>"
							min="60"
							max="86400"
							step="60"
							class="small-text"
						/>
						<p class="description">
							<?php esc_html_e( 'Tiempo en segundos que los datos de cada vista se mantienen en caché. Mínimo: 60. Por defecto: 600 (10 min).', 'suite-paz' ); ?>
						</p>
					</td>
				</tr>

				<!-- Default theme -->
				<tr>
					<th scope="row">
						<label for="spz-default-theme"><?php esc_html_e( 'Tema por defecto', 'suite-paz' ); ?></label>
					</th>
					<td>
						<select id="spz-default-theme" name="default_theme">
							<option value="suite-paz" <?php selected( $settings['default_theme'] ?? 'suite-paz', 'suite-paz' ); ?>>
								<?php esc_html_e( 'Suite PAZ (paleta oficial)', 'suite-paz' ); ?>
							</option>
							<option value="neutral" <?php selected( $settings['default_theme'] ?? 'suite-paz', 'neutral' ); ?>>
								<?php esc_html_e( 'Neutro (grises)', 'suite-paz' ); ?>
							</option>
						</select>
					</td>
				</tr>

				<!-- Allowed roles for shortcode -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Roles con acceso a shortcodes', 'suite-paz' ); ?>
					</th>
					<td>
						<?php
						$allowed_roles = (array) ( $settings['allow_shortcode_roles'] ?? [ 'administrator', 'editor' ] );
						$all_roles     = wp_roles()->get_names();
						foreach ( $all_roles as $role_key => $role_name ) :
						?>
							<label style="display:block; margin-bottom:4px;">
								<input
									type="checkbox"
									name="allow_shortcode_roles[]"
									value="<?php echo esc_attr( $role_key ); ?>"
									<?php checked( in_array( $role_key, $allowed_roles, true ) ); ?>
								/>
								<?php echo esc_html( translate_user_role( $role_name ) ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description">
							<?php esc_html_e( 'Roles que pueden insertar shortcodes de Suite PAZ en páginas/entradas.', 'suite-paz' ); ?>
						</p>
					</td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<span class="dashicons dashicons-saved" aria-hidden="true"></span>
				<?php esc_html_e( 'Guardar ajustes', 'suite-paz' ); ?>
			</button>
		</p>

		<div class="spz-settings-info spz-card" style="margin-top:24px; padding:16px;">
			<h2 style="margin-top:0; font-size:14px;"><?php esc_html_e( 'Información del plugin', 'suite-paz' ); ?></h2>
			<dl class="spz-dl">
				<dt><?php esc_html_e( 'Versión:', 'suite-paz' ); ?></dt>
				<dd><?php echo esc_html( SPZ_VERSION ); ?></dd>

				<dt><?php esc_html_e( 'Secciones activas:', 'suite-paz' ); ?></dt>
				<dd><?php echo esc_html( implode( ', ', array_keys( SPZ_Plugin::SECCIONES ) ) ); ?></dd>

				<dt><?php esc_html_e( 'Namespace REST:', 'suite-paz' ); ?></dt>
				<dd><code><?php echo esc_html( SPZ_REST_NAMESPACE ); ?></code></dd>

				<dt><?php esc_html_e( 'Directorio de datos:', 'suite-paz' ); ?></dt>
				<dd><code><?php echo esc_html( SPZ_DATA_DIR ); ?></code></dd>
			</dl>
		</div>
	</form>
</div>
