<?php
/**
 * Removes the data Writing Streak stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'writing_streak_summary' );
