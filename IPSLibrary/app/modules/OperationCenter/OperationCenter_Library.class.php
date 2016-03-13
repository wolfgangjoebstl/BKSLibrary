<?


/*********************************************************************************************/
/*********************************************************************************************/
/*                                                                                           */
/*                              Functions                                                    */
/*                                                                                           */
/*********************************************************************************************/
/*********************************************************************************************/


/****************************************************************************************************************/

class OperationCenter
	{

	var $CategoryIdData="Default";
	var $categoryId_SysPing="Default";
	var $archiveHandlerID=0;
	var $mactable=array();

	/**
	 * @public
	 *
	 * Initialisierung des DetectHumidityHandler Objektes
	 *
	 */
	public function __construct($CategoryIdData,$subnet)
			{
		   $this->CategoryIdData=$CategoryIdData;
   		$this->categoryId_SysPing    = CreateCategory('SysPing',   $this->CategoryIdData, 200);
         $this->mactable=$this->get_macipTable($subnet);
         $categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
			$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
			$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			}


	function device_ping($device_config, $device, $identifier)
		{
		foreach ($device_config as $name => $config)
		   {
		   //print_r($config);
			$StatusID = CreateVariableByName($this->categoryId_SysPing, $device."_".$name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		   //echo "Sys_ping Led Ansteuerung : ".$name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
		   echo "Sys_ping ".$device." Ansteuerung : ".$name." mit IP Adresse ".$config[$identifier]."\n";
			$status=Sys_Ping($config[$identifier],1000);
			if ($status)
				{
				echo $device."-Modul wird erreicht   !\n";
				if (GetValue($StatusID)==false)
				   {  /* Statusänderung */
					$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
					$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
					SetValue($StatusID,true);
				   }
				}
			else
				{
				echo $device."-Modul wird NICHT erreicht   !\n";
				if (GetValue($StatusID)==true)
				   {  /* Statusänderung */
					$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar');
					$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar');
					SetValue($StatusID,false);
				   }
				}
		   }
		}

	function get_macipTable($subnet,$printHostnames=false)
		{
		$subnetok=substr($subnet,0,strpos($subnet,"255"));
		$ergebnis=""; $print_table="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		unset($catch);
		exec('arp -a',$catch);
		foreach($catch as $line)
   		{
		   $result=trim($line);
   		$result1=substr($result,0,strpos($result," ")); /* zuerst IP Adresse */
	   	$result=trim(substr($result,strpos($result," "),100));
	   	$result2=substr($result,0,strpos($result," ")); /* danach MAC Adresse */
		   $result=trim(substr($result,strpos($result," "),100));
			if ($result1=="10.0.255.255") { break; }
			if (strpos($result1,$subnetok)===false)
			   {
			   }
			else
			   {
		   	//echo $line."\n";
				if (is_numeric(substr($result1,-1)))   /* letzter Wert in der IP Adresse wirklich eine Zahl */
					{
					$ergebnis.=$result1.";".$result2;
					$print_table.=$line;
					$found=false;
					foreach ($ipadressen as $ip)
					   {
				   	if ($result2==$ip["Mac_Adresse"])
		   		   	{
							$ergebnis.=";".$ip["Hostname"].",";
							$print_table.=" ".$ip["Hostname"]."\n";
							$found=true;
							}
						}
					if ($found==false)
						{
						$ergebnis.=";none,";
						$print_table.=" \n";
						}
					}
				}
		  }
		$ergebnis_array=explode(",",$ergebnis);
		$result_array=array();
		$mactable=array();
		foreach ($ergebnis_array as $ergebnis_line)
			{
			//echo $ergebnis_line."\n";
			$result_array=explode(";",$ergebnis_line);
			//print_r($result_array);
			if (sizeof($result_array)>2)
			   {
			   if ($result_array[1]!='ff-ff-ff-ff-ff-ff')
			      {
					$mactable[$result_array[1]]=$result_array[0];
					}
				}
			}
		if ($printHostnames==true)
		   {
			return ($print_table);
			}
		else
		   {
			return($mactable);
			}
		}
		
	function find_HostNames()
	   {
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		foreach ($this->mactable as $mac => $ip )
		   {
		   $result="unknown";
		   foreach ($ipadressen as $name => $entry)
		      {
		      if ($entry["Mac_Adresse"]==$mac) { $result=$name; }
		      }
		   echo "   ".$mac."   ".str_pad($ip,12)." ".$result."\n";
		   }
	   }

	function write_routerdata_MR3420($router)
		{
	   $verzeichnis=$router["DownloadDirectory"]."report_router_".$router['TYP']."_".$router['NAME']."_files/";
		if ( is_dir ( $verzeichnis ))
			{
			echo "Auswertung Dateien aus Verzeichnis : ".$verzeichnis."\n";
			$parser=new parsefile($this->CategoryIdData);
			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
			if ($router_categoryId==false)
			   {
				$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
				IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
				IPS_SetParent($router_categoryId,$this->CategoryIdData);
				}
			$ergebnis=array();
			$ergebnis=$parser->parsetxtfile($verzeichnis,$router['NAME']);
			//print_r($ergebnis);
			$summe=0;
			foreach ($ergebnis as $ipadresse)
			   {
			   $MBytes=(float)$ipadresse['Bytes']/1024/1024;
			   echo "       ".str_pad($ipadresse['IPAdresse'],18)." mit MBytes ".$MBytes."\n";
  				if (($ByteID=@IPS_GetVariableIDByName("MBytes_".$ipadresse['IPAdresse'],$router_categoryId))==false)
     				{
				  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_".$ipadresse['IPAdresse'], 2);
					IPS_SetVariableCustomProfile($ByteID,'MByte');
					AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
					AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
					IPS_ApplyChanges($this->archiveHandlerID);
					}
			  	SetValue($ByteID,$MBytes);
				$summe += $MBytes;
				}
			echo "Summe   ".$summe."\n";
   		if (($ByteID=@IPS_GetVariableIDByName("MBytes_All",$router_categoryId))==false)
     			{
			  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_All", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			}
	   $verzeichnis=$router["DownloadDirectory"]."report_router_".$router['TYP']."_".$router['NAME']."_Statistics_files/";
		if ( is_dir ( $verzeichnis ))
			{
			echo "Auswertung Dateien aus Verzeichnis : ".$verzeichnis."\n";
			$ergebnis=array();
			$ergebnis=$parser->parsetxtfile_statistic($verzeichnis,$router['NAME']);
			$summe=0;
			$MBytes=(float)$ergebnis['RxBytes']/1024/1024;
			echo "       RxBytes mit MBytes ".$MBytes."\n";
			if (($ByteID=@IPS_GetVariableIDByName("Download",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Download", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			$summe += $MBytes;
			$MBytes=(float)$ergebnis['TxBytes']/1024/1024;
			echo "       TxBytes mit MBytes ".$MBytes."\n";
			if (($ByteID=@IPS_GetVariableIDByName("Upload",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Upload", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			$summe += $MBytes;
			if (($ByteID=@IPS_GetVariableIDByName("Total",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Total", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$summe);
			}
		}

	function write_routerdata_MBRN3000($router)
		{
		echo "Daten vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die Tageswerte von gestern erfasst.\n";
		//$Router_Adresse = "http://admin:cloudg06##@www.routerlogin.com/";
		$Router_Adresse = "http://".$router["USER"].":".$router["PASSWORD"]."@".$router["IPADRESSE"]."/";
		echo "  Routeradresse die aufgerufen wird : ".$Router_Adresse." \n";
		//print_r($router);
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$url=$Router_Adresse."traffic_meter.htm";
		$result=@file_get_contents($url);
		if ($result===false) {
		   echo "Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=file_get_contents($url);
			if ($result===false) {
			   echo "Fehler beim holen der Webdatei. Abbruch. \n";
			   break;
			   }
	  		}
		$result=strip_tags($result);
		$pos=strpos($result,"Period");
		if ($pos!=false)
			{
			$result1=substr($result,$pos,6);       /*  Period  */
	   	$result=substr($result,$pos+7,1500);
			$result1=$result1.";".trim(substr($result,20,20));    /* Connection Time  */
			$result=substr($result,140,1500);
			$result1=$result1.";".trim(substr($result,20,40));    /* Upload */
			$result=substr($result,40,1500);
			$result1=$result1.";".trim(substr($result,20,30));    /* Download  */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,20,40))."\n";  /*  Total  */
			$result=substr($result,50,1500);
			$result1=$result1.trim(substr($result,10,30));        /* Today   */
			$result=substr($result,20,1500);
			$result1=$result1.";".trim(substr($result,20,30));    /* Today Connection Time */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30));    /* Today Upload */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30));    /* Today Download */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30))."\n";    /* Today Total */
			$result=substr($result,30,1500);
			$result1=$result1.trim(substr($result,10,30));        /* Yesterday */
			$result=substr($result,20,1500);

			if (($ConnTimeID=@IPS_GetVariableIDByName("ConnTime",$router_categoryId))==false)
  				{
			  	$ConnTimeID = CreateVariableByName($router_categoryId, "ConnTime", 1);
				//IPS_SetVariableCustomProfile($ConnTimeID,'MByte');
				//AC_SetLoggingStatus($this->archiveHandlerID,$ConnTimeID,true);
				//AC_SetAggregationType($this->archiveHandlerID,$ConnTimeID,0);
				//IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,20,30));
		   $pos=strpos($result2,":");
			$conntime=(int)substr($result2,0,$pos);
			$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
			SetValue($ConnTimeID,$conntime);
			echo " Connection Time in Minuten bisher : ".$conntime." sind ".($conntime/60)." Stunden.\n";

			$result1=$result1.";".$result2;    /* Yesterday Connection Time */
			$result=substr($result,30,1500);

			if (($UploadID=@IPS_GetVariableIDByName("Upload",$router_categoryId))==false)
  				{
			  	$UploadID = CreateVariableByName($router_categoryId, "Upload", 2);
				IPS_SetVariableCustomProfile($UploadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$UploadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$UploadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,10,30));
		   $pos=strpos($result2,".");
			//if ($pos!=false)
			//	{
			//	$result2=substr($result2,0,$pos); /* .",".substr($result2,$pos+1,2);  keine Float Variable */
			//	}
			$Upload= (float) $result2;

			SetValue($UploadID,$Upload);
			echo " Upload Datenvolumen bisher ".$Upload." Mbyte \n";;

			$result1=$result1.";".$result2;    /* Yesterday Upload */
			$result=substr($result,30,1500);

			if (($DownloadID=@IPS_GetVariableIDByName("Download",$router_categoryId))==false)
  				{
			  	$DownloadID = CreateVariableByName($router_categoryId, "Download", 2);
				IPS_SetVariableCustomProfile($DownloadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$DownloadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$DownloadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,10,30));
		   $pos=strpos($result2,".");
			$Download= (float) $result2;
			SetValue($DownloadID,$Download);
			echo " Download Datenvolumen bisher ".$Download." \n";
			}
		}

	/*
	 *  Routerdaten direct aus dem Router auslesen,
	 *
	 *  mit actual wird definiert ob als return Wert die Gesamtwerte von heute oder gestern ausgegeben werden sollen
	 *
	 */

	function get_routerdata_MBRN3000($router,$actual=false)
		{
		echo "Daten direkt vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die aktuellen Tageswerte erfasst.\n";
		$Router_Adresse = "http://".$router["USER"].":".$router["PASSWORD"]."@".$router["IPADRESSE"]."/";
		echo "    -> Routeradresse die aufgerufen wird : ".$Router_Adresse." \n";
		//print_r($router);
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$url=$Router_Adresse."traffic_meter.htm";
		$result=file_get_contents($url);
		if ($result===false) {
		   echo "Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=file_get_contents($url);
			if ($result===false) {
			   echo "Fehler beim holen der Webdatei. Abbruch. \n";
			   break;
			   }
	  		}
		$result=strip_tags($result);
		$pos=strpos($result,"Period");
		if ($pos!=false)
			{
			/* Überschriften aus der Tabelle einsammeln, mit Strichpunkt trennen */
			$result_l1=substr($result,$pos,6);       /*  Period  */
	   	$result=substr($result,$pos+7,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,20));    /* Connection Time  */
			$result=substr($result,140,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,40));    /* Upload */
			$result=substr($result,40,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,30));    /* Download  */
			$result=substr($result,30,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,40));  /*  Total  */
			
			/* jetzt die Werte von heute einsammeln */
			$result=substr($result,50,1500);
			$result_l2=trim(substr($result,10,30));        /* Today   */
			$result=substr($result,20,1500);
				$result2=trim(substr($result,20,30));
			   $pos=strpos($result2,":");
				$conntime=(int)substr($result2,0,$pos);
				$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
				echo " Connection Time von Heute in Minuten : ".$conntime." sind ".round(($conntime/60),2)." Stunden.\n";
			$result_l2=$result_l2.";".trim(substr($result,20,30));    /* Today Connection Time */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Upload= (float) $result2;
				echo " Upload Datenvolumen Heute bisher ".$Upload." Mbyte \n";;
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Upload */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Download= (float) $result2;
				echo " Download Datenvolumen Heute bisher ".$Download." MByte \n";
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Download */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Today_Totalload= (float) $result2;
				echo " Gesamt Datenvolumen Heute bisher ".$Today_Totalload." Mbyte \n";
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Total */

			/* und die Werte von gestern */
			$result=substr($result,30,1500);
			$result_l3=trim(substr($result,10,30));        /* Yesterday */
			$result=substr($result,20,1500);
				$result2=trim(substr($result,20,30));
		   	$pos=strpos($result2,":");
				$conntime=(int)substr($result2,0,$pos);
				$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
				echo " Connection Time von Gestern in Minuten : ".$conntime." sind ".round(($conntime/60),2)." Stunden.\n";
			$result_l3=$result_l3.";".$result2;    /* Yesterday Connection Time */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Upload= (float) $result2;
				echo " Upload Datenvolumen von Gestern ".$Upload." Mbyte \n";;
			$result_l3=$result_l3.";".$result2;    /* Yesterday Upload */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Download= (float) $result2;
				echo " Download Datenvolumen von Gestern ".$Download." Mbyte \n";
			$result_l3=$result_l3.";".trim(substr($result,10,30));    /* Yesterday Download */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Yesterday_Totalload= (float) $result2;
				echo " Gesamt Datenvolumen gestern bisher ".$Yesterday_Totalload." Mbyte \n";
			$result_l3=$result_l3.";".trim(substr($result,10,30));    /* Today Total */

			echo "****** ".$result_l1." \n";
			echo "****** ".$result_l2." \n";
			echo "****** ".$result_l3." \n";

			if ($actual==false)
			   {
			   return ($Yesterday_Totalload);
			   }
			else
			   {
			   return ($Today_Totalload);
			   }
			}
		}


	function get_routerdata($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$result1=array();
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000); 
		   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
		   	foreach ($werte as $wert)
		   	   {
		   	   echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
		   	   }
		   	//print_r($werte);
				$result1[IPS_GetName($oid)]=$oid;
		   	}
		   else
		      {
		   	echo "   ".IPS_GetName($oid)." Variable wird NICHT gelogged.\n";
		      }
		   }
		//ksort($result1);
		//print_r($result1);
		}

	function get_routerdata_MR3420($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$ergebnis=0;
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      if (IPS_GetName($oid)=="MBytes_All")
		         {
		         $ergebnis=GetValue($oid);
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
		   		   echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
		   	   	}
					}
		   	}
		   }
		return $ergebnis;
		}


	function get_data($oid)
		{
      $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
	  	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
	  	foreach ($werte as $wert)
	  	   {
	  	   echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
	  	   }
		}

	function sort_routerdata_MR3420($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$result1=array();
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
				$result1[IPS_GetName($oid)]=$oid;
		   	}
		   else
		      {
				$result1["zzz".IPS_GetName($oid)]=$oid;
		      }
		   }
		$i=100;
		ksort($result1);
		foreach($result1 as $oid)
		   {
			IPS_SetPosition($oid,$i);
			$i+=10;
			}
		}
		
	}  /* ende class */

