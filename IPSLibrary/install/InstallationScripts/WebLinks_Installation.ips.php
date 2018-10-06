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
	 
	/**
	 *
	 * Script zur Erstellung von WebLinks
	 *
	 *
	 * @file          WebLinks_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 *
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\WebLinks\WebLinks_Configuration.inc.php");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('WebLinks',$repository);
		}
 	$installedModules = $moduleManager->GetInstalledModules();
	//print_r($installedModules);

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	// definition CreateCategory ($Name, $ParentId, $Position, $Icon=null)
	$CategoryIdDataWL=CreateCategory("WebLinks",IPS_GetParent($CategoryIdData),2000,"");
	echo "Kategorie WebLinks : ".$CategoryIdDataWL."  ".IPS_GetName($CategoryIdDataWL)."/".IPS_GetName(IPS_GetParent($CategoryIdDataWL))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($CategoryIdDataWL)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($CategoryIdDataWL))))."\n";

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfront Konfiguratoren mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId fuer WebLinks, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
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
 *
 ********************************/
 	
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValueDef('Path', 'WFC10',"Visualization.WebFront.Administrator.WebLinks");
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',"WebLinksTPA");
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',"roottp");
		$WFC10_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',"");
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',"Internet");
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10',"");
		$WFC10_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10',"");
		$WFC10_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10',"");
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
		$WFC10User_Path        	 = $moduleManager->GetConfigValueDef('Path', 'WFC10User',"Visualization.WebFront.Administrator.WebLinks");
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',"WebLinksTPU");
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',"roottp");
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',"");
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',"Internet");
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10User',"900");
		$WFC10User_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',"");
		$WFC10User_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10User',"");
		$WFC10User_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10User',"");
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10User',"");
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

	// umbauen auf Weblinks
	
/*******************************
 *
 * Variablen Profile Vorbereitung
 *
 ********************************/
 
 
/*******************************
 *
 * Links für Webfront identifizieren
 *
 ********************************/

    echo "\nWebfront Link Tabelle aufbauen:\n";
	$webfront_links=array();

	/* Abkuerzer, keine Kategorie in Data erstellt sondern gleich in Visualisation die Originalwerte abgelegt - funktioniert nicht da Inhalt geloescht wird bei Installation 
	 * in Visualization werden nur Links angelegt
	 */
	
		// definition: CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') {
	$WebLinkID=CreateVariable("FavouriteWebLinks",3, $CategoryIdDataWL,0,"~HTMLBox",null,null,"");
	echo $WFC10_Path."\n";
	echo $WebLinkID."  ".IPS_GetName($WebLinkID)."/".IPS_GetName(IPS_GetParent($WebLinkID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($WebLinkID)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($WebLinkID))))."\n";

	echo "Weblink Table aufbauen aus Config Tabelle:\n";
	$linkConfig=WebLinks_Configuration();
	$html="";
	//$html.='<iFrame>';
	$html.='<style> a:link    {color: green; background-color: transparent; text-decoration: none; }
                   a:visited {color: pink;  background-color: transparent; text-decoration: none; }
                   a:hover   {color: red;   background-color: transparent; text-decoration: underline; }
                   a:active  {color: yellow;background-color: transparent; text-decoration: underline; } </style>'; 
	$html.='<table>';
    $html.="<th> <tr> <td> Bezeichnung des Link </td> <td> Lokales LAN </td> <td> Internet </td> </tr> </th>";
	$rows=0;$columns=0;
	foreach ($linkConfig as $index => $config)
		{
		$html.='<tr>';
		$col=0;
		echo "    Weblinks Konfigurationen für ".$index." bearbeiten.\n";
		switch (strtoupper($index))
			{
			case "WEBLINKS":
                echo "      Modul WEBLINKS:\n";
				foreach ($config as $entry)
					{
					print_r($entry);
					if (isset($entry["NAME"])==false) $entry["NAME"]=$entry["LINK"];
					if (isset($entry["TITLE"])==false) $entry["TITLE"]=$entry["NAME"];
                    if (isset($entry["TYPE"])==false) $entry["TYPE"]="extern";
					$html.='<tr> <td>'.$entry["NAME"].'</td> <td> ';
                    if ($entry["TYPE"]=="extern") $html .= "</td> <td>";
                    $html .= '<a href="'.$entry["LINK"].'" target="_blank">'.$entry["TITLE"].'</a> </td> </tr>';
					}
				break;
			case "CAMERAS":
				if (isset ($installedModules["IPSCam"]))
					{
                    echo "      Modul CAMERAS:\n";
                	IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
					IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
					$camConfig = IPSCam_GetConfiguration();
					foreach ($camConfig as $idx=>$data) 
						{
						print_r($data);
						$result=explode(",",$data[IPSCAM_PROPERTY_COMPONENT]);
						$html.='<tr> <td>'.$data["Type"].' '.$data["Name"].'</td> <td> </td> <td> <a href="'.'http://'.$result[1].'" target="_blank">'.$data[IPSCAM_PROPERTY_NAME].'</a> </td> </tr>';
						}			
					}			
				break;
			default:
				break;
			}
		if ($col>$columns) $columns=$col;	
		}
	$html.='</table>';

	SetValue($WebLinkID,$html);
 
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
	
		/* basic Setup, gilt für alle Webfronts */
		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal Administrator.Autosteuerung Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		EmptyCategory($categoryId_WebFrontAdministrator);				// ausleeren und neu aufbauen, die Geschichte ist gelöscht !
		IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken

		/* TabPaneItem anlegen und wenn vorhanden vorher loeschen */
		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		if ( exists_WFCItem($WFC10_ConfigId, $WFC10_TabPaneItem) )
		 	{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$WFC10_TabPaneItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $WFC10_TabPaneItem);
			}
		else
			{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$WFC10_TabPaneItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

		/* im TabPane entweder eine Kategorie oder ein SplitPane und Kategorien anlegen */
		CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem,   $WFC10_TabPaneItem,   10, '', '', $categoryId_WebFrontAdministrator   /*BaseId*/, 'false' /*BarBottomVisible*/);

		// definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
		CreateLinkByDestination("Weblinks", $WebLinkID, $categoryId_WebFrontAdministrator,  10,"");

		}

/********************************************************************/



?>