<?php

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
	 
	/**@defgroup Report Installation
	 *
	 * Script zur Visualisierung von Daten mit Highcharts, so auch das Wetter und andere Charts
     * modernisiert mit neuen Darstellungen für Easycharts und alternativ zu Money mit einem speziellen Tab
	 *
	 * mit dodelte kann man das Frontend neu aufbauen, immer dann wenn sich die Tabnamen ändern, Install dann zweimal aufrufen
     *
     * Installationsschritte:
     *      Initialisierung, Modul Handling Vorbereitung
     *      Webfront Vorbereitung, kein anlegen der Webfront Configuratoren Administartor und User, die müssen schon woanders angeelgt worden sein
     *      Program Installation, Constants, Associations etc.
     *      Report Config abarbeiten
     *      WebFront Installation
     *
     * Auswahl der anzuzeigenden Reports
     *      SelectReports , lokale Variable
     *          nach dem gewünschten report benannte Links auf Common/SelectValues
     *          kommt es zum Aufruf des ReportAction Manager, dieser führt zu einer Änderung der aufsteigend benannten Variablen in Common SelectValue[x]
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
	
	echo "\n";
    echo "Kernelversion           : ".IPS_GetKernelVersion()."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "IPSModulManager Version : ".$ergebnis."\n";
	$ergebnisCustoComponent=$moduleManager->VersionHandler()->GetVersion('CustomComponent')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	echo "CustomComponent Version : ".$ergebnisCustoComponent."\n";
	$ergebnisHighCharts=$moduleManager->VersionHandler()->GetVersion('IPSHighcharts')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	echo "IPSHighcharts Version   : ".$ergebnisHighCharts."\n";

    $dodelete=false;            // Webfront Report Tabs delete

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

    $ipsOps = new ipsOps();
    $webOps = new webOps();
	$wfcHandling = new WfcHandling();		// für die Interoperabilität mit den alten WFC Routinen nocheinmal mit der Instanz als Parameter aufrufen

/*******************************
 *
 * Webfront Vorbereitung, kein anlegen der Webfront Configuratoren Administartor und User, die müssen schon woanders angeelgt worden sein
 *
 ********************************/

    $configWFront=$ipsOps->configWebfront($moduleManager);
	$WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();

	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)   	    $WFC10_ConfigId       = $WebfrontConfigID["Administrator"];	
	if ($WFC10User_Enabled==true)		$WFC10User_ConfigId       = $WebfrontConfigID["User"];        
	if ($Mobile_Enabled==true)  		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');	
	if ($Retro_Enabled==true)   		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');


