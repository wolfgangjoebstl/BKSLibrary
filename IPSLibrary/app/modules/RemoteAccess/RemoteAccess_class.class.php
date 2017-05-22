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

	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

class RemoteAccess
	{

	public $includefile;
	private $remServer=array();
	private $profilname=array("Temperatur","Humidity","Switch","Button","Contact","Motion");
	private $listofOIDs=array();

	/**
	 * @public
	 *
	 * Initialisierung des RemoteAccess Manager Objektes
	 *
	 */
	public function __construct()
		{
		$this->includefile='<?'."\n";

		/* Beispiel für RemoteAccess_GetConfiguration()
		 *		"BKS-VIS"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.20:88/api/',
		 *		"LBG-VIS"        		=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/',
		 */
		$this->remServer=RemoteAccess_GetConfigurationNew();	/* es werden nur die Server in die Liste aufgenommen die "STATUS"=="Active" und "LOGGING"=="Enabled" haben */
		}

	public function getRemoteServer()
		{
		return($this->remServer);
		}
		
	/**
	 * @public
	 *
	 * zum Include File werden die Variablen der Guthabensteuerung hinzugefügt
	 *
	 */
	public function add_Guthabensteuerung()
		{
		$this->includefile.='function GuthabensteuerungList() { return array('."\n";
		$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
		echo "\nGuthabensteuerung Data auf :".$parentid."\n";
		$result=IPS_GetChildrenIDs($parentid);
		$count_phone=100;
		$count_var=500;
		foreach ($result as $variableID)
		   {
		   $children=IPS_HasChildren($variableID);
		   echo "  Variable ".IPS_GetName($variableID)."  ".$children;
		   if (IPS_GetObject($variableID)["ObjectType"]==2) // Variable
				{
				if ($children)
				   {
				   $this->add_variable($variableID,$this->includefile,$count_phone);
				   $volumeID=IPS_GetVariableIDByName(IPS_GetName($variableID)."_Volume",$variableID);
				   $this->add_variable($volumeID,$his->includefile,$count_phone);
				   echo"  VolumeID :".$volumeID;
			      }
			   else
			      {
				   $this->add_variable($variableID,$includefile,$count_var);
					}
				echo "\n";
				}
			else
			   {
			   echo " keine Variable";
			   }
		   }
		$this->includefile.="\n      ".');}'."\n";
		}
		
	/**
	 * @public
	 *
	 * zum Include File werden die Variablen der Stromablesung hinzugefügt
	 *
	 */
	public function add_Amis()
		{
		IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
		$MeterConfig = get_MeterConfiguration();

		$this->includefile.="\n".'function AmisStromverbrauchList() { return array('."\n";
		$amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
		echo "\nAmis Stromverbrauch Data auf :".$amisdataID."\n";

		$count_phone=100;
		$count_var=500;
		foreach ($MeterConfig as $meter)
			{
			echo "  Meter :".$meter["NAME"]."\n";
			$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
			/* ID von Wirkenergie bestimmen */
			if ($meter["TYPE"]=="Amis")
			   {
				$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
				$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $zaehlerid );
				$StromL1ID = IPS_GetObjectIDByName ( 'Strom L1' , $zaehlerid );
				$StromL2ID = IPS_GetObjectIDByName ( 'Strom L2' , $zaehlerid );
				$StromL3ID = IPS_GetObjectIDByName ( 'Strom L3' , $zaehlerid );
				$this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
				$this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL1ID,$meter["NAME"]."_StromL1",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL2ID,$meter["NAME"]."_StromL2",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL3ID,$meter["NAME"]."_StromL3",$this->includefile,$count_phone);
				}
			if ($meter["TYPE"]=="Homematic")
				{
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $meterdataID);
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $meterdataID);
				$this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
				$this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);
				}
			}
		$this->includefile.="\n      ".');}'."\n";
		}

		
	/**
	 * @public
	 *
	 * zum Include File werden die Variablen der SysInfo hinzugefügt
	 *
	 */
	public function add_SysInfo()
		{
		$count=200;
		$this->includefile.="\n".'function SysInfoList() { return array('."\n";
		$OCdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.OperationCenter.SystemInfo');
		echo "\nOperationCenter Data auf :".$OCdataID."\n";		
		
		$HostnameID   		= IPS_GetObjectIDByName("Hostname",$OCdataID);
		$SystemNameID		= IPS_GetObjectIDByName("Betriebssystemname",$OCdataID);	
		$SystemVersionID	= IPS_GetObjectIDByName("Betriebssystemversion",$OCdataID);	
		$HotfixID			= IPS_GetObjectIDByName("Hotfix",$OCdataID);
		$ExternalIP			= IPS_GetObjectIDByName("ExternalIP",$OCdataID);
		$UptimeID			= IPS_GetObjectIDByName("IPS_UpTime",$OCdataID);
		$VersionID			= IPS_GetObjectIDByName("IPS_Version",$OCdataID);	
		
		$this->add_variablewithname($HostnameID,"Hostname",$this->includefile,$count);		// param 3 und 4 werden als Referenz uebergeben
		$this->add_variablewithname($SystemNameID,"Betriebssystemname",$this->includefile,$count);
		$this->add_variablewithname($SystemVersionID,"Betriebssystemversion",$this->includefile,$count);
		$this->add_variablewithname($HotfixID,"Hotfix",$this->includefile,$count);
		$this->add_variablewithname($ExternalIP,"ExternalIP",$this->includefile,$count);
		$this->add_variablewithname($UptimeID,"IPS_UpTime",$this->includefile,$count);
		$this->add_variablewithname($VersionID,"IPS_Version",$this->includefile,$count);
		
		$this->includefile.="\n      ".');}'."\n";
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
					$RemoteServer[$Name]["Status"]=false;
					}
				else
				   	{
				   	$ServerName=$rpc->IPS_GetName(0);
				   	$ServerUptime=$rpc->IPS_GetKernelStartTime();
  				   	$ServerVersion=$rpc->IPS_GetKernelVersion();
					echo "   Server : ".$UrlAddress." mit Name: ".$ServerName." und Version ".$ServerVersion." zuletzt rebootet: ".date("d.m H:i:s",$ServerUptime)."\n";
					$RemoteServer[$Name]["Status"]=true;
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
	 * zum Include File werden die OIDs der Kategorien der Remote Server hinzugefügt
	 *
	 *   legt function ROID_List() an
	 *
	 * und eventuell auch angelegt
	 *
	 * wenn eine status Information mitgeliefert wird (aus sys_ping) werden die nicht erreichbaren Server nicht behandelt, vermeidet Fehler bei Installation
	 *
	 */
	public function add_RemoteServer($available=Array())
		{
		$this->includefile.="\n".'function ROID_List() { return array('."\n";
		print_r($available);
		foreach ($this->remServer as $Name => $Server)
			{
			$read=true;
			if ( isset($available[$Name]["Status"]) ) 
				{
				if ($available[$Name]["Status"] == false ) { $read=false; }
				}
			if ($read == true )
				{	
				echo "Server : ".$Name." mit Adresse : ".$Server."bearbeiten.  \n ";
				$rpc = new JSONRPC($Server);
				$this->includefile.='"'.$Name.'" => array('."\n         ".'"Adresse" => "'.$Server.'", ';

				$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
				$visname=IPS_GetName(0);
				echo "Server : ".$Name."  ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n";
				$this->includefile.="\n         ".'"VisRootID" => "'.$visrootID.'", ';

				$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
				$this->includefile.="\n         ".'"WebFront" => "'.$wfID.'", ';

				$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
				$this->includefile.="\n         ".'"Administrator" => "'.$webID.'", ';

				$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
				$this->includefile.="\n         ".'"RemoteAccess" => "'.$raID.'", ';

				$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
				$this->includefile.="\n         ".'"ServerName" => "'.$servID.'", ';

				$this->listofOIDs["Temp"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Temperatur");
				$this->includefile.="\n         ".'"Temperatur" => "'.$this->listofOIDs["Temp"][$Name].'", ';

				$this->listofOIDs["Switch"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Schalter");
				$this->includefile.="\n         ".'"Schalter" => "'.$this->listofOIDs["Switch"][$Name].'", ';

				$this->listofOIDs["Contact"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Kontakte");
				$this->includefile.="\n         ".'"Kontakte" => "'.$this->listofOIDs["Contact"][$Name].'", ';

				$this->listofOIDs["Button"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Taster");
				$this->includefile.="\n         ".'"Taster" => "'.$this->listofOIDs["Button"][$Name].'", ';

				$this->listofOIDs["Motion"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Bewegungsmelder");
				$this->includefile.="\n         ".'"Bewegung" => "'.$this->listofOIDs["Motion"][$Name].'", ';
	
				$this->listofOIDs["Humidity"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Feuchtigkeit");
				$this->includefile.="\n         ".'"Humidity" => "'.$this->listofOIDs["Humidity"][$Name].'", ';

				$this->listofOIDs["SysInfo"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "SysInfo");
				$this->includefile.="\n         ".'"SysInfo" => "'.$this->listofOIDs["SysInfo"][$Name].'", ';
				
				$this->listofOIDs["Other"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Andere");
				$this->includefile.="\n         ".'"Andere" => "'.$this->listofOIDs["Other"][$Name].'", ';

				echo "  Remote VIS-ID                    ".$visrootID,"\n";
				echo "  Remote WebFront-ID               ".$wfID,"\n";
				echo "  Remote Administrator-ID          ".$webID,"\n";
				echo "  RemoteAccess-ID                  ".$raID,"\n";
				echo "  RemoteServer-ID                  ".$servID,"\n";

				$RPCHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
				$RPCarchiveHandlerID[$Name] = $RPCHandlerID[0];
				$this->includefile.="\n         ".'"ArchiveHandler" => "'.$RPCarchiveHandlerID[$Name].'", ';
				$this->includefile.="\n             ".'	),'."\n";
				}
			}
		$this->includefile.="      ".');}'."\n";
		}

	/**
	 * @public
	 *
	 * das Include File schreiben
	 *
	 *
	 */
	public function write_includeFile()
		{
		$this->includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		if (!file_put_contents($filename, $this->includefile))
			{
        	throw new Exception('Create File '.$filename.' failed!');
    		}
		}

	public function read_includeFile()
		{
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		$file=file_get_contents($filename);
		if (!$file)
			{
        	throw new Exception('Read File '.$filename.' failed!');
    		}
		return($file);	
		}
		
	/**
	 * @public
	 *
	 * Profile aus den Remote Servern lesen und anlegen
	 *
	 *
	 */
	public function rpc_showProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					}
				else
				   {
					echo "  Profil ".$pname." existiert. \n";
				   }
				}
			}
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern löschen
	 *
	 *
	 */
	public function rpc_deleteProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					}
				else
				   {
					echo "  Profil ".$pname." existiert, wird gelöscht. \n";
					$rpc->IPS_DeleteVariableProfile($pname);
				   }
				}
			}
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern anlegen
	 *
	 *
	 */
	public function rpc_createProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					switch ($pname)
					   {
					   case "Temperatur":
					 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					  		$rpc->IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
					  		$rpc->IPS_SetVariableProfileText($pname,'',' °C');
					  		break;
						case "Humidity";
					 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					  		$rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
					  		$rpc->IPS_SetVariableProfileText($pname,'',' %');
					  		break;
						case "Switch";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Aus","",0xff0000);   /*  Rot */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Ein","",0x00ff00);     /* Grün */
					  		break;
						case "Contact";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Zu","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Offen","",0xffffff);
					  		break;
						case "Button";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Ja","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Nein","",0xffffff);
					  		break;
						case "Motion";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Ruhe","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Bewegung","",0xffffff);
					  		break;
					   default:
					      break;
						}
					}
				else
				   {
					echo "  Profil ".$pname." existiert. \n";
				   }
				}
			}
		}
		
	/**
	 * @public
	 *
	 * alle Ergebnisse ausgeben
	 *
	 *
	 */
	public function write_classresult()
		{
		echo "\nOID          :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($Name,10);
			}
			
		echo "\nTemperature  :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Temp"][$Name],10);
			}
		echo "\nSwitch       :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Switch"][$Name],10);
			}
		echo "\nKontakt      :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Contact"][$Name],10);
			}
		echo "\nTaster      :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Button"][$Name],10);
			}
		echo "\nBewegung     :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Motion"][$Name],10);
			}
		echo "\nFeuchtigkeit :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Humidity"][$Name],10);
			}
		echo "\nSysInfo     :";
		
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["SysInfo"][$Name],10);
			}
		echo "\nAndere       :";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Other"][$Name],10);
			}
		echo "\n\n";
		}

	/******************************************************************/

	private function add_variable($variableID,&$includefile,&$count)
		{
		$includefile.='"'.IPS_GetName($variableID).'" => array('."\n         ".'"OID" => '.$variableID.', ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($variableID).'", ';
		$variabletyp=IPS_GetVariable($variableID);
		print_r($variabletyp);
		//echo "Typ:".$variabletyp["VariableType"]."\n";
		$includefile.="\n         ".'"Typ" => '.$variabletyp["VariableType"].', ';
		$includefile.="\n         ".'"Order" => "'.$count++.'", ';
		$includefile.="\n             ".'	),'."\n";
		}

	/******************************************************************/

	private function add_variablewithname($variableID,$name,&$includefile,&$count)
		{
		$includefile.='"'.$name.'" => array('."\n         ".'"OID" => '.$variableID.', ';
		$includefile.="\n         ".'"Name" => "'.$name.'", ';
		$variabletyp=IPS_GetVariable($variableID);
		//print_r($variabletyp);
		//echo "Typ:".$variabletyp["VariableType"]."\n";
		$includefile.="\n         ".'"Typ"      => '.$variabletyp["VariableType"].', ';
		$includefile.="\n         ".'"Profile"  => "'.$variabletyp["VariableCustomProfile"].'", ';
		$includefile.="\n         ".'"Order"    => "'.$count++.'", ';
		$includefile.="\n             ".'	),'."\n";
		}

	}  /* Ende class */
	
