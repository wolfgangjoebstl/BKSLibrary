<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier kann man schnell einen Ueberblick über die angeschlossenen VIS Server erhalten
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
//ini_set('max_execution_time', 500);
//$startexec=microtime(true);

	echo "Overview of registered Events\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

	$Homematic = HomematicList();

	$messageHandler = new IPSMessageHandler();

	$eventConfigurationAuto = IPSMessageHandler_GetEventConfiguration();
	print_r($eventConfigurationAuto);
	
	/* manchmal geht die Adressierung mit der externen Adresse leichter als mit der internen Adresse ? */
	//$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/");
	//$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");

	/* Wenn die Servernamen geändert werden muss auch Remoteaccess ausgeführt werden, damit wieder die ROID_List() richtig gestellt wird */
	
	echo "Overview of registered Data Servers\n";
	$remServer=RemoteAccess_GetConfiguration();
	print_r($remServer);
	foreach ($remServer as $Name => $Server)
		{
		$rpc = new JSONRPC($Server);
		$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
		echo "Contact to Server : ".$Server. " successful.\n";
		}
	
	
?>
