<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\PostTypes;
use Merkushin\Wpal\Service\Transient;
use Merkushin\Wpal\ServiceFactory;

class Streak {
  /**
   * @var string
   */
	private const TRANSIENT_KEY = 'writing_streak_summary';

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
		$summary = $this->get_summary();

		return $summary['streak'];
	}

	public function get_summary(): array {
		global $wpdb;

		$summary = $this->transient->get_transient( self::TRANSIENT_KEY );

		if ( $summary !== false && is_array( $summary ) ) {
			return $summary;
		}

		// Query published post dates
		$results = $wpdb->get_col("
			SELECT DATE(post_date) 
			FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND post_type = 'post' 
			ORDER BY post_date DESC
			");

		$today = \date( 'Y-m-d' );
		$yesterday = \date( 'Y-m-d', \strtotime( '-1 day' ) );
		$last_post_date = empty( $results ) ? null : $results[0];

		if ( empty( $results ) ) {
			$summary = [
				'streak' => 0,
				'last_post_date' => null,
				'is_active_today' => false,
				'status' => 'Start your next streak',
				'next_milestone' => 3,
			];

			$this->transient->set_transient( self::TRANSIENT_KEY, $summary, HOUR_IN_SECONDS );

			return $summary;
		}

		$streak = 0;
		$previous_date = null;

		foreach ( $results as $date ) {
			if ( $streak === 0 ) {
				if ( $date === $today || $date === $yesterday ) {
					$streak++;
					$previous_date = $date;
				} else {
					break;
				}
			} else {
				if ( $date === \date( 'Y-m-d', \strtotime( "{$previous_date} -1 day" ) ) ) {
					$streak++;
					$previous_date = $date;
				} else {
					break;
				}
			}
		}

		$is_active_today = $last_post_date === $today;

		if ( $streak === 0 ) {
			$status = 'Start your next streak';
		} elseif ( $is_active_today ) {
			$status = 'You are on fire today';
		} else {
			$status = 'You are still alive, publish today to keep it going';
		}

		$summary = [
			'streak' => $streak,
			'last_post_date' => $last_post_date,
			'is_active_today' => $is_active_today,
			'status' => $status,
			'next_milestone' => $this->get_next_milestone( $streak ),
		];

		$this->transient->set_transient( self::TRANSIENT_KEY, $summary, HOUR_IN_SECONDS );

		return $summary;
	}

	public function clear_cache( $post_id ) {
		if ( ! is_null( $post_id ) && $this->post_types->get_post_type( $post_id ) === 'post' ) {
			$this->transient->delete_transient( self::TRANSIENT_KEY );
		}
	}

	private function get_next_milestone( int $streak ): int {
		$milestones = [ 3, 7, 14, 30, 50, 100 ];

		foreach ( $milestones as $milestone ) {
			if ( $streak < $milestone ) {
				return $milestone;
			}
		}

		return ( (int) \floor( $streak / 25 ) + 1 ) * 25;
	}
}
