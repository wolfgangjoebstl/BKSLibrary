<?
	/**
	 *
	 * Empfangs Script um Requests (JQuery) der HTML Seiten zu bearbeiten.
	 *
	 */

	IPSUtils_Include ("Guthabensteuerung_Include.inc.php", "IPSLibrary::app::modules::Guthabensteuerung");
		
	$id       = $_POST['id'];
	$action   = $_POST['action'];
	$module   = $_POST['module'];
	$info     = $_POST['info'];

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$repository = '';
	if ($module<>'') {
		$moduleInfos = $moduleManager->GetModuleInfos($module);			// Allgemeine Implementierung wenn Kombi Modul/Repository nicht bekannt
		$repository  = $moduleInfos['Repository'];
	 	$installedModules = $moduleManager->GetInstalledModules();
		if (isset($installedModules["Amis"])) {
			$moduleManagerAmis = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
			$CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
			$categoryId_SmartMeter        = IPS_GetObjectIdByName('SmartMeter', $CategoryIdDataAmis);			
        }
	}
	
	$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung.Webfront');	
	$infoID  = IPS_GetObjectIDByIdent(GUTHABEN_VAR_INFO,   $baseId);	
	$actionID = IPS_GetObjectIDByIdent(GUTHABEN_VAR_ACTION, $baseId);
	$htmlID = IPS_GetObjectIDByIdent(GUTHABEN_VAR_HTML, $categoryId_SmartMeter);
	
	$amisSM = new AmisSmartMeter();
	$header = $amisSM->writeSmartMeterCsvInfoToHtml("header");
	if (in_array($action,$header)) { $info=$action; $action="header";  }
	$files  =  $amisSM->writeSmartMeterCsvInfoToHtml("files");
	if (in_array($action,$files)) { $info=$action; $action="files";  }

	
	switch ($action) 
		{

		default:
			//$result=Startpage_SetPage($action, $module, $info);
			//IPSLogger_Inf(__file__, 'GuthabensteuerungReceiver mit Wert '.$action.' und Ergebnis '.$result);			
		}
	echo "was here";
	SetValue($infoID,$info);
	SetValue($actionID,$action);				// genau dei Action, da Abfrage im anderen file
	SetValue($htmlID,GetValue($htmlID));		// Aufruf Guthabensteuerung.php zum Update der Webpage
	//StartpageOverview_Refresh();
	//IPS_Runscript(

	/** @}*/
?>