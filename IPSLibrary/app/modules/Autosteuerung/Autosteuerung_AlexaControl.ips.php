<?

/****************************************************************************************
*
* Modul Autosteuerung,Script Autosteuerung_AlexaControl Funktion Spezialroutinen für Alexa Control
*
* Ansteuerung von Switches, Groups und Programmen. Alexa ändert die angegebene Variable mit dem Sender VoiceControl
* Eine Variable kann bei einer Änderung ein Script aufrufen. Für alle Stromheizungsvariablen ist das IPSHeat_ActionScript.
* das heisst es wird weder das Script Autosteuerung oder Autosteuerung_AlexaControl aufgerufen.
*
* Für Variablen ausserhalb von Stromheizung und für die Proxyfunktion ist es möglich als VoiceControl das Script Autosteuerung oder Autosteuerung_AlexaControl zu definieren.
*
* Befehle die Alexa aktuell unterstützt:
*
* PowerController			Alexa, schalte Smart Home-Gerät ein/aus
* PowerLevelController		Alexa set the power to 40% on device, Alexa stelle Smart Home gerät auf 40 Prozent.
* ThermostatController
*							Alexa Schalte das Licht auf Blau.
*							Alexa, set the AC to 25 degrees for 4 hours.
*							Alexa, make it warmer in here until 10pm
* DeviceTemperatureSensor
*                           Alexa wie ist die Temperatur im Wohnzimmer/Wohnung
*     es reicht wenn die Variable in der ALexa Konfiguration Wohnung Arbeitszimmer etc. heisst
*
* im Modul implementierte Funktionen:
*
    private $switchFunctions = Array("turnOn", "turnOff");
    private $dimmingFunctions = Array("setPercentage", "incrementPercentage", "decrementPercentage");
    private $targetTemperatureFunctions = Array("setTargetTemperature", "incrementTargetTemperature", "decrementTargetTemperature", "getTargetTemperature");
    private $readingTemperatureFunctions = Array("getTemperatureReading");
    private $rgbColorFunctions = Array("SetColor");
    private $rgbTemeratureFunctions = Array("SetColorTemperature", "IncrementColorTemperature", "DecrementColorTemperature");
*
*******************************************************************************************/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_AlexaClass.inc.php");

/*********************************************************************************************/

include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************

				INIT

*************************************************************/

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager)) 
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
        }

    $installedModules = $moduleManager->GetInstalledModules();

    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_Ansteuerung       = IPS_GetObjectIDByIdent("Ansteuerung", $CategoryIdData);            // Unterfunktionen wie Stromheizung, Anwesenheitsberechnung sind hier
    $MonitorModeID                = IPS_GetObjectIDByName("MonitorMode", $categoryId_Ansteuerung);
    $SchalterMonitorID            = IPS_GetObjectIDByName("SchalterMonitor", $MonitorModeID);
	$StatusMonitorID              = IPS_GetObjectIDByName("StatusMonitor",$MonitorModeID);

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

$configurationAutosteuerung = Autosteuerung_Setup();

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
$scriptIdAlexaControl    = IPS_GetScriptIDByName('Autosteuerung_AlexaControl', $CategoryIdApp);

$nachrichten=new AutosteuerungAlexa();
$Alexa = new AutosteuerungAlexaHandler();
$register=new AutosteuerungConfigurationAlexa($scriptIdAlexaControl);

/******************************************************
 *
 *				Abarbeiten nach SENDER 
 *
 *	Default 
 * 	VoiceControl
 *	RunScript
 *	Execute
 *	TimerEvent
 *	Variable
 *	AlexaSmartHome
 *
 *************************************************************/

