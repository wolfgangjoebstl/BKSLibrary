<?

	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */  

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * Stromverbrauch Variablen am Remote Server anlegen, kommen vom AMIS Modul
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 400);
$startexec=microtime(true);

IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

/* wird von Remote Access erzeugt : */
IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

/*	ROID_List() bestimmt die Server an die Daten gesendet werden sollen,  
 *  Function Ist in EvaluateVariables.inc in Modul RemoteAccess und wird von add_remoteServer aus RemoteAccess_GetConfiguration angelegt !
 *  Aufruf erfolgt in RemoteAccess. es wird auf den remote Servern die komplette Struktur aufgebaut und in EvaluateVariables.inc gespeichert.
 */
$remServer=ROID_List();

$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
$modules=$moduleManager->GetInstalledModules();

$status=RemoteAccessServerTable();
	
if (isset ($modules["Amis"]))
	{
	/* nur wenn AMIS installiert ist ausführen */
	echo "Amis Stromverbrauch Struktur auf Remote Servern aufbauen:\n";
	$stromID=Array();
	//print_r($status);
	//print_r($remServer);
	foreach ($remServer as $Name => $Server)
		{
		echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
		if ( $status[$Name]["Status"] == true )
			{		
			$rpc = new JSONRPC($Server["Adresse"]);
			$stromID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Stromverbrauch");
			}
		}
	echo "---------------------------------------------------------\n\n";
	echo "Struktur Server :\n";	
	print_r($stromID);
  	
  	/* EvaluateVariables.inc wird automatisch nach Aufruf von RemoteAccess erstellt , enthält Routine AmisStromverbrauchlist */
	$stromverbrauch=AmisStromverbrauchList();
	print_r($stromverbrauch);

	foreach ($stromverbrauch as $Key)
		{
        $oid=(integer)$Key["OID"];
     	$variabletyp=IPS_GetVariable($oid);
		//print_r($variabletyp);
		if ($variabletyp["VariableProfile"]!="")
		   {
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		else
		   {
			echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
			}
		$parameter="";
		foreach ($remServer as $Name => $Server)
			{
			if ( $status[$Name]["Status"] == true )
				{				
				$rpc = new JSONRPC($Server["Adresse"]);
				$result=RPC_CreateVariableByName($rpc, $stromID[$Name], $Key["Name"], $Key["Typ"]);
				$rpc->IPS_SetVariableCustomProfile($result,$Key["Profile"]);
				$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
				if ($Key["Profile"]=="~Electricity")
			   		{
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);
					}
				else
				   {
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					}
				$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);
				$parameter.=$Name.":".$result.";";
				}
			}				
	   $messageHandler = new IPSMessageHandler();
	   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
	   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
		$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$parameter,'IPSModuleSensor_Remote');
		echo "Stromverbrauch mit Parameter :".$parameter." erzeugt.\n\n";
		}
	}


?>