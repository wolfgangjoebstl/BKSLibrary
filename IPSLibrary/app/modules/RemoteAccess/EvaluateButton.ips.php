<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für alle Homematic Taster, Custom Component Sensor_remote wird verwendet
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 120);
$startexec=microtime(true);

$donotregister=false;

	/***************** INSTALLATION **************/

	echo "Update Konfiguration und register Events für Homematic Taster.\n";

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	/******************************************* Kontakte ***********************************************/


	$remServer=ROID_List();

	echo "******* Alle Homematic Taster ausgeben.\n";
	
	$keyword="MOTION";
	foreach ($Homematic as $Key)
		{
		if ( (isset($Key["COID"]["INSTALL_TEST"])==true) and (isset($Key["COID"]["PRESS_SHORT"])==true) )
	   	{
	   	/* alle Kontakte */

	      $oid=(integer)$Key["COID"]["PRESS_SHORT"]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo "  ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo "  ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$parameter="";
			if ($donotregister==false)
			   {
				foreach ($remServer as $Name => $Server)
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Taster"], $Key["Name"], 0);
	   			$rpc->IPS_SetVariableCustomProfile($result,"Button");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   	//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnUpdate");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnUpdate",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
				}
			}
		}



?>