Switch ($_IPS['SENDER'])
	{
	Default:
		if (isset($_IPS['REQUEST'])) IPSLogger_Dbg(__file__,"Aufruf unbekannt, Alexa empfaengt : Variable ".$_IPS['VARIABLE']."  Request ".$_IPS['REQUEST']."  Wert ".$_IPS['VALUE']);
		else IPSLogger_Dbg(__file__,"Aufruf unbekannt, Alexa empfaengt : Variable ".$_IPS['VARIABLE']." Sender ".$_IPS['SENDER']."  Wert ".$_IPS['VALUE']);
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
		break;
	Case "VoiceControl":
 		IPSLogger_Inf(__file__,"Alexa empfaengt VoiceControl");
		//$nachrichten->LogNachrichten("Alexa empfaengt von VoiceControl : ".$_IPS['VARIABLE']." ".$_IPS['SENDER']."  ".$_IPS['VALUE']." .");
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
    	$variableID=$_IPS['VARIABLE'];
		$alexaConfig=$Alexa->getAlexaConfig();
		$request=array();
		if ( isset ($alexaConfig[$variableID]) ) 
			{	// Nachricht kommt von Alexa und die Variable ist auch ein gültiger Konfigurationseintrag
			$request["REQUEST"]= $alexaConfig[$variableID]["Type"];
			if ( isset($configurationAutosteuerung["AlexaProxyAdr"])==true)
				{
		 		IPSLogger_Inf(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);

				/************** Daten zur Verarbeitung weiterleiten an einen anderen Server */
				switch ($alexaConfig[$variableID]['Type'])
					{
					case "DeviceGenericSwitch":
					case "DeviceLightSwitch":
					case "DeviceDeactivatableScene":
					case "DeviceSimpleScene":
					case "DeviceGenericSlider":
					case "DeviceLightColor":
					case "DeviceLock":
					case "DeviceTemperatureSensor":
						$nachrichten->LogNachrichten("VoiceControl : ".$alexaConfig[$variableID]["Name"]." (".$variableID.") mit Wert ".($_IPS['VALUE']?"Ein":"Aus")." und Typ ".$request["REQUEST"]." und ");
						$nachrichten->LogNachrichten("  leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"].".");	
		 				IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$variableID."  von  ".$_IPS['SENDER']." mit Wert  ".($_IPS['VALUE']?"Ein":"Aus")." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"]);			
						break;					
					case "DeviceThermostat":
						$nachrichten->LogNachrichten("VoiceControl : ".$alexaConfig[$variableID]["Name"]." (".$variableID.") mit Wert ".$_IPS['VALUE']." und Typ ".$request["REQUEST"]." und ");
						$nachrichten->LogNachrichten("  leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"].".");					
		 				IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$variableID."  von  ".$_IPS['SENDER']." mit Wert  ".($_IPS['VALUE']?"Ein":"Aus")." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"]);			
						break;					
					default:
						break;
					}
				$params=$register->getAutoEvent($variableID);
				if ($params === false) 
					{
					$register->registerAutoEvent($variableID, "OnUpdate",$alexaConfig[$variableID]['Type'], "");			/* OnUpdate muss an erster Position stehen */
					$nachrichten->LogNachrichten("VoiceControl : ".$variableID." remote OID nicht bekannt, bitte in Config eintragen.");					
					}
				else
					{
					$nachrichten->LogNachrichten("VoiceControl : ".$params[0]."  ".$params[1]."   ".$params[2]."  ");
					// es gibt jetzt ein Configurations Array das wie bei Autosteuerung strukturiert ist
                    //$variableID=(Integer)$params[2];				
                    $variableID=$params[2];     // es wird ein Befehl oder ein Identifier übertragen
					}										
				$request["VARIABLE"]=	$variableID;
				$request["VALUE"]=		$_IPS['VALUE'];
				$request["MODULE"]=	"VoiceControl";
				if (function_exists("proxyAlexa") == true) proxyAlexa($request);
				}
			else
				{	
				/************* Daten direkt hier verarbeiten */
				//$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']);
		 		IPSLogger_Inf(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
				$request=array();
				$request["VARIABLE"]=	$_IPS['VARIABLE'];
				$request["VALUE"]=		$_IPS['VALUE'];
				$request["SENDER"]=		$_IPS['SENDER'];
				executeAlexa($request);			/* Alexa Event registrierung erfolgt innerhalt der Funktion */
				}
			}						
		break;
	Case "RunScript":
		$request=array();
		if (isset($_IPS['MODULE'])) 
			{
		    IPSLogger_Inf(__file__,"Extern VoiceControl Request (RunScript mit Module ".$_IPS['MODULE']." und Request ".$_IPS['REQUEST'].") mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus"));	                
			switch ($_IPS['REQUEST'])
				{
				case "DeviceGenericSwitch":
				case "DeviceLightSwitch":
				case "DeviceDeactivatableScene":
				case "DeviceSimpleScene":
				case "DeviceGenericSlider":
				case "DeviceLightColor":
				case "DeviceLock":
				case "DeviceTemperatureSensor":
					$nachrichten->LogNachrichten("Extern VoiceControl Request ".$_IPS['REQUEST']." mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus")." .");
					IPSLogger_Dbg(__file__,"Extern VoiceControl Request mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus"));					
					break;					
				case "DeviceThermostat":
					$nachrichten->LogNachrichten("Extern VoiceControl Request ".$_IPS['REQUEST']." mit Variable ".$_IPS['VARIABLE']." und Wert ".$_IPS['VALUE']." .");
					IPSLogger_Dbg(__file__,"Extern VoiceControl Request mit Variable ".$_IPS['VARIABLE']." und Wert ".$_IPS['VALUE']);				
					break;					
				default:
					break;
				}					
			$request["REQUEST"]=	$_IPS['REQUEST'];
			}
		else 
			{
  		    IPSLogger_Inf(__file__,"Extern VoiceControl Request (RunScript) mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus"));	                
			$nachrichten->LogNachrichten("Extern Alexa ".$_IPS['SENDER']." empfängt : ".$_IPS['VARIABLE']."  ".$_IPS['REQUEST']."  ".($_IPS['VALUE']?"Ein":"Aus")." .");
			//IPSLogger_Dbg(__file__,"Extern Alexa RunScript empfaengt : ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".($_IPS['VALUE']?"Ein":"Aus"));
			$request["REQUEST"]=	$_IPS['REQUEST'];			
			}
		$request["VARIABLE"]=	$_IPS['VARIABLE'];
		$request["VALUE"]=		$_IPS['VALUE'];
		executeAlexa($request);
		break;
	Case "Execute":
        echo "--------------------------------------------------------\n";
        echo "AlexaControl Execute aufgerufen:\n";
		$nachrichten->LogNachrichten("AlexaControl Execute aufgerufen.");
        $count=$Alexa->getCountInstances();
        if ($count>0) echo "Alexa lokale Instanzen, StatusCount = ".$Alexa->getCountInstances().": ";
        elseif ($count<0) 
            {
            echo "Alexa Remote Instanzen, StatusCount = ".$Alexa->getCountInstances().": \n";
    	    echo "   Alexa Konfiguration: ".$Alexa->getConfigAlexa()."\n";
            }
	    foreach ($Alexa->getInstances() as $oid) echo "   $oid   ";
		echo "\n";
    	echo "Alexa Configuration:\n";
    	$alexaConfiguration=$Alexa->getAlexaConfig(true);
    	print_r($alexaConfiguration);			
		$request=array();
		//$request["VARIABLE"]=	"649f57f3-0e76-4d0c-83d2-63b0cc03965e";
		$request["VARIABLE"]=20093;
		$request["VALUE"]=		true;
		//$request["REQUEST"]=	"TurnOnRequest";
		//executeAlexa($request);
		test_execute($request);             // die eigentliche Execute Routine
		echo $nachrichten->PrintNachrichten();
	 	break;
	Case "TimerEvent":
		break;
	Case "Variable":
	Case "AlexaSmartHome":
		if ( isset($configurationAutosteuerung["AlexaProxyAdr"])==true)
			{
			/* Daten zur Verarbeitung weiterleiten an einen anderen Server */
		 	IPSLogger_Inf(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."    ".$_IPS['REQUEST']."   ".$_IPS['VALUE']." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"]);			
			$request=array();
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["REQUEST"]=	$_IPS['REQUEST'];
			if (function_exists("proxyAlexa") == true) proxyAlexa($request);
			}
		else
			{	
			//$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']);
		 	IPSLogger_Inf(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
			$request=array();
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["REQUEST"]=	$_IPS['REQUEST'];
			executeAlexa($request);
			}
		break;
	Case "WebFront":        // Zum schalten im Webfront
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);	
        switch ($_IPS['VARIABLE'])
            {
            case $SchalterMonitorID:            // nur hier wenn Alexa nicht installiert ist
                //echo "monitor control ".GetValueIfFormatted($MonitorModeID);
                $state=GetValue($MonitorModeID);
                if ( ($state<2) && ($ergebnisTyp !== false) ) 
                    {
                    $auto->switchByTypeModule($ergebnisTyp,$state, false);         // true für Debug
                    SetValue($SchalterMonitorID,$state);
                    SetValue($StatusMonitorID,$state);              // sollte auch den Änderungsdienst zum Zuletzt Wert machen
                    }
                break;
            default:
                break;
            }
		break;
    } 

