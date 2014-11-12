<?php

namespace Molengo\Test;

class MemoryTest extends \Molengo\TestCase
{

    public function testA()
    {
        // Set memory usage before loop
        $this->addMemoryUsage('Before Loop');

        $arr = array();
        for ($i = 0; $i < 3999; $i++) {
            $arr[] = str_repeat('a', $i);
        }
        // Set memory usage after loop
        $this->addMemoryUsage('After Loop');

        $this->assertEquals(true, true);
    }

    public function testB()
    {
        // Set memory usage before loop
        $this->addMemoryUsage('Before Loop B');

        $arr = array();
        for ($i = 0; $i < 9999; $i++) {
            $arr[] = str_repeat('a', $i);
        }

        $a = array();
        for ($i = 0; $i < 10000; $i++) {
            $a[] = "test";
        }

        $this->addMemoryUsage('After Loop B');
        $this->assertEquals(true, true);
    }

    public function testC()
    {
        $this->addMemoryUsage();

        $arr = array();
        for ($i = 0; $i < 100; $i++) {
            $arr[] = str_repeat('zz', $i);
        }

        $this->assertEquals(true, true);
        $this->addMemoryUsage();
    }
}
