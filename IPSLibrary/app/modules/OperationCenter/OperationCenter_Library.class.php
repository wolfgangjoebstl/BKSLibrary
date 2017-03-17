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

	var $CategoryIdData       	= 0;
	var $categoryId_SysPing   	= 0;
	var $categoryId_RebootCtr 	= 0;
	var $categoryId_Access 		= 0;	
	var $archiveHandlerID     	= 0;
	var $subnet               	= "";
	var $log_OperationCenter  	= array();
	var $mactable             	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();
	var $AllHostnames         	= array();
	var $installedModules     	= array();
	
	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	public function __construct($subnet='10.255.255.255')
		{

		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

		$this->subnet=$subnet;
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

			//echo 'ModuleManager Variable not set --> Create "default" ModuleManager'."\n";
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
	   	$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
	   	$this->installedModules = $moduleManager->GetInstalledModules();

   		$this->categoryId_SysPing    	= CreateCategory('SysPing',       	$this->CategoryIdData, 200);
   		$this->categoryId_RebootCtr  	= CreateCategory('RebootCounter', 	$this->CategoryIdData, 210);
   		$this->categoryId_Access  		= CreateCategory('AccessServer', 	$this->CategoryIdData, 220);
   		$this->categoryId_SysInfo  	= CreateCategory('SystemInfo', 		$this->CategoryIdData, 230);
		
		//echo "Subnet ".$this->subnet."   ".$subnet."\n";		
        $this->mactable=$this->create_macipTable($this->subnet);
        $categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
		$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->oc_Configuration = OperationCenter_Configuration();
		$this->oc_Setup = OperationCenter_SetUp();
		$this->AllHostnames = LogAlles_Hostnames();
		}

	/**
	 * @public
	 *
	 * sys ping IP Adresse von LED Modul oder DENON Receiver
	 *
	 * config objekt von LED oder DENON Ansteuerung, Device LED oder DENON. Identifier IPADRESSE oder MAC
	 *
	 */
	function device_ping($device_config, $device, $identifier)
		{
		foreach ($device_config as $name => $config)
		   {
		   //print_r($config);
			$StatusID = CreateVariableByName($this->categoryId_SysPing,   $device."_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			$RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		   //echo "Sys_ping Led Ansteuerung : ".$name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
			$status=Sys_Ping($config[$identifier],1000);
			if ($status)
				{
			   echo "Sys_ping ".$device." Ansteuerung : ".$name." mit IP Adresse ".$config[$identifier]."                   wird erreicht       !\n";
				if (GetValue($StatusID)==false)
				   {  /* Statusänderung */
					$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
					$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
					SetValue($StatusID,true);
					SetValue($RebootID,0);
				   }
				}
			else
				{
			   echo "Sys_ping ".$device." Ansteuerung : ".$name." mit IP Adresse ".$config[$identifier]."                   wird NICHT erreicht! Zustand seit ".GetValue($RebootID)." Stunden.\n";
				if (GetValue($StatusID)==true)
				   {  /* Statusänderung */
					$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar');
					$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar');
					SetValue($StatusID,false);
				   }
				else
				   {
					SetValue($RebootID,(GetValue($RebootID)+1));
				   }
				}
		   }
		}

	/**
	 * @public
	 *
	 * sys ping IP Adresse von bekannten IP Symcon Servern
	 *
	 * Verwendet selbes Config File wie für die Remote Log Server, es wurden zusätzliche Parameter zur Unterscheidung eingeführt
	 *
	 */
	function server_ping()
		{
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$RemoteServer=array();
		//print_r($remServer);
		$method="IPS_GetName"; $params=array();

		foreach ($remServer as $Name => $Server)
			{
			//print_r($Server);
			$UrlAddress=$Server["ADRESSE"];
			if ($Server["STATUS"]=="Active")
				{
				$IPS_UpTimeID = CreateVariableByName($this->categoryId_Access, $Name."_IPS_UpTime", 1);
				IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
			
				$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */

			   $RemoteServer[$Name]["Name"]=$UrlAddress;
				$rpc = new JSONRPC($UrlAddress);
				//echo "Server : ".$UrlAddress." hat Uptime: ".$rpc->IPS_GetUptime()."\n";
				$data = @parse_url($UrlAddress);
				if(($data === false) || !isset($data['scheme']) || !isset($data['host']))
					throw new Exception("Invalid URL");
				$url = $data['scheme']."://".$data['host'];
				if(isset($data['port'])) $url .= ":".$data['port'];
				if(isset($data['path'])) $url .= $data['path'];
				if(isset($data['user']))
					{
					$username = $data['user'];
					}
				else
					{
					$username = "";
					}
				if(isset($data['pass']))
				   {
					$password = $data['pass'];
					}
				else
					{
					$password = "";
					}
				if (!is_scalar($method)) {
						throw new Exception('Method name has no scalar value');
					}
				if (!is_array($params)) {
						throw new Exception('Params must be given as array');
					}
				$id = round(fmod(microtime(true)*1000, 10000));
				$params = array_values($params);
				$strencode = function(&$item, $key) {
				if ( is_string($item) )
						$item = utf8_encode($item);
					else if ( is_array($item) )
						array_walk_recursive($item, $strencode);
					};
				array_walk_recursive($params, $strencode);
				$request = Array(
									"jsonrpc" => "2.0",
									"method" => $method,
									"params" => $params,
									"id" => $id
								);
				$request = json_encode($request);
				$header = "Content-type: application/json"."\r\n";
				if(($username != "") || ($password != "")) {
					$header .= "Authorization: Basic ".base64_encode($username.":".$password)."\r\n";
					}
				$options = Array(
						"http" => array (
						"method"  => 'POST',
						"header"  => $header,
						"content" => $request
										)
							);
				$context  = stream_context_create($options);

				$response = @file_get_contents($url, false, $context);
				if ($response===false)
				   {
					echo "   Server : ".$url." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht.\n";
					SetValue($IPS_UpTimeID,0);
					$RemoteServer[$Name]["Status"]=false;
					if (GetValue($ServerStatusID)==true)
					   {  /* Statusänderung */
						$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_'.$Name.' auf NICHT erreichbar');
						$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_'.$Name.' auf NICHT erreichbar');
						SetValue($ServerStatusID,false);
			   		}
					}
				else
				   {
				   $ServerName=$rpc->IPS_GetName(0);
				   $ServerUptime=$rpc->IPS_GetKernelStartTime();
	   		   $IPS_VersionID = CreateVariableByName($this->categoryId_Access, $Name."_IPS_Version", 3);
  				   $ServerVersion=$rpc->IPS_GetKernelVersion();
					echo "   Server : ".$UrlAddress." mit Name: ".$ServerName." und Version ".$ServerVersion." zuletzt rebootet: ".date("d.m H:i:s",$ServerUptime)."\n";
					SetValue($IPS_UpTimeID,$ServerUptime);
					SetValue($IPS_VersionID,$ServerVersion);
					$RemoteServer[$Name]["Status"]=true;
					if (GetValue($ServerStatusID)==false)
					   {  /* Statusänderung */
						$this->log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_'.$Name.' auf erreichbar');
						$this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_'.$Name.' auf erreichbar');
						SetValue($ServerStatusID,true);
			   		}
					}
			   }
			else
				{
				echo "   Server : ".$url." mit Name: ".$Name." nicht auf active konfiguriert.\n";
				}	
			}
			return ($RemoteServer);
		}

	/**
	 * @public
	 *
	 * liest systeminfo aus und speichert die relevanten Daten
	 *
	 */
	 function SystemInfo()
	 	{

		$HostnameID   		= CreateVariableByName($this->categoryId_SysInfo, "Hostname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemversion", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$HotfixID			= CreateVariableByName($this->categoryId_SysInfo, "Hotfix", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
		$result=array();	/* fuer Zwischenberechnungen */
		$results=array();
		$results2=array();
	
	$PrintSI="";
		$PrintLines="";		
		
		exec('systeminfo',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */
		foreach($catch as $line)
			{
			if (strlen($line)>2)
				{
				//echo "  | ".$line."\n<br>";
				$PrintLines.=$line."\n";
				if (substr($line,0,1)!=" ")
					{
					/* Ueberschrift */
					$pos1=strpos($line,":");
					$VarName=trim(substr($line,0,$pos1));
					$VarField=trim(substr($line,$pos1+1));
					$result[1]="";$result[2]="";$result[3]="";
					$result=explode(":",$line);
					$results[trim($result[0])]=trim($result[1]);
					for ($i=2; $i<sizeof($result); $i++) { $results[trim($result[0])].=":".trim($result[$i]);  }
					$results2[$VarName]=$VarField;
					$PrintSI.="\n".$line;
					}
				else
					{
					/* Fortsetzung der Paraemeter Ausgabe */
					$PrintSI.=" ".trim($line);
					$results[trim($result[0])].=" ".trim($line);
					$results2[$VarName].=" ".trim($line);
					}
				}  /* ende strlen */
	  		}
			echo "Ausgabe direkt:\n".$PrintLines."\n";		

			print_r($results);
			print_r($results2);
			echo $PrintSI;
		
		SetValue($HostnameID,$results["Hostname"]);
		SetValue($SystemNameID,$results["Betriebssystemname"]);
		SetValue($SystemVersionID,trim(substr($results["Betriebssystemversion"],0,strpos($results["Betriebssystemversion"]," "))));
		SetValue($HotfixID,trim(substr($results["Hotfix(es)"],0,strpos($results["Hotfix(es)"]," "))));
		
		return $results;
		}	

	/**
	 * @public
	 *
	 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
	 *
	 * Übergabe nun das Config file vom Operation Center, LED oder DENON, identifier für IPADRESSE oder IPADR
	 *
	 */
	function device_checkReboot($device_config, $device, $identifier)
		{
		foreach ($device_config as $name => $config)
		   {
		   //print_r($config);
			if (isset ($config["REBOOTSWITCH"]))
			   {
				$RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* 0 Boolean 1 Integer 2 Float 3 String */
				$reboot_ctr = GetValue($RebootID);
				$SwitchName = $config["REBOOTSWITCH"];
				$maxhours = $config["NOK_HOURS"];
				if ($reboot_ctr != 0)
				   {
					if ($reboot_ctr > $maxhours)
					   {
						echo $device."-Modul wird seit ".$reboot_ctr." Stunden nicht erreicht. Reboot ".$SwitchName." !\n";
						include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
						IPSLight_SetSwitchByName($SwitchName,false);
						sleep(2);
						IPSLight_SetSwitchByName($SwitchName,true);
					   }
					else
					   {
						echo $device."-Modul wird NICHT erreicht ! Zustand seit ".$reboot_ctr." Stunden. Max stunden bis zum Reboot ".$maxhours."\n";
						}
					}
				}
		   }
		}


	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	function create_macipTable($subnet,$printHostnames=false)
		{
		$subnetok=substr($subnet,0,strpos($subnet,"255"));
		//echo "Finde in ".$subnet." den ersten 255er :".strpos($subnet,"255")."\n";
		$ergebnis=""; $print_table="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		unset($catch);
		exec('arp -a',$catch);
		foreach($catch as $line)
   			{
   			if (strlen($line)>0)
				{
			   	$result=trim($line);
	   			$result1=substr($result,0,strpos($result," ")); /* zuerst IP Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
	   			$result2=substr($result,0,strpos($result," ")); /* danach MAC Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
				if ($result1=="10.0.255.255") { break; }
				echo "*** ".$line." Result:  ".$result1." SubnetOk: ".$subnetok." SubNet: ".$subnet." ".strlen($result1)."\n";
				if ( (strlen($result1)>0) && ((strlen($subnetok)>0) ) )
					{
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
					} // nur wenn Auswertung ueberhaupt Sinn macht
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

	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	function get_macipTable($subnet,$printHostnames=false)
		{
		return($this->mactable);
		}

	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	function find_HostNames()
	   {
	   $ergebnis="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		$manufacturers=LogAlles_Manufacturers();   /* lange Liste in Config */
		foreach ($this->mactable as $mac => $ip )
		   {
		   $result="unknown"; $result2="";
		   foreach ($ipadressen as $name => $entry)
		      {
		      //echo "Vergleiche ".$entry["Mac_Adresse"]." mit ".$mac."\n";
		      if (strtoupper($entry["Mac_Adresse"])==strtoupper($mac))
					{
					$result=$name;
					$result2=$entry["Hostname"];
					}
    	      }
    	   $manuID=substr($mac,0,8);
    	   if (isset ($manufacturers[$manuID])==true) { $manuID=$manufacturers[$manuID]; }
		   echo "   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   $ergebnis.="   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   }
		echo "\n\n";
		return ($ergebnis);
	   }

	/**
	 * schreibt die gestrigen Download/Upload und Total Werte von einem MR3430 Router
	 *
	 * Werte werden aus dem vorher ausgelesenem html file verzeichnis ausgewertet
	 *
	 */
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

	/**
	 * schreibt die gestrigen Download/Upload und Total Werte von einem MBRN3000 Router
	 *
	 * Werte werden direkt aus dem Router ausgelesen
	 *
	 */
	function write_routerdata_MBRN3000($router, $debug=false)
		{
		$ergebnis=array();
		echo "  Daten vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die Tageswerte von gestern erfasst.\n";
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
		   echo "  -->Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=@file_get_contents($url);
			if ($result===false) {
			   echo "   Fehler beim holen der Webdatei. Abbruch. \n";
			   break;
			   }
	  		}
		$result=strip_tags($result);
		//#echo $result;
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
			if ($debug==false) { SetValue($ConnTimeID,$conntime); }
			$ergebnis["ConnectionTime"]=$conntime;
			echo "    Connection Time in Minuten heute bisher : ".$conntime." sind ".($conntime/60)." Stunden.\n";

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

			if ($debug==false) { SetValue($UploadID,$Upload); }
			$ergebnis["Upload"]=$Upload;
			echo "     Upload   Datenvolumen gestern ".$Upload." Mbyte \n";;

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
			if ($debug==false) { SetValue($DownloadID,$Download); }
			$ergebnis["Download"]=$Download;
			echo "     Download Datenvolumen gestern ".$Download." MByte \n";
			
			if (($TotalID=@IPS_GetVariableIDByName("Total",$router_categoryId))==false)
  				{
			  	$DownloadID = CreateVariableByName($router_categoryId, "Total", 2);
				IPS_SetVariableCustomProfile($DownloadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$DownloadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$DownloadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
			if ($debug==false) { SetValue($TotalID,($Download+$Upload)); }
			$ergebnis["Total"]=($Download+$Upload);
			echo "     Gesamt Datenvolumen gestern ".($Download+$Upload)." MByte \n";
			}
		else
		   {
		   echo "Daten vom Router sind im falschen Format. Bitte überprüfen ob TrafficMeter am Router aktiviert ist.\n";
			$ergebnis["Fehler"]="Daten vom Router sind im falschen Format";
			}
		return $ergebnis;
		}

	/*
	 *  Routerdaten MBRN3000 direct aus dem Router auslesen,
	 *
	 *  mit actual wird definiert ob als return Wert die Gesamtwerte von heute oder gestern ausgegeben werden sollen
	 *
	 */

	function get_routerdata_MBRN3000($router,$actual=false)
		{
		echo "Daten direkt vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die aktuellen Tageswerte erfasst.\n";
		$Router_Adresse = "http://".$router["USER"].":".$router["PASSWORD"]."@".$router["IPADRESSE"]."/";
		//print_r($router);
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$url=$Router_Adresse."traffic_meter.htm";
		echo "    -> Routeradresse die aufgerufen wird : ".$url." \n";
		$result=@file_get_contents($url);
		if ($result===false) {
		   echo "Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=@file_get_contents($url);
			if ($result===false) {
			   echo "Fehler beim Holen der Webdatei. Abbruch. \n";
			   return(false);
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

	/*
	 *  Routerdaten aus der allgemeinen Datenbank auslesen,
	 *
	 *  Allgemeine Routine, sucht die Daten im entsprechenden Verzeichnis, nur Ausgabe auf echo
	 *
	 */

	function get_routerdata($router,$actual=false)
		{
		$ergebnis=0;      // Gesamtdatenvolumen heute oder gestern
		
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
		   	//print_r($werte);
		   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
		   	foreach ($werte as $wert)
		   	   {
		   	   //echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."\n";
		   	   echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
		   	   }
				$result1[IPS_GetName($oid)]=$oid;
		   	}
		   else
		      {
		   	echo "   ".IPS_GetName($oid)." Variable wird NICHT gelogged.\n";
		      }
		   }
		//ksort($result1);
		//print_r($result1);
		if ($actual==false)
		   {
			return ($ergebnis);
		   }
		}

	/*
	 *  Routerdaten Synology RT1900ac direct als SNMP Werte aus dem Router auslesen,
	 *
	 *  mit actual wird definiert ob als return Wert die Gesamtwerte von heute oder gestern ausgegeben werden sollen
	 *
	 *  derzeit gibt es keinen aktuellen Wert, da der immer vorher mit SNMP Aufrufen ausgelesen werden muesste
	 *  es fehlt SNMP Aufruf ohne logging !!!
	 */

	function get_routerdata_RT1900($router,$actual=false)
		{
		$ergebnis=0;      // Gesamtdatenvolumen heute oder gestern

		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      $name=explode("_",IPS_GetName($oid));
		      if ($name[sizeof($name)-1]=="chg")
		         {
		         if ($name["0"]=="eth0") /* In und out von eth0 zusammenzaehlen */
		            {
			         $ergebnis+=GetValue($oid)/1024/1024;
			         }
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
		   		   //echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."  ".round(($wert["Value"]/1024/1024),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60/1024),2)." kBytes/Sek  \n";
		   		   echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."  ".round(($wert["Value"]/1024/1024),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60/1024),2)." kBytes/Sek  \n";
		   	   	}
					}
		      if (IPS_GetName($oid)=="Total")
		         {
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
		   		   //echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."  ".round(($wert["Value"]),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60*1024),2)." kBytes/Sek  \n";
		   		   echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."  ".round(($wert["Value"]),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60*1024),2)." kBytes/Sek  \n";
		   	   	}
					}
		   	}
		   }
		if ($actual==false)
		   {
			return ($ergebnis);
		   }
		}


	/*
	 *  Routerdaten MR3420 aus dem datenobjekt statt direct aus dem Router auslesen,
	 *
	 *  Routine obsolet durch get_router_history
	 *
	 */

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
		      if (IPS_GetName($oid)=="Total")
		         {
		         $ergebnis=GetValue($oid);
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
		   		   //echo "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
		   		   echo "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
		   	   	}
					}
		   	}
		   }
		return $ergebnis;
		}

	/*
	 *  Routerdaten direct aus dem Archiv auslesen,
	 *
	 *
	 */

	function get_router_history($router,$start,$duration)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		//echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$ergebnisPrint="";
		$ergebnis=0;
		$dateOld="";
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      if (IPS_GetName($oid)=="Total")
		         {
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-($start+$duration)*24*60*60, time()-$start*24*60*60,1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, vor ".$start." Tagen fuer ".$duration." Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
                  if (date("d.m",$wert["TimeStamp"])==$dateOld)
                     {
                     //echo "Werte gleich : ".(date("d.m",$wert["TimeStamp"]))."\n";
	                  }
                  else
                     {
     		   		   //$ergebnisPrint.= "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
     		   		   $ergebnisPrint.= "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
                     $dateOld=date("d.m",$wert["TimeStamp"]);
                     $ergebnis += $wert["Value"];
                     }
						}
					}
		   	}
		   }
		//echo $ergebnisPrint;
		return round($ergebnis,2);
		}

	/*
	 *
	 *
	 */

	function get_data($oid)
		{
      $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
	  	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
	  	foreach ($werte as $wert)
	  	   {
	  	   //echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
	  	   echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
	  	   }
		}

	/*
	 *
	 *
	 */

	function sort_routerdata($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Wir sortieren die Routerdaten in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		/* alle mit Archivfunktion werden an den Anfang geschoben und entsprechend alphabetisch sortieren */
		$result1=array();
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      //echo "  --- ".substr(IPS_GetName($oid),0,4)."\n";
		      if ((substr(IPS_GetName($oid),0,4))=="MByt")
		         {
					$result1[IPS_GetName($oid)]=$oid;
					}
				else
				   {
					$result1["zzy".IPS_GetName($oid)]=$oid;
				   }
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


	/*
	 *  Die oft umfangreichen Logfiles in einem Ordner pro tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann.
	 *
	 */

	function MoveLogs()
		{
		$verzeichnis=IPS_GetKernelDir().'logs/';
		echo "Alle Logfiles von ".$verzeichnis." verschieben.\n";

		$count=100;
		//echo "<ol>";

		echo "Heute      : ".date("Ymd", time())."\n";
		echo "Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";
		echo "Vorgestern : ".date("Ymd", strtotime("-2 day"))."\n";
		$vorgestern = date("Ymd", strtotime("-2 day"));

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
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
						$unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
						if ($unterverzeichnis == $vorgestern)
						   {
							$count-=1;
	      	      	if (is_dir($verzeichnis.$unterverzeichnis))
   	      	   		{
      	      			}
         	   		else
								{
	            			mkdir($verzeichnis.$unterverzeichnis);
   	         			}
	   	         	rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
   	   	      	echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
   	      	   	}
         			}
	      	  	} /* Ende while */
		     	closedir($handle);
   			} /* end if dir */
			}/* ende if isdir */
		else
	   	{
		   echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}
		return (100-$count);
		}

	/****************************************************/
	/*
	 * kopiert die Scriptfiles auf ein Dropboxverzeichnis um di eFiles sicherheitshalber auch immer zur Verfügung zu haben
	 * auch wenn Github nicht mehr geht

	 */

	function CopyScripts()
		{
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		print_r($this->oc_Setup);
		$DIR_copyscriptsdropbox = $this->oc_Setup['DropboxDirectory'].IPS_GetName(0).'/';

		mkdirtree($DIR_copyscriptsdropbox);

		$count=0;

		$alleSkripte = IPS_GetScriptList();
		//print_r($alleSkripte);

		/* ein includefile mit allen Dateien erstellen, als Inhaltsverzeichnis */
		$includefile='<?'."\n".'$fileList = array('."\n";

		echo "Alle Scriptfiles werden vom IP Symcon Scriptverzeichnis auf ".$DIR_copyscriptsdropbox." kopiert und in einen Dropbox lesbaren Filenamen umbenannt.\n";
		echo "\n";

		foreach ($alleSkripte as &$value)
			{
			$filename=IPS_GetScriptFile($value);
			$name=IPS_GetName($value);
			$trans = array("," => "", ";" => "", ":" => ""); /* falsche zeichen aus filenamen herausnehmen */
			$name=strtr($name, $trans);
			$destination=$name."-".$value.".php";

			/* herausfinden ob ein Dateiname nur eine Nummer ist, dann vollstaendigen Namen und Struktur geben */
			if (preg_match('/\d+/',$filename,$zahl)==1)
				{
				if ($zahl[0]==$value)
			   	{
				   $dir="";
			   	while (($parent=IPS_GetParent($value))!=0)
			      	{
				      $Struktur=IPS_GetObject($parent);
						if ($Struktur["ObjectType"]==0) {$dir=IPS_GetName($parent).'/'.$dir;}
						$value=$parent;
						}
					$destname=$dir.$name.".ips.php";
					$trans = array("," => "", ";" => "", ":" => ""); /* falsche zeichen aus filenamen herausnehmen */
					$destname=strtr($destname, $trans);
					}
				}
			else
			   {
	   		$destname=$filename;
		   	}
			//echo "-Copy File: ".IPS_GetKernelDir().'scripts/'.$filename." : ".$name." : ".$DIR_copyscriptsdropbox.$destination."\n";
			copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);

			$includefile.='\''.$destname.'\','."\n";
			$count+=1;
	   	}
		unset($value);

		$includefile.=');'."\n".'?>';

		echo "\n";
		echo "-------------------------------------------------------------\n\n";
		echo "Insgesamt ".$count." Scripts kopiert.\n";
		}

	/*
	 * Statusinfo von AllgemeineDefinitionen in einem File auf der Dropbox abspeichern
	 *
	 */

	function FileStatus()
	   {
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		print_r($this->oc_Setup);
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';

		mkdirtree($DIR_copystatusdropbox);

		$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true).
			"\n\n************************************************************************************************************************\n";
		$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false).
			"\n\n************************************************************************************************************************\n";

		//$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
		$filename=$DIR_copystatusdropbox.date("Ymd").'StatusAktuell.txt';
		if (!file_put_contents($filename, $event1)) {
      	  	throw new Exception('Create File '.$filename.' failed!');
    			}
		$filename=$DIR_copystatusdropbox.date("Ymd").'StatusHistorie.txt';
		if (!file_put_contents($filename, $event2)) {
      	  	throw new Exception('Create File '.$filename.' failed!');
    			}
		}


	/*
	 * Statusinfo von Hardware, auslesen der Sensoren und Alarm wenn laenger keine Aktion
	 *
	 */

	function HardwareStatus()
	   {
		if (isset($this->installedModules["RemoteReadWrite"])==true)
		   {
		   IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

			$Homematic = HomematicList();
			$FS20= FS20List();

			foreach ($Homematic as $Key)
				{
				/* alle Homematic Temperaturwerte ausgeben */
				if (isset($Key["COID"]["TEMPERATURE"])==true)
	   			{
		      	$oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
					   {
						$this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						$this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						}
					}
				}

			$FHT = FHTList();
			foreach ($FHT as $Key)
				{
				/* alle FHT Temperaturwerte ausgeben */
				if (isset($Key["COID"]["TemeratureVar"])==true)
				   {
	   	   	$oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
					   {
						$this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						$this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						}
					}
				}

			$alleHumidityWerte="\n\nAktuelle Feuchtigkeitswerte direkt aus den HW-Registern:\n\n";

			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Feuchtigkeitswerte ausgeben */
				if (isset($Key["COID"]["HUMIDITY"])==true)
	   			{
	      		$oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
					echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
					   {
						$this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						$this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						}
					}
				}

			$alleMotionWerte="\n\nAktuelle Bewegungswerte direkt aus den HW-Registern:\n\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
			   	{
		   		/* alle Bewegungsmelder */

			      $oid=(integer)$Key["COID"]["MOTION"]["OID"];
   	   		$variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
				   	{
						echo str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					else
					   {
						echo str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
						}
					if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
					   {
						$this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						$this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
						}
					}
				}
			}

		}
		
	}  /* ende class */

