<?

/* Stromverbrauch variablen am Remote Server anlegen */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 400);
$startexec=microtime(true);

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

/* wird von Remote Access erzeugt : */
IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");


$remServer=ROID_List();

$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$modules=$moduleManager->GetInstalledModules();

if (isset ($modules["Amis"]))
  	{
  	/* nur wenn AMIS installiert ist ausführen */
	echo "Amis Stromverbrauch Struktur auf Remote Servern aufbauen:\n";
	echo "---------------------------------------------------------\n\n";

	foreach ($remServer as $Name => $Server)
		{
		$rpc = new JSONRPC($Server["Adresse"]);
		$stromID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Stromverbrauch");
		}

  	
  	/* EvaluateVariables.inc wird automatisch nach Aufruf von RemoteAccess erstellt , enthält Routin AmisStromverbrauchlist */
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	$stromverbrauch=AmisStromverbrauchList();
	print_r($stromverbrauch);

	foreach ($stromverbrauch as $Key)
		{
      $oid=(integer)$Key["OID"];
     	$variabletyp=IPS_GetVariable($oid);
		//print_r($variabletyp);
		if ($variabletyp["VariableProfile"]!="")
		   {
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		else
		   {
			echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$result=RPC_CreateVariableByName($rpc, $stromID[$Name], $Key["Name"], $Key["Typ"]);
			$rpc->IPS_SetVariableCustomProfile($result,$Key["Profile"]);
			$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
			if ($Key["Profile"]=="~Electricity")
			   {
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);
				}
			else
			   {
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
				}
			$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);
			$parameter.=$Name.":".$result.";";
			}
	   $messageHandler = new IPSMessageHandler();
	   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
	   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		echo "Stromverbrauch mit Parameter :".$parameter." erzeugt.\n\n";
		}
	}


?>
