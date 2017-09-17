<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * 	hier für den Regensensor und die Temperaturwerte der FHT Heizungssteuerungen/zentralen
 *    Basis ist das File EvaluateVariables_ROID.inc.php
 *
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
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	
IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
$remServer=ROID_List();

$status=RemoteAccessServerTable();

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
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
				if ( $status[$Name]["Status"] == true )
					{					
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Andere"], $Key["Name"], 2);
					$rpc->IPS_SetVariableCustomProfile($result,"~Rainfall");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);       /* 0 Standard 1 ist Zähler */
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
				}
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Counter,'.$parameter,'IPSModuleSensor_Counter');
			echo "Regenfall Register mit Parameter :".$parameter." erzeugt.\n";
		   //print_r($Key);
		   }
		}

	$FHT = FHTList();
	$keyword="TemeratureVar";

	foreach ($FHT as $Key)
		{
		/* alle Temperaturwerte der Heizungssteuerungen ausgeben */
		if (isset($Key["COID"][$keyword])==true)
  			{
		   echo "Heizungssteuerung gefunden:\n";
	      $oid=(integer)$Key["COID"][$keyword]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			else
	   		{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
				}
			//$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','par3');
			
			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
				if ( $status[$Name]["Status"] == true )
					{				
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Temperatur"], $Key["Name"], 2);
					$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0); /* 0 Standard 1 ist Zähler */
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
				}
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$parameter,'IPSModuleSensor_Temperatur,1,2,3');
			echo "Heizungssteuerungs Register mit Parameter :".$parameter." erzeugt.\n";
			}
		}

?>