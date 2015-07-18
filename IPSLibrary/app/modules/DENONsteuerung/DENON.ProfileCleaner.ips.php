<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

############################ Info ##############################################
/*

Funktionen:
	*dient zur manuellen Löschung ALLER DENON.XXX-Variablenprofile
	*sollte nur ausgeführt werden wenn auf eine neue Version des DENON-Pakets
		umgestiegen werden soll (und diese neue Version Änderungen in den Variablen-Profilen enthält)
	*wenn mit diesem Script bestehende Variablenprofile gelöscht werden sollen so
		sollte dies unbedingt VOR Ausführung des DENON.Installers erfolgen
		(der >DENON.Installer überschreibt keine bestehenden Profile)
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
      echo "DENON.ProfileCleaner: Variablenprofil $profile gelöscht\n";
   }
}
echo "DENON.ProfileCleaner: alle DENON.XXX Variablenprofile gelöscht!\n";
?>