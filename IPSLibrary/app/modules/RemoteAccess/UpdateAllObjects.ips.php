<?php

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
 * Eventliste wird vom messageHandler extrahiert
 * Zusatzfunktionen wenn als Script und nicht vom Timer gestartet
 *      Darstellung der Events und Überprüfung ob es die referenzierte OID noch gibt und Ausgabe Pfad und aktueller Wert
 *      Wenn es die OID nicht mehr gibt, aus der Eventliste des MessageHanlder löschen
 * Regelmaessige Funktion, einmal am Tag um 5:10
 *      Jetzt andersrum, die ScriptId des messageHandler raussuchen, 
 *      die Childrens des Script ermitteln, also die Events
 *
 */

    $startexec=microtime(true);
    $executeObjects=true;              // false   nicht alle Register updaten, produziert weniger Fehler :-) aktuell auch zu viel Ausgabetext
    $debug=true;

    // active deletion of objects from not needed module, if category is in object path it will be removed
    $dodelete=false;
    

	IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
    IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

    $ipsOps = new ipsOps();
    $remoteAccess = new RemoteAccess();    
    
	//$messageHandler = new IPSMessageHandler();
	$messageHandler = new IPSMessageHandlerExtended();          // kann auch register loeschen

  	$eventConf = IPSMessageHandler_GetEventConfiguration();
 	$eventCust = IPSMessageHandler_GetEventConfigurationCust();
	$eventlist = $eventConf + $eventCust;
	echo "Overview of registered Events ".sizeof($eventConf)." + ".sizeof($eventCust)." = ".sizeof($eventlist)." Eintraege : \n";
    $maxCount=sizeof($eventlist);
    $delete=array();                                                              // delete some objects, ie because they are not needed for Guthaben

    $eventList = new DetectEventListHandler($debug);
    $eventList->setEventListFromConfigFile(); 
    $eventListData = $eventList->getEventlist();

if ( ($_IPS['SENDER']=="Execute") )
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	echo "========================================================================================\n";	

    $xconfig = new XConfigurator();           
    $result=$xconfig->createXConfiguration();

    echo "EventList loaded from DetectEventListHandler (MessageHandler):\n";           // Create, copied to DiagnoseCenter
    //$result = $remoteAccess->processXConfig(IPS_GetName(0),$debug);
    $eventList->extendRemoteAccess($xconfig,$debug);                                  // eventlist erweitern
    $eventListData = $eventList->getEventlist();

	echo "Overview of registered Events, ".sizeof($eventListData)." Eintraege : \n";
    $i=0; 


    $excludeModules=["Guthabensteuerung","DetectMovement"];
	foreach ($eventListData as $oid => $data)
		{
        if (isset($data[0])===false) 
            {
            //echo str_pad($i,3," ",STR_PAD_LEFT)." Oid: ".$oid." | ".json_encode($data)."\n";
            $typeUpdateChange=explode("_",$data["Name"])[0];
            $data[0]=$typeUpdateChange;
            $data[1]=$data["Component"];
            $data[2]=$data["Module"];
            }
        echo str_pad($i,3," ",STR_PAD_LEFT)." Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],90)." | ".str_pad($data[2],40);
		if (IPS_ObjectExists($oid))
			{
            if ($oid != $data["EventID"]) echo "Wrong configuration ";
            //echo str_pad($i,3," ",STR_PAD_LEFT)." | ".$data["EventID"]." | ".str_pad($typeUpdateChange,8)." | ".str_pad($data["Component"],80)." | ".str_pad($data["Module"],30);
            if (isset($excludeModules))
                {
                foreach ($excludeModules as $module)
                    {
                    if ($ipsOps->isMemberOfCategoryName($oid,$module)) 
                        {
                        echo " | $module";
                        $delete[$oid]=$data;
                        //$messageHandler->UnRegisterEvent($eventID);
                        //IPS_DeleteEvent($childrenID);
                        }
                    }
                }
            $data[1]=$data["Component"];
            $status=$remoteAccess->checkServerOIDData($result,$data);
            echo "\n";
            if ($status===false)                 
                {
                echo "    Warning, RemotAccess failed, wrong Data, Object will be deleted from messagehandler. Install again \n";
                $delete[$oid]=$data;
                }
			}
		else
			{
			echo "  ---> OID nicht verfügbar !\n";
            $delete[$oid]=$data;
			}
        $i++;
		}
	echo "===================================================================\n";	
	echo "Delete registered Events ".sizeof($delete)." Eintraege : \n";
    $i=1;
    //print_r($delete);
	foreach ($delete as $oid => $data)
		{
        //print_r($data);
        if (isset($data[2])) echo "$i Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40)." | ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
        else if (isset($data[1])) echo "$i Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
        else echo "$i Oid: ".$oid." | ".$data[0]." | ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
        $i++;  		
		}
    // Delete erfolgt in der nächsten Routine, Überwachung dass nicht zuviele Events gelöscht werden
    if (sizeof($delete)>3) 
        {
        echo "Delete registered Events, we found more than 3 Events. Before deleting check again if desccision ´to delete is valid. We Found ".sizeof($delete)." entries subject to be deleted.\n";
        $delete=array();
        }
    echo "\n\n================================================================================================\n";  
	}
	
