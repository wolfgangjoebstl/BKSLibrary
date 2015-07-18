<?
//Script was created at 18.06.11 15:08.53 by Script Installer Creator V0.2, Raketenschnecke
// ++++ Modul1 DENON.InstallerObjectModul ++++++++++

//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------


############################ Info ##############################################
/*

Funktionen:
	*legt "DENON Client Socket" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt "DENON Cutter" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt "DENON Register Variable" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt Kategorie "DENON" im Root-Ordner an
	*legt Kategorie "DENON Webfront" in Kategorie "DENON" an
	*legt Kategorie "DENON Scripts" in Kategorie "DENON" an
	*legt Scripte "DENON.Install_Library.ips.php", "DENON.ActionScript.ips.php"
		und"DENON.Functions.ips.php in Kategori "DENON Scritpe" an
	* legt Script "DENON.CommandReceiver.ips.php" unterhalb der RegisterVariablen "Denon Register Variable" an
	* legt Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON" an (bestehende Instanzen werden nicht gelöscht)
	* legt Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON Webfront" an (bestehende Instanzen werden nicht gelöscht)

Erst-Installation:
	*dieses Script in IPS hochladen
	*dieses Script ausführen

Installation (erneut/Update)
	* bereits bestehende Kategorieen, Instanzen, Links und Variablen müssen vor Ausführung des Sripts nicht
		zwingend gelöscht werden (bestehende Kategorieen werden nicht verändert)
	* existierende Variablenprofile werden nicht gelöscht (sollen diese gelöscht werden
		bitte vor Ausführung des DENON.Installers das Script DENON.ProfileCleaner ausführen
		(Script liegt im Bojektbaum unter "DENON/DENON Scripts")
	*bestehende Scripte (vorherige Verisionen) werden gelöscht und neu angelegt
*/

############################ Info Ende #########################################

###################### Konfigurationsangaben ###################################

$DENON_VAVR_IP = "10.0.0.115"; // hier die IP des DENON AVR angeben

###################### Konfigurationsangaben Ende ##############################

// ab hier nichts mehr ändern
###################### DENON Kategorieen anlegen ###############################

echo "DENON.Installer started\nwww.raketenschnecke.net\n\n";

// Client Socket "DENON Client Socket" anlegen wenn nicht vorhanden
$DENON_CS_ID = @IPS_GetObjectIDByName("DENON Client Socket", 0);
if ($DENON_CS_ID === false)
{
   $DENON_CS_ID = IPS_CreateInstance("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
   IPS_SetName($DENON_CS_ID, "DENON Client Socket");
   IPS_SetInfo($DENON_CS_ID, "this Object was created by Script DENON.Installer.ips.php");
   CSCK_SetHost($DENON_CS_ID, $DENON_VAVR_IP);
   CSCK_SetPort($DENON_CS_ID, 23);
   CSCK_SetOpen($DENON_CS_ID,true);
	IPS_ApplyChanges($DENON_CS_ID);
	echo "DENON Client Socket angelegt\n";
}
else
{
   echo "DENON Client Socket bereits vorhanden (ID: $DENON_CS_ID)\n";
}

// Cutter "DENON Cutter" anlegen wenn nicht vorhanden und mit Client Socket verbinden
$DENON_Cu_ID = @IPS_GetObjectIDByName("DENON Cutter", 0);
if ($DENON_Cu_ID == false)
{
   $DENON_Cu_ID = IPS_CreateInstance("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}");
   IPS_SetName($DENON_Cu_ID, "DENON Cutter");
   IPS_SetInfo($DENON_Cu_ID, "this Object was created by Script DENON.Installer.ips.php");
   IPS_ConnectInstance($DENON_Cu_ID, $DENON_CS_ID);
	Cutter_SetRightCutChar($DENON_Cu_ID, Chr(0x0D));
	IPS_ApplyChanges($DENON_Cu_ID);
	echo "DENON Cutter angelegt und mit DENON Client Socket #DENON_CS_ID verknüpft\n";
}
else
{
   $DENON_Cu_ID = @IPS_GetInstanceIDByName("DENON Cutter", 0);
   IPS_ConnectInstance($DENON_Cu_ID, $DENON_CS_ID);
   Cutter_SetRightCutChar($DENON_Cu_ID, Chr(0x0D));
	IPS_ApplyChanges($DENON_Cu_ID);
   echo "DENON Cutter (#$DENON_Cu_ID) bereits vorhanden, neu konfiguriert \n";
}

// Cutter "DENON Register Variable" anlegen wenn nicht vorhanden und mit "DENON Cutter" verbinden
$DENON_RegVar_ID = @IPS_GetObjectIDByName("DENON Register Variable", $DENON_Cu_ID);
if ($DENON_RegVar_ID == false)
{
   $DENON_RegVar_ID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}");
   IPS_SetName($DENON_RegVar_ID, "DENON Register Variable");
   IPS_SetInfo($DENON_RegVar_ID, "this Object was created by Script DENON.Installer.ips.php");
   IPS_SetParent($DENON_RegVar_ID, $DENON_Cu_ID);
   IPS_ConnectInstance($DENON_RegVar_ID, $DENON_Cu_ID);

	IPS_ApplyChanges($DENON_RegVar_ID);
	echo "DENON Register Variable angelegt und mit DENON Register Variable #$DENON_Cu_ID verknüpft\n";
}
else
{
   echo "DENON Register Variable bereits vorhanden (ID: $DENON_RegVar_ID)\n";
}

