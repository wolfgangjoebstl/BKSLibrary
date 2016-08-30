<?

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Watchdog\Watchdog_Configuration.inc.php");

IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");
IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");

/***************************
 *
 *
 *************************************/

	echo "Selber Ausschalten und PC herunterfahren.\n";
	$ProgramId = IPS_GetObjectIDByName('Program', 0);
	$LibraryId = IPS_GetObjectIDByName('IPSLibrary', $ProgramId);
	$AppId = IPS_GetObjectIDByName('app', $LibraryId);
	$ModulesId = IPS_GetObjectIDByName('modules', $AppId);
	$WatchdogId = IPS_GetObjectIDByName('Watchdog', $ModulesId);
	$ShutdownId = IPS_GetScriptIDByName('Shutdown', $WatchdogId);
	echo "Lokale Shutdown ID : ".$ShutdownId."\n";

	$WDconfig=Watchdog_Configuration();
	//print_r($WDconfig);

	if (isset($WDconfig["RemoteShutDown"]))
	   {
	   $error=false;
	   echo "Entfernten PC herunterfahren.\n";
		tts_play(1,"Visualisierung herunterfahren",'',2);

		/* rpc Zugriff ausprogrammieren um feststellen zu können ob andere Stelle noch erreichbar */
		$method="IPS_GetName"; $params=array();
		$UrlAddress=$WDconfig["RemoteShutDown"];
		$rpc = new JSONRPC($UrlAddress);
		$data = @parse_url($UrlAddress);
		if(($data === false) || !isset($data['scheme']) || !isset($data['host'])) throw new Exception("Invalid URL");
		$url = $data['scheme']."://".$data['host'];
		if(isset($data['port'])) $url .= ":".$data['port'];
		if(isset($data['path'])) $url .= $data['path'];
		if(isset($data['user']))
			{
			$username = $data['user'];
			}
		else
			{
			$username = "";
			}
		if(isset($data['pass']))
		   {
			$password = $data['pass'];
			}
		else
			{
			$password = "";
			}
		if (!is_scalar($method)) {
			throw new Exception('Method name has no scalar value');
			}
		if (!is_array($params)) {
			throw new Exception('Params must be given as array');
			}
		$id = round(fmod(microtime(true)*1000, 10000));
		$params = array_values($params);
		$strencode = function(&$item, $key) {
		if ( is_string($item) )
			$item = utf8_encode($item);
		else if ( is_array($item) )
			array_walk_recursive($item, $strencode);
			};
		array_walk_recursive($params, $strencode);
		$request = Array(
				"jsonrpc" => "2.0",
				"method" => $method,
				"params" => $params,
				"id" => $id
				);
		$request = json_encode($request);
		$header = "Content-type: application/json"."\r\n";
		if(($username != "") || ($password != "")) {
			$header .= "Authorization: Basic ".base64_encode($username.":".$password)."\r\n";
			}
		$options = Array(
					"http" => array (
					"method"  => 'POST',
					"header"  => $header,
					"content" => $request
									)
				);
		$context  = stream_context_create($options);
		$response = @file_get_contents($url, false, $context);

		if ($response===true)
			{
			echo "Remote Server noch erreichbar.\n";
			$rProgramId = $rpc->IPS_GetObjectIDByName('Program', 0);
			$rLibraryId = $rpc->IPS_GetObjectIDByName('IPSLibrary', $rProgramId);
			$rAppId = $rpc->IPS_GetObjectIDByName('app', $rLibraryId);
			$rModulesId = $rpc->IPS_GetObjectIDByName('modules', $rAppId);
			$rWatchdogId = $rpc->IPS_GetObjectIDByName('Watchdog', $rModulesId);
			$rShutdownId = $rpc->IPS_GetScriptIDByName('Shutdown', $rWatchdogId);
			echo "Remote Shutdown ID : ".$rShutdownId."\n";
			$rpc->IPS_RunScript($rShutdownId);
			}
		else
		   {
			echo "Remote Server nicht mehr erreichbar.\n";
		   }
		} // endif Remote Server ueberhaupt definiert

	$handle2=fopen("c:/scripts/process_self_shutdown.bat","w");
	fwrite($handle2,'net stop IPSServer');
	fwrite($handle2,"\r\n");
	//fwrite($handle2,'shutdown /s');
	fwrite($handle2,'shutdown /r');     /* Restart */
	fwrite($handle2,"\r\n");
	fclose($handle2);
	IPS_ExecuteEx("c:/scripts/process_self_shutdown.bat","", true, false,1);

?>