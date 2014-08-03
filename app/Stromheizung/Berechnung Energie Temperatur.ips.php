<?

 //Fügen Sie hier Ihren Skriptquellcode ein

/* alle Ereignisse protokollieren und am Ende des Tages ein email schreiben
   als Zusammenfassung */


Include("AllgemeineDefinitionen.inc.php");

/**********************************
 File Initialisierung
 **********************************/

$file_TempInnen="C:\Scripts\Log_Temp_Innen.csv";

if (!file_exists("C:\Scripts\Log_Pos_daily.csv"))
		{
      $handle3=fopen("C:\Scripts\Log_Pos_daily.csv", "a");
	   fwrite($handle3, date("d.m.y H:i:s").";Aussentemperatur;Innentemperatur;Energiewert;WZ;AZ;KZ;BZ;Status\r\n");
      fclose($handle3);
		}

if (!file_exists("C:\Scripts\Log_Temp.csv"))
		{
      $handle6=fopen("C:\Scripts\Log_Temp.csv", "a");
	   fwrite($handle6, date("d.m.y H:i:s").";Temp;TempAbs;Zeit;Energie\r\n");
	   fclose($handle6);
	   }

/**********************************
 Variablen Protokollierung
 **********************************/
 
if ($_IPS['SENDER']=="Variable")
{
	switch ($_IPS['VARIABLE'])
	{

	/* Positionswerte geändert */

   case "32688": /* Arbeitszimmer Pos Aenderung Heizung*/
      writeLogPos("C:\Scripts\Log_Pos","AZ");
		break;
	case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
      writeLogPos("C:\Scripts\Log_Pos","KZ");
		break;
	case "39253": /* Kellerzimmer Zusatz Pos Aenderung Heizung*/
      writeLogPos("C:\Scripts\Log_Pos","KZZ");
		break;
	case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
		writeLogPos("C:\Scripts\Log_Pos","WZ");
		break;
	case "33800": /* Wohnzimmer Zusatz Pos Aenderung Heizung*/
		writeLogPos("C:\Scripts\Log_Pos","WZZ");
		break;
		
	/* Temperaturwerte geändert */

   case "38610": /* Arbeitszimmer Temperaturaenderung*/
      SetValue(28516,2);
      writeLogTemp("C:\Scripts\Log_Temp","AZ-T");
		break;

   case "42413": /* Aussen Temperaturaenderung*/
      SetValue(28516,9);
      writeLogTemp("C:\Scripts\Log_Temp","AUSSEN-T");
		break;
		
   case "32563": /* Aussen2 Temperaturaenderung*/
      SetValue(28516,10);
      writeLogTemp("C:\Scripts\Log_Temp","AUSSE2-T");
		break;

   case "31094": /* Aussen3 Wetterstation Temperaturaenderung*/
      SetValue(28516,11);
      writeLogTemp("C:\Scripts\Log_Temp","WETTER-T");
		break;
		
   case "48182": /* Keller Temperaturaenderung*/
      SetValue(28516,4);
      writeLogTemp("C:\Scripts\Log_Temp","KELLER-T");
		break;

   case "13063": /* Kellerzimmer Temperaturaenderung*/
      SetValue(28516,3);
      writeLogTemp("C:\Scripts\Log_Temp","KZ-T");
		break;

   case "58776": /* Kellerlager Temperaturaenderung*/
      SetValue(28516,5);
      writeLogTemp("C:\Scripts\Log_Temp","KELLAG-T");
		break;

   case "41873": /* Wohnzimmer Temperaturaenderung*/
      SetValue(28516,1);
      writeLogTemp("C:\Scripts\Log_Temp","WZ-T");
		break;

   case "29970": /* Wintergarten Temperaturaenderung*/
      SetValue(28516,20);
      writeLogTemp("C:\Scripts\Log_Temp","WINGAR-T");
		break;
		
	/* Regensensor */
	
   case "15620": /* Regensensor*/
      SetValue(28516,8);
      writeLogRain("C:\Scripts\Log_Rain","WETTER-R");
		break;
		
   /* Andere Werte  */

	default:
		//writeLogPos("C:\Scripts\Log_All.csv",$_IPS['VARIABLE'],1);
		break;
	}
}

