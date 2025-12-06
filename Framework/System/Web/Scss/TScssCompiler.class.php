<?php

namespace System\Web\Scss;

use Exception;
use System\Debug\TDebug;
use System\TApplication;

class TScssCompiler
{
    private string $sourceFile;
    private ?string $loadPath;
    private ?string $cacheDir;
    private array $cache = [];

    public function __construct(string $sourceFile, string $loadPath = null, string $cacheDir = null)
    {
        $this->sourceFile = $sourceFile;
        $this->loadPath = $loadPath;
        $this->cacheDir = $cacheDir;

        if ($this->cacheDir && file_exists($this->cacheDir . DIRECTORY_SEPARATOR . 'scss.json')) {
            try {
                $this->cache = json_decode(file_get_contents($this->cacheDir . DIRECTORY_SEPARATOR . 'scss.json'), true);
            } catch (Exception $e) {
                $this->cache = [];
            }
        }
    }

    private function stripComments(string $text): string
    {
        return preg_replace_callback('/(\/\*[\s\S]*?\*\/)|((?:[^:]|^)\/\/.*$)/mUs', fn ($match) => str_repeat("\n", substr_count($match[1] ?: $match[2], "\n")), $text);
    }

    private function getDependencies(string $sourceFile): array
    {
        if (!file_exists($sourceFile)) {
            throw new TScssCompilerException($sourceFile . ': no such file');
        }

        $contents = file_get_contents($sourceFile);

        $contents = $this->stripComments($contents);

        $imports = [$sourceFile];

        if (preg_match_all('/@(import|use|forward)\s+(?P<files>.+)$/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches['files'] as [$file, $offset]) {
                if (preg_match_all('/(?:"(?P<file1>[^"]+)")|(?:\'(?P<file2>[^\']+)\')/', $file, $files)) {
                    foreach (['file1', 'file2'] as $matchGroup) {
                        if (
                            isset($files[$matchGroup][0]) &&
                            $files[$matchGroup][0] != '' &&
                            ($path = $this->expandImport($files[$matchGroup][0], substr_count($contents, "\n", 0, $offset) + 1, $sourceFile)) &&
                            !in_array($path, $imports)
                        ) {
                            $imports = array_merge($imports, $this->getDependencies($path));
                        }
                    }
                }
            }
        }

        return $imports;
    }

    private function realPath(string $directory, string $import): ?string
    {
        $importDirectory = dirname($import);

        if ($importDirectory != '.') {
            $directory .= $importDirectory . DIRECTORY_SEPARATOR;
            $import = basename($import);
        }

        $lookup = [
            $import, "$import.scss", "$import.sass", "$import.css",
            "_$import.scss", "_$import.sass", "_$import.css"
        ];

        foreach ($lookup as $file) {
            if ($realPath = realpath($directory . $file)) {
                return $realPath;
            }
        }

        return null;
    }

    private function expandImport(string $import, int $lineNo, string $sourceFile): ?string
    {
        $paths = [dirname($sourceFile), $this->loadPath];

        if ($import && !preg_match('{^[a-z]+://.+}', $import)) {
            if (preg_match('{^(\.{,2}/)*[\w\._-]+}', $import)) {
                foreach ($paths as $dir) {
                    if (
                        ($realPath = $this->realPath($dir . DIRECTORY_SEPARATOR, $import)) &&
                        strpos($realPath, TApplication::getRootDir()) == 0
                    ) {
                        return $realPath;
                    }
                }

                throw new TScssCompilerException(
                    $import . ': no such file; search paths: ' . implode(', ', $paths),
                    customLineNo: $lineNo,
                    customFileName: $sourceFile
                );
            }
        }

        return null;
    }

    private function needsCompile(array $filesToCheck): bool
    {
        $return = false;

        foreach ($filesToCheck as $file) {
            $mtime = filemtime($file);
            if (!isset($this->cache[$file]) || $this->cache[$file] != $mtime) {
                $this->cache[$file] = $mtime;
                $return = true;
            }
        }

        return $return;
    }

    private function writeCache(): void
    {
        if ($this->cacheDir) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir);
            }

            file_put_contents(
                $this->cacheDir . DIRECTORY_SEPARATOR . 'scss.json',
                json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );
        }
    }

    public function compileToFile(string $destination = null): string
    {
        $destinationFile = $destination ? $destination : substr($this->sourceFile, 0, strrpos($this->sourceFile, '.')) . '.css';
        $filesToCheck = $this->getDependencies($this->sourceFile);

        if ($this->needsCompile($filesToCheck)) {
            TDebug::log($this->sourceFile . ': recompiling');
            system('sass --no-source-map --load-path="' . $this->loadPath . '" "' . $this->sourceFile . '":"' . $destinationFile . '"');

            $this->writeCache();
        }

        return $destinationFile;
    }
}
