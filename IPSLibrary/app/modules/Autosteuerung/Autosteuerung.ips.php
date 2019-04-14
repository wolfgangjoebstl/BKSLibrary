<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
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

    include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
	if ( isset($installedModules["Stromheizung"]) === true )
		{
		include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
		}

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

/********************************************************************************************
 *
 * es gibt 4 verschiedene Logging Registersets
 *
 * hier nur die Logs für die Autosteuerung generell fixieren 
 * denn alle Klassen aus der Klasse Autosteuerungsfunktionen haben ihre eigenen Loggingfunktionen
 * wird mit dem construct automatisch aufgebaut.
 *
 ********************************************************************************************/
 
    $setup = Autosteuerung_Setup();
    if ( isset($setup["LogDirectory"]) == false ) $setup["LogDirectory"]="C:/Scripts/Autosteuerung/";

	$NachrichtenIDAuto=IPS_GetCategoryIDByName("Nachrichtenverlauf-Autosteuerung",$CategoryIdData);
    $NachrichtenInputID=IPS_GetVariableIDByName("Nachricht_Input",$NachrichtenIDAuto);
    $log_Autosteuerung=new Logging($setup["LogDirectory"]."Autosteuerung.csv",$NachrichtenInputID,IPS_GetName(0).";Autosteuerung;");

    $NachrichtenIDAnwe=IPS_GetCategoryIDByName("Nachrichtenverlauf-AnwesenheitErkennung",$CategoryIdData);
    $NachrichtenInputID=IPS_GetVariableIDByName("Nachricht_Input",$NachrichtenIDAnwe);
    $log_Anwesenheitserkennung=new Logging($setup["LogDirectory"]."Anwesenheitserkennung.csv",$NachrichtenInputID,IPS_GetName(0).";Anwesenheitserkennung;");


/* wird jetzt in der jeweiligen Klasse gemacht: 
$NachrichtenID = $object_data->osearch("Schaltbefehle");	// Beim ersten Auftreten des Textes im Variablennamen in der Children Liste, diese OID zurückgeben 
$object4= new ipsobject($NachrichtenID);
$NachrichtenInputID=$object4->osearch("Input");
$log_Anwesenheit=new Logging($setup["LogDirectory"]."Anwesenheit.csv",$NachrichtenInputID,IPS_GetName(0).";Anwesenheitssimulation;");
*/

$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	
$timerAufrufID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdAutosteuerung);
$tim2ID = @IPS_GetEventIDByName("KalenderTimer", $scriptIdHeatControl);

if ($timerAufrufID==false) $fatalerror=true;
if ($tim2ID==false) $fatalerror=true;

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

$StatusTableMapHtml   = CreateVariable("StatusTableView",   3 /*String*/,  $AnwesenheitserkennungID, 1010, '~HTMLBox');

/* wichtigste Parameter vorbereiten */

$configuration = Autosteuerung_GetEventConfiguration();
$scenes=Autosteuerung_GetScenes();
//print_r($configuration);

$speak_config=Autosteuerung_Speak();

$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
$register=new AutosteuerungHandler($scriptIdAutosteuerung);
$operate=new AutosteuerungOperator();
$auto=new Autosteuerung();

$simulation=new AutosteuerungAnwesenheitssimulation("Anwesenheitssimulation.csv");  // automatisch eigenen File und Nachrichtenspeicher anlegen
// Regler ist auch eine Autosteuerungsfunktion
// Alexa ist auch eine Autosteuerungsfunktion
// Stromheizung ist auch eine Autosteuerungsfunktion

if ( isset($installedModules["OperationCenter"]) === true )
	{
    IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
    $statusAWS=new statusDisplay();        // eine OperationCenter Library für die Anwesenheitssimulation
    }
else
    {   /* zweimal tts_play deklariert */
	if ( isset($installedModules["Sprachsteuerung"]) === true )
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Sprachsteuerung\Sprachsteuerung_Library.class.php");
		}            
    }        

/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	}

/*********************************************************************************************/

if ($_IPS['SENDER']=="RunScript")
	{
	/* vom RunScript aus gestartet, parallele Abarbeitung von Autosteuerungs Befehlen */
	if ( (isset($_IPS['MODULE'])) && ($_IPS['MODULE']=="Autosteuerung") )
		{
        if (isset($_IPS['REQUEST']))
            {    
            IPSLogger_Inf(__file__, 'Autosteuerung RunScript, Befehl '.$_IPS['REQUEST']);
            $command=json_decode($_IPS['REQUEST'],true);        // dekodieren als array nicht stdclass
	        $result=$auto->ExecuteCommand($command);
            $ergebnis=$auto->timerCommand($result);
            }
        }
	}



