<?

 //
 // LW12 LED Controller Install-Skript (LW12_Create_Modul) - V 1.0 BETA
 // ===================================================================
 //
 // Autor: Matthias Ziolek / 02.09.2014
 //
 // Dieses Skript legt beim Ausf�hren eine neue LW12 LED Controller Instanz inkl. aller Skripte/Events und �hnlichem an.
 // Bitte am Anfang dieses Skripts die ID der LW12_Library einsetzen
 //
 // Zur Nutzung des Controllers muss in der jeweiligen Instanz nur noch die richtige IP-Adresse und der richtige Port hinterlegt werden.
 //


$LW12_LibraryId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.LedAnsteuerung.LedAnsteuerung_Library');
$parentId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.LedAnsteuerung');

if (false)
{
 // Dummy Instanz anlegen

$name="LW12_Arbeitszimmer";
$modulId = @IPS_GetInstanceIDByName($name, $parentId);

if(!IPS_InstanceExists($modulId))
   {
	$modulId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");

	IPS_SetParent($modulId, $parentId);
	IPS_SetName($modulId, $name);
	IPS_ApplyChanges($modulId);

	 // Variabeln anlegen
	 // =================

	 //!IP
	$var = IPS_CreateVariable(3);
	IPS_SetParent($var, $modulId);
	IPS_SetName($var, "!IP");
	SetValue($var, "1.2.3.4");
	IPS_SetHidden($var, true);

	 //!LW12_Library
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $modulId);
	IPS_SetName($var, "!LW12_Library");
	SetValue($var, $LW12_LibraryId);
	IPS_SetHidden($var, true);

	 //!TCP-Port
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $modulId);
	IPS_SetName($var, "!TCP-Port");
	SetValue($var, 5577);
	IPS_SetHidden($var, true);

	 //Licht
	$var = IPS_CreateVariable(0);
	IPS_SetParent($var, $modulId);
	IPS_SetPosition($var, 10);
	IPS_SetName($var, "Licht");
	SetValue($var, false);
	IPS_SetVariableCustomProfile($var, "~Switch");
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_PowerToggle();");

	 //Modus
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $modulId);
	IPS_SetPosition($var, 20);
	IPS_SetName($var, "Modus");
	SetValue($var, 0);
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_ModeToggle();");

		// Var Profil anlegen und zuweisen
      if (!IPS_VariableProfileExists("LW12_LED_MODE")) {
			IPS_CreateVariableProfile("LW12_LED_MODE", 1);
			IPS_SetVariableProfileValues("LW12_LED_MODE", 0, 2, 1);
			IPS_SetVariableProfileAssociation("LW12_LED_MODE", 0, "Einfarbig", "", 0x00FF00);
			IPS_SetVariableProfileAssociation("LW12_LED_MODE", 1, "Controller-Programme", "", 0x00FFAA);
			IPS_SetVariableProfileAssociation("LW12_LED_MODE", 2, "IPS-Programme", "", 0x00FF88);
			}
		IPS_SetVariableCustomProfile($var, "LW12_LED_MODE");


	 //Farbauswahl
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $modulId);
	IPS_SetPosition($var, 30);
	IPS_SetName($var, "Farbauswahl (Rot-Gr�n-Blau)");
	SetValue($var, 0);
	IPS_SetVariableCustomProfile($var, "~HexColor");
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_setDecRGB(GetValue(\$_IPS['VARIABLE']));");




	 // Controller-Programme Dummy Instanz anlegen
	 // ==========================================

	$subModulId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");

	IPS_SetParent($subModulId, $modulId);
	IPS_SetName($subModulId, "Controller-Programme");
	IPS_SetPosition($subModulId, 50);
	IPS_SetHidden($subModulId, true);

	 //Automatisches Programm
	$var = IPS_CreateVariable(0);
	IPS_SetParent($var, $subModulId);
	IPS_SetPosition($var, 0);
	IPS_SetName($var, "Automatisches Programm");
	SetValue($var, false);
	IPS_SetVariableCustomProfile($var, "~Switch");
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_CtrlPrgToggle();;");

	//Programm
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $subModulId);
	IPS_SetPosition($var, 10);
	IPS_SetName($var, "Programm");
	SetValue($var, 1);
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_SetCtrlPrg(\$_IPS['VALUE']);");

		// Var Profil anlegen und zuweisen
      if (!IPS_VariableProfileExists("LW12_CTRL_PRGS")) {
			IPS_CreateVariableProfile("LW12_CTRL_PRGS", 1);
			IPS_SetVariableProfileValues("LW12_CTRL_PRGS", 1, 20, 1);
			}
		IPS_SetVariableCustomProfile($var, "LW12_CTRL_PRGS");

	//Geschwindigkeit
	$var = IPS_CreateVariable(1);
	IPS_SetParent($var, $subModulId);
	IPS_SetPosition($var, 10);
	IPS_SetName($var, "Geschwindigkeit");
	SetValue($var, 1);
	IPS_SetVariableCustomAction($var, $LW12_LibraryId);

		// Event anlegen
		$eventId = IPS_CreateEvent(0);               //Ausgel�stes Ereignis
		IPS_SetEventTrigger($eventId, 1, $var);      //Bei �nderung von Variable mit ID $var
		IPS_SetParent($eventId, $var);         		//Ereignis zuordnen
		IPS_SetEventActive($eventId, true);          //Ereignis aktivieren
		IPS_SetEventScript($eventId, "require(\"scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php\");\n\nLW12_SetCtrlPrgSpeed(\$_IPS['VALUE']);");

		// Var Profil anlegen und zuweisen
      if (!IPS_VariableProfileExists("LW12_CTRL_PRGS_SPEED")) {
			IPS_CreateVariableProfile("LW12_CTRL_PRGS_SPEED", 1);
			IPS_SetVariableProfileValues("LW12_CTRL_PRGS_SPEED", 1, 31, 1);
			}
		IPS_SetVariableCustomProfile($var, "LW12_CTRL_PRGS_SPEED");

	}
$ipadrId = @IPS_GetObjectIDByName("!IP", $modulId);

echo "ModulID:".$modulId."\n";
echo "IP ID:".$ipadrId."\n";
SetValue($ipadrId,"10.0.0.50");
}

echo "LED auch in IPS_light anlegen.\n";


?>
