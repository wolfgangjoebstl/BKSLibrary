<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Auslesung von Energiewerten. Diese Script übernimmt die Webfrontvariablen Bearbeitung und wird zusaetzlich zu Testzwecken weiterhin verwendet
	 * Regelmaessiger Aufruf wird jetzt von MomentanwerteAbfragen uebernommen. Die Antwort eines AMIS Zähler wird automatisch von AMIS Cutter bearbeitet sobald der Wert verfügbar ist
     *
	 * Diese Routine kann als Einstieg verwendet werden um einen Überblick über die Installation zu bekommen
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 4.0 13.6.2016
*/

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

	}
else
	{	

	/******************************************************

				INIT

	*************************************************************/
	
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $installedModules = $moduleManager->GetInstalledModules();
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$amis=new Amis();           // Ausgabe SystemDir, erstellt MeterConfig
    echo "\n";
	$MeterConfig = $amis->getMeterConfig();
	//print_r($MeterConfig);

    if (isset($installedModules["OperationCenter"]))
        {
        echo "Modul OperationCenter installiert. Qualität des Inventory evaluieren.\n";
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');            
        $DeviceManager = new DeviceManagement();
        echo "--------------------------------\n";
        $result=$DeviceManager->updateHomematicAddressList();
        if ($result) echo "    --> Alles in Ordnung.\n";
        else "Fehler HMI_CreateReport muss schon wieder aufgerufen werden.\n";
        echo "\n";
        }

    if (isset($installedModules["EvaluateHardware"]))
        {
        echo "Modul EvaluateHardware installiert. Ausführliches Inventory vorhanden.\n";
        IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
        IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');           // sonst werden die Event Listen überschrieben
        IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

        echo "========================================================================\n";    
        $hardwareTypeDetect = new Hardware();
        $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php
        if (false)
            {
            echo "Statistik der Devicelist nach Typen, Aufruf der getDeviceStatistics in HardwareLibrary:\n";
            $statistic = $hardwareTypeDetect->getDeviceStatistics($deviceList,false);                // false keine Warnings ausgeben
            print_r($statistic);
            echo "========================================================================\n";    
            echo "Statistik der Register nach Typen:\n";
            $statistic = $hardwareTypeDetect->getRegisterStatistics($deviceList,false);                // false keine Warnings ausgeben
            $hardwareTypeDetect->writeRegisterStatistics($statistic);        
            }
        $deviceListFiltered = $hardwareTypeDetect->getDeviceListFiltered(deviceList(),["TYPECHAN" => "TYPE_METER_POWER"],"Install");     // true with Debug, Install hat keinen Einfluss mehr, gibt nur mehr das
        //print_r($deviceListFiltered);
        $powerMeter=array();
        $energyMeter=array();
        foreach ($deviceListFiltered as $name => $entry)
            {
            foreach ($entry["Instances"] as $index => $instance)
                {
                if ($instance["TYPEDEV"]=="TYPE_METER_POWER") 
                    {
                    $powerMeter[$instance["OID"]]["NAME"]=$instance["NAME"];
                    $powerMeter[$instance["OID"]]["REGISTER_NAME"]=$entry["Channels"][$index]["TYPE_METER_POWER"]["ENERGY"];
                    $childrens=IPS_GetChildrenIDs($instance["OID"]);
                    foreach ($childrens as $children)
                        {
                        if (IPS_GetName($children)==$powerMeter[$instance["OID"]]["REGISTER_NAME"]) 
                            {
                            $powerMeter[$instance["OID"]]["REGISTER_OID"] = $children;
                            $energyMeter[$children]=$instance["NAME"];
                            }
                        }
                    //print_R($childrens); foreach ($childrens as $children) echo IPS_getName($children)."  "; echo "\n";
                    }
                }
            }
        //print_r($energyMeter);
        $energyMeterAll=$energyMeter;
        $powerMeterAll=$powerMeter;
        $energyMeterName=array();
        echo"-------------------------------------------------------------\n";
        echo "Analysing the AMIS Meter Configuration:\n";                                   // Die Konfiguration durchgehen, bekannte Register löschen und schauen was am Ende noch da ist
        foreach ($MeterConfig as $identifier => $meter)
            {
            if (strtoupper($meter["TYPE"])=="HOMEMATIC")
                {
                $variableID = $amis->getWirkenergieID($meter);      // kurze Ausgabe suche nach found as
                echo " ".str_pad($meter["NAME"],35).IPS_GetName($meter["OID"])." Konfig : ".json_encode($meter)."     $variableID ".IPS_GetName($variableID)."\n";
                $oid=$meter["OID"];
                if (isset($powerMeter[$meter["OID"]])) 
                    {
                    //print_r($meter);
                    $oid=$powerMeter[$meter["OID"]]["REGISTER_OID"];
                    unset($powerMeter[$meter["OID"]]);
                    }
                if (isset($energyMeter[$oid])) 
                    {
                    $energyMeterName[$oid]=$meter["NAME"];
                    unset($energyMeter[$oid]);
                    }
                else echo "   --> unknown ".$meter["OID"]." ".IPS_GetName($meter["OID"])."\n"; 

                }
            }
        //echo"-------------------------------------------------------------\n";
        //print_r($energyMeter);
        echo"-------------------------------------------------------------\n";
        echo "Register marked with *** is not in the AMIS configuration.\n";
        foreach ($energyMeterAll as $oid => $register)
            {
            $props=IPS_GetVariable($oid);
            if (isset($energyMeter[$oid])) echo " *** $oid : ";
            else                           echo "     $oid : ";
            echo str_pad(IPS_GetName(IPS_GetParent($oid)),50).str_pad(GetValueIfFormatted($oid),20," ",STR_PAD_LEFT)."   ".date("d.m.Y H:i:s",$props["VariableChanged"])."      ";
            if (isset($energyMeterName[$oid])) echo $energyMeterName[$oid]."\n";
            else echo "\n";            
            }
        echo"-------------------------------------------------------------\n";
        } 


	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$configPort=array();

    echo"-------------------------------------------------------------\n";
	foreach ($MeterConfig as $identifier => $meter)
		{
		echo "Create Variableset for : ".str_pad($meter["NAME"],35)." Konfig : ".json_encode($meter)."\n";
		$ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		if ($meter["TYPE"]=="Amis")
		    {
		    $amismetername=$meter["NAME"];			
			echo "Amis Zähler, verfügbare Ports:\n";			
		
			$AmisID = CreateVariableByName($ID, "AMIS", 3);
			$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
			$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

			// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
			$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
			$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

			// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
			$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
			$variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2);
			
			//Hier die COM-Port Instanz
			$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
			foreach ($serialPortID as $num => $serialPort)
			   {
			   echo "Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";

				/********* COM Port ******************/				
			   if (IPS_GetName($serialPort) == $identifier." Serial Port") 
					{ 
					$com_Port = $serialPort;
					$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
					if (IPS_InstanceExists($regVarID) )
	   				{
						echo "     Registervariable : ".$regVarID."\n";
						$configPort[$regVarID]=$amismetername;							 
						}
					$config = IPS_GetConfiguration($com_Port);
					echo "Comport Serial aktiviert. Konfiguration: ".$config." \n";
					$stdobj = json_decode($config);
					$ergebnis=json_encode($stdobj);
					echo "      ede/encode zum Vergleich ".$ergebnis."\n";
					print_r($stdobj);	
					echo "Comport Status : ".$stdobj->Open."\n";
					$remove = array("{", "}", '"');
					$config = str_replace($remove, "", $config);
					$Config = explode (',',$config);
					$AllConfig=array();
					foreach ($Config as $configItem)
						{
						$items=explode (':',$configItem);
						$Allconfig[$items[0]]=$items[1];
						}
					print_r($Allconfig);
					if ($Allconfig["Open"]==false) 
						{
						COMPort_SetOpen($com_Port, true); //false für aus
						IPS_ApplyChanges($com_Port);
						}
					else
						{
						echo "Port ist offen.\n";
						}
					COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
					}
					
				/********* Bluetooth ******************/			
			   if (IPS_GetName($serialPort) == $identifier." Bluetooth COM") 
					{ 
					$com_Port = $serialPort; 
					$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
					if (IPS_InstanceExists($regVarID) )
	   					{
						echo "     Registervariable : ".$regVarID."\n";
						$configPort[$regVarID]=$amismetername;	 
						}
					$status=IPS_GetProperty($com_Port,"Open");
					if ($status==true)
						{
						$status=@COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
						if ($status==true)
							{
							echo "Comport Bluetooth aktiviert. \n";
							}
						else	
							{
							echo "Comport Bluetooth aktiv. Fehler beim Senden von Text. \n";
							}
						}
					else
						{
						echo "Comport Bluetooth nicht aktiviert. \n";						
						}	
					}
				}
			if (isset($com_Port) === false) { echo "Kein AMIS Zähler Serial Port definiert\n"; break; }
			else { echo "\nAMIS Zähler Serial Port auf OID ".$com_Port." definiert.\n"; }
			}
        //else echo "No configuration for AMIS Meter found.\n";
		//echo "\nZählerkonfiguration: \n";
		//print_r($meter);
		}

	echo "\nGenereller Meter Read eingeschaltet:".GetvalueFormatted($MeterReadID)."\n";
	if (isset($AmisReadMeterID)==true)
		{
		echo "AMIS Meter Read eingeschaltet:".GetvalueFormatted($AmisReadMeterID)." auf Com-Port : ".$com_Port."\n";
		}
	else
		{	
		echo "AMIS Meter Read ausgeschaltet.\n";
		}

	} // ende else Webfront Aufruf
	

