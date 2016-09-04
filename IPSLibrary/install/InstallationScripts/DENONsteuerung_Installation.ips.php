<?

/*

Script von www.raketenschnecke

Modifiziert auf IPS Library und kleine Anpassungen von Wolfgang Joebstl


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


Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
	}

$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

echo "\nKernelversion : ".IPS_GetKernelVersion();
$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
echo "\nIPS Version : ".$ergebnis;
$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
echo " ".$ergebnis;
$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
echo "\nIPSModulManager Version : ".$ergebnis;
$ergebnis=$moduleManager->VersionHandler()->GetVersion('DENONsteuerung');
echo "\nDENONsteuerung Version : ".$ergebnis;

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules;

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

$Audio_Enabled        = $moduleManager->GetConfigValue('Enabled', 'AUDIO');
$Audio_Path        	 = $moduleManager->GetConfigValue('Path', 'AUDIO');

$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-DENONsteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	/* werden automatisch angelegt
	$zeile1 = CreateVariable("Nachricht_Zeile01",3,$categoryId_Nachrichten, 10, "",null,null,""  );
	$zeile2 = CreateVariable("Nachricht_Zeile02",3,$categoryId_Nachrichten, 20, "",null,null,""  );
	$zeile3 = CreateVariable("Nachricht_Zeile03",3,$categoryId_Nachrichten, 30, "",null,null,""  );
	$zeile4 = CreateVariable("Nachricht_Zeile04",3,$categoryId_Nachrichten, 40, "",null,null,""  );
	$zeile5 = CreateVariable("Nachricht_Zeile05",3,$categoryId_Nachrichten, 50, "",null,null,""  );
	$zeile6 = CreateVariable("Nachricht_Zeile06",3,$categoryId_Nachrichten, 60, "",null,null,""  );
	$zeile7 = CreateVariable("Nachricht_Zeile07",3,$categoryId_Nachrichten, 70, "",null,null,"" );
	$zeile8 = CreateVariable("Nachricht_Zeile08",3,$categoryId_Nachrichten, 80, "",null,null,""  );
	$zeile9 = CreateVariable("Nachricht_Zeile09",3,$categoryId_Nachrichten, 90, "",null,null,""  );
	$zeile10 = CreateVariable("Nachricht_Zeile10",3,$categoryId_Nachrichten, 100, "",null,null,""  );
	$zeile11 = CreateVariable("Nachricht_Zeile11",3,$categoryId_Nachrichten, 110, "",null,null,""  );
	$zeile12 = CreateVariable("Nachricht_Zeile12",3,$categoryId_Nachrichten, 120, "",null,null,""  );
	$zeile13 = CreateVariable("Nachricht_Zeile13",3,$categoryId_Nachrichten, 130, "",null,null,""  );
	$zeile14 = CreateVariable("Nachricht_Zeile14",3,$categoryId_Nachrichten, 140, "",null,null,""  );
	$zeile15 = CreateVariable("Nachricht_Zeile15",3,$categoryId_Nachrichten, 150, "",null,null,""  );
	$zeile16 = CreateVariable("Nachricht_Zeile16",3,$categoryId_Nachrichten, 160, "",null,null,""  );
	*/

$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $CategoryIdApp);
$DENON_ActionScript_ID = IPS_GetScriptIDByName("DENON.ActionScript", $CategoryIdApp);

echo "\n";
echo "Category          App ID:".$CategoryIdApp."\n";
echo "DENONsteuerung Script ID:".$scriptIdDENONsteuerung."\n";
echo "DENON Action   Script ID:".$DENON_ActionScript_ID."\n";


