<?php
/**
 * AbsDbAccess class
 *
 * @category   7716
 * @package    7716
 * @subpackage
 * @copyright  Copyright(c)2015 Nanairo, Ltd. All Rights Reserved.
 * @license    Exclusive use for Nanairo.
 * @version    Release: @package_version@
 * @link       http://www.7-16.jp/
 * @since      Class available since Release 2015/01/29
 * @deprecated Class deprecated in Release 2015/01/29
 */

abstract class AbsTbl {
    const COLS             = 'cols';
    
    /**
     * テーブル名を見て対象クラスタを判定
     * @return int クラスタ種別
     */
    public static function getClusterType( $_tableName ) {
        // テーブル名がmasかcrsではじまれば CLUSTER_MAS_DATA と判定
        if(strncasecmp( $_tableName, self::TABLE_PREFIX_MASTER, strlen( self::TABLE_PREFIX_MASTER ) ) === 0
        || strncasecmp( $_tableName, self::TABLE_PREFIX_CROSS, strlen( self::TABLE_PREFIX_CROSS ) ) === 0
        )
        {
            return self::CLUSTER_MAS_DATA;
        }
        return self::CLUSTER_USER_DATA;
    }
    
    /**
     * テーブル名を取得します
     * @param int $paramPlayerId プレイヤーIDを取得します
     * @return String テーブル名を返します
     */
    private function _getTableName() {
        return $this->_name;
    }
    
    /**
     * テーブル名を取得します(スプリットテーブルには非対応)
     * @return String テーブル名を返します
     */
    public function getTableName() {
        return $this->_name;
    }
    
    /**
     * カラム名CSV文字列を作成する
     * 例: USER_PK, NN, RUSH
     * @return <type> CSV文字列を返す
     */
    protected function makeColumnNameCsv() {
        $cols = $this->GetCols();
        $row = "";
        foreach( $cols as $oneColName ) {
            $row .= ", ".$oneColName;
        }
        return substr( $row, 1 );
    }
    const UPDATER_COLUMN_NAME = 'psUpg';
    const CREATED_COLUMN_NAME = 'psCdt';
    const UPDATED_COLUMN_NAME = 'psUdt';
    
    /**
     * IsSystemColumn
     * @param string $_cName
     * @return boolean
     */
    public function IsSystemColumn($_cName) {
        if(    $this->IsUpdaterColumn($_cName)
            || $this->IsCreatedrColumn($_cName) 
            || $this->IsUpdatedColumn($_cName)
        ) {
            return true;
        }
        return false;
    }

    /**
     * IsUpdaterColumn
     * @param string $_cName
     * @return boolean
     */
    private function IsUpdaterColumn($_cName) {
        if(strcasecmp($_cName,  self::UPDATER_COLUMN_NAME)===0) {
            return true;
        }
        return false;
    }

    /**
     * IsCreatedrColumn
     * @param string $_cName
     * @return boolean
     */
    private function IsCreatedrColumn($_cName) {
        if(strcasecmp($_cName,  self::CREATED_COLUMN_NAME)===0) {
            return true;
        }
        return false;
    }

    /**
     * IsUpdatedColumn
     * @param string $_cName
     * @return boolean
     */
    private function IsUpdatedColumn($_cName) {
        if(strcasecmp($_cName,  self::UPDATED_COLUMN_NAME)===0) {
            return true;
        }
        return false;
    }
    
    /**
     * GetProgramId
     * @return int
     */
    public static function GetProgramId() {
        $lst = explode( "/", $_SERVER["REQUEST_URI"] );
        $chkname = $lst[count($lst)-1];                                                                                           
        $program_id = hexdec( substr( md5($chkname), 0, 3) )*(-1);
        return $program_id;
    }

    /**
     * カラムを列挙する(スプリットテーブルには非対応)
     * @return <type> カラム名配列が返る
     */
    public function GetCols() {
        // colsがnullであればDBのカラムを列挙
        if( empty( $this->_cols ) ) {
            $this->_cols = GetDbMan()->EnumColumn($this->_getTableName($this->_name));
            return $this->_cols;
        }
        else {
            return $this->_cols;
        }
    }

    /**
     * GetPrimarys
     * @return type
     */
    public function GetPrimarys() {
        return $this->_primary;
    }

    /**
     * GetFirstPrimary
     * @return string
     */
    public function GetFirstPrimary() {
        $tmpPrimarys = $this->GetPrimarys();
        $firstPrimaryColumnName = $tmpPrimarys[0];
        return $firstPrimaryColumnName;
    }
    
