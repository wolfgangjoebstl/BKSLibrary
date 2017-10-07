<?

	/**@defgroup OperationCenter
	 *
	 * Script zur Unterstützung der Betriebsführung
	 *
	 *
	 * @file          OperationCenter_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
	IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('OperationCenter',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('OperationCenter');
	echo "\nOperationCenter Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
	$scriptIdDiagnoseCenter   = IPS_GetScriptIDByName('DiagnoseCenter', $CategoryIdApp);

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/******************************************************

				INIT, Timer

	*************************************************************/

	/* Timer so konfigurieren dass sie sich nicht in die Quere kommen */

	echo "Timer programmieren :\n";
	
	$timer = new TimerHandling();
	//print_r($timer->listScriptsUsed());
	
	$tim4ID = @IPS_GetEventIDByName("SysPingTimer", $scriptIdOperationCenter);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdOperationCenter);
		IPS_SetName($tim4ID, "SysPingTimer");
		IPS_SetEventCyclic($tim4ID,0,1,0,0,2,60);      /* alle 60 Minuten , Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
		IPS_SetEventActive($tim4ID,true);
	   echo "   Timer Event SysPingTimer neu angelegt. Timer 60 Minuten ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event SysPingTimer bereits angelegt. Timer 60 Minuten ist aktiviert.\n";
  		IPS_SetEventActive($tim4ID,true);
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
  		}
  		
	$tim5ID = @IPS_GetEventIDByName("CyclicUpdate", $scriptIdOperationCenter);
	if ($tim5ID==false)
		{
		$tim5ID = IPS_CreateEvent(1);
		IPS_SetParent($tim5ID, $scriptIdOperationCenter);
		IPS_SetName($tim5ID, "CyclicUpdate");
		IPS_SetEventCyclic($tim5ID,4,1,0,12,0,0);    /* jeden 12. des Monats , Monatliche Ausführung, alle 1 Monate, Datumstage, Datumstageintervall,  */
	   echo "   Timer Event CyclicUpdate neu angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event CyclicUpdate bereits angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
  		IPS_SetEventActive($tim5ID,true);
  		}

	
	$tim1ID=$timer->CreateTimerOC("RouterAufruftimer",00,20);				/* Eventuell Router regelmaessig auslesen */	
	$tim10ID=$timer->CreateTimerOC("Maintenance",01,20);						/* Starte Maintanenance Funktionen */	
	$tim11ID=$timer->CreateTimerSync("MoveLogFiles",150);						/* Maintanenance Funktion: Move Log Files */	
	$tim2ID=$timer->CreateTimerSync("MoveCamFiles",150);
	$tim3ID=$timer->CreateTimerSync("RouterExectimer",150);
		
   $tim6ID=$timer->CreateTimerOC("CopyScriptsTimer",02,20);	
   $tim7ID=$timer->CreateTimerOC("FileStatus",03,50);
   $tim8ID=$timer->CreateTimerOC("SystemInfo",02,30);
	
   $tim9ID=$timer->CreateTimerOC("Reserved",02,40);	
  		
	/******************************************************

				INIT, iMacro Router auslesen

	*************************************************************/

	$OperationCenterConfig = OperationCenter_Configuration();
	//print_r($OperationCenterConfig);
	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
		echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		//print_r($router);
		if ($router['TYP']=='MR3420')
			{
			echo "    iMacro Command-File für Router Typ MR3420 wird hergestellt.\n";
			$handle2=fopen($router["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
      		fwrite($handle2,'VERSION BUILD=8961227 RECORDER=FX'."\n");
	    	fwrite($handle2,'TAB T=1'."\n");
	      	fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
			fwrite($handle2,'SET !ENCRYPTION NO'."\n");
     		fwrite($handle2,'ONLOGIN USER=admin PASSWORD=cloudg06'."\n");
	      	fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
   	   		fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
      		fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
	      	fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
   	   		fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
      		fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
	      	fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
	      	fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
	      	fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
   	   		//fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
   	   		fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
   	   		fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
   	   		fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
   	   		fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
      		fwrite($handle2,'TAB CLOSE'."\n");
			fclose($handle2);

			//SetValue($ScriptCounterID,1);
			//IPS_SetEventActive($tim3ID,true);

			}
		}

	/******************************************************

				INIT, Nachrichtenspeicher

	*************************************************************/


	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

	if ($_IPS['SENDER']=="Execute")
		{
		echo 	$log_OperationCenter->PrintNachrichten();
		}


	/******************************************************

				INIT, TraceRouteSpeicher

	*************************************************************/

	$categoryId_Route    = CreateCategory('TraceRouteVerlauf',   $CategoryIdData, 20);
	for ($i=1; $i<=20;$i++)
	   {
		$input = CreateVariable("RoutePoint".$i,3,$categoryId_Route, $i*5, "",null,null,""  );  /* Name Type ParentID Position */
		}


	/******************************************************

				INIT, Webcams FTP Folder auslesen und auswerten

	*************************************************************/

	if (isset ($installedModules["IPSCam"]))
		{
		echo "\nWebcam anschauen und ftp Folder zusammenräumen.\n";

		IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
		IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");

		if (isset ($OperationCenterConfig['CAM']))
			{
			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
				$verzeichnis = $cam_config['FTPFOLDER'];
				$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
				if ($cam_categoryId==false)
				   {
					$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
					IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
					IPS_SetParent($cam_categoryId,$CategoryIdData);
					}
				$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3); /* 0 Boolean 1 Integer 2 Float 3 String */
				$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,1);      /* 0 normaler Wert 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);

				$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_MotionID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_MotionID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				// Test, ob ein Verzeichnis angegeben wurde
				if ( is_dir ( $verzeichnis ))
					{
	   		 	// öffnen des Verzeichnisses
   		 		if ( $handle = opendir($verzeichnis) )
		    			{
	   	 			$count=0; $list="";
		        		/* einlesen des Verzeichnisses        	*/
			        	while (($file = readdir($handle)) !== false)
	   		     		{
   	   		  		if (is_dir($verzeichnis.$file)==false)
	        				   {
		        				$count++;
	   	     				$list .= $file."\n";
			   	     		}
							}
						echo "   Im Cam FTP Verzeichnis ".$verzeichnis." gibt es ".$count." neue Dateien.\n";
						echo "   Letzter Eintrag von ".GetValue($WebCam_LetzteBewegungID)."\n";
						//echo $list."\n";
						}
					} /* ende ifisdir */
				}  /* ende foreach */
			}
		}

	/******************************************************

				INIT SysPing Variablen und auf Archivierung setzen

	*************************************************************/

	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	$OperationCenterConfig = $OperationCenter->oc_Configuration;

	$categoryId_SysPing    = CreateCategory('SysPing',   $CategoryIdData, 200);

	if (isset ($installedModules["IPSCam"]))
		{
		foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["LedAnsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
		$device_config=LedAnsteuerung_Config();
		foreach ($device_config as $name => $config)
		   {
			$StatusID = CreateVariableByName($categoryId_SysPing, "LED_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["DENONsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
		$device_config=Denon_Configuration();
		foreach ($device_config as $name => $config)
		   {
			$StatusID = CreateVariableByName($categoryId_SysPing, "Denon_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	foreach ($OperationCenterConfig['ROUTER'] as $cam_name => $cam_config)
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Router_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["IPSWeatherForcastAT"]))
	   {
		$StatusID = CreateVariableByName($categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["RemoteAccess"]))
		{
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetConfigurationNew();
		foreach ($remServer as $Name => $UrlAddress)
		   {
			$StatusID = CreateVariableByName($categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}
	IPS_ApplyChanges($archiveHandlerID);
		
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('OperationCenter', $CategoryIdData,    $categoryId_WebFront,  10);
		CreateLinkByDestination('Nachrichtenverlauf', $categoryId_Nachrichten,    $categoryId_WebFront,  20);
		CreateLinkByDestination('TraceRouteVerlauf', $categoryId_Route,    $categoryId_WebFront,  900);

		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

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

	/***********************************************************************
	 *
	 * fuer IPSCam einen Overview der ersten 4 Cameras machen 
	 *
	 *
	 ******************************************************************/


	if ( isset ($installedModules["IPSCam"] ) ) 
		{
		echo "\n"; 
		echo "Modul IPSCam installiert.\n"; 
		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
		$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);
		$ergebnisCam=$moduleManagerCam->VersionHandler()->GetVersion('IPSCam');
		echo "IPSCam Version : ".$ergebnisCam."\n";
		$WFC10Cam_Enabled        = $moduleManagerCam->GetConfigValueDef('Enabled', 'WFC10',false);
		$WFC10_ConfigId       = $moduleManagerCam->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
		echo "  Default WFC10_ConfigId fuer IPSCam, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";			

		if ($WFC10Cam_Enabled)
			{
			
			// ----------------------------------------------------------------------------------------------------------------------------
			// Program Installation
			// ----------------------------------------------------------------------------------------------------------------------------
			$CategoryIdCamData  		= $moduleManagerCam->GetModuleCategoryID('data');
			$CategoryIdCamApp   		= $moduleManagerCam->GetModuleCategoryID('app');
			$categoryIdCams     		= CreateCategory('Cams',    $CategoryIdCamData, 20);
			$scriptIdActionScript   = IPS_GetScriptIDByName('IPSCam_ActionScript', $CategoryIdCamApp);			
			
			// ===================================================================================================
			// Add Camera Devices
			// ===================================================================================================
			
			IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
			IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
			$camConfig = IPSCam_GetConfiguration();
			$result=array();
			foreach ($camConfig as $idx=>$data) 
				{
				print_r($data);
				$categoryIdCamX      = CreateCategory($idx, $categoryIdCams, $idx);
				$variableIdCamHtmlX  = IPS_GetObjectIDByIdent(IPSCAM_VAR_CAMHTML, $categoryIdCamX);
				echo "Kamera ".$idx." auf Kategorie : ".$categoryIdCamX." mit HTML Objekt auf : ".$variableIdCamHtmlX."\n";
				$result[$idx]["OID"]=$variableIdCamHtmlX;
				$result[$idx]["Name"]=$data["Name"];
				}
			
			$WFC10Cam_Path        	 = $moduleManagerCam->GetConfigValue('Path', 'WFC10');
			$WFC10Cam_TabPaneItem    = $moduleManagerCam->GetConfigValue('TabPaneItem', 'WFC10');
			$WFC10Cam_TabPaneParent  = $moduleManagerCam->GetConfigValue('TabPaneParent', 'WFC10');
			$WFC10Cam_TabPaneName    = $moduleManagerCam->GetConfigValue('TabPaneName', 'WFC10');
			$WFC10Cam_TabPaneIcon    = $moduleManagerCam->GetConfigValue('TabPaneIcon', 'WFC10');
			$WFC10Cam_TabPaneOrder   = $moduleManagerCam->GetConfigValueInt('TabPaneOrder', 'WFC10');
			$WFC10Cam_TabItem        = $moduleManagerCam->GetConfigValue('TabItem', 'WFC10');
			$WFC10Cam_TabName        = $moduleManagerCam->GetConfigValue('TabName', 'WFC10');
			$WFC10Cam_TabIcon        = $moduleManagerCam->GetConfigValue('TabIcon', 'WFC10');
			$WFC10Cam_TabOrder       = $moduleManagerCam->GetConfigValueInt('TabOrder', 'WFC10');
			echo "WF10 Administrator\n";
			echo "  Path          : ".$WFC10Cam_Path."\n";
			echo "  TabPaneItem   : ".$WFC10Cam_TabPaneItem."\n";
			echo "  TabPaneParent : ".$WFC10Cam_TabPaneParent."\n";
			echo "  TabPaneName   : ".$WFC10Cam_TabPaneName."\n";
			echo "  TabPaneIcon   : ".$WFC10Cam_TabPaneIcon."\n";
			echo "  TabPaneOrder  : ".$WFC10Cam_TabPaneOrder."\n";
			echo "  TabItem       : ".$WFC10Cam_TabItem."\n";
			echo "  TabName       : ".$WFC10Cam_TabName."\n";
			echo "  TabIcon       : ".$WFC10Cam_TabIcon."\n";
			echo "  TabOrder      : ".$WFC10Cam_TabOrder."\n";
			
			/* zuerst die Kategorien in Visualization aufbauen */
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: ".$WFC10Cam_Path." \n";
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path);
			EmptyCategory($categoryId_WebFrontAdministrator);
			$categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontAdministrator, 10);
			$categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontAdministrator, 20);						
			$categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontAdministrator, 30);
			$categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontAdministrator, 40);						
			
			/* dann die Webfronts initialisieren */
			
			//$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
			//                     WebfrontConfigurator, neuer Name, Ort-Parent
			//CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem,           $WFC10_TabPaneItem,    ($WFC10_TabOrder+100),     $WFC10_TabName,     $WFC10_TabIcon, 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');

			$tabItem = $WFC10Cam_TabPaneItem.'Ovw';																				
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+100), "Overview", $WFC10Cam_TabIcon, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Left", $tabItem, 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Right", $tabItem, 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Left', $tabItem."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Right', $tabItem."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Left', $tabItem."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Right', $tabItem."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);

			CreateLink($result[0]["Name"], $result[0]["OID"], $categoryIdLeftUp, 10);
			CreateLink($result[1]["Name"], $result[1]["OID"], $categoryIdRightUp, 10);
			CreateLink($result[2]["Name"], $result[2]["OID"], $categoryIdLeftDn, 10);
			CreateLink($result[3]["Name"], $result[3]["OID"], $categoryIdRightDn, 10);
				
			}
			
		}


?>