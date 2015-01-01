<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
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
		echo "Create Variableset for :".$meter["NAME"]." \n";
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
			$com_Port = $serialPortID[0];

			}
		print_r($meter);
		}


$AmisConfig = get_AmisConfiguration();
$MeterConfig = get_MeterConfiguration();

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
		COMPort_SetOpen($com_Port, true); //false für aus
		IPS_ApplyChanges($com_Port);
		COMPort_SetDTR($com_Port , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
		}

   switch (Getvalue($TimeSlotReadID))
		{
		case "15":  /* Auto */
		   Setvalue($TimeSlotReadID,1);
  			break;

		case "8":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
		   Setvalue($SendTimeID,time());
		   COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */
			break;
			
		case "14":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "13":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "12":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "11":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "10":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "9":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "7":  /* Auto */
			writeEnergyHomematic($MeterConfig);
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "6":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "5":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "4":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "3":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;
		case "2":  /* Auto */
		   Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;

		case "1":
		   Setvalue($SendTimeID,time());
			COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> auf 300 baud bleiben */
			IPS_Sleep(1550);
			COMPort_SendText($com_Port ,"\x01\x52\x32\x02F009()\x03\x1F");    /* <SOH>R2<STX>F009()<ETX> checksumme*/
			Setvalue($TimeSlotReadID,Getvalue($TimeSlotReadID)+1);
			break;

		default:
		   Setvalue($TimeSlotReadID,1);
			break;

		}
		
	}
else
	{
	if ($AmisConfig["Type"] == "Serial")
	   {
		echo "Comport Serial deaktiviert. \n";
		COMPort_SetOpen($com_Port, false); //false für aus
		IPS_ApplyChanges($com_Port);
		}
		
	if ($AmisConfig["Type"] == "Bluetooth")
	   {
		echo "Comport Bluetooth deaktiviert. \n";
		}
	}

if ($_IPS['SENDER']=="Execute")
   {
	echo "********************************************CONFIG**************************************************************\n";

	$SerialComPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
	//print_r($PortID);
	echo "Serial Port : ".$SerialComPortID[0]." Name : ".IPS_GetName($SerialComPortID[0])."\n";
	
	//echo "Alle I/O Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur I/O Instanzen auflisten

	//echo "Alle Kern Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(0); // nur Kern Instanzen auflisten

	//echo "Alle Splitter Instanzen\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur Splitter Instanzen auflisten
	//print_r($alleInstanzen);
	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   echo " ".$instanz." Name : ".IPS_GetName($instanz)."\n";
	   if ($instanz==$SerialComPortID[0])
	      {
	   	//echo "**RegisterVariable ".$instanz." Name : ".IPS_GetName($instanz)."\n";
		   //print_r($datainstanz);
		   }
	   //print_r($datainstanz);
		//print_r($instanz);
	   }

	if (false)
	   {
		COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
		IPS_Sleep(1550);
		COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
		IPS_Sleep(1550);
		COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */
		}
		
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	$homematic=writeEnergyHomematic($MeterConfig);
	}


/*************************************************************************************************************************/

function writeEnergyHomematic($MConfig)
	{
	$homematic=false;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	foreach ($MConfig as $meter)
		{
		if ($meter["TYPE"]=="Homematic")
	   	{
	   	$homematic=true;
	      $ID = CreateVariableByName($parentid, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      $EnergieID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      IPS_SetVariableCustomProfile($EnergieID,'kWh');
	      AC_SetLoggingStatus($archiveHandlerID,$EnergieID,true);
			AC_SetAggregationType($archiveHandlerID,$EnergieID,1);
			IPS_ApplyChanges($archiveHandlerID);
	      $HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
	      IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
	      $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
  	      IPS_SetVariableCustomProfile($LeistungID,'kW');
	      $energie=GetValue($meter["HM_EnergieID"])/1000;
  	      //IPS_SetVariableCustomProfile($meter["WirkenergieID"],'Wh');
	      $energievorschub=$energie-GetValue($HM_EnergieID);
			SetValue($HM_EnergieID,$energie);
			$energie_neu=GetValue($EnergieID)+$energievorschub;
			SetValue($EnergieID,$energie_neu);
			SetValue($LeistungID,$energievorschub*4);
	      //echo "Energie Aktuell :".$energie." gespeichert auf ID:".$EnergieID."\n";
	      echo "Homematicwerte :".(GetValue($meter["HM_EnergieID"])/1000)."kWh  ".GetValue($meter["HM_LeistungID"])."W\n";
	      echo "Energievorschub aktuell:".$energievorschub."kWh\n";
	      echo "Energiezählerstand :".$energie_neu."kWh Leistung :".GetValue($LeistungID)."kW \n";
			//print_r($meter);
			}
		}
	return ($homematic);
	}


	
?>
