<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak\Pro;

use Merkushin\Wpal\Service\Cron;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\Service\PostTypes;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpstreak\PublishedPostDates;

defined( 'ABSPATH' ) || exit;

/**
 * Sends Streakfire the days this site published on, the same days the panel
 * counts. The whole set goes every time, so unpublished, deleted and re-dated
 * posts are reflected, and a sync that failed is repaired by the next one.
 */
class DaysSync {
	/**
	 * Runs shortly after posts change.
	 */
	public const HOOK = 'streakfire_sync_days';

	/**
	 * Runs once a day as a safety net.
	 */
	public const DAILY_HOOK = 'streakfire_sync_days_daily';

	private const DELAY = 60;

	private const DAY = 86400;

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * @var Cron
	 */
	private $cron;

	/**
	 * @var PostTypes
	 */
	private $post_types;

	/**
	 * @var Dates
	 */
	private $dates;

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var PublishedPostDates
	 */
	private $post_dates;

	public function __construct( Api $api, Connection $connection, ?PublishedPostDates $post_dates = null ) {
		$this->hooks      = ServiceFactory::create_hooks();
		$this->cron       = ServiceFactory::create_cron();
		$this->post_types = ServiceFactory::create_post_types();
		$this->dates      = ServiceFactory::create_dates();
		$this->api        = $api;
		$this->connection = $connection;
		$this->post_dates = $post_dates ?? new PublishedPostDates();
	}

	public function init(): void {
		$this->hooks->add_action( self::HOOK, [ $this, 'sync' ] );
		$this->hooks->add_action( self::DAILY_HOOK, [ $this, 'sync' ] );
		// The same hooks that refresh the panel's cache.
		$this->hooks->add_action( 'save_post', [ $this, 'schedule' ] );
		$this->hooks->add_action( 'delete_post', [ $this, 'schedule' ] );
	}

	/**
	 * Syncs a minute after a post changes, in the background.
	 *
	 * @param int|string $post_id
	 */
	public function schedule( $post_id ): void {
		if ( ! $this->connection->is_connected() || 'post' !== $this->post_types->get_post_type( (int) $post_id ) ) {
			return;
		}

		// WordPress ignores a single event scheduled within 10 minutes of the same one,
		// so a burst of edits makes one sync, which reads the posts as they are by then.
		$this->cron->wp_schedule_single_event( time() + self::DELAY, self::HOOK );
	}

	public function sync(): void {
		$token = $this->connection->token();
		if ( null === $token ) {
			return;
		}

		$response = $this->api->request(
			'PUT',
			'/v1/days',
			$token,
			[
				'days'     => $this->post_dates->get_dates(),
				'timezone' => (string) $this->dates->wp_timezone_string(),
			]
		);

		if ( null === $response ) {
			return;
		}
		if ( 401 === $response['status'] ) {
			// The token was revoked, e.g. the site was disconnected from another device.
			$this->connection->forget();
			$this->stop();
			return;
		}
		if ( 200 === $response['status'] ) {
			$this->connection->record_sync( time() );
		}
	}

	/**
	 * Syncs now and then daily, after connecting.
	 */
	public function start(): void {
		$this->sync();

		if ( false === $this->cron->wp_next_scheduled( self::DAILY_HOOK ) ) {
			$this->cron->wp_schedule_event( time() + self::DAY, 'daily', self::DAILY_HOOK );
		}
	}

	public function stop(): void {
		self::clear_schedule();
	}

	/**
	 * Also used when the plugin is deactivated.
	 */
	public static function clear_schedule(): void {
		$cron = ServiceFactory::create_cron();
		$cron->wp_clear_scheduled_hook( self::HOOK );
		$cron->wp_clear_scheduled_hook( self::DAILY_HOOK );
	}
}
