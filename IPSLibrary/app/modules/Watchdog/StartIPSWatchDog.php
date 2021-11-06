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

	/** 
	 * Script wird aus verschiedenen Gründen aufgerufen
     *     beim Startup des Systems, es werden verschiedene Programme mit einer Statemaschine gestartet
     *     durch den Timer der angeführten Statemaschine
     *     mittels Runscript
     *     oder Execute Script
     *
     * Der Aufruf erfolgt alle 60 Sekunden, die Scriptlaufzeit darf diese Zeiten nicht überschreiten
     *
     * Einmal gestartete Prozesse werden nicht überprüft ob sie noch laufen. Wichtig für Selenium.
	 *
     *
     * bevor Symcon einen eigenen Watchdog unterhält wurde ein externer Watchdog bedient. Dazu musste in regelmaessigen Abständen ein File beschrieben werden.
     *
	 *
	 * @file          StartIPSWatchDog.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 * Version 2.50.1, 11.03.2012<br/>
	 *
	 */
	 
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Watchdog\Watchdog_Configuration.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Watchdog\Watchdog_Library.inc.php");    

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");
    IPSUtils_Include ("Watchdog_Library.inc.php","IPSLibrary::app::modules::Watchdog");

	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    // max. Scriptlaufzeit definieren
    ini_set('max_execution_time', 100);
    $startexec=microtime(true);    
    echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 100 Sek \n";

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdStartWD    = IPS_GetScriptIDByName('StartIPSWatchDog', $CategoryIdApp);
	$scriptIdStopWD     = IPS_GetScriptIDByName('StopIPSWatchDog', $CategoryIdApp);
	$scriptIdAliveWD    = IPS_GetScriptIDByName('IWDAliveFileSkript', $CategoryIdApp);
	echo "Die Scripts sind auf              : ".$CategoryIdApp."\n";
	echo "StartIPSWatchDog hat die ScriptID : ".$scriptIdStartWD." \n";
	echo "StopIPSWatchDog hat die ScriptID  : ".$scriptIdStopWD." \n";
	echo "Alive WatchDog hat die ScriptID   : ".$scriptIdAliveWD." \n";

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

	/********************************************************************
	 *
	 * Init
	 *
	 **********************************************************************/

    $watchDog = new watchDogAutoStart();
    $config = $watchDog->getConfiguration();
	//$config=Watchdog_Configuration();
	//print_r($config);
	
	$tim2ID = @IPS_GetEventIDByName("KeepAlive", $scriptIdAliveWD);
	$tim3ID = @IPS_GetEventIDByName("StartWD", $scriptIdStartWD);
	IPS_SetEventCyclicTimeBounds($tim3ID,time()+60,0);

  	$tim6ID = @IPS_GetEventIDByName("MaintenanceWD", $scriptIdStartWD);         // einmal am Tag 4:12

	$ScriptCounterID = IPS_GetObjectIDByName("AutostartScriptCounter", $CategoryIdData);
    $ProcessStartID  = IPS_GetObjectIDByName("ProcessStart", $CategoryIdData); 
    echo "Watchdog Data Variables : $ScriptCounterID und $ProcessStartID.\n";

	$verzeichnis=$config["WatchDogDirectory"];
	$unterverzeichnis="";
	
    $processStart = json_decode(GetValue($ProcessStartID),true);            // true für Ausgabe als Array
    echo "Status aus register über Programme die gestartet (wenn On) werden muessen:\n";
    print_r($processStart);

	/********************************************************************
	 *
	 * Execute
	 *
	 **********************************************************************/

	if ($_IPS['SENDER']=="RunScript")
		{
		echo "Von einem anderen Script aus gestartet, Autostart Prozess beginnen.\n";
		IPSLogger_Dbg(__file__, "Autostart: Script extern aufgerufen *****************  ".json_encode($_IPS));

            //$command=["Selenium"=>"On"];
		    //$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');           // App Kategorie
		    //$StartIpsWatchdogScriptId   = @IPS_GetScriptIDByName('StartIpsWatchdog', $CategoryIdApp);
            //$request["REQUEST"]=json_encode($command);
            //IPS_RunScriptEx($StartIpsWatchdogScriptId, $request);
  		    //IPSLogger_Inf(__file__,"Extern VoiceControl Request (RunScript) mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus"));	                
			//$nachrichten->LogNachrichten("Extern Alexa ".$_IPS['SENDER']." empfängt : ".$_IPS['VARIABLE']."  ".$_IPS['REQUEST']."  ".($_IPS['VALUE']?"Ein":"Aus")." .");

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
		
		$log_Watchdog->LogMessage(    'Lokaler Server wird durch Aufruf per externem Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_Watchdog->LogNachrichten('Lokaler Server wird durch Aufruf per externem Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_OperationCenter->LogMessage(    'Lokaler Server wird durch Aufruf per externem Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
		$log_OperationCenter->LogNachrichten('Lokaler Server wird durch Aufruf per externem Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
		}

	if ($_IPS['SENDER']=="Execute")
		{
        echo "===============================================================\n";
		echo "Von der Console aus gestartet, Autostart Prozess beginnen.\n";
        echo "\n";
        echo "Konfiguration ausgeben:\n";
        print_r($config);
                
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
        echo "Ermittlung aktiver Prozese abgeschlossen. Die folgenden Programme muessen gestartet (wenn On) werden:\n";
        print_r($processStart);
        SetValue($ProcessStartID,json_encode($processStart));
        echo "Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

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
            
        if (false)
            {
            $status=tts_play(1,"IP Symcon Visualisierung neu starten",'',2);
            if ($status==false)
                {
                echo "Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration.\n";
                $log_OperationCenter->LogMessage(    'Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
                $log_OperationCenter->LogNachrichten('Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
                }

            
            IPS_SetEventActive($tim3ID,true);
            SetValue($ScriptCounterID,1);
            IPSLogger_Dbg(__file__, "Autostart: Script direkt aufgerufen ***********************************************");

            $log_Watchdog->LogMessage(    'Lokaler Server wird durch Aufruf per Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
            $log_Watchdog->LogNachrichten('Lokaler Server wird durch Aufruf per Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
            $log_OperationCenter->LogMessage(    'Lokaler Server wird durch Aufruf per Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
            $log_OperationCenter->LogNachrichten('Lokaler Server wird durch Aufruf per Script hochgefahren, Aufruf der Routine StartIPSWatchdog');
            if (isset ($installedModules["OperationCenter"]))
                {
                $subnet='10.255.255.255';
                $OperationCenter=new OperationCenter($subnet);
                echo "SystemInfo aus dem OperationCenter updaten.\n";
                $OperationCenter->SystemInfo();
                }
            }
		}

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

	if ($_IPS['SENDER']=="TimerEvent")
		{
		switch ($_IPS['EVENT'])
			{
            case $tim6ID:
				IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." einmal am Tag durchführen.");
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
                if ($processStart["selenium"] == "On")
                    {
                    echo "selenium.exe wird neu gestartet.\n";
                    IPSLogger_Dbg(__file__, "Autostart: Selenium wird gestartet");
                    writeLogEvent("Autostart (Watchdog)".$config["Software"]["Selenium"]["Directory"].$config["Software"]["Selenium"]["Execute"]);
                    IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
                    $processStart["selenium"] == "Off";
                    SetValue($ProcessStartID,json_encode($processStart));
                    }
                else
                    {
                    echo "Selenium.exe muss daher nicht erneut gestartet werden.\n";
                    }
                break;
			case $tim3ID:
				$counter=GetValue($ScriptCounterID);
				IPSLogger_Inf(__file__, "TimerEvent from :".$_IPS['EVENT']." Autostart durchführen. ScriptcountID: $counter. Process ".json_encode($processStart));
				switch ($counter)
					{
					case 7:
						SetValue($ScriptCounterID,0);
						IPS_SetEventActive($tim3ID,false);
						IPSLogger_Inf(__file__, "Autostart: Prozess abgeschlossen");
						writeLogEvent("Autostart (Ende)");
						break;
					case 6:
						if (isset ($installedModules["OperationCenter"]))
							{
							$subnet='10.255.255.255';
							$OperationCenter=new OperationCenter($subnet);
							//$OperationCenter->SystemInfo();
        					IPSLogger_Inf(__file__, "Autostart: OperationCenter::SystemInfo aufgerufen.");                            
							}
						SetValue($ScriptCounterID,$counter+1);
						break;
					case 5:
						/* ftp Server wird nun automatisch mit der IS Umgebung von Win 10 gestartet, keine Fremd-Software mehr erforderlich */
						//IPS_ExecuteEx("c:/Users/wolfg_000/Downloads/Programme/47 ftp server/ftpserver31lite/ftpserver.exe","", true, false,1);
						//writeLogEvent("Autostart (ftpserverlite)");
						if ($processStart["Firefox"] == "On")
							{
                            if (is_array($config["Software"]["Firefox"]["Url"]))
                                {
                                $logtext="Autostart (Firefox) ".$config["Software"]["Firefox"]["Directory"]."firefox.exe ";    
                                foreach ($config["Software"]["Firefox"]["Url"] as $address) $logtext .= $address." ";
                                }
                            else $logtext="Autostart (Firefox) ".$config["Software"]["Firefox"]["Directory"]."firefox.exe ".$config["Software"]["Firefox"]["Url"];
        					IPSLogger_Inf(__file__, "Autostart: $logtext.");                            
							writeLogEvent($logtext);
							IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_firefox.bat","", true, false,-1);
							}
						if ($processStart["Chrome"] == "On")
							{
                            if (is_array($config["Software"]["Chrome"]["Url"]))
                                {
                                $logtext="Autostart (Chrome) ".$config["Software"]["Chrome"]["Directory"]."chrome.exe ";    
                                foreach ($config["Software"]["Chrome"]["Url"] as $address) $logtext .= $address." ";
                                }
                            else $logtext="Autostart (Chrome) ".$config["Software"]["Chrome"]["Directory"]."chrome.exe ".$config["Software"]["Chrome"]["Url"];
        					IPSLogger_Inf(__file__, "Autostart: $logtext.");                            
							writeLogEvent($logtext);
							IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_chrome.bat","", true, false,-1);
							}                            
						SetValue($ScriptCounterID,$counter+1);
						break;
					case 4:
						//if (GetValueBoolean(50871))
						if ($processStart["iTunes"] == "On")
						    {
							echo "SOAP Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
					   	    /* Soap ausschalten */
							//IPS_ExecuteEx("c:/scripts/process_kill_java.bat","", true, true,-1);  // Warten auf true gesetzt, das ist essentiell
							IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_soap.bat","",true,false,-1);  // kill wird schon von startsoap mitgemacht
        					IPSLogger_Inf(__file__, "Autostart: (SOAP)).");                            
							writeLogEvent("Autostart (SOAP)");
							}
						SetValue($ScriptCounterID,$counter+1);
			      	break;
					case 3:
						//if (GetValueBoolean(46719))
						if ($processStart["iTunes"] == "On")
					   	    {
							echo "Itunes Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
				   		    /* iTunes ausschalten */
							IPS_ExecuteEx($verzeichnis.$unterverzeichnis."kill_itunes.bat","", true, true,-1); // Warten auf true gesetzt, das ist essentiell
							IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_iTunes.bat","",true,false,-1);  // C:\Program Files\iTunes
                            IPSLogger_Inf(__file__, "Autostart: (iTunes) ".$config["Software"]["iTunes"]["Directory"]."iTunes.exe");
							writeLogEvent("Autostart (iTunes) ".$config["Software"]["iTunes"]["Directory"]."iTunes.exe");
							}
						SetValue($ScriptCounterID,$counter+1);
			      	break;
				   case 2:
						if ($processStart["vmplayer"] == "On")
						   {
							writeLogEvent("Autostart (VMPlayer) ".'\"'.$config["Software"]["VMware"]["Directory"].'vmplayer.exe\" \"'.$config["Software"]["VMware"]["DirFiles"].$config["Software"]["VMware"]["FileName"].'\"');
							IPSLogger_Inf(__file__, "Autostart: VMWare Player wird gestartet");
							IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_VMWare.bat","",true,false,-1);
							}
						else
						   {
						   echo "vmplayer.exe muss daher nicht erneut gestartet werden.\n";
						   }
						SetValue($ScriptCounterID,$counter+1);
						break;
					case 1:
						if ($processStart["selenium"] == "On")
							{
							echo "selenium.exe wird neu gestartet.\n";
							IPSLogger_Inf(__file__, "Autostart: Selenium wird gestartet");
							writeLogEvent("Autostart (Watchdog)".$config["Software"]["Selenium"]["Directory"].$config["Software"]["Selenium"]["Execute"]);
							IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
							}
						else
							{
							echo "Selenium.exe muss daher nicht erneut gestartet werden.\n";
							}
					 	// Parent-ID der Kategorie ermitteln
						$parentID = IPS_GetObject($IPS_SELF);
						$parentID = $parentID['ParentID'];

						// ID der Skripte ermitteln
						$IWDAliveFileSkriptScID = IPS_GetScriptIDByName("IWDAliveFileSkript", $parentID);
						$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

						IPS_RunScript($IWDAliveFileSkriptScID);
					 	IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'start'));
						SetValue($ScriptCounterID,$counter+1);
						break;
				   case 0:
					default:
					   break;
				   }
				break;

			default:
				IPSLogger_Inf(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
			   break;
			}
		}

	/* bei diesen Programmen gab es manchmal Probleme das die User Session nicht gefunden werden kann. Daher kommen
	 * sie nun am Schluss und ein Fehler stoppt nicht den Timer3 Aufruf, Setzen des Aktivierungs Bits
	 

	IPS_ExecuteEx($verzeichnis.$unterverzeichnis."read_username.bat","", true, true,-1);  // warten dass fertig, sonst wird alter Wert ausgelesen 
	$handle3=fopen($verzeichnis.$unterverzeichnis."username.txt","r");
	echo "Username von dem aus IP Symcon zugreift ist : ".fgets($handle3);
	fclose($handle3);

	$result=IPS_EXECUTE("c:/windows/system32/tasklist.exe","/APPS", true, true);
	echo $result;       */



?>