if ( ($_IPS['SENDER']=="TimerEvent") ||  ($_IPS['SENDER']=="Execute") )
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
	if ($debug)
        {
        echo"Execute Timer Procedure once a day:\n";
        echo "Zusätzliche Checks bei der Eventbearbeitung, ScriptID der Eventbearbeitung : ".$scriptId." \n";
        echo"\n";
        }
	$children=IPS_GetChildrenIDs($scriptId);
	$components=array();
	$i=0;
	//print_r($children);
	foreach ($children as $childrenID)          // sind bereits children, als gehen wir davon aus das es sie gibt
		{
        $type = IPS_GetObject($childrenID)["ObjectType"];
        if ($type==4)                       // nur Events berücksichtigen
            {            
            $name=IPS_GetName($childrenID);
            $eventID_str=substr($name,Strpos($name,"_")+1,10);
            $eventID=(integer)$eventID_str;
            if (substr($name,0,1)=="O")
                {
                if ($debug) echo "$i/$maxCount Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)." | ";
                if (isset($eventlist[$eventID_str]))
                    {
                    $componentconfig=explode(",",$eventlist[$eventID_str][1]);
                    //print_r($componentconfig);					
                    $parent=@IPS_GetParent($eventID);
                    if ($parent===false)
                        {		
                        if ($debug) echo "  ---> Objekt ".$eventID." existiert nicht für Event ".$childrenID.". Wird als ".$name." gelöscht. Unregister und Delete Event.\n";
                        if ($dodelete)
                            {
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
                            if ($debug) echo $eventID." | Instanz  : ".str_pad(IPS_GetName($parent),36)." | ".str_pad($eventlist[$eventID_str][1],55);
                            $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($parent);
                            }
                        else
                            {
                            if ($debug) echo $eventID." | Register : ".str_pad(IPS_GetName($eventID),36)." | ".str_pad($eventlist[$eventID_str][1],55);
                            $component[$componentconfig[0]][$eventID]["VarName"]=IPS_GetName($eventID);
                            }	
                        if (isset($delete[$eventID])) 
                            { 
                            if ($debug) echo "  ---> delete \n"; 
                            if ($dodelete)
                                {
                                $messageHandler->UnRegisterEvent($eventID);
                                IPS_DeleteEvent($childrenID);
                                }
                            }
                        else { if ($debug) echo "\n";	 	 }                        	
                        //print_r($eventlist[$eventID_str]);
                        }
                    }
                else
                    {
                    if ($debug) echo " ----> Objekt ".$eventID." ".str_pad(IPS_GetName(IPS_GetParent($eventID)),36)." existiert nicht in IPSMessageHandler_GetEventConfiguration(), Event wird geloescht.\n";
                    if ($dodelete)
                        {
                        $messageHandler->UnRegisterEvent($eventID);
                        IPS_DeleteEvent($childrenID);
                        }
                    }
                }
            }
		$i++;
		//IPS_SetPosition($childrenID,$eventID);
		}

	/*********************************************************************
	 * 
	 * Ausgabe aller Events die konfiguriert sind
	 * wenn $executeObjects am Anfang gesetzt, dann alle Event ausprobieren und auf den remote Servern updaten
     * messageHandler Handle Event macht construct vom Component und ruft das HandleEvent des IPSComponents auf (data[1]) mit dem zusätzlichen Parameter Modul (data[2])
	 * Ausgabe echo Parameter wie im Config angeführt, Verweis auf den entsprechenden IPSComponent auf zB IPSComponentSensor_Motion
     * IPSComponentSwitch_RHomematic
     *      ruft vom module SyncState auf, synchronisieren des Status von Gruppen
	 *
	 ***********************************************************************************/

    if ($debug)
        {
        echo "===================================================================\n";
        echo "Overview of registered Events ".sizeof($eventlist)." / ".sizeof($eventListData)." Eintraege : \n";
        if ($executeObjects)  echo "   Flag executeObjects activated, try function UpdateEvent , needs time \n";
        }
    $i=1;
    //$target=["BUTTON","TEMPERATURE"];
    $target=[];
    foreach ($eventListData as $oid => $data)
        {
        if (isset($data[0])===false) 
            {
            //echo str_pad($i,3," ",STR_PAD_LEFT)." Oid: ".$oid." | ".json_encode($data)."\n";
            $typeUpdateChange=explode("_",$data["Name"])[0];
            $data[0]=$typeUpdateChange;
            $data[1]=$data["Component"];
            $data[2]=$data["Module"];
            }            
        if (IPS_ObjectExists($oid))
            {
            if ($debug)
                {
                echo "\n";
                echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
                //echo "$i/$maxCount  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
                echo "$i/$maxCount  Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40)." | ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
                }
            //print_r($data);
            $component=explode(",",$data["Component"]);
            if ( (isset($component[3])) && (in_array($component[3],$target)) )
                {
                //echo "----------------------- ".$component[3].json_encode($component)."\n";
                if ($executeObjects)  $messageHandler->UpdateEvent($oid, GetValue($oid),false);
                }
            }
        else
            {
            if ($debug)
                {
                echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
                echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
                }
            }
        $i++;
        }
    }       // Ende if Timer


?>