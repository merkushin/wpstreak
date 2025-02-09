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

		// Check if we are on the post list page
		if ($screen->base !== 'edit' && $screen->post_type !== 'post') {
			return;
		}

		// count current post streak
		$streak = $this->streak->get_streak();

		echo '<div class="custom-panel notice notice-info" style="padding: 15px; margin-bottom: 20px;">
		<h2 style="margin-top:0;">Current Streak</h2>
		<p>Your current writing streak is <strong>' . $streak . '</strong> days.</p>
		</div>';
	}
}
