<?php

namespace Molengo\Model;

/**
 * CRUD: Create, read, update and delete
 */
trait CrudModel
{

    /**
     * Table name
     *
     * @var string
     */
    protected $strTable = null;

    /**
     * Constructor
     *
     * @param \Molengo\DbMySql $db
     * @throws Exception if tablename is not defined
     */
    public function __construct(&$db = null)
    {
        parent::__construct($db);

        if ($this->strTable === null) {
            throw new Exception('Tablename not defined!');
        }
    }

    /**
     * Return all rows
     *
     * @return array
     */
    public function getAll()
    {
        $db = $this->getDb();

        $strSql = "SELECT * FROM {table} WHERE deleted = 0;";

        $arrInput = array(
            'table' => $db->escIdent($this->strTable)
        );

        $strSql = $db->prepare($strSql, $arrInput, false);
        $arrReturn = $db->queryAll($strSql);

        return $arrReturn;
    }

    /**
     * Read/Retrieve single record by Id
     *
     * @param string $strId
     * @return array
     */
    public function getById($strId)
    {
        $db = $this->getDb();

        $strSql = "SELECT * FROM {table} WHERE id = {id} AND deleted = 0;";

        $arrInput = array(
            'id' => $db->esc($strId),
            'table' => $db->escIdent($this->strTable)
        );

        $strSql = $db->prepare($strSql, $arrInput, false);
        $arrReturn = $db->queryRow($strSql);

        return $arrReturn;
    }

    /**
     * Create/Insert record
     *
     * @param array $arrRow
     * @param type $numInsertId new id
     * @return boolean
     */
    public function insert($arrRow, &$numInsertId = null)
    {
        $db = $this->getDb();

        $arrRow['created_at'] = now();
        $arrRow['created_user_id'] = $this->user->get('user.id');

        $boolReturn = $db->insertRow($this->strTable, $arrRow);

        if ($boolReturn) {
            $numInsertId = $db->getLastInsertId();
        }

        return $boolReturn;
    }

    /**
     * Update/Modify record
     *
     * @param array $arrRow
     * @return boolean status
     */
    public function update($arrRow)
    {
        $db = $this->getDb();

        $arrRow['updated_at'] = now();
        $arrRow['updated_user_id'] = $this->user->get('user.id');

        $arrWhere = array(
            'id' => $arrRow['id'],
            'deleted' => 0
        );

        $boolReturn = $db->updateRow($this->strTable, $arrWhere, $arrRow);
        return $boolReturn;
    }

    /**
     * Delete/Destroy record
     *
     * @param string $strId
     * @return boolean
     */
    public function delete($strId)
    {
        $db = $this->getDb();

        $arrRow = array(
            'deleted' => 1,
            'deleted_at' => now(),
            'deleted_user_id' => $this->user->get('user.id')
        );

        $arrWhere = array(
            'id' => $strId,
            'deleted' => 0
        );

        $boolReturn = $db->updateRow($this->strTable, $arrWhere, $arrRow);
        return $boolReturn;
    }

}