// Kategorie "DENON" anlegen wenn nicht vorhanden
$DENON_ID = @IPS_GetCategoryIDByName("DENON", 0);
if ($DENON_ID == false)
{
	$DENON_ID = IPS_CreateCategory();
	IPS_SetName($DENON_ID, "DENON");
	IPS_SetInfo($DENON_ID, "this Object was created by Script DENON.Installer.ips.php");
	echo "Kategorie DENON #$DENON_ID angelegt\n";
}

// Kategorie "DENON Webfront" anlegen wenn nicht vorhanden
$DENON_WFE_ID = @IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
if ($DENON_WFE_ID == false)
{
	$DENON_WFE_ID = IPS_CreateCategory();
	IPS_SetName($DENON_WFE_ID, "DENON Webfront");
	IPS_SetInfo($DENON_WFE_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_SetParent($DENON_WFE_ID, $DENON_ID);
	echo "Kategorie DENON Webfront #$DENON_WFE_ID angelegt\n";
}

// Kategorie "DENON Scripts" anlegen wenn nicht vorhanden
$DENON_Scripts_ID = @IPS_GetCategoryIDByName("DENON Scripts", $DENON_ID);
if ($DENON_Scripts_ID == false)
{
	$DENON_Scripts_ID = IPS_CreateCategory();
	IPS_SetName($DENON_Scripts_ID, "DENON Scripts");
	IPS_SetInfo($DENON_Scripts_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_SetParent($DENON_Scripts_ID, $DENON_ID);
}


// Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON"
// anlegen wenn nicht vorhanden
$DENON_MainZone_ID = @IPS_GetInstanceIDByName("Main Zone", $DENON_ID);
if ($DENON_MainZone_ID == false)
{
	$DENON_Main_Instance_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Main_Instance_ID, $DENON_ID);
	IPS_SetName($DENON_Main_Instance_ID, "Main Zone");
	IPS_SetInfo($DENON_Main_Instance_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Main_Instance_ID);
	echo "Dummy-Instanz Main Zone #$DENON_Main_Instance_ID in Kategorie DENON angelegt\n";
}

$DENON_Zone2_ID = @IPS_GetInstanceIDByName("Zone 2", $DENON_ID);
if ($DENON_Zone2_ID == false)
{
	$DENON_Zone2_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Zone2_ID, $DENON_ID);
	IPS_SetName($DENON_Zone2_ID, "Zone 2");
	IPS_SetInfo($DENON_Zone2_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Zone2_ID);
	echo "Dummy-Instanz Zone 2 #$DENON_Zone2_ID in Kategorie DENON angelegt\n";
}

