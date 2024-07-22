<?
	/**
	 *
	 * Empfangs Script um Requests (JQuery) der HTML Seiten zu bearbeiten.
	 *
	 */

	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ("Startpage_Include.inc.php", "IPSLibrary::app::modules::Startpage");
	IPSUtils_Include ("Startpage.class.php", "IPSLibrary::app::modules::Startpage");
	
	IPSUtils_Include ("IPSModuleManager.class.php", "IPSLibrary::install::IPSModuleManager");

	include_once "Startpage_Configuration.inc.php";

	//IPSLogger_Inf(__file__, "Post Parameters: ");
	//foreach ($_POST as $key=>$value) {
	//	IPSLogger_Inf(__file__, "Post $key = $value");
	//}
	
	$id       = $_POST['id'];
	$action   = $_POST['action'];			// Cookie
	$module   = $_POST['module'];
	$info     = $_POST['info'];				// Config

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$repository = '';
	if ($module<>'') {
		$moduleInfos = $moduleManager->GetModuleInfos($module);
		$repository  = $moduleInfos['Repository'];
	}
	$response=array();
	$response["module"]="startpage";
	
	switch ($id) 		// return always as json
		{
		case "button-eins":
			$response[$id]             = Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
			$response["startofscript"] = Startpage_getData($action, "configuration");
			echo json_encode($response);			// format is as JSON
			break;
		case "startofscript":
			$result=Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
			$response[$id] = Startpage_getData($action, "configuration");
			echo json_encode($response);			// format is as JSON
			break;
		default:
			$result=Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
			IPSLogger_Inf(__file__, 'StartpageTopology_Receiver mit Id '.$id.' Cookie '.$action.' und Ergebnis '.$result);	
			$result = Startpage_getData($action);
			$response[$id]=$result;
			echo json_encode($response);			// format is as JSON
			break;
		}
	//echo "was here";
	StartpageOverview_Refresh();


	/** @}*/
?>