<?

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
	IPS_SetEventCyclicTimeFrom($tim1ID,2,20,0);  /* immer um 02:20 */
	}
IPS_SetEventActive($tim1ID,true);

$remServer=RemoteAccess_GetConfiguration();
//print_r($configuration);

foreach ($remServer as $Server)
	{
	$rpc = new JSONRPC($Server);
	}

	/* nimmt vorerst immer die zweite Adresse */

	$result=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	echo "OID = ".$result." \n";

	$visID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
	$wfID=RPC_CreateCategoryByName($rpc, $visID, "WebFront");
	$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
	$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
	$tempID=RPC_CreateCategoryByName($rpc, $raID, "Temperatur");
	$switchID=RPC_CreateCategoryByName($rpc, $raID, "Schalter");
	$humiID=RPC_CreateCategoryByName($rpc, $raID, "Feuchtigkeit");
	echo "Remote VIS-ID                    ".$visID,"\n";
	echo "Remote WebFront-ID               ".$wfID,"\n";
	echo "Remote Administrator-ID          ".$webID,"\n";
	echo "RemoteAccess-ID                  ".$raID,"\n";
	echo "Remote Temperatur Cat-ID         ".$tempID,"\n";
	echo "Remote Switch Cat-ID             ".$switchID,"\n";
	echo "Remote Feuchtigkeit Cat-ID       ".$humiID,"\n";

	$RPCarchiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$RPCarchiveHandlerID = $RPCarchiveHandlerID[0];

	/***************** INSTALLATION **************/

	echo "Update Konfiguration und register Events\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	$Homematic = HomematicList();

   RPC_CreateVariableField($rpc, $tempID, $Homematic, "TEMPERATURE", "Temperatur",$RPCarchiveHandlerID);  /* rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler */

   RPC_CreateVariableField($rpc, $humiID, $Homematic, "HUMIDITY", "Humidity",$RPCarchiveHandlerID);  /* rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler */

?>
