<?php
/**
 * Application Controler
 * Description of AppCtrl
 *
 * @author tsugie
 */
class AppCtrl {
    private static $instance = null;
    public static function get() {
        if(is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    const LOGNAME = "a.log";
    public function fatalError($_msg,$_code,$_debug) {
	echo "<h1>fatal error</h1><h2>$_code:$_msg</h2>";
        if( empty($GLOBALS['settings']) || $GLOBALS['settings']->getShowDebugMessage() ) {
            echo $_debug."<br/>\n";
            debug_print_backtrace();
        }
        exit;
    }
    public function normalError($_msg,$_code) {
	echo "<h1>normal error</h1><h2>$_code:$_msg</h2>";
        if( empty($GLOBALS['settings']) || $GLOBALS['settings']->getShowDebugMessage() ) {
            debug_print_backtrace();
        }
    }
    public function log($_msg) {
        $log_file = $GLOBALS['settings']->getenv()->log_path.LOGNAME;
        $str = date("Y:m:d H:i:s ", time()).$_msg;
        file_put_contents($log_file, $str, FILE_APPEND);
    }
    
    private $settings = null;
    public function GetSettings() {
        if(is_null($this->settings)) {
            $this->settings = Settings::get();
        }
        return $this->settings;
    }
    
    private $dbMan = null;
    public function GetDbMan() {
        if(is_null($this->dbMan)) {
            $this->dbMan = DbMan::get();
        }
        return $this->dbMan;
    }
    public function IsModRewriteDirectory($_requestUri) {
        if(strpos($_requestUri, '/p/')!==false) 
        {
            return true;
        }
        return false;
    }

}
