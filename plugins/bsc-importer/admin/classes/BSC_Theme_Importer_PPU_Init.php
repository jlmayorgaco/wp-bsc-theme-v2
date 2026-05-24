<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_PPU_Init
{
    public static function init()
    {
        $baseDir = BSC_Theme_Importer_PPU_Config::getUploadDirectory();
        $photoDir = BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory();
        $logFile = trailingslashit($photoDir) . 'process.log';

        if (!wp_mkdir_p($photoDir)) {
            throw new RuntimeException('Could not create product photo directory.');
        }

        self::log("BSC_Theme_Importer_PPU_Init: Starting process.", $logFile);

        try {
            $ppuPath = BSC_Theme_Importer_PPU_Config::getUploadDirectory();
            self::log("Upload directory: $ppuPath", $logFile);

            // Step 1: Clean media library
            BSC_Theme_Importer_PPU_MediaCleaner::cleanMediaLibrary();
            self::log("Media library cleaned.", $logFile);

            // Step 2: Clean incomplete media
            BSC_Theme_Importer_PPU_MediaCleaner::cleanIncompleteMedia();
            self::log("Incomplete media cleaned.", $logFile);

            // Step 3: File manager operations
            $ppuFileManager = new BSC_Theme_Importer_PPU_FileManager($ppuPath);
            $ppuFileManager->cleanAndExtractZip();
            self::log("Zip file extracted and processed.", $logFile);

            // Step 4: Initialize managers
            $ppuMediaManager = new BSC_Theme_Importer_PPU_MediaManager();
            $ppuProductManager = new BSC_Theme_Importer_PPU_ProductManager();
            self::log("Media and product managers initialized.", $logFile);

            // Step 5: Process files
            $processor = new BSC_Theme_Importer_PPU_Processor(
                fileManager: $ppuFileManager,
                productManager: $ppuProductManager,
                mediaManager: $ppuMediaManager,
                batchSize: BSC_Theme_Importer_PPU_Config::getBatchSize()
            );

            $processor->process();
            self::log("Processor completed.", $logFile);

        } catch (Throwable $e) {
            self::log("Error: " . $e->getMessage(), $logFile);
        }

        self::log("BSC_Theme_Importer_PPU_Init: Process completed.", $logFile);
    }

    /**
     * Log a message to the specified file.
     */
    public static function log($message, $logFile)
    {
        $timestamp = date('[Y-m-d H:i:s]');
        file_put_contents($logFile, "$timestamp $message" . PHP_EOL, FILE_APPEND);
    }
}
?>