/*****************************************************************************
 *
 *
 *	
 **********************************************************************************/	
	
class IPSMessageHandlerExtended extends IPSMessageHandler 
	{

	private static $eventConfigurationAuto = array();
	private static $eventConfigurationCust = array();

		/**
		 * @private
		 *
		 * Liefert die aktuelle Auto Event Konfiguration
		 *
		 * @return string[] Event Konfiguration
		 */
		private static function Get_EventConfigurationAuto() {
			if (self::$eventConfigurationAuto == null) {
				self::$eventConfigurationAuto = IPSMessageHandler_GetEventConfiguration();
			}
			return self::$eventConfigurationAuto;
		}

		/**
		 * @private
		 *
		 * Liefert die aktuelle Customer Event Konfiguration
		 *
		 * @return string[] Event Konfiguration
		 */
		private static function Get_EventConfigurationCust() {
			if (self::$eventConfigurationCust == null and function_exists('IPSMessageHandler_GetEventConfigurationCust')) {
				self::$eventConfigurationCust = IPSMessageHandler_GetEventConfigurationCust();
			}
			return self::$eventConfigurationCust;
		}

		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private static function StoreEventConfiguration($configuration) {

			// Build Configuration String
			$configString = '$eventConfiguration = array(';
			foreach ($configuration as $variableId=>$params) {
				$configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
				for ($i=0; $i<count($params); $i=$i+3) {
					if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
					$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
				}
				$configString .= '),';
			}
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);

			// Write to File
			$fileNameFull = IPS_GetKernelDir().'scripts/IPSLibrary/config/core/IPSMessageHandler/IPSMessageHandler_Configuration.inc.php';
			if (!file_exists($fileNameFull)) {
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
			}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos1 = strpos($fileContent, '$eventConfiguration = array(');
			$pos2 = strpos($fileContent, 'return $eventConfiguration;');

			if ($pos1 === false or $pos2 === false) {
				throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
			}
			$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
			//echo  $fileContentNew;
			file_put_contents($fileNameFull, $fileContentNew);
			self::$eventConfigurationAuto = $configuration;
		}
				
								
	public static function DeleteEvent($eventName) 
		{
		$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
		$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
		if ($eventId === false) 
			{
			}
		else
			{
			//IPS_DeleteEvent($eventId);
			echo 'Deleted IPSMessageHandler Event ='.$eventName."\n";	
			IPSLogger_Dbg (__file__, 'Deleted IPSMessageHandler Event ='.$eventName);
			}
		}	
		
	/**
	 * @public
	 *
	 * Registriert ein Event im IPSMessageHandler. Die Funktion legt ein ensprechendes Event
	 * für die übergebene Variable an und registriert die dazugehörigen Parameter im MessageHandler
	 * Konfigurations File.
	 *
	 * @param integer $variableId ID der auslösenden Variable
	 * @param string $eventType Type des Events (OnUpdate oder OnChange)
	 * @param string $componentParams Parameter für verlinkte Hardware Komponente (Klasse+Parameter)
	 * @param string $moduleParams Parameter für verlinktes Module (Klasse+Parameter)
	 */
	public static function UnRegisterEvent($variableId) 
		{
		$configurationAuto = self::Get_EventConfigurationAuto();
		$configurationCust = self::Get_EventConfigurationCust();
		
		// Search Configuration
		$found = false;
		if (array_key_exists($variableId, $configurationCust)) 
			{
			$found = true;
			unset($configurationCust[$variableId]); 
			echo "UnregisterEvent in CustomConfiguration.\n";
   		}
		if (array_key_exists($variableId, $configurationAuto)) 
			{
			$found = true;
			unset($configurationAuto[$variableId]); 
			echo "UnregisterEvent in AutoConfiguration.\n";
			}
		if ($found==true)
			{	
			self::StoreEventConfiguration($configurationAuto);
			}
		}
	
		
		
		
	}	
	
	
	/** @}*/
?>