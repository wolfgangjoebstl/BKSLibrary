<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für alle Homematic Kontakte
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 120);
$startexec=microtime(true);

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

	echo "Update Konfiguration und register Events\n";

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	/******************************************* Kontakte ***********************************************/

	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
	$remServer=ROID_List();
	$status=RemoteAccessServerTable();

	echo "******* Alle Homematic Kontakte ausgeben.\n";
	$keyword="MOTION";
	foreach ($Homematic as $Key)
		{
		if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
	   	{
	   	/* alle Kontakte */

	      $oid=(integer)$Key["COID"]["STATE"]["OID"];
      	$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			else
			   {
				echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
				}
			$parameter="";
			foreach ($remServer as $Name => $Server)
				{
				echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
				if ( $status[$Name]["Status"] == true )
					{
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=RPC_CreateVariableByName($rpc, (integer)$Server["Kontakte"], $Key["Name"], 0);
	   			$rpc->IPS_SetVariableCustomProfile($result,"Contact");
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					}
				}	
		   $messageHandler = new IPSMessageHandler();
		   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
		   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
			$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion,'.$parameter,'IPSModuleSensor_Motion');
			//echo "Detect Movement anlegen.\n";
		   $DetectMovementHandler = new DetectMovementHandler();
			$DetectMovementHandler->RegisterEvent($oid,"Contact",'','');
			}
		}



?>