<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager'."\n";
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

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	echo "Nachrichten Script     ID:".$NachrichtenScriptID."\n";
	echo "Nachrichten Input      ID: ".$NachrichtenInputID."\n";
	/* logging in einem File und in einem String am Webfront */
	$log_Autosteuerung=new Logging("C:\Scripts\Log_Autosteuerung.csv",$NachrichtenInputID);
	}
else break;

/* Dummy Objekte für typische Anwendungsbeispiele erstellen, geht nicht automatisch */
/* könnte in Zukunft automatisch beim ersten Aufruf geschehen */


$name="Ansteuerung";
$categoryId_Autosteuerung  = CreateCategory($name, $CategoryIdData, 10);
$AnwesenheitssimulationID = IPS_GetObjectIDByName("Anwesenheitssimulation",$categoryId_Autosteuerung);
$AnwesenheitserkennungID = IPS_GetObjectIDByName("Anwesenheitserkennung",$categoryId_Autosteuerung);
$StatusAnwesendID=IPS_GetObjectIDByName("StatusAnwesend",$AnwesenheitserkennungID);
$StatusAnwesendZuletztID=IPS_GetObjectIDByName("StatusAnwesendZuletzt",$AnwesenheitserkennungID);

/* wichtigste Parameter vorbereiten */

$configuration = Autosteuerung_GetEventConfiguration();
$scenes=Autosteuerung_GetScenes();
//print_r($configuration);

$speak_config=Autosteuerung_Speak();

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
$register=new AutosteuerungHandler($scriptIdAutosteuerung);
$operate=new AutosteuerungOperator();
$auto=new Autosteuerung();

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/





/*********************************************************************************************/
/*                                                                                           */
/* Programmfunktionen             																				*/
/*                                                                                           */
/*********************************************************************************************/

