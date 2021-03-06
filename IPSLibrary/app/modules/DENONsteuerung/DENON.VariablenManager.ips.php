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

/*********************************************
 *
 * Die ganze webconfig für ein Denon Gerät durchgehen
 * Das heisst webfrontname heisst der Reihe nach DATA und dann die String Namen der ganzen Webfronts
 * sucht den Wert item, wenn ein Index item oder * heisst wird getriggert  
 *
 *****************************************************/

function DenonSetValueAll($webconfig, $item, $value, $vtype, $id)
	{
	$log=new Logging("C:\Scripts\Denon\Log_ReceiveVariable.csv");	
	$log->LogMessage("Denon Telegramm;".$id.";".$item.";".$value);
	foreach ($webconfig as $webfrontname => $itemname)
		{
		if (isset($itemname['*']))
			{
			$log->LogMessage("DenonSetValue * ;".$item.";  ".$value.";   ".$vtype.";  ID ".$id.";".$webfrontname.";  ");
			DenonSetValue($item, $value, $vtype, $id, $webfrontname);
			}
		if (isset($itemname[$item]))
			{
			$log->LogMessage("DenonSetValue ;".$item.";  ".$value.";  ".$vtype.";   ID ".$id.";   ".$webfrontname."; false;  ".$itemname[$item].";");
			DenonSetValue($item, $value, $vtype, $id, $webfrontname, false, $itemname[$item]);
			}
		}
	}

/*********************************************************
 *
 * DenonSetValue (VariablenManager)
 *   item ist der Denon Name in der Datenbank, kann auch einen Präfix zB Zone2 enthalten
 *   value der Wert
 *   id
 *   vtype
 *   Webfront zB Visualization.WebFront.User.DENON
 * setzen der Variablen in Data, 
 * wenn erforderlich auch Anlegen der Umgebung in Data und im Visualization Webfront
 *
 ************************************************************************************************/

