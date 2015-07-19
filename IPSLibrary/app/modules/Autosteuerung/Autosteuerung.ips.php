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

$NachrichtenScriptID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung.Nachrichtenverlauf-Autosteuerung');

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($tempOID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	//echo $NachrichtenScriptID."   ".$NachrichtenInputID."\n";
	/* logging in einem File und in einem String am Webfront */
	$log_Giessanlage=new logging("C:\Scripts\Log_Giessanlage2.csv",$NachrichtenScriptID,$NachrichtenInputID);
	}
else break;



/* Dummy Objekte für typische Anwendungsbeispiele erstellen, geht nicht automatisch */
/* könnte in Zukunft automatisch beim ersten Aufruf geschehen */


$name="Ansteuerung";
$categoryId_Autosteuerung  = CreateCategory($name, $CategoryIdData, 10);
$AnwesenheitssimulationID = IPS_GetObjectIDByName("Anwesenheitssimulation",$categoryId_Autosteuerung);

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

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
$register=new AutosteuerungHandler($scriptIdAutosteuerung);

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	//IPSLogger_Dbg(__file__, 'Exec aufgerufen ...');
	test();
	/* von der Konsole aus gestartet */
	echo "\nEingestellte Programme:\n\n";
	foreach ($configuration as $key=>$entry)
	   {
	   echo "Eintraege fuer :".$key." ".IPS_GetName($key)." Parent ".IPS_GetName(IPS_GetParent($key))."\n";
	   print_r($entry);
	   switch ($entry[1])
	      {
	      case "Switch":
	      	$status=true;
			   $lightManager = new IPSLight_Manager();
				$moduleParams2 = explode(",",$entry[2]);
				echo "Anzahl Parameter in Param2: ".count($moduleParams2)."\n";
				print_r($moduleParams2);
				/* wenn nur ein oder zwei Parameter, dann ignorieren */
				$parges=array();
				if (count($moduleParams2)>2)
				   {
				   /* Default Werte setzen */
				   $notmask_on=0;
					$mask_on=0xFFFFFF;
				   $notmask_off=0;
					$mask_off=0xFFFFFF;
					$params_oid=explode(":",$moduleParams2[0]);
					if (count($params_oid)>1)
					   {
						$parges[$params_oid[0]]=$params_oid;
					   }
					else
					   {
					   $lightManager = new IPSLight_Manager();
						$switchOID = @$lightManager->GetSwitchIdByName($moduleParams2[0].'#Color');
						if ($switchOID==false)
						   {
						   $switchOID=$moduleParams2[0];
						   }
					   }
					$params_on=explode(":",$moduleParams2[1]);
					echo "Param 2 1 ON: ".count($params_on)."\n";
					print_r($params_on);
					if (count($params_on)>1)
					   {
						$parges[$params_on[0]]=$params_on;
					   }
					else
					   {
					   $value_on=$moduleParams2[1];
					   }
					$params_off=explode(":",$moduleParams2[2]);
					echo "Param 2 2 OFF: ".count($params_off)."\n";
					print_r($params_off);
					if (count($params_off)>1)
					   {
						$parges[$params_off[0]]=$params_off;
					   }
					else
					   {
					   $value_off=$moduleParams2[2];
					   }
					$i=3;
					while ($i<count($moduleParams2))
					   {
						$params=explode(":",$moduleParams2[$i]);
						if (count($params_off)>1)
						   {
							$parges[$params[0]]=$params;
						   }
						$i++;
					   }
					echo "Ein grosses Array mit allen Befehlen:\n";
					print_r($parges);
					foreach ($parges as $befehl)
					   {
					   print_r($befehl);
						switch (strtoupper($befehl[0]))
						   {
						   case "OID":
							   $switchOID=$befehl[1];
								break;
						   case "ON":
						      $value_on=$befehl[1];
						      $i=2;
						      while ($i<count($befehl))
						         {
						         if (strtoupper($befehl[$i])=="MASK")
						            {
						            $mask_on=$befehl[$i++];
						            }
						         $i++;
						         }
								break;
						   case "OFF":
						      $value_off=$befehl[1];
						      $i=2;
						      while ($i<count($befehl))
						         {
						         if (strtoupper($befehl[$i])=="MASK")
						            {
						            $mask_off=$befehl[$i++];
						            }
						         $i++;
						         }
								break;
							}
						} /* ende foreach */
						if ($status==true)
							{
						   //$new=((int)$lightManager->GetValue($switchOID) & $notmask_on) | ($value_on & $mask_on);
							//$lightManager->SetRGB($switchOID, $new);
							}
						else
					      {
						   //$new=((int)$lightManager->GetValue($switchOID) & $notmask_off) | ($value_off & $mask_off);
							//$lightManager->SetRGB($switchOID, $new);
							}
						printf("Ergebnis OID: %x ON: %x MASK: %x OFF: %x MASK: %x \n",$switchOID,$value_on,$mask_on,$value_off,$mask_off);
					}
				break;

	      case "Status":
	      	$status=true; $delayValue=0;
			   $lightManager = new IPSLight_Manager();
				$moduleParams2 = explode(",",$entry[2]);
				If ($entry[0]=="OnUpdate")
				   {
				   //echo "Andere Behandlung wenn OnUpdate eingestellt ist ...\n";
				   }
				$parges=array();
				switch (count($moduleParams2))
				   {
				   case "3":
						$params_off=explode(":",$moduleParams2[2]);
						if (count($params_off)>1)
						   {
							$parges=parseParameter($params_off,$parges);
						   }
						else
						   {
							echo "Delay ist jetzt : ".$params_off[0]." in Sekunden.\n";
							$delayValue=(integer)$params_off[0];
							}
				   case "2":
				   	$params_on=explode(":",$moduleParams2[1]);
						if (count($params_on)>1)
						   {
							$parges=parseParameter($params_on,$parges);
						   }
						else
							{
							if (strtoupper($params_on[0])=="TRUE") { $status=true;};
							if (strtoupper($params_on[0])=="FALSE") { $status=false;};
							}
				   case "1":
				   	$params_one=explode(":",$moduleParams2[0]);
						if (count($params_one)>1)
						   {
							$parges=parseParameter($params_one,$parges);
						   }
						else
							{
 					      $SwitchName=$moduleParams2[0];
							}
						break;
				   default:
						echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
				      break;
					}
				print_r($parges);
				foreach ($parges as $befehl)
				   {
					switch (strtoupper($befehl[0]))
					   {
					   case "OID":
						   $switchOID=$befehl[1];
							break;
					   case "NAME":
						   $switchName=$befehl[1];
							break;
					   case "ON":
		   			   $value_on=$befehl[1];
					      $i=2;
		   			   while ($i<count($befehl))
					         {
		   			      if (strtoupper($befehl[$i])=="MASK")
		            			{
					            $mask_on=$befehl[$i++];
		   			         }
					         $i++;
		   			      }
							break;
		   			case "OFF":
					      $value_off=$befehl[1];
		   			   $i=2;
					      while ($i<count($befehl))
		   			      {
					         if (strtoupper($befehl[$i])=="MASK")
		   			         {
		            			$mask_off=$befehl[$i++];
					            }
		   			      $i++;
					         }
							break;
					   case "DELAY":
							$delayValue=(integer)$befehl[1];
							break;

						}
					} /* ende foreach */

				if ($status===true)
					{
					echo "Switchname ist : ".$SwitchName." mit Status : true und Delay ".$delayValue."\n";
					}
				else
				   {
					echo "Switchname ist : ".$SwitchName." mit Status : false und Delay ".$delayValue." \n";
					}
				break;

	      case "StatusRGB":
	      	$status=true;
			   $lightManager = new IPSLight_Manager();
				$moduleParams2 = explode(",",$entry[2]);
				echo "Anzahl Parameter in Param2: ".count($moduleParams2)."\n";
				print_r($moduleParams2);
				/* wenn nur ein oder zwei Parameter, dann ignorieren */
				$parges=array();
				if (count($moduleParams2)>2)
				   {
				   /* Default Werte setzen */
				   $notmask_on=0;
					$mask_on=0xFFFFFF;
				   $notmask_off=0;
					$mask_off=0xFFFFFF;
					$params_oid=explode(":",$moduleParams2[0]);
					if (count($params_oid)>1)
					   {
						$parges=parseParameter($params_oid,$parges);
						}
					else
					   {
					   $lightManager = new IPSLight_Manager();
						$switchOID = @$lightManager->GetSwitchIdByName($moduleParams2[0].'#Color');
						if ($switchOID==false)
						   {
						   $switchOID=$moduleParams2[0];
						   }
					   }
					$params_on=explode(":",$moduleParams2[1]);
					echo "Param 2 1 ON: ".count($params_on)."\n";
					print_r($params_on);
					if (count($params_on)>1)
					   {
						$parges=parseParameter($params_on,$parges);
					   }
					else
					   {
					   $value_on=$moduleParams2[1];
					   }
					$params_off=explode(":",$moduleParams2[2]);
					echo "Param 2 2 OFF: ".count($params_off)."\n";
					print_r($params_off);
					if (count($params_off)>1)
					   {
						$parges=parseParameter($params_off,$parges);
					   }
					else
					   {
					   $value_off=$moduleParams2[2];
					   }
					$i=3;
					while ($i<count($moduleParams2))
					   {
						$params=explode(":",$moduleParams2[$i]);
						if (count($params)>1)
         				{
							$parges=parseParameter($params,$parges);
						   }
						$i++;
					   }
					echo "Ein grosses Array mit allen Befehlen:\n";
					print_r($parges);
					foreach ($parges as $befehl)
					   {
					   print_r($befehl);
						switch (strtoupper($befehl[0]))
						   {
						   case "OID":
							   $switchOID=$befehl[1];
								break;
						   case "ON":
						      $value_on=$befehl[1];
						      $i=2;
						      while ($i<count($befehl))
						         {
						         if (strtoupper($befehl[$i])=="MASK")
						            {
						            $mask_on=$befehl[$i++];
						            }
						         $i++;
						         }
								break;
						   case "OFF":
						      $value_off=$befehl[1];
						      $i=2;
						      while ($i<count($befehl))
						         {
						         if (strtoupper($befehl[$i])=="MASK")
						            {
						            $mask_off=$befehl[$i++];
						            }
						         $i++;
						         }
								break;
							}
						} /* ende foreach */
						if ($status==true)
							{
						   //$new=((int)$lightManager->GetValue($switchOID) & $notmask_on) | ($value_on & $mask_on);
							//$lightManager->SetRGB($switchOID, $new);
							}
						else
					      {
						   //$new=((int)$lightManager->GetValue($switchOID) & $notmask_off) | ($value_off & $mask_off);
							//$lightManager->SetRGB($switchOID, $new);
							}
						printf("Ergebnis OID: %x ON: %x MASK: %x OFF: %x MASK: %x \n",$switchOID,$value_on,$mask_on,$value_off,$mask_off);
					}
				break;
			}
		}

	/*********************************************************************************************/

	echo "\nEingestellte Anwesenheitssimulation:\n\n";
   foreach($scenes as $scene)
			{
			echo "  Anwesenheitssimulation Szene : ".$scene["NAME"]."\n";
       	$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
       	if ($actualTime[0]=="sunset") {$actualTime[0]=date("H:i",$sunset);}
       	//print_r($actualTime);
       	$actualTimeStart = explode(":",$actualTime[0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTime[1]);
        	$actualTimeStopHour = $actualTimeStop[0];
        	$actualTimeStopMinute = $actualTimeStop[1];
			echo "    Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
        	$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
        	$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
      	$now = time();
      	//include(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/IPSLight/IPSLight.inc.php");
      	if (isset($scene["EVENT_IPSLIGHT"]))
      	   {
      		echo "    Objekt : ".$scene["EVENT_IPSLIGHT"]."\n";
         	//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
         	}
         else
            {
      		if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	   	{
	      		echo "    Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"]."\n";
   	      	//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
      	   	}
				}
     		}
	/* Events registrieren. Umsetzung des Config Files */

	echo "Programme für Schalter registrieren nach OID des Events.\n";

	$AutoConfiguration = Autosteuerung_GetEventConfiguration();
	foreach ($AutoConfiguration as $variableId=>$params)
		{
		echo "Create Event für ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
		$register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
		}



	}


