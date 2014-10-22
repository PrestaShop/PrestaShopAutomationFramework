<?php

namespace PrestaShop\Helper;

/**
 * http://en.wikipedia.org/wiki/Bollinger_Bands
 *
 * This is used sometimes to help determine when a value is abnormally high or low.
 *
 */

class Bollinger
{
    public function __construct($sample_size = 20)
    {
        $this->sample_size = $sample_size;
        $this->samples = new \SplDoublyLinkedList();
        $this->average = 0;
        $this->stdev = 0;
    }

    public function addSample($s, $K = 2)
    {
        if ($this->samples->count() > 0)
            $this->average = $this->average * $this->samples->count() - $this->samples->bottom();

        $this->samples->push($s);

        $this->average += $s;

        if ($this->samples->count() > $this->sample_size)
            $this->samples->shift();

        $this->average /= $this->samples->count();

        $stdev = 0;
        foreach ($this->samples as $v)
            $stdev += pow($this->average - $v, 2);

        $this->stdev = sqrt($stdev / $this->samples->count());

        if ($this->samples->count() < $this->sample_size)
            return 0;
        elseif ($s > $this->average + $K * $this->stdev)
            return 1;
        elseif ($s < $this->average - $K * $this->stdev)
            return -1;
        else
            return 0;
    }
}
