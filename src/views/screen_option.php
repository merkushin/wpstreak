<?php
/**
 * Screen Options checkbox that shows or hides the streak panel.
 *
 * @var bool $is_panel_visible
 */

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="metabox-prefs">
	<legend><?php esc_html_e( 'Inkmeter', 'inkmeter' ); ?></legend>
	<label for="inkmeter-panel-toggle">
		<input type="checkbox" id="inkmeter-panel-toggle"<?php checked( $is_panel_visible ); ?> />
		<?php esc_html_e( 'Writing streak panel', 'inkmeter' ); ?>
	</label>
</fieldset>
