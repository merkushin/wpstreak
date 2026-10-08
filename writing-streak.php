<?php
/**
 * Plugin Name:       Writing Streak
 * Plugin URI:        https://github.com/merkushin/wpstreak
 * Description:       Shows your current writing streak above the Posts list.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Dmitry Merkushin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       writing-streak
 * Domain Path:       /languages
 *
 * @package Merkushin\Wpstreak
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
