<?php

defined('ABSPATH') || exit;

class BSC_Theme_Importer_PPU_FileManager
{
    private string $baseDir;
    private string $zipFile;
    private string $photosDir;

    public function __construct($baseDir)
    {
        $this->baseDir = untrailingslashit((string) $baseDir);
        $this->zipFile = trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . BSC_Theme_Importer_PPU_Config::getPhotoZipFilename();
        $this->photosDir = trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . BSC_Theme_Importer_PPU_Config::getExtractedFolderName();
    }

    public function getSubfolders(): array
    {
        if (!is_dir($this->photosDir)) {
            return [];
        }

        $folders = array_filter((array) glob($this->photosDir . '/*'), 'is_dir');
        natcasesort($folders);

        return array_values($folders);
    }

    public function getFilesInFolder($folder): array
    {
        if (!is_dir($folder)) {
            return [];
        }

        $files = array_filter((array) glob(trailingslashit((string) $folder) . '*'), static function ($file): bool {
            if (!is_file($file)) {
                return false;
            }

            $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
        });

        usort($files, static function ($left, $right): int {
            return strnatcasecmp(basename((string) $left), basename((string) $right));
        });

        return array_values($files);
    }

    public function isFolderValid($folderName): bool
    {
        $prefixes = array_map('preg_quote', BSC_Theme_Importer_PPU_Config::getSupportedPrefixes());
        return (bool) preg_match('/^(' . implode('|', $prefixes) . ')_\d+$/', (string) $folderName);
    }

    public function extractGroupAndProductId($folderName): ?array
    {
        $prefixes = array_map('preg_quote', BSC_Theme_Importer_PPU_Config::getSupportedPrefixes());

        if (preg_match('/^(' . implode('|', $prefixes) . ')_(\d+)$/', (string) $folderName, $matches)) {
            return [
                'group'      => $matches[1],
                'product_id' => $matches[2],
                'sku'        => "BSC:{$matches[1]}:{$matches[2]}",
            ];
        }

        return null;
    }

    public function getUploadDirectory(): string
    {
        return BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory();
    }

    public function cleanAndExtractZip(): void
    {
        if (!file_exists($this->zipFile)) {
            throw new RuntimeException("ZIP file not found: {$this->zipFile}");
        }

        if (!wp_mkdir_p(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory())) {
            throw new RuntimeException('Could not create product photo upload directory.');
        }

        if (file_exists($this->photosDir) && is_dir($this->photosDir)) {
            $this->deleteFolder($this->photosDir);
        }

        $this->unzipFile($this->zipFile, BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory());
        $this->normalizeExtractedFolder(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory());

        if (!is_dir($this->photosDir)) {
            throw new RuntimeException("Expected extracted folder not found: {$this->photosDir}");
        }
    }

    private function deleteFolder(string $folder): void
    {
        $base = realpath(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory());
        $target = realpath($folder);
        $baseWithSeparator = $base ? rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';

        if (!$base || !$target || $target === $base || strpos($target, $baseWithSeparator) !== 0) {
            throw new RuntimeException('Refusing to delete a folder outside the product photo directory.');
        }

        foreach ((array) scandir($folder) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $folder . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->deleteFolder($itemPath);
            } else {
                wp_delete_file($itemPath);
            }
        }

        rmdir($folder);
    }

    private function unzipFile(string $zipFilePath, string $extractTo): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Failed to open ZIP file: {$zipFilePath}");
        }

        $extractRoot = realpath($extractTo);

        if (!$extractRoot) {
            $zip->close();
            throw new RuntimeException("Extraction directory does not exist: {$extractTo}");
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);

            if (!$entry || $this->isUnsafeZipEntry($entry)) {
                $zip->close();
                throw new RuntimeException('ZIP contains an unsafe path.');
            }

            if (!$this->isAllowedZipEntry($entry)) {
                $zip->close();
                throw new RuntimeException('ZIP contains a non-image file: ' . basename((string) $entry));
            }
        }

        $zip->extractTo($extractTo);
        $zip->close();
    }

    private function isUnsafeZipEntry(string $entry): bool
    {
        $normalized = str_replace('\\', '/', $entry);
        $parts = array_filter(explode('/', $normalized), static fn($part): bool => $part !== '');

        return in_array('..', $parts, true)
            || strpos($normalized, ':') !== false
            || substr($normalized, 0, 1) === '/';
    }

    private function isAllowedZipEntry(string $entry): bool
    {
        $normalized = str_replace('\\', '/', $entry);

        if (substr($normalized, -1) === '/' || strpos($normalized, '__MACOSX/') === 0) {
            return true;
        }

        $basename = strtolower(basename($normalized));

        if (in_array($basename, ['.ds_store', 'thumbs.db'], true)) {
            return true;
        }

        return in_array(strtolower(pathinfo($normalized, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function normalizeExtractedFolder(string $extractTo): void
    {
        $originalFolder = trailingslashit($extractTo) . 'FOTOS PAG WEB NOMENCLATURA';
        $newFolder = trailingslashit($extractTo) . BSC_Theme_Importer_PPU_Config::getExtractedFolderName();

        if (is_dir($originalFolder) && !is_dir($newFolder)) {
            rename($originalFolder, $newFolder);
        }
    }
}
