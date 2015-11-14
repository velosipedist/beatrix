<?php
namespace unit;

use beatrix\form\Form;
use beatrix\form\FormRenderer;
use Symfony\Component\DomCrawler\Crawler;

class FormRenderTest extends \PHPUnit_Framework_TestCase
{
    protected function renderer($options = array())
    {
        return new FormRenderer(new Form($options));
    }

    protected function crawl($html)
    {
        return new Crawler('<html>' . $html . '</html>');
    }

    protected function assertHtmlMatches($html, $selector, $countExpected = 1, $message = null)
    {
        $crawler = $this->crawl($html);
        $message = $message ?: 'Expected ' . $countExpected . ' matches for ' . $selector . "\r\nHTML: " . $html;
        $this->assertCount($countExpected, $crawler->filter($selector), $message);
    }

    public function testBasicInputs()
    {
        $form = $this->renderer();
        $output = $form->input('text', 'foo');
        $this->assertHtmlMatches($output, 'input[type="text"]');
        $this->assertHtmlMatches($output, 'input[name="foo"]');
        $this->assertHtmlMatches($output, 'input[value]', 0);

        $output = $form->input('text', 'foo', array('value' => 123));
        $this->assertHtmlMatches($output, 'input[value="123"]');
    }

    public function testNamespaces()
    {
        $form = $this->renderer(array('namespace' => 'Contact'));
        // simple
        $output = $form->input('text', 'foo');
        $this->assertHtmlMatches($output, 'input[type="text"]');
        $this->assertHtmlMatches($output, 'input[name="Contact[foo]"]');
        // checkboxes have [] at the end
        $output = $form->checkable('checkbox', 'foo', 1);
        $this->assertHtmlMatches($output, 'input[type="checkbox"][value="1"][name="Contact[foo]"]');

        $output = $form->checkable('checkbox', 'foo', 1, ['multiple' => 1]);
        $this->assertHtmlMatches($output, 'input[type="checkbox"][value="1"][name="Contact[foo][]"]');
        // radios just named
        $output = $form->checkable('radio', 'foo', 1);
        $this->assertHtmlMatches($output, 'input[type="radio"]');
        $this->assertHtmlMatches($output, 'input[name="Contact[foo]"]');
        $this->assertHtmlMatches($output, 'input[value="1"]');
    }

    public function testCheckableInputs()
    {
        $form = $this->renderer(array('defaultValues' => array('foo' => 'bar')));
        $output = $form->checkable('radio', 'foo', 'bar', array('class' => 'foo-bar'));
        $this->assertHtmlMatches(
            $output,
            'input[type="radio"][name="foo"][value="bar"][class="foo-bar"][checked="checked"]'
        );
        $output = $form->checkable('radio', 'not_foo', 'bar');
        $this->assertHtmlMatches($output, 'input[type="radio"][checked]', 0);

        $form = $this->renderer(array('defaultValues' => array('foo' => 'g')));
        $choices = array('a' => 'Alpha', 'b' => 'Beta', 'g' => 'Gamma');
        $output = $form->checkableList('radio', 'foo', $choices, array('class' => 'foo-bar'));
        $this->assertHtmlMatches($output, 'label > input[type="radio"][name="foo"][class="foo-bar"]', 3);
        $this->assertHtmlMatches($output, 'label > input[checked="checked"]');
        $this->assertHtmlMatches($output, 'label > input[type="radio"][name="foo"][value="g"][checked="checked"]');

        $output = $form->checkableList('radio', 'foo', $choices, array(
            'class' => 'foo-bar',
            'template' => '{labelOpen}{labelText}{labelClose}{input}'
        ));
        $outputAlt = $form->checkableList('radio', 'foo', $choices, array(
            'class' => 'foo-bar',
            'template' => '{label}{input}'
        ));
        $this->assertEquals($output, $outputAlt);

        $this->assertHtmlMatches($output, 'label + input[type="radio"][name="foo"][class="foo-bar"]', 3);
        $this->assertHtmlMatches($output, 'label + input[checked="checked"]');
        $this->assertHtmlMatches($output, 'label + input[type="radio"][name="foo"][value="g"]');
        $this->assertHtmlMatches($output, 'label + input[type="radio"][name="foo"][value="g"][checked="checked"]');


        $form = $this->renderer(array('defaultValues' => array('foo' => array('a', 'g'))));
        $output = $form->checkableList('checkbox', 'foo', $choices, array('class' => 'foo-bar'));
        $this->assertHtmlMatches($output, 'label > input[type="checkbox"][name="foo[]"][class="foo-bar"]', 3);
        $this->assertHtmlMatches($output, 'label > input[type="checkbox"][name="foo[]"][checked="checked"]', 2);
        $this->assertHtmlMatches($output, 'label > input[type="checkbox"][name="foo[]"][value="g"]');
        $this->assertHtmlMatches($output, 'label > input[type="checkbox"][name="foo[]"][value="a"][checked="checked"]');
        $this->assertHtmlMatches($output, 'label > input[type="checkbox"][name="foo[]"][value="g"][checked="checked"]');
    }

