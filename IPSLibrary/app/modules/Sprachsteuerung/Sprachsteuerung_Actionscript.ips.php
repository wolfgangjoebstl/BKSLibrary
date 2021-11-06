<?

/***********************************************************************
 *
 *    Sprachsteuerung Actionscript
 *
 * kann nicht nur Sprachausgabe sondern auch die Alexa App von IQL4SmartHome bedienen
 * 
 *
 *
 *
 ***********************************************************/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Sprachsteuerung\Sprachsteuerung_Configuration.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
		}
 	$installedModules = $moduleManager->GetInstalledModules();

	if (isset($installedModules["OperationCenter"]))
		{
		IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
		IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');		
		}


	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_Auswertungen    = IPS_GetCategoryIdByName('Auswertungen',$CategoryIdData);
	$ButtonId					= IPS_GetVariableIdByName("Button",$categoryId_Auswertungen);
    $TuneInStation              = IPS_GetVariableIdByName("TuneInStation",$categoryId_Auswertungen);
	$TestnachrichtId 			= IPS_GetVariableIdByName("Testnachricht",$categoryId_Auswertungen);
	$SelectID					= IPS_GetVariableIdByName("SelectSpeaker",$categoryId_Auswertungen);

	$SelectedStationId    		= IPS_GetVariableIdByName("SelectedStationId",$categoryId_Auswertungen);
    $TuneInStationConfig        = IPS_GetVariableIdByName("TuneInStationConfig",$categoryId_Auswertungen);

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");


if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	/* logging in einem File und in einem String am Webfront */
	$log_Sprachsteuerung=new Logging("C:\Scripts\Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
	}
else 
	{
	IPSLogger_Err(__file__,"NachrichtenScriptID nicht bekannt");
	}


Switch ($_IPS['SENDER'])
    {
    Default:
    Case "RunScript":
    Case "TimerEvent":
    	break;
    Case "Execute":
		echo "Nachrichten gibt es auch : ".$NachrichtenID ."  (".IPS_GetName($NachrichtenID).")   ".$NachrichtenScriptID." \n";
		$log_Sprachsteuerung->LogNachrichten("Alexa: Execute");
		break;
    Case "Variable":
    Case "AlexaSmartHome":
	    if ($_IPS['VALUE'] == True)
    		{
			$log_Sprachsteuerung->LogNachrichten("Alexa: ".$_IPS['VARIABLE']." Ein ".$_IPS['VALUE']);
        	}
    	else
        	{
			$log_Sprachsteuerung->LogNachrichten("Alexa: ".$_IPS['VARIABLE']." Aus".$_IPS['VALUE']);
			/*
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
        		}  */          
			} 
       break;
    Case "WebFront":        // Zum schalten im Webfront
       	SetValue($_IPS['VARIABLE'] , $_IPS['VALUE']);
        //echo "Aufruf mit ".$_IPS['VARIABLE']."(".IPS_GetName($_IPS['VARIABLE']).") und Wert :".$_IPS['VALUE']."\n";
		switch ($_IPS['VARIABLE'])
			{
			case $ButtonId:
                $function = GetValue($ButtonId);
                switch ($function)
                    {
                    case 3:
                        ECHOREMOTE_Pause(GetValue($SelectID));
                        break;                    
                    case 2:
                        //$alexa=IPS_GetName(GetValue($SelectID));
        				//echo "Ich wurde mit $function aufgerufen und spiele Radio auf \"$alexa\".\n";
                        //ECHOREMOTE_TuneIn(GetValue($SelectID),"s8007");   // Ö3 s8007  Hitradio FFH s17490
                        ECHOREMOTE_TuneIn(GetValue($SelectID),GetValue($SelectedStationId));
                        ECHOREMOTE_Play(GetValue($SelectID));
                        break;
                    case 1:
				        $spreche=GetValue($TestnachrichtId);
        				echo "Ich wurde mit $function aufgerufen und spreche $spreche.\n";
				        tts_play(GetValue($SelectID),$spreche,'',2);
                        break;
                    default:
                        break;
                    }
				break;
            case $TuneInStation:
                $SelectedStation = GetValue($TuneInStation);
                $TuneInstations = json_decode(GetValue($TuneInStationConfig),true);
                foreach ($TuneInstations as $station)
                    {
                    if ($station["position"]==$SelectedStation) SetValue($SelectedStationId,$station["station_id"]);
                    }
                break;	
			default;
			
			}			    
       	break;
    } 


/*********************************************************************************************/


/*********************************************************************************************/




?>