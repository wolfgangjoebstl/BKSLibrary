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


/*
 * Auf den Logging Server alle Objekte wie bei einer Generalabfrage setzen
 * passiert jeden Tag um 5:10
 *
 *
 *
 */

$startexec=microtime(true);
$executeObjects=true;
 
	IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

	$messageHandler = new IPSMessageHandler();

  	$eventConf = IPSMessageHandler_GetEventConfiguration();
 	$eventCust = IPSMessageHandler_GetEventConfigurationCust();
	$eventlist = $eventConf + $eventCust;
	echo "Overview of registered Events ".sizeof($eventConf)." + ".sizeof($eventCust)." = ".sizeof($eventlist)." Eintraege : \n";
    $maxCount=sizeof($eventlist);

if ($_IPS['SENDER']=="Execute")
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	echo "========================================================================================\n";	
	echo "Overview of registered Events, ".sizeof($eventlist)." Eintraege : \n";
    $i=0;
	foreach ($eventlist as $oid => $data)
		{
        echo str_pad($i,4)."Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40);
		if (IPS_ObjectExists($oid))
			{
			echo " | ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),55)."    | ".GetValue($oid)."\n";
			}
		else
			{
			echo "  ---> OID nicht verfügbar !\n";
			}
        $i++;
		}
	echo "===================================================================\n";	
	echo "Execute registered Events ".sizeof($eventlist)." Eintraege : \n";
    $i=1;
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
            echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
			echo "$i/$maxCount  Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40)." | ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
			if ($executeObjects) $messageHandler->HandleEvent($oid, GetValue($oid));
			}
		else
			{
			echo "*********  Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40)."      ----->    OID nicht verfügbar !\n";
			}
        $i++;  		
		}
    echo "\n\n================================================================================================\n\n";  
	}
	
if ( ($_IPS['SENDER']=="TimerEvent") || ($_IPS['SENDER']=="Execute") )
	{	

	/*********************************************************************
	 *
	 * Ausgabe aller Events die wirklich im System registriert sind
	 *
	 * dazu die Childrens (Events) des Eventhandlers auslesen
	 * Datenbank der verwendeten CFomponents anlegen
	 *
	 ***********************************************************************************/
	
	$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
	echo"\n";
	echo "Zusätzliche Checks bei der Eventbearbeitung:\n";
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
            echo "$i/$maxCount Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." | ";
            if (isset($eventlist[$eventID_str]))
                {
                $componentconfig=explode(",",$eventlist[$eventID_str][1]);
                //print_r($componentconfig);					
                $parent=@IPS_GetParent($eventID);
                if ($parent===false)
                    {		
                    echo "  ---> Objekt ".$eventID." existiert nicht für Event ".$childrenID.". Wird als ".$name." gelöscht. Unregister und Delete Event.\n";
                    $messageHandler->UnRegisterEvent($eventID);
                    IPS_DeleteEvent($childrenID);
                    }
                else
                    {
                    $component[$componentconfig[0]][$eventID]["EventName"]=IPS_GetName($childrenID);
                    $component[$componentconfig[0]][$eventID]["Config"]=$eventlist[$eventID_str][1];
                    $object=IPS_GetObject($parent);
                    if ( $object["ObjectType"] == 1)
                        {
                        echo $eventID." | Instanz  : ".str_pad(IPS_GetName($parent),36)." | ".$eventlist[$eventID_str][1]."\n";
                        $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($parent);
                        }
                    else
                        {
                        echo $eventID." | Register : ".str_pad(IPS_GetName($eventID),36)." | ".$eventlist[$eventID_str][1]."\n";
                        $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($eventID);
                        }		
                    //print_r($eventlist[$eventID_str]);
                    }
                }
            else
                {
                echo " ----> Objekt ".$eventID." ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36)." existiert nicht in IPSMessageHandler_GetEventConfiguration(), Event wird geloescht.\n";
                $messageHandler->UnRegisterEvent($eventID);
                IPS_DeleteEvent($childrenID);
                }
			}
		$i++;
		//IPS_SetPosition($childrenID,$eventID);
		}

	/*********************************************************************
	 * 
	 * Ausgabe aller Events die konfiguriert sind
	 *
	 * dazu config File auslesen
	 *
	 ***********************************************************************************/

 	//$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
	echo "===================================================================\n";
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
    $i=1;
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
			echo "\n";
            echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
			echo "$i/$maxCount  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
			if ($executeObjects) $messageHandler->HandleEvent($oid, GetValue($oid));
			}
		else
			{
            echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
			}
        $i++;
		}
	}


?>