if ($_IPS['SENDER']=="Variable")
	{
	$variableID=$_IPS['VARIABLE'];
	$value=GetValue($variableID);
	$configuration = Autosteuerung_GetEventConfiguration();
	/* eine Variablenaenderung ist aufgetreten */
	IPSLogger_Dbg(__file__, 'Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
	$log_Autosteuerung->LogMessage('Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
	//$log_Autosteuerung->LogNachrichten('Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
	if (array_key_exists($variableID, $configuration)) {
		/* es gibt einen Eintrag fuer das Event */

		$params=$configuration[$variableID];
		$wert=$params[1];
		/* 0: OnChange or OnUpdate, 1 ist die Klassifizierung, Befehl 2 sind Parameter */
  		//tts_play(1,$_IPS['VARIABLE'].' and '.$wert,'',2);
		switch ($wert)    {
			/*********************************************************************************************/
		   case "iTunes":
		      iTunesSteuerung();
		      break;
			/*********************************************************************************************/
		   case "GutenMorgenWecker":
		      GutenMorgenWecker();
		      break;
			/*********************************************************************************************/
		   case "Anwesenheit":
		      Anwesenheit($params,$value);
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
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate',	'Status',	'ArbeitszimmerLampe,	true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,cond#xxxxxx',),       					*/
				Status($params,$value);
				break;
			/*********************************************************************************************/
		   case "StatusRGB":
		      statusRGB($params,$value);
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
	  	if ($speak_config["Parameter"][1]=="Debug") {
  		tts_play(1,'Fehler, Taste gedrueckt mit Zahl '.$variable,'',2);
  		   }
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

	
	$StatusAnwesend=$operate->Anwesend();		
	SetValue($StatusAnwesendID,$StatusAnwesend );
	
	if ( (GetValue($AnwesenheitssimulationID)==1) || ( (GetValue($AnwesenheitssimulationID)==2) && ($operate->Anwesend()==false) )) 
		{
		//Anwesenheitssimulation aktiv, bedeutet ein (1) oder auto (2), bei auto wird bei Anwesenheit nicht simuliert
		echo "\nAnwesenheitssimulation eingeschaltet. \n";
		IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
		//print_r($scenes);					
		}
	else
		{
		IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion nicht aktiv.');
		}

	foreach($scenes as $scene)
		{
		if (isset($scene["TYPE"]))
			{
			if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
				{
				/*****************************************************
				 *
				 * Typ Anwesenheitssimulation
				 *
				 */
				if ( (GetValue($AnwesenheitssimulationID)==1) || ( (GetValue($AnwesenheitssimulationID)==2) && ($operate->Anwesend()==false) ) ) 
 					{
					SetValue($StatusAnwesendZuletztID,true);
					$switch = $auto->timeright($scene);
					$now = time();
					if ($switch)
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
						IPS_SetEventCyclicTimeBounds($EreignisID,$auto->now+$scene["EVENT_DURATION"]*60,0);
						IPS_SetEventCyclicDateBounds($EreignisID,$auto->now+$scene["EVENT_DURATION"]*60,0);
						if ($scene["EVENT_CHANCE"]==100)
							{
							echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
							IPS_SetEventCyclicTimeBounds($EreignisID,$auto->timeStop,0);
							}
						if (isset($scene["EVENT_IPSLIGHT"]))
							{
							IPS_SetEventScript($EreignisID, "include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n"."IPSLight_SetSwitchByName(\"".$scene["EVENT_IPSLIGHT"]."\", false);");
							}
						else
							{
							IPS_SetEventScript($EreignisID,"include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n"."IPSLight_SetGroupByName(\"".$scene["EVENT_IPSLIGHT_GRP"]."\", false);");
							}
						}  /* ende switch */
					}	/*ende AWS einbgeschaltet */
				else
					{
					
					/* wenn die Anwesenheitssimulation ausgeschaltet wird etwas unternehmen */
					
					/* nur bei Änderung des Status etwas unternehmen */
					if (GetValue($StatusAnwesendZuletztID)==true)
						{	
						$EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
						if ($EreignisID != false)
							{
							IPS_SetEventActive($EreignisID,false);
							}
						/* aber auch die Lampen ausschalten, sonst bleiben sie eingeschaltet */
						if (isset($scene["EVENT_IPSLIGHT"]))
							{
							echo "    Objekt : ".$scene["EVENT_IPSLIGHT"]." ausschalten, es ist Ende AWS\n";
							IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], false);
							}
						else
							{
							if (isset($scene["EVENT_IPSLIGHT_GRP"]))
								{
								echo "    Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"]." ausschalten, es ist Ende AWS\n";
								IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
								}
							}
						SetValue($StatusAnwesendZuletztID,false);	
						}
					} /*ende AWS ausgeschaltet */
				} /* Ende AWS Szene */
			else
				{
				/*****************************************************
				 *
				 * Typ normale Timersteuerung
				 *
				 */
					$switch = $auto->timeright($scene);
					$now = time();
					if ($switch)
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
						IPS_SetEventCyclicTimeBounds($EreignisID,$auto->now+$scene["EVENT_DURATION"]*60,0);
						IPS_SetEventCyclicDateBounds($EreignisID,$auto->now+$scene["EVENT_DURATION"]*60,0);
						if ($scene["EVENT_CHANCE"]==100)
							{
							echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
							IPS_SetEventCyclicTimeBounds($EreignisID,$auto->timeStop,0);
							}
						if (isset($scene["EVENT_IPSLIGHT"]))
							{
							IPS_SetEventScript($EreignisID, "include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n"."IPSLight_SetSwitchByName(\"".$scene["EVENT_IPSLIGHT"]."\", false);");
							}
						else
							{
							IPS_SetEventScript($EreignisID,"include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n"."IPSLight_SetGroupByName(\"".$scene["EVENT_IPSLIGHT_GRP"]."\", false);");
							}
						}  /* ende switch */
					
				}	/* ende ifelse AWS */		
			}   /* ende isset Type */		
		} /* end of foreach */
		
	} /* Endif Timer */

