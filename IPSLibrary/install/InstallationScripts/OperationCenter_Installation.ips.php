<?

	/**@defgroup Sprachsteuerung
	 *
	 * Script um automatisch irgendetwas ein und auszuschalten
	 *
	 *
	 * @file          Sprachsteuerungung_Installation.ips.php
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
	
	$tim2ID = @IPS_GetEventIDByName("MoveCamFiles", $scriptIdOperationCenter);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdOperationCenter);
		IPS_SetName($tim2ID, "MoveCamFiles");
		IPS_SetEventCyclic($tim2ID,0,1,0,0,1,150);      /* alle 150 sec */
  		IPS_SetEventActive($tim2ID,true);
		IPS_SetEventCyclicTimeBounds($tim2ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	   echo "   Event neu angelegt. Timer 150 sec ist aktiviert.\n";
		//IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
		}
	else
	   {
	   echo "   Event bereits angelegt. Timer 150 sec ist aktiviert.\n";
  		IPS_SetEventActive($tim2ID,true);
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
		}
	IPS_SetEventActive($tim1ID,true);

	$tim3ID = @IPS_GetEventIDByName("RouterExectimer", $scriptIdOperationCenter);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdOperationCenter);
		IPS_SetName($tim3ID, "RouterExectimer");
		IPS_SetEventCyclic($tim3ID,0,1,0,0,1,150);      /* alle 150 sec */
		IPS_SetEventCyclicTimeBounds($tim3ID,time()+60,0);
		/* diesen Timer nicht aktivieren, er wird vom RouterAufrufTimer aktiviert und deaktiviert */
		}

	IPS_SetEventCyclicTimeBounds($tim2ID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
	IPS_SetEventCyclicTimeBounds($tim3ID,time()+60,0);

	/* PC Daten wie zB Trace regelmaessig auslesen */

	$tim4ID = @IPS_GetEventIDByName("DiagnoseAufruftimer", $scriptIdDiagnoseCenter);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdDiagnoseCenter);
		IPS_SetName($tim4ID, "DiagnoseAufruftimer");
		IPS_SetEventCyclic($tim4ID,0,0,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim4ID,1,40,0);  /* immer um 1:40 */
		}
	IPS_SetEventActive($tim4ID,true);

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


	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);

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
