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

echo "NachrichtenInput OID: ".$NachrichtenInputID." Script OID: ".$NachrichtenScriptID."\n";

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
echo "Giessanlage OID: ".$GiessAnlageID."\n";
$GiessCountID=CreateVariableByName($parentid, "GiessCount", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$GiessAnlagePrevID = CreateVariableByName($parentid, "GiessAnlagePrev", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$GiessTimeID=CreateVariableByName($parentid, "GiessTime", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
$giessTime=GetValue($GiessTimeID);

/******************************************************

				EXECUTE

*************************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
	//echo $parentid."\n";
	/* Berechnung für Giessdauer */
	$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
	$AussenTemperaturGestern=AussenTemperaturGestern();
	$RegenGestern=RegenGestern();
	$LetzterRegen=time()-LetzterRegen();
	//echo "Aussentemperatur Gestern : ".$AussenTemperaturGestern." Maximum : ".$AussenTemperaturGesternMax."\n";
	//echo "Regen Gestern : ".$RegenGestern." mm und letzter Regen war vaktuell vor ".($LetzterRegen/60/60)." Stunden.\n";
	SetValue($GiessTimeID,giessdauer(true));
	/* SetValue($GiessTimeID,giessdauer());
	$textausgabe="Giesszeit berechnet mit ".GetValue($GiessTimeID)." Minuten da ".number_format($RegenGestern, 1, ",", "")." mm Regen vor "
						.number_format(($LetzterRegen/60/60), 1, ",", "")." Stunden. Temperatur gestern "
						.number_format($AussenTemperaturGestern, 1, ",", "")." max "
						.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
	$log_Giessanlage->message($textausgabe);
	echo $textausgabe."\n"; */
	echo "\nStaus Giessanlage         ".GetValue($GiessAnlageID)." (0-Aus,1-Einmalein,2-Auto) \n";
	echo "Staus Giessanlage zuletzt ".GetValue($GiessAnlagePrevID)." (0-Aus,1-Einmalein,2-Auto) \n";

	$resultEvent=IPS_GetEvent($calcgiesstimeID);
	If($resultEvent["EventActive"]){echo "Timer Kalkgiesstime aktiv.\n";};
	$resultEvent=IPS_GetEvent($timerDawnID);
	If($resultEvent["EventActive"]){echo "Timer zum Giessen aktiv.\n";};
	
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

			$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60)).".";
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
      	IPS_SetEventActive($giesstimerID,false);
      	IPS_SetEventActive($timerDawnID,true);
 			$log_Giessanlage->message("Gartengiessanlage auf Auto gesetzt");
 			$failure=set_gartenpumpe(false);
			//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
			/* Vorgeschichte egal, nur bei einmal ein wichtig */
			SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
			break;

		case "1":  /* Einmal Ein */
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
		   $GiessCount=GetValue($GiessCountID);
		   Switch ($GiessCount)
		      {
		      case 9:
		       	$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
               $GiessCount=0;
            	SetValue($GiessAnlageID, GetValue($GiessAnlagePrevID));
      			IPS_SetEventActive($giesstimerID,false);
					$log_Giessanlage->message("Gartengiessanlage Vorgang abgeschlossen");
					$log_Giessanlage->message("Gartengiessanlage zurück auf ".GetValue($GiessAnlagePrevID)." (0-Aus, 1-EinmalEin, 2-Auto) gesetzt");
      			break;
      		case 8:
      		case 6:
      		case 4:
      		case 2:
      		 	$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
					IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $pauseTime);
               $GiessCount+=1;
               break;
            case 7:     /* Beregner auf der Birkenseite */
            case 5:     /* Beregner beim Brunnen */
      		case 3:     /* Schlauchbewaesserung */
				case 1:     /* Beregner ehemaliges Pool */
					if (($giessTime>0) and (GetValue($GiessAnlageID)>0))
					   {
					   $failure=set_gartenpumpe(true);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",true);
						IPS_SetEventCyclic($giesstimerID, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , $giessTime);
      	         $GiessCount+=1;
						$log_Giessanlage->message("Gartengiessanlage Vorgang beginnt jetzt mit einer Giessdauer von: ".$giessTime." Minuten.");
						}
					else
						{
						$failure=set_gartenpumpe(false);
						//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
   	            $GiessCount=0;
      				IPS_SetEventActive($giesstimerID,false);
						$log_Giessanlage->message("Gartengiessanlage beginnt nicht, wegen Regen oder geringer Temperatur ");
						}
					break;
            case 0:
             	$failure=set_gartenpumpe(false);
					//$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false);
            }
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
			
			$textausgabe="Giessbeginn morgen um ".(floor($startminuten/60)).":".sprintf("%2d",($startminuten%60)).".";
			$log_Giessanlage->message($textausgabe);
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

	$giessdauer=0;
	$display=$debug;

	$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
	$AussenTemperaturGestern=AussenTemperaturGestern();
	$RegenGestern=RegenGestern();
	$LetzterRegen=time()-LetzterRegen();

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	$variableID=get_raincounterID();
	$endtime=time();
	$starttime=$endtime-60*60*2*1;
	$ergebnis2h=summestartende($starttime, $endtime, true, false);
	$starttime=$endtime-60*60*48*1;
	$ergebnis48h=summestartende($starttime, $endtime, true, false);

	if ($debug)
		{
 		echo "Aussentemperatur Gestern : ".number_format($AussenTemperaturGestern, 1, ",", "")." Grad ".
			  "Maximum : ".number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad \n";
		echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
			" mm und letzter Regen war aktuell vor ".number_format(($LetzterRegen/60/60), 1, ",", "")." Stunden.\n";
		echo "Regen letzte 2/48 Stunden : ".$ergebnis2h." mm / ".$ergebnis48h." mm \n";
		}

	if (($ergebnis48h<10) && ($AussenTemperaturGestern>20))
	   { /* es hat in den letzten 48h weniger als 10mm geregnet und die mittlere Aussentemperatur war groesser 20 Grad*/
	   if (($ergebnis2h)==0)
	      { /* und es regnet aktuell nicht */
			if ($AussenTemperaturGesternMax>27)
			   { /* es war richtig warm */
				$giessdauer=20;
				}
			else
			   { /* oder nur gleichmässig warm */
				$giessdauer=10;
			   }
	      }
	   }
	$textausgabe="Giesszeit berechnet mit ".GetValue($GiessTimeID)
			." Minuten da Regen letzte 2/48 Stunden : ".$ergebnis2h." mm / ".$ergebnis48h." mm "
			."und vor ".number_format(($LetzterRegen/60/60), 1, ",", "")." Stunden zuletzt. Temperatur gestern "
			.number_format($AussenTemperaturGestern, 1, ",", "")." max "
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
