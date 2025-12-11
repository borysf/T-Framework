<?php

namespace System\Web\Page\AssetBundler;

class TAssetDescriptor
{
    public ?string $fileName;
    public ?string $source;
    public ?string $dest;
    public ?string $type;
    public ?string $bundleId;

    public function __construct(array $manifestEntry)
    {
        $this->fileName = isset($manifestEntry['fileName']) ? $manifestEntry['fileName'] : null;
        $this->source = isset($manifestEntry['source']) ? $manifestEntry['source'] : null;
        $this->dest = isset($manifestEntry['dest']) ? $manifestEntry['dest'] : null;
        $this->type = isset($manifestEntry['type']) ? $manifestEntry['type'] : null;
        $this->bundleId = isset($manifestEntry['bundleId']) ? $manifestEntry['bundleId'] : null;
    }
}
