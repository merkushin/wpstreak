<?php declare( strict_types=1 );

namespace MerkushinTest\Wpstreak;

use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\PostTypes;
use Merkushin\Wpal\Service\Transient;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpstreak\PublishedPostDates;
use Merkushin\Wpstreak\Streak;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreakTest extends TestCase {
	/**
	 * @var Transient&MockObject
	 */
	private $transient;

	/**
	 * @var PostTypes&MockObject
	 */
	private $post_types;

	/**
	 * @var PublishedPostDates&MockObject
	 */
	private $post_dates;

	protected function setUp(): void {
		$this->transient  = $this->createMock( Transient::class );
		$this->post_types = $this->createMock( PostTypes::class );
		$this->post_dates = $this->createMock( PublishedPostDates::class );

		$dates = $this->createMock( Dates::class );
		$dates->method( 'current_time' )->with( 'Y-m-d' )->willReturn( '2026-03-02' );

		ServiceFactory::set_custom_transient( $this->transient );
		ServiceFactory::set_custom_post_types( $this->post_types );
		ServiceFactory::set_custom_dates( $dates );
	}

	protected function tearDown(): void {
		ServiceFactory::set_custom_transient( null );
		ServiceFactory::set_custom_post_types( null );
		ServiceFactory::set_custom_dates( null );
		ServiceFactory::set_custom_hooks( null );
	}

	public function testInit_Always_ClearsCacheOnPostChanges(): void {
		$hooks = $this->createMock( Hooks::class );
		ServiceFactory::set_custom_hooks( $hooks );
		$streak = new Streak( $this->post_dates );

		$registered = [];
		$hooks->method( 'add_action' )->willReturnCallback(
			function ( string $hook, $callback ) use ( &$registered ): bool {
				$registered[ $hook ] = $callback;
				return true;
			}
		);

		$streak->init();

		$this->assertEquals(
			[
				'save_post'   => [ $streak, 'clear_cache' ],
				'delete_post' => [ $streak, 'clear_cache' ],
			],
			$registered
		);
	}

	public function testGetSummary_CachedToday_ReturnsCachedSummary(): void {
		$cached = [
			'streak'          => 5,
			'last_post_date'  => '2026-03-02',
			'is_active_today' => true,
			'next_milestone'  => 7,
		];
		$this->transient->method( 'get_transient' )->willReturn(
			[
				'date'    => '2026-03-02',
				'summary' => $cached,
			]
		);

		$this->post_dates->expects( $this->never() )->method( 'get_dates' );

		$this->assertSame( $cached, ( new Streak( $this->post_dates ) )->get_summary() );
	}

	public function testGetSummary_CachedYesterday_Recalculates(): void {
		$stale = [
			'streak'          => 5,
			'last_post_date'  => '2026-03-01',
			'is_active_today' => true,
			'next_milestone'  => 7,
		];
		$this->transient->method( 'get_transient' )->willReturn(
			[
				'date'    => '2026-03-01',
				'summary' => $stale,
			]
		);
		$this->post_dates->method( 'get_dates' )->willReturn( [ '2026-03-01' ] );

		$summary = ( new Streak( $this->post_dates ) )->get_summary();

		$this->assertSame( 1, $summary['streak'] );
		$this->assertFalse( $summary['is_active_today'] );
	}

	public function testGetSummary_NotCached_StoresSummaryWithToday(): void {
		$this->transient->method( 'get_transient' )->willReturn( false );
		$this->post_dates->method( 'get_dates' )->willReturn( [ '2026-03-02', '2026-03-01' ] );

		$this->transient
			->expects( $this->once() )
			->method( 'set_transient' )
			->with(
				Streak::TRANSIENT_KEY,
				[
					'date'    => '2026-03-02',
					'summary' => [
						'streak'          => 2,
						'last_post_date'  => '2026-03-02',
						'is_active_today' => true,
						'next_milestone'  => 3,
					],
				],
				$this->greaterThan( 0 )
			)
			->willReturn( true );

		( new Streak( $this->post_dates ) )->get_summary();
	}

	public function testClearCache_Post_DeletesTransient(): void {
		$this->post_types->method( 'get_post_type' )->with( 42 )->willReturn( 'post' );

		$this->transient->expects( $this->once() )->method( 'delete_transient' )->with( Streak::TRANSIENT_KEY );

		( new Streak( $this->post_dates ) )->clear_cache( 42 );
	}

	public function testClearCache_OtherPostType_KeepsTransient(): void {
		$this->post_types->method( 'get_post_type' )->willReturn( 'page' );

		$this->transient->expects( $this->never() )->method( 'delete_transient' );

		( new Streak( $this->post_dates ) )->clear_cache( 42 );
	}
}
