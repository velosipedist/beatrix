<?php
namespace beatrix\form;
interface FormInputInterface
{
    /**
     * @param string $name
     * @param array $attributes
     * @return string
     */
    public function renderInput($name, $attributes);
}
