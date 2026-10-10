<?php declare( strict_types=1 );

namespace Merkushin\Inkmeter\Pro;

use Merkushin\Wpal\Service\Capabilities;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Inkmeter Pro: the optional connection to the Inkmeter service.
 *
 * INKMETER_API_URL and INKMETER_SITE_URL in wp-config.php point the
 * plugin at another Inkmeter server, e.g. a local one during development.
 */
class Pro {
	public const API_URL = 'https://api.streakfire.org';

	public const SITE_URL = 'https://streakfire.org';

	/**
	 * @var Capabilities
	 */
	private $capabilities;

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var DaysSync
	 */
	private $sync;

	/**
	 * @var SettingsPage
	 */
	private $settings_page;

	public function __construct( string $plugin_version ) {
		$api_url  = defined( 'INKMETER_API_URL' ) ? (string) INKMETER_API_URL : self::API_URL;
		$site_url = defined( 'INKMETER_SITE_URL' ) ? (string) INKMETER_SITE_URL : self::SITE_URL;

		$this->capabilities  = ServiceFactory::create_capabilities();
		$this->connection    = new Connection();
		$api                 = new Api( $api_url, 'Inkmeter/' . $plugin_version );
		$this->sync          = new DaysSync( $api, $this->connection );
		$this->settings_page = new SettingsPage( $api, $this->connection, $this->sync, $site_url );
	}

	public function init(): void {
		$this->sync->init();
		$this->settings_page->init();
	}

	/**
	 * Where the panel points admins to set up reminders, or null when they
	 * already have them or can't manage the plugin.
	 */
	public function reminders_url(): ?string {
		if ( ! $this->capabilities->current_user_can( 'manage_options' ) || $this->connection->has_feature( Connection::FEATURE_REMINDERS ) ) {
			return null;
		}

		return $this->settings_page->page_url();
	}
}
