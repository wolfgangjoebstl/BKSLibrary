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


// ++++ Modul2 DENON.VariablenManager ++++++++++


		//++++++++++++++++++ Script 'DENON.VariablenManager' +++++++++++++++++++++

		$ScriptNAME = 'DENON.VariablenManager';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------




############################ Info ##############################################
/*
Funktionen:
	*legt unterhalb der Kategorie "DENON" in den Dummy-Instanzen (Main Zone, Zone 2, Zone 3)
		eine Variable für die vom DENON empfangene Stausmeldung an sofern diese noch nicht vorhanden ist
	*legt unterhalb der Kategorie "DENON Webfrontend" in den jeweiligen Dummy-Instanzen
		einen Link zur DENON-Stausmeldung zur passenden Variable an. Existiert bereits
		ein Link mit dem selben Namen wird dieser gelöscht und neu angelegt
	* zuweisen des Actionscripts "DENON.Actionscript" zur Variable
		(Variablen-Eigenschaften "eigene Aktion"), wenn Variable bereits ein Profil zugewiesen bekommen hat
	* legt ein Variablenprofil zu der vom DENON empfangenen Stausmeldung an sofern
		dieses noch nicht vorhanden ist. Variablenprofile haben die Syntax "DENON.Variablenname".
		Vorhandene Profile werden nicht überschrieben.
	* weist das Variablenprofil der Variable zu falls noch nicht erfolgt

*/

############################ Info Ende #########################################


// ---------------------- Variablen-Management ----------------------------------

function DenonSetValue($item, $value, $vtype)
{
	// abhängig von DENON-Zone (Main, Zone 2, Zone 3) Parent ID für Variable und Link ermitteln
	$DENON_ID = IPS_GetCategoryIDByName("DENON", 0);
	$praefix =substr($item, 0, 5);
	if ($praefix == "Zone2") // wenn Präfix "Zone2"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1a: $VAR_Parent_ID";
	}
	elseif ($praefix == "Zone3")// wenn Präfix "Zone3"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
	}
	elseif ($praefix == "Displ")// wenn Präfix "Zone3"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Display", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Display", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
	}
	else // wenn Präfix nicht "Zone2", "Zone3" oder "Display"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1c: $VAR_Parent_ID";
	}

	// Definition div. Parent IDs

	$KAT_DENON_Scripts_ID = IPS_GetCategoryIDByName("DENON Scripts", $DENON_ID);
	$KAT_DENONWFE_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	$ScriptID = IPS_GetScriptIDByName("DENON.ActionScript", $KAT_DENON_Scripts_ID);

	// Bereinigung $item-Präfix (Displxxx)
	$item_praefix= substr($item,0,5); //item-Präfix
	if($item_praefix == "Displ")
	{
	   $item = rtrim(substr($item, 5, 95));
	}


   //Variable anlegen wenn nicht vorhanden
	$itemID = @IPS_GetVariableIDByName($item, $VAR_Parent_ID);
	if ($itemID == 0)
	{
	   // Variable anlegen
		$itemID= IPS_CreateVariable($vtype);
		IPS_SetName($itemID, $item);
		IPS_SetParent($itemID, $VAR_Parent_ID);
	}

	// DENON-Variablenprofil anlegen wenn nicht vorhanden
	$ProfileName = "DENON.".$item;

	if (IPS_VariableProfileExists($ProfileName)== false)
	{
		DENON_SetVarProfile($item, $itemID, $vtype);
	}

	// DENON-Variablenprofil zuweisen wenn nicht bereits zugewiesen
	$VarCustomProfileName = IPS_GetVariable($itemID);
	$VarCustomProfileName = $VarCustomProfileName["VariableCustomProfile"];

	if ($VarCustomProfileName != $ProfileName)
	{
		if (IPS_VariableProfileExists($ProfileName))
		{
		IPS_SetVariableCustomProfile($itemID, $ProfileName);
		echo "Script DENON VariablenManager Profil ".$ProfileName." zugewiesen ;";
		}
	}

	// Action-Script zuweisen wenn nicht bereits zugewiesen
	$VarActionscriptID = IPS_GetVariable($itemID);
	$VarActionscriptID = $VarActionscriptID["VariableCustomAction"];
   $VarCustomProfileName = IPS_GetVariable($itemID);
	$VarCustomProfileName = $VarCustomProfileName["VariableCustomProfile"];

   If ($VarCustomProfileName == $ProfileName)
   {
		if ($VarActionscriptID != $ScriptID)
		{
			IPS_SetVariableCustomAction($itemID, $ScriptID);
		}
	}

	// Link anlegen/zuweisen
	$LinkID = @IPS_GetLinkIDByName($item, $LINK_Parent_ID);
	$LinkChildID = @IPS_GetLink($LinkID);
	$LinkChildID = $LinkChildID["LinkChildID"];

	if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
	{
    	$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
	}
	elseif ($LinkChildID != $itemID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
	{
		IPS_DeleteLink($LinkID);
		$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
	}

	// Variablen-Wert updaten
	switch($vtype)
	{
		case 0: //Variablen-Typ Boolean
			SetValueBoolean($itemID, $value);
		break;

		case 1: //Variablen-Typ Integer
			SetValueInteger($itemID, $value);
		break;

		case 2: //Variablen-Typ Float
			SetValueFloat($itemID, $value);
		break;

		case 3: //Variablen-Typ String
			SetValueString($itemID, $value);
		break;
	}
}

// ------------------- Variablen Profil-Managenet ------------------------------
// Funktion erstellt für DENON-Variablen gleichnamige Variablenprofile mit Namenspräfix "DENON."
// übergeben werden muss Variablenname ($item), Variablen-ID ($itemID) und Variablentyp ($vtype)

function DENON_SetVarProfile($item, $itemID, $vtype)
{

	switch ($item)
	{
		case "Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainZonePower":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "AudioDelay":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 200, 1);  //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "ms"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MasterVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80.0, 18.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "LFELevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -10.0, 0.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainZone":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainMute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS 1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS 2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS 3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS 4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS 5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Sleep":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 120, 10); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DigitalInputMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "PCM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "DTS", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 14, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "DIRECT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "PURE DIRECT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "STEREO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "STANDARD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "DOLBY DIGITAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "DTS SURROUND", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "DOLBY PL2X C", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "MCH STEREO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "ROCK ARENA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "JAZZ CLUB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "MONO MOVIE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "MATRIX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "VIDEO GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "VIRTUAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
				IPS_SetVariableProfileAssociation($ProfileName, 14, "MULTI CH IN 7.1", "", -1); //P-Name, Value, Assotiation, Icon
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundPlayMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "CINEMA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "MUSIC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MultiEQMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "AUDYSSEY", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "BYP.LR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "FLAT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "MANUAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MasterVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80.0, 18.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", " %"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "AudioRestorer":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Restorer 64", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Restorer 96", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Restorer HQ", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "BassLevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -6, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "TrebleLevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -6, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "InputMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "HDMI", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "DIGITAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "ANALOG", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "CinemaEQ":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Dimension":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Panorama":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "FrontHeight":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Midnight", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Evening", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Day", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "RoomSize":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Neutral", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Small", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Small/Medium", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Medium", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "Medium/Large", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "Large", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicCompressor":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "LOW", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MID", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "HIGH", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ToneCTRL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "RoomSize":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "N", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "S", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MS", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "M", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "ML", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicEQ":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "C.Width":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 7, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicRange":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "LOW", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "MID", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "HI", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "VideoSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 8, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "SOURCE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundBackMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 7, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MTRX ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "PL2X CINEMA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "PL2X MUSIC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "ESDSCRT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "PESMTRX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DSCRT ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Preset":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "-empty-", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "-empty-", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}

		break;

		case "ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1.0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeC":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSW":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSBL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSBR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSB":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFHL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFHR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFWL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFWR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		################# Zone 2 #################################################
		case "Zone2Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2Volume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80, 18, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2Mute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelSetting":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Stereo", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Mono", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;


		################# Zone 3 #################################################
		case "Zone3Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3Volume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80, 18, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3Mute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelSetting":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Stereo", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Mono", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		//default: wenn keine Bedingung erfüllt ist
		default:
		// echo "kein neues DENON-Profil angelegt"; // zur Fehlersuche einkommentieren

	}

}

//------------- DENON Function Variablen Profile Manager -----------------------
// Funktion sschreibt in DENON-Variablenprofile die vom DENON übergebeben Werte
// übergeben werden muss Variablenname ($item), Assoziations-Position ($ProfilPosition) und Assoziationswert ($ProfilValue)

