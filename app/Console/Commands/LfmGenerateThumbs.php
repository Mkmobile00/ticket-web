<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class LfmGenerateThumbs extends Command
{
    protected $signature = 'lfm:thumbs {--path=photos/shares}';

    protected $description = 'Generate LFM thumbnails for every image under the given storage/public path (recursive).';

    public function handle(): int
    {
        $base = storage_path('app/public/' . trim($this->option('path'), '/'));

        if (! is_dir($base)) {
            $this->error("Path not found: $base");
            return self::FAILURE;
        }

        $width = (int) config('lfm.thumb_img_width', 200);
        $height = (int) config('lfm.thumb_img_height', 200);
        $thumbFolder = config('lfm.thumb_folder_name', 'thumbs');
        $manager = new ImageManager(new GdDriver());

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iter as $file) {
            if (! $file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . $thumbFolder . DIRECTORY_SEPARATOR)) continue;

            $thumbDir = $file->getPath() . DIRECTORY_SEPARATOR . $thumbFolder;
            $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $file->getFilename();

            if (file_exists($thumbPath)) { $skipped++; continue; }

            if (! is_dir($thumbDir)) {
                @mkdir($thumbDir, 0775, true);
            }

            try {
                $manager->decodePath($file->getPathname())
                    ->cover($width, $height)
                    ->save($thumbPath);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Failed: {$file->getPathname()} — {$e->getMessage()}");
            }
        }

        $this->info("Thumbnails created: {$created}, skipped existing: {$skipped}, failed: {$failed}");
        return self::SUCCESS;
    }
}