/**********************************
 Mitternachtsberechnungen und email
 **********************************/


if ($_IPS['SENDER']=="TimerEvent")
   {

	/* sicherstellen dass heizung immer funktioniert, in Auto Mode setzen */
	SetValue(19155,2);

	/*************************************************************************************
	                  1/7/30/360 Auswertungen
	 *************************************************************************************/

   IPS_RunScript(48267);
   IPS_RunScript(13352);
   IPS_RunScript(32860);
   IPS_RunScript(45023);
   IPS_RunScript(41653);

	/*************************************************************************************
	                  ENERGIEWERTE
	 *************************************************************************************/

   $handle1=fopen("C:\Scripts\Log_Pos.csv", "a");

	/* Berechnung plus Abspeicherung im Logfile, Tageswert mit !! gekennzeichnet */

	/* Programm komplett generisch aufgebaut, muss nicht mehr umgeschrieben werden */

	fwrite($handle1, date("d.m.y H:i:s").";!!;");
   $arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */

	$energieGesID = CreateVariableByName($_IPS['SELF'], "Summe_Energie", 2);
	$energieGesTagID = CreateVariableByName($_IPS['SELF'],"Summe_EnergieTag", 2);
   foreach ($arr as $identifier=>$station)
		{
		/* alle Heizungen um Mitternacht auslesen und schreiben */
		if ($identifier=="TOTAL") break;

		$leistungsfaktor=$station["Leistung"];
		$posFTH80bID=$station["OID_PosHT80b"];
		$leistungID = CreateVariableByName($_IPS['SELF'], $identifier."_Leistung", 1);
		$letzteAenderungID = CreateVariableByName($_IPS['SELF'], $identifier."_Letzte Aenderung", 1);
		$energieID = CreateVariableByName($_IPS['SELF'], $identifier."_Energie", 2);
		$energieTagID = CreateVariableByName($_IPS['SELF'], $identifier."_EnergieTag", 2);
		//$energieGesID = CreateVariableByName($_IPS['SELF'], "Summe_Energie", 2);
		//$energieGesTagID = CreateVariableByName($_IPS['SELF'],"Summe_EnergieTag", 2);

		$leistung=GetValue($posFTH80bID)*$leistungsfaktor;
		SetValue($leistungID,$leistung);

	   $deltatime = time()-GetValue($letzteAenderungID);
   	//SetValue($letzteAenderungID,time());          /* Aenderungszeitpunkt */
	   $energiekWh=$deltatime*GetValue($posFTH80bID)*$leistungsfaktor/3600/1000;   /* Energie in kWh. Variablenwert auf agregation Zaehler setzen */
	   SetValue($energieID,$energiekWh+GetValue($energieID));
   	SetValue($energieTagID,$energiekWh+GetValue($energieTagID));    /* Energie tagesweise aufsummieren, muss um Mitternacht berechnet und resetiert werden */
		/* und für die Summe auch noch einmal */
   	SetValue($energieGesID,$energiekWh+GetValue($energieGesID));
	   SetValue($energieGesTagID,$energiekWh+GetValue($energieGesTagID));    /* Energie tagesweise aufsummieren, muss um Mitternacht berechnet und resetiert werden */

	   fwrite($handle1, number_format(GetValue($energieTagID), 2, ",", "" ).";");
		}
	unset($identifier); // break the reference with the last element

	$ergebnis_tagesenergie="Neue Energiewerte : \n\n";
   foreach ($arr as $identifier=>$station)
		{
		if ($identifier=="TOTAL")
			{
   		$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieGesTagID), 2, ",", "" )."kWh \n";
			fwrite($handle1,number_format(GetValue($energieGesTagID), 2, ",", "" )."\r\n");
			$EnergieTagFinalID=$station["OID_Tageswert"];
   		SetValue($EnergieTagFinalID,GetValue($energieGesTagID));
			SetValue($energieGesTagID,0);    						/* Energie gesamt tagesweise aufsummieren, jetzt resetieren */
			break;
			}
		$posFTH80bID=$station["OID_PosHT80b"];
		$EnergieTagFinalID=$station["OID_Tageswert"];
		$energieTagID = CreateVariableByName($_IPS['SELF'], $identifier."_EnergieTag", 2);

		$deltatime = time()-GetValue($letzteAenderungID);
   	SetValue($letzteAenderungID,time());          		/* Aenderungszeitpunkt jetzt final speichern */
   	$ergebnis_tagesenergie=$ergebnis_tagesenergie.$identifier.":".number_format(GetValue($energieTagID), 2, ",", "" )."kWh ";   /* Schoenes Ergebnis fuer email bauen */
   	SetValue($EnergieTagFinalID,GetValue($energieTagID));
   	SetValue($energieTagID,0);    							/* Energie tagesweise aufsummieren, jetzt resetieren */

      fwrite($handle1,number_format(GetValue($posFTH80bID), 2, ",", "" ).";0;".date("U",$deltatime).";");
		}
	unset($identifier); // break the reference with the last element

  	fclose($handle1);
	$ergebnis_tagesenergie.=   "1/7/30/360 : ".number_format(GetValue(35510), 0, ",", "" )."/"
							  				    .number_format(GetValue(25496), 0, ",", "" )."/"
											    .number_format(GetValue(54896), 0, ",", "" )."/"
											    .number_format(GetValue(30229), 0, ",", "" )." kWh\n";


	/***************************************************************************************************
										TEMPERATURWERTE
	****************************************************************************************************/

   /* um 00:00 Uhr muessen alle Temperaturen gemessen werden und obige Werte ebenfalls berechnet werden */

   $arr=LogAlles_Temperatur();    /* Konfigurationsfile mit allen Variablen  */

	$TempInnenGesWert=0;
	$TempInnenTagGesWert=0;
	$TempInnenZaehler=0;
	$TempAussenGesWert=0;
	$TempAussenTagGesWert=0;
	$TempAussenZaehler=0;

