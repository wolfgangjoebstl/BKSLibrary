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
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Sprachsteuerung\Sprachsteuerung_Configuration.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Sprachsteuerung');
	echo "\nSprachsteuerung Version : ".$ergebnis;

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

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-Sprachsteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

	$scriptIdSprachsteuerung   = IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);

	//$listinstalledmodules=IPS_GetModuleList();
	//print_r($listinstalledmodules);
	//$moduleProp=IPS_GetModule("{2999EBBB-5D36-407E-A52B-E9142A45F19C}");
	//print_r($moduleProp);
	echo "Alle Mediaplayermodule:\n";
	print_r(IPS_GetInstanceListByModuleID("{2999EBBB-5D36-407E-A52B-E9142A45F19C}"));
	echo "Alle Text-to-Speech Module:\n";
	print_r(IPS_GetInstanceListByModuleID("{684CC410-6777-46DD-A33F-C18AC615BB94}"));
	
	$MediaPlayerMusikID = @IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);

   if(!IPS_InstanceExists($MediaPlayerMusikID))
      {
      $MediaPlayerMusikID = IPS_CreateInstance("{2999EBBB-5D36-407E-A52B-E9142A45F19C}"); // Mediaplayer anlegen
	   IPS_SetName($MediaPlayerMusikID, "MP Musik");
		IPS_SetParent($MediaPlayerMusikID,$scriptIdSprachsteuerung);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceNum",1);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceName","Lautsprecher (Realtek High Definition Audio)");
		IPS_SetProperty($MediaPlayerMusikID,"UpdateInterval",0);
		IPS_SetProperty($MediaPlayerMusikID,"DeviceDriver","{0.0.0.00000000}.{eb1c82a1-4bdf-4072-b886-7e0ca86e26e3}");
		IPS_ApplyChanges($MediaPlayerMusikID);
		/*
		DeviceNum integer 0
		DeviceName string
		UpdateInterval integer 0
		DeviceDriver string
		*/
		}
	$MediaPlayerTonID = @IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);

   if(!IPS_InstanceExists($MediaPlayerTonID))
      {
      $MediaPlayerTonID = IPS_CreateInstance("{2999EBBB-5D36-407E-A52B-E9142A45F19C}"); // Mediaplayer anlegen
	   IPS_SetName($MediaPlayerTonID, "MP Ton");
		IPS_SetParent($MediaPlayerTonID,$scriptIdSprachsteuerung);
		IPS_SetProperty($MediaPlayerTonID,"DeviceNum",1);
		IPS_SetProperty($MediaPlayerTonID,"DeviceName","Lautsprecher (Realtek High Definition Audio)");
		IPS_SetProperty($MediaPlayerTonID,"UpdateInterval",0);
		IPS_SetProperty($MediaPlayerTonID,"DeviceDriver","{0.0.0.00000000}.{eb1c82a1-4bdf-4072-b886-7e0ca86e26e3}");
		IPS_ApplyChanges($MediaPlayerTonID);
		/*
		DeviceNum integer 0
		DeviceName string
		UpdateInterval integer 0
		DeviceDriver string
		*/
		}
	$TextToSpeachID = @IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);

   if(!IPS_InstanceExists($TextToSpeachID))
      {
      $TextToSpeachID = IPS_CreateInstance("{684CC410-6777-46DD-A33F-C18AC615BB94}"); // Mediaplayer anlegen
	   IPS_SetName($TextToSpeachID, "Text to Speach");
		IPS_SetParent($TextToSpeachID,$scriptIdSprachsteuerung);
		IPS_SetProperty($TextToSpeachID,"TTSAudioOutput","Lautsprecher (Realtek High Definition Audio)");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","Microsoft Hedda Desktop - German");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","Microsoft Anna - English (United States)");
		//IPS_SetProperty($TextToSpeachID,"TTSEngine","ScanSoft Steffi_Dri40_16kHz");
		$SprachConfig=Sprachsteuerung_Configuration();
		IPS_SetProperty($TextToSpeachID,"TTSEngine",$SprachConfig["Engine".$SprachConfig["Language"]]);
		IPS_ApplyChanges($TextToSpeachID);
		/*
		TTSAudioOutput string
		TTSEngine string
		*/
		}
   $SprachCounterID = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */

	//print_r(IPS_GetStatusVariableIdents($MediaPlayerID));

	echo "TTSAudioOutput :".IPS_GetProperty($TextToSpeachID,"TTSAudioOutput")."\n";
	echo "TTSEngine :".IPS_GetProperty($TextToSpeachID,"TTSEngine")."\n";
	echo "DeviceName :".IPS_GetProperty($MediaPlayerTonID,"DeviceName")."\n";
	echo "DeviceNum :".IPS_GetProperty($MediaPlayerTonID,"DeviceNum")."\n";
	echo "UpdateInterval :".IPS_GetProperty($MediaPlayerTonID,"UpdateInterval")."\n";
	echo "DeviceDriver :".IPS_GetProperty($MediaPlayerTonID,"DeviceDriver")."\n";
	echo "DeviceName :".IPS_GetProperty($MediaPlayerMusikID,"DeviceName")."\n";
	echo "DeviceNum :".IPS_GetProperty($MediaPlayerMusikID,"DeviceNum")."\n";
	echo "UpdateInterval :".IPS_GetProperty($MediaPlayerMusikID,"UpdateInterval")."\n";
	echo "DeviceDriver :".IPS_GetProperty($MediaPlayerMusikID,"DeviceDriver")."\n";
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