/*********************************************************************************************/

function executeAlexa($request)
	{
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

	$alexaHandler = new AutosteuerungAlexaHandler(); 
	$nachrichten=new AutosteuerungAlexa();
	
	$register=new AutosteuerungConfigurationAlexa($scriptIdAutosteuerung);
	$params=$register->getAutoEvent($request['VARIABLE']);
	if ($params === false) 
		{
		$register->registerAutoEvent($request['VARIABLE'], "OnUpdate", "Alexa", "speak:nicht implementiert.");
		$nachrichten->LogNachrichten(" ".$request['VARIABLE']." ".$request['REQUEST']."  ".$request['VALUE']." Neu, registrieren.");
		$params=$register->getAutoEvent($request['VARIABLE']);		
		}
	else
		{
		$nachrichten->LogNachrichten(" ".$request['VARIABLE']." ".$request['REQUEST']."  ".$request['VALUE']." ".json_encode($params));
		}
		
	echo "AlexaControl: Evaluiere Request:  ".json_encode($request)."  ".json_encode($params)."\n";
	$ergebnis=Alexa($params,$request['VALUE'], $request['REQUEST'],false);			// true simulate
	$nachrichten->LogNachrichten(" ".json_encode($ergebnis));
	}

/*********************************************************************************************/

function test_execute($request)
	{
    echo "================================================================";
    echo "function test_execute aufgerufen:\n\n";
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

	$register=new AutosteuerungConfigurationAlexa($scriptIdAutosteuerung);
	
    $alexaHandler = new AutosteuerungAlexaHandler(); 
	$nachrichten=new AutosteuerungAlexa();
	
	$params=$register->getAutoEvent($request['VARIABLE']);
	print_r($params);

	echo "\n=====================================================================\n";
	echo "Execute aufgerufen. Analyse der einzelnen abgespeicherten Alexa Befehle aus .\n";
	$register->PrintAutoEvent(true);            // true für Debug
	$entries=$register->getAutoEvent();
	$i=0;
	echo "\n";
	foreach ($entries as $index => $entry)
		{
		//print_r($entry);
        echo "==============================================\n";
		echo "Bearbeite Eintrag : ".$index." : \n";
		Alexa($entry,1,"SETPERCENTAGE",true);
		}
		
	//$result=$register->getAutoEvent("44404b3f-5f92-40ba-a7d5-63e8a83987a4");
	//print_r($result);
	}



/*********************************************************************************************/


?>