//	$ergebnisTemperatur="\n\nNeue Temperaturwerte :\n\n";
	$ergebnisTemperatur=""; // neu beginnen
	foreach ($arr as $identifier=>$station)
		{
		if ($identifier=="TOTAL")
			{
			$TempWertAussenID = $station["OID_TempWert_Aussen"];
			$TempWertInnenID = $station["OID_TempWert_Innen"];
			$TempWertTagAussenID = $station["OID_TempTagesWert_Aussen"];
			$TempWertTagInnenID = $station["OID_TempTagesWert_Innen"];
			$TempInnenGesWert=$TempInnenGesWert/$TempInnenZaehler;
			$TempAussenGesWert=$TempAussenGesWert/$TempAussenZaehler;
			$TempInnenTagGesWert=$TempInnenTagGesWert/$TempInnenZaehler;
			$TempAussenTagGesWert=$TempAussenTagGesWert/$TempAussenZaehler;
			SetValue($TempWertInnenID,$TempInnenGesWert);
			SetValue($TempWertAussenID,$TempAussenGesWert);
			SetValue($TempWertTagInnenID,$TempInnenTagGesWert);
			SetValue($TempWertTagAussenID,$TempAussenTagGesWert);
			break;
			}

		$TempWertID = $station["OID_Sensor"];
   	$MaxTagID = $station["OID_Max"];
   	$MinTagID = $station["OID_Min"];
		$Type = $station["Type"];
		$letzteAenderungID = CreateVariableByName($_IPS['SELF'], $identifier."_Letzte Aenderung", 1);
		$IntegralID = CreateVariableByName($_IPS['SELF'], $identifier."_Integral", 2);
		$TempTagID = CreateVariableByName($_IPS['SELF'],$identifier."_TempTag", 2);
		$MinTagWork_ID = CreateVariableByName($_IPS['SELF'],$identifier."_MinTag", 2);
		$MaxTagWork_ID = CreateVariableByName($_IPS['SELF'],$identifier."_MaxTag", 2);

	   $temperaturFloat=GetValue($TempWertID);      /* Wert vom Sensor nehmen, sollte ja unveraendert sein */
   	$deltatime = time()-GetValue($letzteAenderungID);
   	SetValue($letzteAenderungID,time());          /* Aenderungszeitpunkt */
   	$temperaturIntegral=GetValue($IntegralID)+$deltatime*($temperaturFloat+273);
   	$TempTag=$temperaturIntegral/60/60/24-273;
	   SetValue($TempTagID,$TempTag);
	   SetValue($MaxTagID,GetValue($MaxTagWork_ID));
	   SetValue($MinTagID,GetValue($MinTagWork_ID));
		//SetValue($TempWertID,$temperaturFloat);
		
		/* gemittelten Temperatur-Tageswert ins email schreiben */
		$ergebnisTemperatur = $ergebnisTemperatur.$identifier." : ".number_format($TempTag, 2, ",", "" )."°C ";
		
		if ($Type=="Innen")
		   {
		   $TempInnenGesWert=$TempInnenGesWert+$temperaturFloat;
         $TempInnenTagGesWert=$TempInnenTagGesWert+$TempTag;
		   $TempInnenZaehler=$TempInnenZaehler+1;
		   }
		if ($Type=="Aussen")
		   {
		   $TempAussenGesWert=$TempAussenGesWert+$temperaturFloat;
         $TempAussenTagGesWert=$TempAussenTagGesWert+$TempTag;
		   $TempAussenZaehler=$TempAussenZaehler+1;
		   }

		/* 00:00 Temperaturwerte der Reihe nach in die entsprechenden Files schreiben */
		$handle1 = fopen("C:\Scripts\Log_Temp_".$identifier.".csv","a");
		fwrite($handle1, date("d.m.y H:i:s").";".number_format($temperaturFloat, 2, ",", "" ).";0;".
			number_format($temperaturIntegral, 2, ",", "" ).";".number_format($TempTag, 2, ",", "" ).";".
			number_format(GetValue($MinTagWork_ID), 2, ",", "" ).";".number_format(GetValue($MaxTagWork_ID), 2, ",", "" ).
			";*******\r\n");
		fclose($handle1);

		SetValue($IntegralID,0);
		SetValue($MinTagWork_ID,50);
		SetValue($MaxTagWork_ID,-50);
		}
	unset($identifier); // break the reference with the last element

	$ergebnisTemperatur = "\nNeue Temperaturwerte :\n\n".$ergebnisTemperatur;

	/***************************************************************************************************
										REGENWERTE
	****************************************************************************************************/
	
	/* Regensensor muss nicht ausgelesen werden, es werden die zuletzt erfassten Werte genommen
	   Tageswert auf letzten Tag schreiben und Tageswert loeschen
	*/
	
	$regenheute=GetValue(15200);
	SetValue(21609,$regenheute);
	SetValue(15200,0);
	$ergebnisRegen="\n\nRegenmenge : ".number_format($regenheute, 2, ",", "" )." mm\n";
	$ergebnisRegen.=   "1/7/30/360 : ".number_format(GetValue(37587), 2, ",", "" )."/"
							  				    .number_format(GetValue(10370), 2, ",", "" )."/"
											    .number_format(GetValue(13883), 2, ",", "" )."/"
											    .number_format(GetValue(10990), 2, ",", "" )." mm\n";

	/***************************************************************************************************
										STROMVERBRAUCH
	****************************************************************************************************/

	$stromGesID = CreateVariableByName($_IPS['SELF'], "Summe_Stromverbrauch", 2);
	$stromGesTagID = CreateVariableByName($_IPS['SELF'],"Summe_StromverbrauchTag", 2);
	SetValue($stromGesTagID,GetValue(52333)-GetValue($stromGesID));
	SetValue($stromGesID,GetValue(52333));
	
	$ergebnisStrom="\n\nTages-Stromverbrauch : ".GetValue($stromGesTagID)." kWh\n";
	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(52252), 0, ",", "" )."/"
							  				    .number_format(GetValue(35513), 0, ",", "" )."/"
											    .number_format(GetValue(35289), 0, ",", "" )."/"
											    .number_format(GetValue(51307), 0, ",", "" )." kWh\n";
	$ergebnisStrom.=   "1/7/30/360 : ".number_format(GetValue(29903), 0, ",", "" )."/"
							  				    .number_format(GetValue(44005), 0, ",", "" )."/"
											    .number_format(GetValue(20129), 0, ",", "" )."/"
											    .number_format(GetValue(47761), 0, ",", "" )." Euro\n";

	/***************************************************************************************************
										ZUSAMMENFASSUNGEN
	****************************************************************************************************/

   $handle3=fopen("C:\Scripts\Log_Pos_daily.csv", "a");
   //fwrite($handle3, date("d.m.y H:i:s").";Aussentemperatur;Innentemperatur;Energiewert;WZ;AZ;KZ;BZ;Status\r\n");
   fwrite($handle3, date("d.m.y H:i:s").";".number_format($TempAussenTagGesWert, 2, ",", "" ).";".number_format($TempInnenTagGesWert, 2, ",", "" ).";");
   $arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */
   foreach ($arr as $identifier=>$station)
		{
		$EnergieTagFinalID=$station["OID_Tageswert"];
	   fwrite($handle3, number_format(GetValue($EnergieTagFinalID), 2, ",", "" ).";");
		}
	fclose($handle3);


	/***************************************************************************************************
										INTERNET AUSWERTUNGEN
	****************************************************************************************************/

	/* Zusaetzlich am Ende des emails den Internet Status mitgeben */
	/* Downtime zuruecksetzen und ins email damit */

	$ergebnisStatus="\nAenderungsverlauf Internet Connectivity :\n\n";
	$ergebnisStatus=$ergebnisStatus."Downtime Internet :".GetValue(49809)." min\n\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(51715)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(55372)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(52397)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(51343)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(29913)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(27604)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(30167)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(41813)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(11169)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(18739)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(39489)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(12808)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(13641)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(36734)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(46381)."\n";
	$ergebnisStatus=$ergebnisStatus.GetValue(24490)."\n";

	$ergebnisStatus.="\nDatenvolumen Down/Up : ".GetValue(32332)."/".GetValue(37701)." Mbyte\n";
	$ergebnisStatus.=  " Down 7/30/30/30/360 : ".GetValue(32642)."/"
															  .GetValue(49944)."/"
															  .GetValue(49121)."/"
															  .GetValue(17604)."/"
															  .GetValue(12069)." Mbyte\n";
	$ergebnisStatus.=  "   Up 7/30/30/30/360 : ".GetValue(39846)."/"
															  .GetValue(46063)."/"
															  .GetValue(45333)."/"
															  .GetValue(50549)."/"
															  .GetValue(21647)." MByte\n";
															  
	/* Zusaetzlich ganz am Ende des emails den Status aller Bewegungen mitgeben */

	$ergebnisBewegung="\n\nVerlauf der Bewegungen in LBG:\n\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(38964)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(23869)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(16966)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(14097)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(14944)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(42042)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(39559)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(36666)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(30427)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(55972)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(57278)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(45148)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(21096)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(46545)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(25902)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(13726)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(22969)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(56534)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(59126)."\n";
	$ergebnisBewegung=$ergebnisBewegung.GetValue(45878)."\n";

	$ergebnis_tagesenergie="Status OK\n\n".$ergebnis_tagesenergie.$ergebnisStrom.$ergebnisTemperatur.$ergebnisRegen.
					$ergebnisStatus.$ergebnisBewegung."\n\n";

	$BrowserExtAdr="http://".trim(GetValue(45252)).":82/";
	$BrowserIntAdr="http://".trim(GetValue(33109)).":82/";
	$IPStatus="\n\nIP Symcon Aufruf extern unter:".$BrowserExtAdr.
	          "\nIP Symcon Aufruf intern unter:".$BrowserIntAdr."\n";
	          
	$ergebnis="\n*true************************************************************************************\n".send_status(true);
	$ergebnis.="\n*false************************************************************************************\n".send_status(false);

	/* wenn kein Internet zur Verfügung kommt an dieser Stelle ein Warning */
   SMTP_SendMail($sendResponse, date("Y.m.d D")." Status BKS01", $ergebnis_tagesenergie.$IPStatus.$ergebnis);

