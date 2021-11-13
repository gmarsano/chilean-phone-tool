<?php

namespace Gmarsano\ChileanPhoneTool\Contracts;

interface FormatterInterface
{
    public const STANDARD_FORMAT = 1; // +56 9 87-654-321, +56 75 7-654-321
    public const PREFIX_FORMAT = 2; // (9) 87-654-321, (75) 7-654-321
    public const DIGITS_FORMAT = 3; // 56987654321, 56757654321
    public const NUMBER_DIGITS_FORMAT = 4; // 987654321, 757654321

    public function format(string $number, int $flag): string;
}
