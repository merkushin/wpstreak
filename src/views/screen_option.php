<?php
/**
 * Screen Options checkbox that shows or hides the streak panel.
 *
 * @var bool $is_panel_visible
 */

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="metabox-prefs">
	<legend><?php esc_html_e( 'Inkstreak', 'inkstreak' ); ?></legend>
	<label for="inkstreak-panel-toggle">
		<input type="checkbox" id="inkstreak-panel-toggle"<?php checked( $is_panel_visible ); ?> />
		<?php esc_html_e( 'Writing streak panel', 'inkstreak' ); ?>
	</label>
</fieldset>
