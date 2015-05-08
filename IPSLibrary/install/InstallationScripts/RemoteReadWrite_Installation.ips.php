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

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

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

	$gartensteuerung=false;
 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		if ($name=="Gartensteuerung") { $gartensteuerung=true; }
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

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
		
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	//print_r($Homematic);
	
	/* check if Gartensteuerung ueberhaupt installiert */
	if ($gartensteuerung==true)
	   {
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
		} /* Ende gartensteuerung */

	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		if ($gartensteuerung==true)
		   {
	      foreach ($ReadWriteList as $Key)
   	      {
      	   print_r($Key);
				CreateLinkByDestination($Key["Name"], $Key["LOID"],    $categoryId_WebFront,  10);
				}
			}
		createPortal($WFC10_Path);
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


function createPortal($Path)
{
		$categoryId_WebFront         = CreateCategoryPath($Path);
		$categoryId_WebFrontTemp     = CreateCategoryPath($Path.".Temperatur");
		$categoryId_WebFrontHumi     = CreateCategoryPath($Path.".Feuchtigkeit");
		$categoryId_WebFrontSwitch   = CreateCategoryPath($Path.".Schalter");

		IPSUtils_Include ("RemoteReadWrite_Configuration.inc.php","IPSLibrary::config::modules::RemoteReadWrite");
		IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
		//IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$Homematic = HomematicList();
		$FHT = FHTList();
		$FS20= FS20List();
	
		foreach ($Homematic as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["TEMPERATURE"])==true)
	   		{
      		$oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontTemp,  10);
				//print_r($Key["COID"]["TEMPERATURE"]);
				//echo $Key["COID"]["TEMPERATURE"]["OID"]." ";
				//echo date("d.m h:i",IPS_GetVariable($oid)["VariableChanged"])." ";
				//echo $Key["Name"].".".$Key["COID"]["TEMPERATURE"]["Name"]." = ".GetValueFormatted($oid)."\n";
				}
			}

		foreach ($FHT as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["TemeratureVar"])==true)
		   	{
      		$oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontTemp,  10);
				}
			}

		foreach ($Homematic as $Key)
			{
			/* alle Feuchtigkeitswerte ausgeben */
			if (isset($Key["COID"]["HUMIDITY"])==true)
		   	{
	   	   $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontHumi,  10);
				//$alleHumidityWerte.=str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			}

   	$categoryId_WebFrontSwitchFS20   = CreateCategoryPath($Path.".Schalter.FS20");
		foreach ($FS20 as $Key)
			{
			/* alle Statuswerte ausgeben */
			if (isset($Key["COID"]["StatusVariable"])==true)
			   {
      		$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontSwitchFS20,  10);
				}
			}
   	$categoryId_WebFrontSwitchHM   = CreateCategoryPath($Path.".Schalter.Homematic");
		foreach ($Homematic as $Key)
			{
			/* alle Temperaturwerte ausgeben */
			if (isset($Key["COID"]["STATE"])==true)
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
      		CreateLinkByDestination($Key["Name"], $oid,    $categoryId_WebFrontSwitchHM,  10);
				}
			}
}



?>
