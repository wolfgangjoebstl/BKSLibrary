<?

	/**@defgroup Sprachsteuerung
	 *
	 * Script um automatisch irgendetwas ein und auszuschalten
	 *
	 *
	 * @file          Sprachsteuerungung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Watchdog\Watchdog_Configuration.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Watchdog',$repository);
	}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Watchdog');
	echo "\nWatchdog Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	//Alle Modulnamen mit GUID ausgeben
	foreach(IPS_GetModuleList() as $guid)
		{
    	$module = IPS_GetModule($guid);
    	$pair[$module['ModuleName']] = $guid;
		}
	ksort($pair);
	foreach($pair as $key=>$guid)
		{
    	//echo $key." = ".$guid."\n";
		}

$name=IPS_GetModule("{ED573B53-8991-4866-B28C-CBE44C59A2DA}");
$oid=IPS_GetInstanceListByModuleID("{ED573B53-8991-4866-B28C-CBE44C59A2DA}")["0"];
echo "Wir interessieren uns für Modul : ".$name['ModuleName']." mit OID: ".$oid." und Name : ".IPS_GetName($oid)."\n";

	/******************************************************
	 *
	 *			INIT, Autostart Configuration
	 *
	 *************************************************************/

	$config = IPS_GetConfiguration($oid);
	echo "Konfiguration vorher: \n";
	echo $config;

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdStartWD    = IPS_GetScriptIDByName('StartIPSWatchDog', $CategoryIdApp);
	$scriptIdStopWD     = IPS_GetScriptIDByName('StopIPSWatchDog', $CategoryIdApp);
	$scriptIdAliveWD    = IPS_GetScriptIDByName('IWDAliveFileSkript', $CategoryIdApp);
	$scriptIdShutdownWD    = IPS_GetScriptIDByName('Shutdown', $CategoryIdApp);

	echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
	echo "StartIPSWatchDog hat die ScriptID ".$scriptIdStartWD." \n";
	echo "StopIPSWatchDog hat die ScriptID ".$scriptIdStopWD." \n";
	echo "Shutdown hat die ScriptID ".$scriptIdShutdownWD." \n";
	echo "Alive WatchDog hat die ScriptID ".$scriptIdAliveWD." \n";
	
	IPS_SetConfiguration($oid, '{"ShutdownScript":'.$scriptIdStopWD.',"StartupScript":'.$scriptIdStartWD.'}');
	IPS_ApplyChanges($oid);

	/*
	ShutdownScript 	integer 	0
	StartupScript 	integer 	0
	StatusEvents 	string 	[]
	WatchdogScript 	integer 	0
	*/

	$config = IPS_GetConfiguration($oid);
	echo "Konfiguration nachhher: \n";
	echo $config;

	/******************************************************
	 *
	 *			INIT, Timer
	 *
	 *************************************************************/
	
	echo "\nTimer programmieren :\n";

	$tim2ID = @IPS_GetEventIDByName("KeepAlive", $scriptIdAliveWD);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdAliveWD);
		IPS_SetName($tim2ID, "KeepAlive");
		IPS_SetEventCyclic($tim2ID,0,1,0,0,1,15);      /* alle 15 sec */
  		IPS_SetEventActive($tim2ID,true);
		IPS_SetEventCyclicTimeBounds($tim2ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Timer Event KeepAlive neu angelegt. Timer 15 sec ist bereits aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event KeepAlive bereits angelegt. Timer 15 sec ist aktiviert.\n";
  		}

	$tim3ID = @IPS_GetEventIDByName("StartWD", $scriptIdStartWD);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdStartWD);
		IPS_SetName($tim3ID, "StartWD");
		IPS_SetEventCyclic($tim3ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim3ID,true);
		IPS_SetEventCyclicTimeBounds($tim3ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Timer Event StartWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event StartWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
  		}

	$tim4ID = @IPS_GetEventIDByName("StopWD", $scriptIdStopWD);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdStopWD);
		IPS_SetName($tim4ID, "StopWD");
		IPS_SetEventCyclic($tim4ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim4ID,true);
		IPS_SetEventCyclicTimeBounds($tim4ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Timer Event StopWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event StopWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
  		}

	$tim5ID = @IPS_GetEventIDByName("ShutdownWD", $scriptIdShutdownWD);
	if ($tim5ID==false)
		{
		$tim5ID = IPS_CreateEvent(1);
		IPS_SetParent($tim5ID, $scriptIdShutdownWD);
		IPS_SetName($tim5ID, "ShutdownWD");
		IPS_SetEventCyclic($tim5ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim5ID,true);
		IPS_SetEventCyclicTimeBounds($tim5ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Timer Event ShutdownWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event ShutdownWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
  		}
  		
?>