$DENON_Zone3_ID = @IPS_GetInstanceIDByName("Zone 3", $DENON_ID);
if ($DENON_Zone3_ID == false)
{
	$DENON_Zone3_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Zone3_ID, $DENON_ID);
	IPS_SetName($DENON_Zone3_ID, "Zone 3");
	IPS_SetInfo($DENON_Zone3_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Zone3_ID);
	echo "Dummy-Instanz Zone 3 #$DENON_Zone3_ID in Kategorie DENON angelegt\n";
}

$DENON_Steuerung_ID = @IPS_GetInstanceIDByName("Steuerung", $DENON_ID);
if ($DENON_Steuerung_ID == false)
{
	$DENON_Steuerung_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Steuerung_ID, $DENON_ID);
	IPS_SetName($DENON_Steuerung_ID, "Steuerung");
	IPS_SetInfo($DENON_Steuerung_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Steuerung_ID);
	echo "Dummy-Instanz Steuerung #$DENON_Steuerung_ID in Kategorie DENON angelegt\n";
}

$DENON_Display_ID = @IPS_GetInstanceIDByName("Display", $DENON_ID);
if ($DENON_Display_ID == false)
{
	$DENON_Display_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Display_ID, $DENON_ID);
	IPS_SetName($DENON_Display_ID, "Display");
	IPS_SetInfo($DENON_Display_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Display_ID);
	echo "Dummy-Instanz Display #$DENON_Display_ID in Kategorie DENON angelegt\n";
}

// Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON Webfront"
// anlegen wenn nicht vorhanden
$DENON_MainZone_ID = @IPS_GetInstanceIDByName("Main Zone", $DENON_WFE_ID);
if ($DENON_MainZone_ID == false)
{
	$DENON_Main_Instance_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Main_Instance_ID, $DENON_WFE_ID);
	IPS_SetName($DENON_Main_Instance_ID, "Main Zone");
	IPS_SetInfo($DENON_Main_Instance_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Main_Instance_ID);
	echo "Dummy-Instanz Main Zone #$DENON_Main_Instance_ID in Kategorie DENON Webfront angelegt\n";
}

$DENON_Zone2_ID = @IPS_GetInstanceIDByName("Zone 2", $DENON_WFE_ID);
if ($DENON_Zone2_ID == false)
{
	$DENON_Zone2_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Zone2_ID, $DENON_WFE_ID);
	IPS_SetName($DENON_Zone2_ID, "Zone 2");
	IPS_SetInfo($DENON_Zone2_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Zone2_ID);
	echo "Dummy-Instanz Zone 2 #$DENON_Zone2_ID in Kategorie DENON Webfront angelegt\n";
}

$DENON_Zone3_ID = @IPS_GetInstanceIDByName("Zone 3", $DENON_WFE_ID);
if ($DENON_Zone3_ID == false)
{
	$DENON_Zone3_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_Zone3_ID, $DENON_WFE_ID);
	IPS_SetName($DENON_Zone3_ID, "Zone 3");
	IPS_SetInfo($DENON_Zone3_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_Zone3_ID);
	echo "Dummy-Instanz Zone 3 #$DENON_Zone3_ID in Kategorie DENON Webfront angelegt\n";
}

$DENON_SteuerungWFE_ID = @IPS_GetInstanceIDByName("Steuerung", $DENON_WFE_ID);
if ($DENON_SteuerungWFE_ID == false)
{
	$DENON_SteuerungWFE_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_SteuerungWFE_ID, $DENON_WFE_ID);
	IPS_SetName($DENON_SteuerungWFE_ID, "Steuerung");
	IPS_SetInfo($DENON_SteuerungWFE_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_SteuerungWFE_ID);
	echo "Dummy-Instanz Steuerung #$DENON_SteuerungWFE_ID in Kategorie DENON Webfront angelegt\n";
}

