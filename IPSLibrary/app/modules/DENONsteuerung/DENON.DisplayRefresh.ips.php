<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------


############################ Info ##############################################
/*

Funktionen:
	*setzt Kommando zur Abfrage der aktuellen Display-Informationen des DENON AVR ab
	*Script kann z.B. durch ein zyklisches Event getrigert werden -> derzeit
		aber nicht Bestandteil des DENON.Installers

*/

############################ Info Ende #########################################

########################## Konfig ##############################################
// include DENON.Functions
$Denon_KatID = IPS_GetCategoryIDByName("DENON", 0);
$DENON_Scripts_ID = IPS_GetCategoryIDByName("DENON Scripts", $Denon_KatID);
if (IPS_GetObjectIDByName("DENON.Functions", $DENON_Scripts_ID) >0)
{
	include "DENON.Functions.ips.php";
}
else
{
	echo "Script DENON.Functions kann nicht gefunden werden!";
}

// Timer Ein bei POWER ein
$Denon_KatID = IPS_GetCategoryIDByName("DENON", 0);
$Denon_MainZone_ID = IPS_GetObjectIDByName("Main Zone", $Denon_KatID);
$DENON_Power_ID = IPS_GetObjectIDByName("Power", $Denon_MainZone_ID);
$Denon_Power_val = getvalueBoolean($DENON_Power_ID);

// ermitteln der Display-EventrefreshTimer-ID
$DisplayRefresh_EventID = IPS_GetObjectIDByName("DENON Scripts", $Denon_KatID);
$DisplayRefresh_EventID = IPS_GetObjectIDByName("DENON.DisplayRefresh", $DisplayRefresh_EventID);
$DisplayRefresh_EventID = IPS_GetObjectIDByName("DENON.DisplayRefreshTimer", $DisplayRefresh_EventID);

// ermitteln der DENON Quickselct Variablen-ID
$Denon_Quickselect_ID = IPS_GetObjectIDByName("Main Zone", $Denon_KatID);
$Denon_Quickselect_ID = IPS_GetObjectIDByName("QuickSelect", $Denon_Quickselect_ID);
$Denon_Quickselect_val = getValueInteger($Denon_Quickselect_ID);

if (($Denon_Power_val == true) && ($Denon_Quickselect_val == 1))
{
	IPS_SetEventActive($DisplayRefresh_EventID, true);
}
else
{
	IPS_SetEventActive($DisplayRefresh_EventID, false);
}
########################## Konfig Ende #########################################


DENON_NSA_DisplayRequest($id);

?>