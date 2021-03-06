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

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');
IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');


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

    $gartensteuerung = new Gartensteuerung(0,0,true);   // default, default, debug=false
    $GartensteuerungConfiguration =	$gartensteuerung->getConfig_Gartensteuerung();

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    $debug=true;

/******************************************************

				Script IDs initialisieren
				
*************************************************************/

$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);

/******************************************************

				Nachrichtenspeicher initialisieren
				
	wir such in der Modul Kategorie nach einer Kategorie die Nachricht enthält und dort nach Input			
				
*************************************************************/

$object2= new ipsobject($CategoryIdData);
$object3= new ipsobject($object2->osearch("Nachricht"));
$NachrichtenInputID=$object3->osearch("Input");
$log_Giessanlage=new Logging("C:\Scripts\Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

/******************************************************

				Timer initialisieren
				
*************************************************************/

$allofftimer1ID = @IPS_GetEventIDByName("Giessstopp1", $GartensteuerungScriptID);
$allofftimer2ID = @IPS_GetEventIDByName("Giessstopp2", $GartensteuerungScriptID);

$timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
$calcgiesstimeID = @IPS_GetEventIDByName("Timer4", $GartensteuerungScriptID);
$UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);
$SlowUpdateTimerID = @IPS_GetEventIDByName("UpdateTimerHourly", $GartensteuerungScriptID);

if ( ($allofftimer1ID==false) || ($allofftimer2ID==false) ) $fatalerror=true;

//$alleEreignisse = IPS_GetEventListByType(1);
//print_r($alleEreignisse);

IPS_SetEventActive($calcgiesstimeID,true);
IPS_SetEventActive($timerDawnID,true);

/* Giesstopp Timer */
IPS_SetEventActive($allofftimer1ID,true);
IPS_SetEventActive($allofftimer2ID,true);

/******************************************************

				Variablen initialisieren
				
*************************************************************/

	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
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
    $GiessStartzeitpunktID 	= @IPS_GetVariableIDByName("GiessStartzeitpunkt",$categoryId_Register);
	
$giessTime=GetValue($GiessTimeID);
$GiessStartzeitpunkt=GetValue($GiessStartzeitpunktID);         // true ist Abends
echo "Naechstes mal Giessen erfolgt ".($GiessStartzeitpunkt ? "Abends":"Morgens")." fuer ".$giessTime." Minuten.\n";

