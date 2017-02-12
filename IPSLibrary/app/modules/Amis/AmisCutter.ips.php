<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur  Bearbeitung von Daten, die aus den AMIS Registern ausgelesen werden
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

/******************************************************

				INIT

*************************************************************/

$trans = array(chr(13) => "", chr(10) => "");	/* für Log_Cutter Log File damit lesbar bleibt */
$cutter=true;	/* wenn der IPS interne Cutter für die Erkennung von 02 (STX) und 03 (ETX) aktiviert ist, spart logging traffic auf den echo Ports */ 

$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');

/* macht das selbe wie der eingebaute Cutter, kann aber selbststaendig installiert werden */


// Archiv Handler damit das Logging eingeschaltet werden kann.
$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
$arhid = $archiveHandlerID[0];
//echo $arhid."\n";

IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
$MeterConfig = get_MeterConfiguration();
//print_r($MeterConfig);

foreach ($MeterConfig as $identifier => $meter)
	{
	//echo"-------------------------------------------------------------\n";
	//echo "Create Variableset for :".$meter["NAME"]." (".$identifier.") \n";
	$ID = CreateVariableByName($parentid1, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	if ($meter["TYPE"]=="Amis")
		{
		$amismetername=$meter["NAME"];

		//echo "Amis Zähler, verfügbare Ports:\n";			
		
		$AmisID = CreateVariableByName($ID, "AMIS", 3);
		$ReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
		$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
		$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
			
		// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
		$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
		$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);

		// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
		$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
		$variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2);
			
		//Hier die COM-Port Instanz festlegen
		$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
		foreach ($serialPortID as $num => $serialPort)
			{
			//echo "      Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
			if (IPS_GetName($serialPort) == $identifier." Serial Port") 
				{ 
				$com_Port = $serialPort;
				$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
				if (IPS_InstanceExists($regVarID) )
					{
					//echo "        Registervariable : ".$regVarID."\n";
					$configPort[$regVarID]["Name"]=$amismetername;	
					$configPort[$regVarID]["ID"]=$identifier;	
					$configPort[$regVarID]["Port"]=$serialPort;																				 
					}
				}	
			if (IPS_GetName($serialPort) == $identifier." Bluetooth COM") 
				{ 
				$com_Port = $serialPort; 
				$regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$serialPort);
				if (IPS_InstanceExists($regVarID) )
					{
					//echo "        Registervariable : ".$regVarID."\n";
					$configPort[$regVarID]["Name"]=$amismetername;	
					$configPort[$regVarID]["ID"]=$identifier;
					$configPort[$regVarID]["Port"]=$serialPort;							
					}					
				}				
			}
		if (isset($com_Port) === true) 
			{ 
			//echo "\nAMIS Zähler Serial Port auf OID ".$com_Port." definiert.\n"; 
			}
		}
	if (!file_exists("C:\Scripts\Log_Cutter_".$identifier.".csv"))
		{
		$handle=fopen("C:\Scripts\Log_Cutter_".$identifier.".csv", "a");
		fwrite($handle, date("d.m.y H:i:s").";Quelle;Laenge;Zählerdatensatz\r\n");
		fclose($handle);
		}				
	//print_r($meter);
	}
//echo "Ermittelte Registervariablen als mögliche Quelle für empfangene Daten.\n";	
//print_r($configPort);		
	
/******************************************************

				EXECUTE

*************************************************************/