if ($_IPS['SENDER'] == "Execute")
	{
	
	/******************************************************

				STATUS

	*************************************************************/

	//Hier die COM-Port Instanz
    echo "\n";
    echo "----------------------------------------------------\n";
	echo "--------Execute aufgerufen -------------------------\n";
    echo "----------------------------------------------------\n";    
    //echo "Data OID der AMIS Zusammenfassung : ".$amis->getAMISDataOids()."\n\n";
    $meterValues=$amis->writeEnergyRegistertoArray($MeterConfig, true);
    //print_R($meterValues);
    echo $amis->writeEnergyRegisterTabletoString($meterValues);
    echo "\n----------------------------------------------------\n";
    echo $amis->getEnergyRegister($meterValues,true);
    echo "\n----------------------------------------------------\n";
    echo $amis->writeEnergyRegistertoString($MeterConfig,true,true);            // output asl html (true) und mit debug (true), sehr lange Ausgabe
	echo "\nUebersicht Homematic Registers:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
        $amis->writeEnergyHomematic($meter,true);           // true für Debug, macht nette Ausgabe
        }

	echo "\nUebersicht serielle Ports:\n";
	foreach ($MeterConfig as $identifier => $meter)
		{	
		$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
		foreach ($serialPortID as $num => $serialPort)
			{
			if (IPS_GetName($serialPort) == $identifier." Serial Port") 
				{ 			
				echo "  Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
				$config = IPS_GetConfiguration($serialPort);
				echo "    ".$config."\n";
				
				/* Standard Verrechnungsdatensatz kurz Abfrage, da kommen einige Daten zurück  */
				$amis->sendReadCommandAmis($meter,$identifier,"F001");
				}
			}
		}	
	
	echo "\n";
	print_r($configPort);
	if (isset($AmisReadMeterID)==true)
		{	
		echo "----------------------\n";
		//SetValue($AMISReceiveCharID,"");
		echo GetValue($AMISReceiveCharID);
		echo "----------------------\n";	
		echo GetValue($AMISReceiveChar1ID);
		}
    
    echo "==================================\n";

    function processMeterTopology(&$meterTopology,&$meter,$ident="")
        {
        $result=false;
        foreach ($meterTopology as $name => $entry)
            {
            if ($name===$meter["PARENT"]) 
                {
                $result=true;
                if (isset($meterTopology[$name]["CHILDREN"][$meter["NAME"]])===false)  $meterTopology[$name]["CHILDREN"][$meter["NAME"]]["CONFIG"]=$meter;
                }
            else
                {
                if (isset($meterTopology[$name]["CHILDREN"]))
                    {
                    $result=processMeterTopology($meterTopology[$name]["CHILDREN"],$meter,$ident."   ");    
                    /*foreach ($meterTopology[$name]["CHILDREN"] as $nameSub => $entrySub)
                        {
                        if ( ($nameSub===$meter["PARENT"]) && (isset($meterTopology[$name]["CHILDREN"][$nameSub]["CHILDREN"][$meter["NAME"]])===false) ) $meterTopology[$name]["CHILDREN"][$nameSub]["CHILDREN"][$meter["NAME"]]["CONFIG"]=$meter;
                        }*/
                    }
                }
            }
        return($result);
        }

    function createMeterTopology(&$meterTopology,$MeterConfig,$debug=false)
        {
        $meterTopology=array();         // Topologie ergründen  Name => [ Children, Config ] , Children [ Name => [ Children, Config ]]
        $resultOverall=true;
        foreach ($MeterConfig as $identifier => $meter)
            {
            if ($debug) echo str_pad($meter["NAME"],30);
            if (strtoupper($meter["ORDER"])=="MAIN") 
                {
                if (isset($meterTopology[$meter["NAME"]])===false) $meterTopology[$meter["NAME"]]["CONFIG"]=$meter;   
                if ($debug) echo "Main    ".json_encode($meter);
                }
            else 
                {
                $result = processMeterTopology($meterTopology,$meter,"");
                if ($debug) echo $meter["ORDER"]."   ".$meter["PARENT"] ;
                if ($result == false) 
                    {
                    echo "Parent \"".$meter["PARENT"]." \" nicht gefunden, noch einmal probieren.\n";
                    $resultOverall=false;
                    }           
                }
            if ($debug) echo "\n";
            }
        return ($resultOverall);
        }

    function printMeterTopology($meterTopology, $meterValues, $ident="")
        {
	    $amis=new Amis();               
        $ipsOps=new ipsOps();
        foreach ($meterTopology as $name => $meter)
            {
            echo $ident.$name;
            foreach ($meterValues as $line => $entry)
                {
                if ( (isset($entry["Information"]["NAME"])) && ($entry["Information"]["NAME"]==$name) ) 
                    {
                    echo "  found";
                    $oid=$amis->getWirkleistungID($name);
                    //print_R($regs);
                    if ($oid !== false) 
                        {
                        echo "    ($oid) ".nf(getValue($oid),"kW");
                        $regs=$amis->getRegistersfromOID($name);     // geht auch mit Name
                        if (isset($regs["LeistungID"])) echo "  Homematic (".$regs["LeistungID"]."): ".GetValueIfFormatted($regs["LeistungID"]);
                        //echo " (".$ipsOps->path($oid).") ";
                        }
                    }
                }
            echo "\n";
            if (isset($meter["CHILDREN"]))
                {
                printMeterTopology($meter["CHILDREN"],$meterValues,$ident."   ");
                }
            }

        }



    $meterTopology=array(); 
    if (createMeterTopology($meterTopology,$MeterConfig)===false) createMeterTopology($meterTopology,$MeterConfig);     //zweimal aufrufen       
    //print_R($meterTopology);
    //print_R($meterValues);
    
    printMeterTopology($meterTopology,$meterValues);

    echo "=====================================================\n";
    echo "Archivierte Werte bearbeiten:\n";
    $archiveOps = new archiveOps(); 
    $archiveID = $archiveOps->getArchiveID();

    $config=array();
    $config["StartTime"]=strtotime("-10days");
    $config["manAggregate"]="daily";
    
    //$config["DataType"]="Array";
    //$config["StartTime"]=strtotime("1.1.2021");
    //$config["manAggregate"]="monthly";            // tägliche Werte, false geloggte Werte auslesen

    /*$config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
    $config["manAggregate"]="daily";            // tägliche Werte, false geloggte Werte auslesen
    $oid=$amis->getWirkleistungID("Kueche");                // Arbeitszimmer  */

    $config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
    $oid=$amis->getWirkenergieID("Weinkuehler");                // Arbeitszimmer Kueche
    if ($oid !== false)
        {
        echo "Ergebnis ist $oid (".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).") \n";
        $ergebnis = $archiveOps->getValues($oid,$config,true);                //1,2 für Debug, 2 mit Werte Ausgabe
        foreach ($ergebnis as $index => $entries) echo "$index \n";                 // index : Values, MeansRoll, Description 
        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug
        
        $config["Aggregated"]=3;            // monatliche Werte, false geloggte Werte auslesen
        $config["manAggregate"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $config["OIdtoStore"]=$oid+1;
        $ergebnis = $archiveOps->getValues($oid,$config,true);                //1,2 für Debug, 2 mit Werte Ausgabe
        //$archiveOps->showValues($ergebnis["Values"],[],false);                  // false no debug
        $archiveOps->showValues(false,[],false);
        }

	}
	
