<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	if (isset ($installedModules["Sprachsteuerung"]))
	   {
		IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");
		IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");
	   }
	else
	   {
	   function tts_play() {};
	   }
	   
 	// Parent-ID der Kategorie ermitteln
	$parentID = IPS_GetObject($IPS_SELF);
	$parentID = $parentID['ParentID'];

	// ID der Skripte ermitteln
	$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

 	IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'stop'));

	tts_play(1,"Gute Nacht",'',2);
	
?>