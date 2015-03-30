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

use PDO;

/**
 * Database Management for MySql
 *
 * @version 2014.08.23
 */
class DbMySql
{

    /**
     * PDO database connection
     * @var PDO
     */
    protected $connection = null;

    /**
     * Number of affected rows (exec)
     * @var int
     */
    protected $numAffectedRows = 0;

    /**
     * Enable logging of sql statements
     * @var bool
     */
    protected $boolDebug = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->clean();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->clean();
        $this->disconnect();
    }

    /**
     * Open db connection with DSN
     * @param string $strDsn
     * @return bool
     */
    public function connect($strDsn)
    {
        if (empty($strDsn)) {
            return false;
        }

        // if connection already open then disconnect
        if (isset($this->connection)) {
            $this->disconnect();
        }

        // get username and password
        $arrDsn = $this->parseDsn($strDsn);

        // open connection
        $this->connection = new \PDO($strDsn, $arrDsn['username'], $arrDsn['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

        // enable exceptions
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // convert nulls to empty string
        $this->connection->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);

        // convert column names to lower case.
        $this->connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        return true;
    }

    /**
     * Close the Database connection
     *
     * @return void
     */
    public function disconnect()
    {
        $this->clean();

        if (isset($this->connection)) {
            $this->connection = null;
        }
    }

    /**
     * Returns connection status
     *
     * @param bool $boolPing
     * @return bool
     */
    public function isConnected($boolPing = true)
    {
        $boolReturn = false;

        if (isset($this->connection)) {
            $boolReturn = true;
        }

        if ($boolReturn === true && $boolPing === true) {
            $arrRows = $this->queryAll('SELECT 1;');
            $boolReturn = ($arrRows !== null && is_array($arrRows));
        }

        return $boolReturn;
    }

    /**
     * Convert DSN (Data Source Name) to array
     *
     * @param string $strDsn
     * @return array
     */
    protected function parseDsn($strDsn)
    {
        $arrReturn = array();
        $arrParams = explode(';', $strDsn);
        foreach ($arrParams as $value) {
            list($k, $v) = explode('=', $value);
            $arrReturn[strtolower($k)] = trim($v);
        }
        return $arrReturn;
    }

    /**
     * Set an attribute
     *
     * @param int $numAttribute
     * @param mixed $mixValue
     * @return bool
     */
    public function setAttribute($numAttribute, $mixValue)
    {
        return $this->connection->setAttribute($numAttribute, $mixValue);
    }

    /**
     * Set debug mode
     *
     * @param bool
     * @return void
     */
    public function setDebug($boolDebug)
    {
        $this->boolDebug = $boolDebug;
    }

    /**
     * Get debug mode
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->boolDebug;
    }

    /**
     * Execute an SQL statement (INSERT, UPDATE, DELETE)
     *
     * @param string $strSql
     * @return bool
     */
    public function exec($strSql)
    {
        $this->clean();

        if ($strSql === '' || $strSql === null) {
            return false;
        }

        // execute SQL and read the number of affected rows
        $this->numAffectedRows = $this->connection->exec($strSql);
        $boolReturn = ($this->numAffectedRows !== false);

        if ($this->boolDebug) {
            $this->printDebug(__METHOD__, $strSql, $boolReturn);
        }

        return $boolReturn;
    }

    /**
     * Prepare sql statement. Bind parameter to {placeholder}
     *
     * @param string $strSql
     * @param array $arrFields
     * @param bool $boolEsc
     * @return string
     */
    public function prepare($strSql = '', $arrFields = array(), $boolEsc = true)
    {
        if ($strSql === null || $strSql === '') {
            return '';
        }

        if (empty($arrFields)) {
            return $strSql;
        }

        if ($boolEsc === true) {
            $arrFields = $this->escArray($arrFields);
        }

        $strSql = interpolate($strSql, $arrFields);
        return $strSql;
    }

    /**
     * Escapes special characters in a string for use in an SQL statement
     *
     * @param string $str
     * @param string $strQuotes Default is '
     * @return string quoted string for use in a query
     */
    public function esc($str, $strQuotes = "'")
    {
        // detect null value
        if ($str === null) {
            return 'NULL';
        } else {
            $str = $this->quote($str, PDO::PARAM_STR);
            if ($strQuotes !== "'") {
                $str = $strQuotes . substr($str, 1, -1) . $strQuotes;
            }
            return $str;
        }
    }

    /**
     * Escape identifier (column, table) with backtick
     *
     * @see: http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
     *
     * @param string $str
     * @param string $strQuotes
     * @return string identifier escaped string
     */
    public function escIdent($str, $strQuotes = "`")
    {
        $str = $this->esc($str, '');

        if (strpos($str, '.') !== false) {
            $arr = explode('.', $str);
            $str = $strQuotes . implode($strQuotes . '.' . $strQuotes, $arr) . $strQuotes;
        } else {
            $str = $strQuotes . $str . $strQuotes;
        }
        return $str;
    }

    /**
     * Escape identifier for array values only
     *
     * @param array $arrFields
     * @return array
     */
    public function escIdentArray($arrFields)
    {
        if (empty($arrFields)) {
            return $arrFields;
        }

        foreach ($arrFields as $key => &$value) {
            $arrFields[$key] = $this->escIdent($value);
        }
        return $arrFields;
    }

    /**
     * Escape tablename
     *
     * @todo test
     * @param string $strTable
     * @return string
     */
    public function escTable($strTable)
    {
        $strReturn = '';
        // dbname.tablename
        if (strpos($strTable, '.') !== false) {
            $arr = explode('.', $strTable);
            $strDbName = $this->escIdent($arr[0]);
            $strTable = $this->escIdent($arr[1]);
            $strReturn = $strDbName . '.' . $strTable;
        } else {
            // tablename
            $strReturn = $this->escIdent($strTable);
        }
        return $strReturn;
    }

    /**
     * Escape only array values
     *
     * @param array $arrFields
     * @return array
     */
    public function escArray($arrFields)
    {
        if (empty($arrFields)) {
            return $arrFields;
        }

        foreach ($arrFields as $key => &$value) {
            $arrFields[$key] = $this->esc($value);
        }
        return $arrFields;
    }

    /**
     * Escape array values and return as string list
     *
     * @param array $arrFields values
     * @param string $strSeperator seperator charachter
     */
    public function escArrayList($arrFields, $strSeperator = ',')
    {
        $strReturn = implode($strSeperator, $this->escArray($arrFields));
        return $strReturn;
    }

    /**
     * Escape identifier (escIdent) for a keys and esc for values
     *
     * @param array $arrFields
     * @param bool $boolNullSafe
     * @return string
     */
    public function escKvArray($arrFields, $boolNullSafe = false)
    {
        if (empty($arrFields)) {
            return $arrFields;
        }
        $strEqual = ($boolNullSafe) ? '<=>' : '=';
        foreach ($arrFields as $key => &$value) {
            $arrFields[$key] = $this->escIdent($key) . $strEqual . $this->esc($value);
        }
        return $arrFields;
    }

    /**
     * Quotes a string for use in a query
     *
     * @param string $str
     * @param int $numParameterType
     * @return string
     */
    protected function quote($str, $numParameterType = 0)
    {
        return $this->connection->quote($str, $numParameterType);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param string $strSql
     * @return PDOStatement
     */
    protected function query($strSql)
    {

        if ($this->boolDebug) {
            $this->printDebug(__METHOD__, $strSql);
        }

        $objStatement = $this->connection->query($strSql);

        if (!$objStatement) {
            throw new Exception($this->getError());
        }

        return $objStatement;
    }

    /**
     * Executes an SQL statement (SELECT) and return all rows as array
     *
     * @param string $strSql
     * @return array  containing all of the remaining rows in the result set.
     * If no rows have been returned, queryAll returns an empty array.
     */
    public function queryAll($strSql)
    {
        $arrReturn = array();

        $this->clean();

        if ($strSql === '' || $strSql === null) {
            return $arrReturn;
        }

        $objStm = $this->query($strSql);
        $arrReturn = $objStm->fetchAll(PDO::FETCH_ASSOC);

        return $arrReturn;
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * Important: If you insert multiple rows using a single INSERT statement,
     * getLastInsertId returns the value generated for the first inserted row only.
     * The reason for this is to make it possible to reproduce easily the same
     * INSERT statement against some other server.
     *
     * If you use bulk insert (insertRows function) use:
     * $numId = $db->getMaxId('table', 'id') + 1;
     *
     * @param string $strName Name of the sequence object from which the ID should be returned.
     * @return string
     */
    public function getLastInsertId($strName = null)
    {
        //$num_return = $this->queryValue('SELECT LAST_INSERT_ID() AS id;', 'id');
        $numReturn = $this->connection->lastInsertId($strName);
        return $numReturn;
    }

    /**
     * Return number of affected rows
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->numAffectedRows;
    }

    /**
     * File logging
     * @param string $str
     * @return void
     */
    public function log($str)
    {
        $strDir = '';
        if (defined('G_LOG_DIR')) {
            $strDir = G_LOG_DIR . '/';
        }
        $strFilename = $strDir . date('Y-m-d') . '_dbmysql.txt';
        file_put_contents($strFilename, date('Y-m-d H:i:s') . " - " . $str . "\n", FILE_APPEND);
    }

    /**
     * Clean some variables
     *
     * @return void
     */
    protected function clean()
    {
        $this->numAffectedRows = 0;
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getError()
    {
        $strReturn = '';
        // statements errorCode() returns an empty string before execution,
        // and '00000' (five zeros) after a sucessfull execution:
        $numError = $this->connection->errorCode();
        if ($numError !== '' && $numError !== '00000' && $numError > 0) {
            $arrError = $this->connection->errorInfo();
            $strReturn = trim('DB PDO ERROR: Code[' . $numError . '] ' . implode(':', $arrError));
        }
        return $strReturn;
    }

    /**
     * Print/Log debug message
     *
     * @param string $strMethod
     * @param string $strSql
     * @param string $mixResult
     */
    protected function printDebug($strMethod, $strSql, &$mixResult = null)
    {
        $strLog = '';

        if ($this->boolDebug) {
            $strLog = 'Function: ' . $strMethod . "\n";
            $strLog .= 'SQL: ' . $strSql . "\n";
            $this->log($strLog);
            return;
        }
        echo "<pre>\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo "Function: " . $strMethod . "\n";
        echo "SQL: $strSql\n";

        if (is_string($mixResult)) {
            echo "Result: " . $mixResult;
        } else {
            echo "Result:\n";
            print_r($mixResult);
        }

        echo "\n<pre>";
    }

    // -------------------------------------------------------------------------
    // Database helper
    // -------------------------------------------------------------------------

    /**
     * Switch database
     *
     * @param string $strDbName
     * @return bool
     */
    public function setDbName($strDbName)
    {
        return $this->exec('USE ' . $this->escIdent($strDbName) . ';');
    }

    /**
     * Return current database name
     *
     * @return string
     */
    public function getDbName()
    {
        $strReturn = $this->queryValue('SELECT database() AS dbname;', 'dbname');
        return $strReturn;
    }

    /**
     * Returns all databases
     *
     * @param string $strLike (optional) e.g. 'information%schema';
     * @return array
     */
    public function getDatabases($strLike = null)
    {
        $strSql = 'SHOW DATABASES;';
        if ($strLike !== null) {
            $strSql = 'SHOW DATABASES WHERE `database` LIKE {like};';
            $strSql = $this->prepare($strSql, array('like' => $strLike));
        }
        $arrReturn = $this->queryMapColumnValue($strSql, 'database');
        return $arrReturn;
    }

    /**
     * Retrieve only the given column of the first result row
     *
     * @param string $strSql
     * @param string $strColumn
     * @param type $strDefault
     * @return string
     */
    public function queryValue($strSql, $strColumn, $strDefault = '')
    {
        $strReturn = $strDefault;

        $arrRow = $this->queryRow($strSql);

        if (!empty($arrRow) && isset($arrRow[$strColumn])) {
            $strReturn = $arrRow[$strColumn];
        }

        return $strReturn;
    }

    /**
     * Retrieve only the first result row
     *
     * @param string $strSql
     * @return array
     */
    public function queryRow($strSql)
    {
        $arrReturn = array();

        $objSt = $this->query($strSql);
        $arrReturn = $objSt->fetch(PDO::FETCH_ASSOC);

        return $arrReturn;
    }

    /**
     * Retrieve only values from a given column
     *
     * sample:
     * $arrIds = $db->queryColumn('SELECT id FROM table;', 'id');
     *
     * @param string $strSql
     * @param string $strKey
     * @return array
     */
    public function queryColumn($strSql, $strKey)
    {
        $arrReturn = array();

        if (is_numeric($strKey)) {
            $obj_st = $this->query($strSql);
            $arrReturn = $obj_st->fetchAll(PDO::FETCH_COLUMN, $strKey);
        } else {
            $arr = $this->queryAll($strSql);
            if (!empty($arr)) {
                foreach ($arr as $row) {
                    $arrReturn[] = $row[$strKey];
                }
            }
        }
        return $arrReturn;
    }

    /**
     * Map query result by column as new index
     *
     * <code>
     * $arrRows = $db->queryMapColumn('SELECT * FROM table;', 'id');
     * </code>
     *
     * @param string $strSql
     * @param string $strKey Column name to map as index
     * @return array
     */
    public function queryMapColumn($strSql, $strKey)
    {
        $arrReturn = array();
        $arr = $this->queryAll($strSql);
        if (!empty($arr)) {
            foreach ($arr as $row) {
                $arrReturn[$row[$strKey]] = $row;
            }
        }
        return $arrReturn;
    }

    /**
     * Map column as new index and value
     *
     * <code>
     * $arrRows = $db->queryMapColumnValue('SELECT * FROM table;', 'id');
     * </code>
     *
     * @param string $strSql
     * @param string $strKey Column name to map as index and value
     * @return array
     */
    public function queryMapColumnValue($strSql, $strKey)
    {
        $arrReturn = array();
        $arr = $this->queryAll($strSql);
        if (!empty($arr)) {
            foreach ($arr as $row) {
                $arrReturn[$row[$strKey]] = $row[$strKey];
            }
        }
        return $arrReturn;
    }

    /**
     * Insert one row
     *
     * @param string $strTable
     * @param array $arrRow
     * @param bool $boolUpdate
     * @return bool
     */
    public function insertRow($strTable, array $arrRow, $boolUpdate = false)
    {
        $boolReturn = false;

        if (empty($arrRow) || !is_array($arrRow)) {
            return false;
        }

        $strSql = 'INSERT INTO {table} SET {v} ';

        if ($boolUpdate === true) {
            $strSql .= 'ON DUPLICATE KEY UPDATE {v}';
        }
        $strSql .= ";\n";

        $i = 0;
        $strValues = '';
        foreach ($arrRow as $key => $value) {
            $strValues .= (($i++ >= 1) ? ',' : '') . $this->escIdent($key) . '=' . $this->esc($value);
        }

        $strSql = $this->prepare($strSql, array(
            'table' => $this->escIdent($strTable),
            'v' => $strValues), false);

        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Insert rows
     *
     * @param string $strTable
     * @param array $arrRows
     * @param int $numChunk
     * @return bool
     */
    public function insertRows($strTable, array &$arrRows, $numChunk = 100)
    {
        $boolReturn = false;

        $this->clean();

        if (empty($arrRows)) {
            return false;
        }

        if (empty($numChunk)) {
            $numChunk = 100;
        }

        // find all keys in datatable
        $arrKeys = array();

        // inspect only first row
        $arrKeys = array_keys($arrRows[0]);

        // Syntax: INSERT INTO tbl_name (a,b,c) VALUES(1,2,3),(4,5,6),(7,8,9);
        $strInsert = 'INSERT INTO ' . $this->escIdent($strTable);

        // escape colnames
        $arrKeys2 = array();
        foreach ($arrKeys as $key => &$value) {
            $arrKeys2[$key] = $this->escIdent($value);
        }

        $strInsert .= ' (' . implode(',', $arrKeys2) . ') VALUES ';
        $arrKeys2 = null;
        $numAffectedRows = 0;

        // inert in small units
        foreach (array_chunk($arrRows, $numChunk) as $arrRowset) {
            $strSql = $strInsert;
            $i = 0;
            foreach ($arrRowset as $row) {
                $strValues = '';
                foreach ($arrKeys as $k) {
                    $strValues .= (($strValues == '') ? '' : ',') . $this->esc($row[$k]);
                }
                $strSql .= (($i++ == 0) ? '' : ',') . '(' . $strValues . ')';
            }
            $strSql .= ';';
            $this->exec($strSql);
            $numAffectedRows += $this->getAffectedRows();
        }

        $this->numAffectedRows = $numAffectedRows;
        $boolReturn = ($this->numAffectedRows > 0);

        return $boolReturn;
    }

    /**
     * Update row
     *
     * <code>
     * $db->updateRow('table_name', array('id' => 42), array('name' => 'bar'));
     * </code>
     *
     * @param string $strTable
     * @param array $arrWhere
     * @param array $arrRow
     * @return bool
     */
    public function updateRow($strTable, array $arrWhere, array $arrRow)
    {
        if (empty($arrRow)) {
            return false;
        }

        $strWhere = implode(' AND ', $this->escKvArray($arrWhere, false));
        $strFields = implode(',', $this->escKvArray($arrRow));

        $strSql = "UPDATE {table} SET {fields} WHERE {where};";

        $strSql = $this->prepare($strSql, array(
            'table' => $this->escIdent($strTable),
            'fields' => $strFields,
            'where' => $strWhere), false);

        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Delete row by clause ($arrWhere)
     *
     * <code>
     * $db->deleteRow('table_name', array('col2' => 42, 'col5' => 3));
     * </code>
     *
     * @param string $strTable
     * @param array $arrWhere
     * @return bool
     */
    public function deleteRow($strTable, array $arrWhere)
    {
        $boolReturn = false;

        $strId = implode(' AND ', $this->escKvArray($arrWhere, true));
        $strSql = "DELETE FROM {table} WHERE {id};";

        $arrInput = array(
            'table' => $this->escIdent($strTable),
            'id' => $strId
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $boolReturn = $this->exec($strSql);

        return $boolReturn;
    }

    /**
     * Copy an existing table to a new table
     *
     * @param string $strTableSource source table name
     * @param string $strTableDestination new table name
     * @param bool $boolCopyData with or without content (rows)
     * @return bool
     */
    public function copyTable($strTableSource, $strTableDestination, $boolCopyData = false)
    {
        $boolReturn = false;

        $strSql = 'CREATE TABLE {dest_table} LIKE {source_table};';

        $arrInput = array(
            'source_table' => $this->escIdent($strTableSource),
            'dest_table' => $this->escIdent($strTableDestination)
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $boolReturn = $this->exec($strSql);

        if ($boolReturn == true && $boolCopyData == true) {
            $boolReturn = $this->copyRows($strTableSource, $strTableDestination);
        }
        return $boolReturn;
    }

    /**
     * Copy all rows from one table to another
     *
     * @param string $strTableSource
     * @param string $strTableDestination
     * @return bool
     */
    public function copyRows($strTableSource, $strTableDestination)
    {
        $boolReturn = false;

        $strSql = 'INSERT INTO {dest_table} SELECT * FROM {source_table};';

        $strSql = $this->prepare($strSql, array(
            'source_table' => $this->escIdent($strTableSource),
            'dest_table' => $this->escIdent($strTableDestination)), false);

        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Delete a table
     *
     * @param string $strTable
     * @return bool
     */
    public function dropTable($strTable)
    {
        $boolReturn = false;
        $strSql = sprintf('DROP TABLE %s;', $this->escIdent($strTable));
        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Clear table content. Delete all rows.
     *
     * @param string $strTable
     * @return bool
     */
    public function clearTable($strTable)
    {
        $boolReturn = false;
        $strSql = sprintf('DELETE FROM %s;', $this->escIdent($strTable));
        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Truncate (drop and re-create) a table
     * Any AUTO_INCREMENT value is reset to its start value.
     *
     * @param string $strTable
     * @return bool
     */
    public function truncateTable($strTable)
    {
        $boolReturn = false;
        $strSql = sprintf('TRUNCATE TABLE %s;', $this->escIdent($strTable));
        $boolReturn = $this->exec($strSql);
        return $boolReturn;
    }

    /**
     * Rename table
     *
     * @param string $strSourceTable
     * @param string $strTargetTable
     * @return bool
     */
    public function renameTable($strSourceTable, $strTargetTable)
    {
        $boolReturn = false;
        $strSql = "RENAME TABLE {sourcetable} TO {targettable};";

        $arrKv = array();
        $arrKv['sourcetable'] = $this->escIdent($strSourceTable);
        $arrKv['targettable'] = $this->escIdent($strTargetTable);

        $strSql = $this->prepare($strSql, $arrKv, false);
        $boolReturn = $this->exec($strSql);

        return $boolReturn;
    }

    /**
     * Check if a MySQL table exists
     *
     * @param string $strDbName
     * @return bool
     */
    public function existDb($strDbName)
    {
        $boolReturn = false;

        $strSql = "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = {dbname};";

        $strSql = $this->prepare($strSql, array(
            'dbname' => $strDbName
        ));

        $arrRows = $this->queryAll($strSql);
        $boolReturn = (!empty($arrRows) && count($arrRows) == 1);
        return $boolReturn;
    }

    /**
     * Return all Tables from Database
     *
     * @param string $strLike (optional) e.g. 'information%'
     * @return array
     */
    public function getTables($strLike = null)
    {
        $arrReturn = array();
        $strDbName = 'database()';

        if ($strLike === null) {
            $strSql = "SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = {database};";
        } else {
            $strSql = "SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = {database}
                AND table_name LIKE {tablename};";
        }

        $arrInput = array(
            'tablename' => $this->esc($strLike),
            'database' => $strDbName
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $arrReturn = $this->queryMapColumnValue($strSql, 'table_name');

        return $arrReturn;
    }

    /**
     * Check if table exist
     *
     * @param string $strTable
     * @return boolean
     */
    public function existTable($strTable)
    {
        $boolReturn = false;

        // @todo test
        if (strpos($strTable, '.') !== false) {
            $strDbName = $this->escTable($strTable);
        } else {
            $strDbName = 'database()';
        }

        $strSql = "SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = {database}
            AND table_name = {table};";

        $arrInput = array(
            'database' => $strDbName,
            'table' => $this->esc($strTable)
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $arrRows = $this->queryAll($strSql);

        // @todo test
        if (is_array($arrRows) && !empty($arrRows)) {
            // table found
            $boolReturn = true;
        }

        return $boolReturn;
    }

    /**
     * Check if value exists
     *
     * @todo test
     *
     * @param string $strTable
     * @param string $strColname
     * @param string $strValue
     * @return bool
     */
    public function existValue($strTable, $strColname, $strValue)
    {
        $strSql = "SELECT {colname}
            FROM {table}
            WHERE {colname} <=> {value}
            LIMIT 1;";

        $arrInput = array(
            'table' => $this->escIdent($strTable),
            'colname' => $this->escIdent($strColname),
            'value' => $this->esc($strValue)
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $arrRow = $this->queryRow($strSql);

        $boolReturn = !empty($arrRow);
        return $boolReturn;
    }

    /**
     * Check if multiple values exists
     *
     * @param string $strTable
     * @param array $arrMatches array('code' => 2311, 'key' => 'value1')
     * @return bool
     */
    public function existValues($strTable, $arrMatches)
    {
        $strSql = 'SELECT 1 AS exist
            FROM {table}
            WHERE {matches} LIMIT 1;';

        $arrInput = array(
            'table' => $this->escIdent($strTable),
            'matches' => implode(' AND ', $this->escKvArray($arrMatches, true))
        );

        $strSql = $this->prepare($strSql, $arrInput, false);
        $arrRow = $this->queryRow($strSql);

        $boolReturn = !empty($arrRow);
        return $boolReturn;
    }

    /**
     * Calculate a hashkey (SHA1) using a table schema
     * Used to quickly compare table structures or schema versions
     *
     * @param string $strTable
     * @return string
     */
    public function getTableSchemaId($strTable)
    {
        $strKey = '';

        // full: with collation
        $strSql = 'SHOW FULL COLUMNS FROM %s;';

        $strSql = sprintf($strSql, $this->escIdent($strTable));
        $arrRs = $this->queryAll($strSql);

        if (!empty($arrRs) && is_array($arrRs)) {
            $strSchema = json_encode($arrRs);
            $strKey = sha1($strSchema);
        }
        return $strKey;
    }

    /**
     * Compare two tables and returns true if the table schema match
     *
     * @param string $strTable1
     * @param string $strTable2
     * @return bool
     */
    public function compareTableSchema($strTable1, $strTable2)
    {
        $strIdSource = $this->getTableSchemaId($strTable1);
        $strIdDest = $this->getTableSchemaId($strTable2);
        $boolReturn = ($strIdSource === $strIdDest);
        return $boolReturn;
    }

    /**
     * Returns all columns in a table
     *
     * @param string $strTable
     * @param string $strDbName
     * @return array
     */
    public function getTableColumns($strTable, $strDbName = null)
    {
        $strSql = 'SELECT
            column_name,
            column_default,
            is_nullable,
            data_type,
            character_maximum_length,
            character_octet_length,
            numeric_precision,
            numeric_scale,
            character_set_name,
            collation_name,
            column_type,
            column_key,
            extra,
            `privileges`,
            column_comment
            FROM information_schema.columns
            WHERE table_schema = {dbname}
            AND table_name = {table};';

        $arrFields = array();
        $arrFields['dbname'] = ($strDbName === null) ? 'DATABASE()' : $this->esc($strDbName);
        $arrFields['table'] = $this->esc($strTable);

        $strSql = $this->prepare($strSql, $arrFields, false);
        $arrReturn = $this->queryAll($strSql);

        return $arrReturn;
    }

    /**
     * Returns the column names of a table as a comma-separated list
     *
     * @param string $strTable
     * @param array $arrExclude exclude columns from result
     * @param bool $boolEscIdent escIdent for columns
     * @return string
     */
    public function getTableColumnsList($strTable, $arrExclude = array(), $boolEscIdent = false)
    {
        $arrFields = $this->getTableColumnsArray($strTable, $arrExclude, $boolEscIdent);
        $strReturn = implode(',', $arrFields);
        return $strReturn;
    }

    /**
     * Returns the column names of a table as an array
     *
     * @param string $strTable
     * @param array $arrExclude exclude columns from result
     * @param bool $boolEscIdent escIdent for columns
     * @return array
     */
    public function getTableColumnsArray($strTable, $arrExclude = array(), $boolEscIdent = false)
    {
        $arrReturn = array();
        $arrFields = $this->getTableColumns($strTable);

        if (!empty($arrFields)) {
            $arrExclude = array_flip($arrExclude);
            foreach ($arrFields as $value) {
                $strField = $value['column_name'];
                if (isset($arrExclude[$strField])) {
                    continue;
                }
                if ($boolEscIdent == true) {
                    $arrReturn[$strField] = $this->escIdent($strField);
                } else {
                    $arrReturn[$strField] = $strField;
                }
            }
        }
        return $arrReturn;
    }

    /**
     * Return maximum value from table field
     *
     * @param string $strTable
     * @param string $strColumn
     * @return string
     */
    public function getMaxId($strTable, $strColumn = 'id')
    {
        $numReturn = 0;
        $strSql = 'SELECT IFNULL(MAX({column}),0) as max_id FROM {table};';
        $strSql = $this->prepare($strSql, array(
            'column' => $this->escIdent($strColumn),
            'table' => $this->escIdent($strTable)), false);

        $numReturn = $this->queryValue($strSql, 'max_id');
        return $numReturn;
    }

    /**
     * Compress compatible with MySQL COMPRESS
     *
     * @param string $str data to compress
     * @param bool $boolBase64 encode to base64 (default = false)
     * @return string compressed data
     */
    public function compress($str, $boolBase64 = false)
    {
        if ($str === null || $str === '') {
            return $str;
        }

        $strReturn = pack('L', strlen($str)) . gzcompress($str);
        if ($boolBase64 === true) {
            $strReturn = base64_encode($strReturn);
        }
        return $strReturn;
    }

    /**
     * Uncompress compatible with MySQL UNCOMPRESS
     *
     * @param string $str
     * @param bool $boolBase64 decode from base64 (default = false)
     * @return string|null
     */
    public function uncompress($str, $boolBase64 = false)
    {
        if ($str === null || $str === '') {
            return $str;
        }
        if ($boolBase64 === true) {
            $str = base64_decode($str);
        }
        $str = gzuncompress(substr($str, 4));
        return $str;
    }

    /**
     * Initiates a transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commits a transaction
     *
     * @return bool
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rolls back a transaction
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }

}
