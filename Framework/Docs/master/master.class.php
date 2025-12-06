<?php
namespace Docs\master;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use System\Http\Error\THttpError;
use System\Http\THttpCode;
use System\TApplication;
use System\Web\Page\TPage;
use System\Web\Action\TActionArgs;

ini_set('highlight.string', '#DD0000');
ini_set('highlight.comment', 'darkgray');
ini_set('highlight.keyword', '#936');
ini_set('highlight.default', '#00598f');
ini_set('highlight.html', '#000000');

function highlight_html(string $text) : string {
    $dirs = [];
    $comms = [];

    $text = preg_replace_callback('{(<!--)(.*)(-->)}sU', function ($match) use (&$comms) {
        $comms[] = $match;
        return '{{{__comm:'.(count($comms) - 1).'}}}';
    }, $text);

    $text = preg_replace_callback('{(<%\s*)(.*)(\s*%>)}sU', function ($match) use (&$dirs) {
        $dirs[] = $match;
        return '{{{__dir:'.(count($dirs) - 1).'}}}';
    }, $text);

    $text = str_replace('&', '&amp;', $text);

    $highlightParams = function (string $params) {
        return preg_replace_callback('{([^=]+)=(["\'])((?!\2)([^"\']+))*\2}', function ($match) {
            return '<span class="html-attr">'.$match[1].'=</span><span class="html-attr-value">'.htmlspecialchars($match[2].$match[3].$match[2]).'</span>';
        }, $params);
    };

    $text = preg_replace_callback('{(<)([^>]+)(>)}', function ($match) use ($highlightParams) {
        $ret = '<span class="html-tag-begin">&lt;</span>';

        if (preg_match('{^(/?(?:(?:com|prop|ref|[A-Z]):)?)?([\.a-z0-9]+)(.*)}i', $match[2], $tagMatch)) {                
            if (in_array($tagMatch[1], ['com:', '/com:', 'prop:', '/prop:', 'ref:', '/ref:']) || preg_match('{^[A-Z]}', $tagMatch[1])) {
                $ret .= '<span class="control-tag-'.trim($tagMatch[1], '/:').'">'.$tagMatch[1].'<span class="control-tag">'.htmlspecialchars($tagMatch[2]).'</span>'.$highlightParams($tagMatch[3]).'</span>';
            } else {
                $ret .= '<span class="html-tag-name">'.$tagMatch[1].htmlspecialchars($tagMatch[2]).'</span>'.$highlightParams($tagMatch[3]);
            }
        } else {
            $ret .= '<span class="html-unknown">'.htmlspecialchars($match[2]).'</span>';
        }

        $ret .= '<span class="html-tag-end">&gt;</span>';

        return $ret;
    }, $text);

    $text = preg_replace_callback('/{{{__dir:([\d+])}}}/', function ($match) use (&$dirs) {
        return '<span class="html-directive">'.htmlspecialchars($dirs[$match[1]][0]).'</span>';
    }, $text);

    $text = preg_replace_callback('/{{{__comm:([\d+])}}}/', function ($match) use (&$comms) {
        return '<span class="html-comment">'.htmlspecialchars($comms[$match[1]][0]).'</span>';
    }, $text);

    return $text;
}

/**
 * Master page for all Documentation pages.
 * 
 * This page is responsible for reading source code directory and
 * preparing left menu of framework classes. Sub pages extends this
 * class and renders their content using TContent control.
 */
class master extends TPage {
    protected string $rootDir;
    private array $filter = ['__cache'];
    private array $classIndex = [];

    private const NAME_KEY = 'name';
    private const PATH_KEY = 'path';
    private const SUB_KEY = 'sub';

    protected function Page_Init(TActionArgs $args) : void {
        $ref = new ReflectionClass(TApplication::class);
        $this->rootDir = dirname(dirname($ref->getFileName())).DIRECTORY_SEPARATOR;

        $this->classIndex = $this->__createIndex();
    }


    protected function Page_Load(TActionArgs $args) : void {
        $this->IndexTree->setData($this->classIndex);
    }

    protected function getFromIndexByPath(string $path) : ?array {
        $ex = explode('.', $path);

        $current = &$this->classIndex;

        $last = count($ex) - 1;
        foreach ($ex as $k => $v) {
            if ($last === $k) {
                return $current[$v];
            }

            if (isset($current[$v]['sub'])) {
                $current = &$current[$v]['sub'];
            } else {
                throw new THttpError(THttpCode::NOT_FOUND);
            }
        }

        return $current;
    }

    private function __createIndex() : array {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootDir));
        
        $namespaces = [];

        foreach ($it as $file) {
            $path = (string) $file;

            if (substr($path, strrpos($path, '.')) == '.php') {
                $path = str_replace($this->rootDir, '', $path);    

                $pathArray = explode(DIRECTORY_SEPARATOR, $path);

                if (!empty(array_intersect($pathArray, $this->filter))) {
                    continue;
                }

                $current = &$namespaces;

                $classPath = [];

                $count = count($pathArray) - 1;

                foreach ($pathArray as $k => $item) {
                    if ($k === $count) {
                        $ext = substr($item, strpos($item, '.') + 1);
                        $item = substr($item, 0, strpos($item, '.'));
                    }

                    $classPath[] = $item;

                    if (!isset($current[$item])) {
                        if ($k !== $count) {
                            $current[$item] = [self::NAME_KEY => $item, self::PATH_KEY => implode('.', $classPath), self::SUB_KEY => []];
                        } else {
                            $current[$item] = [self::NAME_KEY => $item, self::PATH_KEY => implode('.', $classPath), 'ext' => $ext];
                        }
                    }

                    if ($k !== $count) {
                        $current = &$current[$item][self::SUB_KEY];
                    }
                }
            }
        }

        $this->__sort($namespaces);

        return $namespaces;
    }

    private function __sort(&$array) {
        uasort($array, function ($a, $b) {
            $isNodeA = isset($a[self::SUB_KEY]);
            $isNodeB = isset($b[self::SUB_KEY]);

            if (!$isNodeA && $isNodeB) {
                return -1;
            }

            if (!$isNodeB && $isNodeA) {
                return 1;
            }

            return strcmp($a[self::NAME_KEY], $b[self::NAME_KEY]);
        });

        foreach ($array as &$v) {
            if (isset($v[self::SUB_KEY])) {
                $this->__sort($v[self::SUB_KEY]);
            }
        }
    }
}