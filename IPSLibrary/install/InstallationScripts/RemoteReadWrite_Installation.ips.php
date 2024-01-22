<?php

	/**@defgroup RemoteReadWrite_Installation
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script zum zyklischen externem Schreiben und Lesen von Variablen.
	 * verwendet nun EvaluateHardware als zentrale Erfassung des eingebauten Hardwarestandes
	 * 
	 * Im Vergleich dazu pusht RemoteAccess Variablenänderungen als rpc calls zu entsprechenden Logging servern
	 *
	 **/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
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

	/* feststellen ob Gartensteuerung instaliert ist */
	
	$gartensteuerung=false;
 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		if ($name=="Gartensteuerung") { $gartensteuerung=true; }
		}
	echo $inst_modules."\n";

	/******************************************************
	 *
	 *				INIT
	 *
	 *************************************************************/

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdApp);	
	echo "EvaluateHardware Script ID: ".$scriptIdEvaluateHardware."\n\n";
	if ($installedModules["EvaluateHardware"]) 
		{
		echo "Modul EvaluateHardware installiert, keine eigenen Aktivitäten zur Erfassung des Hardwarestandes notwendig.\n";
		$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdEvaluateHardware);
		if ($tim1ID==false)
			{
			IPS_SetEventActive($tim1ID,false);			
			}
		IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
		}
	else	
		{
		$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdEvaluateHardware);
		if ($tim1ID==false)
			{
			$tim1ID = IPS_CreateEvent(1);
			IPS_SetParent($tim1ID, $_IPS['SELF']);
			IPS_SetName($tim1ID, "Aufruftimer");
			IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
			IPS_SetEventCyclicTimeFrom($tim1ID,1,40,0);  /* immer um 02:20 */
			}
		IPS_SetEventActive($tim1ID,true);

		// ----------------------------------------------------------------------------------------------------------------------------
		// Hardware zum ersten Mal analysieren
		// ----------------------------------------------------------------------------------------------------------------------------

		IPS_RunScriptWait($scriptIdEvaluateHardware);		
		IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");			

		}
	
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



?>