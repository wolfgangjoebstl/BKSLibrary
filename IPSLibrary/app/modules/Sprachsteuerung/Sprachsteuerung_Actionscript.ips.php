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

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Sprachsteuerung\Sprachsteuerung_Configuration.inc.php");

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

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");
echo "Nachrichten gibt es auch : ".$NachrichtenID ."  (".IPS_GetName($NachrichtenID).")   ".$NachrichtenScriptID." \n";

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	/* logging in einem File und in einem String am Webfront */
	$log_Sprachsteuerung=new Logging("C:\Scripts\Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
	}
else break;


Switch ($_IPS['SENDER'])
    {
    Default:
    Case "RunScript":
		break;
    Case "Execute":
		$log_Sprachsteuerung->LogNachrichten("Alexa: Execute");
		break;
    Case "TimerEvent":
        break;
    Case "Variable":
    Case "AlexaSmartHome":
		/* SetValue($_IPS['VARIABLE'] , $_IPS['VALUE']); */      
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
        		}          
			} */
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
        }	*/
       break;
    } 


/*********************************************************************************************/


/*********************************************************************************************/




?>