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
    $sysOps = new sysOps();

    $dosOps->setMaxScriptTime(900);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('OperationCenter',$repository);
        }

    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $scriptIdOperationCenter    = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
	$scriptIdStartSymcon    = IPS_GetScriptIDByName('StartSymcon', $CategoryIdApp);
	$scriptIdStoppSymcon    = IPS_GetScriptIDByName('StoppSymcon', $CategoryIdApp);

    $autostart = new AutostartHandler();
    //$config = $autostart->getSetup();
    $configWatchdog = $autostart->getConfiguration();

    $verzeichnis=$configWatchdog["WatchDogDirectory"];
    $unterverzeichnis="";

    $categoryId_WatchdogFunction	= CreateCategory('Watchdog',   $CategoryIdData, 600);
    $ScriptCounterID = CreateVariableByName($categoryId_WatchdogFunction,"AutostartScriptCounter",1);
    $ProcessStartID  = CreateVariableByName($categoryId_WatchdogFunction,"ProcessStart",3);
    
    /* Logging konfigurieren und festlegen */

	//echo "\nStartSymcon: OperationCenter Logspeicher für Start und Stopp vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
    $log_Watchdog=new Logging($configWatchdog["LogDirectory"]."Log_Watchdog.csv",$input);   

	$tim3ID = @IPS_GetEventIDByName("AutoStart", $scriptIdStartSymcon);
  	$tim6ID = @IPS_GetEventIDByName("AutoStartPerDay", $scriptIdStartSymcon);         // einmal am Tag 4:12     

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
        $processes    = $autostart->getActiveProcesses();
        $processStart = $autostart->checkAutostartProgram($processes);
        echo "Die folgenden Programme muessen gestartet (wenn On) werden:\n";
        print_r($processStart);
        SetValue($ProcessStartID,json_encode($processStart));
        echo "Abgelaufene Zeit : ".exectime($startexec)." Sek \n";

		$status=tts_play(1,"IP Symcon Visualisierung neu starten",'',2);
		if ($status==false)
			{
			$log_Watchdog->LogMessage(    'Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
			$log_Watchdog->LogNachrichten('Audio Ausgabe nicht möglich. Überprüfen sie die Instanzen in der Sprachsteuerung auf richtige Funktion/Konfiguration');
			}

		$log_Watchdog->LogMessage(    'Lokaler Server wird im IPS Startup Prozess hochgefahren, Aufruf der Routine StartSymcon');
		$log_Watchdog->LogNachrichten('Lokaler Server wird im IPS Startup Prozess hochgefahren, Aufruf der Routine StartSymcon');

        SetValue($ScriptCounterID,1);
        $zeit=time()+60;
        $stunde=intval(date("H",$zeit),10);             // integer dezimal enkodiern
        $minute=intval(date("i",$zeit),10);    
        IPS_SetEventCyclicTimeFrom($tim3ID,$stunde,$minute,0);  // (integer $EreignisID, integer $Stunde, integer $Minute, integer $Sekunde)        
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
                    writeLogEvent("Autostart (Watchdog)".$configWatchdog["Software"]["Selenium"]["Directory"].$configWatchdog["Software"]["Selenium"]["Execute"]);
                    //IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
                    $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_Selenium.bat","", true, true,-1);
                    $processStart["selenium"] == "Off";
                    SetValue($ProcessStartID,json_encode($processStart));
                    }
                else
                    {
                    echo "Selenium.exe muss nicht erneut gestartet werden.\n";
                    }
                break;
			case $tim3ID:
                $processStart=json_decode(GetValue($ProcessStartID),true);            
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
                            if (is_array($configWatchdog["Software"]["Firefox"]["Url"]))
                                {
                                $logtext="Autostart (Firefox) ".$configWatchdog["Software"]["Firefox"]["Directory"]."firefox.exe ";    
                                foreach ($configWatchdog["Software"]["Firefox"]["Url"] as $address) $logtext .= $address." ";
                                }
                            else $logtext="Autostart (Firefox) ".$configWatchdog["Software"]["Firefox"]["Directory"]."firefox.exe ".$configWatchdog["Software"]["Firefox"]["Url"];
        					IPSLogger_Inf(__file__, "Autostart: $logtext.");                            
							writeLogEvent($logtext);
							//IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_firefox.bat","", true, false,-1);
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_firefox.bat","",true,false,-1);                
							}
						if ($processStart["Chrome"] == "On")
							{
                            if (is_array($configWatchdog["Software"]["Chrome"]["Url"]))
                                {
                                $logtext="Autostart (Chrome) ".$configWatchdog["Software"]["Chrome"]["Directory"]."chrome.exe ";    
                                foreach ($configWatchdog["Software"]["Chrome"]["Url"] as $address) $logtext .= $address." ";
                                }
                            else $logtext="Autostart (Chrome) ".$configWatchdog["Software"]["Chrome"]["Directory"]."chrome.exe ".$configWatchdog["Software"]["Chrome"]["Url"];
        					IPSLogger_Inf(__file__, "Autostart: $logtext.");                            
							writeLogEvent($logtext);
							//IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_chrome.bat","", true, false,-1);
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_chrome.bat","",true,false,-1);                
							}                            
						SetValue($ScriptCounterID,$counter+1);
						break;
					case 4:
						//if (GetValueBoolean(50871))
						if ($processStart["iTunes"] == "On")
						    {
							echo "SOAP Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
					   	    /* Soap ausschalten */
							//IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_soap.bat","",true,false,-1);  // kill wird schon von startsoap mitgemacht
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_soap.bat","",true,false,-1);                
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
							//IPS_ExecuteEx($verzeichnis.$unterverzeichnis."kill_itunes.bat","", true, true,-1); // Warten auf true gesetzt, das ist essentiell
							//IPS_ExecuteEx($verzeichnis.$unterverzeichnis."start_iTunes.bat","",true,false,-1);  // C:\Program Files\iTunes
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."kill_itunes.bat","",true,false,-1);                
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_iTunes.bat","",true,false,-1);                
                            IPSLogger_Inf(__file__, "Autostart: (iTunes) ".$configWatchdog["Software"]["iTunes"]["Directory"]."iTunes.exe");
							writeLogEvent("Autostart (iTunes) ".$configWatchdog["Software"]["iTunes"]["Directory"]."iTunes.exe");
							}
						SetValue($ScriptCounterID,$counter+1);
			      	break;
				   case 2:
						if ($processStart["vmplayer"] == "On")
						   {
							writeLogEvent("Autostart VMWare ".'\"'.$configWatchdog["Software"]["VMware"]["Directory"].'vmware.exe\" \"'.$configWatchdog["Software"]["VMware"]["DirFiles"].$configWatchdog["Software"]["VMware"]["FileName"].'\"');
							IPSLogger_Inf(__file__, "Autostart: VMWare wird gestartet");
							//IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_VMWare.bat","",true,false,-1);
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_VMWare.bat","",true,false,-1);                
							}
						else
						   {
						   echo "vmware.exe muss nicht erneut gestartet werden.\n";
						   }
						SetValue($ScriptCounterID,$counter+1);
						break;
					case 1:
						if ($processStart["selenium"] == "On")
							{
							echo "selenium.exe wird neu gestartet.\n";
							IPSLogger_Inf(__file__, "Autostart: Selenium wird gestartet");
							writeLogEvent("Autostart (Watchdog)".$configWatchdog["Software"]["Selenium"]["Directory"].$configWatchdog["Software"]["Selenium"]["Execute"]);
							//IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
                            $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);                
							}
						else
							{
							echo "Selenium.exe muss nicht erneut gestartet werden.\n";
							}
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


	if ($_IPS['SENDER']=="Execute")
		{
        echo "===============================================================\n";
		echo "Von der Console aus gestartet, Autostart Prozess beginnen.\n";
        echo "\n";

        echo "Category App ID   : ".$CategoryIdApp."\n";


        echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
        echo "Script ID OperationCenter          ".$scriptIdOperationCenter."\n\n";        
        echo "StartSymcon hat die ScriptID       ".$scriptIdStartSymcon." \n";
        echo "StoppSymcon hat die ScriptID       ".$scriptIdStoppSymcon." \n";       

        echo "Konfiguration ausgeben:\n";
        print_r($configWatchdog);
        echo "Nachrichtenspeicher ist hier ".$input."\n";
                
        $autostart = new AutostartHandler();

        /********************************************************************
        *
        * feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden
        * die laufenden Files mit einer User Session definieren um auch die Java basierte Selenium Applikation zu erfassen
        * andernfalls könnte $watchDog->checkAutostartProgram auch ohne einer Prozessliste aufgerufen werden
        *
        **********************************************************************/

        //if (false)
            {
            echo "\n";
            $processes    = $autostart->getActiveProcesses();
            $processStart = $autostart->checkAutostartProgram($processes);
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
                    $sysOps = new sysOps();
                    $verzeichnis=$configWatchdog["WatchDogDirectory"];
                    $unterverzeichnis="";                        
                    echo "selenium.exe wird neu gestartet.\n";
                    IPSLogger_Dbg(__file__, "Autostart: Selenium wird gestartet");
                    writeLogEvent("Autostart (Watchdog)".$configWatchdog["Software"]["Selenium"]["Directory"].$configWatchdog["Software"]["Selenium"]["Execute"]);
                    $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);                
                    //IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_Selenium.bat","",true,false,-1);
                    $processStart["selenium"] == "Off";
                    SetValue($ProcessStartID,json_encode($processStart));
                    }
                else
                    {
                    echo "Selenium.exe muss nicht neu gestartet werden.\n";
                    }
	            if ($processStart["vmplayer"] == "On")
                    {
                    writeLogEvent("Autostart VMWare ".'\"'.$configWatchdog["Software"]["VMware"]["Directory"].'vmware.exe\" \"'.$configWatchdog["Software"]["VMware"]["DirFiles"].$configWatchdog["Software"]["VMware"]["FileName"].'\"');
                    IPSLogger_Inf(__file__, "Autostart: VMWare wird gestartet");
                    //IPS_EXECUTEEX($verzeichnis.$unterverzeichnis."start_VMWare.bat","",true,false,-1);
                    $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."start_VMWare.bat","",true,false,-1);                
                    }
                else
                    {
                    echo "vmware.exe muss nicht erneut gestartet werden.\n";
                    }                    
                }

            SetValue($ScriptCounterID,1);
            $zeit=time()+60;
            $stunde=intval(date("H",$zeit),10);             // integer dezimal enkodiern
            $minute=intval(date("i",$zeit),10);    
            IPS_SetEventCyclicTimeFrom($tim3ID,$stunde,$minute,0);  // (integer $EreignisID, integer $Stunde, integer $Minute, integer $Sekunde)                
            IPS_SetEventActive($tim3ID,true);

            }
    }

?>