<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");

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
if ( isset($installedModules["Sprachsteuerung"]) === true )
	{
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Sprachsteuerung\Sprachsteuerung_Library.class.php");
	}

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));

$object_data= new ipsobject($CategoryIdData);		/* IPSComponentLogger class zum Suchen und Ausgeben von Objekten, hier parent object ID in der Klasse speichern */
$object_app= new ipsobject($CategoryIdApp);

$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");	/* Beim ersten Auftreten des Textes im Variablennamen in der Children Liste, diese OID zurückgeben */
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	/* logging in einem File und in einem String am Webfront */
	$log_Autosteuerung=new Logging("C:\Scripts\Log_Autosteuerung.csv",$NachrichtenInputID);
	}
else break;

$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	
/* Dummy Objekte für typische Anwendungsbeispiele erstellen, geht nicht automatisch */
/* könnte in Zukunft automatisch beim ersten Aufruf geschehen */

$name="Ansteuerung";
$categoryId_Autosteuerung  = CreateCategory($name, $CategoryIdData, 10);
$AnwesenheitssimulationID = @IPS_GetObjectIDByName("Anwesenheitssimulation",$categoryId_Autosteuerung);
if ($AnwesenheitssimulationID === false)
	{
	$AnwesenheitssimulationID = CreateVariable("Anwesenheitssimulation", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );
	}
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

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
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
	$log_Autosteuerung->LogNachrichten("Wert :".$value." von ".$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
	if (array_key_exists($variableID, $configuration)) 
		{
		/* es gibt einen Eintrag fuer das Event */

		$params=$configuration[$variableID];
		$log_Autosteuerung->LogMessage('  erkannter Befehl dafür'.json_encode($params));

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
				//Ventilator($params,$value);				
		      break;
			/*********************************************************************************************/
		   case "Parameter":
		      Parameter();
		      break;
			/*********************************************************************************************/
			case "Ventilator":
			case "HeatControl":
			case "Heizung":
				Ventilator2($params,$value,$variableID);
				break;
			/*********************************************************************************************/
		   case "Status":
			   /* bei einer Statusaenderung oder Aktualisierung einer Variable 														*/
			   /* array($params[0], $params[1], $params[2],),                     													*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate',	'Status',	'ArbeitszimmerLampe,	true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,cond#xxxxxx',),       					*/
				$status=Status($params,$value);
				$log_Autosteuerung->LogMessage('Befehl Status wurde ausgeführt : '.json_encode($status));
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
	/********************************
	 * Wird alle 5 Minuten aufgerufen, da kann man die zeitgesteuerten Dinge hineintun
	 *
	 * lassen sich aber nicht in der event gesteuerten Parametrierung einstellen
	 *
	 * Anwesenheitssimulation (TYPE=AWS):
	 * abhängig vom Schalter in Autosteuerung, entweder immer (1) oder nur wenn niemand anwesend ist (2)
	 *
	 *
	 * Szenensteuerung: 
	 *
	 ****************************************************************/
	
	$StatusAnwesend=$operate->Anwesend();		
	SetValue($StatusAnwesendID,$StatusAnwesend );
	$Anwesenheitssimulation=GetValue($AnwesenheitssimulationID);
	
	if ( $Anwesenheitssimulation>0 )
		{
		$simulation=new AutosteuerungAnwesenheitssimulation("Anwesenheitssimulation.csv");
		}
	
	if ( ($Anwesenheitssimulation==1) || ( ($Anwesenheitssimulation==2) && ($StatusAnwesend==false) )) 
		{
		//Anwesenheitssimulation aktiv, bedeutet ein (1) oder auto (2), bei auto wird bei Anwesenheit nicht simuliert
		//echo "\nAnwesenheitssimulation eingeschaltet. \n";
		IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
		$log_Autosteuerung->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
		$simulation->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
		//print_r($scenes);					
		}
	else
		{
		IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion nicht aktiv.');
		$log_Autosteuerung->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');
		$simulation->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');
		}

	foreach($scenes as $scene)
		{
		if (isset($scene["TYPE"]))
			{
			$statusID  = CreateVariable($scene["NAME"]."_Status",  1, $AnwesenheitssimulationID, 0, "AusEin",null,null,""  );
			AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			$counterID = CreateVariable($scene["NAME"]."_Counter", 1, $AnwesenheitssimulationID, 0, "",null,null,""  );
			if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
				{
				/*****************************************************
				 *
				 * Typ Anwesenheitssimulation
				 *
				 * wird alle 5 Minuten aufgerufen. Egal ob Register bereits vorher eingeschaltet wurde.
				 *
				 */
				if ( ($Anwesenheitssimulation==1) || ( ($Anwesenheitssimulation==2) && ($operate->Anwesend()==false) ) ) 
 					{
					SetValue($StatusAnwesendZuletztID,true);
					$switch = $auto->timeright($scene);
					$now = time();
					if ($switch)
			 			{
						$counter=GetValue($counterID);
						if ($counter == 0)
							{
							SetValue($statusID,true);
							if (isset($scene["EVENT_IPSLIGHT"]))
								{
								$text='IPSLight Switch '.$scene["EVENT_IPSLIGHT"];
								echo "    ".$text."\n";
								$log_Autosteuerung->LogMessage('Befehl Timer AWS aktiv, '.$text.' einschalten. '.json_encode($scene));
								$simulation->LogMessage('Befehl Timer AWS aktiv, '.$text.' einschalten. '.json_encode($scene));
								$simulation->LogNachrichten('Befehl Timer AWS aktiv, '.$text.' einschalten. ');
								IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], true);
								$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetSwitchByName("'.$scene["EVENT_IPSLIGHT"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer AWS Script für IPSLight Schalter '.$scene["EVENT_IPSLIGHT"].' wurde abgeschlossen.");';
								}
							else
								{
								if (isset($scene["EVENT_IPSLIGHT_GRP"]))
									{
									$text='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"];
									echo "    ".$text."\n";
									$log_Autosteuerung->LogMessage('Befehl Timer AWS aktiv, '.$text.' einschalten.'.json_encode($scene));
									$simulation->LogMessage('Befehl Timer AWS aktiv, '.$text.' einschalten.'.json_encode($scene));
									$simulation->LogNachrichten('Befehl Timer AWS aktiv, '.$text.' einschalten.');
									IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
									$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetGroupByName("'.$scene["EVENT_IPSLIGHT_GRP"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer AWS Script für IPSLight Schalter '.$scene["EVENT_IPSLIGHT_GRP"].' wurde abgeschlossen.");';
									}
								}
							if ($scene["EVENT_CHANCE"]==100)
								{
								echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
								setEventTimer($scene["NAME"],$auto->timeStop-$auto->now,$command);
								$log_Autosteuerung->LogMessage('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->timeStop)));
								$simulation->LogMessage('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->timeStop)));								
								$simulation->LogNachrichten('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->timeStop)));	
								}
							else
								{
								SetValue($counterID,$scene["EVENT_DURATION"]);
								setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60,$command);
								$log_Autosteuerung->LogMessage('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->now+$scene["EVENT_DURATION"]*60)));
								$simulation->LogMessage('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->now+$scene["EVENT_DURATION"]*60)));
								$simulation->LogNachrichten('Befehl Timer AWS aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->now+$scene["EVENT_DURATION"]*60)));
								}
							} /* Ende Counter abgelaufen */	
						else
							{
							/* counter um 5 Minuten reduzieren */
							if ($counter>4)	{ $counter-=5; }
							if ($counter<5) {$counter=0;}
							SetValue($counterID,$counter);
							}			
						}  /* ende switch */
					}	/*ende AWS eingeschaltet */
				else
					{
					/* wenn die Anwesenheitssimulation ausgeschaltet wird, etwas unternehmen */
					
					/* nur bei Änderung des Status etwas unternehmen */
					if (GetValue($StatusAnwesendZuletztID)==true)
						{	
						SetValue($statusID,false);
						$EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
						if ($EreignisID != false)
							{
							IPS_SetEventActive($EreignisID,false);
							}
						/* aber auch die Lampen ausschalten, sonst bleiben sie eingeschaltet */
						if (isset($scene["EVENT_IPSLIGHT"]))
							{
							$text='IPSLight Switch '.$scene["EVENT_IPSLIGHT"];
							echo "    ".$text." ausschalten, es ist Ende AWS\n";
							$log_Autosteuerung->LogMessage('Befehl Timer AWS wurde ausgeschaltet für '.$text.' .'.json_encode($scene));
							$simulation->LogMessage('Befehl Timer AWS wurde ausgeschaltet für '.$text.' .'.json_encode($scene));
							IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], false);
							}
						else
							{
							if (isset($scene["EVENT_IPSLIGHT_GRP"]))
								{
								$text='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"];								
								echo "    ".$text." ausschalten, es ist Ende AWS\n";
								$log_Autosteuerung->LogMessage('Befehl Timer AWS wurde ausgeschaltet für '.$text.' .'.json_encode($scene));								
								$simulation->LogMessage('Befehl Timer AWS wurde ausgeschaltet für '.$text.' .'.json_encode($scene));								
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
				 * Auch wenn das Event eigentlich nur am Anfang und am Ende durchlaufen werden muesste, wird alle 5 Minuten geprüft und gesetzt !
				 *
				 */
				$switch = $auto->timeright($scene);
				$now = time();
				if ($switch)
		 			{
					SetValue($statusID,true);
					if (isset($scene["EVENT_IPSLIGHT"]))
						{
						$text='IPSLight Switch '.$scene["EVENT_IPSLIGHT"];							
						echo "    ".$text."\n";
						$log_Autosteuerung->LogMessage('Befehl Timer für '.$text.' wurde ausgeführt.'.json_encode($scene));
						IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], true);
						$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetSwitchByName("'.$scene["EVENT_IPSLIGHT"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSLight Schalter '.$scene["EVENT_IPSLIGHT"].' wurde abgeschlossen.");';
						}
					else
						{
						if (isset($scene["EVENT_IPSLIGHT_GRP"]))
							{
							$text='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"];
							echo "    ".$text."\n";
							$log_Autosteuerung->LogMessage('Befehl Timer für  '.$text.' wurde ausgeführt.'.json_encode($scene));
							IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
							$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetGroupByName("'.$scene["EVENT_IPSLIGHT_GRP"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer AWS Script für IPSLight Schalter '.$scene["EVENT_IPSLIGHT_GRP"].' wurde abgeschlossen.");';
							}
						}
					if ($scene["EVENT_CHANCE"]==100)
						{
						echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
						setEventTimer($scene["NAME"],$auto->timeStop-$auto->now,$command);
						$log_Autosteuerung->LogMessage('Befehl Timer aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->timeStop)));							
						}
					else
						{
						setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60,$command);
						$log_Autosteuerung->LogMessage('Befehl Timer aktiv, '.$text.' Timer gesetzt auf '.date("D d.m.Y H:i",($auto->now+$scene["EVENT_DURATION"]*60)));
						}	
					}  /* ende switch */
				}	/* ende ifelse AWS */		
			}   /* ende isset Type */		
		} /* end of foreach */
		
	} /* Endif Timer */

/********************************************************************************************************************************
 *
 * Execute aufgerufen, simuliert die Parametereingaben
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
			   /* bei einer Statusaenderung oder Aktualisierung einer Variable 														*/
			   /* array($params[0], $params[1], $params[2],),                     													*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate',	'Status',	'ArbeitszimmerLampe,	true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,cond#xxxxxx',),       					*/
				$status=Status($entry,$i++,true);  // Simulation aktiv, Testwert ist +1
				break;
			case "Ventilator":
			case "HeatControl":
			case "Heizung":
				//print_r($entry);
				$status=Ventilator2($entry,18,26409,true);  // Simulation aktiv, Testwert ist 32
				break;	
			case "iTunes":
				$status=iTunesSteuerung($entry,$i++,true);
				break;
			/*********************************************************************************************/
			case "GutenMorgenWecker":
				$status=GutenMorgenWecker($entry,$i++,true);
		      break;
			/*********************************************************************************************/
		   case "Ventilator1":
				$status=Ventilator1($entry,$i++,true);
				//$status=Ventilator($entry,$i++,true);				
		      break;
			/*********************************************************************************************/
		   case "Parameter":
				$status=Parameter($entry,$i++,true);
		      break;
			/*********************************************************************************************/
		   case "StatusRGB":
		      echo "Fehler, Funktion nicht mehr unterstützt.\n";
				$status=array();
				break;
			/*********************************************************************************************/
		   case "Switch":
				$status=SwitchFunction($entry,$i++,true);
		      break;
			/*********************************************************************************************/
		   case "Custom":
		      /* Aufrufen von kundenspezifischen Funktionen */
				$status=array();
		      break;
			/*********************************************************************************************/
		   case "par1":
		   case "dummy":
		   case "Dummy":
		   case "DUMMY":
				$status=array();			
		      break;
		   default:
				$status=array();			
				echo "Aufruf Funktion eval(".json_encode($entry[1]).")\n";
				break;
			}
		echo "Zusammengefasst :".json_encode($status)." \n";
			
		}
	echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n";		

	/*********************************************************************************************/
	
	$Anwesenheitssimulation=GetValue($AnwesenheitssimulationID);
	if ( $Anwesenheitssimulation>0 )
		{
		$simulation=new AutosteuerungAnwesenheitssimulation("Anwesenheitssimulation.csv");
		$simulation->LogMessage('Aufruf Autosteuerung Excute, Anwesenheitssimulation eingeschaltet.');		
		$simulation->LogNachrichten('Aufruf Autosteuerung Excute, Anwesenheitssimulation eingeschaltet.');		
		}
		
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
		$switch = $auto->timeright($scene);	
		echo "      Schaltet jetzt : ".($switch ? "Ja":"Nein")."\n";
		/* Kennt nur zwei Zeiten, sollte auch für mehrere Zeiten getrennt durch , funktionieren, gerade from, ungerader Index to */	
		$actualTimes = $auto->switchingTimes($scene);
		//echo "Evaluierte Schaltzeiten:\n";	
		//print_r($actualTimes);
		for ($sindex=0;($sindex <sizeof($actualTimes));$sindex++)
			{
			//echo "   Schaltzeit ".$sindex."\n";
			$actualTimeStart = explode(":",$actualTimes[$sindex][0]);
			$actualTimeStartHour = $actualTimeStart[0];
			$actualTimeStartMinute = $actualTimeStart[1];
			$actualTimeStop = explode(":",$actualTimes[$sindex][1]);
			$actualTimeStopHour = $actualTimeStop[0];
			$actualTimeStopMinute = $actualTimeStop[1];
			echo "      Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
			$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
			$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
			}
		$now = time();
		//include(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/IPSLight/IPSLight.inc.php");
		if (isset($scene["EVENT_IPSLIGHT"]))
			{
			echo "      Objekt : ".$scene["EVENT_IPSLIGHT"]."\n";
			//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
         	}
         else
            {
      		if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	   		{
	      		echo "      Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"]."\n";
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
	echo "Category App           ID:".$CategoryIdApp."\n";
	echo "Category Data          ID:".$CategoryIdData."\n";
	echo "Category Script        ID:".$scriptId."\n";
	
	} /* Ende Execute */


?>