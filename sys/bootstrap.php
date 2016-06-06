<?php
date_default_timezone_set('Asia/Tokyo');
define('BASE_DIR', dirname(dirname(__FILE__)));
require BASE_DIR.'/sys/AppCtrl.php';
require BASE_DIR.'/sys/model/Settings.php';
require BASE_DIR.'/sys/dbsys/DbMan.php';
require BASE_DIR.'/sys/dbsys/AbsTbl.php';

$GLOBALS['settings'] = new Settings();
require $GLOBALS['settings']->getEnv()->smarty_path;
$GLOBALS['smarty'] = new Smarty();

/**
 * 
 * @return AppCtrl
 */
function GetAppCtrl() {
    return AppCtrl::get();
}

/**
 * 
 * @return DbMan
 */
function GetDbMan() {
    return DbMan::get();
}

function BtGetEnv() {
    return GetAppCtrl()->GetSettings()->GetEnv();
}

function GetInfo() {
    return GetAppCtrl()->GetSettings()->GetInfo();
}

/**
 * GetSmarty
 * @return Smarty
 */
function GetSmarty() {
    return $GLOBALS['smarty'];
}

