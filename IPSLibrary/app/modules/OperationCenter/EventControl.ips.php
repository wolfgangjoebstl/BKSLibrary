<?php

/***********************************************************************
 *
 * Event Control
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

    $OperationConfig=new OperationCenterConfig();

    $configSetup=$OperationConfig->setSetup();

    /* Logging konfigurieren und festlegen */

	echo "\nStartSymcon: Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging($configSetup["LogDirectory"]."Log_Watchdog.csv",$input);    
    
    $instance = $_IPS['INSTANCE'];	        //InstanceID for state change
    $instanceName = IPS_GetName($instance);
    $status = $_IPS['STATUS'];              //	State of the instance. A list of possible values is found here: IPS_GetInstance
    $statusText = $_IPS['STATUSTEXT'];
	$log_Watchdog->LogMessage("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");
	$log_Watchdog->LogNachrichten("$instanceName ($instance) has new Status $status : $statustext , info from EventControl");

	


?>