/********************************************************************************************************************************
 *
 * Execute aufgerufen, simuliert die Paranetereingaben
 *
 +
 *************************************************************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	//IPSLogger_Dbg(__file__, 'Exec aufgerufen ...');
	test();
	/* von der Konsole aus gestartet */
	echo "--------------------------------------------------\n";
	echo "        EXECUTE\n";
	echo "\nEingestellte Programme:\n\n";
	foreach ($configuration as $key=>$entry)
		{
		echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
		echo "Eintrag fuer : ".$key." (".IPS_GetName(IPS_GetParent($key)).".".IPS_GetName($key).") ".$entry[0].",".$entry[1]."\n\n";
		//print_r($entry);
		//print_r($auto->ParseCommand($entry));
		switch ($entry[1])
			{
			case "Anwesenheit":
	         $status=Anwesenheit($entry,0,true);  // Simulation aktiv
	         echo "Resultat von Evaluierung Anwesenheit Funktion ausgeben.\n"; 
				print_r($status);
				break;
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
	         $status=Status($entry,0,true);  // Simulation aktiv
	         print_r($status);
				break;

	      case "StatusRGB":
	         $status=StatusRGB($entry,0,true);  // Simulation aktiv
	         print_r($status);

/*
	         echo "************************\n";
	      	$status=true;
			   $lightManager = new IPSLight_Manager();
				$moduleParams2 = explode(",",$entry[2]);
				echo "Anzahl Parameter in Param2: ".count($moduleParams2)."\n";
				print_r($moduleParams2);
				// wenn nur ein oder zwei Parameter, dann ignorieren
				$parges=array();
				if (count($moduleParams2)>2)
				   {
				   // Default Werte setzen
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
						} // ende foreach
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
*/
				break;
			}
		}
	echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n";		

	/*********************************************************************************************/

	echo "----------------------------------------------\n";
	echo "Status Anwesenheitssimulation : ".(GetValue($AnwesenheitssimulationID))." (0 aus 1 ein 2 auto)\n";
	echo "Festellung der Anwesenheit : ".($operate->Anwesend()?"Anwesend":"Abwesend")."\n"; 	
	echo "\nEingestellte Anwesenheitssimulation:\n\n";
	foreach($scenes as $scene)
		{
		if (isset($scene["TYPE"]))
			{
			if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
				{		
				echo "  Anwesenheitssimulation Szene : ".$scene["NAME"]."\n";
				}
			else
				{		
				echo "  Timer Szene : ".$scene["NAME"]."\n";
				}
			}	
		$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
		if ($actualTime[0]=="sunset") {$actualTime[0]=date("H:i",$auto->sunset);}
		if ($actualTime[1]=="sunrise") {$actualTime[1]=date("H:i",$auto->sunrise);}
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

	echo "----------------------------------------------\n";
	echo "\nProgramme für Schalter registrieren nach OID des Events.\n";

	$AutoConfiguration = Autosteuerung_GetEventConfiguration();
	foreach ($AutoConfiguration as $variableId=>$params)
		{
		echo "Create Event für ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
		$register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
		}

	echo "----------------------------------------------\n";
	echo $inst_modules."\n\n";
	echo "----------------------------------------------\n";
	echo "Category App           ID:".$CategoryIdApp."\n";
	echo "Category Data          ID:".$CategoryIdData."\n";
	echo "Category Script        ID:".$scriptId."\n";
	
	} /* Ende Execute */

/*********************************************************************************************/

/*  setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60)                                */

function setEventTimer($name,$delay,$command)
	{
	echo "Jetzt wird der Timer gesetzt : ".$name."_EVENT"."\n";
	IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.$command);	
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

function Anwesenheit($params,$status,$simulate=false)
	{
	global $AnwesenheitssimulationID;

	IPSLogger_Dbg(__file__, 'Aufruf Routine Anwesenheit mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	$parges=$auto->ParseCommand($params);
	$command=array(); $entry=1;
	//print_r($parges);
	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=true;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */
		$switch=true; $delayValue=0; $speak="Status"; $switchOID=0; // fuer Kompatibilitaetszwecke
		
		foreach ($Kommando as $befehl)
			{
			//echo "Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				case "ALARM":
					if ($auto->CategoryId_SchalterAlarm !== false)
						{
						$command[$entry]["OID"]=$auto->CategoryId_SchalterAlarm;
						//echo "Befehl ALARM : ".$auto->CategoryId_SchalterAlarm." \n";
						}
					if ( (strtoupper($befehl[1]) == "ON") || (strtoupper($befehl[1]) == "TRUE") )
						{
						$command[$entry]["STATUS"]=true;
						}
					if ( (strtoupper($befehl[1]) == "OFF") || (strtoupper($befehl[1]) == "FALSE") )
						{
						$command[$entry]["STATUS"]=false;
						}
					break;
				case "ANWESEND":
					if ($auto->CategoryId_SchalterAnwesend !== false)
						{
						$command[$entry]["OID"]=$auto->CategoryId_SchalterAnwesend;
						//echo "Befehl ANWESEND : ".$auto->CategoryId_SchalterAnwesend." \n";
						}					
					if ( (strtoupper($befehl[1]) == "ON") || (strtoupper($befehl[1]) == "TRUE") )
						{
						$command[$entry]["STATUS"]=true;
						}
					if ( (strtoupper($befehl[1]) == "OFF") || (strtoupper($befehl[1]) == "FALSE") )
						{
						$command[$entry]["STATUS"]=false;
						}
					break;
				default:
					$command[$entry]=$auto->EvaluateCommand($befehl,$command[$entry]);
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry]);
		if (isset($result["DELAY"])==true)
			{
			echo ">>>Ergebnis ExecuteCommand, DELAY.\n";			
			print_r($result);
			if ($simulate==false)
				{
				setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
				}
			}
		$entry++;	
		} /* Ende foreach Kommando */	
	
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
	
	return($command);	
	}

/*********************************************************************************************/

function iTunesSteuerung()
	{

	}

/*********************************************************************************************/

function GutenMorgenWecker()
	{

	}

