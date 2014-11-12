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

namespace Molengo\Model;

/**
 * Meta (KeyValue) Model
 *
 * Example
 * <code>
 * $cache = new MetaModel();
 * $cache->set('', now());
 * $cache->set('bbb', now());
 * $cache->set(array('a', 'b', 'c', 'd'), now());
 * echo $cache->get(array('a', 'b', 'c', 'd'));
 * </code>
 *
 * SQL:
 * <code>
 * CREATE TABLE `cache` (
 *  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 *  `v` longtext COLLATE utf8_unicode_ci,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci
 * </code>
 */
trait MetaModel
{

    protected $strTable = 'cache';

    /**
     * Set value by key
     *
     * @param mixed $mixKey
     * @param string $strValue
     * @return bool
     */
    public function set($mixKey, $strValue)
    {
        $mixKey = $this->getKey($mixKey);
        $boolReturn = $this->setValue($this->strTable, array('id' => $mixKey, 'v' => $strValue));
        return $boolReturn;
    }

    /**
     * Returns value by key
     *
     * @param mixed $mixKey
     * @param string $strDefault
     * @return string
     */
    public function get($mixKey, $strDefault = '')
    {
        $mixKey = $this->getKey($mixKey);
        $strReturn = $this->getValue($this->strTable, 'id', $mixKey, 'v', $strDefault);
        return $strReturn;
    }

    /**
     * Increment value
     *
     * @param mixed $mixKey
     * @param int $numIncrement
     * @return void
     */
    public function inc($mixKey, $numIncrement = 1)
    {
        $mixKey = $this->getKey($mixKey);
        $this->incValue($this->strTable, 'id', $mixKey, 'v', $numIncrement);
    }

    /**
     * Convert array to key string
     *
     * @param mixed $mixKey
     * @return mixed
     */
    protected function getKey($mixKey)
    {
        if (is_array($mixKey)) {
            $mixKey = implode('.', $mixKey);
        }
        return $mixKey;
    }

    /**
     * Set value(s)
     *
     * @param string $strTable
     * @param array $arrRow
     * @return bool
     *
     * <code>
     * $this->setValue('config', array('id' => 'now', 'v' => now()));
     * </code>
     */
    protected function setValue($strTable, $arrRow)
    {
        $db = $this->getDb();
        $boolReturn = $db->insertRow($strTable, $arrRow);
        return $boolReturn;
    }

    /**
     * Returns key value
     *
     * @param string $strTable
     * @param string $strColId
     * @param string $strId
     * @param string $strColValue
     * @param mix $mixDefault
     * @return string
     *
     * <code>
     * $this->getValue('config', 'id', 500, 'v', 'defaultvalue');
     * </code>
     */
    protected function getValue($strTable, $strColId, $strId, $strColValue, $mixDefault = '')
    {
        $strReturn = $mixDefault;

        $db = $this->getDb();
        $strSql = "SELECT {colvalue} FROM {table} WHERE {colid}={id};";

        $strSql = $db->prepare($strSql, array(
            'table' => $db->escIdent($strTable),
            'colvalue' => $db->escIdent($strColValue),
            'colid' => $db->escIdent($strColId),
            'id' => $db->esc($strId)), false);

        $strReturn = $db->queryValue($strSql, $strColValue, $mixDefault);
        return $strReturn;
    }

    /**
     * Increment key value
     *
     * @param string $strTable
     * @param string $strColId
     * @param string $strId
     * @param string $strColname
     * @param int $numIncrement
     * @return bool
     *
     * Example
     * <code>
     * $this->incValue('config', 'id', 500, 'v', 1);
     * </code>
     */
    protected function incValue($strTable, $strColId, $strId, $strColname, $numIncrement = 1)
    {
        $boolReturn = false;

        $db = $this->getDb();
        $strSql = "INSERT INTO {table} ({colid},{colv})
            VALUES ({id}, {inc})
            ON DUPLICATE KEY UPDATE {colv}={colv}+{inc};";

        $arrFields = array(
            'table' => $db->escIdent($strTable),
            'colid' => $db->escIdent($strColId),
            'colv' => $db->escIdent($strColname),
            'id' => $db->esc($strId),
            'inc' => $db->esc((int) $numIncrement, '')
        );

        $strSql = $db->prepare($strSql, $arrFields, false);
        $boolReturn = $db->exec($strSql);
        return $boolReturn;
    }
}
