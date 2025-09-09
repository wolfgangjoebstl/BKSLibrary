<?php

/* Webfront_Control für Autosterung
 * deckt den Rest der nicht von Autosteuerung script direkt gemacht wird.
 *
 *
 *
 */ 

//Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_AlexaClass.inc.php");

IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung");
IPSUtils_Include ("Autosteuerung_AlexaClass.inc.php","IPSLibrary::app::modules::Autosteuerung");
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

IPSUtils_Include ('DeviceManagement_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
    $installedModules 	= $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');

    if ($_IPS['SENDER']=="Execute") $debug=true;
    else $debug=false; 

	if ( isset($installedModules["DetectMovement"]) === true )
		{
        //echo "Module DetectMovement ist installiert.\n";
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        }
        
    $SchalterMonitorID = "unknown";             // Defaultwerte nicht vergessen
    $StatusMonitorID   = "unknown";
    $PushSoundID       = "unknown";

	$categoryId_Ansteuerung       = @IPS_GetObjectIDByIdent("Ansteuerung", $CategoryIdData);            // Unterfunktionen wie Stromheizung, Anwesenheitsberechnung sind hier
    $MonitorModeID                = @IPS_GetObjectIDByName("MonitorMode", $categoryId_Ansteuerung);
    if ($MonitorModeID)
        {
        $SchalterMonitorID            = IPS_GetObjectIDByName("SchalterMonitor", $MonitorModeID);
	    $StatusMonitorID              = IPS_GetObjectIDByName("StatusMonitor",$MonitorModeID);
        }
    else
        {
        $SchalterMonitorID            = "unknown";
	    $StatusMonitorID              = "unknown";
        }
    $SilentModeID                = @IPS_GetObjectIDByName("SilentMode", $categoryId_Ansteuerung);
    if ($SilentModeID)
        {
        $PushSoundID            = IPS_GetObjectIDByName("PushSound", $SilentModeID);
        }
    else $PushSoundID="unknown";

    //echo "gefunden: $MonitorModeID $SchalterMonitorID\n";

    $operate=new AutosteuerungOperator($debug);    
    $auto=new Autosteuerung();
    $ipsOps = new ipsOps();

    $CategoryIdDataOC=false;
    if (isset($installedModules["OperationCenter"]))
        {
        IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
        
        $moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
        $CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
        }

    $powerLock_ID="unknown";
    $SchalterSortAlexa_ID="unknown";
    $AutoSetSwitches = $auto->get_Autosteuerung_SetSwitches();              // allgemein für alle Module verwendet
    foreach ($AutoSetSwitches as $nameAuto => $AutoSetSwitch)
        {
        switch (strtoupper($AutoSetSwitch["TABNAME"]))          // das ist der Key, standardisiserte Namen
            {
            case "MONITORMODE":
                if (isset($AutoSetSwitch["NAME"])) 
                    {
                    $monitorId = @IPS_GetObjectIDByName($AutoSetSwitch["NAME"],$categoryId_Ansteuerung);
                    if ($monitorId) 
                        {
                        $MonConfig=GetValue($monitorId);        // Status MonitorMode in Zahlen
                        //echo "modul Internal abarbeiten, Werte in ".$categoryId_Ansteuerung." Name : ".$AutoSetSwitch["NAME"].":  $monitorId hat ".GetValueIfFormatted($monitorId)."  \n";
                        $monConfigFomat=GetValueIfFormatted($monitorId);            // Status MonitorMode formattiert
                        if (function_exists("Autosteuerung_MonitorMode")) 
                            {
                            $MonitorModeConfig=Autosteuerung_MonitorMode();
                            if (isset($MonitorModeConfig["SwitchName"]))
                                {
                                //echo "function Autosteuerung_MonitorMode existiert, es geht weiter: ".json_encode($MonitorModeConfig)."\n";
                                $result["NAME"]=$MonitorModeConfig["SwitchName"];
                                $ergebnisTyp=$auto->getIdByName($result["NAME"]);                                
                                //echo "Autosteuerung Befehl MONITOR: Switch Befehl gesetzt auf ".$result["NAME"]."   ".json_encode($ergebnisTyp)."\n";    
                                
                                }
                            }
                        }
                    }
                break;
			case "ALEXA":
                if ($CategoryIdDataOC)
                    {
                    $categoryId_AutosteuerungAlexa 	= IPS_GetObjectIDByIdent('Alexa',   $CategoryIdDataOC);
                    $TableEventsAlexa_ID			= IPS_GetObjectIDByName("TableEvents",$categoryId_AutosteuerungAlexa);
                    $SchalterSortAlexa_ID			= IPS_GetObjectIDByName("Tabelle sortieren",$categoryId_AutosteuerungAlexa);	
                    }
                break;    
            case "ALARMANLAGE":
                $AlarmanlageModeID                = @IPS_GetObjectIDByName("Alarmanlage", $categoryId_Ansteuerung);
                if ($AlarmanlageModeID)
                    {
                    // Aktuator
                    $componentHandling = new ComponentHandling();
                    if ($debug) echo "Geräte mit getComponent suchen, geht jetzt mit HardwareList und DeviceList.\n";
                    IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");
                    $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php                    
                    $resultKey=$componentHandling->getComponent($deviceList,["TYPECHAN" => "TYPE_POWERLOCK","REGISTER" => "KEYSTATE"],"Install",$debug);                        // true für Debug, bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben   
                    $countPowerLock=(sizeof($resultKey));				
                    // Status
                    $resultState=$componentHandling->getComponent($deviceList,["TYPECHAN" => "TYPE_POWERLOCK","REGISTER" => "LOCKSTATE"],"Install",$debug);                        // true für Debug, bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben   
                    $countPowerLock+=(sizeof($resultState));				
                    if ($countPowerLock>0)
                        {
                        if ($debug) echo "Es wird ein PowerLock von Homematic verwendet. Die Darstellung erfolgt unter Alarmanlage/Tab Sicherheit:\n";
                        $powerLock_ID=IPS_GetObjectIdByName("LockBuilding",$AlarmanlageModeID);
                        $alarm=new AutosteuerungAlarmanlage();
                        $config=$alarm->getPowerLockEnvironmentConfig();
                        //print_R($config);
                        if (isset($config["Count"]))
                            {
                            foreach ($config["Key"] as $index => $entry)
                                {
                                if ($debug) echo "Index $index :  ".$entry["OID"]."\n";
                                //print_r($entry);
                                $powerLockActuatorOID=$entry["OID"];    
                                }
                            }
                        }
                    }
                break;
            }
        }

    $ergebnisTyp=false;


    if ($CategoryIdDataOC)
        {
        $categoryId_Autosteuerung 		= IPS_GetObjectIDByIdent('Autosteuerung',   $CategoryIdDataOC);
        $SchalterSortAS_ID				= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_Autosteuerung);
        
        $categoryId_DeviceManagement    = IPS_GetObjectIDByIdent('DeviceManagement',   $CategoryIdDataOC);
        $TableEventsDevMan_ID			= IPS_GetObjectIDByName("TableEvents", $categoryId_DeviceManagement);
        $SchalterSortDevMan_ID			= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_DeviceManagement);
        }
    else 
        {
        $SchalterSortAS_ID="unknown";
        $SchalterSortDevMan_ID="unknown";
        }

    if (isset ($installedModules["DetectMovement"]))
        {
        $moduleManagerDM = new IPSModuleManager('DetectMovement',$repository);
        $CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
        $CategoryIdAppDM      = $moduleManagerDM->GetModuleCategoryID('app');
        $scriptId  = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdAppDM);	

        $categoryId_DetectMovement    	= IPS_GetObjectIDByIdent('DetectMovement',   $CategoryIdDataOC);
        $TableEventsDM_ID				= IPS_GetObjectIDByName("TableEvents", $categoryId_DetectMovement);
        $SchalterSortDM_ID				= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_DetectMovement);
        }
    else 
        {
        $SchalterSortDM_ID="unknown";
        }



