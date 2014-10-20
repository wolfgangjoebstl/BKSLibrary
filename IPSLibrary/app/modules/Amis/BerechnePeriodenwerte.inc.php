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

$display=false;       /* alle Eintraege auf der Console ausgeben */

$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
$archiveHandlerID = $archiveHandlerID[0];
//echo "Handler ID:".$archiveHandlerID;

//$ID_ArchivHandler = 50532;

$Tag=1;
$Monat=1;
$Jahr=2011;
//$variableID=30163;

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis.Zaehlervariablen');
$variableID = IPS_GetObjectIDByName ( 'Default-Wirkenergie' , $parentid );
//$variableID=57237;

$letzterTagID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_letzterTag", 2);
$letzte7TageID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_letzte7Tage", 2);
$letzte30TageID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_letzte30Tage", 2);
$letzte360TageID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_letzte360Tage", 2);

$letzterTagEurID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_Euro_letzterTag", 2);
$letzte7TageEurID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_Euro_letzte7Tage", 2);
$letzte30TageEurID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_Euro_letzte30Tage", 2);
$letzte360TageEurID = CreateVariableByName($_IPS['SELF'], "Wirkenergie_Euro_letzte360Tage", 2);

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
SetValue($letzterTagEurID,$ergebnis*GetValue(28811));

$starttime=$endtime-60*60*24*7;
$ergebnis=summestartende($starttime, $endtime, true, false, $archiveHandlerID, $variableID, $display);
echo "Ergebnis Wert letzte 7 Tage : ".$ergebnis."kWh \n";
SetValue($letzte7TageID,$ergebnis);
SetValue($letzte7TageEurID,$ergebnis*GetValue(28811));

$starttime=$endtime-60*60*24*30;
$ergebnis=summestartende($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
echo "Ergebnis Wert letzte 30 Tage : ".$ergebnis."kWh \n";
SetValue($letzte30TageID,$ergebnis);
SetValue($letzte30TageEurID,$ergebnis*GetValue(28811));

$starttime=$endtime-60*60*24*360;
$ergebnis=summestartende($starttime, $endtime, true, false,$archiveHandlerID,$variableID,$display);
echo "Ergebnis Wert letzte 360 Tage : ".$ergebnis."kWh \n";
SetValue($letzte360TageID,$ergebnis);
SetValue($letzte360TageEurID,$ergebnis*GetValue(28811));

	 
	   
?>
