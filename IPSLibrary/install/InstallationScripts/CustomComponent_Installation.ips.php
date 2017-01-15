<?

	/**@defgroup CustomComponent
	 * @ingroup 
	 * @{
	 *
	 * Script für zusätzliche eigene Komponenten und Programme rund um die Verarbeitung der Hardware Komponenten
	 * baut das gesamte Webfront mit Administrator und User auf
	 *
	 * @file          CustomComponent_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('CustomComponent',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('CustomComponent');
	echo "\nRemoteAccess Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	//echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	echo "\n";
	echo "Category OIDs for data : ".$CategoryIdData." for App : ".$CategoryIdApp."\n";

	/* webfront Configuratoren anlegen, wenn noch nicht vorhanden */
	
	$AdministratorID = @IPS_GetInstanceIDByName("Administrator", 0);
   	if(!IPS_InstanceExists($AdministratorID))
		{
     	echo "\nWebfront Configurator Administrator  erstellen !\n";
	  	$AdministratorID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
		IPS_SetName($AdministratorID, "Administrator");
		$config = IPS_GetConfiguration($AdministratorID);
		echo " Konfig: ".$config."\n";

  	 	IPS_ApplyChanges($AdministratorID);
		echo "Webfront Configurator Administrator aktiviert. \n";
		}

  	$UserID = @IPS_GetInstanceIDByName("User", 0);
   	if(!IPS_InstanceExists($UserID))
		{
     	echo "\nWebfront Configurator User  erstellen !\n";
	  	$UserID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
		IPS_SetName($UserID, "User");
		$config = IPS_GetConfiguration($UserID);
		echo "Konfig : ".$config."\n";

  	 	IPS_ApplyChanges($UserID);
		echo "Webfront Configurator User aktiviert. \n";
		}

	echo "Administrator ID : ".$AdministratorID." User ID : ".$UserID."\n";

	/* Webfront GUID herausfinden */
	
	echo "\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
		
	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
	if ($WFC10_Enabled==true)
		{
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
		$WFC10_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');
		$WFC10_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10');
		$WFC10_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10');
		$WFC10_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10');
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."\n";
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

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_ConfigId       = $WebfrontConfigID["User"];
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10User');
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10User');
		$WFC10User_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10User');
		$WFC10User_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10User');
		$WFC10User_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10User');
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10User');
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."\n";
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
      
	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
	   	{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		}
	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
	   	{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		}
		
	/* Links für Webfront identifizieren, alle Verzeichnisse in CustomComponents Data /core/IPSComponent Verzeichnis 
	 *
	 * Trennung in Kategorien erfolgt durch - Zeichen nach Auswertung und Nachrichten
	 */
	echo "\nLinks für Webfront identifizieren :\n";
	$webfront_links=array();
	$Category=IPS_GetChildrenIDs($CategoryIdData);
	foreach ($Category as $CategoryId)
		{
		echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		$Params = explode("-",IPS_GetName($CategoryId)); 
		$SubCategory=IPS_GetChildrenIDs($CategoryId);
		foreach ($SubCategory as $SubCategoryId)
	   		{
			//echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
	   		$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
	   		}
	   	}
	print_r($webfront_links);

	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------



	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "\nWebportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_WebFront." ".IPS_GetName($categoryId_WebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   0, IPS_GetName(0).'-Admin', '', $categoryId_WebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */

		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10_ConfigId." ".$WFC10_TabPaneItem." ".$WFC10_TabPaneParent." ".$WFC10_TabPaneOrder." ".$WFC10_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTP", $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, "HouseTP",  20, $WFC10_TabPaneName, $WFC10_TabPaneIcon);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);
		//EmptyCategory($categoryId_WebFrontAdministrator);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			/* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.
			 * Der Name für die Felder wird selbst erfunden.
			 */
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, 10);
			EmptyCategory($categoryId_WebFrontTab);

			$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontTab, 10);
			$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontTab, 20);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";

			$tabItem = $WFC10_TabPaneItem.$Name;				/* Netten eindeutigen Namen berechnen */
			echo "Webfront ".$WFC10_ConfigId." löscht TabItem :".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				{
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  20);
				 		}
				 	if ($Group=="Nachrichten")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  20);
						}
					}
    			}
			}
		}
	else
	   {
	   /* Admin not enabled, alles loeschen */
		DeleteWFCItems($WFC10_ConfigId, "HouseTP");
	   }

	if ($WFC10User_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_WebFront." ".IPS_GetName($categoryId_WebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_WebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10User_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10User_ConfigId." ".$WFC10User_TabPaneItem." ".$WFC10User_TabPaneParent." ".$WFC10User_TabPaneOrder." ".$WFC10User_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, "HouseTP", $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, "", "HouseRemote");     /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, "HouseTP",  20, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);      /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		//EmptyCategory($categoryId_WebFrontAdministrator);

		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $WFC10User_TabPaneItem.$Name;
			echo "Webfront ".$WFC10User_ConfigId." löscht TabItem :".$tabItem."\n";
			DeleteWFCItems($WFC10User_ConfigId, $tabItem);
			echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10User_TabPaneItem."\n";

			CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem, $WFC10User_TabPaneItem, 0, $Name, "");
			CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, false /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* User not enabled, alles loeschen */
		DeleteWFCItems($WFC10User_ConfigId, "HouseTP");
	   }

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFront, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* Mobile not enabled, alles loeschen */
	   }

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);
		}
	else
	   {
	   /* Retro not enabled, alles loeschen */
	   }

	

?>