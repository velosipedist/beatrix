<?php
namespace unit;

use beatrix\form\Form;

class FormTest extends \PHPUnit_Framework_TestCase
{
    public function testPopulate()
    {
        $f = new Form();
        $f->populate(array('a' => 'foo', 'b' => 'bar'));
        $this->assertEquals('foo', $f->getValue('a'));
        $this->assertEquals('bar', $f->getValue('b'));
        $this->assertNull($f->getValue('undefined'));

        $fDef = new Form(array(
            'defaultValues' => array('a' => 'AAA')
        ));
        $this->assertEquals('AAA', $fDef->getValue('a'));
        $this->assertEquals(null, $fDef->getValue('b'));
        $fDef->populate(array('b' => 'bar'));
        $this->assertEquals('AAA', $fDef->getValue('a'));
        $this->assertEquals('bar', $fDef->getValue('b'));

        $fDef->populate(array('a' => 'foo'));
        $this->assertEquals('foo', $fDef->getValue('a'));
        $this->assertEquals(null, $fDef->getValue('b'), 'repopulate should reset form contents');
    }

    public function testValidation()
    {
        $f = new Form(array(
            'validationRules' => array(
                'a' => 'required',
                'b' => 'required'
            )
        ));
        $this->assertTrue($f->validate(array(
            'a' => 'foo',
            'b' => 'bar',
        )));
        $this->assertTrue($f->validate(array(
            'a' => [0, 0, 0],
            'b' => 'bar',
        )));
        $this->assertFalse($f->validate(array(
            'a' => 'foo',
        )));
        $this->assertFalse($f->validate(array(
            'a' => 'foo',
            'b' => '',
        )));
        $emptyForm = new Form(array());
        $this->assertTrue($emptyForm->validate(array()));
    }

    public function testGetErrorMessages()
    {
        $f = new Form(array(
            'validationRules' => array(
                'a' => 'required()(a is required)',
            )
        ));
        $f->validate([]);
        $messages = $f->getErrorMessages();
        $this->assertEquals('a is required', $messages['a'][0]);
        $messages = $f->getErrorMessages('[error]{message}[/error]');
        $this->assertEquals('[error]a is required[/error]', $messages['a'][0]);
    }

    public function testSanitize()
    {
        $commonValidatorRules = array('a' => 'required | integer', 'b' => 'required | integer');
        $datasetsGood = array(
            'non_casted' => array('a' => '1', 'b' => '2'),
            'spaced1' => array('a' => '   1   ', 'b' => '   2   '),
        );
        $datasetsBad = array(
            'emptySpaced' => array('a' => '   ', 'b' => ''),
            'mistyped' => array('a' => '  x ', 'b' => 'y'),
        );
        /** @var Form[] $failForms */
        $failForms = array(
            'assoc1' => new Form(array(
                'validationRules' => $commonValidatorRules,
                'sanitizeRules' => array('trim' => 'a, b')
            )),
            'assoc2' => new Form(array(
                'validationRules' => $commonValidatorRules,
                'sanitizeRules' => array('trim' => 'a b')
            )),
            'assoc3' => new Form(array(
                'validationRules' => $commonValidatorRules,
                'sanitizeRules' => array('a' => 'trim', 'b' => 'trim')
            )),
            'list' => new Form(array(
                'validationRules' => $commonValidatorRules,
                'sanitizeRules' => array('trim')
            )),
        );
        foreach ($failForms as $f => $form) {
            foreach ($datasetsBad as $d => $set) {
                $this->assertFalse($form->validate($set), "Fail $f [ $d ]");
            }
            $this->assertCount(2, $form->getErrorMessages(), "Fail $f");
            $this->assertFalse($form->validate(array('a' => '  1 ', 'b' => 'y')), "Fail mixed data");
            $this->assertCount(1, $form->getErrorMessages(), "Fail mixed data errors count");
            foreach ($datasetsGood as $d => $set) {
                $this->assertTrue($form->validate($set), "Fail $f [ $d ]");
            }
        }
        $datasetsGood = array(
            'correct' => array('a' => 3, 'b' => 4),
            'non-casted' => array('a' => '3', 'b' => '4'),
            'spaced1' => array('a' => '   1   ', 'b' => '   2   '),
        );
        $successForms = array(
            'mixed' => new Form(array(
                'validationRules' => $commonValidatorRules,
                'sanitizeRules' => array('trim', 'intval' => 'a b')
            ))
        );
        foreach ($successForms as $f => $form) {
            foreach ($datasetsGood as $d => $set) {
                $this->assertTrue($form->validate($set), "Fail $f [ $d ]");
            }
            // intval forces integers
            foreach ($datasetsBad as $d => $set) {
                $this->assertTrue($form->validate($set), "Fail $f [ $d ]");
            }
        }
    }

    public function testSanitizeArrays()
    {
        $f = new Form(array(
            'sanitizeRules' => array('trim', 'intval')
        ));
        $f->populate(array('a' => array('1', 2, ' 3 ', ' x ', '')));
        $this->assertEquals(array(1, 2, 3, 0, 0), $f->getValue('a'));

        $f = new Form(array(
            'sanitizeRules' => array('trim' => array('a'), 'intval')
        ));
        $f->populate(array('a' => array('1', 2, ' 3 ', ' x ', '')));
        $this->assertEquals(array(1, 2, 3, 0, 0), $f->getValue('a'));
    }

    public function testDefaultValues()
    {
        $f = new Form(array(
            'validationRules' => array('a' => 'required', 'b' => 'required'),
            'defaultValues' => array('b' => 'foo')
        ));
        $this->assertTrue($f->validate(array('a' => 'bar')));
    }


}
