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

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');


	/********************************************************************
	 *
	 * Init
	 *
	 **********************************************************************/

	$WDconfig=Watchdog_Configuration();
	//print_r($config);

	$tim5ID = @IPS_GetEventIDByName("ShutdownWD", $ShutdownId);
	echo "Timer ID : ".$tim5ID." Script ID : ".$ShutdownId."\n";

	$ScriptCounterID=CreateVariableByName($CategoryIdData,"ShutdownScriptCounter",1);

	$ShutRestart=true;  	/* true bedeutet restart */
	$debug=true;         /* für debug Zwecke die eigene Maschine nicht neu starten */

	/********************************************************************
	 *
	 * feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden
	 *
	 **********************************************************************/

	echo "\n";
	$processStart=array("IPSWatchDog.exe" => "On","vmplayer.exe" => "On", "iTunes.exe" => "On");
	$processStart=checkProcess($processStart);
	echo "Die folgenden Programme muessen gestoppt (wenn Off) werden:\n";
	print_r($processStart);

	if ($_IPS['SENDER']=="RunScript")
		{
		echo "Von der Console aus gestartet, Shutdown Prozess beginnen.\n";
		tts_play(1,"IP Symcon Visualisierung herunterfahren",'',2);
		IPS_SetEventActive($tim5ID,true);
   	SetValue($ScriptCounterID,1);
		if (isset($state) == true )
			{
			IPSLogger_Dbg(__file__, "Shutdown: Script aufgerufen mit Befehl : ".$state." *****************  ");
			if ($state == "Shutdown")
				{
				$ShutRestart=false;
				IPSLogger_Dbg(__file__, "Shutdown: es erfolgt ein Shutdown  ");
				}
			}
		}

	if ($_IPS['SENDER']=="Execute")
		{
		echo "Von der Console aus gestartet, Shutdown Prozess beginnen.\n";
		tts_play(1,"IP Symcon Visualisierung herunterfahren",'',2);
		IPS_SetEventActive($tim5ID,true);
   	SetValue($ScriptCounterID,1);
	   IPSLogger_Dbg(__file__, "Shutdown: aus dem Execute des Scripts initiert ***********************************************");
		}

	if ($_IPS['SENDER']=="TimerEvent")
		{
		switch ($_IPS['EVENT'])
		   {
	   	case $tim5ID:
				IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." Shutdown durchführen. ScriptcountID:".GetValue($ScriptCounterID));

				/******************************************************************************************
				 *
				 *
				 *********************************************************************************************/

				$counter=GetValue($ScriptCounterID);
				switch ($counter)
				   {
					case 3:
			   	   SetValue($ScriptCounterID,0);
			      	IPS_SetEventActive($tim5ID,false);
			      	break;
				   case 2:
						if ( $processStart["vmplayer.exe"]=="On" )
						   {
							IPSLogger_Dbg(__file__, "Shutdown: PC wird heruntergefahren.");
							$handle2=fopen("c:/scripts/process_self_shutdown.bat","w");
							fwrite($handle2,'net stop IPSServer');
							fwrite($handle2,"\r\n");
							if ($debug == false)
								{
								if ($ShutRestart == true)   /* Restart */
								   {
									fwrite($handle2,'shutdown /r');     /* Restart */
									IPSLogger_Dbg(__file__, "Shutdown: es erfolgt ein Restart ");
									}
								else
								   {
									fwrite($handle2,'shutdown /s');
									IPSLogger_Dbg(__file__, "Shutdown: es erfolgt ein Shutdown  ");
									}
								}
							fwrite($handle2,"\r\n");
							fclose($handle2);
							IPS_ExecuteEx("c:/scripts/process_self_shutdown.bat","", true, false,1);
							SetValue($ScriptCounterID,$counter+1);
							}
						else
						   {
						   IPSLogger_Dbg(__file__, "Shutdown: entfernter PC noch nicht vollständig heruntergefahren.");
					   	}
	   			  	break;
				   case 1:

						if (isset($WDconfig["RemoteShutDown"]["Server"]))
						   {
							if ( $processStart["vmplayer.exe"]=="Off" )
							   {
							   $error=false;
							   echo "Entfernten PC herunterfahren.\n";
								IPSLogger_Dbg(__file__, "Shutdown: entfernter PC wird heruntergefahren.");
								tts_play(1,"IP Symcon Visualisierung herunterfahren",'',2);

								/* rpc Zugriff ausprogrammieren um feststellen zu können ob andere Stelle noch erreichbar */
								$method="IPS_GetName"; $params=array();
								$UrlAddress=$WDconfig["RemoteShutDown"]["Server"];
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
								if (!is_scalar($method)) { throw new Exception('Method name has no scalar value');  }
								if (!is_array($params)) { 	throw new Exception('Params must be given as array');   	}
								$id = round(fmod(microtime(true)*1000, 10000));
								$params = array_values($params);
								$strencode = function(&$item, $key) {
									if ( is_string($item) ) $item = utf8_encode($item);
									else if ( is_array($item) ) array_walk_recursive($item, $strencode);
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

								if ($response===false)
								   {
									echo "Remote Server nicht mehr erreichbar.\n";
									IPSLogger_Dbg(__file__, "Shutdown: entfernter PC bereits heruntegfahren.");
							   	}
								else
									{
									echo "Remote Server noch erreichbar.\n";
									$rProgramId = $rpc->IPS_GetObjectIDByName('Program', 0);
									$rLibraryId = $rpc->IPS_GetObjectIDByName('IPSLibrary', $rProgramId);
									$rAppId = $rpc->IPS_GetObjectIDByName('app', $rLibraryId);
									$rModulesId = $rpc->IPS_GetObjectIDByName('modules', $rAppId);
									$rWatchdogId = $rpc->IPS_GetObjectIDByName('Watchdog', $rModulesId);
									$rShutdownId = $rpc->IPS_GetScriptIDByName('Shutdown', $rWatchdogId);
									echo "Remote Shutdown ID : ".$rShutdownId."\n";
									$rpc->IPS_RunScriptEx($rShutdownId, Array('state' =>  'Shutdown'));

									IPSLogger_Dbg(__file__, "Shutdown: entfernter PC wird nun heruntergefahren. Remote Script ".$rShutdownId." gestartet");
									}
							   }
							else
							   {
								IPSLogger_Dbg(__file__, "Shutdown: vmplayer nicht gestartet.");
								}
							} // endif Remote Server ueberhaupt definiert

				      SetValue($ScriptCounterID,$counter+1);
						break;
				   case 0:
					default:
					   break;
				   }
				break;

			default:
				IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']." ID unbekannt.");
			   break;
			}
		}



?>