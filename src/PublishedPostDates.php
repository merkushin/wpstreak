<?php declare( strict_types=1 );

namespace Merkushin\Inkstreak;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the days that have at least one published post.
 */
class PublishedPostDates {
	/**
	 * @return string[] Distinct days (Y-m-d, site timezone), newest first.
	 */
	public function get_dates(): array {
		global $wpdb;

		// A single aggregate query served by the type_status_date index; the result is cached by Streak.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$dates = $wpdb->get_col(
			"SELECT DISTINCT DATE(post_date) AS day
			FROM {$wpdb->posts}
			WHERE post_type = 'post' AND post_status = 'publish'
			ORDER BY day DESC"
		);

		return array_map( 'strval', (array) $dates );
	}
}
