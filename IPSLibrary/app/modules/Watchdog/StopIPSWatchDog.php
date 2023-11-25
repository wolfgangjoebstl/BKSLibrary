<?php

    //Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Watchdog\Watchdog_Library.inc.php"); 

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("Watchdog_Library.inc.php","IPSLibrary::app::modules::Watchdog");
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $dosOps = new dosOps();
    $sysOps = new sysOps();

    $systemDir     = $dosOps->getWorkDirectory();       // systemdir festlegen, typisch auf Windows C:/Scripts/

    /* Logging konfigurieren und festlegen */

	echo "\nStartIPSWatchdog: Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging($systemDir."Log_Watchdog.csv",$input);

    /* Audioausgabe festlegen und konfigurieren */

	if (isset ($installedModules["OperationCenter"]))
		{
		IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
		echo "Logspeicher für OperationCenter mitnutzen.\n";
		$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
		$CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');
		$categoryId_NachrichtenOC    = CreateCategory('Nachrichtenverlauf',   $CategoryIdDataOC, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenOC, 0, "",null,null,""  );
		$log_OperationCenter=new Logging($systemDir."Log_OperationCenter.csv",$input);
		}
	else
		{
		if (isset ($installedModules["Sprachsteuerung"]))
			{
			IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");
			IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");
			}
		else
			{
			function tts_play() {};
			}		
		}	


 	// Parent-ID der Kategorie ermitteln
	$parentID = IPS_GetObject($IPS_SELF);
	$parentID = $parentID['ParentID'];

	// ID der Skripte ermitteln
	$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

 	IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'stop'));

	tts_play(1,"Gute Nacht",'',2);
	
?>