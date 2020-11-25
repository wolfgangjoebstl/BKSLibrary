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
    * 	hier für den Regensensor und die Temperaturwerte der FHT Heizungssteuerungen/zentralen
    *    Basis ist das File EvaluateVariables_ROID.inc.php
    *
    * RemoteAccess				erzeugt die include Files für EvaluateVariables und erstellt die benötigten Profile
    *
    * EvaluateAndere				Regensensor, FHT Temperatur (TemeratureVar)
    * EvaluateButton				Taster Homematic und FS20EX
    * EvaluateContact			Kontakte Homematic
    * EvaluateHeatControl		Homematic und FHT Thermostate den Stellwert und den Sollwert
    * EvaluateHomematic			Homematic Temperatur und Feuchtigkeitswerte
    * EvaluateMotion				Homematic und FS20 Bewegungsmelder, und die Bewegungsmelder der Cams
    * EvaluateStronverbrauch 	die von AMIS angelegten Register
    * EvaluateSwitch				Homematic, HomematicIP udn FS20 Schalter 
    * EvaluateVariables			Guthaben, SysInfo und RouterDaten Register
    *
    *
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

    echo "Update Konfiguration und register CO2, BAROPRESSURE, RAIN Events\n";

    IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
    IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
    IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");

    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt

    IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
    $remServer=ROID_List();

    $status=RemoteAccessServerTable();

    $componentHandling=new ComponentHandling();
    $commentField="zuletzt Konfiguriert von RemoteAccess EvaluateAndere um ".date("h:i am d.m.Y ").".";   
        
    if (getfromDataBase())
        {
        echo "\n\n==CLIMATE based on MySQL ===============================================================================\n";
        $componentHandling->installComponentFull("MySQL",["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "CO2"],"","","",false);                   // true ist Debug
        $componentHandling->installComponentFull("MySQL",["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "BAROPRESSURE"],"","","",false);          // true ist Debug
        $componentHandling->installComponentFull("MySQL",["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "RAIN"],"","","",false);                  // true ist Debug
        }
    elseif ( (function_exists('deviceList')) )
        {
        echo "\n\n==Climate von verschiedenen Geräten auf Basis devicelist() werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "CO2"],'IPSComponentSensor_Remote','IPSModuleSensor_Remote,',$commentField, false);				/* true ist Debug,  */
        echo "==================\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "BAROPRESSURE"],'IPSComponentSensor_Remote','IPSModuleSensor_Remote,',$commentField, false);		/* true ist Debug,  */
        echo "==================\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "RAIN_COUNTER"],'IPSComponentSensor_Counter','IPSModuleSensor_Counter,',$commentField, false);				/* true ist Debug,  */
        }
    elseif (function_exists('HomematicList'))
		{
        echo "\n\n=================================================================================\n";
        $Homematic = HomematicList();
        //print_r($Homematic);
        foreach ($Homematic as $Key)
            {
            /* alle Regensensoren ausgeben */
            if (isset($Key["COID"]["RAIN_COUNTER"])==true)
                {
                $oid=(integer)$Key["COID"]["RAIN_COUNTER"]["OID"];
                echo "Regensensor gefunden  $oid ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))).":\n";
                echo str_pad($oid,8).str_pad($Key["Name"],30)." = ".GetValueIfFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
                $parameter="";
                foreach ($remServer as $Name => $Server)
                    {
                    echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
                    if ( $status[$Name]["Status"] == true )
                        {					
                        $rpc = new JSONRPC($Server["Adresse"]);
                        $result=RPC_CreateVariableByName($rpc, (integer)$Server["Andere"], $Key["Name"], 2);
                        $rpc->IPS_SetVariableCustomProfile($result,"~Rainfall");
                        $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                        $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);       /* 0 Standard 1 ist Zähler */
                        $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
                        $parameter.=$Name.":".$result.";";
                        }
                    }
                $messageHandler = new IPSMessageHandler();
                $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
                $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Counter,'.$parameter,'IPSModuleSensor_Counter');
                echo "Regenfall Register mit Parameter :".$parameter." erzeugt.\n";
                //print_r($Key);
                }
            }
        }

?>