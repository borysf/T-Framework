<?php
namespace System\Web\Page\Control\Template\Loader;

use ReflectionClass;
use System\Debug\TDebug;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\Compiler\TTemplateCompiler;
use System\Web\Page\Control\Template\TTemplate;


/** 
 * Class responsible for loading, compiling and caching templates.
 */
class TTemplateLoader {
    private static $_paths = [];
    
    /**
     * Returns paths and metadata for template associated with given `TTemplatedControl` class.
     */
    public static function getPaths(string $templateControlClassName) : array {
        if (!isset(self::$_paths[$templateControlClassName])) {
            $ref = new ReflectionClass($templateControlClassName);
            $filename = $ref->getFileName();
            $dir = dirname($filename).DIRECTORY_SEPARATOR;
            $ext = $ref->getConstant('TEMPLATE_EXTENSION');
            $ext = $ext ?: '.tpl';
            $filename = basename($filename);
            $filename = substr($filename, 0, strpos($filename, '.class.php'));
            $tpl = $dir.$filename.$ext;

            if (!file_exists($tpl)) {
                throw new TTemplateLoaderException('template file could not be found: '.$tpl);
            }

            return self::$_paths[$templateControlClassName] = [
                'dir' => $dir,
                'tpl' => $tpl,
                'cache' => $dir.'__cache'.DIRECTORY_SEPARATOR,
                'mtime' => filemtime($tpl)
            ];
        }

        return self::$_paths[$templateControlClassName];
    }

    /**
     * Compiles, caches and loads a template associated with given `TTemplatedControl` class and 
     * sets `TControl $ownerControl` as the owner of the loaded template.
     */
    public static function loadForClass(string $templateControlClassName, TControl $ownerControl) : TTemplate {
        $templateClass = strtr($templateControlClassName, '\\', '_').'_Template';
        
        if ($template = self::__loadByTemplateClassName($templateClass, $ownerControl)) {
            return $template;
        }

        $paths = self::getPaths($templateControlClassName);

        $sourcePath = $paths['tpl'];

        $cacheDir = $paths['cache'];
        $compiledPath = $cacheDir.$templateClass.'.template.php';
        
        if (self::__templateNeedsCompile($paths['mtime'], $compiledPath)) {
            return self::__loadFromSource($templateClass, $sourcePath, $ownerControl);
        } else {
            return self::__loadFromCompiled($templateClass, $compiledPath, $ownerControl);
        }
    }

    private static function __loadByTemplateClassName(string &$templateClass, TControl $ownerControl) : ?TTemplate {
        if (class_exists($templateClass, false)) {
            $template = new $templateClass($ownerControl);

            $ownerControl->addControl($template);

            return $template;
        }

        return null;
    }

    private static function __loadFromSource(string $templateClass, string $sourceFileName, TControl $ownerControl) : TTemplate {
        if ($template = self::__loadByTemplateClassName($templateClass, $ownerControl)) {
            return $template;
        }

        $paths = self::getPaths($ownerControl::class);

        $cacheDir = $paths['cache'];

        // $compiler = new TTemplateCompiler($sourceFileName, $code, $templateClass, $paths['dir']);
        $filename = basename($sourceFileName);
        $filename = substr($filename, 0, strpos($filename, '.'));
        $compiledPath = $cacheDir.DIRECTORY_SEPARATOR.$filename.'.template.php';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }

        $sourceModifyTime = filemtime($sourceFileName);

        if (self::__templateNeedsCompile($sourceModifyTime, $compiledPath)) {
            TDebug::log('Compiling template file', $sourceFileName, $templateClass);

            $compiled = TTemplateCompiler::compileFile($sourceFileName, $templateClass, $ownerControl);
            file_put_contents($compiledPath, $compiled);

            file_put_contents($compiledPath.'.meta.json', json_encode([
                'created' => filectime($compiledPath),
                'modified' => $sourceModifyTime
            ]));
        }

        return self::__loadFromCompiled($templateClass, $compiledPath, $ownerControl);
    }

    private static function __loadFromCompiled(string $templateClass, string $compiledPath, TControl $ownerControl): TTemplate {
        include_once $compiledPath;

        $template = new $templateClass($ownerControl);

        $ownerControl->addControl($template);

        return $template;
    }

    private static function __templateNeedsCompile(int $sourceModifyTime, string $compiledPath) {
        if (!file_exists($compiledPath)) {
            return true;
        }

        if (!file_exists($compiledPath.'.meta.json')) {
            return true;
        }

        $meta = json_decode(file_get_contents($compiledPath.'.meta.json'));

        if (!isset($meta->modified)) {
            return true;
        }

        return $meta->modified != $sourceModifyTime;
    }
}