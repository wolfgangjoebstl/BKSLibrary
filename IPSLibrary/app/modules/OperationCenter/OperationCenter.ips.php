<?php

/***********************************************************************
 *
 * OperationCenter
 *
 * Allerlei betriebliche Abfragen und Wartungsmassnahmen
 * die Timer sind so gesetzt dass sie teilweise alle 10 Sekunden kommen können, auf Durchlaufzeiten achten
 *
 * schlichtet auch die Bilder der Webcams, ändern auf neues Verzeichnis ab IPS7
 *
 * wurde für Windows betriebssystem geschrieben, grosse Problem wenn Betrieb auf Unix erfolgt:
 *
 *
 *
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
 * Bearbeitet Webfront Bedienelemente:
 *  abhängig von varaibleID
 *
 *
 * Bearbeitet Timer:
 *
 * Execute:
 *
 * Variable:
 *
 ***********************************************************/

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("DeviceManagement_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

$ExecuteExecute=true;             	// Execute machen
$debug=false;	                    // keine lokalen Echo Ausgaben
if ($_IPS['SENDER']=="Execute") $debug=true;

/******************************************************

				INIT

*************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(500);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
    ini_set('memory_limit', '128M');       //usually it is 32/16/8/4MB 
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

    $scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
    $scriptIdFastPollShort     = IPS_GetScriptIDByName('FastPollShortExecution', $CategoryIdApp);

    $scriptId           = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
    $backupScriptId     = @IPS_GetObjectIDByIdent('UpdateBackupLogs', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
    if ($backupScriptId !== false) 
        {
        //echo "Die Backups werden in einem eigenem Script ($backupScriptId) mit höherem Speicherbedarf finalisiert.\n";
        }
    else 
        {
        //echo "Script UpdateBackupLogs nicht gefunden. Speicherlimit kann überschritten werden.\n";
        }
    //echo "Zwei Werte, OperationCenter Modul (".IPS_GetName($scriptId).") und Script im Modul $CategoryIdApp (".IPS_GetName($CategoryIdApp).").\n";

    $scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);

	/******************************************************
	 *
	 * INIT, Timer, sollte eigentlich in der Install Routine sein
	 *			
	 *		MoveCamFiles				, alle 150 Sec
	 *		RouterAufruftimer       , immer um 0:20Geräte mit
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
        IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
        IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

        if ($debug) echo "EvaluateHardware, Geräte mit getComponent suchen, geht jetzt mit HardwareList und DeviceList.\n";
        IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");

        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
        }

    $tim1ID  = @IPS_GetEventIDByName("RouterAufruftimer", $scriptId);
    $tim3ID  = @IPS_GetEventIDByName("RouterExectimer", $scriptId);
    $tim4ID  = @IPS_GetEventIDByName("SysPingTimer", $scriptId);                // alle 5 Minuten aufgerufen, Variable für hour passed und four hour passed
    $tim5ID  = @IPS_GetEventIDByName("CyclicUpdate", $scriptId);
    $tim6ID  = @IPS_GetEventIDByName("CopyScriptsTimer", $scriptId);
    $tim7ID  = @IPS_GetEventIDByName("FileStatus", $scriptId);
    $tim8ID  = @IPS_GetEventIDByName("SystemInfo", $scriptId);
    $tim9ID  = @IPS_GetEventIDByName("Homematic", $scriptId);
    $tim10ID = @IPS_GetEventIDByName("Maintenance",$scriptId);						/* Starte Maintennance Funktionen */	
    $tim11ID = @IPS_GetEventIDByName("MoveLogFiles",$scriptId);						/* Maintenance Funktion: Move Log Files */	
    $tim12ID = @IPS_GetEventIDByName("HighSpeedUpdate",$scriptIdFastPollShort);					/* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */

    $tim13ID = @IPS_GetEventIDByName("CleanUpEndofDay",$scriptId);                  /* CleanUp für Backup starten, sollte alte Backups loeschen */
    $tim14ID = @IPS_GetEventIDByName("UpdateStatus", $scriptId);                   /* rausfinden welche Module ein Update benötigen, war früher bei FleStatus Timer dabei. */ 

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
	$pingOperation       = new PingOperation();             // ping Befehle zusammengefasst

	$OperationCenterConfig = $OperationCenter->getConfiguration();
	$OperationCenterSetup = $OperationCenter->getSetup();

    // wird nur für Windows Betriebssystem verwendet
    $verzeichnis=$OperationCenterSetup["SystemDirectory"];
    $filename = $verzeichnis."read_Systeminfo.bat";
    $fileRead=array();
    $fileRead["SystemInfo"] = $verzeichnis."system.txt";
	$sysOps = new sysOps(); 
    
    $DeviceManager = new DeviceManagement();                            // stürzt aktuell mit HMI_CreateReport ab
    //$DeviceManagerHomematic = new DeviceManagement_Homematic();         // deshalb diese class verwenden
    

/**********************************
 *
 * Backup Funktion und Move Log and Capture Files vorbereiten
 *
 *************************************/

	$BackupCenter=new BackupIpsymcon($subnet);

	$LogFileHandler=new LogFileHandler($subnet);    // handles Logfiles und Cam Capture Files

/***********************************************
 *
 * Homematic RSSI Werte auslesen
 *
 ********************************************************************************************/

	$CategoryIdHomematicErreichbarkeit = CreateCategoryPath('Program.IPSLibrary.data.modules.OperationCenter.HomematicRSSI');
    $ExecuteRefreshID = @IPS_GetObjectIDByName("UpdateDurchfuehren", $CategoryIdHomematicErreichbarkeit);
    if ($ExecuteRefreshID === false )
        {
        $fatalerror=true;
        $ExecuteRefreshID = CreateVariable("UpdateDurchfuehren",   0 /*Boolean*/,  $CategoryIdHomematicErreichbarkeit, 400 , '~Switch',$scriptIdOperationCenter,null,"");
        }
    $ExecuteRefreshRSSI=GetValue($ExecuteRefreshID);    

	/* gemeinsame Behandlung von ActionButtons aus den verschiedenen Klassen
 	 *
 	 *******************************************************************************************/	

	$ActionButton=$OperationCenter->get_ActionButton();
	$ActionButton+=$DeviceManager->get_ActionButton();
    //$ActionButton+=$DeviceManagerHomematic->get_ActionButton();               // keine zusätzlichen Buttons
	$ActionButton+=$BackupCenter->get_ActionButton();

/********************************************************************************************
 *  HMI Buttons in Doctor Bag -> HMI
 *  Router Management, SNMP FastPoll
 *  Backup Funktionen
 *  Monitor
 *  Update
 *
 */

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $variableId=$_IPS['VARIABLE'];
    $value=$_IPS['VALUE'];
    $oldvalue=GetValue($variableId);
	SetValue($variableId,$value);
    $debugWebfront=false;
    if ($debugWebfront) echo "Taste gedrückt. $variableId ".IPS_GetName($variableId)."\n";
	switch ($variableId)
		{
		case 0:
			break;
		default:	
            //echo "OperationCenter $variableId ";        
		    if (array_key_exists($variableId,$ActionButton))
		        {
                if ($debugWebfront) echo "found ";                    
                /* nach Klassen getrennt auswerten, Routine kann in die Klasse später übernommen werden */
				if (isset($ActionButton[$variableId]["DeviceManagement"]))
					{
                    //echo "in DM "; 
					if (isset($ActionButton[$variableId]["DeviceManagement"]["HMI"]))
                        {
                        /* Homematic Inventory Tabelle sortieren
                        *
                        ********************************************************************************************/					
                        $sortOrder = $_IPS['VALUE'];
                        if ($sortOrder>5) $sortOrder=0;
                        $HMI=$ActionButton[$variableId]["DeviceManagement"]["HMI"];
                        $HomematicInventoryId=$ActionButton[$variableId]["DeviceManagement"]["HtmlBox"];
                        //echo "$variableId gefunden.".IPS_GetName($HMI)."   ".IPS_GetProperty($HMI,"SortOrder");
                        $outputfile = IPS_GetProperty($HMI,"OutputFile");
                        if (strpos("webfront",$outputfile)) echo "neuen Ordner user einstellen, webfront ab IPS 7 nicht mehr erlaubt. \n";
                        IPS_SetProperty($HMI,"SortOrder",$sortOrder);
                        IPS_ApplyChanges($HMI);
                        HMI_CreateReport($HMI);
                        SetValue($HomematicInventoryId,GetValue($HomematicInventoryId));
                        if ( (isset($scriptIdEvaluateHardware)) && ($_IPS['VALUE']>5) ) IPS_RunScriptWait($scriptIdEvaluateHardware);
                        //echo "fertig";
                        }
					}

                /* Router Management, SNMP FastPoll */

				if (isset($ActionButton[$variableId]["OperationCenter"]))
                    {
                    if (isset($ActionButton[$variableId]["OperationCenter"]["ActivateTimer"]))
                        {
                        if (GetValue($variableId)) IPS_SetEventActive($tim12ID,true);
                        else IPS_SetEventActive($tim12ID,false);
                        }
                    }

                /* Backup Funktionen */
				if (isset($ActionButton[$variableId]["Backup"]))
                    {
                    //echo "Wir sind bei den Action Buttons der Klasse Backup. Variable Id ist $variableId.\n";
                    if (isset($ActionButton[$variableId]["Backup"]["BackupActionSwitch"]))
                        {
                        //echo "Button Action Switch mit Wert $value.\n";
                        switch ($value)
                            {
                            case 0: // Repair
                                $BackupCenter->cleanToken();
                                SetValue($variableId,$oldvalue); 
                                IPS_SetEventActive($tim11ID,true); 
                                $BackupCenter->setBackupStatus("Repair ".date("d.m.Y H:i:s"));                     
                                break;
                            case 1: // Restart
                                $BackupCenter->startBackup();               // no type means, start the old one again
                                $BackupCenter->setBackupStatus("Restart ".date("d.m.Y H:i:s"));                     
                                $BackupCenter->cleanToken();
                                IPS_SetEventActive($tim11ID,true);          // event erst am Ende starten, damit nicht gleich wieder ausgeschaltet wird
                                break;
                            case 2: // Full
                                $BackupCenter->startBackup("full");
                                $BackupCenter->setBackupStatus("Backup Full ".date("d.m.Y H:i:s"));                     
                                IPS_SetEventActive($tim11ID,true);          // event erst am Ende starten, damit nicht gleich wieder ausgeschaltet wird
                                break;
                            case 3: // Increment
                                //echo "Increment gedrückt. Jetzt mit Backup starten.\n";
                                $BackupCenter->startBackup("increment");
                                $BackupCenter->setBackupStatus("Backup Increment ".date("d.m.Y H:i:s"));                     
                                IPS_SetEventActive($tim11ID,true);          // event erst am Ende starten, damit nicht gleich wieder ausgeschaltet wird
                                break;
                            case 4: // CleanUp
                                $BackupCenter->configBackup(["status" => "cleanup"]);
                                $BackupCenter->configBackup(["cleanup" => "started"]);
                                $BackupCenter->setBackupStatus("Cleanup ".date("d.m.Y H:i:s"));                     
                                IPS_SetEventActive($tim11ID,true);          // event erst am Ende starten, damit nicht gleich wieder ausgeschaltet wird
                                break;
                            case 5: // Stopp
                                $BackupCenter->stoppBackup();
                                $BackupCenter->setBackupStatus("Stopp ".date("d.m.Y H:i:s"));                     
                                break;                            
                            default:
                                break;                                                    
                            }
                        }
                    if (isset($ActionButton[$variableId]["Backup"]["BackupFunctionSwitch"]))
                        {
                        //echo "BackupFunctionSwitch mit $value gedrueckt.\n";
                        switch ($value)
                            {
                            case 0: // Aus
                            case 1: // Ein
                            case 2: // Auto
                            default:
                                break;                                                    
                            }
                        }
                    if (isset($ActionButton[$variableId]["Backup"]["BackupOverwriteSwitch"]))
                        {
                        //echo "BackupOverwriteSwitch mit $value gedrueckt.\n";
                        switch ($value)
                            {
                            case 0: // Keep
                                $BackupCenter->configBackup(["update" => "keep"]);
                                break;
                            case 1: // Overwrite
                                $BackupCenter->configBackup(["update" => "overwrite"]);
                                break;
                            case 2: // Auto
                            default:
                                break;                                                    
                            }
                        }

                    if (isset($ActionButton[$variableId]["Backup"]["StatusSliderMaxcopy"])) 
                        // StatusSliderMaxcopy schreibt in params
                        {
                        $BackupCenter->configBackup(["maxcopy" => $value]);
                        }

                    }  
                //echo "Had been here $variableId : ".json_encode($ActionButton)."  ";;
                if (isset($ActionButton[$variableId]["Monitor"]))
                    {
                    //echo "Monitor gedrückt"; 
                    $pingOperation->writeSysPingStatistics(); 
                    }                     
                if (isset($ActionButton[$variableId]["Update"]))
                    {
                    //echo "Update gedrückt. Bitte Geduld :";                   // kommt nicht etwa schneller, sondern mit allen Ergebnissen gemeinsam am Ende
                    $sysOps->ExecuteUserCommand($filename,"", false, false,-1,false);                          // false nix anzeigen  false nix warten, da Batch writing wäre das ausreichend
                    // $OperationCenter->SystemInfo();                            //  ohne Parameter fragt SystemInfo selbst ab, mit Parameter wird der Input aus einer Variable extrahiert

                    $categoryId_SysInfo = CreateCategory('SystemInfo', 		$CategoryIdData, 230);
                    $sumTableHtmlID     = IPS_GetObjectIdByName("SystemInfoOverview", $categoryId_SysInfo);           // obige Informationen als kleine Tabelle erstellen
                    $sysOps->getProcessListFull($fileRead);                 // startet getSystemInfo($filename["SystemInfo"] um die Werte aus der Systeminfo Datei zu extrahieren
 
                    $html=true;
                    $sumTableHtml=$OperationCenter->readSystemInfo($html);             // die Systeminfo als html Tabelle zusammenstellen
                    SetValue($sumTableHtmlID, $sumTableHtml);

                    $categoryId_SysPing    	= CreateCategory('SysPing',       	$CategoryIdData, 200);
                    $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$categoryId_SysPing);
                    $SysPingActivityTableID = @IPS_GetObjectIDByName("SysPingActivityTable",$categoryId_SysPingControl); 
                    $actual=false;
                    $html=$pingOperation->writeSysPingActivity($actual, true, false);
                    SetValue($SysPingActivityTableID,$html);   

                    //$pingOperation->SysPingAllDevices($log_OperationCenter);          
                    $pingOperation->writeSysPingStatistics(); 
  
                    }  
                if (isset($ActionButton[$variableId]["RemoteAccess"]))
                    {
                    $remoteaccessId = getCategoryIdByName($CategoryIdData, "RemoteAccess");
                    $htmlBoxId = getVariableIDByName($remoteaccessId, "htmlBigBox");                        
                    $dir=$OperationCenter->appInstalledWin("Tailscale");
                    if ($dir) 
                        {
                        $remoteaccessData = new RemoteAccessData();
                        if ($oid=$remoteaccessData->showSqlStatus())
                            {
                            echo "SQL Instanz installiert : $oid \n";
                            }
                        echo "TailScale Installed at $dir \n";
                        $resultSystemInfo=$sysOps->ExecuteUserCommand($dir."tailscale.exe","status", false, true);

                        /* verwendung von ipsTables, mehrfache verwendung ergibt jede Menge gleicher Styles, die aber nicht richtig gekapselt sind
                        */
                        $id="a1235"; $class="maindiv2";
                        $text = "<style>";
                        $text.='#'.$id.' table { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';
                        $text.='font-size:16px; max-width: 900px ';        // responsive font size   
                        $text.='color:black; border-collapse: collapse;  }';
                        $text.='#'.$id.' td, #customers th { border: 1px solid #ddd; padding: 8px; }';
                        $text.='#'.$id.' tr:nth-child(even){background-color: #f2f2f2;color:black;}';
                        $text.='#'.$id.' tr:nth-child(odd){background-color: #e2e2e2;color:black;}';
                        $text.='#'.$id.' tr:hover {background-color: #ddd;}';
                        $text.='#'.$id.' th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; word-wrap: break-word; white-space: normal;}';

                        /* class1 flex container mit den class2 darunterliegenden div 
                           darunter übereinander rechtsbündig darstellen 
                           display: flex; reicht zum nebeneinander darstellen
                           wrap kann verwendet werden, aber Achtung mit der größe der Tabellen darunter
                           */
                        $text.=".".$class."1 {  idth: 100%; height: 100%;         
                            display: flex;  
                            flex-direction: row; flex-wrap: wrap;        
                            justify-content: space-between;   ";
                            //align-items: flex-start; align-content: flex-start;
                            $text.="box-sizing: border-box; padding: 1px 1px 1px 1px;		}"; 
                        // zusaetzliche Formatierung für die divs unter maindiv1 :
                        $text.=".".$class."1>* { position: relative;	z-index: 1; }"; 
                        // div darunter für left-aligned, center-aligned und right aligned
                        $text.=".".$class."2 { ";          
                            $text.="display: flex; position: relative; 
                            flex-direction: line; flex-wrap: wrap;
                            justify-content: space-between;
                            align-items: center;
                            box-sizing: border-box;	padding: 0px 0px 5px 0px;	}"; 
                        $text.="</style>";
                        $html=$text;
                        $html .= "<div class=".$class."1 id=$id>";
                        /* html ohne html block aber mit style und unterschiedlicher id
                        */
                        $html .= "<div class=".$class."2 >";
                        $html .= $remoteaccessData->showTailscaleStatus($resultSystemInfo);         // immer als zweites, does reuse of style
                        $html .= "</div>";
                        $html .= "<div class=".$class."2 >";

                        $html .= $remoteaccessData->showRemoteAcessStatus();
                        $html .= "</div>";
                        $html .= "</div>";

                        $remoteaccessId = getCategoryIdByName($CategoryIdData, "RemoteAccess");
                        $htmlBoxId = getVariableIDByName($remoteaccessId, "htmlBigBox");
                        echo "Remote Access Data Id in OperationCenter : $remoteaccessId  View html Box : $htmlBoxId \n";
                        if ($htmlBoxId)
                            {
                            SetValue($htmlBoxId,$html);
                            }
                        }
                    }                                       
				}	
			break;
		}
    }

