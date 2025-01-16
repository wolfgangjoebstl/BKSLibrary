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

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * Stromverbrauch Variablen am Remote Server anlegen, kommen vom AMIS Modul
 *
 *  data.modules.Amis.BKS01.AMIS.Zählervariablen
 *
 * zuerst die Erreichbarkeit der konfigurierten Server prüfen, dann für die AMIS Register ein Pendant auf den remote Servern alegen
 * dazu wird auf den remoteservern folgende Struktur aufgebaut
 *
 *  Visualization.Webfront.Administrator.RemoteAccess.BKS01 oder BKS01-VIS.Stromverbrauch
 *
 * Besonders die Stromverbrauchsregister werden auf der Startpage visualisiert, manuell den Konnex herstellen
 *
 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");
    IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

    /* wird von Remote Access erzeugt : */
    IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");

    /******************************************************

                    INIT

    *************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(400); 
    $startexec=microtime(true);

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new ModuleManagerIPS7('RemoteAccess',$repository);

    $installedModules = $moduleManager->GetInstalledModules();
    $inst_modules="\nInstallierte Module:\n";
    foreach ($installedModules as $name=>$modules)
        {
        $inst_modules.=str_pad($name,30)." ".$modules."\n";
        }
    echo $inst_modules."\n\n";


	if (isset ($installedModules["DetectMovement"]))
		{
		IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        echo "  Modul DetectMovement ist installiert.\n";
		}
    else { echo "Modul DetectMovement ist NICHT installiert.\n";}
    
    /*	ROID_List() bestimmt die Server an die Daten gesendet werden sollen,  
    *  Function Ist in EvaluateVariables.inc in Modul RemoteAccess und wird von add_remoteServer aus RemoteAccess_GetConfiguration angelegt !
    *  Aufruf erfolgt in RemoteAccess. es wird auf den remote Servern die komplette Struktur aufgebaut und in EvaluateVariables.inc gespeichert.
    */
    $remServer=ROID_List();
    $status=RemoteAccessServerTable();
	
    if (isset ($installedModules["Amis"]))
        {
        echo "  Modul Amis ist installiert.\n";
        echo "\n";
        /* nur wenn AMIS installiert ist ausführen */
        echo "Amis Stromverbrauch Struktur auf Remote Servern aufbauen:\n";
        $stromID=Array();
        //print_r($status);
        //print_r($remServer);
        foreach ($remServer as $Name => $Server)
            {
            echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
            if ( $status[$Name]["Status"] == true )
                {		
                $rpc = new JSONRPC($Server["Adresse"]);
                $stromID[$Name]=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], "Stromverbrauch");
                }
            }
        echo "---------------------------------------------------------\n\n";
        echo "Struktur Server :\n";	
        print_r($stromID);
        
        /* EvaluateVariables.inc wird automatisch nach Aufruf von RemoteAccess erstellt , enthält Routine AmisStromverbrauchlist 
         */
        $stromverbrauch=AmisStromverbrauchList();
        echo "Darstellung Konfiguration/Zusammenfassung Stromverbrauch:\n";
        //print_r($stromverbrauch);
        foreach ($stromverbrauch as $Key) echo "    ".$Key["Name"]."\n";
            
        foreach ($stromverbrauch as $Key)
            {
            print_r($Key);
            $oid=(integer)$Key["OID"];        
            $variabletyp=@IPS_GetVariable($oid);
            if ($variabletyp)
                {
                //print_r($variabletyp);
                if ($variabletyp["VariableProfile"]!="")
                    {
                    echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                    }
                else
                    {
                    echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".(microtime(true)-$startexec)." Sekunden\n";
                    }
                $parameter="";
                foreach ($remServer as $Name => $Server)
                    {
                    if ( $status[$Name]["Status"] == true )
                        {				
                        $rpc = new JSONRPC($Server["Adresse"]);
                        $result=RPC_CreateVariableByName($rpc, $stromID[$Name], $Key["Name"], $Key["Typ"]);
                        $rpc->IPS_SetVariableCustomProfile($result,$Key["Profile"]);
                        $rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
                        if ($Key["Profile"]=="~Electricity")
                            {
                            $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,1);
                            }
                        else
                            {
                            $rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
                            }
                        $rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);
                        $parameter.=$Name.":".$result.";";
                        }
                    }				
                $messageHandler = new IPSMessageHandler();
                //$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
                if ($Key["Profile"]=="~Electricity") 
                    {
                    $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$Key["OID"].','.$parameter.',ENERGY','IPSModuleSensor_Remote');
                    echo "Register Stromverbrauch mit Parameter :\"".'IPSComponentSensor_Remote,'.$Key["OID"].','.$parameter.',ENERGY'."\" erzeugt.\n\n";
                    }
                else 
                    {
                    $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Remote,'.$Key["OID"].','.$parameter.',POWER','IPSModuleSensor_Remote');
                    echo "Register Stromverbrauch mit Parameter :\"".'IPSComponentSensor_Remote,'.$Key["OID"].','.$parameter.',POWER'."\" erzeugt.\n\n";
                    }
                if (isset ($installedModules["DetectMovement"]))
                    {
                    //echo "Detect Movement anlegen.\n";
                    $DetectSensorHandler = new DetectSensorHandler();
                    $DetectSensorHandler->RegisterEvent($oid,"Sensor",'','');
                    }                    
                }
            else echo "Fehler, ".$Key["Name"]." nicht vorhanden.\n"; 
            }
        }


?>