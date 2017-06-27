<?

    /*
     * This file is part of the IPSLibrary.
     *
     * The IPSLibrary is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published
     * by the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * The IPSLibrary is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
     */
	 
	/**@defgroup ipstwilight IPSTwilight
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	 
	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Startpage',$repository);
	}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion           : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version             : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Startpage');
	echo "\nStartpage       Version : ".$ergebnis;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
 	//read_wfc();
	
	echo "\n=============================================\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		}

/*******************************
 *
 * Webfront Konfiguration einlesen
 *
 ********************************/

	echo "\nWebuser activated : ";
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled)
		{
      	$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
		$WFC10_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');		
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";		
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";		
		}

	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];		
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10User');
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10User');
		$WFC10User_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10User');		
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";		
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled)
		{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}
		
	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled)
		{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$StartPageTypeID = CreateVariableByName($CategoryIdData, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,null,"");

	$name="SwitchScreen";
	$vid = @IPS_GetVariableIDByName($name,$CategoryIdData);
	if($vid === false)
    	{
        $vid = IPS_CreateVariable(1);  /* 0 Boolean 1 Integer 2 Float 3 String */
        IPS_SetParent($vid, $CategoryIdData);
        IPS_SetName($vid, $name);
        IPS_SetInfo($vid, "this variable was created by script #".$CategoryIdData.".");
        echo "Variable erstellt;\n";
    	}
	$pname="StartpageControl";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues($pname, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation($pname, 0, "Explorer", "", 0xc0c0c0); //P-Name, Value, Assotiation, Icon, Color=grau
  	   IPS_SetVariableProfileAssociation($pname, 1, "FullScreen", "", 0x00f0c0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 2, "Station", "", 0xf040f0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil erstellt;\n";
		}
	IPS_SetVariableCustomProfile($vid, $pname); // Ziel-ID, P-Name

	// Add Scripts, they have auto install
	$scriptIdStartpage   = IPS_GetScriptIDByName('Startpage_copyfiles', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdStartpage, 8*60*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */
	IPS_RunScript($scriptIdStartpage);
	$scriptIdStartpageWrite   = IPS_GetScriptIDByName('Startpage_schreiben', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdStartpageWrite, 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */
	IPS_RunScript($scriptIdStartpageWrite);

	IPS_SetVariableCustomAction($vid, $scriptIdStartpageWrite);
	
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	
	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "\nWebportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   800, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);		

		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		EmptyCategory($categoryId_WebFront);
		IPS_SetHidden($categoryId_WebFront, true); 		/* in der normalen Viz Darstellung verstecken */			
				  
		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;	
		CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem,   $WFC10_TabPaneParent,   $WFC10_TabPaneOrder, '', $WFC10_TabPaneIcon, $categoryId_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
	
		CreateLinkByDestination('Uebersicht', $variableIdHTML,    $categoryId_WebFront,  10);
		CreateLinkByDestination('Ansicht', $vid,    $categoryId_WebFront,  20);
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
		
	if ($WFC10User_Enabled)
		{		
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   800, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$WFC10User_Path." \n";
		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		EmptyCategory($categoryId_WebFrontUser);
		IPS_SetHidden($categoryId_WebFrontUser, true); /* in der normalen Viz Darstellung verstecken */		

		/*************************************/
		
		$tabItem = $WFC10User_TabPaneItem.$WFC10User_TabItem;	
		CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem,   $WFC10User_TabPaneParent,   $WFC10User_TabPaneOrder, '', $WFC10User_TabPaneIcon, $categoryId_WebFrontUser   /*BaseId*/, 'false' /*BarBottomVisible*/);
	
		CreateLinkByDestination('Uebersicht', $variableIdHTML,    $categoryId_WebFrontUser,  10);
		CreateLinkByDestination('Ansicht', $vid,    $categoryId_WebFrontUser,  20);
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($WFC10User_ConfigId, "StartpageTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);

		}


?>