<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\PostTypes;
use Merkushin\Wpal\Service\Transient;
use Merkushin\Wpal\ServiceFactory;

class Streak {
	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Transient
	 */
	private $transient;

	/**
	 * @var PostTypes
	 */
	private $post_types;

	public function __construct() {
		$this->hooks = ServiceFactory::create_hooks();
		$this->transient = ServiceFactory::create_transient();
		$this->post_types = ServiceFactory::create_post_types();
	}

	public function init() {
		$this->hooks->add_action( 'save_post', [ $this, 'clear_cache' ] );
		$this->hooks->add_action( 'delete_post', [ $this, 'clear_cache' ] );
	}

	public function get_streak() {
		global $wpdb;

		// Try to get cached streak value from transient
		$streak = $this->transient->get_transient('writing_streak');

		if ($streak !== false) {
			return $streak; // Return cached value if available
		}

		// Query published post dates
		$results = $wpdb->get_col("
			SELECT DATE(post_date) 
			FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND post_type = 'post' 
			ORDER BY post_date DESC
			");

		if (empty($results)) {
			return 0; // No posts found
		}

		$streak = 0;
		$previous_date = null;
		$today = date('Y-m-d');

		foreach ($results as $date) {
			if ($streak === 0) {
				if ($date == $today || $date == date('Y-m-d', strtotime('-1 day'))) {
					$streak++;
					$previous_date = $date;
				} else {
					break;
				}
			} else {
				if ($date == date('Y-m-d', strtotime("$previous_date -1 day"))) {
					$streak++;
					$previous_date = $date;
				} else {
					break;
				}
			}
		}

		// Cache the result in a transient for 1 hour
		$this->transient->set_transient('writing_streak', $streak, HOUR_IN_SECONDS);

		return $streak;
	}

	public function clear_cache( $post_id ) {
		if ( ! is_null( $post_id ) && $this->post_types->get_post_type( $post_id ) === 'post' ) {
			$this->transient->delete_transient( 'writing_streak' );
		}
	}
}
