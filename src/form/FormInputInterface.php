<?php
namespace beatrix\form;
interface FormInputInterface
{
    /**
     * @param string $name
     * @param $value
     * @param array $attributes
     * @return string
     */
    public function renderInput($name, $value, $attributes);
}