/*********************************************************************************************/
/*                                                                                           */
/* Programmfunktionen             																				*/
/*                                                                                           */
/*********************************************************************************************/

if ($_IPS['SENDER']=="Variable")
	{
	/* eine Variablenaenderung ist aufgetreten */
	IPSLogger_Dbg(__file__, 'Variablenaenderung von '.$_IPS['VARIABLE'].'...');
	if (array_key_exists($_IPS['VARIABLE'], $configuration)) {
		/* es gibt einen Eintrag fuer das Event */

		$params=$configuration[$_IPS['VARIABLE']];
		$wert=$params[1];
		/* 0: OnChange or OnUpdate, 1 ist die Klassifizierung, Befehl 2 sind Parameter */
  		//tts_play(1,$_IPS['VARIABLE'].' and '.$wert,'',2);
		switch ($wert)    {
			/*********************************************************************************************/
		   case "Anwesenheit":
		      Anwesenheit();
		      break;
			/*********************************************************************************************/
		   case "Ventilator1":
		      Ventilator1();
		      break;
			/*********************************************************************************************/
		   case "Parameter":
		      Parameter();
		      break;
			/*********************************************************************************************/
		   case "Ventilator":
		      Ventilator();
				break;
			/*********************************************************************************************/
		   case "Status":
			   /* bei einer Statusaenderung oder Aktualisierung einer Variable 														*/
			   /* array($params[0], $params[1], $params[2],),                     													*/
			   /* array('OnChange','Status',   'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate','Status','ArbeitszimmerLampe,true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,cond#xxxxxx',),       					*/
				Status();
				break;
			/*********************************************************************************************/
		   case "StatusRGB":
		      statusRGB();
				break;
			/*********************************************************************************************/
		   case "Switch":
				SwitchFunction();
		      break;
			/*********************************************************************************************/
		   case "Custom":
		      /* Aufrufen von kundenspezifischen Funktionen */
		      break;
			/*********************************************************************************************/
		   case "par1":
		   case "dummy":
		   case "Dummy":
		   case "DUMMY":
		      break;

		   default:
				eval($params[1]);
				break;
			}
		}
	else  {
  		tts_play(1,'Taste gedrueckt mit Zahl '.$_IPS['VARIABLE'],'',2);
  		}



	}

