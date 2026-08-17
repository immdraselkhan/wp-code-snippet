<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id      = absint( wcs_get_param( 'id', 0 ) );
$snippet = $id ? WCS_DB::get( $id ) : null;

$defaults = array(
	'id'            => 0,
	'title'         => '',
	'description'   => '',
	'code'          => "// Your code here.\n",
	'type'          => 'php',
	'location'      => 'run_everywhere',
	'conditions'    => array( 'rule' => 'everywhere', 'scope' => 'include' ),
	'priority'      => 10,
	'status'        => 'inactive',
	'shortcode_tag' => '',
	'error_message' => '',
);
$snippet = wp_parse_args( $snippet ? $snippet : array(), $defaults );
$is_new  = empty( $snippet['id'] );

$roles = wp_roles()->get_names();

$post_types = get_post_types( array( 'public' => true ), 'objects' );
?>
<div class="wrap wcs-wrap" id="wcs-app-root" data-view="<?php echo $is_new ? 'add' : 'edit'; ?>" data-id="<?php echo esc_attr( $snippet['id'] ); ?>">

	<div class="wcs-header">
		<div class="wcs-header-left">
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet' ) ); ?>" class="wcs-btn wcs-btn-secondary wcs-btn-sm wcs-back-btn wcs-nav-link" data-view="list">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
			</a>
			<div>
				<h1 class="wcs-title"><?php echo $is_new ? esc_html__( 'Add New Snippet', 'wp-code-snippet' ) : esc_html__( 'Edit Snippet', 'wp-code-snippet' ); ?></h1>
				<p class="wcs-subtitle"><?php esc_html_e( 'Code is validated live while you type; PHP receives server-side syntax validation before activation.', 'wp-code-snippet' ); ?></p>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $snippet['error_message'] ) ) : ?>
		<div class="wcs-card" style="border-color:#fda29b;background:var(--wcs-danger-subtle);margin-bottom:20px;">
			<div class="wcs-card-body" style="display:flex;gap:10px;align-items:flex-start;">
				<span class="dashicons dashicons-warning" style="color:var(--wcs-danger-foreground);"></span>
				<div>
					<strong style="color:var(--wcs-danger-foreground);"><?php esc_html_e( 'This snippet was automatically deactivated', 'wp-code-snippet' ); ?></strong>
					<p class="wcs-muted" style="margin:4px 0 0;"><?php echo esc_html( $snippet['error_message'] ); ?></p>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<form id="wcs-snippet-form" method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-add' ) ); ?>">
		<?php wp_nonce_field( 'wcs_save_snippet', 'wcs_nonce' ); ?>
		<input type="hidden" name="wcs_action" value="save_snippet">
		<input type="hidden" name="snippet_id" value="<?php echo esc_attr( $snippet['id'] ); ?>">
		<input type="hidden" name="conditions" id="wcs-conditions-input" value="<?php echo esc_attr( wp_json_encode( $snippet['conditions'] ) ); ?>">

		<div class="wcs-grid-2">
			<!-- Main column -->
			<div>
				<div class="wcs-card">
					<div class="wcs-card-body">
						<div class="wcs-field">
							<label class="wcs-label" for="wcs-title"><?php esc_html_e( 'Snippet Title', 'wp-code-snippet' ); ?></label>
							<input type="text" id="wcs-title" name="title" class="wcs-input large-text" placeholder="<?php esc_attr_e( 'e.g. Custom excerpt length', 'wp-code-snippet' ); ?>" value="<?php echo esc_attr( $snippet['title'] ); ?>" required>
						</div>
						<div class="wcs-field">
							<label class="wcs-label" for="wcs-description"><?php esc_html_e( 'Description (optional)', 'wp-code-snippet' ); ?></label>
							<textarea id="wcs-description" name="description" class="wcs-textarea large-text" rows="2" placeholder="<?php esc_attr_e( 'What does this snippet do?', 'wp-code-snippet' ); ?>"><?php echo esc_textarea( $snippet['description'] ); ?></textarea>
						</div>
					</div>
				</div>

				<div class="wcs-card">
					<div class="wcs-tabs">
						<button type="button" class="wcs-tab is-active" data-tab="code"><?php esc_html_e( 'Code', 'wp-code-snippet' ); ?></button>
						<button type="button" class="wcs-tab" data-tab="placement"><?php esc_html_e( 'Placement & Logic', 'wp-code-snippet' ); ?></button>
					</div>

					<div class="wcs-card-body">

						<!-- CODE TAB -->
						<div class="wcs-tab-panel is-active" id="wcs-tab-code">
							<div class="wcs-field">
								<span class="wcs-label"><?php esc_html_e( 'Snippet Type', 'wp-code-snippet' ); ?></span>
								<div class="wcs-segmented">
									<?php
									$types = array(
										'php'  => 'PHP',
										'html' => 'HTML',
										'css'  => 'CSS',
										'js'   => 'JavaScript',
									);
									foreach ( $types as $val => $label ) :
										$input_id = 'wcs-type-' . $val;
										?>
										<input type="radio" name="type" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( $snippet['type'], $val ); ?>>
										<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="wcs-editor-wrap">
								<div class="wcs-editor-toolbar">
									<span><?php esc_html_e( 'No opening', 'wp-code-snippet' ); ?> <code>&lt;?php</code> <?php esc_html_e( 'tag needed for PHP snippets.', 'wp-code-snippet' ); ?></span>
									<span class="wcs-editor-status"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Checking will start once you type.', 'wp-code-snippet' ); ?></span>
								</div>
								<textarea id="wcs-code-editor" name="code"><?php echo esc_textarea( $snippet['code'] ); ?></textarea>
							</div>
						</div>

						<!-- PLACEMENT & LOGIC TAB -->
						<div class="wcs-tab-panel" id="wcs-tab-placement" style="display:none;">
							<div class="wcs-section-heading">
								<div><h3><?php esc_html_e( 'Where should this snippet run?', 'wp-code-snippet' ); ?></h3><p><?php esc_html_e( 'Choose the base placement. Conditional rules below can narrow it further.', 'wp-code-snippet' ); ?></p></div>
							</div>
							<div class="wcs-location-list">

								<div class="wcs-option-card wcs-location-option" data-types="php">
									<input type="radio" name="location" id="loc-everywhere" value="run_everywhere" <?php checked( $snippet['location'], 'run_everywhere' ); ?>>
									<label for="loc-everywhere">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-admin-site-alt3"></span><?php esc_html_e( 'Run Everywhere', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Same as functions.php — runs on init, admin & frontend.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="php">
									<input type="radio" name="location" id="loc-frontend" value="frontend_only" <?php checked( $snippet['location'], 'frontend_only' ); ?>>
									<label for="loc-frontend">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-admin-appearance"></span><?php esc_html_e( 'Frontend Only', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Skipped inside wp-admin.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="php">
									<input type="radio" name="location" id="loc-admin" value="admin_only" <?php checked( $snippet['location'], 'admin_only' ); ?>>
									<label for="loc-admin">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-admin-tools"></span><?php esc_html_e( 'Admin Only', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Only runs inside wp-admin.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-header" value="wp_head" <?php checked( $snippet['location'], 'wp_head' ); ?>>
									<label for="loc-header">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-align-left"></span><?php esc_html_e( 'Site Header', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Prints in wp_head, before </head>.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-footer" value="wp_footer" <?php checked( $snippet['location'], 'wp_footer' ); ?>>
									<label for="loc-footer">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-align-right"></span><?php esc_html_e( 'Site Footer', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Prints in wp_footer, before </body>.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-admin-header" value="admin_head" <?php checked( $snippet['location'], 'admin_head' ); ?>>
									<label for="loc-admin-header">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'Admin Header', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Prints in admin_head.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-admin-footer" value="admin_footer" <?php checked( $snippet['location'], 'admin_footer' ); ?>>
									<label for="loc-admin-footer">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'Admin Footer', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Prints in admin_footer.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-login" value="login_head" <?php checked( $snippet['location'], 'login_head' ); ?>>
									<label for="loc-login">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-lock"></span><?php esc_html_e( 'Login Page', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Prints in login_head.', 'wp-code-snippet' ); ?></div>
									</label>
								</div>

								<div class="wcs-option-card wcs-location-option" data-types="css,js,html">
									<input type="radio" name="location" id="loc-shortcode" value="shortcode" <?php checked( $snippet['location'], 'shortcode' ); ?>>
									<label for="loc-shortcode">
										<div class="wcs-option-card-title"><span class="dashicons dashicons-shortcode"></span><?php esc_html_e( 'Shortcode', 'wp-code-snippet' ); ?></div>
										<div class="wcs-option-card-desc"><?php esc_html_e( 'Insert manually anywhere with [tag].', 'wp-code-snippet' ); ?></div>
									</label>
								</div>
							</div>

							<div class="wcs-field" id="wcs-shortcode-field" style="margin-top:16px;<?php echo 'shortcode' === $snippet['location'] ? '' : 'display:none;'; ?>">
								<label class="wcs-label" for="wcs-shortcode-tag"><?php esc_html_e( 'Shortcode Tag', 'wp-code-snippet' ); ?></label>
								<input type="text" id="wcs-shortcode-tag" name="shortcode_tag" class="wcs-input regular-text" placeholder="my_snippet" value="<?php echo esc_attr( $snippet['shortcode_tag'] ); ?>">
								<p class="wcs-hint"><?php esc_html_e( 'Use as', 'wp-code-snippet' ); ?> <code class="wcs-mono">[<?php echo esc_html( $snippet['shortcode_tag'] ? $snippet['shortcode_tag'] : 'my_snippet' ); ?>]</code></p>
							</div>
							<div class="wcs-placement-divider"></div>
							<div class="wcs-condition-builder" data-saved="<?php echo esc_attr( wp_json_encode( $snippet['conditions'] ) ); ?>">
								<div class="wcs-section-heading">
									<div><h3><?php esc_html_e( 'Conditional logic', 'wp-code-snippet' ); ?></h3><p><?php esc_html_e( 'Optional. Add rules only when the base placement should be limited further.', 'wp-code-snippet' ); ?></p></div>
								</div>
								<div class="wcs-condition-sentence">
									<select class="wcs-select wcs-condition-scope-select" aria-label="Condition action">
										<option value="include" <?php selected( ( $snippet['conditions']['scope'] ?? 'include' ), 'include' ); ?>><?php esc_html_e( 'Run', 'wp-code-snippet' ); ?></option>
										<option value="exclude" <?php selected( ( $snippet['conditions']['scope'] ?? 'include' ), 'exclude' ); ?>><?php esc_html_e( 'Do not run', 'wp-code-snippet' ); ?></option>
									</select>
									<span><?php esc_html_e( 'this snippet if', 'wp-code-snippet' ); ?></span>
									<select class="wcs-select wcs-condition-logic-select" aria-label="Condition match mode">
										<option value="and" <?php selected( ( $snippet['conditions']['logic'] ?? 'and' ), 'and' ); ?>><?php esc_html_e( 'All', 'wp-code-snippet' ); ?></option>
										<option value="or" <?php selected( ( $snippet['conditions']['logic'] ?? 'and' ), 'or' ); ?>><?php esc_html_e( 'Any', 'wp-code-snippet' ); ?></option>
									</select>
									<span><?php esc_html_e( 'of the following match:', 'wp-code-snippet' ); ?></span>
								</div>
								<div class="wcs-condition-table-head" aria-hidden="true"><span><?php esc_html_e( 'Condition', 'wp-code-snippet' ); ?></span><span><?php esc_html_e( 'Comparison', 'wp-code-snippet' ); ?></span><span><?php esc_html_e( 'Value', 'wp-code-snippet' ); ?></span><span></span></div>
								<div id="wcs-condition-rows"></div>
								<button type="button" class="wcs-btn wcs-btn-secondary wcs-add-condition"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add condition', 'wp-code-snippet' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Sidebar column -->
			<div>
				<div class="wcs-card">
					<div class="wcs-card-header">
						<h3 class="wcs-card-title"><?php esc_html_e( 'Publish', 'wp-code-snippet' ); ?></h3>
					</div>
					<div class="wcs-card-body">
						<div class="wcs-field">
							<div class="wcs-flex" style="justify-content:space-between;">
								<span class="wcs-label" style="margin:0;"><?php esc_html_e( 'Status', 'wp-code-snippet' ); ?></span>
								<label class="wcs-switch">
									<input type="checkbox" name="status" value="active" <?php checked( 'active', $snippet['status'] ); ?>>
									<span class="wcs-switch-slider"></span>
								</label>
							</div>
							<p class="wcs-hint"><?php esc_html_e( 'PHP snippets are syntax-checked before activation; a broken snippet is auto-disabled if it ever errors live.', 'wp-code-snippet' ); ?></p>
						</div>
						<div class="wcs-field">
							<label class="wcs-label" for="wcs-priority"><?php esc_html_e( 'Priority', 'wp-code-snippet' ); ?></label>
							<input type="number" id="wcs-priority" name="priority" class="wcs-input small-text" value="<?php echo esc_attr( $snippet['priority'] ); ?>" min="1" max="999">
							<p class="wcs-hint"><?php esc_html_e( 'Lower numbers run first when several snippets target the same hook.', 'wp-code-snippet' ); ?></p>
						</div>
					</div>
					<div class="wcs-sticky-bar" style="margin:0;border-radius:0 0 var(--wcs-radius-lg) var(--wcs-radius-lg);">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet' ) ); ?>" class="wcs-btn wcs-btn-ghost wcs-nav-link" data-view="list"><?php esc_html_e( 'Cancel', 'wp-code-snippet' ); ?></a>
						<button type="submit" class="wcs-btn wcs-btn-primary">
							<span class="dashicons dashicons-saved"></span> <?php echo $is_new ? esc_html__( 'Create Snippet', 'wp-code-snippet' ) : esc_html__( 'Update Snippet', 'wp-code-snippet' ); ?>
						</button>
					</div>
				</div>

			</div>
		</div>
	</form>
</div>
