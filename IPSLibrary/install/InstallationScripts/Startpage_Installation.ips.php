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
	 
	/**@defgroup 
	 * @ingroup 
	 * @{
	 *
	 * Script zur Ansteuerung der Startpage wird hier installiert
     * wenn Instanz von OpenWeatherData vorhanden ist, wird auch das Wetter eingebaut
     * notwendig geworden da Wunderground nicht mehr funktioniert
     *
     * Für die Startpage wird hier die Visualisiserung aneglegt
     *      Visualization.WebFront.Administrator. siehe WFC10 Path in config ini
     *
     * Für das Wetter Meteogram wird hier die Visualisierung angelegt:
     *      Visualization.WebFront.Administrator.Weather.OpenWeather
	 *
	 * Startet waehrend der Installation auch die beiden scripts
     *      	IPS_RunScript($scriptIdStartpage);              Startpage_copyfiles
     *	        IPS_RunScript($scriptIdStartpageWrite);         Startpage_schreiben
     *
     *
     * Wichtige Schritte der Installation:
     *      Initialisierung, Modul Handling Vorbereitung
     *      Initialisierung für Monitor On/Off Befehle und Bedienung VLC zum Fernsehen. Scripts verwenden wenn im Pfad ein Blank vorkommt.
     *      Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
     *      Webfront Konfiguration einlesen
     *      Webfront Variablen und Profile für Visualisiserung anlegen
     *      Initialisierung der Timer
     *      Initialisierung und Herstellung Webfront für OpenWeatherMap
	 *      WebFront Administrator Startpage Installation
	 *      WebFront User Startpage Installation          
     *
     *
	 * @file          Startpage_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/
	 
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Startpage',$repository);
	}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	//echo "\nKernelversion           : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version             : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Startpage');
	echo "\nStartpage       Version : ".$ergebnis;

    $installedModules = $moduleManager->GetInstalledModules();
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
    IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
    IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

    $debug=false;               // weniger Informationen im Echo erhöhen die Übersichtlichkeit

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();
    $webOps = new webOps();                     // Buttons anlegen, sind auch Profile, werden aber bei Install angelegt

	$wfcHandling = new WfcHandling();		// für die Interoperabilität mit den alten WFC Routinen nocheinmal mit der Instanz als Parameter aufrufen

/*******************************
 *
 * Initialisierung für Monitor On/Off Befehle und Bedienung VLC zum Fernsehen. Scripts verwenden wenn im Pfad ein Blank vorkommt.
 *
 ********************************/

    echo "\n\n";
    echo "Kernel Version (Revision) ist              : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
    echo "Kernel Datum ist                           : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
    echo "Kernel Startzeit ist                       : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
    echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
    echo "Kernel Install Dir ist auf                 : ".IPS_GetKernelDirEx()."\n";
    $operatingSystem = $dosOps->getOperatingSystem();
    echo "OperatingSystem ist                        : $operatingSystem\n";
	$verzeichnis     = $dosOps->getWorkDirectory();
    if ($verzeichnis===false) 
        {
        echo "Fehler, Work directory nicht verfügbar. bitte erstellen.\n";
        }
    else
        {
        echo "Kernel Working Directory ist auf           :  $verzeichnis\n";
        echo "\n";
        }
    $startpage = new StartpageHandler();         
    $unterverzeichnis=$startpage->getWorkDirectory();
   	$configuration = $startpage->getStartpageConfiguration();
    if ($debug) print_r($configuration);
    
    if (($dosOps->getOperatingSystem()) == "WINDOWS")
        {
        echo "Schreibe Batchfile zum automatischen Start und Stopp von VLC in Verzeichnis $unterverzeichnis:\n";
        if ( isset($configuration["Directories"]["VideoLan"]) == true ) $command=$configuration["Directories"]["VideoLan"];
        else $command='C:/Program Files/VideoLAN/VLC/VLC.exe';
        if ( isset($configuration["Directories"]["Playlist"]) == true ) $playlist=$configuration["Directories"]["Playlist"];	
        else $playlist = $verzeichnis."Fernsehprogramme\Technisat.m3u";

        $handle2=fopen($unterverzeichnis."start_vlc.bat","w");
        fwrite($handle2,'"'.$command.'"  "'.$playlist.'"'."\r\n");
        fwrite($handle2,'pause'."\r\n");
        fclose($handle2);

        //echo "  Schreibe Batchfile zum automatischen Stopp von VLC.\n";
        $handle2=fopen($unterverzeichnis."kill_vlc.bat","w");
        fwrite($handle2,'c:/Windows/System32/taskkill.exe /im vlc.exe');
        fwrite($handle2,"\r\n");
        fclose($handle2);
        }
    else echo "Unix System. Kein Handling von externen Programmen. \n";

    echo "Hinweis: Schwierigkeiten bei Programmaufrufs Pfaden mit einem Blank dazwischen.\n";  

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 * Webfront Konfiguration einlesen
 *
 ********************************/

	$WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();
	 
    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
	if ($WFC10User_Enabled==true)   $WFC10User_ConfigId       = $WebfrontConfigID["User"];
  
    $ipsOps->writeConfigWebfrontAll($configWFront);
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');


