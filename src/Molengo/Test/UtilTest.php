<?php

namespace Molengo\Test;

class UtilTest extends \Molengo\TestCase
{

    /**
     * Test av function
     *
     * @covers av
     */
    public function testAv()
    {
        $arr = array();
        $arr['key'] = 'value';
        $arr['key2']['sub1'] = 'value2';
        $arr['key3']['sub1']['sub2'] = 'value3';

        $strResult = av($arr, ['key']);
        $this->assertEquals('value', $strResult);

        $strResult = av($arr, ['key2', 'sub1']);
        $this->assertEquals('value2', $strResult);

        $strResult = av($arr, ['key3', 'sub1', 'sub2']);
        $this->assertEquals('value3', $strResult);

        $strResult = av($arr, ['key4', 'sub1', 'sub2'], 'defaultvalue');
        $this->assertEquals('defaultvalue', $strResult);
    }

    /**
     * Test random_string function
     *
     * @covers random_string
     */
    public function testRandomString()
    {
        $strResult = random_string(0);
        $this->assertEquals(0, strlen($strResult));

        $strResult = random_string(10);
        $this->assertEquals(10, strlen($strResult));

        $strResult = random_string(255);
        $this->assertEquals(255, strlen($strResult));

        $strResult = random_string(12, true, true, true, true);
        $this->assertEquals(12, strlen($strResult));
    }

    /**
     * Test gh function
     *
     * @covers gh
     */
    public function testGh()
    {
        $strResult = gh(null);
        $this->assertEquals('', $strResult);

        $strResult = gh('');
        $this->assertEquals('', $strResult);

        $strResult = gh(' ');
        $this->assertEquals(' ', $strResult);

        $strResult = gh('abcdefghijklmnopqrstuvwxyz');
        $this->assertEquals('abcdefghijklmnopqrstuvwxyz', $strResult);

        $strResult = gh('01234567890');
        $this->assertEquals('01234567890', $strResult);

        $strResult = gh('öäü#+!"§$%&/()=?´\\~<>|ÿ');
        $this->assertEquals('&#246;&#228;&#252;&#35;&#43;&#33;&#34;&#167;&#36;&#37;&#38;&#47;&#40;&#41;&#61;&#63;&#180;&#92;&#126;&#60;&#62;&#124;&#255;', $strResult);

        $strResult = gh(mb_convert_encoding('öäü@€È/\<>', 'ISO-8859-1', 'UTF-8'));
        $this->assertEquals('&#246;&#228;&#252;&#64;&#63;&#200;&#47;&#92;&#60;&#62;', $strResult);
    }

    /**
     * Test blank function
     *
     * @covers blank
     */
    public function testBlank()
    {
        $boolResult = blank('');
        $this->assertTrue($boolResult);

        $boolResult = blank(false);
        $this->assertTrue($boolResult);

        $boolResult = blank(true);
        $this->assertFalse($boolResult);

        $boolResult = blank('0');
        $this->assertFalse($boolResult);

        $boolResult = blank('0.00');
        $this->assertFalse($boolResult);

        $boolResult = blank(0);
        $this->assertFalse($boolResult);

        $boolResult = blank(array());
        $this->assertTrue($boolResult);
    }

    /**
     * Test interpolate function
     *
     * @covers interpolate
     * @covers i
     */
    public function testInterpolate()
    {
        $strResult = interpolate('');
        $this->assertEquals($strResult, '');

        $strResult = interpolate('Test');
        $this->assertEquals($strResult, 'Test');

        $strResult = interpolate('User {username} created', array('username' => 'Max'));
        $this->assertEquals($strResult, 'User Max created');

        $strResult = i('User {username} created', array('username' => 'Max'));
        $this->assertEquals($strResult, 'User Max created');
    }
}
