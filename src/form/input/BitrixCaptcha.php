<?php
namespace beatrix\form\input;

use beatrix\form\validation\BitrixCaptcha as BitrixCaptchaRule;

class BitrixCaptcha extends BaseInputPlugin
{

    /**
     * {@inheritdoc}
     */
    public function renderInput($name, $attributes)
    {
        @require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/captcha.php");
        $captcha = new \CCaptcha();
        $captcha->SetCode();
        $sid = $captcha->GetSID();
        $hidden = $this->formRenderer->input(
            'hidden',
            BitrixCaptchaRule::HIDDEN_INPUT_SID_NAME,
            array('value' => $sid)
        );

        $imgAttributes = array_merge(array(
            'src' => '/bitrix/tools/captcha.php?captcha_code=' . $sid,
        ), array_get($attributes, 'imgAttributes', []));

        $challengeAttributes = array_merge(
            array_get($attributes, 'challengeAttributes', array()),
            array('autocomplete' => 'off', 'required' => 'required',
                'value' => '') //todo autodetect minlength etc from Bitrix setup
        );

        $template = array_get($attributes, 'template', '{img} {input}');

        $output = strtr($template, [
            '{img}' => '<img ' . $this->formRenderer->renderAttributes($imgAttributes) . '/>',
            '{input}' => $this->formRenderer->input(
                'text',
                BitrixCaptchaRule::CHALLENGE_TEXT_INPUT_NAME,
                $challengeAttributes
            ),
        ]);
        return $output . "\r\n" . $hidden;
    }
}
