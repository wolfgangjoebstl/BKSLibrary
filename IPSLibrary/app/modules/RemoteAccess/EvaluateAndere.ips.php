<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Ver�nderung Werte geschrieben werden
	hier f�r den Regensensor und  andere Werte
	
*/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

	echo "Update Konfiguration und register Events\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
$remServer=ROID_List();

	$Homematic = HomematicList();
	//print_r($Homematic);
	foreach ($Homematic as $Key)
		{
		/* alle Regensensoren ausgeben */
		if (isset($Key["COID"]["RAIN_COUNTER"])==true)
		   {
		   echo "Regensensor gefunden:\n";
		   $oid=(integer)$Key["COID"]["RAIN_COUNTER"]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
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
				$result=RPC_CreateVariableByName($rpc, (integer)$Server["Andere"], $Key["Name"], 2);
   			$rpc->IPS_SetVariableCustomProfile($result,"~Rainfall");
				$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
				$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);
				$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
				$parameter.=$Name.":".$result.";";
				}
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird f�r HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
			echo "Regenfall Register mit Parameter :".$parameter." erzeugt.\n";
		   //print_r($Key);
		   }
		}


?>