<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

############################ Info ##############################################
/*

Funktionen:
	*dient zur manuellen L�schung ALLER DENON.XXX-Variablenprofile
	*sollte nur ausgef�hrt werden wenn auf eine neue Version des DENON-Pakets
		umgestiegen werden soll (und diese neue Version �nderungen in den Variablen-Profilen enth�lt)
	*wenn mit diesem Script bestehende Variablenprofile gel�scht werden sollen so
		sollte dies unbedingt VOR Ausf�hrung des DENON.Installers erfolgen
		(der >DENON.Installer �berschreibt keine bestehenden Profile)
*/

############################ Info Ende #########################################

echo "DENON.ProfileCleaner started\nwww.raketenschnecke.net\n\n";

$profile_array = IPS_GetVariableProfileList ();
$profile_praefix = "DENON.";
foreach ($profile_array as $profile)
{
   if (strpos ($profile, $profile_praefix) !== false)
	{
      IPS_DeleteVariableProfile($profile);
      echo "DENON.ProfileCleaner: Variablenprofil $profile gel�scht\n";
   }
}
echo "DENON.ProfileCleaner: alle DENON.XXX Variablenprofile gel�scht!\n";
?>