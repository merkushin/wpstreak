<?php declare( strict_types=1 );

namespace Merkushin\Inkstreak;

use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\PostTypes;
use Merkushin\Wpal\Service\Transient;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

class Streak {
	public const TRANSIENT_KEY = 'inkstreak_summary';

	private const CACHE_TTL = 86400;

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

	/**
	 * @var Dates
	 */
	private $dates;

	/**
	 * @var PublishedPostDates
	 */
	private $post_dates;

	/**
	 * @var StreakCalculator
	 */
	private $calculator;

	public function __construct( ?PublishedPostDates $post_dates = null, ?StreakCalculator $calculator = null ) {
		$this->hooks      = ServiceFactory::create_hooks();
		$this->transient  = ServiceFactory::create_transient();
		$this->post_types = ServiceFactory::create_post_types();
		$this->dates      = ServiceFactory::create_dates();
		$this->post_dates = $post_dates ?? new PublishedPostDates();
		$this->calculator = $calculator ?? new StreakCalculator();
	}

	public function init(): void {
		$this->hooks->add_action( 'save_post', [ $this, 'clear_cache' ] );
		$this->hooks->add_action( 'delete_post', [ $this, 'clear_cache' ] );
	}

	/**
	 * @return array{streak: int, last_post_date: ?string, is_active_today: bool, next_milestone: int}
	 */
	public function get_summary(): array {
		$today = (string) $this->dates->current_time( 'Y-m-d' );

		// The cache is tied to the day it was computed on, so the streak is never stale after midnight.
		$cached = $this->transient->get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) && ( $cached['date'] ?? null ) === $today && is_array( $cached['summary'] ?? null ) ) {
			return $cached['summary'];
		}

		$summary = $this->calculator->calculate( $this->post_dates->get_dates(), $today );

		$this->transient->set_transient(
			self::TRANSIENT_KEY,
			[
				'date'    => $today,
				'summary' => $summary,
			],
			self::CACHE_TTL
		);

		return $summary;
	}

	/**
	 * @param int|string $post_id
	 */
	public function clear_cache( $post_id ): void {
		if ( 'post' === $this->post_types->get_post_type( (int) $post_id ) ) {
			$this->transient->delete_transient( self::TRANSIENT_KEY );
		}
	}
}