/*********************************************************************************************/
/*                                                                                           */
/* Anwesenheitssimulation, Timerfunktionen																	*/
/*                                                                                           */
/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	/* Wird alle 5 Minuten aufgerufen, da kann man die zeitgesteuerten Dinge hineintun */
	/* lassen sich aber nicht in der event gesteuerten Parametrierung einstellen */

	if (GetValue($AnwesenheitssimulationID)>0)
 		{
		//Anwesenheitssimulation aktiv
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
		      	if (isset($scene["EVENT_IPSLIGHT"]))
      			   {
      				echo "    Objekt : ".$scene["EVENT_IPSLIGHT"]."\n";
						IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], true);
      		   	}
		         else
      		      {
      				if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	   			{
			      		echo "    Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"]."\n";
   			      	IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
      	   			}
						}
					echo "Jetzt wird der Timer gesetzt : ".$scene["NAME"]."_EVENT"."\n";
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
 		} /* endif Anwesenheitssimulation */
	} /* Endif Timer */


/*********************************************************************************************/

/*  setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60)                                */

function setEventTimer($name,$delay,$command)
	{
	echo "Jetzt wird der Timer gesetzt : ".$name."_EVENT"."\n";
  	$now = time();
   $EreignisID = @IPS_GetEventIDByName($name."_EVENT", IPS_GetParent($_IPS['SELF']));
   if ($EreignisID === false)
		{ //Event nicht gefunden > neu anlegen
      $EreignisID = IPS_CreateEvent(1);
      IPS_SetName($EreignisID,$name."_EVENT");
      IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
     	}
   IPS_SetEventActive($EreignisID,true);
   IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	/* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
   IPS_SetEventCyclicTimeBounds($EreignisID,$now+$delay,0);
   IPS_SetEventCyclicDateBounds($EreignisID,$now+$delay,0);
   IPS_SetEventScript($EreignisID,$command);
	}

/*********************************************************************************************/

function Anwesenheit()
	{
	global $AnwesenheitssimulationID;

   /* Funktion um Anwesenheitssimulation ein und auszuschalten */
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
	}

/*********************************************************************************************/

function Status()
	{
	global $params,$speak_config;

   /* bei einer Statusaenderung oder Aktualisierung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,false',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,true,20',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,timer#dawn-23:45',),       			*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on#true,off#false,cond#xxxxxx',),       				*/

  	$status=GetValue($_IPS['VARIABLE']);
   $delayValue=0; $speak="Status";
  	$moduleParams2 = explode(',', $params[2]);
	//print_r($moduleParams2);
	$parges=array();
	switch (count($moduleParams2))
	   {
	   case "5":
	   case "4":
			$i=3;
			while ($i<count($moduleParams2))
			   {
				$params=explode(":",$moduleParams2[$i]);
				if (count($params)>1)
         		{
					$parges=parseParameter($params,$parges);
				   }
				$i++;
			   }
	   case "3":
			$params_three=explode(":",$moduleParams2[2]);
			if (count($params_three)>1)
				{
				$parges=parseParameter($params_three,$parges);
				}
			else
			   {
				$delayValue=(integer)$params_three[0];
				}
	   case "2":
	   	$params_two=explode(":",$moduleParams2[1]);
			if (count($params_two)>1)
				{
				$parges=parseParameter($params_two,$parges);
				}
			else
			   {
				if (strtoupper($params_two[0])=="TRUE") { $status=true;};
				if (strtoupper($params_two[0])=="FALSE") { $status=false;};
			   }
	   case "1":
	   	$params_one=explode(":",$moduleParams2[0]);
			if (count($params_one)>1)
				{
				$parges=parseParameter($params_one,$parges);
				}
			else
			   {
	      	$SwitchName=$params_one[0];
				}
	      break;
		default:
			echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
		   break;
		}
	foreach ($parges as $befehl)
	   {
		switch (strtoupper($befehl[0]))
		   {
		   case "OID":
		   $switchOID=$befehl[1];
				break;
		   case "NAME":
			   $SwitchName=$befehl[1];
				break;
		   case "ON":
		      $value_on=$befehl[1];
		      $i=2;
		      while ($i<count($befehl))
		         {
		         if (strtoupper($befehl[$i])=="MASK")
		            {
		            $mask_on=$befehl[$i++];
		            }
		         $i++;
		         }
				break;
		   case "OFF":
		      $value_off=$befehl[1];
		      $i=2;
		      while ($i<count($befehl))
		         {
		         if (strtoupper($befehl[$i])=="MASK")
		            {
		            $mask_off=$befehl[$i++];
		            }
		         $i++;
		         }
				break;
		   case "DELAY":
				$delayValue=(integer)$befehl[1];
				break;
		   case "LEVEL":
				$levelValue=(integer)$befehl[1];
				break;
		   case "SPEAK":
				$speak=$befehl[1];
				break;

			}
		} /* ende foreach */
	if ($status===true)
		{
		IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit '.$SwitchName.' und true und Delay '.$delayValue);
		}
	else
	 	{
	  	IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit '.$SwitchName.' und false und Delay '.$delayValue);
		}
	if (isset($levelValue)==true)
	 	{
	  	IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit Level '.$levelValue);
		}

	$command="include(\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n";


	$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
	$switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
	$groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);

	$result=@IPS_GetVariableIDByName($SwitchName,$switchCategoryId);
	if ($result==false)
	   {
		$result=@IPS_GetVariableIDByName($SwitchName,$groupCategoryId);
		if ($result==false)
	   	{
	   	}
	   else   /* Wert ist eine Gruppe */
	      {
  	   	$command.="IPSLight_SetGroupByName(\"".$SwitchName."\", false);";
	     	if ($status===true)
	     	   {
	     	   IPSLight_SetGroupByName($SwitchName,true);
		     	}
	  		else
	  	   	{
	     	   IPSLight_SetGroupByName($SwitchName,false);
		    	}
		   }
		}
	else     /* Wert ist ein Schalter */
	   {
     	$command.="IPSLight_SetSwitchByName(\"".$SwitchName."\", false);";
	  	if ($status===true)
	  	   {
    	   IPSLight_SetSwitchByName($SwitchName,true);
			if (isset($levelValue)==true)
	 			{
				$lightManager = new IPSLight_Manager();
				$switchId = $lightManager->GetSwitchIdByName($SwitchName."#Level");
				$lightManager->SetValue($switchId, $levelValue);
				}
	     	}
		else
	   	{
     	   IPSLight_SetSwitchByName($SwitchName,false);
	     	}
	  }

   if ($delayValue>0)
      {
      setEventTimer($SwitchName,$delayValue,$command);
      }
      
	/* Sprachausgabe auch noch anschauen. wichtig, erst schnelle Reaktionszeit */
	If ($params[0]=="OnUpdate")
		{
	  	if ($speak_config["Parameter"][0]=="On") {
			tts_play(1,'Taster wurde gedrueckt.','',2);
			}
		}
	else
		{
		if ($status)
			{
			if ($speak_config["Parameter"][0]=="On")
				{
				tts_play(1,'Wert '.$speak.' geht auf ein.','',2);
				}
			}
		else
			{
		  	if ($speak_config["Parameter"][0]=="On")  {
				tts_play(1,'Wert '.$speak.' geht auf aus.','',2);
				}
			}
		}
	}

