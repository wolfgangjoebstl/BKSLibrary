<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Auslesung von Energiewerten. Diese Script ist verwaist und wird nur zu Testzwecken weiterhin verwendet
	 * Aufgabe wird jetzt von MomentanwerteAbfragen uebernommen.
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 4.0 13.6.2016
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
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

	$amis=new Amis();

	$MeterConfig = $amis->getMeterConfig();
	//print_r($MeterConfig);

	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$configPort=array();

	foreach ($MeterConfig as $identifier => $meter)
		{
		echo"-------------------------------------------------------------\n";
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
	echo "\n--------Execute aufgerufen -------------------------\n";
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