/********************************************************************************************
 *                                                                                           
 * Programmfunktionen             																				
 *                                                                                           
 * alles aus Variablenaenderungen gesteuert. GutenMorgen Wecker verändert die Variable Wecker in Data auf 0,1,2. Daraus werden hier die Handlungen abgeleitet.
 * in Autosteurung Configuration stehen die entsprechenden Befehle
 *
 *
 *
 *********************************************************************************************/

if ($_IPS['SENDER']=="Variable")
	{
	$variableID=$_IPS['VARIABLE'];
	$value=GetValue($variableID);
	$configuration = Autosteuerung_GetEventConfiguration();
	/* eine Variablenaenderung ist aufgetreten */
	IPSLogger_Dbg(__file__, 'Autosteuerung, Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).') auf '.GetValueIfFormatted($variableID).'.');
	$log_Autosteuerung->LogMessage('Variablenaenderung;'.$variableID.';'.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).'.');
	//$log_Autosteuerung->LogNachrichten("Wert :".$value." von ".$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
	if (array_key_exists($variableID, $configuration)) 
		{
		/* es gibt einen Eintrag fuer das Event */

		$params=$configuration[$variableID];
		$log_Autosteuerung->LogMessage('  erkannter Befehl dafür'.json_encode($params));

		$wert=$params[1];
        if (strpos($wert,"+"))
            {   // es gibt einen Zusatzparameter beim Modul
            $wertparam=explode("+",$wert);
            $wert = $wertparam[0];
            $wertOpt=$wertparam[1];
            }
        else $wertOpt="";
		/* 0: OnChange or OnUpdate, 1 ist die Klassifizierung, Befehl 2 sind Parameter */
		//tts_play(1,$_IPS['VARIABLE'].' and '.$wert,'',2);
		switch ($wert)    {
			/*********************************************************************************************/
			case "iTunes":
			case "Media":
				$status=iTunesSteuerung($params,$value,$variableID,false,$wertOpt);
				$log_Autosteuerung->LogMessage('Befehl der App Media wurde ausgeführt : '.json_encode($status));
				break;
			/*********************************************************************************************/
			case "GutenMorgenWecker":
				$functions=$auto->getFunctions();
				if ( (isset($functions["GutenMorgenWecker"]["VALUE"])) && ($functions["GutenMorgenWecker"]["VALUE"] > 0) )
					{
					$status=GutenMorgenWecker($params,$value,$variableID,false,$wertOpt);
					$log_Autosteuerung->LogMessage('Befehl der App GutenMorgenWecker wurde ausgeführt : '.json_encode($status));
					}
				else $log_Autosteuerung->LogMessage('Befehl der App GutenMorgenWecker wurde nicht ausgeführt, Wecker steht auf Aus.'); 	
				break;
			/*********************************************************************************************/
			case "Anwesenheit":
				$log_Autosteuerung->LogNachrichten("Wert :".$value." von ".$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').');
				$status=Anwesenheit($params,$value,$variableID,false,$wertOpt);
				$log_Autosteuerung->LogMessage('Befehl der App Anwesenheit wurde ausgeführt : '.json_encode($status));
				break;
			/*********************************************************************************************/
		   case "Ventilator1":
		      Ventilator1($params,$value,$variableID,false,$wertOpt);
				//Ventilator($params,$value);				
		      break;
			/*********************************************************************************************/
		   case "Parameter":
		      Parameter($params,$value,$variableID,false,$wertOpt);
		      break;
			/*********************************************************************************************/
			case "Ventilator":
			case "HeatControl":
			case "Heizung":
				Ventilator2($params,$value,$variableID,false,$wertOpt);
				break;
			/*********************************************************************************************/
		   case "Status":
				$status=Status($params,$value,$variableID,false,$wertOpt);
				$log_Autosteuerung->LogMessage('Befehl Status wurde ausgeführt : '.json_encode($status));
				break;		   
		   case "StatusParallel":           
			   /* bei einer Statusaenderung oder Aktualisierung einer Variable 														*/
			   /* array($params[0], $params[1], $params[2],),                     													*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate',	'Status',	'ArbeitszimmerLampe,	true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,cond#xxxxxx',),       					*/
				$status=StatusParallel($params,$value,$variableID,false,$wertOpt);
				$log_Autosteuerung->LogMessage('Befehl StatusParallel wurde ausgeführt : '.json_encode($status));
				break;
			/*********************************************************************************************/
		   case "StatusRGB":
		      statusRGB($params,$value,$variableID,false,$wertOpt);
				break;
			/*********************************************************************************************/
		   case "Switch":
				SwitchFunction($params,$value,$variableID,false,$wertOpt);
		      break;
			/*********************************************************************************************/
		   case "Custom":
		      /* Aufrufen von kundenspezifischen Funktionen */
				eval($params[1]);
		      break;
			/*********************************************************************************************/
		   case "par1":
		   case "dummy":
		   case "Dummy":
		   case "DUMMY":
		      break;
		   default:

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
	switch ($_IPS['EVENT'])
		{
		case $timerAufrufID:
			/* alle 5 Minuten aufrufen */
			$StatusAnwesend=$operate->Anwesend();
            if ($StatusAnwesend<>GetValue($StatusAnwesendID))
                {
                $log_Anwesenheitserkennung->LogMessage('Änderung Status Anwesenheit auf '.($StatusAnwesend?"Anwesend":"Abwesend"));
                $log_Anwesenheitserkennung->LogNachrichten('Änderung Status Anwesenheit auf '.($StatusAnwesend?"Anwesend":"Abwesend"));                    
			    SetValue($StatusAnwesendID,$StatusAnwesend );
                }
			$Anwesenheitssimulation=GetValue($AnwesenheitssimulationID);
			
			/* Kurzüberblick als Tabelle machen über Bewegnung in den Räumen */
			$topology = $operate->getLogicAnwesend();
			$html=$operate->writeTopologyTable($topology);
			SetValue($StatusTableMapHtml,$html);
				
			$AWSFunktionStatus=( ($Anwesenheitssimulation==1) || ( ($Anwesenheitssimulation==2) && ($StatusAnwesend==false) ));
			if ( $AWSFunktionStatus ) 
				{
				//Anwesenheitssimulation aktiv, bedeutet ein (1) oder auto (2), bei auto wird bei Anwesenheit nicht simuliert
				//echo "\nAnwesenheitssimulation eingeschaltet. \n";
				IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
				//$log_Anwesenheit->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
				//$log_Anwesenheit->LogNachrichten('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');		
				//$simulation->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
				//$simulation->LogNachrichten('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion aktiviert.');
				//print_r($scenes);					
				}
			else
				{
				IPSLogger_Dbg(__file__, 'Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion nicht aktiv.');
				//$log_Anwesenheit->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');
				//$log_Anwesenheit->LogNachrichten('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');		
				//$simulation->LogMessage('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');
				//$simulation->LogNachrichten('Aufruf Autosteuerung Timer von '.$_IPS['EVENT']."(".IPS_GetName($_IPS['EVENT']).') , AWS Funktion NICHT aktiviert.');
				}

			foreach($scenes as $scene)
				{
				if (isset($scene["TYPE"]))
					{
                    if ( (isset($scene["STATUS"])) && (strtoupper($scene["STATUS"])=="DISABLED") )
                        {
                        /* Schalter ist deaktiviert, nichts tun */    
                        }
                    else
                        {                        
					$statusID  = CreateVariable($scene["NAME"]."_Status",  1, $AnwesenheitssimulationID, 0, "AusEin",null,null,""  );
					$counterID = CreateVariable($scene["NAME"]."_Counter", 1, $AnwesenheitssimulationID, 0, "",null,null,""  );
                    AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
					AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
					IPS_ApplyChanges($archiveHandlerID);
					if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
						{
						/*****************************************************
						 *
						 * Typ Anwesenheitssimulation
						 *
						 * wird alle 5 Minuten aufgerufen. Egal ob Register bereits vorher eingeschaltet wurde.
						 *
						 */
						if ( $AWSFunktionStatus ) 
 							{
							SetValue($StatusAnwesendZuletztID,true);
							$switch = $auto->timeright($scene);
							$now = time();
							if ($switch)
					 			{
								$counter=GetValue($counterID);
								if ($counter == 0)
									{
                                    $text=$auto->switchAWS(true,$scene);
                                    if ($text != "")
                                        {           /* intensives Logging nur wenn Timer nicht schon aktiv ist */                        
    									echo "    ".$text."\n";
	    								$simulation->LogMessage($text.'. '.json_encode($scene));
		    							$simulation->LogNachrichten($text);
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
								$EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
								if ($EreignisID != false)
									{
									IPS_SetEventActive($EreignisID,false);
									}
								/* aber auch die Lampen ausschalten, sonst bleiben sie eingeschaltet */
                                $text=$auto->switchAWS(false,$scene);       /* Status und aktuelle Szene übergeben */
                                if ($text != "")
                                    {           /* intensives Logging nur wenn Timer nicht schon aktiv ist */                        
    								echo "    ".$text." ausschalten, es ist Ende AWS\n";
	    							$simulation->LogMessage($text.' .'.json_encode($scene));
		    						//$simulation->LogNachrichten($text.' .'.json_encode($scene));
                                    $simulation->LogNachrichten($text);
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
                         * Die Variable wird nur gesetzt, das ausschalten erfolgt mit einem eigenen Timer
						 *
						 */
						$switch = $auto->timeright($scene);
						if ($switch)
				 			{
                            $text=$auto->switchAWS(true,$scene); 
                            if ($text != "")
                                {           /* intensives Logging nur wenn Timer nicht schon aktiv ist */                        
    							echo "    ".$text."\n";
	    						//$simulation->LogMessage($text.json_encode($scene));
					    		$simulation->LogMessage($text);								
		    					$simulation->LogNachrichten($text);
                                }    
							}  /* ende switch */
						}	/* ende ifelse AWS */	
                        }   /* ende Status aktiv */	
					}   /* ende isset Type */		
				} /* end of foreach */
            if ( isset($installedModules["OperationCenter"]) === true ) $statusAWS->setStatus();                
			break;
		default:
			$simulation->LogMessage('Timer nicht bekannt.');
			$simulation->LogNachrichten('Timer nicht bekannt.');
			break;
		}	/* ende switch/case */	
	} /* Endif Timer */

/********************************************************************************************************************************
 *
 * Execute aufgerufen, simuliert die Parametereingaben
 *
 +
 *************************************************************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{	/* von der Konsole aus gestartet */
	echo "--------------------------------------------------------------\n";
	echo "        EXECUTE (Überprüfung mit Testwerten)\n";
	echo "--------------------------------------------------------------\n\n";
	//IPSLogger_Dbg(__file__, 'Exec aufgerufen ...');
	
	test();		/* gibt die IDs von Anwesenheitsimulation, Nachrichten Script und Nachrichten Input aus.\n";
	
	// testweise Sprache ausgeben */
	//tts_play(1,"Claudia, ich hab dich so lieb.",'',2);
	
	echo "\nEingestellte Programme:\n\n";
	$i=0;	// testwert um zu sehen wir die Programm reagieren
	foreach ($configuration as $key=>$entry)
		{
		echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
		echo "Eintrag fuer : ".$key." (".IPS_GetName(IPS_GetParent($key)).".".IPS_GetName($key).") ".$entry[0].",".$entry[1]."       ";
		echo "(".memory_get_usage()." Byte).";
		echo "\n";
		//print_r($entry);
		//print_r($auto->ParseCommand($entry));
		switch ($entry[1])
			{
			case "Anwesenheit":
				$status=Anwesenheit($entry,GetValue($key),$key,true);  // Simulation aktiv, Testwert ist +1
				echo "Resultat von Evaluierung Anwesenheit Funktion ausgeben.\n"; 
				break;
			case "iTunes":
			case "Media":
				$status=iTunesSteuerung($entry,$i++,12345,true);
				break;				
			case "Status":
				$status=Status($entry,GetValue($key),$key,true);
				break;
		   case "StatusParallel":                       
			   /* bei einer Statusaenderung oder Aktualisierung einer Variable 														*/
			   /* array($params[0], $params[1], $params[2],),                     													*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe',),      bei Change Lightswitch mit Wert schreiben   */
				/* array('OnUpdate',	'Status',	'ArbeitszimmerLampe,	true',),    bei Update Taster LightSwitch einschalten   */
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,timer#dawn-23:45',),       			*/
			   /* array('OnChange',	'Status',	'ArbeitszimmerLampe,	on#true,	off#false,cond#xxxxxx',),       					*/
				//$status=Status($entry,$i++,12345,true);  // Simulation aktiv, Testwert ist +1
				$status=StatusParallel($entry,GetValue($key),$key,true);
				break;
			case "Ventilator":
			case "HeatControl":
			case "Heizung":
				//print_r($entry);
				$status=Ventilator2($entry,GetValue($key),$key,true);  // Simulation aktiv, Testwert ist 32
				break;	
			case "iTunes":
				$status=iTunesSteuerung($entry,$i++,12345,true);
				break;
			/*********************************************************************************************/
			case "GutenMorgenWecker":
				$status=GutenMorgenWecker($entry,$i++,12345,true);
		      break;
			/*********************************************************************************************/
		   case "Ventilator1":
				$status=Ventilator1($entry,$i++,12345,true);
				//$status=Ventilator($entry,$i++,true);				
		      break;
			/*********************************************************************************************/
		   case "Parameter":
				$status=Parameter($entry,$i++,12345,true);
		      break;
			/*********************************************************************************************/
		   case "StatusRGB":
		      echo "Fehler, Funktion nicht mehr unterstützt.\n";
				$status=array();
				break;
			/*********************************************************************************************/
		   case "Switch":
				$status=SwitchFunction($entry,$i++,12345,true);
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