$configuration=Denon_Configuration();
foreach ($configuration as $config)
	{
	$DENON_VAVR_IP = $config['IPADRESSE']; // hier die IP des DENON AVR angeben
	echo "\n\n****************************************************************************************************\n";
   echo "\nDENON.Installer for \"".$config['NAME']."\" started with IP Adresse ".$DENON_VAVR_IP."\n";

	/************************************************************************************
	 *
	 * DENON Sockets aufsetzen, für jedes Gerät einen eigenen
	 *
	 *******************************************************************************************/

	// Client Socket "DENON Client Socket" anlegen wenn nicht vorhanden
	$DENON_CS_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Client Socket", 0);
	if ($DENON_CS_ID === false)
		{
	   $DENON_CS_ID = IPS_CreateInstance("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
   	IPS_SetName($DENON_CS_ID, $config['INSTANZ']." Client Socket");
	   IPS_SetInfo($DENON_CS_ID, "this Object was created by Script DENON.Installer.ips.php");
	   CSCK_SetHost($DENON_CS_ID, $DENON_VAVR_IP);
	   CSCK_SetPort($DENON_CS_ID, 23);
	   CSCK_SetOpen($DENON_CS_ID,true);
		if (@IPS_ApplyChanges($DENON_CS_ID)===false) {echo "Achtung ".$config['INSTANZ']." Client Socket mit Fehler installiert. Überprüfe IP Adresse !\n"; }
		echo "DENON Client Socket angelegt\n";
		}
	else
		{
   	echo "DENON Client Socket bereits vorhanden (ID: $DENON_CS_ID) -> Konfiguration upgedated\n";
   	//IPS_DeleteInstance($DENON_CS_ID);
	   //$DENON_CS_ID = IPS_CreateInstance("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
   	//IPS_SetName($DENON_CS_ID, "DENON Client Socket");
	   //IPS_SetInfo($DENON_CS_ID, "this Object was created by Script DENON.Installer.ips.php");
	   CSCK_SetHost($DENON_CS_ID, $DENON_VAVR_IP);
	   //CSCK_SetPort($DENON_CS_ID, 23);
	   //CSCK_SetOpen($DENON_CS_ID,true);
		if (@IPS_ApplyChanges($DENON_CS_ID)===false) {echo "Achtung ".$config['INSTANZ']." Client Socket mit Fehler installiert. Überprüfe IP Adresse !\n"; }
		}

	// Cutter "DENON Cutter" anlegen wenn nicht vorhanden und mit Client Socket verbinden
	$DENON_Cu_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Cutter", 0);
	if ($DENON_Cu_ID == false)
		{
	   $DENON_Cu_ID = IPS_CreateInstance("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}");
   	IPS_SetName($DENON_Cu_ID, $config['INSTANZ']." Cutter");
	   IPS_SetInfo($DENON_Cu_ID, "this Object was created by Script DENON.Installer.ips.php");
   	IPS_ConnectInstance($DENON_Cu_ID, $DENON_CS_ID);
		Cutter_SetRightCutChar($DENON_Cu_ID, Chr(0x0D));
		IPS_ApplyChanges($DENON_Cu_ID);
		echo $config['INSTANZ']." Cutter angelegt und mit ".$config['INSTANZ']." Client Socket #".$DENON_CS_ID." verknüpft\n";
		}
	else
		{
		echo $config['INSTANZ']." Cutter #".$DENON_Cu_ID." ist bereits angelegt und mit ".$config['INSTANZ']." Client Socket #".$DENON_CS_ID." verknüpft\n";
	   $DENON_Cu_ID = @IPS_GetInstanceIDByName($config['INSTANZ']." Cutter", 0);
	   IPS_DisconnectInstance($DENON_Cu_ID);
   	IPS_ConnectInstance($DENON_Cu_ID, $DENON_CS_ID);
	   Cutter_SetRightCutChar($DENON_Cu_ID, Chr(0x0D));
		IPS_ApplyChanges($DENON_Cu_ID);
	   echo "DENON Cutter (#$DENON_Cu_ID) bereits vorhanden, neu konfiguriert \n";
		}

	// Cutter "DENON Register Variable" anlegen wenn nicht vorhanden und mit "DENON Cutter" verbinden
	$DENON_RegVar_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Register Variable", $DENON_Cu_ID);
	if ($DENON_RegVar_ID == false)
		{
	   $DENON_RegVar_ID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}");
   	IPS_SetName($DENON_RegVar_ID, $config['INSTANZ']." Register Variable");
	   IPS_SetInfo($DENON_RegVar_ID, "this Object was created by Script DENON.Installer.ips.php");
   	IPS_SetParent($DENON_RegVar_ID, $DENON_Cu_ID);
	   IPS_ConnectInstance($DENON_RegVar_ID, $DENON_Cu_ID);

		IPS_ApplyChanges($DENON_RegVar_ID);
		echo $config['INSTANZ']." Register Variable angelegt und mit ".$config['INSTANZ']." Cutter #$DENON_Cu_ID verknüpft\n";
		}
	else
		{
	   echo $config['INSTANZ']." Register Variable bereits vorhanden (ID: $DENON_RegVar_ID)\n";
		}
	$scriptId_DENONCommandManager = IPS_GetScriptIDByName('DENON.CommandManager', $CategoryIdApp);
	echo "\nScript ID DENON Command Manager für Register Variable :".$scriptId_DENONCommandManager."\n";
	RegVar_SetRXObjectID($DENON_RegVar_ID , $scriptId_DENONCommandManager);
	IPS_ApplyChanges($DENON_RegVar_ID);
	echo "DENON Register Variable  mit Script DENON.CommandManager #".$scriptId_DENONCommandManager." verknüpft\n";

	// Event "DisplayRefreshTimer" anlegen und zuweisen wenn nicht vorhanden
	$DENON_DisplayRefresh_ID = IPS_GetScriptIDByName("DENON.DisplayRefresh", $CategoryIdApp);
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

	/************************************************************************************
	 *
	 * Datenstrukturen aufsetzen, für jedes Gerät eigene
	 *
	 *******************************************************************************************/

	$DENON_ID  = CreateCategory($config['NAME'], $CategoryIdData, 10);

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

	echo "Category App           ID: ".$CategoryIdApp."\n";
	echo "Category Data          ID: ".$CategoryIdData."\n";
	//echo "Pfad für Webfront        :".$WFC10_Path." \n";

	/* include DENON.Functions
	  $id des DENON Client sockets muss nun selbst berechnet werden, war vorher automatisch
	*/
	if (IPS_GetObjectIDByName("DENON.VariablenManager", $CategoryIdApp) >0)
		{
		IPSUtils_Include ("DENON.VariablenManager.ips.php", "IPSLibrary::app::modules::DENONsteuerung");
		//include "DENON.VariablenManager.ips.php";
		}
	else
		{
		echo "Script DENON.VariablenManager kann nicht gefunden werden!";
		}

	/************************************************************************************
	 *
	 * Webfron Installation
	 *
	 *******************************************************************************************/


	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
      IPS_SetPosition($categoryId_WebFront,600);
		WebfrontInstall($categoryId_WebFront,$config,$moduleManager);
		}

	if ($Audio_Enabled)
		{
		echo "\nWebportal Administrator Audio installieren in: ".$Audio_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($Audio_Path);
		WebfrontInstall($categoryId_WebFront,$config,$moduleManager);
		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);
		WebfrontInstall($categoryId_WebFront,$config,$moduleManager);
		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		// Kategorie "DENON Webfront" anlegen wenn nicht vorhanden
		$DENON_WFE_ID = @IPS_GetCategoryIDByName($config['NAME'], $categoryId_WebFront);
		if ($DENON_WFE_ID == false)
			{
			$DENON_WFE_ID = IPS_CreateCategory();
			IPS_SetName($DENON_WFE_ID, $config['NAME']);
			IPS_SetInfo($DENON_WFE_ID, "this Object was created by Script DENON.Installer.ips.php");
			IPS_SetParent($DENON_WFE_ID, $categoryId_WebFront);
			echo "Kategorie DENON Webfront #$DENON_WFE_ID angelegt\n";
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
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);

		$DENON_ID  = CreateCategory($config['NAME'], $CategoryIdData, 10);
		$DENON_Steuerung_ID = @IPS_GetInstanceIDByName("Steuerung", $DENON_ID);

		// Kategorie "DENON Webfront" anlegen wenn nicht vorhanden
		$DENON_WFE_ID = @IPS_GetCategoryIDByName($config['NAME'], $categoryId_WebFront);
		if ($DENON_WFE_ID == false)
			{
			$DENON_WFE_ID = IPS_CreateCategory();
			IPS_SetName($DENON_WFE_ID, $config['NAME']);
			IPS_SetInfo($DENON_WFE_ID, "this Object was created by Script DENON.Installer.ips.php");
			IPS_SetParent($DENON_WFE_ID, $categoryId_WebFront);
			echo "Kategorie DENON Webfront #$DENON_WFE_ID angelegt\n";
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
		}

	/***************************************************************************************/

	echo " ... sicherstellen das Configfile uebernommen wird.\n";
  	$id=$config['NAME'];
	$item="AuswahlFunktion";
	$vtype = 1;
	$value=1;
   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   $VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
	$itemID = @IPS_GetVariableIDByName($item, $VAR_Parent_ID);
	$ProfileName = "DENON.".$item."_".$id;
	echo "Variablenprofil neu anlegen für ".$item." mit Profilname ".$ProfileName." mit Item ID ".$itemID." \n";
   @IPS_DeleteVariableProfile($ProfileName);
	DENON_SetVarProfile($item, $itemID, $vtype, $id);

	echo "Shortcut anlegen für ".$id.".".$item." in ".$Audio_Path." \n";
	DenonSetValue($item, $value, $vtype, $id, $Audio_Path);
   }  /* ende foreach Denon Device */

