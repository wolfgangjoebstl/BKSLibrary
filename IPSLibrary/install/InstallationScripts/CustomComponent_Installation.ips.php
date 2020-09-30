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
	 
	/***********************************
	 *
	 * Script für zusätzliche eigene Komponenten und Programme rund um die Verarbeitung der Hardware Komponenten
	 * baut das gesamte Webfront mit Administrator und User auf
	 * korrigiert diverse Einstellungen, loescht nicht konfigurierte Webfrontends
	 *
	 * @file          CustomComponent_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

    $noinstall=true;                        /* true, keine Installation der lokalen Variablen um die Laufzeit der Routine zu verkuerzen */
    $evaluateHardware=false;                /* false, keine EvaluateHardware aufgerufen für die Aktualisierung */
    //$startexec=microtime(true);             /* Laufzeitmessung */
    $startexec=startexec("s");              /* Laufzeitmessung */

    /*******************************
    *
    * Initialisierung, Modul Handling Vorbereitung
    *
    ********************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('CustomComponent',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	$ergebnis1=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	$ergebnis2=$moduleManager->VersionHandler()->GetVersion('CustomComponent')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	//echo "\nKernelversion : ".IPS_GetKernelVersion()."\n";
	//echo "IPSModulManager Version : ".$ergebnis1."\n";
	//echo "CustomComponent Version : ".$ergebnis2."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	//echo $inst_modules."\n";

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

    /*******************************
    *
    * Zusammenräumen
    *
    ********************************/

	if (isset ($installedModules["IPSTwilight"]))
		{
		$moduleManagerTwilight = new IPSModuleManager('IPSTwilight');
		$WFC10Twilight_Path    	 		= $moduleManagerTwilight->GetConfigValue('Path', 'WFC10');
		$categoryId_Twilight_Path                = CreateCategoryPath($WFC10Twilight_Path);
		IPS_SetHidden($categoryId_Twilight_Path, true); /* in der normalen Viz Darstellung verstecken */	
		echo "Twilight Vizualisation Path : ".$WFC10Twilight_Path." versteckt.\n";
		}

    $module="IPSModuleManagerGUI";
	if (isset ($installedModules[$module]))
		{
		$mManager = new IPSModuleManager($module);
		$WFC10Module_Path    	 = $mManager->GetConfigValue('Path', 'WFC10');
		$categoryId_Module_Path  = CreateCategoryPath($WFC10Module_Path);
		IPS_SetHidden($categoryId_Module_Path, true); /* in der normalen Viz Darstellung verstecken */	
        $parent=IPS_GetParent($categoryId_Module_Path);
        if ( ($parent != "Administrator") && ($parent != "User") ) IPS_SetHidden($parent, true);
		echo "Module ".$module." Vizualisation Path : ".$WFC10Module_Path." versteckt.\n";
		}        

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung Harwdare Script starten um die aktuellen Werte zu erfassen, CustomComponent baut darauf auf
	 * dauert halt auch etwas.
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    if ($evaluateHardware)
        {
        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
        echo "\n";
        echo "Die EvaluateHardware Scripts sind auf               ".$CategoryIdAppEH."\n";
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
        echo "Evaluate Hardware hat die ScriptID                  ".$scriptIdEvaluateHardware." \n";
        IPS_RunScriptWait($scriptIdEvaluateHardware);
        echo "Script Evaluate Hardware wurde gestartet und bereits abgearbeitet. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
        }

    /*******************************
    *
    * Webfront Vorbereitung
    *
    ********************************/

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	echo "\n";
	echo "Custom Component Category OIDs for data : ".$CategoryIdData." for App : ".$CategoryIdApp."\n";

    /* check if Administrator and User Webfronts already available */
    
    $wfcHandling =  new WfcHandling();
    $WebfrontConfigID = $wfcHandling->installWebfront();

