<?php

/* eingefuegt von den Timer Events beim Schalten von Stromheizung Switches */

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung");

IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
$moduleManager = new IPSModuleManager('Autosteuerung',$repository);

$installedModules = $moduleManager->GetInstalledModules();
if ( isset($installedModules["Sprachsteuerung"]) === true )
	{
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Sprachsteuerung\Sprachsteuerung_Library.class.php");
    IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");
	}

if ( isset($installedModules["IPSLight"]) === true )
	{
    //include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
    IPSUtils_Include ("IPSLight.inc.php","IPSLibrary::app::modules::IPSLight");
	$lightManager = new IPSLight_Manager();
    $baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
	}    

if ( isset($installedModules["Stromheizung"]) === true )
	{
    //include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
    IPSUtils_Include ("IPSHeat.inc.php","IPSLibrary::app::modules::Stromheizung");        
    $heatManager = new IPSHeat_Manager();
    $baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
    }

$switchCategoryId 	= IPS_GetObjectIDByIdent('Switches', $baseId);
$groupCategoryId   	= IPS_GetObjectIDByIdent('Groups', $baseId);
$prgCategoryId   	= IPS_GetObjectIDByIdent('Programs', $baseId);	

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$autosteuerung = new Autosteuerung();
$config = $autosteuerung->get_Configuration();

$ipsOps = new ipsOps();
$NachrichtenID = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
$NachrichtenScriptID = $ipsOps->searchIDbyName("Nachricht",$CategoryIdApp);

if ($NachrichtenID)           // nicht 0 oder false
    {
    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);
	$log_Autosteuerung=new Logging($config["LogDirectory"]."Log_Autosteuerung.csv",$NachrichtenInputID);
	}


?>