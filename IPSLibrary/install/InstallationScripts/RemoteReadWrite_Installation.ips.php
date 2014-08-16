<?

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

	Include_once(IPS_GetKernelDir()."scripts\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteReadWrite',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('RemoteReadWrite');
	echo "\nRemoteReadWrite Version : ".$ergebnis;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	echo "\nWF10 ";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	echo "WF10User ";
	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	echo "Mobile ";
	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
	
	echo "Retro \n";
	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	IPSUtils_Include ("Gartensteuerung.inc.php","IPSLibrary::app::modules::Gartensteuerung");
	$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.RemoteReadWrite');
	echo "BaseID :".$baseId."\n";

	/* Typ 0 Boolean 1 Integer 2 Float 3 String */
	$StatusID = CreateVariableByName($baseId, "StatusReadWrite-BKS", 0);
	$letzterWertID = CreateVariableByName($baseId, "LetzterWert-BKS", 3);

	$ParamList = ParamList();

	$ReadWriteList=array();
	SetValueBoolean($StatusID,true);
	foreach ($ParamList as $Key)
		{
		$typ=(integer)$Key["Type"];
		$oid=(integer)$Key["OID"];
		if ($Key["Profile"]=="")
		   { /* keine Formattierung */
	   	$vid = CreateVariableByName($baseId, $Key["Name"], $typ);
		   }
		else
		   {
	   	$vid = CreateVariableByName($baseId, $Key["Name"], 3);
		   }
		$ReadWriteList[$Key["Name"]]=array("OID" => 0, "Name" => 0, "Profile" => 0, "Type" => 0, "LOID" => 0);
		$ReadWriteList[$Key["Name"]]["OID"]=$oid;
		$ReadWriteList[$Key["Name"]]["Name"]=$Key["Name"];
		$ReadWriteList[$Key["Name"]]["Profile"]=$Key["Profile"];
		$ReadWriteList[$Key["Name"]]["Type"]=$typ;
		$ReadWriteList[$Key["Name"]]["LOID"]=$vid;
	}
	//print_r($ReadWriteList);


	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
      foreach ($ReadWriteList as $Key)
         {
         print_r($Key);
			CreateLinkByDestination($Key["Name"], $Key["LOID"],    $categoryId_WebFront,  10);
			}
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


?>