/* mit Attachement dann so:
	SMTP_SendMailAttachment($instanzid, $betreff, $message, $file);
	
	Andernfalls wird es richtig kompliziert, muss vollständige MIME Dekodierung machen
*/

	/*****************************************************************************************************************************
	                  REGISTER RÜCKSETZEN
	************************************************************************************************************************************/

	/* Internet Auswertungen rücksetzen */
	
	SetValue(49809,0);
	SetValue(59685,0);
	SetValue(44960,0);

	/* den Pause Router Keep Alive check wieder auf false setzen, damit die Auswertungen funktionieren */

	SetValue(48297,false);

	/*****************************************************************************************************************************
	                  TAGESAUSWERTUNGEN
	************************************************************************************************************************************/
	
	
	
	
	}



 if($IPS_SENDER == "Execute")
 {


	/* Summenenergieregister rausfinden */
	

   echo send_status(false);
   echo send_status(true);
 }



/************************************************************************************/
/**********************************************************************************************************************************************************/
/************************************************************************************/

function writeLogPos($filename,$identifier)
{

   $arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Variablen  */

	switch ($identifier)
		{
		case "AZ": $delimiter="";       		$delimiter3=";;;;;;;;;";     $delimiter1="";   		$delimiter4=";;;";
			break;
		case "KZ": $delimiter=";;;";     	$delimiter3=";;;;;;";        $delimiter1=";";  		$delimiter4=";;";
			break;
		case "WZ": $delimiter=";;;;;;";  	$delimiter3=";;;";        	  $delimiter1=";;"; 		$delimiter4=";";
			break;
		default: $delimiter=";;;;;;;;;"; 	$delimiter3="";				  $delimiter1=";;;";  	$delimiter4="";
			break;
		}

	/* neue Berechnung plus Abspeicherung im Logfile */

	$leistungsfaktor=$arr[$identifier]["Leistung"];
	$TempID=$arr[$identifier]["OID_Temp"];
	$leistungID = CreateVariableByName($_IPS['SELF'], $identifier."_Leistung", 1);
	$letzteAenderungID = CreateVariableByName($_IPS['SELF'], $identifier."_Letzte Aenderung", 1);
	$energieID = CreateVariableByName($_IPS['SELF'], $identifier."_Energie", 2);
	$energieTagID = CreateVariableByName($_IPS['SELF'], $identifier."_EnergieTag", 2);
	$energieGesID = CreateVariableByName($_IPS['SELF'], "Summe_Energie", 2);
	$energieGesTagID = CreateVariableByName($_IPS['SELF'],"Summe_EnergieTag", 2);

	$leistung=$_IPS['VALUE']*$leistungsfaktor;
	SetValue($leistungID,$leistung);

   $deltatime = time()-GetValue($letzteAenderungID);
   SetValue($letzteAenderungID,time());          /* Aenderungszeitpunkt */
   $energiekWh=$deltatime*$_IPS['OLDVALUE']*$leistungsfaktor/3600/1000;   /* Energie in kWh. Variablenwert auf agregation Zaehler setzen */
   SetValue($energieID,$energiekWh+GetValue($energieID));
   SetValue($energieTagID,$energiekWh+GetValue($energieTagID));    /* Energie tagesweise aufsummieren, muss um Mitternacht berechnet und resetiert werden */
	/* und für die Summe auch noch einmal */
   SetValue($energieGesID,$energiekWh+GetValue($energieGesID));
   SetValue($energieGesTagID,$energiekWh+GetValue($energieGesTagID));    /* Energie tagesweise aufsummieren, muss um Mitternacht berechnet und resetiert werden */

   if (!file_exists($filename.".csv"))
		{
      $handle=fopen($filename.".csv", "a");
	   fwrite($handle, date("d.m.y H:i:s").";ID;AZ;BZ;SZ;WZ;Apos;Adpos;Adtime;Bpos;Bdpos;Bdtime;Spos;Sdpos;Sdtime;Wpos;Wdpos;Wdtime;TGes;T-AZ;T-BZ;T-SZ;T-WZ;SW-AZ;SW-BZ;SW-SZ;SW-WZ\r\n");
      fclose($handle);
	   }
	$handle=fopen($filename.".csv","a");

   /* delimiter und delimiter3 gleichen 3 Spalten aus, delimiter1 und delimiter4 gleichen eine Spalte aus */

	fwrite($handle, date("d.m.y H:i:s").";".$identifier.";".$delimiter1.number_format($energiekWh, 2, ",", "" )." ".
	     $delimiter.$delimiter4.";".number_format($_IPS['VALUE'], 2, ",", "" ).";".number_format($_IPS['VALUE']-$_IPS['OLDVALUE'], 2, ",", "" ).
		  ";".date("U",$deltatime).";".$delimiter3.number_format(GetValue($energieGesTagID), 2, ",", "" ).$delimiter1.
		  ";".number_format(GetValue($energieTagID), 2, ",", "" ).$delimiter4.
		  ";".";".";".";"."\r\n");

	fclose($handle);

   if (!file_exists($filename."_".$identifier.".csv"))
		{
      $handle=fopen($filename."_".$identifier.".csv", "a");
	   fwrite($handle, date("d.m.y H:i:s").";Pos;Dtime;Leistung;EnergieTag;Energie;Inkrement;Temperatur;\r\n");
      fclose($handle);
	   }
	$handle=fopen($filename."_".$identifier.".csv","a");
	fwrite($handle, date("d.m.y H:i:s").";".number_format($_IPS['VALUE'], 2, ",", "" ).";".date("U",$deltatime).
			";".number_format($leistung, 2, ",", "" ).";".number_format(GetValue($energieTagID), 2, ",", "" ).
			";".number_format(GetValue($energieID), 2, ",", "" ).";".number_format($energiekWh, 2, ",", "" ).";".number_format(GetValue($TempID), 2, ",", "" )."\r\n");
	fclose($handle);

	/* Abschliessend noch die gerade von der Stromheizung gezogene leistung ermitteln */
	
	$summeLeistung=0;
	foreach ($arr as $identifier=>$station)
		{
		if ($identifier=="TOTAL")
			{
			$summeLeistungID = CreateVariableByName($_IPS['SELF'], "Summe_Leistung", 1);
			SetValue($summeLeistungID,$summeLeistung);
			break;
			}
	   $leistungID = CreateVariableByName($_IPS['SELF'], $identifier."_Leistung", 1);
		$summeLeistung+=GetValue($leistungID);
		}
	unset($identifier); // break the reference with the last element

}


