<?php
require_once("defines.php");
if (DEV_MOD)
{
	error_reporting(E_ALL);
	ini_set('display_errors',true);
}
require_once("config_root.php");
require_once("config_smarty.php");
require_once(CLASS_DIR . "/Controller.php");
require_once(CLASS_DIR . "/FrontController.php");

if(file_exists(CLASS_DIR . "/" . ucfirst($configReader->getConfig()->myLocalUse) . "Controller.php")){
    require_once(CLASS_DIR . "/VideoController.php");
}
?>
