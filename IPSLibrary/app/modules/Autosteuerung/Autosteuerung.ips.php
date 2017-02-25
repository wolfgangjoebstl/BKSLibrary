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
		      Ventilator($params,$value);
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
	$i=0;	// testwert um zu sehen wir die Programm reagieren
	foreach ($configuration as $key=>$entry)
		{
		echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
		echo "Eintrag fuer : ".$key." (".IPS_GetName(IPS_GetParent($key)).".".IPS_GetName($key).") ".$entry[0].",".$entry[1]."\n\n";
		//print_r($entry);
		//print_r($auto->ParseCommand($entry));
		switch ($entry[1])
			{
			case "Anwesenheit":
				$status=Anwesenheit($entry,$i++,true);  // Simulation aktiv, Testwert ist +1
				echo "Resultat von Evaluierung Anwesenheit Funktion ausgeben.\n"; 
				break;
			case "Status":
				$status=Status($entry,$i++,true);  // Simulation aktiv, Testwert ist +1
				break;
			case "Ventilator":
				print_r($entry);
				$status=Ventilator($entry,$i++,true);  // Simulation aktiv, Testwert ist +1
				break;				
			}
		echo "Zusammengefasst :".json_encode($status)." \n";
			
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
						$command[$entry]["ON"]="true";
						}
					if ( (strtoupper($befehl[1]) == "OFF") || (strtoupper($befehl[1]) == "FALSE") )
						{
						$command[$entry]["OFF"]="false";
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
						$command[$entry]["ON"]="true";
						}
					if ( (strtoupper($befehl[1]) == "OFF") || (strtoupper($befehl[1]) == "FALSE") )
						{
						$command[$entry]["OFF"]="false";
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
	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;	
		
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */

	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	

		foreach ($Kommando as $befehl)
			{
			//echo "Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$command[$entry]=$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
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
	return($command);
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
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on:true,off:false,timer:dawn-23:45',),       			*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on:true,off:false,if:xxxxxx',),       				*/

	IPSLogger_Dbg(__file__, 'Aufruf Routine StatusRGB mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	$parges=$auto->ParseCommand($params);
	$command=array(); $entry=1;	

	if ($simulate==true) 
		{
		echo "***Simulationsergebnisse (parges):";
		print_r($parges);
		}

	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=true;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */

		foreach ($Kommando as $befehl)
			{
			//echo "Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
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
		
	return $command;
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

function Ventilator($params,$status,$simulate=false)
	{
	global $categoryId_Autosteuerung,$speak_config;

	IPSLogger_Dbg(__file__, 'Aufruf Routine Ventilator mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	echo 'Aufruf Routine Ventilator mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status."\n";
	
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