//
function DENON_SetProfileValue($item, $ProfilPosition, $ProfilValue)
{
	switch ($item)
	{
		case "Preset": // Schreibt Preset Info ins Profil "DENON.Preset"
			   $ProfileName = "DENON.".$item;
				if (IPS_VariableProfileExists($ProfileName) == false)
				{
				   echo "Variablenprofil $ProfileName nicht vorhanden!";
				}
				elseif (strlen($ProfilValue) > 0) // wenn in Profilassoziation Werte enthalten sind
				{
					IPS_SetVariableProfileAssociation($ProfileName, $ProfilPosition, $ProfilValue, "", -1); //P-Name, Value, Assotiation, Icon, Color
					// Preset-Variablenprofil dynamisch: Variablenprofil-Max-Wert entspricht den tatsächlich angelegten Presets im Receiver
					$Profil_maxCount =  IPS_GetVariableProfile($ProfileName);
					$Profil_maxCount =  count($Profil_maxCount"Associations"])-1);
					IPS_SetVariableProfileValues($ProfileName, 0, $Profil_maxCount, 1); //PName, Minimal, Maximal, Schrittweite
				}
		break;


	}
}



?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul3 DENON.ActionScript ++++++++++


		//++++++++++++++++++ Script 'DENON.ActionScript' +++++++++++++++++++++
		$ScriptNAME = 'DENON.ActionScript';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

############################ Info ##############################################
/*

Funktionen:
	*wird vom Script "DENON.Install_Library" in allen DENON-Variablen als Actionsript
		in den Variableneigenschaften eingetragen
	* sendet (WFE-)Kommandos an das DENON.Functions-Script
*/

############################ Info Ende #########################################

########################## Konfig ##############################################

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
########################## Konfig Ende #########################################



//if ($IPS_SENDER == "WebFront")
//{
	SetValue($IPS_VARIABLE, $IPS_VALUE);
	$VarName = IPS_GetName($IPS_VARIABLE);

	switch ($VarName)
	{
	   ############### Main Zone ################################################
		case "Power":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Power($id, "STANDBY");
			}
			else
			{
				DENON_Power($id, "ON");
			}
		break;

		case "DigitalInputMode":
         $DigitalInputMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DigitalInputMode($id, $DigitalInputMode_val);
		break;

		case "InputSource":
         $InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_InputSource($id, $InputSource_val);
		break;

		case "InputMode":
         $InputMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_InputMode($id, $InputMode_val);
		break;

		case "RoomSize":
			DENON_RoomSize($id, $IPS_VALUE);
		break;

		case "MainMute":
         $MainMute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MainMute($id, $MainMute_val);
		break;

		case "ToneCTRL":
         $ToneCTRL_val = GetValueFormatted($IPS_VARIABLE);
			DENON_ToneCTRL($id, $ToneCTRL_val);
		break;

		case "ToneDefeat":
         $ToneDefeat_val = GetValueFormatted($IPS_VARIABLE);
			DENON_ToneDefeat($id, $ToneDefeat_val);
		break;

		case "QuickSelect":
         $QuickSelect_val = GetValueInteger($IPS_VARIABLE);
			DENON_Quickselect($id, $QuickSelect_val);
		break;

		case "VideoSelect":
         $VideoSelect_val = GetValueFormatted($IPS_VARIABLE);
			DENON_VideoSelect($id, $VideoSelect_val);
		break;

		case "Panorama":
         $Panorama_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Panorama($id, $Panorama_val);
		break;

		case "FrontHeight":
         $FrontHeight_val = GetValueFormatted($IPS_VARIABLE);
			DENON_FrontHeight($id, $FrontHeight_val);
		break;

		case "BassLevel":
			DENON_BassLevel($id, $IPS_VALUE);
		break;

		case "LFELevel":
			DENON_LFELevel($id, $IPS_VALUE);
		break;

		case "TrebleLevel":
			DENON_TrebleLevel($id, $IPS_VALUE);
		break;

		case "DynamicEQ":
         $DynamicEQ_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicEQ($id, $DynamicEQ_val);
		break;

		case "DynamicCompressor":
         $DynamicCompressor_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "DynamicVolume":
         DENON_DynamicVolume($id, $IPS_VALUE);
		break;

		case "DynamicRange":
         $DynamicCompressor_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "AudioDelay":
			DENON_AudioDelay($id, $IPS_VALUE);
		break;

		case "AudioRestorer":
			DENON_AudioRestorer($id, $IPS_VALUE);
		break;

		case "MasterVolume":
			DENON_MasterVolumeFix($id, $IPS_VALUE);
		break;

		case "C.Width":
			DENON_CWidth($id, $IPS_VALUE);
		break;

		case "Dimension":
			DENON_Dimension($id, $IPS_VALUE);
		break;

		case "SurroundMode":
         $SurroundMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundMode($id, $SurroundMode_val);
		break;

		case "SurroundPlayMode":
         $SurroundPlayMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundPlayMode($id, $SurroundPlayMode_val);
		break;

		case "SurroundBackMode":
         $SurroundBackMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundBackMode($id, $SurroundBackMode_val);
		break;

		case "Sleep":
			DENON_Sleep($id, $IPS_VALUE);
		break;

		case "CinemaEQ":
         $CinemaEQ_val = GetValueFormatted($IPS_VARIABLE);
			DENON_CinemaEQ($id, $CinemaEQ_val);
		break;

		case "MainZonePower":
			$MainZonePower_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MainZonePower($id, $MainZonePower_val);
		break;

		case "MultiEQMode":
         $MultiEQMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MultiEQMode($id, $MultiEQMode_val);
		break;

		case "Preset":
         $Preset_val = GetValueInteger($IPS_VARIABLE);
			DENON_Preset($id, $Preset_val);
		break;

		case "ChannelVolumeFL":
			DENON_ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFR":
			DENON_ChannelVolumeFR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeC":
			DENON_ChannelVolumeC($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSW":
			DENON_ChannelVolumeSW($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSL":
			DENON_ChannelVolumeSL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSR":
			DENON_ChannelVolumeSR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSBL":
			DENON_ChannelVolumeSBL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSBR":
			DENON_ChannelVolumeSBR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSB":
			DENON_ChannelVolumeSB($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFHL":
			DENON_ChannelVolumeFHL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFHR":
			DENON_ChannelVolumeFHR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFWL":
			DENON_ChannelVolumeFWL($id, $IPS_VALUE);
		break;

      case "ChannelVolumeFWR":
			DENON_ChannelVolumeFWR($id, $IPS_VALUE);
		break;


		#################### Cursorsteuerung #####################################

		case "CursorUp":
			DENON_CursorUp($id);
		break;

		case "CursorDown":
			DENON_CursorDown($id);
		break;

		case "CursorLeft":
			DENON_CursorLeft($id);
		break;

		case "CursorRight":
			DENON_CursorRight($id);
		break;

		case "Enter":
			DENON_Enter($id);
		break;

		case "Return":
			DENON_Return($id);
		break;

		#################### Zone 2 ##############################################
      case "Zone2Power":
         $Zone2Power_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2Power($id, $Zone2Power_val);
		break;

		case "Zone2Volume":
			DENON_Zone2VolumeFix($id, $IPS_VALUE);
		break;

		case "Zone2Mute":
			$Zone2Mute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2Mute($id, $Zone2Mute_val);
		break;

		case "Zone2InputSource":
			$Zone2InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2InputSource($id, $Zone2InputSource_val);
		break;

		case "Zone2ChannelSetting":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Zone2ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone2ChannelSetting($id, "MONO");
			}
		break;

		case "Zone2ChannelVolumeFL":
			DENON_Zone2ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "Zone2ChannelVolumeFR":
			DENON_Zone2ChannelVolumeFL($id, $IPS_VALUE);
		break;


		#################### Zone 3 ##############################################
      case "Zone3Power":
         $Zone3Power_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3Power($id, $Zone3Power_val);
		break;

		case "Zone3Volume":
			DENON_Zone3VolumeFix($id, $IPS_VALUE);
		break;

		case "Zone3Mute":
			$Zone3Mute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3Mute($id, $Zone3Mute_val);
		break;

		case "Zone3InputSource":
			$Zone3InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3InputSource($id, $Zone3InputSource_val);
		break;

		case "Zone3ChannelSetting":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Zone3ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone3ChannelSetting($id, "MONO");
			}
		break;

		case "Zone3ChannelVolumeFL":
			DENON_Zone3ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "Zone3ChannelVolumeFR":
			DENON_Zone3ChannelVolumeFL($id, $IPS_VALUE);
		break;

	}
//}
?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul2 DENON.VariablenManager ++++++++++


		//++++++++++++++++++ Script 'DENON.VariablenManager' +++++++++++++++++++++

		$ScriptNAME = 'DENON.VariablenManager';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------




