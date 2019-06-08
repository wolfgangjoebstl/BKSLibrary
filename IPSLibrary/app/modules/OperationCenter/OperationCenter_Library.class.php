<?

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
/*                              Functions   , Klassendefinitionen                            */
/*                                                                                           */
/*********************************************************************************************/
/*********************************************************************************************
 *
 * OperationCenter
 * DeviceManagement
 * statusDisplay
 * parsefile
 * TimerHandling
 *
 */




/***************************************************************************************************************
 *
 * Routinen für Klasse  OperationCenter
 *
 * Herausfinden der eigenen externen und lokalen IP Adresse
 *   whatismyIPaddress1   verwendet http://whatismyipaddress.com/
 *   whatismyIPaddress2
 *   ownIPaddress
 *
 * sys device ping IP Adresse von LED Modul oder DENON Receiver
 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
 * Erreichbarkeit der Remote Server zum Loggen
 *
 *   device_ping  
 *   server_ping
 *   writeServerPingResults
 *
 *   SysPingAllDevices
 *   writeSysPingResults
 *
 * systeminfo 			systeminfo vom PC auslesen und lokal die relevanten Daten speichern
 * readSystemInfo		die lokalen Daten als text ausgeben
 *
 * device_checkReboot	wenn device_ping zu oft gescheitert ist
 *
 * create_macipTable 	Verwalten einer MAC-IP Tabelle
 * get_macipTable
 * find_hostnames
 *
 * write_routerdata_MR3420	schreibt die gestrigen Download/Upload und Total Werte von einem MR3430 Router 
 * write_routerdata_MBRN3000
 * get_routerdata_MBRN3000
 * get_routerdata
 * get_routerdata_RT1900
 * get_routerdata_MR3420
 * get_router_history
 * get_data
 * sort_routerdata
 *
 * Bearbeiten von FTP Cam Files als auch Logdateien
 * MoveFiles			räumt die Dateien in ein Verzeichnisse pro Tag, einstellbar ist wieviele Tage zurückliegend damit begonnen werden soll
 * MoveCamFiles			MoveFiles mit den CamFiles. Es werden alle am FTP Laufwerk abgespeicherten Bilder sofort in die Tagesverzeichnissse geschlichtet
 * PurgeFiles			die älteren Verzeichnisse loeschen, wenn () keine Parameter dann die Funktion von PurgeLogs nachstellen
 *
 * CopyCamSnapshots		die Snapshots (zB alle Tage, Stunden, Minuten die von IPSCam erstellt werden im Webfront darstellen, und dazu wegkopieren
 * showCamCaptureFiles	es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user verzeichnis für die Darstellung im Webfront kopiert
 *
 * imgsrcstring, extracttime	Hilfsroutinen
 * DirLogs, readdirToArray		Hilfsroutinen
 *
 * CopyScripts			Scriptfiles auf Dropbox kopieren
 * FileStatus			Statusfiles auf Dropbox kopieren
 * FileStatusDir		StatusFiles in Verzeichnisse verschieben
 * FileStatusDelete		verschobene Files gemeinsam mit den Verzeichnisssen loeschen
 * getFileStatusDir
 *
 * HardwareStatus
 *
 * Verwenden gemeinsames Array $HomematicSerialNumberList:
 * getHomematicSerialNumberList		erfasst alle Homematic Geräte anhand der Seriennumme und erstellt eine gemeinsame liste die mit anderen Funktionen erweiterbar ist
 * addHomematicSerialList_Typ		die Homematic Liste wird um weitere Informationen erweitert:  Typ
 * writeHomematicSerialNumberList	Ausgabe der Liste
 * getHomematicDeviceList
 *
 * getIPSLoggerErrors	aus dem HTML Info Feld des IPS Loggers die Errormeldungen wieder herausziehen
 * stripHTMLTags
 *
 *
 * Klasse parsefile
 * ================
 *
 * Klasse TimerHandling
 * =====================
 * Funktionen ausserhalb der Klassen
 *
 * move_camPicture
 * get_Data
 * extractIPaddress		
 * dirtoArray, dirtoArray2
 * tts_play				Ausgabe von Ton für Sprachansagen
 * CyclicUpdate 		updatet und installiert neue Versionen der Module
 *
 ****************************************************************************************************************/

class OperationCenter
	{

	private $log_OperationCenter;

	private $CategoryIdData, $categoryId_SysPing,$categoryId_RebootCtr,$categoryId_Access,$archiveHandlerID;
	
    var $subnet               	= "";

	var $mactable             	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();			/* Setup von Operationcenter, Verzeichnisse, Konfigurationen */
	var $AllHostnames         	= array();
	var $installedModules     	= array();
	
	var $HomematicSerialNumberList	= array();
	
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
		$this->categoryId_SysInfo  		= CreateCategory('SystemInfo', 		$this->CategoryIdData, 230);
		
		//echo "Subnet ".$this->subnet."   ".$subnet."\n";		
		$this->mactable=$this->create_macipTable($this->subnet);
		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
		$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->oc_Configuration = OperationCenter_Configuration();
		$this->oc_Setup = OperationCenter_SetUp();
		
		/* Defaultwerte vergeben, falls nicht im Configfile eingestellt */
		if (isset($this->oc_Setup['DropboxDirectory'])===false) {$this->oc_Setup['DropboxDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/';}
		if (isset($this->oc_Setup['DropboxStatusDirectory'])===false) {$this->oc_Setup['DropboxStatusDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/';}
		if (isset($this->oc_Setup['CONFIG'])===false) 
			{
			$this->oc_Setup['CONFIG']= array("MOVELOGS"  => true,"PURGELOGS" => true,"PURGESIZE"  => 10,);
			}		
		else
			{
			if (isset($this->oc_Setup['CONFIG']['MOVELOGS'])===false) {$this->oc_Setup['CONFIG']['MOVELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGELOGS'])===false) {$this->oc_Setup['CONFIG']['PURGELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGESIZE'])===false) {$this->oc_Setup['CONFIG']['PURGESIZE']=10;}
			}		
		$this->AllHostnames = LogAlles_Hostnames();
		}
		
/****************************************************************************************************************/

    public function getSetup()
        {
        return ($this->oc_Setup);
        }

    public function getConfiguration()
        {
        return ($this->oc_Configuration);
        }

    public function getCategoryIdData()
        {
        return ($this->CategoryIdData);
        }


	/**
	 * @public
	 *
	 * what is my IP Adresse liefert eigene IP Adresse
	 *
	 * verwendet zwei unterschiedliche Methoden. 
	 *		address1 sucht nach Punkten mit kleiner 4 Zeichen Abstand, Ergebniss werden als Array ausgegeben pos und IP
	 *		address2 sucht nach einem Keywort. Ausgabe als String.
	 *
	 * Variante1 ist mehr generisch
	 *
	 */
	 
	function whatismyIPaddress1()
		{
		$posP[0]["IP"]="unknown";
		$url="http://checkip.dyndns.com/";
		$posP[0]["Server"]=$url;
		
		//$url="http://whatismyipaddress.com/";  //auch gesperrt,  da html 1.1
		//$url="http://www.whatismyip.com/";  //gesperrt
		//$url="http://whatismyip.org/"; // java script
		//$url="http://www.myipaddress.com/show-my-ip-address/"; // check auf computerzugriffe
		//$url="http://www.ip-adress.com/"; //gesperrt

		/* ab und zu gibt es auch bei der whatismyipaddress url timeouts, 30sek maximum timeout 
		   d.h. Timeout: Server wird nicht erreicht
			Zustand false: kein Internet
		*/

		//$result=file_get_contents($url);
		$result1=get_data($url);

		/* letzte Alternative ist die Webcam selbst */

		echo "\n";
		if ($result1==false)
			{
			echo "Server reagiert nicht. Ip Adresse anders ermitteln.\n";
			return ($posP);
			}
		else	
			{
			$i=0;
			//echo "Variante wenn IPv4 Adresse als Tag im html steht :\n".$result1."\n";			
			$pos_start=strpos($result1,"whatismyipaddress.com/ip")+25;
			//echo "Suche Punkte:\n";
			$result2=$result1; $result=$result1;
			$pos=0; 
			while (strpos($result2,".")!==false)
				{
				$pos1=strpos($result2,".");
				$result2=substr($result2,$pos1+1);
				$pos+=$pos1+1; 
				//echo ":".$pos."(".substr($result,$pos-1,5).")";
				if ($pos1<4)  // zwei Punkte knapp beieinander
					{
					if (is_numeric(substr($result,$pos-$pos1-1,$pos1)) ) // das Ergebnis zwischen den Punkten ist numerisch, hurrah
						{
						//echo "    ".substr($result,$pos-$pos1-1,$pos1)." ist numerisch. zwei Punkte mit Abstand kleiner 4 gefunden. Wir sind auf Pos ".$pos."\n";
						// Was ist vor dem Punkt, auch eine Zahl ?
						$digits=0;
						//echo "|".substr($result,($pos-$pos1-5),16)."|";
						if ( is_numeric(substr($result,($pos-$pos1-3),1)) ) { $digits=1; }
						if ( is_numeric(substr($result,($pos-$pos1-4),1)) ) { $digits=2; }
						if ( is_numeric(substr($result,($pos-$pos1-5),1)) ) { $digits=3; }
						$posIP=$pos-($digits+$pos1+3);
						$result3=extractIPaddress(trim(substr($result,$posIP,20)));
						//echo "   Evaluate  ".($result3)."  Erste Zahl hat ".$digits." Stellen.\n";
						if (filter_var($result3, FILTER_VALIDATE_IP))
							{
							$found=false;
							if ($i>0)
								{
								if ($posP[$i-1]["IP"]==$result3)
									{
									//echo "IP Adresse schon einmal gefunden, nicht noch einmal speichern.\n";
									$found=true;
									}
								}
							if ($found == false)
								{		
								$posP[$i]["Pos"]=$posIP;
								$posP[$i++]["IP"]=$result3; 
								// "   IP Adresse gefunden   (Pos: ".$posIP.") :    ".trim(substr($result,$posIP,20))."\n";
								}
							}
						} 
					}
				}
			
			
			//echo "Variante wenn IPv4 Adresse als normaler Text im html steht :\n";			
			$result=strip_tags($result1);
			$result2=$result;
			$pos=0; 
			
			while (strpos($result2,".")!==false)
				{
				$pos1=strpos($result2,".");
				$result2=substr($result2,$pos1+1);
				$pos+=$pos1+1; 
				//echo ":".$pos."(".substr($result,$pos-1,5).")";
				if ($pos1<4)  // zwei Punkte knapp beieinander
					{
					if (is_numeric(substr($result,$pos-$pos1-1,$pos1)) ) // das Ergebnis zwischen den Punkten ist numerisch, hurrah
						{
						//echo "    ".substr($result,$pos-$pos1-1,$pos1)." ist numerisch. zwei Punkte mit Abstand kleiner 4 gefunden. Wir sind auf Pos ".$pos."\n";
						// Was ist vor dem Punkt, auch eine Zahl ?
						$digits=0;
						//echo "|".substr($result,($pos-$pos1-5),16)."|";
						if ( is_numeric(substr($result,($pos-$pos1-3),1)) ) { $digits=1; }
						if ( is_numeric(substr($result,($pos-$pos1-4),1)) ) { $digits=2; }
						if ( is_numeric(substr($result,($pos-$pos1-5),1)) ) { $digits=3; }
						$posIP=$pos-($digits+$pos1+3);
						$result3=extractIPaddress(trim(substr($result,$posIP,20)));
						//echo "   Evaluate  ".($result3)."  Erste Zahl hat ".$digits." Stellen.\n";
						if (filter_var($result3, FILTER_VALIDATE_IP))
							{
							$found=false;
							if ($i>0)
								{
								if ($posP[$i-1]["IP"]==$result3)
									{
									//echo "IP Adresse schon einmal gefunden, nicht noch einmal speichern.\n";
									$found=true;
									}
								}
							if ($found == false)
								{		
								$posP[$i]["Pos"]=$posIP;
								$posP[$i++]["IP"]=$result3; 
								// "   IP Adresse gefunden   (Pos: ".$posIP.") :    ".trim(substr($result,$posIP,20))."\n";
								}
							}
						} 
					}
				}
			//echo "\n";	
			return ($posP);
	   		}
		}

	function whatismyIPaddress2()
		{
		$url="http://whatismyipaddress.com/";  
		$result=get_data($url);
		if ($result==false)
			{
			echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";
			return (false);
			}
		else	
			{
			$result=strip_tags($result);
			$pos_start=strpos($result,"Your IPv4 Address Is:")+21;		
			$subresult=trim(substr($result,$pos_start,40));
			//$pos_length=strpos($subresult,"\"");
			//$pos_length=strpos($subresult,chr(10));
			//echo "Startpos: ".$pos_start." Length: ".$pos_length." \n".$subresult."\n";
			//$subresult=substr($subresult,0,$pos_length);
			//echo "Whatismyipaddress liefert : ".$subresult."\n";
			if (filter_var($subresult, FILTER_VALIDATE_IP))
				{		
				//echo "Ausgabe ".$subresult."\n";	
				return ($subresult);
				}
			else
				{
				$result=self::whatismyIPaddress1();
				//echo "Ausgabe der Alternativwerte.\n";
				//print_r($result);
				if ( isset($result[0]["IP"]) ) { return ($result[0]["IP"]); }
				else { return ("unknown"); }
				}	
			}
		}	

	/**
	 * @public
	 *
	 * ownIPaddress liefert eigene IP Adresse
	 *
	 * Output ist ein array mit allen gefundenen IP Adressen und deren Ports
	 *
	 */
	 
	function ownIPaddress()
		{
	   
		/********************************************************
	   	Eigene Ip Adresse immer ermitteln
		**********************************************************/

		echo "\nIPConfig Befehl liefert ...\n";
		$ipall=""; $hostname="unknown"; $lookforgateway=false;
		exec('ipconfig /all',$catch);   /* braucht ein MSDOS Befehl manchmal laenger als 30 Sekunden zum abarbeiten ? */
		//exec('ipconfig',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden, allerdings fehlt dann der Hostname */

		$ipports=array();

		foreach($catch as $line)
   			{
			if (strlen($line)>2)
				{
				//echo "  | ".$line."\n<br>";
				if (substr($line,0,1)!=" ")
					{
					//echo "-------------------> Ueberschrift \n";
					$portname=substr($line,0,strpos($line,":"));
					}
   				if(preg_match('/IPv4-Adresse/i',$line))
	   				{
					//echo "Ausgabe catch :".$line."\n<br>";
   	   				list($t,$ip) = explode(':',$line);
	      			$result = extractIPaddress($ip);
    	  			$ipports[$result]["Name"]=$portname;
	    	     	$ipall=$ipall." ".$result;
	        	 	$lookforgateway=true;
		      		/* if(ip2long($ip > 0))
				   		{
	      		   		$ipports[]=$ip;
    	     			$ipall=$ipall." ".$ip;
			         	$status2=true;
						$pos=strpos($ipall,"(");  // bevorzugt eliminieren
						$ipall=trim(substr($ipall,0,$pos));
   	   	   				}  */
		      		}
		      	if ($lookforgateway==true)
					{
					if(preg_match('/Standardgateway/i',$line))
	   					{
						//echo "Ausgabe catch :".$line."\n<br>";
	   		   			list($t,$gw) = explode(':',$line);
    	  				$gw = extractIPaddress($gw);
      					$ipports[$result]["Gateway"]=$gw;
	        	 		$lookforgateway=false;
						}
					}
   				if(preg_match('/Hostname/i',$line))
	   				{
	   				list($t,$hostname) = explode(':',$line);
	      			$hostname = trim($hostname);
					}
				}  /* ende strlen */
		  	}
		if ($ipall == "") {$ipall="unknown";}

		echo "\n";
		echo "Hostname ist          : ".$hostname."\n";
		echo "Eigene IP Adresse ist : ".$ipall."\n";
		echo "\n";

		//print_r($ipports);
		return ($ipports);
		}

