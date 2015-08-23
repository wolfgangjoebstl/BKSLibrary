<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------




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

// ---------------------- Variablen-Management ----------------------------------

function DenonSetValue($item, $value, $vtype, $id, $webfrontID="")
{
	global $CategoryIdData,$CategoryIdApp;
	global $WFC10_Path,$WFC10User_Path,$Mobile_Path,$Retro_Path;
	
	if ($webfrontID=="")
	   {
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		}
	else
		{
		$categoryId_WebFront         = CreateCategoryPath($webfrontID);
		}

	// abhängig von DENON-Zone (Main, Zone 2, Zone 3) Parent ID für Variable und Link ermitteln
	//$DENON_ID = IPS_GetCategoryIDByName("DENON", 0);
   $DENON_ID = $id;     /* zb Denon-Arbeitszimmer, dann ist sie einmal in Data und der Link im Webfront zu finden */
	$praefix =substr($item, 0, 5);
	if ($praefix == "Zone2") // wenn Präfix "Zone2"
		{
	  	$VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $VAR_Parent_ID);
	   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 2", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1a: $VAR_Parent_ID";
		}
	elseif ($praefix == "Zone3")// wenn Präfix "Zone3"
		{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $VAR_Parent_ID);
	  	$LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Zone 3", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
		}
	elseif ($praefix == "Displ")// wenn Präfix "Zone3"
		{
	   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Display", $VAR_Parent_ID);
	  	$LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Display", $LINK_Parent_ID);
	   //echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
		}
	else // wenn Präfix nicht "Zone2", "Zone3" oder "Display"
		{
		//echo "Datenkategorie ist ".$CategoryIdData." mit ".$id."\n";
	   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
	   $VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		//echo "  Daten Mainzone ist auf ".$VAR_Parent_ID."  Das Webfromnt auf ".$categoryId_WebFront."   \n";
	   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
	   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
		//echo "Werte sind ".$LINK_Parent_ID." \n";
	  	//echo "Script DENON VariablenManager 1c: ".$VAR_Parent_ID."\n";
		}

	// Definition div. Parent IDs

	$KAT_DENON_Scripts_ID = $CategoryIdApp;
	$KAT_DENONWFE_ID = $categoryId_WebFront;
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
		echo "Script DENON Variable ".$item." angelegt\n";
	}

	// DENON-Variablenprofil anlegen wenn nicht vorhanden
	$ProfileName = "DENON.".$item;

	if (IPS_VariableProfileExists($ProfileName)== false)
	{
		echo "Script DENON VariablenManager Profil ".$ProfileName." existiert nicht.\n";
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

	echo "Profil für ".$item." anlegen.\n";
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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

		case "AuswahlFunktion":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
				{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "VOID", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "PC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 2, "XBOX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 3, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   //IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   //IPS_SetVariableProfileAssociation($ProfileName, 3, "PC", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   //IPS_SetVariableProfileAssociation($ProfileName, 6, "XBOX", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen; ";
				}
			else
				{
				echo "Profil ".$ProfileName." existiert bereits.\n";
				}
		break;

		case "InputSource":
		   $ProfileName = "DENON.".$item;
			if (IPS_VariableProfileExists($ProfileName) == false)
			{
			   //Var-Profil erstellen
				IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
				IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
			   IPS_SetVariableProfileValues($ProfileName, 0, 22, 1); //PName, Minimal, Maximal, Schrittweite
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
			   IPS_SetVariableProfileAssociation($ProfileName, 20, "MPLAY", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 21, "NET", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 22, "IPOD DIRECT", "", -1); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileValues($ProfileName, 0, 16, 1); //PName, Minimal, Maximal, Schrittweite
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
				IPS_SetVariableProfileAssociation($ProfileName, 15, "DTS NEO:6 C", "", -1); //P-Name, Value, Assotiation, Icon
				IPS_SetVariableProfileAssociation($ProfileName, 16, "DOLBY PL2 C", "", -1); //P-Name, Value, Assotiation, Icon
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
			   IPS_SetVariableProfileAssociation($ProfileName, 4, "NO", "", -1); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 1, "ON", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 8, "OFF", "", -1); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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
			   IPS_SetVariableProfileAssociation($ProfileName, 0, "OFF", "", 65280); //P-Name, Value, Assotiation, Icon, Color
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



?>