############################ Info ##############################################
/*
Funktionen:
	*legt unterhalb der Kategorie "DENON" in den Dummy-Instanzen (Main Zone, Zone 2, Zone 3)
		eine Variable für die vom DENON empfangene Stausmeldung an sofern diese noch nicht vorhanden ist
	*legt unterhalb der Kategorie "DENON Webfrontend" in den jeweiligen Dummy-Instanzen
		einen Link zur DENON-Stausmeldung zur passenden Variable an. Existiert bereits
		ein Link mit dem selben Namen wird dieser gelöscht und neu angelegt
	* zuweisen des Actionscripts "DENON.Actionscript" zur Variable
		(Variablen-Eigenschaften "eigene Aktion"), wenn Variable bereits ein Profil zugewiesen bekommen hat
	* legt ein Variablenprofil zu der vom DENON empfangenen Stausmeldung an sofern
		dieses noch nicht vorhanden ist. Variablenprofile haben die Syntax "DENON.Variablenname".
		Vorhandene Profile werden nicht überschrieben.
	* weist das Variablenprofil der Variable zu falls noch nicht erfolgt

*/

############################ Info Ende #########################################


// ---------------------- Variablen-Management ----------------------------------

function DenonSetValue($item, $value, $vtype)
{
	// abhängig von DENON-Zone (Main, Zone 2, Zone 3) Parent ID für Variable und Link ermitteln
	$DENON_ID = IPS_GetCategoryIDByName("DENON", 0);
	$praefix =substr($item, 0, 5);
	if ($praefix == "Zone2") // wenn Präfix "Zone2"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1a: $VAR_Parent_ID";
	}
	elseif ($praefix == "Zone3")// wenn Präfix "Zone3"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
	}
	elseif ($praefix == "Displ")// wenn Präfix "Zone3"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Display", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Display", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
	}
	else // wenn Präfix nicht "Zone2", "Zone3" oder "Display"
	{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName("DENON", 0);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1c: $VAR_Parent_ID";
	}

	// Definition div. Parent IDs

	$KAT_DENON_Scripts_ID = IPS_GetCategoryIDByName("DENON Scripts", $DENON_ID);
	$KAT_DENONWFE_ID = IPS_GetCategoryIDByName("DENON Webfront", $DENON_ID);
	$ScriptID = IPS_GetScriptIDByName("DENON.ActionScript", $KAT_DENON_Scripts_ID);

	// Bereinigung $item-Präfix (Displxxx)
	$item_praefix= substr($item,0,5); //item-Präfix
	if($item_praefix == "Displ")
	{
	   $item = rtrim(substr($item, 5, 95));
	}


   //Variable anlegen wenn nicht vorhanden
	$itemID = @IPS_GetVariableIDByName($item, $VAR_Parent_ID);
	if ($itemID == 0)
	{
	   // Variable anlegen
		$itemID= IPS_CreateVariable($vtype);
		IPS_SetName($itemID, $item);
		IPS_SetParent($itemID, $VAR_Parent_ID);
	}

	// DENON-Variablenprofil anlegen wenn nicht vorhanden
	$ProfileName = "DENON.".$item;

	if (IPS_VariableProfileExists($ProfileName)== false)
	{
		DENON_SetVarProfile($item, $itemID, $vtype);
	}

	// DENON-Variablenprofil zuweisen wenn nicht bereits zugewiesen
	$VarCustomProfileName = IPS_GetVariable($itemID);
	$VarCustomProfileName = $VarCustomProfileName["VariableCustomProfile"];

	if ($VarCustomProfileName != $ProfileName)
	{
		if (IPS_VariableProfileExists($ProfileName))
		{
		IPS_SetVariableCustomProfile($itemID, $ProfileName);
		echo "Script DENON VariablenManager Profil ".$ProfileName." zugewiesen ;";
		}
	}

	// Action-Script zuweisen wenn nicht bereits zugewiesen
	$VarActionscriptID = IPS_GetVariable($itemID);
	$VarActionscriptID = $VarActionscriptID["VariableCustomAction"];
   $VarCustomProfileName = IPS_GetVariable($itemID);
	$VarCustomProfileName = $VarCustomProfileName["VariableCustomProfile"];

   If ($VarCustomProfileName == $ProfileName)
   {
		if ($VarActionscriptID != $ScriptID)
		{
			IPS_SetVariableCustomAction($itemID, $ScriptID);
		}
	}

	// Link anlegen/zuweisen
	$LinkID = @IPS_GetLinkIDByName($item, $LINK_Parent_ID);
	$LinkChildID = @IPS_GetLink($LinkID);
	$LinkChildID = $LinkChildID["LinkChildID"];

	if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
	{
    	$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
	}
	elseif ($LinkChildID != $itemID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
	{
		IPS_DeleteLink($LinkID);
		$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
	}

	// Variablen-Wert updaten
	switch($vtype)
	{
		case 0: //Variablen-Typ Boolean
			SetValueBoolean($itemID, $value);
		break;

		case 1: //Variablen-Typ Integer
			SetValueInteger($itemID, $value);
		break;

		case 2: //Variablen-Typ Float
			SetValueFloat($itemID, $value);
		break;

		case 3: //Variablen-Typ String
			SetValueString($itemID, $value);
		break;
	}
}

// ------------------- Variablen Profil-Managenet ------------------------------
// Funktion erstellt für DENON-Variablen gleichnamige Variablenprofile mit Namenspräfix "DENON."
// übergeben werden muss Variablenname ($item), Variablen-ID ($itemID) und Variablentyp ($vtype)

function DENON_SetVarProfile($item, $itemID, $vtype)
{

	switch ($item)
	{
		case "Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainZonePower":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "AudioDelay":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 200, 1);  //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "ms"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MasterVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80.0, 18.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "LFELevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -10.0, 0.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainZone":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MainMute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS 1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS 2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS 3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS 4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS 5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Sleep":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 120, 10); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DigitalInputMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "PCM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "DTS", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 14, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "DIRECT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "PURE DIRECT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "STEREO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "STANDARD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "DOLBY DIGITAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "DTS SURROUND", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "DOLBY PL2X C", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "MCH STEREO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "ROCK ARENA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "JAZZ CLUB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "MONO MOVIE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "MATRIX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "VIDEO GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "VIRTUAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
				IPS_SetVariableProfileAssociation($ProfileName, 14, "MULTI CH IN 7.1", "", -1); //P-Name, Value, Assotiation, Icon
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundPlayMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "CINEMA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "MUSIC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MultiEQMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "AUDYSSEY", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "BYP.LR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "FLAT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "MANUAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "MasterVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80.0, 18.0, 0.5); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", " %"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "AudioRestorer":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Restorer 64", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Restorer 96", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Restorer HQ", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "BassLevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -6, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "TrebleLevel":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -6, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "InputMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "HDMI", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "DIGITAL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "ANALOG", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "CinemaEQ":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Dimension":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
				//Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Panorama":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "FrontHeight":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicVolume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Midnight", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Evening", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Day", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "RoomSize":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Neutral", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Small", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "Small/Medium", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "Medium", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "Medium/Large", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "Large", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicCompressor":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "LOW", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MID", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "HIGH", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ToneCTRL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "RoomSize":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "N", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "S", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MS", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "M", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "ML", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicEQ":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "C.Width":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 7, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "DynamicRange":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "AUTO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "LOW", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "MID", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "HI", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "VideoSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 8, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "SOURCE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "SurroundBackMode":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 7, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "MTRX ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "PL2X CINEMA", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "PL2X MUSIC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "ESDSCRT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "PESMTRX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DSCRT ON", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Preset":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "-empty-", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "-empty-", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}

		break;

		case "ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1.0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeC":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSW":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSBL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSBR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeSB":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFHL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFHR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFWL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "ChannelVolumeFWR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1.0); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		################# Zone 2 #################################################
		case "Zone2Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2Volume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80, 18, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2Mute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelSetting":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Stereo", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Mono", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone2QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;


		################# Zone 3 #################################################
		case "Zone3Power":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3Volume":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -80, 18, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "%"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3Mute":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280 /*[Objekt #65280 existiert nicht]*/); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 16711680); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 19, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "PHONO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "CD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "DVD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "BD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "TV", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 6, "SAT/CBL", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 7, "DVR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "GAME", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 9, "V.AUX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 10, "DOCK", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 11, "IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 12, "NET/USB", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 13, "NAPSTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 14, "LASTFM", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 15, "FLICKR", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 16, "FAVORITES", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 17, "IRADIO", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 18, "SERVER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 19, "USB/IPOD", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelSetting":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "Stereo", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "Mono", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelVolumeFL":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3ChannelVolumeFR":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
			   IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 1); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, -12, 12, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileText($ProfileName, "", "dB"); // Pname, Präfix, Suffix
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		case "Zone3QuickSelect":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 4, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "NONE", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "QS1", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "QS2", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "QS3", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "QS4", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 5, "QS5", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
			}
		break;

		//default: wenn keine Bedingung erfüllt ist
		default:
		// echo "kein neues DENON-Profil angelegt"; // zur Fehlersuche einkommentieren

	}

}