/**********************************************************************************************************************************************************/


function writeLogTemp($filename, $identifier)
{

/* call with writelogTemp("C:\scripts\Log_temp","AZ";
*/

   $arr=LogAlles_Temperatur();    /* Konfigurationsfile mit allen Variablen  */

	/* neue Berechnung plus Abspeicherung im Logfile */

	//$TempWertID=$arr[$identifier]["OID_TempWert"];     /* aktueller Wert Temperatur in ein Register gespiegelt */

	$letzteAenderungID = CreateVariableByName($_IPS['SELF'], $identifier."_Letzte Aenderung", 1);
	$IntegralID = CreateVariableByName($_IPS['SELF'], $identifier."_Integral", 2);
	$DifferentialID = CreateVariableByName($_IPS['SELF'], $identifier."_Differential", 2);
	$TempTagID = CreateVariableByName($_IPS['SELF'],$identifier."_TempTag", 2);
	$MinTagID = CreateVariableByName($_IPS['SELF'],$identifier."_MinTag", 2);
	$MaxTagID = CreateVariableByName($_IPS['SELF'],$identifier."_MaxTag", 2);

   $filename=$filename."_".$identifier.".csv";
   if (!file_exists($filename))
		{
      $handle=fopen($filename, "a");
	   fwrite($handle, date("d.m.y H:i:s").";Temperatur;Differenz;Integral;SekundenHeute;gemittelterWert\n");
	   fclose($handle);
	   }
	$handle=fopen($filename,"a");

   $temperaturFloat=$_IPS['VALUE'];
   $deltatempFloat=$_IPS['VALUE']-$_IPS['OLDVALUE'];
   $deltatime = time()-GetValue($letzteAenderungID);
   SetValue($letzteAenderungID,time());          /* Aenderungszeitpunkt */
   SetValue(17064,time());  /* fuer Homematic Watchdog */
   if ($deltatime > (24*60*60*2)) {  $deltatime = 0; }

	if ($temperaturFloat<GetValue($MinTagID)) {   SetValue($MinTagID,$temperaturFloat);   }
	if ($temperaturFloat>GetValue($MaxTagID)) {   SetValue($MaxTagID,$temperaturFloat);   }

   $temperaturIntegral=GetValue($IntegralID)+$deltatime*($_IPS['OLDVALUE']+273);
   SetValue($IntegralID,$temperaturIntegral);

   $temperaturDifferential=$deltatempFloat*3600/$deltatime;
   SetValue($DifferentialID,$temperaturDifferential);

   $secondstoday  = time()-mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
   $TempTag=$temperaturIntegral/$secondstoday-273;
   SetValue($TempTagID,$TempTag);
	//SetValue($TempWertID,$temperaturFloat);   /* Kopie vom aktuellem Wert in einem Register */

	$TempInnenGesWert=0;
	$TempInnenZaehler=0;
	$TempAussenGesWert=0;
	$TempAussenZaehler=0;
	/* keine automatisches Anlegen von Variablen nach dem Akkumulationstyp implementiert */
	foreach ($arr as $identifier=>$station)
		{
		if ($identifier=="TOTAL")
			{
			$TempWertAussenID = $station["OID_TempWert_Aussen"];
			$TempWertInnenID = $station["OID_TempWert_Innen"];
			$TempWertTagAussenID = $station["OID_TempTagesWert_Aussen"];
			$TempWertTagInnenID = $station["OID_TempTagesWert_Innen"];
			SetValue($TempWertInnenID,$TempInnenGesWert/$TempInnenZaehler);
			SetValue($TempWertAussenID,$TempAussenGesWert/$TempAussenZaehler);
			break;
			}
		$TempWertID = $station["OID_Sensor"];
		$Type = $station["Type"];
		if ($Type=="Innen")
		   {
		   $TempInnenGesWert=$TempInnenGesWert+GetValue($TempWertID);
		   $TempInnenZaehler=$TempInnenZaehler+1;
		   }
		if ($Type=="Aussen")
		   {
		   $TempAussenGesWert=$TempAussenGesWert+GetValue($TempWertID);
		   $TempAussenZaehler=$TempAussenZaehler+1;
		   }
		}
	unset($identifier); // break the reference with the last element

	fwrite($handle, date("d.m.y H:i:s").";".number_format($temperaturFloat, 2, ",", "" ).";".number_format($deltatempFloat, 2, ",", "" ).";".
			number_format($temperaturIntegral, 2, ",", "" ).";".number_format($secondstoday, 2, ",", "" ).";".number_format($TempTag, 2, ",", "" )."\r\n");

	fclose($handle);
}

