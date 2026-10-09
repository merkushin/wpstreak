<?php
/**
 * Removes the data Streakfire stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'streakfire_summary' );
