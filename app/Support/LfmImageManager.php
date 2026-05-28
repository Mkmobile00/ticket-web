<?php

namespace App\Support;

use Intervention\Image\ImageManager;

/**
 * LFM 2.14 calls $imageManager->read($source) (a v2 method) on the bound
 * Intervention\Image manager, but v3 dropped read() in favour of decode()
 * variants. This subclass restores read() as an alias of decodePath() so
 * LFM uploads, crops and resizes keep working without forking the package.
 */
class LfmImageManager extends ImageManager
{
    public function read(mixed $source): \Intervention\Image\Interfaces\ImageInterface
    {
        if (is_string($source) && @is_file($source)) {
            return $this->decodePath($source);
        }
        return $this->decode($source);
    }
}