/****************************************************************************************************************/

function move_camPicture($verzeichnis,$WebCamWZ_LetzteBewegungID)
	{
	$count=100;
	//echo "<ol>";

	// Test, ob ein Verzeichnis angegeben wurde
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	/* einlesen der Verzeichnisses
			nur count mal Eintraege
        	*/
        	while ((($file = readdir($handle)) !== false) and ($count > 0))
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
					$count-=1;
					$unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
					$letztesfotodatumzeit=date("d.m.Y H:i", filectime($verzeichnis.$file));
            	if (is_dir($verzeichnis.$unterverzeichnis))
            		{
            		}
            	else
						{
            		mkdir($verzeichnis.$unterverzeichnis);
            		}
            	rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
            	//echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
		  		   SetValue($WebCamWZ_LetzteBewegungID,$letztesfotodatumzeit);
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein FTP Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}
	return(100-$count);
	}



/*********************************************************************************************/

function get_data($url) {
	$ch = curl_init($url);
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (FM Scene 4.6.1)"); // who am i

	/*   CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */

	$data = curl_exec($ch);

	/* Curl Debug Funktionen */
	/*
	echo "Channel :".$ch."\n";
  	$err     = curl_errno( $ch );
   $errmsg  = curl_error( $ch );
   $header  = curl_getinfo( $ch );

	echo "Fehler ".$err." von ";
	print_r($errmsg);
	echo "\n";
	echo "Header ";
	print_r($header);
	echo "\n";
	*/

	curl_close($ch);

	return $data;
}

