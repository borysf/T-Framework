<?php

namespace System\Web\Page\Control\Template\Compiler;

use CompileError;
use ParseError;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use System\Web\Page\Control\Event\TEventArgs;
use System\Web\Page\Control\Prop;
use System\Web\Page\Control\TControl;
use System\Web\Page\Control\Template\TControlPropTemplate;
use System\Web\Page\Control\Template\TTemplate;
use System\Web\Page\Control\Template\TTemplateLiteral;
use System\Web\Page\Control\Template\TTemplateLiteralWhite;
use TAutoloader;
use Throwable;

/**
 * Compiler token
 */
class Token
{
    /** Root token type */
    public const TYPE_ROOT    = 'root';

    /** Control token type */
    public const TYPE_CONTROL = 'control';

    /** Prop token type */
    public const TYPE_PROP    = 'prop';

    /** Literal token type */
    public const TYPE_LITERAL = 'literal';

    public ?string $type = null;
    public ?string $element = null;
    public ?string $tagName = null;
    public ?string $name = null;
    public bool $isClosing = false;
    public bool $isSelfClosing = false;
    public ?string $comName = null;
    public ?string $refName = null;
    public array $props = [];
    public array $children = [];
    public int $lineNo = 0;
}

/**
 * Property with PHP code
 */
class PropValuePhp
{
    public readonly string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}

/**
 * Compiles plain text templates into PHP.
 * Supports both control declaration types: via com `<com:ControlComName>` and via ref to declaration in owner control `<ref:Name>`.
 */
class TTemplateCompiler
{
    private const CONTROL_DECLARE_PREFIX            = '(?:(?:com:)(?P<comName>[a-zA-Z_\.][a-zA-Z0-9_\.]*))|(?:(?:ref:)?(?P<refName>[A-Z][a-zA-Z0-9_]+))|(?:html:(?P<htmlName>[a-zA-Z][a-zA-Z0-9]*))';
    private const PROPERTY_DECLARE_PREFIX           = 'prop:';

    private const TOKENIZER_SPLIT                   = '{((?:<!--)|(?:-->)|(?:<[^%>]+>))}';
    private const CONTROL_TAG_MATCH                 = '{^<(?:(?P<isClosing>/?)(?P<tagName>' . self::CONTROL_DECLARE_PREFIX . ')(\s+[\.a-zA-Z0-9-]+\s*=\s*"[^"]*")*\s*(?P<isSelfClosing>/?))>$}';
    private const PROPERTY_TAG_MATCH                = '{^<(?:(?P<isClosing>/?)(?P<tagName>' . self::PROPERTY_DECLARE_PREFIX . '(?P<name>[a-zA-Z_][a-zA-Z0-9]*)))(\s+[\.a-zA-Z0-9-]+\s*=\s*"[^"]*")*\s*(?P<isSelfClosing>/?)>$}';
    private const COMMENT_BEGIN_MATCH               = '{^<!--$}';
    private const COMMENT_END_MATCH                 = '{^-->$}';

    private const ATTRIBUTES_MATCH                  = '{\s+(?P<name>[\.a-zA-Z0-9-]+)\s*=\s*"(?P<value>[^"]*)"}';

    private const CONTROL_PROP_ID_MATCH             = '{^[A-Z][a-zA-Z0-9_]*$}';
    private const CONTROL_PROP_NAME_MATCH           = '{^[a-z][a-zA-Z0-9_\.-]*$}';

    private const SUPPORTED_PROP_ATTRIBUTES         = ['value', 'ltrim', 'rtrim', 'trim'];

    private readonly string $templateFileName;
    private readonly string $templateSourceCode;
    private readonly string $outputTemplateClassName;
    private readonly ?TControl $ownerControl;

    private readonly int $modifiedAt;
    private readonly int $compiledAt;
    private int $subTemplatesCount = 0;

    private array $uses = [TTemplate::class => 'TTemplate', TEventArgs::class => 'TEventArgs'];
    private array $sub = [];
    private array $phps = [];

