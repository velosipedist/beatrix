<?php
namespace beatrix\form;

use Sirius\Validation\Rule\AbstractRule;

/**
 * Digs injected validator instance to detect HTML5-compatible attributes
 */
class ValidatorRenderer
{
    /**
     * @var AbstractRule|Callback
     */
    private $decoratedValidator;

    public function __construct(AbstractRule $validator)
    {
        $this->decoratedValidator = $validator;
    }

    public function getOptions()
    {
        $refl = new \ReflectionObject($this->decoratedValidator);
        $optProperty = $refl->getProperty('options');
        $optProperty->setAccessible(true);
        return $optProperty->getValue($this->decoratedValidator);
    }

    public function getHtmlAttributes($inputType)
    {
        $attributes = array();
        $classParts = array_filter(explode('\\', get_class($this->decoratedValidator)));
        $isNumber = array_search('Number', $classParts) !== false;
        $isInt = array_search('Integer', $classParts) !== false;
        $class = array_pop($classParts);
        $options = $this->getOptions();
        if ($class == 'Callback') {
            $map = [
                'greaterthan' => 'GreaterThan',
                'lessthan' => 'LessThan',
            ];
            // guessing Sirius validator
            if (is_string($options['callback']) && isset($map[$options['callback']])) {
                $class = $map[$options['callback']];
                $options['arguments'] = $this->decodeCallbackArguments($options['arguments']);
            }
        }
        switch ($class) {
            case 'Required':
                $attributes['required'] = 1;
                break;
            case 'Integer':
                $attributes['data-parsley-type'] = 'number';
                break;
            case 'Number':
                $attributes['data-parsley-type'] = 'number';
                break;
            case 'LessThan':
                if (isset($options['arguments']['inclusive'])) {
                    unset($options['arguments']['inclusive']);
                }
                $attributes['max'] = array_shift($options['arguments']);
                break;
            case 'GreaterThan':
                if (isset($options['arguments']['inclusive'])) {
                    unset($options['arguments']['inclusive']);
                }
                $attributes['min'] = array_shift($options['arguments']);
                break;
            case 'Between':
                $attributes['min'] = $options['min'];
                $attributes['max'] = $options['max'];
                break;
            case 'AlphaNumeric':
                $attributes['data-parsley-type'] = 'alphanum';
                break;
            case 'MaxLength':
                $attributes['maxlength'] = $options['max'];
                $attributes['data-parsley-maxlength'] = $options['max'];
                break;
            case 'MinLength':
                $attributes['minlength'] = $options['min'];
                $attributes['data-parsley-minlength'] = $options['min'];
                break;
            case 'Length':
                if (isset($options['min'])) {
                    $attributes['minlength'] = $options['min'];
                    $attributes['data-parsley-minlength'] = $options['min'];
                }
                if (isset($options['max'])) {
                    $attributes['maxlength'] = $options['max'];
                    $attributes['data-parsley-maxlength'] = $options['max'];
                }
                break;
            case 'Regex':
                $attributes['pattern'] = array_get($options, 'pattern', $options[0]); //should be fixed
                break;
            case 'Email':
                $attributes['type'] = 'email';
//?             $attributes['pattern'] = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)'
//                  .'*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
                break;
        }
        return $attributes;
    }

    private function decodeCallbackArguments($arguments)
    {
        if (!is_array($arguments)) {
            $json = json_decode($arguments, true);
            if (is_array($json)) {
                $arguments = $json;
            } else {
                $arguments = (array)$arguments;
            }
        }
        return $arguments;

    }
}
