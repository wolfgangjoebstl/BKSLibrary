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
	 
	/**@defgroup Report
	 *
	 * Script zur Visualisierung von Daten mit Highcharts, so ei auch das Wetter und andere Charts
	 *
	 *
	 * @file          Report_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.12.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/
 
	
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('Report_class.php', 					'IPSLibrary::app::modules::Report');    

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';	
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Report',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSComponent','2.50.1');
	
	echo "\nKernelversion         : ".IPS_GetKernelVersion()."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "IPSModulManager Version : ".$ergebnis."\n";
	$ergebnisCustoComponent=$moduleManager->VersionHandler()->GetVersion('CustomComponent')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	echo "CustomComponent Version : ".$ergebnisCustoComponent."\n";
	$ergebnisHighCharts=$moduleManager->VersionHandler()->GetVersion('IPSHighcharts')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	echo "IPSHighcharts Version   : ".$ergebnisHighCharts."\n";

    echo "\n";
    $knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
    $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
    $inst_modules = "Verfügbare Module und die installierte Version :\n\n";
    $inst_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";

    $upd_modules = "Module die upgedated werden müssen und die installierte Version :\n\n";
    $upd_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";

    foreach ($knownModules as $module=>$data)
        {
        $infos   = $moduleManager->GetModuleInfos($module);
        $inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
        if (array_key_exists($module, $installedModules))
            {
            //$html .= "installiert als ".str_pad($installedModules[$module],10)."   ";
            $inst_modules .= "installiert als ".str_pad($infos['CurrentVersion'],10)."   ";
            if ($infos['Version']!=$infos['CurrentVersion'])
                {
                $inst_modules .= "***";
                $upd_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10)." ".str_pad($infos['CurrentVersion'],10)."   ".$infos['Description']."\n";
                $loadfromrepository[]=$module;
                }
            }
        else
            {
            $inst_modules .= "nicht installiert            ";
            }
        $inst_modules .=  $infos['Description']."\n";
        }
        
    echo $inst_modules;
    echo "\n".$upd_modules;
    echo "-----------------------------------------------\n\n";

	if (isset ($installedModules["IPSHighcharts"]))
		{
		}
	else
		{
		echo "Zuerst IPSHighcharts installiern:\n";
		$LBG_Highcharts = new IPSModuleManager('IPSHighcharts', $repositoryIPS);
		$LBG_Highcharts->LoadModule();
		$LBG_Highcharts->InstallModule(true);
		}	
	
	IPSUtils_Include ("IPSInstaller.inc.php",                      "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",               "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",     "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("Report_Constants.inc.php",      				"IPSLibrary::app::modules::Report");
	IPSUtils_Include ('Report_Configuration.inc.php', 					'IPSLibrary::config::modules::Report');

/*******************************
 *
 * Webfront Vorbereitung
 *
 ********************************/

	/* Webfront GUID herausfinden */
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".IPS_GetName($instanz)." ID : ".$result["InstanceID"]."\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
	    }
	
	echo "\nVorgesehene Webfronts für die Darstellung aus dem .ini File:\n";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled==true)
		{
		/* kann ich selber herausfinden, ist die Admistrator ID von der Konfigurator Instanz des Webfront, es gibt auch noch User */
		//$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
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
		echo "WF10 \n";
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

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled==true)
		{
		echo "WF10User \n";
    	$WFC10User_ConfigId       = $WebfrontConfigID["User"];
		$WFC10User_Path       = $moduleManager->GetConfigValue('Path', 'WFC10User');
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)              // funktinoiert auch mit Defaultwerten
		{
		echo "Mobile \n";
		$Mobile_Path          = $moduleManager->GetConfigValue('Path', 'Mobile');
		$Mobile_PathOrder     = $moduleManager->GetConfigValueIntDef('PathOrder', 'Mobile',15);
		$Mobile_PathIcon      = $moduleManager->GetConfigValueDef('PathIcon', 'Mobile','Graph');
		$Mobile_Name          = $moduleManager->GetConfigValueDef('Name', 'Mobile', 'Report');
		$Mobile_Order         = $moduleManager->GetConfigValueIntDef('Order', 'Mobile',15);
		$Mobile_Icon          = $moduleManager->GetConfigValueDef('Icon', 'Mobile','Image');
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
	   {
		echo "Retro \n";
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		}

/*******************************
 *
 * Program Installation, Constants, Associations etc.
 *
 ********************************/
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryIdCommon   = CreateCategory('Common',  $CategoryIdData, 10);
	$categoryIdValues   = CreateCategory('Values',  $CategoryIdData, 20);
	//$categoryIdCustom   = CreateCategory('Custom',  $CategoryIdData, 30);

    /* muss bevor die Class zum ersten Mal aufgerufen wird bereits vorhanden sein */
    $visualizationCategoryID = CreateVariableByName($CategoryIdData, "VisualizationCategory", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

	// Add Scripts
	$scriptIdActionScript   = IPS_GetScriptIDByName('Report_ActionManager', $CategoryIdApp);
	//$scriptIdNavPrev        = IPS_GetScriptIDByName('IPSPowerControl_NavigatePrev', $CategoryIdApp);
	//$scriptIdNavNext        = IPS_GetScriptIDByName('IPSPowerControl_NavigateNext', $CategoryIdApp);
	//IPS_SetIcon($scriptIdNavPrev, 'HollowArrowLeft');
	//IPS_SetIcon($scriptIdNavNext, 'HollowArrowRight');
	//$scriptIdCountPlus      = IPS_GetScriptIDByName('IPSPowerControl_NavigatePlus', $CategoryIdApp);
	//$scriptIdCountMinus     = IPS_GetScriptIDByName('IPSPowerControl_NavigateMinus', $CategoryIdApp);
	//IPS_SetIcon($scriptIdCountPlus,  'HollowArrowUp');
	//IPS_SetIcon($scriptIdCountMinus, 'HollowArrowDown');

/*
	$timerId_Refresh     = CreateTimer_CyclicBySeconds ('CalculateWattValues', $scriptIdActionScript, IPSPC_REFRESHINTERVAL_WATT) ;
	$timerId_Refresh     = CreateTimer_CyclicByMinutes ('CalculateKWHValues',  $scriptIdActionScript, IPSPC_REFRESHINTERVAL_KWH) ;
*/

	$associationsTypeAndOffset   = array(
	                               IPSRP_TYPE_WATT        => 'Watt',
	                               IPSRP_TYPE_KWH         => 'kWh',
	                               IPSRP_TYPE_EURO        => 'Euro',
	                               IPSRP_TYPE_STACK       => 'Details',
	                               IPSRP_TYPE_STACK2      => 'Total',
	                               IPSRP_TYPE_PIE         => 'Pie',
	                               IPSRP_TYPE_OFF         => 'Off',
	                               IPSRP_OFFSET_SEPARATOR => ' ',
	                               IPSRP_OFFSET_PREV      => '<<',
	                               IPSRP_OFFSET_VALUE     => '0',
	                               IPSRP_OFFSET_NEXT      => '>>'
	                               );
	CreateProfile_Associations ('IPSReport_TypeAndOffset',   $associationsTypeAndOffset);

	$associationsPeriodAndCount  = array(
	                              IPSRP_PERIOD_HOUR     => 'Stunde',
	                              IPSRP_PERIOD_DAY      => 'Tag',
	                              IPSRP_PERIOD_WEEK     => 'Woche',
	                              IPSRP_PERIOD_MONTH    => 'Monat',
	                              IPSRP_PERIOD_YEAR     => 'Jahr',
	                              IPSRP_COUNT_SEPARATOR => ' ',
	                              IPSRP_COUNT_MINUS     => '-',
	                              IPSRP_COUNT_VALUE     => '1',
	                              IPSRP_COUNT_PLUS      => '+',
	                              );
	CreateProfile_Associations ('IPSReport_PeriodAndCount',   $associationsPeriodAndCount);


/*******************************
 *
 * Report Config abarbeiten
 *
 ********************************/

    $pcManager = new ReportControl_Manager();
  	$report_config=$pcManager->getConfiguration();
    echo "\n";
    echo "Report_GetConfiguration abarbeiten. Es gibt ".count($report_config)." Einträge. Das ist die lange detaillierte Liste.\n";

  	$count=0;
	$associationsValues = array();
	foreach ($report_config as $displaypanel=>$values)
		{
		//echo "     Profileintrag ".$displaypanel."  \n";
		$associationsValues[$count]=$displaypanel;
		$count++;
  		}
	CreateProfile_Associations ('IPSReport_SelectValues',     $associationsValues);
	//print_r($associationsValues);

	// ===================================================================================================
	// Add Variables
	// ===================================================================================================
	$variableIdTypeOffset  = CreateVariable(IPSRP_VAR_TYPEOFFSET,  1 /*Integer*/, $categoryIdCommon,  10, 'IPSReport_TypeAndOffset',   $scriptIdActionScript,  IPSRP_TYPE_KWH, 'Clock');
	$variableIdPeriodCount = CreateVariable(IPSRP_VAR_PERIODCOUNT, 1 /*Integer*/, $categoryIdCommon,  20, 'IPSReport_PeriodAndCount',  $scriptIdActionScript,  IPSRP_PERIOD_DAY, 'Clock');
	$variableIdTimeOffset  = CreateVariable(IPSRP_VAR_TIMEOFFSET,  1 /*Integer*/, $categoryIdCommon,  40, '',                                null,                   0, '');
	$variableIdTimeCount   = CreateVariable(IPSRP_VAR_TIMECOUNT,   1 /*Integer*/, $categoryIdCommon,  50, '',                                null,                   1, '');
	/* Höhe kann nicht absolut angegeben werden, ausserdem wird die Variable noch von Highcharts ueberschrieben */
	$variableIdChartHtml   = CreateVariable(IPSRP_VAR_CHARTHTML,   3 /*String*/,  $categoryIdCommon, 100, '~HTMLBox',                        $scriptIdActionScript, '<iframe frameborder="0" width="100%" height="530px"  src="../user/Highcharts/IPS_Template.php" </iframe>', 'Graph');

	if ( function_exists("Report_GetValueConfiguration") == true )
		{
		foreach (Report_GetValueConfiguration() as $idx=>$data)
			{
			$valueType = $data[IPSRP_PROPERTY_VALUETYPE];
			switch($valueType)
				{
				case IPSRP_VALUETYPE_GAS:
					$variableIdValueM3     = CreateVariable(IPSRP_VAR_VALUEM3.$idx,    2 /*float*/,   $categoryIdValues,  100+$idx, '~Gas',    null,          0, 'Lightning');
					break;
				case IPSRP_VALUETYPE_WATER:
					$variableIdValueM3     = CreateVariable(IPSRP_VAR_VALUEM3.$idx,    2 /*float*/,   $categoryIdValues,  100+$idx, '~Water',    null,          0, 'Lightning');
					break;
				default:
					$variableIdValueKWH     = CreateVariable(IPSRP_VAR_VALUEKWH.$idx,    2 /*float*/,   $categoryIdValues,  100+$idx, '~Electricity',    null,          0, 'Lightning');
					$variableIdValueWatt    = CreateVariable(IPSRP_VAR_VALUEWATT.$idx,   2 /*float*/,   $categoryIdValues,  200+$idx, '~Watt.14490',     null,          0, 'Lightning');
				}
			$variableIdSelectValue  = CreateVariable(IPSRP_VAR_SELECTVALUE.$idx, 0 /*Boolean*/, $categoryIdCommon,  100+$idx, '~Switch', $scriptIdActionScript, 0, 'Lightning');
			}
		}


	// ----------------------------------------------------------------------------------------------------------------------------
	// Custom Installation
	// ----------------------------------------------------------------------------------------------------------------------------

    /* weitere Möglichkeit die Anzeige der Daten zu selektieren
     * den Ort wo die Links für Hide/unhide abgespeichert sein einfach als Variable mit abspeichern
     *
     */

    //$visualizationCategoryID = CreateVariableByName($CategoryIdData, "VisualizationCategory", 1);   // vor class Aufruf unterbringen

    $ReportDataSelectorID = CreateVariableByName($CategoryIdData, "ReportDataSelector", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$pname="ReportDataSelect";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		}
    IPS_SetVariableCustomProfile($ReportDataSelectorID,$pname);
    $ReportDataTableID = CreateVariableByName($CategoryIdData, "ReportDataTable",3,'~HTMLBox');

/* alte Variante, bald nicht mehr benötigt */

	$ReportPageTypeID = CreateVariableByName($CategoryIdData, "ReportPageType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$ReportTimeTypeID = CreateVariableByName($CategoryIdData, "ReportTimeType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,null,"");

	IPSUtils_Include ('Report_Configuration.inc.php', 'IPSLibrary::config::modules::Report');

	$pname="ReportPageControl";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		}

  	$count=0;
	foreach ($report_config as $displaypanel=>$values)              // da sind noch die Farben dabei
		{
	   echo "      Profileintrag ".$displaypanel." mit Farbe ".$values['color'].". \n";
	   IPS_SetVariableProfileAssociation($pname, $count, $displaypanel, "", $values['color']); //P-Name, Value, Assotiation, Icon, Color
	   $count++;
  		}
    IPS_SetVariableProfileValues($pname, 0, $count, 1); //PName, Minimal, Maximal, Schrittweite
	//echo "Profil erstellt mit ".$count. " Einträgen.\n";
	IPS_SetVariableCustomProfile($ReportPageTypeID,$pname); // Ziel-ID, P-Name
    echo "\n";

/*	$pname="ReportTimeControl";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); // PName, Typ 0 Boolean 1 Integer 2 Float 3 String
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues($pname, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation($pname, 0, "Tag", "", 0xc0c0c0); //P-Name, Value, Assotiation, Icon, Color=grau
  	   IPS_SetVariableProfileAssociation($pname, 1, "Woche", "", 0x00f0c0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 2, "Monat", "", 0xf040f0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 3, "Jahr", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil erstellt;\n";
		}
	IPS_SetVariableCustomProfile($ReportTimeTypeID,$pname); // Ziel-ID, P-Name    */

	$associationsPeriodAndCount  = array(
	                              //IPSPC_PERIOD_HOUR     => 'Stunde',
	                              IPSRP_PERIOD_DAY      => 'Tag',
	                              IPSRP_PERIOD_WEEK     => 'Woche',
	                              IPSRP_PERIOD_MONTH    => 'Monat',
	                              IPSRP_PERIOD_YEAR     => 'Jahr',
	                              IPSRP_COUNT_SEPARATOR => ' ',
	                              IPSRP_COUNT_MINUS     => '-',
	                              IPSRP_COUNT_VALUE     => '1',
	                              IPSRP_COUNT_PLUS      => '+',
	                              );
	CreateProfile_Associations ('ReportTimeControl',   $associationsPeriodAndCount);
	IPS_SetVariableCustomProfile($ReportTimeTypeID,'ReportTimeControl'); // Ziel-ID, P-Name    */
	SetValue($ReportTimeTypeID,IPSRP_PERIOD_DAY);
	
	// Add Scripts, they have auto install
	$scriptIdReport   = IPS_GetScriptIDByName('Report', $CategoryIdApp);
	IPS_SetVariableCustomAction($ReportPageTypeID, $scriptIdReport);
	IPS_SetVariableCustomAction($ReportTimeTypeID, $scriptIdReport);
	IPS_SetVariableCustomAction($ReportDataSelectorID, $scriptIdReport);

	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	if ($WFC10_Enabled) {
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path."2");
		EmptyCategory($categoryId_WebFront);
		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFront, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFront, 20);
        SetValue($visualizationCategoryID,$categoryIdRight);
		echo "Kategorien erstellt, Main: ".$categoryId_WebFront." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";

		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		echo "Webfront ".$WFC10_ConfigId." löscht TabItem :".$tabItem."\n";
		DeleteWFCItems($WFC10_ConfigId, $tabItem);
		echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
		CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem,           $WFC10_TabPaneItem,    $WFC10_TabOrder,     $WFC10_TabName."2",     $WFC10_TabIcon, 1 /*Vertical*/, 360 /*Width*/, 0 /*Target=Pane1*/, 1/*UsePixel*/, 'true');
		CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
		CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

		// Left Panel

		$instanceId = CreateDummyInstance("Berichte", $categoryIdLeft, 30);
		if ( function_exists("Report_GetValueConfiguration") == true )
			{		
			foreach (Report_GetValueConfiguration() as $idx=>$data)
				{
				if ($data[IPSRP_PROPERTY_DISPLAY])
					{
					$variableIdSelectValue  = IPS_GetObjectIDByIdent(IPSRP_VAR_SELECTVALUE.$idx, $categoryIdCommon);
					$valueType = $data[IPSRP_PROPERTY_VALUETYPE];
					switch($valueType)
						{
						case IPSRP_VALUETYPE_GAS:
							CreateLink($data[IPSRP_PROPERTY_NAME], $variableIdSelectValue, $categoryIdLeft, $idx);
							break;
						case IPSRP_VALUETYPE_WATER:
							CreateLink($data[IPSRP_PROPERTY_NAME], $variableIdSelectValue, $categoryIdLeft, $idx);
							break;
						default:
							CreateLink($data[IPSRP_PROPERTY_NAME], $variableIdSelectValue, $instanceId, $idx);
						}
					}
				}
			}
			
		// Right Panel
		CreateLink('Type/Offset',       $variableIdTypeOffset,  $categoryIdRight, 10);
		CreateLink('Zeitraum',          $variableIdPeriodCount, $categoryIdRight, 20);
		CreateLink('Chart',             $variableIdChartHtml,   $categoryIdRight, 40);
        CreateLink('AddSelector',       $ReportDataSelectorID,   $categoryIdRight, 100);
        CreateLink('DataTable',         $ReportDataTableID,   $categoryIdRight, 110);

		ReloadAllWebFronts();
	}


