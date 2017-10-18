<?

/*

Itunes Ansteuerung und Ueberwachung, macht ein kleines Lautsprechersymbol im Webfront, oben in der Schnellauswahl

Modifiziert auf IPS Library und kleine Anpassungen von Wolfgang Joebstl


Funktionen:

Erst-Installation:

Installation (erneut/Update)



*/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\iTunesSteuerung\iTunes.Configuration.inc.php");

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('iTunesSteuerung',$repository);
	}

$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nIP Symcon Kernelversion    : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPS ModulManager Version   : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Stromheizung');
	echo "\nModul iTunesSteuerung Version : ".$ergebnis."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";

	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n";

	echo "Variablen vorbereiten.\n";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_iTunes  = CreateCategory("iTunes", $CategoryIdData, 10);

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-iTunesSteuerung',   $CategoryIdData, 20);
	$iTunes_NachrichtenInput = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('iTunes.ActionScript', $CategoryIdApp);

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");


/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
	echo "\n";
	
/*******************************
 *
 * Webfront Konfiguration einlesen
 
[RemoteVis]
Enabled=false

[WFC10]
Enabled=true
Path=Visualization.WebFront.Administrator.iTunes
TabPaneItem=iTunesTPA
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[WFC10User]
Enabled=true
Path=Visualization.WebFront.User.iTunes
TabPaneItem=iTunesTPU
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[Mobile]
Enabled=true
Path=Visualization.Mobile.iTunes

[Retro]
Enabled=false
Path=Visualization.Mobile.iTunes 
 
 *
 ********************************/	
	
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',"iTunesTPA");
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',"roottp");
		$WFC10_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',"");
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',"Music");
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10',200);
		$WFC10_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10',"");
		$WFC10_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10',"");
		$WFC10_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10',"");
		$WFC10_TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10',"");
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";		
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";
		echo "  TabName       : ".$WFC10_TabName."\n";
		echo "  TabIcon       : ".$WFC10_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10_TabOrder."\n";
		}

	echo "\n";

	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',"iTunesTPU");
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',"roottp");
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',"");
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',"Music");
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10User',"");
		$WFC10User_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',"");
		$WFC10User_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10User',"");
		$WFC10User_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10User',"");
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueIntDef('TabOrder', 'WFC10User',"");
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";
		echo "  TabName       : ".$WFC10User_TabName."\n";
		echo "  TabIcon       : ".$WFC10User_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10User_TabOrder."\n";
		}		

	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";		
		}

	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}	

	/*******************************
	 *
	 * Variablen Profile Vorbereitung
	 *
	 ********************************/

	$pname="AusEinAuto";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}


	/******************************************************
	 *
	 * Timer Konfiguration
	 *
	 * Wecker programmierung ist bei GutenMorgen Funktion
	 *
	 ***********************************************************************/


	/*******************************
	 *
	 * Links für Webfront identifizieren
	 *
	 * Webfront Links werden für alle Autosteuerungs Default Funktionen erfasst. Es werden auch gleich die 
	 * Default Variablen dazu angelegt
	 *
	 *
	 * funktioniert für jeden beliebigen Vartiablennamen, zumindest ein/aus Schalter wird angelegt
	 *
	 *
	 ********************************/

	$webfront_links=array();
	$AutosteuerungID = CreateVariable("iTunesSteuerung", 1, $categoryId_iTunes, 1, "AusEinAuto",$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
	$webfront_links[$AutosteuerungID]["TAB"]="iTunes";
	$webfront_links[$AutosteuerungID]["OID_L"]=$AutosteuerungID;
	$webfront_links[$AutosteuerungID]["OID_R"]=$iTunes_NachrichtenInput;	
	$webfront_links[$AutosteuerungID]["NAME"]="iTunesSteuerung";
	$webfront_links[$AutosteuerungID]["ADMINISTRATOR"]=true;
	$webfront_links[$AutosteuerungID]["USER"]=true;
	$webfront_links[$AutosteuerungID]["MOBILE"]=true;

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben 
		 *
		 * typische Struktur, festgelegt im ini File:
		 *
		 * roottp/AutoTPA (Autosteuerung)/AutoTPADetails und /AutoTPADetails2
		 *
		 */
		
		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";		
		echo "\nWebportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */

		/*************************************/

		/* Ordnung machen, hat sicher irgendein anderes Modul bereits erledigt */
		//CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		//@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		//@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für diese untergeordnete Anzeigen schaffen */
		echo "\nWebportal Administrator.iTunes Steuerung Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		EmptyCategory($categoryId_WebFrontAdministrator);
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontAdministrator, true); //Objekt verstecken

		/*************************************/

		/* TabPaneItem anlegen, etwas kompliziert geloest */
		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
		 	{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
			}
		else
			{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

		$tabs=array();
		foreach ($webfront_links as $OID => $webfront_link)
			{
			$tabs[$webfront_link["TAB"]]=$webfront_link["TAB"];
			}

		$i=0;
		foreach ($tabs as $tab)
			{
			$categoryIdTab  = CreateCategory($tab,  $categoryId_WebFrontAdministrator, 100);
			$categoryIdLeft  = CreateCategory('Left',  $categoryIdTab, 10);
			$categoryIdRight = CreateCategory('Right', $categoryIdTab, 20);

			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem.$i,           $WFC10_TabPaneItem,    $WFC10_TabOrder+$i,     $tab, '', 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Left',   $tabItem.$i,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Right',  $tabItem.$i,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			echo "Kategorien erstellt, Main: ".$categoryIdTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";
			$i++;
			
			foreach ($webfront_links as $OID => $webfront_link)
				{
				if ($webfront_link["ADMINISTRATOR"]==true)
					{
					if ($webfront_link["TAB"]==$tab)
						{
						echo $tab." CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   ".$categoryIdLeft."\n";
						CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryIdLeft,  10);
						if ( isset( $webfront_link["OID_R"]) == true )
							{
							CreateLinkByDestination("Nachrichtenverlauf", $webfront_link["OID_R"],    $categoryIdRight,  20);
							}
						}
					}
				} 

			}  


		ReloadAllWebFronts();

		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	 
	 
	 
	 
	 
	 



/****************************************************************************************************************/








/****************************************************************************************************************/
/****************************************************************************************************************/
/****************************************************************************************************************/


?>