/*********************************************************************************************/
function statusRGB()
	{
	global $params;

   /* allerlei Spielereien mit einer RGB Anzeige */

   /* bei einer Statusaenderung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on#true,off#false,timer#dawn-23:45',),       			*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on#true,off#false,cond#xxxxxx',),       				*/


  	$status=GetValue($_IPS['VARIABLE']);
  	//tts_play(1,'Anwesenheit Status geht auf '.$status,'',2);
  	$moduleParams2 = explode(',', $params[2]);
   //IPSLight_SetSwitchByName($moduleParams2[0],$status);
   $lightManager = new IPSLight_Manager();
	$switchOID = $lightManager->GetSwitchIdByName($moduleParams2[0].'#Color');
	$params_on=explode(":",$moduleParams2[1]);
	$params_off=explode(":",$moduleParams2[2]);

  	//Farbe per RGB(Hex)-Wert setzen
	$wert=count($params_on);
	switch ($wert)
		{
	   case "1":
			if ($status==true)
			   {
			   $lightManager->SetRGB($switchOID, $moduleParams2[1]);
			   }
			break;
		case "2":
		   if (strtoupper($params_on[0])=="ON")
		      {
			   if ($status==true)
			      {
				   $lightManager->SetRGB($switchOID, $params_on[1]);
				   }
		     	}
		  	if (strtoupper($params_on[0])=="OFF")
			   {
			 	if ($status==false)
				   {
				   $lightManager->SetRGB($switchOID, $params_on[1]);
				   }
				}
			break;
		case "4":
		  	if (strtoupper($params_on[2])=="MASK")
			   {
			   $mask=hexdec($params_on[3]);
			   $notmask=~($mask)&0xFFFFFF;
			   }
			else
			  	{
				$mask=0xFFFFFF;
				$notmask=0;
				}
			if (strtoupper($params_on[0])=="ON")
			   {
			  	if ($status==true)
				   {
				   $new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_on[1] & $mask);
				  	$lightManager->SetRGB($switchOID, $new);
					}
				}
			if (strtoupper($params_on[0])=="OFF")
			   {
			  	if ($status==false)
				   {
				   $new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_on[1] & $mask);
				  	$lightManager->SetRGB($switchOID, $new);
					}
				}
			break;
		}
	switch (count($params_off))
	   {
	   case "1":
		   if ($status==false)
		      {
			   $lightManager->SetRGB($switchOID, $moduleParams2[2]);
			   }
			break;
		case "2":
		   if (strtoupper($params_off[0])=="ON")
		      {
			   if ($status==true)
			      {
				   $lightManager->SetRGB($switchOID, $params_off[1]);
				   }
		     	}
		   if (strtoupper($params_off[0])=="OFF")
		      {
			   if ($status==false)
			      {
				   $lightManager->SetRGB($switchOID, $params_off[1]);
				   }
		     	}
			break;
		case "4":
		 	if (strtoupper($params_off[2])=="MASK")
			   {
			   $mask=hexdec($params_off[3]);
			   $notmask=~($mask)&0xFFFFFF;
			   }
			else
			   {
			   $mask=0xFFFFFF;
			   $notmask=0;
			   }
			if (strtoupper($params_off[0])=="ON")
			   {
			  	if ($status==true)
				   {
				   $new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_off[1] & $mask);
					$lightManager->SetRGB($switchOID, $new);
					}
				}
			if (strtoupper($params_on[0])=="OFF")
			   {
			  	if ($status==false)
					{
					$new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_off[1] & $mask);
					$lightManager->SetRGB($switchOID, $new);
					}
				}
			break;
		}
	}

