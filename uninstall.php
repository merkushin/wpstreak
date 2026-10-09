<?php
/**
 * Removes the data Streakfire stores when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// A closure keeps these variables out of the global scope.
( static function (): void {
	delete_transient( 'streakfire_summary' );

	// Streakfire Pro. Revoke the site's token, best effort, so it can't be used again.
	$connection = get_option( 'streakfire_connection' );
	if ( is_array( $connection ) && ! empty( $connection['token'] ) && is_string( $connection['token'] ) ) {
		$api_url = defined( 'STREAKFIRE_API_URL' ) ? (string) STREAKFIRE_API_URL : 'https://api.streakfire.org';
		wp_remote_request(
			$api_url . '/v1/site',
			[
				'method'  => 'DELETE',
				'timeout' => 5,
				'headers' => [ 'Authorization' => 'Bearer ' . $connection['token'] ],
			]
		);
	}
	delete_option( 'streakfire_connection' );
	wp_clear_scheduled_hook( 'streakfire_sync_days' );
	wp_clear_scheduled_hook( 'streakfire_sync_days_daily' );
} )();
