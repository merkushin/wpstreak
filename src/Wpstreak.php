<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\Service\Screen;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

class Wpstreak {
	public const VERSION = '1.0.0';

	/**
	 * The Posts list screen, where the panel is shown.
	 */
	private const SCREEN_ID = 'edit-post';

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Assets
	 */
	private $assets;

	/**
	 * @var Plugins
	 */
	private $plugins;

	/**
	 * @var Screen
	 */
	private $screen;

	/**
	 * @var Streak
	 */
	private $streak;

	public function __construct( string $plugin_file, ?Streak $streak = null ) {
		$this->plugin_file = $plugin_file;
		$this->hooks = ServiceFactory::create_hooks();
		$this->assets = ServiceFactory::create_assets();
		$this->plugins = ServiceFactory::create_plugins();
		$this->screen = ServiceFactory::create_screen();
		$this->streak = $streak ?? new Streak();
	}

	public function init(): void {
		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		$this->hooks->add_action( 'all_admin_notices', [ $this, 'render_streak_panel' ] );

		$this->streak->init();
	}

	public function enqueue_admin_assets(): void {
		if ( ! $this->is_posts_screen() ) {
			return;
		}

		$url = $this->plugins->plugin_dir_url( $this->plugin_file );

		$this->assets->wp_enqueue_style( 'wpstreak-admin', $url . 'assets/dist/styles/admin.css', [], self::VERSION );
		$this->assets->wp_enqueue_script( 'wpstreak-admin', $url . 'assets/dist/javascript/admin.js', [], self::VERSION, true );
	}

	public function render_streak_panel(): void {
		if ( ! $this->is_posts_screen() ) {
			return;
		}

		$summary = $this->streak->get_summary();
		$streak = $summary['streak'];
		$is_active_today = $summary['is_active_today'];
		$next_milestone = $summary['next_milestone'];
		$progress = min( 100, (int) round( ( $streak / $next_milestone ) * 100 ) );
		$accent_class = $is_active_today ? 'is-hot' : 'is-warm';
		$day_label = 1 === $streak ? 'day' : 'days';
		$status = $this->get_status( $streak, $is_active_today );
		$last_post_label = $this->format_date( $summary['last_post_date'] ) ?? 'No published posts yet';

		include __DIR__ . '/views/streak_panel.php';
	}

	private function is_posts_screen(): bool {
		$screen = $this->screen->get_current_screen();

		return is_object( $screen ) && isset( $screen->id ) && self::SCREEN_ID === $screen->id;
	}

	private function get_status( int $streak, bool $is_active_today ): string {
		if ( 0 === $streak ) {
			return 'Start your next streak';
		}

		return $is_active_today
			? 'You are on fire today'
			: 'You are still alive, publish today to keep it going';
	}

	private function format_date( ?string $date ): ?string {
		$parsed = null === $date ? false : \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );

		return $parsed ? $parsed->format( 'M j, Y' ) : null;
	}
}
