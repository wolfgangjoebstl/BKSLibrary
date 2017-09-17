<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * Guthabensteuerung installiert: 
 *     Guthabenvariablen am Remote Server anlegen
 *
 * OperationCenter installiert:
 *     SysInfo Variablen anlegen
 *     Routerdaten ebenfalls anlegen
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

/******************************************************

				INIT

*************************************************************/

// Scriptlaufzeit erfassen, kann sehr viel länger sein da remote Server kontaktiert werden müssen
$startexec=microtime(true);

echo "Liste der Remote Logging Server (mit Status Active und für Logging freigegeben):\n<br>";
$status=RemoteAccessServerTable();
print_r($status);

echo "Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben):\n<br>";
$remServer=ROID_List();
print_r($remServer);

$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$installedModules=$moduleManager->GetInstalledModules();
//print_r($result);

echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
if (isset ($installedModules["Guthabensteuerung"])) { 			echo "  Modul Guthabensteuerung ist installiert.\n"; } else { echo "  Modul Guthabensteuerung ist NICHT installiert.\n"; }
if (isset ($installedModules["OperationCenter"])) { 	echo "  Modul OperationCenter ist installiert.\n"; } else { echo "  Modul OperationCenter ist NICHT installiert.\n"; }
echo "\n";

$messageHandler = new IPSMessageHandler();

if (isset ($installedModules["Guthabensteuerung"]))
	{
	/* nur wenn Guthabensteuerung installiert ist ausführen */
	echo "Mobilfunk Guthaben Struktur auf Remote Servern aufbauen:\n";

	$struktur=array();$guthID=array();
	foreach ($remServer as $Name => $Server)
		{
		echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
		if ( $status[$Name]["Status"] == true )
			{
			$rpc = new JSONRPC($Server["Adresse"]);
			$guthID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Guthaben");
			$children=$rpc->IPS_GetChildrenIDs($guthID[$Name]);
			$struktur[$Name]=array();
			foreach ($children as $oid)
				{
				$struktur[$Name][$oid]=$rpc->IPS_GetName($oid);
				}		
			}
		}	
	echo "Struktur Server :\n";
	print_r($struktur);
	
	/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

	$Guthabensteuerung=GuthabensteuerungList();
	
	foreach ($Guthabensteuerung as $Key)
		{
		set_time_limit(120);
		$oid=(integer)$Key["OID"];
		$variabletyp=IPS_GetVariable($oid);
		//print_r($variabletyp);
		if ($variabletyp["VariableProfile"]!="")
			{
			echo "    ".str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".exectime($startexec)." Sekunden\n";
			}
		else
			{
			echo "    ".str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".exectime($startexec)." Sekunden\n";
			}
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			echo "   Guthabensteuerung: Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
			if ( $status[$Name]["Status"] == true )
				{			
				$rpc = new JSONRPC($Server["Adresse"]);
				$result=RPC_CreateVariableByName($rpc, $guthID[$Name], $Key["Name"], $Key["Typ"],$struktur[$Name]);
				//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
				//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
				//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
				//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
				$parameter.=$Name.":".$result.";";
				}
			}
		$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		}
	}

set_time_limit(180);

if (isset ($installedModules["OperationCenter"]))
	{
	/* nur wenn OperationCenter installiert ist ausführen */

	echo "\nSysInfo Struktur auf Remote Servern aufbauen:\n";
	$SysInfos=SysInfoList();
	foreach ($SysInfos as $SysInfo)
		{
		$oid=(integer)$SysInfo["OID"];
		$variabletyp=IPS_GetVariable($oid);
		if ($variabletyp["VariableProfile"]!="")
			{
			echo "    ".str_pad($SysInfo["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".exectime($startexec)." Sekunden\n";
			}
		else
			{
			echo "    ".str_pad($SysInfo["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".exectime($startexec)." Sekunden\n";
			}	
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			echo "   System Information: Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
			if ( $status[$Name]["Status"] == true )
				{			
				$rpc = new JSONRPC($Server["Adresse"]);
				$result=RPC_CreateVariableByName($rpc, (integer)$Server["SysInfo"], $SysInfo["Name"], $SysInfo["Typ"]);
				$parameter.=$Name.":".$result.";";
				}
			}
		$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		}				

	echo "\nRouter Datenverbrauch Struktur auf Remote Servern aufbauen:\n";
	$OperationCenterID=array();
	foreach ($remServer as $Name => $Server)
		{
		echo "   OperationCenter: Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
		if ( $status[$Name]["Status"] == true )
			{					
			$rpc = new JSONRPC($Server["Adresse"]);
			$OperationCenterID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "OperationCenter");
			}
		}

	/* RPC braucht elendslang in der Verarbeitung, bis hierher 10 Sekunden !!!! */

	$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
					
	IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
	$OperationCenterConfig = OperationCenter_Configuration();
  	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
		echo "Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$OperationCenterDataId);
		if ($router_categoryId != false)
			{
			setup_variable("Download",$router['NAME'],$OperationCenterID);
			setup_variable("Upload",$router['NAME'],$OperationCenterID);
  			setup_variable("Total",$router['NAME'],$OperationCenterID);
			}
   		}
	}

/*******************************************************************/

function setup_variable($name, $router, $rpc_catID)
	{
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	$remServer=ROID_List();

	$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
	$router_categoryId=@IPS_GetObjectIDByName("Router_".$router,$OperationCenterDataId);
	$router_OID=@IPS_GetObjectIDByName($name,$router_categoryId);
	echo "   folgende Ergebnisse für ".$router." in OID : ".$OperationCenterDataId." Cat-OID : ".	$router_categoryId."  Data OID : ".$router_OID."\n";
	if (($router_categoryId != false) && ($router_OID != false))
		{
	  	/* nur wenn bereits zumindest das Datenfeld angelegt wurde weitermachen */
	  	$router_name=$router."_".$name;
    	$variabletyp=IPS_GetVariable($router_OID);
    	//print_r($variabletyp);
		if (($variabletyp["VariableCustomProfile"]!="") || ($variabletyp["VariableProfile"]!=""))
			{
			echo "       ".str_pad($router_name,30)." = ".GetValueFormatted($router_OID)."   (".date("d.m H:i",IPS_GetVariable($router_OID)["VariableChanged"]).")       \n";
			}
		else
			{
			echo "       ".str_pad($router_name,30)." = ".GetValue($router_OID)."   (".date("d.m H:i",IPS_GetVariable($router_OID)["VariableChanged"]).")       \n";
			}
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			echo "Server : ".$Name."  Url : ".$Server["Adresse"]."\n";
			$rpc = new JSONRPC($Server["Adresse"]);
			$result=RPC_CreateVariableByName($rpc, $rpc_catID[$Name], $router_name, 2);
			//$rpc->IPS_SetVariableCustomProfile($result,"Temperatur");
			//$rpc->AC_SetLoggingStatus($RPCarchiveHandlerID,$result,true);
			//$rpc->AC_SetAggregationType($RPCarchiveHandlerID,$result,1);
			//$rpc->IPS_ApplyChanges($RPCarchiveHandlerID);
			$parameter.=$Name.":".$result.";";
			}
		$messageHandler = new IPSMessageHandler();
		$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
		$messageHandler->CreateEvent($router_OID,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($router_OID,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		}
	}


?>