    public function Set($_setData,$_strWhere,$_prmWhere) {
        // 既存行存在チェックを行い存在しなければInsert 存在する場合はUpdateを行います。
        $sql = "SELECT COUNT(*) as cnt FROM ".$this->_getTableName()." WHERE ".$_strWhere;
        $resDb = GetDbMan()->FetchAllMaster($sql, $_prmWhere);
        $cnt = $resDb->lst[0]['cnt'];
        if($cnt>0) {
            $this->Update($_setData, $_strWhere, $_prmWhere);
        }
        else {
            $this->Insert($_setData);
        }
    }

    /**
     * Update
     * @param array $_setData
     * @param string $_strWhere
     * @param array $_prmWhere
     * @return boolean
     */
    public function Update($_setData,$_strWhere,$_prmWhere) {
        $arrayColumnName = $this->GetCols();
        $valuePart = "";
        $valueArray = array();
        $strSet = "";
        $updateColumnFoundFlag = false;
        foreach($_setData as $cName=>$value) {
            if(in_array($cName,$arrayColumnName)){
                $strSet .= ",".$cName."=?";
                array_push($valueArray,$value);
                if($this->IsUpdatedColumn($cName)) {
                    $updateColumnFoundFlag = true;
                }
            }
        }
        if(!$updateColumnFoundFlag) {
            $strSet .= ",".self::UPDATED_COLUMN_NAME."=?";
            array_push($valueArray, (string)date("c"));
        }
        $strSet .= ",".self::UPDATER_COLUMN_NAME."=?";
        array_push($valueArray,self::GetProgramId());
       
        $strSet = substr( $strSet, 1 );
        $sql = "UPDATE ".$this->_getTableName()." SET ".$strSet." WHERE ".$_strWhere;
        GetDbMan()->QueryMaster($sql, array_merge($valueArray,$_prmWhere));
        return true;

    }
    
    /**
     * Insert
     * @param array $_data
     * @return boolean
     */
    public function Insert($_data) {
        $arrayColumnName = $this->GetCols();
        $valuePart = "";
        $valueArray = array();
        foreach( $arrayColumnName as $cName ) {
            if(in_array($cName, $arrayColumnName)) {
                if($this->IsUpdaterColumn($cName)) {
                    $_data[$cName] = self::GetProgramId();
                } elseif($this->IsCreatedrColumn($cName)) {
                    $_data[$cName] = (string)date("c");
                } elseif($this->IsUpdatedColumn($cName)) {
                    $_data[$cName] = (string)date("c");
                }
            }
            $cValue = $_data[$cName];
            $valuePart .= ",?";
            array_push($valueArray,$_data[$cName]);
        }
        $valuePart = substr( $valuePart, 1 );
        $sql = "INSERT INTO ".$this->_getTableName()." ( ".$this->makeColumnNameCsv()." ) VALUES( $valuePart )";
        GetDbMan()->QueryMaster($sql, $valueArray);
        return true;
    }
    
    /**
     * GetLastId
     * @return int
     */
    public function GetLastId() {
        return GetDbMan()->GetLastId();
    }
    
    /**
     * Delete
     * @param string $_strWhere
     * @param array $_prmWhere
     * @return type
     */
    public function Delete($_strWhere, $_prmWhere) {
        $sql = "DELETE FROM ".$this->_getTableName()." WHERE ".$_strWhere;
        $dbRes = GetDbMan()->QueryMaster( $sql, $_prmWhere );
        return $dbRes;
    }
    
    /**
     * Find
     * @param string $_strWhere
     * @param array $_prmWhere
     * @param int $_startRow
     * @param int $_numRow
     * @param int $_order
     * @return DbRes
     */
    public function Find($_strWhere, $_prmWhere, $_startRow=null, $_numRow=null, $_order=null) {
        $sql = "SELECT ".$this->makeColumnNameCsv()." FROM ".$this->_getTableName();
        $sql .= (is_null($_strWhere)?" ":" WHERE ".$_strWhere);
        $sql .= is_null($_order)?"":" ORDER BY ".  addslashes($_order);
        $sql .= is_null($_startRow)?"":" LIMIT ".$_startRow.",".$_numRow;
        $dbRes = GetDbMan()->FetchAllSlave( $sql, $_prmWhere );
        return $dbRes;
    }

}
