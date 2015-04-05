<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 120);

$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,2,30,0);  /* immer um 02:30 */
	}
IPS_SetEventActive($tim1ID,true);

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
	$remServer=RemoteAccess_GetConfiguration();
	
$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$result=$moduleManager->GetInstalledModules();
print_r($result);

$remServer=RemoteAccess_GetConfiguration();
foreach ($remServer as $Name => $Server)
	{
	$rpc = new JSONRPC($Server);

	$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	$visname=IPS_GetName(0);
	echo "Server : ".$Name."  ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n";

	$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
	$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
	$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
	$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
	$tempID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Temperatur");
	$switchID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Schalter");
	$contactID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Kontakte");
	$motionID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Bewegungsmelder");
	$humiID[$Name]=RPC_CreateCategoryByName($rpc, $servID, "Feuchtigkeit");
	echo "Remote VIS-ID                    ".$visrootID,"\n";
	echo "Remote WebFront-ID               ".$wfID,"\n";
	echo "Remote Administrator-ID          ".$webID,"\n";
	echo "RemoteAccess-ID                  ".$raID,"\n";
	echo "RemoteServer-ID                  ".$servID,"\n";

	$RPCHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$RPCarchiveHandlerID[$Name] = $RPCHandlerID[0];
	}
echo "\nOID          ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($Name,10);
	}
echo "\nTemperature  ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($tempID[$Name],10);
	}
echo "\nSwitch       ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($switchID[$Name],10);
	}
echo "\nKontakt      ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($contactID[$Name],10);
	}
echo "\nBewegung     ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($motionID[$Name],10);
	}
echo "\nFeuchtigkeit ";
foreach ($remServer as $Name => $Server)
	{
	echo str_pad($humiID[$Name],10);
	}
echo "\n\n";
break;

if (isset ($result["Guthabensteuerung"]))
  	{
  	/* nur wenn Guthabensteuerung installiert ist ausführen */
	echo "Mobilfunk Guthaben Struktur auf Remote Servern aufbauen:\n";
	
	foreach ($remServer as $Server)
		{
		$rpc = new JSONRPC($Server);

		$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
		$visname=IPS_GetName(0);
		echo "\nServer : ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n\n";
		$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
		$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
		$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
		$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
		$guthID=RPC_CreateCategoryByName($rpc, $servID, "Guthaben");

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
				echo "    ".str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo "    ".str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$ergebnis=RPC_CreateVariableByName($rpc, $guthID, $Key["Name"], $Key["Typ"]);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$ergebnis,'IPSModuleSensor_Remote');
			}
		}
	}

if (isset ($result["Amis"]))
  	{
  	/* nur wenn AMIS installiert ist ausführen */
	echo "Amis Stromverbrauch Struktur auf Remote Servern aufbauen\n";
	
	foreach ($remServer as $Server)
		{
		$rpc = new JSONRPC($Server);

		$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
		$visname=IPS_GetName(0);
		echo "Server : ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n\n";
		$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
		$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
		$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
		$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
		$stromID=RPC_CreateCategoryByName($rpc, $servID, "Stromverbrauch");

		/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

		//IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

		IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$stromverbrauch=AmisStromverbrauchList();

		foreach ($stromverbrauch as $Key)
			{
	      $oid=(integer)$Key["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			//print_r($variabletyp);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$ergebnis=RPC_CreateVariableByName($rpc, $stromID, $Key["Name"], $Key["Typ"]);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$ergebnis,'IPSModuleSensor_Remote');
			}
		}



	}


?>
