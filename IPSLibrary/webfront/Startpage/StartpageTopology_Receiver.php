<?
	/**
	 *
	 * Empfangs Script um Requests (JQuery) der HTML Seiten zu bearbeiten.
	 *
	 */

	IPSUtils_Include ("Startpage_Include.inc.php", "IPSLibrary::app::modules::Startpage");
	IPSUtils_Include ("Startpage.class.php", "IPSLibrary::app::modules::Startpage");
	
	IPSUtils_Include ("IPSModuleManager.class.php", "IPSLibrary::install::IPSModuleManager");

	include_once "Startpage_Configuration.inc.php";

	//IPSLogger_Inf(__file__, "Post Parameters: ");
	//foreach ($_POST as $key=>$value) {
	//	IPSLogger_Inf(__file__, "Post $key = $value");
	//}
	
	$id       = $_POST['id'];
	$action   = $_POST['action'];
	$module   = $_POST['module'];
	$info     = $_POST['info'];

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$repository = '';
	if ($module<>'') {
		$moduleInfos = $moduleManager->GetModuleInfos($module);
		$repository  = $moduleInfos['Repository'];
	}
	switch ($action) 
		{

		default:
			$result=Startpage_SetPage($action, $module, $info);
			IPSLogger_Inf(__file__, 'StartpageTopology_Receiver mit Wert '.$action.' und Ergebnis '.$result);			
		}
	echo "was here";
	StartpageOverview_Refresh();


	/** @}*/
?>