/************************************************************************************
 *
 * Webfront Routinen, abhängig vom Sortierbefehl
 *
 *  powerLock_ID
 *
 *  SchalterSortAS_ID           Autosteuerung Tabelle
 *  SchalterSortAlexa_ID        Alexa Tabelle
 *  MonitorModeID               Ansteuerung, Monitor Mode 
 *  SchalterMonitorID           Ansteuerung, Monitor Mode
 *  PushSoundID                 Ansteuerung, Silent Mode
 *  SchalterSortDevMan_ID:      DeviceManagement Tabelle
 *  SchalterSortDM_ID:          DetectMovement Tabelle
 *
 *
 ***************************************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $value=$_IPS['VALUE'];
    SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);             // für alle Schater weiter unten in Ordnung
	switch ($_IPS['VARIABLE'])
		{
        case $powerLock_ID:
            if ($value>90)
                {
                if ($debug) echo "Lock";
                HM_WriteValueInteger ($powerLockActuatorOID, "LOCK_TARGET_LEVEL",1);                // 0 zusperren, 1 aufsperren, 2 öffnen  Achtung auch 1 kann bereits die Türe öffnen
                }
            if ($value<10) 
                {
                if ($debug) echo "Open"; 
                HM_WriteValueInteger ($powerLockActuatorOID, "LOCK_TARGET_LEVEL",0);                // 0 zusperren, 1 aufsperren, 2 öffnen  Achtung auch 1 kann bereits die Türe öffnen
                }
            SetValue($powerLock_ID,50);
            break;
		case $SchalterSortAS_ID:            			// Autosteuerungs Events, Tabelle updaten wenn die Taste gedrueckt wird 
        	//SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
            $TableEventsAS_ID				= IPS_GetObjectIDByIdent("TableEvents", $categoryId_Autosteuerung);
            if ( isset($installedModules["DetectMovement"]) === true )
                {            
                $detectMovement = new TestMovement($debug);
                $detectMovement->syncEventList($debug);       // speichert eventList und eventListDelete, früher Teil des constructs

                $autosteuerung_config=Autosteuerung_GetEventConfiguration();
                $eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config,$debug);		// no Debug
                switch ($_IPS['VALUE'])
                    {
                    case 0:
                        $html=$detectMovement->writeEventlistTable($eventlist);				
                        break;
                    case 1:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("OID",$eventlist));
                        break;
                    case 2:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Name",$eventlist));
                        break;
                    case 3:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Pfad",$eventlist));
                        break;
                    case 4:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("NameEvent",$eventlist));
                        break;
                    case 5:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Instanz",$eventlist));
                        break;
                    case 6:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Typ",$eventlist));
                        break;
                    case 7:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Config",$eventlist));
                        break;
                    case 8:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Homematic",$eventlist));
                        break;
                    case 9:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("DetectMovement",$eventlist));
                        break;
                    case 10:
                        $html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Autosteuerung",$eventlist));
                        break;
                    default;
                        break;	
                    }
                SetValue($TableEventsAS_ID,$html);
                }                				
			break;
		case $SchalterSortAlexa_ID:                 // Alexa Befehle, Tabelle updaten wenn die Taste gedrueckt wird 
        	//SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
            //echo "Alexa";
			$Alexa = new AutosteuerungAlexaHandler();
			$alexaConfiguration=$Alexa->getAlexaConfig();
            switch ($_IPS['VALUE'])
                {
                case 0:            
                    break;
                case 1:
                    $ipsOps-> intelliSort($alexaConfiguration,"Name");            
                    break;
                case 2:
                    $ipsOps-> intelliSort($alexaConfiguration,"Type");            
                    break;
                case 3:
                    $ipsOps-> intelliSort($alexaConfiguration,"Pfad");            
                    break;
                }
			$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
			SetValue($TableEventsAlexa_ID,$table);
            //echo "fertig";			
			break;
        case $MonitorModeID:                // immer hier
        	//SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
            //echo "monitor control ".GetValueIfFormatted($MonitorModeID);
            $state=GetValue($MonitorModeID);
            if ( ($state<2) && ($ergebnisTyp !== false) ) 
                {
                $auto->switchByTypeModule($ergebnisTyp,$state, false);         // true für Debug
                SetValue($SchalterMonitorID,$state);
                SetValue($StatusMonitorID,$state);              // sollte auch den Änderungsdienst zum Zuletzt Wert machen
                }
            break;
        case $SchalterMonitorID:            // kommt nur hier her, wenn Alexa nicht installiert ist
        	//SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
            $state=GetValue($SchalterMonitorID);
            $auto->switchByTypeModule($ergebnisTyp,$state, false);         // true für Debug
            SetValue($StatusMonitorID,$state);                  // sollte auch den Änderungsdienst zum Zuletzt Wert machen
            break;
        case $PushSoundID:
        	//SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
            //echo "Push Default Sound Module\n";
			if (isset($installedModules["OperationCenter"])==true)
				{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
				IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                if (GetValue($PushSoundID)) tts_play(1,'Wir testen das Soundmodul und es steht auf ein','',2);
                else tts_play(1,'Wir testen das Soundmodul und es steht auf ein','',2);
				}

            break;
        case $SchalterSortDevMan_ID:
		case $SchalterSortDM_ID:
		default:
        	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

            IPSLogger_Inf(__file__, 'Aufruf WebfrontControl Variable Change von '.$_IPS['VARIABLE']."(".IPS_GetName($_IPS['VARIABLE']).') auf Wert '.$_IPS['VALUE']);
			break;				
		}
	}

/************************************************************************************
 *
 * Execute, Testroutinen
 *
 ***************************************************************************************/
	
