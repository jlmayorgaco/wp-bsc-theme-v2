<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_PPU_Config
{
    public static function getUploadDirectory()
    {
        $uploadDir = wp_upload_dir();
        return untrailingslashit($uploadDir['basedir']);
    }

    public static function getPhotoZipDirectory()
    {
        return self::getUploadDirectory() . '/product_photos_zips';
    }

    public static function getPhotoZipFilename()
    {
        return 'FOTOS_PAG_WEB_NOMENCLATURA.zip';
    }

    public static function getExtractedFolderName()
    {
        return 'FOTOS_PAG_WEB_NOMENCLATURA';
    }

    public static function getSupportedPrefixes()
    {
        return ['SK', 'HC', 'MK'];
    }

    public static function getBatchSize()
    {
        return 500;
    }
}
