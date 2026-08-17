<?php
 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wcs-stat">
	<div class="wcs-stat-label"><?php esc_html_e( 'Total Snippets', 'wp-code-snippet' ); ?></div>
	<div class="wcs-stat-value"><?php echo esc_html( $total ); ?></div>
</div>
<div class="wcs-stat">
	<div class="wcs-stat-label"><?php esc_html_e( 'Active', 'wp-code-snippet' ); ?></div>
	<div class="wcs-stat-value success"><?php echo esc_html( $active ); ?></div>
</div>
<div class="wcs-stat">
	<div class="wcs-stat-label"><?php esc_html_e( 'Inactive', 'wp-code-snippet' ); ?></div>
	<div class="wcs-stat-value"><?php echo esc_html( $inactive ); ?></div>
</div>
<div class="wcs-stat">
	<div class="wcs-stat-label"><?php esc_html_e( 'Auto-deactivated (errors)', 'wp-code-snippet' ); ?></div>
	<div class="wcs-stat-value <?php echo $errored > 0 ? 'danger' : ''; ?>"><?php echo esc_html( $errored ); ?></div>
</div>
