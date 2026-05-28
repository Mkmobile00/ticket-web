<?php
/**
 * Patches unisharp/laravel-filemanager 2.14 to work with Intervention/Image v3.
 * Run automatically on every `composer install` / `composer update` via the
 * post-autoload-dump script in composer.json.
 *
 * Why: LFM 2.14 calls `$image->encodeByMediaType()` which existed in
 * Intervention v2 but was renamed to `encodeUsingMediaType($mediaType)` in v3.
 * The no-arg `encode()` call uses AutoEncoder which preserves the source media
 * type, giving identical behaviour. The custom App\Support\LfmImageManager
 * separately re-adds the `read()` method also removed in v3.
 */

$file = __DIR__ . '/../vendor/unisharp/laravel-filemanager/src/LfmPath.php';

if (!is_file($file)) {
    fwrite(STDERR, "[patch-lfm-vendor] vendor file not found: $file\n");
    exit(0); // do not fail composer
}

$contents = file_get_contents($file);

if (!str_contains($contents, '->encodeByMediaType()')) {
    // already patched (or upstream fixed)
    exit(0);
}

$patched = str_replace(
    "            ->cover(\$thumbWidth, \$thumbHeight)\n            ->encodeByMediaType();",
    "            ->cover(\$thumbWidth, \$thumbHeight)\n            ->encode(); // PATCHED for Intervention/Image v3 compatibility",
    $contents
);

if ($patched === $contents) {
    fwrite(STDERR, "[patch-lfm-vendor] expected pattern not found, skipping\n");
    exit(0);
}

file_put_contents($file, $patched);
echo "[patch-lfm-vendor] LfmPath.php patched for Intervention/Image v3\n";
