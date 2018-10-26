<?

/****************************************************************************************
*
* Autosteuerung, Spezialroutinen für Alexa Control
*
* Befehle die >Alexa aktuell unterstützt:
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

$configurationAutosteuerung = Autosteuerung_Setup();

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

$nachrichten=new AutosteuerungAlexa();

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
		//$nachrichten->LogNachrichten("Alexa empfaengt von VoiceControl : ".$_IPS['VARIABLE']." ".$_IPS['SENDER']."  ".$_IPS['VALUE']." .");
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
    	$modulhandling = new ModuleHandling();
        $config=IPS_GetConfiguration($modulhandling->getInstances("Alexa")[0]);
        $ac=json_decode($config,true);
    
        $alexaConfig=array();
        $alexaTypes=array("DeviceDeactivatableScene","DeviceGenericSlider","DeviceGenericSwitch","DeviceLightColor","DeviceLightDimmer","DeviceLightSwitch","DeviceLock","DeviceSimpleScene","DeviceTemperatureSensor","DeviceThermostat");
        foreach($alexaTypes as $Type)
            {
			echo "Alle ".$Type." in der Konfiguration suchen.\n";
            $acdl=json_decode($ac[$Type],true);
			switch ($Type)
				{
				case "DeviceGenericSwitch":
				case "DeviceLightSwitch":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["PowerControllerID"]]["OID"]=$entry["PowerControllerID"];
						$alexaConfig[$entry["PowerControllerID"]]["Type"]=$Type;
						$alexaConfig[$entry["PowerControllerID"]]["Name"]=$entry["Name"];
                		}
					break;
				case "DeviceDeactivatableScene":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["SceneControllerDeactivatableActivateID"]]["OID"]=$entry["SceneControllerDeactivatableActivateID"];						
						$alexaConfig[$entry["SceneControllerDeactivatableActivateID"]]["Type"]=$Type;
						$alexaConfig[$entry["SceneControllerDeactivatableActivateID"]]["Name"]=$entry["Name"];
						$alexaConfig[$entry["SceneControllerDeactivatableActivateID"]]["Script"]=$entry["SceneControllerDeactivatableDeactivateID"];						
                		}
					break;
				case "DeviceSimpleScene":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["SceneControllerSimpleID"]]["OID"]=$entry["SceneControllerSimpleID"];						
						$alexaConfig[$entry["SceneControllerSimpleID"]]["Type"]=$Type;
						$alexaConfig[$entry["SceneControllerSimpleID"]]["Name"]=$entry["Name"];
                		}
					break;
				case "DeviceGenericSlider":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["PercentageControllerID"]]["OID"]=$entry["PercentageControllerID"];						
						$alexaConfig[$entry["PercentageControllerID"]]["Type"]=$Type;
						$alexaConfig[$entry["PercentageControllerID"]]["Name"]=$entry["Name"];
						}
					break;					
				case "DeviceLightColor":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["ColorControllerID"]]["OID"]=$entry["ColorControllerID"];						
						$alexaConfig[$entry["ColorControllerID"]]["Type"]=$Type;
						$alexaConfig[$entry["ColorControllerID"]]["Name"]=$entry["Name"];
						}
					break;						
				case "DeviceLock":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["ColorControllerID"]]["OID"]=$entry["ColorControllerID"];						
						$alexaConfig[$entry["ColorControllerID"]]["Type"]=$Type;
						$alexaConfig[$entry["ColorControllerID"]]["Name"]=$entry["Name"];
						}
					break;
				case "DeviceTemperatureSensor":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["TemperatureSensorID"]]["OID"]=$entry["TemperatureSensorID"];						
						$alexaConfig[$entry["TemperatureSensorID"]]["Type"]=$Type;
						$alexaConfig[$entry["TemperatureSensorID"]]["Name"]=$entry["Name"];
						}
					break;
				case "DeviceThermostat":
					foreach ($acdl as $entry) 
						{
						print_r($entry);
						$alexaConfig[$entry["ThermostatControllerID"]]["OID"]=$entry["ThermostatControllerID"];						
						$alexaConfig[$entry["ThermostatControllerID"]]["Type"]=$Type;
						$alexaConfig[$entry["ThermostatControllerID"]]["Name"]=$entry["Name"];
						}
					break;											
				default:
					break;
				}	
            }
			
		$request=array();
		if ( isset ($alexaConfig[$_IPS['VARIABLE']]) ) $request["REQUEST"]= $alexaConfig[$_IPS['VARIABLE']]["Type"];
		if ( isset($configurationAutosteuerung["AlexaProxyAdr"])==true)
			{
			/* Daten zur Verarbeitung weiterleiten an einen anderen Server */
			$nachrichten->LogNachrichten("VoiceControl : ".$_IPS['VARIABLE']." mit Wert ".($_IPS['VALUE']?"Ein":"Aus")." Typ ".$request["REQUEST"]." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"].".");
		 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."  von  ".$_IPS['SENDER']." mit Wert  ".($_IPS['VALUE']?"Ein":"Aus")." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"]);			
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["MODULE"]=	"VoiceControl";
			if (function_exists("proxyAlexa") == true) proxyAlexa($request);
			}
		else
			{	
			//$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']);
		 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
			$request=array();
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["SENDER"]=		$_IPS['SENDER'];
			executeAlexa($request);			
			}			
		break;
	Case "RunScript":
		$request=array();
		if (isset($_IPS['MODULE'])) 
			{
			$nachrichten->LogNachrichten("Extern VoiceControl Request ".$_IPS['REQUEST']."mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus")." .");
			IPSLogger_Dbg(__file__,"Extern VoiceControl Request mit Variable ".$_IPS['VARIABLE']." und Wert ".($_IPS['VALUE']?"Ein":"Aus"));
			$request["REQUEST"]=	$_IPS['REQUEST'];
			}
		else 
			{
			$nachrichten->LogNachrichten("Extern Alexa ".$_IPS['SENDER']." empfängt : ".$_IPS['VARIABLE']."  ".$_IPS['REQUEST']."  ".($_IPS['VALUE']?"Ein":"Aus")." .");
			IPSLogger_Dbg(__file__,"Extern Alexa RunScript empfaengt : ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".($_IPS['VALUE']?"Ein":"Aus"));
			$request["REQUEST"]=	$_IPS['REQUEST'];			
			}
		$request["VARIABLE"]=	$_IPS['VARIABLE'];
		$request["VALUE"]=		$_IPS['VALUE'];
		executeAlexa($request);
		break;
	Case "Execute":
		$request=array();
		//$request["VARIABLE"]=	"649f57f3-0e76-4d0c-83d2-63b0cc03965e";
		$request["VARIABLE"]=20093;
		$request["VALUE"]=		true;
		//$request["REQUEST"]=	"TurnOnRequest";
		//executeAlexa($request);
		test_execute($request);
		echo $nachrichten->PrintNachrichten();
	 	break;
	Case "TimerEvent":
		break;
	Case "Variable":
	Case "AlexaSmartHome":
		if ( isset($configurationAutosteuerung["AlexaProxyAdr"])==true)
			{
			/* Daten zur Verarbeitung weiterleiten an einen anderen Server */
		 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."    ".$_IPS['REQUEST']."   ".$_IPS['VALUE']." und leitet weiter an ".$configurationAutosteuerung["AlexaProxyAdr"]);			
			$request=array();
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["REQUEST"]=	$_IPS['REQUEST'];
			if (function_exists("proxyAlexa") == true) proxyAlexa($request);
			}
		else
			{	
			//$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']);
		 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
			$request=array();
			$request["VARIABLE"]=	$_IPS['VARIABLE'];
			$request["VALUE"]=		$_IPS['VALUE'];
			$request["REQUEST"]=	$_IPS['REQUEST'];
			executeAlexa($request);
			}
