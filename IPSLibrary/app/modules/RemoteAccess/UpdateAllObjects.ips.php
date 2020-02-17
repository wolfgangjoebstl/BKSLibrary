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
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

if ($_IPS['SENDER']=="Execute")
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	$messageHandler = new IPSMessageHandler();
 	$eventlist = IPSMessageHandler_GetEventConfiguration();
    $i=0;
	echo "===================================================================\n";	
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
        echo str_pad($i,4)."Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],30);
		if (IPS_ObjectExists($oid))
			{
			echo " | ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),55)."     ".GetValue($oid)."\n";
			}
		else
			{
			echo "   OID nicht verfügbar !\n";
			}
        $i++;
		}
	echo "===================================================================\n";	
	echo "Execute registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
            echo "----------------------------------------------------------------------------------------- ".exectime($startexec)." Sekunden\n";
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
			if ($executeObjects) $messageHandler->HandleEvent($oid, GetValue($oid));
			}
		else
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
			}
		}		
	}
	
if ($_IPS['SENDER']=="TimerEvent")
	{	
	$messageHandler = new IPSMessageHandler();

	/*********************************************************************
	 * 
	 * Ausgabe aller Events die konfiguriert sind
	 *
	 * dazu config File auslesen
	 *
	 ***********************************************************************************/

 	//$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
 	$eventlist = IPSMessageHandler_GetEventConfiguration();
	echo "===================================================================\n";
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
			echo "\n";
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
			if ($executeObjects) $messageHandler->HandleEvent($oid, GetValue($oid));
			}
		else
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
			}
		}
	}


?>