/********************************************************************************************
 *
 *  Statusbefehle
 *
 *  egal ob bei einer variablenänderung oder bei einem Update werden verschiedene Befehle die im Parameterfeld stehen abgearbeitet
 *  IpsLight Name und optional ob ein, aus und am Ende noch ein delay kann ohne Spezialbefehle eingegeben werden
 *
 * zum Beispiel:  'OnUpdate','Status','WohnzimmerKugellampe,toggle',
 *                 siehe auch Beispiele iweiter unten 
 *
 *  komplizierte Algorithmen werden immer mit befehl:parameter eigegeben
 *
 *  OID:12345        Definition des zu schaltenden objektes
 *  NAME:Wohnzimmer  Definition des zu schaltenden IPSLight Schlaters, Gruppe oder programms, wird automatisch der reihe nach auf
 *                       	Vorhandensein überprüft
 * 								Wenn keine Angabe wird der Status des Objektes (OnUpdate/OnChange) für den neuen Schaltzustand verwendet
 *
 *  DELAY:TIME      ein timer wird aktiviert, nach Ablauf von TIME (in Sekunden) wird der Schalter ausgeschaltet
 *  ENVELOPE:TIME   ein Statuswert wird so verschliffen, das nur selten tatsächlich der Schalter aktiviert wird
 *                      immer bei Wert 1 wird der timer neu aktiviert, ein Ablaufen des Timers führt zum Ausschalten
 *  MONITOR:ON|OFF|STATUS  die custom function monitorOnOff wird aufgerufen
 *  MUTE:ON|OFF|STATUS
 *
 ************************************************************************************************/

