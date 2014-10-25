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
$MeterReadID = CreateVariableByName($parentid, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
$TimeSlotReadID = CreateVariableByName($parentid, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
$SendTimeID = CreateVariableByName($parentid, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
$AmisConfig = get_AmisConfiguration();

$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
$com_Port = $serialPortID[0];
//print_r($serialPortID);

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
	echo "\n\n************************************************************************************************************************\n";

	$SerialComPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
	//print_r($PortID);
	echo "Serial Port : ".$SerialComPortID[0]." Name : ".IPS_GetName($SerialComPortID[0])."\n";
	
  	if ($AmisConfig["Type"] == "Bluetooth")
	   {
	   $SerialComPortID = @IPS_GetInstanceIDByName("AMIS Bluetooth COM", 0);
  		$com_Port = $SerialComPortID[0];

      if(!IPS_InstanceExists($SerialComPortID))
	      {
     		echo "\nAMIS Blutooth Port erstellen !";
	      $SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
     		IPS_SetName($SerialComPortID, "AMIS Bluetooth COM");

		   COMPort_SetPort($SerialComPortID, 'COM3'); // ComNummer welche dem PC-Interface zugewiesen ist!
  			COMPort_SetBaudRate($SerialComPortID, '115200');
		   COMPort_SetDataBits($SerialComPortID, '8');
  			COMPort_SetStopBits($SerialComPortID, '1');
	    	COMPort_SetParity($SerialComPortID, 'Keine');
  			COMPort_SetOpen($SerialComPortID, true);
	    	IPS_ApplyChanges($SerialComPortID);
     		echo "Comport Bluetooth aktiviert. \n";
	      }
      COMPort_SendText($com_Port ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
		}

	if ($AmisConfig["Type"] == "Serial")
	   {
      $SerialComPortID = @IPS_GetInstanceIDByName("AMIS Serial Port", 0);
      $com_Port = $SerialComPortID[0];
      
   	if(!IPS_InstanceExists($SerialComPortID))
      	{
	      echo "AMIS Serial Port erstellen !";
   	   $SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
      	IPS_SetName($SerialComPortID, "AMIS Serial Port");
		   COMPort_SetPort($SerialComPortID, 'COM3'); // ComNummer welche dem PC-Interface zugewiesen ist!
	    	COMPort_SetBaudRate($SerialComPortID, '300');
   	 	COMPort_SetDataBits($SerialComPortID, '7');
    		COMPort_SetStopBits($SerialComPortID, '1');
    		COMPort_SetParity($SerialComPortID, 'Even');
    		COMPort_SetOpen($SerialComPortID, true);
    		IPS_ApplyChanges($SerialComPortID);
      	}
		}
	$CutterPortID = @IPS_GetInstanceIDByName("AMIS Cutter", 0);
   if(!IPS_InstanceExists($CutterPortID))
      {
      echo "AMIS Cutter erstellen !";
      $CutterPortID = IPS_CreateInstance("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}"); // Cutter anlegen
      IPS_SetName($CutterPortID, "AMIS Cutter");
      }
	/* Cutter eigentlich gar nicht notwendig, kann ich doch auch selbst machen
	   vor allem weis nicht wie programmieren */
	
	//echo "-----".$CutterPortID."\n";
	
   $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$SerialComPortID);
   if(!IPS_InstanceExists($regVarID))
      {
      $regVarID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}"); // Registervariable anlegen
      IPS_SetName($regVarID, "AMIS RegisterVariable");
      IPS_SetParent($regVarID, $SerialComPortID);
	   RegVar_SetRXObjectID($regVarID, 16522);
   	IPS_ConnectInstance($regVarID, $SerialComPortID);
	   IPS_ApplyChanges($regVarID);
      }

	
	
	//echo "Alle I/O Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur I/O Instanzen auflisten

	//echo "Alle Kern Instanzen\n";
	//$alleInstanzen = IPS_GetInstanceListByModuleType(0); // nur Kern Instanzen auflisten

	//echo "Alle Splitter Instanzen\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(2); // nur Splitter Instanzen auflisten


	//print_r($alleInstanzen);
	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   //echo "****".$instanz;
	   //echo " Cutter "$instanz." Name : ".IPS_GetName($instanz)."\n";
	   if ($datainstanz["ConnectionID"]==$SerialComPortID)
	      {
	   	echo "Cutter ".$instanz." Name : ".IPS_GetName($instanz)."\n";
		   //print_r($datainstanz);
		   }
		//print_r(IPS_GetInstance($instanz));
	   }
	
	//echo "Alle Splitter Instanzen\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(3); // nur Splitter Instanzen auflisten
	//print_r($alleInstanzen);

	foreach ($alleInstanzen as $instanz)
	   {
	   $datainstanz=IPS_GetInstance($instanz);
	   //echo "****".$instanz;
	   //echo " Cutter "$instanz." Name : ".IPS_GetName($instanz)."\n";
	   if ($datainstanz["ConnectionID"]==$SerialComPortID)
	      {
	   	echo "RegisterVariable ".$instanz." Name : ".IPS_GetName($instanz)."\n";
		   //print_r($datainstanz);
		   }
	   if ($datainstanz["ConnectionID"]==$CutterPortID)
	      {
	   	echo "RegisterVariable ".$instanz." Name : ".IPS_GetName($instanz)."\n";
		   //print_r($datainstanz);
		   }
	   //print_r($datainstanz);
		//print_r(IPS_GetInstance($instanz));
	   }

	   COMPort_SendText($com_Port ,"\x2F\x3F\x21\x0D\x0A");   /* /?! <cr><lf> */
		IPS_Sleep(1550);
		COMPort_SendText($com_Port ,"\x06\x30\x30\x31\x0D\x0A");    /* ACK 001 <cr><lf> */
		IPS_Sleep(1550);
		COMPort_SendText($com_Port ,"\x01\x52\x32\x02F001()\x03\x17");    /* <SOH>R2<STX>F001()<ETX> */

	/* Selbst anlegen, Beispiel */
/*	$comPortID = @IPS_GetInstanceIDByName("HS485 PC-Interface", 0);
   if(!IPS_InstanceExists($comPortID))
            {
               $comPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
               IPS_SetName($comPortID, "HS485 PC-Interface");
            }
    COMPort_SetPort($comPortID, 'COM5'); // ComNummer welche dem PC-Interface zugewiesen ist!
           COMPort_SetBaudRate($comPortID, '19200');
            COMPort_SetDataBits($comPortID, '8');
            COMPort_SetStopBits($comPortID, '1');
           COMPort_SetParity($comPortID, 'Even');
           COMPort_SetOpen($comPortID, true);
           IPS_ApplyChanges($comPortID);

            $regVarID = @IPS_GetInstanceIDByName("HS485 RegisterVariable", $parentID);
            if(!IPS_InstanceExists($regVarID))
            {
                $regVarID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}"); // Registervariable anlegen
            IPS_SetName($regVarID, "HS485 RegisterVariable");
            IPS_SetParent($regVarID, $CatID);
            }
            RegVar_SetRXObjectID($regVarID, $IPS_SELF);
            IPS_ConnectInstance($regVarID, $comPortID);
           IPS_ApplyChanges($regVarID);

*/



	}
	
?>
