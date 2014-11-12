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

class TestCase extends \PHPUnit_Framework_TestCase
{

    protected $numTime = 0;
    protected $numStartTime = 0;
    protected $arrMemory = array();

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->numStartTime = microtime(true);
        parent::setUp();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->numTime = microtime(true) - $this->numStartTime;
        parent::tearDown();
    }

    /**
     * Returns elapsed time
     *
     * @return float
     */
    public function getTime()
    {
        return $this->numTime;
    }

    /**
     * Returns current memory usage with or without styling
     *
     * @return int
     */
    protected function getMemoryUsage()
    {
        return memory_get_usage(true);
    }

    /**
     * Returns peak of memory usage
     *
     * @return int
     */
    protected function getMemoryUsagePeak()
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Append memory usage with info (optional)
     *
     * @param string $strInfo
     */
    protected function addMemoryUsage($strInfo = '')
    {
        $numMem = $this->getMemoryUsage();
        $strMemText = $this->formatByte($numMem);

        $this->arrMemory[] = array('time' => date('Y-m-d H:i:s'),
            'name' => $this->getName(),
            'info' => $strInfo,
            'memory' => $numMem,
            'memory_text' => $strMemText
        );
    }

    /**
     * Returns memory statistic
     *
     * @return array
     */
    public function getMemoryStatistic()
    {
        return $this->arrMemory;
    }

    /**
     * Clear memory statistic
     */
    protected function clearMemoryStatistic()
    {
        $this->arrMemory = array();
    }

    /**
     * Returns formated byte as string
     *
     * @param int $bytes
     * @param string $unit
     * @param int $decimals
     * @return string
     */
    protected function formatByte($bytes, $unit = "", $decimals = 2)
    {
        $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4,
            'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);

        $value = 0;
        if ($bytes > 0) {
            // Generate automatic prefix by bytes
            // If wrong prefix given
            if (!array_key_exists($unit, $units)) {
                $pow = floor(log($bytes) / log(1024));
                $unit = array_search($pow, $units);
            }

            // Calculate byte value by prefix
            $value = ($bytes / pow(1024, floor($units[$unit])));
        }

        // If decimals is not numeric or decimals is less than 0
        // then set default value
        if (!is_numeric($decimals) || $decimals < 0) {
            $decimals = 2;
        }

        // Format output
        $strReturn = sprintf('%.' . $decimals . 'f ' . $unit, $value);
        return $strReturn;
    }
}
