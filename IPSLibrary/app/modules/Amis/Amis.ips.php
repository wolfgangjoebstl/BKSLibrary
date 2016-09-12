<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur Auslesung von Energiewerten. Diese Script ist verweist und wird zu Testzzecken weiterhin verwendet !
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 4.0 13.6.2016
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

/******************************************************

				INIT

*************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');

	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
	$MeterConfig = get_MeterConfiguration();
	//print_r($MeterConfig);

	foreach ($MeterConfig as $meter)
		{
		echo"-------------------------------------------------------------\n";
		echo "Create Variableset for : ".$meter["NAME"]." \n";
		$ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		if ($meter["TYPE"]=="Amis")
		   {
		   /* kann derzeit nur ein AMIS Modul installieren */
			$variableID = $meter["WirkenergieID"];
			$AmisID = CreateVariableByName($ID, "AMIS", 3);
			$MeterReadID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
			$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */

			// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
			$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
			$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

			// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
			$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);

			//Hier die COM-Port Instanz
			$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
			if (isset($com_Port) === true) { echo "Nur ein AMIS Zähler möglich\n"; break; }
			foreach ($serialPortID as $num => $serialPort)
			   {
			   echo "Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
			   if (IPS_GetName($serialPort) == "AMIS Serial Port") { $com_Port = $serialPort; }
			   if (IPS_GetName($serialPort) == "AMIS Bluetooth COM") { $com_Port = $serialPort; }
				}
			if (isset($com_Port) === false) { echo "Kein AMIS Zähler Serial Port definiert\n"; break; }
			else { echo "\nAMIS Zähler Serial Port auf OID ".$com_Port." definiert.\n"; }
			}
		//echo "\nZählerkonfiguration: \n";
		//print_r($meter);
		}


$AmisConfig = get_AmisConfiguration();
$MeterConfig = get_MeterConfiguration();

echo "\nAMIS Meter Read eingeschaltet:".Getvalue($MeterReadID)." auf Com-Port : ".$com_Port."\n";

if (Getvalue($MeterReadID))
	{
	if ($AmisConfig["Type"] == "Bluetooth")
	   {
      echo "Comport Bluetooth aktiviert. \n";
      COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
		}

	if ($AmisConfig["Type"] == "Serial")
	   {
      echo "Comport Serial aktiviert. \n";
      $config = IPS_GetConfiguration($com_Port);
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
	}
	

if ($_IPS['SENDER'] == "Execute")
	{

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
