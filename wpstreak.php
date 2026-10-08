<?php
/*
 * Plugin Name: WP Streak
 * Description: Shows your current writing streak above the Posts list.
 * Version:     1.0.0
 * Text Domain: wpstreak
 * Domain Path: /languages
 *
 * @since 1.0.0
*/

namespace Merkushin\Wpstreak;

defined( 'ABSPATH' ) || exit;

// Release builds ship dependencies prefixed by wp-scoper in vendor-prefixed/;
// a development checkout uses the regular Composer autoloader.
if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
} else {
	require_once __DIR__ . '/vendor/autoload.php';
}

add_action( 'init', [ new Wpstreak( __FILE__ ), 'init' ] );
