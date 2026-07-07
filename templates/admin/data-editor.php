<?php
/**
 * Admin screen: editable data editor.
 *
 * Allows admins to browse, edit and save any view or module via the REST API.
 *
 * Flow:
 *   1. Select sección → JS fetches GET /views?seccion= → populates view select.
 *   2. Select view/module → JS fetches GET /export?seccion=&slug= → builds editable form.
 *   3. Guardar → JS POSTs to /save with nonce.
 *   4. Restablecer → JS POSTs to /reset with nonce.
 *   5. Exportar JSON → JS creates a download link from current data.
 *
 * @package SuitePaz
 * @var array<string,string> $secciones All secciones (slug → label).
 * @var string               $seccion   Currently selected sección slug.
 * @var string               $label     Human label for the current sección.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap spz-wrap" id="spz-editor-wrap" data-spz-seccion="<?php echo esc_attr( $seccion ); ?>">
	<header class="spz-header">
		<div class="spz-header__title">
			<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			<h1>
				<?php esc_html_e( 'Editar datos', 'suite-paz' ); ?>
				<span class="spz-header__sub">· <?php echo esc_html( $label ); ?></span>
			</h1>
		</div>
		<p class="spz-header__lede">
			<?php esc_html_e( 'Selecciona una sección y una vista o módulo para editar sus datos. Los cambios se guardan en la base de datos de WordPress y prevalecen sobre los archivos JSON de origen.', 'suite-paz' ); ?>
		</p>
	</header>

	<!-- Sección + view selectors -->
	<div class="spz-editor-selectors spz-card">
		<div class="spz-form-row">
			<div class="spz-form-field">
				<label for="spz-editor-seccion">
					<strong><?php esc_html_e( 'Sección', 'suite-paz' ); ?></strong>
				</label>
				<select id="spz-editor-seccion" class="spz-select" data-spz-editor-seccion>
					<?php foreach ( $secciones as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $seccion, $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="spz-form-field">
				<label for="spz-editor-view">
					<strong><?php esc_html_e( 'Vista / Módulo', 'suite-paz' ); ?></strong>
				</label>
				<select id="spz-editor-view" class="spz-select" data-spz-editor-view disabled>
					<option value=""><?php esc_html_e( '— Cargando… —', 'suite-paz' ); ?></option>
				</select>
			</div>

			<div class="spz-form-field spz-form-field--actions">
				<button type="button" class="button button-primary" id="spz-btn-save" disabled>
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Guardar', 'suite-paz' ); ?>
				</button>
				<button type="button" class="button" id="spz-btn-export" disabled>
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
					<?php esc_html_e( 'Exportar JSON', 'suite-paz' ); ?>
				</button>
				<button type="button" class="button spz-btn--danger" id="spz-btn-reset" disabled>
					<span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
					<?php esc_html_e( 'Restablecer', 'suite-paz' ); ?>
				</button>
			</div>
		</div>

		<!-- Feedback banner -->
		<div id="spz-editor-feedback" class="spz-feedback" hidden aria-live="polite"></div>

		<!-- Source badge -->
		<div id="spz-editor-source" class="spz-source-badge" hidden>
			<span id="spz-source-label"></span>
		</div>
	</div>

	<!-- Editable form area — rendered by admin.js -->
	<div id="spz-editor-form" class="spz-editor-form" aria-live="polite">
		<p class="spz-empty"><?php esc_html_e( 'Selecciona una sección y una vista para comenzar a editar.', 'suite-paz' ); ?></p>
	</div>
</div>
