<?

	/**@defgroup Guthabensteuerung
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script um herauszufinden ob die Guthaben der Simkarten schon abgelaufen sind
	 *
	 *
	 * @file          Guthabensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Guthabensteuerung');
	echo "\nRemoteAccess Version : ".$ergebnis;

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

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	/* Create Variables */

	$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);
	$GuthabensteuerungID=IPS_GetScriptIDByName('Guthabensteuerung',$CategoryIdApp);

	IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

	$GuthabenConfig = get_GuthabenConfiguration();
	$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();
	//print_r($GuthabenConfig);

	$pname="Euro";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Euro');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="MByte";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'',' MByte');
	   print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }
	   
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	
	$phoneID=array();
	$i=0;
	foreach ($GuthabenConfig as $TelNummer)
		{
		$handle2=fopen($GuthabenAllgConfig["MacroDirectory"]."dreiat_".$TelNummer["NUMMER"].".iim","w");
      fwrite($handle2,'VERSION BUILD=8300326 RECORDER=FX'."\n");
      fwrite($handle2,'TAB T=1'."\n");
      fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
      fwrite($handle2,'SET !EXTRACT NULL'."\n");
      fwrite($handle2,'SET !VAR0 '.$TelNummer["NUMMER"]."\n");
      fwrite($handle2,'ADD !EXTRACT {{!VAR0}}'."\n");
      fwrite($handle2,'URL GOTO=https://www.drei.at/portal/de/privat/index.html'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:nav_user'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:loginForm ATTR=ID:userName CONTENT={{!VAR0}}'."\n");
      fwrite($handle2,'SET !ENCRYPTION NO'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=NAME:loginForm ATTR=ID:password CONTENT='.$TelNummer["PASSWORD"]."\n");
      fwrite($handle2,'TAG POS=1 TYPE=BUTTON ATTR=TXT:Einloggen'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=DIV ATTR=ID:account-balance EXTRACT=TXT'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:Link_B2C_CoCo'."\n");
      fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
      fwrite($handle2,'\'Ausloggen'."\n");
      fwrite($handle2,'FRAME NAME="topbar"'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:nav_user'."\n");
      fwrite($handle2,'TAB CLOSE'."\n");
		fclose($handle2);

		$phone1ID = CreateVariableByName($CategoryIdData, "Phone_".$TelNummer["NUMMER"], 3);
		$phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Summary", 3);
		$phoneID[$i++]=$phone_Summ_ID;
    	$phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
     	$phone_Status_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Status", 3);
     	$phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Date", 3);
     	$phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_unchangedDate", 3);
     	$phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Bonus", 3);

   	$phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
		IPS_SetVariableCustomProfile($phone_Volume_ID,'MByte');
		AC_SetLoggingStatus($archiveHandlerID,$phone_Volume_ID,true);
		AC_SetAggregationType($archiveHandlerID,$phone_Volume_ID,0);
		IPS_ApplyChanges($archiveHandlerID);

   	$phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
		IPS_SetVariableCustomProfile($phone_VolumeCumm_ID,'MByte');
		AC_SetLoggingStatus($archiveHandlerID,$phone_VolumeCumm_ID,true);
		AC_SetAggregationType($archiveHandlerID,$phone_VolumeCumm_ID,0);
		IPS_ApplyChanges($archiveHandlerID);

		$phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Cost", 2);
		IPS_SetVariableCustomProfile($phone_nCost_ID,'Euro');
	  	IPS_SetPosition($phone_nCost_ID, 130);
		AC_SetLoggingStatus($archiveHandlerID,$phone_nCost_ID,true);
		AC_SetAggregationType($archiveHandlerID,$phone_nCost_ID,0);
		IPS_ApplyChanges($archiveHandlerID);

     	$phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Load", 2);
		IPS_SetVariableCustomProfile($phone_nLoad_ID,'Euro');
	  	IPS_SetPosition($phone_nLoad_ID, 140);
		AC_SetLoggingStatus($archiveHandlerID,$phone_nLoad_ID,true);
		AC_SetAggregationType($archiveHandlerID,$phone_nLoad_ID,0);
		IPS_ApplyChanges($archiveHandlerID);
      }

  	$phone_CL_Change_ID = CreateVariableByName($CategoryIdData, "Phone_CL_Change", 2);
	IPS_SetVariableCustomProfile($phone_CL_Change_ID,'Euro');
	
	$phone_Cost_ID = CreateVariableByName($CategoryIdData, "Phone_Cost", 2);
	IPS_SetVariableCustomProfile($phone_Cost_ID,'Euro');
	AC_SetLoggingStatus($archiveHandlerID,$phone_Cost_ID,true);
	AC_SetAggregationType($archiveHandlerID,$phone_Cost_ID,0);
	IPS_ApplyChanges($archiveHandlerID);
	
	$phone_Load_ID = CreateVariableByName($CategoryIdData, "Phone_Load", 2);
	IPS_SetVariableCustomProfile($phone_Load_ID,'Euro');
	AC_SetLoggingStatus($archiveHandlerID,$phone_Load_ID,true);
	AC_SetAggregationType($archiveHandlerID,$phone_Load_ID,0);
	IPS_ApplyChanges($archiveHandlerID);

	/* initialize timer */

	echo "Guthabensteuerung ScriptID:".$GuthabensteuerungID."\n";

	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $GuthabensteuerungID);
	if ($tim1ID==false)
		{
		echo "Timer erstellen.\n";
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $GuthabensteuerungID);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim1ID,2,rand(1,59),0);  /* immer um 02:xx , nicht selnbe Zeit damit keien zugriffsverletzungen auf der Drei Homepage entstehen */
		}
	IPS_SetEventActive($tim1ID,true);



	/* Create Web Pages */

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled==true)
	   {
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
		echo "\nWF10 ";
		}

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled==true)
	   {
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "WF10User ";
		}
		
	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
	   {
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
	   {
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}

	//echo "Test";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');


	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		$phone_summary_ID = CreateVariableByName($categoryId_WebFront, "Summary", 3);
		foreach ($phoneID as $phone)
		   {
		   CreateLinkByDestination(IPS_GetName($phone), $phone,    $phone_summary_ID,  10);
		   }
		CreateLinkByDestination(IPS_GetName($phone_Cost_ID), $phone_Cost_ID,    $categoryId_WebFront,  20);
		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren auf ".$WFC10User_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

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