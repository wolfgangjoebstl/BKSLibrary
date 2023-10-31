<?

/***********************************************************************
 *
 * OperationCenter
 *
 * Allerlei betriebliche Abfragen und Wartungsmassnahmen
 * die Timer sind so gesetzt dass sie teilweise alle 10 Sekunden kommen können, auf Durchlaufzeiten achten
 *
 * RouterAufruftimer
 * RouterExectimer
 * SysPingTimer			alle 5 Minuten, wenn nicht anders konfiguriert wird alle 60 Minuten syspingalldevices aufgerufen
 *						für alle bekannten Geräte (Router, LED, Denon, Cams) pingen und Status ermitteln
 *						eventuell auch reboot, reset für erhöhte betriebssicherheit
 * CyclicUpdate			Update aller IPS Module, zB immer am 12. des Monates
 * CopyScriptsTimer
 * FileStatus
 * SystemInfo
 * Reserved
 * Maintenance			Starte Maintennance Funktionen 
 * MoveLogFiles			Maintenance Funktion: für Move Log Files 
 * HighSpeedUpdate      alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP
 * CleanUpEndofDay      CleanUp für Backup starten, sollte alte Backups loeschen
 * UpdateStatus
 * 
 *
 ***********************************************************/

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

$debug=false;	                    // keine lokalen Echo Ausgaben

/******************************************************

				INIT

*************************************************************/

    $dosOps = new dosOps();
    $startexec=microtime(true);

$dir655=false;

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId           = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
$backupScriptId     = @IPS_GetObjectIDByIdent('UpdateBackupLogs', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));

$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);

	/******************************************************
	 *
	 * INIT, Timer, sollte eigentlich in der Install Routine sein
	 *			
	 *		MoveCamFiles				, alle 150 Sec
	 *		RouterAufruftimer       , immer um 0:20
 	 *
	 *************************************************************/

    if (isset ($installedModules["WebCamera"]))
        {
        IPSUtils_Include ("WebCamera_Configuration.inc.php","IPSLibrary::config::modules::WebCamera");
        IPSUtils_Include ("WebCamera_Library.inc.php","IPSLibrary::app::modules::WebCamera");
        }

    if (isset ($installedModules["IPSCam"]))
        {
        //echo "Modul IPSCam ist installiert.\n";
        //echo "   Timer 150 Sekunden aktivieren um Camfiles wegzuschlichten.\n";
        $tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptId);
        IPS_SetEventActive($tim2ID,true);
        }
    else
        {
        //echo "Modul IPSCam ist NICHT installiert.\n";
        $tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptId);
        if ($tim2ID > 0)  {	IPS_SetEventActive($tim2ID,false);  }
        }
	if (isset ($installedModules["EvaluateHardware"])) 
		{     
        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
        }

    $tim1ID  = @IPS_GetEventIDByName("RouterAufruftimer", $scriptId);
    $tim3ID  = @IPS_GetEventIDByName("RouterExectimer", $scriptId);
    $tim4ID  = @IPS_GetEventIDByName("SysPingTimer", $scriptId);
    $tim5ID  = @IPS_GetEventIDByName("CyclicUpdate", $scriptId);
    $tim6ID  = @IPS_GetEventIDByName("CopyScriptsTimer", $scriptId);
    $tim7ID  = @IPS_GetEventIDByName("FileStatus", $scriptId);
    $tim8ID  = @IPS_GetEventIDByName("SystemInfo", $scriptId);
    $tim9ID  = @IPS_GetEventIDByName("Reserved", $scriptId);
    $tim10ID = @IPS_GetEventIDByName("Maintenance",$scriptId);						/* Starte Maintennance Funktionen */	
    $tim11ID = @IPS_GetEventIDByName("MoveLogFiles",$scriptId);						/* Maintenance Funktion: Move Log Files */	
    $tim12ID = @IPS_GetEventIDByName("HighSpeedUpdate",$scriptId);					/* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */

    $tim13ID = @IPS_GetEventIDByName("CleanUpEndofDay",$scriptId);                  /* CleanUp für Backup starten, sollte alte Backups loeschen */
    $tim14ID  = @IPS_GetEventIDByName("UpdateStatus", $scriptId);                   /* rausfinden welche Module ein Update benötigen, war früher bei FleStatus Timer dabei. */ 

