<?php

namespace Gmarsano\ChileanPhoneTool;

use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

class Phone
{
    public const COUNTRY_CODE = '56';

    public const AREA_CODES = [
        '32', '33', '34', '35', '39', '41', '42', '43', '45', '51',
        '52', '53', '55', '57', '58', '61', '63', '64', '65', '67',
        '68', '71', '72', '73', '75',
        // ip telephony
        '44',
        // deprecated codes
        '46', '56', '74', '80', '81', '82', '83',
    ];

    public const SINGLE_DIGITS_CODES = ['2', '9'];

    public const CELLPHONE_CODES = ['9'];

    /**
     * formatter
     *
     * @var FormatterInterface
     */
    protected $_formatter;

    /**
     * quiet guard.
     *
     * @var bool
     */
    protected $_quiet = false;
    
    /**
     * ignore prefix guard.
     *
     * @var bool
     */
    protected $_ignorePrefix = false;

    /**
     * was parsed once.
     *
     * @var bool
     */
    protected $_parsed = false;

    /**
     * validation result.
     *
     * @var bool|null
     */
    protected $_valid;

    /**
     * fix guard.
     *
     * @var bool|null
     */
    protected $_fixed;

    /**
     * phone value.
     *
     * @var mixed
     */
    protected $_phone;

    /**
     * original phone value backup.
     *
     * @var string
     */
    protected $_old;

    /**
     * prefix value.
     *
     * @var mixed
     */
    protected $_prefix;

    /**
     * phone digits.
     *
     * @var array
     */
    protected $_digits;

    /**
     * errors.
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * @param  string|int $phone
     * @return void
     */
    public function __construct($phone)
    {
        $this->initPhone($phone);
    }

    /**
     * prevent validation exceptions.
     *
     * @return self
     */
    public function quiet(): self
    {
        $this->_quiet = true;
        return $this;
    }

    public function ignorePrefix(): self
    {
        $this->_ignorePrefix = true;
        return $this;
    }

    /**
     * set phone only with significant digits from any input format.
     *
     * @param  string|int $phone
     * @return self
     */
    public static function parse($phone): self
    {
        $self = self::setPhone($phone);
        $self->parseSignificantDigits();
        return $self;
    }

    /**
     * set the phone value.
     *
     * @param  string|int $phone
     * @return self
     */
    public static function setPhone($phone): self
    {
        return new self($phone);
    }

    /**
     * get phone value or fixed value. If isn't in quiet mode and the input
     * isn't valid o validated, throws an exception.
     *
     * @param  bool $withCountryCode
     * @return string
     */
    public function get(bool $withCountryCode = false): string
    {
        $this->needValidationGuard();
        if (!$this->_valid) return $this->_parsed
            ? $this->getOld()
            : $this->_phone;
        return $withCountryCode
            ? self::COUNTRY_CODE . $this->_phone
            : $this->_phone;
    }

    /**
     * get() alias without country code option.
     *
     * @return string
     */
    public function number(): string
    {
        return $this->get();
    }

    /**
     * get() alias only for country code option.
     *
     * @return string
     */
    public function fullNumber(): string
    {
        return $this->get(true);
    }

    /**
     * return original input.
     *
     * @return string|null
     */
    public function getOld(): string
    {
        return $this->_old ?? $this->_phone;
    }

    /**
     * return phone prefix if it's a valid number and null otherwise.
     *
     * @return string|null
     */
    public function getPrefix()
    {
        $this->needValidationGuard();
        return $this->isValid() ? $this->_prefix : null;
    }

    /**
     * getPrefix alias.
     *
     * @return string|null
     */
    public function prefix()
    {
        return $this->getPrefix();
    }

    /**
     * format
     *
     * @param  int $flag
     * @return string
     */
    public function format(
        int $flag = FormatterInterface::STANDARD_FORMAT
    ): string {
        $phone = $this->get();
        if (!isset($this->_valid) || !$this->_valid) return $phone;
        return $this->getFormatter()->format($phone, $flag);
    }

    /**
     * getFormatter
     *
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        if (!isset($this->_formatter)) $this->setFormatter(new Formatter());
        return $this->_formatter;
    }

    /**
     * setFormatter
     *
     * @param  mixed $formatter
     * @return void
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->_formatter = $formatter;
    }

    /**
     * factory
     *
     * @return Factory
     */
    public static function factory(): Factory
    {
        return new Factory();
    }

    /**
     * get errors with error code as key.
     *
     * @return array
     */
    public function errors(): array
    {
        return array_reverse($this->_errors, true);
    }

    /**
     * get only errors messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return array_values($this->errors());
    }

    /**
     * tries to set a valid phone from original input.
     *
     * @return self
     */
    public function fix(): self
    {
        if (isset($this->_valid) && $this->_valid) return $this;
        if (!isset($this->_fixed)) {
            $this->_fixed = false;
            $this->validation(true);
        }
        return $this;
    }

    /**
     * tries to set a valid phone from original input and guess there is
     * metropolitan missing prefix and fix it. NOT SAFE: it is guessing!
     *
     * @return self
     */
    public function luckyFix(): self
    {
        $this->_valid = null;
        $this->_fixed = false;
        $this->validation(true, true);
        return $this;
    }

