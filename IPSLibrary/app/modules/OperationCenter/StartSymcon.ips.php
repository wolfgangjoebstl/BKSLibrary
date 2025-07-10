<?php

/***********************************************************************
 *
 * StartSymcon
 *
 *
 *
 ***********************************************************/

//Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("Autostart_Library.class.php","IPSLibrary::app::modules::OperationCenter");

/******************************************************

				INIT

*************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(900);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

        echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
        $moduleManager = new IPSModuleManager('OperationCenter',$repository);
        }

    $installedModules = $moduleManager->GetInstalledModules();
    $inst_modules="\nInstallierte Module:\n";
    foreach ($installedModules as $name=>$modules)
        {
        $inst_modules.=str_pad($name,30)." ".$modules."\n";
        }
    echo $inst_modules."\n\n";

    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $scriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
    echo "Category App ID   : ".$CategoryIdApp."\n";
    echo "Category Script ID: ".$scriptId."\n\n";
	$scriptIdStartSymcon    = IPS_GetScriptIDByName('StartSymcon', $CategoryIdApp);
	$scriptIdStoppSymcon    = IPS_GetScriptIDByName('StoppSymcon', $CategoryIdApp);
	echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
	echo "StartSymcon hat die ScriptID       ".$scriptIdStartSymcon." \n";
	echo "StoppSymcon hat die ScriptID       ".$scriptIdStoppSymcon." \n";

    echo "Folgende Module werden von OperationCenter bearbeitet:\n";
    if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
    if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n"; }
    if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
    if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
    echo "\n";


    $OperationConfig=new OperationCenterConfig();
    $configSetup=$OperationConfig->setSetup();

    $sysOps = new sysOps();

    $systemDir     = $dosOps->getWorkDirectory();       // systemdir festlegen, typisch auf Windows C:/Scripts/

    /* Logging konfigurieren und festlegen */

	echo "\nStartSymcon: OperationCenter Logspeicher für Start und Stopp vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging($systemDir."Log_Autostart.csv",$input);


	if ($_IPS['SENDER']=="Startup")
		{
		echo "IPS Server fährt hoch, im Startup gestartet, Autostart Prozess beginnen.\n";
		IPSLogger_Dbg(__file__, "Autostart: Script durch IPS Startup prozess aufgerufen *****************  ");

        /********************************************************************
        *
        * feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden
        * die laufenden Files mit einer User Session definieren um auch die Java basierte Selenium Applikation zu erfassen
        * andernfalls könnte $watchDog->checkAutostartProgram auch ohne einer Prozessliste aufgerufen werden
        *
        **********************************************************************/

        echo "\n";
        $processes    = $watchDog->getActiveProcesses();
        $processStart = $watchDog->checkAutostartProgram($processes);
        echo "Die folgenden Programme muessen gestartet (wenn On) werden:\n";
        print_r($processStart);
        SetValue($ProcessStartID,json_encode($processStart));
        echo "Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

		$status=tts_play(1,"IP Symcon Visualisierung neu starten",'',2);
		if ($status==false)
			{
			$log_OperationCenter->LogMessage(    'Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
			$log_OperationCenter->LogNachrichten('Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
			}

		IPS_SetEventActive($tim3ID,true);
		SetValue($ScriptCounterID,1);

		$log_Watchdog->LogMessage(    'Lokaler Server wird im IPS Startup Prozess hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_Watchdog->LogNachrichten('Lokaler Server wird im IPS Startup Prozess hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_OperationCenter->LogMessage(    'Lokaler Server wird im Startup Prozess hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_OperationCenter->LogNachrichten('Lokaler Server wird im Startup Prozess hochgefahren, Aufruf der Routine StartIPSWatchdog');
		}


	if ($_IPS['SENDER']=="Execute")
		{
        echo "===============================================================\n";
		echo "Von der Console aus gestartet, Autostart Prozess beginnen.\n";
        echo "\n";
        echo "Konfiguration ausgeben:\n";
        print_r($configSetup);
                
        $autostart = new AutostartHandler();

        /********************************************************************
        *
        * feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden
        * die laufenden Files mit einer User Session definieren um auch die Java basierte Selenium Applikation zu erfassen
        * andernfalls könnte $watchDog->checkAutostartProgram auch ohne einer Prozessliste aufgerufen werden
        *
        **********************************************************************/

    if (false)
        {
        echo "\n";
        $processes    = $watchDog->getActiveProcesses();
        $processStart = $watchDog->checkAutostartProgram($processes);
        echo "Ermittlung aktiver Prozese abgeschlossen. Die folgenden Programme muessen gestartet (wenn On) werden:\n";
        print_r($processStart);
        SetValue($ProcessStartID,json_encode($processStart));
        echo "Abgelaufene Zeit nach Start Execute: ".exectime($startexec)." Sek \n";

        /* mehrere Optionen, entweder den ganzen IP Symcon Server hochfahren oder einzelne Prozesse durchstarten 
         * seit es keinen externen Watchdog mehr gibt hat das Hochstarten nicht mehr soviel Bedeutung
         */

        if (true)
            {
            if ($processStart["selenium"] == "On")
                {
                echo "selenium.exe wird neu gestartet.\n";
                IPSLogger_Dbg(__file__, "Autostart: Selenium wird gestartet");
                writeLogEvent("Autostart (Watchdog)".$config["Software"]["Selenium"]["Directory"].$config["Software"]["Selenium"]["Execute"]);
                $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);                
                //IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
                $processStart["selenium"] == "Off";
                SetValue($ProcessStartID,json_encode($processStart));
                }
            else
                {
                echo "Selenium.exe muss nicht neu gestartet werden.\n";
                }
            }
           
		}
    }

?>