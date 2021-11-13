<?php

namespace Gmarsano\ChileanPhoneTool;

use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

class Bag
{
    /**
     * bag
     *
     * @var array
     */
    protected $_bag;

    protected $_bak;
    /**
     * formatter
     *
     * @var FormatterInterface
     */
    protected $_formatter;

    protected $_lastFormat = FormatterInterface::NUMBER_DIGITS_FORMAT;

    private function __construct(array $data)
    {
        $this->_bag = $data;
    }

    /**
     * fill
     *
     * @param  array <string>$data
     * @return self
     */
    public static function fill(array $data): self
    {
        return new self($data);
    }

    /**
     * all
     *
     * @return array
     */
    public function all(): array
    {
        return $this->_bag;
    }

    /**
     * first
     *
     * @return string|null
     */
    public function first()
    {
        return $this->_bag[array_key_first($this->_bag)] ?? null;
    }

    /**
     * format
     *
     * @param  int $flag
     * @return self
     */
    public function format(int $flag = FormatterInterface::STANDARD_FORMAT): self
    {
        if (!isset($this->_formatter)) {
            $this->_formatter = new Formatter();
            $this->_bak = $this->_bag;
        }
        if ($this->_lastFormat !== $flag) {
            $this->_bag = FormatterInterface::NUMBER_DIGITS_FORMAT === $flag
                ? $this->_bak
                : array_map(function ($item) use ($flag) {
                    return $this->_formatter->format($item, $flag);
                }, $this->_bak);
        }
        return $this;
    }
}
