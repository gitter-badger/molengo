<?php

namespace Molengo\Test;

/**
 * @coversDefaultClass \Molengo\DbMySql
 */
class DbMySqlTest extends \Molengo\TestCase
{

    protected static $db;

    /**
     * Returns database object
     *
     * @return \Molengo\DbMySql
     */
    protected function getDb()
    {
        if (self::$db === null) {
            self::$db = new \Molengo\DbMySql();
        }
        return self::$db;
    }

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Test create object
     *
     * @return void
     */
    public function testInstance()
    {
        $this->assertTrue(class_exists('\Molengo\\DbMySql'));
        $this->assertInstanceOf('\Molengo\\DbMySql', new \Molengo\DbMySql());
    }

    /**
     * Test connect method
     *
     * @return void
     * @covers ::connect
     */
    public function testConnect()
    {
        $strDsn = 'mysql:host=127.0.0.1;port=3306;dbname=molengo_test;username=root;password=';

        $db = $this->getDb();
        $result = $db->connect($strDsn);
        $this->assertEquals(true, $result);
    }

    /**
     * Test isConnected method
     *
     * @return void
     * @covers ::isConnected
     */
    public function testIsConnected()
    {
        //$this->markTestSkipped('must be revisited.');
        $db = $this->getDb();
        $result = $db->isConnected();
        $this->assertEquals(true, $result);
    }
}
