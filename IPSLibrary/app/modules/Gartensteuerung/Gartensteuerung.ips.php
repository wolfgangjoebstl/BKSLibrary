<?

/*
	 * @defgroup ipstwilight IPSTwilight
	 * @ingroup modules_weather
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

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
include_once(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');


/******************************************************

				INIT
				
*************************************************************/

//$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSModuleManagerGUI');
$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Gartensteuerung.Gartensteuerung');

$pauseTime=1;

//$NachrichtenGartenInputID=19073;
//$NachrichtenGartenScriptID=14948;

/* alternatives Logging mit Objektorientierung */

$object= new ipsobject($parentid);
$object2= new ipsobject($object->oparent());
//$object2->oprint("Nachricht");
$tempOID=$object2->osearch("Nachricht");
$NachrichtenScriptID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Gartensteuerung.Nachrichtenverlauf-Garten');
$GartensteuerungScriptID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Gartensteuerung.Gartensteuerung');

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

/* Timerprogrammierung */

$eid1 = @IPS_GetEventIDByName("Timer1", $_IPS['SELF']);
if ($eid1==false)
	{
	$eid1 = IPS_CreateEvent(1);
	IPS_SetParent($eid1, $_IPS['SELF']);
	IPS_SetName($eid1, "Timer1");
	IPS_SetEventCyclic($eid1, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , 10 /* Alle 10 Minuten */);
	}

$eid2 = @IPS_GetEventIDByName("Timer2", $_IPS['SELF']);
if ($eid2==false)
	{
	$eid2 = IPS_CreateEvent(1);
	IPS_SetParent($eid2, $_IPS['SELF']);
	IPS_SetName($eid2, "Timer2");
	IPS_SetEventCyclicTimeFrom($eid2,22,0,0);  /* immer um 22:00 */
	}
	
$eid3 = @IPS_GetEventIDByName("Timer3", $_IPS['SELF']);
if ($eid3==false)
	{
	$eid3 = IPS_CreateEvent(1);
	IPS_SetParent($eid3, $_IPS['SELF']);
	IPS_SetName($eid3, "Timer3");
	}

$eid4 = @IPS_GetEventIDByName("Timer4", $_IPS['SELF']);
if ($eid4==false)
	{
	$eid4 = IPS_CreateEvent(1);
	IPS_SetParent($eid4, $_IPS['SELF']);
	IPS_SetName($eid4, "Timer4");
	}

//$alleEreignisse = IPS_GetEventListByType(1);
//print_r($alleEreignisse);

$giesstimerID=$eid1;
$allofftimerID=$eid2;
$timerDawnID=$eid3;
$calcgiesstimeID=$eid4;

//echo "Timer OID: ".$giesstimerID." ".$timerDawnID." ".$calcgiesstimeID."\n";

IPS_SetEventActive($calcgiesstimeID,true);
IPS_SetEventActive($timerDawnID,true);
IPS_SetEventActive($allofftimerID,true);

$name="GiessAnlage";
$vid = @IPS_GetVariableIDByName($name,$parentid);
if($vid === false)
    {
        $vid = IPS_CreateVariable(1);  /* 0 Boolean 1 Integer 2 Float 3 String */
        IPS_SetParent($vid, $parentid);
        IPS_SetName($vid, $name);
		  IPS_SetVariableCustomAction($vid,$GartensteuerungScriptID);
        IPS_SetInfo($vid, "this variable was created by script #".$parentid.".");
        echo "Variable erstellt;\n";
    }