/******************************************************

				EXECUTE

*************************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
	$log_Giessanlage->LogMessage("Gartengiessanlage Execute aufgerufen");
	//$variableTempID = $gartensteuerung->getConfig_aussentempID();
	//$variableID     = $gartensteuerung->getConfig_raincounterID();
    $variableTempID   = $GartensteuerungConfiguration["Configuration"]["AussenTemp"];
    $variableID       = $GartensteuerungConfiguration["Configuration"]["RainCounter"];
	
	echo "\n";	
	echo "=======EXECUTE====================================================\n";
	echo "\n";
	echo "Gartensteuerung Script  ID : ".$GartensteuerungScriptID."  (".IPS_GetName(IPS_GetParent($GartensteuerungScriptID))."/".IPS_GetName($GartensteuerungScriptID).")\n";
	echo "Giessanlage             ID : ".$GiessAnlageID."  (".IPS_GetName(IPS_GetParent($GiessAnlageID))."/".IPS_GetName($GiessAnlageID).")\n";
	echo "\nStatus Giessanlage         ".GetValue($GiessAnlageID)." (0-Aus,1-Einmalein,2-Auto) \n";
	echo "Status Giessanlage zuletzt ".GetValue($GiessAnlagePrevID)." (0-Aus,1-Einmalein,2-Auto) \n\n";
	echo "AussenTemperatur        ID : ".$variableTempID."  (".IPS_GetName(IPS_GetParent($variableTempID))."/".IPS_GetName($variableTempID).")    ".GetValue($variableTempID)."°C \n";
	echo "RainCounter             ID : ".$variableID."  (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")    ".GetValue($variableID)."mm \n";
	echo "\n";
	echo "Timerprogrammierung: \n";
	echo "  AllOff Timer 1 ID : ".$allofftimer1ID."\n";
    echo "  AllOff Timer 2 ID : ".$allofftimer2ID."\n";
	echo "  Dawn Timer ID   : ".$timerDawnID."\n";
	echo "  Calc Timer ID   : ".$calcgiesstimeID."\n";
	echo "  Update Timer ID : ".$UpdateTimerID."\n";
	echo "  Hourly Timer ID : ".$SlowUpdateTimerID."\n";
	echo "\n";
	echo "Gartensteuerungs Konfiguration:\n";
	print_r($GartensteuerungConfiguration);
	if ( (isset($GartensteuerungConfiguration["Configuration"]["DEBUG"])) && ($GartensteuerungConfiguration["Configuration"]["DEBUG"]==true) )
	   {
	   echo "  Debugmeldungen eingeschaltet.\n";
	   }
	$Count=floor(GetValue($GiessCountID)/2+GetValue($GiessCountOffsetID));
	if ( isset($GartensteuerungConfiguration["Configuration"]["KREIS".(string)$Count]) )
		{	
		echo "  Giesskreis : ".$GartensteuerungConfiguration["Configuration"]["KREIS".(string)$Count]."\n";
		}
	else
		{
		echo "  Giesskreis : ".$Count."\n";
		}	
	if (isset ($GartensteuerungConfiguration["Configuration"]["PAUSE"])) 
		{ 
		$pauseTime=$GartensteuerungConfiguration["Configuration"]["PAUSE"]; 
		} 
	else 
		{ 
		$pauseTime=1; 
		}
	SetValue($GiessPauseID,$pauseTime);
	echo "  Pause zwischen den Giesskriesen : ".$pauseTime." Minuten\n";
		
	echo "Jetzt umstellen auf berechnete Werte. Es reicht ein Regen und ein Aussentemperaturwert.\n";
	$endtime=time();
	$starttime=$endtime-60*60*24*3;  /* die letzten zwei Tage, sicherheitshalber drei nehmen */
	$starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage */

	$Server=RemoteAccess_Address();
	If ($Server=="")
		{
		echo "Regen und Temperaturdaten, lokale Daten: \n\n";		
		//$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$tempwerte = AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);                // 1 für tägliche Aggregation der Temperaturwerte
		$tempwerteLog = AC_GetLoggedValues($archiveHandlerID, $variableTempID, $starttime, $endtime,0);		
		$variableTempName = IPS_GetName($variableTempID);
		$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
		$werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
		$werteStd = AC_GetAggregatedValues($archiveHandlerID, $variableID, 0, $starttime2, $endtime,0);	/* Stundenwerte agreggiert */
		$variableName = IPS_GetName($variableID);
        if (count($tempwerte)<2) AC_ReAggregateVariable ($archiveHandlerID, $variableTempID);    
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
    */
    if (count($tempwerte)<2)
        {        
    	echo "Fehler, Aussentemperaturwerte, taeglich aggregiert liefert zu wenig Einträge:\n";	
	    print_r($tempwerte);
        echo "Die geloggten Eintraege der letzten drei Tage:\n";
	    print_r($tempwerteLog);

        //$tempwert = $tempwerte[0];         echo "  aggregierter Wert : ".date("d.m.Y H:i:s",$tempwert["TimeStamp"])."   ".($tempwert["Duration"]/60)." Minuten.\n";
        }
    /*
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
	SetValue($GiessTimeID,$gartensteuerung->Giessdauer($GartensteuerungConfiguration["Configuration"]));            // umgestellt auf Sub Configuration
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

	$zeitdauergiessen=(GetValue($GiessTimeID)+1)*$GartensteuerungConfiguration["Configuration"]["KREISE"];
	$endeminuten=$startminuten+$zeitdauergiessen;
	$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60))." für die Dauer von ".
	$zeitdauergiessen." Minuten bis ".(floor($endeminuten/60)).":".sprintf("%2d",($endeminuten%60))." .";
	//$log_Giessanlage->message($textausgabe);
	echo $textausgabe."\n";

    /* Statistik Modul ausprobieren */
    echo "Statistikomodul wird einmal am Tag von TimerdawnID aufgerufen:\n";
    if (isset($GartensteuerungConfiguration["Configuration"]["RainCounterHistory"])) $input = $GartensteuerungConfiguration["Configuration"]["RainCounterHistory"];
    else $input=[];    
    $gartensteuerung->getRainStatistics($input);  // die Werte berechnen, die in der nächsten Routine verwendet werden    
    SetValue($gartensteuerung->StatistikBox1ID,$gartensteuerung->writeOverviewMonthsHtml($gartensteuerung->RegenKalendermonate));
    SetValue($gartensteuerung->StatistikBox2ID,$gartensteuerung->writeOverviewMonthsHtml($gartensteuerung->DauerKalendermonate));
    SetValue($gartensteuerung->StatistikBox3ID,$gartensteuerung->writeRainEventsHtml($gartensteuerung->listRainEvents(100)));


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
	/* Zeitdauer für Pause zwischen den Giessereignissen aus der Config holen oder selbst bestimmen */
	if (isset ($GartensteuerungConfiguration["Configuration"]["PAUSE"])) { $pauseTime=$GartensteuerungConfiguration["Configuration"]["PAUSE"]; } else { $pauseTime=1; }
	SetValue($GiessPauseID,$pauseTime);

	$TEventName = $_IPS['EVENT'];
	Switch ($TEventName)
		{
		/*
		 * Giess Start bei Sonnenuntergang oder Sonnenaufgang
		 *
		 */
		case $timerDawnID: /* Immer um 16:00 bzw. aus Astroprogramm den nächsten Wert übernehmen  */
			if ((GetValue($GiessTimeID)>0) and (GetValue($GiessAnlageID)>0))
				{
				SetValue($GiessCountID,1);
				IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit der Timer richtig anfängt und nicht zur vollen Stunde */
				IPS_SetEventActive($UpdateTimerID,true);
				$log_Giessanlage->LogMessage("Gartengiessanlage hat beschlossen fuer ".GetValue($GiessTimeID)." Minuten zu giessen");
				$log_Giessanlage->LogNachrichten("Gartengiessanlage hat beschlossen fuer ".GetValue($GiessTimeID)." Minuten zu giessen");
				}
			else /* wenn giessdauer 0 ist nicht giessen */
				{
				SetValue($GiessCountID,0);
				IPS_SetEventActive($UpdateTimerID,false);
				}
            if (isset($GartensteuerungConfiguration["Configuration"]["RainCounterHistory"])) $input = $GartensteuerungConfiguration["Configuration"]["RainCounterHistory"];
            else $input=[];    
            $gartensteuerung->getRainStatistics($input);  // die Werte berechnen, die in der nächsten Routine verwendet werden   
            SetValue($gartensteuerung->StatistikBox1ID,$gartensteuerung->writeOverviewMonthsHtml($gartensteuerung->RegenKalendermonate));
            SetValue($gartensteuerung->StatistikBox2ID,$gartensteuerung->writeOverviewMonthsHtml($gartensteuerung->DauerKalendermonate));
            SetValue($gartensteuerung->StatistikBox3ID,$gartensteuerung->writeRainEventsHtml($gartensteuerung->listRainEvents(100)));
			break;

		/*
		 * Garantierter Giess Stopp um 10:00 und 22:00
		 */
		case $allofftimer1ID: /* Immer um 10:00 sicherheitshalber alles ausschalten  */
		case $allofftimer2ID: /* Immer um 22:00 sicherheitshalber alles ausschalten  */
            /* und den Zeitpunkt für die Evaluierung für den nächsten Giesszeitpunkt bestimmen */
			SetValue($GiessCountID,0);
			IPS_SetEventActive($UpdateTimerID,false);
			if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(false,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
			else $failure=set_gartenpumpe(false);
			//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);

			/* Beginnzeit Timer für morgen ausrechnen, abhängig von Konfig entweder morgens oder abends */
            $startminuten=$gartensteuerung->fromdusktilldawn($GiessStartzeitpunkt);
			$calcminuten=$startminuten-5;
			IPS_SetEventCyclicTimeFrom($timerDawnID,(floor($startminuten/60)),($startminuten%60),0);
			IPS_SetEventCyclicTimeFrom($calcgiesstimeID,(floor($calcminuten/60)),($calcminuten%60),0);
			
			if ($GartensteuerungConfiguration["Configuration"]["DEBUG"]==true)
				{
    			$log_Giessanlage->LogMessage("Evaluierung Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60)));
	    		$log_Giessanlage->LogNachrichten("Evaluierung Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60)));
                }
        	break;

		case $calcgiesstimeID: /* Immer 5 Minuten vor Giesbeginn die Giessdauer berechnen  */
			SetValue($GiessTimeID,$gartensteuerung->Giessdauer($GartensteuerungConfiguration["Configuration"]));
	   		break;

		/*
		 * wird alle Minuten während der Giessdauer aufgerufen. Übernimmt das Weiterschalten und Herunterzählen der verbleibenden
		 * Giessdauer.
		 *
		 */
		case $UpdateTimerID: /* Alle 1 Minuten für Berechnung verbleibende Giesszeit */
			/* Gesteuert über Timer Ein/Aus, GiessCount und GiessTimeRemain
			 * Jede Minute wird der Stand der verbleibenden Minuten heruntergezählt, 
			 
			     Alle giesdauer Minuten für Monitor Ein/Aus
				Beregner auf der Birkenseite
				(4) Beregner beim Brunnen 1 und 2
					Schlauchbewaesserung
				(3) Beregner ehemaliges Pool (Spritzer bei Fichte, Poolberegner 1 und 2)
			*/
			$GiessCount=GetValue($GiessCountID);
			//if ($GartensteuerungConfiguration["DEBUG"]==true) $log_Giessanlage->message("Gartengiessanlage Giesstimer ".$TEventName."  ".$GiessCount);
			// zweimal message innerhalb eines Scripts führt zur gleichen Nachricht
			if ($GiessCount==0)
				{			
				if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(false,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
				else $failure=set_gartenpumpe(false);

				//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
				IPS_SetEventActive($UpdateTimerID,false);
				SetValue($GiessTimeRemainID ,0);				
				}
			else
				{
				/* es wird gegossen bis GiessCount die Anzanhler der Giesskreise erreicht hat und wieder auf  Null gesetzt wird */
				if ($GiessCount==(($GartensteuerungConfiguration["Configuration"]["KREISE"]*2)+1))
					{
					if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(false,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
					else $failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
	 				$GiessCount=0;
					SetValue($GiessTimeRemainID ,0);
                    if ( GetValue($GiessAnlageID) != GetValue($GiessAnlagePrevID) )	$difference=true; else $difference=false;			
					SetValue($GiessAnlageID, GetValue($GiessAnlagePrevID));
					IPS_SetEventActive($UpdateTimerID,false);
					if ($GartensteuerungConfiguration["Configuration"]["DEBUG"]==true)
						{
						$log_Giessanlage->LogMessage("Gartengiessanlage Vorgang abgeschlossen");
						$log_Giessanlage->LogNachrichten("Gartengiessanlage Vorgang abgeschlossen");
                        if ($difference) 
                            {
                            $log_Giessanlage->LogMessage("Gartengiessanlage zurück auf ".GetValue($GiessAnlagePrevID)." (0-Aus, 1-EinmalEin, 2-Auto) gesetzt");
						    $log_Giessanlage->LogNachrichten("Gartengiessanlage zurück auf ".GetValue($GiessAnlagePrevID)." (0-Aus, 1-EinmalEin, 2-Auto) gesetzt");
                            }
						}
					}
				else
					{
					$GiessTimeRemain=GetValue($GiessTimeRemainID);
					if ($GiessTimeRemain == 0)
						{
						if (($GiessCount % 2)==1)
							{
							/*  ungerade Zahl des Giesscounters bedeutet weiterschalten vom letzten Zustand Pause */
							if (($giessTime>0) and (GetValue($GiessAnlageID)>0))
								{
								if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(true,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
								else $failure=set_gartenpumpe(true);
								//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",true);
								SetValue($GiessTimeRemainID ,$giessTime);
								$GiessCount+=1;
								if ($GartensteuerungConfiguration["Configuration"]["DEBUG"]==true)
									{
									$log_Giessanlage->LogMessage("Gartengiessanlage Vorgang beginnt jetzt mit einer Giessdauer von: ".$giessTime." Minuten.");
									//$log_Giessanlage->LogNachrichten("Gartengiessanlage Vorgang beginnt jetzt mit einer Giessdauer von: ".$giessTime." Minuten.");
									}
								}
							else
								{
								if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(false,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
								else $failure=set_gartenpumpe(false);
								//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
								$GiessCount=0;
								IPS_SetEventActive($giesstimerID,false);
								$log_Giessanlage->LogMessage("Gartengiessanlage beginnt nicht, wegen Regen oder geringer Temperatur ");
								$log_Giessanlage->LogNachrichten("Gartengiessanlage beginnt nicht, wegen Regen oder geringer Temperatur ");
								}
							}
						else
							{
							/*  gerade Zahl des Giesscounters bedeutet weiterschalten vom letzten Zustand Giessen */
							if (isset($GartensteuerungConfiguration["Configuration"]["PUMPE"])==true) $failure=set_gartenpumpe(false,$GartensteuerungConfiguration["Configuration"]["PUMPE"]);
							else $failure=set_gartenpumpe(false);
							//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
							SetValue($GiessTimeRemainID ,$pauseTime);
							$GiessCount+=1;
							}
						}
					else
						{
						SetValue($GiessTimeRemainID ,$GiessTimeRemain-1); 
						//if ($GartensteuerungConfiguration["Configuration"]["DEBUG"]==true) $log_Giessanlage->message("Gartengiessanlage Update RemainTime auf ".GetValue($GiessTimeRemainID)." Min");			
						}
					}  /* if nicht ende */
				} /* if nicht 0 */
			SetValue($GiessCountID,$GiessCount);
			$Count=floor(GetValue($GiessCountID)/2+GetValue($GiessCountOffsetID));
			if ( isset($GartensteuerungConfiguration["Configuration"]["KREIS".(string)($Count)]) )
				{
				SetValue($GiessKreisInfoID,$GartensteuerungConfiguration["Configuration"]["KREIS".(string)($Count)]);
				SetValue($GiessKreisID,$Count);                          
				}

			break;
		}
		
	} // Ende Timerevent
	
	

/****************************************************************************************************/


	
?>