<?php

namespace Gmarsano\ChileanPhoneTool;

use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

class Formatter implements FormatterInterface
{
    /**
     * format
     *
     * @param  string $number
     * @param  int $flag
     * @return string
     */
    public function format(
        string $number,
        int $flag = self::STANDARD_FORMAT
    ): string {
        switch ($flag) {
            case self::STANDARD_FORMAT:
                extract($this->parse($number));
                return "+$cc $prefix $num";
            case self::PREFIX_FORMAT:
                extract($this->parse($number));
                return "($prefix) $num";
            case self::DIGITS_FORMAT:
                return Phone::COUNTRY_CODE . $number;
            case self::NUMBER_DIGITS_FORMAT:
                return $number;
        }
        throw new \Exception("Invalid formatter flag.", 1);
    }

    protected function parse($number): array
    {
        $digits = str_split($number);
        $isSinglePrefix = in_array($digits[0], Phone::SINGLE_DIGITS_CODES);
        $slice = $isSinglePrefix
            ? array_slice($digits, 1)
            : array_slice($digits, 2);
        $len = count($slice);

        $cc = Phone::COUNTRY_CODE;
        $prefix = $isSinglePrefix ? $digits[0] : "{$digits[0]}{$digits[1]}";
        $num = "";
        for ($i = $len - 1; $i >= 0; $i--) {
            $num = $slice[$i] . $num;
            if (($len - $i) % 3 === 0) $num = "-$num";
        }
        return compact('cc', 'prefix', 'num');
    }
}