$DENON_DisplayWFE_ID = @IPS_GetInstanceIDByName("Display", $DENON_WFE_ID);
if ($DENON_DisplayWFE_ID == false)
{
	$DENON_DisplayWFE_ID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
	IPS_SetParent($DENON_DisplayWFE_ID, $DENON_WFE_ID);
	IPS_SetName($DENON_DisplayWFE_ID, "Display");
	IPS_SetInfo($DENON_DisplayWFE_ID, "this Object was created by Script DENON.Installer.ips.php");
	IPS_ApplyChanges($DENON_DisplayWFE_ID);
	echo "Dummy-Instanz Display #$DENON_DisplayWFE_ID in Kategorie DENON Webfront angelegt\n";
}

#################### DENON Scripte anlegen #####################################


		$ScriptNAME = 'DENON.VariablenManager';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.ActionScript';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.VariablenManager';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.CommandManager';
		$D_Cutter_ID = @IPS_GetObjectIDByName("DENON Cutter", 0);
		$ParentID = @IPS_GetObjectIDByName("DENON Register Variable", $D_Cutter_ID);
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.Functions';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.ProfileCleaner';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);

		$ScriptNAME = 'DENON.DisplayRefresh';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '';
		//CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);


// ++++ Modul8 DENON.InstallerConfigModul ++++++++++

//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------
#

// Cutter "DENON Register Variable" mit Script "DENON.CommandManager" verbinden
$DENON_Cu_ID = IPS_GetObjectIDByName("DENON Cutter", 0);
$DENON_RegVar_ID = IPS_GetObjectIDByName("DENON Register Variable", $DENON_Cu_ID);
$DENON_CommandManager_ID = IPS_GetObjectIDByName("DENON.CommandManager", $DENON_RegVar_ID);
if ($DENON_RegVar_ID != false)
{
	RegVar_SetRXObjectID($DENON_RegVar_ID , $DENON_CommandManager_ID);
	IPS_ApplyChanges($DENON_RegVar_ID);
	echo "DENON Register Variable  mit Script DENON.DENON_CommandManager #$DENON_CommandManager_ID verknüpft\n";
}
else
{
   echo "DENON Register Variable konnte nicht mit Script
				DENON.CommandReceiver #$DENON_CommandManager_ID verknüpft werden\n";
}

// Event "DisplayRefreshTimer" anlegen und zuweisen wenn nicht vorhanden
$DENON_Kat_ID = IPS_GetObjectIDByName("DENON", 0);
$DENON_Scripts_ID = IPS_GetObjectIDByName("DENON Scripts", $DENON_Kat_ID);
$DENON_DisplayRefresh_ID = IPS_GetScriptIDByName("DENON.DisplayRefresh", $DENON_Scripts_ID);

$DENON_DisplayRefreshTimer_ID = @IPS_GetObjectIDByName("DENON.DisplayRefreshTimer", $DENON_DisplayRefresh_ID);

if ($DENON_DisplayRefreshTimer_ID == 0)
{
	$DENON_DisplayRefreshTimer_ID = IPS_CreateEvent(1);        //DisplayRefreshTimer erstellen
	IPS_SetParent($DENON_DisplayRefreshTimer_ID, $DENON_DisplayRefresh_ID); //Ereignis zuordnen
	IPS_SetName($DENON_DisplayRefreshTimer_ID, "DENON.DisplayRefreshTimer");
	IPS_SetEventCyclic($DENON_DisplayRefreshTimer_ID, 0, 0, 0, 0, 1, 5); // alle 5 Sekunden
	IPS_SetEventActive($DENON_DisplayRefreshTimer_ID, false);    //Ereignis deaktivieren
}
else
{
	IPS_SetParent($DENON_DisplayRefreshTimer_ID, $DENON_DisplayRefresh_ID); //Ereignis zuordnen
	IPS_SetEventCyclic($DENON_DisplayRefreshTimer_ID, 0, 0, 0, 0, 1, 5); // alle 5 Sekunden
	IPS_SetEventActive($DENON_DisplayRefreshTimer_ID, false);    //Ereignis deaktivieren
}

