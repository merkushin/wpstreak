<?php declare( strict_types=1 );

namespace Merkushin\Wpstreak\Pro;

use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * The site's link to a Streakfire account: its token, the account's email and
 * plan, and when the published days were last synced. Stored in one option
 * that isn't autoloaded, since only the settings page and the sync read it.
 */
class Connection {
	public const OPTION = 'streakfire_connection';

	public const FEATURE_REMINDERS = 'reminders';

	/**
	 * @var Options
	 */
	private $options;

	public function __construct() {
		$this->options = ServiceFactory::create_options();
	}

	public function is_connected(): bool {
		return null !== $this->token();
	}

	public function token(): ?string {
		$token = $this->get()['token'] ?? null;

		return is_string( $token ) && '' !== $token ? $token : null;
	}

	public function email(): string {
		return (string) ( $this->get()['email'] ?? '' );
	}

	/**
	 * @return string 'free' or 'pro'.
	 */
	public function plan(): string {
		return (string) ( $this->get()['plan'] ?? 'free' );
	}

	public function has_feature( string $feature ): bool {
		$features = $this->get()['features'] ?? [];

		return is_array( $features ) && in_array( $feature, $features, true );
	}

	/**
	 * @return int|null Unix time of the last successful sync.
	 */
	public function last_synced_at(): ?int {
		$time = $this->get()['synced_at'] ?? null;

		return is_int( $time ) ? $time : null;
	}

	/**
	 * @param array<string, mixed> $entitlements As returned by the API: plan and features.
	 */
	public function connect( string $token, string $email, array $entitlements ): void {
		$this->set(
			[
				'token' => $token,
				'email' => $email,
			]
		);
		$this->update_entitlements( $entitlements );
	}

	/**
	 * @param array<string, mixed> $entitlements As returned by the API: plan and features.
	 */
	public function update_entitlements( array $entitlements ): void {
		$features = $entitlements['features'] ?? [];

		$this->set(
			[
				'plan'     => (string) ( $entitlements['plan'] ?? 'free' ),
				'features' => is_array( $features ) ? array_values( array_filter( $features, 'is_string' ) ) : [],
			]
		);
	}

	public function record_sync( int $time ): void {
		$this->set( [ 'synced_at' => $time ] );
	}

	public function forget(): void {
		$this->options->delete_option( self::OPTION );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get(): array {
		$value = $this->options->get_option( self::OPTION, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * @param array<string, mixed> $changes Merged into the stored value.
	 */
	private function set( array $changes ): void {
		$this->options->update_option( self::OPTION, array_merge( $this->get(), $changes ), false );
	}
}
