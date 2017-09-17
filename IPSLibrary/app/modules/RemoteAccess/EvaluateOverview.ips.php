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
ini_set('max_execution_time', 500);
$startexec=microtime(true);

IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

	$Homematic = HomematicList();

	$messageHandler = new IPSMessageHandlerExtended(); /* auch delete von Events moeglich */

 	//$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
 	$eventlist = IPSMessageHandler_GetEventConfiguration();
	
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."\n";
			}
		else
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
			}
		}
	
	$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
	echo"\n";
	echo "Zusätzliche Checks bei der Eventbearbeitung:\n";
	echo "ScriptID der Eventbearbeitung : ".$scriptId." \n";
	echo"\n";
	$children=IPS_GetChildrenIDs($scriptId);
	$i=0;
	//print_r($children);
	foreach ($children as $childrenID)
		{
		$name=IPS_GetName($childrenID);
		$eventID_str=substr($name,Strpos($name,"_")+1,10);
		$eventID=(integer)$eventID_str;
		if (substr($name,0,1)=="O")
		   {
			//if (isset($movement_config[$eventID_str]))
			//   {
			//   if (isset($eventlist[$eventID_str]))
			//      {
  			//   	echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." ".$eventID."  Movement: ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36).
			//		  			"  ".$eventlist[$eventID_str][1]."\n";
  			//   	//print_r($eventlist[$eventID_str]);
			//      }
			//   else
			//      {
			//   	echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." ".$eventID."  Movement: ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36)."\n";
			//   	}
			//	}
			//else
			   {
			   if (isset($eventlist[$eventID_str]))
					{
					$parent=@IPS_GetParent($eventID);
					if ($parent===false)
			   			{		
						echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)."Objekt ".$eventID." existiert nicht für Event ".$childrenID.". Wird als ".$name." gelöscht.\n";
						$messageHandler->UnRegisterEvent($eventID);
						IPS_DeleteEvent($childrenID);
						}
					else
						{	
  						echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." ".$eventID."            ".str_pad(IPS_GetName($parent),36)."  ".$eventlist[$eventID_str][1]."\n";
  						//print_r($eventlist[$eventID_str]);
						}
			      	}
			   else
			      	{
					echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." ".$eventID."            ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36)." existiert nicht in IPSMessageHandler_GetEventConfiguration().\n";
					}
				}
			}
		$i++;
		//IPS_SetPosition($childrenID,$eventID);
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