################# Variablen/Links Cursorsteuerung anlegen ######################

$DENON_Kat_ID = IPS_GetObjectIDByName("DENON", 0);
$DENON_WebfrontID = IPS_GetObjectIDByName("DENON Webfront", $DENON_Kat_ID);
$DENON_SteuerungWFE_ID = IPS_GetObjectIDByName("Steuerung", $DENON_WebfrontID);
$DENON_Scripts_ID = IPS_GetObjectIDByName("DENON Scripts", $DENON_Kat_ID);
$DENON_ActionScript_ID = IPS_GetScriptIDByName("DENON.ActionScript", $DENON_Scripts_ID);

// Cursor Up & VarProfil anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorUp", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
	{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorUp");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Cursor_ID);
	}

	if (IPS_VariableProfileExists("DENON.CursorUP") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorUP", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorUP", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorUP", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorUP", 0, " UP ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorUP erstellt;\n";
	}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorUP"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("UP", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
	    	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "UP");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 10);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "UP");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 10);
		}
   @IPS_ApplyChanges($DENON_Cursor_ID);
	echo "Variable CursorUp #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

// Cursor Down anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorDown", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
	{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorDown");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Cursor_ID);
		IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);
	}

	if (IPS_VariableProfileExists("DENON.CursorDOWN") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorDOWN", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorDOWN", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorDOWN", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorDOWN", 0, "DOWN", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorDOWN erstellt;\n";
	}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorDOWN"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("DOWN", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
	    	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "DOWN");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 40);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "DOWN");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 40);
		}
	@IPS_ApplyChanges($DENON_Cursor_ID);
	echo "Variable CursorDown #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

// Cursor Left anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorLeft", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
	{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorLeft");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Cursor_ID);
	}

	if (IPS_VariableProfileExists("DENON.CursorLEFT") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorLEFT", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorLEFT", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorLEFT", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorLEFT", 0, "LEFT", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorLEFT erstellt; ";
	}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorLEFT"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("LEFT", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
	    	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "LEFT");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 20);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "LEFT");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 20);
		}
	@IPS_ApplyChanges($DENON_Cursor_ID);
	echo "Variable CursorLEFT #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

// Cursor Right anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorRight", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
	{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorRight");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Cursor_ID);
		IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);
	}

	if (IPS_VariableProfileExists("DENON.CursorRIGHT") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorRIGHT", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorRIGHT", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorRIGHT", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorRIGHT", 0, "RIGHT", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorRIGHT erstellt;\n";
	}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorRIGHT"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("RIGHT", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
	    	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "RIGHT");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 30);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "RIGHT");
			IPS_SetLinkChildID($LinkID, $DENON_Cursor_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 30);
		}
	@IPS_ApplyChanges($DENON_Cursor_ID);
	echo "Variable CursorRight #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

// Enter anlegen wenn nicht vorhanden
	$DENON_Enter_ID = @IPS_GetVariableIDByName("Enter", $DENON_Steuerung_ID);
	if ($DENON_Enter_ID == false)
	{
		$DENON_Enter_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Enter_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Enter_ID, "Enter");
		IPS_SetInfo($DENON_Enter_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Enter_ID);
		IPS_SetVariableCustomAction($DENON_Enter_ID, $DENON_ActionScript_ID);
	}

	if (IPS_VariableProfileExists("DENON.ENTER") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.ENTER", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.ENTER", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.ENTER", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.ENTER", 0, "ENTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.ENTER erstellt;\n";
	}

	IPS_SetVariableCustomProfile($DENON_Enter_ID, "DENON.ENTER"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Enter_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("ENTER", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
	    	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "ENTER");
			IPS_SetLinkChildID($LinkID, $DENON_Enter_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 50);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "ENTER");
			IPS_SetLinkChildID($LinkID, $DENON_Enter_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 50);
		}
 	@IPS_ApplyChanges($DENON_Enter_ID);
	echo "Variable Enter #$DENON_Enter_ID in Kategorie DENON angelegt\n";