if ($_IPS['SENDER']=="Execute")
	{
	echo "Testweise Execute des Scripts aufgerufen.\n";
	echo "  Schalter für Sortierung der Autosteuerungs Events : $SchalterSortAS_ID.\n";
	echo "  Schalter für Sortierung der Alexa Befehle         : $SchalterSortAlexa_ID.\n";
	echo "\n";
	$debug=true;
	echo "========================================Monitor\n";
    $ergebnis=$operate->MonitorStatus($debug);


	echo "========================================Alexa\n";
    $Alexa = new AutosteuerungAlexaHandler();
    echo "Alexa Instanzen, StatusCount = ".$Alexa->getCountInstances()." : ";
    foreach ($Alexa->getInstances() as $oid) echo $oid."   ";
	echo "\n";
    echo "Alexa Configuration:\n";
    $alexaConfiguration=$Alexa->getAlexaConfig(true);               // true für Debug
    $ipsOps-> intelliSort($alexaConfiguration,"Name");
    print_r($alexaConfiguration);
	$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
    echo $table;

    echo "Ausgabe DeviceTemperature:\n";
    $filter="DeviceTemperatureSensor";
    $Alexa->writeAlexaConfig($alexaConfiguration,$filter);
    $filter="DeviceThermostat";
    $Alexa->writeAlexaConfig($alexaConfiguration,$filter,true);	
	
	$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
	echo $table;
	if ( isset($installedModules["DetectMovement"]) === true )
		{
        echo "\n";
        echo "========================================DetectMovement\n";
        $detectMovement = new TestMovement($debug);
        $detectMovement->syncEventList($debug);       // speichert eventList und eventListDelete, früher Teil des constructs

        $autosteuerung_config=Autosteuerung_GetEventConfiguration();
        $eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config);
        echo "Ergebnis der Analyse der Autosteuerungs Events wird in der Tabelle gespeichert.\n";
        //print_r($eventlist);
        $html=$detectMovement->writeEventlistTable($eventlist);
        SetValue($TableEventsAS_ID,$html);
        }


	
	}								

?>