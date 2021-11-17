<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gmarsano\ChileanPhoneTool\Phone;
use Gmarsano\ChileanPhoneTool\Factory;

class FactoryTest extends TestCase
{
    public function testMakeAPhone()
    {
        $factory = new Factory();
        $this->assertTrue(
            Phone::setPhone($factory->make()->first())->quiet()->isValid()
        );
        $this->assertTrue(
            Phone::setPhone($factory->make(1, true)->first())
                ->quiet()->isValid()
        );
        $this->assertTrue(
            1 === count($factory->make(1, true)->all())
        );
    }

    public function testMakeTenPhones()
    {
        $factory = new Factory();
        $this->assertTrue(
            10 === count($factory->make(10)->all())
        );
    }

    public function testMakeCellPhone()
    {
        $factory = new Factory();
        $phone = Phone::setPhone($factory->cellPhone()->make()->first());
        $this->assertTrue($phone->quiet()->isValid());
        $prefix = $phone->prefix();
        $this->assertTrue(in_array($prefix, Phone::CELLPHONE_CODES));
    }

    public function testMakeLandLinePhone()
    {
        $factory = new Factory();
        $phone = Phone::setPhone($factory->landLine()->make()->first());
        $this->assertTrue($phone->quiet()->isValid());
        $options = array_diff(
            Phone::SINGLE_DIGITS_CODES,
            Phone::CELLPHONE_CODES
        );
        $options = array_merge($options, Phone::AREA_CODES);
        $prefix = $phone->prefix();
        $this->assertTrue(in_array($prefix, $options));
    }

    public function testMakePhoneWithGivenPrefix()
    {
        $factory = new Factory();

        $givenPrefix = '2';
        $phone = Phone::setPhone(
            $factory->prefix($givenPrefix)->make()->first()
        );
        $this->assertTrue($phone->quiet()->isValid());
        $prefix = $phone->prefix();
        $this->assertTrue($givenPrefix === $prefix);

        $givenPrefix = '32';
        $phone = Phone::setPhone(
            $factory->prefix($givenPrefix)->make()->first()
        );
        $this->assertTrue($phone->quiet()->isValid());
        $prefix = $phone->prefix();
        $this->assertTrue($givenPrefix === $prefix);
    }

    public function testGiveInvalidPrefixMustThrowException()
    {
        $factory = new Factory();
        try {
            $factory->prefix('11');
            $this->fail('Exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertEquals("Invalid prefix.", $e->getMessage());
        }
    }

    public function testExceptionThrowWhenCountBreaksMaxUniqueValues()
    {
        $factory = new Factory();
        $counts = [
            '9'     => pow(10, 8),
            '32'    => pow(10, 7)
        ];
        foreach ($counts as $key => $value) {
            try {
                $factory->prefix($key)->unique()->make($value);
                $this->fail('Exception was not thrown.');
            } catch (\Throwable $e) {
                $this->assertInstanceOf(\Exception::class, $e);
                $this->assertEquals(
                    "Can't get $value unique values.",
                    $e->getMessage()
                );
            }
        }
    }

    public function testCanITestUniqueMaybeDont()
    {
        $factory = new Factory();
        $count = 1000000;
        $phones = $factory->prefix('32')->unique()->make($count)->all();
        $this->assertCount($count, $phones);
        $this->assertCount($count, array_unique($phones));
        $phone = Phone::setPhone($phones[random_int(0, $count - 1)])->fix();
        $this->assertTrue('32' === $phone->prefix());
    }
}
