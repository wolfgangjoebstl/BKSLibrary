<?
// ************************************************************************
// Bitte die nachfolgenden Beispiele durch die eigene Daten ersetzen
// Bei der Bezeichnung des $Zimmers bitte darauf achten, dass der Name mit den
// IPS-Konventionen f�r den Befehl IPS_SetIdent im  Einklang steht
// (d.H. keine Sonderzeichen, Umlaute oder Leerzeichen etc.)
// *************************************************************************

$HM_Edit_Wfe_ID= xxxxx /*[Visualization\WebFront\Heizung\WF_R�ume\Zeitplan - Editieren]*/ ;

//******************************************************************************
$Zimmer[1]="Kueche";
$HM_Typ[1]="HM-CC-TC";

$IPS_HM_DeviceID[1]=xxxxx /*[Hardware\Haus\Erdgeschoss\K�che\Heizung\HM-CC-TC - K�che\CLIMATECONTROL_REGULATOR]*/ ;
$HM_ID_W[1]=xxxxx /*[Hardware\Haus\Erdgeschoss\K�che\Heizung\HM-CC-TC - K�che\WEATHER]*/  ;
$HM_ID_VD[1]=xxxxx /*[Hardware\Haus\Erdgeschoss\K�che\Heizung\HM-CC-VD - K�che\CLIMATECONTROL_VENT_DRIVE]*/  ;
$HM_Wfe_ID[1]=xxxxx /*[Visualization\WebFront\Heizung\WF_R�ume\K�che\K�che-links]*/ ;
//****************************************************************************
$Zimmer[2]="Arbeitszimmer";
$HM_Typ[2]="HM-TC-IT-WM-W-EU";

$IPS_HM_DeviceID[2]=xxxxx /*[Hardware\Haus\1. Etage\Arbeitszimmer\Heizung\HM-TC-IT-WM-W-EU_03 - AZ\THERMALCONTROL_TRANSMIT]*/  ;
$HM_Wfe_ID[2]=xxxxx /*[Visualization\WebFront\Heizung\WF_R�ume\Arbeitszimmer\Arbeitszimmer-links]*/   ;
//******************************************************************************
$Zimmer[3]="Wohnzimmer";
$HM_Typ[3]="HM-CC-RT-DN";

$IPS_HM_DeviceID[3]=xxxxx /*[Hardware\Haus\Erdgeschoss\Wohnzimmer\HM-CC-RT-DN_03 - Wohnzimmer - links\CLIMATECONTROL_RT_TRANSCEIVER]*/  ;
$HM_Wfe_ID[3]=xxxxx /*[Visualization\WebFront\Heizung\WF_R�ume\WZ01\WZ01-links]*/ ;
//****************************************************************************

?>