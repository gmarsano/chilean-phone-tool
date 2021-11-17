<?php

namespace Gmarsano\ChileanPhoneTool;

use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

class Factory
{
    private $_unique = false;
    private $_cellPhone = false;
    private $_landLine = false;
    private $_prefix;
    private $_options;

    public function make(int $count = 1, bool $countryCode = false): Bag
    {
        if (!isset($this->_prefix)) $this->initRandomPrefixOptions();
        if ($this->_unique && !$this->checkMaxCountAllowed($count)) {
            throw new \Exception("Can't get $count unique values.", 1);
        }
        $response = $this->_unique
            ? $this->getUniqueBase($count)
            : $this->getBase($count);
        $response = Bag::fill($response);
        if ($countryCode) $response->format(FormatterInterface::DIGITS_FORMAT);
        $this->_options = null;
        return $response;
    }

    public function unique(): self
    {
        $self = clone $this;
        $self->_unique = true;
        return $self;
    }

    public function cellPhone(): self
    {
        $self = clone $this;
        $self->_cellPhone = true;
        $self->_landLine = false;
        $self->_prefix = null;
        return $self;
    }

    public function landLine(): self
    {
        $self = clone $this;
        $self->_landLine = true;
        $self->_cellPhone = false;
        $self->_prefix = null;
        return $self;
    }

    public function prefix(string $prefix): self
    {
        if (!in_array(
            $prefix,
            array_merge(Phone::SINGLE_DIGITS_CODES, Phone::AREA_CODES)
        )) throw new \Exception("Invalid prefix.", 1);
        $self = clone $this;
        $self->_prefix = $prefix;
        return $self;
    }

    protected function initRandomPrefixOptions()
    {
        if ($this->_cellPhone) {
            $options = Phone::CELLPHONE_CODES;
        } elseif ($this->_landLine) {
            $options = array_diff(
                Phone::SINGLE_DIGITS_CODES,
                Phone::CELLPHONE_CODES
            );
            $options = array_merge($options, Phone::AREA_CODES);
        } else {
            $options = array_merge(
                Phone::SINGLE_DIGITS_CODES,
                Phone::AREA_CODES
            );
        }

        $this->_options = $options;
    }

    protected function getUniqueBase(int $count): array
    {
        $chunk = $this->getSCurveValue($count);
        $uCount = 0;
        $collect = [];
        while ($uCount < $count) {
            for ($i = 0; $i < $chunk; $i++) {
                $collect[] = $this->getValidRandomBaseNumber();
            }
            $unique = array_unique($collect);
            $uCount = count($unique);
        }

        return array_slice($unique, 0, $count);
    }

    protected function getBase(int $count): array
    {
        $collect = [];
        for ($i = 0; $i < $count; $i++) {
            $collect[] = $this->getValidRandomBaseNumber();
        }
        return $collect;
    }

    protected function getValidRandomBaseNumber(): string
    {
        $prefix = $this->_prefix ?? $this->getRandomPrefix();
        $digits = strlen($prefix) == 1 ? 8 : 7;
        $min = pow(10, $digits - 1);
        $max = (int)str_repeat('9', $digits);
        do {
            $phone = $prefix . random_int($min, $max);
        } while (!Phone::setPhone($phone)->quiet()->validate());
        return $phone;
    }

    protected function getRandomPrefix(): string
    {
        return $this->_options[array_rand($this->_options)];
    }

    protected function getSCurveValue(int $x): int
    {
        $k = 0.0001;
        $divisor = 1 + pow(exp(1), (-$k * ($x - 1)));
        return ceil(60000 / $divisor - 29990);
    }

    protected function checkMaxCountAllowed(int $count): bool
    {
        $lastLen = 0;
        $options = $this->_prefix
            ? [$this->_prefix]
            : $this->_options;
        foreach ($options as $value) {
            $len = strlen($value);
            if ($len > $lastLen) $lastLen = $len;
        }
        return $count < pow(10, 9 - $lastLen);
    }
}
