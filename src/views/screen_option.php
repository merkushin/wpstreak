<?php
/**
 * Screen Options checkbox that shows or hides the streak panel.
 *
 * @var bool $is_panel_visible
 */

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="metabox-prefs">
	<legend><?php esc_html_e( 'Streakfire', 'streakfire' ); ?></legend>
	<label for="streakfire-panel-toggle">
		<input type="checkbox" id="streakfire-panel-toggle"<?php checked( $is_panel_visible ); ?> />
		<?php esc_html_e( 'Writing streak panel', 'streakfire' ); ?>
	</label>
</fieldset>