/*********************************************************************************************/

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);

	$pname="MByte";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'',' MByte');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   //echo "Profile \"MByte\" vorhanden.\n";
	   }


/* Logging aktivieren
 *
 *********************************************************************************************/

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

/*********************************************************************************************/

	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	
	$OperationCenterConfig = $OperationCenter->getConfiguration();
	$OperationCenterSetup = $OperationCenter->getSetup();
    
	$DeviceManager = new DeviceManagement();                            // stürzt aktuell mit HMI_CreateReport ab


/********************************************************************************************
 *
 * Timer Aufrufe gestaffelt
 *
 * 1 Router auslesen starten
 * 2 Webcam Files zusammenräumen
 * 3 Router auswerten, wird von 1 gestartet
 * 4 Sysping alle Geräte, alle 5 Minuten, wenn nicht anders konfiguriert exec alle 60 Minuten
 * 5 automatisches Update der App Routinen, immer am 12. des Monats
 * 6 Scripts auf Dropbox kopieren
 * 7 File Status kopieren
 * 8 System Info auslesen und speichern
 * 9 Homematic RSSI Werte updaten
 * 10 logfiles zusammenräumen starten
 * 11 Logfiles verschieben, bis alle weg, von 10 gestartet 
 *
 **********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
		{
		case $tim12ID:			// High Speed Polling, alle 10 Sekunden
            /* benötigt ein fehlendes STATUS oder ein STATUS mit ACTIVE und ein READMODE mit SNMP
             * im data gibt es ein Kategorie mit Router_RouterName, und eine Variable SnmpFastPoll
             * wenn nicht ebenfalls Bearbeitung abbrechen, unter der Variable SnmpFastPoll gibt es zwei Variablen ifTable, SNMP Fast Poll
             * wenn die Variablen nicht vorhanden sind oder SNMP Fast Poll nicht auf true steht Abbruch
             */
			foreach ($OperationCenterConfig['ROUTER'] as $router)
				{
                if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                    {

                    }
                else
                    {
					if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") ) 
						{					                    
					    echo "Timer: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
	        			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
						$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);
						if ($fastPollId!== false)
							{
							$ifTable_ID=@IPS_GetObjectIDByName("ifTable", $fastPollId);
							$SchalterFastPoll_ID=@IPS_GetObjectIDByName("SNMP Fast Poll", $fastPollId);
							if ( ($SchalterFastPoll_ID !== false) && ($ifTable_ID !== false) && (GetValue($SchalterFastPoll_ID)==true) )
								{
								$host          = $router["IPADRESSE"];
								if (isset($router["COMMUNITY"])) $community     = $router["COMMUNITY"]; 
								else $community     = "public";				    //print_r($router);
			                    $binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
													
							    //print_r($router);
			                    switch (strtoupper($router["TYP"]))
			                        {                    
			                        case 'B2368':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_B2368($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT1900AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT1900AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT2600AC':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT2600AC($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
							        case 'RT6600AX':
										echo "   Auslesen per SNMP von \"".$router['NAME']."\".\n";
										$OperationCenter->read_routerdata_RT6600AX($fastPollId, $host, $community, $binary, $debug, true);		// nur abarbeiten wenn SNMP Library installiert ist
			                            break;
									default:
										echo "   Kein Eintrag für \"".$router['NAME']."\" gefunden. Typ \"".strtoupper($router["TYP"])."\" nicht erkannt.\n";
										break;						
			                        }       // router case
								}			// ende if SchalterFastPoll
							}				// ende if fastPoll Category
						}   	// if snmp fast poll active
                    }       // if active
                }   // foreach
			break;
		default:                // unbekannt
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}

    echo "\nDurchlaufzeit : ".(microtime(true)-$startexec)." Sekunden\n";


	
?>