    private function __construct(
        string $templateFileName,
        string $outputTemplateClassName,
        string $templateSourceCode = '',
        ?TControl $ownerControl = null
    ) {
        $this->templateFileName          = $templateFileName;
        $this->templateSourceCode        = $this->__preProcessCode($templateSourceCode);
        $this->outputTemplateClassName   = $outputTemplateClassName;
        $this->ownerControl              = $ownerControl;

        $this->modifiedAt = is_file($templateFileName) ? filemtime($templateFileName) : 0;
        $this->compiledAt = time();
    }

    /**
     * Compiles given template file (`$templateFileName`) and creates new class
     * (`$outputTemplateClassName`) extending `TTemplate`. Additionally takes
     * `TControl $ownerControl` to support referenced controls. Returns compiled PHP code.
     */
    public static function compileFile(
        string $templateFileName,
        string $outputTemplateClassName,
        ?TControl $ownerControl = null
    ): string {
        return self::compileSource(
            file_get_contents($templateFileName),
            $outputTemplateClassName,
            $templateFileName,
            $ownerControl
        );
    }

    /**
     * Compiles given template source code (`$templateSourceCode`) and creates new
     * class (`$outputTemplateClassName`) extending `TTemplate`. Additionally takes
     * `TControl $ownerControl` to support referenced controls. In case of compilation
     * from user-specified source code, `$templateFileName` can be whatever, however
     * it will be displayed during compilation errors and written into compiled template.
     * Returns compiled PHP code.
     */
    public static function compileSource(
        string $templateSourceCode,
        string $outputTemplateClassName,
        string $templateFileName = '<none>',
        ?TControl $ownerControl = null
    ): string {
        $compiler = new self($templateFileName, $outputTemplateClassName, $templateSourceCode, $ownerControl);

        $namedMembers = [];
        $membersEvents = [];
        $code = $compiler->__compileTokens($compiler->__tokenize(), $namedMembers, $membersEvents);

        $return = self::__generateHeader($compiler->uses);

        $return .= self::__generateClassDef($outputTemplateClassName);
        $return .= self::__generateMembers(
            $namedMembers,
            $templateFileName,
            $compiler->modifiedAt,
            $compiler->compiledAt
        );
        $return .= self::__generateBody($code, $membersEvents, $namedMembers);
        $return .= self::__generateFooter() . "\n\n";

        foreach ($compiler->sub as $subClassName => [$subCode, $subNamed, $subEvents]) {
            $return .= self::__generateClassDef($subClassName);
            $return .= self::__generateMembers(
                $subNamed,
                $templateFileName,
                $compiler->modifiedAt,
                $compiler->compiledAt
            );
            $return .= self::__generateBody($subCode, $subEvents, $subNamed);
            $return .= self::__generateFooter() . "\n\n";
        }

        return rtrim($return);
    }

    private static function __generateHeader(array $uses): string
    {
        ksort($uses);

        $result = "<?php\n";

        foreach ($uses as $namespace => $className) {
            $result .= "use $namespace" . (is_array($className) ? ' as ' . $className[0] : '') . ";\n";
        }

        return $result . "\n";
    }

    private static function __generateClassDef(string $className): string
    {
        return "class $className extends TTemplate {\n";
    }

    private static function __generateMembers(
        array $members,
        string $sourceFileName,
        int $modified,
        int $compiled
    ): string {
        $items = [];

        if (!empty($members)) {
            foreach ($members as $name => $type) {
                $items[] = "   public readonly $type \$$name;";
            }
            sort($items);
        }

        $items = array_merge([
            '   const TEMPLATE_SOURCE_FILE        = ' . self::__quote($sourceFileName) . ';',
            '   const TEMPLATE_SOURCE_MODIFIED_AT = ' . $modified . ';',
            '   const TEMPLATE_SOURCE_COMPILED_AT = ' . $compiled . ';',
            '',
        ], $items);

        return implode("\n", $items) . "\n\n";
    }

