<?

//Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_AlexaClass.inc.php");

IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung");
IPSUtils_Include ("Autosteuerung_AlexaClass.inc.php","IPSLibrary::app::modules::Autosteuerung");
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
    $installedModules 	= $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');

	if ( isset($installedModules["DetectMovement"]) === true )
		{
        //echo "Module DetectMovement ist installiert.\n";
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        }

	$categoryId_Ansteuerung       = @IPS_GetObjectIDByIdent("Ansteuerung", $CategoryIdData);            // Unterfunktionen wie Stromheizung, Anwesenheitsberechnung sind hier
    $MonitorModeID                = @IPS_GetObjectIDByName("MonitorMode", $categoryId_Ansteuerung);
    if ($MonitorModeID)
        {
        $SchalterMonitorID            = IPS_GetObjectIDByName("SchalterMonitor", $MonitorModeID);
	    $StatusMonitorID              = IPS_GetObjectIDByName("StatusMonitor",$MonitorModeID);
        }
    $SilentModeID                = @IPS_GetObjectIDByName("SilentMode", $categoryId_Ansteuerung);
    if ($SilentModeID)
        {
        $PushSoundID            = IPS_GetObjectIDByName("PushSound", $SilentModeID);
        }

    //echo "gefunden: $MonitorModeID $SchalterMonitorID\n";
    $debug=true;
    $operate=new AutosteuerungOperator($debug);    
    $auto=new Autosteuerung();
    $ergebnisTyp=false;

    $AutoSetSwitches = Autosteuerung_SetSwitches();
    if (isset($AutoSetSwitches["MonitorMode"]["NAME"])) 
        {
        $monitorId = @IPS_GetObjectIDByName($AutoSetSwitches["MonitorMode"]["NAME"],$categoryId_Ansteuerung);
        if ($monitorId) 
            {
            $MonConfig=GetValue($monitorId);        // Status MonitorMode in Zahlen
            //echo "modul Internal abarbeiten, Werte in ".$categoryId_Ansteuerung." Name : ".$AutoSetSwitches["MonitorMode"]["NAME"].":  $monitorId hat ".GetValueIfFormatted($monitorId)."  \n";
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

    if (isset($installedModules["OperationCenter"]))
        {
        $moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
        $CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');


        $categoryId_Autosteuerung 		= IPS_GetObjectIDByIdent('Autosteuerung',   $CategoryIdDataOC);
        $TableEventsAS_ID				= IPS_GetObjectIDByIdent("TableEvents", $categoryId_Autosteuerung);
        $SchalterSortAS_ID				= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_Autosteuerung);

        $categoryId_AutosteuerungAlexa 	= IPS_GetObjectIDByIdent('Alexa',   $CategoryIdDataOC);
        $TableEventsAlexa_ID			= IPS_GetObjectIDByName("TableEvents",$categoryId_AutosteuerungAlexa);
        $SchalterSortAlexa_ID			= IPS_GetObjectIDByName("Tabelle sortieren",$categoryId_AutosteuerungAlexa);	

        $categoryId_DeviceManagement    = IPS_GetObjectIDByIdent('DeviceManagement',   $CategoryIdDataOC);
        $TableEventsDevMan_ID			= IPS_GetObjectIDByName("TableEvents", $categoryId_DeviceManagement);
        $SchalterSortDevMan_ID			= IPS_GetObjectIDByName("Tabelle sortieren", $categoryId_DeviceManagement);
        }
    else 
        {
        $SchalterSortAS_ID="unknown";
        $SchalterSortAlexa_ID="unknown";
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
 ***************************************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
	$debug=false;	// keine Echo Ausgaben
	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	switch ($_IPS['VARIABLE'])
		{
		case $SchalterSortAS_ID:
			/* Tabelle updaten wenn die Taste gedrueckt wird */
            if ( isset($installedModules["DetectMovement"]) === true )
                {            
                $detectMovement = new TestMovement($debug);
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
		case $SchalterSortAlexa_ID:
			$Alexa = new AutosteuerungAlexaHandler();
			$alexaConfiguration=$Alexa->getAlexaConfig();
			$table = $Alexa->writeAlexaConfig($alexaConfiguration,"",true);	// html Ausgabe
			SetValue($TableEventsAlexa_ID,$table);			
			break;
        case $MonitorModeID:                // immer hier
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
            $state=GetValue($SchalterMonitorID);
            $auto->switchByTypeModule($ergebnisTyp,$state, false);         // true für Debug
            SetValue($StatusMonitorID,$state);                  // sollte auch den Änderungsdienst zum Zuletzt Wert machen
            break;
        case $PushSoundID:
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
    print_r($alexaConfiguration);
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
        $autosteuerung_config=Autosteuerung_GetEventConfiguration();
        $eventlist=$detectMovement->getAutoEventListTable($autosteuerung_config);
        echo "Ergebnis der Analyse der Autosteuerungs Events wird in der Tabelle gespeichert.\n";
        //print_r($eventlist);
        $html=$detectMovement->writeEventlistTable($eventlist);
        SetValue($TableEventsAS_ID,$html);
        }


	
	}								

?>