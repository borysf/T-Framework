<?php
namespace Docs\docs;

use Docs\classes\DocComment;
use Docs\master\master;
use Exception;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;
use System\Component\TComponent;
use System\Web\Action\TActionArgs;
use System\Web\Action\Action;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\Template\Compiler\TTemplateCompiler;
use System\Web\Page\Control\Template\Loader\TTemplateLoader;
use System\Web\Page\Control\Template\TTemplatedControl;

/**
 * Page class for displaying single namespace or class information
 */
class docs extends master {
    private const VIEW_NAMESPACE = 0;
    private const VIEW_CLASS = 1;

    private string $filePath;
    private string $fullClassName;
    private string $namespace;
    private string $className;

    /** Action method displaying single class documentation */
    #[action(name: "classDocs", method: "GET")]
    public function docs(TActionArgs $args) {
        $this->fullClassName = $this->__toSlashName($args->path);
        
        $item = $this->getFromIndexByPath($args->path);

        $this->filePath = str_replace('.', DIRECTORY_SEPARATOR, $args->path).(isset($item['ext']) ? '.'.$item['ext'] : '');

        $this->LocalPathRoot->text = $this->rootDir;
        $this->LocalPath->text = $this->filePath;

        if (isset($item['sub'])) {
            $this->TitleClassName->text = $this->fullClassName;
            $this->Views->activeViewIndex = self::VIEW_NAMESPACE;
            $this->SubIndexTree->setData($item['sub']);
            $this->Type->text = 'namespace';
            return;
        } else {
            $ref = new ReflectionClass('\\'.$this->fullClassName);
            $this->Type->text = $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class');
        }

        if ($namespace = $this->__getNamespace($args->path)) {
            $this->namespace = $this->__toSlashName($namespace).'\\';
            $this->className = $this->__getClassName($args->path);
        } else {
            $this->namespace = '';
            $this->className = $args->path;
        }

        $this->TitleNamespace->text = $this->namespace;
        $this->TitleClassName->text = $this->className;

        $this->Views->activeViewIndex = self::VIEW_CLASS;

        $this->Modifiers->text = implode(' ', Reflection::getModifierNames($ref->getModifiers()));

        if ($parent = $ref->getParentClass()) {
            $this->ParentClass->show();
            $this->ParentName->text = $parent->getName();
            $this->ParentName->navigateUrl = ['system:docs:path', [
                'path' => $this->__toDotName($parent->getName())
            ]];
        }

        $this->DocComment->comment = new DocComment($ref);

        $this->TraitsList->display($ref);

        $this->ConstantsList->display($ref, function (ReflectionClassConstant $constant) {
            return !$constant->isPrivate();
        });

        $this->PropsList->display($ref, function (ReflectionProperty $property) {
            $attrs = $property->getAttributes(Prop::class);
            return !empty($attrs);
        });

        $this->ActionMethodsList->display($ref, function (ReflectionMethod $method) {
            return $method->isPublic() && !empty($method->getAttributes(Action::class, ReflectionAttribute::IS_INSTANCEOF));
        });

        $this->ProtectedPropertiesList->display($ref, function (ReflectionProperty $property) {
            return $property->isProtected() && empty($property->getAttributes(Prop::class));
        });

        $this->PublicPropertiesList->display($ref, function (ReflectionProperty $property) {
            return $property->isPublic() && empty($property->getAttributes(Prop::class));
        });

        $this->PublicMethodsList->display($ref, function (ReflectionMethod $method) {
            return $method->isPublic() && empty($method->getAttributes(action::class)) && !(substr($method->getName(), 0, 2) == 'on' && is_subclass_of($method->getDeclaringClass()->getName(), TComponent::class));
        });

        $this->ProtectedMethodsList->display($ref, function (ReflectionMethod $method) {
            return $method->isProtected() && !(substr($method->getName(), 0, 2) == 'on' && is_subclass_of($method->getDeclaringClass()->getName(), TComponent::class));
        });

        $this->EventsList->display($ref, function (ReflectionMethod $method) {
            return substr($method->getName(), 0, 2) == 'on' && is_subclass_of($method->getDeclaringClass()->getName(), TComponent::class);
        });

        $this->Source->text = str_replace('&nbsp;', ' ', highlight_file($ref->getFileName(), true));

        if (is_subclass_of($ref->getName(), TTemplatedControl::class)) {
            try {
                $paths = TTemplateLoader::getPaths($ref->getName());
                
                $this->SourceTemplateStr->text = \Docs\master\highlight_html(file_get_contents($paths['tpl']));
                $this->SourceTemplate->visible = true;

                $this->SourceTemplateCompiled->text = highlight_string(TTemplateCompiler::compileFile($paths['tpl'], $this->className.'_template', null), true);
                $this->SourceTemplateCompiled->visible =true;
            } catch(Exception $e) {}
        }
    }
    
    private function __toDotName(string $value) : string {
        return str_replace('\\', '.', $value);
    }

    private function __toSlashName(string $value) : string {
        return str_replace('.', '\\', $value);
    }

    private function __getNamespace(string $value, string $separator = '.') : ?string {
        $dotPos = strrpos($value, $separator);

        if ($dotPos !== false) {
            return substr($value, 0, $dotPos);
        } else {
            return null;
        }
    }

    private function __getClassName(string $value, string $separator = '.') : ?string {
        $dotPos = strrpos($value, $separator);

        if ($dotPos !== false) {
            return substr($value, $dotPos + 1);
        } else {
            return null;
        }
    }
}