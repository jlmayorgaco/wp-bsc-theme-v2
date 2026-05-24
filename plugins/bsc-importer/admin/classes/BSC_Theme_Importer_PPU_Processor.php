<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_PPU_Processor
{
    private BSC_Theme_Importer_PPU_FileManager $fileManager;
    private BSC_Theme_Importer_PPU_ProductManager $productManager;
    private BSC_Theme_Importer_PPU_MediaManager $mediaManager;
    private int $batchSize;

    public function __construct(
        BSC_Theme_Importer_PPU_FileManager $fileManager,
        BSC_Theme_Importer_PPU_ProductManager $productManager,
        BSC_Theme_Importer_PPU_MediaManager $mediaManager,
        int $batchSize
    ) {
        $this->fileManager = $fileManager;
        $this->productManager = $productManager;
        $this->mediaManager = $mediaManager;
        $this->batchSize = $batchSize;
    }

    public function process()
    {
        $batchSize = $this->batchSize;
        $subfolders = $this->fileManager->getSubfolders();
        $limitedSubfolders = array_slice($subfolders, 0, $batchSize);

        $processed = 0;
        $skipped = 0;

        foreach ($limitedSubfolders as $folder) {
            $folderName = basename($folder);
            if (!$this->fileManager->isFolderValid($folderName)) {
                $this->log("Invalid folder skipped: $folderName");
                $skipped++;
                continue;
            }

            $groupData = $this->fileManager->extractGroupAndProductId($folderName);

            if (!$groupData || empty($groupData['sku'])) {
                $this->log("Folder without SKU skipped: $folderName");
                $skipped++;
                continue;
            }

            $product = $this->productManager->getProductBySku($groupData['sku']);

            if (!$product) {
                $this->log("Product not found for SKU: {$groupData['sku']}");
                $skipped++;
                continue;
            }

            $this->processFolder($folder, $product);
            $processed++;

        }

        $this->log("Processor summary: {$processed} folders processed, {$skipped} skipped.");
    }

    private function processFolder($folder, $product)
    {
        $files = $this->fileManager->getFilesInFolder($folder);
        $attachmentIds = [];

        foreach ($files as $file) {
            $attachmentId = $this->mediaManager->uploadImage($file);
            if ($attachmentId) {
                $attachmentIds[] = $attachmentId;
            }
        }

        if (empty($attachmentIds)) {
            $this->log("No valid images found for folder: $folder. Existing product images were left unchanged.");
            return;
        }

        $this->productManager->clearImages($product);
        $this->productManager->attachImages($product, $attachmentIds);

        $baseDir = BSC_Theme_Importer_PPU_Config::getUploadDirectory();
        $logFile = $baseDir . '/product_photos_zips/process.log';
        $text = "Processed folder: $folder for product: {$product->get_name()} (" . count($attachmentIds) . " images)";
        BSC_Theme_Importer_PPU_Init::log( $text, $logFile);
    }

    private function log(string $message): void
    {
        $logFile = trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . 'process.log';
        BSC_Theme_Importer_PPU_Init::log($message, $logFile);
    }
}