/* alte variante, bald nicht mehr benötigt */
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('Uebersicht', $variableIdHTML,    $categoryId_WebFront,  20);
		CreateLinkByDestination('ReportPageType', $ReportPageTypeID,    $categoryId_WebFront,  10);
		CreateLinkByDestination('ReportTimeType', $ReportTimeTypeID,    $categoryId_WebFront,  11);
		}
		
	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren auf ".$WFC10User_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

		}


	// ----------------------------------------------------------------------------------------------------------------------------
	// Mobile Installation
	// ----------------------------------------------------------------------------------------------------------------------------

/*
	if ($Mobile_Enabled )
		{
		$mobileId  = CreateCategoryPath($Mobile_Path, $Mobile_PathOrder, $Mobile_PathIcon);
		$mobileId  = CreateCategory($Mobile_Name, $mobileId, $Mobile_Order, $Mobile_Icon);
		EmptyCategory($mobileId);

		CreateLink('Chart',         $variableIdChartHtml,   $mobileId, 10);

		$instanceIdChart  = CreateDummyInstance("Chart Auswahl", $mobileId, 20);
		CreateLink('Statistic', $variableIdTypeOffset,  $instanceIdChart, 10);

		$instanceIdChart  = CreateDummyInstance("Zeitraum", $mobileId, 30);
		CreateLink('Zeitraum',      $variableIdPeriodCount, $instanceIdChart, 50);
		CreateLink('Anzahl',        $variableIdTimeCount,   $instanceIdChart, 60);
		CreateLink('Anzahl -',      $scriptIdCountMinus,    $instanceIdChart, 70);
		CreateLink('Anzahl +',      $scriptIdCountPlus,     $instanceIdChart, 80);
		CreateLink('Zeit Offset',   $variableIdTimeOffset,  $instanceIdChart, 20);
		CreateLink('Zeit Zurück',   $scriptIdNavPrev,       $instanceIdChart, 30);
		CreateLink('Zeit Vorwärts', $scriptIdNavNext,       $instanceIdChart, 40);

		$instanceIdChart = CreateDummyInstance("Auswahl Verbraucher", $mobileId, 40);
		foreach (IPSPowerControl_GetValueConfiguration() as $idx=>$data) {
			if ($data[IPSPC_PROPERTY_DISPLAY]) {
				$variableIdSelectValue  = IPS_GetObjectIDByIdent(IPSPC_VAR_SELECTVALUE.$idx, $categoryIdCommon);
				$valueType = $data[IPSPC_PROPERTY_VALUETYPE];
				switch($valueType) {
					case IPSPC_VALUETYPE_GAS:
						CreateLink($data[IPSPC_PROPERTY_NAME], $variableIdSelectValue, $instanceIdChart, $idx);
						break;
					case IPSPC_VALUETYPE_WATER:
						CreateLink($data[IPSPC_PROPERTY_NAME], $variableIdSelectValue, $instanceIdChart, $idx);
						break;
					default:
						CreateLink($data[IPSPC_PROPERTY_NAME], $variableIdSelectValue, $instanceIdChart, $idx);
				}
			}
		}
	}

*/

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren auf ".$Mobile_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren auf ".$Retro_Path.": \n";
		createPortal($Retro_Path);
		}


/** Anlegen eines Profils mit Associations
	 *
	 * der Befehl legt ein Profile an und erzeugt für die übergebenen Werte Assoziationen
	 *
	 * @param string $Name Name des Profiles
	 * @param string $Associations[] Array mit Wert und Namens Zuordnungen
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param integer $Color[] Array mit Farbwerten im HTML Farbcode (z.b. 0x0000FF für Blau). Sonderfall: -1 für Transparent
	 * @param boolean $DeleteProfile Profile löschen und neu generieren
	 *
	 *   function CreateProfile_Associations ($Name, $Associations, $Icon="", $Color=-1, $DeleteProfile=true)
	 */


?>