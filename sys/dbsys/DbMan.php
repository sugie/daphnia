<?php
/**
 * DbRes class
 */
class DbRes {
    /* @var string */
    public $code;
    /* @var string */
    public $msg;
    /* @var array */
    public $lst;
    /* @var int */
    public $num;
    
    /**
     * コンストラクタ
     * @param string $_code
     * @param string $_msg
     * @param array $_num
     * @param int $_lst
     */
    public function __construct($_code,$_msg,$_num,$_lst) {
        $this->code = $_code;
        $this->msg  = $_msg;
        $this->num  = $_num;
        $this->lst  = $_lst;
    }
}
class DbMan {
    private static $instance = null;
    public static function get() {
        if(is_null(self::$instance)) {
            self::$instance = new self;
            self::$dmy1 = mt_rand(0, 99999);
        }
        return self::$instance;
    }
    
    private function IsMasterOnly() {
        return GetAppCtrl()->GetSettings()->IsDbMasterOnly();
    }
    
    private $mDbh;
    private $sDbh;
    private function __construct() {
        if(true) {
            $dsn = 'mysql:host='.BtGetEnv()->master_db_server.';dbname='.BtGetEnv()->master_db_name;
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            );
            try {
                $this->mDbh = new PDO($dsn, BtGetEnv()->master_db_user, BtGetEnv()->master_db_password, $options);
            } catch (Exception $ex) {
                GetAppCtrl()->fatalError("Can't connect to master database.","DM38","#".$dsn."#:".$ex->getMessage());
            }
            if(!$this->mDbh){
                GetAppCtrl()->fatalError("Can't connect to master database.","DM40","@".$dsn.":@"."");
            }
        }
        
        if(!$this->IsMasterOnly()) {
            $dsn = 'mysql:host='.BtGetEnv()->slave_db_server.';dbname='.BtGetEnv()->slave_db_name;
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ); 
            try {
                $this->sDbh = new PDO($dsn, BtGetEnv()->slave_db_user, BtGetEnv()->slave_db_password, $options);
            } catch (Exception $ex) {
                GetAppCtrl()->fatalError("Can't connect to master database.","DM50",$ex->getMessage());
            }
            if(!$this->sDbh) {
                GetAppCtrl()->fatalError("Can't connect to slave database.","DM56","");
            }
        }
    }
    private static  $dmy1;

    public function dummy1() {
        echo "<br/>\n".__FILE__.":".__LINE__; echo "Dummy1():".self::$dmy1;
    }
    
    //  プログラム中で一度だけ発行が必要になる SELECT 文に対しては、 PDO::query() の発行を検討してください。 
    //  複数回発行が必要な文については、PDO::prepare() による PDOStatement オブジェクトの準備と PDOStatement::execute() による文の発行を行ってください。
    public function FetchAllSlave($_sql,$_prms) {
        return $this->_FetchAllExec($_sql,$_prms,$this->IsMasterOnly());
    }
    public function FetchAllMaster($_sql,$_prms) {
        return $this->_FetchAllExec($_sql,$_prms,$__userMaster=true);
    }
    private function GetSlaveHandle() {
        return $this->IsMasterOnly()?$this->mDbh:$this->sDbh;
    }
    private function GetMasterHandle() {
        return $this->mDbh;
    }
    private function _FetchAllExec($_sql,$_prms,$_flagUseMaster) {
        $oneLineSql = false?preg_replace("/\n/"," ",$_sql):$_sql;
        $dbh = $_flagUseMaster?$this->mDbh:$this->sDbh;
        $sth = $dbh->prepare($oneLineSql);
        $res1 = $sth->execute($_prms);
        if($res1===false) {
            GetAppCtrl()->fatalError("QUERY ERROR","DM80", $dbh->errorInfo().":".$oneLineSql);
        }
        $num = $sth->rowCount();
        $lst = $sth->fetchAll();
        $res2 = new DbRes($__code=1,$__msg="",$num,$lst);
        return $res2;
    }

    public function EnumColumn($_tableName) {
        $sql = "SELECT * FROM $_tableName LIMIT 0,1";
        $dbh = $this->GetSlaveHandle();
        $sth = $dbh->prepare($sql);
        $res = $sth->execute();
        $num = $sth->rowCount();
        $nco = $sth->columnCount();

        $cols = array();
        for($i=0;$i<$nco;$i++){
            $meta = $sth->getColumnMeta($i);
            array_push($cols,$meta['name']);
        }
        return $cols;
    }

    /**
     * マスターDBでクエリを実行しPDOStatement オブジェクトを返します。
     * @param string $_sql
     * @param array $_prms
     * @return PDOStatement 
     */
    public function QueryMaster($_sql, $_prms) {
        $oneLineSql = false?preg_replace("/\n/"," ",$_sql):$_sql;
        $sth = $this->mDbh->prepare($oneLineSql);
        $res1 = $sth->execute($_prms);
        if($res1===false) {
            GetAppCtrl()->fatalError("QUERY ERROR","DM122", $this->mDbh->errorInfo().":".$oneLineSql);
        }
        return $sth;
    }
    
    /**
     * 指定されたPDOStatement オブジェクトから一行 FETCH_ASSOC したarrayを返します。
     * @param PDOStatement  $_sth
     * @return array
     */
    public function FetchAssocMaster($_sth) {
        $result = $_sth->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    
    /**
     * 指定されたPDOStatement オブジェクトに FetchAll を行い Fetch groupされた配列を返します。
     * 例：返値例 array('YMD0001'=>array(nm='Seiko', age='17'), 'YMD0002'=>array('nm'=>'Akina', age='16'))
     * @param PDOStatement  $_sth
     * @return array
     */
    public function FetchAllAssocMasterGrouped($_sth) {
        $result = $_sth->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
        return $result;
    }
    
    public function GetLastId() {
        return $this->mDbh->lastInsertId();
    }


//    public function ExecMaster() {
//        $res = new DbRes($__code=1,$__msg="",$num,$lst);
//        return $res;
//    }
}

