<?php

/***********************************************************************
 *
 * StoppSymcon
 *
 *
 *
 ***********************************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    
    IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ("DeviceManagement_Library.class.php","IPSLibrary::app::modules::OperationCenter");  

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $dosOps = new dosOps();
    $sysOps = new sysOps();

    $systemDir     = $dosOps->getWorkDirectory();       // systemdir festlegen, typisch auf Windows C:/Scripts/

    $OperationConfig=new OperationCenterConfig();

    $configSetup=$OperationConfig->setSetup();

    /* Logging konfigurieren und festlegen */

	echo "\nStartSymcon: Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging($configSetup["LogDirectory"]."Log_Watchdog.csv",$input);    

	$log_Watchdog->LogMessage(    'Lokaler Server wird im IPS Startup Prozess gestoppt, Aufruf der Routine StoppSymcon');
	$log_Watchdog->LogNachrichten('Lokaler Server wird im IPS Startup Prozess gestoppt, Aufruf der Routine StoppSymcon');

	tts_play(1,"Gute Nacht, Symcon stoppt",'',2);
	


?>