<?php

class FluentCalculator
{
    const MAX_NUM_LEN = 9;

    protected $stack = [];
    protected $numLookup = [
        'zero' => 0,
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
    ];
    protected $opLookup = [
        'plus' => 1,
        'minus' => 1,
        'times' => 1,
        'dividedBy' => 1,
    ];
    protected $lastItemIsOperation = false;

    public static function init() {
        return new static();
    }

    public function __get($name)
    {
        if (isset($this->numLookup[$name])) {
            if (empty($this->stack) || $this->lastItemIsOperation) {
                $this->stack[] = '';
            }

            $this->lastItemIsOperation = false;

            $key = count($this->stack) - 1;
            if (!empty($this->stack[$key]) || $name !== 'zero') {
                $this->stack[$key] .= $this->numLookup[$name];
            }
        } else {
            if (!empty($this->opLookup[$name])) {
                if ($this->lastItemIsOperation) {
                    $this->stack[count($this->stack) - 1] = $name;
                } else {
                    $this->stack[] = $name;
                }

                $this->lastItemIsOperation = true;
            } else {
                throw new InvalidInputException();
            }
        }

        return $this;
    }

    public function __call($method, $args)
    {
        if (isset($this->numLookup[$method])) {
            if ($this->lastItemIsOperation) {
                $this->stack[] = $this->numLookup[$method];
            } else {
                if (empty($this->stack)) {
                    $this->stack[] = '';
                }

                $this->stack[count($this->stack) - 1] .= $this->numLookup[$method];
            }
        } elseif (!empty($this->opLookup[$method])) {
            // we can add item to stack but
            // there will be no operands on
            // the right, so just skip it
        } else {
            throw new InvalidInputException();
        }

        // now count result:
        $r = 0;
        $first_run = true;

        while ($this->stack) {
            $cur = array_shift($this->stack);
            if (!empty($this->opLookup[$cur])) {
                if (empty($this->stack)) {
                    continue;
                }

                $operand = array_shift($this->stack);
                if (strlen(abs($operand)) > self::MAX_NUM_LEN) {
                    throw new DigitCountOverflowException();
                }

                switch ($cur) {
                    case 'plus':
                        $r += $operand;
                        break;

                    case 'minus':
                        if ($first_run) {
                            $r = -1 * $operand;
                        } else {
                            $r -= $operand;
                        }
                        break;

                    case 'times':
                        $r *= $operand;
                        break;

                    case 'dividedBy':
                        if (0 == $operand) {
                            throw new DivisionByZeroException();
                        }
                        //$r /= $operand;
                        $r = intdiv($r, $operand);
                        break;
                }
            } else {
                $r = (int)$cur;
            }

            if (strlen(abs($r)) > self::MAX_NUM_LEN) {
                throw new DigitCountOverflowException();
            }

            $first_run = false;
        }

        return $r;
    }
}