echo "\nInstallation abgeschlossen\n\nwww.raketenschnecke.net";


/****************************************************************************************************************/
/****************************************************************************************************************/
/****************************************************************************************************************/
/****************************************************************************************************************/

function WebfrontInstall($categoryId_WebFront,$config,$moduleManager)
	{

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	echo "Category App           ID: ".$CategoryIdApp."\n";
	echo "Category Data          ID: ".$CategoryIdData."\n";

	$DENON_ID  = CreateCategory($config['NAME'], $CategoryIdData, 10);
	$DENON_Steuerung_ID = @IPS_GetInstanceIDByName("Steuerung", $DENON_ID);

		// Kategorie "DENON Webfront" anlegen wenn nicht vorhanden
		$DENON_WFE_ID = @IPS_GetCategoryIDByName($config['NAME'], $categoryId_WebFront);
		if ($DENON_WFE_ID == false)
			{
			$DENON_WFE_ID = IPS_CreateCategory();
			IPS_SetName($DENON_WFE_ID, $config['NAME']);
			IPS_SetInfo($DENON_WFE_ID, "this Object was created by Script DENON.Installer.ips.php");
			IPS_SetParent($DENON_WFE_ID, $categoryId_WebFront);
			echo "Kategorie DENON Webfront #$DENON_WFE_ID angelegt\n";
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
		IPS_SetPosition($DENON_MainZone_ID,0);

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
		IPS_SetPosition($DENON_Zone2_ID,10);

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
		IPS_SetPosition($DENON_Zone3_ID,10);

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
		IPS_SetPosition($DENON_SteuerungWFE_ID,100);

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
		IPS_SetPosition($DENON_DisplayWFE_ID,200);

	// Cursor Up & VarProfil anlegen wenn nicht vorhanden
	echo "Data Steuerung ID #".$DENON_Steuerung_ID."\n";
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorUp", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
		{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorUp");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		echo "Data Cursor Up ".$DENON_Cursor_ID." angelegt.\n";
		}
	else
	   {
		echo "Data Cursor Up ".$DENON_Cursor_ID." vorhanden.\n";
	   }

	if (IPS_VariableProfileExists("DENON.CursorUP") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorUP", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorUP", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorUP", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorUP", 0, " UP ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.CursorUP", 1, " ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorUP erstellt;\n";
		}

	$DENON_ActionScript_ID = IPS_GetScriptIDByName("DENON.ActionScript", $CategoryIdApp);
	echo "\nScript ID DENON.ActionScript für Cursor Steuerung ".$DENON_ActionScript_ID."\n";
	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorUP"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);



	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("UP", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable CursorUp #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

	// Cursor Down anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorDown", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
		{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorDown");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);
		}

	if (IPS_VariableProfileExists("DENON.CursorDOWN") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorDOWN", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorDOWN", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorDOWN", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorDOWN", 0, "DOWN", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.CursorDOWN", 1, "    ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorDOWN erstellt;\n";
		}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorDOWN"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("DOWN", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable CursorDown #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

	// Cursor Left anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorLeft", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
		{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorLeft");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		}

	if (IPS_VariableProfileExists("DENON.CursorLEFT") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorLEFT", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorLEFT", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorLEFT", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorLEFT", 0, "LEFT", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.CursorLEFT", 1, "    ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorLEFT erstellt; ";
		}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorLEFT"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("LEFT", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable CursorLEFT #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

	// Cursor Right anlegen wenn nicht vorhanden
	$DENON_Cursor_ID = @IPS_GetVariableIDByName("CursorRight", $DENON_Steuerung_ID);
	if ($DENON_Cursor_ID == false)
		{
		$DENON_Cursor_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Cursor_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Cursor_ID, "CursorRight");
		IPS_SetInfo($DENON_Cursor_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);
		}

	if (IPS_VariableProfileExists("DENON.CursorRIGHT") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.CursorRIGHT", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.CursorRIGHT", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.CursorRIGHT", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.CursorRIGHT", 0, "RIGHT", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.CursorRIGHT", 1, "     ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.CursorRIGHT erstellt;\n";
		}

	IPS_SetVariableCustomProfile($DENON_Cursor_ID, "DENON.CursorRIGHT"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Cursor_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("RIGHT", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable CursorRight #$DENON_Cursor_ID in Kategorie DENON angelegt\n";

	// Enter anlegen wenn nicht vorhanden
	$DENON_Enter_ID = @IPS_GetVariableIDByName("Enter", $DENON_Steuerung_ID);
	if ($DENON_Enter_ID == false)
		{
		$DENON_Enter_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Enter_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Enter_ID, "Enter");
		IPS_SetInfo($DENON_Enter_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_SetVariableCustomAction($DENON_Enter_ID, $DENON_ActionScript_ID);
		}

	if (IPS_VariableProfileExists("DENON.ENTER") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.ENTER", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.ENTER", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.ENTER", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.ENTER", 0, "ENTER", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.ENTER", 1, "     ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.ENTER erstellt;\n";
		}

	IPS_SetVariableCustomProfile($DENON_Enter_ID, "DENON.ENTER"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Enter_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("ENTER", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable Enter #$DENON_Enter_ID in Kategorie DENON angelegt\n";

	// Return anlegen wenn nicht vorhanden
	$DENON_Return_ID = @IPS_GetVariableIDByName("Return", $DENON_Steuerung_ID);
	if ($DENON_Return_ID == false)
		{
		$DENON_Return_ID = IPS_CreateVariable(0);
		IPS_SetParent($DENON_Return_ID, $DENON_Steuerung_ID);
		IPS_SetName($DENON_Return_ID, "Return");
		IPS_SetInfo($DENON_Return_ID, "this Object was created by Script DENON.Installer.ips.php");
		IPS_SetVariableCustomAction($DENON_Return_ID, $DENON_ActionScript_ID);
		}

	if (IPS_VariableProfileExists("DENON.RETURN") == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile("DENON.RETURN", 0); // PName, Typ
		IPS_SetVariableProfileDigits("DENON.RETURN", 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues("DENON.RETURN", 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation("DENON.RETURN", 0, "RETURN", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   IPS_SetVariableProfileAssociation("DENON.RETURN", 1, "      ", "", -1); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil DENON.RETURN erstellt;\n";
		}

	IPS_SetVariableCustomProfile($DENON_Return_ID, "DENON.RETURN"); // Ziel-ID, P-Name
	IPS_SetVariableCustomAction($DENON_Return_ID, $DENON_ActionScript_ID);

	// Link anlegen/zuweisen
		$LinkID = @IPS_GetLinkIDByName("RETURN", $DENON_SteuerungWFE_ID);
		$LinkChildID = @IPS_GetLink($LinkID);
		$LinkChildID = $LinkChildID["TargetID"];

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
	echo "Variable Return #$DENON_Return_ID in Kategorie DENON angelegt\n";
	}


?>