function Status($params,$status,$simulate=false)
	{
	global $speak_config;
	
	IPSLogger_Dbg(__file__, 'Aufruf Routine Status mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);

   /* bei einer Statusaenderung oder Aktualisierung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,false',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,true,20',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,timer#dawn-23:45',),       			*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,if:light',),       				*/

	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	
	/* Befehlsgruppe zerlegen zB von params : [0] OnChange [1] Status [2] name:Stiegenlicht,speak:Stiegenlicht
	 * aus [2] name:Stiegenlicht,speak:Stiegenlicht wird
	 *          [0] name:Stiegenlicht [1] speak:Stiegenlicht
	 *
	 * Parameter mit : enthalten Befehl:Parameter
	 */
	 

/*   >>>>>>>>>>>>>>>   delete

	$moduleParams2=Array();
  	$moduleParams2 = explode(',', $params[2]);  

	// in parges werden alle Parameter erfasst und abgespeichert. nur mehr Sonderbefehle werden hier abgeabrbeitet 
	$parges=array();
	switch (count($moduleParams2))
	   {
	   case "6":
	   case "5":
	   case "4":
	   case "3":
	   case "2":
	   	$params_two=explode(":",$moduleParams2[1]);
			if (count($params_two)>1)
				{
				}
			else
			   {
				//$parges["STATUS"]=$params_two[0];
				if (strtoupper($params_two[0])=="TRUE") { $status=true;};
				if (strtoupper($params_two[0])=="FALSE") { $status=false;};
				if (strtoupper($params_two[0])=="TOGGLE")
					{
					if (strtoupper($params[0])=="ONUPDATE")
					   {
					   // Bei OnUpdate herausfinden wie der Wert der Variable ist 
				   		$params_one=explode(":",$moduleParams2[0]);
						if (count($params_one)==1)
						   {
	      				$lightName=$params_one[0];
							$lightManager = new IPSLight_Manager();
							$switchId = $lightManager->GetSwitchIdByName($lightName);
							$status=!$lightManager->GetValue($switchId);
							}
					   }
					else
					   {
					   // bei OnChange nur invertieren, wenn OnUpdate bei einem Taster dann hat dieser Wert wenig zu sagen 
						$status=!$status;          
						}
					};
			   }
	   case "1":
	      break;
		default:
			echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
		   break;
		}
*/		
	
	$parges=$auto->ParseCommand($params);
	
	if ($simulate==true) 
		{
		echo "***Simulationsergebnisse (parges):";
		// wird eh schon oben ausgegeben
		print_r($parges);
		}
	
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */

	foreach ($parges as $Kommando)
		{
		$result=array();   /* Werte vom vorigen Befehl wieder loeschen */
		$result["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$result["STATUS"]=true;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */
		$switch=true; $delayValue=0; $speak="Status"; $switchOID=0; // fuer Kompatibilitaetszwecke
		
		foreach ($Kommando as $befehl)
			{
			//print_r($befehl);
			/* im uebergeordneten Element steht ein Index und darunter der Befehl als Unterobjekt im array , 
			 * funktiert auch schon für ; Befehl, damit können mehrere Befehle nacheinander abgearbeitet werden 
			 */
			switch (strtoupper($befehl[0]))
				{
				case "OID":			/* muss noch implementiert werden , wie switch name nur statt IPSLight die OID */
					$switchOID=$befehl[1];
					$result["OID"]=$SwitchOID;
					break;
				
				case "NAME":		/* IPSLight identifier der verändert wird */
					$SwitchName=$befehl[1];
					$result["NAME"]=$SwitchName;
					break;
				
				case "STATUS":    /* für die Kurzbefehle, wird normalerweise durch die Befehle On und OFF ersetzt */
					if (strtoupper($befehl[1])=="TRUE") { $status=true;};
					if (strtoupper($befehl[1])=="FALSE") { $status=false;};
					if (strtoupper($befehl[1])=="TOGGLE")
						{
						if (strtoupper($params[0])=="ONUPDATE")
							{
							/* Bei OnUpdate herausfinden wie der Wert der Variable ist */
							//print_r($result);
							$lightName=$result["NAME"];
							$switchId = $lightManager->GetSwitchIdByName($lightName);
							$status=!$lightManager->GetValue($switchId);
							}
						else
							{
							/* bei OnChange nur invertieren, wenn OnUpdate bei einem Taster dann hat dieser Wert wenig zu sagen */
							$status=!$status;          
							}
						};
					$result["STATUS"]=$status;	
					break;
					
				case "ON":
					$value_on=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_on=$befehl[$i++];
							$result["ON_MASK"]=$mask_on;
							}
						$i++;
						}
					switch ($value_on)
						{
						case "TRUE":
						case "FALSE":	
							$result["ON"]=$value_on;
							break;
						case "TOGGLE":
							/* Befehl noch nicht implementiert */	
						default:
							break;
						}		
					break;
				
				case "OFF":
					$value_off=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_off=$befehl[$i++];
							$result["OFF_MASK"]=$mask_off;
							}
						$i++;
						}
					switch ($value_off)
						{
						case "TRUE":
						case "FALSE":	
							$result["OFF"]=$value_off;
							break;
						case "TOGGLE":
							/* Befehl noch nicht implementiert */	
						default:
							break;
						}							
					break;
				
				case "DELAY":
					$delayValue=(integer)$befehl[1];
					$result["DELAY"]=$delayValue;
					break;
				
				case "ENVELOPE":
					$envelValue=(integer)$befehl[1];
					$result["ENVEL"]=$envelValue;
					break;
				
				case "LEVEL":
					$levelValue=(integer)$befehl[1];
					$result["LEVEL"]=$levelValue;
					break;
				
				case "SPEAK":
					$speak=$befehl[1];
					$result["SPEAK"]=$speak;
					break;
				
				case "MONITOR":
					$monitor=$befehl[1];
					if ($monitor=="STATUS")
						{
						if ($status==true)
							{
							$result="ON";
							$result["MONITOR"]=$monitor;
							}
						else
							{
							$result="OFF";
							$result["MONITOR"]=$monitor;
							}
						}
					else
						{
						$result["MONITOR"]=$monitor;
						}
					break;
				
				case "MUTE":
					$mute=$befehl[1];
					if ($mute=="STATUS")
						{
						if ($status==true)
							{
							$mute="ON";
							$result["MONITOR"]=$mute;
							}
						else
							{
							$mute="OFF";
							$result["MONITOR"]=$mute;
							}
						}
					else
						{
						$result["MUTE"]=$mute;
						}
					break;
				
				case "IF":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
					$cond=strtoupper($befehl[1]);
					$result["COND"]=$cond;
					if ($cond=="LIGHT")
						{
						/* nur Schalten wenn es hell ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if ($auto->isitdark())
							{
							unset($SwitchName);
							unset($speak);
							$switch=false;
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist dunkel ');
							}
						}
					elseif ($cond=="DARK")
						{
						/* nur Schalten wenn es dunkel ist, geschaltet wird nur wenn ein variablenname bekannt ist */
				  		if ($auto->isitlight())
							{
							unset($SwitchName);
							unset($speak);
							$switch=false;
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist hell ');
							}
						}
					else
						{  /* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
						$checkId = $lightManager->GetSwitchIdByName($cond);
						$statusCheck=$lightManager->GetValue($checkId);
						$result["SWITCH"]=$statusCheck;	
						}			
					break;
				
				default:
					echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
					break;				
				}  /* ende switch */
			} /* ende foreach Befehl*/

		if ( (isset($result["NAME"])==true) && ($result["SWITCH"]==true) )
			{
			/* wenn ein Name definiert ist und keine if Anweisung das Schalten verhindert geht es hier los */ 
			if ($result["STATUS"]===true)
				{
				IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit '.$SwitchName.' und true und Delay '.$delayValue." Funktion : ".$params[0]." : ".$params[1]." : ".$params[2]);
				}
			else
				{
				/* ein Tastendruck ist immer false, hier ist nur die Aktualisierung interessant */
				IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit '.$SwitchName.' und false und Delay '.$delayValue." Funktion : ".$params[0]." : ".$params[1]." : ".$params[2]);
				}
			
			if (isset($result["LEVEL"])==true)
				{
				IPSLogger_Dbg(__file__, 'Status ist ausgewaehlt mit Level '.$levelValue);
				}

			$result=$auto->ExecuteCommand($result,$simulate);

			/* Ein Delaywert ist definiert, den Eventtimer mit dem entsprechenden vorher eingesammelten Befehl starten */
			if ($delayValue>0)
				{
				if ($simulate==false)
					{
					setEventTimer($SwitchName,$delayValue,$result["COMMAND"]);
					}
				}
			} // Ende isset Switchname

		if (isset($monitor)==true)
			{
			if (function_exists('monitorOnOff')==true)
				{
				monitorOnOff($result["MONITOR"]);
				}
			}

		if (isset($result["OID"])==true)
			{
			/* Kein IPSLight Objekt sondern normales Objekt das gesetzt wird */
			}
	
		/* Sprachausgabe durchführen, immer letzter befehl, sonst ist die Reaktion zu langsam */
		if (($simulate==false) && (isset($mute)==false))
			{
			if (isset($speak)==true)
				{
				if ($speak_config["Parameter"][0]=="On") 
					{
					if ($speak != "Status")
						{
						tts_play(1,$speak,'',2);    // Soundkarte 1, mit diesem Ansagetext, kein Ton, Modus 2
						}
					}
				}
			/* Debug Sprachausgabe auch noch anschauen. wichtig, erst schnelle Reaktionszeit */
			If ($params[0]=="OnUpdate")
				{
				if ($speak_config["Parameter"][1]=="Debug") {
					tts_play(1,"Taster ".$speak."wurde gedrueckt.",'',2);
					}
				}
			else
				{
				if ($status)
					{
					if ($speak_config["Parameter"][1]=="Debug")
						{
						tts_play(1,'Der Wert für '.$speak.' geht auf ein.','',2);
						}
					}
				else
					{
					if ($speak_config["Parameter"][1]=="Debug")  {
						tts_play(1,'Der Wert für '.$speak.' geht auf aus.','',2);
						}
					}
				}
			}
		$ergebnis[]=$result;
		
		} /* Kommando fuer Kommando abarbeiten   */
	return $ergebnis;
	}

