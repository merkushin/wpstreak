<?php
/**
 * Removes the data Streakfire stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_transient( 'streakfire_summary' );

// Streakfire Pro. Revoke the site's token, best effort, so it can't be used again.
$streakfire_connection = get_option( 'streakfire_connection' );
if ( is_array( $streakfire_connection ) && ! empty( $streakfire_connection['token'] ) && is_string( $streakfire_connection['token'] ) ) {
	$streakfire_api_url = defined( 'STREAKFIRE_API_URL' ) ? (string) STREAKFIRE_API_URL : 'https://api.streakfire.org';
	wp_remote_request(
		$streakfire_api_url . '/v1/site',
		[
			'method'  => 'DELETE',
			'timeout' => 5,
			'headers' => [ 'Authorization' => 'Bearer ' . $streakfire_connection['token'] ],
		]
	);
}
delete_option( 'streakfire_connection' );
wp_clear_scheduled_hook( 'streakfire_sync_days' );
wp_clear_scheduled_hook( 'streakfire_sync_days_daily' );