/****************************************************************************************************************/

	/**
	 * @public
	 *
	 * sys ping IP Adresse von LED Modul, DENON Receiver oder einem anderem generischem Device
	 *
	 * es wird ein Statuseintrag und ein reboot Counter Eintrag erstellt und bearbeitet
	 * Eine Statusänderung erzeugt einen Eintrag im OperationCenter Logfile
	 *
	 * config objekt von LED oder DENON Ansteuerung, Device LED oder DENON. Identifier IPADRESSE oder MAC
	 *
	 * ROUTER
	 * es wird die Konfiguration aus OperationCenter_Configuration()["ROUTER"] übergeben, identifier=IPADRESSE, device=router
	 *
	 */
	function device_ping($device_config, $device, $identifier)
		{
		foreach ($device_config as $name => $config)
			{
			//print_r($config);
			$StatusID = CreateVariableByName($this->categoryId_SysPing,   $device."_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			$RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			if (isset($config[$identifier])==true)
				{
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
				}	/* falsche Konfigurationen ignorieren */
			}
		}

	/**
	 * @public
	 *
	 * rpc call (sys ping) der IP Adresse von bekannten IP Symcon Servern
	 *
	 * Verwendet selbes Config File wie für die Remote Log Server, es wurden zusätzliche Parameter zur Unterscheidung eingeführt
	 *
	 * Wenn der Remote Server erreichbar ist werden Kernel Version udn Uptime abngefragt und lokal gespeichert
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
					echo "   Server : ".$UrlAddress." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht.\n";
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
				echo "   Server : ".$UrlAddress." mit Name: ".$Name." nicht auf active konfiguriert.\n";
				}	
			}
			return ($RemoteServer);
		}
		
	/*****************************************************************************
	 *
	 * Die Ergebnisse des Server Pings als String ausgeben
	 *
	 *******************************************************************************/	
		
	function writeServerPingResults()
		{

		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$result="Status der Remote Access Server (Name, Configuration, Logging und Status); Modul RenoteAccess ist installiert:\n\n";

		foreach ($remServer as $Name => $Server)
			{
			$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			if ( GetValue($ServerStatusID)==true )
				{
				$result .= str_pad($Name,30).str_pad($Server["STATUS"],15).str_pad($Server["LOGGING"],15)."erreichbar\n";
				}
			else
				{
				$result .= str_pad($Name,30).str_pad($Server["STATUS"],15).str_pad($Server["LOGGING"],15)."abwesend\n";
				}

			}
		return($result);	
		}

	/*************************************************************************************************************
	 *
	 * function SysPingAllDevices
	 *
	 * Pingen von IP Geräten, Aufgelistet im Konfigurationsfile:
	 *
	 * CAM config file, ping mit sysping
	 *
	 * LED config File, ping mit device_ping
	 *
	 * Denon
	 *
	 * Router, ping mit device_ping (echtes sys_ping aus IP Symcon Funktionsliste)
	 *				OperationCenterConfig['ROUTER']
	 *
	 * Wunderground
	 *
	 * localAccess Server, wie für die Remote Abfrage, den lokalen Wert setzen
	 *
	 * RemoteAccess Server, rpc call ping mit function server_ping
	 *	Aufruf von function server_ping()
	 *				RemoteAccess_Configuration.inc.php : RemoteAccess_GetServerConfig() wenn set to Active
	 *				
	 *
	 *
	 *
	 * in Data/OperationCenter/Sysping/ pro Gerät ein Status angelegt
	 * in Data/OperationCenter/RebootCounter/ pro Gerät ein Reboot Request Counter angelegt
	 *
	 ********************************************************************************************************************/

	function SysPingAllDevices($log_OperationCenter)
		{
		echo "Sysping All Devices. Subnet : ".$this->subnet."\n";

		$OperationCenterConfig = $this->oc_Configuration;
		//print_r($OperationCenterConfig);
		
		$SysPingStatusID = CreateVariableByName($this->categoryId_SysPing, "SysPingExectime", 1); /* 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableCustomProfile($SysPingStatusID,"~UnixTimestamp");
		SetValue($SysPingStatusID,time());

		/************************************************************************************
		 * Erreichbarkeit IPCams
		 *************************************************************************************/
		 
		if (isset ($this->installedModules["IPSCam"]))
			{
			$mactable=$this->get_macipTable($this->subnet);
			//print_r($mactable);
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				$CamStatusID = CreateVariableByName($this->categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
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
		if (isset ($this->installedModules["LedAnsteuerung"]))
			{
			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
			$device_config=LedAnsteuerung_Config();
			$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
			$this->device_ping($device_config, $device, $identifier);
			$this->device_checkReboot($OperationCenterConfig['LED'], $device, $identifier);
			}

		/************************************************************************************
		 * Erreichbarkeit Denon Receiver
		 *************************************************************************************/
		if (isset ($this->installedModules["DENONsteuerung"]))
			{
			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
			$device_config=Denon_Configuration();
			$deviceConfig=array();
			foreach ($device_config as $name => $config)
				{
				if ( $name != "Netplayer" ) { $deviceConfig[$name]=$config; }
				if ( isset ($config["TYPE"]) ) { if ( strtoupper($config["TYPE"]) == "DENON" ) $deviceConfig[$name]=$config; }
				}
			$device="DENON"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
			$this->device_ping($deviceConfig, $device, $identifier);
			$this->device_checkReboot($OperationCenterConfig['DENON'], $device, $identifier);
			}

		/************************************************************************************
		 * Erreichbarkeit Router
		 *************************************************************************************/
		$device="Router"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
		$this->device_ping($OperationCenterConfig['ROUTER'], $device, $identifier);
		$this->device_checkReboot($OperationCenterConfig['ROUTER'], $device, $identifier);
		
		/************************************************************************************
		 * Erreichbarkeit Internet
		 *************************************************************************************/
		$device="Internet"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
		$this->device_ping($OperationCenterConfig['INTERNET'], $device, $identifier);
		$this->device_checkReboot($OperationCenterConfig['INTERNET'], $device, $identifier);		

		/************************************************************************************
		 * Überprüfen ob Wunderground noch funktioniert.
		 *************************************************************************************/
		if (isset ($this->installedModules["IPSWeatherForcastAT"]))
			{
			echo "\nWunderground API überprüfen.\n";
			IPSUtils_Include ("IPSWeatherForcastAT_Constants.inc.php",     "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
			IPSUtils_Include ("IPSWeatherForcastAT_Configuration.inc.php", "IPSLibrary::config::modules::Weather::IPSWeatherForcastAT");
			IPSUtils_Include ("IPSWeatherForcastAT_Utils.inc.php",         "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
			$urlWunderground      = 'http://api.wunderground.com/api/'.IPSWEATHERFAT_WUNDERGROUND_KEY.'/forecast/lang:DL/q/'.IPSWEATHERFAT_WUNDERGROUND_COUNTRY.'/'.IPSWEATHERFAT_WUNDERGROUND_TOWN.'.xml';
			IPSLogger_Trc(__file__, 'Load Weather Data from Wunderground, URL='.$urlWunderground);
			$urlContent = @Sys_GetURLContent($urlWunderground);
			$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
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

		$Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$this->CategoryIdData);
		if ($Access_categoryId==false)
			{
			$Access_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($Access_categoryId, "AccessServer"); // Kategorie benennen
			IPS_SetParent($Access_categoryId,$this->CategoryIdData);
			}
		$IPS_UpTimeID = CreateVariableByName($Access_categoryId, IPS_GetName(0)."_IPS_UpTime", 1);
		IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
		SetValue($IPS_UpTimeID,IPS_GetKernelStartTime());
		echo "   Server : ".IPS_GetName(0)." zuletzt rebootet am: ".date("d.m H:i:s",GetValue($IPS_UpTimeID)).".\n";

		/********************************************************
		Die entfernten logserver auf Erreichbarkeit prüfen
		**********************************************************/

		if (isset ($this->installedModules["RemoteAccess"]))
			{
			echo "\nSind die RemoteAccess Server erreichbar ....\n";
			$result=$this->server_ping();
			}

		}

	/*************************************************************************************************************
	 *
	 * writeSysPingResults() , in Textform die Ergebnisse des Befehls SysPingAllDevices ausgeben
	 *
	 * es werden die Dateneintraege analysiert und ausgegeben
	 *   Erreichbarkeit IPCams
	 *	 writeServerPingResults()
	 *
	 *
	 **************************************************************************************************************/

	function writeSysPingResults($actual=true)
		{
		$result="";

		$OperationCenterConfig = $this->oc_Configuration;
		//print_r($OperationCenterConfig);
		
		/************************************************************************************
		 * Erreichbarkeit IPCams
		 *************************************************************************************/
		$result .= "Erreichbarkeit der IPCams:\n\n";
		 
		if (isset ($this->installedModules["IPSCam"]))
			{
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				$CamStatusID = CreateVariableByName($this->categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				if ( GetValue($CamStatusID)==true )
					{
					$result .= str_pad($cam_name,30)."erreichbar\n";
					}
				else
					{
					$result .= str_pad($cam_name,30)."abwesend\n";
					}
				if ( (AC_GetLoggingStatus($this->archiveHandlerID,$CamStatusID)) && ($actual==false) )
		   			{
					/* schauen ob sich etwas in der Vergangenheit getan hat */
					//echo "Loggingstatus aktiv.\n";
        			$werte = AC_GetLoggedValues($this->archiveHandlerID,$CamStatusID, time()-30*24*60*60, time(),1000);
					//print_r($werte);
					}
				} /* Ende foreach */
			}

		$result .= "\nAusfallsstatistik der konfigurierten Geraete:\n\n";			
		$childrens=IPS_GetChildrenIDs($this->categoryId_SysPing);
		echo "Sysping Statusdaten liegen in der Kategorie SysPing unter der OID: ".$this->categoryId_SysPing." \n";
		$result1=array();
		foreach($childrens as $oid)
			{
			if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		   		{
        		$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000); 
		   		//print_r($werte);
		   		echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
				$status=getValue($oid); $first=true; $timeok=0;
		   		foreach ($werte as $wert)
		   	   		{
					if ($status!==$wert["Value"])
						{
						/* Aenderung */
						if ($first==true)
							{
							/* sollte eigentlich nicht sein dass der erste Eintrag eine Aenderung ist */
							If ($wert["Value"]==true) echo "     Zuletzt wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
							If ($wert["Value"]==false) echo "    Zuletzt ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
							$first=false;
							}
						else
							{
							If ($wert["Value"]==true) 
								{
								echo "     Wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
								$timeok=$wert["TimeStamp"];
								}
							If ($wert["Value"]==false) 
								{
								$dauer=(($timeok-$wert["TimeStamp"])/60);
								echo "    Ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])." Dauer ".number_format($dauer,2)." Minuten.\n";
								if ($dauer>100)	$result .= "        Ausfall länger als 100 Minuten am ".date("D d.m H:i:s",$wert["TimeStamp"])." fuer ".number_format($dauer,2)." Minuten.\n";
								}
							}	
						$status=$wert["Value"];
						}
					else
						{
						/* keine Aenderung, erster Eintrag im Logfile, so sollte es sein */
						if ($first==true)
							{
							If ($wert["Value"]==true) 
								{
								echo "     Zuletzt wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
								$result .= IPS_GetName($oid).": Verbindung zuletzt wiederhergestellt am ".date("D d.m H:i:s",$wert["TimeStamp"])."\n";
								$timeok=$wert["TimeStamp"];
								}
							If ($wert["Value"]==false) echo "    Zuletzt ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
							$first=false;
							}
						}	
						
		   	   		//echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."\n";
		   	   		//echo "       Wert : ".str_pad(($wert["Value"] ? "Ein" : "Aus"),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
		   	   		}
				$result1[IPS_GetName($oid)]=$oid;
		   		}
			else
		    	{
				echo "   ".IPS_GetName($oid)." Variable wird NICHT gelogged.\n";
		    	}
			}
		print_r($result1);	
							
		$result .= "\n";

		/************************************************************************************
		 * Erreichbarkeit Remote Access Server
		 *************************************************************************************/
		
		if (isset ($this->installedModules["RemoteAccess"]))
			{		
			$result .= $this->writeServerPingResults();
			}
		return($result);
		}

/**************************************************************************************************************/

	/**
	 * @public
	 *
	 * liest systeminfo aus und speichert die relevanten Daten als Register
	 *
	 */
	 function SystemInfo($debug=false)
	 	{

		$HostnameID   		= CreateVariableByName($this->categoryId_SysInfo, "Hostname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemversion", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$HotfixID			= CreateVariableByName($this->categoryId_SysInfo, "Hotfix", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$ExternalIP			= CreateVariableByName($this->categoryId_SysInfo, "ExternalIP", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$UptimeID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_UpTime", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$VersionID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_Version", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
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
		if ($debug) echo "Ausgabe direkt:\n".$PrintLines."\n";		

		//print_r($results);
		//print_r($results2);
		//echo $PrintSI;
		
		//$IPAdresse=$this->whatismyIPaddress2();
		//$results["ExterneIP"]=$IPAdresse;
		$IPAdresse=$this->whatismyIPaddress1()[0]["IP"];
		if (GetValue($ExternalIP) !== $IPAdresse) SetValue($ExternalIP,$IPAdresse);
		$results["ExterneIP"]=$IPAdresse;

		$ServerUptime=date("D d.m.Y H:i:s",IPS_GetKernelStartTime());
		$results["IPS_UpTime"]=$ServerUptime;

		$ServerVersion=IPS_GetKernelVersion();
		$results["IPS_Version"]=$ServerVersion;
		
		SetValue($HostnameID,$results["Hostname"]);
		SetValue($SystemNameID,$results["Betriebssystemname"]);
		SetValue($SystemVersionID,trim(substr($results["Betriebssystemversion"],0,strpos($results["Betriebssystemversion"]," "))));
		SetValue($HotfixID,trim(substr($results["Hotfix(es)"],0,strpos($results["Hotfix(es)"]," "))));
		SetValue($UptimeID,$ServerUptime);
		SetValue($VersionID,$ServerVersion);
						
		return $results;
		}	
	
	/*******************
	 *
	 * Die von SystemInfo aus dem PC (via systeminfo) ausgelesenen und gespeicherten Daten werden für die Textausgabe formatiert und angereichert
	 *
	 ***************************************************/
									
	 function readSystemInfo()
	 	{
		$PrintLn="";
		$HostnameID   		= CreateVariableByName($this->categoryId_SysInfo, "Hostname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemversion", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$Version=explode(".",getValue($SystemVersionID));
		//print_r($Version);
		switch ($Version[2])
			{
			/* siehe wikipedia Eintrag : https://en.wikipedia.org/wiki/Windows_10_version_history für die Uebersetzung der PC Versionen */
			case "10240": $Codename="RTM (Threshold 1)"; break;
			case "10586": $Codename="November Update (Threshold 2)"; break;
			case "14393": $Codename="Anniversary Update (Redstone 1)"; break;
			case "15063": $Codename="Creators Update (Redstone 2)"; break;
			case "16299": $Codename="Fall Creators Update (Redstone 3)"; break;
			case "17134": $Codename="Spring Creators Update (Redstone 4)"; break;
			case "17763": $Codename="Fall 2018 Update (Redstone 5)"; break;			
			default: $Codename=$Version[2];break;
			}			
		$HotfixID			= CreateVariableByName($this->categoryId_SysInfo, "Hotfix", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$ExternalIP			= CreateVariableByName($this->categoryId_SysInfo, "ExternalIP", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$UptimeID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_UpTime", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$VersionID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_Version", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$PrintLn.="   ".str_pad("Hostname",30)." = ".str_pad(GetValue($HostnameID),30)."   (".date("d.m H:i",IPS_GetVariable($HostnameID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Name",30)." = ".str_pad(GetValue($SystemNameID),30)."   (".date("d.m H:i",IPS_GetVariable($SystemNameID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Version",30)." = ".str_pad(GetValue($SystemVersionID),30)."   (".date("d.m H:i",IPS_GetVariable($SystemVersionID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Codename",30)." = ".str_pad($Codename,30)."   \n";
		$PrintLn.="   ".str_pad("Anzahl Hotfix",30)." = ".str_pad(GetValue($HotfixID),30)."   (".date("d.m H:i",IPS_GetVariable($HotfixID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("External IP Adresse",30)." = ".str_pad(GetValue($ExternalIP),30)."   (".date("d.m H:i",IPS_GetVariable($ExternalIP)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("IPS Uptime",30)." = ".str_pad(GetValue($UptimeID),30)."   (".date("d.m H:i",IPS_GetVariable($UptimeID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("IPS Version",30)." = ".str_pad(GetValue($VersionID),30)."   (".date("d.m H:i",IPS_GetVariable($VersionID)["VariableChanged"]).") \n";
		return ($PrintLn);
		}

/****************************************************************************************************************/

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
			if (isset ($config["NOK_HOURS"]))
				{
				$RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* 0 Boolean 1 Integer 2 Float 3 String */
				if (AC_GetLoggingStatus($this->archiveHandlerID,$RebootID) === false)
					{ // nachtraeglich Loggingstatus setzen
					AC_SetLoggingStatus($this->archiveHandlerID,$RebootID,true);
					AC_SetAggregationType($this->archiveHandlerID,$RebootID,0);
					IPS_ApplyChanges($this->archiveHandlerID);
					}
				$reboot_ctr = GetValue($RebootID);
				$maxhours = $config["NOK_HOURS"];
				if ($reboot_ctr != 0)
					{
					if ($reboot_ctr > $maxhours)
						{
						if (isset ($config["REBOOTSWITCH"]))
							{
							$SwitchName = $config["REBOOTSWITCH"];
							echo $device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht. Reboot ".$SwitchName." !\n";
							include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
							IPSLight_SetSwitchByName($SwitchName,false);
							sleep(2);
							IPSLight_SetSwitchByName($SwitchName,true);
							$this->log_OperationCenter->LogMessage($device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht. Reboot ".$SwitchName." erfolgt");
							$this->log_OperationCenter->LogNachrichten($device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht. Reboot ".$SwitchName." erfolgt");							
							}
						else
							{
                            if ($reboot_ctr<100)
                                {   /* die ersten 100 Stunden, Nachricht jede Stunde ausgeben, dann nur mehr einmal am Tag */
							    $this->log_OperationCenter->LogMessage($device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht.");
							    $this->log_OperationCenter->LogNachrichten($device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht.");							
                                }
                            elseif (($reboot_ctr%24)==0)
                                {
                                $days=round($reboot_ctr/24,0);
							    $this->log_OperationCenter->LogMessage($device."_".$name." wird seit ".$days." Tagen nicht erreicht.");
							    $this->log_OperationCenter->LogNachrichten($device."_".$name." wird seit ".$days." Tagen nicht erreicht.");                                
                                }
							}	
						}
					else
						{
						echo $device."_".$name." wird NICHT erreicht ! Zustand seit ".$reboot_ctr." Stunden. Max stunden bis zum Reboot ".$maxhours."\n";
						}
					}
				}
			}
		}

/****************************************************************************************************************/

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
				//echo "*** ".$line." Result:  ".$result1." SubnetOk: ".$subnetok." SubNet: ".$subnet." ".strlen($result1)."\n";
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

/****************************************************************************************************************/

	/**
	 * sZusammenfassung der ActionButtons der class OperationCenter
	 *
	 * derzeit sind es die ActionButtons der SNMP Router Erfassung
	 *
	 */
	
	function get_ActionButton()
		{	
		$actionButton=array();
		
		foreach ($this->oc_Configuration['ROUTER'] as $router)
			{
	        if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
	            {
	
	            }
	        else
	            {
				//echo "get_ActionButton: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";				
	        	$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
			    if ($router_categoryId !== false)
					{
		            switch (strtoupper($router["TYP"]))
	    	            {
				        case 'B2368':
			        	case 'RT1900AC':
							//print_r($router);
							if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
								{	
								$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);				// FastPoll Kategorie anlegen
								$SchalterFastPoll_ID=@IPS_GetObjectIDByName("SNMP Fast Poll",$fastPollId);		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
								$actionButton[$SchalterFastPoll_ID]["ActivateTimer"]=true;
								}
	                    	break;
						}          // ende switch
	                }
				}
			}
		return($actionButton);
		}

	/**
	 * schreibt die gestrigen Download/Upload und Total Werte von einem MR3430 Router
	 * dazu wird das von imacro eingelesene Textfile geparsed
	 *
	 * Werte werden aus dem vorher ausgelesenem html file verzeichnis ausgewertet
	 *
	 */
	 
	function write_routerdata_MR3420($router)
		{
        if (isset($router["DownloadDirectory"])) $downloadDir=$router["DownloadDirectory"];
        else $downloadDir = $this->oc_Setup["DownloadDirectory"];

	    $verzeichnis=$downloadDir."report_router_".$router['TYP']."_".$router['NAME']."_files/";
        echo "Aufruf write_routerdata_MR3420 , Datei in $verzeichnis : \n";            
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
	   	$verzeichnis=$downloadDir."report_router_".$router['TYP']."_".$router['NAME']."_Statistics_files/";
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
		IPSLogger_Dbg(__file__, "Router MBRN3000 Auswertung gestartet, traffic meter von gestern holen.");
		$url=$Router_Adresse."traffic_meter.htm";
		$result=@file_get_contents($url);
		if ($result===false) {
		   echo "  -->Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=@file_get_contents($url);
			if ($result===false) 
				{
			   	echo "   Fehler beim holen der Webdatei. Abbruch. \n";
				IPSLogger_Dbg(__file__, "OperationCenter: Fehler beim Holen der Webdatei. Abbruch.");			   
			   	$fatalerror=true;
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
	 *  Routerdaten vom MBRN3000 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  --> derzeit nicht verwendet
	 *
	 */

	function read_routerdata_MBRN3000($router)
		{
		 /* siehe write_router weiter oben....   */
		 
		write_routerdata_MBRN3000($router);
		
		}

	/*
	 *  Routerdaten vom MR3420 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  --> derzeit nicht verwendet
	 *
	 */

	function read_routerdata_MR3420($router)
		{
		IPS_ExecuteEX($this->oc_Setup["FirefoxDirectory"]."firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
		}

	/*
	 *  Routerdaten vom B2368 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  
	 *
	 */

	function read_routerdata_B2368($router_categoryId, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug, $useSnmpLib);							
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.1.0", "wan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.1.0", "wan0_ifOutOctets", "Counter32");
        $snmp->update(false,"wan0_ifInOctets","wan0_ifOutOctets");  
		}

	/*
	 *  Routerdaten vom RT1900AC auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  blöd ist das die Interface Nummern auch irgendwie von der eingestellten Konfiguration abhängen.
	 *  langfristig auf die Beschreibung wie zB eth0 ausweichen
	 *
	 */

	function read_routerdata_RT1900AC($router_categoryId, $host, $community, $binary, $debug=false)
		{
		/* Interface Nummer 4,5,8   4 ist der Uplink */
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
        $snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
		}

	function read_routerdata_RT2600AC($router_categoryId, $host, $community, $binary, $debug=false)
		{
		/* Interface Nummer 4,6,15   4 ist der Uplink, 6 ist eth2 und Lankabel steckt auf Ethernet LAN Port 1 */
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");		// Uplink, WAN Port
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");		// Video or Ethernet
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.6", "eth2_ifInOctets", "Counter32");		// Ethernet

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.6", "eth2_ifOutOctets", "Counter32");

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.15", "wlan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.15", "wlan0_ifOutOctets", "Counter32");
        $snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
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
	 *
	 * Variablennamen in der Category sind kodiert und mit _ getrennt. Der erste Teil ist der Name des Ports und der letzte der Status
	 *
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
			$analyze=@AC_GetLoggingStatus($this->archiveHandlerID,$oid);
			echo "   Analysiere $oid (".IPS_GetName($oid).")  : ".($analyze?"Ja":"Nein")."\n";
			if ($analyze)
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
			$analyze=@AC_GetLoggingStatus($this->archiveHandlerID,$oid);
			echo "   Analysiere $oid (".IPS_GetName($oid).")  : ".($analyze?"Ja":"Nein")."\n";
			if ($analyze)
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
	 *  Die oft umfangreichen Files in einem Ordner pro Tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann. 
	 *   Verzeichnis: Ort der Dateien, days: wieviele Tage zurück werden Dateien erst zusammengeräumt, StatusID : OID in der der Zeitstempel der letzten Datei gespeichert wird.
	 *    
	 *  kann für CamFTP Files als auch für Logs verwendet werden.
	 */

	function MoveFiles($verzeichnis="",$days=2,$statusID=0)
		{
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs/';
		echo "MoveFiles: Alle Files von ".$verzeichnis." in eigene Verzeichnisse pro Tag verschieben.";

			$count=100;
			//echo "<ol>";

			//echo "Heute      : ".date("Ymd", time())."\n";
			//echo "Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";
			//echo "Vorgestern : ".date("Ymd", strtotime("-2 day"))."\n";
			$vorgestern = date("Ymd", strtotime("-".$days." day"));
			$moveTime=strtotime("-".$days." day");
			echo " Dateien behalten bis ".$vorgestern."\n";

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
						if ( ($file != ".") && ($file != "..") )
							{
							$dateityp=filetype( $verzeichnis.$file );
							if ($dateityp == "file")
								{
								$fileTime=filectime($verzeichnis.$file);
								$unterverzeichnis=date("Ymd", $fileTime);
								$letztesfotodatumzeit=date("d.m.Y H:i", $fileTime);   // anderes Format fuer Status
								if ($fileTime <= $moveTime)
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
									echo "  Datei: ".$verzeichnis.$file." auf ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
									if ($statusID != 0) SetValue($statusID,$letztesfotodatumzeit);
									}
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

	/*
	 *  Die oft umfangreichen Fotos der Webcams in einem Ordner pro Tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann. Es werden die selben Move Operationen wie bei Logs verwendet.
	 *
	 */

	function MoveCamFiles($cam_config)
		{
		$count=0;
		$cam_name=$cam_config['CAMNAME'];		
		$verzeichnis = $cam_config['FTPFOLDER'];
		$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$this->CategoryIdData);
		if ($cam_categoryId==false)
			{
			$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
			IPS_SetParent($cam_categoryId,$this->CategoryIdData);
			}
		$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3);
		$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
		$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0, '~Motion', null ); /* 0 Boolean 1 Integer 2 Float 3 String */

		$count=$this->MoveFiles($verzeichnis,0,$WebCam_LetzteBewegungID);      /* in letzteBewegungID wird das Datum/Zeit des letzten kopierten Fotos geschrieben */
		$PhotoCountID = CreateVariableByName($this->CategoryIdData, "Webcam_PhotoCount", 1);
		SetValue($PhotoCountID,GetValue($PhotoCountID)+$count);                   /* uebergeordneten Counter und Cam spezifischen Counter nachdrehen */
		SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count);
		if ($count>0)
			{
			SetValue($WebCam_MotionID,true);
			}
		else
			{
			SetValue($WebCam_MotionID,false);
			}
		echo "    Anzahl verschobener Fotos für ".$cam_name." : ".$count."\n";
		return ($count);
		}

	/******************************
	 *
	 * es werden Snapshots pro Kamera erstellt, muss in IPSCam eingeschaltet sein
	 * diese werden regelmaessig für ein Overview Webfront in das webfront/user Verzeichnis kopiert
	 * 
	 * Übergabeparameter ist IPSCam Configfile aus IPSCam
	 */
	
	function copyCamSnapshots($camConfig=array())
		{
		if (sizeof($camConfig)==0)
			{
			echo "Kein Configarray als Übergabeparameter, sich selbst eines überlegen.\n";
			IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
			IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
			$camConfig = IPSCam_GetConfiguration();			
			}
		$categoryIdCams     		= CreateCategory('Cams',    $this->CategoryIdData, 20);
	
		$camVerzeichnis=IPS_GetKernelDir()."Cams/";
		$camVerzeichnis = str_replace('\\','/',$camVerzeichnis);
		$picVerzeichnis="user/OperationCenter/AllPics/";
		$picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
		$picVerzeichnisFull = str_replace('\\','/',$picVerzeichnisFull);
		echo "Bilderverzeichnis der Kameras, Quellverzeichnis : ".$camVerzeichnis."   Zielverzeichnis : ".$picVerzeichnisFull."\n";
		if ( is_dir ( $picVerzeichnisFull ) == false ) mkdirtree($picVerzeichnisFull);

		echo "\n---------------------------------------------------------\n";
		$anzahl=sizeof($camConfig);
		$rows=(integer)($anzahl/2);
		echo "Es werden im Picture Overview insgesamt ".$anzahl." Bilder in ".$rows." Zeilen mal 2 Spalten angezeigt.\n";		
		//print_r($camConfig);
		$CamTablePictureID=IPS_GetObjectIDbyName("CamTablePicture",$categoryIdCams);
		$html="";
		$html.='<style> 
					table {width:100%}
					td {width:50%}						
					table,td {align:center;border:1px solid white;border-collapse:collapse;}
					.bildmittext {border: 5px solid red;position: relative;}
					.bildmittext img {display:block;}
					.bildmittext span {background-color: red;position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
					 </style>';
		$html.='<table>';
		
		$count=0;
		foreach ($camConfig as $index=>$data) 
			{
			If ( ($count % 2) == 0) $html.="<tr>";
			$PictureTitleID=CreateVariable("CamPictureTitle".$index,3, $categoryIdCams,100,"",null,null,"");
			$filename=$camVerzeichnis.$index."/Picture/Current.jpg";
			if ( file_exists($filename) == true )
				{
				echo "Kamera ".$data["Name"]." copy ".$filename." nach ".$picVerzeichnisFull." \n";	
				copy($filename,$picVerzeichnisFull."Cam".$index.".jpg");
				SetValue($PictureTitleID,$data["Name"]."   ".date ("F d Y H:i:s.", filemtime($filename)));
				}
			$text=GetValue($PictureTitleID);
			$html.='<td frameborder="1"> '.$this->imgsrcstring($picVerzeichnis,"Cam".$count.".jpg","Cam".$count.".jpg",$text).' </td>'; 			
			If ( ($count % 2) == 1) $html.="</tr>";
			$count++;
			}
		If ( ($count % 2) == 0) $html.="<td> </td> </tr>";
		$html.="</table>";			
		SetValue($CamTablePictureID,$html);			
		}

	/******************************
	 *
	 * es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user verzeichnis für die Darstellung im Webfront kopiert
	 * 
	 */
	
	function showCamCaptureFiles($ocCamConfig,$debug=false)
		{
		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
		$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);
		$WFC10Cam_Path        	 = $moduleManagerCam->GetConfigValue('Path', 'WFC10');
		$count=0; $index=0;		
		foreach ($ocCamConfig as $cam_name => $cam_config)
			{
			$index++;
			echo "\n---------------------------------------------------------\n";
			echo "  Webfront Tabname für ".$cam_name." erstellen.\n";
			$heute=date("Ymd", time());
			//echo "    Heute      : ".$heute."    Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";

			/* in data/OperationCenter/ jeweils pro Camera eine Kategorie mit Cam_Name anlegen 
			 * sollte bereits vorhanden sein, hier werden die Infos über letzte Bewegung und Anzahl Capture Bilder gesammelt
			 *
			 */
			$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$this->CategoryIdData);
			if ($cam_categoryId==false)
				{
				$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
				IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
				IPS_SetParent($cam_categoryId,$this->CategoryIdData);
				}
			/* im Webfront visualization/adminstrator/ eine IPSCam_Capture Kategorie mit jeweils pro Kamera eigener Kategorie anlegen 
			 * dort wird die Variable für die html box gespeichert
			 *  ändern auf Link, damit später auch link von user geht
			 */	
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path."_Capture");
			$categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$index);
		
			/* hmtl box in Vizualization anlegen statt in data und einen Link darauf setzen */
			$pictureFieldID = CreateVariable("pictureField",   3 /*String*/,  $categoryIdCapture, 50 , '~HTMLBox');
			/*$html.='<style> 
					table {width:100%}
					td {width:50%}						
					table,td {align:center;border:1px solid white;border-collapse:collapse;}
					.bildmittext {border: 5px solid red;position: relative;}
					.bildmittext img {display:block;}
					.bildmittext span {background-color: red;position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
					 </style>';
			$html.='<table>'; */
		
			$box="";
			$box.='<style> 
					table {width:100%}
					td {width:20%}						
					table,td {align:center;border:1px solid white;border-collapse:collapse;}
					.bildmittext {border: 5px solid green;position: relative;}
					.bildmittext img {display:block;}
					.bildmittext span {background-color: red;position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
					 </style>';
			$box.='<table>';		
			/* $box.='<style> .container { position: relative; text-align: center; color: white; } 
               .bottom-left { position: absolute; bottom: 8px; left: 16px; } 
               .top-left {position: absolute; top: 8px; left: 16px; } 
               .top-right { position: absolute; top: 8px; right: 16px; } 
               .bottom-right { position: absolute; bottom: 8px; right: 16px; } 
               .centered { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); } </style>';
			$box.='<table frameborder="1" width="100%">'; */

			$verzeichnis=$cam_config['FTPFOLDER'].$heute;
		
			/* Kamerabilderverzeichnis muss innerhalb Webfront entstehen, daher Bilder dorthin kopieren */
			$imgVerzeichnis="user/OperationCenter/Cams/".$cam_name."/";
			$imgVerzeichnisFull=IPS_GetKernelDir()."webfront/".$imgVerzeichnis;
			$imgVerzeichnisFull = str_replace('\\','/',$imgVerzeichnisFull);
			//echo "Quellverzeichnis : ".$verzeichnis."   Zielverzeichnis : ".$imgVerzeichnisFull."\n";
			if ( is_dir ( $imgVerzeichnisFull ) == false ) mkdirtree($imgVerzeichnisFull);
		
			$picdir=$this->readdirToArray($verzeichnis,false,-500);
			/* Fileliste die kopiert werden soll, vorhandene Dateien werden nicht kopiert, andere Dateien werden gelöscht */
			$size=sizeof($picdir);
			$j=0;$k=0;
			$logdir=array();  // logdir loeschen, sonst werden die Filenamen vom letzten Mal mitgenommen
			for ($i=0;$i<$size;$i++)
				{
				if ($debug) echo "   ".$picdir[$i];
				$path_parts = pathinfo($picdir[$i]);
				if ($path_parts['extension']=="jpg")
					{
					//echo "       Dirname: ".$path_parts['dirname'], "\n";
					//echo "       Basename: ".$path_parts['basename'], "\n";
					//echo "       Extension: ".$path_parts['extension'], "\n";
					//echo "       Filename: ".$path_parts['filename'], "\n"; // seit PHP 5.2.0			
					if (($k % 6)==2) { $logdir[$j++]=$picdir[$i]; if ($debug) echo "  *"; };
					$k++;		// eigener Index, da manche Files übersprungen werden
					} 
				if ($debug) echo "\n";
				}
			echo "Im Quellverzeichnis ".$verzeichnis." sind insgesamt ".$size." Dateien :\n";
			echo "Es wird nur jeweils aus sechs jpg Dateien die dritte genommen.\n"; 	
			//print_r($logdir);	
			$check=array();
			$handle=opendir ($imgVerzeichnisFull);
			while ( false !== ($datei = readdir ($handle)) )
				{
				if (($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($imgVerzeichnisFull.$datei) == false)) 
					{
					$check[$datei]=true;
					}
				}
			closedir($handle);
			/* im array check steht für vorhandene Dateien ein true, wenn sie auch im Quellverzeichnis sind wird nicht kopiert */
			$c=0;
			foreach ($logdir as $filename)
				{
				if ( isset($check[$filename]) == true )
					{
					$check[$filename]=false;
					if ($debug) echo "Datei ".$filename." in beiden Verzeichnissen.\n";
					}
				else
					{	
					echo "copy ".$verzeichnis."\\".$filename." nach ".$imgVerzeichnisFull.$filename." \n";	
					copy($verzeichnis."\\".$filename,$imgVerzeichnisFull.$filename);
					$c++;
					}
				}		
			if ($debug) echo "Verzeichnis für Anzeige im Webfront: ".$imgVerzeichnisFull."\n";	
			$i=0; $d=0;
			foreach ($check as $filename => $delete)
				{
				if ($delete == true)
					{
					if ($debug) echo "Datei ".$filename." wird gelöscht.\n";
					unlink($imgVerzeichnisFull.$filename);
					$d++;
					}
				else
					{
					if ($debug) echo "   ".$filename."\n";
					$i++;		
					}	
				}	
			echo "insgesamt ".$i." Dateien im Zielverzeichnis. Dazu wurden ".$c." Dateien kopiert und ".$d." Dateien im Zielverzeichnis ".$imgVerzeichnisFull." geloescht.\n";

			$end=sizeof($logdir);
			if ($end>100) $end=100;		
			for ($j=1; $j<$end;$j++)
				{
				if (($j % 5)==0) { $box.='<td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],$this->extractTime($logdir[$j-1])).' </td> </tr>'; }
				elseif (($j % 5)==1) { $box.='<tr> <td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],$this->extractTime($logdir[$j-1])).' </td>'; }
				else { $box.='<td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],$this->extractTime($logdir[$j-1])).' </td>'; }
				}

			$box.='</table>';
			SetValue($pictureFieldID,$box);
			//echo $box;		
		
			}
		}	
						
	/*
	 * Bei jedem Bild als html Verzeichnis und alternativem Bildtitel darstellen
	 *
	 */

	private function imgsrcstring($imgVerzeichnis,$filename,$title,$text="")
		{
		return ('<div class="bildmittext"> <img src="'.$imgVerzeichnis.$filename.'" title="'.$title.'" alt="'.$filename.'" width=100%> <span>'.$text.'</span></div>');
		//return ('<div class="bildmittext"> <img src="'.$imgVerzeichnis."\\".$filename.'" title="'.$title.'" alt="'.$filename.'" width=100%> <span>'.$text.'</span></div>');
		}						
			
	private function extractTime($filename)
		{
		$time="default";
		$year=date("Y",time());
		$pos=strpos($filename,$year);
		if ($pos !== false)
			{
			//echo "Suche : ".$year." in ".$filename." gefunden auf ".$pos."\n";
			$time=substr($filename,$pos+8,2).":".substr($filename,$pos+10,2).":".substr($filename,$pos+12,2);
			}
		return ($time);
		}			
			
																											
	/*
	 *  Die oft umfangreichen Files die erstellt werden in einem Ordner pro Tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann.
	 *
	 */

	function DirLogs($verzeichnis="")
		{
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs/';
		echo "DirLogs: Verzeichnis der Logfiles von ".$verzeichnis.".\n";

		$print=0;
		$dir=0; $totalsize=0; $warning=false;

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
			{
			//echo "Konfiguration:\n";
			//print_r($this->oc_Setup);
			$logdir=$this->readdirToArray($verzeichnis);	// es gibt zweiten Parameter für rekursiv wenn true
			//print_r($logdir); // zu gross
			foreach ($logdir as $index => $entry)
				{
				if (is_dir($verzeichnis.$entry)==true) 
					{
					echo "  ".$index."  Directory  ".$entry."\n";;
					$size=sizeof($entry);	
					$dir++;
					$totalsize+=$size;							
					}
				else
					{	
					if (($print++)<1000) 
						{
						echo "  ".$index."  Datei     ".$entry."\n"; // aufpassen damit nicht zu gross
						}
					else
						{	
						if ($warning==false)
							{
							echo "**** es sind mehr Dateien als 1000 vorhanden- Ausgabe gestoppt.\n";
							$warning=true;
							}
						}	
					$totalsize++;
					}	
				}
			echo "Insgesamt wurden ".$totalsize. " Dateien und Verzeichnisse gefunden.\n";	
			}
		else
			{
			echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}
		return($dir);		
		}

	/*
	 *  Die in einem Ordner pro Tag zusammengefassten Logfiles loeschen.
	 *  Es werden die älteren Dateien bis zur Anzahl x geloescht.
	 *
	 */

	function PurgeFiles($remain=false,$verzeichnis="")
		{
		if ($remain===false)
			{
			$remain=$this->oc_Setup['CONFIG']['PURGESIZE'];
			}
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs/';			

		echo "PurgeFiles: Überschüssige (>".$remain.") Sammel-Verzeichnisse von ".$verzeichnis." löschen. Die letzen ".$remain." Verzeichnisse bleiben.\n";
		//echo "Heute      : ".date("Ymd", time())."\n";
		//echo "Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";

		$count=0;
		$dir=array();
		
		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
			{
			// öffnen des Verzeichnisses
			if ( $handle = opendir($verzeichnis) )
				{
				/* einlesen der Verzeichnisses	*/
				while ((($file = readdir($handle)) !== false) )
					{
					if ($file!="." and $file != "..")
						{	/* kein Directoryverweis (. oder ..), würde zu einer Fehlermeldung bei filetype führen */
						//echo "Bearbeite ".$verzeichnis.$file."\n";
						$dateityp=filetype( $verzeichnis.$file );
						if ($dateityp == "dir")
							{
							//echo "   Erfasse Verzeichnis ".$verzeichnis.$file."\n";
							$count++;
							$dir[]=$verzeichnis.$file;
							}
						}	
					//echo "    ".$file."    ".$dateityp."\n";
					} /* Ende while */
				//echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt.\n";	
				closedir($handle);
				} /* end if dir */
			}/* ende if isdir */
		else
			{
			echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}
		if (($count-$remain)>0) 
			{	
			echo "Loeschen von 0 bis ".($count-$remain)."\n";
			for ($i=0;$i<($count-$remain);$i++)
				{
				echo "    Loeschen von Verzeichnis ".$dir[$i]."\n";
				$this->rrmdir($dir[$i]);
				}
    		return ($count-$remain);
			} 	
		else return (0);
		}
		
	/* gesammelte Funktionen zur Bearbeitung von Verzeichnissen 
	 *
	 * ein Verzeichnis einlesen und als Array zurückgeben 
	 *
	 */
	
	public function readdirToArray($dir,$recursive=false,$newest=0)
		{
	   	$result = array();

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $dir ) )
			{		
			$cdir = scandir($dir);
			foreach ($cdir as $key => $value)
				{
				if (!in_array($value,array(".","..")))
					{
					if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
						{
						if ($recursive)
							{ 
							echo "DirtoArray, vor Aufruf (".memory_get_usage()." Byte).\n";					
							$result[$value]=$this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
							echo "  danach (".memory_get_usage()." Byte).  ".sizeof($result)."/".sizeof($result[$value])."\n";
							}
						else $result[] = $value;
						//$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
						}
					else
						{
						$result[] = $value;
						}
					}
				} // ende foreach
			} // ende isdir
		if ($newest != 0)
			{
			if ($newest<0) 
				{
				rsort($result);
				$newest=-$newest;
				}				
			foreach ($result as $index => $entry)
				{
				if ($index>$newest) unset($result[$index]);
				}
			}
		return $result;
		}		

	/* ein Verzeichnis rekursiv loeschen */

	private function rrmdir($dir) 
		{
		if (is_dir($dir)) 
			{
			$objects = scandir($dir);
			foreach ($objects as $object) 
				{
				if ($object != "." && $object != "..") 
					{
					if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
					}
				}
			reset($objects);
			rmdir($dir);
			}
		} 

	/***************************************************
	 *
	 * kopiert die Scriptfiles auf ein Dropboxverzeichnis um die Files sicherheitshalber auch immer zur Verfügung zu haben
	 * auch wenn Github nicht mehr geht
	 *
	 */

	function CopyScripts()
		{
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		echo "CopyScripts, relevante Configuration mit Dropbox Verzeichnissen:\n";
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

		foreach ($alleSkripte as $value)
			{
			/*
			 *  Script Files auf die Dropbox kopieren 
			 */
			$filename=IPS_GetScriptFile($value);	/* von hier wird kopiert -> source */
			$name=IPS_GetName($value);
			$trans = array("," => "", ";" => "", ":" => "", "/" => ""); /* falsche zeichen aus filenamen herausnehmen */
			$name=strtr($name, $trans);
			$destination=$name."-".$value.".php";		/* name der als Ziel Filename verwendet wird */
			//echo "-Copy File: ".IPS_GetKernelDir().'scripts/'.$filename." : ".$name." : ".$DIR_copyscriptsdropbox.$destination."\n";
			copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);
			
			/* 
			 * Includefile mit allen Filenamen und dem Pfad herstellen 
			 */
			$value1=$value;
			$check="no ";
			/* herausfinden ob ein Dateiname nur eine Nummer ist, dann vollstaendigen Namen und Struktur geben */
			$zahl=array();
			if (preg_match('/\d+/',$filename,$zahl)==1)
				{
				if ($zahl[0]==$value)
					{
					$dir="";
					while (($parent=IPS_GetParent($value1))!=0)
						{
						$Struktur=IPS_GetObject($parent);
						if ($Struktur["ObjectType"]==0) {$dir=IPS_GetName($parent).'/'.$dir;}
						$value1=$parent;
						}
					$destname=$dir.$name.".ips.php";
					$trans = array("," => "", ";" => "", ":" => ""); /* falsche zeichen aus filenamen herausnehmen */
					$destname=strtr($destname, $trans);
					$check="yes";
					}
				else {  $destname=$filename;  	}	
				}
			else
			   {  $destname=$filename;  	}
			echo $check." ".$count.": OID: ".$value." Verzeichnis ".$filename."   ".$destname."\n";
			$includefile.='\''.$destname.'\','."\n";
			$count+=1;
			}

		$includefile.=');'."\n".'?>';

		echo "\n";
		echo "-------------------------------------------------------------\n\n";
		echo "Insgesamt ".$count." Scripts kopiert.\n";
		return ($includefile);
		}

	/*
	 * Statusinfo von AllgemeineDefinitionen in einem File auf der Dropbox abspeichern
	 *
	 */

	function FileStatus($filename="")
		{
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		print_r($this->oc_Setup);
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';

		mkdirtree($DIR_copystatusdropbox);

		$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true).
			"\n\n************************************************************************************************************************\n";
		$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false).
			"\n\n************************************************************************************************************************\n";
		
		if ($filename=="")		/* sonst filename übernehmen */
			{
			//$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
			$filenameAktuell=$DIR_copystatusdropbox.date("Ymd").'StatusAktuell.txt';
			$filenameHistorisch=$DIR_copystatusdropbox.date("Ymd").'StatusHistorie.txt';
			
			if (!file_put_contents($filenameAktuell, $event1)) {
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}

			if (!file_put_contents($filenameHistorisch, $event2)) {
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}
			}
		else
			{	
			if (!file_put_contents($filename, $event1.$event2)) {
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}
			}
		}	

	/*
	 * Anzahl der Status Files auf der Dropbox, werden gemeinsam mit der Purge Funktion der Logfiles geloescht
	 *
	 */

	function FileStatusDir()
		{
		echo "FileStatusDir: Konfiguration:\n";
		print_r($this->oc_Setup);
		echo "Verzeichnis für Status Files:\n";
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';	
		echo $DIR_copystatusdropbox."\n";   
	   	$statusdir=$this->readdirToArray($DIR_copystatusdropbox);
		rsort($statusdir);
		print_r($statusdir);
		$size=sizeof($statusdir);
		return($size);
		}
	
	/*
	 * Loescht bis auf die angegebenen letzte x Files alle Status Files auf der Dropbox, werden gemeinsam mit der Purge Funktion der Logfiles geloescht
	 *
	 */

	function FileStatusDelete()
		{
		$delete=0;	
		echo "FileStatusDelete: ";
		//print_r($this->oc_Setup);		
		if ( isset($this->oc_Setup['DropboxStatusMaxFileCount']) == true )
			{
			echo "Wenn mehr als ".$this->oc_Setup['DropboxStatusMaxFileCount']." Status Dateien gespeichert, diese loeschen.\n";
			if ( $this->oc_Setup['DropboxStatusMaxFileCount'] > 0)
				{
				$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';
	   			$statusdir=$this->readdirToArray($DIR_copystatusdropbox);
				rsort($statusdir);
				$i=0; 			   
				foreach ($statusdir as $index => $name)
					{
					if ($i > $this->oc_Setup['DropboxStatusMaxFileCount'])
						{	/* delete File */
						echo "delete File :".$DIR_copystatusdropbox.$name."\n";
						unlink($DIR_copystatusdropbox.$name);
						$delete++;
						}
					$i++;	
					}
				}
			}
		return($delete);			   	   
		}

	/*
	 * gibt das lokale Verzeichnis der Status Files auf der Dropbox zurück
	 *
	 */

	function getFileStatusDir()
		{
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';	
		return($DIR_copystatusdropbox);
		}

	/*
	 * aus dem HTML Info Feld des IPS Loggers die Errormeldungen wieder herausziehen
	 *
	 */

	function getIPSLoggerErrors()
		{
		IPSUtils_Include ("IPSLogger_Constants.inc.php","IPSLibrary::app::core::IPSLogger");
		$htmlstring=GetValue(c_ID_HtmlOutMsgList);
		$delete_tags = array("style","colgroup");
		$strip_tags = array("table","tr","td");
		$result=$this->stripHTMLTags($htmlstring,$delete_tags, $strip_tags);
		$result2=$this->stripHTMLTags($result,$delete_tags, $strip_tags);
		$result3=$this->stripHTMLTags($result2,$delete_tags,$strip_tags);
		$result4=$this->stripHTMLTags($result3,$delete_tags,$strip_tags);
		$result5=str_replace("<BR>","\n",$result4);	
		$result5=str_replace("<DIV>"," ",$result5);
		$result5=str_replace("</DIV>","\n",$result5);
		return(trim($result5));	
		}

	/*
	 *
	 */

	private function stripHTMLTags($htmlstring,$delete_tags=array(), $strip_tags=array())
		{
		$len=strlen($htmlstring);
		$ignorestyle=false; $striptag=false;
		$ignore=0; $tag=""; $result="";
		for ($i=0; $i<$len; $i++)
			{
			switch ($htmlstring[$i])
				{
				case "<":
					/* Start eines Tags erkannt */
					$tagstart=$i+1;
					$ignore=255;
					break;
				case ">":
					/* Ende eines Tags erkannt */
					$text=substr($htmlstring,$tagstart,$i-$tagstart);
					if ( $text == ("/".$tag) ) 
						{  /* es wurde ein Ende Tag zu einem vor her erkanntem Tag erkannt */
						//echo "\nEnde Tag: </".$tag.">\n";
						if ( ($ignorestyle==true) && ($style==$tag) ) 
							{
							$ignorestyle=false;
							}
						else
							{
							if ($striptag==true)
								{
								$striptag=false;
								}
							else
								{	
								$result.="<".$text.">";
								}
							}	
						$tag="";
						$ignore=1;					}
					else
						{	/* es wurde das Ende eines Tags erkannt */
						if (in_array(strtolower($text), $delete_tags) && ($striptag==false))
							{
							/* den ganzen Text zwischen start und ende Tag eliminieren */
							//echo "Ignore ".$text." ";
							$ignorestyle=true;
							$style=$text;
							$ignore=255;
							if ($tag=="") 
								{
								$params=explode(" ",$text);
								//print_r($params);
								//echo "Start Tag: <".$params[0].">    ".$text."\n";
								$tag=$params[0];
								}
							else
								{		
								//echo "Tag: <".$text."> Start Tag unveraendert ".$tag."\n";
								$result.="<".$text.">";
								}
							}
						else
							{
							//echo "Start Tag: ".$text."\n";
							if ($ignorestyle==false)
								{
								if ($tag=="") 
									{
									$params=explode(" ",$text);
									//print_r($params);
									$tag=$params[0];
									if (in_array(strtolower($tag), $strip_tags))
										{			
										//echo "Strip Start Tag: <".$tag.">    ".$text."\n";
										$striptag=true;
										}
									else
										{						
										//echo "Start Tag: <".$tag.">    ".$text."\n";
										$result.="<".$text.">";
										}
									}
								else
									{		
									//echo "Tag: <".$text."> Start Tag unveraendert ".$tag."\n";
									$result.="<".$text.">";
									}								
								/* nur den Start und Ende tag selbst eliminieren */ 
								$ignore=1;
								}
							}
						}
					break;
				default:
					break;
				}
			if ($ignore>0) 
				{
				$ignore--;
				}
			else
				{	 
				//echo $htmlstring[$i];
				$result.=$htmlstring[$i];
				}
			}
		//$newresult=stripHTMLTags($result,$strip_tags);
		//if ($newresult==$result) echo "unveraendert !\n";	
		return ($result);
		}																
		
			
	}  /* ende class OperationCenter*/

/********************************************************************************************************
 *
 * DeviceManagement
 * ================ 
 *
 * HardwareStatus
 * getHomematicSerialNumberList
 * addHomematicSerialList_Typ
 * addHomematicSerialList_RSSI
 * addHomematicSerialList_DetectMovement
 * writeHomematicSerialNumberList
 * tableHomematicSerialNumberList
 *
 *
 * HomematicFehlermeldungen
 *
 **************************************************************************************************************************/

class DeviceManagement
	{

	var $CategoryIdData       	= 0;
	var $archiveHandlerID     	= 0;
 
	var $log_OperationCenter  	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();			/* Setup von Operationcenter, Verzeichnisse, Konfigurationen */

	var $installedModules     	= array();
	
	var $HomematicSerialNumberList	= array();
	var $HomematicAddressesList	= array();
	
	var $HMIs = array();							/* Zusammenfassung aller Homatic Inventory module */
	
	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	public function __construct()
		{

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
		$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$this->oc_Configuration = OperationCenter_Configuration();
		$this->oc_Setup = OperationCenter_SetUp();
		
		/* Defaultwerte vergeben, falls nicht im Configfile eingestellt */
		if (isset($this->oc_Setup['DropboxDirectory'])===false) {$this->oc_Setup['DropboxDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/';}
		if (isset($this->oc_Setup['DropboxStatusDirectory'])===false) {$this->oc_Setup['DropboxStatusDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/';}
		if (isset($this->oc_Setup['CONFIG'])===false) 
			{
			$this->oc_Setup['CONFIG']= array("MOVELOGS"  => true,"PURGELOGS" => true,"PURGESIZE"  => 10,);
			}		
		else
			{
			if (isset($this->oc_Setup['CONFIG']['MOVELOGS'])===false) {$this->oc_Setup['CONFIG']['MOVELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGELOGS'])===false) {$this->oc_Setup['CONFIG']['PURGELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGESIZE'])===false) {$this->oc_Setup['CONFIG']['PURGESIZE']=10;}
			}
        $this->getHomematicSerialNumberList();
		$this->HomematicAddressesList=$this->getHomematicAddressList();
		
		$modulhandling = new ModuleHandling();
		$this->HMIs=$modulhandling->getInstances('HM Inventory Report Creator');	
		}
		
/****************************************************************************************************************/


	/*
	 * Statusinfo von Hardware, auslesen der Sensoren 
	 *
	 * und Alarm wenn laenger keine Aktion.
	 *
	 * Parameter:
	 * -----------
	 * Default: Ausgabe als Textfile für zB send_status, gibt alle Geräte der Reihe nach als text aus. 
	 * Wenn Parameter true dann Ausgabe als Array mit den Fehlermeldungen wenn Geräte über längeren Zeitraum nicht erreichbar sind
	 *
	 */

	function HardwareStatus($text=false)
		{
		$result=array(); $index=0;
		$resulttext="";
		//print_r($this->installedModules);
		if (isset($this->installedModules["EvaluateHardware"])==true)
			{
			/* es gibt nur mehr eine Instanz für die Evaluierung der Hardware und die ist im Modul EvaluateHardware */
			
			//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
			IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

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
			}
		
		if ($text==true) return($resulttext); 
		else return($result);
		}


	/********************************************************************
	 *
	 * erfasst alle Homematic Geräte anhand der Seriennumme rund erstellt eine gemeinsame liste 
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
	
	/*
	 * Zusammenfassung aller ActionButtons in dieser Klasse
	 *
	 */
	 
	function get_ActionButton()
		{
		$countHMI = sizeof($this->HMIs);
		//echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs.\n";
	    $ActionButton=array();
		if ($countHMI>0)
	        {
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($this->HMIs as $HMI)
	            {
				$CategoryIdHomematicCCU=IPS_GetCategoryIdByName("HomematicInventory_".$HMI,$CategoryIdHomematicInventory);
	            $SortInventoryId = IPS_GetVariableIdByName("Sortieren",$CategoryIdHomematicCCU);
	   			$HomematicInventoryId = IPS_GetVariableIdByName(IPS_GetName($HMI),$CategoryIdHomematicCCU);
	
	            $ActionButton[$SortInventoryId]["HMI"]=$HMI;
	            $ActionButton[$SortInventoryId]["HtmlBox"]=$HomematicInventoryId;
	            }            
	        }
		return($ActionButton);
		}
		
	/********************************************************************
	 *
	 * erfasst alle Homematic Geräte anhand der Hardware Addressen und erstellt eine gemeinsame liste mit dem DeviceTyp aus HM Inventory 
     * wird bei construct bereits gestartet als gemeinsames Datenobjekt
	 *
	 *****************************************************************************/

	function getHomematicAddressList($debug=false)
		{
		$countHMI = sizeof($this->HMIs);

		$addresses=array();
		if ($countHMI>0)
			{
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($HMIs as $HMI)
				{
				$configHMI=IPS_GetConfiguration($HMI);
				if ($debug)             // no information available in configuration wether creation of report as variable is activated
					{
					echo "\n-----------------------------------\n";
					echo "Konfiguration für HMI Report Creator : ".$HMI."\n";
					echo $configHMI."\n";
					}
	            $childrens=IPS_GetChildrenIDs($HMI);
    	        if (isset($childrens[0]))
        	        {
            	    //print_r($childrens);
                	//echo GetValue($childrens[0]);
	                $HomeMaticEntries=json_decode(GetValue($childrens[0]),true);
                    if ( (is_array($HomeMaticEntries)) && (sizeof($HomeMaticEntries)>0) )
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
                    else 
                        {
                        echo "HMI_CreateReport wurde noch nicht aufgerufen oder HM Inventory Instanz ist falsch konfiguriert.\n";   
                        HMI_CreateReport($HMI);                     
                        }                    
                	}
	            else echo "HM Inventory, Abspeicherung in einer variable wurde nicht konfiguriert\n";    
				}					
			}
		if ($debug)
			{
			echo "Ausgabe Adressen versus DeviceType für insgesamt ".sizeof($addresses)." Instanzen (Geräte/Kanäle).\n";	
			print_r($addresses);
			}
		return($addresses);
		}


	/********************************************************************
	 *
	 * Wenn Debug gib die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 * formatierte liste aus 
	 *
	 * die Homematic Liste wird um weitere Informationen erweitert:  Typ
	 *
	 *****************************************************************************/

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
                if ( isset($serials[$name])==true ) echo "  !!! Doppelter Eintrag.\n";
				else $serials[$name]=$anzahl["Name"];
				$register=explode(" ",trim($anzahl["Values"]));


				if ($debug) echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".str_pad($name,20)." Inst: ".str_pad($anzahl["Anzahl"],4)." Child: ".str_pad(sizeof($register),6)." ";
			    if (sizeof($register)>1) 
				    { /* es gibt Childrens zum analysieren, zuerst gleiche Werte unterdruecken */
				    echo $this->HomematicDeviceType($register,2)."\n";
					} 
				else
					{	
					//if ($debug)
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
	 *
	 * die Homematic Liste der Seriennummern wird um weitere Informationen erweitert:  RSSI
	 *
	 *****************************************************************************/

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
			if ($debug) echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    ".HM_GetAddress($instanceId)."    ".$value."\n";
			$HMaddress=explode(":",HM_GetAddress($instanceId));
			$this->HomematicSerialNumberList[$HM_CCU_Name][$HMaddress[0]]["RSSI"]=$value;
			}			
		}

	/********************************************************************
	 *
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

	function tableHomematicSerialNumberList($columns=array(),$sort=array())
		{
		//print_r($columns);
		if (isset($columns["Channels"])==true) $showChannels=$columns["Channels"]; else $showChannels=false;
		if (isset($sort["Serials"])==true) $sortSerials=$sort["Serials"]; else $sortSerials=false;
		$str="";
		$ccuNum=1;	
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			$str .= "<table width='90%' align='center'>"; 
			$str .= "<tr><td><b>".$ccu."</b></td></tr>";
			$str .= "<tr><td><b>Seriennummer</b></td>";
			if ($showChannels) $str .= "<td><b>Kanal</b></td>";
			$str .= "<td><b>GeräteName</b></td><td><b>Protokoll</b></td><td><b>GeraeteTyp</b></td><td><b>UpdateTime</b></td><td><b>RSSI</b></td></tr>";
			if ($sortSerials) ksort($geraete);
			foreach ($geraete as $name => $geraet)
				{
				$str .= "<tr><td>".$name."</td>";			// Name ist die Seriennummer
				if ($showChannels) $str .= "<td></td>";		// eventuell Platz lassen für Kanalnummer	
				if (isset($geraet["Typ"])==true)
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
			$ccuNum++;
			}
		echo $str; 
		return ($str);		
		}
	
	/********************************************************************
	 *
	 * alle Homematic Geräte erfassen und in einer grossen Tabelle ausgeben
	 *
	 *****************************************************************************/

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
				echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    ".HM_GetAddress($instanceId)."    ".$value."\n";
				$HMaddress=explode(":",HM_GetAddress($instanceId));
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
     * Homematic Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * VALVE_STATE                                  Stellmotor, (IP) Funk Stellmotor, TYPE_ACTUATOR
     * ACTIVE_PROFILE oder WINDOW_OPEN_REPORTING    Wandthermostat, (IP) Funk Wandthermostat, TYPE_THERMOSTAT
     * TEMPERATURE und HUMIDITY                     Temperatursensor, (IP) Funk Temperatursensor, TYPE_METER_TEMPERATURE
     * PRESS_SHORT                                  Taster x-fach, (IP) Funk Tast x-fach, TYPE_BUTTON
     * STATE
     *
     ****************************************/

    private function HomematicDeviceType($register, $debug=false)
        {
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        /*-------------------------------------*/
        if ( array_search("VALVE_STATE",$registerNew) !== false) /* Stellmotor */
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
            $result[2]="TYPE_ACTUATOR";
            }
        /*-------------------------------------*/
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
            $result[2] = "TYPE_THERMOSTAT";
            }                    
        /*-------------------------------------*/
        elseif ( (array_search("TEMPERATURE",$registerNew) !== false) && (array_search("HUMIDITY",$registerNew) !== false) )   /* Temperatur Sensor */
            {
            $result[1] = "Funk Temperatursensor";
            $result[0] = "Temperatursensor";
            $result[2] = "TYPE_METER_TEMPERATURE";            
            }                    
        /*-------------------------------------*/
        elseif (array_search("PRESS_SHORT",$registerNew) !== false) /* Taster */
            {
            //print_r($registerNew);
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
            $result[2]="TYPE_BUTTON";                                        
            }
        /*-------------------------------------*/
        elseif ( array_search("STATE",$registerNew) !== false) /* Schaltaktor oder Kontakt */
            {
            //print_r($registerNew);
            $anzahl=sizeof(array_keys($register,"STATE"));                     
            if ( (array_search("PROCESS",$registerNew) !== false) || (array_search("WORKING",$registerNew) !== false) )
                {
                $result[0]="Schaltaktor ".$anzahl."-fach";
                if ( (array_search("BOOT",$registerNew) !== false) || (array_search("LOWBAT",$registerNew) !== false) )
                    {
                    $result[1]="Funk-Schaltaktor ".$anzahl."-fach";
                    }
                else    
                    {
                    $result[1]="IP Funk-Schaltaktor ".$anzahl."-fach";
                    }
                if (array_search("ENERGY_COUNTER",$registerNew) !== false) 
                    {
                    $result[0] .= " mit Energiemesung";
                    $result[1] .= " mit Energiemesung";
                    }
                $result[2]="TYPE_SWITCH";     
                }
            else 
                {
                $result[0] = "Tuerkontakt";
                $result[1] = "Funk-Tuerkontakt";
                $result[2] = "TYPE_CONTACT";     
                }
            }
        /*-------------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) && ( array_search("ERROR_OVERLOAD",$registerNew) !== false) )/* Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Dimmer";
            $result[1] = "Funk-Dimmer";
            $result[2] = "TYPE_DIMMER";            
            }                    
        /*-------------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) )/* Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Rollladensteuerung";
            $result[1] = "Funk-Rollladensteuerung";
            $result[2] = "TYPE_SHUTTER";            
            }                    
        /*-------------------------------------*/
        elseif ( array_search("MOTION",$registerNew) !== false) /* Bewegungsmelder */
            {
            //print_r($registerNew);    
            $result[0] = "Bewegungsmelder";
            $result[1] = "Funk-Bewegungsmelder";
            $result[2] = "TYPE_MOTION";             
            }
        elseif ( array_search("RSSI_DEVICE",$registerNew) !== false) /* Bewegungsmelder */
            {
            $result[0] = "RSSI Wert";
            if ( array_search("DUTY_CYCLE",$registerNew) !== false) $result[1] = "IP Funk RSSI Wert";
            else $result[1] = "Funk RSSI Wert";
            $result[2] = "TYPE_RSSI";             
            }            
        elseif ( array_search("CURRENT",$registerNew) !== false) /* Bewegungsmelder */
            {
            $result[0] = "Energiemessgeraet";
            if ( array_search("BOOT",$registerNew) !== false) $result[1] = "Funk Energiemessgeraet";
            else $result[1] = "IP Funk Energiemessgeraet";
            $result[2] = "TYPE_METER_POWER";             
            }          
        else $found=false;

        if ($found) 
            {
            if ($debug==false) return($result[2]);
            elseif ($debug==2) return ($result[1]);
			else return ($result[0]);
            }
        else return (false);
        }


    /*********************************
     *
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     ***********************************************/

    function getHomematicDeviceType($instanz, $debug=false)
	    {
    	$cids = IPS_GetChildrenIDs($instanz);
	    $homematic=array();
    	foreach($cids as $cid)
	    	{
		    $homematic[]=IPS_GetName($cid);
    		}
    	return ($this->HomematicDeviceType($homematic));
    	}

    /*********************************
     *
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Device Typ aus HM Inventory aus
     *
     ***********************************************/
	 		
	function getHomematicHMDevice($instanz, $debug=false)
		{
		if (isset($this->HomematicAddressesList[IPS_GetProperty($instanz,"Address")]) ) return($this->HomematicAddressesList[IPS_GetProperty($instanz,"Address")]);
		else return("");
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


/*****************************************************************
 *
 * den Status der HomematicCCU auslesen, alle Fehlermeldungen
 *
 **************/

    function HomematicFehlermeldungen()
	    {
		$alleHM_Errors="\n\nAktuelle Fehlermeldungen der Homematic Funkkommunikation:\n";
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
		    }
		else
		    {
			/* Homematic Instanzen vorhanden, sind sie aber auch aktiv ? */
			$aktiv=false;
			foreach ($ids as $id)
	   		    {
				$ergebnis=IPS_GetConfiguration($id);
				//echo "Homematic Socket : ".IPS_GetName($id)."  Konfig : ".$ergebnis."\n";
                $CCUconfig=json_decode($ergebnis);
                //print_r($CCUconfig);
				if ( $CCUconfig->Open==false )
				    {
					$alleHM_Errors.="\nHomatic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.\n";
					}
				else
				    {
            		$ccu_name=IPS_GetName($id);
            		$alleHM_Errors.="\nHomatic Socket ID ".$id." / ".$ccu_name."   ".sizeof($this->HomematicSerialNumberList[$ccu_name])." Endgeräte angeschlossen.\n";                        
					$msgs = @HM_ReadServiceMessages($id);
					if($msgs === false)
					    {
						//die("Verbindung zur CCU fehlgeschlagen");
					    $alleHM_Errors.="  ERROR: Verbindung zur CCU fehlgeschlagen!\n";
					    }
					if ($msgs != Null)
						{
						if(sizeof($msgs) == 0)
						    {
							//echo "Keine Servicemeldungen!\n";
					   	    $alleHM_Errors.="OK, keine Servicemeldungen!\n";
							}

						foreach($msgs as $msg)
						    {
				   		    if(array_key_exists($msg['Message'], $texte))
								{
      					  	    $text = $texte[$msg['Message']];
		   					    }
							else
								{
	      	  				    $text = $msg['Message'];
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
						  	$alleHM_Errors.="  NACHRICHT : ".str_pad($name,60)."  ".$msg['Address']."   ".$text." \n";
							}
						}
					}
				}
			}
		return($alleHM_Errors);
    	}

    } /* ende class DeviceManagement */

/*********************************************************************************************
 *
 *
 ***********************************************************************************************/

class statusDisplay
	{

    private $CategoryIdData,$categoryId_TimerSimulation,$archiveHandlerID;

 	private $log_OperationCenter, $auto;           // class declarations

	private $installedModules     	= array();          // koennte man auch static mit einer abstracten Klasse für alle machen
	private $ScriptsUsed = array();                     // alle Skripts für dieses Modul
	
	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	public function __construct()
		{

		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
		$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
		$this->installedModules = $moduleManager->GetInstalledModules();

		$app_oid=$moduleManager->GetModuleCategoryID()."\n";
		$oid_children=IPS_GetChildrenIDs($app_oid);
		$result=array();
		//echo "  Alle Skript Files :\n";
		foreach($oid_children as $oid)
			{
			$result[IPS_GetName($oid)]=$oid;
			//echo "      OID : ".$oid." Name : ".IPS_GetName($oid)."\n";
			}
		$this->ScriptsUsed=$result;

		$this->categoryId_TimerSimulation    	= IPS_GetCategoryIDByName('TimerSimulation',$this->CategoryIdData);

		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

        //$moduleManagerAS = new IPSModuleManager('Autosteuerung',$repository);
        //print_r($this->installedModules);
        if (isset($this->installedModules["Autosteuerung"])) echo "Module Autosteuerung installiert.\n";
        $this->auto=new Autosteuerung();

		$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		}

    function getCategory()
        {
        return ($this->categoryId_TimerSimulation);
        }

    function initSlider()
        {
        $variables=$this->auto->getScenes();
        foreach ($variables as $id => $value)
            {
            /* 	function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') */
            $variableID=CreateVariable($id,1,$this->categoryId_TimerSimulation,100,"~Intensity.100",$this->ScriptsUsed["OperationCenter"],null,"");
            SetValue($variableID,$value);
            }
        }

    /**************************************************************
     *
     * schreibt den Status der AWS in die Tabelle vom OperationCenter
     *
     **********************************************************************/

    function setStatus()
        {
        $oid=IPS_GetVariableIDByName("TableEvents",$this->categoryId_TimerSimulation);
        SetValue($oid,$this->auto->statusAnwesenheitSimulation(true));
        }    

    } // ende class statusDisplay        
		
/****************************************************************************************************************/



/********************************************************************************************
 *
 *  Eigene Klasse für parsefile
 *
 ***************************************************************************************************/


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

	} /* Ende class parsefile*/

/********************************************************************************************
 *
 *  Eigene Klasse für Timer
 *
 ***************************************************************************************************/

class TimerHandling
	{

	private $ScriptsUsed = array();
	
	public function __construct()
		{
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		$moduleManager = new IPSModuleManager('OperationCenter',$repository);
		$app_oid=$moduleManager->GetModuleCategoryID()."\n";
		$oid_children=IPS_GetChildrenIDs($app_oid);
		$result=array();
		//echo "  Alle Skript Files :\n";
		foreach($oid_children as $oid)
			{
			$result[IPS_GetName($oid)]=$oid;
			//echo "      OID : ".$oid." Name : ".IPS_GetName($oid)."\n";
			}
		$this->ScriptsUsed=$result;
		}
		
	public function listScriptsUsed()
		{
		return ($this->ScriptsUsed);
		}		

	/***************************************************************************************/

	/* automatisch Timer kreieren, damit nicht immer alle Befehle kopiert werden müssen */

	function CreateTimerOC($name,$stunde,$minute)
		{
		/* EventHandler Config regelmaessig bearbeiten */
			
		$timID=@IPS_GetEventIDByName($name, $this->ScriptsUsed["OperationCenter"]);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $this->ScriptsUsed["OperationCenter"]);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,0,0,0,0,0);
			IPS_SetEventCyclicTimeFrom($timID,$stunde,$minute,0);  /* immer um ss:xx */
			IPS_SetEventActive($timID,true);
			echo "   Timer Event ".$name." neu angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			}
		else
			{
			echo "   Timer Event ".$name." bereits angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			IPS_SetEventActive($timID,true);
			}
		return($timID);
		}

	function CreateTimerSync($name,$sekunden)
		{
		$timID = @IPS_GetEventIDByName($name, $this->ScriptsUsed["OperationCenter"]);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $this->ScriptsUsed["OperationCenter"]);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,1,0,0,1,$sekunden);      /* alle 150 sec */
			//IPS_SetEventActive($tim2ID,true);
			IPS_SetEventCyclicTimeFrom($timID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
			echo "   Timer Event ".$name." neu angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			}
		else
			{
			echo "   Timer Event ".$name." bereits angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			IPS_SetEventCyclicTimeFrom($timID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
			//IPS_SetEventActive($tim2ID,true);
			}
		return($timID);
		}		
	
	} /* Ende class timer */
	

/***************************************************************************************************************
 *
 *  Allgemeine Funktionen
 *
 *
 *
 *****************************************************************************************************************/

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
				//echo "move_camPicture, Verzeichnis : ".$verzeichnis."  Filename : ".$file."\n";
				if ( ($file != ".") && ($file != "..") )
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

/**************************************************************/


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


/********************************************************************************************
 *
 * Ausgabe von Ton für Sprachansagen, kommt noch einmal in der Sprachsteuerungslibrary vor 
 * beide functions sind gleich gestellt.
 *
 *  sk    soundkarte   es gibt immer nur 1, andere bis 9 kann man implementieren
 *        größer 9 ist eine ID einer EchoControl Instanz (Amazon Echo Geräte)
 *
 * 	modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
 * 	modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
 * 	modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
 *
 * zum Beispiel  tts_play(1,$speak,'',2);  // Soundkarte 1, mit diesem Ansagetext, kein Ton, Modus 2 
 *
 *************************************************************/

	function tts_play($sk,$ansagetext,$ton,$modus,$debug=false)
 		{
		$tts_status=true;		
		$sprachsteuerung=false; $remote=false;

		if ($debug) echo "Aufgerufen als Teil der Library des OperationCenter.\n";
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
			}
		$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
		if ( (isset($installedModules["Sprachsteuerung"]) )  && ($installedModules["Sprachsteuerung"] <>  "") ) 
			{
			$sprachsteuerung=true;
			IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");					
			$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
			$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
			$config=Sprachsteuerung_Configuration();
			if ( (isset($config["RemoteAddress"])) && (isset($config["ScriptID"])) && ($sk<10) ) 
                { 
                $remote=true; 
                $url=$config["RemoteAddress"]; 
                $oid=$config["ScriptID"]; 
                }					

			$object_data= new ipsobject($CategoryIdData);
			$object_app= new ipsobject($CategoryIdApp);

			$NachrichtenID = $object_data->osearch("Nachricht");
			$NachrichtenScriptID  = $object_app->osearch("Nachricht");
			if ($debug) echo "Nachrichten gibt es auch : ".$NachrichtenID ."  (".IPS_GetName($NachrichtenID).")   ".$NachrichtenScriptID." \n";

			if (isset($NachrichtenScriptID))
				{
				$object3= new ipsobject($NachrichtenID);
				$NachrichtenInputID=$object3->osearch("Input");
				$log_Sprachsteuerung=new Logging("C:\Scripts\Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
				if ($sk<10) $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk mit \"".$ansagetext."\"");
				else $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk (".IPS_GetName($sk).") mit \"".$ansagetext."\"");
				}
			}
		$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');           // App Kategorie der Sprachsteuerung
		$scriptIdSprachsteuerung   = @IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
		if ($scriptIdSprachsteuerung==false) $sprachsteuerung=false;
		if ( ($sprachsteuerung==true) && ($remote==false) )
			{
			if ($debug) echo "Sprache lokal ausgeben.\n";			
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
			switch ($sk)		/* Switch unterschiedliche Routinen anhand der Spoundkarten ID, meistens eh nur eine */
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
					$ton_status           = GetValueInteger($id_sk1_ton_status);					

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
						if($ton_status == 1)
							{
							/* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
							WAC_Stop($id_sk1_ton);
							WAC_SetRepeat($id_sk1_ton, false);
							WAC_ClearPlaylist($id_sk1_ton);
							}
						if (isset($wav[$ton])==true)
							{
							WAC_AddFile($id_sk1_ton,$wav[$ton]);
							echo "Check SoundID: ".$id_sk1_ton." Ton: ".$wav[$ton]." Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
							while (@WAC_Next($id_sk1_ton)==true) { echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; }
							WAC_Play($id_sk1_ton);
							//solange in Schleife bleiben wie 1 = play
							sleep(1);
							$status = getvalue($id_sk1_ton_status);
							while ($status == 1)	$status = getvalue($id_sk1_ton_status);
							}						
			 			}

					if($ansagetext !="")
						{
						if($ton_status == 1)
							{
							/* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
							WAC_Stop($id_sk1_ton);
							WAC_SetRepeat($id_sk1_ton, false);
							WAC_ClearPlaylist($id_sk1_ton);
							if ($debug) echo "Tonwiedergabe auf Stopp stellen \n";
							}
						$status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) { echo "Error Erzeugung Sprachfile gescheitert.\n"; $tts_status=false; }
						WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
						if ($debug) echo "Check SoundID: ".$id_sk1_ton." Ton: ".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav  Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
						while (@WAC_Next($id_sk1_ton)==true) 
							{ 
							if ($debug) echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; 
							}
						$status=@WAC_Play($id_sk1_ton);
						if (!$status) 
							{ 
							if ($debug) echo "Fehler WAC_play nicht ausführbar.\n"; 
							$tts_status=false; 
							}
						
  						WAC_Stop($id_sk1_ton);
						WAC_SetRepeat($id_sk1_ton, false);
						WAC_ClearPlaylist($id_sk1_ton);
						$status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) echo "Error";
		     			WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
		     			if ($debug) echo "---------------------------".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";
						WAC_Play($id_sk1_ton);
						}

					//Script solange anhalten wie Sprachausgabe läuft
					if($modus != 1)
						{
						if (GetValueInteger($id_sk1_ton_status) == 1) echo "Noch warten bis Status des Ton Moduls ungleich 1 :";
						while (GetValueInteger($id_sk1_ton_status) == 1)
							{												
							sleep(1);
							if ($debug) echo ".";
							}
						if ($debug) echo "\nLänge der Playliste : ".WAC_GetPlaylistLength($id_sk1_ton)." Position : ".WAC_GetPlaylistPosition($id_sk1_ton)."\n";														
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
							if ($debug) echo "Musikwiedergabe auf Pause stellen \n";							
							}
						}
					break;

				//---------------------------------------------------------------------

				//Hier können weitere Soundkarten eingefügt werden
				case '2':
					echo "Fehler: Soundkarte 2 nicht definiert.\n";
					break;
				default:
                    $modulhandling = new ModuleHandling($debug);
                    $echos=$modulhandling->getInstances('EchoRemote');
                    if (in_array($sk,$echos)) EchoRemote_TextToSpeech($sk, $ansagetext);
				    break;				

				}  //end switch
			} //endif	sprachsteuerungs Modul richtig konfiguriert
			
		if ( ($sprachsteuerung==true) && ($remote==true) )
			{
			if ($debug)echo "Sprache remote auf ".$url." ausgeben. verwende Script mit OID : ".$oid."\n";
			$rpc = new JSONRPC($url);
			$monitor=array("Text" => $ansagetext);
			$rpc->IPS_RunScriptEx($oid,$monitor);
			}

		return ($tts_status);
 	}   //end function

/***************************************************
 *
 * updatet und installiert neue Versionen der Module
 *
 *******************************************/

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