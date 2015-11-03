<?php
namespace beatrix\form;

use Sirius\Validation\Validator;
use Sirius\Validation\ValueValidator;

/**
 * Validates, sanitizes and keeps input values of form.
 */
class Form implements \ArrayAccess
{
    /**
     * @var Validator
     */
    protected $validator;
    /**
     * @var array
     */
    protected $fieldsData = array();
    /**
     * @var array
     */
    protected $sanitizeRules;
    /**
     * @var bool
     */
    private $isValid = true;
    /**
     * @var array
     */
    private $defaultValues;
    /**
     * @var string
     */
    private $namespace;

    /**
     * @param array $options Following options:
     * validationRules (see Sirius doc)
     *
     * sanitizeRules sanitize callbacks supported by PHP like
     * [trim, strtolower],
     * [trim => 'field_a, field_b']
     * or [field_a=>'trim, strtolower', ...]
     *
     * defaultValues
     * namespace Which name to use for fields grouping for further POST[namespace] fetching. By default is empty.
     */
    function __construct(array $options = array())
    {
        $this->validator = new Validator();

        $rules = array_merge(
            $this->rulesDefault(),
            array_get($options, 'validationRules', array())
        );
        foreach ($rules as $sel => $rule) {
            $this->validator->add($sel, $rule);
        }

        $this->sanitizeRules = array_merge(
            $this->sanitizeRulesDefault(),
            array_get($options, 'sanitizeRules', array())
        );
        $this->defaultValues = array_get($options, 'defaultValues', array());
        $this->namespace = array_get($options, 'namespace', '');
    }

    /**
     * To be overriden in descendant form class
     * @return array
     */
    protected function rulesDefault()
    {
        return array();
    }

    /**
     * To be overriden in descendant form class
     * @return array
     */
    protected function sanitizeRulesDefault()
    {
        return array();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function validate(array $data)
    {
        $this->populate($data);
        return $this->isValid = $this->validator->validate($this->fieldsData);
    }

    /**
     * @param string $messageTemplate
     * @return array List of error message groups, mapped like [fieldA =>[message1, message2], ...]
     */
    public function getErrorMessages($messageTemplate = '{message}')
    {
        $result = [];
        $messages = $this->validator->getMessages();
        foreach ($messages as $fieldName => $msgList) {
            $result[$fieldName] = [];
            foreach ($msgList as $msg) {
                $result[$fieldName][] = str_replace('{message}', (string)$msg, $messageTemplate);
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitize(array $data)
    {
        $allFields = array_keys($this->validator->getRules());
        foreach ($this->sanitizeRules as $rule => $fields) {
            if (is_int($rule)) {
                $rule = $fields;
                $fields = $allFields ? $allFields : array_keys($data);
            } else {
                $fields = is_string($fields) ? preg_split('/\s+|,/', $fields) : (array)$fields;
            }
            foreach ($data as $name => &$value) {
                if (in_array($name, $fields)) {
                    if (is_array($value)) {
                        foreach ($value as &$valItem) {
                            $valItem = call_user_func_array($rule, array($valItem));
                        }
                    } else {
                        $value = call_user_func_array($rule, array($value));
                    }
                }
            }

        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->fieldsData[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->fieldsData[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Form data can be populated or changed only during validation");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException("Form data can be populated or changed only during validation");
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->isValid;
    }

    public function getValue($name)
    {
        //todo exception on null?
        return array_get($this->fieldsData, $name, array_get($this->defaultValues, $name));
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @see \Sirius\Validation\Validator::add()
     */
    public function addValidationRule($selector, $name = null, $options = null, $messageTemplate = null, $label = null)
    {
        $this->validator->add($selector, $name, $options, $messageTemplate, $label);
    }

    /**
     * @param $name
     * @return \Sirius\Validation\RuleCollection|null
     */
    public function getRulesCollection($name)
    {
        $validator = array_get($this->validator->getRules(), $name, null);
        if ($validator instanceof ValueValidator) {
            return $validator->getRules();
        }
    }

    /**
     * Fill with data, respecting defaults
     * @param array $data
     */
    public function populate(array $data)
    {
        $this->fieldsData = $this->sanitize(array_merge($this->defaultValues, $data));
    }

}
