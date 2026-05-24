<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Screen;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\ServiceFactory;

class Wpstreak {
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

	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->hooks = ServiceFactory::create_hooks();
		$this->assets = ServiceFactory::create_assets();
		$this->plugins = ServiceFactory::create_plugins();
		$this->screen = ServiceFactory::create_screen();
		$this->streak = new Streak();
	}


	public function init() {
		$this->hooks->add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		$this->hooks->add_action('all_admin_notices', [ $this, 'add_streak_panel' ] );

		$this->streak->init();
	}

	public function enqueue_admin_scripts() {
    $this->assets->wp_enqueue_style(
      'wpplugin-admin-styles',
      $this->plugins->plugin_dir_url( $this->plugin_file ) . '/assets/dist/styles/admin.css',
      [],
      '1.0.0'
    );

			$this->assets->wp_enqueue_script(
				'wpplugin-admin-scripts',
			$this->plugins->plugin_dir_url( $this->plugin_file ) . '/assets/dist/javascript/admin.js',
			[],
			'1.0.0',
			true
		);
	}

	public function enqueue_frontend_scripts() {
		$this->assets->wp_enqueue_script(
			'wpplugin-frontend-scripts',
			$this->plugins->plugin_dir_url( $this->plugin_file ) . '/assets/dist/javascript/frontend.js',
			[],
			'1.0.0',
			true
		);
	}

	public function add_streak_panel() {
		$screen = $this->screen->get_current_screen();

		if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== 'post' ) {
			return;
		}

		$summary = $this->streak->get_summary();
		$streak = (int) $summary['streak'];
		$is_active_today = (bool) $summary['is_active_today'];
		$last_post_date = $summary['last_post_date'];
		$next_milestone = (int) $summary['next_milestone'];
		$progress = 0 === $next_milestone ? 0 : min( 100, (int) round( ( $streak / $next_milestone ) * 100 ) );
		$accent_class = $is_active_today ? 'is-hot' : 'is-warm';
		$day_label = 1 === $streak ? 'day' : 'days';
		$status = $summary['status'];
		$last_post_label = $last_post_date
			? \date( 'M j, Y', \strtotime( $last_post_date ) )
			: 'No published posts yet';

    include __DIR__ . '/views/streak_panel.php';
	}
}