/*********************************************************************************************/

function SwitchFunction()
	{
	
	global $params2,$speak_config;
	
	/* Anlegen eines Schalters in der GUI der Autosteuerung, Bedienelemente können angegeben werden */
	$switchStatus=GetValue($_IPS['VARIABLE']);
	$moduleParams2 = explode(',', $params[2]);
	if ($switchStatus==0)
	   {
		IPSLight_SetSwitchByName($params[2],false);
	}
	if ($switchStatus==1)
	   {
		IPSLight_SetSwitchByName($params[2],true);
	  	}
	if ($speak_config["Parameter"][0]=="On")
		{
		tts_play(1,"Schalter ".$params[2]." manuell auf ".$switchStatus.".",'',2);
		}
	}

/*********************************************************************************************/

function Ventilator1()
	{
	global $categoryId_Autosteuerung,$params;
	
	$VentilatorsteuerungID = IPS_GetObjectIDByName("Ventilatorsteuerung",$categoryId_Autosteuerung);

   /* Funktion um Ventilatorsteuerung ein und aus zuschalten */
	$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
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
   }

/*********************************************************************************************/

function Ventilator()
	{
	global $params,$categoryId_Autosteuerung,$speak_config;

	$VentilatorsteuerungID = IPS_GetObjectIDByName("Ventilatorsteuerung",$categoryId_Autosteuerung);

  	$moduleParams2 = explode(',', $params[2]);
	if (GetValue($VentilatorsteuerungID)==0)
	   {
  		IPSLight_SetSwitchByName($moduleParams2[0],false);
	   }
	if (GetValue($VentilatorsteuerungID)==1)
	   {
  		IPSLight_SetSwitchByName($moduleParams2[0],true);
	   }
	if (GetValue($VentilatorsteuerungID)==2)
	   {
      /* wenn Parameter ueberschritten etwas tun */
   	$temperatur=GetValue($_IPS['VARIABLE']);
   	if ($speak_config["Parameter"][0]=="On")
  		   {
  			tts_play(1,'Temperatur im Wohnzimmer '.floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10)." Grad.",'',2);
  			}
     	//print_r($moduleParams2);
     	if ($moduleParams2[2]=="true") {$switch_ein=true;} else {$switch_ein=false; }
  	  	if ($moduleParams2[4]=="true") {$switch_aus=true;} else {$switch_aus=false; }
  		$lightManager = new IPSLight_Manager();
		$switchID=$lightManager->GetSwitchIdByName($moduleParams2[0]);
		$status=$lightManager->GetValue($switchID);
     	if ($temperatur>$moduleParams2[1])
  	  	   {
			if ($status==false)
			   {
	     		IPSLight_SetSwitchByName($moduleParams2[0],$switch_ein);
		     	if ($speak_config["Parameter"][0]=="On")
	   	   	{
	     			tts_play(1,"Ventilator ein.",'',2);
		  			}
		  		}
	  		}
	  	if ($temperatur<$moduleParams2[3])
	  	   {
			if ($status==true)
			   {
		     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_aus);
	   	  	if ($speak_config["Parameter"][0]=="On")
	  	   		{
	  				tts_play(1,"Ventilator aus.",'',2);
	  				}
	  			}
	    	}
		} /* ende if Auto */
	}

/*********************************************************************************************/


function Parameter()
	{
	global $speak_config,$params;
	
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
	$lightManager = new IPSLight_Manager();
	$switchID=$lightManager->GetSwitchIdByName($moduleParams2[0]);
	$status=$lightManager->GetValue($switchID);
	if ($temperatur>$moduleParams2[1])
	   {
		if ($status==false)
		   {
	     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_ein);
	     	if ($speak_config["Parameter"][0]=="On")
	  	   	{
	  			tts_play(1,"Ventilator ein.",'',2);
	  			}
	  		}
	  	}
  	if ($temperatur<$moduleParams2[3])
  	   {
		if ($status==true)
		   {
	     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_aus);
	     	if ($speak_config["Parameter"][0]=="On")
	  	   	{
	  			tts_play(1,"Ventilator aus.",'',2);
				}
			}
	  	}
	}

/*********************************************************************************************/

function parseParameter($params,$result=array())
	{
	if (count($params)>1)
		{
		$result[$params[0]]=$params;
		}
	return($result);
	}


/*********************************************************************************************/

function test()
	{
	global $AnwesenheitssimulationID;

	echo "Anwesenheitsimulation ID : ".$AnwesenheitssimulationID." \n";
	}
	
?>