if ($_IPS['SENDER'] == "RegisterVariable")
	{
	$content = $_IPS['VALUE'];
	$sender=$_IPS['INSTANCE'];   /* damit kann festgestellt werden von wo der Datensatz kommt */	 
	if (isset($configPort[$sender]["Name"])== true)
	 	{
	 	$amismetername=$configPort[$sender]["Name"];
		/* parentid1 ist das Data Verzeichnis fuer AMIS */
		$ID = CreateVariableByName($parentid1, $amismetername, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		$AmisID = CreateVariableByName($ID, "AMIS", 3);
		$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
		$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);			/* aktuell empfangener Befehl */
		$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);     /* Zuletzt empfangener Befehl */
		$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
			
		$handle=fopen("C:\Scripts\Log_Cutter_".$configPort[$sender]["ID"].".csv","a");
		$ausgabewert=date("d.m.y H:i:s").";".$sender.";".strlen($content).";";

		if ($cutter == true)
			{ /* Es wird ein Cutter eingesetzt, d.h. die RegisterVariable liefert bereits vollstaendige Pakete */
			fwrite($handle, strtr($ausgabewert.$content,$trans)."\r\n");
			$content=chr(02).$content.chr(03);	/* Die Zeichen die der Cutter entfernt hat wieder dazu bauen */
			}
		$ReceiveChar=GetValue($AMISReceiveCharID);  /* Werte von frueherer Session als Ausgangswert uebernehmen */
		for($i=0;$i<strlen($content);$i++)
			{
			//$ausgabewert.=ord($content[$i]).";".$content[$i].";";
			if (ord($content[$i])==2)
				{
				//$ausgabewert.="Anfang**********************;";
				SetValue($AMISReceiveChar1ID,GetValue($AMISReceiveCharID));				/* Eingang neuer Befehl, alten Befehl in Char1 wegspeichern */ 
				SetValue($AMISReceiveCharID,"");
				$ReceiveChar="";  	/* mit lokaler Variable arbeiten, solange wir in einer Routine/Script sind */
				}
			else
				{
				if (ord($content[$i])==3)									/* Endezeichen neuer befehl, nun bearbeiten */
					{
					//$ausgabewert.="Ende**********************;";
					$ausgabewert.=$ReceiveChar.";";
					SetValue($AMISReceiveCharID,$ReceiveChar);
					if ($cutter==false) {	fwrite($handle, strtr($ausgabewert,$trans)."\r\n"); }		/* erst am Ende das Cutter Logfile schreiben */
		
					/* verarbeitung der eingelesenen Telegramme  */
					/*                                           */
					/*                                           */
					/*                                           */
					/*********************************************/
	
					$content = $ausgabewert;

					$handlelog=fopen("C:\Scripts\Log_".$configPort[$sender]["Name"].".csv","a");
					$ausgabewert=date("d.m.y H:i:s").";".$content;
		 			fwrite($handlelog, $ausgabewert."\r\n");
		 			fclose($handlelog);

					SetValue($AMISReceiveID,date("Y-m-d H:i:s",time()).":".$content);
    				if (strlen($content)>20)
      				{
	 					/* Routine funktioniert nur wenn der ganze Verrechnungsdatensatz ausgelesen wird
		 					Dauer ca. 60 Sekunden
	 					*/

						anfrage('Fehlerregister','F.F(',')',$content,3,'',$arhid,$zaehlerid);
						anfrage('Wirkleistung','1.7.0(','*kW)',$content,2,'~Power',$arhid,$zaehlerid);
						anfrage('Strom L1','31.7(','*A)',$content,2,'~Ampere',$arhid,$zaehlerid);
						anfrage('Strom L2','51.7(','*A)',$content,2,'~Ampere',$arhid,$zaehlerid);
						anfrage('Strom L3','71.7(','*A)',$content,2,'~Ampere',$arhid,$zaehlerid);
						anfrage('Frequenz','14.7(','*Hz)',$content,2,'~Hertz',$arhid,$zaehlerid);

						if (anfrage('Wirkenergie','1.8.0(','*kWh)',$content,2,'~Electricity',$arhid,$zaehlerid))
							{
							$wirkenergie_vwID = CreateVariableByName($AmisID, "Letzter Wert Wirkenergie", 2);
							$letzterWertID = CreateVariableByName($AmisID, "Letzter Wert", 1);
							$aktuelleLeistungID = CreateVariableByName($AmisID, "Wirkleistung", 2);
							$wirkenergie1_ID = CreateVariableByName($AmisID,'Wirkenergie', 2);         /* Backup der Wirkemnergie machen um auch direkt auslesbar zu sein */
							$wirkenergie_ID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
							//echo "ID:".$wirkenergie_ID."\n";

	 						$wirkenergie=GetValue($wirkenergie_ID);
	 						SetValue($wirkenergie1_ID,$wirkenergie);
		 					$wirkenergie_vw=GetValue($wirkenergie_vwID);
			 				SetValue($wirkenergie_vwID,$wirkenergie);
		 					$time_now=time();
			 				$time=$time_now-GetValue($letzterWertID);
		 					SetValue($letzterWertID,$time_now);
	 						$power=($wirkenergie-$wirkenergie_vw)/$time*60*60;
		 					//echo "Zeit in Sek : ".$time. " Leistung aktuell: ".$power."\n";
		 					SetValue($aktuelleLeistungID,$power);

			 				//echo "Ergebnis:".$content."\n";
							}
						}  /* ende if strlen */
					}  /* ende if ord */
				else
					{
					//SetValue($AMISReceiveCharID,GetValue($AMISReceiveCharID).$content[$i]);
					$ReceiveChar.=$content[$i];
					}
				} /* ende elseif ord */
			}	/* ende for */
		SetValue($AMISReceiveCharID,$ReceiveChar);	
		fclose($handle);
		}             /* if isset */
	else 
		{ 
		IPSLogger_Dbg(__file__, 'AMISCutter: Unbekannte Adresse von AMIS Receive Char : '.$sender.'.'); 
		}
	}
	 
if ($_IPS['SENDER'] == "Execute")
	{
	$ID = CreateVariableByName($parentid1, $amismetername, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	echo "\n==================================================================\n";
	echo "Amis Execute aufgerufen:\n\n";
	print_r($configPort);
	foreach ($configPort as $config)
		{	
		$amismetername=$config["Name"];
	 	$ID = CreateVariableByName($parentid1, $amismetername, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */		
		echo "AMIS Root OID:".$ID."  ".$amismetername."\n";
		$AmisID = CreateVariableByName($ID, "AMIS", 3);
		echo "  Alle mit der AMIS Zählerauswertung notwendige Register hier gespeichert :".$AmisID."\n";
	   	$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
   		echo "  Und dort stehen die eigentlichen aus dem AMIS Zähler ausgelesenen Register :".$zaehlerid."\n";
   		echo "\n";

		$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);	   
		echo "   Testweise letztes Ergebnis auswerten.\n";
		$content=GetValue($AMISReceiveID);
   		echo $content."\n";
		echo "     Fehlerregister : ".Auswerten($content,'F.F(',')')."\n";
		echo "     Wirkenergie : ".Auswerten($content,'1.8.0(','*kWh)')."kWh \n";
		$letzterWertID = CreateVariableByName($AmisID, "Letzter Wert", 1);
		echo "     Zuletzt berechnet/ausgelesen : ".date("d.m.y H:i:s",GetValue($letzterWertID))."\n";

		$wirkenergie_ID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
		echo "Wirkenergie : ".GetValue($wirkenergie_ID)." kWh\n";
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