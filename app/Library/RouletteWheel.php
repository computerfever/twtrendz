<?php

/**
 * RouletteWheel class.
 *
 * Implementation of the Roulette Wheel algorithm to pick up a delivery server
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Library;

class RouletteWheel
{
    public $weights = [];
    public $elements = [];
    public $allElements = [];
    public $allWeights = [];

    // @deprecated
    public static function take($a)
    {
        if (empty($a)) {
            return null;
        }

        $sum = 0.0;
        $total = array_sum(array_values($a));
        $r = self::frandom();

        // just in case
        if ($r == $sum) { // in other words, r == 0
            return array_keys($a)[0];
        }

        foreach ($a as $key => $percentage) {
            $newsum = $sum + (float) $percentage / (float) $total;
            if ($r > $sum && $r <= $newsum) {
                return $key;
            }
            $sum = $newsum;
        }

        // just in case
        return array_keys($a)[sizeof($a) - 1];
    }

    public static function frandom()
    {
        return (float) rand() / (float) getrandmax();
    }

    public function _contruct()
    {
        //
    }

    public function add($element, float $weightValue)
    {
        if ($weightValue < 0) {
            throw new \Exception("Invalid WEIGHT value. Must be >= 0");
        }

        $newElementIndex = sizeof($this->elements);
        $this->elements[$newElementIndex] = $element;
        $this->weights[$newElementIndex] = $weightValue;

        $this->allWeights[$newElementIndex] = $weightValue;
        $this->allElements[$newElementIndex] = $element;
    }

    public function select($takeOutOfList = false)
    {
        $selectedIndex = static::take($this->weights);

        if (is_null($selectedIndex)) {
            return;
        }

        $selectedElement = $this->elements[ $selectedIndex ];

        if ($takeOutOfList) {
            unset($this->elements[$selectedIndex]);
            unset($this->weights[$selectedIndex]);
        }

        return $selectedElement;
    }

    public function count()
    {
        return sizeof($this->elements);
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function getAllElementsInclDroppedOnes()
    {
        return $this->allElements;
    }

    public function reset()
    {
        $this->elements = $this->allElements;
        $this->weights = $this->allWeights;
    }
}
