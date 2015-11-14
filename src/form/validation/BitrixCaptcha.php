<?php
namespace beatrix\form\validation;

use Sirius\Validation\Rule\AbstractStringRule;

class BitrixCaptcha extends AbstractStringRule
{
    const MESSAGE = 'Enter correct symbols from image';
    const LABELED_MESSAGE = '{label} must contain correct symbols from image';
    const HIDDEN_INPUT_SID_NAME = 'beatrix_captcha_sid';
    const CHALLENGE_TEXT_INPUT_NAME = 'beatrix_captcha_challenge';

    /**
     * {@inheritdoc}
     */
    function validate($value, $valueIdentifier = null)
    {
        @require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/captcha.php");
        $captcha = new \CCaptcha();
        return $captcha->CheckCode($value, $this->context->getItemValue(static::HIDDEN_INPUT_SID_NAME));
    }
}