/*******************************
 *
 * Webfront Konfiguration einlesen
 *
 ********************************/
			
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
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
	
	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Variablen Profile für lokale Darstellung anlegen, sind die selben wie bei Remote Access
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	echo "Darstellung der Variablenprofile, wenn fehlt anlegen:\n";
	$profilname=array("Temperatur","TemperaturSet","Humidity","Switch","Button","Contact","Motion","mode.HM");
	foreach ($profilname as $pname)
		{
		if (IPS_VariableProfileExists($pname) == false)
			{
			echo "  Profil ".$pname." existiert nicht \n";
			switch ($pname)
				{
				case "Temperatur":
					IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
					IPS_SetVariableProfileText($pname,'',' °C');
					break;
				case "TemperaturSet":
					IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileDigits($pname, 1); // PName, Nachkommastellen
					IPS_SetVariableProfileValues ($pname, 6, 30, 0.5 );	// eingeschraenkte Werte von 6 bis 30 mit Abstand 0,5					
					IPS_SetVariableProfileText($pname,'',' °C');
					break;
				case "Humidity";
					IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
					IPS_SetVariableProfileText($pname,'',' %');
					break;
				case "Switch";
					IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileAssociation($pname, 0, "Aus","",0xff0000);   /*  Rot */
					IPS_SetVariableProfileAssociation($pname, 1, "Ein","",0x00ff00);     /* Grün */
					break;
				case "Contact";
					IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileAssociation($pname, 0, "Zu","",0xffffff);
					IPS_SetVariableProfileAssociation($pname, 1, "Offen","",0xffffff);
					break;
				case "Button";
					IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileAssociation($pname, 0, "Ja","",0xffffff);
					IPS_SetVariableProfileAssociation($pname, 1, "Nein","",0xffffff);
					break;
				case "Motion";
					IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileAssociation($pname, 0, "Ruhe","",0xffffff);
					IPS_SetVariableProfileAssociation($pname, 1, "Bewegung","",0xffffff);
					break;
				case "mode.HM";
					IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
					IPS_SetVariableProfileValues($pname, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
					IPS_SetVariableProfileAssociation($pname, 0, "Automatisch", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
					IPS_SetVariableProfileAssociation($pname, 1, "Manuell", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
					IPS_SetVariableProfileAssociation($pname, 2, "Profil1", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
					IPS_SetVariableProfileAssociation($pname, 3, "Profil2", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
					IPS_SetVariableProfileAssociation($pname, 4, "Profil3", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
					IPS_SetVariableProfileAssociation($pname, 5, "Urlaub", "", 0x5e2187); //P-Name, Value, Assotiation, Icon, Color
					//echo "Profil ".$pname." erstellt;\n";
					break;					
				default:
					break;
				}
			}
		else
			{
			echo "  Profil ".$pname." existiert. \n";
			}
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Variablen für Darstellung evaluieren
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
		
	/* Links für Webfront identifizieren, alle Verzeichnisse in CustomComponents Data /core/IPSComponent Verzeichnis 
	 *
	 * Trennung in Kategorien erfolgt durch - Zeichen nach Auswertung und Nachrichten
	 */
	 
	echo "\nLinks für Webfront Administrator und User identifizieren :\n";
	$webfront_links=array();
	$Category=IPS_GetChildrenIDs($CategoryIdData);
	foreach ($Category as $CategoryId)
		{
		echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		$Params = explode("-",IPS_GetName($CategoryId)); 
        if (sizeof($Params)>1)
            {
            $SubCategory=IPS_GetChildrenIDs($CategoryId);
            foreach ($SubCategory as $SubCategoryId)
                {
                if (IPS_GetObject($SubCategoryId)["ObjectIsHidden"] == false)           // versteckte Objekte nicht mehr im Webfront anzeigen
                    {
                    //echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
                    $webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
                    $webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["ORDER"]=IPS_GetObject($SubCategoryId)["ObjectPosition"];
                    }
                }
            }
		}
	/* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.	
	 *
	 */
	
    echo "Array webfront_Links als Input für die Webfront Erstellung: \n"; 
    //print_r($webfront_links);
    foreach ($webfront_links as $group => $webfront_link)
        {
        echo "Gruppe $group:\n";
        foreach ($webfront_link as $area => $entries)
            {
            echo "     Area $area:\n";
            if ($area != "Nachrichten")
                {
                foreach ($entries as $name => $entry) echo "       ".$entry["NAME"]." ".$entry["ORDER"]."\n";
                }
            }
        }

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
		echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in Kategorie ". $categoryId_AdminWebFront." (".IPS_GetName($categoryId_AdminWebFront).")\n";
        
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10_ConfigId.",Item:HouseTPA,Parent:".$WFC10_TabPaneParent.",Order:".$WFC10_TabPaneOrder."Name:,Icon:HouseRemote\n";        
 		echo "Webfront SubTabPane mit Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneItem.",Parent:HouseTPA,Order:20,Name:".$WFC10_TabPaneName.",Icon:".$WFC10_TabPaneIcon."\n";        
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTPA", $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, "HouseTPA",  20, $WFC10_TabPaneName, $WFC10_TabPaneIcon);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		/*************************************/
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);

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
			if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				{
				foreach ($webfront_link as $OID => $link)
					{
					/* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
			 		 * Auswertung kommt nach links und Nachrichten nach rechts
			 		 */					
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
				 		}
				 	if ($Group=="Nachrichten")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  $link["ORDER"]);
						}
					}
    			}
			}
		}
	else
	   {
	   /* Admin not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert sind
		*/
	   DeleteWFCItems($WFC10_ConfigId, "HouseTPA");
	   EmptyCategory($categoryId_WebFrontAdministrator);		
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
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10User_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10User_ConfigId." ".$WFC10User_TabPaneItem." ".$WFC10User_TabPaneParent." ".$WFC10User_TabPaneOrder." ".$WFC10User_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, "HouseTPU", $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, "", "HouseRemote");     /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, "HouseTPU",  20, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);      /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		/*************************************/

		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		IPS_SetHidden($categoryId_WebFrontUser,true);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $WFC10User_TabPaneItem.$Name;
			if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10User_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10User_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
			echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10User_TabPaneItem."\n";

			CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem, $WFC10User_TabPaneItem, 0, $Name, "");
			CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

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
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($WFC10User_ConfigId, "HouseTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_MobileWebFront         = CreateCategoryPath($Mobile_Path);
		IPS_SetHidden($categoryId_MobileWebFront,false);	/* mus dargestellt werden, sonst keine Anzeige am Mobiltelefon */	
			
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_MobileWebFront, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

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
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}
	else
	   {
	   /* Retro not enabled, alles loeschen */
	   }


	/****************************************************************************************************************
	 *                                                                                                    
	 *                                      COMPONENTS INSTALLATION
	 *
	 ****************************************************************************************************************/

