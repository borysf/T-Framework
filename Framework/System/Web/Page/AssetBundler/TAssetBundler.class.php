<?php

namespace System\Web\Page\AssetBundler;

use ReflectionClass;
use System\TApplication;
use System\Utils\TMimeType;
use System\Web\Page\TPage;

class TAssetBundler
{
    const SERVE_ALLOWED_MIMETYPES = ['audio/.+', 'video/.+', 'image/.+', 'text/.+', 'application/json'];
    const SERVE_DISALLOWED_EXTENSIONS = ['page', 'tpl', 'scss', 'sass'];

    private static array $_assets = [];
    private static array $_processors = ['scss' => TAssetProcessorScss::class, 'sass' => TAssetProcessorScss::class];

    public static function lookupAssets(string $fileName, array $append = []): void
    {
        self::$_assets = array_merge(self::$_assets, self::getAssetsNearbyFile($fileName, $append, false));
    }

    public static function getAssetPath(string $bundleId, string $fileName): ?string
    {
        $extension = substr($fileName, strrpos($fileName, '.') + 1);

        if (in_array($extension, self::SERVE_DISALLOWED_EXTENSIONS)) {
            return null;
        }

        $manifestFile = TApplication::getRootDir() . '__cache' . DIRECTORY_SEPARATOR . 'assets.bundle' . DIRECTORY_SEPARATOR . $bundleId . DIRECTORY_SEPARATOR . 'bundle.manifest';

        if (!file_exists($manifestFile)) {
            return null;
        }

        $manifest = unserialize(file_get_contents($manifestFile));

        if (isset($manifest['assets'][$fileName])) {
            if (file_exists($manifest['assets'][$fileName]->dest)) {
                return $manifest['assets'][$fileName]->dest;
            }
        }

        $realPath = realpath($manifest['path'] . $fileName);

        if (!$realPath || strpos($realPath, $manifest['path']) !== 0 || !file_exists($realPath)) {
            return null;
        }

        $mimetype = TMimeType::get($realPath);

        foreach (self::SERVE_ALLOWED_MIMETYPES as $allowed) {
            if (preg_match('{^' . $allowed . '$}i', $mimetype)) {
                return $realPath;
            }
        }

        return null;
    }

    public static function buildForPage(TPage $page): array
    {
        $className = $page::class;

        $assets = [];

        while ($className != TPage::class) {
            $assets = array_merge(self::getClassAssets($className), $assets);
            $className = get_parent_class($className);
        }

        $assets = array_merge($assets, self::$_assets);

        return self::processAssets($assets);
    }

    private static function processAssets(array $assets): array
    {
        $cacheRoot = TApplication::getRootDir() . '__cache' . DIRECTORY_SEPARATOR . 'assets.bundle' . DIRECTORY_SEPARATOR;
        $return = [];

        foreach ($assets as $bundleId => [$bundlePath, $bundleAssets]) {
            $cacheDir = $cacheRoot . $bundleId . DIRECTORY_SEPARATOR;

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, recursive: true);
            }

            $manifestFile = $cacheDir . 'bundle.manifest';

            $manifest = ['path' => $bundlePath, 'assets' => []];

            if (file_exists($manifestFile) && TApplication::isProduction()) {
                $manifest = unserialize(file_get_contents($manifestFile));

                foreach ($manifest['assets'] as $asset) {
                    $return[] = $asset;
                }

                continue;
            }

            foreach ($bundleAssets as $assetFileSource => $assetFileDest) {
                $baseName = basename($assetFileSource);
                $extension = substr($baseName, strrpos($baseName, '.') + 1);

                $processor = isset(self::$_processors[$extension])
                    ? new self::$_processors[$extension]($bundlePath, $cacheDir)
                    : new TAssetProcessor($bundlePath, $cacheDir);

                $descriptor = $processor->process(
                    new TAssetDescriptor(
                        isset($manifest['assets'][$assetFileSource])
                            ? get_object_vars($manifest['assets'][$assetFileSource])
                            : [
                                'source'            => $bundlePath . $assetFileSource,
                                'dest'              => $cacheDir . $assetFileDest,
                                'fileName'          => $assetFileDest,
                                'type'              => $extension,
                                'bundleId'          => $bundleId,
                            ]
                    )
                );

                if (!isset($manifest['assets'][$descriptor->fileName])) {
                    $manifest['assets'][$descriptor->fileName] = $descriptor;

                    $return[] = $descriptor;
                }
            }

            file_put_contents($manifestFile, serialize($manifest), LOCK_EX);
        }

        return $return;
    }

    private static function getClassAssets(string $className): array
    {
        $ref = new ReflectionClass($className);

        return self::getAssetsNearbyFile($ref->getFileName(), $ref->getConstant('ASSETS') ?: []);
    }

    public static function getAssetsNearbyFile(string $fileName, array $append = [], bool $includeClassAssets = true)
    {
        $baseName = basename($fileName);
        $className = substr($baseName, 0, strpos($baseName, '.'));

        $classDir = dirname($fileName) . DIRECTORY_SEPARATOR;

        if ($includeClassAssets) {
            $assetsDir = $classDir . 'Assets' . DIRECTORY_SEPARATOR;

            $lookup = [
                $assetsDir => ['index.scss', 'index.sass', 'index.css', 'index.js'],
                $classDir => array_merge([$className . '.scss', $className . '.sass', $className . '.css', $className . '.js'], $append)
            ];
        } else {
            $lookup = [$classDir => $append];
        }

        $assets = [];

        $bundleId = substr(sha1($fileName), 0, 7);

        foreach ($lookup as $path => $files) {
            foreach ($files as $key => $file) {
                $isFileByKey = is_string($key) && file_exists($path . $key);
                $isFileByValue = is_int($key) && file_exists($path . $file);

                if ($isFileByKey || $isFileByValue) {
                    if (!isset($assets[$bundleId])) {
                        $assets[$bundleId] = [$path, []];
                    }

                    $assets[$bundleId][1][$isFileByKey ? $key : $file] = $file;
                }
            }
        }

        return $assets;
    }
}
