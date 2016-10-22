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


	/******************************************************

				INIT, Timer

	*************************************************************/

	/* Timer so konfigurieren dass sie sich nicht in die Quere kommen */

	echo "Timer programmieren :\n";
	
	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptIdOperationCenter);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdOperationCenter);
		IPS_SetName($tim2ID, "MoveCamFiles");
		IPS_SetEventCyclic($tim2ID,0,1,0,0,1,150);      /* alle 150 sec */
  		//IPS_SetEventActive($tim2ID,true);
		IPS_SetEventCyclicTimeFrom($tim2ID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Timer Event MoveCamFiles neu angelegt. Timer 150 sec ist noch nicht aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event MoveCamFiles bereits angelegt. Timer 150 sec ist noch nicht aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim2ID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
  		//IPS_SetEventActive($tim2ID,true);
  		}

	$tim3ID = @IPS_GetEventIDByName("RouterExectimer", $scriptIdOperationCenter);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdOperationCenter);
		IPS_SetName($tim3ID, "RouterExectimer");
		IPS_SetEventCyclic($tim3ID,0,1,0,0,1,150);      /* alle 150 sec */
		IPS_SetEventCyclicTimeFrom($tim3ID,0,3,0);
		/* diesen Timer nicht aktivieren, er wird vom RouterAufrufTimer aktiviert und deaktiviert */
	   echo "   Timer Event RouterExectimer neu angelegt. Timer 150 sec ist nicht aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event RouterExectimer bereits angelegt. Timer 150 sec ist nicht aktiviert.\n";
		IPS_SetEventCyclicTimeFrom($tim3ID,0,3,0);
  		}



	/* Eventuell Router regelmaessig auslesen */

	$tim1ID = @IPS_GetEventIDByName("RouterAufruftimer", $scriptIdOperationCenter);
	if ($tim1ID==false)
		{
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $scriptIdOperationCenter);
		IPS_SetName($tim1ID, "RouterAufruftimer");
		IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim1ID,0,20,0);  /* immer um 0:20 */
  		IPS_SetEventActive($tim1ID,true);
	   echo "   Timer Event RouterAufruftimer neu angelegt. Timer um 0:20 ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event RouterAufruftimer bereits angelegt. Timer um 0:20 ist aktiviert.\n";
  		IPS_SetEventActive($tim1ID,true);
  		}

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

	$tim6ID = @IPS_GetEventIDByName("CopyScriptsTimer", $scriptIdOperationCenter);
	if ($tim6ID==false)
		{
		$tim6ID = IPS_CreateEvent(1);
		IPS_SetParent($tim6ID, $scriptIdOperationCenter);
		IPS_SetName($tim6ID, "CopyScriptsTimer");
		IPS_SetEventCyclic($tim6ID,0,0,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim6ID,2,20,0);  /* immer um 2:20 */
  		IPS_SetEventActive($tim6ID,true);
	   echo "   Timer Event CopyScriptsTimer neu angelegt. Timer um 2:20 ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event CopyScriptsTimer bereits angelegt. Timer um 2:20 ist aktiviert.\n";
  		IPS_SetEventActive($tim6ID,true);
  		}

	$tim7ID = @IPS_GetEventIDByName("FileStatus", $scriptIdOperationCenter);
	if ($tim7ID==false)
		{
		$tim7ID = IPS_CreateEvent(1);
		IPS_SetParent($tim7ID, $scriptIdOperationCenter);
		IPS_SetName($tim7ID, "FileStatus");
		IPS_SetEventCyclic($tim7ID,0,0,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim7ID,3,50,0);  /* immer um 3:50 */
  		IPS_SetEventActive($tim7ID,true);
	   echo "   Timer Event FileStatus neu angelegt. Timer um 3:50 ist aktiviert.\n";
		}
	else
	   {
	   echo "   Timer Event FileStatus bereits angelegt. Timer um 3:50 ist aktiviert.\n";
  		IPS_SetEventActive($tim7ID,true);
  		}

  		
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
   	   fwrite($handle2,'SAVEAS TYPE=CPL FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
   	   fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
   	   fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
   	   fwrite($handle2,'SAVEAS TYPE=CPL FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
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
  				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
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

/***************************************************************************************/


?>
