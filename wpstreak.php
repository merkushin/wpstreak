<?php
/*
 * Plugin Name: WP Streak
 *
 * @version   1.0.0
 * @since     1.0.0
*/

namespace Merkushin\Wpstreak;

// Release builds ship dependencies prefixed by wp-scoper in vendor-prefixed/;
// a development checkout uses the regular Composer autoloader.
if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
} else {
	require_once __DIR__ . '/vendor/autoload.php';
}

use Merkushin\Wpstreak\Wpstreak;

$plugin_file = __FILE__;
$plugin = new Wpstreak( $plugin_file );
add_action( 'init', [ $plugin, 'init' ] );