/*******************************************************************************************
 * 
 *      EXECUTE, nur machen wenn am Anfang der Scriptdatei freigegeben
 *
 ***/

if (($_IPS['SENDER']=="Execute") && $ExecuteExecute)
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	IPSLogger_Dbg(__file__, "Operation Center von Konsole aus gestartet:");
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.="   ".str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

	echo "Category Data ID   : ".$CategoryIdData."\n";
	echo "Category App ID    : ".$CategoryIdApp."\n";
	echo "Category Script ID : ".$scriptId."\n\n";

	echo "Folgende Module werden von OperationCenter bearbeitet:\n";
	if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
	if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
	if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
	if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
	if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["IPSWeatherForcastAT"])){ 	echo "  Modul IPSWeatherForcastAT ist installiert.\n"; } else { echo "Modul IPSWeatherForcastAT ist NICHT installiert.\n";}
	echo "\n";

	echo "Timer Installation : \n";
	echo "  Timer RouterAufruftimer OID : ".$tim1ID."\n";
	echo "  Timer MoveCamFiles OID      : ".$tim2ID."\n";
	echo "  Timer RouterExectimer OID   : ".$tim3ID."\n";
	echo "  Timer SysPingTimer OID      : ".$tim4ID."\n";
	echo "  Timer CyclicUpdate OID      : ".$tim5ID."\n";
	echo "  Timer CopyScriptsTimer OID  : ".$tim6ID."\n";
	echo "  Timer FileStatus OID        : ".$tim7ID."\n";
	echo "  Timer SystemInfo OID        : ".$tim8ID."\n";
	echo "  Timer Reserved OID          : ".$tim9ID."\n";
	echo "  Timer Maintenance OID       : ".$tim10ID."\n";                /* Starte Maintennance Funktionen */
	echo "  Timer MoveLogs OID          : ".$tim11ID."\n";              /* Maintenance Funktion: Move Log Files */
	echo "  Timer HighSpeedUpdate OID   : ".$tim12ID."\n";              /* alle 10 Sekunden Werte updaten, zB die Werte einer SNMP Auslesung über IPS SNMP */
	echo "  Timer CleanUpEndofDay OID   : ".$tim13ID."\n";              /* CleanUp für Backup starten, sollte alte Backups loeschen */
	echo "  Timer UpdateStatus OID      : ".$tim14ID."\n";              /* rausfinden welche Module ein Update benötigen, war früher bei FleStatus Timer dabei. */

	/********************************************************
   	Erreichbarkeit Hardware im Execute
	**********************************************************/
    echo "Ausgabe HardwareStatus aus dem DeviceManager:\n";
	$ergebnis=$DeviceManager->HardwareStatus(true);                     // true ist Output als Text
    print_r($ergebnis);

	/********************************************************
   	Externe Ip Adresse immer ermitteln
	**********************************************************/
	
	echo "\nExterne IP Adresse ermitteln.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	$result=$OperationCenter->whatismyIPaddress1()[0];
	if ($result["IP"]==false)
		{
		echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";
		}
	else
		{
	   	echo "Whatismyipaddress liefert : \"".$result["IP"]."\"\n";
	   	}
	   
	$result=$OperationCenter->ownIPaddress();
	foreach ($result as $ip => $data)
		{
		printf("Port \"%s\" hat IP Adresse %s und Gateway %s Ip Adresse im Longformat : %u\n", $data["Name"],$ip,$data["Gateway"],ip2long($ip));
		}

	/********************************************************
   	die Webcam anschauen und den FTP Folder zusammenräumen
	**********************************************************/

	if (isset ($installedModules["IPSCam"]))
		{
		echo "\nWebcam anschauen und ftp Folder zusammenräumen.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

		IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
		IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");

		if (isset ($OperationCenterConfig['CAM']))
			{
			
			/* möglicherweise ist der Archivstatus für die Variablen noch nicht definiert --> Teil des Install Prozesses */
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
                if (isset($cam_config['FTPFOLDER']))            			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
                    {
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                        
                        echo "Create Variable Structure für Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                        $verzeichnis = $cam_config['FTPFOLDER'];
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                        if ($cam_categoryId==false)
                        {
                            $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                            IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                            IPS_SetParent($cam_categoryId,$CategoryIdData);
                            }
                        $WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3); /* 0 Boolean 1 Integer 2 Float 3 String */
                        $WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
                        $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                        AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
                        AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,1);      /* 0 normaler Wert 1 Zähler */
                        IPS_ApplyChanges($archiveHandlerID);

                        $WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                        AC_SetLoggingStatus($archiveHandlerID,$WebCam_MotionID,true);
                        AC_SetAggregationType($archiveHandlerID,$WebCam_MotionID,0);      /* normaler Wwert */
                        IPS_ApplyChanges($archiveHandlerID);

                        // Test, ob ein Verzeichnis angegeben wurde
                        if ( is_dir ( $verzeichnis ))
                            {
                        // öffnen des Verzeichnisses
                        if ( $handle = opendir($verzeichnis) )
                                {
                            $count=0; $list="";
                                /* einlesen des Verzeichnisses        	*/
                                while (($file = readdir($handle)) !== false)
                                {
                            if (is_dir($verzeichnis.$file)==false)
                                    {
                                        $count++;
                                    $list .= $file."\n";
                                    }
                                    }
                                echo "   Im Cam FTP Verzeichnis ".$verzeichnis." gibt es ".$count." neue Dateien.\n";
                                echo "   Letzter Eintrag von ".GetValue($WebCam_LetzteBewegungID)."\n";
                                //echo $list."\n";
                                }
                            }
                        }    /* ende ftp enabled */
                    }       /* ende ftpfolder */
				}  /* ende foreach */

			/* eigentliche Zusammenräum Routine, siehe auch Timeraufrufe */
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
                if (isset($cam_config['FTPFOLDER']))            			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
                    {
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                        
        				echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                        $verzeichnis = $cam_config['FTPFOLDER'];
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
                        if ($cam_categoryId==false)
                        {
                            $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                            IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                            IPS_SetParent($cam_categoryId,$CategoryIdData);
                            }
                        $WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3);
                        $WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
                        $WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0, '~Motion', null ); /* 0 Boolean 1 Integer 2 Float 3 String */

                        $count=move_camPicture($verzeichnis,$WebCam_LetzteBewegungID);
                        SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count);
                        }
                    }
				}  /* ende foreach */
			}
		}       // ende if (isset ($installedModules["IPSCam"]))

    /* Timer 2 Emulation/Simulation */
        echo "\n=================================================================\n";
        echo "Timer 2 showCamCaptureFiles und showCamSnapshots ausführen:\n\n";
        if (isset ($OperationCenterConfig['CAM']))
            {
            $count=0;
            foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)             /* das sind die Capture Dateien, die häufen sich natürlich wenn mehr Bewegung ist */
                {
                if (isset ($cam_config['FTPFOLDER']))         
                    {
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                        
                        echo "   Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                        $cam_config['CAMNAME']=$cam_name;
                        if (isset($cam_config["MOVECAMFILES"])) if ($cam_config["MOVECAMFILES"]) $count+=$LogFileHandler->MoveCamFiles($cam_config);
                        $OperationCenter->PurgeCamFiles($cam_config);
                        }
                    }
                }


            /* Die Snapshots der IPS Cam Kameras auf einen Bildschirm bringen, kann auch Modul Webcamera übernehmen */	
            //$OperationCenter->copyCamSnapshots();
            if (isset ($installedModules["WebCamera"]))
                {
                $webCamera = new webCamera();       // eigene class starten
                $camConfig = $webCamera->getStillPicsConfiguration();
                
                $camOperation = new CamOperation();
                /* die wichtigsten Capture Files auf einen Bildschirm je lokaler Kamera bringen */
                echo "   --> Show CamCapture files:\n";
                $camOperation->showCamCaptureFiles($camConfig,true);  
                echo "   --> Show CamSnapshots files:\n";
                $camOperation->showCamSnapshots($camConfig,true);	            // sonst wertden die Objekte der IPSCam verwendet, sind viel weniger
                }
			} /* Ende isset */        

	/********************************************************
   	Auswertung Router MR3420   curl
	**********************************************************/

	echo "\nAuswertung Router Daten.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n\n";

	$mr3420=false;    /* jetzt mit imacro geloest, die können die gesamte Webseite inklusive Unterverzeichnisse abspeichern und beliebig im Frame manövrieren */
	if ($mr3420==true)
		{
		$url="http://10.0.1.201/userRpm/StatusRpm.htm";  	/* gets the data from a URL */

		/*  $result=file_get_contents($url) geht leider nicht, passwort Eingabe, Browserchecks etc  */
		$ch = curl_init($url);
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
		curl_setopt($ch, CURLOPT_USERPWD, "admin:cloudg06");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
	   	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko"); // who am i
   		curl_setopt($ch, CURLOPT_ENCODING, "");       // handle all encodings
	   	curl_setopt($ch, CURLOPT_AUTOREFERER, true);     // set referer on redirect
		//curl_setopt($ch, CURLOPT_REFERER, $url);  /* wichtig damit TP-Link weiss wo er die Daten hinschicken soll, Autoreferer funktioniert aber besser, siehe oben */
	   	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);      // timeout on connect
   		curl_setopt($ch, CURLOPT_TIMEOUT, 120);      // timeout on response
	   	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // stop after 10 redirects

		/*
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */

		$data = curl_exec($ch);

		/* Curl Debug Funktionen */

		echo "Channel :".$ch."\n";
	  	$err     = curl_errno( $ch );
   		$errmsg  = curl_error( $ch );
	   	$header  = curl_getinfo( $ch );

		echo "Fehler ".$err." von ";
		print_r($errmsg);
		echo "\n";
		echo "Header ";
		print_r($header);
		echo "\n##################################################################################################\n";


		curl_close($ch);

		echo $data;
		}

	/********************************************************
   	Auswertung der angeschlossenen Router
	**********************************************************/

   	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
		//print_r($router);
			
		/********************************************************
   		Auswertung Router MR3420 mit imacro
		**********************************************************/

		echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		if ($router['TYP']=='MR3420')
			{
			//$OperationCenter->write_routerdata_MR3420($router);   // keine logging Einträge machen
			}
		if ($router['TYP']=='MBRN3000')
			{
			$RouterResult=$OperationCenter->write_routerdata_MBRN3000($router,true);   // keine logging Einträge machen, debug=false
			print_r($RouterResult);
			}
		if ($router['TYP']=='RT1900ac')
			{
			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
			if ($router_categoryId==false)
				{
				$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
				IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
				IPS_SetParent($router_categoryId,$CategoryIdData);
				}
			$host          = $router["IPADRESSE"];
			$community     = "public";                                                                         // SNMP Community
			$binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
			$snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
			$result=$snmp->update(true);           /* mit Parameter true erfolgt kein Logging, also Spontanabfrage */
			//print_r($result);
				
      		/*		if (($ByteID=@IPS_GetVariableIDByName("MBytes_".$ipadresse['IPAdresse'],$router_categoryId))==false)
         				{
						  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_".$ipadresse['IPAdresse'], 2);
							IPS_SetVariableCustomProfile($ByteID,'MByte');
							AC_SetLoggingStatus($archiveHandlerID,$ByteID,true);
							AC_SetAggregationType($archiveHandlerID,$ByteID,0);
							IPS_ApplyChanges($archiveHandlerID);
							}  */
			}
	   	}

		//$handle2=fopen($router["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");

	/********************************************************
   	Logspeicher anlegen und auslesen
	**********************************************************/

	echo "Logspeicher ausgedruckt:\n";
	echo 	$log_OperationCenter->PrintNachrichten();

	/********************************************************
   	ARP für alle IP Adressen im Netz
	**********************************************************/

	echo "ARP Auswertung für alle bekannten MAC Adressen aus AllgDefinitionen.       ".(microtime(true)-$startexec)." Sekunden\n";
	$OperationCenter->find_Hostnames();

	echo "============================================================================================================\n";

	/********************************************************
   	Router daten ausgeben
	**********************************************************/

  	foreach ($OperationCenterConfig['ROUTER'] as $router)
	   {
	   echo "\n";
	   echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CatIdData);
		if ($router['TYP']=='MBRN3000')
		   {
			//$OperationCenter->sort_routerdata($router);
			//$OperationCenter->get_routerdata($router);
			echo "MBRN3000 Werte von Heute   : ".$OperationCenter->get_routerdata_MBRN3000($router,true)." Mbyte \n";
			echo "MBRN3000 Werte von Gestern : ".$OperationCenter->get_routerdata_MBRN3000($router,false)." Mbyte \n";
		   }
		if ($router['TYP']=='MR3420')
		   {
			$OperationCenter->sort_routerdata($router);
			$OperationCenter->get_routerdata($router);
			}
		if ($router['TYP']=='RT1900ac')
		    {
			//$OperationCenter->get_routerdata($router);
            $OperationCenter->get_routerdata_RT1900($router);
            //$OperationCenter->get_routerdata_RT1900($router,true);
			}
		}

	echo "============================================================================================================\n";

	/********************************************************
   	Sys Ping the Devices
	**********************************************************/

	//SysPingAllDevices($OperationCenter,$log_OperationCenter);
	//$OperationCenter->SysPingAllDevices($log_OperationCenter);

    $homematicOperation  = new HomematicOperation();
			
    $homematicOperation->ccuSocketStatus($log_OperationCenter,true);        // true für Debug
    $homematicOperation->ccuSocketDutyCycle($log_OperationCenter,true);      // called every hour, internal controled by variable in count5mins
    $pingOperation->SysPingAllDevices($log_OperationCenter);          

	echo "============================================================================================================\n";

	/********************************************************
    *
	 *	UpdateAll
	 *
	 * Aufpassen, ueberschreibt wieder alle bereits programmierten Änderungen. besser auskommentiert lassen
	 *
	 **********************************************************/

	//CyclicUpdate();

	echo "============================================================================================================\n";

	/********************************************************
   	CopyScripts
	**********************************************************/

	//$OperationCenter->CopyScripts();

	/********************************************************
   	Move Logs
	**********************************************************/

	//$OperationCenter->MoveLogs();
	if (isset($OperationCenter->oc_Setup['CONFIG']['MOVELOGS'])==true) if ($OperationCenter->oc_Setup['CONFIG']['MOVELOGS']==true) $countlog=$LogFileHandler->MoveFiles(IPS_GetKernelDir().'logs/',2);


	/************************************************************************************
  	StatusInformation von sendstatus auf ein Dropboxverzeichnis kopieren
  	einmal als aktuelle Werte und einmal als historische Werte
	*************************************************************************************/
	echo "============================================================================================================\n";
	echo "Operation center, Filestatus (Send_status) berechnen.\n";
	$OperationCenter->FileStatus();
	
	if (isset ($installedModules["Amis"]))
		{
		echo "============================================================================================================\n";
		echo "Operation center, AMIS Registertabellen in Zusammenfassung neu berechnen.\n";

		/* html Tabellen der Energieregister und Historien ebenfalls updaten */
		IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
		IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
			
		$amis=new Amis();
		$MeterConfig = $amis->getMeterConfig();
		$dataOID=$amis->getAMISDataOids();
		$tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
		$regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
		$Meter=$amis->writeEnergyRegistertoArray($MeterConfig);
		SetValue($tableID,$amis->writeEnergyRegisterTabletoString($Meter));
		SetValue($regID,$amis->writeEnergyRegisterValuestoString($Meter));		
		}				

	/************************************************************************************
	 * System Informationen berechnen
	 *
	 *************************************************************************************/

	echo "============================================================================================================\n";
	echo "Operation center, SystemInfo.\n";

	//$OperationCenter->SystemInfo();
    $sysOps->getProcessListFull($fileRead);

	echo "============================================================================================================\n";
	echo "\nEnde Execute.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";


	} /* ende Execute */

	
