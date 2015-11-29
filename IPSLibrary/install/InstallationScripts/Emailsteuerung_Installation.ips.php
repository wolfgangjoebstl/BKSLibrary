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
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Emailsteuerung\Emailsteuerung_Configuration.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Emailsteuerung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Emailsteuerung');
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

	$scriptIdEmailsteuerung   = IPS_GetScriptIDByName('Emailsteuerung', $CategoryIdApp);

	echo "Alle SMTP Clients:\n";
	print_r(IPS_GetInstanceListByModuleID("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}"));
	//echo "Alle POP3 Server:\n";
	//print_r(IPS_GetInstanceListByModuleID("{69CA7DBF-5FCE-4FDF-9F36-C05E0136ECFD}"));
	echo "Alle IMAP Server:\n";
	print_r(IPS_GetInstanceListByModuleID("{CABFCCA1-FBFF-4AB7-B11B-9879E67E152F}"));

	$SendEmailID = @IPS_GetInstanceIDByName("SendEmail", $CategoryIdData);
	$SmtpConfig = Smtp_Configuration();

   if(!IPS_InstanceExists($SendEmailID))
      {
      $SendEmailID = IPS_CreateInstance("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}"); // SMTP anlegen
	   IPS_SetName($SendEmailID, "SendEmail");
		IPS_SetParent($SendEmailID,$CategoryIdData);
		foreach ($SmtpConfig as $key => $value)
		   {
		   echo "Property ".$key." ".$value."\n";
			IPS_SetProperty($SendEmailID,$key,$value);
			}
		IPS_ApplyChanges($SendEmailID);
		}

	$SmtpID=$SendEmailID;
	echo "Password :".IPS_GetProperty($SmtpID,"Password")."\n";
	echo "Recipient :".IPS_GetProperty($SmtpID,"Recipient")."\n";
	echo "SenderAddress :".IPS_GetProperty($SmtpID,"SenderAddress")."\n";
	echo "Username :".IPS_GetProperty($SmtpID,"Username")."\n";
	echo "SenderName :".IPS_GetProperty($SmtpID,"SenderName")."\n";
	echo "UseAuthentication :".IPS_GetProperty($SmtpID,"UseAuthentication")."\n";
	echo "Port :".IPS_GetProperty($SmtpID,"Port")."\n";
	echo "Host :".IPS_GetProperty($SmtpID,"Host")."\n";
	echo "UseSSL :".IPS_GetProperty($SmtpID,"UseSSL")."\n";

	$ReceiveEmailID = @IPS_GetInstanceIDByName("ReceiveEmail", $CategoryIdData);
	$ImapConfig = Imap_Configuration();
	
   if(!IPS_InstanceExists($ReceiveEmailID))
      {
      $ReceiveEmailID = IPS_CreateInstance("{CABFCCA1-FBFF-4AB7-B11B-9879E67E152F}"); // IMAP anlegen
	   IPS_SetName($ReceiveEmailID, "ReceiveEmail");
		IPS_SetParent($ReceiveEmailID,$CategoryIdData);
		foreach ($ImapConfig as $key => $value)
		   {
		   echo "Property ".$key." ".$value."\n";
			IPS_SetProperty($ReceiveEmailID,$key,$value);
			}
		IPS_ApplyChanges($ReceiveEmailID);
		}

	$SmtpID=$ReceiveEmailID;
	echo "CacheInterval :".IPS_GetProperty($SmtpID,"CacheInterval")."\n";
	echo "Password :".IPS_GetProperty($SmtpID,"Password")."\n";
	echo "CacheSize :".IPS_GetProperty($SmtpID,"CacheSize")."\n";
	echo "Username :".IPS_GetProperty($SmtpID,"Username")."\n";
	echo "UseAuthentication :".IPS_GetProperty($SmtpID,"UseAuthentication")."\n";
	echo "Port :".IPS_GetProperty($SmtpID,"Port")."\n";
	echo "Host :".IPS_GetProperty($SmtpID,"Host")."\n";
	echo "UseSSL :".IPS_GetProperty($SmtpID,"UseSSL")."\n";


$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdEmailsteuerung);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $scriptIdEmailsteuerung);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,4,10,0);  /* immer um 04:10 */
	}
IPS_SetEventActive($tim1ID,true);
	
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
      IPS_SetPosition($categoryId_WebFront,999);
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