    /**
     * performs validation and throws exception, if phone is invalid. In quiet
     * mode it returns true if it is valid or false if it is invalid.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (!isset($this->_valid)) $this->validation();
        return $this->_valid;
    }

    public function isValid(): bool
    {
        return $this->validate();
    }

    protected function validation($fixing = false, $lucky = false)
    {
        if (!$this->passEmptyTest()) return;
        if (!$this->passMustBeSanitizedTest($fixing)) return;
        if (!$this->passEmptyTest()) return;
        if ($lucky && in_array(count($this->_digits), [8, 10])) {
            count($this->_digits) === 8
                ? array_unshift($this->_digits, '2')
                : array_splice($this->_digits, 2, 0, '2');
        }
        if (!$this->passCountryCodeTest()) return;
        if (!$this->passPrefixTest()) return;
        if (!$this->passSixConsecutiveEqualDigitsTest()) return;

        $this->_phone = implode($this->_digits);
        $this->_valid = true;
        if ($fixing) $this->_fixed = true;
    }

    protected function passEmptyTest()
    {
        if ($this->empty()) {
            $this->_valid = false;
            $this->throwEmpty();
            return false;
        }
        return true;
    }

    protected function passMustBeSanitizedTest(bool $fixing = false)
    {
        if (!$this->_parsed) {
            if (!isset($this->_old)) $this->_old = $this->_phone;
            $this->parseSignificantDigits();
            if ($this->_old !== $this->_phone) {
                if ($fixing) {
                    $quietOld = $this->_quiet;
                    $this->_quiet = true;
                    $this->throwInvalidFormat();
                    $this->_quiet = $quietOld;
                    return true;
                }
                $this->_valid = false;
                $this->throwInvalidFormat();
                return false;
            }
        }
        return true;
    }

    protected function passCountryCodeTest()
    {
        $len = count($this->_digits);
        if ($len != 11 && $len != 9) {
            $this->_valid = false;
            $this->throwInvalidFormat();
            return false;
        }
        if ($len == 11) {
            $code = array_shift($this->_digits) . array_shift($this->_digits);
            if ($code !== self::COUNTRY_CODE) {
                $this->_valid = false;
                $this->throwInvalidCountryCode();
                return false;
            }
        }
        return true;
    }

    protected function passPrefixTest()
    {
        $area = $this->_digits[0] . $this->_digits[1];
        if ($this->_ignorePrefix) {
            $this->_prefix = $area;
            return true;
        }
        $single = $this->_digits[0];
        $sTest = in_array($single, self::SINGLE_DIGITS_CODES);
        $aTest = in_array($area, self::AREA_CODES);
        if (!$sTest && !$aTest) {
            $this->_valid = false;
            $this->throwInvalidPrefix();
            return false;
        }
        $this->_prefix = $sTest ? $single : $area;
        return true;
    }

    protected function passSixConsecutiveEqualDigitsTest()
    {
        $last = null;
        $count = 1;
        foreach ($this->_digits as $digit) {
            if ($digit === $last) {
                $count++;
            } else {
                $count = 1;
                $last = $digit;
            }
            if ($count > 5) {
                $this->_valid = false;
                $this->throwInvalidPhoneNumber();
                return false;
            }
        }
        return true;
    }

    protected function parseSignificantDigits()
    {
        if (!ctype_digit($this->_phone)) {
            $this->_phone = preg_replace("/[^0-9]/", "", $this->_phone);
        }
        $this->_phone = ltrim($this->_phone, "0");
        $this->_digits = str_split($this->_phone);
        $this->_parsed = true;
    }

    protected function empty()
    {
        if ($this->_phone === "" || $this->_phone === null) return true;
        return false;
    }

    protected function initPhone($phone)
    {
        if (is_string($phone)) {
            $this->_phone = $phone;
            return;
        }
        if (is_int($phone)) {
            $this->_phone = strval($phone);
            return;
        }
        throw new \Exception("Invalid input type.", 1);
    }

    protected function needValidationGuard()
    {
        if ($this->_parsed) $this->validate();
        if (!$this->_quiet && !isset($this->_valid)) {
            throw new \Exception("The phone needs to be validated.", 1);
        } elseif ($this->_quiet) {
            $this->validate();
        }
    }

    protected function throwEmpty()
    {
        $this->throwMessage("Empty digits count.", 2);
    }

    protected function throwInvalidFormat()
    {
        $this->throwMessage("Invalid phone number format.", 3);
    }

    protected function throwInvalidCountryCode()
    {
        $this->throwMessage("Invalid country code.", 4);
    }

    protected function throwInvalidPrefix()
    {
        $this->throwMessage("Invalid prefix.", 5);
    }

    protected function throwInvalidPhoneNumber()
    {
        $this->throwMessage("Invalid phone number.", 6);
    }

    protected function throwMessage($message, $code)
    {
        if (!$this->_quiet) throw new \Exception($message, $code);
        $this->_errors[$code] = $message;
    }
}