/*******************************
 *
 * Webfront Variablen und Profile für Visualisiserung anlegen
 *
 ********************************/

    $profileOps = new profileOps();
    echo "Darstellung der Variablenprofile für Startpage im lokalem Bereich, wenn fehlt anlegen:\n";
	$profilname=array("HTMLBox.NoTitle"=>"~HTMLBox", );
    $profileOps->synchronizeProfiles($profilname,true);             //true für Debug


    /* Uebersicht ist die Variable für die Darstellung der Seite 
     * Startpagetype wird von der Startpage Write Funktion verwendet verwendet
     */

	$StartPageTypeID = CreateVariableByName($CategoryIdData, "Startpagetype", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$variableIdStartpageHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,null,"");

    $ScreenHeightID  = CreateVariable("ScreenHeight", 1 /*Integer*/,  $CategoryIdData, 400, '', null,null,"");              // Bildschirmhöhe,kann angepasst werden, leider nicht individuell für jeden Browser
    SetValue($ScreenHeightID,800);

	/* 
	 * Variablen für die Topologiedarstellung generieren, abgeleitet vom Webfront des IPSModuleManagerGUI 
	 *
	 */

	$variableIdStatus        = CreateVariable(STARTPAGE_VAR_ACTION,      3 /*String*/,  $CategoryIdData, 10, '',  null,   'View1', '');
	$variableIdModule        = CreateVariable(STARTPAGE_VAR_MODULE,      3 /*String*/,  $CategoryIdData, 20, '',  null,   '', '');
	$variableIdInfo          = CreateVariable(STARTPAGE_VAR_INFO,        3 /*String*/,  $CategoryIdData, 30, '',  null,   '', '');
	$variableIdHTML          = CreateVariable(STARTPAGE_VAR_HTML,        3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,   '<iframe frameborder="0" width="100%" height="600px"  src="../user/Startpage/StartpageTopology.php"></iframe>', 'Information');

	SetValue($variableIdStatus,'View1');

	/* 
	 * Variable zum Umschalten der Bildschirme generieren 
	 *    SwitchScreen hat das Profil StartpageControl
	 */

    // ButtonProfil anpassen : \n";
	$pname="StartpageControl";
    $tabs=["Explorer","FullScreen","Station","Media", "Frame",  "Picture","Topologie","Hierarchie","Graph","Off"];
    $color=[0xc0c0c0,   0x00f0c0,  0xf040f0, 0xf04040, 0xc0c040, 0xf0c000, 0xc0f0c0,   0x40f0f0,  0x80a0c0,  0xf0f0f0];
    $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor

