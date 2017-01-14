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
//ini_set('max_execution_time', 5000);
set_time_limit(120);
$startexec=microtime(true);

$donotregister=false; $i=0; $maxi=40;

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

	/*	ROID_List() bestimmt die Server an die Daten gesendet werden sollen,  
 	 *  Function Ist in EvaluateVariables.inc in Modul RemoteAccess und wird von add_remoteServer aus RemoteAccess_GetConfiguration angelegt !
	 *  Aufruf erfolgt in RemoteAccess. es wird auf den remote Servern die komplette Struktur aufgebaut und in EvaluateVariables.inc gespeichert.
	 */
	$remServer=ROID_List();
	$struktur=array();
	foreach ($remServer as $Name => $Server)
		{
		echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
		if ( $status[$Name]["Status"] == true )
			{			
			$id=(integer)$Server["Taster"];
			$rpc = new JSONRPC($Server["Adresse"]);	
			$children=$rpc->IPS_GetChildrenIDs($id);
			foreach ($children as $oid)
			   	{
			   	$struktur[$Name][$oid]=$rpc->IPS_GetName($oid);
	   			}		
			}
		}
	echo "Struktur Server :\n";
	print_r($struktur);

	echo "******* Alle Homematic Taster ausgeben.\n";
	
	$keyword="MOTION";
	foreach ($Homematic as $Key)
		{
		set_time_limit(1200);		
		if ( (isset($Key["COID"]["INSTALL_TEST"])==true) and (isset($Key["COID"]["PRESS_SHORT"])==true) )
	   		{
	   		/* alle Kontakte */

	      	$oid=(integer)$Key["COID"]["PRESS_SHORT"]["OID"];
	      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   	{
				echo "  ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")                                    Exectime: ".exectime($startexec)."\n";
				}
			else
			   	{
				echo "  ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")                                    Exectime: ".exectime($startexec)."\n";
				}
			$parameter="";
			if ($donotregister==false)
			   	{
				$i++; if ($i>$maxi) { $donotregister=true; }
				foreach ($remServer as $Name => $Server)
					{
					if ( $status[$Name]["Status"] == true )
						{						
						$rpc = new JSONRPC($Server["Adresse"]);
						$result=RPC_CreateVariableByName($rpc, (integer)$Server["Taster"], $Key["Name"], 0, $struktur[$Name]);
	   					$rpc->IPS_SetVariableCustomProfile($result,"Button");
						$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
						$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
						}
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