//------------- DENON Function Variablen Profile Manager -----------------------
// Funktion sschreibt in DENON-Variablenprofile die vom DENON übergebeben Werte
// übergeben werden muss Variablenname ($item), Assoziations-Position ($ProfilPosition) und Assoziationswert ($ProfilValue)

//
function DENON_SetProfileValue($item, $ProfilPosition, $ProfilValue)
{
	switch ($item)
	{
		case "Preset": // Schreibt Preset Info ins Profil "DENON.Preset"
			   $ProfileName = "DENON.".$item;
				if (IPS_VariableProfileExists($ProfileName) == false)
				{
				   echo "Variablenprofil $ProfileName nicht vorhanden!";
				}
				elseif (strlen($ProfilValue) > 0) // wenn in Profilassoziation Werte enthalten sind
				{
					IPS_SetVariableProfileAssociation($ProfileName, $ProfilPosition, $ProfilValue, "", -1); //P-Name, Value, Assotiation, Icon, Color
					// Preset-Variablenprofil dynamisch: Variablenprofil-Max-Wert entspricht den tatsächlich angelegten Presets im Receiver
					$Profil_maxCount =  IPS_GetVariableProfile($ProfileName);
					$Profil_maxCount =  count($Profil_maxCount["Associations"]);
					IPS_SetVariableProfileValues($ProfileName, 0, $Profil_maxCount, 1); //PName, Minimal, Maximal, Schrittweite
				}
		break;


	}
}



?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul4 DENON.CommandManager ++++++++++


		//++++++++++++++++++ Script 'DENON.CommandManager' +++++++++++++++++++++
		$ScriptNAME = 'DENON.CommandManager';
		$D_Cutter_ID = @IPS_GetObjectIDByName("DENON Cutter", 0);
		$ParentID = @IPS_GetObjectIDByName("DENON Register Variable", $D_Cutter_ID);
		$ScriptTEXT = '<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------



############################ Info ##############################################
/*
Inital-Autor: philipp, Quelle: http://www.ip-symcon.de/forum/f53/denon-avr-3808-integration-7007/

Funktionen:
	* liest und interpretiert die vom DENON empfangenen Statusmeldungen und
		übergibt diese zur Veiterverarbeitung an das Script "DENON.VariablenManager"

*/

############################ Info Ende #########################################

############################# Konfig ###########################################
$Denon_KatID = IPS_GetCategoryIDByName("DENON", 0);
$DENON_Scripts_ID = @IPS_GetCategoryIDByName("DENON Scripts", $Denon_KatID);
if (IPS_GetObjectIDByName("DENON.VariablenManager", $DENON_Scripts_ID) >0)
{
	include "DENON.VariablenManager.ips.php";
}
else
{
	echo "Script DENON.VariablenManager kann nicht gefunden werden!";
}

############################# Konfig Ende ######################################

$data=$IPS_VALUE;

