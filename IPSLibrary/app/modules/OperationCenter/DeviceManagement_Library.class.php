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
	 
/*********************************************************************************************/
/*********************************************************************************************/
/*                                                                                           */
/*                              Functions, Klassendefinitionen                               */
/*                                                                                           */
/*********************************************************************************************/
/*********************************************************************************************
 *
 *
 *
 *
 * diese Klassen werden hier behandelt, waren vorher in der OperationCenter_Library
 *
 *
 *      DeviceManagement  
 *          DeviceManagement_FS20       extends DeviceManagement
 *          DeviceManagement_Homematic  extends DeviceManagement
 *          DeviceManagement_Hue        extends DeviceManagement
 *          DeviceManagement_HueV2      extends DeviceManagement_Hue
 *
 */


/********************************************************************************************************
 *
 * DeviceManagement
 * ================ 
 *
 *      __construct          
 *          HMIs aus dem HomeMatic Inventory Handler
 *          HomematicAddressesList 
 *          HomematicSerialNumberList
 *
 *      HardwareStatus                      Statusinfo von Hardware, auslesen der Sensoren und Alarm wenn laenger keine Aktion.
 *      checkVariableChanged                einheitliche Überprüfung ob schon länger keine Änderung mehr war
 *      writeCheckStatus
 *      get_ActionButton                     Standardfunktion für die Activities aus dem Webfront, ale Action Buttons in einer function
 *
 * Verwenden gemeinsames Array $HomematicSerialNumberList:
 *      getHomematicSerialNumberList		erfasst alle Homematic Geräte anhand der Seriennumme und erstellt eine gemeinsame liste die mit anderen Funktionen erweiterbar ist
 *      getHomematicAddressList
 *
 *      updateHomematicAddressList
 *      updateHmiReport
 *
 *      addHomematicSerialList_Typ		die Homematic Liste wird um weitere Informationen erweitert:  Typ
 *      addHomematicSerialList_RSSI
 *      addHomematicSerialList_DetectMovement
 *
 *      writeHomematicSerialNumberList	Ausgabe der Liste
 *      tableHomematicSerialNumberList
 *
 *                      "Type"    = $DeviceManager->getHomematicType($instanz);           wird für Homematic IPS Light benötigt 
 *                      "Device"  = $DeviceManager->getHomematicDeviceType($instanz);     wird für CustomComponents verwendet, gibt als echo auch den Typ aus 
 *                      "HMDevice"= $DeviceManager->getHomematicHMDevice($instanz);
 *
 *      getHomematicDeviceList
 *      getHomematicType
 *      HomematicDeviceType             wird von getHomematicDeviceType aufgerufen
 *      getHomematicDeviceType          das ist die function, die von der HardwareLibrary aufgerufen wird
 *      getHomematicHMDevice            hier neue Geräte anlegen, damit sie erkannt werden, gibt für eine Homematic Instanz/Kanal eines Gerätes den Device Typ aus HM Inventory aus
 *
 * DeviceManagement_FS20
 *      getFS20Type
 *      getFS20DeviceType
 *
 * DeviceManagement_Homematic
 *      HomematicFehlermeldungen
 *      updateHomematicErrorLog
 *
 *
 **************************************************************************************************************************/

