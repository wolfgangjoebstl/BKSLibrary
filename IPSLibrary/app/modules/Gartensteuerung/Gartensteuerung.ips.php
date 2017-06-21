<?

/*
	 * @defgroup Gartensteuerung
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


/************************************************************
 *
 * Gartensteuerung
 *
 * wird nur mit den Timern aufgerufen und steuert die Giessanlage
 *
 * 
 *
 *
 ****************************************************************/
 
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
include_once(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');


/******************************************************

				INIT
				
*************************************************************/

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
		 
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
		}
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

/******************************************************

				Nachrichtenspeicher initialisieren
				
*************************************************************/

$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);
$NachrichtenScriptID   	= IPS_GetScriptIDByName('Nachrichtenverlauf-Garten', $CategoryIdApp);

if (isset($NachrichtenScriptID))
	{
	/* alternatives Logging mit Objektorientierung */
	$object2= new ipsobject($CategoryIdData);
	$object2->oprint("Nachricht"); echo "\n";
	$object3= new ipsobject($object2->osearch("Nachricht"));
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	//echo "Nachrichten ScriptID : ".$NachrichtenScriptID." InputID :  ".$NachrichtenInputID." (".IPS_GetName(IPS_GetParent($NachrichtenInputID))."/".IPS_GetName($NachrichtenInputID).")\n";"\n";
	/* logging in einem File und in einem String am Webfront */
	$log_Giessanlage=new logging("C:\Scripts\Log_Giessanlage2.csv",$NachrichtenScriptID,$NachrichtenInputID);
	}
else break;

/******************************************************

				Timer initialisieren
				
*************************************************************/


$giesstimerID = @IPS_GetEventIDByName("Timer1", $GartensteuerungScriptID);
$allofftimerID = @IPS_GetEventIDByName("Timer2", $GartensteuerungScriptID);
$timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
$calcgiesstimeID = @IPS_GetEventIDByName("Timer4", $GartensteuerungScriptID);
$UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);

//$alleEreignisse = IPS_GetEventListByType(1);
//print_r($alleEreignisse);

IPS_SetEventActive($calcgiesstimeID,true);
IPS_SetEventActive($timerDawnID,true);
IPS_SetEventActive($allofftimerID,true);

echo "Timerprogrammierung: \n";
echo "  Giess Timer ID  : ".$giesstimerID."\n";
echo "  AllOff Timer ID : ".$allofftimerID."\n";
echo "  Dawn Timer ID   : ".$timerDawnID."\n";
echo "  Calc Timer ID   : ".$calcgiesstimeID."\n";
echo "  Update Timer ID : ".$UpdateTimerID."\n";

/******************************************************

				Variablen initialisieren
				
*************************************************************/

	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Nachrichten    		= CreateCategory('Gartensteuerung-Nachrichten',   $CategoryIdData, 20);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);

	$GiessAnlageID	= @IPS_GetVariableIDByName("GiessAnlage",$categoryId_Gartensteuerung);
	$GiessKreisID	= @IPS_GetVariableIDByName("GiessKreis",$categoryId_Gartensteuerung); 
	$GiessKreisInfoID	= @IPS_GetVariableIDByName("GiessKreisInfo",$categoryId_Gartensteuerung);
	$GiessDauerInfoID	= @IPS_GetVariableIDByName("GiessDauerInfo",$categoryId_Gartensteuerung);
	$GiessTimeID	= @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
	$GiessTimeRemainID	= @IPS_GetVariableIDByName("GiessTimeRemain", $categoryId_Gartensteuerung); 
	
	$GiessCountID	= @IPS_GetVariableIDByName("GiessCount", $categoryId_Register);
	$GiessCountOffsetID	= @IPS_GetVariableIDByName("GiessCountOffset",$categoryId_Register);
	$GiessAnlagePrevID = @IPS_GetVariableIDByName("GiessAnlagePrev", $categoryId_Register); 
	$GiessPauseID 	= @IPS_GetVariableIDByName("GiessPause",$categoryId_Register);

	
$giessTime=GetValue($GiessTimeID);