/*********************************************************************************************/

function extractIPaddress($ip)
	{
		$parts = str_split($ip);   /* String in lauter einzelne Zeichen zerlegen */
		$first_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$first_num = $num_loc;
				break;
				}
			$num_loc++;
			}
		if ($first_num == -1) {return "unknown";}

		/* IP adresse Stelle fuer Stelle dekodieren, Anhaltspunkt ist der Punkt */
		$result=substr($ip,$first_num,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_1=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_2=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_3=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$parts = str_split($result);   /* String in lauter einzelne Zeichen zerlegen */
		$last_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$last_num = $num_loc;
				}
			$num_loc++;
			}
		$result=substr($result,0,$last_num+1);
		//echo "-------------------------> externe IP Adresse in Einzelteilen:  ".$result_1.".".$result_2.".".$result_3.".".$result."\n";
		return($result_1.".".$result_2.".".$result_3.".".$result);
	}

/*********************************************************************************************/


class parsefile
	{

	private $dataID;

	public function __construct($moduldataID)
		{
		//echo "Parsefile construct mit Data ID des aktuellen Moduls: ".$moduldataID."\n";
		$this->dataID=$moduldataID;
		}

	function parsetxtfile($verzeichnis, $name)
		{
		$ergebnis_array=array();

		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
      if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert nicht, jetzt neu angelegt.\n";
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		$handle = @fopen($verzeichnis."SystemStatisticRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile ".$verzeichnis."SystemStatisticRpm.htm gefunden.\n";
			$ok=true;
   		while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	if(preg_match('/statList/i',$buffer))
		   		{
		   		do {
		   		   if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						   {
							//echo "       ".$buffer;
					  		$pos1=strpos($buffer,"\"");
							if ($pos1!=false)
								{
						  		$pos2=strpos($buffer,"\"",$pos1+1);
						  		$ipadresse=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array[$ipadresse]['IPAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
								$buffer=trim(substr($buffer,$pos2+1,200));
								//echo "       **IP Adresse: ".$ergebnis_array[$ipadresse]['IPAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
								//echo "       **1:".$buffer."\n";
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$ergebnis_array[$ipadresse]['MacAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
									$buffer=trim(substr($buffer,$pos2,200));
									//echo "       **MAC Adresse: ".$ergebnis_array[$ipadresse]['MacAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
									//echo "       **2:".$buffer."\n";
							  		$pos1=strpos($buffer,',');
									if ($pos1!=false)
										{
								  		$pos2=strpos($buffer,',',$pos1+1);
								  		$ergebnis_array[$ipadresse]['Packets']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
										$buffer=trim(substr($buffer,$pos2,200));
										//echo "       **Packets: ".$ergebnis_array[[$ipadresse]['Packets']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
										//echo "       **3:".$buffer."\n";
								  		$pos1=strpos($buffer,',');
										if ($pos1!==false)
											{
									  		$pos2=strpos($buffer,',',$pos1+1);
									  		$ergebnis_array[$ipadresse]['Bytes']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
											$buffer=trim(substr($buffer,$pos2,200));
											//echo "       **Bytes: ".$ergebnis_array[$ipadresse]['Bytes']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
											//echo "       **4:".$buffer."\n";
											}
										}
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
		return $ergebnis_array;
		}

	function parsetxtfile_Statistic($verzeichnis, $name)
		{
		$ergebnis_array=array();

		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
      if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert nicht, jetzt neu angelegt.\n";
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		/*  Routine sucht in einem File dass zeilenweise ausgelesen wird,
		 *   es wird zwischen dem Anfangsstring und dem Endstring ausgewertet
		 */
		$handle = @fopen($verzeichnis."StatusRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile ".$verzeichnis."StatusRpm.htm gefunden.\n";
			$ok=true;
   		while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	if(preg_match('/statistList/i',$buffer))
		   		{
		   		do {
		   		   if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						   {
						   /* nächste Zeile wurde ausgelesen, hier stehen die wichtigen Informationen */
					  		$pos1=strpos($buffer,'"');
							//echo "      |".$buffer."    | ".$pos1."  \n";
							if ($pos1!==false)
								{
						  		$pos2=strpos($buffer,'"',$pos1+1);
						  		//echo "Die ersten zwei Anführungszeichen sind auf Position ".$pos1." und ".$pos2." \n";
						  		$received_bytes=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array["RxBytes"]=$this->removecomma($received_bytes);
								$buffer=trim(substr($buffer,$pos2+1,200));
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$transmitted_bytes=substr($buffer,$pos1+1,$pos2-$pos1-1);
							  		$ergebnis_array["TxBytes"]=$this->removecomma($transmitted_bytes);
							  		$ok=false;
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
		echo "Received Bytes : ".$ergebnis_array["RxBytes"]." Transmitted Bytes : ".$ergebnis_array["TxBytes"]." \n";
		return $ergebnis_array;
		}

	private function removecomma($number)
	   {
	   return str_replace(',','',$number);
	   }

	} /* Ende class */


/*********************************************************************************************/


function dirToArray($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}

/*********************************************************************************************/

function dirToArray2($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}


/*********************************************************************************************/

function tts_play($sk,$ansagetext,$ton,$modus)
 	{

  	/*
		modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
		modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
		modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
		*/

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

			echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
			$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
			}
		$sprachsteuerung=false;
		$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
		$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
		foreach ($knownModules as $module=>$data)
			{
			$infos   = $moduleManager->GetModuleInfos($module);
			if (array_key_exists($module, $installedModules))
				{
				if ($module=="Sprachsteuerung") $sprachsteuerung=true;
				}
			}
		$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
		$scriptIdSprachsteuerung   = IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);

		$id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
		$id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
		$id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
		$id_sk1_musik_status = IPS_GetVariableIDByName("Status", $id_sk1_musik);
		$id_sk1_ton_status = IPS_GetVariableIDByName("Status", $id_sk1_ton);
		$id_sk1_musik_vol = IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik);
	   $id_sk1_counter = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
		echo "\nAlle IDs :".$id_sk1_musik." ".$id_sk1_musik_status." ".$id_sk1_musik_vol." ".$id_sk1_ton." ".$id_sk1_ton_status." ".$id_sk1_tts."\n";

		$wav = array
		(
      "hinweis"  => IPS_GetKernelDir()."media/wav/hinweis.wav",
      "meldung"  => IPS_GetKernelDir()."media/wav/meldung.wav",
      "abmelden" => IPS_GetKernelDir()."media/wav/abmelden.wav",
      "aus"      => IPS_GetKernelDir()."media/wav/aus.wav",
      "coin"     => IPS_GetKernelDir()."media/wav/coin-fall.wav",
      "thunder"  => IPS_GetKernelDir()."media/wav/thunder.wav",
      "clock"    => IPS_GetKernelDir()."media/wav/clock.wav",
      "bell"     => IPS_GetKernelDir()."media/wav/bell.wav",
      "horn"     => IPS_GetKernelDir()."media/wav/horn.wav",
      "sirene"   => IPS_GetKernelDir()."media/wav/sirene.wav"
		);
		switch ($sk)
		{
			//---------------------------------------------------------------------
			case '1':

			  		$status = GetValueInteger($id_sk1_ton_status);
				   while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);

			      $sk1_counter = GetValueInteger($id_sk1_counter);
   	 			$sk1_counter++;
			      SetValueInteger($id_sk1_counter, $sk1_counter);
					if($sk1_counter >= 9) SetValueInteger($id_sk1_counter, $sk1_counter = 0);

				 	if($ton == "zeit")
 						{
						$time = time();
						// die Integer-Wandlung dient dazu eine führende Null zu beseitigen
	   				$hrs = (integer)date("H", $time);
   					$min = (integer)date("i", $time);
	   				$sec = (integer)date("s", $time);
   					// "kosmetische Behandlung" für Ein- und Mehrzahl der Minutenangabe
   					if($hrs==1) $hrs = "ein";
	   				$minuten = "Minuten";
   					if($min==1)
   						{
      					$min = "eine";
	      				$minuten = "Minute";
			   			}
   					// Zeitansage über Text-To-Speech
  	 					$ansagetext = "Die aktuelle Uhrzeit ist ". $hrs. " Uhr und ". $min. " ". $minuten;
			  	 		$ton        = "";
					 	}

			   	//Lautstärke von Musik am Anfang speichern
					$merken = $musik_vol = GetValue($id_sk1_musik_vol);
      			$musik_status 			 = GetValueInteger($id_sk1_musik_status);

					if($modus == 2)
						{
					   if($musik_status != 2)	WAC_Pause($id_sk1_musik);
						}


					if($modus == 3)
						{
						//Slider
		  			 	for ($musik_vol; $musik_vol>=1; $musik_vol--)
   					  	{
		      			WAC_SetVolume ($id_sk1_musik, $musik_vol);
      			   	$slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
			     			}
     					}

					if($ton != "" and $modus != 1)
						{
  	   				WAC_Stop($id_sk1_ton);
		      		WAC_SetRepeat($id_sk1_ton, false);
     					WAC_ClearPlaylist($id_sk1_ton);
     					WAC_AddFile($id_sk1_ton,$wav[$ton]);
		     			WAC_Play($id_sk1_ton);
		            //solange in Schleife bleiben wie 1 = play
		   	  		sleep(1);
      			  $status = getvalue($id_sk1_ton_status);
  	   			  while ($status == 1)	$status = getvalue($id_sk1_ton_status);
			 		  }

					if($ansagetext !="")
						{
  						WAC_Stop($id_sk1_ton);
			      	WAC_SetRepeat($id_sk1_ton, false);
			         WAC_ClearPlaylist($id_sk1_ton);
   			      $status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) echo "Error";
		     			WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
		     			echo "---------------------------".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";
						WAC_Play($id_sk1_ton);
						}

					//Script solange anghalten wie Sprachausgabe läuft
					if($modus != 1)
						{
			   		sleep(1);
						$status = GetValueInteger($id_sk1_ton_status);
   	  				while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);
			   		}

			 		if($modus == 3)
						{
			   		$musik_vol = GetValueInteger($id_sk1_musik_vol);
		   			for ($musik_vol=1; $musik_vol<=$merken; $musik_vol++)
		      			{
				         WAC_SetVolume ($id_sk1_musik, $musik_vol);
      	   		   $slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
      					}
      				}
					if($modus == 2)
						{
				   	if($musik_status != 2)	WAC_Pause($id_sk1_musik);
				   	}
					break;

			//---------------------------------------------------------------------

			//Hier können weitere Soundkarten eingefügt werden
			//case '2':
			//entsprechende Werte bitte anpassen

		}  //end switch
 	}   //end function


/*********************************************************************************************/




?>
