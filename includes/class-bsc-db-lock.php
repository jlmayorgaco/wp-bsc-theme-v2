<?php
/** Connection-scoped, reentrant locks for operations that must not overlap. */
defined( 'ABSPATH' ) || exit;

final class BSC_DB_Lock {

	private static array $held = array();
	private static int $transaction_depth = 0;

	public static function acquire( string $resource, int $timeout = 5 ): bool {
		global $wpdb;
		$name = 'bsc:' . hash( 'sha224', (string) ( $wpdb->prefix ?? '' ) . ':' . $resource );
		if ( isset( self::$held[ $name ] ) ) {
			++self::$held[ $name ];
			return true;
		}
		if ( ! is_object( $wpdb ) || 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, max( 0, min( 10, $timeout ) ) ) ) ) {
			return false;
		}
		self::$held[ $name ] = 1;
		return true;
	}

	public static function release( string $resource ): void {
		global $wpdb;
		$name = 'bsc:' . hash( 'sha224', (string) ( $wpdb->prefix ?? '' ) . ':' . $resource );
		if ( ! isset( self::$held[ $name ] ) || --self::$held[ $name ] > 0 ) {
			return;
		}
		unset( self::$held[ $name ] );
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	}

	/** @return mixed|WP_Error */
	public static function run( string $resource, callable $callback, int $timeout = 5 ) {
		if ( ! self::acquire( $resource, $timeout ) ) {
			return new WP_Error( 'bsc_lock_timeout', 'La operación está en curso. Intenta nuevamente.' );
		}
		try {
			return $callback();
		} finally {
			self::release( $resource );
		}
	}

	/** Keep domain data and its idempotency marker in the same InnoDB transaction. */
	public static function transaction( callable $callback ) {
		global $wpdb;
		$depth = self::$transaction_depth++;
		$savepoint = 'bsc_transaction_' . $depth;
		if ( false === $wpdb->query( $depth ? 'SAVEPOINT ' . $savepoint : 'START TRANSACTION' ) ) {
			--self::$transaction_depth;
			return new WP_Error( 'bsc_transaction_failed', 'No se pudo iniciar la operación.' );
		}
		try {
			$result = $callback();
			if ( is_wp_error( $result ) ) {
				$wpdb->query( $depth ? 'ROLLBACK TO SAVEPOINT ' . $savepoint : 'ROLLBACK' );
				return $result;
			}
			if ( false === $wpdb->query( $depth ? 'RELEASE SAVEPOINT ' . $savepoint : 'COMMIT' ) ) {
				$wpdb->query( $depth ? 'ROLLBACK TO SAVEPOINT ' . $savepoint : 'ROLLBACK' );
				return new WP_Error( 'bsc_transaction_failed', 'No se pudo confirmar la operación.' );
			}
			return $result;
		} catch ( Throwable $error ) {
			$wpdb->query( $depth ? 'ROLLBACK TO SAVEPOINT ' . $savepoint : 'ROLLBACK' );
			throw $error;
		} finally {
			--self::$transaction_depth;
		}
	}
}