if ($noinstall==false)
    {
	$commentField="zuletzt Konfiguriert von CustomComponent_Installation um ".date("h:i am d.m.Y ").".";

	IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	
	/****************************************************************************************************************
	 *                                                                                                    
	 *                                      Movement
	 *
	 ****************************************************************************************************************/


	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Ereignishandler für CustomComponents aktivieren, selbe Routine auch in RemoteAccess und DetectMovement.\n";
	echo "\n";
	
	/* nur die CustomComponent Funktion registrieren */
	/* Wenn Eder intrag in Datenbank bereits besteht wird er nicht mehr geaendert */



	if (function_exists('HomematicList'))
		{
		echo "Homematic Bewegungsmelder und Kontakte werden registriert.\n";
		$Homematic = HomematicList();
		$keyword="MOTION";
		foreach ($Homematic as $Key)
			{
			$found=false;
			if ( (isset($Key["COID"][$keyword])==true) )
				{
				/* alle Bewegungsmelder */

				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$found=true;
				}

			if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
				{
				/* alle Kontakte */

				$oid=(integer)$Key["COID"]["STATE"]["OID"];
				$found=true;
				}
			if ($found)
				{
 				$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
					{
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				else
					{
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}

				if (isset ($installedModules["RemoteAccess"]))
					{
					//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
					}
				else
					{
					/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
					echo "Remote Access nicht installiert, Variable ".IPS_GetName($oid)." selbst registrieren.\n";
					$messageHandler = new IPSMessageHandler();
					$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					/* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
					}
				}
			}
		}

	if (function_exists('FS20List'))
	   {
		echo "FS20 Bewegungsmelder und Kontakte werden registriert.\n";
		$TypeFS20=RemoteAccess_TypeFS20();
		$FS20= FS20List();
		foreach ($FS20 as $Key)
			{
			/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
			$found=false;
			if ( (isset($Key["COID"]["MOTION"])==true) )
   			{
	   		/* alle Bewegungsmelder */
		      $oid=(integer)$Key["COID"]["MOTION"]["OID"];
	   	   $found=true;
				}
			/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
			if ((isset($Key["COID"]["StatusVariable"])==true))
	   		{
   			foreach ($TypeFS20 as $Type)
   		   	{
	   	   	if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
		   	      {
     					$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
				      $found=true;
   		   	   }
	   	   	}
				}

			if ($found)
			   {
      		$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
			   	{
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				else
				   {
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}

				if (isset ($installedModules["RemoteAccess"]))
					{
					//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
					}
				else
				   {
				   /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
					echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
				   $messageHandler = new IPSMessageHandler();
				   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

				   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
				   }
				}
			}
		}
		
	if (isset ($installedModules["IPSCam"]))
		{
		IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");

		$camManager = new IPSCam_Manager();
		$config     = IPSCam_GetConfiguration();
	    echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
		foreach ($config as $cam)
	   	    {
		    echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
		    }
	    echo "Bearbeite lokale Kameras im Modul OperationCenter definiert:\n";
		if (isset ($installedModules["OperationCenter"]))
			{
			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			$OperationCenterConfig = OperationCenter_Configuration();
			echo "IPSCam und OperationCenter Modul installiert. \n";
			if (isset ($OperationCenterConfig['CAM']))
				{
				echo "Im OperationCenterConfig sind auch die CAM Variablen angelegt.\n";
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$OperationCenterScriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
					$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
					$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);

					$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
					echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;

    				$oid=$WebCam_MotionID;
    				$cam_name="IPCam_".$cam_name;
	  	      	    $variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
					   {
						echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
						}
					else
					   {
						echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
						}
			
					if (isset ($installedModules["RemoteAccess"]))
						{
						//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
						}
					else
					   {
					   /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
						echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
					   $messageHandler = new IPSMessageHandler();
					   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
					   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

					   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
						$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
					   }
					}

				}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
			}     /* isset OperationCenter */
		}     /* isset IPSCam */


	/****************************************************************************************************************
	 *
	 *                                      Switches
	 *
	 ****************************************************************************************************************/

    $componentHandling=new ComponentHandling();

	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Switch Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Switche werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		//installComponentFull(HomematicList(),"STATE",'IPSComponentSensor_RHomematic','IPSModuleSwitch_IPSHeat,');				/* Switche */
        $struktur1=$componentHandling->installComponentFull(HomematicList(),["STATE","INHIBIT","!ERROR"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField); 				/* Homematic Switche */
	    echo "***********************************************************************************************\n";
		$struktur2=$componentHandling->installComponentFull(HomematicList(),["STATE","SECTION","PROCESS"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField);			    /* HomemeaticIP Switche */       
        print_r($struktur1);            // Ausgabe RemoteAccess Variablen
        print_r($struktur2);
        }
	if (function_exists('FS20List'))
		{
	    echo "***********************************************************************************************\n";        
        $struktur3=$componentHandling->installComponentFull(FS20List(),"StatusVariable",'IPSComponentSwitch_RFS20','IPSModuleSwitch_IPSHeat,',$commentField);
        print_r($struktur3);
		}
	echo "***********************************************************************************************\n"; 	


	/****************************************************************************************************************
	 *
	 *                                      Temperature
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Temperatur Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
    if (function_exists('deviceList'))
        {
        echo "Temperatur Sensoren von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField,true);				/* Temperatursensoren und Homematic Thermostat */
        //print_r($result);
        }
    if (false) 
        {
        echo "Homematic Temperatur Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);				/* Temperatursensoren und Homematic Thermostat */
            $componentHandling->installComponentFull(HomematicList(),"ACTUAL_TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);		/* HomematicIP Thermostat */
            } 
        echo "FHT Heizungssteuerung Geräte werden registriert.\n";
        if (function_exists('FHTList'))
            {
            $componentHandling->installComponentFull(FHTList(),"TemeratureVar",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);
            } 	
        }

	/****************************************************************************************************************
	 *
	 *                                      Humidity
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Humidity Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Humidity Sensoren werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		$componentHandling->installComponentFull(HomematicList(),"HUMIDITY",'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,true);          // true mit Debug
		} 			
		
	/****************************************************************************************************************
	 *
	 *                                      Heat Control Actuators
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Heat Control Actuator Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Heat Control Actuator werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All',$commentField);
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_HomematicIP','IPSModuleHeatControl_All',$commentField);
		} 			
	echo "\n";
	echo "FHT80b Heat Control Actuator werden registriert.\n";
	if (function_exists('FHTList'))
		{
		//installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All');
		$componentHandling->installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All',$commentField);
		}

	echo "***********************************************************************************************\n";

	/****************************************************************************************************************
	 *
	 *                                      Heat Set (Thermostate, zB an der Wand)
	 *
	 ****************************************************************************************************************/
	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Heat Control Set Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Heat Set Werte werden aus den Thermostaten registriert.\n";
	if (function_exists('HomematicList'))
		{
		//installComponentFull(HomematicList(),array("SET_TEMPERATURE","WINDOW_OPEN_REPORTING"),'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		//installComponentFull(HomematicList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);

        $componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),["CONTROL_MODE","WINDOW_OPEN_REPORTING"],'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);
	    $componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),["CONTROL_MODE","!VALVE_STATE"],'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);
		} 	
	if ( (function_exists('FHTList')) && (sizeof(FHTList())>0) )
		{
    	echo "\n";
	    echo "FHT80b Heat Set Werte aus den Thermostaten werden registriert.\n";		
        //installComponentFull(FHTList(),"TargetTempVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All');
		$componentHandling->installComponentFull(FHTList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);
        
        $componentHandling->installComponentFull(FHTList(),"TargetModeVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);          
		}

	echo "***********************************************************************************************\n";
    
    }  // ende noinstall

	/****************************************************************************************************************
	 *
	 *                                      Functions
	 *
	 ****************************************************************************************************************/

    
echo "CustomCompenent Installation abgeschlossen. Optionen : ".($noinstall?"Kiene Installation der Components":"Installatione der Componenets")."  \n";
echo "\nAktuell vergangene Zeit : ".exectime($startexec,"s")." Sekunden\n";


?>