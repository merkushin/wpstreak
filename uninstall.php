<?php
/**
 * Removes the data WP Streak stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'wpstreak_summary' );