$GartensteuerungConfiguration=getGartensteuerungConfiguration();


	
/******************************************************

				EXECUTE

*************************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
	$variableTempID=get_aussentempID();
	$variableID=get_raincounterID();
	
	echo "\n";	
	echo "=======EXECUTE====================================================\n";
	echo "\n";
	echo "Nachrichten Script      ID : ".$NachrichtenScriptID."  (".IPS_GetName(IPS_GetParent($NachrichtenScriptID))."/".IPS_GetName($NachrichtenScriptID).")\n";
	echo "Nachrichten Log Input   ID : ".$NachrichtenInputID."  (".IPS_GetName(IPS_GetParent($NachrichtenInputID))."/".IPS_GetName($NachrichtenInputID).")\n";
	echo "Gartensteuerung Script  ID : ".$GartensteuerungScriptID."  (".IPS_GetName(IPS_GetParent($GartensteuerungScriptID))."/".IPS_GetName($GartensteuerungScriptID).")\n";
	echo "Giessanlage             ID : ".$GiessAnlageID."  (".IPS_GetName(IPS_GetParent($GiessAnlageID))."/".IPS_GetName($GiessAnlageID).")\n";
	echo "\nStatus Giessanlage         ".GetValue($GiessAnlageID)." (0-Aus,1-Einmalein,2-Auto) \n";
	echo "Status Giessanlage zuletzt ".GetValue($GiessAnlagePrevID)." (0-Aus,1-Einmalein,2-Auto) \n\n";
	echo "AussenTemperatur        ID : ".$variableTempID."  (".IPS_GetName(IPS_GetParent($variableTempID))."/".IPS_GetName($variableTempID).")    ".GetValue($variableTempID)."°C \n";
	echo "RainCounter             ID : ".$variableID."  (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")    ".GetValue($variableID)."mm \n";
	echo "Gartensteuerungs Konfiguration:\n";
	print_r($GartensteuerungConfiguration);
	if ($GartensteuerungConfiguration["DEBUG"]==true)
	   {
	   echo "  Debugmeldungen eingeschaltet.\n";
	   }
	$Count=floor(GetValue($GiessCountID)/2+GetValue($GiessCountOffsetID));
	if ( isset($GartensteuerungConfiguration["KREIS".(string)$Count]) )
		{	
		echo "  Giesskreis : ".$GartensteuerungConfiguration["KREIS".(string)$Count]."\n";
		}
	else
		{
		echo "  Giesskreis : ".$Count."\n";
		}	
	if (isset ($GartensteuerungConfiguration["PAUSE"])) 
		{ 
		$pauseTime=$GartensteuerungConfiguration["PAUSE"]; 
		} 
	else 
		{ 
		$pauseTime=1; 
		}
	SetValue($GiessPauseID,$pauseTime);
	echo "  Pause zwischen den Giesskriesen : ".$pauseTime." Minuten\n";
		
	echo "Jetzt umstellen auf berechnete Werte. Es reicht ein Regen und ein Aussentemperaturwert.\n";
	$endtime=time();
	$starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
	$starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage */

	$Server=RemoteAccess_Address();
	If ($Server=="")
	   {
		echo "Regen und Temperaturdaten : \n\n";		
		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
   		$tempwerte = AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
		$tempwerteLog = AC_GetLoggedValues($archiveHandlerID, $variableTempID, $starttime, $endtime,0);		
		$variableTempName = IPS_GetName($variableTempID);
		$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
	    $werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);
		$variableName = IPS_GetName($variableID);
		}
	else
		{
		echo "Regen und Temperaturdaten vom Server : ".$Server."\n\n";
		$rpc = new JSONRPC($Server);
		$archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
   		$tempwerte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
		$tempwerteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableTempID, $starttime, $endtime,0);			
		$variableTempName = $rpc->IPS_GetName($variableTempID);
		$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
	    $werte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);
		$variableName = $rpc->IPS_GetName($variableID);
		}

	/*
	echo "Regenwerte:\n";
	print_r($werte);
	echo "Aussentemperaturwerte:\n";	
	print_r($tempwerte);
	echo "Aussentemperaturwerte Log:\n";	
	foreach ($tempwerteLog as $wert) { echo date("d.m H:i",$wert["TimeStamp"])."  ".$wert["Value"]."\n"; }
	*/
	
  	$anzahl=count($tempwerteLog);
 	echo "Agg. Variable: ".$variableTempName." mit ".$anzahl." Werte \n";
 	echo "   Durchschnittstemp heute   : ".number_format($tempwerte[0]["Avg"], 1, ",", "")." Grad\n";
 	echo "   Durchschnittstemp gestern : ".number_format($tempwerte[1]["Avg"], 1, ",", "")." Grad\n";
 	echo "   Maxtemperatur heute       : ".number_format($tempwerte[0]["Max"], 1, ",", "")." Grad um ".date("H:i \a\m d.m",($tempwerte[0]["MaxTime"]))."\n";
 	echo "   Maxtemperatur gestern     : ".number_format($tempwerte[1]["Max"], 1, ",", "")." Grad um ".date("H:i \a\m d.m",($tempwerte[1]["MaxTime"]))."\n";
 	echo "   Mintemperatur heute       : ".number_format($tempwerte[0]["Min"], 1, ",", "")." Grad um ".date("H:i \a\m d.m",($tempwerte[0]["MinTime"]))."\n";
 	echo "   Mintemperatur gestern     : ".number_format($tempwerte[1]["Min"], 1, ",", "")." Grad um ".date("H:i \a\m d.m",($tempwerte[1]["MinTime"]))."\n";
 	//echo "Dauer heute : ".number_format(($tempwerte[0]["Duration"]/60/60), 1, ",", "")."Stunden \n";
 	//echo "LastTime    : ".date("d.m H:i",($tempwerte[0]["LastTime"]))." \n";
 	//echo "   TimeStamp   : ".date("d.m H:i",($tempwerte[1]["TimeStamp"]))." \n";
 	
	$anzahl=count($werteLog);
 	echo "Agg. Variable: ".$variableName." mit ".$anzahl." Werte\n";
	
	/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */
	$letzterRegen=0;
	$regenStand=0;
	$regenStand2h=0;
	$regenStand48h=0;
	$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
	$regenStandEnde=0;
	$vorwert=0;
	foreach ($werteLog as $wert)
	   {
	   if ($vorwert=0) { echo "Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." ".round($wert["Value"])."mm "; }
	   else { echo "Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." ".round($wert["Value"])."mm "; }
	   If (($letzterRegen==0) && (round($wert["Value"]) > 0))
			{
			/* Wenn nur ein Wert in der Datenbank ohne Veränderung dann Problem */
			$letzterRegen=$wert["TimeStamp"];
			$regenStandEnde=$wert["Value"];
			echo "Letzter Regen ! \n";
		  	}
		else
			{
			echo "\n";
			} 
		/* Regenstand innerhalb der letzten 2 Stunden ermitteln */
	   if (((time()-$wert["TimeStamp"])/60/60)<2)
	      {
	      //$regenStand2h=$regenStandEnde-$regenStandAnfang+0.3; /* Warum plus 0.3 ??? */
	      $regenStand2h=$regenStandEnde-$regenStandAnfang;
	      }
		/* Regenstand innerhalb der letzten 48 Stunden ermitteln */
	   if (((time()-$wert["TimeStamp"])/60/60)<48)
	      {
	      //$regenStand48h=$regenStandEnde-$regenStandAnfang+0.3; /* Warum plus 0.3 ??? */
	      $regenStand48h=$regenStandEnde-$regenStandAnfang;
	      }
	   }
	echo "Regenstandanfang : ".$regenStandAnfang." mm\n";
	echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h."\n";
	echo "Letzter Regen vor ".number_format((time()-$letzterRegen)/60/60, 1, ",", ""). "Stunden.\n";

	$letzterRegen=0;
	$RefWert=0;
	foreach ($werte as $wert)
		{
		if ($RefWert == 0) { $RefWert=round($wert["Avg"]); }
 		//echo "Wert : ".number_format($wert["Avg"], 1, ",", "")."   ".date("d.m H:i",$wert["MaxTime"])."   ".date("d.m H:i",$wert["MinTime"])."   ".date("d.m H:i",$wert["TimeStamp"])."   ".date("d.m H:i",$wert["LastTime"])."\n";
		echo "Wert Avg: ".number_format($wert["Avg"], 1, ",", "")." MaxTime: ".date("d.m H:i",$wert["MaxTime"])."  MinTime: ".date("d.m H:i",$wert["MinTime"])."  ".($RefWert-round($wert["Avg"]))."mm";
		if ( ($letzterRegen==0) && (($RefWert)-round($wert["Avg"])>0) )
		   {
		   $letzterRegen=$wert["MaxTime"]; 		/* MaxTime ist der Wert mit dem groessten Niederschlagswert, also am Ende des Regens, */
															/* und MinTime daher immer am Anfang des Tages */
		   echo " Letzter Regen !\n";
		   }
		else
			{
			echo "\n";
			}	   
	   	}
	echo "Letzter Regen vor (Agg.Auswertung) : ".number_format((time()-$letzterRegen)/60/60, 1, ",", ""). "Stunden.\n";
	//print_r($werte);

	//echo $parentid."\n";
	/* Berechnung für Giessdauer , Routinen in Config Datei mit Funktion befuellen */
	/*
	$AussenTemperaturGesternMax=$tempwerte[1]["Max"];
	echo "Aussentemperatur max : ".get_AussenTemperaturGesternMax()."   ".$tempwerte[1]["Max"]." \n";
	$AussenTemperaturGestern=$tempwerte[1]["Avg"];
	echo "Aussentemperatur med : ".AussenTemperaturGestern()."   ".$tempwerte[1]["Avg"]." \n";
	*/
	if ( isset($werte[1]["Avg"]) == true ) {	$RegenGestern=$werte[1]["Avg"]; }
	/*
	echo "Regen gestern : ".RegenGestern()."   ".$werte[1]["Avg"]." \n";
	echo "Letzter Regen Zeit : ".date("d.m H:i",LetzterRegen())."   ".date("d.m H:i",$letzterRegen)." \n\n";
	$LetzterRegen=time()-$letzterRegen;
	//echo "Aussentemperatur Gestern : ".$AussenTemperaturGestern." Maximum : ".$AussenTemperaturGesternMax."\n";
	//echo "Regen Gestern : ".$RegenGestern." mm und letzter Regen war vaktuell vor ".($LetzterRegen/60/60)." Stunden.\n";
	*/

	echo "Als Funktion berechnen :\n";
	SetValue($GiessTimeID,giessdauer(true));
	/* SetValue($GiessTimeID,giessdauer());
	$textausgabe="Giesszeit berechnet mit ".GetValue($GiessTimeID)." Minuten da ".number_format($RegenGestern, 1, ",", "")." mm Regen vor "
						.number_format(($LetzterRegen/60/60), 1, ",", "")." Stunden. Temperatur gestern "
						.number_format($AussenTemperaturGestern, 1, ",", "")." max "
						.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
	$log_Giessanlage->message($textausgabe);
	echo $textausgabe."\n"; */

	echo "\n\n";
	$resultEvent=IPS_GetEvent($calcgiesstimeID);
	If($resultEvent["EventActive"]){echo "Timer zur Berechnung Giessdauer aktiv (immer 5 Min vorher).\n";};
	$resultEvent=IPS_GetEvent($timerDawnID);
	If($resultEvent["EventActive"]){echo "Timer zum tatsächlichen Giessen aktiv.\n";};
	
	/* Beginnzeit Timer für morgen ausrechnen */
	$dawnID = @IPS_GetObjectIDByName("Program",0);
	$dawnID = @IPS_GetObjectIDByName("IPSLibrary",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("data",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("modules",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("Weather",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("IPSTwilight",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("Values",$dawnID);
	//$dawnID = @IPS_GetObjectIDByName("SunriseEndLimited",$dawnID);
	$dawnID = @IPS_GetObjectIDByName("SunriseEnd",$dawnID);

	if ($dawnID == true)
		{
		$dawn=GetValue($dawnID);
		$pos=strrpos($dawn,":");
		if ($pos==false) { $dawn="16:00";$pos=strrpos($dawn,":");}
		$hour=(integer)substr($dawn,0,$pos);
		$minute=(integer)substr($dawn,$pos+1,10);
		echo "Sonnenuntergang morgen : ".$dawn."   ".$hour.":".$minute."\n";
		$startminuten=$hour*60+$minute-90;
		$calcminuten=$startminuten-5;
		}
	else     /* keine Dämmerungszeit verfügbar */
		{
		$startminuten=16*60;
		$calcminuten=$startminuten-5;
		}
	echo "Ausgabe Minuten : ".$startminuten."  ".(floor($startminuten/60))." ".($startminuten%60)."  ".$calcminuten."\n";	
	IPS_SetEventCyclicTimeFrom($timerDawnID,(floor($startminuten/60)),($startminuten%60),0);
	IPS_SetEventCyclicTimeFrom($calcgiesstimeID,(floor($calcminuten/60)),($calcminuten%60),0);

	$zeitdauergiessen=(GetValue($GiessTimeID)+1)*$GartensteuerungConfiguration["KREISE"];
	$endeminuten=$startminuten+$zeitdauergiessen;
	$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60))." für die Dauer von ".
	$zeitdauergiessen." Minuten bis ".(floor($endeminuten/60)).":".sprintf("%2d",($endeminuten%60))." .";
	$log_Giessanlage->message($textausgabe);
	echo $textausgabe."\n";

	}


/************************************************************
 *
 * Timer Aufruf
 *
 * calcgiesstime, giesstimer, timerdawn und alloff
 *
 * giesstimer wird abwechselnd abhängig von giesscount einmal mit pausetime (1 min) oder Giesstime (10,20min) initialisiert und am Ende nach einem Durchlauf wieder deaktiviert
 *
 *
 ****************************************************************/


if($_IPS['SENDER'] == "TimerEvent")
	{
	if (isset ($GartensteuerungConfiguration["PAUSE"])) { $pauseTime=$GartensteuerungConfiguration["PAUSE"]; } else { $pauseTime=1; }
	SetValue($GiessPauseID,$pauseTime);

	$TEventName = $_IPS['EVENT'];
	Switch ($TEventName)
		{
		/*
		 * Giesstimer für Dauer Giesszeit
		 */
		case $giesstimerID: /* Alle 10 oder 20 Minuten für Monitor Ein/Aus */
			/* Alle giesdauer Minuten für Monitor Ein/Aus
            Beregner auf der Birkenseite
            (4) Beregner beim Brunnen 1 und 2
      		Schlauchbewaesserung
				(3) Beregner ehemaliges Pool (Spritzer bei Fichte, Poolberegner 1 und 2)
			*/
			$GiessCount=GetValue($GiessCountID);
			if ($GiessCount==0)
				{
    			$failure=set_gartenpumpe(false);
				//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
				}
			else
				{
				if ($GiessCount==(($GartensteuerungConfiguration["KREISE"]*2)+1))
					{
					$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
    				$GiessCount=0;
    				SetValue($GiessAnlageID, GetValue($GiessAnlagePrevID));
	     			IPS_SetEventActive($giesstimerID,false);
     				if ($GartensteuerungConfiguration["DEBUG"]==true)
						{
						$log_Giessanlage->message("Gartengiessanlage Vorgang abgeschlossen");
						$log_Giessanlage->message("Gartengiessanlage zurück auf ".GetValue($GiessAnlagePrevID)." (0-Aus, 1-EinmalEin, 2-Auto) gesetzt");
						}
					}
				else
					{
					if (($GiessCount % 2)==1)
						{
						/*  ungerade Zahl des Giesscounters bedeutet weiterschalten vom letzten Zustand Pause */
						if (($giessTime>0) and (GetValue($GiessAnlageID)>0))
							{
							$failure=set_gartenpumpe(true);
							//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",true);
							IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $giessTime);
							SetValue($GiessTimeRemainID ,$giessTime);
   							$GiessCount+=1;
		     				if ($GartensteuerungConfiguration["DEBUG"]==true)
								{
								$log_Giessanlage->message("Gartengiessanlage Vorgang beginnt jetzt mit einer Giessdauer von: ".$giessTime." Minuten.");
								}
							}
						else
							{
							$failure=set_gartenpumpe(false);
							//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
   							$GiessCount=0;
      						IPS_SetEventActive($giesstimerID,false);
							$log_Giessanlage->message("Gartengiessanlage beginnt nicht, wegen Regen oder geringer Temperatur ");
							}
						}
					else
				   		{
				      	/*  gerade Zahl des Giesscounters bedeutet weiterschalten vom letzten Zustand Giessen */
   						$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
						IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $pauseTime);
						SetValue($GiessTimeRemainID ,$pauseTime);
        				$GiessCount+=1;
				   		}
					}  /* if nicht ende */
				} /* if nicht 0 */
			SetValue($GiessCountID,$GiessCount);
			$Count=floor(GetValue($GiessCountID)/2+GetValue($GiessCountOffsetID));
			if ( isset($GartensteuerungConfiguration["KREIS".(string)($Count)]) )
				{
				SetValue($GiessKreisInfoID,$GartensteuerungConfiguration["KREIS".(string)($Count)]);
				SetValue($GiessKreisID,$Count);
				}
			break;

		/*
		 * Giess Start bei Sonnenuntergang
		 */
		case $timerDawnID: /* Immer um 16:00 bzw. aus Astroprogramm den nächsten Wert übernehmen  */
			if ((GetValue($GiessTimeID)>0) and (GetValue($GiessAnlageID)>0))
			   {
				SetValue($GiessCountID,1);
				IPS_SetEventCyclicTimeBounds($giesstimerID,time(),0);  /* damit der Timer richtig anfängt und nicht zur vollen Stunde */
      			IPS_SetEventActive($giesstimerID,true);
      			}
	      	else /* wenn giessdauer 0 ist nicht giessen */
    	  		{
				SetValue($GiessCountID,0);
      			IPS_SetEventActive($giesstimerID,false);
      			}
				break;

		/*
		 * Garantierter Giess Stopp um 22:00
		 */
		case $allofftimerID: /* Immer um 22:00 sicherheitshalber alles ausschalten  */
			SetValue($GiessCountID,0);
			IPS_SetEventActive($giesstimerID,false);
			$failure=set_gartenpumpe(false);
			//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);

			/* Beginnzeit Timer für morgen ausrechnen */
			$dawnID = @IPS_GetObjectIDByName("Program",0);
			$dawnID = @IPS_GetObjectIDByName("IPSLibrary",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("data",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("modules",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("Weather",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("IPSTwilight",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("Values",$dawnID);
			//$dawnID = @IPS_GetObjectIDByName("SunriseEndLimited",$dawnID);
			$dawnID = @IPS_GetObjectIDByName("SunriseEnd",$dawnID);

			if ($dawnID == true)
				{
				$dawn=GetValue($dawnID);
				$pos=strrpos($dawn,":");
				if ($pos==false) break;
				$hour=(integer)substr($dawn,0,$pos);
				$minute=(integer)substr($dawn,$pos+1,10);
				$startminuten=$hour*60+$minute-90;
				$calcminuten=$startminuten-5;
				}
			else     /* keine Dämmerungszeit verfügbar */
				{
				$startminuten=16*60;
				$calcminuten=$startminuten-5;
				}
			IPS_SetEventCyclicTimeFrom($timerDawnID,(floor($startminuten/60)),($startminuten%60),0);
			IPS_SetEventCyclicTimeFrom($calcgiesstimeID,(floor($calcminuten/60)),($calcminuten%60),0);
			
			//$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60)).".";
			//$log_Giessanlage->message($textausgabe);
			break;

		case $calcgiesstimeID: /* Immer 5 Minuten vor Giesbeginn die Giessdauer berechnen  */
			SetValue($GiessTimeID,giessdauer());
	   		break;
		case $UpdateTimerID: /* Alle 1 Minuten für Berechnung verbleibende Giesszeit */
			$GiessTimeRemain=GetValue($GiessTimeRemainID);
			if ($GiessTimeRemainID > 0) { SetValue($GiessTimeRemainID ,$GiessTimeRemain--); }
			break;
		}
	}
	
	

/****************************************************************************************************/

function giessdauer($debug=false)
	{

	global $archiveHandlerID, $variableID, $display;  /* für agregate Regen */
	global $GiessTimeID,$log_Giessanlage,$GiessDauerInfoID;
	global $GartensteuerungConfiguration; /* für minimale mittlere Temperatur */

	$giessdauer=0;
	$display=$debug;
	
	$variableTempID=get_aussentempID();
	$variableID=get_raincounterID();
	$endtime=time();
	$starttime=$endtime-60*60*24*2;  /* die letzten zwei tage Temperatur*/
	$starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage Niederschlag*/

	$Server=RemoteAccess_Address();
	if ($debug)
		{
		echo"--------Giessdauerberechnung:\n";
		echo "Server : ".$Server."\n\n";
		}
	If ($Server=="")
		{
  		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
   		$tempwerte = AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
		$variableTempName = IPS_GetName($variableTempID);
		$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
	   	$werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);
		$variableName = IPS_GetName($variableID);
		}
	else
		{
		$rpc = new JSONRPC($Server);
		$archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
   		$tempwerte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
		$variableTempName = $rpc->IPS_GetName($variableTempID);
		$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
		$werte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);
		$variableName = $rpc->IPS_GetName($variableID);
		if ($debug)
			{
			echo "   Server : ".$Server."\n";
			}
		}

	//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
	$AussenTemperaturGesternMax=$tempwerte[1]["Max"];
	//$AussenTemperaturGestern=AussenTemperaturGestern();
	$AussenTemperaturGestern=$tempwerte[1]["Avg"];
	
	$letzterRegen=0;
	$regenStand=0;
	$regenStand2h=0;
	$regenStand48h=0;
	$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
	$regenStandEnde=0;
	$RegenGestern=0;
	/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */

	foreach ($werteLog as $wert)
		{
    	//echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
		$regenStandAnfang=$wert["Value"];
		If (($letzterRegen==0) && ($wert["Value"]>0))
			{
			$letzterRegen=$wert["TimeStamp"];
			$regenStandEnde=$wert["Value"];
			}
		if (((time()-$wert["TimeStamp"])/60/60)<2)
	   		{
			$regenStand2h=$regenStandEnde-$regenStandAnfang;
			}
		if (((time()-$wert["TimeStamp"])/60/60)<48)
			{
			$regenStand48h=$regenStandEnde-$regenStandAnfang;
			}
		$regenStand=$regenStandEnde-$regenStandAnfang;
		}
	if ($debug)
		{
		echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h." 10 Tage : ".$regenStand." mm.\n";
		}
	$letzterRegen=0;
	$RefWert=0;
	foreach ($werte as $wert)
		{
		if ($RefWert == 0) { $RefWert=round($wert["Avg"]); }
		if ( ($letzterRegen==0) && (($RefWert)-round($wert["Avg"])>0) )
		   {
		   $letzterRegen=$wert["MaxTime"]; 		/* MaxTime ist der Wert mit dem groessten Niederschlagswert, also am Ende des Regens, und MinTime daher immer am Anfang des Tages */
		   }
	   	}
	if ( isset($werte[1]["Avg"]) == true ) {	$RegenGestern=$werte[1]["Avg"]; }
	$letzterRegenStd=(time()-$letzterRegen)/60/60;

	if ($debug)
		{
		echo "Letzter erfasster Regenwert : ".date("d.m H:i",$letzterRegen)." also vor ".$letzterRegenStd." Stunden.\n";
 		echo "Aussentemperatur Gestern : ".number_format($AussenTemperaturGestern, 1, ",", "")." Grad (muss > ".$GartensteuerungConfiguration["TEMPERATUR-MITTEL"]."° sein).\n";
 		if ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"]))
 		   {
 		   echo "Doppelte Giesszeit da Maximumtemperatur  : ".number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad groesser als ".$GartensteuerungConfiguration["TEMPERATUR-MAX"]." Grad ist.\n";
			}
		if (($letzterRegenStd/60/60)<50)
		   {
			echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
				" mm und letzter Regen war aktuell vor ".number_format(($letzterRegenStd), 1, ",", "")." Stunden.\n";
			}
		else
		   {
			echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
				" mm und letzter Regen war aktuell vor länger als 48 Stunden.\n";
		   }
		echo "Regen letzte 2/48 Stunden : ".$regenStand2h." mm / ".$regenStand48h." mm \n\n";
		if ($regenStand48h<($GartensteuerungConfiguration["REGEN48H"]))
		   {
			echo "Regen in den letzten 48 Stunden weniger als ".$GartensteuerungConfiguration["REGEN48H"]."mm.\n";
			}
		if ($regenStand<($GartensteuerungConfiguration["REGEN10T"]))
		   {
			echo "Regen in den letzten 10 Tagen weniger als ".$GartensteuerungConfiguration["REGEN10T"]."mm.\n";
			}
		}

	if (($regenStand48h<($GartensteuerungConfiguration["REGEN48H"])) && ($AussenTemperaturGestern>($GartensteuerungConfiguration["TEMPERATUR-MITTEL"])))
	   { /* es hat in den letzten 48h weniger als xx mm geregnet und die mittlere Aussentemperatur war groesser xx Grad*/
	   if (($regenStand2h)==0)
	      { /* und es regnet aktuell nicht */
			if ( ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"])) || ($regenStand<($GartensteuerungConfiguration["REGEN10T"])) )
			   { /* es war richtig warm */
				$giessdauer=20;
				}
			else
			   { /* oder nur gleichmässig warm */
				$giessdauer=10;
			   }
	      }
	   }
	$textausgabe="Giessdauer:".GetValue($GiessTimeID)
			." Min. Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
	$textausgabe2="Giessdauer:".GetValue($GiessTimeID)
			." Min. <br>Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. <br>Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
	SetValue($GiessDauerInfoID,$textausgabe2);
	if ($debug==false)
		{
		$log_Giessanlage->message($textausgabe);
		}
	else
	   {
	   echo $textausgabe;
	   }
	return $giessdauer;
	}



	
?>