<?
	/**
	 *
	 * Empfangs Script um Requests (JQuery) der HTML Seiten zu bearbeiten.
	 * wir sind Serverseitig, das bedeutet wir können nur IP Symcon Variablen verändern
	 * und eine response als json encoded zurückschicken
	 */

	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
	IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
	
	if (isset($_POST['id']))
		{
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
		$response["module"]="evaluatehardware";
		switch ($id) 
			{
			case "button-ev1":
				//$result=Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
				echo $id;
				break;
			case "button-ev2":
				//$result=Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
				//$result = Startpage_getData($action, "configuration");
				//$response[$id]=$result;
				//echo json_encode($response);			// format is as JSON
				break;
			default:
				//$result=Startpage_SetPage($action, $module, $id, "TopologyReceiver", $info);
				//IPSLogger_Inf(__file__, 'StartpageTopology_Receiver mit Id '.$id.' Cookie '.$action.' und Ergebnis '.$result);	
				//$result = Startpage_getData($action);
				//echo $id.":".$action.":".$module.":".$info;
				$result="";
				$response[$id]=$result;
				$response[$action]=$result;
				echo json_encode($response);
				break;
			}
		
		$config["text"]    = false;						// kein echo
		$config["insert"]["Header"]    = true;
		$config["insert"]["Index"]    = true;
		$config["html"]    = 'html';    
		$config["display"] = [
						"Index"                     => ["header"=>"OID","format"=>"OID"],
						"Name"                      => "ObjectName",
						"0"                         => "Modul",
						"1"                         => "Place",
						"2"                         => "Type",
						"3"                         => "Device",
						"4"                         => "newName",
					];
		$ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden				
		//echo $ipsTables->showTable(IPSDetectDeviceHandler_GetEventConfiguration(), false ,$config, false);

		$showEvaluateHardware = new showEvaluateHardware();
		//$showEvaluateHardware->showControlLine();
		}
	else echo "POST:".json_encode($_POST)." GET:".json_encode($_GET);
	/** @}*/
?>