    public function testSelectInputs()
    {
        $form = $this->renderer();
        $choices = array('a' => 'Alpha', 'b' => 'Beta', 'g' => 'Gamma');
        $output = $form->select('foo', $choices);
        $this->assertHtmlMatches($output, 'select[name="foo"] > option', 3);
        $this->assertHtmlMatches($output, 'select[name="foo"] > option[value="a"]');
        $this->assertHtmlMatches($output, 'select[name="foo"] > option[selected]', 0);

        // select the option
        $form->getForm()->populate(array('foo' => 'b'));
        $output = $form->select('foo', $choices);
        $this->assertHtmlMatches($output, 'select[name="foo"] > option[value="b"][selected]');

        // multiple
        $form->getForm()->populate(array('foo' => array('b', 'a')));
        $output = $form->select('foo', $choices, array('multiple' => 1));
        $this->assertHtmlMatches($output, 'select[multiple="multiple"][name="foo[]"] > option[selected]', 2);

        // non-indexed choices
        $output = $form->select('foo', array('Alpha', 'Beta'));
        $this->assertHtmlMatches($output, 'select[name="foo"] > option[value="0"]');

        $output = $form->select(
            'foo',
            array('a' => 'Alpha', 'b' => 'Beta', array('label' => 'Gamma', 'value' => 'gamma-value'))
        );
        $this->assertHtmlMatches($output, 'select[name="foo"] > option[value="a"]');
        $this->assertHtmlMatches($output, 'option[value="a"] + option[value="b"] + option[value="gamma-value"]');

        $output = $form->select('foo', array(
            array('label' => 'Alpha', 'value' => 'a'),
            'b' => array('label' => 'Beta'), // if arry config passed, array index will NOT be used as value
            array('label' => 'Gamma', 'value' => 'gamma-value', 'data-foo' => 'bar')
        ));
        $this->assertContains('Alpha</option>', $output);
        $this->assertContains('Beta</option>', $output);
        $this->assertContains('Gamma</option>', $output);
        $this->assertHtmlMatches(
            $output,
            'option[value="a"] + option[value="Beta"] + option[value="gamma-value"][data-foo="bar"]'
        );


        $output = $form->select('foo', array(
            array('label' => 'Alpha', 'value' => 'a'),
            'Group' => array(
                'A1', // Group item may be just string (as value and label)
                array('label' => 'The A2', 'value' => 'a2_value') // or option config like plain option
            ),
            'beta' => array('label' => 'Beta'),
            array('label' => 'Gamma', 'value' => 'gamma-value', 'data-foo' => 'bar')
        ));
        $this->assertHtmlMatches($output, 'select > option[value="a"][selected] + optgroup');
        $this->assertHtmlMatches($output, 'select > optgroup > option[value="A1"]');
        $this->assertHtmlMatches($output, 'select > optgroup > option[value="a2_value"]');
        $output = $form->select('foo', array(
            array(
                'a',
                array('label' => 'The A2')
            ),
        ));
        $this->assertHtmlMatches($output, 'select > optgroup > option[value="a"][selected]');
        $this->assertHtmlMatches($output, 'select > optgroup > option[value="The A2"]');

    }

    public function testValidators()
    {
        $pattern = '/(\+7))?\d\d\d-\d\d\d\d\d\d\ds/';
        $form = $this->renderer(array(
            'validationRules' => array(
                'foo' => 'required | minlength(10) | maxlength(100)',
                'between' => 'integer | between(-3,3)', // NO SPACE after commas !
                'min' => 'integer | greaterthan({"max":4})',
                'max' => 'integer | lessthan(5)',
                'email' => 'required | email',
                'phone' => array(
                    array('regex', array($pattern)) // !!! the only way to add regex with braces now
                )
            )
        ));

        $foo = $form->input('text', 'foo');
        $this->assertHtmlMatches($foo, 'input[type="text"][name="foo"][required][minlength="10"][maxlength="100"]');

        $between = $form->input('text', 'between');
        $this->assertHtmlMatches(
            $between,
            'input[type="text"][data-parsley-type="number"][name="between"][min="-3"][max="3"]'
        );
        $between = $form->input('number', 'between');
        $this->assertHtmlMatches($between,
            'input[type="number"][data-parsley-type="number"][name="between"][min="-3"][max="3"]'
        );

        $min = $form->input('number', 'min');
        $this->assertHtmlMatches($min, 'input[type="number"][data-parsley-type="number"][name="min"][min="4"]');
        $max = $form->input('number', 'max');
        $this->assertHtmlMatches($max, 'input[type="number"][data-parsley-type="number"][name="max"][max="5"]');

        $email = $form->input('text', 'email');
        $this->assertHtmlMatches($email, 'input[type="email"][name="email"][required]');

        $phone = $form->input('text', 'phone');
        $this->assertHtmlMatches($phone, 'input[name="phone"][type="text"][pattern="' . $pattern . '"]');
    }

}
