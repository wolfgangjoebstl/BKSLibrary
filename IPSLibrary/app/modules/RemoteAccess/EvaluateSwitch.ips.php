<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für Homematic und FS20 Schalten
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 2000);    /* sollte man am Ende wieder zurückstellen, gilt global */
set_time_limit(120);
$startexec=microtime(true);
$donotregister=false; $i=0; $maxi=600;

	/***************** INSTALLATION **************/

	echo "Update Konfiguration und register Events\n";

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");

	/* Folgende Variablen werden von Evaluate Hardware erstellt */
	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	/******************************************** Schalter  *****************************************/

	IPSUtils_Include ("EvaluateVariables.inc.php","IPSLibrary::app::modules::RemoteAccess");
echo "Liste der Remote Logging Server (mit Status Active und für Logging freigegeben):\n";
$status=RemoteAccessServerTable();
print_r($status);

echo "Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben):\n";
$remServer=ROID_List();
print_r($remServer);
	
	$struktur=array();
	foreach ($remServer as $Name => $Server)
		{
		echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
		if ( $status[$Name]["Status"] == true )
			{
			$id=(integer)$Server["Schalter"];
			$rpc = new JSONRPC($Server["Adresse"]);	
			$children=$rpc->IPS_GetChildrenIDs($id);
			$struktur[$Name]=array();			
			foreach ($children as $oid)
				{
				$struktur[$Name][$oid]=$rpc->IPS_GetName($oid);
				}
			}		
		}
	echo "Struktur Server :\n";
	foreach ($struktur as $Name => $Eintraege)
		{
		echo "   ".$Name."  hat ".sizeof($Eintraege)." Eintraege \n";
		}
	print_r($struktur);
	
	echo "\n******* Alle Homematic Schalter ausgeben.       ".(microtime(true)-$startexec)." Sekunden\n";
	foreach ($Homematic as $Key)
		{
		/* alle Homematic Schalterzustände ausgeben */
		if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["INHIBIT"]) and (isset($Key["COID"]["ERROR"])==false) )
	   		{
	      	$oid=(integer)$Key["COID"]["STATE"]["OID"];
  	      	$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       Exectime: ".exectime($startexec)." Sekunden\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       Exectime: ".exectime($startexec)." Sekunden\n";
					}
				$parameter="";
				if ($donotregister==false)
			   	{
					$i++; if ($i>$maxi) { $donotregister=true; }				
					foreach ($remServer as $Name => $Server)
						{
						echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
						if ( $status[$Name]["Status"] == true )
							{						
							//print_r($Server);
							$rpc = new JSONRPC($Server["Adresse"]);
							$result=RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0, $struktur[$Name]);
							$rpc->IPS_SetVariableCustomProfile($result,"Switch");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							}
						}
					}	
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_Remote,'.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "   Homematic Switch mit Parameter :".$parameter." erzeugt.\n";
				}
		}

set_time_limit(120);

	echo "******* Alle FS20 Schalter ausgeben.\n";
	foreach ($FS20 as $Key)
		{
		/* FS20 alle Schalterzustände ausgeben */
		if (isset($Key["COID"]["StatusVariable"])==true)
		   	{
      		$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
  	      	$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       Exectime: ".exectime($startexec)." Sekunden\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       Exectime: ".exectime($startexec)." Sekunden\n";
					}
				$parameter="";
				if ($donotregister==false)
			   	{
					$i++; if ($i>$maxi) { $donotregister=true; }				
					foreach ($remServer as $Name => $Server)
						{
						echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
						if ( $status[$Name]["Status"] == true )
							{										
							$rpc = new JSONRPC($Server["Adresse"]);
							$result=RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0, $struktur[$Name]);
							$rpc->IPS_SetVariableCustomProfile($result,"Switch");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							}
						}
					}
			   $messageHandler = new IPSMessageHandler();
			   $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
			   //echo "Message Handler hat Event mit ".$oid." angelegt.\n";
			   $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSwitch_Remote,'.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "   FS20 Switch mit Parameter :".$parameter." erzeugt.\n";
			}
		}


?>