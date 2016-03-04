<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * Guthabenvariablen am Remote Server anlegen
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 400);
$startexec=microtime(true);

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
$remServer=ROID_List();
	
$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$modules=$moduleManager->GetInstalledModules();
//print_r($result);

if (isset ($modules["Guthabensteuerung"]))
  	{
  	/* nur wenn Guthabensteuerung installiert ist ausführen */
	echo "Mobilfunk Guthaben Struktur auf Remote Servern aufbauen:\n";
	
	foreach ($remServer as $Name => $Server)
		{
		$rpc = new JSONRPC($Server["Adresse"]);
		$guthID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Guthaben");
		}

	/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

	//IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
  	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	$Guthabensteuerung=GuthabensteuerungList();
	
	foreach ($Guthabensteuerung as $Key)
		{
      $oid=(integer)$Key["OID"];
     	$variabletyp=IPS_GetVariable($oid);
		//print_r($variabletyp);
		if ($variabletyp["VariableProfile"]!="")
		   {
			echo "    ".str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		else
		   {
			echo "    ".str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$result=RPC_CreateVariableByName($rpc, $guthID[$Name], $Key["Name"], $Key["Typ"]);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
			$parameter.=$Name.":".$result.";";
			}
	   $messageHandler = new IPSMessageHandler();
	   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
	   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		}
	}


?>
