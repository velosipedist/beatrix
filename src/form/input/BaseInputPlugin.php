<?php
namespace beatrix\form\input;

use beatrix\form\FormInputInterface;
use beatrix\form\FormRenderer;

/**
 * Base input class that auto-injects form renderer on creation
 */
abstract class BaseInputPlugin implements FormInputInterface
{
    /**
     * @var FormRenderer
     */
    protected $formRenderer;

    /**
     * BaseInputPlugin constructor.
     * @param FormRenderer $formRenderer
     */
    public function __construct(FormRenderer $formRenderer)
    {
        $this->formRenderer = $formRenderer;
    }
}