    private static function __generateBody(string $code, array $events, array $members): string
    {
        $eventsStr = [];

        foreach ($events as $controlName => $controlEvents) {
            foreach ($controlEvents as $eventName => $eventCallback) {
                $eventsStr[] = "      {$controlName}->on('$eventName', \$this->ownerControl->$eventCallback);";
            }
        }

        $eventsStr = !empty($eventsStr) ? implode("\n", $eventsStr) . "\n\n" : "";

        $namedControls = '      $this->namedControls = ';

        if (!empty($members)) {
            $named = [];
            foreach ($members as $name => $_) {
                $named[] = "         '$name' => \$this->$name";
            }
            sort($named);

            $namedControls .= "[\n" . implode(",\n", $named) . "\n      ];\n";
        } else {
            $namedControls .= "[];\n";
        }

        $onCreate = "protected function onCreate(?TEventArgs \$args) : void {\n      parent::onCreate(\$args);\n\n";
        $unpackData = "      if (!empty(\$this->__data)) foreach (\$this->__data as \$varName => \$varValue) { $\$varName = \$varValue; }\n\n";

        return "   {$onCreate}{$unpackData}{$code}{$eventsStr}{$namedControls}  }\n";
    }

    private static function __generateFooter()
    {
        return "}";
    }

    private function __preProcessCode(string $code): string
    {
        $code = preg_replace_callback('{(<%#?\s*)(?P<php>.*)(\s*%>)}sU', function ($match) {
            $this->phps[] = trim($match['php']);
            return '{{{___php:' . (count($this->phps) - 1) . '}}}';
        }, $code);

        return $code;
    }

