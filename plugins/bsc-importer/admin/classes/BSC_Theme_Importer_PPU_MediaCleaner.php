<?php

defined( 'ABSPATH' ) || exit;

class BSC_Theme_Importer_PPU_MediaCleaner {

	/**
	 * Clean WordPress media library by deleting attachments matching the given filename patterns.
	 */
	public static function cleanMediaLibrary() {
		global $wpdb;

		$sql          = "
            SELECT p.ID, p.guid
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '_bsc_theme_importer_photo'
            WHERE p.post_type = 'attachment'
            AND (
                pm.meta_value = '1'
                OR p.guid LIKE %s OR p.guid LIKE %s OR p.guid LIKE %s
                OR p.post_title LIKE %s OR p.post_title LIKE %s OR p.post_title LIKE %s
            )
        ";
		$likePatterns = array(
			'%SK\_%\_PHOTO\_%',
			'%HC\_%\_PHOTO\_%',
			'%MK\_%\_PHOTO\_%',
			'%SK\_%\_PHOTO\_%',
			'%HC\_%\_PHOTO\_%',
			'%MK\_%\_PHOTO\_%',
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal media cleanup with prepared LIKE patterns.
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$likePatterns ) );
		if (!empty( $results )) {
			foreach ($results as $attachment) {
				// Delete the attachment and the associated file
				wp_delete_attachment( $attachment->ID, true );
			}
		}

		return $sql;
	}

	/**
	 * Clean WordPress media library by deleting attachments without files.
	 */
	public static function cleanIncompleteMedia() {
		global $wpdb;

		// SQL query to find attachments without files
		$sql = "
            SELECT ID, guid
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
              AND (guid LIKE %s AND post_title = '')
        ";

		// Pattern to match URLs that end with `/`
		$likePattern = '%/';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal media cleanup with prepared LIKE pattern.
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $likePattern ) );
		if (!empty( $results )) {
			foreach ($results as $attachment) {
				// Delete the attachment (doesn't delete any associated file since there isn't one)
				wp_delete_attachment( $attachment->ID, true );
			}
		}

		return $sql;
	}
}