class DeviceManagement
	{

	var $CategoryIdData       	= 0;
	var $archiveHandlerID     	= 0;

    private $debug                  = false;            /* wenig Debug Info ausgeben */
    private $dosOps;                        /* verwendete andere Klassen */
    private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert

	var $log_OperationCenter  	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();			/* Setup von Operationcenter, Verzeichnisse, Konfigurationen */

	var $installedModules     	= array();
	
	var $HomematicSerialNumberList	= array();
	var $HomematicAddressesList	= array();
	
	var $HMIs = array();							/* Zusammenfassung aller Homatic Inventory module */
    var $HMI_ReportStatusID   = 0;                  /* der HMI_CreateReport wird regelmaessig aufgerufen, diesen auch überwachen. */
	
	/**
	 * DeviceManagement
	 *
	 * Initialisierung des DeviceManagement Class Objektes
     *
     * wichtige Variablen die erfast werden (????)
     *      $this->HMIs
     *      $this->HomematicAddressesList
     *
	 *
	 */
	public function __construct($debug=false)
		{
        $this->debug=$debug;
        $this->dosOps = new dosOps();     // create classes used in this class
        $this->systemDir     = $this->dosOps->getWorkDirectory();

		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
		$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
		$this->installedModules = $moduleManager->GetInstalledModules();

		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
		$this->log_OperationCenter=new Logging($this->systemDir."Log_OperationCenter.csv",$input);

		$categoryId_DeviceManagement    = IPS_GetObjectIDByName('DeviceManagement',$this->CategoryIdData);
        
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		
        $this->oc_Configuration = OperationCenter_Configuration();
		$this->oc_Setup = OperationCenter_SetUp();
		
        if ($debug) 
            { 
            echo "Aufbau des OperationCenter Setups. Fehlende Werte in der Konfiguration ersetzen.\n";
            print_r($this->oc_Setup);
            echo "DeviceManagement Modul vollständig initialisiert.\n";
            }
		}
		
    /****************************************************************************************************************/


	/*
	 * DeviceManagement::HardwareStatus Statusinfo von Hardware, auslesen der Sensoren und Alarm wenn laenger keine Aktion.
	 *
	 * Parameter:
	 * -----------
	 * Default: Ausgabe als Textfile für zB send_status, gibt alle Geräte der Reihe nach als text aus. 
	 * Wenn Parameter true dann Ausgabe als Array mit den Fehlermeldungen wenn Geräte über längeren Zeitraum nicht erreichbar sind
	 *
	 */
	function HardwareStatus($text=false,$debug=false)
		{
        if ($debug) echo "HardwareStatus($text,..) aufgerufen:\n";
        $resultTable=array();
		$resultarray=array(); $index=0;
		$resulttext="";
        $oldstyle=false;                        // alte Auswertungsart, depriciated
		//print_r($this->installedModules);
		if (isset($this->installedModules["EvaluateHardware"])==true)
			{
			/* es gibt nur mehr eine Instanz für die Evaluierung der Hardware und die ist im Modul EvaluateHardware */
			
			//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
            IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            $componentHandling=new ComponentHandling();
            if ($debug) echo "   Geräte mit getComponent suchen, geht jetzt mit HarwdareList und DeviceList.\n";
            //$result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "HUMIDITY"],"Install");
            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            if ($debug>1)
                {
                echo "   Insgesamt $count Register für die Temperature Component Installation gefunden.\n";
                foreach ($result as $entry) echo "   ".$entry["OID"]."    ".IPS_GetName($entry["OID"])."   \n";             $resulttext.="Alle Temperaturwerte ausgeben ($count):\n";            
                }
            foreach ($result as $Key) 
                {
                //echo "   ".json_encode($Key)."   \n";
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                //echo "   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                $resulttext.= "\n"; 
                $result = $this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                /*if ((time()-IPS_GetVariable($Key["COID"])["VariableChanged"])>(60*60*24*2)) 
                    {  
                    $result[$index]["Name"]=$Key["Name"];
                    $result[$index]["OID"]=$Key["COID"];
                    $index++;					
                    $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                    $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');             
                    }*/
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Temperature",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                //print_R($Key);
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_HUMIDITY","REGISTER" => "HUMIDITY"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Feuchtigkeitswerte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueIfFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Humidity",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }
            if (function_exists("deviceList")) 
                {
                $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "BRIGHTNESS"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
                $result += $componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "BRIGHTNESS"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
                }
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Helligkeitswerte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueIfFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Brightness",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Bewegungsmelder ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Motion",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Kontakte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";        
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Contact",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_POWER","REGISTER" => "ENERGY"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Energieregister ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben, schreibt Nachrichten in Operation Log                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Energy",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_THERMOSTAT","REGISTER" => "SET_TEMPERATURE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Sollwerte Thermostate ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben, schreibt Nachrichten in Operation Log                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Solltemperatur",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if (function_exists("deviceList")) $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_ACTUATOR","REGISTER" => "VALVE_STATE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            else $result = array();
            $count=(sizeof($result));				
            $resulttext.="Alle Stellwerte Aktuatoren ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $result=$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben, schreibt Nachrichten in Operation Log                
                $resultTable[$Key["COID"]]=array(
                        "Type"          => "Ventilwert",
                        "Name"          => $Key["Name"],
                        "Value"         => GetValueFormatted($Key["COID"]),
                        "LastChanged"   => IPS_GetVariable($Key["COID"])["VariableChanged"],
                        "Reach"       => $result,
                        );
                }

            if ($oldstyle)
                {
                IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
                $Homematic = HomematicList();
                $FS20= FS20List();
                
                $resulttext.="Alle Temperaturwerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    $homematicerror=false;
                    /* alle Homematic Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TEMPERATURE"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).") ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2)) $homematicerror=true;
                        }
                    elseif ( (isset($Key["COID"]["MOTION"])==true) )	
                        {
                        }				
                    elseif (isset($Key["COID"]["HUMIDITY"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["RSSI_DEVICE"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["STATE"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["CURRENT"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["CURRENT_ILLUMINATION"])==true)
                        {
                        }	
                    elseif (isset($Key["COID"]["PRESS_LONG"])==true)
                        {
                        }	
                    elseif (isset($Key["COID"]["DIRECTION"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["ACTUAL_TEMPERATURE"])==true)
                        {
                        $oid=(integer)$Key["COID"]["ACTUAL_TEMPERATURE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2)) $homematicerror=true;
                        }
                    elseif (sizeof($Key["COID"])==0) 
                        {
                        }																								
                    else
                        {
                        $resulttext.="**********Homematic Temperatur Geraet unbekannt : ".str_pad($Key["Name"],30)."\n";					
                        print_r($Key);
                        }
                    if ($homematicerror == true)		
                        {
                        $result[$index]["Name"]=$Key["Name"];
                        $result[$index]["OID"]=$oid;
                        $index++;					
                        $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        }
                    }

                $FHT = FHTList();
                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TemeratureVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;	
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $resulttext.="Alle Feuchtigkeitswerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Feuchtigkeitswerte ausgeben */
                    if (isset($Key["COID"]["HUMIDITY"])==true)
                        {
                        $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $resulttext.="Alle Bewegungsmelder ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        //print_r($Key);
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";
                                                
                        /* es kann laenger sein dass keine Bewegung, aber Helligkeitsaenderungen sind immer */	
                        if (isset($Key["COID"]["BRIGHTNESS"]["OID"])==true)
                            {
                            $oid=(integer)$Key["COID"]["BRIGHTNESS"]["OID"];
                            }
                        elseif (isset($Key["COID"]["ILLUMINATION"]["OID"])==true)
                            {
                            $oid=(integer)$Key["COID"]["ILLUMINATION"]["OID"];
                            }					 
                        else	
                            {
                            echo "Bewegungsmelder ohne Helligkeitssensor gefunden:\n";
                            print_r($Key);						
                            }		
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }	
                        }
                    }
                    
                $resulttext.="Alle Kontakte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* alle Homematic Kontakte ausgeben */
                    if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["LOWBAT"])==true) )
                        {
                        //print_r($Key);
                        $oid=(integer)$Key["COID"]["STATE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }
                    
                $resulttext.="Alle Energiewerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Energiesensoren ausgeben */
                    if ( (isset($Key["COID"]["VOLTAGE"])==true) )
                        {
                        /* alle Energiesensoren */

                        $oid=(integer)$Key["COID"]["ENERGY_COUNTER"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $pad=50;
                $resulttext.="Aktuelle Heizungswerte ausgeben:\n";
                $varname="SET_TEMPERATURE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);

                        $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $varname="SET_POINT_TEMPERATURE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);
                        $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TargetTempVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TargetTempVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        }
                    }			
                    
                $resulttext.="Aktuelle Heizungs-Aktuatorenwerte ausgeben:\n";
                $varname="VALVE_STATE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);
                        if ( (isset($Key["COID"]["LEVEL"])==true) ) $oid=(integer)$Key["COID"]["LEVEL"]["OID"];
                        else $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["PositionVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["PositionVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        }
                    if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                        {
                        $result[$index]["Name"]=$Key["Name"];
                        $result[$index]["OID"]=$oid;
                        $index++;
                        $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        }					
                    }
                }               // oldStyle HardwareStatus
			}
        //print_r($resulttext);
		if ($text==="array") return($resultTable);                  // normaler Vergleich geht nicht da $text auch boolean sein kann und dann der vergleich als Boolean geführt wird
		if ($text==true) return($resulttext); 
		else return($resultarray);
		}

    /* DeviceManagement::checkVariableChanged
     * einheitliche Überprüfung ob schon länger keine Änderung mehr war 
     */
    private function checkVariableChanged(&$result,&$index,$Key)
        {
        if ((time()-IPS_GetVariable($Key["COID"])["VariableUpdated"])>(60*60*24*2))         // vorher VariableChanged, zu unsicher da nicht immer Änderungen erfolgen
            {           
            $result[$index]["Name"]=$Key["Name"];
            $result[$index]["OID"]=$Key["COID"];
            $index++;					
            $this->log_OperationCenter->LogMessage('HardwareStatus Gerät '.$Key["Name"].' meldet sich seit 2 Tagen nicht, check '.$Key["COID"]);
            $this->log_OperationCenter->LogNachrichten('HardwareStatus Gerät '.$Key["Name"].' meldet sich seit 2 Tagen nicht, check '.$Key["COID"]);
            return (false);
            }
        return (true);
        }

    /* DeviceManagement::writeCheckStatus
     * da die Überprüfung private ist hier die Ausgabe public machen 
     * Input ist ein array mit [COID] als Parameter
     */
    public function writeCheckStatus($result)
        {
        $resulttext="";
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueIfFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                //$this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                }
        return ($resulttext);
        }
	
	/*
	 * DeviceManagement::showHardwareStatus Statusinfo von Hardware als Table ausgeben
	 *
	 */

	function showHardwareStatus($hwStatus,$filter=false,$config=false,$debug=false)
		{
        if ($debug) echo "showHardwareStatus aufgerufen.\n";
        if ( ($filter !== false) && (is_array($filter)===false) )
            {
            $filter = array( "Type" => $filter,);
            }
        if ( ($config === false) || (is_array($config)===false) )
            {
            $config = array( "Header" => false,);
            }

        $html = '';  
        $html.='<style>';             
        $html.='.statyHm table,td {align:center;border:1px solid white;border-collapse:collapse; word-break:break-all;}';
        //$html.='.statyHm table    {table-layout: fixed; width: 100%; }';
        //$html.='.statyHm td:nth-child(1) { width: 60%; }';                        // fixe breiten, sehr hilfreich
        //$html.='.statyHm td:nth-child(2) { width: 15%; }';
        //$html.='.statyHm td:nth-child(3) { width: 15%; }';
        //$html.='.statyHm td:nth-child(4) { width: 10%; }';
        $html.='</style>';        
        $html.='<table class="statyHm">'; 
        if ($config["Header"]) $html .= '<th>'.$config["Header"].'</th>';
        foreach ($hwStatus as $coid => $entry)
            {
            if ( ($filter===false) || ($this->filterOnArray($entry,$filter)) )
                {
                if ($debug) echo "$coid ".json_encode($entry)."  \n";
                $html .= '<tr>';
                $html .= '<td>'.$entry["Name"].'</td>';
                $html .= '<td>'.$entry["Value"].'</td>';
                $html .= '<td>'.date("d.m.Y H:i",$entry["LastChanged"]).'</td>';
                $html .= '<td>'.$entry["Reach"].'</td>';
                $html .= '</tr>';    
                }
            }
        $html .= '</table>';
        return ($html);
        }

	/*
	 * DeviceManagement::countHardwareStatus vorab rausfinden ob es Werte gibt bevor eine Tabelle erzeugt wird
	 *
	 */
	function countHardwareStatus($hwStatus,$filter=false,$debug=false)
		{
        if ($debug) echo "countHardwareStatus aufgerufen.\n";
        if ( ($filter !== false) && (is_array($filter)===false) )
            {
            $filter = array( "Type" => $filter,);
            }
        $count=0;
        foreach ($hwStatus as $coid => $entry)
            {
            if ( ($filter===false) || ($this->filterOnArray($entry,$filter)) ) $count++;
            }
        return ($count);
        }

	/*
	 * filter auf verschiedene Spalten der Tabelle, es geht AND oder OR Verknüpfung
	 *
	 */
    private function filterOnArray($entry,$filter)    
        {
        $result=false;
        if (isset($filter["Operation"])) $operation=strtoupper($filter["Operation"]);
        else $operation="AND";
        if ($operation=="AND") $resultSub = true;    
        else $resultSub=false;
        foreach ($filter as $key => $value)
            {
            if ((isset($entry[$key])) && ($value !== null))
                {
                //echo "Filter $key ".$entry[$key]."===$value  "; 
                if ($entry[$key]===$value) 
                    {
                    //echo "$operation OK  ";
                    $result=true;
                    if ( ($operation=="AND") && $resultSub) $resultSub = true;    
                    else $resultSub = false;
                    //echo $resultSub;
                    }
                else $resultSub = false;
                }
            }
        //echo "\n";
        return ($result && $resultSub);
        }

	/* DeviceManagement::get_ActionButton()
	 * Zusammenfassung aller ActionButtons in dieser Klasse
	 * funktioniert auch ohne DeviceManagment_Homematic, holt sich die Inventories automatisch
	 */
	 
	function get_ActionButton($debug=false)
		{
        if (sizeof($this->HMIs)==0) 
            {
            //echo "No Homematic Instances, doublecheck and call class DeviceManagement_Homematic.\n";
            $modulhandling = new ModuleHandling();
            $this->HMIs=$modulhandling->getInstances('HM Inventory Report Creator');
            }
        $countHMI = sizeof($this->HMIs);            
		if ($debug) echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs.\n";
	    $ActionButton=array();
		if ($countHMI>0)
	        {
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($this->HMIs as $HMI)
	            {
				$CategoryIdHomematicCCU=IPS_GetCategoryIdByName("HomematicInventory_".$HMI,$CategoryIdHomematicInventory);
	            $SortInventoryId = IPS_GetVariableIdByName("Sortieren",$CategoryIdHomematicCCU);
	   			$HomematicInventoryId = IPS_GetVariableIdByName(IPS_GetName($HMI),$CategoryIdHomematicCCU);
	
	            $ActionButton[$SortInventoryId]["DeviceManagement"]["HMI"]=$HMI;
	            $ActionButton[$SortInventoryId]["DeviceManagement"]["HtmlBox"]=$HomematicInventoryId;
	            }            
	        }
		return($ActionButton);
		}

	/********************************************************************
	 * DeviceManagement::getHomematicSerialNumberList
	 * erfasst alle Homematic Geräte anhand der Seriennummer und erstellt eine gemeinsame liste 
     *
     * wird bei construct bereits gestartet als gemeinsames Datenobjekt
	 *
	 *****************************************************************************/

	function getHomematicSerialNumberList($debug=false)
		{
		$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
		//Auflisten
		$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
		if ($debug)
			{
			echo "\nHomematic Instanzen: ".sizeof($alleInstanzen)." \n";
			echo "Werte geordnet und angeführt nach Instanzen, es erfolgt keine Zusammenfassung auf Geräte/Seriennummern.\n";
			echo "Children der Instanzen werden nur angeführt wenn die Zeit ungleich 0 ist.\n\n";
			}
		$serienNummer=array();
		foreach ($alleInstanzen as $instanz)
			{
			$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
			switch (IPS_GetProperty($instanz,'Protocol'))
				{
				case 0:
					$protocol="Funk";
					break;
				case 1:
				    $protocol="Wired";
    				break;
	    		case 2:
		    		$protocol="IP";
			    	break;
                default:
	    			$protocol="Unknown";
				break;
				}
			$HM_Adresse=IPS_GetProperty($instanz,'Address');
			$result=explode(":",$HM_Adresse);
			$sizeResult=sizeof($result);
			//print_r($result);
			if ($debug) echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad($protocol,6)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
			if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
				}
			else
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
				$serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
				}
			$serienNummer[$HM_CCU_Name][$result[0]]["Name"]=IPS_GetName($instanz);
			$serienNummer[$HM_CCU_Name][$result[0]]["Protokoll"]=$protocol;
			if ($sizeResult>1)
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["OID:".$result[1]]=$instanz;
				$serienNummer[$HM_CCU_Name][$result[0]]["Name:".$result[1]]=IPS_GetName($instanz);
				}
			else { if ($debug) echo "Fehler mit ".$result[0]."\n"; }			
			$cids = IPS_GetChildrenIDs($instanz);
			if ( isset($serienNummer[$HM_CCU_Name][$result[0]]["Update"]) == true) $update=$serienNummer[$HM_CCU_Name][$result[0]]["Update"];
			else $update=0;
			foreach($cids as $cid)
				{
				$o = IPS_GetObject($cid);
				if (IPS_GetVariable($cid)["VariableChanged"] != 0) 
					{
					if (IPS_GetVariable($cid)["VariableChanged"]>$update) $update=IPS_GetVariable($cid)["VariableChanged"];
					if ($debug) echo "   CID : ".$cid."  ".IPS_GetName($cid)."  ".date("d.m H:i",IPS_GetVariable($cid)["VariableChanged"])."   \n";
					}
				if($o['ObjectIdent'] != "")
					{
					$serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
					}
		    	}
			$serienNummer[$HM_CCU_Name][$result[0]]["Update"] = $update;	
			}
		$this->HomematicSerialNumberList=$serienNummer;
		return ($serienNummer);
		}

	/********************************************************************
	 * DeviceManagement::getHomematicAddressList
	 * erfasst alle Homematic Geräte anhand der Hardware Addressen und erstellt eine gemeinsame liste mit dem DeviceTyp aus HM Inventory 
     * wird bei construct bereits gestartet als gemeinsames Datenobjekt
     *
     * etwas komplizierte Überwachung der HMI_CreateReport Funktion:
     *
     * wenn länger als einen Tag kein Update dann wird das Update neu angefordert
     * wenn das Update angefordert wurde und noch nicht erfolgt ist gibt es eine Fehlermeldung und es wird nicht mehr angefordert
	 *
	 * die AdressListe wird trotzdem, allerdings basierend auf den letzten Daten erstellt. Andernfalls  funktioniert zum Beispiel evaluateHardware nicht mehr, da alle Hoimematic Adressen fehlen
     */
	function getHomematicAddressList($callCreateReport=false, $debug=false, $supress=false)
		{
        if ($debug>1) echo "DeviceManagement::getHomematicAddressList aufgerufen : Evaluate HMI : ".json_encode($this->HMIs)."\n";
		$countHMI = sizeof($this->HMIs);
		$addresses=array();         // Ergebnis
		if ($countHMI>0)
			{
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($this->HMIs as $HMI)
				{
                $HomeMaticEntries=false;
                if ($DeviceListe=$this->updateHmiReport($HMI,$debug, $supress))
                    {
                    $HomeMaticEntries=json_decode(GetValue($DeviceListe),true); 
                    }
                else 
                    {
                    echo  "DeviceListe $HMI NICHT erzeugt\n";
                    $deviceListId            = @IPS_GetObjectIDByName("Device Liste",$HMI);
                    if ($deviceListId) $HomeMaticEntries=json_decode(GetValue($deviceListId),true);
                    }
                if ($HomeMaticEntries)    
                    {
                    foreach ($HomeMaticEntries as $HomeMaticEntry)
                        {
                        if (isset($HomeMaticEntry["HM_address"])) 
                            {
                            if ($debug) echo "Addresse: ".$HomeMaticEntry["HM_address"]." Type ".$HomeMaticEntry["HM_device"]." Devicetyp ".$HomeMaticEntry["HM_devtype"]."\n";
                            $addresses[$HomeMaticEntry["HM_address"]]=$HomeMaticEntry["HM_device"];
                            //print_r($HomeMaticEntry);
                            }
                        }
                    }
				}       // ende foreach					
			}
		if ($debug)
			{
			echo "Ausgabe Adressen versus DeviceType für insgesamt ".sizeof($addresses)." Instanzen (Geräte/Kanäle).\n";	
			print_r($addresses);
			}
		return($addresses);
		}

    /* öffentliche Funktion um updateHmiReport aufzurufen
     *
     */

    public function updateHomematicAddressList($HMI=false, $debug=false, $supress=false, $callCreateReport=false)
        {
        //$debug=true; 
        if ($debug) echo "updateHomematicAddressList aufgerufen. herausfinden ob HMI_CreateReport erfolgreich war.\n";  
        $result= true;
        if ($HMI==false)
            {
            $countHMI = sizeof($this->HMIs);
            if ($countHMI>0)
                {
                foreach ($this->HMIs as $HMI)
                    {
                    $result = $result && $this->updateHmiReport($HMI,$debug,$supress, $callCreateReport);
                    }           // foreach
                }           //if
            }
        else return($this->updateHmiReport($HMI,$debug,$supress, $callCreateReport));
        return ($result);
        } 

    /* DeviceManagement::updateHmiReport
     * private, von updateHomematicAddressList und getHomematicAddressList für jeden Homematic Report aufgerufen
     *
     * das Ergebnisfile ist Children(0). Wenn es leer ist oder kein Array gebildet werden kann wird der Create report aufgerufen.
     * wenn das Ergebnisfile älter als 48 Stunden auch
     *
     */
    private function updateHmiReport($HMI,$debug=false, $supress=false, $callCreateReport=false)
        {
        $result=false; 
        $configHMI=IPS_GetConfiguration($HMI);
        $lastUpdateRequestTimeId = CreateVariableByName($HMI,"HMIReportUpdateRequestTime", 1, "", "", 101, null);         // integer, time
        $deviceListId            = @IPS_GetObjectIDByName("Device Liste",$HMI);
        if ($debug)             // no information available in configuration wether creation of report as variable is activated
            {
            echo "\n-----------------------------------\n";
            echo "updateHmiReport aufgerufen, Konfiguration für HMI Report Creator : ".$HMI." (".IPS_GetName($HMI).")\n";
            echo $configHMI."\n";
            echo "IDs found as childrens under Report Instance : LastUpdate $lastUpdateRequestTimeId DeviceList $deviceListId \n";
            }
        if ($deviceListId)                  // bei false nicht verwenden, Variable nicht vorhanden
            {
            $lastUpdate=IPS_GetVariable($deviceListId)["VariableChanged"];
            $noUpdate=time()-$lastUpdate;
            if ( $noUpdate > (48*60*60) )           // Abfragen für Fehlermeldungen etwas entschärft
                {
                if ( $noUpdate > (100*60*60) )           // schwerer Fehler, wenn das Update mehrere Tage lang nicht durchgeht
                    {
                    if ($debug) echo  "HMI_CreateReport needs update. Last update was ".date("d.m.y H:i:s",$lastUpdate).". CCU might had crashed. Please check. no more request for  HMI_CreateReport($HMI) to save CCU power.\n";
                    IPSLogger_Err(__file__, "HMI_CreateReport needs update. Last update was ".date("d.m.y H:i:s",$lastUpdate).". CCU might had crashed. Please check.");
                    }
                else
                    {
                    $message = "HMI_CreateReport needs update. Last update was ".date("d.m.y H:i:s",$lastUpdate).". Do right now.";
                    if ($debug) echo "     $message\n";
                    if ( $noUpdate > (25*60*60) )
                        {
                        SetValue($this->HMI_ReportStatusID,$message);
                        $callCreateReport=true;
                        }
                    else
                        {
                        $hoursnok=round($noUpdate/60/60);
                        if (GetValue($this->HMI_ReportStatusID)==$message) IPSLogger_Err(__file__, "HMI_CreateReport did not execute for $hoursnok hours. CCU might had crashed. Please check.");
                        else 
                            {
                            SetValue($this->HMI_ReportStatusID,$message);
                            $callCreateReport=true;
                            }
                        }
                    }
                }
            else
                {
                if ($supress==false) echo "    HMI_CreateReport für ".IPS_GetName($HMI)." wurde zuletzt am ".date("d.m.y H:i:s",$lastUpdate)." upgedatet.\n";
                SetValue($this->HMI_ReportStatusID,"HMI_CreateReport wurde zuletzt am ".date("d.m.y H:i:s",$lastUpdate)." upgedatet.");
                $result=$deviceListId;
                }
            $HomeMaticEntries=json_decode(GetValue($deviceListId),true);
            //print_R($HomeMaticEntries);
            if ($debug) echo "updateHmiReport, ".IPS_GetName($HMI)." ($HMI) HomeMaticEntries erzeugt : ".sizeof($HomeMaticEntries)."\n";
            if ( ( ( (is_array($HomeMaticEntries)) && (sizeof($HomeMaticEntries)>0) ) === false) || $callCreateReport)
                {
                $lastUpdateRequestTime = GetValue($lastUpdateRequestTimeId);
                SetValue($lastUpdateRequestTimeId,time());
                //if ($debug) 
                    {
                    echo "     updateHmiReport: HMI_CreateReport($HMI) aufrufen:";   
                    }
                HMI_CreateReport($HMI);  
                if ($debug) echo "  --> done\n";                  
                }                    
            }                   // es gibt einen report
        else echo "ERROR, no HMI Report created, no devicelist available.\n";
        return ($result);
        }

	/********************************************************************
	 * DeviceManagement::addHomematicSerialList_Typ
	 * Wenn Debug gib die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 * formatierte liste aus 
	 *
	 * die Homematic Liste wird um weitere Informationen erweitert:  Typ
     *
     * Beim Namen werden Untergruppierungen nach dem Doppelpunkt entfernt und nur der eigentliche Namen davor verglichen.
     * Es ist nicht erlaubt das dieser Name für eine Serialnummer,also ein Gerät, unterschiedlich ist
	 *
     */
	function addHomematicSerialList_Typ($debug=false)
		{
		if ($debug) echo "\nInsgesamt gibt es ".sizeof($this->HomematicSerialNumberList)." Homematic CCUs.\n";
        $serials=array();       /* eventuell doppelte Eintraege finden */
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			if ($debug) 
				{
				echo "-------------------------------------------\n";
			 	echo "  CCU mit Name :".$ccu."\n";
 				echo "    Es sind ".sizeof($geraete)." Geraete angeschlossen. (Zusammenfassung nach Geräte, Seriennummer)\n";
				}
			foreach ($geraete as $name => $anzahl)
				{
				//echo "\n *** ".$name."  \n";
				//print_r($anzahl);
                if ( isset($serials[$name])==true ) 
                    {
                    // Variablen Bezeichnungen die ein Hash am Ende haben sind nicht mehr zeitgemaes - umbenennen auf :Status oder loeschen
                    echo "  addHomematicSerialList_Typ, Fehler !!! Doppelter Eintrag in HomematicSerialNumberList für $name (".$serials[$name]."!=".$anzahl["Name"].").\n";
                    }
				else $serials[$name]=$anzahl["Name"];
				$register=explode(" ",trim($anzahl["Values"]));


				if ($debug) echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".str_pad($name,20)." Inst: ".str_pad($anzahl["Anzahl"],4)." Child: ".str_pad(sizeof($register),6)." ";
			    if (sizeof($register)>1) 
				    { /* es gibt Childrens zum analysieren, zuerst gleiche Werte unterdruecken */
				    if ($debug) echo $this->HomematicDeviceType($register,2)."\n";
                    $this->HomematicSerialNumberList[$ccu][$name]["Type"]=$this->HomematicDeviceType($register,2);
					} 
				else
					{	
					if ($debug)
						{ 
						echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ";
						echo "not installed\n";
						}
					}	
				}
			}
        return($serials);            
		}

	/********************************************************************
	 * DeviceManagement::addHomematicSerialList_RSSI
	 * die Homematic Liste der Seriennummern wird um weitere Informationen erweitert:  RSSI
	 *
	 *
     */
	function addHomematicSerialList_RSSI($debug=false)
		{
		/* Tabelle vorbereiten, RSSI Werte ermitteln */
	
		IPSUtils_Include ('Homematic_Library.class.php',      'IPSLibrary::app::modules::OperationCenter');

		$homematicManager = new Homematic_OperationCenter();
		$homematicManager->RefreshRSSI($debug);

		$categoryIdHtml     = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.hardware.IPSHomematic.StatusMessages');
		$variableIdRssi       = IPS_GetObjectIDByIdent(HM_CONTROL_RSSI, $categoryIdHtml);
		if ($debug) echo GetValue($variableIdRssi);	// output Table

		$instanceIdList = $homematicManager->GetMaintainanceInstanceList($debug);
		$rssiDeviceList = array();
		$rssiPeerList   = array();
		foreach ($instanceIdList as $instanceId) {
			$variableId = @IPS_GetVariableIDByName('RSSI_DEVICE', $instanceId);
			if ($variableId!==false) {
				$rssiValue = GetValue($variableId);
				if ($rssiValue<>-65535) {
					$rssiDeviceList[$instanceId] = $rssiValue;
					}
				}
			}
		arsort($rssiDeviceList, SORT_NATURAL);

		foreach ($instanceIdList as $instanceId) {
			$variableId = @IPS_GetVariableIDByName('RSSI_PEER', $instanceId);
			if ($variableId!==false) {
				$rssiValue = GetValue($variableId);
				if ($rssiValue<>-65535) {
					$rssiPeerList[$instanceId] = $rssiValue;
					}
				}
			}
			
		if ($debug) echo "\n\nAusgabe RSSI Werte pro Seriennummer (Anreicherung der serienNummer Tabelle):\n\n";	
		foreach($rssiDeviceList as $instanceId=>$value) 
			{
			$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanceId)['ConnectionID']);
            $HMaddressName=IPS_GetProperty($instanceId,'Address');                              //HM_GetAddress($instanceId)
			if ($debug) echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    $HMaddressName    ".$value."\n";
			$HMaddress=explode(":",$HMaddressName);
			$this->HomematicSerialNumberList[$HM_CCU_Name][$HMaddress[0]]["RSSI"]=$value;
			}			
		}

	/********************************************************************
	 * DeviceManagement::addHomematicSerialList_DetectMovement
	 * die Homematic Liste der Seriennummern wird um weitere Informationen erweitert:  Detect Movement
	 *
	 *****************************************************************************/

	function addHomematicSerialList_DetectMovement($debug=false)
		{
		if (isset($this->installedModules["DetectMovement"])==true)
			{
			/* DetectMovement */
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

			if (function_exists('IPSDetectMovementHandler_GetEventConfiguration')) 		$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
			else $movement_config=array();
			if (function_exists('IPSDetectTemperatureHandler_GetEventConfiguration'))	$temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
			else $temperature_config=array();
			if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		$humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
			else $humidity_config=array();
			if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	$heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
			else $heatcontrol_config=array();


			}
		}
	
	/********************************************************************
	 *
	 * Die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 *  formatierte liste ausgeben mit echo 
	 *
	 *****************************************************************************/

	function writeHomematicSerialNumberList()
		{
		$instanzCount=0;
		$channelCount=0;
		echo "\nInsgesamt gibt es ".sizeof($this->HomematicSerialNumberList)." Homematic CCUs.\n";
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			echo "-------------------------------------------\n";
		 	echo "  CCU mit Name :".$ccu."\n";
			echo "    Es sind ".sizeof($geraete)." Geraete angeschlossen. (Zusammenfassung nach Geräte, Seriennummer)\n";
			foreach ($geraete as $name => $anzahl)
				{
				$register=explode(" ",trim($anzahl["Values"]));
				if ( isset($anzahl["Typ"]) == true )
					{
					echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ".$anzahl["Typ"]."\n";
					}
				else
					{
					echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ********** Typ nicht bekannt \n";
					}
				$instanzCount+=$anzahl["Anzahl"];
				$channelCount+=sizeof($register);	
				}
			}
		echo "\nEs wurden insgesamt ".$instanzCount." Geraeteinstanzen mit total ".$channelCount." Kanälen/Registern ausgegeben.\n";
		}

	/********************************************************************
	 *
	 * Die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 * html formatierte liste ausgeben (echo) 
	 *
	 *****************************************************************************/

	function tableHomematicSerialNumberList($columns=array(),$sort=array(),$debug=false)
		{
		//print_r($columns);
        if ($debug) echo "tableHomematicSerialNumberList aufgerufen, fuer ".sizeof($this->HomematicSerialNumberList)." CCUs:\n";
		if (isset($columns["Channels"])==true) $showChannels=$columns["Channels"]; else $showChannels=false;
		if (isset($sort["Serials"])==true) $sortSerials=$sort["Serials"]; else $sortSerials=false;
		$str="";
		$ccuNum=1;	
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
            if ($debug) echo "   $ccu ".sizeof($geraete)."  :\n";
			$str .= "<table width='90%' align='center'>"; 
			$str .= "<tr><td><b>".$ccu."</b></td></tr>";
			$str .= "<tr><td><b>Seriennummer</b></td>";
			if ($showChannels) $str .= "<td><b>Kanal</b></td>";
			$str .= "<td><b>GeräteName</b></td><td><b>Protokoll</b></td><td><b>GeraeteTyp</b></td><td><b>UpdateTime</b></td><td><b>RSSI</b></td></tr>";
			if ($sortSerials) ksort($geraete);
			foreach ($geraete as $name => $geraet)
				{
                if ($debug) echo "       $name ";
				$str .= "<tr><td>".$name."</td>";			// Name ist die Seriennummer
				if ($showChannels) $str .= "<td></td>";		// eventuell Platz lassen für Kanalnummer	
				if (isset($geraet["Typ"])==true)                        // wir haben nur Type
					{
					if (isset($geraet["RSSI"])==true)
						{
						$str .= "<td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
						date("d.m H:i",$geraet["Update"])."</td><td>".$geraet["RSSI"]."</td></tr>";
						}
					else
						{	
						$str .= "<td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
						date("d.m H:i",$geraet["Update"])."</td></tr>";
						}
					}
				else
					{
					$str .= "<td>   </td><td>      </td></tr>";				
					//$str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td></tr>";				
					}
				$strChannel=array();	
				if ($showChannels) 
					{
					foreach ($geraet as $id => $channels)
						{
						$channel=explode(":",$id);
						if (sizeof($channel)==2) 
							{
							if ($channel[0]=="Name")
								{
								$strChannel[$channel[1]] = "<tr><td></td><td>".$channel[1]."</td><td>".$channels."</td></tr>";
								}
							}	
						}
					ksort($strChannel);	
					foreach ($strChannel as $index => $line) { $str .= $line; }					
					}
				}		
            $str .= "</table>"; // Tabelle abschliessen    
			$ccuNum++;
			}
		echo $str; 
		return ($str);		
		}
	
	/********************************************************************
	 * DeviceManagement::getHomematicDeviceList
	 * alle Homematic Geräte erfassen und in einer grossen Tabelle ausgeben
	 *
	 *
     */
	function getHomematicDeviceList($debug=false)
		{

		//$this->getHomematicSerialNumberList($debug);			// gleich die Liste die in der Klasse gespeichert wird nehmen
		$this->addHomematicSerialList_Typ($debug);
		//$this->writeHomematicSerialNumberList();						// Die Geräte schön formatiert als Liste ausgeben


		$serienNummer=$this->HomematicSerialNumberList;
		/* Tabelle vorbereiten, RSSI Werte ermitteln */
	
		IPSUtils_Include ('Homematic_Library.class.php',      'IPSLibrary::app::modules::OperationCenter');

		$homematicManager = new Homematic_OperationCenter();
		$homematicManager->RefreshRSSI();

			$categoryIdHtml     = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.hardware.IPSHomematic.StatusMessages');
			$variableIdRssi       = IPS_GetObjectIDByIdent(HM_CONTROL_RSSI, $categoryIdHtml);
			echo GetValue($variableIdRssi);	// output Table

			$instanceIdList = $homematicManager->GetMaintainanceInstanceList();
			$rssiDeviceList = array();
			$rssiPeerList   = array();
			foreach ($instanceIdList as $instanceId) {
				$variableId = @IPS_GetVariableIDByName('RSSI_DEVICE', $instanceId);
				if ($variableId!==false) {
					$rssiValue = GetValue($variableId);
					if ($rssiValue<>-65535) {
						$rssiDeviceList[$instanceId] = $rssiValue;
					}
				}
			}
			arsort($rssiDeviceList, SORT_NATURAL);

			foreach ($instanceIdList as $instanceId) {
				$variableId = @IPS_GetVariableIDByName('RSSI_PEER', $instanceId);
				if ($variableId!==false) {
					$rssiValue = GetValue($variableId);
					if ($rssiValue<>-65535) {
						$rssiPeerList[$instanceId] = $rssiValue;
					}
				}
			}
			
		echo "\n\nAusgabe RSSI Werte pro Seriennummer (Anreicherung der serienNummer Tabelle):\n\n";	
			foreach($rssiDeviceList as $instanceId=>$value) 
				{
				$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanceId)['ConnectionID']);
                $HMaddressName=IPS_GetProperty($instanceId,'Address');                              //HM_GetAddress($instanceId)
				echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    $HMaddressName    ".$value."\n";
				$HMaddress=explode(":",$HMaddressName);
				$serienNummer[$HM_CCU_Name][$HMaddress[0]]["RSSI"]=$value;
				}			
        
        print_r($serienNummer);

        /* Tabelle indexiert nach Seriennummern ausgeben, es wird pro Homematic Socket eine eigene Tabelle erstellt */

        $str="";
        $ccuNum=1;	
        foreach ($serienNummer as $ccu => $geraete)
            {
            $str .= "<table width='90%' align='center'>"; 
            $str .= "<tr><td><b>".$ccu."</b></td></tr>";
            $str .= "<tr><td><b>Seriennummer</b></td><td><b>GeräteName</b></td><td><b>Protokoll</b></td><td><b>GeraeteTyp</b></td><td><b>UpdateTime</b></td><td><b>RSSI</b></td></tr>";
            foreach ($geraete as $name => $geraet)
                {
                if (isset($geraet["Typ"])==true)
                    {
                    if (isset($geraet["RSSI"])==true)
                        {
                        $str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
                            date("d.m H:i",$geraet["Update"])."</td><td>".$geraet["RSSI"]."</td></tr>";
                        }
                    else
                        {	
                        $str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
                            date("d.m H:i",$geraet["Update"])."</td></tr>";
                        }
                    }
                else
                    {
                    $str .= "<tr><td>".$name."</td><td>   </td><td>      </td></tr>";				
                    //$str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td></tr>";				
                    }		
                }		
            $ccuNum++;
            }
        echo $str; 		

    	$CategoryIdHomematicGeraeteliste = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicDeviceList');
	    $HomematicGeraeteliste = CreateVariable("HomematicGeraeteListe",   3 /*String*/,  $CategoryIdHomematicGeraeteliste, 50 , '~HTMLBox');
	    SetValue($HomematicGeraeteliste,$str);
        return ($str);
		}

    /*****************************************************
    *
    * HM_TYPE für Homematic feststellen
    *
    * anhand einer Homatic Instanz ID ermitteln 
    * um welchen Typ von Homematic Geraet es sich handeln koennte,
    * es wird nur HM_TYPE_BUTTON, HM_TYPE_SWITCH, HM_TYPE_DIMMER, HM_TYPE_SHUTTER unterschieden
    */
    function getHomematicType($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        //print_r($cids);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        //print_r($homematic);
        /* 	define ('HM_TYPE_LIGHT',					'Light');
        define ('HM_TYPE_SHUTTER',					'Shutter');
        define ('HM_TYPE_DIMMER',					'Dimmer');
        define ('HM_TYPE_BUTTON',					'Button');
        define ('HM_TYPE_SMOKEDETECTOR',			'SmokeDetector');
        define ('HM_TYPE_SWITCH',					'Switch'); */
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            switch ($homematic[0])
                {
                case "ERROR":
                    //echo "Funk-Tür-/Fensterkontakt\n";
                    break;
                case "INSTALL_TEST":
                    if ($homematic[1]=="PRESS_CONT")
                        {
                        //echo "Taster 6fach\n";
                        }
                    else
                        {
                        //echo "Funk-Display-Wandtaster\n";
                        }
                    $type="HM_TYPE_BUTTON";
                    break;
                case "ACTUAL_HUMIDITY":
                    //echo "Funk-Wandthermostat\n";
                    break;
                case "ACTUAL_TEMPERATURE":
                    //echo "Funk-Heizkörperthermostat\n";
                    break;
                case "BRIGHTNESS":
                    //echo "Funk-Bewegungsmelder\n";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        //echo "Dimmer\n";
                        $type="HM_TYPE_DIMMER";						
                        }
                    else
                        {
                        //echo "Rolladensteuerung\n";
                        }
                    break;
                case "PROCESS":
                case "INHIBIT":
                    //echo "Funk-Schaltaktor 1-fach\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "BOOT":
                    //echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "CURRENT":
                    //echo "Energiemessung\n";
                    break;
                case "HUMIDITY":
                    //echo "Funk-Thermometer\n";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        //echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        //echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        //echo "IP Funk-Schaltaktor\n";
                        $type="HM_TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    //echo "unknown\n";
                    //print_r($homematic);
                    break;
                }
            }
        else
            {
            //echo "   noch nicht angelegt.\n";
            }			

        return ($type);
        }


    /*********************************
     * DeviceManagement::HomematicDeviceType
     * Homematic Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * VALVE_STATE                                  Stellmotor, (IP) Funk Stellmotor, TYPE_ACTUATOR
     * ACTIVE_PROFILE oder WINDOW_OPEN_REPORTING    Wandthermostat, (IP) Funk Wandthermostat, TYPE_THERMOSTAT
     * TEMPERATURE und HUMIDITY                     Temperatursensor, (IP) Funk Temperatursensor, TYPE_METER_TEMPERATURE
     * PRESS_SHORT                                  Taster x-fach, (IP) Funk Tast x-fach, TYPE_BUTTON
     * STATE                                        kann ein Schalter oder ein Kontakt sein
     * LEVEL                                        Dimmer oder Rolladensteuerung
     * MOTION                                       Bewegungserkennung
     * RSSI                                         Statusregster
     * CURRENT                                      Energiemessgerät
     * CURRENT_ILLUMINATION                         Helligkeitssensor
     * RAIN_COUNTER                                 Wetterstation
     * CURRENT_PASSAGE_DIRECTION                    Durchgangserkennung, zählt und erkennt Richtung
     * ACTIVITY_STATE                               Tuerschloss
     *
     * zur Auswertung werden die Namen der Childrens sortiert und gleiche Namen entfernt
     *
     * nach der Auswertung wird $resultType[0] mit dem DeviceType beschrieben.
     * 
     * erkannte Device Typen (unabhängig ob Homematic, Evaluierung von oben nach unten
     *  TYPE_ACTUATOR               => VALVE_STATE
     *  TYPE_THERMOSTAT             => ACTIVE_PROFILE || WINDOW_OPEN_REPORTING
     *  TYPE_METER_TEMPERATURE      => TEMPERATURE && HUMIDITY
     *  TYPE_METER_HUMIDITY         => HUMIDITY
     *  TYPE_BUTTON                 => PRESS_SHORT
     *  TYPE_SWITCH                 => STATE && (PROCESS || WORKING)
     *  TYPE_CONTACT                => STATE
     *  TYPE_DIMMER                 => LEVEL && DIRECTION && ERROR_OVERLOAD
     *  TYPE_SHUTTER                => LEVEL && DIRECTION
     *  TYPE_MOTION                 => MOTION
     *  TYPE_RSSI                   => RSSI
     *  TYPE_METER_POWER
     *  TYPE_POWERLOCK
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false   die aktuelle Kategorisierung
     *
     * abhängig vom Gerätetyp bzw. den Instanzeigenschaften werden für die Instanz die Register jeweils mit Typ und Parameter ermittelt
     *      $resultType[i] = "TYPE_METER_TEMPERATURE";            
     *      $resultReg[i]["TEMPERATURE"]="TEMPERATURE";
     *      $resultReg[i]["HUMIDITY"]="HUMIDITY";
     *
     *
     *
     *
     */
    private function HomematicDeviceType($register, $outputVersion=false, $debug=false)
        {
        /* register in registernew umkopieren, dabei alle Einträge sortieren und gleiche, doppelte Einträge entfernen */
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
        /* gleiche Einträge eliminieren */
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        if ($debug) echo "             HomematicDeviceType: Info mit Debug aufgerufen. Parameter ".json_encode($registerNew)."\n";

        /*--Stellmotor-----------------------------------*/
        if ( array_search("VALVE_STATE",$registerNew) !== false)            /* Stellmotor */
            {
            //print_r($registerNew);
            //echo "Stellmotor gefunden.\n";
            if (array_search("ACTIVE_PROFILE",$registerNew) !== false) 
                {
                $result[1]="IP Funk Stellmotor";
                }
            else 
                {
                $result[1]="Funk Stellmotor";
                }                         
            $result[0]="Stellmotor";   
            $i=0;                            
            $resultType[$i]="TYPE_ACTUATOR";
            if (array_search("LEVEL",$registerNew) !== false)           // di emodernere Variante
                {
                $resultReg[$i]["VALVE_STATE"]="LEVEL"; 
                if (array_search("SET_POINT_TEMPERATURE",$registerNew) !== false)$resultReg[$i]["SET_TEMPERATURE"]="SET_POINT_TEMPERATURE";
                }
            else 
                {
                $resultReg[$i]["VALVE_STATE"]="VALVE_STATE";
                if (array_search("SET_TEMPERATURE",$registerNew) !== false)$resultReg[$i]["SET_TEMPERATURE"]="SET_TEMPERATURE";                
                }
            if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                }
            }
        /*-----Wandthermostat--------------------------------*/
        elseif ( (array_search("ACTIVE_PROFILE",$registerNew) !== false) || (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false) )   /* Wandthermostat */
            {
            if (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false)
                {
                $result[1]="Funk Wandthermostat";
                }
            else 
                {
                $result[1]="IP Funk Wandthermostat";
                }
            $result[0] = "Wandthermostat";
            $i=0;
            $resultType[$i]="TYPE_THERMOSTAT";
            if (array_search("SET_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_TEMPERATURE";
            if (array_search("SET_POINT_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_POINT_TEMPERATURE";
            if (array_search("TargetTempVar",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="TargetTempVar";
            //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
            if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                {
                $i++;
                $resultType[$i]= "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                }
            if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                }
            if (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            }                    
        /*-----Temperatur Sensor--------------------------------*/
        elseif ( (array_search("TEMPERATURE",$registerNew) !== false) && (array_search("HUMIDITY",$registerNew) !== false) )   /* Temperatur Sensor */
            {
            $result[1] = "Funk Temperatursensor";
            $result[0] = "Temperatursensor";
            $i=0;
            $resultType[$i] = "TYPE_METER_TEMPERATURE";            
            $resultReg[$i]["TEMPERATURE"]="TEMPERATURE";
            $resultReg[$i]["HUMIDITY"]="HUMIDITY";
            if (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            } 
        /* Temperatur Sensor , aber keine Wetterstation, kommt erst später -------------*/
        elseif ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("HUMIDITY",$registerNew) !== false) && (array_search("RAIN_COUNTER",$registerNew) == false))   
            {
            $result[1] = "IP Funk Temperatursensor";
            $result[0] = "Temperatursensor";
            $i=0;
            $resultType[$i] = "TYPE_METER_TEMPERATURE";            
            $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE";
            $resultReg[$i]["HUMIDITY"]="HUMIDITY";
            if (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            }                                 
        /*------Taster-------------------------------*/
        elseif (array_search("PRESS_SHORT",$registerNew) !== false) /* Taster */
            {
            $anzahl=sizeof(array_keys($register,"PRESS_SHORT")); 
            if (array_search("INSTALL_TEST",$registerNew) !== false) 
                {
                $result[1]="Funk-Taster ".$anzahl."-fach";
                }
            else 
                {
                $result[1]="IP Funk-Taster ".$anzahl."-fach";
                }
            $result[0]="Taster ".$anzahl."-fach";
            $resultType[0] = "TYPE_BUTTON";            
            if (array_search("PRESS_SHORT",$registerNew) !== false) $resultReg[0]["PRESS_SHORT"]="PRESS_SHORT";
            if (array_search("PRESS_LONG",$registerNew) !== false) $resultReg[0]["PRESS_LONG"]="PRESS_LONG";
            if ($debug) echo "-----> Taster : ".$resultType[0]." ".json_encode($registerNew).json_encode($resultReg[0])."\n";
            }
        /*-------Schaltaktor oder Kontakt------------------------------*/
        elseif ( array_search("STATE",$registerNew) !== false) /* Schaltaktor oder Kontakt */
            {
            //print_r($registerNew);
            $anzahl=sizeof(array_keys($register,"STATE"));                     
            if ( (array_search("PROCESS",$registerNew) !== false) || (array_search("WORKING",$registerNew) !== false) )     // entweder PROCESS oder WORKING gefunden
                {
                $result[0]="Schaltaktor ".$anzahl."-fach";
                if ( (array_search("BOOT",$registerNew) !== false) || (array_search("LOWBAT",$registerNew) !== false) )     //entweder Boot oder LOWBAT gefunden
                    {
                    $result[1]="Funk-Schaltaktor ".$anzahl."-fach";
                    }
                /* "SECTION_STATUS" ist bei den neuen Schaltern auch dabei. Die neuen HomematicIP Schalter geben den Status insgesamt dreimal zurück, Selektion mus ich wohl wo anders machen */
                else    
                    {
                    $result[1]="IP Funk-Schaltaktor ".$anzahl."-fach";
                    }
                if (array_search("ENERGY_COUNTER",$registerNew) !== false) 
                    {
                    $result[0] .= " mit Energiemesung";
                    $result[1] .= " mit Energiemesung";
                    }
                $resultType[0] = "TYPE_SWITCH";            
                $resultReg[0]["STATE"]="STATE";
                }
            else 
                {
                $result[0] = "Tuerkontakt";
                $result[1] = "Funk-Tuerkontakt";
                $resultType[0] = "TYPE_CONTACT";            
                $resultReg[0]["CONTACT"]="STATE";                
                }
            }
        /*-----RGBW Ansteuerung --------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("LEVEL_STATUS",$registerNew) !== false) && ( array_search("HUE",$registerNew) !== false) )/* RGBW Ansteuerung */
            {
            $result[0] = "RGBW";
            $result[1] = "Funk-RGBW";
            $resultType[0] = "TYPE_RGBW"; 
            $resultReg[0]["LEVEL"]="LEVEL";                       
            $resultReg[0]["HUE"]="HUE";                       
            $resultReg[0]["SATURATION"]="SATURATION";
            $resultReg[0]["COLOR_TEMPERATURE"]="COLOR_TEMPERATURE";
            }
        /*-----Dimmer--------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) && ( array_search("ERROR_OVERLOAD",$registerNew) !== false) )/* Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Dimmer";
            $result[1] = "Funk-Dimmer";
            $resultType[0] = "TYPE_DIMMER"; 
            $resultReg[0]["LEVEL"]="LEVEL";                       
            }                    
        /*-------------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("LEVEL_STATUS",$registerNew) !== false) )/* HomematicIP Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Dimmer";
            $result[1] = "Funk-Dimmer";
            $resultType[0] = "TYPE_DIMMER"; 
            $resultReg[0]["LEVEL"]="LEVEL";                       
            }         
        /*------Rolladensteuerung-------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) )                   /* Rollladensteuerung/SHUTTER */
            {
            //print_r($registerNew);                
            $result[0] = "Rollladensteuerung";
            $result[1] = "Funk-Rollladensteuerung";
            $resultType[0] = "TYPE_SHUTTER";    
            $resultReg[0]["HEIGHT"]="LEVEL";              // DIRECTION INHIBIT LEVEL WORKING
            }                    
        /*-------Bewegung------------------------------*/
        elseif ( array_search("MOTION",$registerNew) !== false) /* Bewegungsmelder, Durchgangssensor ist weiter unten */
            {
            //print_r($registerNew);    
            $result[0] = "Bewegungsmelder";
            $result[1] = "Funk-Bewegungsmelder";
            $resultType[0] = "TYPE_MOTION";            
            $resultReg[0]["MOTION"]="MOTION";
            if ( array_search("BRIGHTNESS",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="BRIGHTNESS";
            if ( array_search("ILLUMINATION",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="ILLUMINATION";
            }
        elseif ( array_search("PRESENCE_DETECTION_STATE",$registerNew) !== false) /* Presaenzmelder  */
            {
            //print_r($registerNew);    
            $result[0] = "Bewegungsmelder";
            $result[1] = "Funk-Bewegungsmelder";
            $resultType[0] = "TYPE_MOTION";            
            $resultReg[0]["MOTION"]="PRESENCE_DETECTION_STATE";
            if ( array_search("BRIGHTNESS",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="BRIGHTNESS";
            if ( array_search("ILLUMINATION",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="ILLUMINATION";
            }            
        /*-------RSSI------------------------------*/
        elseif ( array_search("RSSI_DEVICE",$registerNew) !== false) /* nur der Empfangswert */
            {
            $result[0] = "RSSI Wert";
            if ( array_search("DUTY_CYCLE",$registerNew) !== false) $result[1] = "IP Funk RSSI Wert";
            else $result[1] = "Funk RSSI Wert";
            $resultType[0] = "TYPE_RSSI";             
            $resultReg[0]["RSSI"] = "";
            }            
        /*-------Energiemessgerät------------------------------*/
        elseif ( array_search("CURRENT",$registerNew) !== false) /* Messgerät */
            {
            $result[0] = "Energiemessgeraet";
            if ( array_search("BOOT",$registerNew) !== false) $result[1] = "Funk Energiemessgeraet";
            else $result[1] = "IP Funk Energiemessgeraet";
            $resultType[0] = "TYPE_METER_POWER";             
            if (array_search("ENERGY_COUNTER",$registerNew)) $resultReg[0]["ENERGY"]="ENERGY_COUNTER";                   // diese Register werden zur Verfügung gestellt und regelmaessig ausgewertet
            if (array_search("POWER",$registerNew)) $resultReg[0]["POWER"]="POWER";  
            }          
        /*-------Helligkeitssensor------------------------------*/
        elseif ( array_search("CURRENT_ILLUMINATION",$registerNew) !== false)     /* Helligkeitssensor */
            {
            $result[0] = "Helligkeitssensor";
            $result[1] = "IP Funk Helligkeitssensor";
            $resultType[0] = "TYPE_METER_CLIMATE";             
            $resultReg[0]["BRIGHTNESS"]="CURRENT_ILLUMINATION";          
            }
        /*-----Wetterstation--------------------------------*/
        elseif  (array_search("RAIN_COUNTER",$registerNew) !== false)    /* neue HomematicIP Wetterstation  */
            {
            $result[0] = "Wetterstation";
            $result[1]="Funk Wetterstation";

            $i=0;
            $resultType[$i]="TYPE_METER_CLIMATE";
            $resultReg[$i]["RAIN_COUNTER"]="RAIN_COUNTER";
            $resultReg[$i]["RAINING"]="RAINING";
            $resultReg[$i]["WIND_SPEED"]="WIND_SPEED";
            if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i]= "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY";           //Homematic
                elseif (array_search("HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="HUMIDITY";                     //HomematicIP 
                }
            if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                }
            elseif (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            }
        /*-------------Durchgangssensor ["CURRENT_PASSAGE_DIRECTION","LAST_PASSAGE_DIRECTION","PASSAGE_COUNTER_OVERFLOW","PASSAGE_COUNTER_VALUE"]-------*/
        elseif  (array_search("CURRENT_PASSAGE_DIRECTION",$registerNew) !== false)    /* neue HomematicIP Durchgangserkennung  */
            {
            $result[0] = "Durchgangsmelder";
            $result[1]="IP Funk Durchgangsmelder";              // HomematicIP 

            $i=0;                                               // kann auch weitere Funktionen beinhalten
            $resultType[$i]="TYPE_MOTION";
            $resultReg[$i]["COUNTER"]="PASSAGE_COUNTER_VALUE";
            $resultReg[$i]["DIRECTION"]="CURRENT_PASSAGE_DIRECTION";
            $resultReg[$i]["LAST_DIRECTION"]="LAST_PASSAGE_DIRECTION";
            }                      
        /*-------Tuerschloss  ["ACTIVITY_STATE","LOCK_STATE","PROCESS","SECTION","SECTION_STATUS","WP_OPTIONS"]--------------------------------*/
        elseif  (array_search("ACTIVITY_STATE",$registerNew) !== false)    /* HomematicIP Tuerschloss, Aktuator WP_OPTIONS 0,1,2 Status LOCK_STATE  */
            {
            $result[0] = "Tuerschloss";
            $result[1]="IP Funk Tuerschloss";              // HomematicIP 

            $i=0;                                               // kann auch weitere Funktionen beinhalten
            $resultType[$i]="TYPE_POWERLOCK";
            $resultReg[$i]["LOCKSTATE"]="LOCK_STATE";
            $resultReg[$i]["KEYSTATE"]="WP_OPTIONS";                // Aktuator
            }                      
        /*-------CCU3  ["DUTY_CYCLE_LEVEL"]--------------------------------*/
        elseif  (array_search("DUTY_CYCLE_LEVEL",$registerNew) !== false)    /* HomematicIP CCU3 Performance  */
            {
            $result[0] = "CCU";
            $result[1]="IP Funk CCU";              // HomematicIP 

            $i=0;                                               // kann auch weitere Funktionen beinhalten
            $resultType[$i]="TYPE_CCU";
            $resultReg[$i]["DUTY_CYCLE_LEVEL"]="DUTY_CYCLE_LEVEL";
            }                      
        /*-------CCU  ["DUTY_CYCLE","CONNECTED"] von HomematicExtended--------------------------------*/
        elseif  ( (array_search("DUTY_CYCLE",$registerNew) !== false)   && ( array_search("CONNECTED",$registerNew) !== false) )  /* HomematicExtended CCU Parameter, getrennt für RF und HmIP   */
            {
            $result[0] = "CCU";
            $result[1]="IP Funk CCU";              // HomematicIP 

            $i=0;                                               // kann auch weitere Funktionen beinhalten
            $resultType[$i]="TYPE_CCU";
            $resultReg[$i]["DUTY_CYCLE_LEVEL"]="DUTY_CYCLE";
            }                      
        else 
            {
            $found=false;
            if ($debug)
                { 
                echo "             HomematicDeviceType: kein bekanntes Muster für ein Gerät entdeckt. Wirklich so schwierig ?\n";
                print_r($registerNew);
                }
            }
        /* result[0] und result[1] wurden bereits geschrieben, hier result[2], result[3] und result[4] ergänzen 
         * result[2] ist der resultType also TYPE_METER_POWER
         * result[3] ist für die deviceList, "Type" ist resultType, "Register" ist resultReg
         * 
         */

        if ($found) 
            {
            $result[2]                = $resultType[0];
            $result[3]["Type"]        = $resultType[0];
            $result[3]["Register"]    = $resultReg[0];
            $result[3]["RegisterAll"] = $registerNew;
            $result[4]["TYPECHAN"]    = "";
            $first=true;
            foreach ($resultType as $index => $type)            // normalerweise wird nur [0] befüllt, wenn mehrere Register Sets verfügbar auch mehrere
                {
                if ($first) $first=false;
                else $result[4]["TYPECHAN"] .= ",";
                $result[4]["TYPECHAN"] .= $type;
                $result[4][$type]   = $resultReg[$index];
                }
            $result[4]["RegisterAll"] = $registerNew;

            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) 
                {       
                /* bei Output Version 4 mehrere TYPECHANs zulassen 
                if ($resultType[0]=="TYPE_ACTUATOR")
                    {
                    if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }
                elseif ($resultType[0]=="TYPE_THERMOSTAT")
                    {
                    //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
                    if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }*/
                return ($result[4]);
                } 
			else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }
        }


    /*********************************
     *
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     * Es gibt für ein Homematic Gerät mehrere Instanzen/Channels. Nicht alle sind relevant. Daher ausklammern.
     * Routine ermittelt alle Children eines Objektes und übergibt sie als array zur Prüfung
     * ruft HomematicDeviceType auf, es gibt verschieden Ausgabeformate
     *   0   Beispiel  "Bewegungsmelder";
     *   1   Beispiel  "Funk-Bewegungsmelder";
     *   2   Beispiel  TYPE_MOTION
     *   3   { "Type"=>TYPE_MOTION,"Register"=> $resultReg[0],"RegisterAll"=>  }
     *   4
     *
     *  0/false ist Default
     *
     * HomematicDeviceType siehe oben
     *
     *
     */
    function getHomematicDeviceType($instanz, $outputVersion=false, $debug=false)
	    {
        if ($debug) echo "          getHomematicDeviceType : $instanz  \"".IPS_GetName($instanz)."\" Modus : $outputVersion\n";
    	$cids = IPS_GetChildrenIDs($instanz);
	    $homematic=array();
    	foreach($cids as $cid)
	    	{
		    $homematic[$cid]=IPS_GetName($cid);
    		}
    	return ($this->HomematicDeviceType($homematic,$outputVersion, $debug));
    	}

    /*********************************
     * DeviceManagement::getHomematicHMDevice
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Device Typ aus HM Inventory aus
     * Voraussetzung ist das das Homematic Inventory Handler Modul installiert ist. Sonst wird ein leerer String zurückgegeben
     * Aufruf von getHomematicDevices -> EvaluateHardware
     *
     * Der zweite Parameter definiert den gewünschten Output
     *      default, false      Standard Homematic Name 
     *      1                   eine deutsprachige Beschreibung ausgegeben.
     *      2                   eine Matrix,  Index ist Port, nur Port mit Wert 1 oder größer wird übernommen, 0 generell ignoriert
     *
     * Neue Geräte mit dem Device Type der in der Fehlermeldung angegeben wurde hinzufügen. Neue Kategorie nur anlegen wenn keine vergleichbare Funktion gefunden wurde.
     * HomematicDeviceType muss eventuell auch angepasst werden
     *
     *
     */		
	function getHomematicHMDevice($instanz, $output=false, $debug=false)
		{
        $matrix=false;
        $key=IPS_GetProperty($instanz,"Address");
        if ($debug) echo "Aufruf getHomematicHMDevice mit $instanz die hat Adresse \"$key\"\n";
        //print_R($this->HomematicAddressesList);
		if (isset($this->HomematicAddressesList[$key]) ) 
            {
            //echo "getHomematicHMDevice , $instanz $key in HMI Report gefunden.\n";
            if ($output == false) return($this->HomematicAddressesList[$key]);
            else
                {
                switch ($this->HomematicAddressesList[$key])
                    {
                    case "HM-PB-6-WM55":
                    case "HmIP-WRC6":
                    case "HmIP-FCI6":                               // der mit den Kontakten, hat auch andere Darstellung der einzelnen Taster : PRESS_LONG_START, PRESS_LONG_RELEASE, STATE
                        $result="Taster 6-fach";
                        $matrix=[0,2,2,2,2,2,2,1];                        
                        break;

                    case "HM-PB-4Dis-WM":
                        $result="Taster 4-fach";
                        $matrix=[0,2,2,2,2,1,1,1];                        
                        break;

                    case "HM-PB-2-WM55":
                    case "HM-PB-2-WM55-2":
                    case "HM-LC-Sw2-PB-FM":                 // Doppel Taster als Einbauvariante mit Schalter
                        $result="Taster 2-fach";
                        $matrix=[0,2,2,1,1,1,1,1];                        
                        break;

                    case "HmIP-SPDR":                       // Durchgangssensor
                        $result="Durchgangssensor";
                        $matrix=[0,1,2,2,1,1];              // 5 Ports und die Durchgangssensoren sind auf 2 und 3                        
                        break;

                    case "HM-Sec-SC":
                    case "HM-Sec-SC-2":
                    case "HMIP-SWDO":
                    case "HmIP-SWDO-2":                 // immer neue Varianten, das ist ein optischer Sensor
                    case "HmIP-SWDO-PL-2":              // STATE Variable ist Integer, 3 Varianten
                    case "HmIP-SWDM":                   // magnetischer Sensor
                    case "HmIP-SRH":                    // Kontakt der den Türgriff erfasst, 3 Positionen, Integer
                        $result="Tuerkontakt";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HMIP-eTRV":
                    case "HmIP-eTRV-B":
                    case "HmIP-eTRV-2":
                    case "HmIP-eTRV-B-2 R4M":           // Stellmotor Heizung, neueste Variante, schräges Display
                    case "HmIP-eTRV-F":                 // die mit dem Paperwhite Display
                    case "HM-CC-RT-DN":
                        $result="Stellmotor";
                        $matrix=[0,2,1,1,2,1,1,1];                        
                        break;

                    case "HmIP-SPI":                    // Presencemelder, PRESENCE_DETECTION_STATE
                    case "HmIP-SMI":
                    case "HM-Sec-MDIR":
                    case "HM-Sec-MDIR-2":
                    case "HM-Sen-MDIR-O-2":  
                    case "HM-Sen-MDIR-O":
                        $result="Bewegungsmelder";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-SMI55-2":                // Einbaurahmen Bewegungsmelder, MOTION; ILLUMINATION, BUTTON und ein paar mehr
                        $result="Bewegungsmelder mit Taster";
                        $matrix=[0,2,1,2,1,1,1,1];                        
                        break;

                    case "HM-TC-IT-WM-W-EU":
                        $result="Wandthermostat";
                        $matrix=[0,2,2,1,1,1,1,1];                        // die Homematic Variante hat zwei Kanäle
                        break;

                    case "HMIP-WTH":
                    case "HmIP-WTH-1":                  // Homematic IP Smart Home Wandthermostat HmIP-WTH-1 mit Luftfeuchtigkeitssensor (nur ein rad)
                    case "HmIP-WTH-2":                  // Homematic IP Smart Home Wandthermostat HmIP-WTH-2 mit Luftfeuchtigkeitssensor (nur ein rad)
                    case "HmIP-WTH-B":                  // Homematic IP Smart Home Wandthermostat – basic HmIP-WTH-B  (3 Tasten + boost -)
                        $result="Wandthermostat";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;
                        
                    case "HM-LC-Sw1-FM":
                    case "HM-LC-Sw1-Pl":
                    case "HM-LC-Sw1-Pl-2":
                    case "HM-LC-Sw1-Pl-DN-R1":
                        $result="Schaltaktor 1-fach";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-LC-Sw4-DR":
                    case "HM-LC-Sw4-DR-2":
                        $matrix=[0,2,2,2,2,1,1,1];                        
                        $result="Schaltaktor 4-fach";
                        break;

                    case "HmIP-DRSI4":                                  // Hat 4 Taster :1 bis :4, 4 Schalter :5,:9,:13,:17
                        $matrix=[0,2,2,2,2,2,1,1,1,2,1,1,1,2,1,1,1,2,1,1,1,1,1];
                        $result="Schaltaktor mit Input 4-fach";
                        break;
                    
                    case "HM-ES-PMSw1-Pl":
                    case "HM-ES-PMSw1-DR":                                  // die Hutschienen Variante dazu 
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,2,1,1,1,1,1];
                        break;
                    
                    case "HMIP-PSM":
                    case "HmIP-PSM-2 QHJ":                      // neue Type
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,1,2,1,1,2,1,1];
                        break;

                    case "HmIP-FSM16":                                      // Einbauvariante mit Energiemessung
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,1,2,1,2,1,1];
                        break;

                    case "HM-LC-Dim1T-FM":
                    case "HM-LC-Dim1T-Pl":
                    case "HM-LC-Dim1L-Pl":
                        $result="Dimmer 1-fach";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-PDT":
                        $result="Dimmer 1-fach";                            // wie HomematicIP Steckdosen Schalter, aber mit Dimmer und ohne Energiemessung
                        $matrix=[0,2,1,2,1,1,1,1];                        
                        break;

                    case "HM-LC-Bl1-FM":
                        $result="Rolladensteuerung";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-RGBW":
                        $result="RGBW Steuerung";
                        $matrix=[0,2,1,1,1,1];                        
                        break;

                    case "HM-WDS10-TH-O":
                    case "HM-WDS40-TH-I":
                    case "HmIP-STHO":                                   // sieht aus wie ein Lichtsensor, ist für den Aussenbereich
                    case "HmIP-STHD":                                   // sieht aus wie ein Thermostat und hat auch versteckte Thermostatfunktionen
                        $result="Temperatur und Feuchtigkeitssensor";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-WDS100-C6-O":
                    case "HmIP-SWO-PR":                    
                    case "HmIP-SWO-PL":                             // ohne Windrichtungserfassung
                        $result="Wetterstation";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-RCV-50":
                    case "HM-RCV-50":
                        $result="Receiver for internal messages, 50 channels";
                        $matrix=[0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];            // alle Tasten ignorieren             
                        break;

                    case "HM-RC-19-SW":
                        $result="RemoteControl, 19 channels";
                        $matrix=[0,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2];                        
                        break;

                    case "HMIP-SLO":
                    case "HmIP-SLO":
                        $result="Helligkeitssensor";
                        $matrix=[0,2,1,1,1,1,1,1];      // Standard Matrix, Infos in Kanal 1
                        break;                        

                    case "HMIP-DLD":
                    case "HmIP-DLD":                        // unterschiedliche Schreibweise
                        $result="Tuerschloss";                  
                        $matrix=[0,2,1,1,1,1,1,1];      // Standard Matrix, Infos in Kanal 1
                        break;

                    case "HmIP-CCU3":
                        $result="CCU Performance";                  
                        $matrix=[2];                // Infos in Kanal 0
                        break;

                    case "-":
                        echo "getHomematicHMDevice: $instanz ".IPS_GetName($instanz)."/".IPS_GetName(IPS_GetParent($instanz))." Gerät wurde gelöscht. Bitte auch manuell in IP Symcon loeschen.\n";
                        return(false);          // nicht hier weiter machen
                    default:
                        echo "getHomematicHMDevice: $instanz ".IPS_GetName($instanz)."/".IPS_GetName(IPS_GetParent($instanz)).", result is default, not known case key of \"".$this->HomematicAddressesList[$key]."\" for key $key.\n";
                        return ($this->HomematicAddressesList[$key]);
                        break;
                    }
                if ($output == 1) return($result);
                else return($matrix);               // wenn $output 2 ist
                }
            }
		else 
            {
            echo "getHomematicHMDevice , Instanz $instanz Key $key in HMI Report NICHT gefunden. Run HMI_CreateReport again. Ensure that Gateway is correctly configured to get updated values.\n";
            return("");
            }
		}	


    } /* ende class DeviceManagement */

class DeviceManagement_FS20 extends DeviceManagement
	{


    /*****************************************************
    *
    * HM_TYPE für FS20, FS20EX oder FHT Instanz feststellen
    *
    * anhand einer FS20, FS20EX oder FHT Instanz ID ermitteln 
    * um welchen Typ von Gerät es sich handeln koennte,
    * es wird nur HM_TYPE_BUTTON, HM_TYPE_SWITCH, HM_TYPE_DIMMER, HM_TYPE_SHUTTER unterschieden
    *
    *******************************************************************/

    function getFS20Type($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        //print_r($cids);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        //print_r($homematic);
        /* 	define ('HM_TYPE_LIGHT',					'Light');
        define ('HM_TYPE_SHUTTER',					'Shutter');
        define ('HM_TYPE_DIMMER',					'Dimmer');
        define ('HM_TYPE_BUTTON',					'Button');
        define ('HM_TYPE_SMOKEDETECTOR',			'SmokeDetector');
        define ('HM_TYPE_SWITCH',					'Switch'); */
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            switch ($homematic[0])
                {
                case "ERROR":
                    //echo "Funk-Tür-/Fensterkontakt\n";
                    break;
                case "INSTALL_TEST":
                    if ($homematic[1]=="PRESS_CONT")
                        {
                        //echo "Taster 6fach\n";
                        }
                    else
                        {
                        //echo "Funk-Display-Wandtaster\n";
                        }
                    $type="HM_TYPE_BUTTON";
                    break;
                case "ACTUAL_HUMIDITY":
                    //echo "Funk-Wandthermostat\n";
                    break;
                case "ACTUAL_TEMPERATURE":
                    //echo "Funk-Heizkörperthermostat\n";
                    break;
                case "BRIGHTNESS":
                    //echo "Funk-Bewegungsmelder\n";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        //echo "Dimmer\n";
                        $type="HM_TYPE_DIMMER";						
                        }
                    else
                        {
                        //echo "Rolladensteuerung\n";
                        }
                    break;
                case "PROCESS":
                case "INHIBIT":
                    //echo "Funk-Schaltaktor 1-fach\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "BOOT":
                    //echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "CURRENT":
                    //echo "Energiemessung\n";
                    break;
                case "HUMIDITY":
                    //echo "Funk-Thermometer\n";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        //echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        //echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        //echo "IP Funk-Schaltaktor\n";
                        $type="HM_TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    //echo "unknown\n";
                    //print_r($homematic);
                    break;
                }
            }
        else
            {
            //echo "   noch nicht angelegt.\n";
            }			

        return ($type);
        }

    /*********************************
     *
     * gibt für eine FS20 Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     ***********************************************/

    function getFS20DeviceType($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            if (strpos($homematic[0],"(") !== false) 	$auswahl=substr($homematic[0],0,(strpos($homematic[0],"(")-1));
            else $auswahl=$homematic[0];
            echo "Auf ".$auswahl." untersuchen.\n";
            switch ($auswahl)
                {
                case "ERROR":
                    echo "Funk-Tür-/Fensterkontakt\n";
                    $type="TYPE_CONTACT";
                    break;
                case "Gerät":
                    echo "Funk-Display-Wandtaster\n";
                    $type="TYPE_BUTTON";
                    break;
                case "Batterie":
                    echo "Funk-Wandthermostat\n";
                    $type="TYPE_THERMOSTAT";
                    break;
                case "ACTIVE_PROFILE":
                    if ($homematic[15]=="VALVE_ADAPTION")
                        {
                        echo "Stellmotor\n";
                        $type="TYPE_ACTUATOR";
                        }
                    else
                        {
                        echo "Wandthermostat (IP)\n";
                        $type="TYPE_THERMOSTAT";
                        }
                    break;
                case "ACTUAL_TEMPERATURE":
                    echo "Funk-Heizkörperthermostat\n";
                    $type="TYPE_ACTUATOR";
                    break;
                case "ILLUMINATION":
                case "BRIGHTNESS":
                    echo "Funk-Bewegungsmelder\n";
                    $type="TYPE_MOTION";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        echo "Dimmer\n";
                        $type="TYPE_DIMMER";						
                        }
                    else
                        {
                        echo "Rolladensteuerung\n";
                        }
                    break;
                case "Daten":
                    echo "Funk-Schaltaktor 1-fach\n";
                    $type="TYPE_SWITCH";
                    break;
                case "BOOT":
                    echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="TYPE_SWITCH";
                    break;
                case "CURRENT":
                    echo "Energiemessung\n";
                    $type="TYPE_METER_POWER";
                    break;
                case "HUMIDITY":
                    echo "Funk-Thermometer\n";
                    $type="TYPE_METER_TEMPERATURE";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        echo "IP Funk-Schaltaktor\n";
                        $type="TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    echo "unknown\n";
                    print_r($homematic);
                    break;
                }
            }
        else
            {
            echo "   noch nicht angelegt.\n";
            }			
        return ($type);
        }



    } /* ende class DeviceManagement_FS20 */

/* Hardware spezifische Device management Class
 * für Homeematic wenig Routinen bereits rausgelöst
 *
 * verwendete functions
 *      construct
 *      HomematicFehlermeldungen            den Status der HomematicCCU auslesen, alle Fehlermeldungen
 *      showHomematicFehlermeldungen        Ausgabe Fehler Status als html für Webfront
 *      showHomematicFehlermeldungenLog
 *      updateHomematicErrorLog
 *
 */
class DeviceManagement_Homematic extends DeviceManagement
	{

    function __construct($debug=false)
        {
        parent::__construct($debug);
        
		$categoryId_DeviceManagement    = IPS_GetObjectIDByName('DeviceManagement',$this->CategoryIdData);
        $this->HMI_ReportStatusID       = IPS_GetObjectIDByName("HMI_ReportStatus",$categoryId_DeviceManagement);
        if ($debug) echo "DeviceManagement_Homematic: found category DeviceManagement $categoryId_DeviceManagement mit dem HMI_ReportStatus : ".$this->HMI_ReportStatusID."\n";

        $this->getHomematicSerialNumberList();

        if ($debug) echo "ModulHandling aufrufen:\n";
		$modulhandling = new ModuleHandling();
		$this->HMIs=$modulhandling->getInstances('HM Inventory Report Creator');	

        if ($debug) echo "getHomematicAddressList aufrufen:\n";
        $this->HomematicAddressesList=$this->getHomematicAddressList(false,$debug,true);         // false für no Create Report, nimmt die HMIs aus der class, kommt in einen eigenen Timer, true wenn kein echo für eine Zusammenfassung erforderlich ist

        }

    /* DeviceManagement_Homematic::HomematicFehlermeldungen
     *
     * den Status der HomematicCCU auslesen, alle Fehlermeldungen
     *
     * funktioniert für CCU2 und CCU3
     * alle echo Meldungen werden im String alleHM_errors gesammelt
     *
     * Parameter mode
     *      Array       $arrHM2_Errors
     *      true        $arrHM_Errors
     *      false
     *
     **************/

    function HomematicFehlermeldungen($mode=false, $debug=false)
	    {
        if ($debug) 
            {
            echo "HomematicFehlermeldungen für die Ausgabe der aktuellen Fehlermeldungen der Homematic Funkkommunikation aufgerufen. Ausgabeart : ";
            if ($mode) echo "Array\n";
            else "Text\n";
            }
		$alleHM_Errors="\nAktuelle Fehlermeldungen der Homematic Funkkommunikation:\n";
        $arrHM_Errors=array();
        $arrHM2_Errors=array();
		$texte = Array(
		    "CONFIG_PENDING" => "Konfigurationsdaten stehen zur Übertragung an",
		    "LOWBAT" => "Batterieladezustand gering",
		    "STICKY_UNREACH" => "Gerätekommunikation war gestört",
		    "UNREACH" => "Gerätekommunikation aktuell gestört"
			);

		$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
		$HomInstanz=sizeof($ids);
		if($HomInstanz == 0)
		    {
		    //die("Keine HomeMatic Socket Instanz gefunden!");
		    $alleHM_Errors.="  ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
            if ($mode===false) return (false);     // nicht so wichtig nehmen, es müssen nicht unbedingt Homematic Geräte installiert sein   , leeres Array als Rückgabewert ausreichend , wenn mode nicht false        
		    }
		else
		    {
			/* Homematic Instanzen vorhanden, sind sie aber auch aktiv ? */
			$aktiv=false;
            if ($debug) echo "  Es wurden insgesamt $HomInstanz CCUs gefunden.\n";            
            $alleHM_Errors.="  Es wurden insgesamt $HomInstanz CCUs gefunden.\n";
			foreach ($ids as $id)
	   		    {
				$HM_Config=IPS_GetConfiguration($id);
				if ($debug) echo "      Homematic Socket : ".IPS_GetName($id)."  Konfig : ".$HM_Config."\n";
                $CCUconfig=json_decode($HM_Config);
                //print_r($CCUconfig);
				if ( $CCUconfig->Open==false )
				    {
                    if ($debug) echo "               Homematic Socket ID ".$id." / ".IPS_GetName($id)."   -> im IO Socket Homematic Funk nicht aktiviert.\n";
					$alleHM_Errors.="\nHomematic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.\n";
                    $arrHM_Errors[$id]["ErrorMessage"]="Homematic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.";
                    $arrHM2_Errors[$id]["ErrorMessage"]="Homematic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.";
					}
				else
				    {
            		$ccu_name=IPS_GetName($id);
                    if ($debug) echo "   work on $ccu_name ($id).\n";
            		if (isset($this->HomematicSerialNumberList[$ccu_name])) $alleHM_Errors.="\nHomatic Socket ID ".$id." / ".$ccu_name."   ".sizeof($this->HomematicSerialNumberList[$ccu_name])." Endgeräte angeschlossen.\n";  
                    else 
                        {
                        echo "        CCU not found in HomematicSerialNumberList:\n";
                        print_r($this->HomematicSerialNumberList);
                        }
                    $msgs = @HM_ReadServiceMessages($id);
					if($msgs === false)
					    {
						//die("Verbindung zur CCU fehlgeschlagen");
					    $alleHM_Errors.="  ERROR: Verbindung zur CCU fehlgeschlagen!\n";
                        echo "  ERROR: Verbindung zur CCU $id fehlgeschlagen!\n";
                        $HM_Status=IPS_GetInstance($id);
                        //print_r($HM_Status);
                        if ($HM_Status["InstanceStatus"] != 102) echo "    Instanz $id nicht aktiv.\n";
                        print_r($HM_Config);
                        $arrHM_Errors[$id]["ErrorMessage"]="Verbindung zur CCU $id fehlgeschlagen";
                        $arrHM2_Errors[$id]["ErrorMessage"]="Verbindung zur CCU $id fehlgeschlagen";
					    }
					if ($msgs != Null)
						{
                        if ($debug) { echo "    Messages : "; print_R($msgs); }
						if(sizeof($msgs) == 0)
						    {
							//echo "Keine Servicemeldungen!\n";
					   	    $alleHM_Errors.="OK, keine Servicemeldungen!\n";
							}
                        //else echo "Insgesamt sind es ".sizeof($msgs)." Servicemeldungen auf $ccu_name.\n";
                        //print_r($msgs);
						foreach($msgs as $msg)
						    {
				   		    if(array_key_exists($msg['Message'], $texte))
								{
      					  	    $text = $msg['Address']."   ".$texte[$msg['Message']]."(".$msg['Value'].")";
                                $text2 = $texte[$msg['Message']];    
		   					    }
							else
								{
	      	  				    $text = $msg['Address']."   ".$msg['Message']."(".$msg['Value'].")";
                                $text2 = $msg['Message'];    
			        			}
						    $HMID = GetInstanceIDFromHMID($msg['Address']);
					    	if(IPS_InstanceExists($HMID))
							 	{
        						$name = IPS_GetLocation($HMID);
					   		    }
							else
								{
			      	  		    $name = "Gerät nicht in IP-Symcon eingerichtet";
    							}
			  				//echo "Name : ".$name."  ".$msg['Address']."   ".$text." \n";
						  	$alleHM_Errors.="  NACHRICHT : ".str_pad($name,60)."  $text \n";
                            $arrHM_Errors[$HMID]["ErrorMessage"]="$name $text";
                            $arrHM2_Errors[$HMID]["ErrorMessage"][]=$text2;
                            $arrHM2_Errors[$HMID]["Address"]=$msg['Address'];							
                            $arrHM2_Errors[$HMID]["Location"]=$name;							
                            $arrHM2_Errors[$HMID]["CCU"]=$ccu_name;							
                            }
						}
                    elseif ($debug) 
                        {
                        echo "    Messages Null, warum ? \n";
                        $msgs = HM_ReadServiceMessages($id);
                        }
					}
				}
			}
        if ($debug) echo $alleHM_Errors;
		if ($mode=="Array") return($arrHM2_Errors);
		elseif ($mode) return($arrHM_Errors);
        else return($alleHM_Errors);
    	}

    /* showHomematicFehlermeldungen, Ausgabe Fehler Status als html für Webfront
     * Umgestellt auf neues Format mit detaillierteren Ergebnissen, mehr Spalten
     */

    function showHomematicFehlermeldungen($arrHM_Errors,$debug=false)
	    {
        if ($debug) echo "showHomematicFehlermeldungen aufgerufen.\n";
        $html = '';  
        $html.='<style>';             
        $html.='.statyHm table,td {align:center;border:1px solid white;border-collapse:collapse;}';
        $html.='.statyHm table    {table-layout: fixed; width: 100%; }';
        //$html.='.statyHm td:nth-child(1) { width: 60%; }';                        // fixe breiten, sehr hilfreich
        //$html.='.statyHm td:nth-child(2) { width: 15%; }';
        //$html.='.statyHm td:nth-child(3) { width: 15%; }';
        //$html.='.statyHm td:nth-child(4) { width: 10%; }';
        $html.='</style>';        
        $html.='<table class="statyHm">';              
        foreach ($arrHM_Errors as $oid=>$entry) 
            {
            if ( (isset($entry["ErrorMessage"])) && (is_array($entry["ErrorMessage"])===false) )
                {
                unset($arrHM_Errors[$oid]["ErrorMessage"]);
                $arrHM_Errors[$oid]["ErrorMessage"][]=$entry["ErrorMessage"];                  // wird nur eine Zeile werden
                }
            }
        foreach ($arrHM_Errors as $oid=>$entry) 
            {
            if ($debug) echo "$oid ".json_encode($entry)."  \n";
            //print_r($entry);
            $html .= '<tr>';
            if (isset($entry["CCU"])) $html .= '<td>'.$entry["CCU"].'</td>';
            if (isset($entry["Address"])) $html .= '<td>'.$entry["Address"].'</td>';
            if (isset($entry["Location"])) 
                {
                $location = $entry["Location"];
                $name=$location; $dir="";
                $posSlash=strrpos($location,'/');
                $posBackSlash=strrpos($location,'\\');
                if ($posSlash !== false)  
                    {
                    //echo $posSlash;
                    $name=substr($location,$posSlash+1);
                    $dir=substr($location,0,$posSlash+1);
                    }
                elseif ($posBackSlash !== false)  
                    {
                    //echo $posBackSlash;
                    $name=substr($location,$posBackSlash+1);
                    $dir=substr($location,0,$posBackSlash+1);
                    }
                $html .= '<td>'.$dir.'<br>'.$name.'</td>';
                }
            $html .= '<td>';
            $text="";
            foreach ($entry["ErrorMessage"] as $num => $message) 
                {
                $html .= $message.'<br>';
                $text .= $message."   ";
                }
            if ($debug) echo $text."\n";
            $html .= '</td></tr>';               
            }
        $html .= '</table>';
        return($html);
        }

    /* showHomematicFehlermeldungenLog, Ausgabe Fehler Log als html für Webfront
     *
     */

    function showHomematicFehlermeldungenLog($storedError_Log,$debug=false)
	    {
        if ($debug) echo "showHomematicFehlermeldungenLog aufgerufen.\n";
        $html = '';  
        $html.='<style>';             
        $html.='.statyHm table,td {align:center;border:1px solid white;border-collapse:collapse;}';
        $html.='.statyHm table    {table-layout: fixed; width: 100%; }';
        //$html.='.statyHm td:nth-child(1) { width: 60%; }';                        // fixe breiten, sehr hilfreich
        //$html.='.statyHm td:nth-child(2) { width: 15%; }';
        //$html.='.statyHm td:nth-child(3) { width: 15%; }';
        //$html.='.statyHm td:nth-child(4) { width: 10%; }';
        $html.='</style>';        
        $html.='<table class="statyHm">'; 
        foreach ($storedError_Log as $oid=>$entry) 
            {
            if ( (isset($entry["Message"])) && (is_array($entry["Message"])===false) ) 
                {
                unset($storedError_Log[$oid]["Message"]);
                $storedError_Log[$oid]["Message"][]=$entry["Message"];                  // wird nur eine Zeile werden
                }
            }        
        foreach ($storedError_Log as $date => $entry)
            {
            if ($debug>1) echo "   $date ".json_encode($entry)."  \n";
            $html .= '<tr>';
            $text="";
            $text .= $date."    ".$entry["State"]."   ";
            if (isset($entry["DateTime"])) $html .= '<td>'.date("d.m.Y H:i",$entry["DateTime"]).'</td>';
            else $html .= '<td>'.$date.'</td>';
            $html .= '<td>'.$entry["State"].'</td>';
            if (isset($entry["CCU"])) $html .= '<td>'.$entry["CCU"].'</td>';
            else $html .= '<td></td>';
            if (isset($entry["Address"])) $html .= '<td>'.$entry["Address"].'</td>';
            else $html .= '<td></td>';
            if (isset($entry["Location"])) 
                {
                $location = $entry["Location"];
                $name=$location; $dir="";
                $posSlash=strrpos($location,'/');
                $posBackSlash=strrpos($location,'\\');
                if ($posSlash !== false)  
                    {
                    //echo $posSlash;
                    $name=substr($location,$posSlash+1);
                    $dir=substr($location,0,$posSlash+1);
                    }
                elseif ($posBackSlash !== false)  
                    {
                    //echo $posBackSlash;
                    $name=substr($location,$posBackSlash+1);
                    $dir=substr($location,0,$posBackSlash+1);
                    }
                $html .= '<td>'.$dir.'<br>'.$name.'</td>';
                }
            else $html .= '<td></td>';
            $html .= '<td>';
            foreach ($entry["Message"] as $num => $message) 
                {
                $text .= $message."   ";
                $html .= $message.'<br>';
                }
            if ($debug) echo $text."\n";
            $html .= '</td>';       
            $html .= '</tr>';    
            }
        $html .= '</table>';
        return ($html);
        }

    /* DeviceManagement_Homematic::updateHomematicErrorLog
     *
     * $filename ist eiugentlich immer auf 'EvaluateHardware_DeviceErrorLog.inc.php', 'IPSLibrary::config::modules::EvaluateHardware'
     */

    public function updateHomematicErrorLog($filename,$arrHM_Errors,$debug=false)
        {
        if ($debug) echo "updateHomematicErrorLog($filename,...) aufgerufen:\n";
        $dosOps = new dosOps();               
        $ipsOps = new ipsOps();
        if ($dosOps->fileAvailable($filename,$debug))
            {
            include $filename;
            //IPSUtils_Include ('EvaluateHardware_DeviceErrorLog.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');          // deviceList
            }
        elseif ($debug) echo "File $filename wird neu angelegt.\n";      
        $storedHM_Errors=array(); $storedError_Log=array();
        if (function_exists("get_DeviceErrorStatus")) $storedHM_Errors = get_DeviceErrorStatus();           // aus dem file nehmen, sind dort definiert, das ist der aktuelle Status
        if (function_exists("get_DeviceErrorLog")) $storedError_Log = get_DeviceErrorLog();                 // aus dem file nehmen, sind dort definiert, das ist das aktuelle Log mit Add und Delete

        $newHM_Errors = array();
        $today = time();

        if ($debug) echo "    Unterschied zu den gespeicherten Homematic Fehlermeldungen, insgesamt ".sizeof($storedHM_Errors).":\n";
        //print_R($storedHM_Errors);        
        foreach ($arrHM_Errors as $oid=>$entry)
            {
            if (isset($storedHM_Errors[$oid]))          // war vorher auch schon da
                {
                if ($storedHM_Errors[$oid]["ErrorMessage"]==$arrHM_Errors[$oid]["ErrorMessage"]) unset($storedHM_Errors[$oid]);
                } 
            else
                {
                $newHM_Errors[$oid] = $arrHM_Errors[$oid];      // kommt neu hinzu
                }
            }

        $i=0; 
        //$storedError_Log=array();

        foreach ($storedHM_Errors as $oid=>$entry)
            {
            $dateTime = date("YmdHis",($today+$i));
            $storedError_Log[$dateTime]["OID"]=$oid;
            $storedError_Log[$dateTime]["Message"]=$entry["ErrorMessage"];
            if (isset($entry["CCU"]))       $storedError_Log[$dateTime]["CCU"]=$entry["CCU"];
            if (isset($entry["Address"]))   $storedError_Log[$dateTime]["Address"]=$entry["Address"];
            if (isset($entry["Location"]))  $storedError_Log[$dateTime]["Location"]=$entry["Location"];
            $storedError_Log[$dateTime]["State"]="DELETE";
            $storedError_Log[$dateTime]["DateTime"]=$today;
            $i++;
            }
        if ($debug) 
            {
            echo "    Diese Meldungen sind weggefallen, insgesamt ".sizeof($storedHM_Errors).":\n";
            if ($debug>1) print_R($storedHM_Errors);
            echo "    Diese Meldungen sind neu dazugekommen, insgesamt ".sizeof($newHM_Errors).":\n";
            if ($debug>1) print_R($newHM_Errors);
            }            
        foreach ($newHM_Errors as $oid=>$entry)
            {
            $dateTime = date("YmdHis",($today+$i));              // wie ein zeitbasierter Index, für die Zeit der Erfassung in die Spalte schauen  
            $storedError_Log[$dateTime]["OID"]=$oid;
            $storedError_Log[$dateTime]["Message"]=$entry["ErrorMessage"];
            if (isset($entry["CCU"]))       $storedError_Log[$dateTime]["CCU"]=$entry["CCU"];
            if (isset($entry["Address"]))   $storedError_Log[$dateTime]["Address"]=$entry["Address"];
            if (isset($entry["Location"]))  $storedError_Log[$dateTime]["Location"]=$entry["Location"];
            $storedError_Log[$dateTime]["State"]="ADD";
            $storedError_Log[$dateTime]["DateTime"]=$today;
            $i++;
            }  
        //print_R($storedError_Log);   
        krsort($storedError_Log);
        $shortError_Log = array_slice($storedError_Log, 0, 1000, true);             // auf 1.000 Eintraege begrenzen

        $statusDevices     = '<?php'."\n";             // für die php Devices and Gateways, neu
        $statusDevices     .= '/* This file has been generated automatically by EvaluateHardware on '.date("d.m.Y H:i:s").".\n"; 
        $statusDevices     .= " *  \n";
        $statusDevices     .= " * Please do not edit, file will be overwritten on a regular base.     \n";
        $statusDevices     .= " *  \n";
        $statusDevices     .= " */    \n\n";
        $statusDevices .= "function get_DeviceErrorStatus() { return ";
        $ipsOps->serializeArrayAsPhp($arrHM_Errors, $statusDevices,0,0,true);        // gateway array in das include File schreiben
        $statusDevices .= ';}'."\n\n";        
        $statusDevices .= "function get_DeviceErrorLog() { return ";
        $ipsOps->serializeArrayAsPhp($storedError_Log, $statusDevices);        // gateway array in das include File schreiben
        $statusDevices .= ';}'."\n\n";        
        $statusDevices .= "\n".'?>';
        //if (false)      // kein Update der Datei
            {
            if (!file_put_contents($filename, $statusDevices)) 
                {
                throw new Exception('Create File '.$filename.' failed!');
                } 
            }

        return($shortError_Log);

        }


    } /* ende class DeviceManagement_Homematic */

/**************************************************
 * Hardware spezifische Device management Class
 * verwendet Hue Modul und daraus die Rückmeldung aus dem ConfigurationForm, dieses etwas umständlich auswerten
 *
 * für Hue typische Routinen rauslösen
 *      analyse ConfigurationForm
 *
 */
class DeviceManagement_Hue extends DeviceManagement
	{

    protected $itemslist=array(); 

    public function __construct($debug)
        {
        if ($debug) echo "DeviceManagement_Hue construct started.\n";
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        $oids = $modulhandling->getInstances('HUEConfigurator');   //   HUEDiscovery  0
        if (isset($oids[0])) 
            {
            $oid=$oids[0];
            $config=json_decode(IPS_GetConfigurationForm($oid),true);
            if ($debug) echo "Instance of Configurator for Library Philips HUE found: $oid  ".IPS_GetName($oid)."\n";
            $actions = $this->lookforkeyinarray($config,"actions",$debug>2);
            $values  = $this->lookforkeyinarray($actions,"values",$debug>2);
            if ($values) $this->createItemlist($values,$debug>1);                                //erzeugt itemlist
            }
        parent::__construct($debug>1);                       // wenn ein eigenes construct dann auch das übergeordnete aifrufen
        }
    
    public function lookforitems($oid,$debug=false)
        {
            $config=json_decode(IPS_GetConfigurationForm($oid),true);
            if ($debug) echo "Instance of Configurator for Library Philips HUE found: $oid  ".IPS_GetName($oid)."\n";
            $actions = $this->lookforkeyinarray($config,"actions",$debug>2);
            $values  = $this->lookforkeyinarray($actions,"values",$debug>2);
            //if ($values) $this->createItemlist($values,$debug>1);              
            return ($values);
        }


    /* DeviceManagement_Hue::lookforkeyinarray jetzt auch in Modulhandling
     * einen besonderen key finden, liese sich auch recursive anstellen
     */
    private function lookforkeyinarray($config,$key,$debug)
        {
        $actions=false;
        
        if (is_array($config))
            {
            foreach ($config as $type => $item)
                {
                if ($debug) 
                    {
                    echo "    ".str_pad($type,23)." | ";
                    if (is_array($item)) echo sizeof($item);
                    echo "\n";  
                    }
                if ($type==$key) $actions=$item; 
                if (is_array($item)) foreach ($item as $index => $entry) 
                    {
                    if ($debug) 
                        {                    
                        echo "        ".str_pad($index,20)." | ";
                        if (is_array($entry)) echo sizeof($entry);
                        echo "\n"; 
                        }
                    if ($index==$key) $actions=$entry; 
                    if ($index=="de") ;                             // Schönheitskorrektur für mehr Übersichtlichkeit
                    elseif (is_array($entry)) foreach ($entry as $topic => $subentry)
                        {
                        if ($debug) 
                            {                         
                            echo "           ".str_pad($topic,17)." | ";
                            if (is_array($subentry)) echo sizeof($subentry);
                            echo "\n";   
                            }
                        if ($topic==$key) $actions=$subentry;
                        }  
                    }
                }
            }
        else echo "lookforkeyinarray, warning, no array provided.\n";
        return $actions;
        }

    /* DeviceManagement_Hue::createItemlist
     * die itemlist erzeugen, Basis sind die values aus dem configurationForm
     * sucht nach einer instanceID, sonst ist die Instanz nicht angelegt, diese mal so komplett in der itemList abspeichern
     * zusätzliche Auswertung über ganzes Array:
     *             Type     aus dem Wert den Wert für TypeDev ableiten
     */
    private function createItemlist($values,$debug) 
        {
        if ($debug) echo "createItemlist for ".sizeof($values)." items called.\n";
        foreach ($values as $itemIndex => $itemConfig)
            {
            if (isset($itemConfig["instanceID"]))
                {
                $instanceID=$itemConfig["instanceID"];
                $this->itemslist[$instanceID] = $itemConfig;   
                }
            }
        foreach ($this->itemslist as $oid => $entry)
            {
            if ($debug) echo str_pad($oid,12).str_pad($entry["DisplayName"],34).str_pad($entry["Type"],34)."\n";
            switch ($entry["Type"])
                {
                case "Extended color light":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_RGB";
                    break;
                case "Color temperature light":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_AMBIENT";
                    break;
                case "Dimmable light":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_DIMMER";
                    break;
                case "Room":
                case "Zone":            // eine Gruppierung, aber wahrscheinlich örtlich abgegrenzt
                    // damit kann die Topologie abgeglichen werden
                    //print_r($entry);
                    break;
                case "Daylight":
                case "LightGroup":              // ein alter Begriff
                    break;
                case "ZLLSwitch":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_BUTTON";            // am Register ButtonEvent kann man erahnen welche Taste wie gedrückt wurde
                    break;
                default:
                    echo "      DeviceManagement_Hue::createItemlist, warning, do not know key ".$entry["Type"]."\n";
                    print_r($entry);
                    break;
                }
            }                    
        }

    /* DeviceManagement_Hue
     * variable ist proteced, daher jhier eine nette Anzeige bauen
     */
    public function showItemlist()
        {
        print_r($this->itemslist);
        }

    /* DeviceManagement_Hue::getHueDeviceType
     *
     * gibt für eine Philips Hue Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     * Routine ermittelt alle Children eines Objektes und übergibt sie als array zur Prüfung
     * ruft HueDeviceType auf, es gibt verschieden Ausgabeformate
     *   0   Beispiel  "Bewegungsmelder";
     *   1   Beispiel  "Funk-Bewegungsmelder";
     *   2   Beispiel  TYPE_MOTION
     *   3   { "Type"=>TYPE_MOTION,"Register"=> $resultReg[0],"RegisterAll"=>  }
     *   4
     *
     *  0/false ist Default
     *
     *
     *
     */
    function getHueDeviceType($instanz, $outputVersion=false, $config=array(), $debug=false)
	    {
        if ($debug) echo "             getHueDeviceType : $instanz  \"".IPS_GetName($instanz)."\" Modus : $outputVersion\n";
	    $homematic=array();
    	$cids = IPS_GetChildrenIDs($instanz);
        $register=array();
        foreach($cids as $cid) $register[strtoupper(IPS_GetName($cid))]=$cid;
    	//foreach($cids as $cid) $homematic[$cid]=IPS_GetName($cid);
    	//return ($this->HueDeviceType($homematic,$outputVersion, $config, $debug));
        return ($this->HueDeviceType($register,$outputVersion, $config, $debug));
    	}

    /* DeviceManagement_Hue::HueDeviceType
     * Hue Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen, gemeinsam für Hue und HueV2 
     * die Register werden wie bei Homematic übergeben, allerdings mit dem Namen in Grossbuchstaben als Key
     * zusättlich wird die Config der Instanz mit übergeben, bei Hue gibt es mehr Informationen die ausgewertet werden können
     * 
     * nach der Auswertung wird $resultType[0] mit dem DeviceType beschrieben.
     * 
     * erkannte Device Typen (unabhängig ob Homematic, Evaluierung von oben nach unten
     *  TYPE_ACTUATOR               => VALVE_STATE
     *  TYPE_THERMOSTAT             => ACTIVE_PROFILE || WINDOW_OPEN_REPORTING
     *  TYPE_METER_TEMPERATURE      => TEMPERATURE && HUMIDITY
     *  TYPE_METER_HUMIDITY         => HUMIDITY
     *  TYPE_BUTTON                 => PRESS_SHORT
     *  TYPE_SWITCH                 => STATE && (PROCESS || WORKING)
     *  TYPE_AMBIENT
     *  TYPE_RGBW
     *  TYPE_CONTACT                => STATE
     *  TYPE_DIMMER                 => LEVEL && DIRECTION && ERROR_OVERLOAD
     *  TYPE_SHUTTER                => LEVEL && DIRECTION
     *  TYPE_MOTION                 => MOTION
     *  TYPE_RSSI                   => RSSI
     *  TYPE_METER_POWER
     *  TYPE_POWERLOCK
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false,0   die aktuelle Kategorisierung, also $resultType[$i]
     *
     * abhängig vom Gerätetyp bzw. den Instanzeigenschaften werden für die Instanz die Register jeweils mit Typ und Parameter ermittelt
     *      $resultType[i] = "TYPE_METER_TEMPERATURE";            
     *      $resultReg[i]["TEMPERATURE"]="TEMPERATURE";
     *      $resultReg[i]["HUMIDITY"]="HUMIDITY";
     *
     *
     *
     *
     */
    private function HueDeviceType($register, $outputVersion=false, $entry, $debug=false)
        {
        $i=0; $devicetype=false; $found=false; $resultType=array();
        if (is_array($entry["OID"])) return (false);                                // es gibt keine mehreren Instanzen
        //echo "                HueDeviceType aufgerufen für ".$entry["OID"]."\n";
        //print_R($this->itemslist);
        if ( (isset($entry["OID"])) && (isset($this->itemslist[$entry["OID"]])) )
            {
            $config=$this->itemslist[$entry["OID"]];
            //echo "                     ".json_encode($config)."\n";
            if ($debug>1) echo "getDeviceParameters::HUE,function HueDeviceType, called for instance ".$entry["OID"]." : Config ".json_encode($config);
            if (isset($config["TypeDev"])) 
                {
                $resultType[$i]= $config["TypeDev"];
                $found=true;
                }
            elseif ($debug>1)
                {
                echo "  , TypeDev in Config nicht gefunden.";
                //print_r($config);
                } 
            if ($debug>1) echo "\n";
            //if ($found) echo "                   getDeviceParameters:HUE, HueDeviceType, Hue device ".$entry["NAME"]." available. ".$config["TypeDev"]."\n";
            }
        if ($found==false)
            {
            $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
            if (isset($result["DeviceType"])) $devicetype=$result["DeviceType"];
            if ($debug>1) echo "getDeviceParameters:HUE, HueDeviceType, called for instance ".$entry["OID"]."  ".$entry["CONFIG"]." with devicetype $devicetype\n";
            }
        if ($devicetype)            // nur ufgerufen wenn found false und DeviceType set in Config
            {
            //echo "getDeviceParameters:HUE, HueDeviceType, analyse devicetype $devicetype.\n";
            switch (strtoupper($devicetype))
                {
                case "LIGHTS":
                    $found=true;
                    print_r($register);
                    $resultType[$i]="TYPE_SWITCH";         // kann groups, lights, sensor  mehr Unterschied gibts nicht, wir wollen es genauer wissen
                    if (isset($register["STATUS"])) $typedevRegs["STATE"]=IPS_GetName($register["STATUS"]);

                    if (isset($register["FARBMODUS"]))                                          // es gibt das Register Farbmodus und das kann man auslesen
                        {
                        $modus = GetValueIfFormatted($register["FARBMODUS"]);
                        //echo "getDeviceParameters:HUE ".str_pad($name,22)." : ".$modus."    ".json_encode($result)."\n";
                        if (strtoupper($modus)=="FARBTEMPERATUR") 
                            {
                            $resultType[$i]="TYPE_AMBIENT";
                            } 
                        else            // schwierige Unterscheidung zwischen Dimmer und RGB
                            {
                            if ( (isset($register["FARBE"])) && (GetValue($register["FARBE"])==0) ) $resultType[$i]="TYPE_DIMMER";
                            }
                        }
                    //echo "               getDeviceParameters:HUE, HueDeviceType, Hue Device ".$entry["NAME"]." available. ".$resultType[$i]."\n";
                    break;
                case "GROUPS":
                    $resultType[$i]="TYPE_GROUP";
                    if ($debug) echo "  getDeviceParameters:HUE, HueDeviceType, Hue Group  available. Childrens are : ".json_encode($register)."\n";
                    $found=true;
                    if (isset($register["FARBMODUS"]))                                          // es gibt das Register Farbmodus und das kann man auslesen
                        {
                        $modus = GetValueIfFormatted($register["FARBMODUS"]);
                        //echo "getDeviceParameters:HUE ".str_pad($name,22)." : ".$modus."    ".json_encode($result)."\n";
                        if (strtoupper($modus)=="FARBTEMPERATUR") $resultType[$i]="TYPE_AMBIENT"; 
                        else            // schwierige Unterscheidung zwischen Dimmer und RGB
                            {
                            if ( (isset($register["FARBE"])) && (GetValue($register["FARBE"])==0) ) $resultType[$i]="TYPE_DIMMER";
                            }
                        }
                    //echo "               getDeviceParameters:HUE, HueDeviceType, Hue Group ".$entry["NAME"]." available. ".$resultType[$i]." \n";                        
                    break;
                case "SENSORS":                         // Hue Sensoren, das sind zum Beispiel Taster und Bewegungsmelder
                    $found=true;
                    $resultType[$i]="TYPE_SENSOR";
                    echo "               getDeviceParameters:HUE, HueDeviceType, Hue Sensor ".$entry["NAME"]." available. ".$resultType[$i]." .\n";
                    print_r($register);
                    break;                                           
                }
            }


        if (isset($resultType[$i]))
            {
            $typedev = $resultType[$i];
            $typedevRegs=array();
            $cids = IPS_GetChildrenIDs($entry["OID"]);           // für jede Instanz die Children einsammeln
            $register=array();
            if ($debug>1) echo "                  $typedev : ";         // Typedev Ausgabe
            $first=true;
            foreach($cids as $cid)
                {
                $regName=IPS_GetName($cid);
                if ($debug>1) 
                    {
                    if ($first) $first=false;
                    else echo ",";
                    echo $regName;
                    }
                $register[]=$regName;
                switch ($typedev)
                    {
                    case "TYPE_SWITCH":
                    case "TYPE_GROUP":
                        if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                        break;
                    case "TYPE_DIMMER":
                        if ($regName=="Status")     $typedevRegs["STATE"]=$regName;
                        if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                        break;
                    case "TYPE_AMBIENT":
                        if ($regName=="Status")         $typedevRegs["STATE"]=$regName;
                        if ($regName=="Helligkeit")     $typedevRegs["LEVEL"]=$regName;
                        if ($regName=="Farbtemperatur") $typedevRegs["AMBIENCE"]=$regName;
                        break;
                    case "TYPE_RGB":                                                        // keine Ahnung wie es zu diesem Type kommt
                        if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                        if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                        if ($regName=="Farbe") $typedevRegs["COLOR"]=$regName;                      // muss aber nicht stimmen
                        break;
                    case "TYPE_BUTTON":
                        if ($regName=="Letztes Ereignis") $typedevRegs["EVENTCODE"]=$regName;           // als String wenn formattiert
                        break;  
                    case "TYPE_BRIGHTNESS":
                        if ($regName=="Lichtpegel") $typedevRegs["BRIGHTNESS"]=$regName;           // als String wenn formattiert
                        break;   
                    case "TYPE_MOTION":
                        if ($regName=="Bewegung") $typedevRegs["MOTION"]=$regName;           // als String wenn formattiert
                        break;         
                    case "TYPE_TEMPERATURE":
                        if ($regName=="Temperatur") $typedevRegs["TEMPERATURE"]=$regName;           // als String wenn formattiert
                        break;                                                                      
                    case "TYPE_SENSOR":
                        break;
                    }
                $resultReg[$i]=$typedevRegs;                     
                sort($register);
                $registerNew=array();
                $oldvalue="";        
                /* gleiche Einträge eliminieren */
                foreach ($register as $index => $value)
                    {
                    if ($value!=$oldvalue) {$registerNew[]=$value;}
                    $oldvalue=$value;
                    }                     
                }
            if ($debug>1) echo "\n";
            //print_R($typedevRegs);
            }

        if (false)           // loeschen, nur als Referenz
            {
            /* register in registernew umkopieren, dabei alle Einträge sortieren und gleiche, doppelte Einträge entfernen */
            sort($register);
            $registerNew=array();
            $oldvalue="";        
            /* gleiche Einträge eliminieren */
            foreach ($register as $index => $value)
                {
                if ($value!=$oldvalue) {$registerNew[]=$value;}
                $oldvalue=$value;
                }         
            $found=true; 
            if ($debug) echo "             HomematicDeviceType: Info mit Debug aufgerufen. Parameter ".json_encode($registerNew)."\n";

            /*--Stellmotor-----------------------------------*/
            if ( array_search("VALVE_STATE",$registerNew) !== false)            /* Stellmotor */
                {
                //print_r($registerNew);
                //echo "Stellmotor gefunden.\n";
                if (array_search("ACTIVE_PROFILE",$registerNew) !== false) 
                    {
                    $result[1]="IP Funk Stellmotor";
                    }
                else 
                    {
                    $result[1]="Funk Stellmotor";
                    }                         
                $result[0]="Stellmotor";   
                $i=0;                            
                $resultType[$i]="TYPE_ACTUATOR";
                if (array_search("LEVEL",$registerNew) !== false)           // di emodernere Variante
                    {
                    $resultReg[$i]["VALVE_STATE"]="LEVEL"; 
                    if (array_search("SET_POINT_TEMPERATURE",$registerNew) !== false)$resultReg[$i]["SET_TEMPERATURE"]="SET_POINT_TEMPERATURE";
                    }
                else 
                    {
                    $resultReg[$i]["VALVE_STATE"]="VALVE_STATE";
                    if (array_search("SET_TEMPERATURE",$registerNew) !== false)$resultReg[$i]["SET_TEMPERATURE"]="SET_TEMPERATURE";                
                    }
                if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_TEMPERATURE";
                    $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                    }
                }
            /*-----Wandthermostat--------------------------------*/
            elseif ( (array_search("ACTIVE_PROFILE",$registerNew) !== false) || (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false) )   /* Wandthermostat */
                {
                if (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false)
                    {
                    $result[1]="Funk Wandthermostat";
                    }
                else 
                    {
                    $result[1]="IP Funk Wandthermostat";
                    }
                $result[0] = "Wandthermostat";
                $i=0;
                $resultType[$i]="TYPE_THERMOSTAT";
                if (array_search("SET_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_TEMPERATURE";
                if (array_search("SET_POINT_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_POINT_TEMPERATURE";
                if (array_search("TargetTempVar",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="TargetTempVar";
                //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
                if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                    {
                    $i++;
                    $resultType[$i]= "TYPE_METER_TEMPERATURE";
                    $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                    }
                if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_HUMIDITY";
                    $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                    }
                if (array_search("HUMIDITY",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_HUMIDITY";
                    $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                    }
                }                    
            /*-----Temperatur Sensor--------------------------------*/
            elseif ( (array_search("TEMPERATURE",$registerNew) !== false) && (array_search("HUMIDITY",$registerNew) !== false) )   /* Temperatur Sensor */
                {
                $result[1] = "Funk Temperatursensor";
                $result[0] = "Temperatursensor";
                $i=0;
                $resultType[$i] = "TYPE_METER_TEMPERATURE";            
                $resultReg[$i]["TEMPERATURE"]="TEMPERATURE";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY";
                if (array_search("HUMIDITY",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_HUMIDITY";
                    $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                    }
                }                    
            /*------Taster-------------------------------*/
            elseif (array_search("PRESS_SHORT",$registerNew) !== false) /* Taster */
                {
                $anzahl=sizeof(array_keys($register,"PRESS_SHORT")); 
                if (array_search("INSTALL_TEST",$registerNew) !== false) 
                    {
                    $result[1]="Funk-Taster ".$anzahl."-fach";
                    }
                else 
                    {
                    $result[1]="IP Funk-Taster ".$anzahl."-fach";
                    }
                $result[0]="Taster ".$anzahl."-fach";
                $resultType[0] = "TYPE_BUTTON";            
                if (array_search("PRESS_SHORT",$registerNew) !== false) $resultReg[0]["PRESS_SHORT"]="PRESS_SHORT";
                if (array_search("PRESS_LONG",$registerNew) !== false) $resultReg[0]["PRESS_LONG"]="PRESS_LONG";
                if ($debug) echo "-----> Taster : ".$resultType[0]." ".json_encode($registerNew).json_encode($resultReg[0])."\n";
                }
            /*-------Schaltaktor oder Kontakt------------------------------*/
            elseif ( array_search("STATE",$registerNew) !== false) /* Schaltaktor oder Kontakt */
                {
                //print_r($registerNew);
                $anzahl=sizeof(array_keys($register,"STATE"));                     
                if ( (array_search("PROCESS",$registerNew) !== false) || (array_search("WORKING",$registerNew) !== false) )     // entweder PROCESS oder WORKING gefunden
                    {
                    $result[0]="Schaltaktor ".$anzahl."-fach";
                    if ( (array_search("BOOT",$registerNew) !== false) || (array_search("LOWBAT",$registerNew) !== false) )     //entweder Boot oder LOWBAT gefunden
                        {
                        $result[1]="Funk-Schaltaktor ".$anzahl."-fach";
                        }
                    /* "SECTION_STATUS" ist bei den neuen Schaltern auch dabei. Die neuen HomematicIP Schalter geben den Status insgesamt dreimal zurück, Selektion mus ich wohl wo anders machen */
                    else    
                        {
                        $result[1]="IP Funk-Schaltaktor ".$anzahl."-fach";
                        }
                    if (array_search("ENERGY_COUNTER",$registerNew) !== false) 
                        {
                        $result[0] .= " mit Energiemesung";
                        $result[1] .= " mit Energiemesung";
                        }
                    $resultType[0] = "TYPE_SWITCH";            
                    $resultReg[0]["STATE"]="STATE";
                    }
                else 
                    {
                    $result[0] = "Tuerkontakt";
                    $result[1] = "Funk-Tuerkontakt";
                    $resultType[0] = "TYPE_CONTACT";            
                    $resultReg[0]["CONTACT"]="STATE";                
                    }
                }
            /*-----RGBW Ansteuerung --------------------------------*/
            elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("LEVEL_STATUS",$registerNew) !== false) && ( array_search("HUE",$registerNew) !== false) )/* RGBW Ansteuerung */
                {
                $result[0] = "RGBW";
                $result[1] = "Funk-RGBW";
                $resultType[0] = "TYPE_RGBW"; 
                $resultReg[0]["LEVEL"]="LEVEL";                       
                $resultReg[0]["HUE"]="HUE";                       
                $resultReg[0]["SATURATION"]="SATURATION";
                $resultReg[0]["COLOR_TEMPERATURE"]="COLOR_TEMPERATURE";
                }
            /*-----Dimmer--------------------------------*/
            elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) && ( array_search("ERROR_OVERLOAD",$registerNew) !== false) )/* Dimmer */
                {
                //print_r($registerNew);                
                $result[0] = "Dimmer";
                $result[1] = "Funk-Dimmer";
                $resultType[0] = "TYPE_DIMMER"; 
                $resultReg[0]["LEVEL"]="LEVEL";                       
                }                    
            /*-------------------------------------*/
            elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("LEVEL_STATUS",$registerNew) !== false) )/* HomematicIP Dimmer */
                {
                //print_r($registerNew);                
                $result[0] = "Dimmer";
                $result[1] = "Funk-Dimmer";
                $resultType[0] = "TYPE_DIMMER"; 
                $resultReg[0]["LEVEL"]="LEVEL";                       
                }         
            /*------Rolladensteuerung-------------------------------*/
            elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) )                   /* Rollladensteuerung/SHUTTER */
                {
                //print_r($registerNew);                
                $result[0] = "Rollladensteuerung";
                $result[1] = "Funk-Rollladensteuerung";
                $resultType[0] = "TYPE_SHUTTER";    
                $resultReg[0]["HEIGHT"]="LEVEL";              // DIRECTION INHIBIT LEVEL WORKING
                }                    
            /*-------Bewegung------------------------------*/
            elseif ( array_search("MOTION",$registerNew) !== false) /* Bewegungsmelder, Durchgangssensor ist weiter unten */
                {
                //print_r($registerNew);    
                $result[0] = "Bewegungsmelder";
                $result[1] = "Funk-Bewegungsmelder";
                $resultType[0] = "TYPE_MOTION";            
                $resultReg[0]["MOTION"]="MOTION";
                if ( array_search("BRIGHTNESS",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="BRIGHTNESS";
                if ( array_search("ILLUMINATION",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="ILLUMINATION";
                }
            elseif ( array_search("PRESENCE_DETECTION_STATE",$registerNew) !== false) /* Presaenzmelder  */
                {
                //print_r($registerNew);    
                $result[0] = "Bewegungsmelder";
                $result[1] = "Funk-Bewegungsmelder";
                $resultType[0] = "TYPE_MOTION";            
                $resultReg[0]["MOTION"]="PRESENCE_DETECTION_STATE";
                if ( array_search("BRIGHTNESS",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="BRIGHTNESS";
                if ( array_search("ILLUMINATION",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="ILLUMINATION";
                }            
            /*-------RSSI------------------------------*/
            elseif ( array_search("RSSI_DEVICE",$registerNew) !== false) /* nur der Empfangswert */
                {
                $result[0] = "RSSI Wert";
                if ( array_search("DUTY_CYCLE",$registerNew) !== false) $result[1] = "IP Funk RSSI Wert";
                else $result[1] = "Funk RSSI Wert";
                $resultType[0] = "TYPE_RSSI";             
                $resultReg[0]["RSSI"] = "";
                }            
            /*-------Energiemessgerät------------------------------*/
            elseif ( array_search("CURRENT",$registerNew) !== false) /* Messgerät */
                {
                $result[0] = "Energiemessgeraet";
                if ( array_search("BOOT",$registerNew) !== false) $result[1] = "Funk Energiemessgeraet";
                else $result[1] = "IP Funk Energiemessgeraet";
                $resultType[0] = "TYPE_METER_POWER";             
                if (array_search("ENERGY_COUNTER",$registerNew)) $resultReg[0]["ENERGY"]="ENERGY_COUNTER";                   // diese Register werden zur Verfügung gestellt und regelmaessig ausgewertet
                if (array_search("POWER",$registerNew)) $resultReg[0]["POWER"]="POWER";  
                }          
            /*-------Helligkeitssensor------------------------------*/
            elseif ( array_search("CURRENT_ILLUMINATION",$registerNew) !== false)     /* Helligkeitssensor */
                {
                $result[0] = "Helligkeitssensor";
                $result[1] = "IP Funk Helligkeitssensor";
                $resultType[0] = "TYPE_METER_CLIMATE";             
                $resultReg[0]["BRIGHTNESS"]="CURRENT_ILLUMINATION";          
                }
            /*-----Wetterstation--------------------------------*/
            elseif  (array_search("RAIN_COUNTER",$registerNew) !== false)    /* neue HomematicIP Wetterstation  */
                {
                $result[0] = "Wetterstation";
                $result[1]="Funk Wetterstation";

                $i=0;
                $resultType[$i]="TYPE_METER_CLIMATE";
                $resultReg[$i]["RAIN_COUNTER"]="RAIN_COUNTER";
                $resultReg[$i]["RAINING"]="RAINING";
                $resultReg[$i]["WIND_SPEED"]="WIND_SPEED";
                if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i]= "TYPE_METER_TEMPERATURE";
                    $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                    if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY";           //Homematic
                    elseif (array_search("HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="HUMIDITY";                     //HomematicIP 
                    }
                if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_HUMIDITY";
                    $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                    }
                elseif (array_search("HUMIDITY",$registerNew) !== false) 
                    {
                    $i++;
                    $resultType[$i] = "TYPE_METER_HUMIDITY";
                    $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                    }
                }
            /*-------------Durchgangssensor ["CURRENT_PASSAGE_DIRECTION","LAST_PASSAGE_DIRECTION","PASSAGE_COUNTER_OVERFLOW","PASSAGE_COUNTER_VALUE"]-------*/
            elseif  (array_search("CURRENT_PASSAGE_DIRECTION",$registerNew) !== false)    /* neue HomematicIP Durchgangserkennung  */
                {
                $result[0] = "Durchgangsmelder";
                $result[1]="IP Funk Durchgangsmelder";              // HomematicIP 

                $i=0;                                               // kann auch weitere Funktionen beinhalten
                $resultType[$i]="TYPE_MOTION";
                $resultReg[$i]["COUNTER"]="PASSAGE_COUNTER_VALUE";
                $resultReg[$i]["DIRECTION"]="CURRENT_PASSAGE_DIRECTION";
                $resultReg[$i]["LAST_DIRECTION"]="LAST_PASSAGE_DIRECTION";
                }                      
            /*-------Tuerschloss  ["ACTIVITY_STATE","LOCK_STATE","PROCESS","SECTION","SECTION_STATUS","WP_OPTIONS"]--------------------------------*/
            elseif  (array_search("ACTIVITY_STATE",$registerNew) !== false)    /* HomematicIP Tuerschloss, Aktuator WP_OPTIONS 0,1,2 Status LOCK_STATE  */
                {
                $result[0] = "Tuerschloss";
                $result[1]="IP Funk Tuerschloss";              // HomematicIP 

                $i=0;                                               // kann auch weitere Funktionen beinhalten
                $resultType[$i]="TYPE_POWERLOCK";
                $resultReg[$i]["LOCKSTATE"]="LOCK_STATE";
                $resultReg[$i]["KEYSTATE"]="WP_OPTIONS";                // Aktuator
                }                      
            /*-------CCU3  ["DUTY_CYCLE_LEVEL"]--------------------------------*/
            elseif  (array_search("DUTY_CYCLE_LEVEL",$registerNew) !== false)    /* HomematicIP CCU3 Performance  */
                {
                $result[0] = "CCU";
                $result[1]="IP Funk CCU";              // HomematicIP 

                $i=0;                                               // kann auch weitere Funktionen beinhalten
                $resultType[$i]="TYPE_CCU";
                $resultReg[$i]["DUTY_CYCLE_LEVEL"]="DUTY_CYCLE_LEVEL";
                }                      
            /*-------CCU  ["DUTY_CYCLE","CONNECTED"] von HomematicExtended--------------------------------*/
            elseif  ( (array_search("DUTY_CYCLE",$registerNew) !== false)   && ( array_search("CONNECTED",$registerNew) !== false) )  /* HomematicExtended CCU Parameter, getrennt für RF und HmIP   */
                {
                $result[0] = "CCU";
                $result[1]="IP Funk CCU";              // HomematicIP 

                $i=0;                                               // kann auch weitere Funktionen beinhalten
                $resultType[$i]="TYPE_CCU";
                $resultReg[$i]["DUTY_CYCLE_LEVEL"]="DUTY_CYCLE";
                }                      
            else 
                {
                $found=false;
                if ($debug)
                    { 
                    echo "             HomematicDeviceType: kein bekanntes Muster für ein Gerät entdeckt. Wirklich so schwierig ?\n";
                    print_r($registerNew);
                    }
                }
            /* result[0] und result[1] wurden bereits geschrieben, hier result[2], result[3] und result[4] ergänzen 
            * result[2] ist der resultType also TYPE_METER_POWER
            * result[3] ist für die deviceList, "Type" ist resultType, "Register" ist resultReg
            * 
            */
            }

        if ($found) 
            {
            $result=array();
            $result[2]                = $resultType[0];

            //result 3 hat TYPE                                                                
            $result[3]["Type"]        = $resultType[0];                     // sowas wie TYPE_BUTTON
            $result[3]["RegisterAll"] = $registerNew;                       // alle register die angelegt wurden
            $result[3][$typedev]        = $typedevRegs;                     // und die die für eine Funktion relevant sind
            //$result[3]["Register"]    = $resultReg[0];

            // result 4 hat TYPECHAN
            $result[4]["TYPECHAN"]    = "";
            $first=true;
            foreach ($resultType as $index => $type)            // normalerweise wird nur [0] befüllt, wenn mehrere Register Sets verfügbar auch mehrere
                {
                if ($first) $first=false;
                else $result[4]["TYPECHAN"] .= ",";
                $result[4]["TYPECHAN"] .= $type;
                $result[4][$type]   = $resultReg[$index];                  // 4 mit mehreren type
                }
            $result[4]["RegisterAll"] = $registerNew;

            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) 
                {       
                /* bei Output Version 4 mehrere TYPECHANs zulassen 
                if ($resultType[0]=="TYPE_ACTUATOR")
                    {
                    if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }
                elseif ($resultType[0]=="TYPE_THERMOSTAT")
                    {
                    //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
                    if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }*/
                return ($result[4]);
                } 
            else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }

        }

    }

/**************************************************
 * Hardware spezifische Device management Class
 * verwendet HueV2 Modul und daraus die Rückmeldung aus dem ConfigurationForm, dieses etwas umständlich auswerten
 *
 * Grundsaetzlich ähnlicher Umgang mit Discovery und Configurator für alle Hardware Komponenten:
 *    Discovery listet alle Bridges auf 
 *    Configurator (einer oder mehrere) alle Gerätekonfigurationen einer Bridge
 * Zugang zu den Details der Konfigurationen von Configuratoren und Discovery Modulen nur über ConfigurationForms
 *
 * HueV2 liefert mehr Informationen und es soll nur mehr dieses Modul verwendet werden
 * es sind noch jede Menge alternativer HueDevices im Umlauf, diese in IPS_Heat durch die neuen Gerätetypen übernehmen
 *
 *
 * die Auswertung ist noch umständlicher, keine vollständige Erkennung möglich
 *
 * für HueV2 typische Routinen rauslösen
 *      analyse ConfigurationForm
 *
 */
class DeviceManagement_HueV2 extends DeviceManagement_Hue
	{

    public function __construct($debug)
        {
        //$debug=2;
        if ($debug) echo "DeviceManagement_HueV2 construct started.\n";
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        $oids = $modulhandling->getInstances(["{52399872-F02A-4BEB-ACA0-1F6AE04D9663}","{943D4F07-294C-4FFC-98E1-82E78D3B4584}","{2024E794-672B-49E2-894D-ED04414D8061}","{2DCB7BB9-4634-4419-AE68-C0CC771547E5}"]);        // es gibt 4 Configuratioren Device, Room, Scene, Zone
        $result=array();
        foreach ($oids as $oid)
            {
            if ($debug) echo "Instance of Configurator for Library Philips HUE V2 found: $oid  ".IPS_GetName($oid)."\n";
            $values=$this->lookforitems($oid);
            $result=array_merge($result,$this->analyseConfigStructure($values,$debug>1));
            }
        $this->createItemlist($result,$debug>1);                                //erzeugt itemlist
        //$this->showItemlist();
        DeviceManagement::__construct($debug>1);                       // wenn ein eigenes construct dann auch das übergeordnete aufrufen
        }

    /* DeviceManagement_HueV2::analyseConfigStructure jetzt auch in Modulhandling
     * Values in der ConfigurationForms gefunden, jetzt analysieren, die ConfigurationForms ist mit parent als hierarchische Struktur aufgebaut
     */
    private function analyseConfigStructure($values,$debug=false)
        {
        $result=array();
        if ($debug) echo "analyseConfigStructure \n";
        foreach ($values as $id => $value)
            {
            $itemId=$value["id"];
            if (isset($value["parent"])==false)     // nur root 
                {
                if ($debug) echo "$id   ID : $itemId  ";                                        // Ausgabe zB 0  ID : 1  Stehlampe     Hue white lamp
                if ( (isset($value["name"])) && ($value["name"] != "")  ) 
                    {
                    $name=$value["name"];
                    if ($debug) echo $value["name"]."     ";
                    //print_r($value);
                    }
                else $name=$id;
                if ( (isset($value["Productname"])) && ($value["Productname"] != "")  )             // Type wird entweder Productname Hue Motion Sensor oder Type device
                    {
                    $type=$value["Productname"];
                    if ($debug) echo $type."     ";
                    //print_r($value);
                    }
                else $type=$value["Type"];

                if ( (isset($value["instanceID"])) && ((int)$value["instanceID"]>0) ) 
                    {
                    if ($debug) echo "  OID:  ".$value["instanceID"]."     ";
                    //print_r($value);
                    }
                //if (isset($value["instanceID"])) echo "  OID:  ".$value["instanceID"];
                foreach ($values as $idx => $child)          // looking for childrens
                    {
                    if ( (isset($child["parent"])) && ($child["parent"]==$itemId) ) 
                        {
                        //print_r($child);
                        if ($debug) echo "\n    $idx  ".$child["Type"]." ";
                        if ( (isset($child["instanceID"])) && ((int)$child["instanceID"]>0) ) 
                            {
                            if ($debug) echo "  OID:  ".$child["instanceID"]."     ";
                            $deviceOID=$child["instanceID"];
                            $result[$deviceOID]["instanceID"]=$deviceOID;              // geht sonst bei merge verloren
                            $result[$deviceOID]["name"]=$name;
                            $result[$deviceOID]["Type"]=$type;
                            $result[$deviceOID]["TypeChild"]=$child["Type"];
                            $found=$id;
                            //print_r($value);
                            }
                        }
                    }
                if ($debug) echo "\n";
                }
            }
        return($result);
        }

    /* DeviceManagement_HueV2::createItemlist
     * die itemlist erzeugen, Basis sind die values aus dem configurationForm
     * sucht nach einer instanceID, sonst ist die Instanz nicht angelegt, diese mal so komplett in der itemList abspeichern
     * zusätzliche Auswertung über ganzes Array:
     *             Type     aus dem Wert den Wert für TypeDev ableiten
     * es werden immer neue Gerätetypen erkannt, kontinuierlich erweitern:
     *          Hue filament bulb
     *
     */
    private function createItemlist($values,$debug) 
        {
        //$debug=true;
        if ($debug) echo "createItemlist Hue V2 for ".sizeof($values)." items called.\n";
        foreach ($values as $itemIndex => $itemConfig)
            {
            if (isset($itemConfig["instanceID"]))
                {
                $instanceID=$itemConfig["instanceID"];
                $this->itemslist[$instanceID] = $itemConfig;   
                }
            }
        foreach ($this->itemslist as $oid => $item)
            {
            //echo "    $oid \n";
            switch ($item["Type"])
                {
                case "Hue lightstrip plus":
                case "Hue bloom":
                case "Hue Play":
                case "Extended color light":
                case "Hue color lamp":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_RGB";
                    break;
                case "Hue ambiance lamp":
                case "Hue ambiance spot":
                case "Hue filament bulb":               // die grosse Lampe, kann dimmen und ambient, TypeChild Light
                    $this->itemslist[$oid]["TypeDev"]="TYPE_AMBIENT";
                    break;
                case "Hue white lamp":              // wenn typeChild definiert und nicht light aufpassen   
                case "Hue white candle":
                case "Hue dimmer lamp":
                    if (isset($item["TypeChild"]))
                        {
                        if ($item["TypeChild"]=="light")  $this->itemslist[$oid]["TypeDev"]="TYPE_DIMMER";
                        }
                    else $this->itemslist[$oid]["TypeDev"]="TYPE_DIMMER";
                    break;
                case "Hue dimmer switch":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_BUTTON";
                    break;
                case "Dimmable light":                  // Ikea Actuator Driver
                    $this->itemslist[$oid]["TypeDev"]="TYPE_DIMMER";
                    break;
                case "room":
                case "zone":            // eine Gruppierung, aber wahrscheinlich örtlich abgegrenzt
                    // damit kann die Topologie abgeglichen werden
                    //print_r($entry);
                    break;
                case "Hue motion sensor":
                    //echo "Hue motion sensor, $oid ".json_encode($item)."\n";
                    switch ($item["TypeChild"])
                        {
                        case "motion":
                            $this->itemslist[$oid]["TypeDev"]="TYPE_MOTION";
                            break;
                        case "light_level":
                            $this->itemslist[$oid]["TypeDev"]="TYPE_BRIGHTNESS";
                            break;
                        case "temperature":
                            $this->itemslist[$oid]["TypeDev"]="TYPE_TEMPERATURE";
                            break;       
                        default:                     
                            echo "createItemlist, warning, do not know key \"".$item["Type"]."\" with child type \"".$item["TypeChild"]."\"\n";
                            break;
                        }                    
                    break;
                case "Hue Bridge":              // hat einen bewegungssensor ??? ist aber die Bridge
                    break;                    
                case "Daylight":
                case "LightGroup":              // ein alter Begriff
                    break;
                case "Hue Smart button":
                    $this->itemslist[$oid]["TypeDev"]="TYPE_BUTTON";            // am Register ButtonEvent kann man erahnen welche Taste wie gedrückt wurde
                    break;
                default:
                    echo "createItemlist, warning, do not know key \"".$item["Type"]."\"\n";
                    print_r($item);
                    break;
                }    
            }                    
        }



    }




/****************************************************/


?>