<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2004-2014 odan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Molengo;

/**
 * Timer for performance Benchmarks
 *
 * @version 2014.08.27
 *
 * Example
 * <code>
 * $timer = new \Molengo\Timer();
 * $timer->start();
 * sleep(1);
 * $timer->stop();
 * $timer->write();
 * $timer->writeMemory();
 * $timer->writeMemoryMax();
 * </code>
 */
class Timer
{

    protected $numStart = 0;
    protected $numStop = 0;

    /**
     * Constructor
     * @param bool $boolStart
     */
    public function __construct($boolStart = false)
    {
        if ($boolStart) {
            $this->start();
        }
    }

    /**
     * Start counting time
     */
    public function start()
    {
        $this->numStart = microtime(true);
    }

    public function stop()
    {
        $this->numStop = microtime(true);
    }

    public function getStartTime()
    {
        return $this->numStart;
    }

    public function getStopTime()
    {
        return $this->numStop;
    }

    /**
     * Returns elapsed time
     * @return int
     */
    public function elapsed()
    {
        $this->stop();
        $numReturn = $this->getStopTime() - $this->getStartTime();
        return $numReturn;
    }

    /**
     * Reset timer
     */
    public function reset()
    {
        $this->numStart = 0;
        $this->numStop = 0;
    }

    /**
     * Print elapsed time formated in seconds
     *
     * @param string $strNewline
     */
    public function write($strNewline = "\n")
    {
        $str = sprintf('%.12f', $this->elapsed()) . ' sec.' . $strNewline;
        echo $str;
    }

    /**
     * Print the current amount of memory
     *
     * @param string $strNewline
     */
    public function writeMemory($strNewline = "\n")
    {
        $strReturn = $this->convertByteToString($this->memory()) . $strNewline;
        echo $strReturn;
    }

    /**
     * Print the peak amount of memory
     *
     * @param string $strNewline
     */
    public function writeMemoryMax($strNewline = "\n")
    {
        $strReturn = $this->convertByteToString($this->memoryMax()) . $strNewline;
        echo $strReturn;
    }

    /**
     * Returns the amount of memory allocated to PHP
     *
     * @return int
     */
    public function memory()
    {
        $numReturn = memory_get_usage(true);
        return $numReturn;
    }

    /**
     * Returns the peak of memory allocated by PHP
     * @return int
     */
    public function memoryMax()
    {
        $numReturn = memory_get_peak_usage(true);
        return $numReturn;
    }

    /**
     * Returns the memory size in human readable format
     *
     * @param float $numSize
     * @return string
     */
    public function convertByteToString($numSize)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $strReturn = round($numSize / pow(1024, ($i = floor(log($numSize, 1024)))), 2) . ' ' . $unit[$i];
        return $strReturn;
    }
}
