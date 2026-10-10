<?php declare( strict_types=1 );

namespace Merkushin\Inkmeter;

defined( 'ABSPATH' ) || exit;

/**
 * Computes the writing streak from the days posts were published on.
 *
 * Pure logic with no WordPress dependencies: dates in, summary out. With a
 * daily goal the streak counts days in a row; with a weekly goal it counts
 * weeks in a row in which the goal's number of days had a post.
 */
class StreakCalculator {
	private const DAY_MILESTONES = [ 3, 7, 14, 30, 50, 100 ];

	private const DAY_MILESTONE_STEP = 25;

	private const WEEK_MILESTONES = [ 2, 4, 8, 12, 26, 52 ];

	private const WEEK_MILESTONE_STEP = 26;

	/**
	 * @param string[]  $dates      Days with at least one published post (Y-m-d, site timezone). Any order, duplicates allowed.
	 * @param string    $today      Today in the site timezone (Y-m-d).
	 * @param Goal|null $goal       Daily when null.
	 * @param int       $week_start First day of the week, 0 (Sunday) to 6, as WordPress' start_of_week option.
	 *
	 * @return array{
	 *     streak: int,
	 *     unit: string,
	 *     last_post_date: ?string,
	 *     is_active_today: bool,
	 *     is_goal_met: bool,
	 *     goal_days: int,
	 *     period_days: int,
	 *     days_left: int,
	 *     next_milestone: int
	 * } unit is 'day' or 'week'. is_goal_met, period_days and days_left describe the current period
	 *   (today, or this week): whether its goal is met, how many days in it had a post so far, and
	 *   how many days of it are left including today.
	 */
	public function calculate( array $dates, string $today, ?Goal $goal = null, int $week_start = 1 ): array {
		$goal = $goal ?? Goal::daily();

		$days = [];
		foreach ( $dates as $date ) {
			if ( $date <= $today ) {
				$days[ $date ] = true;
			}
		}

		$last_post_date  = empty( $days ) ? null : (string) max( array_keys( $days ) );
		$is_active_today = isset( $days[ $today ] );

		$summary = $goal->is_weekly()
			? $this->weekly( $days, $today, $goal->days(), $week_start )
			: $this->daily( $days, $today, $is_active_today );

		return [
			'streak'          => $summary['streak'],
			'unit'            => $summary['unit'],
			'last_post_date'  => $last_post_date,
			'is_active_today' => $is_active_today,
			'is_goal_met'     => $summary['is_goal_met'],
			'goal_days'       => $goal->days(),
			'period_days'     => $summary['period_days'],
			'days_left'       => $summary['days_left'],
			'next_milestone'  => $this->next_milestone( $summary['streak'], $goal->is_weekly() ),
		];
	}

	/**
	 * @param array<string, true> $days
	 *
	 * @return array{streak: int, unit: string, is_goal_met: bool, period_days: int, days_left: int}
	 */
	private function daily( array $days, string $today, bool $is_active_today ): array {
		// The streak survives until the end of the day after the last post.
		$day    = $is_active_today ? $today : $this->add_days( $today, -1 );
		$streak = 0;
		while ( isset( $days[ $day ] ) ) {
			++$streak;
			$day = $this->add_days( $day, -1 );
		}

		return [
			'streak'      => $streak,
			'unit'        => 'day',
			'is_goal_met' => $is_active_today,
			'period_days' => $is_active_today ? 1 : 0,
			'days_left'   => 1,
		];
	}

	/**
	 * @param array<string, true> $days
	 *
	 * @return array{streak: int, unit: string, is_goal_met: bool, period_days: int, days_left: int}
	 */
	private function weekly( array $days, string $today, int $goal_days, int $week_start ): array {
		$offset          = ( (int) $this->date( $today )->format( 'w' ) - $week_start + 7 ) % 7;
		$this_week_start = $this->add_days( $today, -$offset );

		$this_week   = $this->days_in_week( $days, $this_week_start );
		$is_goal_met = $this_week >= $goal_days;

		// Like a day, this week keeps the streak alive until it's over.
		$streak     = $is_goal_met ? 1 : 0;
		$week_start = $this->add_days( $this_week_start, -7 );
		while ( $this->days_in_week( $days, $week_start ) >= $goal_days ) {
			++$streak;
			$week_start = $this->add_days( $week_start, -7 );
		}

		return [
			'streak'      => $streak,
			'unit'        => 'week',
			'is_goal_met' => $is_goal_met,
			'period_days' => $this_week,
			'days_left'   => 7 - $offset,
		];
	}

	/**
	 * @param array<string, true> $days
	 */
	private function days_in_week( array $days, string $week_start ): int {
		$count = 0;
		for ( $i = 0; $i < 7; $i++ ) {
			if ( isset( $days[ $this->add_days( $week_start, $i ) ] ) ) {
				++$count;
			}
		}

		return $count;
	}

	private function add_days( string $date, int $days ): string {
		return $this->date( $date )->modify( sprintf( '%+d day', $days ) )->format( 'Y-m-d' );
	}

	private function date( string $date ): \DateTimeImmutable {
		return new \DateTimeImmutable( $date, new \DateTimeZone( 'UTC' ) );
	}

	private function next_milestone( int $streak, bool $in_weeks ): int {
		foreach ( $in_weeks ? self::WEEK_MILESTONES : self::DAY_MILESTONES as $milestone ) {
			if ( $streak < $milestone ) {
				return $milestone;
			}
		}

		$step = $in_weeks ? self::WEEK_MILESTONE_STEP : self::DAY_MILESTONE_STEP;

		return ( intdiv( $streak, $step ) + 1 ) * $step;
	}
}
