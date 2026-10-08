<?php declare( strict_types=1 );

namespace MerkushinTest\Wpstreak;

use Merkushin\Wpstreak\StreakCalculator;
use PHPUnit\Framework\TestCase;

class StreakCalculatorTest extends TestCase {
	private const TODAY = '2026-03-02';

	/**
	 * @dataProvider provide_streaks
	 *
	 * @param string[] $dates
	 */
	public function testCalculate_GivenDates_ReturnsStreak( array $dates, int $expected_streak, bool $expected_active ): void {
		$summary = ( new StreakCalculator() )->calculate( $dates, self::TODAY );

		$this->assertSame( $expected_streak, $summary['streak'] );
		$this->assertSame( $expected_active, $summary['is_active_today'] );
	}

	public function provide_streaks(): array {
		return [
			'no posts' => [ [], 0, false ],
			'only today' => [ [ '2026-03-02' ], 1, true ],
			'only yesterday keeps the streak alive' => [ [ '2026-03-01' ], 1, false ],
			'two days ago breaks the streak' => [ [ '2026-02-28' ], 0, false ],
			'consecutive days across a month boundary' => [ [ '2026-03-02', '2026-03-01', '2026-02-28', '2026-02-27' ], 4, true ],
			'gap stops the count' => [ [ '2026-03-02', '2026-03-01', '2026-02-27' ], 2, true ],
			'consecutive up to yesterday' => [ [ '2026-03-01', '2026-02-28', '2026-02-27' ], 3, false ],
			'duplicate days count once and do not break the streak' => [ [ '2026-03-02', '2026-03-02', '2026-03-01', '2026-03-01', '2026-02-28' ], 3, true ],
			'unsorted input' => [ [ '2026-02-28', '2026-03-02', '2026-03-01' ], 3, true ],
			'future dates are ignored' => [ [ '2026-03-05', '2026-03-01' ], 1, false ],
		];
	}

	public function testCalculate_AcrossLeapDay_CountsEveryDay(): void {
		$summary = ( new StreakCalculator() )->calculate( [ '2024-03-01', '2024-02-29', '2024-02-28' ], '2024-03-01' );

		$this->assertSame( 3, $summary['streak'] );
	}

	public function testCalculate_GivenPosts_ReturnsLatestDate(): void {
		$summary = ( new StreakCalculator() )->calculate( [ '2025-12-01', '2026-01-15', '2025-06-30' ], self::TODAY );

		$this->assertSame( '2026-01-15', $summary['last_post_date'] );
	}

	public function testCalculate_NoPosts_ReturnsNullLastPostDate(): void {
		$summary = ( new StreakCalculator() )->calculate( [], self::TODAY );

		$this->assertNull( $summary['last_post_date'] );
	}

	/**
	 * @dataProvider provide_milestones
	 */
	public function testCalculate_GivenStreak_ReturnsNextMilestone( int $streak, int $expected ): void {
		$dates = [];
		$day = new \DateTimeImmutable( self::TODAY );
		for ( $i = 0; $i < $streak; $i++ ) {
			$dates[] = $day->modify( "-{$i} days" )->format( 'Y-m-d' );
		}

		$summary = ( new StreakCalculator() )->calculate( $dates, self::TODAY );

		$this->assertSame( $streak, $summary['streak'] );
		$this->assertSame( $expected, $summary['next_milestone'] );
	}

	public function provide_milestones(): array {
		return [
			[ 0, 3 ],
			[ 2, 3 ],
			[ 3, 7 ],
			[ 29, 30 ],
			[ 99, 100 ],
			[ 100, 125 ],
			[ 124, 125 ],
			[ 125, 150 ],
		];
	}
}
