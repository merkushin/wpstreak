<?php declare( strict_types=1 );

namespace MerkushinTest\Inkstreak;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Localization;
use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\Service\Screen;
use Merkushin\Wpal\Service\UserSettings;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Inkstreak\Streak;
use Merkushin\Inkstreak\Plugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase {
	private const PLUGIN_FILE = '/plugins/inkstreak/inkstreak.php';

	private const PLUGIN_URL = 'https://example.com/wp-content/plugins/inkstreak/';

	/**
	 * @var Assets&MockObject
	 */
	private $assets;

	/**
	 * @var Screen&MockObject
	 */
	private $screen;

	/**
	 * @var Localization&MockObject
	 */
	private $localization;

	/**
	 * @var Dates&MockObject
	 */
	private $dates;

	/**
	 * @var UserSettings&MockObject
	 */
	private $user_settings;

	/**
	 * @var Streak&MockObject
	 */
	private $streak;

	protected function setUp(): void {
		$this->assets       = $this->createMock( Assets::class );
		$this->screen       = $this->createMock( Screen::class );
		$this->localization = $this->createMock( Localization::class );
		$this->dates        = $this->createMock( Dates::class );
		$this->streak       = $this->createMock( Streak::class );

		$this->user_settings = $this->createMock( UserSettings::class );
		$this->user_settings->method( 'get_user_setting' )->with( Plugin::PANEL_SETTING, 'on' )->willReturn( 'on' );

		$plugins = $this->createMock( Plugins::class );
		$plugins->method( 'plugin_dir_url' )->with( self::PLUGIN_FILE )->willReturn( self::PLUGIN_URL );

		$options = $this->createMock( Options::class );
		$options->method( 'get_option' )->with( 'date_format' )->willReturn( 'd.m.Y' );

		ServiceFactory::set_custom_assets( $this->assets );
		ServiceFactory::set_custom_screen( $this->screen );
		ServiceFactory::set_custom_plugins( $plugins );
		ServiceFactory::set_custom_localization( $this->localization );
		ServiceFactory::set_custom_dates( $this->dates );
		ServiceFactory::set_custom_options( $options );
		ServiceFactory::set_custom_user_settings( $this->user_settings );
	}

	protected function tearDown(): void {
		ServiceFactory::set_custom_assets( null );
		ServiceFactory::set_custom_screen( null );
		ServiceFactory::set_custom_plugins( null );
		ServiceFactory::set_custom_localization( null );
		ServiceFactory::set_custom_dates( null );
		ServiceFactory::set_custom_options( null );
		ServiceFactory::set_custom_user_settings( null );
		ServiceFactory::set_custom_hooks( null );
	}

	public function testInit_Always_RegistersAdminHooksAndInitsStreak(): void {
		$hooks = $this->createMock( Hooks::class );
		ServiceFactory::set_custom_hooks( $hooks );
		$plugin = new Plugin( self::PLUGIN_FILE, $this->streak );

		$registered = [];
		$record     = function ( string $hook, $callback ) use ( &$registered ): bool {
			$registered[ $hook ] = $callback;
			return true;
		};
		$hooks->method( 'add_action' )->willReturnCallback( $record );
		$hooks->method( 'add_filter' )->willReturnCallback( $record );
		$this->streak->expects( $this->once() )->method( 'init' );

		$plugin->init();

		$this->assertEquals(
			[
				'admin_enqueue_scripts' => [ $plugin, 'enqueue_admin_assets' ],
				'all_admin_notices'     => [ $plugin, 'render_streak_panel' ],
				'screen_settings'       => [ $plugin, 'add_screen_option' ],
			],
			$registered
		);
	}

	public function testEnqueueAdminAssets_PostsScreen_EnqueuesAssets(): void {
		$this->screen->method( 'get_current_screen' )->willReturn( (object) [ 'id' => 'edit-post' ] );

		$this->assets
			->expects( $this->once() )
			->method( 'wp_enqueue_style' )
			->with( 'inkstreak-admin', self::PLUGIN_URL . 'assets/dist/styles/admin.css', [], Plugin::VERSION );
		$this->assets
			->expects( $this->once() )
			->method( 'wp_enqueue_script' )
			->with( 'inkstreak-admin', self::PLUGIN_URL . 'assets/dist/javascript/admin.js', [ 'utils' ], Plugin::VERSION, true );

		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->enqueue_admin_assets();
	}

	/**
	 * @dataProvider provide_other_screens
	 *
	 * @param object|null $screen
	 */
	public function testEnqueueAdminAssets_OtherScreen_EnqueuesNothing( $screen ): void {
		$this->screen->method( 'get_current_screen' )->willReturn( $screen );

		$this->assets->expects( $this->never() )->method( 'wp_enqueue_style' );
		$this->assets->expects( $this->never() )->method( 'wp_enqueue_script' );

		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->enqueue_admin_assets();
	}

	/**
	 * @dataProvider provide_other_screens
	 *
	 * @param object|null $screen
	 */
	public function testRenderStreakPanel_OtherScreen_RendersNothing( $screen ): void {
		$this->screen->method( 'get_current_screen' )->willReturn( $screen );

		$this->streak->expects( $this->never() )->method( 'get_summary' );

		$this->expectOutputString( '' );
		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->render_streak_panel();
	}

	public function testRenderStreakPanel_PostsScreen_RendersLocalizedSummary(): void {
		$this->screen->method( 'get_current_screen' )->willReturn( (object) [ 'id' => 'edit-post' ] );
		$this->streak->method( 'get_summary' )->willReturn(
			[
				'streak'          => 1234,
				'last_post_date'  => '2026-03-01',
				'is_active_today' => false,
				'next_milestone'  => 1250,
			]
		);
		$this->localization->method( 'number_format_i18n' )->willReturnCallback(
			function ( $number ): string {
				return number_format( (float) $number, 0, ',', '.' );
			}
		);
		$this->dates
			->method( 'wp_date' )
			->willReturnCallback(
				function ( string $format, int $timestamp, \DateTimeZone $timezone ): string {
					return ( new \DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
				}
			);

		ob_start();
		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->render_streak_panel();
		$text = trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) ob_get_clean() ) ) );

		$this->assertStringContainsString( 'You are still alive, publish today to keep it going', $text );
		$this->assertStringContainsString( 'Current run 1.234 days', $text );
		$this->assertStringContainsString( 'Last published 01.03.2026', $text );
		$this->assertStringContainsString( 'Next milestone 1.250 days', $text );
		$this->assertStringContainsString( 'Needs a post today', $text );
		$this->assertStringContainsString( 'Milestone progress 99%', $text );
	}

	public function testRenderStreakPanel_NoPosts_RendersEmptyState(): void {
		$this->screen->method( 'get_current_screen' )->willReturn( (object) [ 'id' => 'edit-post' ] );
		$this->streak->method( 'get_summary' )->willReturn(
			[
				'streak'          => 0,
				'last_post_date'  => null,
				'is_active_today' => false,
				'next_milestone'  => 3,
			]
		);
		$this->localization->method( 'number_format_i18n' )->willReturnCallback(
			function ( $number ): string {
				return (string) $number;
			}
		);
		$this->dates->expects( $this->never() )->method( 'wp_date' );

		ob_start();
		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->render_streak_panel();
		$text = trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) ob_get_clean() ) ) );

		$this->assertStringContainsString( 'Start your next streak', $text );
		$this->assertStringContainsString( 'Current run 0 days', $text );
		$this->assertStringContainsString( 'Last published No published posts yet', $text );
		$this->assertStringContainsString( 'Milestone progress 0%', $text );
	}

	public function testRenderStreakPanel_PanelVisible_RendersWithoutHiddenAttribute(): void {
		$html = $this->render_panel();

		$this->assertStringContainsString( 'id="inkstreak-panel"', $html );
		$this->assertStringNotContainsString( ' hidden', $html );
	}

	public function testRenderStreakPanel_PanelHiddenInScreenOptions_RendersHidden(): void {
		$this->use_panel_setting( 'off' );

		$this->assertMatchesRegularExpression( '/<div id="inkstreak-panel"[^>]* hidden>/', $this->render_panel() );
	}

	public function testAddScreenOption_PostsScreen_AppendsCheckedToggle(): void {
		$settings = ( new Plugin( self::PLUGIN_FILE, $this->streak ) )->add_screen_option( '<p>core</p>', (object) [ 'id' => 'edit-post' ] );

		$this->assertStringStartsWith( '<p>core</p>', $settings );
		$this->assertStringContainsString( 'id="inkstreak-panel-toggle"', $settings );
		$this->assertStringContainsString( "checked='checked'", $settings );
		$this->assertStringContainsString( 'Writing streak panel', $settings );
	}

	public function testAddScreenOption_PanelHidden_AppendsUncheckedToggle(): void {
		$this->use_panel_setting( 'off' );

		$settings = ( new Plugin( self::PLUGIN_FILE, $this->streak ) )->add_screen_option( '', (object) [ 'id' => 'edit-post' ] );

		$this->assertStringContainsString( 'id="inkstreak-panel-toggle"', $settings );
		$this->assertStringNotContainsString( 'checked', $settings );
	}

	/**
	 * @dataProvider provide_other_screens
	 *
	 * @param object|null $screen
	 */
	public function testAddScreenOption_OtherScreen_KeepsSettingsUnchanged( $screen ): void {
		$settings = ( new Plugin( self::PLUGIN_FILE, $this->streak ) )->add_screen_option( '<p>core</p>', $screen );

		$this->assertSame( '<p>core</p>', $settings );
	}

	private function use_panel_setting( string $value ): void {
		$this->user_settings = $this->createMock( UserSettings::class );
		$this->user_settings->method( 'get_user_setting' )->with( Plugin::PANEL_SETTING, 'on' )->willReturn( $value );
		ServiceFactory::set_custom_user_settings( $this->user_settings );
	}

	private function render_panel(): string {
		$this->screen->method( 'get_current_screen' )->willReturn( (object) [ 'id' => 'edit-post' ] );
		$this->streak->method( 'get_summary' )->willReturn(
			[
				'streak'          => 2,
				'last_post_date'  => null,
				'is_active_today' => true,
				'next_milestone'  => 3,
			]
		);
		$this->localization->method( 'number_format_i18n' )->willReturnCallback(
			function ( $number ): string {
				return (string) $number;
			}
		);

		ob_start();
		( new Plugin( self::PLUGIN_FILE, $this->streak ) )->render_streak_panel();

		return (string) ob_get_clean();
	}

	public function provide_other_screens(): array {
		return [
			'pages list' => [ (object) [ 'id' => 'edit-page' ] ],
			'dashboard'  => [ (object) [ 'id' => 'dashboard' ] ],
			'no screen'  => [ null ],
		];
	}
}