/*********************************************************************************************
 *  StatusRGB
 *
 *
 *********************************************************************************************/

function statusRGB($params,$status,$simulate=false)
	{
   /* allerlei Spielereien mit einer RGB Anzeige */

   /* bei einer Statusaenderung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on#true,off#false,timer#dawn-23:45',),       			*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on#true,off#false,cond#xxxxxx',),       				*/

	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$result=array();  	/* in result wird die Zusammenfassung für die Simulation gegeben */

	/* in parges werden alle Parameter erfasst und abgespeichert */
	$parges=array();
	/* params[0] is OnUpdate oder OnChange und params[1] hat uns zu diesem Befehl geführt 		*/
	/* in params[2] ist die Ausführung versteckt                                             	*/
	$value=$status; 
  	$moduleParams2 = explode(',', $params[2]);
	switch (count($moduleParams2))
	   {
	   case "9":
	   case "8":
	   case "7":
	   case "6":
	   case "5":
	   case "4":
			$i=3;
			while ($i<count($moduleParams2))
			   {
				$params_more=explode(":",$moduleParams2[$i]);
				if (count($params)>1)
         		{
					$parges=parseParameter($params_more,$parges);
				   }
				$i++;
			   }
	   case "3":      /* wenn drei Parameter gibt der dritte vor wann wieder abgeschaltet werden soll */
			$params_three=explode(":",$moduleParams2[2]);
			if (count($params_three)>1)
				{
				$parges=parseParameter($params_three,$parges);
				}
			else
			   {
				$delayValue=(integer)$params_three[0];
				$result["DELAY"]=$delayValue;
				}
	   case "2":         /* wenn zwei Parameter, gibt der zweite vor auf welchen Wert gesetzt werden soll */
	   	$params_two=explode(":",$moduleParams2[1]);
			if (count($params_two)>1)
				{
				$parges=parseParameter($params_two,$parges);
				}
			else
			   {
				if (strtoupper($params_two[0])=="TRUE") { $value=true; };
				if (strtoupper($params_two[0])=="FALSE") { $value=false; };
				if (strtoupper($params_two[0])=="TOGGLE") { $status=!$status;};
			   }
	   case "1":         /* nur ein Parameter, muss der Name des Schalters/Gruppe sein */
	   	$params_one=explode(":",$moduleParams2[0]);
			if (count($params_one)>1)
				{
				$parges=parseParameter($params_one,$parges);
				}
			else
			   {
	      	$SwitchName=$params_one[0];
				$result["NAME"]=$SwitchName;
				}
	      break;
		default:
			echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
		   break;
		}
	if ($simulate==true) {
		//print_r($parges);
		}

	/*-------------------------------------------------------------------------------*/
	/* und danach abgearbeitet 																		*/
	
	$mask=0xFFFFFF;
	$notmask=0;
	
	foreach ($parges as $befehl)
	   {
		switch (strtoupper($befehl[0]))
		   {
		   case "OID":
			   $switchOID=$befehl[1];
				$result["OID"]=$SwitchOID;
				break;
		   case "NAME":
			   $SwitchName=$befehl[1];
				$result["NAME"]=$SwitchName;
				break;
		   case "ON":
		      $value_on=hexdec($befehl[1]);
		      $i=2;
		      while ($i<count($befehl))
		         {
		         if (strtoupper($befehl[$i])=="MASK")
		            {
		            $i++;
		            $mask_on=hexdec($befehl[$i]);
					   $notmask_on=~($mask_on)&0xFFFFFF;
						$result["ON_MASK"]=$mask_on;
				      }
		         $i++;
		         }
				$result["ON"]=$value_on;
				break;
		   case "OFF":
		      $value_off=hexdec($befehl[1]);
		      $i=2;
		      while ($i<count($befehl))
		         {
		         if (strtoupper($befehl[$i])=="MASK")
		            {
		            $i++;
		            $mask_off=hexdec($befehl[$i]);
					   $notmask_off=~($mask_off)&0xFFFFFF;
						$result["OFF_MASK"]=$mask_off;
					   }
		         $i++;
		         }
				$result["OFF"]=$value_off;
				break;
		   case "DELAY":
				$delayValue=(integer)$befehl[1];
				$result["DELAY"]=$delayValue;
				break;
		   case "LEVEL":
				$levelValue=(integer)$befehl[1];
				$result["LEVEL"]=$levelValue;
				break;
		   case "SPEAK":
				$speak=$befehl[1];
				$result["SPEAK"]=$speak;
				break;
		   case "MUTE":
				$mute=strtoupper($befehl[1]);
				$result["MUTE"]=$mute;
				break;
		   case "IF":
				$cond=strtoupper($befehl[1]);
				$result["COND"]=$cond;
				if ($cond=="LIGHT")
				   {
				   if ($auto->isitdark()) {unset($SwitchName);}
				   }
				if ($cond=="DARK")
				   {
				   if ($auto->isitlight()) {unset($SwitchName);}
				   }
				break;
			}
		} /* ende foreach */

	/*-------------------------------------------------------------------------------*/
	/* und schlussendlich ausgeführt 																		*/

	if ( (isset($SwitchName)==true) && (isset($value_on)==false) && (isset($value_off)==false) )
		{
  	   /* eigenwillige Technik. wenn als zweiter Parameter nicht on: oder off: definiert wird, sind keine RGB Werte im Spiel */

		if ($value==true) { $valueChar="true"; } else { $valueChar="false"; }
		/* ohne Switchname kann man nichts schalten */
		if ($status===true)
			{
			IPSLogger_Dbg(__file__, 'StatusRGB Input ist true und '.$SwitchName.' wird '.$valueChar);
			}
		else
	 		{
		 	/* ein Tastendruck ist immer false, hier ist nur die Aktualisierung interessant */
		  	IPSLogger_Dbg(__file__, 'StatusRGB Input ist false und '.$SwitchName.' wird '.$valueChar);
			}
		if (isset($levelValue)==true)
		 	{
	  		IPSLogger_Dbg(__file__, 'StatusRGB ist ausgewaehlt mit Level '.$levelValue);
			}

	   $result["COMMAND"]=switchNameGroup($SwitchName,$value,$simulate);
  	   }
  	else
  	   {
		if (isset($SwitchName)==true)
  		   {
			/* mit Parameter on und/oder off */
		   $lightManager = new IPSLight_Manager();
			$switchOID = $lightManager->GetSwitchIdByName($SwitchName.'#Color');

			if ($status==true)
			   {
			   $new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($value_on & $mask);
		   	}
			else
			   {
	   		$new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($value_off & $mask);
				}
			IPSLogger_Dbg(__file__, 'StatusRGB Input ist true und '.$SwitchName.' mit OID '.$switchOID.' geht auf Wert '.$new);
   	  	if ($simulate==false)
     		   {
				$lightManager->SetRGB($switchOID, $new);
				}
			}
		}
/*

		$params_on=explode(":",$moduleParams2[1]);
		$params_off=explode(":",$moduleParams2[2]);

  	//Farbe per RGB(Hex)-Wert setzen
	$wert=count($params_on);
	switch ($wert)
		{
	   case "1":
			if ($status==true)
			   {
		     	if ($simulate==false)
		     	   {
				   $lightManager->SetRGB($switchOID, $moduleParams2[1]);
				   }
			   }
			break;
		case "2":
		   if (strtoupper($params_on[0])=="ON")
		      {
			   if ($status==true)
			      {
			     	if ($simulate==false)
			     	   {
					   $lightManager->SetRGB($switchOID, $params_on[1]);
					   }
					}
		     	}
		  	if (strtoupper($params_on[0])=="OFF")
			   {
			 	if ($status==false)
				   {
			     	if ($simulate==false)
			     	   {
					   $lightManager->SetRGB($switchOID, $params_on[1]);
						}
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
			     	if ($simulate==false)
			     	   {
					  	$lightManager->SetRGB($switchOID, $new);
						}
					}
				}
			if (strtoupper($params_on[0])=="OFF")
			   {
			  	if ($status==false)
				   {
				   $new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_on[1] & $mask);
			     	if ($simulate==false)
			     	   {
					  	$lightManager->SetRGB($switchOID, $new);
						}
					}
				}
			break;
		}
	switch (count($params_off))
	   {
	   case "1":
		   if ($status==false)
		      {
		     	if ($simulate==false)
		     	   {
				   $lightManager->SetRGB($switchOID, $moduleParams2[2]);
					}
			   }
			break;
		case "2":
		   if (strtoupper($params_off[0])=="ON")
		      {
			   if ($status==true)
			      {
			     	if ($simulate==false)
			     	   {
					   $lightManager->SetRGB($switchOID, $params_off[1]);
						}
				   }
		     	}
		   if (strtoupper($params_off[0])=="OFF")
		      {
			   if ($status==false)
			      {
			     	if ($simulate==false)
			     	   {
					   $lightManager->SetRGB($switchOID, $params_off[1]);
						}
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
			     	if ($simulate==false)
			     	   {
						$lightManager->SetRGB($switchOID, $new);
						}
					}
				}
			if (strtoupper($params_on[0])=="OFF")
			   {
			  	if ($status==false)
					{
					$new=((int)$lightManager->GetValue($switchOID) & $notmask) | ($params_off[1] & $mask);
			     	if ($simulate==false)
			     	   {
						$lightManager->SetRGB($switchOID, $new);
						}
					}
				}
			break;
		}
		
		}
*/
		
	return $result;
	}

