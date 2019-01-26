<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 *	hier für HomematicIP, Homematic und FS20 Schalten
 *
 */

	Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

    /******************************************************

				INIT

    *************************************************************/

	// max. Scriptlaufzeit definieren
	ini_set('max_execution_time', 2000);    /* sollte man am Ende wieder zurückstellen, gilt global */
	set_time_limit(120);
	$startexec=microtime(true);

	/***************** INSTALLATION **************/

	echo "Update Switch configuration and register Events\n";

	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

	/******************************************** Schalter  *****************************************/

	echo "***********************************************************************************************\n";
	echo "Switch Handler wird ausgeführt. Macht bereits install CustomCompnents, DetectMovement und RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic, HomematicIP und FS20 Switche werden registriert.\n";
    echo "\n";
	if (function_exists('HomematicList'))
		{
        /* die Homematic Switche werden installiert, Routine übernimmt install CustomComponents, DetectMovement und RemoteAccess */
		$struktur1=installComponentFull(HomematicList(),["STATE","INHIBIT","!ERROR"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,');				/* Homematic Switche */
	    echo "***********************************************************************************************\n";
		$struktur2=installComponentFull(HomematicList(),["STATE","SECTION","PROCESS"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,');			    /* HomemeaticIP Switche */
	    echo "***********************************************************************************************\n";        
        $struktur3=installComponentFull(FS20List(),"StatusVariable",'IPSComponentSwitch_RFS20','IPSModuleSwitch_IPSHeat,');
		}
	echo "***********************************************************************************************\n";
    print_r($struktur1);
    print_r($struktur2);
    print_r($struktur3);

if (false)
    {
	$donotregister=false; $i=0; $maxi=600;
	/* Folgende Variablen werden von Evaluate Hardware erstellt */
	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

    $componentName="IPSComponentSwitch_Remote";
    $componentHomematicName="IPSComponentSwitch_Remote";

	$remote=new RemoteAccess();
	$status=$remote->RemoteAccessServerTable();
	echo "Liste der Remote Logging Server (mit Status Active und für Logging freigegeben):        Exectime: ".exectime($startexec)." Sekunden\n";
	echo $remote->writeRemoteAccessServerTable($status);

	echo "Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben):              Exectime: ".exectime($startexec)." Sekunden\n";
	$remServer=$remote->get_listofROIDs();
	echo $remote->write_listofROIDs();
	
	$struktur=$remote->get_StructureofROID();
	echo "Struktur Server :                Exectime: ".exectime($startexec)." Sekunden\n";
	foreach ($struktur as $Name => $Eintraege)
		{
		echo "   ".$Name." für Schalter hat ".sizeof($Eintraege)." Eintraege \n";
		//print_r($Eintraege);
		foreach ($Eintraege as $Eintrag) echo "      ".$Eintrag["Name"]."   ".$Eintrag["OID"]."\n";
		}
	
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
							$result=$remote->RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0, $struktur[$Name]);
							$rpc->IPS_SetVariableCustomProfile($result,"Switch");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							$struktur[$Name][$result]["Status"]=true;
							$struktur[$Name][$result]["Hide"]=false;
							$struktur[$Name][$result]["newName"]=$Key["Name"];
							}
						}
					}	
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",$componentHomematicName.','.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "   Homematic Switch mit Parameter :".$parameter." erzeugt.\n";
				}
		}

	set_time_limit(120);

	echo "\n******* Alle HomematicIP Schalter ausgeben.       ".(microtime(true)-$startexec)." Sekunden\n";
	foreach ($Homematic as $Key)
		{
		/* alle HomematicIP Schalterzustände ausgeben */
		if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["SECTION"]) and isset($Key["COID"]["PROCESS"]) )
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
							$result=$remote->RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0, $struktur[$Name]);
							$rpc->IPS_SetVariableCustomProfile($result,"Switch");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							$struktur[$Name][$result]["Status"]=true;
							$struktur[$Name][$result]["Hide"]=false;
							$struktur[$Name][$result]["newName"]=$Key["Name"];
							}
						}
					}	
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",$componentHomematicName.','.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "   HomematicIP Switch mit Parameter :".$parameter." erzeugt.\n";
				}
		}

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
							$result=$remote->RPC_CreateVariableByName($rpc, (integer)$Server["Schalter"], $Key["Name"], 0, $struktur[$Name]);
							$rpc->IPS_SetVariableCustomProfile($result,"Switch");
							$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
							$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
							$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
							$parameter.=$Name.":".$result.";";
							$struktur[$Name][$result]["Status"]=true;
							$struktur[$Name][$result]["Hide"]=false;
							$struktur[$Name][$result]["newName"]=$Key["Name"];														
							}
						}
					}
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				//echo "Message Handler hat Event mit ".$oid." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",$componentName.','.$parameter,'IPSModuleSwitch_IPSLight,1,2,3');
				echo "   FS20 Switch mit Parameter :".$parameter." erzeugt.\n";
			}
		}
    
    print_r($struktur);
	foreach ($struktur as $server => $entries)
		{
		//echo $remServer[$server]["Name"].":\n";
		print_r($remServer[$server]);
		$rpc = new JSONRPC($remServer[$server]["Adresse"]);		
		foreach ($entries as $oid => $entry)
			{
			if ($entry["Hide"])
				{
				echo "Wert aus alten Zeiten. einfach verstecken, damit die Werte erhalten bleiben.\n";
				}
			}
		}
    }    
	
?>