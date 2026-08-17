<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_status = sanitize_key( wcs_get_param( 'status', '' ) );
$search         = sanitize_text_field( wcs_get_param( 'search', wcs_get_param( 's', '' ) ) );
$paged          = max( 1, absint( wcs_get_param( 'paged', 1 ) ) );
$per_page       = 20;

$total_filtered = WCS_DB::count_filtered( array( 'status' => $current_status, 'search' => $search ) );
$total_pages    = max( 1, (int) ceil( $total_filtered / $per_page ) );
$paged          = min( $paged, $total_pages );

$snippets = WCS_DB::get_all(
	array(
		'status' => $current_status,
		'search' => $search,
		'number' => $per_page,
		'offset' => ( $paged - 1 ) * $per_page,
	)
);

$total    = WCS_DB::count();
$active   = WCS_DB::count( 'active' );
$inactive = WCS_DB::count( 'inactive' );
$errored  = WCS_DB::count( 'auto-deactivated' );
?>
<div class="wrap wcs-wrap" id="wcs-app-root" data-view="list" data-per-page="<?php echo esc_attr( $per_page ); ?>">

	<div class="wcs-header">
		<div class="wcs-header-left">
			<div class="wcs-logo">
				<span class="dashicons dashicons-editor-code" style="font-size:20px;"></span>
			</div>
			<div>
				<h1 class="wcs-title"><?php esc_html_e( 'WP Code Snippet', 'wp-code-snippet' ); ?></h1>
				<p class="wcs-subtitle"><?php esc_html_e( 'Manage PHP, HTML, CSS and JS snippets without touching your theme files.', 'wp-code-snippet' ); ?></p>
			</div>
		</div>
		<div class="wcs-header-actions">
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-settings' ) ); ?>" class="wcs-btn wcs-btn-outline wcs-nav-link" data-view="settings">
				<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Settings', 'wp-code-snippet' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-code-snippet-add' ) ); ?>" class="wcs-btn wcs-btn-primary wcs-nav-link" data-view="add">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Snippet', 'wp-code-snippet' ); ?>
			</a>
		</div>
	</div>

	<div class="wcs-stats" id="wcs-stats">
		<?php require WCS_PLUGIN_DIR . 'admin/views/partial-stats.php'; ?>
	</div>

	<div class="wcs-card">
		<div class="wcs-toolbar">
			<div class="wcs-filter-pills" id="wcs-filter-pills">
				<?php
				$pills = array(
					''                 => __( 'All', 'wp-code-snippet' ),
					'active'           => __( 'Active', 'wp-code-snippet' ),
					'inactive'         => __( 'Inactive', 'wp-code-snippet' ),
					'auto-deactivated' => __( 'Errors', 'wp-code-snippet' ),
				);
				foreach ( $pills as $key => $label ) :
					?>
					<button type="button" class="wcs-pill wcs-filter-pill <?php echo $current_status === $key ? 'is-active' : ''; ?>" data-status="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="wcs-flex">
				<div class="wcs-search-input">
					<span class="dashicons dashicons-search"></span>
					<input type="text" id="wcs-search-input" class="wcs-input" placeholder="<?php esc_attr_e( 'Search snippets…', 'wp-code-snippet' ); ?>" value="<?php echo esc_attr( $search ); ?>">
				</div>
				<div class="wcs-input-group">
					<select id="wcs-bulk-select" class="wcs-select">
						<option value=""><?php esc_html_e( 'Bulk actions', 'wp-code-snippet' ); ?></option>
						<option value="activate"><?php esc_html_e( 'Activate', 'wp-code-snippet' ); ?></option>
						<option value="deactivate"><?php esc_html_e( 'Deactivate', 'wp-code-snippet' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'wp-code-snippet' ); ?></option>
					</select>
					<button type="button" class="wcs-btn wcs-btn-outline wcs-bulk-apply" disabled>
						<?php esc_html_e( 'Apply', 'wp-code-snippet' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="wcs-card-body no-padding" id="wcs-list-container" data-status="<?php echo esc_attr( $current_status ); ?>" data-search="<?php echo esc_attr( $search ); ?>" data-paged="<?php echo esc_attr( $paged ); ?>">
			<?php require WCS_PLUGIN_DIR . 'admin/views/partial-table.php'; ?>
		</div>
	</div>
</div>
