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

$gartensteuerung = new Gartensteuerung();
	
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
		$werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
		$werteStd = AC_GetAggregatedValues($archiveHandlerID, $variableID, 0, $starttime2, $endtime,0);	/* Stundenwerte agreggiert */
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
		$werteStd = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableID, 0, $starttime2, $endtime,0);
		$variableName = $rpc->IPS_GetName($variableID);
		}

	/*
	echo "Regenwerte im Detail (immer zwei Wete Zeitstempel und Wert) :\n";
	print_r($werteLog);

	echo "Regenwerte täglich agreggiert (Min Time ist der Beginn und Maxtime das Ende) :\n";
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
 	echo "\nGeloggte Werte der Regen-Variable in den letzten 10 Tagen: ".$variableName." mit ".$anzahl." Werte\n\n";
		
	foreach ($gartensteuerung->regenStatistik as $regeneintrag)
		{
		echo "Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"]).
		   "  Regenende ".date("d.m H:i",$regeneintrag["Ende"]).
		   " mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "").
		   " mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		}	
		
	echo "\n";			
	echo "Regenstand 2h : ".$gartensteuerung->regenStand2h." 48h : ".$gartensteuerung->regenStand48h."\n";
	echo "Letzter Regen vor ".number_format((time()-$gartensteuerung->letzterRegen)/60/60, 1, ",", ""). " Stunden.\n";

	/*
	echo "\nAggregierte Stunden Regenwerte:\n";
	$regenbeginn=0; $regenende=0; $regen=0; $regendauer=0; $regendauermin=60; $regenstd=0; $regenmaxstd=0;
	$regeneintraege=array();
	//print_r($werteStd);	
	foreach ($werteStd as $wert)
		{
		if ( $wert["MaxTime"] != $wert["MinTime"] )
			{
			// es regnet 
			$regenstd=($wert["Max"]-$wert["Min"])/(($wert["MaxTime"]-$wert["MinTime"])/3600);
			if ($regenende>0) 
				{
				// es regnet schon länger 
				$regendauer=($regenende-$wert["MaxTime"])/60;
				if ($regenstd>$regenmaxstd) $regenmaxstd=$regenstd;
				if ( ($regendauer>$regendauermin) or ($regen<0.4) )
					{
					if ( ($regendauer>$regendauermin) and ($regen<0.4) )
						{
						// Regen nicht der Rede wert 
						//echo "    >>".$regendauer." / ".$regendauermin." min, d.h. Regendauer zu kurz und Regen zu wenig, Regenfall ignorieren !\n";
						$regen=0;
						$regenende=0;
						$regenbeginn=0;
						$regendauermin=60;
						}
					else
						{
						// Regen ist zu Ende
						if ($regendauer>$regendauermin)
							{
							//echo "    >>Regenbeginn ".date("d.m H:i",$regenbeginn)."  Regenende ".date("d.m H:i",$regenende)." mit insgesamt ".number_format($regen, 1, ",", "").
									" mm Regen. Ca. ".number_format($regenmaxstd, 1, ",", "")."mm/Std.\n";
							$regeneintraege[$regenbeginn]["Beginn"]=$regenbeginn;
							$regeneintraege[$regenbeginn]["Ende"]  =$regenende;
							$regeneintraege[$regenbeginn]["Regen"] =$regen;
							$regeneintraege[$regenbeginn]["Max"]   =$regenmaxstd;
							$regen=0;
							$regenende=0;
							$regenbeginn=0;
							$regendauermin=60;
							$regenmaxstd=0;						
							}
						else
							{
							// es regnet noch
							$regen+=$wert["Max"]-$wert["Min"];
							$regenbeginn=$wert["MinTime"];					
							//echo "                   Regendauer : ".number_format($regendauer, 1, ",", "")." / ".number_format($regendauermin, 1, ",", "")." min und Regen : ".$regen." mm.\n";
							$regendauermin+=60;
							}	
						}	
					}
				else
					{
					// es regnet noch 
					$regen+=$wert["Max"]-$wert["Min"];
					$regenbeginn=$wert["MinTime"];					
					//echo "                   Regendauer : ".number_format($regendauer, 1, ",", "")." / ".number_format($regendauermin, 1, ",", "")." min und Regen : ".number_format($regen, 1, ",", "")." mm.\n";
					$regendauermin+=60;	
					}
				}	
			if ($regenende==0) 
				{
				$regenende=$wert["MaxTime"];
				$regenbeginn=$wert["MinTime"];					
				$regendauermin=($wert["MaxTime"]-$wert["MinTime"])/60+60;				
				$regen=$wert["Max"]-$wert["Min"];
				//echo "    >>Regenende ".date("d.m H:i",$regenende).".\n";
				}
			//echo "   Regen : ".number_format($wert["Max"]-$wert["Min"], 1, ",", "")."mm  um ".date("d.m H:i",$wert["MinTime"])."                    Wert Avg: ".number_format($wert["Avg"], 1, ",", "")." Wert Max: ".number_format($wert["Max"], 1, ",", "")." Wert Min: ".number_format($wert["Min"], 1, ",", "")." MaxTime: ".date("d.m H:i",$wert["MaxTime"])."  MinTime: ".date("d.m H:i",$wert["MinTime"])."\n";
			}
		}
	//print_r($regeneintraege);
	foreach ($regeneintraege as $regeneintrag)
		{
		echo "Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"]).
		   "  Regenende ".date("d.m H:i",$regeneintrag["Ende"]).
		   " mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "").
		   " mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		}
	*/	
	
	$letzterRegen=0;
	$RefWert=0;
	echo "\nAggregierte Regenwerte:\n";	
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

	echo "Zum Vergleich als Funktion berechnen :\n";
	SetValue($GiessTimeID,$gartensteuerung->giessdauer(true));
	echo "Giessdauer wurde festgelegt mit ".GetValue($GiessTimeID)." Min.\n";
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
				IPS_SetEventActive($UpdateTimerID,false);
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
					IPS_SetEventActive($UpdateTimerID,false);
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
			SetValue($GiessTimeID,$gartensteuerung->giessdauer());
	   		break;
		case $UpdateTimerID: /* Alle 1 Minuten für Berechnung verbleibende Giesszeit */
			$GiessTimeRemain=GetValue($GiessTimeRemainID);
			if ($GiessTimeRemainID > 0) { SetValue($GiessTimeRemainID ,$GiessTimeRemain--); }
			break;
		}
	}
	
	

