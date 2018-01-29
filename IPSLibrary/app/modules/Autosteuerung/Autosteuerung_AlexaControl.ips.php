<?

/****************************************************************************************
*
* Autosteuerung, Spezialroutinen für Alexa Control
*
*
*
*
*******************************************************************************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");
	


/*********************************************************************************************/

include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
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

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
$register=new AutosteuerungConfigurationAlexa($scriptIdAutosteuerung);

$nachrichten=new AutosteuerungAlexa();

Switch ($_IPS['SENDER'])
	{
	Default:
	 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
		break;
	Case "RunScript":
	Case "Execute":
		test_execute($register);
	 	break;
	Case "TimerEvent":
		break;
	Case "Variable":
	Case "AlexaSmartHome":
		//$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']);
	 	IPSLogger_Dbg(__file__,"Alexa empfaengt : ".$_IPS['VARIABLE']."   ".$_IPS['VALUE']);
		$params=$register->getAutoEvent($_IPS['VARIABLE']);
		if ($params === false) 
			{
			$register->registerAutoEvent($_IPS['VARIABLE'], $_IPS['REQUEST'], "", "");
			$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']." Neu, registrieren.");
			}
		else
			{
			$nachrichten->LogNachrichten(" ".$_IPS['VARIABLE']." ".$_IPS['REQUEST']."  ".$_IPS['VALUE']." ".json_encode($params));
			$ergebnis=Status($params,$_IPS['VALUE']);
			//$nachrichten->LogNachrichten(" ".json_encode($ergebnis));
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

function test_execute($register)
	{
	echo "\n=====================================================================\n";
	echo "Execute aufgerufen. Analyse der einzelnen abgespeicherten Befehle.\n";
	//$register->PrintAutoEvent();
	$entries=$register->getAutoEvent();
	$i=0;
	echo "\n";
	foreach ($entries as $index => $entry)
		{
		//print_r($entry);
		echo "Bearbeite Eintrag : ".$index." : \n";
		Status($entry,$i++,true);
		}
		
	//$result=$register->getAutoEvent("44404b3f-5f92-40ba-a7d5-63e8a83987a4");
	//print_r($result);
	}



/*********************************************************************************************/


?>