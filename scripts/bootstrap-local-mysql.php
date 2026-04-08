<?php

declare(strict_types=1);

/**
 * One-time local MySQL setup: creates DB/user `homestead` / `secret` and optionally imports db_rentasuit_php.sql.
 *
 * Usage:
 *   MYSQL_ROOT_PASSWORD='your-root-password' php scripts/bootstrap-local-mysql.php
 *   MYSQL_ROOT_PASSWORD='your-root-password' php scripts/bootstrap-local-mysql.php --import
 *
 * If root has an empty password:
 *   MYSQL_ROOT_PASSWORD='' php scripts/bootstrap-local-mysql.php
 */

$root = __DIR__ . '/..';
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = (int)(getenv('MYSQL_PORT') ?: '3306');

if ( getenv( 'MYSQL_ROOT_PASSWORD' ) === false ) {
	fwrite( STDERR, "Set MYSQL_ROOT_PASSWORD (use MYSQL_ROOT_PASSWORD='' for empty password).\n" );
	fwrite( STDERR, "Example: MYSQL_ROOT_PASSWORD='...' php scripts/bootstrap-local-mysql.php --import\n" );
	exit( 1 );
}

$rootPass = getenv( 'MYSQL_ROOT_PASSWORD' );
$import = in_array( '--import', $argv, true );

$mysqli = @new mysqli( $host, 'root', $rootPass, '', $port );
if ( $mysqli->connect_error ) {
	fwrite( STDERR, "Root connection failed: {$mysqli->connect_error}\n" );
	exit( 1 );
}

$mysqli->set_charset( 'utf8mb4' );

$sqlPath = $root . '/database/setup/homestead-local.sql';
if ( ! is_readable( $sqlPath ) ) {
	fwrite( STDERR, "Missing {$sqlPath}\n" );
	exit( 1 );
}

$sqlRaw = file_get_contents( $sqlPath );
// Strip full-line and trailing -- comments (file has no semicolons inside strings).
$lines = explode( "\n", $sqlRaw );
$buf = [];
foreach ( $lines as $line ) {
	$dash = strpos( $line, '--' );
	if ( $dash !== false ) {
		$line = substr( $line, 0, $dash );
	}
	$buf[] = $line;
}
$sql = implode( "\n", $buf );
$statements = array_filter(
	array_map( 'trim', explode( ';', $sql ) ),
	static fn ( string $s ): bool => $s !== ''
);
foreach ( $statements as $stmt ) {
	if ( ! $mysqli->query( $stmt ) ) {
		fwrite( STDERR, "homestead-local.sql failed: {$mysqli->error}\n---\n{$stmt}\n---\n" );
		exit( 1 );
	}
}

$mysqli->close();

$h = @new mysqli( $host, 'homestead', 'secret', 'homestead', $port );
if ( $h->connect_error ) {
	fwrite( STDERR, "Could not connect as homestead after bootstrap: {$h->connect_error}\n" );
	exit( 1 );
}
$h->close();

echo "MySQL user/database `homestead` is ready.\n";

if ( ! $import ) {
	echo "Optional: add --import to load db_rentasuit_php.sql (app needs this data).\n";
	exit( 0 );
}

$dump = $root . '/db_rentasuit_php.sql';
if ( ! is_readable( $dump ) ) {
	fwrite( STDERR, "Missing {$dump}\n" );
	exit( 1 );
}

$mysqlBin = getenv( 'MYSQL_CLI' );
if ( ! $mysqlBin || ! is_executable( $mysqlBin ) ) {
	$candidates = [
		'/usr/local/mysql-9.4.0-macos15-arm64/bin/mysql',
		'/usr/local/mysql/bin/mysql',
		'/opt/homebrew/opt/mysql/bin/mysql',
		'/opt/homebrew/opt/mysql-client/bin/mysql',
	];
	$mysqlBin = null;
	foreach ( $candidates as $c ) {
		if ( is_executable( $c ) ) {
			$mysqlBin = $c;
			break;
		}
	}
	if ( ! $mysqlBin ) {
		foreach ( glob( '/usr/local/mysql-*/bin/mysql', GLOB_NOSORT ) ?: [] as $c ) {
			if ( is_executable( $c ) ) {
				$mysqlBin = $c;
				break;
			}
		}
	}
}
if ( ! $mysqlBin ) {
	fwrite( STDERR, "Set MYSQL_CLI to your mysql client path, or install mysql client.\n" );
	exit( 1 );
}

$cmd = [
	$mysqlBin,
	'-h',
	$host,
	'-P',
	(string) $port,
	'-uhomestead',
	'--password=secret',
	'homestead',
];

$spec = [
	0 => [ 'file', $dump, 'r' ],
	1 => STDOUT,
	2 => PIPE,
];
$proc = proc_open( $cmd, $spec, $pipes );
if ( ! is_resource( $proc ) ) {
	fwrite( STDERR, "Could not run mysql import.\n" );
	exit( 1 );
}
$stderr = stream_get_contents( $pipes[2] );
fclose( $pipes[2] );
$code = proc_close( $proc );
if ( $code !== 0 ) {
	fwrite( STDERR, "Import failed (exit {$code}).\n{$stderr}\n" );
	exit( 1 );
}

echo "Imported db_rentasuit_php.sql into `homestead`.\n";