$pname="GiessAnlagenProfil";
if (IPS_VariableProfileExists($pname) == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   IPS_SetVariableProfileAssociation($pname, 1, "EinmalEin", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
  	   //IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil erstellt;\n";
	}
	
$GiessAnlageID=$vid;
$GiessCountID=CreateVariableByName($parentid, "GiessCount", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$GiessAnlagePrevID = CreateVariableByName($parentid, "GiessAnlagePrev", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$GiessTimeID=CreateVariableByName($parentid, "GiessTime", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$giessTime=GetValue($GiessTimeID);

$GartensteuerungConfiguration=getGartensteuerungConfiguration();

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	
/******************************************************

				EXECUTE

*************************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
	echo "Nachrichten Script      ID : ".$NachrichtenScriptID."\n";
	echo "Nachrichten Log Input   ID : ".$NachrichtenInputID."\n";
	echo "Gartensteuerung Script  ID : ".$GartensteuerungScriptID."\n";
	echo "Giessanlage             ID : ".$GiessAnlageID."\n";
	echo "\nStatus Giessanlage         ".GetValue($GiessAnlageID)." (0-Aus,1-Einmalein,2-Auto) \n";
	echo   "Status Giessanlage zuletzt ".GetValue($GiessAnlagePrevID)." (0-Aus,1-Einmalein,2-Auto) \n\n";
	echo "Gartensteuerungs Konfiguration:\n";
	print_r($GartensteuerungConfiguration);
	if ($GartensteuerungConfiguration["DEBUG"]==true)
	   {
	   echo "Debugmeldungen eingeschaltet.\n";
	   }
	echo "Jetzt umstellen auf berechnete Werte. Es reicht ein Regen und ein Aussentemperaturwert.\n";

	$variableTempID=get_aussentempID();
	$variableID=get_raincounterID();
	$endtime=time();
	$starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
	$starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage */

	$Server=RemoteAccess_Address();
	echo "Server : ".$Server."\n\n";
	If ($Server=="")
	   {
		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
   	$tempwerte = AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
		$variableTempName = IPS_GetName($variableTempID);
		$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime,0);
	   $werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime, $endtime,0);
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
		}

	/* function summestartende($starttime, $endtime, $increment_var, $estimate, $archiveHandlerID, $variableID, $display=false ) */
	//$ergebnis24h=summestartende($starttime, $endtime, 1, false,$archiveHandlerID,$variableTempID);

	//$werte = AC_GetLoggedValues($archiveHandlerID, $variableTempID, $starttime, $endtime,0);
	/* Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten
		Nicht mer als 10.000 Werte ...
	*/
	//print_r($werte);
  	//$anzahl=count($werte);
 	//echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte \n";
  	/* array AC_GetAggregatedValues ( integer $InstanzID, integer $VariablenID, integer $Aggregationsstufe, integer $Startzeit, integer $Endzeit, integer $Limit )
  	0 Stündliche Aggregation, 1 Tägliche Aggregation, 2 Wöchentliche Aggregation, 3 Monatliche Aggregation
	4 Jährliche Aggregation, 5 5-Minütige Aggregation (Aus Rohdaten berechnet), 6 1-Minütige Aggregation (Aus Rohdaten berechnet)
	*/

  	$anzahl=count($tempwerte);
 	echo "   Agg. Variable: ".$variableTempName." mit ".$anzahl." Werte \n";
 	echo "Durchschnittstemp heute   : ".number_format($tempwerte[0]["Avg"], 1, ",", "")." Grad\n";
 	echo "Durchschnittstemp gestern : ".number_format($tempwerte[1]["Avg"], 1, ",", "")." Grad\n";
 	echo "Maxtemperatur heute       : ".number_format($tempwerte[0]["Max"], 1, ",", "")." Grad um ".date("d.m H:i",($tempwerte[0]["MaxTime"]))."\n";
 	echo "Maxtemperatur gestern     : ".number_format($tempwerte[1]["Max"], 1, ",", "")." Grad um ".date("d.m H:i",($tempwerte[1]["MaxTime"]))."\n";
 	echo "Mintemperatur heute       : ".number_format($tempwerte[0]["Min"], 1, ",", "")." Grad um ".date("d.m H:i",($tempwerte[0]["MinTime"]))."\n";
 	echo "Mintemperatur gestern     : ".number_format($tempwerte[1]["Min"], 1, ",", "")." Grad um ".date("d.m H:i",($tempwerte[1]["MinTime"]))."\n";
 	echo "Dauer heute : ".number_format(($tempwerte[0]["Duration"]/60/60), 1, ",", "")."Stunden \n";
 	echo "LastTime    : ".date("d.m H:i",($tempwerte[0]["LastTime"]))." \n";
 	echo "TimeStamp   : ".date("d.m H:i",($tempwerte[1]["TimeStamp"]))." \n";
 	

	$anzahl=count($werte);
 	echo "   Variable: ".$variableName." mit ".$anzahl." agreggierten Werten.\n";
	echo "   Variable: WerteLog mit ".count($werteLog)." geloggten Werten.\n";
	
	/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */
	$letzterRegen=0;
	$regenStand2h=0;
	$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
	$regenStandEnde=0;
	foreach ($werteLog as $wert)
	   {
	   echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
	   $regenStandAnfang=$wert["Value"];
	   If (($letzterRegen==0) && ($wert["Value"]>0))
	      {
	      /* Wenn nur ein Wert in der Datenbank ohne Veränderung dann problem */
	      $letzterRegen=$wert["TimeStamp"];
	      $regenStandEnde=$wert["Value"];
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
	foreach ($werte as $wert)
	   {
 	   //echo "Wert : ".number_format($wert["Avg"], 1, ",", "")."   ".date("d.m H:i",$wert["MaxTime"])."   ".date("d.m H:i",$wert["MinTime"])."   ".date("d.m H:i",$wert["TimeStamp"])."   ".date("d.m H:i",$wert["LastTime"])."\n";
	   echo "Wert : ".number_format($wert["Avg"], 1, ",", "")."   ".date("d.m H:i",$wert["MaxTime"])."   ".date("d.m H:i",$wert["MinTime"])."\n";
	   }
	//print_r($werte);

	//echo $parentid."\n";
	/* Berechnung für Giessdauer , Routinen in Config Datei mit Funktion befuellen */
	/*
	$AussenTemperaturGesternMax=$tempwerte[1]["Max"];
	echo "Aussentemperatur max : ".get_AussenTemperaturGesternMax()."   ".$tempwerte[1]["Max"]." \n";
	$AussenTemperaturGestern=$tempwerte[1]["Avg"];
	echo "Aussentemperatur med : ".AussenTemperaturGestern()."   ".$tempwerte[1]["Avg"]." \n";
	*/
	$RegenGestern=$werte[1]["Avg"];
	/*
	echo "Regen gestern : ".RegenGestern()."   ".$werte[1]["Avg"]." \n";
	echo "Letzter Regen Zeit : ".date("d.m H:i",LetzterRegen())."   ".date("d.m H:i",$letzterRegen)." \n\n";
	$LetzterRegen=time()-$letzterRegen;
	//echo "Aussentemperatur Gestern : ".$AussenTemperaturGestern." Maximum : ".$AussenTemperaturGesternMax."\n";
	//echo "Regen Gestern : ".$RegenGestern." mm und letzter Regen war vaktuell vor ".($LetzterRegen/60/60)." Stunden.\n";
	*/
	
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

			$zeitdauergiessen=(GetValue($GiessTimeID)+1)*$GartensteuerungConfiguration["KREISE"];
			$endeminuten=$startminuten+$zeitdauergiessen;
			$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60))." für die Dauer von ".
			    $zeitdauergiessen." Minuten bis ".(floor($endeminuten/60)).":".sprintf("%2d",($endeminuten%60))." .";
			$log_Giessanlage->message($textausgabe);
	echo $textausgabe."\n";

	}

/*************************************************************/

 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
	$samebutton=false;
	$variableID=$_IPS['VARIABLE'];
	$value=$_IPS['VALUE'];
	if (GetValue($variableID)==$value)
	   { /* die selbe Taste nocheinmal gedrückt */
		$samebutton=true;
	   }
	else
	   {  /* andere Taste als vorher */
		SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
		SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
		}
	switch ($_IPS['VALUE'])
		{
		case "2":  /* Auto */
		case "-1":  /* Auto */
      	IPS_SetEventActive($giesstimerID,false);
      	IPS_SetEventActive($timerDawnID,true);
 			$log_Giessanlage->message("Gartengiessanlage auf Auto gesetzt");
 			$failure=set_gartenpumpe(false);
			//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
			/* Vorgeschichte egal, nur bei einmal ein wichtig */
			SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
			break;

		case "1":  /* Einmal Ein */
			/* damit auch wenn noch kein Wetter zum Giessen, gegossenw erden kann, Giesszeit manuell setzen */
			SetValue($GiessTimeID,10);
			if ($samebutton==true)
			   { /* gleiche Taste heisst weiter */
				IPS_SetEventCyclicTimeBounds($giesstimerID,time(),0);  /* damit der Timer richtig anfängt und nicht zur vollen Stunde */
				IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $pauseTime);
      		IPS_SetEventActive($giesstimerID,true);
      		IPS_SetEventActive($timerDawnID,false);
	      	SetValue($GiessCountID,GetValue($GiessCountID)+1);
 				$log_Giessanlage->message("Gartengiessanlage Weiter geschaltet");
 				$failure=set_gartenpumpe(false);
				//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
			   }
			else
			   {
				IPS_SetEventCyclicTimeBounds($giesstimerID,time(),0);  /* damit der Timer richtig anfängt und nicht zur vollen Stunde */
				IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $pauseTime);
      		IPS_SetEventActive($giesstimerID,true);
      		IPS_SetEventActive($timerDawnID,false);
	      	SetValue($GiessCountID,1);
 				$log_Giessanlage->message("Gartengiessanlage auf EinmalEin gesetzt");
 				$failure=set_gartenpumpe(false);
				//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
				}
			break;

		case "0":  /* Aus */
      	IPS_SetEventActive($giesstimerID,false);
      	IPS_SetEventActive($timerDawnID,false);
      	SetValue($GiessCountID,0);
 			$log_Giessanlage->message("Gartengiessanlage auf Aus gesetzt");
 			$failure=set_gartenpumpe(false);
			//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
			/* Vorgeschichte egal, nur bei einmal ein wichtig */
			SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
			break;
		}
	}

