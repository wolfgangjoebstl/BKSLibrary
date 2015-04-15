<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung.class.php");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
}

$sprachsteuerung=false;
$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	if ($name=="Sprachsteuerung")
		{
		$sprachsteuerung=true;
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Sprachsteuerung\Sprachsteuerung_Library.class.php");
		}
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";

/* Dummy Objekte für typische Anwendungsbeispiele erstellen, geht nicht automatisch */
/* könnte in Zukunft automatisch beim ersten Aufruf geschehen */

$name="Bedienung";
$categoryId_Autosteuerung  = CreateCategory($name, $CategoryIdData, 10);
$AnwesenheitssimulationID = IPS_GetObjectIDByName("Anwesenheitssimulation",$categoryId_Autosteuerung);
$VentilatorsteuerungID = IPS_GetObjectIDByName("Ventilatorsteuerung",$categoryId_Autosteuerung);

/* wichtigste Parameter vorbereiten */

$configuration = Autosteuerung_GetEventConfiguration();
$scenes=Autosteuerung_GetScenes();
//print_r($configuration);

// Sonnenauf.- u. Untergang berechnen
$longitude = 16.36; //14.074881;
$latitude = 48.21;  //48.028615;
$timestamp = time();
/*php >Funktion: par1: Zeitstempel des heutigen Tages
					  par2: Format des retourwertes, String, Timestamp, float SUNNFUNCS_RET_xxxxx
					  par3: north direction (for south use negative)
					  par4: west direction (for east use negative)
					  par5: zenith, see example
							$zenith=90+50/60; Sunrise/sunset
							$zenith=96; Civilian Twilight Start/end
							$zenith=102; Nautical Twilight Start/End
							$zenith=108; Astronomical Twilight start/End
					  par6: GMT offset  zB mit date("O")/100 oder date("Z")/3600 bestimmen
					  möglicherweise mit Sommerzeitberechnung addieren:  date("I") == 1 ist Sommerzeit
*/
$sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+50/60, date("O")/100);
$sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+50/60, date("O")/100);

echo "Sonnenauf/untergang ".date("H:i",$sunrise)." ".date("H:i",$sunset)." \n";