/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/********************************************************************************************
 *
 * Timer Aufrufe gestaffelt
 *
 * 1 Router auslesen starten, einmal am tag
 * 2 Webcam Files zusammenräumen, alle 150 Sekunden
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
		case $tim1ID:       // einmal am Tag Router auslesen
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Router Auswertung");
			/********************************************************
			Einmal am Tag: nun den Datenverbrauch über den router auslesen
			**********************************************************/
			foreach ($OperationCenterConfig['ROUTER'] as $router)
				{
                if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
                    {

                    }
                else
                    {                    
				    echo "Timer: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
        			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$CategoryIdData);
					$host          = $router["IPADRESSE"];
					if (isset($router["COMMUNITY"])) $community     = $router["COMMUNITY"]; 
					else $community     = "public";				    //print_r($router);
                    $binary        = "C:\Scripts\ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
					
					if ($router_categoryId !== false)		// wenn in Install noch nicht angelegt, auch hier im Timer ignorieren
						{
	                    switch (strtoupper($router["TYP"]))
	                        {                    
					        case 'MR3420':      /* dynamische Inhalte, so gehts eigentlich nicht mehr, auf curl umsteigen */
	        					IPS_ExecuteEX($OperationCenterSetup["FirefoxDirectory"]."firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
			        			//IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
					        	SetValue($ScriptCounterID,1);
	        					IPS_SetEventActive($tim3ID,true);
			        			IPSLogger_Dbg(__file__, "Router ".$router['TYP']." ".$router['NAME']." Auswertung gestartet.");
	                            break;
	                        case 'B2368':
								$OperationCenter->read_routerdata_B2368($router_categoryId, $host, $community, $binary, $debug);
								IPSLogger_Dbg(__file__, "Router B2368 Auswertung abgeschlossen.");
								break;							
					        case 'RT1900AC':
								$OperationCenter->read_routerdata_RT1900AC($router_categoryId, $host, $community, $binary, $debug);
					            IPSLogger_Dbg(__file__, "Router RT1900ac Auswertung abgeschlossen.");
	                            break;
					        case 'RT2600AC':
								$OperationCenter->read_routerdata_RT2600AC($router_categoryId, $host, $community, $binary, $debug);
					            IPSLogger_Dbg(__file__, "Router RT2600ac Auswertung abgeschlossen.");
	                            break;								
	                        case 'MBRN3000':
	    					    $OperationCenter->write_routerdata_MBRN3000($router);
	                            break;
							default:
								echo "   Kein Eintrag für \"".$router['NAME']."\" gefunden. Typ \"".strtoupper($router["TYP"])."\" nicht erkannt.\n";
								break;						
					        }   /* ende switch */

						if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") ) 
							{					                    
						    echo "ifTable: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
							$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);
							$ifTable_ID=@IPS_GetObjectIDByName("ifTable", $fastPollId);
							if ($ifTable_ID !== false)
								{					
			                    switch (strtoupper($router["TYP"]))
			                        {                    
			                        case 'B2368':
										$snmp=new SNMP_OperationCenter($fastPollId, $host, $community, $binary, $debug);							
										$filterLine=array();
										$filterCol=false;
										$result=$snmp->getifTable("1.3.6.1.2.1.2", $filterLine, $filterCol);
										SetValue($ifTable_ID,$result);
			                            break;
							        case 'RT1900AC':
							        case 'RT2600AC':
										$snmp=new SNMP_OperationCenter($fastPollId, $host, $community, $binary, $debug);							
										$filterLine=["AND" => ["ifType" => "6", "ifOperStatus" => "1"]];
			                            $filterCol=array(		// kopiere die Spalten die enthalten sein sollen von collums
							                "1" => "ifIndex",
							                "2" => "ifDescr",
											 "6" => "ifPhysAddress",
			                                "10" => "ifnOctets",
			                                "16" => "ifOutOctests",
			                                        );
										$result=$snmp->getifTable("1.3.6.1.2.1.2", $filterLine, $filterCol);
										SetValue($ifTable_ID,$result);
			                            break;
									default:
										break;						
			                        }       // router case
								}			// ifTable HTMLBox angelegt
							} 			// if snmp readmode
						} /* ende if routerCategory definiert */
                    }   /* ende if active */
				} /* Ende foreach */
			break;
		
		case $tim2ID:       // Webcam FTP Dateien zusammenraeumen:
			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam FTP Dateien zusammenraeumen:");
			/********************************************************
		     * nun die Webcam zusammenraeumen, derzeit alle 150 Sekunden
			 **********************************************************/
            $camOperation = new CamOperation();
			$count=0;
			if (isset ($OperationCenterConfig['CAM']))
				{
                foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)             /* das sind die Capture Dateien, die häufen sich natürlich wenn mehr Bewegung ist */
                    {
                    if (isset ($cam_config['FTPFOLDER']))         
                        {
                        if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                            {                        
                            echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
                            $cam_config['CAMNAME']=$cam_name;
                            if ( (isset($cam_config["MOVECAMFILES"])) && ($cam_config["MOVECAMFILES"]) ) $count+=$LogFileHandler->MoveCamFiles($cam_config);
			                else IPSLogger_Err(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam FTP Dateien wegraeumen nicht aktiviert, kann zum Speicherüberlauf führen.");
                            if ( (isset($cam_config["PURGECAMFILES"])) && ($cam_config["PURGECAMFILES"]) ) $OperationCenter->PurgeCamFiles($cam_config);
			                else IPSLogger_Err(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam FTP Verzeichnisse loeschen nicht aktiviert, kann zum Speicherüberlauf führen.");
                            }
                        }
                    }

				/* Die Snapshots der IPS Cam Kameras auf einen Bildschirm bringen, kann auch Modul Webcamera übernehmen */	
				//$OperationCenter->copyCamSnapshots();
                if (isset ($installedModules["WebCamera"]))
                    {
                    $webCamera = new webCamera();       // eigene class starten
                    $camConfig = $webCamera->getStillPicsConfiguration();

                    /* die wichtigsten Capture Files auf einen Bildschirm je lokaler Kamera bringen */
                    $camOperation->showCamCaptureFiles($camConfig);

                    $camOperation->showCamSnapshots($camConfig);	            // sonst werden die Objekte der IPSCam verwendet, sind viel weniger
                    }
				} /* Ende isset */
			if ($count>0)
				{
				IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Webcam zusammengeraeumt, ".$count." Fotos verschoben.");
				}
			break;
		case $tim3ID:       // Routerdaten empfangen, auswerten
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Routerdaten empfangen, auswerten. ScriptcountID:".GetValue($ScriptCounterID));

			/******************************************************************************************
			 *
			 * Router Auswertung, zuerst Imacro und danach die Files auswerten, Schritt für Schritt
			 * Wird nur von tim1 gestartet und arbeitet das vom Router heruntergeladene File ab
			 *
			 *********************************************************************************************/
			
			$counter=GetValue($ScriptCounterID);
			switch ($counter)
				{
				case 3:
					/* reserviert für Nachbearbeitung */
		      		SetValue($ScriptCounterID,0);
			      	IPS_SetEventActive($tim3ID,false);
		      		break;
				case 2:
					/* Router Auswertung */
			   		foreach ($OperationCenterConfig['ROUTER'] as $router)
		   				{
						/********************************************************
   						Auswertung Router MR3420 mit imacro
						**********************************************************/
					   	echo "Ergebnisse vom Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
						if ($router['TYP']=='MR3420')
							{
							$OperationCenter->write_routerdata_MR3420($router);
							}
						/* die anderen Router werden direkt abgefragt, keine nachgelagerte Auswertung notwendig */
				   		}
					SetValue($ScriptCounterID,$counter+1);
		      		break;
				case 1:
					/* Zeit gewinnen */
			      	SetValue($ScriptCounterID,$counter+1);
					break;
			   	case 0:
			 	default:
				   	break;
			   	}
			break;
			
		case $tim4ID:       // SysPingAllDevices
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." SysPingAllDevices called");
			/********************************************************
			 *
			 * Alle 5 bzw. 60 Minuten: Sys_Ping durchführen basierend auf ermittelter mactable, 
             * für die Verfügbarkeit wird größere Auflösung gefahren
			 *
			 **********************************************************/
            $OperationCenter->count5mins();
            $homematicOperation  = new HomematicOperation();
            $homematicOperation->ccuSocketStatus($log_OperationCenter);         // called every hour, internal controled by variable in count5mins
            $homematicOperation->ccuSocketDutyCycle($log_OperationCenter);      // called every hour, internal controled by variable in count5mins
            $pingOperation->SysPingAllDevices($log_OperationCenter);            // called every 5mins, hour and 4 hour,  internal controled by variable in count5mins

            //echo "SwitchBot Update per Instance: \n";
            $categoryId_PullFunction	= IPS_GetObjectIDByName('Pull',   $CategoryIdData);
            $ConfigPullId				= IPS_GetObjectIDByName("ConfigPull", $categoryId_PullFunction); 
            $LastTimePullId				= IPS_GetObjectIDByName("LastTimePull", $categoryId_PullFunction); 
            //echo "Pull ID           :  $ConfigPullId   \n";
            //echo "LastTime Pull ID  :  $LastTimePullId \n";
            if ($LastTimePullId && $ConfigPullId)
                {
                $switchBotIDs=json_decode(GetValue($ConfigPullId),true);
                //print_R($switchBotIDs);
                $i=0;
                foreach ($switchBotIDs as $id => $name)
                    {
                    SWB_DeviceStatus($id);
                    $i++;
                    }
                //echo "SwitchBot Device ($i) Values updated. \n";
                SetValue($LastTimePullId,time());                       
                }
			break;
		case $tim5ID:       // CyclicUpdate
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." CyclicUpdate");
			/************************************************************************************
	   	     *
			 * Einmal am 12.Tag des Monates: CyclicUpdate, alle Module automatisch updaten
			 *
			 *************************************************************************************/
			CyclicUpdate();
			break;
		case $tim6ID:       // CopyScriptsTimer
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." CopyScriptsTimer");
			/************************************************************************************
	   		 *
			 * Alle Scripts auf ein Dropboxverzeichnis kopieren und wenn notwendig umbenennen
			 * Timer einmal am Tag
			 *
			 *************************************************************************************/
			$OperationCenter->CopyScripts();
			break;
		case $tim7ID:       // FileStatusTimer
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." FileStatusTimer");
			/************************************************************************************
 			 *
			 * StatusInformation von sendstatus auf ein Dropboxverzeichnis kopieren
	   		 * Timer einmal am Tag um 3:50
	   		 *
			 *************************************************************************************/
			$OperationCenter->FileStatus();
			if (isset ($installedModules["Amis"]))
				{
				/* html Tabellen der Energieregister und Historien ebenfalls updaten */
				IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
				IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
				$amis=new Amis();
				$MeterConfig = $amis->getMeterConfig();
				$dataOID=$amis->getAMISDataOids();
				$tableID = CreateVariableByName($dataOID, "Historie-Energie", 3);
				$regID = CreateVariableByName($dataOID, "Aktuelle-Energie", 3);
				$Meter=$amis->writeEnergyRegistertoArray($MeterConfig);
				SetValue($tableID,$amis->writeEnergyRegisterTabletoString($Meter));
				SetValue($regID,$amis->writeEnergyRegisterValuestoString($Meter));		
				}
            $sysOps->getProcessListFull($fileRead);                 // um 00:50 aufrufen und um um 3:50 auswerten
			break;
		case $tim8ID:       // FileStatusTimer
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." FileStatusTimer");
			/************************************************************************************
 			 *
			 * System Information von sysinfo auswerten
	   		 * Timer einmal am Tag um 00:50
	   		 *
			 *************************************************************************************/
            if (isset ($installedModules["Watchdog"])===false)          // nicht installiert
                {
                $sysOps->ExecuteUserCommand($filename,"", false, false,-1,false);                          // false nix anzeigen  false nix warten, da Batch writing wäre das ausreichend
                }
			else $OperationCenter->SystemInfo();                            // bei den LBG und BKS einmal so lassen
			break;		
		case $tim9ID:       // Homematic RSSI auslesen
			/************************************************************************************
 			 *
			 * Timer Homematic, einmal am Tag
			 * Timer einmal am Tag um 02:40
			 * Es werden die wichtigsten Homematic Geraete mit Kanal 0 angelegt. Passiert in Install.
			 * Wenn Der Schalter im Webfront auf An gestellt ist werden hier die RSSI Werte ausgelesen 
             * und die RSSI Tabelle upgedaten. Abhängig von der Größe der Tabelle kann es in den nächsten 
             * Stunden zu einem DUTY_CYCLE Alarm kommen. Nur testweise einschalten !   
			 * 
			 *************************************************************************************/	
    		IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Homematic RSSI auslesen");
			IPSUtils_Include ("Homematic_Library.class.php","IPSLibrary::app::modules::OperationCenter");
   			$homematicManager = new Homematic_OperationCenter();
	    	$CategoryIdHomematicErreichbarkeit = CreateCategoryPath('Program.IPSLibrary.data.modules.OperationCenter.HomematicRSSI');
            if ($ExecuteRefreshRSSI)
                {
    		    $HomematicErreichbarkeit = CreateVariable("ErreichbarkeitHomematic",   3 /*String*/,  $CategoryIdHomematicErreichbarkeit, 50 , '~HTMLBox');	
	    		$str=$homematicManager->RefreshRSSI();
    			SetValue($HomematicErreichbarkeit,$str);						 	
	    		$UpdateErreichbarkeit = CreateVariable("UpdateErreichbarkeit",   1 /*String*/,  $CategoryIdHomematicErreichbarkeit, 500 , '~UnixTimestamp');
		    	SetValue($UpdateErreichbarkeit,time());
                }
		    //$OperationCenter->getHomematicDeviceList();	// wrong reference to Class
            $DeviceManager->getHomematicDeviceList();  		
			break;		
		case $tim10ID:      // Maintenance
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Maintenance");
			/************************************************************************************
 			 *
			 * Maintenance Modi
			 * Timer "Maintenance" einmal am Tag um 01:20, schaltet derzeit nur Timer11 ein, damit dieser zyklisch abarbeitet
	  		 *
			 *************************************************************************************/	

            $oc_setup=$BackupCenter->getSetup()["BACKUP"];                // direkter Zugriff auf Parent variablen sollte vermieden werden
            if ( (isset($oc_setup["FULL"])) && (count($oc_setup["FULL"])>0) )
                {
                $full=false;
                echo "Die nächsten Wochentage : \n";
                for ($i=0; $i < 7; $i++)
                    {
                    $weekday = date("D", time()+60*60*24*$i);    
                    if (in_array($weekday, $oc_setup["FULL"])) 
                        {
                        $full=true;
                        $style="full"; 
                        }
                    else $style="increment";
                    echo "    $weekday => $style \n";
                    }
                echo "\n";            
                }
            if ( ($full==false) || (in_array(date("D"), $oc_setup["FULL"])) ) $style="full";
            else $style="increment";
            $BackupCenter->startBackup($style); 
            $BackupCenter->cleanToken();                    
            $BackupCenter->setBackupStatus("Backup $style, automatically started ".date("d.m.Y H:i:s"));     
			IPS_SetEventActive($tim11ID,true);	
			break;		
		case $tim11ID:      // Maintenance Intervall, Logdateien zusammenräumen
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Maintenance Intervall, Logdateien zusammenräumen");    // Dbg is less than inf
			/************************************************************************************
 			 *
			 * Log Dateien zusammenräumen, alle 150 Sekunden, bis fertig, von Timer 10 gestartet
			 * am Ende auch noch alte Statusdateien in der Dropbox loeschen
			 *
			 *************************************************************************************/	
			$countlog=0;
			if (isset($OperationCenter->oc_Setup['CONFIG']['MOVELOGS'])==true) if ($OperationCenter->oc_Setup['CONFIG']['MOVELOGS']==true) $countlog=$LogFileHandler->MoveFiles(IPS_GetKernelDir().'logs',2);
			if ($countlog == 100)
				{
				IPSLogger_Inf(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, ".$countlog." Dateien verschoben. Es gibt noch mehr.");				
				}
			elseif ($countlog>0)
				{
				IPSLogger_Inf(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, restliche ".$countlog." Dateien verschoben.");
				}
			elseif ( ($BackupCenter->getBackupSwitch()>0) && ($BackupCenter->checkToken()=="free") )
                {
                /* hier ist Platz für regelmaessige, laufende Backup Aktivitäten. Im Hintergrund laufen lassen 
                    *
                    * nur abarbeiten wenn die Logs bereits verraeumt sind und die Backup Funktion im Webfront eingeschaltet ist 
                    */
                $BackupDrive=$BackupCenter->getBackupDrive();
                $BackupDrive = $BackupCenter->dosOps->correctDirName($BackupDrive);                    
                $params=$BackupCenter->getConfigurationStatus("array");
                switch ( $BackupCenter->getMode() )
                    {
                    case "backup":      /* ohne das das Backup fertig oder gestoppt ist einfach weitermachen mit dem Backup */ 
                        IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Backup wird durchgeführt.");

                        /* update Targets for successfull Backup */
                        $BackupCenter->readSourceDirs($params,$result);    
                        $params["sizeTarget"]=$params["size"]; 
                        $params["countTarget"]=$params["count"];        
                        $params["size"]=0;  $params["count"]=0;  $params["copied"]=0;
                        
                        $log=array();
                        $BackupCenter->BackupDirs($log, $params);

                        //$BackupCenter->setBackupStatus(date("Y:m:d H:i:s"));
                        $ausdrucken="Status : ".$params["status"]." , aktuell ".$params["copied"]." von ".$params["count"]." nach ".$params["BackupTargetDir"]." kopiert ".date("d.m.Y H:i:s");
                        $BackupCenter->setBackupStatus($ausdrucken);
                        echo "Status \"$ausdrucken\"\n";                            
                        IPSLogger_Inf(__file__, "TimerEvent from ".$_IPS['EVENT']." Backup wird durchgeführt: $ausdrucken");
                        //$BackupCenter->setBackupStatus("Status : ".$params["status"]." , aktuell ".$params["copied"]." von ".$params["count"]." nach ".$params["BackupTargetDir"]." kopiert ".date("Y:m:d H:i:s"));
                        echo "Zum Vergleich Size ".$params["size"]." und Count ".$params["count"]." mit ".$params["sizeTarget"]." und ".$params["countTarget"].".\n";

                        $BackupCenter->setConfigurationStatus($params,"array"); 

                        if ( ($params["size"]==$params["sizeTarget"]) && ($params["count"]==$params["countTarget"]) )
                            {
                            $BackupCenter->writeBackupLogStatus($log);  
                            if ($backupScriptId !== false)
                                {
                                IPS_RunScriptEx($backupScriptId, array());
                                }
                            else
                                {
                                echo "Backup.csv updaten.\n";
                                $result=$BackupCenter->getBackupDirectoryStatus("update");
                                echo "SummaryofBackup.csv updaten.\n";  
                                $BackupCenter->updateSummaryofBackupFile();                                                                                              
                                }
                            }
                        break;
                    case "cleanup": /* cleanup, finished oder stopped, es ist Zeit für Wartungsarbeiten. Das Backup.csv wird automatisch neu erstellt. */
                        IPSLogger_Inf(__file__, "TimerEvent from ".$_IPS['EVENT']." Backup, Cleanup wird durchgeführt.");
                        if ($params["status"] == "cleanup")
                            {
                            $BackupCenter->deleteBackupStatusError();
                            $deleteCsvFiles=$BackupCenter->cleanupBackupLogTable();
                            //print_r($deleteCsvFiles);
                            foreach ($deleteCsvFiles as $file)
                                {
                                //echo "Loesche csv Datei $file\n";
                                unlink($file);
                                }                            
                            $BackupCenter->setBackupStatus("Status, getBackupDirectoryStatus : ".$params["status"]."  ".date("d.m.Y H:i:s"));                                 
                            $params["status"]="cleanup-read";
                            $BackupCenter->setConfigurationStatus($params,"array");                                
                            }    
                        elseif ($params["status"] == "cleanup-read")
                            {  
                            /* solange im cleanup bleiben bis finished oder stopped */
                            IPSLogger_Inf(__file__, "TimerEvent from ".$_IPS['EVENT']." Cleanup vom Backup wird durchgeführt.");
                            $result=$BackupCenter->getBackupDirectoryStatus("reload");			// Backup.csv neu erstellen, result ist params
                            $BackupCenter->setBackupStatus("Status, getBackupDirectoryStatus : ".$params["status"]."  ".date("d.m.Y H:i:s"));   
                            }
                        break;
                    }
                $BackupCenter->writeTableStatus($params);            // ohne Parameter wird das html automatisch geschrieben
                $BackupCenter->setExecTime((microtime(true)-$startexec),2);           // Zahl übergeben, optional die Anzahl Stellen zum Runden
                $BackupCenter->cleanToken();
                }   // ende if backupCenter active
            else      // Timer nicht ausschalten, bis alle Funktione getestet wurden
                {		// erst wenn Backup auch fertig mit der Fertigstellungsmeldung kommen
                IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei bereits zusammengeraeumt.");	
                $countdir=$OperationCenter->PurgeFiles();
                IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Logdatei zusammengeraeumt, ".$countdir." alte Verzeichnisse geloescht.");
                $countdelstatus=$OperationCenter->FileStatusDelete();	
                IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Dropbox Statusdateien zusammengeraeumt, ".$countdelstatus." alte Dateien geloescht.");

                IPS_SetEventActive($tim11ID,false);
                }
			break;
		/* case $tim12ID:			// High Speed Polling, alle 10 Sekunden
             * benötigt ein fehlendes STATUS oder ein STATUS mit ACTIVE und ein READMODE mit SNMP
             * im data gibt es ein Kategorie mit Router_RouterName, und eine Variable SnmpFastPoll
             * wenn nicht ebenfalls Bearbeitung abbrechen, unter der Variable SnmpFastPoll gibt es zwei Variablen ifTable, SNMP Fast Poll
             * wenn die Variablen nicht vorhanden sind oder SNMP Fast Poll nicht auf true steht Abbruch
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
			break;  */
		case $tim13ID:          // Maintenance EndofDay Intervall, Backup Dateien zusammenräumen, CleanUp
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Maintenance EndofDay Intervall, Backup Dateien zusammenräumen, CleanUp");    // Dbg is less than inf
            /* jeden Abend die Backup Verzeichnisse zusammenräumen */
            $BackupCenter->configBackup(["status" => "cleanup"]);
            $BackupCenter->configBackup(["cleanup" => "started"]);
            $BackupCenter->setBackupStatus("Cleanup ".date("d.m.Y H:i:s"));                     
			IPS_SetEventActive($tim11ID,true);	
            break;
		case $tim14ID:          // Update Status tbd     
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Update Status");    // Dbg is less than inf
            break;
		default:                // unbekannt
			IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
		   break;
		}
	}

    if ($debug) echo "\nDurchlaufzeit : ".(microtime(true)-$startexec)." Sekunden\n";


	
?>