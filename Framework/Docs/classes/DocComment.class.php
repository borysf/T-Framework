<?php
namespace Docs\classes;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;

class DocComment {
    public readonly ?string $text;
    public readonly ?string $inheritedFrom;

    public function __construct(ReflectionClass $refClass, ReflectionMethod|ReflectionProperty|ReflectionClassConstant|null $ref = null) {
        $this->_getComment($refClass, $ref);
    }

    private function _getComment(ReflectionClass $refClass, mixed $ref) : void {
        $inheritedFrom = null;
        $text = null;

        if ($ref === null) {
            $text = $refClass->getDocComment();

            if (!$text) {
                do {
                    $refClass = $refClass->getParentClass();
                    if (!$refClass) {
                        break;
                    }
                    $text = $refClass->getDocComment();
                    $inheritedFrom = $refClass;
                } while (!$text && $refClass);
            }
        } else {
            $name = $ref->getName();

            $text = $ref->getDocComment();

            if ($ref->getDeclaringClass()->getName() != $refClass->getName()) {
                $inheritedFrom = $ref->getDeclaringClass();
            }

            while (!$text && $ref && ($refClass = $refClass->getParentClass())) {
                switch($ref::class) {
                    case ReflectionMethod::class:
                        $ref = $refClass->hasMethod($name) ? $refClass->getMethod($name) : null;
                        $inheritedFrom = $refClass;
                        break;
                    case ReflectionProperty::class:
                        $ref = $refClass->hasProperty($name) ? $refClass->getProperty($name) : null;
                        $inheritedFrom = $refClass;
                        break;
                    case ReflectionClassConstant::class:
                        $ref = $refClass->hasConstant($name) ? $refClass->getReflectionConstant($name) : null;
                        $inheritedFrom = $refClass;
                        break;
                    default:
                        $ref = null;
                        break;
                }

                if (!$ref) {
                    break;
                }

                $text = $ref->getDocComment();
            }
        }

        $this->text = $text ? $this->_parseComment($text) : null;
        $this->inheritedFrom = $text && $inheritedFrom ? $inheritedFrom->getName() : null;
    }

    private function _parseComment(string $comment) : string {
        $comment = preg_split('{(?:(?:\r\n)|\r|\n)}', $comment);

        foreach ($comment as &$v) {
            $v = preg_replace('{^\s*/?\*+/?}', '', $v);
            $v = preg_replace('{\*+/$}', '', $v);
        }

        return trim(implode("\n", $comment));
    }
}