<?php

namespace System\Web\Page\AssetBundler;

use System\TApplication;
use System\Web\Scss\TScssCompiler;

class TAssetProcessorScss extends TAssetProcessor
{
    public function process(TAssetDescriptor $descriptor): TAssetDescriptor
    {
        $descriptor->dest = substr($descriptor->dest, 0, strrpos($descriptor->dest, '.')) . '.css';
        $descriptor->fileName = substr($descriptor->fileName, 0, strrpos($descriptor->fileName, '.')) . '.css';
        $descriptor->type = 'css';

        $scss = new TScssCompiler($descriptor->source, TApplication::getRootDir() . 'SCSS', dirname($descriptor->dest));
        $scss->compileToFile($descriptor->dest);

        return $descriptor;
    }
}