if (false)
{
IPS_LogMessage("Alexa Aquarium: ","SENDER: '".$_IPS['SENDER']."' REQUEST: '".$_IPS['REQUEST']."' VALUE: '".$_IPS['VALUE']."'");

$ID_Schalter = 58944; // Variable für Schalten
$ID_Wert_Warmweiss = 36817; // Variable für Wert Weiss
$ID_Wert_Kaltweiss = 21812; // Variable für Wert Weiss
$ID_Wert_RGB = 56142; // Variable für Wert RGB

if($_IPS['SENDER'] == "AlexaSmartHome"){
    if($_IPS['REQUEST'] == "TurnOnRequest"){
        SetValue($ID_Wert_Warmweiss, 255); // LED auf 100%
        SetValue($ID_Schalter , 1);
    }
    elseif($_IPS['REQUEST'] == "TurnOffRequest"){
        SetValue($ID_Schalter , 0);
    }
    elseif($_IPS['REQUEST'] == "SetPercentageRequest"){
        SetValue($ID_Wert_Warmweiss, ($_IPS['VALUE'] / 100 * 255)); // helligkeit weisse LED
        SetValue($ID_Schalter , 1);
    }
    elseif($_IPS['REQUEST'] == "SetColorRequest"){
        SetValue($ID_Wert_RGB , hexdec($_IPS['VALUE'])); // Farbe übernehmen
        SetValue($ID_Schalter , 3); // RGB einschalten
    }
    elseif($_IPS['REQUEST'] == "SetColorTemperatureRequest"){
    # 2700 = Warmweiss, 4000 = weiss, 7000 = Kaltweiss
        if($_IPS['VALUE'] == 2700){
            SetValue($ID_Wert_Warmweiss, 255); // LED auf 100%
            SetValue($ID_Wert_Kaltweiss, 0); // LED auf 0%
            SetValue($ID_Schalter , 1); // LED einschalten
        }
        elseif($_IPS['VALUE'] == 4000){
            SetValue($ID_Wert_Warmweiss, 255); // LED auf 100%
            SetValue($ID_Wert_Kaltweiss, 0); // LED auf 0%
            SetValue($ID_Schalter , 1); // LED einschalten
        }
        elseif($_IPS['VALUE'] == 7000){
            SetValue($ID_Wert_Kaltweiss, 255); // LED auf 100%
            SetValue($ID_Wert_Warmweiss, 0); // LED auf 0%
            SetValue($ID_Schalter , 2); // LED einschalten
        }
        
    }
} 
}
		/*
    SetValue($_IPS['VARIABLE'] , $_IPS['VALUE']);      
                      
    if ($_IPS['VALUE'] == True)
          {
            IPS_LogMessage( "Fernseher:" , "Einschalten" );
        }
    else
        {
            IPS_LogMessage( "Fernseher:" , "Ausschalten" );    
            $host="192.168.0.49";  
            if (Sys_Ping($host,100))  
            {  
                $cu = curl_init('http://'.$host.':1925/1/input/key');  
                  curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);  
                $befehl=array('key'=>'Standby');  
                $json_befehl=json_encode($befehl);  
                curl_setopt($cu, CURLOPT_POSTFIELDS,$json_befehl);  
                curl_exec($cu);  
                curl_close($cu);  
            }          
        }
    
*/
       break;
    Case "WebFront":        // Zum schalten im Webfront
/*
                
    SetValue($_IPS['VARIABLE'] , $_IPS['VALUE']); 
      
    if ($_IPS['VALUE'] == True)
          {
         // an    
        }
    else
        {            
            $host="192.168.0.49";  
            if (Sys_Ping($host,100))  
            {  
                $cu = curl_init('http://'.$host.':1925/1/input/key');  
                  curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);  
                $befehl=array('key'=>'Standby');  
                $json_befehl=json_encode($befehl);  
                curl_setopt($cu, CURLOPT_POSTFIELDS,$json_befehl);  
                curl_exec($cu);  
                curl_close($cu);  
            }  
        }
    
*/
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
	$register=new AutosteuerungConfigurationAlexa($scriptIdAutosteuerung);
	$alexaHandler = new AutosteuerungAlexaHandler(); 
	$nachrichten=new AutosteuerungAlexa();

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
	echo "Execute aufgerufen. Analyse der einzelnen abgespeicherten Befehle.\n";
	$register->PrintAutoEvent();
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