/******************************************************************************************************************/

function anfragezahlernr($varname,$anfang,$ende,$content){
    $zaehler_nr_ist = Auswerten($content,$anfang,$ende);
    return $zaehler_nr_ist;
};

function anfrage($varname, $anfang, $ende, $content, $vartyp, $VariProfile, $arhid, $ParentID){
    $wert = Auswerten($content, $anfang, $ende);
    if ($wert) {vars($arhid, $ParentID, $varname, $wert, $vartyp, $VariProfile); return (true); }
    else { return (false); }
};

function Auswerten($content,$anfang,$ende){
 	$result_1 = explode($anfang,$content);
 	if (sizeof($result_1)>1)
   	{
		$result_2 = explode($ende,$result_1[1]);
 		$wert = str_replace(".", ",", $result_2[0]);
	 	/* echo "gefunden:".sizeof($result_1)." ".sizeof($result_2)." \n";
 		print_r($result_1);
	 	print_r($result_2);   */
 		return $wert;
 		}
 	else
 	   {
 	   return (false);
 	   }
};


function vars($arhid,$ParentID, $varname, $wert, $vartyp, $VariProfile)
  {
$VariID = IPS_GetVariableIDByName($varname, $ParentID);
    if ($VariID == false)
    {
        $VariID = IPS_CreateVariable ($vartyp);
        IPS_SetVariableCustomProfile($VariID, $VariProfile);
        IPS_SetName($VariID,$varname);
          AC_SetLoggingStatus($arhid, $VariID, true);
        IPS_SetParent($VariID,$ParentID);
    }
    SetValue($VariID, $wert);
  };


	   
?>