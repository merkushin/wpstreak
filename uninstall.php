<?php
/**
 * Removes the data Inkmeter stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'inkmeter_summary' );