/*******************************
 *
 * Initialisierung der Timer
 *
 ********************************/

	/* 
	 * Add Scripts, they have auto install
	 * am Ende der SwitchScreen Variable auch Startpage_Schreiben als CustomAction zuweisen
	 * 
	 */
	
	$scriptIdStartpage   = IPS_GetScriptIDByName('Startpage_copyfiles', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdStartpage, 8*60*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */
	
	$scriptIdStartpageWrite   = IPS_GetScriptIDByName('Startpage_schreiben', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdStartpageWrite, 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

    $switchScreenID=CreateVariableByName($CategoryIdData,"SwitchScreen",1,"StartpageControl",false,0,$scriptIdStartpageWrite);               // $parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false
    $switchSubScreenID=CreateVariableByName($CategoryIdData,"SwitchSubScreen",1);                                                              // Station Screen hat mehrere Sub Screens
    $switchMediaID=CreateVariableByName($CategoryIdData,"SwitchMedia",1);

	IPS_RunScript($scriptIdStartpage);
	IPS_RunScript($scriptIdStartpageWrite);


/*******************************
 *
 * Initialisierung und Herstellung Webfront für OpenWeatherMap
 *
 * wenn es eine Instanz gibt wird eine Kategorie in Data und eine für die Links in der Visualization erstellt.
 * der Webfront Path ist fix und kann nicht geändert werden, das Item heisst OpenWeatherTPA.TPWeather
 * 
 * die HTML Box beinhaltet das Wort Zusammenfassung
 * die notwendigen Variablen für die Berechnung werden in der Startpage Kategorie angelegt
 *
 * insgesamt werden drei html Boxen verlinkt. Die erste ist das Meteogram
 *
 *********************************************************************/
 
	$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
	//$modulhandling->printLibraries();
	echo "\n";
	$modulhandling->printModules('IPSymconOpenWeatherMap');	
	$OWDs=$modulhandling->getInstances('OpenWeatherData');
	echo "\n"; 
	if (sizeof($OWDs)>0)
		{
		echo "Modul OpenWeatherMap ist installiert.\n";
		if (sizeof($OWDs)>1) echo "ACHTUNG: Zuviele OpenWeatherMap Instanzen sind installiert !\n";

	    $categoryId_OpenWeather = CreateCategory('OpenWeather',   $CategoryIdData, 2000);           // Kategorie in der data.modules.Startpage

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		$WFC10_OW_Path=	"Visualization.WebFront.Administrator.Weather.OpenWeather";									
		$categoryId_OW_WebFront         = CreateCategoryPath($WFC10_OW_Path);                       // Kategorie in der Visualization

        $wfcHandling->read_WebfrontConfig($configWFront["Administrator"]["ConfigId"]);         // register Webfront Confígurator ID
		$WFC10_OW_TabPaneItem="OpenWeatherTPA";$WFC10_OW_TabItem=""; $WFC10_OW_TabPaneParent="TPWeather"; $WFC10_OW_TabPaneOrder=100; $WFC10_OW_TabPaneIcon="Cloudy";
        //$WFC10_OW_TabPaneName="OpenWeather";
        $WFC10_OW_TabPaneName="";                       // Platz sparen in der ersten Leiste des alten Webfronts
		$wfcHandling->DeleteWFCItems( $WFC10_OW_TabPaneItem.$WFC10_OW_TabItem);           // delete alle panes mit "OpenWeatherTPA"

        /* früher von Weatherforecast angelegt: gibts nicht mehr : TabPaneItem=TPWeather, TabPaneParent=roottp, TabPaneName= , TabPaneOrder=10, TabPaneExclusive=false , TabPaneIcon=Cloud */
        //$wfcHandling->CreateWFCItemCategory  ($WFC10_OW_TabPaneParent,   "roottp",   10 , "", "Cloud", $categoryId_AdminWebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
        $wfcHandling->DeleteWFCItems($WFC10_OW_TabPaneParent);
        
		//CreateWFCItemTabPane      ($WFC10_ConfigId, $WFC10_OW_TabPaneItem, $WFC10_OW_TabPaneParent,  $WFC10_OW_TabPaneOrder, $WFC10_OW_TabPaneName, $WFC10_OW_TabPaneIcon);
		//CreateWFCItemExternalPage ($WFC10_ConfigId, $WFC10_TabPaneItem.$WFC10_TabItem, $WFC10_TabPaneItem, $WFC10_TabOrder, $WFC10_TabName, $WFC10_TabIcon, "user\/IPSWeatherForcastAT\/Weather.php", 'false' /*BarBottomVisible*/);
		//CreateWFCItemCategory  ($WFC10_ConfigId, $WFC10_OW_TabPaneItem.$WFC10_OW_TabItem,   $WFC10_OW_TabPaneItem,   $WFC10_OW_TabPaneOrder, '', $WFC10_OW_TabPaneIcon, $categoryId_OW_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
		
        /* in TPWeather wieder das Pane OpenWeatherTPA und die Category anlegen */
		$wfcHandling->CreateWFCItemTabPane   ( $WFC10_OW_TabPaneParent, "roottp" , $WFC10_OW_TabPaneOrder, $WFC10_OW_TabPaneName, $WFC10_OW_TabPaneIcon);
        $wfcHandling->CreateWFCItemCategory  ( $WFC10_OW_TabPaneItem,  $WFC10_OW_TabPaneParent,   10, "", "", $categoryId_OW_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
        //$wfcHandling->CreateWFCItemCategory  ( $WFC10_OW_TabPaneItem,  "roottp" ,   $WFC10_OW_TabPaneOrder, "", $WFC10_OW_TabPaneIcon, $categoryId_OW_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);

		EmptyCategory($categoryId_OW_WebFront);
		IPS_SetHidden($categoryId_OW_WebFront, true); 		/* alte Links ausräumen und in der normalen Viz Darstellung verstecken */	
		//CreateLinkByDestination('Openweather', $categoryId_OpenWeather,    $categoryId_OW_WebFront,  10);

		echo "\n";
		$i=1; $find="Zusammenfassung"; $gefunden=false;                 // die letzte Variable die das Wort Zusammenfassung enthält übernehmen und einen Link in die neu zusammengeräumte Visualization Kategorie setzen
		foreach ($OWDs as $OWD)
			{
			echo "Instanz $i: ".$OWD."   ".IPS_GetName($OWD)."\n";
			$childrens=IPS_GetChildrenIDs($OWD);
			foreach($childrens as $children)
				{
				//echo "Vergleiche ".IPS_GetName($children)."\n";
				if ( (strpos(IPS_GetName($children),$find)) !== false ) $gefunden=$children;
				}
			$i++;
			}
		echo "Html Box ist : $gefunden \n";
		//CreateLinkByDestination("OpenWeather", $gefunden, $categoryId_WebFrontAdministrator,  1000,"");	
		CreateLinkByDestination('Openweather', $gefunden,    $categoryId_OW_WebFront,  10);
		echo "\n";

		CreateProfile_Count        ('IPSWeatherForcastAT_Temp',  null, null,  null,     null, " °C", null);

		// Create Variables
		$LastRefreshDateTime     = CreateVariable("LastRefreshDateTime",    3 /*String*/,  $categoryId_OpenWeather,  10,  '',  null, '');
		$LastRefreshTime         = CreateVariable("LastRefreshTime",        3 /*String*/,  $categoryId_OpenWeather,  20,  '',  null, '');
		$TodaySeaLevel           = CreateVariable("SeaLevel",               1 /*Integer*/, $categoryId_OpenWeather,  30,  null,       null, 0);
		$TodayAirHumidity        = CreateVariable("AirHumidity",            3 /*String*/,  $categoryId_OpenWeather,  40,  '',  null, '');
		$TodayWind               = CreateVariable("Wind",                   3 /*String*/,  $categoryId_OpenWeather,  50,  '',  null, '');

		$TodayDayOfWeek          = CreateVariable("TodayDay",               3 /*String*/,  $categoryId_OpenWeather,  100,  '',  null, '');
		$TodayTempCurrent        = CreateVariable("TodayTempCurrent",       2, $categoryId_OpenWeather,  110,  'OpenWeatherMap.Temperatur',       null, 0);
		$TodayTempMin            = CreateVariable("TodayTempMin",           2, $categoryId_OpenWeather,  120,  'OpenWeatherMap.Temperatur',       null, 0);
		$TodayTempMax            = CreateVariable("TodayTempMax",           2, $categoryId_OpenWeather,  130,  'OpenWeatherMap.Temperatur',       null, 0);
		$TodayIcon               = CreateVariable("TodayIcon",              3 /*String*/,  $categoryId_OpenWeather,  140,  '',  null, '');
		$TodayTextShort          = CreateVariable("TodayForecastLong",      3 /*String*/,  $categoryId_OpenWeather,  150,  '',  null, '');
		$TodayTextLong           = CreateVariable("TodayForecastShort",     3 /*String*/,  $categoryId_OpenWeather,  160,  '',  null, '');

		$Forecast1DayOfWeek       = CreateVariable("TomorrowDay",           3 /*String*/,  $categoryId_OpenWeather,  200,  '',  null, '');
		$Forecast1TempMin         = CreateVariable("TomorrowTempMin",       2, $categoryId_OpenWeather,  210,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast1TempMax         = CreateVariable("TomorrowTempMax",       2, $categoryId_OpenWeather,  220,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast1TextShort       = CreateVariable("TomorrowForecastLong",  3 /*String*/,  $categoryId_OpenWeather,  230,  '',  null, '');
		$Forecast1TextLong        = CreateVariable("TomorrowForecastShort", 3 /*String*/,  $categoryId_OpenWeather,  240,  '',  null, '');
		$Forecast1Icon            = CreateVariable("TomorrowIcon",          3 /*String*/,  $categoryId_OpenWeather,  250,  '',  null, '');

		$Forecast2DayOfWeek       = CreateVariable("Tomorrow1Day",          3 /*String*/,  $categoryId_OpenWeather,  300,  '',  null, '');
		$Forecast2TempMin         = CreateVariable("Tomorrow1TempMin",      2, $categoryId_OpenWeather,  310,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast2TempMax         = CreateVariable("Tomorrow1TempMax",      2, $categoryId_OpenWeather,  320,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast2TextShort       = CreateVariable("Tomorrow1ForecastLong", 3 /*String*/,  $categoryId_OpenWeather,  330,  '',  null, '');
		$Forecast2TextLong        = CreateVariable("Tomorrow1ForecastShort",3 /*String*/,  $categoryId_OpenWeather,  340,  '',  null, '');
		$Forecast2Icon            = CreateVariable("Tomorrow1Icon",         3 /*String*/,  $categoryId_OpenWeather,  350,  '',  null, '');

		$Forecast3DayOfWeek       = CreateVariable("Tomorrow2Day",          3 /*String*/,  $categoryId_OpenWeather,  400,  '',  null, '');
		$Forecast3TempMin         = CreateVariable("Tomorrow2TempMin",      2, $categoryId_OpenWeather,  410,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast3TempMax         = CreateVariable("Tomorrow2TempMax",      2, $categoryId_OpenWeather,  420,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast3TextShort       = CreateVariable("Tomorrow2ForecastLong", 3 /*String*/,  $categoryId_OpenWeather,  430,  '',  null, '');
		$Forecast3TextLong        = CreateVariable("Tomorrow2ForecastShort",3 /*String*/,  $categoryId_OpenWeather,  440,  '',  null, '');
		$Forecast3Icon            = CreateVariable("Tomorrow2Icon",         3 /*String*/,  $categoryId_OpenWeather,  450,  '',  null, '');

		$Forecast4DayOfWeek       = CreateVariable("Tomorrow3Day",          3 /*String*/,  $categoryId_OpenWeather,  500,  '',  null, '');
		$Forecast4TempMin         = CreateVariable("Tomorrow3TempMin",      2, $categoryId_OpenWeather,  510,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast4TempMax         = CreateVariable("Tomorrow3TempMax",      2, $categoryId_OpenWeather,  520,  'OpenWeatherMap.Temperatur',       null, 0);
		$Forecast4TextShort       = CreateVariable("Tomorrow3ForecastLong", 3 /*String*/,  $categoryId_OpenWeather,  530,  '',  null, '');
		$Forecast4TextLong        = CreateVariable("Tomorrow3ForecastShort",3 /*String*/,  $categoryId_OpenWeather,  540,  '',  null, '');
		$Forecast4Icon            = CreateVariable("Tomorrow3Icon",         3 /*String*/,  $categoryId_OpenWeather,  550,  '',  null, '');

        /* create Highcharts Meteogram for better Weather Overview */

       	//$variableIdMeteoChartHtml   = CreateVariable("OpenWeatherMeteoHTML",   3 /*String*/,  $categoryId_OpenWeather, 1010, '~HTMLBox',$scriptIdStartpageWrite, '<iframe src="./user/IPSHighcharts/IPSTemplates/Highcharts.php?CfgFile=C:\IP-Symcon\webfront\user\IPSHighcharts\Highcharts\HighchartsCfgOpenweather.tmp" width="100%" height="616" frameborder="0" scrolling="no" ></iframe>', 'Graph');
        $variableIdMeteoChartHtml   = CreateVariable("OpenWeatherMeteoHTML",   3 /*String*/,  $categoryId_OpenWeather, 1010, '~HTMLBox',$scriptIdStartpageWrite, '<iframe src="./user/IPSHighcharts/IPSTemplates/Highcharts.php?CfgFile=.\user\IPSHighcharts\Highcharts\HighchartsCfgOpenweather.tmp" width="100%" height="616" frameborder="0" scrolling="no" ></iframe>', 'Graph');        
        CreateLinkByDestination('Meteogram', $variableIdMeteoChartHtml,    $categoryId_OW_WebFront,  10);
       	
        $variableIdZusammenfassungHtml   = CreateVariable("OpenWeatherZusammenfassung",   3 /*String*/,  $categoryId_OpenWeather, 1010, '~HTMLBox',$scriptIdStartpageWrite, '<iframe frameborder="0" width="100%" height="530px" scrolling="yes" src="../user/Startpage/Startpage_Openweather.php" </iframe>', 'Graph');
        CreateLinkByDestination('Zusammenfassung', $variableIdZusammenfassungHtml,    $categoryId_OW_WebFront,  20);

            //$wfc=$wfcHandling->read_wfc(1);
            $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
            foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                {
                echo "\n------$index:\n";
                $wfcHandling->print_wfc($wfc[$index]);
                } 
            $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);
		}

    /* Initialissierung für ORF Wetter
     *
     */

    if (isset($installedModules["Guthabensteuerung"]))
        {

        IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
        IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

        echo "Guthabensteuerung installiert.\n";

        $guthabenHandler = new GuthabenHandler(true,true,true);         // true,true,true Steuerung für parsetxtfile
        $GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //get_GuthabenAllgemeinConfig();

        //print_R($GuthabenAllgConfig);
        if ( ($GuthabenAllgConfig["OperatingMode"]=="Selenium") && (isset($GuthabenAllgConfig["Selenium"]["Hosts"]["ORF"])) )
            {
            echo "ORF Wetter und Fernsehprogramm werden eingelesen:\n";
            $categoryId_OrfWeather = CreateCategory('OrfWeather',   $CategoryIdData, 2200); 
            $variableIdOrfText   = CreateVariable("OrfWeatherReportHTML",   3 /*String*/,  $categoryId_OrfWeather, 1010, '~HTMLBox');        

            CreateLinkByDestination('ORF Wetter', $variableIdOrfText,    $categoryId_OW_WebFront,  19);


            }

        }

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Startpage Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */
        $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
        $config = $configWFront["Administrator"];   
        $wfcHandling->read_WebfrontConfig($config["ConfigId"]);         // register Webfront Confígurator ID          
        echo "Webfront Administrator aufbauen in ".$config["Path"]." :\n";
        echo "\n";		

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "\nWebportal Administrator: Startpage Kategorie installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."/".IPS_GetName(IPS_GetParent($categoryId_AdminWebFront))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($categoryId_AdminWebFront)))."\n";
		$WFC10_OW_TabPaneParent="TPStartpage";
        //$WFC10_OW_TabPaneItem="StartpageTPA";$WFC10_OW_TabItem="";  $WFC10_OW_TabPaneOrder=10; 
        //$WFC10_OW_TabPaneName="StartPage";
        //$WFC10_OW_TabPaneName="";                       // Platz sparen in der ersten Leiste des alten Webfronts
        /* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */

		echo "    Startpage Kategorie installieren als: ".$config["Path"]." und Inhalt löschen und dann verstecken.\n";
		$categoryId_WebFront         = CreateCategoryPath($config["Path"]);
		EmptyCategory($categoryId_WebFront);
		IPS_SetHidden($categoryId_WebFront, true); 		/* in der normalen Viz Darstellung verstecken */			

		echo "\nWebportal Administrator:  in Webfront Konfigurator ID ".$config["ConfigId"]." die ID Admin für die gesamte Kategorie Visualization installieren.\n";
		$wfcHandling->CreateWFCItemCategory  ('Admin',   "roottp",   800, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		echo "       Delete/hide IDs root und dwd.\n";
		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($config["ConfigId"],"root",false	);				
		@WFC_UpdateVisibility ($config["ConfigId"],"dwd",false	);		

		$tabItem = $config["TabPaneItem"].$config["TabItem"];	
		echo "       Create ID ".$tabItem." in ".$config["TabPaneParent"].".\n";
        //$nameIds=$wfcHandling->GetItemsByName("roott"); echo "Look for ItemID of root : ".json_encode($nameIds)." \n";
        //$wfcHandling->UpdateParentID("roottp", "");        // Hotfix da rootp überschrieben wurde
        $wfcHandling->CreateWFCItemTabPane   ($WFC10_OW_TabPaneParent, "roottp" , $config["TabPaneOrder"], "", $config["TabPaneIcon"]);
        $wfcHandling->CreateWFCItemCategory  ($tabItem,   $WFC10_OW_TabPaneParent,   10, '', "" , $categoryId_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
		//$wfcHandling->CreateWFCItemCategory  ($tabItem,   $config["TabPaneParent"],   10, '', $config["TabPaneIcon"] , $categoryId_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
	
		CreateLinkByDestination('Uebersicht', $variableIdStartpageHTML,    $categoryId_WebFront,  100);
		CreateLinkByDestination('Ansicht', $switchScreenID,    $categoryId_WebFront,  20);

        $wfcHandling->write_WebfrontConfig($config["ConfigId"]);
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Startpage Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
		
	if ($WFC10User_Enabled)
		{		
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */
        $wfcHandling =  new WfcHandling();                              // ohne Parameter wird die Konfiguration der Webfronts editiert, sonst werden die Standard Befehle der IPS Library verwendet
        $config = $configWFront["User"];   
        $wfcHandling->read_WebfrontConfig($config["ConfigId"]);         // register Webfront Confígurator ID          
        echo "Webfront User aufbauen in ".$config["Path"]." :\n";
        echo "\n";		

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$config["ConfigId"]." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		$wfcHandling->CreateWFCItemCategory  ('User',   "roottp",   800, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($config["ConfigId"],"root",false	);				
		@WFC_UpdateVisibility ($config["ConfigId"],"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$config["Path"]." \n";
		$categoryId_WebFrontUser         = CreateCategoryPath($config["Path"]);
		EmptyCategory($categoryId_WebFrontUser);
		IPS_SetHidden($categoryId_WebFrontUser, true); /* in der normalen Viz Darstellung verstecken */		

		/*************************************/
		
		$tabItem = $config["TabPaneItem"].$config["TabItem"];	
		echo "       Create ID ".$tabItem." in ".$config["TabPaneParent"].".\n";
		$wfcHandling->CreateWFCItemCategory  ($tabItem,   $config["TabPaneParent"],   $config["TabPaneOrder"], '', $config["TabPaneIcon"], $categoryId_WebFront   /*BaseId*/, 'false' /*BarBottomVisible*/);
	
		CreateLinkByDestination('Uebersicht', $variableIdStartpageHTML,    $categoryId_WebFrontUser,  10);
		CreateLinkByDestination('Ansicht', $switchScreenID,    $categoryId_WebFrontUser,  20);

        $wfcHandling->write_WebfrontConfig($config["ConfigId"]);
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($config["ConfigId"], "StartpageTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }

	if ($Mobile_Enabled)
		{
        $config = $configWFront["Mobile"];  
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($config["Path"]);

		}

	if ($Retro_Enabled)
		{
        $config = $configWFront["Retro"];  
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($config["Path"]);

		}


?>