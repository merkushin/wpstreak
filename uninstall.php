<?php
/**
 * Removes the data Inkstreak stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'inkstreak_summary' );