    private function __resolveClassNameByRef(Token $token)
    {
        if (!$this->ownerControl) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $token->lineNo,
                'Missing control reference.',
                reason: 'Referenced control `<' . $token->tagName . '> requires `TControl $ownerControl` ' .
                    'to be passed to compiler'
            );
        }

        try {
            $ref = new ReflectionProperty($this->ownerControl, $token->refName);
        } catch (ReflectionException $e) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $token->lineNo,
                'Broken control reference.',
                reason: 'Referenced control `<' . $token->tagName . '>` not defined in `' . $this->ownerControl::class . '`. ' .
                    'Template owner control must define a property named `' . $token->refName . '` in order ' .
                    'to create a reference.'
            );
        }

        $type = '' . $ref->getType();

        if (!is_subclass_of($type, TControl::class)) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $token->lineNo,
                'Reference type mismatch',
                reason: 'Referenced control `<' . $token->tagName . '> must refer to `' . TControl::class . '` ' .
                    'instance, but `' . $type . '` found.'
            );
        }

        return $type;
    }

    private function __compileTokens(
        array $tokens,
        array &$named,
        array &$events,
        int $level = 2,
        bool $isRoot = true,
        ?string $parentClassName = null,
        ?Token $parentToken = null
    ): string {
        static $ctlNum = 0;

        $code = '';
        $indent = str_repeat('   ', $level);

        foreach ($tokens as $token) {
            $className = null;

            switch ($token->type) {
                case Token::TYPE_LITERAL:
                    $token->type = Token::TYPE_CONTROL;

                    if (!$token->element) {
                        continue 2;
                    }

                    $token->props['text'] = $token->element;

                    $className = preg_match('{^\s*$}', $token->element)
                        ? TTemplateLiteralWhite::class
                        : TTemplateLiteral::class;

                case Token::TYPE_CONTROL:
                    if (!$className) {
                        if ($token->refName) {
                            $className = $this->__resolveClassNameByRef($token);
                            $token->props['id'] = $token->refName;
                        } else {
                            $className = TAutoloader::resolveComName($token->comName);

                            if (!$className) {
                                throw new TTemplateCompilerException(
                                    $this->templateFileName,
                                    $token->lineNo,
                                    'Could not resolve com name: `' . $token->comName . '`',
                                    reason: 'Most likely the class file does not exist at required location or declared ' .
                                        'namespace and/or class name is wrong.'
                                );
                            }

                            if (!is_subclass_of($className, TControl::class)) {
                                throw new TTemplateCompilerException(
                                    $this->templateFileName,
                                    $token->lineNo,
                                    'Class `' . $className . '` must be a subclass of `' . TControl::class . '`'
                                );
                            }
                        }
                    }

                    if ($parentClassName && ($ignoredChildrenTypes = $parentClassName::CHILDREN_TYPES_IGNORE)) {
                        if (in_array($className, $ignoredChildrenTypes)) {
                            continue 2;
                        }
                    }

                    if ($parentClassName && $parentToken) {
                        $allowedChildrenTypes = $parentClassName::CHILDREN_TYPES_ALLOW;

                        if ($allowedChildrenTypes !== null) {
                            if ($allowedChildrenTypes === false) {
                                throw new TTemplateCompilerException(
                                    $this->templateFileName,
                                    $token->lineNo,
                                    'Control of type `' . $parentClassName . '` does not allow any children controls',
                                    reason: $parentClassName . '::CHILDREN_TYPES_ALLOW = ' .
                                        str_replace('\\\\', '\\', json_encode($allowedChildrenTypes))
                                );
                            }
                            if (!in_array($className, $allowedChildrenTypes) && $className != TTemplateLiteralWhite::class) {
                                throw new TTemplateCompilerException(
                                    $this->templateFileName,
                                    $token->lineNo,
                                    'Control of type `' . $className . '` not allowed as direct child of `' . $parentClassName . '`',
                                    reason: $parentClassName . '::CHILDREN_TYPES_ALLOW = ' .
                                        str_replace('\\\\', '\\', json_encode($allowedChildrenTypes))
                                );
                            }
                        }
                    }

                    $name = '';

                    $shortClassName = (($pos = strrpos($className, '\\')) && $pos !== false ? substr($className, $pos + 1) : $className);
                    $alreadyUsed = array_search($shortClassName, $this->uses);

                    if ($alreadyUsed && $alreadyUsed != $className) {
                        $shortClassName = $shortClassName . count(array_filter($this->uses, fn ($used) => $used == $shortClassName));
                        $this->uses[$className] = [$shortClassName];
                    } else {
                        $this->uses[$className] = $shortClassName;
                    }

                    $controlProps = [];
                    $controlEvents = [];

                    foreach ($token->props as $k => $v) {
                        if (preg_match('{^on[A-Z]}', $k)) {
                            if (!$this->__eventExists($k, $className)) {
                                throw new TTemplateCompilerException(
                                    $this->templateFileName,
                                    $token->lineNo,
                                    "Unsupported event: `$className::$k`",
                                    reason: 'Control does not define such event.'
                                );
                            }
                            $controlEvents[$k] = $v;
                        } else {
                            $controlProps[$k] = $v;
                        }
                    }

                    if (isset($token->props['id'])) {
                        $name = '$this->' . $token->props['id'];

                        if (!preg_match(self::CONTROL_PROP_ID_MATCH, $token->props['id'])) {
                            throw new TTemplateCompilerException(
                                $this->templateFileName,
                                $token->lineNo,
                                'Invalid `id` value: ' . $token->props['id'],
                                reason: 'id string must match `' . self::CONTROL_PROP_ID_MATCH . '`'
                            );
                        }

                        if (isset($named[$token->props['id']])) {
                            throw new TTemplateCompilerException(
                                $this->templateFileName,
                                $token->lineNo,
                                'id redeclared: `' . $token->props['id'] . '`',
                                reason: 'The same id is in use by another control within the template.'
                            );
                        }

                        $named[$token->props['id']] = $shortClassName;
                    } else {
                        $name = (!empty($controlEvents)) ? '$_ctl' . ($ctlNum++) : '';
                    }

                    if ($name && !empty($controlEvents)) {
                        $events[$name] = $controlEvents;
                    }

                    $constructorArguments = [];
                    $compiledProps = $this->__compileProps($controlProps, $token->children, $indent, $token->lineNo, $className);

                    if ($compiledProps) {
                        $constructorArguments[] = "props: [\n" . $compiledProps . "\n$indent]";
                    }

                    $compiledChildren = rtrim(
                        $this->__compileTokens(
                            $token->children,
                            $named,
                            $events,
                            $level + 1,
                            false,
                            $className,
                            $token
                        ),
                        ",\n"
                    );

                    if ($compiledChildren) {
                        $constructorArguments[] =  "children: [\n" . $compiledChildren . "\n$indent]";
                    }

                    $code .= $indent . ($isRoot ? '$this->addControl(' : '') . ($name ? $name . ' = ' : '') .
                        "new $shortClassName(" . implode(', ', $constructorArguments) . ')' . ($isRoot ? ')' : '') .
                        ($isRoot ? ";\n\n" : ",\n");
                    break;
            }
        }

        return $code;
    }

    private static function __quote(string $value): string
    {
        $quote = strpos($value, "\n") === false ? '\'' : '"';
        return $quote . str_replace($quote, '\\' . $quote, str_replace("\n", '\n', $value)) . $quote;
    }

    private function __validatePhp(string $php, int $lineNo): bool
    {
        if (preg_match('{;\s*$}', $php)) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                'Inline PHP code must not end with semicolon'
            );
        }

        try {
            token_get_all('<?php ' . $php . ';', TOKEN_PARSE);
        } catch (CompileError $e) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                'Inline PHP code compilation error',
                reason: $e->getMessage()
            );
        } catch (ParseError $e) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                'Inline PHP code parse error',
                reason: $e->getMessage()
            );
        } catch (Throwable $e) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                'Inline PHP code compilation error',
                reason: $e->getMessage()
            );
        }

        return true;
    }

    private function __propExists(string $name, string $className): bool
    {
        $defined = false;

        try {
            $name = ($pos = strpos($name, '.')) !== false ? substr($name, 0, $pos) : $name;
            $ref = new ReflectionProperty($className, $name);
            $defined = !empty($ref->getAttributes(Prop::class));
        } catch (ReflectionException $e) {
        }

        return $defined;
    }

    private function __eventExists(string $name, string $className): bool
    {
        try {
            new ReflectionMethod($className, $name);
            return true;
        } catch (ReflectionException $e) {
        }

        return false;
    }

    private function __compileProps(array $props, array $children, string $indent, int $lineNo, string $className): string
    {
        $return = [];

        foreach ($children as $token) {
            if ($token->type == Token::TYPE_PROP) {
                if (!$this->__propExists($token->name, $className)) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $token->lineNo,
                        "Undefined prop: `$className::$token->name`",
                        reason: 'Control does not define such prop. A prop must exist and must have a #[Prop] attribute.'
                    );
                }

                if (isset($token->props['value'])) {
                    $props[$token->name] = $token->props['value'];
                } else {
                    $subTemplateName = $this->outputTemplateClassName . '_sub' . ($this->subTemplatesCount++);
                    $props[$token->name] = new PropValuePhp(
                        'new TControlPropTemplate(\'' . $subTemplateName . '\', $this->ownerControl)'
                    );

                    $this->uses[TControlPropTemplate::class] = 'TControlPropTemplate';

                    $named = [];
                    $events = [];

                    $ltrim = isset($token->props['ltrim']) && $token->props['ltrim'];
                    $rtrim = isset($token->props['rtrim']) && $token->props['rtrim'];
                    $trim  = isset($token->props['trim']) && $token->props['trim'];

                    if (($ltrim || $rtrim || $trim) && ($count = count($token->children))) {
                        if (($ltrim || $trim) && $token->children[0]->type == Token::TYPE_LITERAL) {
                            $token->children[0]->element = ltrim($token->children[0]->element);
                        }
                        if (($rtrim || $trim) && $token->children[$count - 1]->type == Token::TYPE_LITERAL) {
                            $token->children[$count - 1]->element = rtrim($token->children[$count - 1]->element);
                        }
                    }

                    $code = $this->__compileTokens($token->children, $named, $events);

                    $this->sub[$subTemplateName] = [$code, $named, $events];
                }
            }
        }

        foreach ($props as $name => $value) {
            if (!$this->__propExists($name, $className)) {
                throw new TTemplateCompilerException(
                    $this->templateFileName,
                    $lineNo,
                    "Undefined prop: `$className::$name`",
                    reason: 'Control does not define such prop. A prop must exist and must have a #[Prop] attribute.'
                );
            }

            if ($value instanceof PropValuePhp) {
                $value = $value->code;
            } else {
                if (is_string($value)) {
                    if (preg_match('/({{{___php:[\d]+}}})/', $value)) {
                        $split = preg_split(
                            '/({{{___php:[\d]+}}})/',
                            $value,
                            -1,
                            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE
                        );
                        $value = [];

                        foreach ($split as [$part, $offset]) {
                            if (preg_match('/^{{{___php:(?P<index>[\d]+)}}}$/', $part, $matches)) {
                                $php = '(' . $this->phps[$matches['index']] . ')';

                                if ($this->__validatePhp($php, $lineNo)) {
                                    $value[] = $php;
                                }
                            } else {
                                $value[] = $this->__quote($part);
                            }
                        }
                        $value = implode(' . ', $value);
                    } else {
                        $value = $this->__quote($value);
                    }
                } else if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else if (is_null($value)) {
                    $value = 'null';
                }
            }

            $return[] = "'$name' => $value";
        }

        return !empty($return) ? $indent . '   ' . implode(",\n$indent   ", $return) : '';
    }

    private function __tokenize()
    {
        $split = preg_split(
            self::TOKENIZER_SPLIT,
            $this->templateSourceCode,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE
        );
        $root = $currentToken = new Token;
        $root->type = Token::TYPE_ROOT;

        $stack = [$root];

        $token = null;
        $inComment = false;

        foreach ($split as [$element, $offset]) {
            if ($element == '') continue;

            $lineNo = $this->__getLine($offset);

            if (preg_match(self::COMMENT_BEGIN_MATCH, $element)) {
                $inComment = true;
                continue;
            }

            if (preg_match(self::COMMENT_END_MATCH, $element)) {
                if (!$inComment) {
                    throw new TTemplateCompilerException($this->templateFileName, $lineNo, 'Unexpected end of comment');
                }
                $inComment = false;
                continue;
            }

            if ($inComment) {
                continue;
            }

            if (preg_match(self::CONTROL_TAG_MATCH, $element, $matches)) {
                $isClosing       = !!$matches['isClosing'];
                $isSelfClosing   = !!$matches['isSelfClosing'];
                $comName         = $matches['comName'];
                $refName         = $matches['refName'];

                $props = [];

                if ($matches['htmlName']) {
                    $comName = 'THtmlElement';
                    $props['name'] = $matches['htmlName'];
                    $props['hasEndTag'] = !$isSelfClosing;
                }

                $tokenName = $comName ?: $refName;

                if ($isClosing && $isSelfClosing) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Closing tag cannot be simultaneously self-closing.'
                    );
                }

                $props = array_merge($props, $this->__parseAttributes($element, $isClosing, $lineNo));

                $token = new Token;
                $token->type = Token::TYPE_CONTROL;
                $token->element = $element;
                $token->tagName = $matches['tagName'];
                $token->name = $tokenName;
                $token->isClosing = $isClosing;
                $token->isSelfClosing = $isSelfClosing;
                $token->comName = $comName;
                $token->refName = $refName;
                $token->props = $props;
                $token->lineNo = $lineNo;

                if ($refName && $currentToken->type == Token::TYPE_PROP) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Referenced controls can not be declared in prop context.',
                        reason: 'Controls declared within props belong to the same control as the props. ' .
                            'Cross-context references is not possible.'
                    );
                }
            } else if (preg_match(self::PROPERTY_TAG_MATCH, $element, $matches)) {
                $isClosing       = !!$matches['isClosing'];
                $isSelfClosing   = !!$matches['isSelfClosing'];

                if ($currentToken->type != Token::TYPE_CONTROL && !($currentToken->type == Token::TYPE_PROP && $isClosing)) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Prop `' . $element . '` declared outside of control context.',
                        reason: 'The <prop:*> tags are bound to the control they are declared within ' .
                            'so no-context declaration is wrong.'
                    );
                }

                $props = $this->__parseAttributes($element, $isClosing, $lineNo);

                if ($isSelfClosing && !isset($props['value'])) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Self-closing prop tag `' . $element . '` requires `value` attribute.',
                        reason: 'A prop must have a value. Declaring empty prop is redundant.'
                    );
                }

                if ($isSelfClosing && count($props) > 1) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Self-closing prop tag `' . $element . '` defines unsupported attributes.',
                        reason: 'A self-closing prop tag must have only one attribute: `value`.'
                    );
                }

                if (!$isSelfClosing && isset($props['value'])) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Opening prop tag `' . $element . '` can not have `value` attribute.',
                        reason: 'If a prop tag is not self-closing, it is assumed it contains a ' .
                            'sub-template definition as a value.'
                    );
                }

                $diff = array_diff(array_keys($props), self::SUPPORTED_PROP_ATTRIBUTES);

                if (!empty($diff)) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Unknown attributes in prop tag `' . $element . '`',
                        reason: 'Prop tag accepts `' . implode('`, `', self::SUPPORTED_PROP_ATTRIBUTES) . '` attributes only, but unknown attributes found: `' . implode('`, `', $diff) . '`'
                    );
                }

                $token = new Token;
                $token->type = Token::TYPE_PROP;
                $token->element = $element;
                $token->tagName = $matches['tagName'];
                $token->name = $matches['name'];
                $token->isClosing = $isClosing;
                $token->isSelfClosing = $isSelfClosing;
                $token->props = $props;
                $token->lineNo = $lineNo;
            } else {
                if ($token && $token->type == Token::TYPE_LITERAL) {
                    $token->element .= $element;
                    continue;
                }

                $token = new Token;
                $token->type = Token::TYPE_LITERAL;
                $token->element = $element;
                $token->isSelfClosing = true;
                $token->lineNo = $lineNo;
            }

            if (!$token) {
                continue;
            }

            if ($token->isClosing) {
                if (empty($stack)) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Unexpected closing tag found: `</' . $token->tagName . '`>'
                    );
                }

                if ($token->tagName !== $stack[count($stack) - 1]->tagName) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Unexpected closing tag found: `</' . $token->tagName . '>`, ' .
                            'expecting `</' . $stack[count($stack) - 1]->tagName . '>`'
                    );
                }

                array_pop($stack);
                $currentToken = end($stack);
            } else {
                $currentToken->children[] = $token;

                if (!$token->isSelfClosing) {
                    $stack[] = $token;
                    $currentToken = $token;
                }
            }
        }

        if (count($stack) > 1) {
            $last = end($stack);
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                'No closing tag for `' . $last->element . '` found'
            );
        }

        return $root->children;
    }

    private function __getLine($offset): int
    {
        return substr_count($this->templateSourceCode, "\n", 0, $offset) + 1;
    }

    private function __parseAttributes(string $element, bool $isClosing, int $lineNo): array
    {
        $props = [];

        if (preg_match_all(self::ATTRIBUTES_MATCH, $element, $propMatches)) {
            if ($isClosing) {
                throw new TTemplateCompilerException(
                    $this->templateFileName,
                    $lineNo,
                    'Attributes not allowed in closing tag'
                );
            }

            foreach ($propMatches['name'] as $k => $v) {
                if (!preg_match(self::CONTROL_PROP_NAME_MATCH, $v)) {
                    throw new TTemplateCompilerException(
                        $this->templateFileName,
                        $lineNo,
                        'Invalid prop name: `' . $v . '`',
                        reason: 'Prop name must match `' . self::CONTROL_PROP_NAME_MATCH . '`'
                    );
                }

                [$name, $value] = $this->__parseProp($v, $propMatches['value'][$k], $lineNo);

                $props[$name] = $value;
            }
        }

        return $props;
    }

    private function __parseProp(string $name, string $value, int $lineNo): array
    {
        $deprecated = [
            'cssClass' => 'html.class',
            'class' => 'html.class',
            'style' => 'html.style'
        ];

        if (isset($deprecated[$name])) {
            throw new TTemplateCompilerException(
                $this->templateFileName,
                $lineNo,
                "Prop name `$name` is deprecated. Please use `{$deprecated[$name]}` instead"
            );
        }

        $_value = strtolower($value);

        if ($_value == 'true' || $_value == 'yes') {
            $value = true;
        } else if ($_value == 'false' || $_value == 'no') {
            $value = false; 
        } else if ($_value == 'null') {
            $value = null;
        } else if (is_numeric($_value)) {
            $_int = intval($_value);
            $_float = floatval($_value);

            $value = $_int == $_float ? $_int : $_float;
        }

        return [$name, $value];
    }
}
