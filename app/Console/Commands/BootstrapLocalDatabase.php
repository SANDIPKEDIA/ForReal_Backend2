<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BootstrapLocalDatabase extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'db:bootstrap-local
                            {--import : Import db_rentasuit_php.sql after creating the user}
                            {--password= : MySQL root password (otherwise DB_ROOT_PASSWORD from .env or prompt)}';

	/**
	 * @var string
	 */
	protected $description = 'One-time local MySQL: create database/user homestead/secret matching .env';

	public function handle(): int {
		$password = $this->option( 'password' );
		if ( $password === null ) {
			$password = env( 'DB_ROOT_PASSWORD' );
		}
		if ( $password === null && $this->input->isInteractive() ) {
			$password = (string) $this->secret( 'MySQL root password (press Enter if root has no password)' );
		}
		if ( $password === null ) {
			$this->error( 'Provide --password=..., or add DB_ROOT_PASSWORD to .env, or run this command in a terminal (not CI) so it can prompt.' );

			return self::FAILURE;
		}

		$connection = config( 'database.connections.mysql' );
		$args = [ 'php', base_path( 'scripts/bootstrap-local-mysql.php' ) ];
		if ( $this->option( 'import' ) ) {
			$args[] = '--import';
		}

		$this->info( 'Applying database/setup/homestead-local.sql as MySQL root…' );

		$result = Process::timeout( 600 )
			->env( [
				'MYSQL_ROOT_PASSWORD' => $password,
				'MYSQL_HOST' => $connection['host'] ?? '127.0.0.1',
				'MYSQL_PORT' => (string) ( $connection['port'] ?? 3306 ),
			] )
			->run( $args );

		if ( $result->failed() ) {
			$this->error( trim( $result->errorOutput() ) ?: trim( $result->output() ) );
			$this->newLine();
			$this->warn( 'If the homestead user still fails: open Terminal and run the SQL file as MySQL root:' );
			$this->line( '  /usr/local/mysql/bin/mysql -u root -p < database/setup/homestead-local.sql' );
			$this->line( '  /usr/local/mysql/bin/mysql -u homestead -psecret homestead < db_rentasuit_php.sql' );
			$this->newLine();
			$this->warn( 'Or for local dev only, set DB_USERNAME / DB_PASSWORD in .env to a MySQL user that already works (e.g. root).' );

			return self::FAILURE;
		}

		$this->line( $result->output() );

		if ( env( 'DB_ROOT_PASSWORD' ) !== null && env( 'DB_ROOT_PASSWORD' ) !== '' ) {
			$this->warn( 'Remove DB_ROOT_PASSWORD from .env when done (do not commit it).' );
		}

		$this->info( 'Next: php artisan config:clear' );

		return self::SUCCESS;
	}
}
