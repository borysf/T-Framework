<?php

namespace System\Web\Page\AssetBundler;

class TAssetProcessor
{
    public function process(TAssetDescriptor $descriptor): TAssetDescriptor
    {
        $descriptor->dest = $descriptor->source;

        return $descriptor;
    }
}