/****************************************************************************************************************/

function move_camPicture($verzeichnis,$WebCam_LetzteBewegungID)
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
		  		   SetValue($WebCam_LetzteBewegungID,$letztesfotodatumzeit);
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
					   if($musik_status == 1)
							{
							/* wenn der Musikplayer läuft, diesen auf Pause setzen */
							WAC_Pause($id_sk1_musik);
							}
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
					   if($musik_status == 1)
							{
							/* wenn der Musikplayer läuft, diesen auf Pause setzen */
							WAC_Pause($id_sk1_musik);
							}
						}
					break;

			//---------------------------------------------------------------------

			//Hier können weitere Soundkarten eingefügt werden
			//case '2':
			//entsprechende Werte bitte anpassen

		}  //end switch
 	}   //end function


/*********************************************************************************************/


/**************************************************************************************************************/

function SysPingAllDevices($OperationCenter,$log_OperationCenter)
	{
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('OperationCenter',$repository);
		}
	$installedModules = $moduleManager->GetInstalledModules();
	$CategoryIdData   = $moduleManager->GetModuleCategoryID('data');

	echo "Sysping All Devices. Subnet : ".$OperationCenter->subnet."\n";
	$subnet=$OperationCenter->subnet;
	$OperationCenterConfig = $OperationCenter->oc_Configuration;
	//print_r($OperationCenterConfig);

	$categoryId_SysPing    = CreateCategory('SysPing',   $CategoryIdData, 200);
	$SysPingStatusID = CreateVariableByName($categoryId_SysPing, "SysPingExectime", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
	IPS_SetVariableCustomProfile($SysPingStatusID,"~UnixTimestamp");
	SetValue($SysPingStatusID,time());

	/************************************************************************************
  	 * Erreichbarkeit IPCams
	 *************************************************************************************/
	if (isset ($installedModules["IPSCam"]))
		{
		$mactable=$OperationCenter->get_macipTable($subnet);
		//print_r($mactable);
		foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
			{
			$CamStatusID = CreateVariableByName($categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			if (isset($mactable[$cam_config['MAC']]))
			   {
				echo "Sys_ping Kamera : ".$cam_name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
				$status=Sys_Ping($mactable[$cam_config['MAC']],1000);
				if ($status)
					{
					echo "Kamera wird erreicht   !\n";
					if (GetValue($CamStatusID)==false)
					   {  /* Statusänderung */
						$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
						$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
						SetValue($CamStatusID,true);
				   	}
					}
				else
					{
					echo "Kamera wird NICHT erreicht   !\n";
					if (GetValue($CamStatusID)==true)
					   {  /* Statusänderung */
						$log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
						$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
						SetValue($CamStatusID,false);
					   }
					}
				}
			else  /* mac adresse nicht bekannt */
			   {
			   echo "Sys_ping Kamera : ".$cam_name." mit Mac Adresse ".$cam_config['MAC']." nicht bekannt.\n";
			   }
			} /* Ende foreach */
		}

	/************************************************************************************
  	 * Erreichbarkeit LED Ansteuerungs WLAN Geräte
	 *************************************************************************************/
	if (isset ($installedModules["LedAnsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
		$device_config=LedAnsteuerung_Config();
		$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
		$OperationCenter->device_ping($device_config, $device, $identifier);
		$OperationCenter->device_checkReboot($OperationCenterConfig['LED'], $device, $identifier);
		}

	/************************************************************************************
  	 * Erreichbarkeit Denon Receiver
	 *************************************************************************************/
	if (isset ($installedModules["DENONsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
		$device_config=Denon_Configuration();
		$deviceConfig=array();
		foreach ($device_config as $name => $config)
		   {
		   if ( $name != "Netplayer" ) { $deviceConfig[$name]=$config; }
		   if ( isset ($config["TYPE"]) ) { if ( strtoupper($config["TYPE"]) == "DENON" ) $deviceConfig[$name]=$config; }
			}
		$device="Denon"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
		$OperationCenter->device_ping($deviceConfig, $device, $identifier);
		}

	/************************************************************************************
  	 * Erreichbarkeit Router
	 *************************************************************************************/
	$device="Router"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
	$OperationCenter->device_ping($OperationCenterConfig['ROUTER'], $device, $identifier);

	/************************************************************************************
  	 * Überprüfen ob Wunderground noch funktioniert.
	 *************************************************************************************/
	if (isset ($installedModules["IPSWeatherForcastAT"]))
	   {
	   echo "\nWunderground API überprüfen.\n";
		IPSUtils_Include ("IPSWeatherForcastAT_Constants.inc.php",     "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
		IPSUtils_Include ("IPSWeatherForcastAT_Configuration.inc.php", "IPSLibrary::config::modules::Weather::IPSWeatherForcastAT");
		IPSUtils_Include ("IPSWeatherForcastAT_Utils.inc.php",         "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
		$urlWunderground      = 'http://api.wunderground.com/api/'.IPSWEATHERFAT_WUNDERGROUND_KEY.'/forecast/lang:DL/q/'.IPSWEATHERFAT_WUNDERGROUND_COUNTRY.'/'.IPSWEATHERFAT_WUNDERGROUND_TOWN.'.xml';
		IPSLogger_Trc(__file__, 'Load Weather Data from Wunderground, URL='.$urlWunderground);
		$urlContent = @Sys_GetURLContent($urlWunderground);
		$ServerStatusID = CreateVariableByName($categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		if ($urlContent===false)
			{
			echo "Wunderground Key ist defekt oder überlastet.\n";
			if (GetValue($ServerStatusID)==true)
			   {  /* Statusänderung */
				$log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_Wunderground auf NICHT erreichbar');
				$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_Wunderground auf NICHT erreichbar');
				SetValue($ServerStatusID,false);
		   	}
			}
		else
		   {
		   echo "  -> APP ist okay !.\n";
			if (GetValue($ServerStatusID)==false)
			   {  /* Statusänderung */
				$log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_Wunderground auf Erreichbar');
				$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_Wunderground auf Erreichbar');
				SetValue($ServerStatusID,true);
		   	}
		   }
		$api = @simplexml_load_string($urlContent);
		//print_r($api);
		}

	/********************************************************
   	Sys Uptime lokaler Server ermitteln
	**********************************************************/

	echo "\nSind die LocalAccess Server erreichbar ....\n";

	$Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$CategoryIdData);
	if ($Access_categoryId==false)
		{
		$Access_categoryId = IPS_CreateCategory();       // Kategorie anlegen
		IPS_SetName($Access_categoryId, "AccessServer"); // Kategorie benennen
		IPS_SetParent($Access_categoryId,$CategoryIdData);
		}
	$IPS_UpTimeID = CreateVariableByName($Access_categoryId, IPS_GetName(0)."_IPS_UpTime", 1);
	IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
	SetValue($IPS_UpTimeID,IPS_GetKernelStartTime());
	echo "   Server : ".IPS_GetName(0)." zuletzt rebootet am: ".date("d.m H:i:s",GetValue($IPS_UpTimeID)).".\n";

	/********************************************************
   	Die entfernten logserver auf Erreichbarkeit prüfen
	**********************************************************/

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "\nSind die RemoteAccess Server erreichbar ....\n";
		$result=$OperationCenter->server_ping();
		}

	}

/****************************************************/

function CyclicUpdate()
	{
	// Repository
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$repositoryJW="https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/";

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

	$versionHandler = $moduleManager->VersionHandler();
	$versionHandler->BuildKnownModules();
	$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	$inst_modules = "Verfügbare Module und die installierte Version :\n\n";
	$inst_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";
	$loadfromrepository=array();

	foreach ($knownModules as $module=>$data)
		{
		$infos   = $moduleManager->GetModuleInfos($module);
		$inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
		if (array_key_exists($module, $installedModules))
			{
			//$html .= "installiert als ".str_pad($installedModules[$module],10)."   ";
			$inst_modules .= "installiert als ".str_pad($infos['CurrentVersion'],10)."   ";
			if ($infos['Version']!=$infos['CurrentVersion'])
				{
				$inst_modules .= "***";
				$loadfromrepository[]=$module;
				}
			}
		else
			{
			$inst_modules .= "nicht installiert            ";
		   }
		$inst_modules .=  $infos['Description']."\n";
		}

	echo $inst_modules;

	foreach ($loadfromrepository as $upd_module)
	   {
		$useRepository=$knownModules[$upd_module]['Repository'];
		echo "-----------------------------------------------------------------------------------------------------------------------------\n";
		echo "Update Module ".$upd_module." from Repository : ".$useRepository."\n";
	  	$LBG_module = new IPSModuleManager($upd_module,$useRepository);
		$LBG_module->LoadModule();
	   $LBG_module->InstallModule(true);
		}
	}
	

/****************************************************/


?>