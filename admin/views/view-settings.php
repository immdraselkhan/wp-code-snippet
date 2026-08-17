<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 
 
 
 
$settings = wp_parse_args(
	get_option( 'wcs_settings', array() ),
	array(
		'safe_mode'          => 1,
		'disable_editor_php' => 0,
		'load_frontend_css'  => 1,
	)
);
?>
<div class="wrap wcs-wrap" id="wcs-app-root" data-view="settings">
	<div class="wcs-header">
		<div class="wcs-header-left">
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet' ) ); ?>" class="wcs-btn wcs-btn-ghost wcs-btn-sm wcs-nav-link" data-view="list">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
			</a>
			<div>
				<h1 class="wcs-title"><?php esc_html_e( 'Settings', 'wp-code-snippet' ); ?></h1>
				<p class="wcs-subtitle"><?php esc_html_e( 'Global behavior for WP Code Snippet.', 'wp-code-snippet' ); ?></p>
			</div>
		</div>
	</div>

	<form method="post" id="wcs-settings-form">
		<?php wp_nonce_field( 'wcs_save_settings', 'wcs_settings_nonce' ); ?>

		<div class="wcs-card">
			<div class="wcs-card-header">
				<div>
					<h3 class="wcs-card-title"><?php esc_html_e( 'Safety', 'wp-code-snippet' ); ?></h3>
					<p class="wcs-card-description"><?php esc_html_e( 'Protections that keep a bad snippet from taking your site down.', 'wp-code-snippet' ); ?></p>
				</div>
			</div>
			<div class="wcs-card-body">
				<div class="wcs-field">
					<div class="wcs-flex" style="justify-content:space-between;">
						<div>
							<span class="wcs-label" style="margin:0;"><?php esc_html_e( 'Safe Mode', 'wp-code-snippet' ); ?></span>
							<p class="wcs-hint" style="margin-top:2px;"><?php esc_html_e( 'Automatically deactivate a PHP snippet if it causes a fatal error, and syntax-check PHP before it can be activated.', 'wp-code-snippet' ); ?></p>
						</div>
						<label class="wcs-switch">
							<input type="checkbox" name="safe_mode" <?php checked( 1, $settings['safe_mode'] ); ?>>
							<span class="wcs-switch-slider"></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="wcs-card">
			<div class="wcs-card-header">
				<div>
					<h3 class="wcs-card-title"><?php esc_html_e( 'Frontend Output', 'wp-code-snippet' ); ?></h3>
				</div>
			</div>
			<div class="wcs-card-body">
				<div class="wcs-field">
					<div class="wcs-flex" style="justify-content:space-between;">
						<div>
							<span class="wcs-label" style="margin:0;"><?php esc_html_e( 'Load CSS/JS snippets on the frontend', 'wp-code-snippet' ); ?></span>
							<p class="wcs-hint" style="margin-top:2px;"><?php esc_html_e( 'Turn off temporarily to debug a frontend styling/script issue.', 'wp-code-snippet' ); ?></p>
						</div>
						<label class="wcs-switch">
							<input type="checkbox" name="load_frontend_css" <?php checked( 1, $settings['load_frontend_css'] ); ?>>
							<span class="wcs-switch-slider"></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="wcs-card">
			<div class="wcs-card-header">
				<div>
					<h3 class="wcs-card-title"><?php esc_html_e( 'Access', 'wp-code-snippet' ); ?></h3>
				</div>
			</div>
			<div class="wcs-card-body">
				<div class="wcs-field">
					<div class="wcs-flex" style="justify-content:space-between;">
						<div>
							<span class="wcs-label" style="margin:0;"><?php esc_html_e( 'Disable the PHP editor', 'wp-code-snippet' ); ?></span>
							<p class="wcs-hint" style="margin-top:2px;"><?php esc_html_e( 'Restrict this install to HTML/CSS/JS snippets only (useful on client sites).', 'wp-code-snippet' ); ?></p>
						</div>
						<label class="wcs-switch">
							<input type="checkbox" name="disable_editor_php" <?php checked( 1, $settings['disable_editor_php'] ); ?>>
							<span class="wcs-switch-slider"></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="wcs-sticky-bar" style="margin:20px 0 0;border:1px solid var(--wcs-border);border-radius:var(--wcs-radius-lg);">
			<span class="wcs-muted" style="font-size:12px;"><?php esc_html_e( 'Changes apply immediately across the site.', 'wp-code-snippet' ); ?></span>
			<button type="submit" class="wcs-btn wcs-btn-primary">
				<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'wp-code-snippet' ); ?>
			</button>
		</div>
	</form>
</div>
