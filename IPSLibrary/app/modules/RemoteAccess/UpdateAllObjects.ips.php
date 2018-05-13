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
 * Auf den Logging Server alle objekte wie bei einer generalabfrage setzen
 *
 */

$startexec=microtime(true);
 
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
 	$eventlist = IPSMessageHandler_GetEventConfiguration();
	
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
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
	
	echo "Overview of registered Events ".sizeof($eventlist)." Eintraege : \n";
	foreach ($eventlist as $oid => $data)
		{
		if (IPS_ObjectExists($oid))
			{
			echo "\n";
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."     ".GetValue($oid)."\n";
			$messageHandler->HandleEvent($oid, GetValue($oid));
			}
		else
			{
			echo "  Oid: ".$oid." | ".$data[0]." | ".$data[1]." | ".$data[2]."          OID nicht verfügbar !\n";
			}
		}
	}


?>