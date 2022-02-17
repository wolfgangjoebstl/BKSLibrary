<?
	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */    

	/**@defgroup Watchdog
	 *
	 * Script um automatisch beim Hoch- und Runterfahren irgendetwas ein und auszuschalten
	 * Hier wird die Installation des Webfronts, der Variablen und der Timer übernommen.
     * Wird immer nach einer Neuinstallation aufgerufen
     *
	 *
	 * @file          Watchdog_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	// Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Watchdog\Watchdog_Configuration.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Watchdog\Watchdog_Library.inc.php");

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Watchdog_Configuration.inc.php', 'IPSLibrary::config::modules::Watchdog');
    IPSUtils_Include ('Watchdog_Library.inc.php', 'IPSLibrary::app::modules::Watchdog');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Watchdog',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion :          ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version :            ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Watchdog');
	echo "\nWatchdog Version :        ".$ergebnis."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	//echo $inst_modules;
	
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


	/******************************************************
	 *
	 *			MODULE, Event Control
	 *
	 *************************************************************/

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

	$config = IPS_GetConfiguration($oid);
	echo "Konfiguration EventControl für Startup/Shutdown IPS vorher: \n";
	echo $config;
    echo "\n";

	/******************************************************
	 *
	 *			INIT, Autostart Configuration
	 *
	 *************************************************************/


	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdStartWD    = IPS_GetScriptIDByName('StartIPSWatchDog', $CategoryIdApp);
	$scriptIdStopWD     = IPS_GetScriptIDByName('StopIPSWatchDog', $CategoryIdApp);
	$scriptIdAliveWD    = IPS_GetScriptIDByName('IWDAliveFileSkript', $CategoryIdApp);
	$scriptIdShutdownWD    = IPS_GetScriptIDByName('Shutdown', $CategoryIdApp);

	echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
	echo "StartIPSWatchDog hat die ScriptID  ".$scriptIdStartWD." \n";
	echo "StopIPSWatchDog hat die ScriptID   ".$scriptIdStopWD." \n";
	echo "Shutdown hat die ScriptID          ".$scriptIdShutdownWD." \n";
	echo "Alive WatchDog hat die ScriptID    ".$scriptIdAliveWD." \n";
	
    /* verschiedene Hilfsklassen aktivieren */
    $dosOps = new dosOps();
    $timerOps = new timerOps();

	IPS_SetConfiguration($oid, '{"ShutdownScript":'.$scriptIdStopWD.',"StartupScript":'.$scriptIdStartWD.'}');
	IPS_ApplyChanges($oid);

	/*
	ShutdownScript 	integer 	0
	StartupScript 	integer 	0
	StatusEvents 	string 	[]
	WatchdogScript 	integer 	0
	*/

	$config = IPS_GetConfiguration($oid);
	echo "Konfiguration nachher: \n";
	echo $config;

    $ScriptCounterID = CreateVariableByName($CategoryIdData,"AutostartScriptCounter",1);
    $ProcessStartID  = CreateVariableByName($CategoryIdData,"ProcessStart",3);                         // welche Prozesse müssen noch gestartet werden, json encoded

	/******************************************************
	 *
	 *			INIT, Timer
	 *
	 *************************************************************/
	
	echo "\nTimer mit unterschiedlicher Startzeit programmieren :\n";

	$tim2ID = @IPS_GetEventIDByName("KeepAlive", $scriptIdAliveWD);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdAliveWD);
		IPS_SetName($tim2ID, "KeepAlive");
		IPS_SetEventCyclic($tim2ID,0,1,0,0,1,15);      /* alle 15 sec */
		IPS_SetEventActive($tim2ID,true);
		IPS_SetEventCyclicTimeFrom($tim2ID,0,0,12);  /* damit die Timer hintereinander ausgeführt werden */
		IPS_SetEventCyclicTimeTo($tim2ID, 0, 0, 0);
		echo "   Timer Event KeepAlive neu angelegt. Timer 15 sec ist bereits aktiviert.\n";
		}
	else
		{
		echo "   Timer Event KeepAlive bereits angelegt. Timer 15 sec ist aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim2ID,0,0,12);  /* damit die Timer hintereinander ausgeführt werden */
		IPS_SetEventCyclicTimeTo($tim2ID, 0, 0, 0);		
  		}

	$tim3ID = @IPS_GetEventIDByName("StartWD", $scriptIdStartWD);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdStartWD);
		IPS_SetName($tim3ID, "StartWD");
		IPS_SetEventCyclic($tim3ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim3ID,true);
		IPS_SetEventCyclicTimeFrom($tim3ID,0,3,0);  /* damit die Timer hintereinander ausgeführt werden, hier Minute 3 */
		IPS_SetEventCyclicTimeTo($tim3ID, 0, 0, 0);		
		echo "   Timer Event StartWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
		{
		echo "   Timer Event StartWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim3ID,0,3,0);  /* damit die Timer hintereinander ausgeführt werden */
		IPS_SetEventCyclicTimeTo($tim3ID, 0, 0, 0);	
  		}

	$tim4ID = @IPS_GetEventIDByName("StopWD", $scriptIdStopWD);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdStopWD);
		IPS_SetName($tim4ID, "StopWD");
		IPS_SetEventCyclic($tim4ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim4ID,true);
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);  /* damit die Timer hintereinander ausgeführt werden, hier Minute 4 */
		IPS_SetEventCyclicTimeTo($tim4ID, 0, 0, 0);
		echo "   Timer Event StopWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
		{
		echo "   Timer Event StopWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);  /* damit die Timer hintereinander ausgeführt werden */
		IPS_SetEventCyclicTimeTo($tim4ID, 0, 0, 0);		
  		}

	$tim5ID = @IPS_GetEventIDByName("ShutdownWD", $scriptIdShutdownWD);
	if ($tim5ID==false)
		{
		$tim5ID = IPS_CreateEvent(1);
		IPS_SetParent($tim5ID, $scriptIdShutdownWD);
		IPS_SetName($tim5ID, "ShutdownWD");
		IPS_SetEventCyclic($tim5ID,0,1,0,0,1,60);      /* alle 60 sec */
  		//IPS_SetEventActive($tim5ID,true);
		IPS_SetEventCyclicTimeFrom($tim5ID,0,5,0);  /* damit die Timer hintereinander ausgeführt werden, hier Minute 5 */
		IPS_SetEventCyclicTimeTo($tim5ID, 0, 0, 0);
		echo "   Timer Event ShutdownWD neu angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		}
	else
		{
		echo "   Timer Event ShutdownWD bereits angelegt. Timer 60 sec ist noch nicht aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim5ID,0,5,0);  /* damit die Timer hintereinander ausgeführt werden */
		IPS_SetEventCyclicTimeTo($tim5ID, 0, 0, 0);		
  		}

    $timerOps->CreateTimerHour("MaintenanceWD",4,12,$scriptIdStartWD);

	/******************************************************
	 *
	 *			INIT, Batchdateien, Scripts
	 *
	 *************************************************************/

    $watchDog = new watchDogAutoStart();
    $configWD = $watchDog->getConfiguration();
    print_r($configWD);
    if ( (isset($configWD["RemoteShutDown"])) && $configWD["RemoteShutDown"]) echo "Remote Shutdown wird unterstützt.\n";

	$verzeichnis=$configWD["WatchDogDirectory"];
	$unterverzeichnis="";

    echo "Write check username and active processes including java to script ".$verzeichnis.$unterverzeichnis."read_username.bat\n";
	$handle2=fopen($verzeichnis.$unterverzeichnis."read_username.bat","w");
    fwrite($handle2,'cd '.$verzeichnis.$unterverzeichnis."\r\n");
	fwrite($handle2,'echo %username% >>username.txt'."\r\n");
    fwrite($handle2,'wmic process list >>processlist.txt'."\r\n");                          // sehr aufwendige Darstellung der aktiven Prozesse
    fwrite($handle2,'tasklist >>tasklist.txt'."\r\n");
    fwrite($handle2,'jps >>jps.txt'."\r\n");  
	//fwrite($handle2,"pause\r\n");
	fclose($handle2);

    echo "Write Shutdown procedure to script ".$verzeichnis.$unterverzeichnis."self_shutdown.bat\n";
	$handle2=fopen($verzeichnis.$unterverzeichnis."self_shutdown.bat","w");
	fwrite($handle2,'net stop IPSServer'."\r\n");
	fwrite($handle2,'shutdown /s /t 150 /c "Es erfolgt ein Shutdown in 2 Minuten'."\r\n");
	fwrite($handle2,'pause'."\r\n");
	fwrite($handle2,'shutdown /a'."\r\n");
	fclose($handle2);

    echo "Write Self Restart procedure to script ".$verzeichnis.$unterverzeichnis."self_restart.bat\n";
	$handle2=fopen($verzeichnis.$unterverzeichnis."self_restart.bat","w");
	fwrite($handle2,'net stop IPSServer'."\r\n");
	fwrite($handle2,'shutdown /r /t 150 /c "Es erfolgt ein Restart in 2 Minuten'."\r\n");
	fwrite($handle2,'pause'."\r\n");
	fwrite($handle2,'shutdown /a'."\r\n");
	fclose($handle2);

    if (strtoupper($configWD["Software"]["Selenium"]["Autostart"])=="YES")
        {
        echo "Write Selenium Startup Script to ".$verzeichnis.$unterverzeichnis."start_Selenium.bat\n";
        $handle2=fopen($verzeichnis.$unterverzeichnis."start_Selenium.bat","w");
        fwrite($handle2,'# written '.date("H:m:i d.m.Y")."\r\n");
        fwrite($handle2,'cd '.$configWD["Software"]["Selenium"]["Directory"]."\r\n");
        fwrite($handle2,'java -jar '.$configWD["Software"]["Selenium"]["Execute"]."\r\n");
        /*  cd C:\Scripts\Selenium\ 
            java -jar selenium-server-standalone-3.141.59.jar
            pause       */
        fwrite($handle2,'pause'."\r\n");
        fclose($handle2);
        }

	if (strtoupper($configWD["Software"]["Firefox"]["Autostart"])=="YES" )
	    {
	    echo "Schreibe Batchfile zum automatischen Start von Firefox.\n";
        $handle2=fopen($verzeichnis.$unterverzeichnis."start_firefox.bat","w");        
        fwrite($handle2,'# written '.date("H:m:i d.m.Y")."\r\n");
        if (is_array($configWD["Software"]["Firefox"]["Url"]))
            {
            echo "   mehrere Urls sollen gestartet werden.\n";
            $command='"'.$configWD["Software"]["Firefox"]["Directory"].'firefox.exe" ';
            foreach ($configWD["Software"]["Firefox"]["Url"] as $url) $command.=' "'.$url.'" ';
            $command.="\r\n";
            fwrite($handle2,$command);
            echo "   Befehl ist jetzt : $command\n";
            }
		else fwrite($handle2,'"'.$configWD["Software"]["Firefox"]["Directory"].'firefox.exe" "'.$configWD["Software"]["Firefox"]["Url"].'"'."\r\n");
		fclose($handle2);
		}

	if (strtoupper($configWD["Software"]["Chrome"]["Autostart"])=="YES" )
	    {
	    echo "Schreibe Batchfile zum automatischen Start von Chrome.\n";
        $handle2=fopen($verzeichnis.$unterverzeichnis."start_chrome.bat","w");        
        fwrite($handle2,'# written '.date("H:m:i d.m.Y")."\r\n");
        if (is_array($configWD["Software"]["Chrome"]["Url"]))
            {
            echo "   mehrere Urls sollen gestartet werden.\n";
            $command='"'.$configWD["Software"]["Chrome"]["Directory"].'chrome.exe" ';
            foreach ($configWD["Software"]["Chrome"]["Url"] as $url) $command.=' "'.$url.'" ';
            $command.="\r\n";
            fwrite($handle2,$command);
            echo "   Befehl ist jetzt : $command\n";
            }
		else fwrite($handle2,'"'.$configWD["Software"]["Chrome"]["Directory"].'chrome.exe" "'.$configWD["Software"]["Chrome"]["Url"].'"'."\r\n");
		fclose($handle2);
		}

	if (strtoupper($configWD["Software"]["iTunes"]["Autostart"])=="YES" )
	   {
  	   echo "Schreibe Batchfile zum automatischen Kill von Java und Soap zur Steuerung von iTunes.\n";
		$handle2=fopen($verzeichnis.$unterverzeichnis."kill_java.bat","w");
		fwrite($handle2,'c:/Windows/System32/taskkill.exe /f /im java.exe'."\r\n");
		//fwrite($handle2,"pause\r\n");
		fclose($handle2);

		$handle2=fopen($verzeichnis.$unterverzeichnis."start_soap.bat","w");
		fwrite($handle2,'c:/scripts/nircmd.exe closeprocess java.exe'."\r\n");
		fwrite($handle2,'echo ------------------------------------------------ >>c:/scripts/log.txt'."\r\n");
		fwrite($handle2,'echo %date% %time% shutdown soap >>c:/scripts/log.txt'."\r\n");
		fwrite($handle2,'ping 127.0.0.1 -n 4'."\r\n");
		fwrite($handle2,'c:/Windows/System32/taskkill.exe /f /im java.exe'."\r\n");
		//fwrite($handle2,'c:/Windows/System32/Taskkill.exe /F /FI "IMAGENAME eq java.exe"'."\r\n");
		fwrite($handle2,'ping 127.0.0.1 -n 2'."\r\n");
		fwrite($handle2,'cd c:/scripts/'."\r\n");
		fwrite($handle2,'%windir%/system32/java -jar iTunesSoap_Beta1.jar '.$configWD["Software"]["iTunes"]["SoapIP"].' '.$configWD["Software"]["iTunes"]["SoapIP"].':8085'."\r\n");
		fwrite($handle2,'rem pause'."\r\n");
		fclose($handle2);

	   echo "Schreibe Batchfile zum automatischen Start von iTunes.\n";
		$handle2=fopen($verzeichnis.$unterverzeichnis."start_iTunes.bat","w");
  		fwrite($handle2,'"'.$configWD["Software"]["iTunes"]["Directory"].'iTunes.exe"'."\r\n");
		fclose($handle2);

	   echo "Schreibe Batchfile zum automatischen Stopp von iTunes.\n";
		$handle2=fopen($verzeichnis.$unterverzeichnis."kill_itunes.bat","w");
		fwrite($handle2,'c:/Windows/System32/taskkill.exe /im itunes.exe');
		fwrite($handle2,"\r\n");
		fwrite($handle2,'c:/Windows/System32/taskkill.exe /f /im java.exe');
		fwrite($handle2,"\r\n");
		//fwrite($handle2,"pause\r\n");
		fclose($handle2);
		
		}
		
	if (strtoupper($configWD["Software"]["VMware"]["Autostart"])=="YES" )
	   {
	   echo "Schreib Batchfile zum automatischen Start der VMware.\n";
		$handle2=fopen($verzeichnis.$unterverzeichnis."start_VMware.bat","w");
  		fwrite($handle2,'"'.$configWD["Software"]["VMware"]["Directory"].'vmplayer.exe" "'.$configWD["Software"]["VMware"]["DirFiles"].$configWD["Software"]["VMware"]["FileName"].'"'."\r\n");
		fclose($handle2);
		}

    echo "\n-----------------------------\nWatchdog Installation beendet.\n"


    /* Depreciated, kein IPSWatchdog mehr notwendig
	if (isset($configWD["Software"]["Watchdog"]["Directory"])==true )
	   {
	   echo "Schreib Batchfile zum automatischen Start des Watchdogs.\n";
		$handle2=fopen($verzeichnis.$unterverzeichnis."start_Watchdog.bat","w");
  		fwrite($handle2,'"'.$configWD["Software"]["Watchdog"]["Directory"].'IPSWatchDog.exe"'."\r\n");
		fclose($handle2);
		}   */

  		
?>