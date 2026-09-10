<?php
/**
 * Legacy BSC helper bootstrap.
 *
 * Account custom fields are saved by inc/setup/theme-setup.php so validation
 * stays centralized.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format a date in Spanish without depending on the active WordPress locale.
 *
 * @param DateTimeInterface $date Date to format.
 * @return string
 */
function bsc_format_date_es( DateTimeInterface $date ): string {
	$months = array(
		1  => 'enero',
		2  => 'febrero',
		3  => 'marzo',
		4  => 'abril',
		5  => 'mayo',
		6  => 'junio',
		7  => 'julio',
		8  => 'agosto',
		9  => 'septiembre',
		10 => 'octubre',
		11 => 'noviembre',
		12 => 'diciembre',
	);

	$month = (int) $date->format( 'n' );

	return sprintf(
		'%1$s de %2$s de %3$s',
		$date->format( 'j' ),
		$months[ $month ],
		$date->format( 'Y' )
	);
}