/*************************************************************/


if($_IPS['SENDER'] == "TimerEvent")
	{

	$TEventName = $_IPS['EVENT'];
   Switch ($TEventName)
		{
		case $giesstimerID: /* Alle 10 Minuten für Monitor Ein/Aus */
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
				      /*  ungerade Zahl des Giesscounters bedeutet weiterschalten Pause */
						if (($giessTime>0) and (GetValue($GiessAnlageID)>0))
						   {
						   $failure=set_gartenpumpe(true);
							//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",true);
							IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $giessTime);
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
				      /*  gerade Zahl des Giesscounters bedeutet weiterschalten Giessen */
   	   		 	$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
						IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $pauseTime);
            	   $GiessCount+=1;
				   	}
		      	}  /* if nicht ende */
		      } /* if nicht 0 */
		     	SetValue($GiessCountID,$GiessCount);
			break;

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
		}
	}

/****************************************************************************************************/

function giessdauer($debug=false)
	{

	global $archiveHandlerID, $variableID, $display;  /* für agregate Regen */
	global $GiessTimeID,$log_Giessanlage;
	global $GartensteuerungConfiguration; /* für minimale mittlere Temperatur */

	$giessdauer=0;
	$display=$debug;
	
	$variableTempID=get_aussentempID();
	$variableID=get_raincounterID();
	$endtime=time();
	$starttime=$endtime-60*60*24*2;  /* die letzten zwei tage */
	$starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage */

	$Server=RemoteAccess_Address();
	echo "Server : ".$Server."\n\n";
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
		}

	//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
	$AussenTemperaturGesternMax=$tempwerte[1]["Max"];
	//$AussenTemperaturGestern=AussenTemperaturGestern();
	$AussenTemperaturGestern=$tempwerte[1]["Avg"];
	
	$letzterRegen=0;
	$regenStand2h=0;
	$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
	$regenStandEnde=0;

	/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */

	foreach ($werteLog as $wert)
	   {
	   echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
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
	   }
	echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h."\n";
	foreach ($werteLog as $wert)
	   {
	   If (($letzterRegen==0) && ($wert["Value"]>0))
	      {
	      $letzterRegen=$wert["TimeStamp"];
			}
	   }

	//$RegenGestern=RegenGestern();
	$RegenGestern=$werte[1]["Avg"];
	//$LetzterRegen=time()-LetzterRegen();
	$LetzterRegen=time()-$letzterRegen;

	if ($debug)
		{
		echo "Letzter Regen : ".date("d.m H:i",$LetzterRegen)."   ".$letzterRegen."\n";
 		echo "Aussentemperatur Gestern : ".number_format($AussenTemperaturGestern, 1, ",", "")." Grad (muss > 20° sein) ".
			  "Maximum : ".number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad \n";
		if (($LetzterRegen/60/60)<50)
		   {
			echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
				" mm und letzter Regen war aktuell vor ".number_format(($LetzterRegen/60/60), 1, ",", "")." Stunden.\n";
			}
		else
		   {
			echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
				" mm und letzter Regen war aktuell vor länger als 48 Stunden.\n";
		   }
		echo "Regen letzte 2/48 Stunden : ".$regenStand2h." mm / ".$regenStand48h." mm \n\n";
		}

	if (($regenStand48h<($GartensteuerungConfiguration["REGEN48H"])) && ($AussenTemperaturGestern>($GartensteuerungConfiguration["TEMPERATUR"])))
	   { /* es hat in den letzten 48h weniger als 10mm geregnet und die mittlere Aussentemperatur war groesser 20 Grad*/
	   if (($regenStand2h)==0)
	      { /* und es regnet aktuell nicht */
			if ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPLANGE"]))
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
			." Min. Regen 2/48Std:".$regenStand2h."mm/".$regenStand48h."mm. Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
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
