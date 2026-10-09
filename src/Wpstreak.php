<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\Localization;
use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\Service\Plugins;
use Merkushin\Wpal\Service\Screen;
use Merkushin\Wpal\Service\UserSettings;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpstreak\Pro\Pro;

defined( 'ABSPATH' ) || exit;

class Wpstreak {
	public const VERSION = '1.1.0';

	public const TEXT_DOMAIN = 'streakfire';

	/**
	 * The Posts list screen, where the panel is shown.
	 */
	private const SCREEN_ID = 'edit-post';

	/**
	 * Per-user setting for the panel's Screen Options checkbox: 'on' or 'off'.
	 * Stored by WordPress' user settings; the admin script updates it with setUserSetting().
	 */
	public const PANEL_SETTING = 'streakfire_panel';

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
	 * @var Localization
	 */
	private $localization;

	/**
	 * @var Dates
	 */
	private $dates;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var UserSettings
	 */
	private $user_settings;

	/**
	 * @var Streak
	 */
	private $streak;

	/**
	 * @var Pro
	 */
	private $pro;

	public function __construct( string $plugin_file, ?Streak $streak = null, ?Pro $pro = null ) {
		$this->plugin_file   = $plugin_file;
		$this->hooks         = ServiceFactory::create_hooks();
		$this->assets        = ServiceFactory::create_assets();
		$this->plugins       = ServiceFactory::create_plugins();
		$this->screen        = ServiceFactory::create_screen();
		$this->localization  = ServiceFactory::create_localization();
		$this->dates         = ServiceFactory::create_dates();
		$this->options       = ServiceFactory::create_options();
		$this->user_settings = ServiceFactory::create_user_settings();
		$this->streak        = $streak ?? new Streak();
		$this->pro           = $pro ?? new Pro( self::VERSION );
	}

	public function init(): void {
		// Bundled translations in languages/. Language packs from translate.wordpress.org take precedence.
		$this->localization->load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			dirname( $this->plugins->plugin_basename( $this->plugin_file ) ) . '/languages'
		);

		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		$this->hooks->add_action( 'all_admin_notices', [ $this, 'render_streak_panel' ] );
		$this->hooks->add_filter( 'screen_settings', [ $this, 'add_screen_option' ], 10, 2 );

		$this->streak->init();
		$this->pro->init();
	}

	public function enqueue_admin_assets(): void {
		if ( ! $this->is_posts_screen() ) {
			return;
		}

		$url = $this->plugins->plugin_dir_url( $this->plugin_file );

		$this->assets->wp_enqueue_style( 'streakfire-admin', $url . 'assets/dist/styles/admin.css', [], self::VERSION );
		// The script saves the Screen Options checkbox with setUserSetting() from WordPress' utils script.
		$this->assets->wp_enqueue_script( 'streakfire-admin', $url . 'assets/dist/javascript/admin.js', [ 'utils' ], self::VERSION, true );
	}

	public function render_streak_panel(): void {
		if ( ! $this->is_posts_screen() ) {
			return;
		}

		$summary         = $this->streak->get_summary();
		$streak          = $summary['streak'];
		$is_active_today = $summary['is_active_today'];
		$next_milestone  = $summary['next_milestone'];
		$progress        = min( 100, (int) round( ( $streak / $next_milestone ) * 100 ) );

		// The view holds all translatable text; it receives raw values plus their localized formatting.
		$accent_class         = $is_active_today ? 'is-hot' : 'is-warm';
		$status_key           = $this->get_status_key( $streak, $is_active_today );
		$streak_label         = $this->format_number( $streak );
		$next_milestone_label = $this->format_number( $next_milestone );
		$progress_label       = $this->format_number( $progress );
		$last_post_label      = $this->format_date( $summary['last_post_date'] );
		$is_panel_visible     = $this->is_panel_visible();
		$reminders_url        = $this->pro->reminders_url();

		include __DIR__ . '/views/streak_panel.php';
	}

	/**
	 * Adds a checkbox to the Posts screen's Screen Options so each user can hide the panel.
	 *
	 * @param string $settings Screen settings HTML.
	 * @param mixed  $screen   WP_Screen object.
	 */
	public function add_screen_option( $settings, $screen ): string {
		$settings = (string) $settings;

		if ( ! is_object( $screen ) || ! isset( $screen->id ) || self::SCREEN_ID !== $screen->id ) {
			return $settings;
		}

		$is_panel_visible = $this->is_panel_visible();

		ob_start();
		include __DIR__ . '/views/screen_option.php';

		return $settings . (string) ob_get_clean();
	}

	private function is_panel_visible(): bool {
		return 'off' !== $this->user_settings->get_user_setting( self::PANEL_SETTING, 'on' );
	}

	private function is_posts_screen(): bool {
		$screen = $this->screen->get_current_screen();

		return is_object( $screen ) && isset( $screen->id ) && self::SCREEN_ID === $screen->id;
	}

	/**
	 * @return string One of 'start', 'on_fire' or 'alive'; the view maps it to a message.
	 */
	private function get_status_key( int $streak, bool $is_active_today ): string {
		if ( 0 === $streak ) {
			return 'start';
		}

		return $is_active_today ? 'on_fire' : 'alive';
	}

	private function format_number( int $number ): string {
		return (string) $this->localization->number_format_i18n( $number );
	}

	/**
	 * Formats a day (Y-m-d) with the site's date format and locale.
	 */
	private function format_date( ?string $date ): ?string {
		$utc    = new \DateTimeZone( 'UTC' );
		$parsed = null === $date ? false : \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $utc );

		if ( ! $parsed ) {
			return null;
		}

		// The day is already in the site timezone, so format it as UTC midnight to keep it unchanged.
		$formatted = $this->dates->wp_date( (string) $this->options->get_option( 'date_format', 'F j, Y' ), $parsed->getTimestamp(), $utc );

		return is_string( $formatted ) ? $formatted : null;
	}
}