/*********************************************************************************************/

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
	if ($speak_config["Parameter"][1]=="Debug")
		{
		tts_play(1,"Schalter ".$params[2]." manuell auf ".$switchStatus.".",'',2);
		}
	}

/*********************************************************************************************/

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
   	if ($speak_config["Parameter"][1]=="Debug")
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
		     	if ($speak_config["Parameter"][1]=="Debug")
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
	   	  	if ($speak_config["Parameter"][1]=="Debug")
	  	   		{
	  				tts_play(1,"Ventilator aus.",'',2);
	  				}
	  			}
	    	}
		} /* ende if Auto */
	}

/*********************************************************************************************/

/*********************************************************************************************/

function Parameter()
	{
	global $speak_config,$params;
	
	/* wenn Parameter ueberschritten etwas tun */
	$temperatur=GetValue($_IPS['VARIABLE']);
	if ($speak_config["Parameter"][1]=="Debug")
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
	     	if ($speak_config["Parameter"][1]=="Debug")
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
	     	if ($speak_config["Parameter"][1]=="Debug")
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


/*********************************************************************************************/

function switchNameGroup($SwitchName,$status,$simulate=false)
	{
	$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
	$switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
	$groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
	
	$command="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");";

	$resultID=@IPS_GetVariableIDByName($SwitchName,$switchCategoryId);
	if ($resultID==false)
   	{
		$result=@IPS_GetVariableIDByName($SwitchName,$groupCategoryId);
		if ($resultID==false)
   		{
			/* Name nicht bekannt */
	   	}
	   else   /* Wert ist eine Gruppe */
   	   {
   		$command.="IPSLight_SetGroupByName(\"".$SwitchName."\", false);";
	   	if ($simulate==false)
	   	   {
	   	   IPSLight_SetGroupByName($SwitchName,$status);
   	  		}
   	  	}
		}
	else     /* Wert ist ein Schalter */
	   {
  		$command.="IPSLight_SetSwitchByName(\"".$SwitchName."\", false);";
  		if ($simulate==false)
 		   {
	  	   IPSLight_SetSwitchByName($SwitchName,$status);
			}
		}
	return $command;
	}

/*********************************************************************************************/

?>