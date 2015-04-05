<?

/******** macht Evaluate Variables */


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
	IPS_SetEventCyclicTimeFrom($tim1ID,2,40,0);  /* immer um 02:40 */
	}
IPS_SetEventActive($tim1ID,true);

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$result=$moduleManager->GetInstalledModules();
//if (isset ($result["Amis"]))
if (false)
  	{
  	/* nur ausführen wenn AMIS installiert wurde */

	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
	$remServer=RemoteAccess_GetConfiguration();

	foreach ($remServer as $Server)
		{
		$rpc = new JSONRPC($Server);

		$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
		$visname=IPS_GetName(0);
		echo "Server : ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n";
		$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
		$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
		$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
		$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
		$amiswebID=RPC_CreateCategoryByName($rpc, $servID, "Stromverbrauch");

		/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

		//IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

		IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$AmisStromverbrauch=AmisStromverbrauchList()();
	   print_r($AmisStromverbrauch);
	   
		foreach ($AmisStromverbrauch as $Key)
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
			$result=RPC_CreateVariableByName($rpc, $guthID, $Key["Name"], $Key["Typ"]);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$result,'IPSModuleSensor_Remote');
	
			}
		}
	}


/******************************************************************/

function add_variable($variableID,&$includefile,&$count)
	{
	$includefile.='"'.IPS_GetName($variableID).'" => array('."\n         ".'"OID" => '.$variableID.', ';
	$includefile.="\n         ".'"Name" => "'.IPS_GetName($variableID).'", ';
	$variabletyp=IPS_GetVariable($variableID);
	//print_r($variabletyp);
	//echo "Typ:".$variabletyp["VariableValue"]["ValueType"]."\n";
	$includefile.="\n         ".'"Typ" => '.$variabletyp["VariableValue"]["ValueType"].', ';
	$includefile.="\n         ".'"Order" => "'.$count++.'", ';
	$includefile.="\n             ".'	),'."\n";
	}

?>
