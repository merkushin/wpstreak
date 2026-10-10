<?php
/**
 * Limits the wpal classes that ship in a release build to the ones the plugin uses.
 *
 * Usage: php bin/prune-wpal.php <build-dir>
 *
 * Run on the build copy before wp-scoper. It finds the wpal services referenced in
 * <build-dir>/src (`use Merkushin\Wpal\Service\X`, `Merkushin\Wpal\Service\X` and
 * `ServiceFactory::create_x()`), checks that each one exists, and adds exclude_patterns
 * to <build-dir>/composer.json so wp-scoper copies only ServiceFactory and those services:
 * each interface and its Wp* implementation. wpal's Api layer (PHP 8.4+) is always left
 * out, because the plugin supports PHP 7.4.
 *
 * ServiceFactory can stay whole: PHP only loads a service class when its create_*()
 * method runs, so the services that are left out are never loaded.
 *
 * @package Merkushin\Inkmeter
 */

declare( strict_types=1 );

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$build_dir = rtrim( $argv[1] ?? '', '/' );
if ( '' === $build_dir || ! is_dir( $build_dir . '/src' ) || ! is_file( $build_dir . '/composer.json' ) ) {
	fwrite( STDERR, "Usage: php bin/prune-wpal.php <build-dir>\n" );
	exit( 1 );
}

$service_dir = $build_dir . '/vendor/merkushin/wpal/src/Service';
if ( ! is_dir( $service_dir ) ) {
	fwrite( STDERR, "wpal is not installed in {$build_dir}/vendor; run composer install there first.\n" );
	exit( 1 );
}

$services = [];
$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $build_dir . '/src', FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}

	$code = (string) file_get_contents( $file->getPathname() );

	preg_match_all( '/Merkushin\\\\Wpal\\\\Service\\\\([A-Za-z0-9_]+)/', $code, $matches );
	foreach ( $matches[1] as $class ) {
		$services[ preg_replace( '/^Wp(?=[A-Z])/', '', $class ) ] = true;
	}

	preg_match_all( '/ServiceFactory::create_([a-z0-9_]+)\s*\(/', $code, $matches );
	foreach ( $matches[1] as $name ) {
		$services[ str_replace( '_', '', ucwords( $name, '_' ) ) ] = true;
	}
}

$services = array_keys( $services );
sort( $services );

if ( empty( $services ) ) {
	fwrite( STDERR, "No wpal services found in {$build_dir}/src.\n" );
	exit( 1 );
}

$missing = [];
foreach ( $services as $service ) {
	foreach ( [ $service, 'Wp' . $service ] as $class ) {
		if ( ! is_file( "{$service_dir}/{$class}.php" ) ) {
			$missing[] = $class;
		}
	}
}

if ( $missing ) {
	fwrite( STDERR, 'Referenced wpal services do not exist: ' . implode( ', ', $missing ) . "\n" );
	exit( 1 );
}

$composer_file = $build_dir . '/composer.json';
$composer      = json_decode( (string) file_get_contents( $composer_file ), true );

$patterns   = $composer['extra']['wp-scoper']['exclude_patterns'] ?? [];
$patterns[] = '#^src/Api/#';
$patterns[] = '#^src/Wpal\.php$#';
$patterns[] = '#^src/Service/(?!(Wp)?(' . implode( '|', $services ) . ')\.php$)#';

$composer['extra']['wp-scoper']['exclude_patterns'] = $patterns;

file_put_contents( $composer_file, json_encode( $composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );

$total = count( glob( $service_dir . '/*.php' ) );
printf( "wpal: keeping %d of %d service files (%s)\n", count( $services ) * 2, $total, implode( ', ', $services ) );