$speak_config=Autosteuerung_Speak();

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{

	/* von der Konsole aus gestartet */
	foreach ($configuration as $key=>$entry)
	   {
	   echo "Eintraege fuer :".$key." ".IPS_GetName($key)." Parent ".IPS_GetName(IPS_GetParent($key))."\n";
	   print_r($entry);

	   foreach($scenes as $scene)
			{
			echo "Anwesenheitssimulation Szene : ".$scene["NAME"]."\n";
       	$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
       	if ($actualTime[0]=="sunset") {$actualTime[0]=date("H:i",$sunset);}
       	print_r($actualTime);
       	$actualTimeStart = explode(":",$actualTime[0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTime[1]);
        	$actualTimeStopHour = $actualTimeStop[0];
        	$actualTimeStopMinute = $actualTimeStop[1];
			echo "Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
        	$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
        	$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
      	$now = time();
      	//include(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/IPSLight/IPSLight.inc.php");
      	if (isset($scene["EVENT_IPSLIGHT"]))
      	   {
      		echo "Objekt : ".$scene["EVENT_IPSLIGHT"]."\n";
         	//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
         	}
         else
            {
      		if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	   	{
	      		echo "Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"]."\n";
   	      	//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
      	   	}
				}
     		}
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{
	/* eine Variablenaenderung ist aufgetreten */

	if (array_key_exists($_IPS['VARIABLE'], $configuration))
		{
		/* es gibt einen Eintrag fuer das Event */
		
		$params=$configuration[$_IPS['VARIABLE']];
		$wert=$params[1];
		/* 0: OnChange or OnUpdate, 1 ist die Klassifizierung, Befehl 2 sind Parameter */
  		//tts_play(1,$_IPS['VARIABLE'].' and '.$wert,'',2);
		switch ($wert)
		   {
		   case "Anwesenheit":
		      /* Funktion um Anwesenheitssimulation ein und aus zuschalten */
		      If (GetValue($AnwesenheitssimulationID)>0)
		         {
		         //Script alle 5 Minuten ausführen
		 			IPS_SetScriptTimer($_IPS['SELF'], 5*60);
		         }
				else
				   {
				   //Script nicht mehr automatisch ausführen
		 			IPS_SetScriptTimer($_IPS['SELF'], 0);
				   }
		      break;
		   case "Ventilator":
		      /* Funktion um Ventilatorsteuerung ein und aus zuschalten */
		   	$eventName = 'OnChange_'.$_IPS['VARIABLE'];
				$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
		      If (GetValue($VentilatorsteuerungID)>0)
		         {
					if ($eventId === false)
						{
						$eventId = IPS_CreateEvent(0);
						IPS_SetName($eventId, $eventName);
						IPS_SetIdent($eventId, $eventName);
						IPS_SetEventTrigger($eventId, 1, $params[3]);
						IPS_SetParent($eventId, $scriptId);
						IPS_SetEventActive($eventId, true);
						IPSLogger_Dbg (__file__, 'Created IPSMessageHandler Event for Variable='.$params[3]);
						}
					else
			   		{
			   		echo "EventName uns ID: ".$eventName."  ".$eventId."\n";
			   		}
		         }
				else
				   {
					IPS_SetEventActive($eventId, false);
				   }
		      break;
		   case "Parameter":
		      /* wenn Parameter ueberschritten etwas tun */
		   	$temperatur=GetValue($_IPS['VARIABLE']);
		   	if ($speak_config["Parameter"][0]=="On")
		   	   {
		     		tts_play(1,'Temperatur im Wohnzimmer '.floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10)." Grad.",'',2);
		     		}
		     	$moduleParams2 = explode(',', $params[2]);
		     	//print_r($moduleParams2);
		     	if ($moduleParams2[2]=="true") {$switch_ein=true;} else {$switch_ein=false; }
		     	if ($moduleParams2[4]=="true") {$switch_aus=true;} else {$switch_aus=false; }
		     	if ($temperatur>$moduleParams2[1])
		     	   {
			     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_ein);
			     	if ($speak_config["Parameter"][0]=="On")
		   	   	{
		     			tts_play(1,"Ventilator ein.",'',2);
		     			}
			     	}
		     	if ($temperatur<$moduleParams2[3])
		     	   {
			     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_aus);
			     	if ($speak_config["Parameter"][0]=="On")
		   	   	{
		     			tts_play(1,"Ventilator aus.",'',2);
		     			}
			     	}
				break;
		   case "Status":
		      /* bei einer Statusaenderung einer Variable 																						*/
		      /* array($params[0], $params[1],             $params[2],),                     										*/
		      /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
		      /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,timer#dawn-23:45',),       			*/
		      /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,cond#xxxxxx',),       				*/
		      
		   	$status=GetValue($_IPS['VARIABLE']);
		   	if ($status)
		   	   {
			     	if ($speak_config["Parameter"][0]=="On")
		   	   	{
		     			tts_play(1,'Status geht auf ein.','',2);
		     			}
		     		}
		     	else
		     	   {
			     	if ($speak_config["Parameter"][0]=="On")
		   	   	{
		     			tts_play(1,'Status geht auf aus.','',2);
		     			}
		     		}
		     	$moduleParams2 = explode(',', $params[2]);
		     	print_r($moduleParams2);
		     	if ($status)
		     	   {
			     	IPSLight_SetSwitchByName($moduleParams2[0],true);
			     	}
		     	else
		     	   {
			     	IPSLight_SetSwitchByName($moduleParams2[0],false);
			     	}
				break;
		   case "StatusRGB":
		      /* allerlei Spielereien mit einer RGB Anzeige */
		      
		   	$status=GetValue($_IPS['VARIABLE']);
		   	//tts_play(1,'Anwesenheit Status geht auf '.$status,'',2);
		     	$moduleParams2 = explode(',', $params[2]);
			   //IPSLight_SetSwitchByName($moduleParams2[0],$status);
			   $lightManager = new IPSLight_Manager();
			   //Farbe per RGB(Hex)-Wert setzen
			   if ($staus==true)
			      {
				   $lightManager->SetRGB(22722 , $moduleParams2[1]);
				   }
				else
			      {
				   $lightManager->SetRGB(22722 , $moduleParams2[2]);
				   }

				break;
		   default:
				eval($params[1]);
				break;
			}
		}
	else
	   {
  		tts_play(1,'Taste gedrueckt mit Zahl '.$_IPS['VARIABLE'],'',2);
  		}
  		
	if (false)
		   {
			$remServer=array(
				"BKS-Server"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/',
						);
			foreach ($remServer as $Server)
				{
				$rpc = new JSONRPC($Server);
				}
			$rpc->IPS_RunScript(10004);
		   }
	
	switch ($_IPS['VARIABLE'])
			{

			/* Positionswerte geändert */

		   case "32688": /* Arbeitszimmer Pos Aenderung Heizung*/
				break;

			case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
				break;

			case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
				break;
			}
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	/* Wird alle 5 Minuten aufgerufen, da kann man die zeitgesteuerten Dinge hineintun */
	/* lassen sich aber nicht in der event gesteuerten Parametrierung einstellen */
	
	if (GetValue($AnwesenheitssimulationID)>0)
 		{//Anwesenheitssimulation aktiv
		echo "\nAnwesenheitssimulation eingeschaltet. \n";
		//print_r($scenes);
	   foreach($scenes as $scene)
			{
       	$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
       	if ($actualTime[0]=="sunset") {$actualTime[0]=date("H:i",$sunset);}
       	print_r($actualTime);
       	$actualTimeStart = explode(":",$actualTime[0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTime[1]);
        	$actualTimeStopHour = $actualTimeStop[0];
        	$actualTimeStopMinute = $actualTimeStop[1];
			echo "Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
        	$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
        	$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
      	$now = time();

       	if ($now > $timeStart && $now < $timeStop)
			 	{
			 	echo "Es ist Zeit für Szene ".$scene['NAME']."\n";
          	$minutesRange = ($timeStop-$timeStart)/60;
          	$actionTriggerMinutes = 5;
            $rndVal = rand(1,100);
				echo "Zufallszahl:".$rndVal."\n";
            if (($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100))
					{
					echo "Jetzt wird der Timer gesetzt : ".$scene["NAME"]."_EVENT"."\n";
               IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
               $EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
               if ($EreignisID === false)
						{ //Event nicht gefunden > neu anlegen
                  $EreignisID = IPS_CreateEvent(1);
                  IPS_SetName($EreignisID,$scene["NAME"]."_EVENT");
                  IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
               	}
               IPS_SetEventActive($EreignisID,true);
               IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);  /* EreignisID, 0 Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
               IPS_SetEventCyclicTimeBounds($EreignisID,$now+$scene["EVENT_DURATION"]*60,0);
               IPS_SetEventCyclicDateBounds($EreignisID,$now+$scene["EVENT_DURATION"]*60,0);
					if ($scene["EVENT_CHANCE"]==100)
						{
						echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
	               IPS_SetEventCyclicTimeBounds($EreignisID,$timeStop,0);
						}
		      	if (isset($scene["EVENT_IPSLIGHT"]))
      			   {
  	               IPS_SetEventScript($EreignisID,
                                                "include(\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n".
                                                "IPSLight_SetSwitchByName(\"".$scene["EVENT_IPSLIGHT"]."\", false);");
						}
					else
					   {
	               IPS_SetEventScript($EreignisID,
                                                "include(\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n".
                                                "IPSLight_SetGroupByName(\"".$scene["EVENT_IPSLIGHT_GRP"]."\", false);");
						}
            	}
        		}
		   } /* end of foreach */

		 }  /* endif */
	else
		{
    	//Anwesenheitssimulation nicht aktiv
    	//Alle Timer deaktivieren
    	foreach($scenes as $scene)
		 	{
      	$EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
        	if ($EreignisID != false)
			  	{
         	IPS_SetEventActive($EreignisID,false);
        		}
    		}
 		}
	} /* endif Anwesenheitssimulation */
} /* Endif Timer */


/*********************************************************************************************/





/*********************************************************************************************/




?>
