<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gmarsano\ChileanPhoneTool\Formatter;
use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

class FormatterTest extends TestCase
{
    public function testImplementsFormatterInterface()
    {
        $this->assertInstanceOf(FormatterInterface::class, new Formatter());
    }

    public function testFormat()
    {
        $formatter = new Formatter();
        $cases = [
            [FormatterInterface::STANDARD_FORMAT, '987654321', '+56 9 87-654-321'],
            [FormatterInterface::STANDARD_FORMAT, '237654321', '+56 2 37-654-321'],
            [FormatterInterface::STANDARD_FORMAT, '327654321', '+56 32 7-654-321'],
            [FormatterInterface::PREFIX_FORMAT, '987654321', '(9) 87-654-321'],
            [FormatterInterface::PREFIX_FORMAT, '327654321', '(32) 7-654-321'],
            [FormatterInterface::DIGITS_FORMAT, '987654321', '56987654321'],
            [FormatterInterface::NUMBER_DIGITS_FORMAT, '987654321', '987654321'],
        ];
        foreach ($cases as $case) {
            $formatted = $formatter->format($case[1], $case[0]);
            $this->assertTrue(
                $case[2] === $formatted,
                "Expected \"{$case[2]}\" but found \"$formatted\""
            );
        }
    }

    public function testDefaultFormat()
    {
        $formatter = new Formatter();
        $number = '987654321';
        $standardFormat = $formatter
            ->format($number, FormatterInterface::STANDARD_FORMAT);
        $default = $formatter->format($number);
        $this->assertTrue($standardFormat === $default);
    }
}
