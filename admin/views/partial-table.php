<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type_labels     = WCS_Labels::types();
$location_labels = WCS_Labels::locations();
?>
<?php if ( empty( $snippets ) ) : ?>
	<div class="wcs-empty-state">
		<span class="dashicons dashicons-editor-code"></span>
		<p><strong><?php esc_html_e( 'No snippets found', 'wp-code-snippet' ); ?></strong></p>
		<p class="wcs-muted"><?php esc_html_e( 'Try a different filter or search, or create your first snippet.', 'wp-code-snippet' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-add' ) ); ?>" class="wcs-btn wcs-btn-primary wcs-nav-link" data-view="add" style="margin-top:12px;">
			<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Snippet', 'wp-code-snippet' ); ?>
		</a>
	</div>
<?php else : ?>
	<table class="wcs-table">
		<thead>
			<tr>
				<th style="width:32px;"><input type="checkbox" id="wcs-select-all"></th>
				<th><?php esc_html_e( 'Snippet', 'wp-code-snippet' ); ?></th>
				<th style="width:80px;"><?php esc_html_e( 'Type', 'wp-code-snippet' ); ?></th>
				<th style="width:160px;"><?php esc_html_e( 'Location', 'wp-code-snippet' ); ?></th>
				<th style="width:90px;"><?php esc_html_e( 'Runs', 'wp-code-snippet' ); ?></th>
				<th style="width:140px;"><?php esc_html_e( 'Status', 'wp-code-snippet' ); ?></th>
				<th style="width:110px;"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $snippets as $snippet ) : ?>
				<tr data-title="<?php echo esc_attr( $snippet['title'] ); ?>" data-id="<?php echo esc_attr( $snippet['id'] ); ?>">
					<td><input type="checkbox" class="wcs-row-checkbox" value="<?php echo esc_attr( $snippet['id'] ); ?>"></td>
					<td>
						<a class="wcs-cell-title wcs-nav-link" data-view="edit" data-id="<?php echo esc_attr( $snippet['id'] ); ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-add&id=' . $snippet['id'] ) ); ?>">
							<?php echo esc_html( $snippet['title'] ); ?>
						</a>
						<?php if ( ! empty( $snippet['description'] ) ) : ?>
							<div class="wcs-cell-desc"><?php echo esc_html( wp_trim_words( $snippet['description'], 14 ) ); ?></div>
						<?php endif; ?>
						<?php if ( 'auto-deactivated' === $snippet['status'] && ! empty( $snippet['error_message'] ) ) : ?>
							<div class="wcs-cell-desc" style="color:var(--wcs-danger-foreground);">
								<span class="dashicons dashicons-warning" style="font-size:13px;width:13px;height:13px;"></span>
								<?php echo esc_html( $snippet['error_message'] ); ?>
							</div>
						<?php endif; ?>
					</td>
					<td>
						<span class="wcs-badge <?php echo esc_attr( $type_labels[ $snippet['type'] ]['class'] ?? 'wcs-badge-neutral' ); ?>">
							<?php echo esc_html( $type_labels[ $snippet['type'] ]['label'] ?? strtoupper( $snippet['type'] ) ); ?>
						</span>
					</td>
					<td class="wcs-muted"><?php echo esc_html( $location_labels[ $snippet['location'] ] ?? $snippet['location'] ); ?></td>
					<td class="wcs-muted"><?php echo esc_html( number_format_i18n( $snippet['run_count'] ) ); ?></td>
					<td>
						<div class="wcs-flex">
							<label class="wcs-switch">
								<input type="checkbox" class="wcs-status-toggle" data-id="<?php echo esc_attr( $snippet['id'] ); ?>" <?php checked( 'active', $snippet['status'] ); ?> <?php disabled( 'auto-deactivated', $snippet['status'] ); ?>>
								<span class="wcs-switch-slider"></span>
							</label>
							<?php if ( 'auto-deactivated' === $snippet['status'] ) : ?>
								<span class="wcs-badge wcs-badge-danger wcs-status-badge"><span class="wcs-badge-dot"></span><?php esc_html_e( 'Error', 'wp-code-snippet' ); ?></span>
							<?php elseif ( 'active' === $snippet['status'] ) : ?>
								<span class="wcs-badge wcs-badge-success wcs-status-badge"><span class="wcs-badge-dot"></span><?php esc_html_e( 'Active', 'wp-code-snippet' ); ?></span>
							<?php else : ?>
								<span class="wcs-badge wcs-badge-neutral wcs-status-badge"><span class="wcs-badge-dot"></span><?php esc_html_e( 'Inactive', 'wp-code-snippet' ); ?></span>
							<?php endif; ?>
						</div>
					</td>
					<td>
						<div class="wcs-row-actions">
							<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-add&id=' . $snippet['id'] ) ); ?>" class="wcs-btn wcs-btn-ghost wcs-btn-sm wcs-nav-link" data-view="edit" data-id="<?php echo esc_attr( $snippet['id'] ); ?>">
								<span class="dashicons dashicons-edit"></span>
							</a>
							<button type="button" class="wcs-btn wcs-btn-ghost wcs-btn-sm wcs-delete-snippet" data-id="<?php echo esc_attr( $snippet['id'] ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="wcs-pagination">
			<button type="button" class="wcs-btn wcs-btn-outline wcs-btn-sm wcs-page-btn" data-page="<?php echo esc_attr( max( 1, $paged - 1 ) ); ?>" <?php disabled( 1, $paged ); ?>>
				<span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e( 'Previous', 'wp-code-snippet' ); ?>
			</button>
			<span class="wcs-muted" style="font-size:12.5px;">
				<?php
				printf(
					 
					esc_html__( 'Page %1$d of %2$d', 'wp-code-snippet' ),
					(int) $paged,
					(int) $total_pages
				);
				?>
			</span>
			<button type="button" class="wcs-btn wcs-btn-outline wcs-btn-sm wcs-page-btn" data-page="<?php echo esc_attr( min( $total_pages, $paged + 1 ) ); ?>" <?php disabled( $paged, $total_pages ); ?>>
				<?php esc_html_e( 'Next', 'wp-code-snippet' ); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
			</button>
		</div>
	<?php endif; ?>
<?php endif; ?>
