<?php
/*
 * Plugin Name: WP Streak
 *
 * @version   1.0.0
 * @since     1.0.0
*/

namespace Merkushin\Wpstreak;

require_once __DIR__ . '/vendor/autoload.php';

use Merkushin\Wpstreak\Wpstreak;

$plugin_file = __FILE__;
$plugin = new Wpstreak( $plugin_file );
add_action( 'init', [ $plugin, 'init' ] );
