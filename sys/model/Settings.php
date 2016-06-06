<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author tsugie
 */
class Settings {
    private static $instance = null;
    public static function get() {
        if(is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    /** 環境設定XMLパス */
    const ENV_FILE_PATH = '/sys/settings/env.json';
    /** インフォメーション設定ファイルパス */
    const INFO_FILE_PATH = '/sys/settings/info.json';
    protected $env;
    protected $info;
    
    public function GetEnv() {
        return $this->env;
    }
    
    public function GetInfo() {
        return $this->info;
    }

    /**
     * コンストラクタ
     */
    function __construct() {
        $envFile  = dirname( dirname( dirname( ( __FILE__ ) ) ) ).self::ENV_FILE_PATH;
        $infoFile = dirname( dirname( dirname( ( __FILE__ ) ) ) ).self::INFO_FILE_PATH;
        $this->loadEnv($envFile);
        $this->loadInfo($infoFile);
    }
    
    private function loadEnv($_path) {
        if( !file_exists( $_path ) ) {
            AppCtrl::fatalError("ST12", "Not exists env.json [".$_path."]" );
        }
        $this->env = json_decode(file_get_contents($_path));
    }
    
    private function loadInfo($_path) {
        if( !file_exists( $_path ) ) {
            AppCtrl::fatalError("ST14", "Not exists info.json [".$_path."]" );
        }
        $this->info = json_decode(file_get_contents($_path));
    }
    
    public function IsDbMasterOnly() {
        if(intval($this->GetEnv()->db_master_only)==1) {
            return true;
        }
        return false;
    }
    
    /**
     * @assert (0, 0) == 0
     * @assert (0, 1) == 1
     * @assert (1, 0) == 1
     * @assert (1, 1) == 2
     * @assert (1, 2) == 4
     */
    public function add($a, $b)
    {
        return $a + $b;
    }
    
    public function getShowDebugMessage() {
        return true;
    }
}
