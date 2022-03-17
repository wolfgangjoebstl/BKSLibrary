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
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(500); 
    $startexec=microtime(true);

    $forceDelete=true;

    IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

	$Homematic = HomematicList();

	$messageHandler = new IPSMessageHandlerExtended(); /* auch delete von Events moeglich */

	/*********************************************************************
	 * 
	 * Ausgabe aller Events die konfiguriert sind
	 *
	 * dazu config File auslesen
	 *
	 ***********************************************************************************/

 	//$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
 	$eventConf = IPSMessageHandler_GetEventConfiguration();
 	$eventCust = IPSMessageHandler_GetEventConfigurationCust();
	$eventlist = $eventConf + $eventCust;
	echo "Overview of registered Events ".sizeof($eventConf)." + ".sizeof($eventCust)." = ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventConf as $oid => $data)
		{
        echo str_pad($oid,7)." | ".$data[0]." | ".str_pad($data[1],80)." | ".str_pad($data[2],40)." | ";
		if (IPS_ObjectExists($oid))
			{
			echo IPS_GetName($oid)."\n";
			}
		else
			{
			echo "--------->  OID nicht verfügbar !\n";
			}
		}
	
	/*********************************************************************
	 *
	 * Ausgabe aller Events die wirklich im System registriert sind
	 *
	 * dazu die Childrens (Events) des Eventhandlers auslesen
	 * Datenbank der verwendeten Components anlegen
	 *
	 ***********************************************************************************/
	
	$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
	echo"\n";
	echo "Zusätzliche Checks bei der Eventbearbeitung, ausgehenbd von den registrierten Events:\n";
	echo "ScriptID der Eventbearbeitung : ".$scriptId." \n";
	echo"\n";
	$children=IPS_GetChildrenIDs($scriptId);
	$components=array();
	$i=0;
	//print_r($children);
	foreach ($children as $childrenID)
		{
		$name=IPS_GetName($childrenID);
		$eventID_str=substr($name,Strpos($name,"_")+1,10);
		$eventID=(integer)$eventID_str;
		if (substr($name,0,1)=="O")
			{
            echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." | ";
            if (isset($eventlist[$eventID_str]))
                {
                $componentconfig=explode(",",$eventlist[$eventID_str][1]);
                //print_r($componentconfig);					
                $parent=@IPS_GetParent($eventID);
                if ($parent===false)
                    {		
                    echo "  ---> Objekt ".$eventID." existiert nicht für Event ".$childrenID.". Muss als ".$name." gelöscht werden.\n";
                    if ($forceDelete)
                        {
                        echo " wird geloescht ***********************\n";
                        $messageHandler->UnRegisterEvent($eventID);
                        IPS_DeleteEvent($childrenID);
                        }
                    }
                else
                    {
                    $component[$componentconfig[0]][$eventID]["EventName"]=IPS_GetName($childrenID);
                    $component[$componentconfig[0]][$eventID]["Config"]=$eventlist[$eventID_str][1];
                    $object=IPS_GetObject($parent);
                    if ( $object["ObjectType"] == 1)
                        {
                        echo $eventID." | Instanz  : ".str_pad(IPS_GetName($parent),46)." | ".$eventlist[$eventID_str][1]."\n";
                        $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($parent);
                        }
                    else
                        {
                        echo $eventID." | Register : ".str_pad(IPS_GetName($eventID),46)." | ".$eventlist[$eventID_str][1]."\n";
                        $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($eventID);
                        }		
                    //print_r($eventlist[$eventID_str]);
                    }
                }
            else
                {
                echo " ----> Objekt ".$eventID." ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36)." existiert nicht in IPSMessageHandler_GetEventConfiguration().\n";
                if ($forceDelete)
                    {
                    echo " wird geloescht ***********************\n";
                    $messageHandler->UnRegisterEvent($eventID);
                    IPS_DeleteEvent($childrenID);
                    }
                }
			}
		$i++;
		//IPS_SetPosition($childrenID,$eventID);
		}
	
	echo "\nAnzeige der konfigurierten IPS Components und die Anzahl der Konfigurationen:\n";
	foreach ($component as $componentName => $componentEntry)
		{
		echo "   ComponentName:  ".str_pad($componentName,51)."  |  ".sizeof($componentEntry)."\n";
		}
									
	echo "\nAnzeige der Paramnetrierung der einzelnen Components. Vorverarbeitung für die Übersichtlichkeit. Untätige Components werden nicht angezeigt.\n";
			
	foreach ($component as $componentName => $componentEntry)
		{
		echo "ComponentName:  ".$componentName."\n";
		foreach ($componentEntry as $Event => $componentConf)
			{
			$configComp=explode(",",$componentConf["Config"]);
			switch ($componentName)
				{
				case "IPSComponentSensor_Remote":
					if ( $configComp[1] != "" )
						{
						print_R($componentConf);
						}
					break;
				default:		
					print_R($componentConf);
					break;
				}
			}		
		}
		
	/* manchmal geht die Adressierung mit der externen Adresse leichter als mit der internen Adresse ? */
	//$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/");
	//$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");

	/* Wenn die Servernamen geändert werden muss auch Remoteaccess ausgeführt werden, damit wieder die ROID_List() richtig gestellt wird */
	
	echo "Overview of registered Data Servers\n";
	$remServer=RemoteAccess_GetConfigurationNew();
	print_r($remServer);
	foreach ($remServer as $Name => $Server)
		{
		$rpc = new JSONRPC($Server);
		$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
		echo "Contact to Server : ".$Server. " successful.\n";
		}
	
	
?>