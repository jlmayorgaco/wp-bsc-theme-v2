<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_PPU_MediaManager
{
    public function uploadImage($filePath): ?int
    {
        $filePath = (string) $filePath;

        if (!is_readable($filePath) || !is_file($filePath)) {
            return null;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $fileName = sanitize_file_name(basename($filePath));
        $fileInfo = wp_check_filetype_and_ext($filePath, $fileName);

        if (empty($fileInfo['type']) || strpos((string) $fileInfo['type'], 'image/') !== 0) {
            return null;
        }

        $tmp = wp_tempnam($fileName);

        if (!$tmp || !copy($filePath, $tmp)) {
            if ($tmp) {
                wp_delete_file($tmp);
            }

            return null;
        }

        $fileArray = [
            'name'     => $fileName,
            'tmp_name' => $tmp,
            'type'     => $fileInfo['type'],
            'size'     => filesize($tmp),
            'error'    => 0,
        ];

        $attachmentId = media_handle_sideload($fileArray, 0, null, [
            'post_title'   => preg_replace('/\.[^.]+$/', '', $fileName),
            'post_content' => '',
            'post_status'  => 'inherit',
        ]);

        if (is_wp_error($attachmentId)) {
            wp_delete_file($tmp);
            BSC_Theme_Importer_PPU_Init::log('Image upload failed for ' . $filePath . ': ' . $attachmentId->get_error_message(), trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . 'process.log');
            return null;
        }

        update_post_meta((int) $attachmentId, '_wp_attachment_image_alt', preg_replace('/\.[^.]+$/', '', $fileName));
        update_post_meta((int) $attachmentId, '_bsc_theme_importer_photo', '1');

        return (int) $attachmentId;
    }
}
