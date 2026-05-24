<?php

class BSC_Bubble_Points {

	protected const META_KEY = 'bsc_bubble_points';

	/**
	 * Get the current user's bubble points
	 */
	public static function get( $user_id ) {
		return (int) get_user_meta( $user_id, self::META_KEY, true );
	}

	/**
	 * Add points to a user
	 */
	public static function add( $user_id, $points ) {
		$current = self::get( $user_id );
		update_user_meta( $user_id, self::META_KEY, $current + (int) $points );
	}

	/**
	 * Deduct points from a user
	 */
	public static function deduct( $user_id, $points ) {
		$current = self::get( $user_id );
		$new     = max( 0, $current - (int) $points );
		update_user_meta( $user_id, self::META_KEY, $new );
	}

	/**
	 * Set absolute point value
	 */
	public static function set( $user_id, $points ) {
		update_user_meta( $user_id, self::META_KEY, max( 0, (int) $points ) );
	}

	/**
	 * Check if user has enough points
	 */
	public static function has_enough( $user_id, $required_points ) {
		return self::get( $user_id ) >= $required_points;
	}

	/**
	 * Get current user points (shortcut)
	 */
	public static function get_current() {
		return is_user_logged_in() ? self::get( get_current_user_id() ) : 0;
	}
}