// Return anlegen wenn nicht vorhanden
	$DENON_Return_ID = @IPS_GetVariableIDByName("Return", $DENON_Steuerung_ID);
	if ($DENON_Return_ID == false)
	{
		$DENON_Return_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Return_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Return_ID, "Return");
		IPS_SetInfo($DENON_Return_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_ApplyChanges($DENON_Return_ID);
		IPS_SetVariableCustomProfile($DENON_Return_ID, "DENON.Cursor"); // Ziel-ID, P-Name
		IPS_SetVariableCustomAction($DENON_Return_ID, $DENON_ActionScript_ID);
	}

	if (IPS_VariableProfileExists("DENON.RETURN") == false)
	{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.RETURN", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.RETURN", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.RETURN", 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.RETURN", 0, "RETURN", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.RETURN erstellt;\n";
	}

	IPS_SetVariableCustomProfile($DENON_Return_ID, "DENON.RETURN"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Return_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("RETURN", $DENON_SteuerungWFE_ID);
		$LinkChildID = IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["LinkChildID"];

		if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
		 	$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "RETURN");
			IPS_SetLinkChildID($LinkID, $DENON_Return_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 60);
		}
		elseif ($LinkChildID != $DENON_Cursor_ID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
			IPS_DeleteLink($LinkID);
			$LinkID = IPS_CreateLink();
			IPS_SetName($LinkID, "RETURN");
			IPS_SetLinkChildID($LinkID, $DENON_Return_ID);
			IPS_SetParent($LinkID, $DENON_SteuerungWFE_ID);
			IPS_SetPosition($LinkID, 60);
		}
	@IPS_ApplyChanges($DENON_Enter_ID);
	echo "Variable Return #$DENON_Return_ID in Kategorie DENON angelegt\n";


################## Installation abgeschlossen ##################################
echo "\nInstallation abgeschlossen\n\nwww.raketenschnecke.net";

################## Function Script install #####################################
// Quelle: hirschbrat, http://www.ip-symcon.de/forum/f52/ips_createscript-9659/
function CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT)
{
	global $IPS_SELF;
	$ScriptID = @IPS_GetScriptIDByName($ScriptNAME, $ParentID); // altes Script löschen wenn vorhanden, neues anlegen
	if ($ScriptID > 0)
	{
		IPS_DeleteScript($ScriptID, true);
		$ScriptID = IPS_CreateScript(0);
  		IPS_SetName($ScriptID, $ScriptNAME);
		IPS_SetParent($ScriptID, $ParentID);
		IPS_SetInfo($ScriptID, "This script was created by: DENON.Installer ID #$IPS_SELF#");
		IPS_SetHidden($ScriptID, true);
		$fh = fopen(IPS_GetKernelDir()."scripts\\".$ScriptID.".ips.php", 'w') or die("can't open file");
		fwrite($fh, $ScriptTEXT);
		fclose($fh);
		rename($ScriptID.".ips.php", $ScriptNAME.".ips.php");
		$ScriptPath = $ScriptNAME.".ips.php";
		IPS_SetScriptFile($ScriptID, $ScriptPath);
		echo "Script $ScriptNAME angelegt\n";
	}
	else
	{
		$ScriptID = IPS_CreateScript(0);
  		IPS_SetName($ScriptID, $ScriptNAME);
		IPS_SetParent($ScriptID, $ParentID);
		IPS_SetInfo($ScriptID, "This script was created by: DENON Installscript ID #$IPS_SELF#");
		IPS_SetHidden($ScriptID, true);
		$fh = fopen(IPS_GetKernelDir()."scripts\\".$ScriptID.".ips.php", 'w') or die("can't open file");
		fwrite($fh, $ScriptTEXT);
		fclose($fh);
		rename($ScriptID.".ips.php", $ScriptNAME.".ips.php");
		$ScriptPath = $ScriptNAME.".ips.php";
		IPS_SetScriptFile($ScriptID, $ScriptPath);
		echo "Script $ScriptNAME angelegt\n";
   }
}




?>
