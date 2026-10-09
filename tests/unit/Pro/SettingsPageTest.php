<?php declare( strict_types=1 );

namespace MerkushinTest\Wpstreak\Pro;

use Merkushin\Wpal\Service\Capabilities;
use Merkushin\Wpal\Service\Dates;
use Merkushin\Wpal\Service\Errors;
use Merkushin\Wpal\Service\Formatting;
use Merkushin\Wpal\Service\Nonces;
use Merkushin\Wpal\Service\Passwords;
use Merkushin\Wpal\Service\Redirects;
use Merkushin\Wpal\Service\Sanitization;
use Merkushin\Wpal\Service\Transient;
use Merkushin\Wpal\Service\Urls;
use Merkushin\Wpal\Service\Users;
use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpstreak\Pro\Api;
use Merkushin\Wpstreak\Pro\Connection;
use Merkushin\Wpstreak\Pro\DaysSync;
use Merkushin\Wpstreak\Pro\SettingsPage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SettingsPageTest extends TestCase {
	use OptionsStore;

	private const PAGE_URL = 'https://blog.example/wp-admin/options-general.php?page=streakfire';

	/**
	 * @var Api&MockObject
	 */
	private $api;

	/**
	 * @var DaysSync&MockObject
	 */
	private $sync;

	/**
	 * @var Capabilities&MockObject
	 */
	private $capabilities;

	/**
	 * @var Nonces&MockObject
	 */
	private $nonces;

	/**
	 * @var Errors&MockObject
	 */
	private $errors;

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var array<string, mixed>
	 */
	private $transients = [];

	/**
	 * @var string[] Every redirect, in order: "safe:<url>" or "away:<url>".
	 */
	private $redirects = [];

	/**
	 * @var int
	 */
	private $terminated = 0;

	protected function setUp(): void {
		$_GET  = [];
		$_POST = [];

		$this->api          = $this->createMock( Api::class );
		$this->sync         = $this->createMock( DaysSync::class );
		$this->capabilities = $this->createMock( Capabilities::class );
		$this->capabilities->method( 'current_user_can' )->with( 'manage_options' )->willReturn( true );
		$this->nonces = $this->createMock( Nonces::class );
		$this->errors = $this->createMock( Errors::class );

		$urls = $this->createMock( Urls::class );
		$urls->method( 'home_url' )->willReturn( 'https://blog.example' );
		$urls->method( 'admin_url' )->willReturnCallback(
			function ( string $path = '' ): string {
				return 'https://blog.example/wp-admin/' . $path;
			}
		);
		// Like add_query_arg( $args, $url ), which leaves values as they are.
		$urls->method( 'add_query_arg' )->willReturnCallback(
			function ( array $args, string $url ): string {
				foreach ( $args as $key => $value ) {
					$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $key . '=' . $value;
				}
				return $url;
			}
		);

		$transient = $this->createMock( Transient::class );
		$transient->method( 'set_transient' )->willReturnCallback(
			function ( string $key, $value ): bool {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
		$transient->method( 'get_transient' )->willReturnCallback(
			function ( string $key ) {
				return $this->transients[ $key ] ?? false;
			}
		);
		$transient->method( 'delete_transient' )->willReturnCallback(
			function ( string $key ): bool {
				unset( $this->transients[ $key ] );
				return true;
			}
		);

		$redirects = $this->createMock( Redirects::class );
		$redirects->method( 'wp_safe_redirect' )->willReturnCallback(
			function ( string $url ): bool {
				$this->redirects[] = 'safe:' . $url;
				return true;
			}
		);
		$redirects->method( 'wp_redirect' )->willReturnCallback(
			function ( string $url ): bool {
				$this->redirects[] = 'away:' . $url;
				return true;
			}
		);

		$users = $this->createMock( Users::class );
		$users->method( 'get_current_user_id' )->willReturn( 7 );
		$passwords = $this->createMock( Passwords::class );
		$passwords->method( 'wp_generate_password' )->with( 32, false )->willReturn( 'state123' );
		$dates = $this->createMock( Dates::class );
		$dates->method( 'wp_timezone_string' )->willReturn( 'America/Mexico_City' );
		$dates->method( 'human_time_diff' )->willReturn( '5 mins' );
		$sanitization = $this->createMock( Sanitization::class );
		$sanitization->method( 'sanitize_text_field' )->willReturnArgument( 0 );
		$formatting = $this->createMock( Formatting::class );
		$formatting->method( 'wp_unslash' )->willReturnArgument( 0 );

		ServiceFactory::set_custom_capabilities( $this->capabilities );
		ServiceFactory::set_custom_nonces( $this->nonces );
		ServiceFactory::set_custom_errors( $this->errors );
		ServiceFactory::set_custom_urls( $urls );
		ServiceFactory::set_custom_transient( $transient );
		ServiceFactory::set_custom_redirects( $redirects );
		ServiceFactory::set_custom_users( $users );
		ServiceFactory::set_custom_passwords( $passwords );
		ServiceFactory::set_custom_dates( $dates );
		ServiceFactory::set_custom_sanitization( $sanitization );
		ServiceFactory::set_custom_formatting( $formatting );
		ServiceFactory::set_custom_options( $this->create_options_store( $this ) );

		$this->connection = new Connection();
	}

	protected function tearDown(): void {
		$_GET  = [];
		$_POST = [];
		foreach ( [ 'capabilities', 'nonces', 'errors', 'urls', 'transient', 'redirects', 'users', 'passwords', 'dates', 'sanitization', 'formatting', 'options' ] as $service ) {
			ServiceFactory::{'set_custom_' . $service}( null );
		}
	}

	public function testConnect_Always_SendsAdminToConnectPageWithState(): void {
		$this->nonces->expects( $this->once() )->method( 'check_admin_referer' )->with( 'streakfire_connect' );

		$this->create_page()->connect();

		$this->assertSame( 'state123', $this->transients['streakfire_connect_7'] );
		$this->assertSame(
			[ 'away:https://streakfire.test/connect?site_url=' . rawurlencode( 'https://blog.example' ) . '&return_url=' . rawurlencode( self::PAGE_URL ) . '&state=state123' ],
			$this->redirects
		);
		$this->assertSame( 1, $this->terminated );
	}

	public function testConnect_NotAnAdmin_Dies(): void {
		$this->capabilities = $this->createMock( Capabilities::class );
		$this->capabilities->method( 'current_user_can' )->willReturn( false );
		ServiceFactory::set_custom_capabilities( $this->capabilities );

		$this->errors->expects( $this->once() )->method( 'wp_die' );

		$this->create_page()->connect();
	}

	public function testHandleReturn_ValidStateAndCode_ConnectsAndStartsSyncing(): void {
		$this->transients['streakfire_connect_7'] = 'state123';
		$_GET                                     = [
			'streakfire_code'  => 'code456',
			'streakfire_state' => 'state123',
		];
		$this->api
			->expects( $this->once() )
			->method( 'request' )
			->with(
				'POST',
				'/v1/connect/exchange',
				null,
				[
					'code'     => 'code456',
					'site_url' => 'https://blog.example',
					'timezone' => 'America/Mexico_City',
				]
			)
			->willReturn(
				[
					'status' => 200,
					'data'   => [
						'token'        => 'sfs_token',
						'email'        => 'writer@example.com',
						'entitlements' => [
							'plan'     => 'free',
							'features' => [],
						],
					],
				]
			);
		$this->sync->expects( $this->once() )->method( 'start' );

		$this->create_page()->handle_return();

		$this->assertSame( 'sfs_token', $this->connection->token() );
		$this->assertSame( 'writer@example.com', $this->connection->email() );
		$this->assertArrayNotHasKey( 'streakfire_connect_7', $this->transients );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=connected' ], $this->redirects );
	}

	public function testHandleReturn_WrongState_RefusesWithoutCallingTheApi(): void {
		$this->transients['streakfire_connect_7'] = 'state123';
		$_GET                                     = [
			'streakfire_code'  => 'code456',
			'streakfire_state' => 'forged',
		];
		$this->api->expects( $this->never() )->method( 'request' );

		$this->create_page()->handle_return();

		$this->assertFalse( $this->connection->is_connected() );
		$this->assertArrayNotHasKey( 'streakfire_connect_7', $this->transients, 'a state works once' );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=connect_failed' ], $this->redirects );
	}

	public function testHandleReturn_NoStateStarted_Refuses(): void {
		$_GET = [
			'streakfire_code'  => 'code456',
			'streakfire_state' => 'state123',
		];
		$this->api->expects( $this->never() )->method( 'request' );

		$this->create_page()->handle_return();

		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=connect_failed' ], $this->redirects );
	}

	public function testHandleReturn_ExchangeRejected_ReportsFailure(): void {
		$this->transients['streakfire_connect_7'] = 'state123';
		$_GET                                     = [
			'streakfire_code'  => 'expired',
			'streakfire_state' => 'state123',
		];
		$this->api->method( 'request' )->willReturn(
			[
				'status' => 400,
				'data'   => [ 'error' => 'invalid or expired code' ],
			]
		);
		$this->sync->expects( $this->never() )->method( 'start' );

		$this->create_page()->handle_return();

		$this->assertFalse( $this->connection->is_connected() );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=connect_failed' ], $this->redirects );
	}

	public function testHandleReturn_NoCode_DoesNothing(): void {
		$this->api->expects( $this->never() )->method( 'request' );

		$this->create_page()->handle_return();

		$this->assertSame( [], $this->redirects );
	}

	public function testDisconnect_Connected_RevokesForgetsAndStopsSyncing(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->expects( $this->once() )->method( 'request' )->with( 'DELETE', '/v1/site', 'sfs_token' );
		$this->sync->expects( $this->once() )->method( 'stop' );

		$this->create_page()->disconnect();

		$this->assertFalse( $this->connection->is_connected() );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=disconnected' ], $this->redirects );
	}

	public function testReminders_Submitted_SavesThemOnTheServer(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$_POST = [
			'reminder_enabled' => '1',
			'reminder_hour'    => '30',
		];
		$this->nonces->expects( $this->once() )->method( 'check_admin_referer' )->with( 'streakfire_reminders' );
		$this->api
			->expects( $this->once() )
			->method( 'request' )
			->with(
				'PATCH',
				'/v1/settings',
				'sfs_token',
				[
					'reminder_enabled' => true,
					'reminder_hour'    => 23,
				]
			)
			->willReturn(
				[
					'status' => 200,
					'data'   => [],
				]
			);

		$this->create_page()->reminders();

		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=saved' ], $this->redirects );
	}

	public function testReminders_Unchecked_TurnsThemOff(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$_POST = [ 'reminder_hour' => '7' ];
		$this->api
			->expects( $this->once() )
			->method( 'request' )
			->with(
				'PATCH',
				'/v1/settings',
				'sfs_token',
				[
					'reminder_enabled' => false,
					'reminder_hour'    => 7,
				]
			)
			->willReturn(
				[
					'status' => 200,
					'data'   => [],
				]
			);

		$this->create_page()->reminders();
	}

	public function testUpgrade_CheckoutAvailable_OpensIt(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->with( 'POST', '/v1/checkout', 'sfs_token' )->willReturn(
			[
				'status' => 200,
				'data'   => [ 'url' => 'https://streakfire.lemonsqueezy.com/buy/abc?checkout[custom][account_id]=1' ],
			]
		);

		$this->create_page()->upgrade();

		$this->assertSame( [ 'away:https://streakfire.lemonsqueezy.com/buy/abc?checkout[custom][account_id]=1' ], $this->redirects );
	}

	public function testUpgrade_BillingNotConfigured_ReportsIt(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->willReturn(
			[
				'status' => 503,
				'data'   => [ 'error' => 'billing is not configured' ],
			]
		);

		$this->create_page()->upgrade();

		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=billing_unavailable' ], $this->redirects );
	}

	public function testUpgrade_UrlIsNotHttps_RefusesToRedirect(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->willReturn(
			[
				'status' => 200,
				'data'   => [ 'url' => 'javascript:alert(1)' ],
			]
		);

		$this->create_page()->upgrade();

		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=billing_unavailable' ], $this->redirects );
	}

	public function testManage_TokenRevoked_ForgetsConnection(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->with( 'GET', '/v1/billing/portal', 'sfs_token' )->willReturn(
			[
				'status' => 401,
				'data'   => [],
			]
		);
		$this->sync->expects( $this->once() )->method( 'stop' );

		$this->create_page()->manage();

		$this->assertFalse( $this->connection->is_connected() );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=revoked' ], $this->redirects );
	}

	public function testManage_Unreachable_SaysSo(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->willReturn( null );

		$this->create_page()->manage();

		$this->assertTrue( $this->connection->is_connected() );
		$this->assertSame( [ 'safe:' . self::PAGE_URL . '&streakfire_notice=unreachable' ], $this->redirects );
	}

	public function testRender_NotConnected_OffersToConnectWithoutCallingTheApi(): void {
		$this->api->expects( $this->never() )->method( 'request' );

		$text = $this->render();

		$this->assertStringContainsString( 'Connect to Streakfire', $text );
		$this->assertStringContainsString( 'Nothing is sent until you connect.', $text );
		$this->assertStringContainsString( 'name="action" value="streakfire_connect"', $text );
		$this->assertStringContainsString( 'href="https://streakfire.test/privacy"', $text );
	}

	public function testRender_ConnectedPro_ShowsAccountRemindersAndManage(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->connection->record_sync( time() - 300 );
		$this->api_returns(
			[
				'/v1/entitlements' => [
					'plan'     => 'pro',
					'features' => [ 'reminders' ],
				],
				'/v1/settings'     => [
					'reminder_enabled' => true,
					'reminder_hour'    => 7,
					'timezone'         => 'America/Mexico_City',
				],
				'/v1/streak'       => [ 'current' => 12 ],
			]
		);

		$html = $this->render();

		$this->assertStringContainsString( 'writer@example.com', $html );
		$this->assertStringContainsString( '12 days', $html );
		$this->assertStringContainsString( '5 mins ago', $html );
		$this->assertStringContainsString( "value=\"7\" selected='selected'>07:00", $html );
		$this->assertStringContainsString( "value=\"1\" checked='checked'", $html );
		$this->assertStringContainsString( 'value="streakfire_manage"', $html );
		$this->assertStringNotContainsString( 'value="streakfire_upgrade"', $html );
		$this->assertTrue( $this->connection->has_feature( Connection::FEATURE_REMINDERS ), 'entitlements are refreshed' );
	}

	public function testRender_ConnectedFree_OffersUpgrade(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api_returns(
			[
				'/v1/entitlements' => [
					'plan'     => 'free',
					'features' => [],
				],
				'/v1/settings'     => [],
				'/v1/streak'       => [ 'current' => 0 ],
			]
		);

		$html = $this->render();

		$this->assertStringContainsString( 'value="streakfire_upgrade"', $html );
		$this->assertStringNotContainsString( 'value="streakfire_reminders"', $html );
		$this->assertStringContainsString( 'value="streakfire_disconnect"', $html );
	}

	public function testRender_RevokedElsewhere_ShowsConnectAgain(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->willReturn(
			[
				'status' => 401,
				'data'   => [],
			]
		);

		$html = $this->render();

		$this->assertStringContainsString( 'no longer connected', $html );
		$this->assertStringContainsString( 'value="streakfire_connect"', $html );
	}

	public function testRender_Unreachable_KeepsTheConnection(): void {
		$this->connection->connect( 'sfs_token', 'writer@example.com', [] );
		$this->api->method( 'request' )->willReturn( null );

		$html = $this->render();

		$this->assertStringContainsString( 'couldn&#039;t be reached', $html );
		$this->assertStringContainsString( 'value="streakfire_disconnect"', $html );
	}

	/**
	 * @param array<string, array<string, mixed>> $responses Data for each GET path.
	 */
	private function api_returns( array $responses ): void {
		$this->api->method( 'request' )->willReturnCallback(
			function ( string $method, string $path ) use ( $responses ): array {
				return [
					'status' => 200,
					'data'   => $responses[ $path ],
				];
			}
		);
	}

	private function render(): string {
		ob_start();
		$this->create_page()->render();
		return (string) ob_get_clean();
	}

	private function create_page(): SettingsPage {
		return new SettingsPage(
			$this->api,
			$this->connection,
			$this->sync,
			'https://streakfire.test',
			function (): void {
				++$this->terminated;
			}
		);
	}
}