$maincat= substr($data,0,2); //Eventidentifikation
$zonecat= substr($data,2); //Zoneneventidentifikation
switch($maincat)
{
	case "PW": //MainPower
		$item = "Power";
		$vtype = 0;
		if ($data == "PWON")
		{
			$value = true;
		}
		if ($data == "PWSTANDBY")
		{
			$value = false;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "MV": //Mastervolume
		if (substr($data,2,3) =="MAX")
		{
		}
		else
		{
			$item = "MasterVolume";
			$vtype = 2;
			$itemdata=substr($data,2);
		if ( $itemdata == "99")
		{
			$value = "";
		}
		else
		{
			$itemdata= str_pad ( $itemdata, 3, "0" );
			$value = (intval($itemdata)/10) -80;
		}
		DenonSetValue($item, $value, $vtype);
		}
	 break;

	case "MU": //MainMute
		$item = "MainMute";
		$vtype = 0;
		if ($data == "MUON")
		{
			$value = true;
		}
		if ($data == "MUOFF")
		{
			$value = false;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "ZM": //MainZone
		$item = "MainZonePower";
		$vtype = 0;
		if ($data == "ZMON")
		{
			$value = true;
		}
		if ($data == "ZMOFF")
		{
			$value = false;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "SI": //Source Input
		$item = "InputSource";
		$vtype = 1;
		if ($data == "SIPHONO")
		{
			$value = 0;
		}
		elseif ($data == "SICD")
		{
			$value = 1;
		}
		elseif ($data == "SITUNER")
		{
			$value = 2;
		}
		elseif ($data == "SIDVD")
		{
			$value = 3;
		}
		elseif ($data == "SIBD")
		{
			$value = 4;
		}
		elseif ($data == "SITV")
		{
			$value = 5;
		}
		elseif ($data == "SISAT/CBL")
		{
			$value = 6;
		}
		elseif ($data == "SIDVR")
		{
			$value = 7;
		}
		elseif ($data == "SIGAME")
		{
			$value = 8;
		}
		elseif ($data == "SIV.AUX")
		{
			$value = 9;
		}
		elseif ($data == "SIDOCK")
		{
			$value = 10;
		}
		elseif ($data == "SIIPOD")
		{
			$value = 11;
		}
		elseif ($data == "SINET/USB")
		{
			$value = 12;
		}
		elseif ($data == "SINAPSTER")
		{
			$value = 13;
		}
		elseif ($data == "SILASTFM")
		{
			$value = 14;
		}
		elseif ($data == "SIFLICKR")
		{
			$value = 15;
		}
		elseif ($data == "SIFAVORITES")
		{
			$value = 16;
		}
		elseif ($data == "SIIRADIO")
		{
			$value = 17;
		}
		elseif ($data == "SISERVER")
		{
			$value = 18;
		}
		elseif ($data == "SIUSB/IPOD")
		{
			$value = 19;
		}
		$value = intval($value);
		DenonSetValue($item, $value, $vtype);
	break;

	case "SV": //Video Select
		$item = "VideoSelect";
		$vtype = 1;
		if ($data == "SVDVD")
		{
			$value = 0;
		}
		elseif ($data == "SVBD")
		{
			$value = 1;
		}
		elseif ($data == "SVTV")
		{
			$value = 2;
		}
		elseif ($data == "SVSAT/CBL")
		{
			$value = 3;
		}
		elseif ($data == "SVDVR")
		{
			$value = 4;
		}
		elseif ($data == "SVGAME")
		{
			$value = 5;
		}
		elseif ($data == "SVV.AUX")
		{
			$value = 6;
		}
		elseif ($data == "SVDOCK")
		{
			$value = 7;
		}
		elseif ($data == "SVSOURCE")
		{
			$value = 8;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "MS": // Surround Mode und Quickselect
		if (substr($data,0,7) == "MSQUICK")
		{
			//Quickselect
			$item = "QuickSelect";
			$vtype = 1;
			if (substr($data,0,7) == "MSQUICK")
			{
				$value = intval(substr($data,7,1));
			}
			DenonSetValue($item, $value, $vtype);
		}
		else
		{
			//Surround Mode
			$item = "SurroundMode";
			$vtype = 1;
			if ($data == "MSDIRECT")
			{
				$value = 0;
			}
			elseif ($data == "MSPURE DIRECT")
			{
				$value = 1;
			}
			elseif ($data == "MSSTEREO")
			{
				$value = 2;
			}
			elseif ($data == "MSSTANDARD")
			{
				$value = 3;
			}
			elseif ($data == "MSDOLBY DIGITAL")
			{
				$value = 4;
			}
			elseif ($data == "MSDTS SURROUND")
			{
				$value = 5;
			}
			elseif ($data == "MSDOLBY PL2X C")
			{
				$value = 6;
			}
			elseif ($data == "MSMCH STEREO")
			{
				$value = 7;
			}
			elseif ($data == "MSROCK ARENA")
			{
				$value = 8;
			}
			elseif ($data == "MSJAZZ CLUB")
			{
				$value = 9;
			}
			elseif ($data == "MSMONO MOVIE")
			{
				$value = 10;
			}
			elseif ($data == "MSMATRIX")
			{
				$value = 11;
			}
			elseif ($data == "MSVIDEO GAME")
			{
				$value = 12;
			}
			elseif ($data == "MSVIRTUAL")
			{
				$value = 13;
			}
			elseif ($data == "MSMULTI CH IN 7.1")
			{
				$value = 14;
			}
			DenonSetValue($item, $value, $vtype);
		}
	break;

	case "DC": //Digital Input Mode
		$item = "DigitalInputMode";
		$vtype = 1;
		if ($data == "DCAUTO")
		{
			$value = 0;
		}
		elseif ($data == "DCPCM")
		{
			$value = 1;
		}
		elseif ($data == "DCDTS")
		{
			$value = 2;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "SD": //Input Mode AUTO/HDMI/DIGITALANALOG/ARC/NO
		$item = "InputMode";
		$vtype = 1;
		if ($data == "SDAUTO")
		{
			$value = 0;
		}
		elseif ($data == "SDHDMI")
		{
			$value = 1;
		}
		elseif ($data == "SDDIGITAL")
		{
			$value = 2;
		}
		elseif ($data == "DCANALOG")
		{
			$value = 3;
		}
		DenonSetValue($item, $value, $vtype);
	break;

	case "SR": //Record Selection
		$item = "RecordSelection";
		$vtype = 1;
		$itemdata=substr($data,2);
		$value = $itemdata;
		DenonSetValue($item, $value, $vtype);
	break;

	case "SL": //Main Zone Sleep
		$item = "Sleep";
		$vtype = 1;
		if ($data == "SLPOFF")
		{
			$itemdata = 0;
		}
		else
		{
			$itemdata = substr($data,3,3);
		}
		$value = intval($itemdata);
		DenonSetValue($item, $value, $vtype);
	break;

	case "VS": //Videosignal
		$vssub=substr($data,2,2);
		switch($vssub)
		{
		case "MO": //HDMI Monitor
			$item = "HDMIMonitor";
			$vtype = 3;
			$itemdata=substr($data,5);
			$value = $itemdata;
			DenonSetValue($item, $value, $vtype);
		break;

		case "AS": //Video Aspect
			$item = "VideoAspect";
			$vtype = 0;
			if ($data == "VSASPFUL")
			{
				$value = true;
			}
			elseif ($data == "VSASPNRM")
			{
				$value = false;
			}
			DenonSetValue($item, $value, $vtype);
		break;

		case "SC": //Scaler
			$item = "Scaler";
			$vtype = 3;
			$itemdata=substr($data,4);
			$value = $itemdata;
			DenonSetValue($item, $value, $vtype);
		break;
		}
	break;

	case "PS": //Sound
		$pssub=substr($data,2,2);
		switch($pssub)
		{
			case "TO": //Tone Defeat/Tone Control
				$pssubsub=substr($data,7,2);
				switch($pssubsub)
				{
				case "CT": //Tone Control (AVR 3311)
					$item = "ToneCTRL";
					$vtype = 0;
					if ($data == "PSTONE CTRL ON")
					{
						$value = true;
					}
					elseif ($data == "PSTONE CTRL OFF")
					{
						$value = false;
					}
					DenonSetValue($item, $value, $vtype);
				break;

				case "DE": //Tone Defeat (AVR 3808)
					$item = "ToneDefeat";
					$vtype = 0;
					if ($data == "PSTONE DEFEAT ON")
					{
						$value = true;
					}
					elseif ($data == "PSTONE DEFEAT ON")
					{
						$value = false;
					}
					DenonSetValue($item, $value, $vtype);
				break;
				}
			break;

			case "FH": // Front Height ON/OFF
				$item = "FrontHeight";
				$vtype = 0;
				if ($data == "PSFH:ON")
				{
					$value = true;
				}
				if ($data == "PSFH:OFF")
				{
					$value = false;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "CI": //Cinema EQ
				$item = "CinemaEQ";
				$vtype = 0;
				if ($data == "PSCINEMA EQ.ON")
				{
					$value = true;
				}
				if ($data == "PSCINEMA EQ.OFF")
				{
					$value = false;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "RO": //Room EQ Mode
				$item = "RoomEQMode";
				$vtype = 3;
				$itemdata=substr($data,10);
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DC": //Dynamic Compressor
				$item = "DynamicCompressor";
				$vtype = 1;
				if ($data == "PSDCO OFF")
				{
					$value = 0;
				}
				elseif ($data == "PSDCO LOW")
				{
					$value = 1;
				}
				elseif ($data == "PSDCO MID")
				{
					$value = 2;
				}
				elseif ($data == "PSDCO HIGH")
				{
					$value = 3;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "PA": //Verteilung Front-Signal auf Surround-Kanäle
				$item = "Panorama";
				$vtype = 0;
				if ($data == "PSPAN ON")
				{
					$value = true;
				}
				elseif ($data == "PSPAN OFF")
				{
					$value = false;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "DI": //Balance zwischen Front und Surround-LS
				$item = "Dimension";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "CE": //Center-Signal Verteilung auf FrontR/L
				$item = "C.Width";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (int)$itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SB": //Surround-Back ON/OFF
				$item = "SurroundBackMode";
				$vtype = 1;
				if ($data == "PSSB:OFF")
				{
					$value = 0;
				}
				elseif ($data == "PSSB:ON")
				{
					$value = 1;
				}
				elseif ($data == "PSSB:MRTX ON")
				{
					$value = 2;
				}
				elseif ($data == "PSSB:PL2X CINEMA")
				{
					$value = 3;
				}
				elseif ($data == "PSSB:PL2X MUSIC")
				{
					$value = 4;
				}
				elseif ($data == "PSSB:ESDSCRT")
				{
					$value = 5;
				}
				elseif ($data == "PSSB:ESMRTX")
				{
					$value = 6;
				}
				elseif ($data == "PSSB:DSCRT ON")
				{
					$value = 7;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "MO": //Surround-Spielmodi für Surround-Mode
				$item = "SurroundPlayMode";
				$vtype = 1;
				if ($data == "PSMODE:CINEMA")
				{
					$value = 0;
				}
				elseif ($data == "PSMODE:MUSIC")
				{
					$value = 1;
				}
				elseif ($data == "PSMODE:GAME")
				{
					$value = 2;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "MU": //MultEQ XT mode
				$item = "MultiEQMode";
				$vtype = 1;
				if ($data == "PSMULTEQ:OFF")
				{
					$value = 0;
				}
				elseif ($data == "PSMULTEQ:AUDYSSEY")
				{
					$value = 1;
				}
				elseif ($data == "PSMULTEQ:BYP.LR")
				{
					$value = 2;
				}
				elseif ($data == "PSMULTEQ:FLAT")
				{
					$value = 3;
				}
				elseif ($data == "PSMULTEQ:MANUAL")
				{
					$value = 4;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "DY": //Sound
				$pssubsub=substr($data,4,2);
				switch($pssubsub)
				{
					case "NE": //Dynamic Equalizer ON/OFF
						$item = "DynamicEQ";
						$vtype = 0;
						if ($data == "PSDYNEQ ON")
						{
							$value = true;
						}
						elseif ($data == "PSDYNEQ OFF")
						{
							$value = false;
						}
						DenonSetValue($item, $value, $vtype);
					break;

					case "NV": //Surround-Spielmodi für Surround-Mode
						$item = "DynamicVolume";
						$vtype = 1;
						if ($data == "PSDYNVOL OFF")
						{
							$value = 0;
						}
						if ($data == "PSDYNVOL NGT")
						{
							$value = 1;
						}
						elseif ($data == "PSDYNVOL EVE")
						{
							$value = 2;
						}
						elseif ($data == "PSDYNVOL DAY")
						{
							$value = 3;
						}
						DenonSetValue($item, $value, $vtype);
					break;
				}
			break;

			case "DR": //Dynamic Compressor
				$item = "DynamicRange";
				$vtype = 1;
				if ($data == "PSDRC OFF")
				{
					$value = 0;
				}
				elseif ($data == "PSDRC AUTO")
				{
					$value = 1;
				}
				elseif ($data == "PSDRC LOW")
				{
					$value = 2;
				}
				elseif ($data == "PSDRC MID")
				{
					$value = 3;
				}
				elseif ($data == "PSDRC HIGH")
				{
					$value = 4;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "LF": //LFE Pegel
				$item = "LFELevel";
				$vtype = 2;
				$itemdata=substr($data, 6, 2);
				$value = (0 - intval($itemdata));
				DenonSetValue($item, $value, $vtype);
			 break;

			case "BA": //Bass Pegel
				$item = "BassLevel";
				$vtype = 1;
				$itemdata=substr($data, 6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype);
			 break;

			case "TR": //Treble Pegel
				$item = "TrebleLevel";
				$vtype = 2;
				$itemdata=substr($data,6, 2);
				$value = (intval($itemdata)) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DE": //Audio Delay 0-200ms
				$item = "AudioDelay";
				$vtype = 1;
				$itemdata=substr($data,8, 3);
				$value = intval($itemdata);
				DenonSetValue($item, $value, $vtype);
			break;

			case "RS": //Tone Defeat/Tone Control
				$pssubsub1=substr($data,4,1);
				switch($pssubsub1)
				{
					case "T": //Surround-Spielmodi für Surround-Mode
						$item = "AudioRestorer";
						$vtype = 1;
						if ($data == "PSRSTR OFF")
						{
							$value = 0;
						}
						elseif ($data == "PSRSTR MODE1")
						{
							$value = 1;
						}
						elseif ($data == "PSRSTR MODE2")
						{
							$value = 2;
						}
						elseif ($data == "PSRSTR MODE3")
						{
							$value = 3;
						}
						DenonSetValue($item, $value, $vtype);
					break;

					case "Z": //RoomSize
						$item = "RoomSize";
						$vtype = 1;
						if ($data == "PSRSZ N")
						{
							$value = 0;
						}
						elseif ($data == "PSRSZ S")
						{
							$value = 1;
						}
						elseif ($data == "PSRSZ MS")
						{
							$value = 2;
						}
						elseif ($data == "PSRSZ M")
						{
							$value = 3;
						}
						elseif ($data == "PSRSZ ML")
						{
							$value = 4;
						}
						elseif ($data == "PSRSZ L")
						{
							$value = 5;
						}
						DenonSetValue($item, $value, $vtype);
					break;
				}
			break;
		}
	break;

	// Display
	case "NS": //NSE, NSA, NSH
		$vssub=substr($data,2,1);
		switch($vssub)
		{
			case "E": //Anzeige aktueller Titel
				$vssubE=substr($data,2,2);
				switch($vssubE)
				{
					case "E0": //Zeile 1
						$item = "DisplLine1";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E1": //Zeile 2
						$item = "DisplLine2";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E2": //Zeile 3
						$item = "DisplLine3";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E3": // Zeile 4
						$item = "DisplLine4";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E4": // Zeile 5
						$item = "DisplLine5";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E5": // Zeile 6
						$item = "DisplLine6";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E6": // Zeile 7
						$item = "DisplLine7";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E7": // Zeile 8
						$item = "DisplLine8";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "E8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
						$currentPosition = $itemdata = substr($data, 7, 1);
					break;
				}
			break;

			case "A": // Display NSA Zeilen 1-8
				$vssubA = substr($data,2,2);
				switch($vssubA)
				{
					case "A0": //Zeile 1
						$item = "DisplLine1";
						$vtype = 3;
						$itemdata = rtrim(substr($data, 4, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A1": //Zeile 2
						$item = "DisplLine2";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A2": //Zeile 3
						$item = "DisplLine3";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A3": // Zeile 4
						$item = "DisplLine4";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A4": // Zeile 5
						$item = "DisplLine5";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A5": // Zeile 6
						$item = "DisplLine6";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A6": // Zeile 7
						$item = "DisplLine7";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A7": // Zeile 8
						$item = "DisplLine8";
						$vtype = 3;
						if (substr($data, 4, 1) == "")
						{
							$itemdata = rtrim(substr($data, 5, 95));
						}
						else
						{
							$itemdata = "==> ".rtrim(substr($data, 4, 95));
						}
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
					break;

					case "A8": // Zeile 9
						$item = "Displcurrent Position";
						$vtype = 3;
						$itemdata = $ProfilValue = rtrim(substr($data, 5, 95));
						$value = $itemdata;
						DenonSetValue($item, $value, $vtype);
						$currentPosition = $itemdata = substr($data, 7, 1);
					break;
				}
			break;

			case "H": // Preset-Werte ins Variablenprofil "DENON.Preset" schreiben
				// Variable anlegen
				$item = "Preset";
				$vtype = 1;
				$itemdata=substr($data, 3, 2);
				$value = intval($itemdata);
				$ProfilPosition = $value;
				$ProfilValue = rtrim(substr($data, 5, 100));
            if (strlen($ProfilValue) > 0)
            {
					DenonSetValue($item, $value, $vtype); // Variablenwert setzen
					DENON_SetProfileValue($item, $ProfilPosition, $ProfilValue); // Werte ins Variablenprofil schreiben (nur wenn Preset mit Werten belegt)
				}
			break;
		}
	break;

	case "CV": //Zone 2 Channel Volume
		$CV_sub = substr($data,2,2);
		switch ($CV_sub)
		{
			case "FL":
				$item = "ChannelVolumeFL";
				$vtype = 2;
				$itemdata = substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "FR":
				$item = "ChannelVolumeFR";
				$vtype = 2;
				$itemdata = substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "C ":
				$item = "ChannelVolumeC";
				$vtype = 2;
				$itemdata=substr($data,4,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SW":
				$item = "ChannelVolumeSW";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SL":
				$item = "ChannelVolumeSL";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SR":
				$item = "ChannelVolumeSR";
				$vtype = 2;
				$itemdata=substr($data,5,3);
				$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
				$value = (intval($itemdata)/10) -50;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SB":
				$case = substr($data,2,3);
				if ($case == "SBL")
				{
					$item = "ChannelVolumeSBL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					echo "itemdata $itemdata /n";
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
					echo "SBL Wert = $value /n";
				}
				elseif ($case == "SBR")
				{
					$item = "ChannelVolumeSBR";
					$vtype = 2;
					$itemdata = substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
				elseif ($case == "SB ")
				{
					$item = "ChannelVolumeSB";
					$vtype = 2;
					$itemdata = substr($data,5,2);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
			break;

			case "FH":
				$case = $itemdata=substr($data,2,3);
				if ($case == "FHL")
				{
					$item = "ChannelVolumeFHL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
				elseif ($case == "FHR")
				{
					$item = "ChannelVolumeFHR";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
			break;

			case "FW":
				$case = $itemdata=substr($data,2,3);
				if ($case == "FWL")
				{
					$item = "ChannelVolumeFWL";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
				elseif ($case == "FWR")
				{
					$item = "ChannelVolumeFWR";
					$vtype = 2;
					$itemdata=substr($data,6,3);
					$itemdata = str_pad( $itemdata, 3, 0, STR_PAD_RIGHT);
					$value = (intval($itemdata)/10) -50;
					DenonSetValue($item, $value, $vtype);
				}
			break;
		}
	break;


############### Zone 2 #########################################################

	case "Z2":
	   if (intval($zonecat) <100 and intval($zonecat) >9)
		{
			$item = "Zone2Volume";
			$vtype = 1;
			$itemdata=substr($data,2,2);
			if ( $itemdata == "99")
			{
				$value = "";
			}
			else
			{
				$value = (intval($itemdata)) -80;

			}
			DenonSetValue($item, $value, $vtype);
		}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "CD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "BD": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "TV": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SAT/CBL": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "GAME": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DOCK": //Source Input Z3
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "IPOD": //Source Input Z3 (AVR 3809)
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "NET/USB":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 12;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "NAPSTER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "LASTFM":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 14;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "FLICKR":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 15;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "FAVORITES":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 16;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "IRADIO":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 17;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SERVER":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 18;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "USP/IPOD":
				$item = "Zone2InputSource";
				$vtype = 1;
				$itemdata= 19;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "OFF": //Zone 2 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone2Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;
		}


		$ZoneCat_sub = substr($data,2,2);
		switch ($ZoneCat_sub)
		{
			case "MU": //Zone 2 Mute ON/OFF
				$item = "Zone2Mute";
				$vtype = 0;
				if ($data == "Z2MUOFF")
				{
					$value = false;
				}
				elseif ($data == "Z2MUON")
				{
					$value = true;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "CS": //Zone 2 Channel Setting MONO/STEREO
				$item = "Zone2ChannelSetting";
				$vtype = 1;
				if ($data == "Z2CSST")
				{
					$value = 0;
				}
				elseif ($data == "Z2CSMONO")
				{
					$value = 1;
				}
			     DenonSetValue($item, $value, $vtype);
			break;

			case "CV": //Zone 2 Channel Volume
				$Z2CV_sub = substr($data,4,2);
				switch ($Z2CV_sub)
				{
					case "FL":
						$item = "Zone2ChannelVolumeFL";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype);
					break;

					case "FR":
						$item = "Zone2ChannelVolumeFR";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype);
					break;
				}
			break;

			case "QU": //Zone 2 Quick Select
				$item = "Zone2QuickSelect";
				$vtype = 1;
				if ($data == "Z2QUICK0")
					{
						$value = 0;
					}
					elseif ($data == "Z2QUICK1")
					{
						$value = 1;
					}
					elseif ($data == "Z2QUICK2")
					{
						$value = 2;
					}
					elseif ($data == "Z2QUICK3")
					{
						$value = 3;
					}
					elseif ($data == "Z2QUICK4")
					{
						$value = 4;
					}
					elseif ($data == "Z2QUICK5")
					{
						$value = 5;
					}
					$value = intval($value);
			     DenonSetValue($item, $value, $vtype);
			break;
		}
	break;

#################### Zone 3 ####################################################

	case "Z3": //Source Input
		if (intval($zonecat) <100 and intval($zonecat) >9)
		{
			$item = "Zone3Volume";
			$vtype = 1;
			$itemdata=substr($data,2,2);
			if ( $itemdata == "99")
			{
				$value = "";
			}
			else
			{
				$value = (intval($itemdata)) -80;
			}
			DenonSetValue($item, $value, $vtype);
		}

		switch ($zonecat)
		{
			case "PHONO": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 0;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "CD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 1;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "TUNER": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 2;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DVD": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 3;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "HDP": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 4;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "TV/CBL": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 5;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "SAT": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 6;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "VCR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 7;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "DVR": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 8;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "V.AUX": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 9;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "NET/USB": //Source Input Z3
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 10;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "USB": //Source Input Z3 (AVR 3809)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 11;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "USB/IPOD": //Source Input Z3 (AVR 3311)
				$item = "Zone3InputSource";
				$vtype = 1;
				$itemdata= 13;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "OFF": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= false;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;

			case "ON": //Zone 3 Power
				$item = "Zone3Power";
				$vtype = 0;
				$itemdata= true;
				$value = $itemdata;
				DenonSetValue($item, $value, $vtype);
			break;
		}

		$ZoneCat_sub = substr($data,2,2);
		switch ($ZoneCat_sub)
		{
			case "MU": //Zone 3 Mute ON/OFF
				$item = "Zone3Mute";
				$vtype = 0;
				if ($data == "Z3MUOFF")
				{
					$value = false;
				}
				elseif ($data == "Z3MUON")
				{
					$value = true;
				}
				DenonSetValue($item, $value, $vtype);
			break;

			case "CS": //Zone 3 Channel Setting MONO/STEREO
				 $item = "Zone3ChannelSetting";
				 $vtype = 1;
				 if ($data == "Z3CSST")
					{
						$value = 0;
					}
					elseif ($data == "Z3CSMONO")
					{
						$value = 1;
					}
				 DenonSetValue($item, $value, $vtype);
			break;

			case "CV": //Zone 3 Channel Volume
				$Z3CV_sub = substr($data,4,2);
				switch ($Z3CV_sub)
				{
					case "FL":
						$item = "Zone3ChannelVolumeFL";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype);
					break;

					case "FR":
						$item = "Zone3ChannelVolumeFR";
						$vtype = 1;
						$itemdata=substr($data,7,2);
						$value = intval($itemdata) -50;
						DenonSetValue($item, $value, $vtype);
					break;
				}
			break;

			case "QU": //Zone 3 Quick Select
				 $item = "Zone3QuickSelect";
				 $vtype = 1;
				 if ($data == "Z3QUICK0")
					{
						$value = 0;
					}
					elseif ($data == "Z3QUICK1")
					{
						$value = 1;
					}
					elseif ($data == "Z3QUICK2")
					{
						$value = 2;
					}
					elseif ($data == "Z3QUICK3")
					{
						$value = 3;
					}
					elseif ($data == "Z3QUICK4")
					{
						$value = 4;
					}
					elseif ($data == "Z3QUICK5")
					{
						$value = 5;
					}
					$value = intval($value);
					DenonSetValue($item, $value, $vtype);
			break;
		}
	break;
}

?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul5 DENON.Functions ++++++++++


		//++++++++++++++++++ Script 'DENON.Functions' +++++++++++++++++++++
		$ScriptNAME = 'DENON.Functions';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

############################ Info ##############################################
/*
Inital-Autor: philipp, Quelle: http://www.ip-symcon.de/forum/f53/denon-avr-3808-integration-7007/

Funktionen:
	*Funktionssammlung aller implementierten DENON-Status und Befehle
	*empängt die Steuerbefehle aus dem DENON.Actionscript,
		formatiert diese und sendt sie an den "DENON Client Socket"
*/

############################ Info Ende #########################################

############################ Konfig ############################################

$Denon_KatID = IPS_GetCategoryIDByName("DENON", 0);
if (IPS_GetObjectIDByName("DENON Client Socket", 0) >0)
{
	$id = IPS_GetObjectIDByName("DENON Client Socket", 0);
}
else
{
	echo "die ID des DENON Client Sockets kann nicht ermittelt werden/n ->
		Client Socket angelegt?/n Name richtig geschrieben (DENON Client Socket)?";
}
########################## Konfig Ende #########################################

######################### Main Functions #######################################

function DENON_POWER($id, $value) // STANDBY oder ON
{
 CSCK_SendText($id, "PW".$value.chr(13));
}

function DENON_MasterVolume($id, $value) // "UP" or "DOWN"
{
 CSCK_SendText($id, "MV".$value.chr(13));
}

function DENON_MasterVolumeFix($id, $value) // Volume direct -80(db) bis 18(db)
{
 $value= intval($value) +80;
 CSCK_SendText($id, "MV".$value.chr(13));
}

function DENON_BassLevel($id, $value)
{
	$value = (intval($value) +50);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSBAS ".$value.chr(13));
}

function DENON_LFELevel($id, $value)
{
	$value = (intval($value) +10);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSLFE ".$value.chr(13));
}

function DENON_TrebleLevel($id, $value)
{
	$value = (intval($value) +50);
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSTRE ".$value.chr(13));
}

function DENON_ChannelVolume($id, $value) // setzen Korrekturlevel pro LS-Kanal
{
 CSCK_SendText($id, "CV".$value.chr(13));
}

function DENON_MainMute($id, $value) // "ON" or "OFF"
{
 CSCK_SendText($id, "MU".$value.chr(13));
}

function DENON_Input($id, $value) // NET/USB; USB; NAPSTER; LASTFM; FLICKR; FAVORITES; IRADIO; SERVER; SERVER;  USB/IPOD
{
 CSCK_SendText($id, "SI".$value.chr(13));
}

function DENON_MainZonePower($id, $value) // MainZone "ON" or "OFF"
{
 CSCK_SendText($id, "ZM".$value.chr(13));
}

function DENON_RecSelect($id, $value) //
{
 CSCK_SendText($id, "SR".$value.chr(13)); // NET/USB; USB; NAPSTER; LASTFM; FLICKR; FAVORITES; IRADIO; SERVER; SERVER;  USB/IPOD
}

function DENON_SelectDecodeMode($id, $value) // AUTO; HDMI; DIGITAL; ANALOG
{
  CSCK_SendText($id, "SD".$value.chr(13));
}

function DENON_DecodeMode($id, $value) // Auto, PCM, DTS
{
 CSCK_SendText($id, "DC".$value.chr(13));
}

function DENON_VideoSelect($id, $value) // Video Select DVD/BD/TV/SAT_CBL/DVR/GAME/V.AUX/DOCK/SOURCE
{
 CSCK_SendText($id, "SV".$value.chr(13));
}

function DENON_SLEEP($id, $value) //
{
	if ($value == 0)
	{
		CSCK_SendText($id, "SLPOFF".chr(13));
	}
	ELSE
	{
	$value = str_pad($value, 3 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "SLP".$value.chr(13));
	}
}

function DENON_ModeSelect($id, $value) //
{
 CSCK_SendText($id, "MS".$value.chr(13));
}

function DENON_VideoSet($id, $value) //
{
 CSCK_SendText($id, "VS".$value.chr(13));
}

function DENON_ParaSettings($id, $value) // S
{
 CSCK_SendText($id, "PS".$value.chr(13));
}

function DENON_ParaVideo($id, $value) //
{
 CSCK_SendText($id, "PV".$value.chr(13));
}

function DENON_QuickSelect($id, $value) // 1-5
{
  CSCK_SendText($id, "MSQUICK".$value.chr(13));
}

function DENON_Preset($id, $value) //
{
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "NSB".$value.chr(13));
}

function DENON_NSE_Request($id) // fragt NSE-Werte ab
{
  CSCK_SendText($id, "NSE".chr(13));
}

function DENON_DynEQ($id, $value) // Dynamic Equilizer ON/OFF
{
  CSCK_SendText($id, "PSDYNEQ ".$value.chr(13));
}

function DENON_CinEQ($id, $value) // Cinema Equilizer ON/OFF
{
  CSCK_SendText($id, "PSCINEMA EQ.".$value.chr(13));
}
function DENON_MultiEQMode($id, $value) // MultiEquilizer AUDYSSEE/BYP.LR/FLAT/MANUELL/OFF
{
  CSCK_SendText($id, "PSMULTEQ:".$value.chr(13));
}

function DENON_DynVol($id, $value) // Dynamic Volume NGT(EVE/DAY
{
  CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
}

function DENON_AudioDelay($id, $value) // Audio Delay 0-200 ms
{
	$value = str_pad($value, 3 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSDELAY ".$value.chr(13));
}

function DENON_Dimension($id, $value) // Audio Delay 0-200 ms
{
	$value = str_pad($value, 2 ,"0", STR_PAD_LEFT);
	CSCK_SendText($id, "PSDIM ".$value.chr(13));
}

function DENON_InputSource($id, $value) // Input Source
{
  CSCK_SendText($id, "SI".$value.chr(13));
}

function DENON_DynamicCompressor($id, $value) // Dynamic Compressor OFF/LOW/MID/HIGH
{
  CSCK_SendText($id, "PSDCO ".$value.chr(13));
}

function DENON_ToneDefeat($id, $value) // Tone Defeat (AVR3809) ON/OFF
{
  CSCK_SendText($id, "PSTONE DEFEAT ".$value.chr(13));
}

function DENON_ToneCTRL($id, $value) // Tone Control (AVR 3311) ON/OFF
{
  CSCK_SendText($id, "PSTONE CTRL ".$value.chr(13));
}

function DENON_AudioRestorer($id, $value) // Audio Restorer OFF/MODE1/MODE2/MODE3
{
	switch ($value)
	{
	   case 0:
	      $value = "OFF";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 1:
	      $value = "MODE1";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 2:
	      $value = "MODE2";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

		case 3:
	      $value = "MODE2";
	      CSCK_SendText($id, "PSRSTR ".$value.chr(13));
		break;

	}
}

function DENON_DigitalInputMode($id, $value) // Digital Input Mode AUTO/PCM/DTS
{
  CSCK_SendText($id, "DC".$value.chr(13));
}

function DENON_InputMode($id, $value) // Input Mode AUTO/HDMI/DIGITALANALOG/ARC/NO
{
  CSCK_SendText($id, "SD".$value.chr(13));
}

function DENON_DynamicRange($id, $value) // DynamicRange
{
  CSCK_SendText($id, "PSDRC ".$value.chr(13));
}

function DENON_DynamicEQ($id, $value)
{
  CSCK_SendText($id, "PSDYNEQ ".$value.chr(13));
}

function DENON_DynamicVolume($id, $value)
{
	switch ($value)
		{
		   case 0:
		      $value = "OFF";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 1:
		      $value = "NGT";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 2:
		      $value = "EVE";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

			case 3:
		      $value = "DAY";
		      CSCK_SendText($id, "PSDYNVOL ".$value.chr(13));
			break;

		}
}

function DENON_RoomSize($id, $value)
{
	switch ($value)
		{
		   case 0:
		      $value = "N";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 1:
		      $value = "S";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 2:
		      $value = "MS";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 3:
		      $value = "M";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 4:
		      $value = "MS";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

			case 5:
		      $value = "L";
		      CSCK_SendText($id, "PSRSZ ".$value.chr(13));
			break;

		}
}

function DENON_SurroundBackMode($id, $value)
{
  CSCK_SendText($id, "PSSB:".$value.chr(13));
}

function DENON_CWidth($id, $value)
{
  CSCK_SendText($id, "PSCEN ".$value.chr(13));
}

function DENON_SurroundMode($id, $value)
{
  CSCK_SendText($id, "MS".$value.chr(13));
}

function DENON_SurroundPlayMode($id, $value)
{
  CSCK_SendText($id, "PSMODE:".$value.chr(13));
}

function DENON_CinemaEQ($id, $value)
{
  CSCK_SendText($id, "PSCINEMA EQ.".$value.chr(13));
}

function DENON_Panorama($id, $value)
{
  CSCK_SendText($id, "PSPAN ".$value.chr(13));
}

function DENON_FrontHeight($id, $value)
{
  CSCK_SendText($id, "PSFH:".$value.chr(13));
}

function DENON_NSE_DisplayRequest($id)
{
  CSCK_SendText($id, "NSE".chr(13));
}

function DENON_NSA_DisplayRequest($id)
{
  CSCK_SendText($id, "NSA".chr(13));
}

function DENON_PresetRequest($id)
{
  CSCK_SendText($id, "NSH".chr(13));
}

function DENON_ChannelVolumeFL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFL ".$value.chr(13));
}

function DENON_ChannelVolumeFR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFR ".$value.chr(13));
}

function DENON_ChannelVolumeC($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVC ".$value.chr(13));
}

function DENON_ChannelVolumeSW($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSW ".$value.chr(13));
}

function DENON_ChannelVolumeSL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSL ".$value.chr(13));
}

function DENON_ChannelVolumeSR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSR ".$value.chr(13));
}

function DENON_ChannelVolumeSBL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSBL ".$value.chr(13));
}

function DENON_ChannelVolumeSBR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSBR ".$value.chr(13));
}

function DENON_ChannelVolumeSB($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVSB ".$value.chr(13));
}

function DENON_ChannelVolumeFHL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFHL ".$value.chr(13));
}

function DENON_ChannelVolumeFHR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFHR ".$value.chr(13));
}

function DENON_ChannelVolumeFWL($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFWL ".$value.chr(13));
}

function DENON_ChannelVolumeFWR($id, $value)
{
	$value = (intval($value) +50);
	CSCK_SendText($id, "CVFWR ".$value.chr(13));
}

######################## Cursor Steuerung ######################################

function DENON_CursorUp($id)
{
  CSCK_SendText($id, "MNCUP".chr(13));
}

function DENON_CursorDown($id)
{
  CSCK_SendText($id, "MNCDN".chr(13));
}

function DENON_CursorLeft($id)
{
  CSCK_SendText($id, "MNCLT".chr(13));
}

function DENON_CursorRight($id)
{
  CSCK_SendText($id, "MNCRT".chr(13));
}

function DENON_Enter($id)
{
  CSCK_SendText($id, "MNENT".chr(13));
}

function DENON_Return($id)
{
  CSCK_SendText($id, "MNRTN".chr(13));
}


######################## Zone 2 functions ######################################

function DENON_Z2_Volume($id, $value) // "UP" or "DOWN"
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2VolumeFix($id, $value) // 18(db) bis -80(db)
{
	$value= intval($value) +80;
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2Power($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2Mute($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z2MU".$value.chr(13));
}

function DENON_Zone2InputSource($id, $value) // PHONO ; DVD ; HDP ; "TV/CBL" ; SAT ; "NET/USB" ; DVR ; TUNER
{
	CSCK_SendText($id, "Z2".$value.chr(13));
}

function DENON_Zone2ChannelSetting($id, $value) // Zone 2 Channel Setting: STEREO/MONO
{
	CSCK_SendText($id, "Z2CS".$value.chr(13));
}

function DENON_Zone2QuickSelect($id, $value) // Zone 2 Quickselect 1-5
{
	$value = $value +1;
	CSCK_SendText($id, "Z2QUICK".$value.chr(13));
}

function DENON_Zone2ChannelVolumeFL($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z2CVFL ".$value.chr(13));
}

function DENON_Zone2ChannelVolumeFR($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z2CVFR ".$value.chr(13));
}

########################## Zone 3 Functions ####################################

function DENON_Zone3Volume($id, $value) // "UP" or "DOWN"
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3VolumeFix($id, $value) // 18(db) bis -80(db)
{
	$value= intval($value) +80;
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3Power($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3Mute($id, $value) // "ON" or "OFF"
{
	CSCK_SendText($id, "Z3MU".$value.chr(13));
}

function DENON_Zone3InputSource($id, $value) // PHONO ; DVD ; HDP ; "TV/CBL" ; SAT ; "NET/USB" ; DVR
{
	CSCK_SendText($id, "Z3".$value.chr(13));
}

function DENON_Zone3ChannelSetting($id, $value) // Zone 3 Channel Setting: STEREO/MONO
{
	CSCK_SendText($id, "Z3CS".$value.chr(13));
}

function DENON_Zone3QuickSelect($id, $value) // Zone 3 Quickselect 1-5
{
   $value = $value +1;
	CSCK_SendText($id, "Z3QUICK".$value.chr(13));
}

function DENON_Zone3ChannelVolumeFL($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z3CVFL ".$value.chr(13));
}

function DENON_Zone3ChannelVolumeFR($id)
{
   $value = $value + 50;
	CSCK_SendText($id, "Z3CVFR ".$value.chr(13));
}

?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul6 DENON.ProfileCleaner ++++++++++


		//++++++++++++++++++ Script 'DENON.ProfileCleaner' +++++++++++++++++++++
		$ScriptNAME = 'DENON.ProfileCleaner';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
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
?>';CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
// ++++ Modul7 DENON.DisplayRefresh ++++++++++


		//++++++++++++++++++ Script 'DENON.DisplayRefresh' +++++++++++++++++++++
		$ScriptNAME = 'DENON.DisplayRefresh';
		$ParentID = $DENON_Scripts_ID;
		$ScriptTEXT = '<?
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

?>';
CreateScriptByName($ScriptNAME, $ParentID, $ScriptTEXT);
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