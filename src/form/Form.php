<?php
namespace beatrix\form;

use Sirius\Validation\Validator;

class Form implements \ArrayAccess{
    /**
     * @var Validator
     */
    protected $validator;
    /**
     * @var
     */
    protected $fieldsData;
    /**
     * @var array
     */
    protected $sanitizeRules;
    private $isValid = true;
    private $isPopulated = false;

    /**
     * @param array $validationRules
     * @param array $sanitizeRules
     */
    function __construct(array $validationRules, array $sanitizeRules = array())
    {
        $this->validator = new Validator();
        $this->validator->add(array_merge($this->rulesDefault(), $validationRules));
        $this->sanitizeRules = array_merge($this->sanitizeRulesDefault(), $sanitizeRules);
    }

    protected function rulesDefault()
    {
        return array();
    }

    protected function sanitizeRulesDefault()
    {
        return array();
    }

    /**
     * @param array $data
     */
    public function validate(array $data)
    {
        $this->isPopulated = true;
        $this->fieldsData = $this->sanitize($data);
        return $this->isValid = $this->validator->validate($data);
    }

    /**
     * @param array $data
     */
    protected function sanitize(array $data)
    {
        //todo configurable sanitizing
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
     * @return mixed
     */
    public function isValid()
    {
        return $this->isValid;
    }

}
