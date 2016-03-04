<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für Homematic und FS20 Schalten
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 800);
$startexec=microtime(true);

	/***************** INSTALLATION **************/

	echo "Update Konfiguration und register Events\n";

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	/******************************************** Schalter  *****************************************/

	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	$remServer=ROID_List();
	//print_r($remServer);

	echo "\n******* Alle Homematic Schalter ausgeben.       ".(microtime(true)-$startexec)." Sekunden\n";
	foreach ($Homematic as $Key)
		{
		/* alle Homematic Schalterzustände ausgeben */
		if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["INHIBIT"]) and (isset($Key["COID"]["ERROR"])==false) )
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
  	      	$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}
				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					//print_r($Server);
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0);
	   			$rpc->IPS_SetVariableCustomProfile($result,"Switch");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_Remote,'.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "Homematic Switch mit Parameter :".$parameter." erzeugt.\n";
				}
		}

	echo "******* Alle FS20 Schalter ausgeben.\n";
	foreach ($FS20 as $Key)
		{
		/* FS20 alle Schalterzustände ausgeben */
		if (isset($Key["COID"]["StatusVariable"])==true)
		   	{
      		$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
  	      	$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}
				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0);
		   		$rpc->IPS_SetVariableCustomProfile($result,"Switch");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_Remote,'.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "FS20 Switch mit Parameter :".$parameter." erzeugt.\n";
			}
		}


?>
