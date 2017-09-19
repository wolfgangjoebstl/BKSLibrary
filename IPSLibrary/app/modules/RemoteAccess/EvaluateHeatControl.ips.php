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
 *	hier für Homematic Temperatur und Feuchtigkeits Werte
 *
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

/******************************************************

				INIT

*************************************************************/

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

	echo "Update Konfiguration und register Events fuer HeatControl:\n\n";

	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
	//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
	
	$remServer=ROID_List();
	$status=RemoteAccessServerTable();	

	if (function_exists('HomematicList'))
	   {
		echo "Homematic HeatControl Actuatoren werden registriert.\n";
		$Homematic=HomematicList();		
		$keyword="VALVE_STATE";
		foreach ($Homematic as $Key)
			{
			if ( (isset($Key["COID"][$keyword])==true) )
				{
				/* alle Stellmotoren ausgeben */

				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$variabletyp=IPS_GetVariable($oid);
      		
				if ($variabletyp["VariableProfile"]!="")
			 		{
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}
				else
				   {
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
					}

				/* check, es sollten auch alle Quellvariablen gelogged werden */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				if (AC_GetLoggingStatus($archiveHandlerID,$oid)==false)
					{
					/* Wenn variable noch nicht gelogged automatisch logging einschalten */
					AC_SetLoggingStatus($archiveHandlerID,$oid,true);
					AC_SetAggregationType($archiveHandlerID,$oid,0);
					IPS_ApplyChanges($archiveHandlerID);
					echo "Variable ".$oid." Archiv logging aktiviert.\n";
					}

				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
					if ( $status[$Name]["Status"] == true )
						{				
						$rpc = new JSONRPC($Server["Adresse"]);
						$result=RPC_CreateVariableByName($rpc, (integer)$Server["HeatControl"], $Key["Name"], 0);
						$rpc->IPS_SetVariableCustomProfile($result,"~Intensity.100");
						$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
						$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
							}
						}
					}
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				echo "Message Handler hat Homematic HeatControl Actuator Event mit ".$oid." und ROIDs mit ".$parameter." angelegt.\n";
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentHeatControl_Homematic,'.$parameter,'IPSModuleHeatControl');
			
				}
			}
		}	
	// $remote=new RemoteAccess();
	   /*   list of Elements, Keyword
	// $remote->RPC_CreateVariableField(HomematicList(), "TEMPERATURE", "Temperatur", $startexec);  /* rpc, remote OID of category, OID Liste, OID Typ daraus, zuzuordnendes Profil, RPC ArchiveHandler */


?>