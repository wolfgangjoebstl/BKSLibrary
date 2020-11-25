<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für alle Homematic Taster, Custom Component Sensor_remote wird verwendet
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
//ini_set('max_execution_time', 5000);
set_time_limit(120);
$startexec=microtime(true);

$donotregister=false; $i=0; $maxi=40;

/******************************************************

				INSTALLATION

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

echo "RA Category Data ID   : ".$CategoryIdData."\n";
echo "RA Category App ID    : ".$CategoryIdApp."\n";

echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
if (isset ($installedModules["OperationCenter"])) { 	echo "  Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; }
if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
if (isset ($installedModules["DetectMovement"])) { 	echo "  Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n";}
echo "\n";


 /******************************************************
  *
  *  			INSTALLATION
  *
  *************************************************************/

	if (isset ($installedModules["DetectMovement"]))
		{
		IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
		}

	echo "Update Konfiguration und register Events für Homematic Taster.\n";

   	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

	/* die hier aufgerufenen Functions sind das von Evelaute hardware in EvaluateHardware.inc angelegte Inventory der lokalen Hardware Register. */

	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();
	$FS20EX= FS20EXList();
	
	/******************************************* Kontakte ***********************************************/

	/*	ROID_List() bestimmt die Server an die Daten gesendet werden sollen,  
 	 * Function Ist in EvaluateVariables_ROID.inc in Modul RemoteAccess und wird von add_remoteServer aus RemoteAccess_GetConfiguration angelegt !
	 * Aufruf erfolgt in RemoteAccess. es wird auf den remote Servern die komplette Visualization Struktur erfasst und in EvaluateVariables.inc gespeichert.
	 * Bislang werden allerdings nur die Kategorien im includefile angegeben. man könnte es auch um die Objekte in den Kategorien erweitern. Diese werden
	 * aktuell einmal pro Scriptaufruf ermittelt.
	 *
	 *    [Server Name] => Array ([Adresse], [VisRootID], [WebFront], [Administrator], [RemoteAccess], [ServerName], [Temperatur] 
	 *                  [Schalter], [Kontakte], [Taster], [Bewegung], [HeatControl], [HeatSet], [Humidity], [SysInfo], [Andere], [ArchiveHandler] }
	 *
	 * in EvaluateVariables_ROID.inc werden auch lokale Variablen angelegt um die Suche nach denselben auf den Remoteservern zu erleichtern.
	 *
	 * Mit RemoteAccessServerTable wird anhand von RemoteAccess_GetServerConfig() eine Tabelle erstellt mit den Remote Servern und die im 60 Minutenabstand 
	 * von OperationCenter aktualisierte Erreichbarkeit. Es werden nur die Server uebernommen mit LOGGING STATUS active.
	 *
	 */
	 
 	echo "Remote Server OID der Kategorien aus dem EvaluateVariable_ROID inlude File ausgeben:\n";
	$remServer=ROID_List();
	print_r($remServer);

	echo "Zugangsdaten und Erreichbarkeit der remote Server ausgeben:\n";
	$status=RemoteAccessServerTable();
	print_r($status);

	$remote=new RemoteAccess();
	echo "Jetzt die Struktur für jeden Remote Server und die Kategorie Taster ermitteln und ausgeben:\n";
	$struktur=$remote->RPC_getExtendedStructure($remServer,"Taster");
	$remote->RPC_writeExtendedStructure($struktur);

	echo "******* Alle Homematic Taster ausgeben.     Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
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
                        echo "   Register am Remote Server $name anlegen:\n";
						$result=$remote->RPC_CreateVariableByName($rpc, (integer)$Server["Taster"], $Key["Name"], 0, $struktur[$Name]);
						$rpc->IPS_SetVariableCustomProfile($result,"Button");
						$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
						$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
						$struktur[$Name][$oid]["Active"]=true;							
						}
					}
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnUpdate");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnUpdate",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
				
				/* Kein Detect Movement, Taster sind keine Kontakte */
				
				}
			}
		}

	echo "******* Alle FS20EX Taster ausgeben.\n";
	foreach ($FS20EX as $Key)
		{
		if ( (isset($Key["COID"])==true) and (isset($Key["DeviceList"])==true) )
			{
			/* alle Taster */
			echo str_pad($Key["Name"],30)."\n";
			foreach ($Key["COID"] as $Entry)
				{
				//print_r($Entry);
				$oid=(integer)$Entry["OID"];
				$variabletyp=IPS_GetVariable($oid);
				$SubName=$Entry["Name"];
				if ( strpos($SubName,"rät ") != false) { $SubName = "Status"; }
				if ( strpos($SubName,"räte") != false) { $SubName = "Wert"; }
				$VarName=$Key["Name"]."-".$SubName;
				if ($variabletyp["VariableProfile"]!="")
					{
					echo "    ".str_pad($VarName,30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",$variabletyp["VariableChanged"]).")  ".$variabletyp["VariableType"]."\n";
					}
				else
					{
					echo "    ".str_pad($VarName,30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",$variabletyp["VariableChanged"]).")  ".$variabletyp["VariableType"]."\n";
					}
				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
					if ( $status[$Name]["Status"] == true )
						{
						$rpc = new JSONRPC($Server["Adresse"]);
						$result=RPC_CreateVariableByName($rpc, (integer)$Server["Taster"], $VarName, $variabletyp["VariableType"]);
						if ( $variabletyp["VariableType"] == 0) /* Boolean, loggen, Rest zur Vollstaendigkeit */
							{
							$rpc->IPS_SetVariableCustomProfile($result,"Contact");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							}
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
						$struktur[$Name][$oid]["Active"]=true;							
						}
					}	
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Motion');

				/* Kein Detect Movement, Taster sind keine Kontakte */

				}	
			}
		}
	$remote->RPC_setHiddenExtendedStructure($remServer,$struktur);
	
	
?>