function DenonSetValue($item, $value, $vtype, $id, $webfrontID="", $debug=false, $item_link="")
	{
	//global $CategoryIdData,$CategoryIdApp;
	global $WFC10_Path,$WFC10User_Path,$Mobile_Path,$Retro_Path;

	if ($debug) echo "      Aufruf DenonSetValue: Variable ".$item." mit Wert ".$value." beschreiben.\n";
	if ($item_link=="") $item_link=$item;
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
		}
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	if ($webfrontID=="")
		{
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		if ($debug) echo "         Standard Webfront Kategorie ".$webfrontID." (".$categoryId_WebFront."), hier kommt der Link hin.\n";		
		}
	else
		{
		$categoryId_WebFront         = CreateCategoryPath($webfrontID);
		if ($debug) echo "         Neue Webfront Kategorie ".$webfrontID." (".$categoryId_WebFront.") anlegen, hier kommt der Link hin.\n";
		}

	// abhängig von DENON-Zone (Main, Zone 2, Zone 3) Parent ID für Variable und Link ermitteln
	
	//IPSLogger_Dbg (__file__, 'Received command for '.$id. ' WebfrontID '.$categoryId_WebFront.' find on '.$webfrontID.' with item '.$item);
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
	elseif ($praefix == "Displ")// wenn Präfix "Displ"
		{
		$VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
		$VAR_Parent_ID = IPS_GetInstanceIDByName("Display", $VAR_Parent_ID);
	  	$LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
		$LINK_Parent_ID = IPS_GetInstanceIDByName("Display", $LINK_Parent_ID);
		//echo "Script DENON VariablenManager 1b: $VAR_Parent_ID";
		}
	else // wenn Präfix nicht "Zone2", "Zone3" oder "Display"
		{
		$VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		$LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
		$LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
		}
	if ($debug)
		{
		echo "       Datenkategorie ist ".$CategoryIdData." mit ".$id." \n";
		echo "       Daten sind auf ".$VAR_Parent_ID."  (".IPS_GetName($VAR_Parent_ID)."/".IPS_GetName(IPS_GetParent($VAR_Parent_ID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($VAR_Parent_ID))).")\n";
		echo "       Webfront Links sind auf ".$LINK_Parent_ID."   (".IPS_GetName($LINK_Parent_ID)."/".IPS_GetName(IPS_GetParent($LINK_Parent_ID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($LINK_Parent_ID))).")\n";
		echo "       Das Webfront ist auf ".$categoryId_WebFront."  (".IPS_GetName($categoryId_WebFront)."/".IPS_GetName(IPS_GetParent($categoryId_WebFront))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($categoryId_WebFront))).")  \n";
		}

	// Definition div. Parent IDs

	$KAT_DENONWFE_ID = $categoryId_WebFront;
	$ScriptID = IPS_GetScriptIDByName("DENON.ActionScript", $CategoryIdApp);

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
		if ($debug) echo "        DENON Variable ".$item." angelegt\n";
		}
	if ($debug) echo "        DENON Variable ".$item." unter ".$itemID." (".IPS_GetName($itemID)."/".IPS_GetName(IPS_GetParent($itemID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($itemID))).") vorhanden.\n";

	// DENON-Variablenprofil anlegen wenn nicht vorhanden
	switch ($item)
		{
		case "AuswahlFunktion":
			$ProfileName = "DENON.".$item."_".$id;
			break;
		default:
			$ProfileName = "DENON.".$item;
			break;
		}

	if (IPS_VariableProfileExists($ProfileName)== false)
		{
		if ($debug) echo "          DENON VariablenManager Profil ".$ProfileName." existiert nicht.\n";
		DENON_SetVarProfile($item, $itemID, $vtype, $id);
		}
	elseif ($debug) echo "          DENON VariablenManager Profil ".$ProfileName." existiert bereits.\n";

	// DENON-Variablenprofil der Variable zuweisen und Actionscript konfigurieren, nur wenn nicht bereits zugewiesen
	//print_r(IPS_GetVariable($itemID));
	$VarCustomProfileName = IPS_GetVariable($itemID)["VariableCustomProfile"];

	if ($VarCustomProfileName != $ProfileName)
		{
		if (IPS_VariableProfileExists($ProfileName))
			{
			IPS_SetVariableCustomProfile($itemID, $ProfileName);
			echo "               Script DENON VariablenManager Profil ".$ProfileName." zugewiesen ;";
			}
		}

	// Action-Script zuweisen wenn nicht bereits zugewiesen
	$VarActionscriptID = IPS_GetVariable($itemID);
	$VarActionscriptID = $VarActionscriptID["VariableCustomAction"];
	$VarCustomProfileName = IPS_GetVariable($itemID);
	$VarCustomProfileName = $VarCustomProfileName["VariableCustomProfile"];

	//echo ">>>>>>Trickreich, nur wenn ".$VarCustomProfileName." == ".$ProfileName."\n";
	If ($VarCustomProfileName == $ProfileName)
		{
		if ($VarActionscriptID != $ScriptID)
			{
			IPS_SetVariableCustomAction($itemID, $ScriptID);
			}
		}

	// Link anlegen/zuweisen
	$LinkID = @IPS_GetLinkIDByName($item_link, $LINK_Parent_ID);
	$LinkChildID = @IPS_GetLink($LinkID);
	$LinkChildID = $LinkChildID["TargetID"];

	if (IPS_LinkExists($LinkID) == false)// Link anlegen wenn nicht vorhanden
		{
    	$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item_link);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
		}
	elseif ($LinkChildID != $itemID) // wenn Link nicht korrekt verlinkt -> löschen und neu anlegen
		{
		IPS_DeleteLink($LinkID);
		$LinkID = IPS_CreateLink();
		IPS_SetName($LinkID, $item_link);
		IPS_SetLinkChildID($LinkID, $itemID);
		IPS_SetParent($LinkID, $LINK_Parent_ID);
		}

	/* es gibt soviele Links, ein bisschen ordnung schaffen und unwichtige nach hinten geben */
	switch ($item)
		{
		case "Power":
			IPS_SetPosition($LinkID,0);
			break;

		case "MainZonePower":
			IPS_SetPosition($LinkID,0);
			break;

		case "AudioDelay":
			IPS_SetPosition($LinkID,100);
			break;

		case "MasterVolume":
			IPS_SetPosition($LinkID,0);
			break;

		case "LFELevel":
			IPS_SetPosition($LinkID,100);
			break;

		case "MainZone":
			break;

		case "MainMute":
			break;

		case "QuickSelect":
			IPS_SetPosition($LinkID,100);
			break;

		case "Sleep":
			break;

		case "DigitalInputMode":
			IPS_SetPosition($LinkID,20);
			break;

		case "AuswahlFunktion":
			break;

		case "InputSource":
			break;

		case "SurroundMode":
			break;

		case "SurroundPlayMode":
			break;

		case "MultiEQMode":
			break;

		case "MasterVolume":
			break;

		case "SSINFAISFSV":
		case "SSINFAISSIG";
		case "SS SMG":
		case "SS CMP":
		case "SS HDM":
		case "SS ANA":
		case "SS VDO":
		case "SS DIN":
		case "AudioRestorer":
			IPS_SetPosition($LinkID,900);
			break;

		case "BassLevel":
			IPS_SetPosition($LinkID,100);
			break;

		case "TrebleLevel":
			IPS_SetPosition($LinkID,100);
			break;

		case "InputMode":
			IPS_SetPosition($LinkID,100);
			break;

		case "CinemaEQ":
			IPS_SetPosition($LinkID,100);
			break;

		case "Dimension":
			IPS_SetPosition($LinkID,100);
			break;

		case "Panorama":
			IPS_SetPosition($LinkID,100);
			break;

		case "FrontHeight":
			IPS_SetPosition($LinkID,100);
			break;

		case "DynamicVolume":
			IPS_SetPosition($LinkID,100);
			break;

		case "RoomSize":
			IPS_SetPosition($LinkID,100);
			break;

		case "DynamicCompressor":
			IPS_SetPosition($LinkID,100);
			break;

		case "ToneCTRL":
			IPS_SetPosition($LinkID,100);
			break;

		case "DynamicEQ":
			IPS_SetPosition($LinkID,100);
			break;

		case "C.Width":
			IPS_SetPosition($LinkID,100);
			break;

		case "DynamicRange":
			IPS_SetPosition($LinkID,100);
			break;

		case "VideoSelect":
			IPS_SetPosition($LinkID,100);
			break;

		case "SurroundBackMode":
			IPS_SetPosition($LinkID,100);
			break;

		case "Preset":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFL":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFR":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeC":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSW":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSL":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSR":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSBL":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSBR":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeSB":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFHL":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFHR":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFWL":
			IPS_SetPosition($LinkID,100);
			break;

		case "ChannelVolumeFWR":
			IPS_SetPosition($LinkID,100);
			break;

		################# Zone 2 #################################################
		case "Zone2Power":
			break;

		case "Zone2Volume":
			break;

		case "Zone2Mute":
			break;

		case "Zone2InputSource":
			break;

		case "Zone2ChannelSetting":
			break;

		case "Zone2ChannelVolumeFL":
			IPS_SetPosition($LinkID,100);
			break;

		case "Zone2ChannelVolumeFR":
			IPS_SetPosition($LinkID,100);
			break;

		case "Zone2QuickSelect":
			IPS_SetPosition($LinkID,100);
			break;


		################# Zone 3 #################################################
		case "Zone3Power":
			break;

		case "Zone3Volume":
			break;

		case "Zone3Mute":
			break;

		case "Zone3InputSource":
			break;

		case "Zone3ChannelSetting":
			break;

		case "Zone3ChannelVolumeFL":
			IPS_SetPosition($LinkID,100);
			break;

		case "Zone3ChannelVolumeFR":
			IPS_SetPosition($LinkID,100);
			break;

		case "Zone3QuickSelect":
			IPS_SetPosition($LinkID,100);
			break;

		//default: wenn keine Bedingung erfüllt ist
		default:
			IPS_SetPosition($LinkID,1000);
			// echo "kein neues DENON-Profil angelegt"; // zur Fehlersuche einkommentieren
			break;
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

// ------------------- Variablen Profil-Management ------------------------------
// Funktion erstellt für DENON-Variablen gleichnamige Variablenprofile mit Namenspräfix "DENON."
// übergeben werden muss Variablenname ($item), Variablen-ID ($itemID) und Variablentyp ($vtype)

function DENON_SetVarProfile($item, $itemID, $vtype, $id="")
	{
	echo "DENON_SetVarProfile aufgerufen mit ".$item." ".$itemID."  ".$vtype."   ".$id."\n";
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
			   echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und zugewiesen \n";
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
			$configuration=Denon_Configuration();
			$NameTag=false;
			foreach ($configuration as $Denon => $config)
				{
				if ($config['NAME']==$id)
					{
					$NameTag=$Denon;
					$instanz=$config['INSTANZ'];
					}
				}
			if ( $NameTag !== false )
				{
				$ProfileName = "DENON.".$item."_".$id;
				$webconfig=Denon_WebfrontConfig();
				//echo "Jetzt Profil erstellen : ".$ProfileName." mit der ID : \"".$id."\" NameTag zur Wiedererkennung \"".$NameTag."\"\n";
				if (isset($webconfig[$NameTag]['DATA'][$item])==true)
					{
					//print_r($webconfig[$NameTag]['DATA']);
					$profil=$webconfig[$NameTag]['DATA'][$item];
					$profil_size=sizeof($profil);
					//echo "Neues Profil mit ".$profil_size." Einträgen.\n";
					if (IPS_VariableProfileExists($ProfileName) == false)
						{
						//Var-Profil erstellen
						//echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellen.\n";						
						IPS_CreateVariableProfile($ProfileName, $vtype); // PName, Typ
						IPS_SetVariableProfileDigits($ProfileName, 0); // PName, Nachkommastellen
						IPS_SetVariableProfileValues($ProfileName, 0, $profil_size, 1); //PName, Minimal, Maximal, Schrittweite
						$i=0;
						IPS_SetVariableProfileAssociation($ProfileName, 0, "VOID", "", -1); //P-Name, Value, Assotiation, Icon, Color
						foreach ($profil as $name => $assoc)
							{
							$i++;
							//echo "          Eintrag ".$i." erstellen fuer Name ".$name." Association ".$assoc." \n";
							IPS_SetVariableProfileAssociation($ProfileName, $i, $name, "", -1); //P-Name, Value, Assotiation, Icon, Color
							}
						//IPS_SetVariableProfileAssociation($ProfileName, 2, "TUNER", "", -1); //P-Name, Value, Assotiation, Icon, Color
						//IPS_SetVariableProfileAssociation($ProfileName, 3, "PC", "", -1); //P-Name, Value, Assotiation, Icon, Color
						//IPS_SetVariableProfileAssociation($ProfileName, 6, "XBOX", "", -1); //P-Name, Value, Assotiation, Icon, Color
						IPS_SetVariableCustomProfile($itemID, $ProfileName); // Ziel-ID, P-Name
						echo "Script DENON VariablenManager Profil  ".$ProfileName." erstellt und der Variable ".$itemID." zugewiesen.\n";
						}
					else
						{
						//echo "Profil ".$ProfileName." existiert bereits.\n";
						}
					}
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
			   IPS_SetVariableProfileAssociation($ProfileName, 23, "BT", "", -1); //P-Name, Value, Assotiation, Icon, Color
			   IPS_SetVariableProfileAssociation($ProfileName, 24, "AUX2", "", -1); //P-Name, Value, Assotiation, Icon, Color
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
				IPS_SetVariableProfileAssociation($ProfileName, 17, "DTS NEO:6 M", "", -1); //P-Name, Value, Assotiation, Icon
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
		  echo "kein neues DENON-Profil angelegt"; // zur Fehlersuche einkommentieren

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