/*******************************
 *
 * Program Installation, Constants, Associations etc.
 *
 ********************************/
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryIdCommon       = CreateCategory('Common',          $CategoryIdData, 10);
	$categoryIdValues       = CreateCategory('Values',          $CategoryIdData, 20);
	$categoryIdSelectValues = CreateCategory('SelectValues',    $categoryIdCommon, 1000);

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
    //echo "Report_GetConfiguration abarbeiten. Es gibt ".count($report_config)." Einträge. Das ist die lange detaillierte Liste.\n";

  	$count=0;
	$associationsValues = array();
	foreach ($report_config as $displaypanel=>$values)
		{
		//echo "     Profileintrag ".$displaypanel."  \n";
		$associationsValues[$count]=$displaypanel;
		$count++;
  		}
	CreateProfile_Associations ('IPSReport_SelectValues',     $associationsValues);             // wird augenscheinlich nicht verwendet
	//print_r($associationsValues);

	// ===================================================================================================
	// Add Variables
	// ===================================================================================================
	$variableIdTypeOffset  = CreateVariable(IPSRP_VAR_TYPEOFFSET,  1 /*Integer*/, $categoryIdCommon,  10, 'IPSReport_TypeAndOffset',   $scriptIdActionScript,  IPSRP_TYPE_KWH, 'Clock');
	$variableIdPeriodCount = CreateVariable(IPSRP_VAR_PERIODCOUNT, 1 /*Integer*/, $categoryIdCommon,  20, 'IPSReport_PeriodAndCount',  $scriptIdActionScript,  IPSRP_PERIOD_DAY, 'Clock');
	$variableIdTimeOffset  = CreateVariable(IPSRP_VAR_TIMEOFFSET,  1 /*Integer*/, $categoryIdCommon,  40, '',                                null,                   0, '');
	$variableIdTimeCount   = CreateVariable(IPSRP_VAR_TIMECOUNT,   1 /*Integer*/, $categoryIdCommon,  50, '',                                null,                   1, '');

    echo "Defaultwerte für Zwischenspeicherung Werte lezter Auswahl der Periode, Speicherung in Kategorie $categoryIdCommon:\n";
    // defaultwerte, Speicherung letzter Wert, wird verwendet beim Schalten zwischen den Perioden
    // function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
    $variableIdPeriodYear  = CreateVariableByName($categoryIdCommon,"PeriodYearLast",   1 /*Integer*/,false,false,   5000);
    $variableIdPeriodMonth = CreateVariableByName($categoryIdCommon,"PeriodMonthLast",  1 /*Integer*/,false,false,   5010);
    $variableIdPeriodWeek  = CreateVariableByName($categoryIdCommon,"PeriodWeekLast",   1 /*Integer*/,false,false,   5020);
    $variableIdPeriodDay   = CreateVariableByName($categoryIdCommon,"PeriodDayLast",    1 /*Integer*/,false,false,   5030);
    $variableIdPeriodHour  = CreateVariableByName($categoryIdCommon,"PeriodHourLast",   1 /*Integer*/,false,false,   5040);

	/* Höhe kann nicht absolut angegeben werden, ausserdem wird die Variable noch von Highcharts ueberschrieben */
	$variableIdChartHtml   = CreateVariable(IPSRP_VAR_CHARTHTML,   3 /*String*/,  $categoryIdCommon, 100, '~HTMLBox', $scriptIdActionScript, '<iframe frameborder="0" width="100%" height="530px"  src="../user/Highcharts/IPS_Template.php" </iframe>', 'Graph');

    // Report Umschalter auf der inken Seite
	if ( function_exists("Report_GetValueConfiguration") == true )
		{
        echo "Evaluate function Report_GetValueConfiguration.\n";
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
        $webOps->setConfigButtons(9000);                    // order in ID display
        $buttonIds = $webOps->createSelectButtons($associationsValues,$categoryIdSelectValues, $scriptIdActionScript);
		}      
    else echo "Fehler, function Report_GetValueConfiguration nicht verfügbar in Report_Configuration.\n";


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


	if ($WFC10_Enabled) 
        {
        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID
        $configWf=$configWFront["Administrator"];

        echo "\nWebportal Report SplitPane installieren in: ".$configWf["Path"]." \n";
		$categoryId_WebFront         = CreateCategoryPath($configWf["Path"]);         // neueste Variante es gibt bereits "", "2" und jetzt "3"
		$ipsOps->emptyCategory($categoryId_WebFront);
		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFront, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFront, 20);
        SetValue($visualizationCategoryID,$categoryIdRight);
		echo "Kategorien erstellt, Main: ".$categoryId_WebFront." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";

        echo "\n";
        echo "Webfront SplitPane mit Parameter : ConfigId ".$configWf["ConfigId"]." TabItem ".$configWf["TabItem"]." TabPaneItem ".$configWf["TabPaneItem"]." TabPaneParent ".$configWf["TabPaneParent"];
        echo " TabPaneOrder ".$configWf["TabPaneOrder"]." TabPaneName ".$configWf["TabPaneName"]." TabPaneIcon ".$configWf["TabPaneIcon"]."\n";
        if ($dodelete) $wfcHandling->DeleteWFCItems("Report");         // alles was mit report anfängt
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);      



        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID
        //$wfcHandling->deletePane ($WFC10_ConfigId);
        //$wfcHandling->deletePane ($configWf["TabItem"]);
        // function CreateWFCItemSplitPane ($ItemId, $ParentId, $Position, $Title, $Icon="", $Alignment=0 /*0=horizontal, 1=vertical*/, $Ratio=50, $RatioTarget=0 /*0 or 1*/, $RatioType /*0=Percentage, 1=Pixel*/, $ShowBorder='true' /*'true' or 'false'*/) 
		$wfcHandling->CreateWFCItemSplitPane ($configWf["TabPaneItem"], $configWf["TabPaneParent"], $configWf["TabPaneOrder"], $configWf["TabPaneName"], $configWf["TabPaneIcon"], 1 /*Vertical*/, 15 /*Width*/, 0 /*Target=Pane1*/, 0 /*1 Percentage, 1 UsePixel*/, 'true');
		$wfcHandling->CreateWFCItemCategory  ($configWf["TabItem"].'_Right',  $configWf["TabPaneItem"],   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);
		$wfcHandling->CreateWFCItemCategory  ($configWf["TabItem"].'_Left',   $configWf["TabPaneItem"],   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);

        $categoryIdButtonGroup  = CreateVariableByName($categoryIdLeft, "Select Report", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
        foreach ($buttonIds as $id => $button)
            {
            //print_R($button);
            CreateLinkByDestination(" ", $button["ID"], $categoryIdButtonGroup, $id+100);         // kein Name, sonst zu Viel Platzbedarf, Profil hat einen Namen, geht nicht mit CreateLink

            }
			
		// Right Panel
		CreateLink('Type/Offset',       $variableIdTypeOffset,  $categoryIdRight, 10);
		CreateLink('Zeitraum',          $variableIdPeriodCount, $categoryIdRight, 20);
		CreateLink('Chart',             $variableIdChartHtml,   $categoryIdRight, 40);
        CreateLink('AddSelector',       $ReportDataSelectorID,  $categoryIdRight, 10);
        CreateLink('DataTable',         $ReportDataTableID,     $categoryIdRight, 110);
        
        $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
        foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
            {
            echo "\n------$index:\n";
            $wfcHandling->print_wfc($wfc[$index]);
            } 
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       
        }
		
	if ($WFC10User_Enabled)
		{
        $configWf=$configWFront["User"];
        echo "\nWebportal User Report installieren in: ".$configWf["Path"]." \n";
		$categoryId_WebFront         = CreateCategoryPath($configWf["Path"]);
		}

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



?>