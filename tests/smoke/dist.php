<?php
/**
 * Smoke test for a release build: php tests/smoke/dist.php <build-dir>
 *
 * Loads the built plugin (scoped, with wpal pruned) using stand-ins for the WordPress
 * functions it calls, then runs every path: init, asset loading, rendering the panel with a
 * daily and a weekly goal, and clearing the cache. A wpal class missing from the build fails
 * here with a fatal error.
 *
 * @package Merkushin\Inkmeter
 */

declare( strict_types=1 );

$build_dir = rtrim( $argv[1] ?? '', '/' );
$main_file = $build_dir . '/inkmeter.php';
if ( ! is_file( $main_file ) ) {
	fwrite( STDERR, "Usage: php tests/smoke/dist.php <build-dir>\n" );
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['smoke_actions']    = [];
$GLOBALS['smoke_enqueued']   = [];
$GLOBALS['smoke_transients'] = [];
$GLOBALS['smoke_options']    = [
	'date_format'   => 'F j, Y',
	'start_of_week' => '1',
];

function add_action( string $hook, callable $callback ): bool {
	$GLOBALS['smoke_actions'][ $hook ] = $callback;
	return true;
}

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	$GLOBALS['smoke_actions'][ $hook ] = $callback;
	return true;
}

function get_user_setting( $name, $default_value = false ) {
	return $default_value;
}

function checked( $checked, $current = true, bool $display = true ): string {
	$result = (string) $checked === (string) $current ? " checked='checked'" : '';
	if ( $display ) {
		echo $result;
	}
	return $result;
}

function plugin_dir_url( string $file ): string {
	return 'https://example.com/wp-content/plugins/inkmeter/';
}

function get_current_screen() {
	return (object) [ 'id' => 'edit-post' ];
}

function wp_enqueue_style( string $handle, string $src = '' ): void {
	$GLOBALS['smoke_enqueued'][] = $src;
}

function wp_enqueue_script( string $handle, string $src = '' ): void {
	$GLOBALS['smoke_enqueued'][] = $src;
}

function current_time( $type ) {
	return '2026-03-02';
}

function get_transient( string $key ) {
	return $GLOBALS['smoke_transients'][ $key ] ?? false;
}

function set_transient( string $key, $value, int $expiration = 0 ): bool {
	$GLOBALS['smoke_transients'][ $key ] = $value;
	return true;
}

function delete_transient( string $key ): bool {
	unset( $GLOBALS['smoke_transients'][ $key ] );
	return true;
}

function get_post_type( $post = null ) {
	return 'post';
}

function get_option( $option, $default_value = false ) {
	return $GLOBALS['smoke_options'][ $option ] ?? $default_value;
}

function current_user_can( $capability ): bool {
	return true;
}

function admin_url( string $path = '' ): string {
	return 'https://example.com/wp-admin/' . $path;
}

function esc_url( string $url ): string {
	return htmlspecialchars( $url, ENT_QUOTES );
}

function esc_html__( string $text, string $domain = 'default' ): string {
	return esc_html( $text );
}

function selected( $selected, $current = true, bool $display = true ): string {
	$result = (string) $selected === (string) $current ? " selected='selected'" : '';
	if ( $display ) {
		echo $result;
	}
	return $result;
}

function wp_nonce_field( $action = -1 ): string {
	echo '<input type="hidden" name="_wpnonce" value="nonce" />';
	return '';
}

function wp_date( $format, $timestamp = null, $timezone = null ) {
	return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
}

function number_format_i18n( $number, $decimals = 0 ): string {
	return number_format( (float) $number, $decimals );
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function _nx( string $single, string $plural, int $number, string $context, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_html_e( string $text, string $domain = 'default' ): void {
	echo esc_html( $text );
}

// The one query, in PublishedPostDates.
$GLOBALS['wpdb'] = new class() {
	/**
	 * @var string
	 */
	public $posts = 'wp_posts';

	public function get_col( string $query ): array {
		return [ '2026-03-02', '2026-03-01', '2026-02-28' ];
	}
};

/**
 * Stops the smoke test with a message.
 */
function smoke_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

require $main_file;

if ( ! isset( $GLOBALS['smoke_actions']['init'] ) ) {
	smoke_fail( 'the plugin did not hook into init' );
}
call_user_func( $GLOBALS['smoke_actions']['init'] );

foreach ( [ 'admin_enqueue_scripts', 'all_admin_notices', 'screen_settings', 'save_post', 'delete_post', 'admin_post_inkmeter_goal' ] as $hook ) {
	if ( ! isset( $GLOBALS['smoke_actions'][ $hook ] ) ) {
		smoke_fail( "no callback for {$hook}" );
	}
}

call_user_func( $GLOBALS['smoke_actions']['admin_enqueue_scripts'] );
if ( 2 !== count( $GLOBALS['smoke_enqueued'] ) ) {
	smoke_fail( 'expected 2 enqueued assets, got ' . count( $GLOBALS['smoke_enqueued'] ) );
}

ob_start();
call_user_func( $GLOBALS['smoke_actions']['all_admin_notices'] );
$text = trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) ob_get_clean() ) ) );
foreach ( [ 'Current run 3 days', 'Last published March 2, 2026', 'Next milestone 7 days', 'Published today', 'Publish every day', 'Change goal' ] as $expected ) {
	if ( false === strpos( $text, $expected ) ) {
		smoke_fail( "panel is missing \"{$expected}\": {$text}" );
	}
}

// A weekly goal: March 2, 2026 is a Monday, so this week has one day with a post so far.
$GLOBALS['smoke_options']['inkmeter_goal'] = [
	'type' => 'weekly',
	'days' => 2,
];
$GLOBALS['smoke_transients']               = [];
ob_start();
call_user_func( $GLOBALS['smoke_actions']['all_admin_notices'] );
$weekly = trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) ob_get_clean() ) ) );
foreach ( [ 'Publish on 2 days a week', 'This week 1 of 2 days', '1 more day this week' ] as $expected ) {
	if ( false === strpos( $weekly, $expected ) ) {
		smoke_fail( "weekly panel is missing \"{$expected}\": {$weekly}" );
	}
}

$screen_settings = (string) call_user_func( $GLOBALS['smoke_actions']['screen_settings'], '', get_current_screen() );
if ( false === strpos( $screen_settings, 'id="inkmeter-panel-toggle"' ) ) {
	smoke_fail( 'the Screen Options toggle is missing' );
}

call_user_func( $GLOBALS['smoke_actions']['save_post'], 1 );
if ( [] !== $GLOBALS['smoke_transients'] ) {
	smoke_fail( 'save_post did not clear the cache' );
}

// Only the prefixed copy of wpal may be loaded, never the original namespace.
foreach ( array_merge( get_declared_classes(), get_declared_interfaces() ) as $class ) {
	if ( 0 === strpos( $class, 'Merkushin\\Wpal\\' ) ) {
		smoke_fail( "unprefixed wpal class loaded: {$class}" );
	}
}

echo "Smoke test passed: {$text}\n";
