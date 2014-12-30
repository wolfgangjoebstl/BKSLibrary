<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

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

	/* macht einmal die Installation, später rueberkopieren, Routine dann eigentlich unnötig */

	$pname="Temperatur";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		$rpc->IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		$rpc->IPS_SetVariableProfileText($pname,'',' °C');
	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Humidity";
	if ($rpc->IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		$rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
  		$rpc->IPS_SetVariableProfileText($pname,'',' %');
	   //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

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
