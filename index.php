<?php
define("INDEXPHP",true);
require_once './sys/bootstrap.php';
$appCtrl = AppCtrl::get();

const DETAULT_INDEX_PAGE = 'index';
$page = filter_input(INPUT_GET,'page',FILTER_SANITIZE_STRING);
if(empty($page)) {
    $page = filter_input(INPUT_POST,'page',FILTER_SANITIZE_STRING);
    if(empty($page)) {
        $page = DETAULT_INDEX_PAGE;
    }
}

$controller = BASE_DIR.'/sys/ctrl/'.$page.'.php';
if( file_exists( $controller ) ) {
    require $controller;    // ページコントローラーを実行
}
else {
    $controller =  BASE_DIR.'/sys/ctrl/_defult.php';
}

$viewFile   = $page.'.tpl';
if( file_exists( './templates/'.$viewFile ) ) {
    $pagefile = $viewFile;
} else {
    $pagefile = 'system/error_no_view.tpl';
    $GLOBALS['smarty']->assign( "viewFile" , $viewFile );
}

//$strout = $GLOBALS['smarty']->fetch($pagefile);
$strout = GetSmarty()->fetch($pagefile);
echo $strout;

