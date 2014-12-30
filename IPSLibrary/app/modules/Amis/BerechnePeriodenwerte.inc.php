<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');

/************************************************************

				INIT

*************************************************************/

$display=false;       /* alle Eintraege auf der Console ausgeben */

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
$archiveHandlerID = $archiveHandlerID[0];

$MeterConfig = get_MeterConfiguration();

$Tag=1;
$Monat=1;
$Jahr=2011;
//$variableID=30163;

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis.Zaehlervariablen');
$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');

foreach ($MeterConfig as $meter)
	{
	echo"-------------------------------------------------------------\n";
	echo "Create Variableset for :".$meter["NAME"]." \n";
	$ID = CreateVariableByName($parentid1, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	if ($meter["TYPE"]=="Homematic")
	   {
		/* Variable ID selbst bestimmen */
	   $variableID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	   }
	else
	   {
		$variableID = $meter["WirkenergieID"];
		}
	//print_r($meter);

	$PeriodenwerteID = CreateVariableByName($ID, "Periodenwerte", 3);
   $KostenID = CreateVariableByName($ID, "Kosten kWh", 2);

	$letzterTagID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzterTag", 2);
   IPS_SetVariableCustomProfile($letzterTagID,'kWh');
	IPS_SetPosition($letzterTagID, 100);
	$letzte7TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte7Tage", 2);
   IPS_SetVariableCustomProfile($letzte7TageID,'kWh');
  	IPS_SetPosition($letzte7TageID, 110);
	$letzte30TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte30Tage", 2);
   IPS_SetVariableCustomProfile($letzte30TageID,'kWh');
  	IPS_SetPosition($letzte30TageID, 120);
	$letzte360TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte360Tage", 2);
   IPS_SetVariableCustomProfile($letzte360TageID,'kWh');
  	IPS_SetPosition($letzte360TageID, 130);
  	
	$letzterTagEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzterTag", 2);
   IPS_SetVariableCustomProfile($letzterTagEurID,'Euro');
  	IPS_SetPosition($letzterTagEurID, 200);
	$letzte7TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte7Tage", 2);
   IPS_SetVariableCustomProfile($letzte7TageEurID,'Euro');
  	IPS_SetPosition($letzte7TageEurID, 210);
	$letzte30TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte30Tage", 2);
   IPS_SetVariableCustomProfile($letzte30TageEurID,'Euro');
  	IPS_SetPosition($letzte30TageEurID, 220);
	$letzte360TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte360Tage", 2);
   IPS_SetVariableCustomProfile($letzte360TageEurID,'Euro');
  	IPS_SetPosition($letzte360TageEurID, 230);
  	
	$vorwert=0;
	$zaehler=0;
	$jetzt=time();

	$endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	$starttime=$endtime-60*60*24*1;
	echo "Werte von ".date("d.m.Y H:i:s",$starttime)." bis ".date("d.m.Y H:i:s",$endtime)."\n";
	echo "Variable: ".IPS_GetName($variableID)."\n";

	$ergebnis=summestartende($starttime, $endtime, true,false,$archiveHandlerID,$variableID,$display);
	echo "Ergebnis Wert letzter Tag : ".$ergebnis."kWh \n";
	SetValue($letzterTagID,$ergebnis);
	SetValue($letzterTagEurID,$ergebnis*GetValue($KostenID));

	$starttime=$endtime-60*60*24*7;
	$ergebnis=summestartende($starttime, $endtime, true, false, $archiveHandlerID, $variableID, $display);
	echo "Ergebnis Wert letzte 7 Tage : ".$ergebnis."kWh \n";
	SetValue($letzte7TageID,$ergebnis);
	SetValue($letzte7TageEurID,$ergebnis*GetValue($KostenID));

	$starttime=$endtime-60*60*24*30;
	$ergebnis=summestartende($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
	echo "Ergebnis Wert letzte 30 Tage : ".$ergebnis."kWh \n";
	SetValue($letzte30TageID,$ergebnis);
	SetValue($letzte30TageEurID,$ergebnis*GetValue($KostenID));

	$starttime=$endtime-60*60*24*360;
	$ergebnis=summestartende($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
	echo "Ergebnis Wert letzte 360 Tage : ".$ergebnis."kWh \n";
	SetValue($letzte360TageID,$ergebnis);
	SetValue($letzte360TageEurID,$ergebnis*GetValue($KostenID));
   }

if ($_IPS['SENDER'] == "Execute")
	{
	echo"-------------------------------------------------------------\n";
	echo "Plausi-Check von Logged Variablen.\n";

	$zaehler=0;
	$initial=true;
	$ergebnis=0;
	$vorigertag="";
	$disp_vorigertag="";
	$neuwert=0;
	
	
	$endtime=mktime(0,0,0,date("m", $jetzt), date("d", $jetzt), date("Y", $jetzt));
	//$endtime=mktime(0,0,0,3 /* Monat */, 1/* Tag */, date("Y", $jetzt));
	$endtime=time();

	$starttime=$endtime-60*60*24*360;
	$starttime=mktime(0,0,0,2 /* Monat */, 1/* Tag */, date("Y", $jetzt));

	$display=false;
	$delete=false;

foreach ($MeterConfig as $meter)
	{
	echo"-------------------------------------------------------------\n";
	echo "Create Variableset for :".$meter["NAME"]." \n";
	$ID = CreateVariableByName($parentid1, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	if ($meter["TYPE"]=="Homematic")
	   {
		/* Variable ID selbst bestimmen */
	   $variableID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	   }
	else
	   {
		$variableID = $meter["WirkenergieID"];
		}
		
	$vorwert=0;
	//$variableID=44113;

	echo "ArchiveHandler: ".$archiveHandlerID." Variable: ".$variableID."\n";
	$increment=1;
	//echo "Increment :".$increment."\n";
	$gepldauer=($endtime-$starttime)/24/60/60;
	do {
		/* es könnten mehr als 10.000 Werte sein
			Abfrage generisch lassen
		*/

		$werte = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime, $endtime, 0);
		/* Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten
			Nicht mer als 10.000 Werte ...
		*/
		//print_r($werte);
   	$anzahl=count($werte);

   	//echo "   Variable: ".IPS_GetName($variableID)." mit ".$anzahl." Werte \n";

		if (($anzahl == 0) & ($zaehler == 0)) {return 0;}   // hartes Ende wenn keine Werte vorhanden

		if ($initial)
			   {
			   /* allererster Durchlauf */
				$ersterwert=$werte['0']['Value'];
		   	$ersterzeit=$werte['0']['TimeStamp'];
		   	}

		if ($anzahl<10000)
		   	{
	   		/* letzter Durchlauf */
		   	$letzterwert=$werte[sprintf('%d',$anzahl-1)]['Value'];
			   $letzterzeit=$werte[sprintf('%d',$anzahl-1)]['TimeStamp'];
				//echo "   Erster Wert : ".$werte[sprintf('%d',$anzahl-1)]['Value']." vom ".date("D d.m.Y H:i:s",$werte[sprintf('%d',$anzahl-1)]['TimeStamp']).
				//     " Letzter Wert: ".$werte['0']['Value']." vom ".date("D d.m.Y H:i:s",$werte['0']['TimeStamp'])." \n";
				}

		$initial=true;

		foreach($werte as $wert)
				{
				$zeit=$wert['TimeStamp'];
				$tag=date("d.m.Y", $zeit);
				$aktwert=(float)$wert['Value'];

				if ($initial)
					{
					//print_r($wert);
					$initial=false;
					$vorwert=$aktwert;
					echo "   Initial Startzeitpunkt:".date("d.m.Y H:i:s", $wert['TimeStamp'])."\n";
					}
				if (($aktwert>$vorwert) or ($aktwert==0) or ($aktwert<0))
				   {
				 	if ($delete==true)
			   		{
						AC_DeleteVariableData($archiveHandlerID, $variableID, $zeit, $zeit);
						}
					echo "****".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe: " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
				   }
				else
				   {
					$vorwert=$aktwert;
					}
				if ($tag!=$vorigertag)
			   	{ /* neuer Tag */
			   	$altwert=$neuwert;
			   	$neuwert=$aktwert;
			   	switch ($increment)
			   		{
			   		case 1:
							$ergebnis=$aktwert;
			   		   break;
			   		case 2:
						   if ($altwert<$neuwert)
						      {
								$ergebnis+=($neuwert-$altwert);
								}
							else
							   {
								//$ergebnis+=($altwert-$neuwert);
								//$ergebnis=$aktwert;
								}
							break;
						case 0:
							$ergebnis+=$aktwert;
	                  break;
	               default:
	               }
				   $vorigertag=$tag;
				   }

				if ($display==true)
					{
			   	/* jeden Eintrag ausgeben */
			   	//print_r($wert);
			   	   {
						echo "   ".date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . number_format($aktwert, 3, ".", "") ." ergibt in Summe: " . number_format($ergebnis, 3, ".", "") . PHP_EOL;
						}
					}
				$zaehler+=1;
				}
				$endtime=$zeit;
		} while (count($werte)==10000);

//Alle Datensätze vom 01.01.2013 bis zum 31.12.2013 abfragen (Tägliche Aggregationsstufe)
//z.B. um den Verbrauch am jeweiligen Tag zu ermitteln oder die Durchschnittstemperatur am jeweiligen Tag (1) Monat (3)
$werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1 , mktime(0, 0, 0, 1, 2, 2014), mktime(23, 59, 59, 02, 31, 2014), 0); //55554 ist die ID der Variable, 12345 vom Archiv

//Alle heutigen Datensätze abfragen (Tägliche Aggregationsstufe)
//z.B. um den heutigen Verbrauch er ermitteln oder die heutige Durchschnittstemperatur
//$werte = AC_GetAggregatedValues(12345, 55554, 1 /* Täglich */, strtotime("today 00:00"), time(), 0); //55554 ist die ID der Variable, 12345 vom Archiv

//Alle gestrigen Datensätze abfragen (Stündlichen Aggregationsstufe)
//z.B. um den gesterigen Verbrauch oder die durchschittliche Windgeschwindigkeit jeder Stunde zu begutachten
//$werte = AC_GetAggregatedValues(12345, 55554, 0 /* Stündlich */, strtotime("yesterday 00:00"), strtotime("today 00:00")-1, 0); //55554 ist die ID der Variable, 12345 vom Archiv

//Dieser Teil erstellt eine Ausgabe im Skriptfenster mit den abgefragten Werten
foreach($werte as $wert) {
	echo date("d.m.Y H:i:s", $wert['TimeStamp']) . " -> " . $wert['Avg'] . PHP_EOL;
}


	}


	}
	   
?>
