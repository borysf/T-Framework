<?php
/**
 * Automatically lookups classes and loads them
 */
class TAutoloader {
    private static ?string $_projectRoot = null;
    private static ?string $_frameworkRoot = null;
    private static ?string $_projectsRoot = null;
    private static array $_resolvedComs = [];

    public static function setProjectDir(string $dir) {
        self::$_projectRoot = $dir;

        self::__setupComposerAutoLoader();
    }

    /**
     * Resolves com name (from templates) to namespaced class name
     */
    public static function resolveComName(string $comName) : ?string {
        if (isset(self::$_resolvedComs[$comName])) {
            return self::$_resolvedComs[$comName];
        }

        if (!preg_match('{^(\.?[a-z_][a-z0-9_]*)+$}i', $comName)) {
            throw new Exception('Invalid com name: '.$comName);
        }

        if ($comName[0] == '.') {
            $ns = explode(DIRECTORY_SEPARATOR, rtrim(preg_replace('{^'.preg_quote(self::$_projectsRoot).'}', '', self::$_projectRoot), DIRECTORY_SEPARATOR));

            if ($ns[0] == 'Framework') {
                unset($ns[0]);
            }

            $root = self::$_projectRoot;
        } else {
            $ns = ['System', 'Web', 'Page', 'Control', 'Core'];
            $root = self::$_frameworkRoot.implode(DIRECTORY_SEPARATOR, $ns).DIRECTORY_SEPARATOR;
        }
        
        $classPath = explode('.', ltrim($comName, '.'));
        $className = array_pop($classPath);

        $path = empty($classPath) ? $root : $root.implode(DIRECTORY_SEPARATOR, $classPath).DIRECTORY_SEPARATOR;

        if (is_dir($path)) {
            if (is_file($path.$className.'.class.php')) {
                $ns = array_merge($ns, $classPath, [$className]);

                self::$_resolvedComs[$comName] = implode('\\', $ns);
                return self::$_resolvedComs[$comName];
            }

            if (is_file($path.'T'.$className.'.class.php')) {
                $ns = array_merge($ns, $classPath, ['T'.$className]);

                self::$_resolvedComs[$comName] = implode('\\', $ns);
                return self::$_resolvedComs[$comName];
            }

            if (is_file($path.$className.DIRECTORY_SEPARATOR.$className.'.class.php')) {
                $ns = array_merge($ns, $classPath, [$className, $className]);

                self::$_resolvedComs[$comName] = implode('\\', $ns);
                return self::$_resolvedComs[$comName];
            }

            if (is_file($path.$className.DIRECTORY_SEPARATOR.'T'.$className.'.class.php')) {
                $ns = array_merge($ns, $classPath, [$className, 'T'.$className]);

                self::$_resolvedComs[$comName] = implode('\\', $ns);
                return self::$_resolvedComs[$comName];
            }
        }

        return null;
    }

    private static function __setupComposerAutoLoader() {
        if (self::$_projectRoot) {
            $composerAutoload = self::$_projectRoot.'__composer'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

            if (file_exists($composerAutoload)) {
                require_once $composerAutoload;
            }
        }
    }

    public static function __init() {
        self::$_frameworkRoot = dirname(__FILE__).DIRECTORY_SEPARATOR;
        self::$_projectsRoot = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR;

        spl_autoload_register(['TAutoloader', 'load']);
    }

    public static function load(string $className) {
        $parts = explode('\\', $className);
        $path = implode(DIRECTORY_SEPARATOR, $parts);

        switch ($parts[0]) {
            case 'Docs':
                return self::_loadSystemClass($path, end($parts));

            case 'System':
                return self::_loadSystemClass($path, end($parts));
            
            case 'Project':
                return self::_loadProjectClass($path, end($parts));
            
            default:
                self::__setupComposerAutoLoader();
        }
    }

    private static function _loadSystemClass(string $path, string $className) : bool {
        return self::_loadPath(self::$_frameworkRoot, $path, $className);
    }

    private static function _loadProjectClass(string $path, string $className) : bool {
        $path = explode(DIRECTORY_SEPARATOR, $path);
        $path[0] = 'Project';
        $path = implode(DIRECTORY_SEPARATOR, $path);

        return self::_loadPath(self::$_projectsRoot, $path, $className);
    }

    private static function _loadPath(string $root, string $path, string $className) : bool {
        switch (substr($className, 0, 1)) {
            case 'I':
                $ext = '.interface.php';
                break;

            case 'T':
            default:
                $ext = '.class.php';
        }

        if (file_exists($root.$path.$ext)) {
            require_once $root.$path.$ext;
            return true;
        }

        return false;
    }
}

TAutoloader::__init();