/**********************************************************************************************************************************************************/


function writeLogRain($filename, $identifier)
{

/* call with writelogRain("C:\scripts\Log_Rain","AZ";
*/

	$letzteAenderungID = CreateVariableByName($_IPS['SELF'], $identifier."_Letzte Aenderung", 1);
	$SummeTagID = CreateVariableByName($_IPS['SELF'],$identifier."_SummeTag", 2);
	$SummeLetzterTagID = CreateVariableByName($_IPS['SELF'],$identifier."_SummeLetzterTag", 2);

   $filename=$filename."_".$identifier.".csv";
   if (!file_exists($filename))
		{
      $handle=fopen($filename, "a");
	   fwrite($handle, date("d.m.y H:i:s").";Regenmenge;ZeiSekunden;SummeTag\n");
	   fclose($handle);
	   }
	$handle=fopen($filename,"a");

   $RainFloat=$_IPS['VALUE']-$_IPS['OLDVALUE'];
   $deltatime = time()-GetValue($letzteAenderungID);
   SetValue($letzteAenderungID,time());          /* Aenderungszeitpunkt */
   SetValue(17064,time());  /* fuer Homematic Watchdog */
   //if ($deltatime > (24*60*60*2)) {  $deltatime = 0; }  // es kann auch laenger als zwei Tage nicht regnen

   $SummeTag=GetValue($SummeTagID)+$RainFloat;
   SetValue($SummeTagID,$SummeTag);

	fwrite($handle, date("d.m.y H:i:s").";".number_format($RainFloat, 2, ",", "" ).";".
			number_format($deltatime, 2, ",", "" ).";".number_format($SummeTag, 2, ",", "" )."\r\n");

	fclose($handle);
}


?>
