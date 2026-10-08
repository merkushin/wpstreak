<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

defined( 'ABSPATH' ) || exit;

/**
 * Computes the writing streak from the days posts were published on.
 *
 * Pure logic with no WordPress dependencies: dates in, summary out.
 */
class StreakCalculator {
	private const MILESTONES = [ 3, 7, 14, 30, 50, 100 ];

	private const MILESTONE_STEP = 25;

	/**
	 * @param string[] $dates Days with at least one published post (Y-m-d, site timezone). Any order, duplicates allowed.
	 * @param string   $today Today in the site timezone (Y-m-d).
	 *
	 * @return array{streak: int, last_post_date: ?string, is_active_today: bool, next_milestone: int}
	 */
	public function calculate( array $dates, string $today ): array {
		$days = [];
		foreach ( $dates as $date ) {
			if ( $date <= $today ) {
				$days[ $date ] = true;
			}
		}

		$last_post_date  = empty( $days ) ? null : (string) max( array_keys( $days ) );
		$is_active_today = isset( $days[ $today ] );

		// The streak survives until the end of the day after the last post.
		$day    = $is_active_today ? $today : $this->previous_day( $today );
		$streak = 0;
		while ( isset( $days[ $day ] ) ) {
			++$streak;
			$day = $this->previous_day( $day );
		}

		return [
			'streak'          => $streak,
			'last_post_date'  => $last_post_date,
			'is_active_today' => $is_active_today,
			'next_milestone'  => $this->next_milestone( $streak ),
		];
	}

	private function previous_day( string $date ): string {
		return ( new \DateTimeImmutable( $date, new \DateTimeZone( 'UTC' ) ) )->modify( '-1 day' )->format( 'Y-m-d' );
	}

	private function next_milestone( int $streak ): int {
		foreach ( self::MILESTONES as $milestone ) {
			if ( $streak < $milestone ) {
				return $milestone;
			}
		}

		return ( intdiv( $streak, self::MILESTONE_STEP ) + 1 ) * self::MILESTONE_STEP;
	}
}
