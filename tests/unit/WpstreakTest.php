<?php declare( strict_types=1 );

namespace MerkushinTest\Wpstreak;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\Service\Screen;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpstreak\Streak;
use Merkushin\Wpstreak\Wpstreak;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WpstreakTest extends TestCase {
	private const PLUGIN_FILE = '/plugins/wpstreak/wpstreak.php';

	private const PLUGIN_URL = 'https://example.com/wp-content/plugins/wpstreak/';

	/**
	 * @var Assets&MockObject
	 */
	private $assets;

	/**
	 * @var Screen&MockObject
	 */
	private $screen;

	/**
	 * @var Streak&MockObject
	 */
	private $streak;

	protected function setUp(): void {
		$this->assets = $this->createMock( Assets::class );
		$this->screen = $this->createMock( Screen::class );
		$this->streak = $this->createMock( Streak::class );

		$plugins = $this->createMock( Plugins::class );
		$plugins->method( 'plugin_dir_url' )->with( self::PLUGIN_FILE )->willReturn( self::PLUGIN_URL );

		ServiceFactory::set_custom_assets( $this->assets );
		ServiceFactory::set_custom_screen( $this->screen );
		ServiceFactory::set_custom_plugins( $plugins );
	}

	protected function tearDown(): void {
		ServiceFactory::set_custom_assets( null );
		ServiceFactory::set_custom_screen( null );
		ServiceFactory::set_custom_plugins( null );
		ServiceFactory::set_custom_hooks( null );
	}

	public function testInit_Always_RegistersAdminHooksAndInitsStreak(): void {
		$hooks = $this->createMock( Hooks::class );
		ServiceFactory::set_custom_hooks( $hooks );
		$plugin = new Wpstreak( self::PLUGIN_FILE, $this->streak );

		$registered = [];
		$hooks->method( 'add_action' )->willReturnCallback(
			function ( string $hook, $callback ) use ( &$registered ): bool {
				$registered[ $hook ] = $callback;
				return true;
			}
		);
		$this->streak->expects( $this->once() )->method( 'init' );

		$plugin->init();

		$this->assertEquals(
			[
				'admin_enqueue_scripts' => [ $plugin, 'enqueue_admin_assets' ],
				'all_admin_notices' => [ $plugin, 'render_streak_panel' ],
			],
			$registered
		);
	}

	public function testEnqueueAdminAssets_PostsScreen_EnqueuesAssets(): void {
		$this->screen->method( 'get_current_screen' )->willReturn( (object) [ 'id' => 'edit-post' ] );

		$this->assets
			->expects( $this->once() )
			->method( 'wp_enqueue_style' )
			->with( 'wpstreak-admin', self::PLUGIN_URL . 'assets/dist/styles/admin.css', [], Wpstreak::VERSION );
		$this->assets
			->expects( $this->once() )
			->method( 'wp_enqueue_script' )
			->with( 'wpstreak-admin', self::PLUGIN_URL . 'assets/dist/javascript/admin.js', [], Wpstreak::VERSION, true );

		( new Wpstreak( self::PLUGIN_FILE, $this->streak ) )->enqueue_admin_assets();
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

		( new Wpstreak( self::PLUGIN_FILE, $this->streak ) )->enqueue_admin_assets();
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
		( new Wpstreak( self::PLUGIN_FILE, $this->streak ) )->render_streak_panel();
	}

	public function provide_other_screens(): array {
		return [
			'pages list' => [ (object) [ 'id' => 'edit-page' ] ],
			'dashboard' => [ (object) [ 'id' => 'dashboard' ] ],
			'no screen' => [ null ],
		];
	}
}
