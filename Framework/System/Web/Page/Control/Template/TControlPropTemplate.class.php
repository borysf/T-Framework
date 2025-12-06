<?php
namespace System\Web\Page\Control\Template;

use System\Exception\TInvalidValueException;
use System\TApplication;
use System\Web\Page\Control\TControl;

/**
 * Class representing a control prop containing a compiled template.
 * In practical matter you won't need to use this class unless you 
 * create a custom control using templates assigned via props
 * (just like `TRepeater` does).
 */
class TControlPropTemplate {
    private string $templateClassName;
    private TControl $ownerControl;

    public function __construct(string $templateClassName, TControl $ownerControl) {
        $this->templateClassName = $templateClassName;
        $this->ownerControl = $ownerControl;
    }

    /** 
     * Creates and returns a new `TTemplate` instance assigned to this prop. 
     * Accepts association array which is then expanded into variables in
     * the instantiated template. It makes it possible to use inline PHP code
     * using expanded variables in the template.
     */
    public function instance(array $data = []) {
        if (TApplication::isDevelopment()) {
            foreach ($data as $k => $v) {
                if (!preg_match('{^[a-zA-Z_][a-zA-Z0-9_]+$}', $k)) {
                    throw new TInvalidValueException($k, '`^[a-zA-Z_][a-zA-Z0-9_]+$`', 
                        reason: 'Keys are turned into variable names. Provided value is not a valid variable name.'
                    );
                }
                
                if ($k == 'this') {
                    throw new TInvalidValueException($k, null, 
                        reason: 'Keys are turned into variable names. Provided value `this` is a reserved variable name.'
                    );
                }
            }
        }
        $className = $this->templateClassName;

        return new $className($this->ownerControl, $data);
    }
}