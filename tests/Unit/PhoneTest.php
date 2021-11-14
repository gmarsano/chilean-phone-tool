<?php

namespace Tests\Unit;

use Gmarsano\ChileanPhoneTool\Factory;
use PHPUnit\Framework\TestCase;
use Gmarsano\ChileanPhoneTool\Phone;

class PhoneTest extends TestCase
{
    public function testSetPhoneValue()
    {
        $values = [
            987654321,
            "+56-9-87-654-321"
        ];
        foreach ($values as $value) {
            $found = Phone::setPhone($value)->getOld();
            $this->assertTrue(
                (string)$value === $found,
                "Expected \"$value\" found \"$found\"."
            );
        }
    }

    public function testParseValue()
    {
        $expected = [
            ["987654321", 987654321],
            ["987654321", "(9) 87-654-321"],
            ["327654321", "+(32) 7-654-321"],
            ["56987654321", "+56 (9) 87-654-321"],
            ["327654321", "prefix: (0032) number: 7-654-321"],
            ["56327654321", "+0056 prefix: (0032) number: 7-654-321"],
            ["123", "invalid: 1.2-3"],
        ];
        foreach ($expected as $value) {
            $found = Phone::parse($value[1])->getOld();
            $this->assertTrue(
                $value[0] === $found,
                "Expected \"{$value[0]}\" found \"$found\"."
            );
        }
    }

    public function testValidationSuccess()
    {
        foreach (Phone::AREA_CODES as $code) {
            $this->assertTrue(Phone::setPhone("{$code}7654321")->isValid());
        }
        foreach (Phone::SINGLE_DIGITS_CODES as $code) {
            $this->assertTrue(Phone::setPhone("{$code}87654321")->validate());
        }
        $this->assertTrue(Phone::setPhone(987654321)->isValid());
        $this->assertTrue(Phone::setPhone(56987654321)->validate());
        $this->assertTrue(Phone::setPhone(56327654321)->validate());
    }

    public function testValidationFails()
    {
        $cases = function () {
            return [
                [2, Phone::setPhone('')],
                [3, Phone::setPhone(0)],
                [3, Phone::setPhone("+56327654321")],
                [4, Phone::setPhone(10987654321)],
                [5, Phone::setPhone(56317654321)],
                [6, Phone::setPhone(56987666666)],
            ];
        };
        foreach ($cases() as $case) {
            try {
                $case[1]->isValid();
                $this->fail('Exception was not thrown.');
            } catch (\Throwable $e) {
                $this->assertInstanceOf(\Exception::class, $e);
                $this->assertTrue(
                     $case[0] === $e->getCode(),
                    "Expected {$case[0]} but found {$e->getCode()}."
                );
            }
        }
        foreach ($cases() as $case) {
            $phone = $case[1]->quiet();
            $this->assertFalse($phone->isValid());
            $this->assertTrue(
                isset($phone->errors()[$case[0]]),
                "Expected {$case[0]} as error code."
            );
        }
    }

    public function testIgnorePrefixValidationSuccess()
    {
        $this->assertTrue(
            Phone::setPhone(56317654321)->ignorePrefix()->isValid()
        );
    }

    public function testIgnorePrefixValidationFails()
    {
        $this->assertFalse(
            Phone::setPhone(56317666666)->ignorePrefix()->quiet()->isValid()
        );
    }

    public function testGetSettedPhone()
    {
        $ccToNumber = Phone::setPhone('56987654321');
        $numberToCc = Phone::setPhone('987654321');
        $ccToNumber->validate();
        $numberToCc->validate();
        $this->assertTrue($ccToNumber->get() === '987654321');
        $this->assertTrue($numberToCc->get(true) === '56987654321');
    }

    public function testGetSettedPhoneMustThrowExceptionIfNotValidated()
    {
        try {
            Phone::setPhone('56987654321')->get();
            $this->fail('Exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals(
                "The phone needs to be validated.", $e->getMessage()
            );
            $this->assertEquals(1, $e->getCode());
        }
    }

    public function testGetParsedPhone()
    {
        $phone = Phone::parse('+56 9 87-654-321');
        $this->assertTrue($phone->get() === '987654321');
        $this->assertTrue($phone->get(true) === '56987654321');
    }

    public function testGetParsedPhoneMustThrowExceptionIfIsNotAValidPhone()
    {
        try {
            Phone::parse('56317654321')->get();
            $this->fail('Exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals(5, $e->getCode());
        }
    }

    public function testGetPrefix()
    {
        $this->assertTrue('9' === Phone::parse('56987654321')->getPrefix());
        $this->assertTrue('32' === Phone::parse('56327654321')->getPrefix());
        $this->assertTrue('2' === Phone::parse('217654321')->getPrefix());
    }

    public function testFixSettedPhone()
    {
        $this->assertTrue(
            '987654321' === Phone::setPhone('+56 09 87-654-321')->fix()->get()
        );
    }

    public function testFixSettedPhoneKeptValidationErrors()
    {
        $phone = Phone::setPhone('+56 09 87-654-321'); 
        $this->assertTrue(
            '987654321' === $phone->fix()->get()
        );
        $this->assertTrue($phone->isValid());
        $this->assertTrue(isset($phone->errors()[3]));
    }

    public function testThrowExceptionIfCantFix()
    {
        try {
            Phone::setPhone('56317654321')->fix();
            $this->fail('Exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals(5, $e->getCode());
        }
    }

    public function testLuckyFixSettedPhone()
    {
        $this->assertTrue(
            '237654321' === Phone::setPhone('+56 37-654-321')->luckyFix()->get()
        );
        $this->assertTrue(
            '237654321' === Phone::setPhone('037654321')->luckyFix()->get()
        );
    }

    public function testLuckyFixSettedPhoneKeptValidationErrors()
    {
        $phone = Phone::setPhone('5637654321'); 
        $this->assertTrue(
            '237654321' === $phone->luckyFix()->get()
        );
        $this->assertTrue($phone->isValid());
        $this->assertTrue(isset($phone->errors()[3]));
    }

    public function testFormatCall()
    {
        $this->assertEquals(
            '+56 2 37-654-321', Phone::parse('237654321')->format()
        );
    }

    public function testFactoryCall()
    {
        $this->assertInstanceOf(Factory::class, Phone::factory());
    }
}