/****************************************************************************************************/

class Gartensteuerung
	{
	
	private 	$archiveHandlerID;
	private		$debug;
	private		$tempwerte, $tempwerteLog, $werteLog, $werte;
	private		$variableTempID, $variableID;
	
	public 		$regenStatistik;
	public 		$letzterRegen, $regenStand2h, $regenStand48h;
		
	public function __construct($starttime=0,$starttime2=0,$debug=false)
		{
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->debug=$debug;
		$endtime=time();
		if ($starttime==0)  { $starttime=$endtime-60*60*24*2; }  /* die letzten zwei Tage Temperatur*/
		if ($starttime2==0) { $starttime2=$endtime-60*60*24*10; }  /* die letzten 10 Tage Niederschlag*/

		$this->variableTempID=get_aussentempID();
		$this->variableID=get_raincounterID();

		$Server=RemoteAccess_Address();
		if ($this->debug)
			{
			echo"--------Class Construct Giessdauerberechnung:\n";
			}
		If ($Server=="")
			{
  			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$this->tempwerteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
	   		$this->tempwerte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);	/* Tageswerte agreggiert */
			$this->werteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
		   	$this->werte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$this->tempwerteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
   			$this->tempwerte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);
			$this->werteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			$this->werte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */
		$this->letzterRegen=0;
		$this->regenStand2h=0;
		$this->regenStand48h=0;
		$regenStand=0;			/* der erste Regenwerte, also aktueller Stand */
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
		$regenAnfangZeit=0;
		$regenStandEnde=0;
		$regenEndeZeit=0;
		$regenMenge=0; $regenMengeAcc=0;
		$regenDauer=0; $regenDauerAcc=0;
		$vorwert=0; $vorzeit=0;
		$this->regenStatistik=array();
		$regenMaxStd=0;
		foreach ($this->werteLog as $wert)
			{
			if ($vorwert==0) 
		   		{ 
				if ($this->debug) {	echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				$regenStand=$wert["Value"];
				}
			else 
				{
				/* die erste Zeile erst mit dem zweiten Eintrag auffuellen ... */
				$regenMenge=round(($vorwert-$wert["Value"]),1);
				$regenDauer=round(($vorzeit-$wert["TimeStamp"])/60,0);
				if ($this->debug) { echo " ".$regenMenge."mm/".$regenDauer."min  "; }
				if (($regenMenge/$regenDauer*60)>$regenMaxStd) {$regenMaxStd=$regenMenge/$regenDauer*60;}
				if ( ($regenMenge<0.4) and ($regenDauer>60) ) 
					{
					/* gilt nicht als Regen, ist uns zu wenig, mehr ein nieseln */
					if ($regenEndeZeit != 0)
						{ 
						/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
						$regenAnfangZeit=$vorzeit;
						if ($this->debug) 
							{ 
							echo $regenMengeAcc."mm ".$regenDauerAcc."min ";						
							echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
							}
						$this->regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
						$this->regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
						$this->regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
						$this->regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
						$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
						}
					else
						{
						if ($this->debug) { echo "* "; }
						}	
					$regenMenge=0; $regenDauerAcc=0; $regenMengeAcc=0;
					} 
				else
					{
					/* es regnet */
					$regenMengeAcc+=$regenMenge;
					$regenDauerAcc+=$regenDauer;				
					if ($this->debug) { echo $regenMengeAcc."mm ".$regenDauerAcc."min "; }
					if ($regenEndeZeit==0)
						{
						$regenStandEnde=$vorwert;
						$regenEndeZeit=$vorzeit;
						}						
					if ($this->debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
					If ( ($this->letzterRegen==0) && (round($wert["Value"]) > 0) )
						{
						$this->letzterRegen=$wert["TimeStamp"];
						$regenStandEnde=$wert["Value"];
						if ($this->debug) { echo "Letzter Regen !"; }
			  			}				
					}	
				if ($this->debug) { echo "\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				}
			/* Regenstand innerhalb der letzten 2 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<2)
				{
				$this->regenStand2h=$regenStand-$wert["Value"];
				}
			/* Regenstand innerhalb der letzten 48 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$this->regenStand48h=$regenStand-$wert["Value"];
				}
			$vorwert=$wert["Value"];	
			$vorzeit=$wert["TimeStamp"];
			}
		if ($this->debug) { echo "\n\n"; }
		}
	

	public function giessdauer($debug=false)
		{

		global $GiessTimeID,$log_Giessanlage,$GiessDauerInfoID;
		global $GartensteuerungConfiguration; /* für minimale mittlere Temperatur */

		$giessdauer=0;
	
		if ($debug==true) { $this->debug=true; }
		$Server=RemoteAccess_Address();
		if ($this->debug)
			{
			echo"--------Giessdauerberechnung:\n";
			}
		If ($Server=="")
			{
			$variableTempName = IPS_GetName($this->variableTempID);
			$variableName = IPS_GetName($this->variableID);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$variableTempName = $rpc->IPS_GetName($this->variableTempID);
			$variableName = $rpc->IPS_GetName($this->variableID);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
		$AussenTemperaturGesternMax=$this->tempwerte[1]["Max"];
		//$AussenTemperaturGestern=AussenTemperaturGestern();
		$AussenTemperaturGestern=$this->tempwerte[1]["Avg"];
	
		$letzterRegen=0;
		$regenStand=0;
		$regenStand2h=0;
		$regenStand48h=0;
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogged wurden */
		$regenStandEnde=0;
		$RegenGestern=0;
		/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */

		foreach ($this->werteLog as $wert)
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
		if ($this->debug)
			{
			echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h." 10 Tage : ".$regenStand." mm.\n";
			}
		$letzterRegen=0;
		$RefWert=0;
		foreach ($this->werte as $wert)
			{
			if ($RefWert == 0) { $RefWert=round($wert["Avg"]); }
			if ( ($letzterRegen==0) && (($RefWert)-round($wert["Avg"])>0) )
				{
		   		$letzterRegen=$wert["MaxTime"]; 		/* MaxTime ist der Wert mit dem groessten Niederschlagswert, also am Ende des Regens, und MinTime daher immer am Anfang des Tages */
				}
			}
		if ( isset($werte[1]["Avg"]) == true ) {	$RegenGestern=$werte[1]["Avg"]; }
		$letzterRegenStd=(time()-$letzterRegen)/60/60;

		if ($this->debug)
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
		if ($this->debug==false)
			{
			$log_Giessanlage->message($textausgabe);
			}
		else
			{
			echo $textausgabe;
			}
		return $giessdauer;
		}
		
	}  /* Ende class Gartensteuerung */



	
?>