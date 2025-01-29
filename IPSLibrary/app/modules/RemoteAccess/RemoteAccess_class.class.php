<?php
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

	/*********************
	 *
     *  Überblick zur Verfügung gestellter classes und functions
     *
     * RemoteAccess Class
     * RA_Autosteuerung extends RemoteAccess
     * IPSMessageHandlerExtended extends IPSMessageHandler
     *
     * function installAccess
     *
     * HandleEvent in den Component Classes, am Beispiel IPSComponentSensor_remote für Betriebssystemname
     * IpsMessageHandler_Configuration: array('OnChange','IPSComponentSensor_Remote,LBG70-2Virt:32824;BKS01:23742;','IPSModuleSensor_Remote',),
     * im constuct($instanceId, $remoteOID, $tempValue)
     * 			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
	 *			$this->remServer	  = RemoteAccessServerTable();              // Liste der remoteAccess server in einer besseren Tabelle mit dem aktuellen Status zur Erreichbarkeit 
     *    RemoteAccessServerTable ist in AllgemeineDefinitionen, hat den Vorteil funktioniert auch wenn class RemoteAccess nicht verwendet wird.
     * bei Änderung
     *    HandleEvent($variable, $value, IPSModuleSensor $module)
     *          $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID, $debug );
     *
     */

	/*********************
	 *
	 * RemoteAccess Class
	 *
	 * Vereinfachung von Remote Access von Servern zum Logging oder Update von Variablen
	 *
	 * Um Zeit zu sparen werden nur die mit Logging enable und im Status Active konfigurierten Server angesprochen
	 *
	 * getRemoteServer()
     *
     * Die Struktur der Remote Server wird vorab erfasst und gespeichert um Zeit zu sparen
	 * abgespeichert wird als Includefile das regelmaessig erzeugt wird
	 * Routinen um Ihre Daten im Incliudefile zu speichern
	 *      add_Guthabensteuerung()
	 *      add_Amis()
	 *      add_Sysinfo()
     *
	 * Server_ping()	ermittelt die Erreichbarkeit aller Server in der Liste und gibt sie als Array aus
     *
	 *      add_Remoteserver(array)    legt function ROID_List() an
     *      write_includeFile()
     *      read_includeFile()
	 *
	 * rpc_showProfiles
     * rpc_deleteProfiles
     * rpc_createProfiles
     * write_classresult
     *
     * get_listofROIDs
     * write_listofROIDs
     * get_StructureofROID
     * get_listofOIDs
     *
     * RPC_CreateVariableByName
     * RPC_CreateCategoryByName
     * RPC_CreateVariableField
     * RPC_getExtendedStructure
     * RPC_writeExtendedStructure
     * RPC_setHiddenExtendedStructure
     *
     * RemoteAccessServerTable
     * writeRemoteAccessServerTable
     * RemoteAccess_GetConfigurationNew
     * add_variable
     * add_variablewithname
     *
	 ****************************************************/

	IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

class RemoteAccess
	{

	public $includefile;
	private $remServer=array();
	private $profilname=array("Temperatur","TemperaturSet","Humidity","HumidityInt","Switch","Button","Contact","Motion","Pressure","CO2","Rainfall","Helligkeit");      // diese Profile werden installiert
	private $listofOIDs=array();
	private $listofROIDs=array();
    private $ipsOps;

    public $profileConfig;                  // eine Art Mapping zwischen neuen Allgemeinen Profilen und vorhandenen
	
	/**
	 * RemoteAccess::__construct
	 *
	 * Initialisierung des RemoteAccess Manager Objektes
	 *
	 */
	public function __construct()
		{
        $this->ipsOps = new ipsOps();
		$this->includefile='<?php'."\n";

		/* Beispiel für RemoteAccess_GetConfiguration()
		 *		"BKS-VIS"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.20:88/api/',
		 *		"LBG-VIS"        		=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/',
		 */
		$this->remServer=RemoteAccess_GetConfigurationNew();	/* es werden nur die Server in die Liste aufgenommen die "STATUS"=="Active" und "LOGGING"=="Enabled" haben */

       	//$this->profileConfig=array("Temperatur"=>"new","TemperaturSet"=>"new","Humidity"=>"new","Switch"=>"new","Button"=>"new","Contact"=>"new","Motion"=>"new","Pressure"=>"Netatmo.Pressure","CO2"=>"Netatmo.CO2","mode.HM"=>"new","Rainfall"=>"~Rainfall","Helligkeit"=>"~Brightness.HM");

		}

    /* RemoteAccess::getRemoteServer
     * es werden nur die Server in die Liste aufgenommen die "STATUS"=="Active" und "LOGGING"=="Enabled" haben
     * RemoteAccess_GetConfigurationNew() 
     */
	public function getRemoteServer()
		{
		return($this->remServer);
		}
		
    /* Beschleunigung des Ablaufs
     * Analysiert Structure in Visualization und speichere sie serialisiert in einer Variable
     * beschleunigt täglichen Aufruf von RemoteAccess
     * damit Upodate bereits erfolgt ist Aufruf in EvaluateHardware
     */		 
    public function createXconfig()
        {
        
        $configId = @IPS_GetObjectIDByIdent("XConfigurator", 0);
        if ($configId === false)
            {
            // function createVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
            $configId = $this->ipsOps->createVariableByName(0,"XConfigurator",3,false,"XConfigurator",9999);
            echo "createXconfig create $configId \n";
            }
        // Abfrage vereinfacht
        echo "createXconfig found $configId \n";

        $visId=IPS_GetObjectIDByName("Visualization", 0);
        $webfrontId=IPS_GetObjectIDByName("WebFront", $visId);
        $adminId=IPS_GetObjectIDByName("Administrator", $webfrontId);
        $raId=IPS_GetObjectIDByName("RemoteAccess", $adminId);
        echo "Visualization.WebFront.Administrator.RemoteAccess : $visId.$webfrontId.$adminId.$raId   \n";
        $entries=$this->ipsOps->getChildrenIDsOfType($raId,0);
        //print_R ($entries);
        $result=array();
        foreach ($entries as $index=>$entry) 
            {
            $name=IPS_GetName($entry);
            echo "$index $entry $name\n";
            $result[$name]["OID"]=$entry;
            $sub=array();
            $subEntries=$this->ipsOps->getChildrenIDsOfType($entry,0);
            foreach ($subEntries as $idx=>$subEntry) 
                {
                $subName=IPS_GetName($subEntry);
                echo "          $idx $subEntry $subName\n";
                $sub[$subName]=$subEntry;    
                $result[$name]["."][$subName]=array();
                $this->createXConfigSub($result[$name]["."][$subName],$subEntry);                
                }
            ksort($sub);
            $result[$name]["Childs"]=$sub;
            }
        //print_r($result);
        SetValue($configId, json_encode($result));
        }

    private function createXConfigSub(&$result,$raId)
        {
        $entries=$this->ipsOps->getChildrenIDsOfType($raId,2);                        // das sind alle Kategorien
        foreach ($entries as $index=>$entry) 
            {
            $name=IPS_GetName($entry);
            echo "$index $entry $name\n";
            $result[$name]["OID"]=$entry;
            $sub=array();
            $subEntries=$this->ipsOps->getChildrenIDsOfType($entry,0);                // das sind alle Sub Kategorien
            foreach ($subEntries as $idx=>$subEntry) 
                {
                $subName=IPS_GetName($subEntry);
                echo "          $idx $subEntry $subName\n";
                $sub[$subName]=$subEntry;    
                }
            ksort($sub);
            $result[$name]["Childs"]=$sub;
            }
        }

    public function getXConfig($Server)
        {
        $rpc = new JSONRPC($Server);
        try {
            $configId = $rpc->IPS_GetObjectIDByIdent("XConfigurator", 0);
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        if ($configId)
            {
            $xconfig=json_decode($rpc->GetValue($configId),true);
            //echo "Xconfigurator found $configId \n";
            //print_r($xconfig);
            }
        else $xconfig=array();

        return ($xconfig);
        }

	/**
	 * RemoteAccess::add_Guthabensteuerung
	 *
	 * zum Include File werden die Variablen der Guthabensteuerung hinzugefügt
	 *
	 */
	public function add_Guthabensteuerung($debug=false)
		{
		$this->includefile.="/*erstellt von RemoteAccess::add_Guthabensteuerung() am ".date("d.m.Y H:i")."\n */\n";
		$this->includefile.='function GuthabensteuerungList() { return array('."\n";
		$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
		if ($debug) echo "\nadd_Guthabensteuerung aufgerufen, Data in Kategorie :".$parentid.". Alle Variablen dort kopieren.\n";
		$result=IPS_GetChildrenIDs($parentid);
		$count_phone=100;
		$count_var=500;
		foreach ($result as $variableID)
			{
			$children=IPS_HasChildren($variableID);
			if ($debug) echo "  Variable ".IPS_GetName($variableID)."  ".($children?"hat  Children":"keine Children")."\n";
			if (IPS_GetObject($variableID)["ObjectType"]==2) // Variable
				{
				if ($children)
					{
					$this->add_variable($variableID,$this->includefile,$count_phone);
					$volumeID=@IPS_GetVariableIDByName(IPS_GetName($variableID)."_Volume",$variableID);
                    if ($volumeID==false) 
                        {
                        echo "   Kenne Variable nicht.\n";
                        }
                    else
                        {
                        if ($debug) 
                            {
                            echo "    VolumeID : $volumeID Count_Phone : $count_phone\n";
                            //echo "  VolumeID : $volumeID,".$this->includefile.",$count_phone\n";
                            }
                        $this->add_variable($volumeID,$this->includefile,$count_phone);
                        }
			    	}
		   		else
					{
					$this->add_variable($variableID,$this->includefile,$count_var);
					}
				}
			else
				{
				if ($debug) echo "   keine Variable";
				}
			}
		$this->includefile.="\n      ".');}'."\n";
		}
		
	/**
	 * RemoteAccess::add_Amis
	 *
	 * zum Include File werden die Variablen der Stromablesung hinzugefügt
     * nur diese werden von EvaluateStromverbrauch auch initialisisert, das bedeutet sie haben ein ENERGY oder POWER im MessageHandler Config am Schluss
     * diese Routine braucht richtig lange für die Erstellung wenn damit Remote register erstellt werden.
	 *
	 */
	public function add_Amis()
		{
		IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
        IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');        
        $Amis = new Amis();           
		$MeterConfig = $Amis->getMeterConfig();
		$this->includefile.="\n/*erstellt von RemoteAccess::add_Amis() am ".date("d.m.Y H:i")."\n */\n";
		$this->includefile.='function AmisStromverbrauchList() { return array('."\n";
		$amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
		echo "\nAmis Stromverbrauch Data auf :".$amisdataID."\n";

		$count_phone=100;
		$count_var=500;
		foreach ($MeterConfig as $meter)
			{
			echo "  Meter :".$meter["NAME"]."\n";
			$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
			/* ID von Wirkenergie bestimmen */
			if (strtoupper($meter["TYPE"])=="AMIS")
			   {
				$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
				$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $zaehlerid );
                $this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
                $this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);

                $PowerL1ID = @IPS_GetObjectIDByName ( 'Power L1' , $zaehlerid );
                if ($PowerL1ID !== false)           // Power pro L wird berechnet
                    {
                    $PowerL2ID = IPS_GetObjectIDByName ( 'Power L2' , $zaehlerid );
                    $PowerL3ID = IPS_GetObjectIDByName ( 'Power L3' , $zaehlerid );
                    $this->add_variablewithname($PowerL1ID,$meter["NAME"]."_PowerL1",$this->includefile,$count_phone);
                    $this->add_variablewithname($PowerL2ID,$meter["NAME"]."_PowerL2",$this->includefile,$count_phone);
                    $this->add_variablewithname($PowerL3ID,$meter["NAME"]."_PowerL3",$this->includefile,$count_phone);
                        
                    }
                else 
                    {
                    $StromL1ID = IPS_GetObjectIDByName ( 'Strom L1' , $zaehlerid );
                    $StromL2ID = IPS_GetObjectIDByName ( 'Strom L2' , $zaehlerid );
                    $StromL3ID = IPS_GetObjectIDByName ( 'Strom L3' , $zaehlerid );
                    $this->add_variablewithname($StromL1ID,$meter["NAME"]."_StromL1",$this->includefile,$count_phone);
                    $this->add_variablewithname($StromL2ID,$meter["NAME"]."_StromL2",$this->includefile,$count_phone);
                    $this->add_variablewithname($StromL3ID,$meter["NAME"]."_StromL3",$this->includefile,$count_phone);
                    }
				}
			if (strtoupper($meter["TYPE"])=="HOMEMATIC")
				{
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $meterdataID);
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $meterdataID);
				$this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
				$this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);
				}
			if (strtoupper($meter["TYPE"])=="SUMME")
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
	 * RemoteAccess::add_SysInfo
	 *
	 * zum Include File werden die Variablen der SysInfo hinzugefügt
	 *
	 */
	public function add_SysInfo()
		{
		$count=200;
		$this->includefile.="\n/*erstellt von RemoteAccess::add_SysInfo() am ".date("d.m.Y H:i")."\n */\n";
		$this->includefile.='function SysInfoList() { return array('."\n";
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
	function server_ping($debug=false)
		{
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$RemoteServer=array();
		//print_r($remServer);
		$method="IPS_GetName"; $params=array(0);            // von OID 0 IPS_GetName abfragen, das ganz zu fuss und dann nochmal mit einem rpc call

		foreach ($remServer as $Name => $Server)
			{
			//print_r($Server);
			$UrlAddress=$Server["ADRESSE"];
			if ($Server["STATUS"]=="Active")
				{
                if ($debug) echo "Ping active Server $UrlAddress :\n"; 
				$RemoteServer[$Name]["Name"]=$UrlAddress;
				$data = @parse_url($UrlAddress);
				if(($data === false) || !isset($data['scheme']) || !isset($data['host']))
					{
					echo "Invalid URL.\n";
					$response=false;
					}
				else
					{	
                    if ($debug) print_R($data);
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
					if (!is_scalar($method)) 
						{
						echo "Method name has no scalar value-\n";
						$response=false;
						}
					else
						{	
						if (!is_array($params)) 
							{
							echo "Params must be given as array.\n";
							$response=false;							
							}
						else
							{	
                            /* echo file_get_contents('http://ip:82/api/', false, stream_context_create(
                                array('http' =>
                                    array(
                                    'method'  => 'POST',
                                    'header'  => 'Content-type: application/json; charset=utf-8',
                                    'content' => '{"jsonrpc":"2.0","method":"GetValueformatted","params":[38809],"id":"null"}'
                                    )
                                )
                            )); */                                
							$id = round(fmod(microtime(true)*1000, 10000));         // eine id mitgeben, kan auch null sein
							$params = array_values($params);
							$strencode = function(&$item, $key) 
								{
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
							if(($username != "") || ($password != "")) 
								{
								$header .= "Authorization: Basic ".base64_encode($username.":".$password)."\r\n";
								}
							$options = Array(
								"http" => array (
								"method"  => 'POST',
								"header"  => $header,
								"content" => $request,
                                'timeout' => 4,                     // 4 seconds
										)
								);
							$context  = stream_context_create($options);
							$urlen = urlencode($url);	
                            try {
    							$response = file_get_contents($url, false, $context);							
                                } 
                            catch (Exception $e) {
                                echo 'Caught exception: ',  $e->getMessage(), "\n";
                                $response=false;
                                }
                            echo " done $response \n";                            						
							}
						}
					}		
				if ($response===false)
					{
					echo "   Server : ".$url." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht.\n";
					$RemoteServer[$Name]["Status"]=false;
					}
				else
					{
                    try {
				        $rpc = new JSONRPC($UrlAddress);                        
                        $ServerName=$rpc->IPS_GetName(0);
                        $ServerUptime=$rpc->IPS_GetKernelStartTime();
                        $ServerVersion=$rpc->IPS_GetKernelVersion();
                        } 
                    catch (Exception $e) {
                        echo 'Caught exception: ',  $e->getMessage(), "\n";
                        $response=false;
                        }
                if ($response===false)
					{
					echo "   Server : ".$url." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht.\n";
					$RemoteServer[$Name]["Status"]=false;
					}
                else
                    {
					echo "   Server : ".$UrlAddress." mit Name: ".$ServerName." und Version ".$ServerVersion." zuletzt rebootet: ".date("d.m H:i:s",$ServerUptime)."\n";
					$RemoteServer[$Name]["Status"]=true;
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
	 * zum Include File werden die OIDs der Kategorien der Remote Server hinzugefügt
	 *
	 * erstellt includeFile und legt function ROID_List() an
	 *
	 * und legt auch gleich die Kategorien aud den Logging Servern an. Ziel ist die Remote OIDs hier zu speichern, 
	 * damit die verarbeitung schneller geht und die ROIDs zuerst erst gesucht werden muessen. auch angelegt
	 *
	 * wenn eine status Information mitgeliefert wird (aus sys_ping) werden die nicht erreichbaren Server nicht behandelt, vermeidet Fehler bei Installation
	 *
	 */
	public function add_RemoteServer($available=array(),$debug=false)
		{
		$this->includefile.="\n/*erstellt von RemoteAccess::add_RemoteServer() am ".date("d.m.Y H:i")."\n */\n";
        $this->includefile.='function ROID_List() { return array('."\n";
		//print_r($available);
        $client = IPS_GetName(0);                                   // unverändert das ist der lokale PC
        if ($debug) 
            {
            echo "add_RemoteServer für Client $client \n"; 
            print_R($this->remServer);
            }
		foreach ($this->remServer as $Name => $Server)
			{
			$read=true;             // default Server bearbeiten, wird false wenn Status=false
            $create=false;          // default keine neue Struktur aufbauen, sondern xconfig verwenden
            $configId=false;
			if ( isset($available[$Name]["Status"]) ) 
				{
				if ($available[$Name]["Status"] == false ) { $read=false; }
				}
			if ($read == true )
				{	
				echo "Server : ".$Name." mit Adresse : ".$Server."bearbeiten. ";
                $xconfig=$this->getXConfig($Server);
                if (isset($xconfig[$client])) 
                    {
                    echo "rOID Daten verfügbar.";
                    //echo "ServerName : ".$xconfig[$client]["OID"]." ";
                    //print_r($xconfig[$client]["Childs"]);
                    }
                else $create=true;
                echo "\n";
				$this->includefile.='    "'.$Name.'" => array('."\n         ".'"Adresse" => "'.$Server.'", ';
                $rpc = new JSONRPC($Server);
                if ($create)
                    {
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

                    $this->listofOIDs["HeatControl"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "HeatControl");
                    $this->includefile.="\n         ".'"HeatControl" => "'.$this->listofOIDs["HeatControl"][$Name].'", ';

                    $this->listofOIDs["HeatSet"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "HeatSet");
                    $this->includefile.="\n         ".'"HeatSet" => "'.$this->listofOIDs["HeatSet"][$Name].'", ';	
                    
                    $this->listofOIDs["Humidity"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Feuchtigkeit");
                    $this->includefile.="\n         ".'"Humidity" => "'.$this->listofOIDs["Humidity"][$Name].'", ';

                    $this->listofOIDs["SysInfo"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "SysInfo");
                    $this->includefile.="\n         ".'"SysInfo" => "'.$this->listofOIDs["SysInfo"][$Name].'", ';
                    
                    $this->listofOIDs["Klima"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Klima");
                    $this->includefile.="\n         ".'"Klima" => "'.$this->listofOIDs["Klima"][$Name].'", ';
                    
                    $this->listofOIDs["Helligkeit"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Helligkeit");
                    $this->includefile.="\n         ".'"Helligkeit" => "'.$this->listofOIDs["Helligkeit"][$Name].'", ';

                    $this->listofOIDs["Stromverbrauch"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Stromverbrauch");                               // auch neu
                    $this->includefile.="\n         ".'"Stromverbrauch" => "'.$this->listofOIDs["Stromverbrauch"][$Name].'", ';                

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
                    }
                else
                    {
                    $servID = $xconfig[$client]["OID"];
                    $this->includefile.="\n         ".'"ServerName" => "'.$servID.'", ';
                    // $profilname=array("Temperatur","TemperaturSet","Humidity","HumidityInt","Switch","Button","Contact","Motion","Pressure","CO2","Rainfall","Helligkeit");      // diese Profile werden installiert

                    $profiles=array(
                        "Temperatur"       => ["Tag"       => "Temp",      "Profil"    =>"Temperatur"],          // name => [tag => profil]
                        "Switch"            => ["Tag"       => "Switch",    "Profil"    =>"Schalter"],
                        "Kontakt"           => ["Tag"       => "Contact",   "Profil"    =>"Kontakte"],
                        "Taster"            => ["Tag"       => "Button",    "Profil"    =>"Taster"],
                        "Bewegung"          => ["Tag"       => "Motion",   "Profil"    =>"Bewegungsmelder"],
                        "HeatControl"       => "HeatControl",
                        "Feuchtigkeit"      => "Humidity",
                        "SysInfo"           => "SysInfo",
                        "Klima"             => "Klima",
                        "Helligkeit"        => "Helligkeit",
                        "Stromverbrauch"    => "Stromverbrauch",                                                 // neu für direkte Stronverbrauchs register                    
                        "Andere"            => "Other",
                        );
                    foreach ($profiles as $key => $profile) 
                        {
                        $tag=$key;
                        if (is_array($profile) === false) 
                            {
                            $profil=$key;
                            $tag=$profil;
                            //echo "Line $profil $tag \n";
                            }
                        else
                            {    
                            //     function configfileParser(&$inputArray, &$outputArray, $synonymArray,$tag,$defaultValue,$debug=false)
                            $confprof=array();
                            configfileParser($profile,$confprof,["Tag","tag","TAG"],"Tag",$key);
                            configfileParser($profile,$confprof,["Profil","profil","PROFIL"],"Profil",$key);
                            $tag    = $confprof["Tag"];
                            $profil = $confprof["Profil"];
                            //echo "Array $profil $tag \n";
                            }
                        
                        
                        if (isset($xconfig[$client]["Childs"][$profil]))
                            {
                            $this->listofOIDs[$tag][$Name] = $xconfig[$client]["Childs"][$profil];   
                            //echo "Found $profil => ".$this->listofOIDs[$tag][$Name]." with Tag $tag from xconfig\n"; 
                            $this->includefile.="\n         ".'"'.$key.'" => "'.$this->listofOIDs[$tag][$Name].'",        // from xconfig';
                            } 
                        else    
                            {
                            $this->listofOIDs[$tag][$Name] = RPC_CreateCategoryByName($rpc, $servID, $profil);
                            //echo "Found $profil => ".$this->listofOIDs[$tag][$Name]." with Tag $tag from rpc\n"; 
                            //echo "Found $profil => ??? with Tag $tag from rpc\n"; 
                            $this->includefile.="\n         ".'"'.$key.'" => "'.$this->listofOIDs[$tag][$Name].'",        // per rpc call';
                            } 
                        }                       // ende foreach profiles                    
                    $RPCHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
                    $RPCarchiveHandlerID[$Name] = $RPCHandlerID[0];
                    $this->includefile.="\n         ".'"ArchiveHandler" => "'.$RPCarchiveHandlerID[$Name].'", ';
                    }               // ende else create
                $this->includefile.="\n             ".'	),'."\n";
				}           // ende read
			}
        $this->includefile.="      ".');}'."\n";
        return ($this->includefile);
		}

	/**
	 * @public
	 *
	 * das Include File das in der class gespeichert ist abschliessen und als File EvaluateVariables_ROID.inc.php schreiben
     *
     * erstellt function ROID_List() über die OIDs auf den remote Servern und die lokalen OIDs
     *  in function SysInfoList(), function AmisStromverbrauchList() und function GuthabensteuerungList()
     *
	 *
	 */
	public function write_includeFile()
		{
		$this->includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables_ROID.inc.php';
		if (!file_put_contents($filename, $this->includefile))
			{
        	throw new Exception('Create File '.$filename.' failed!');
    		}
		}

    /* für debugging mal das Ergebnis anschauen
     */
	public function show_includeFile()
		{
		$this->includefile.="\n".'?>';
        return($this->includefile);
		}


	/**
	 * @public
	 *
	 * das Include File EvaluateVariables_ROID.inc.php lesen und als string ausgeben
	 *
	 *
	 */

	public function read_includeFile()
		{
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables_ROID.inc.php';
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
	 * Profile aus den Remote Servern lesen und anzeigen ob am jeweiligen Server vorhanden
	 * die Namen der Profile sind in profilname gespeichert, als constant in der Variablendefinition     
	 * die remServer werden aus der Config angelegt, available gibt die Erreichbarkeit des Servers an
	 *
	 */
	public function rpc_showProfiles($available=Array(),$debug=false)
		{
        if ($debug) print_R($this->remServer);
		foreach ($this->remServer as $Name => $Server)
			{
			$read=true;
			if ( isset($available[$Name]["Status"]) ) 
				{
				if ($available[$Name]["Status"] == false ) { $read=false; }
				}
			if ($read == true )
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
		}

	/**
	 * @public
	 *
	 * alle Profile aus den Remote Servern löschen
	 * die Profilnamen sind in profilname gespeichert
	 *
	 */
	public function rpc_deleteProfiles($available=Array())
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$read=true;
			if ( isset($available[$Name]["Status"]) ) 
				{
				if ($available[$Name]["Status"] == false ) { $read=false; }
				}
			if ($read == true )
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
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern anlegen
	 * die Namen der Profile sind in profilname gespeichert     
	 * Die Konfiguration der Profile ist hier Hardcoded ausprogrammiert
	 *
	 */
	public function rpc_createProfiles($available=Array())
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$read=true;
			if ( isset($available[$Name]["Status"]) ) 
				{
				if ($available[$Name]["Status"] == false ) { $read=false; }
				}
			if ($read == true )
				{				
				$rpc = new JSONRPC($Server);
				echo "Server : ".$Name."   \n";

				foreach ($this->profilname as $pname)
					{
					if ($rpc->IPS_VariableProfileExists($pname) == false)
						{
						echo "  Profil ".$pname." existiert nicht auf Server $Name ($Server).\n";
						switch ($pname)
							{
							case "Temperatur":
								$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
								$rpc->IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
								$rpc->IPS_SetVariableProfileText($pname,'','°C');
								break;
							case "TemperaturSet":
								$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
								$rpc->IPS_SetVariableProfileDigits($pname, 1); // PName, Nachkommastellen
								$rpc->IPS_SetVariableProfileValues ($pname, 6, 30, 0.5 );	// eingeschraenkte Werte von 6 bis 30 mit Abstand 0,5					
								$rpc->IPS_SetVariableProfileText($pname,'','°C');
								break;								
							case "Humidity";
								$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
								$rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
								$rpc->IPS_SetVariableProfileText($pname,'',' %');
								break;
							case "HumidityInt";
								$rpc->IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
                            case "Pressure";
                                $rpc->IPS_CreateVariableProfile($pname, 2);
                                $rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
                                $rpc->IPS_SetVariableProfileText($pname,'',' mbar');
                                $rpc->IPS_SetVariableProfileIcon($pname,"Gauge");
                                break;      
                            case "CO2";
                                $rpc->IPS_CreateVariableProfile($pname, 1);
                                $rpc->IPS_SetVariableProfileText($pname,'',' ppm');
                                $rpc->IPS_SetVariableProfileIcon($pname,"Gauge");
                                $rpc->IPS_SetVariableProfileValues ($pname, 250, 2000, 0);
                                break;                                    
                            case "Rainfall":
                                $rpc->IPS_CreateVariableProfile ($pname, 2);
                                $rpc->IPS_SetVariableProfileIcon ($pname, "Rainfall");
                                $rpc->IPS_SetVariableProfileText ($pname, ""," mm");
                                $rpc->IPS_SetVariableProfileValues ($pname, 0,0,0);
                                $rpc->IPS_SetVariableProfileDigits ($pname, 1);                				
                                break;
                            case "Helligkeit":
                                $rpc->IPS_CreateVariableProfile ($pname, 1);
                                $rpc->IPS_SetVariableProfileIcon ($pname, "Sun");
                                $rpc->IPS_SetVariableProfileText ($pname, "","");
                                $rpc->IPS_SetVariableProfileValues ($pname, 0,255,0);
                                $rpc->IPS_SetVariableProfileDigits ($pname, 0);                                    
                                break;

							default:
						      break;
							}
						}
					else
						{
						echo "  Profil ".$pname." existiert. \n";
                        /*
                        $target=$rpc->IPS_GetVariableProfile ($pname);
                        echo "  Profil ".$pname." erhält Aufruf zum Synchronisieren mit einem vorhandenen Profil namens $masterName.\n";
                        $master=IPS_GetVariableProfile ($masterName);

                        $masterName=$master["ProfileName"];         // sonst nicht rekursiv möglich
                        $targetName=$target["ProfileName"];
                        compareProfiles("local",$master, $target,$masterName,$targetName);      // nur die lokalen Profile anpassem, geht auch Remote
                        */


						}
					}
				}
			}
		}
		
	/**
	 * @public
	 *
	 * alle Ergebnisse ausgeben
	 * benötigt aufruf funktion add_RemoteServer
	 *
	 */
	public function write_classresult($available=Array())
		{
        $profiles=array("Temperature" => "Temp","Switch"=>"Switch","Kontakt"=>"Contact","Taster"=>"Button","Bewegung"=>"Motion","Feuchtigkeit"=>"Humidity","SysInfo"=>"SysInfo","Klima"=>"Klima","Andere"=>"Other");
		echo "\n".str_pad("OID",20).":";
		foreach ($this->remServer as $Name => $Server)
			{
			if ( isset($available[$Name]["Status"]) ) {	if ($available[$Name]["Status"] == true ) 
				{ echo str_pad($Name,15); } }
			}
		foreach ($profiles as $nameProfile => $profile)
            {
		    echo "\n".str_pad($nameProfile,20).":";
    		foreach ($this->remServer as $Name => $Server)
	    		{
		    	if ( isset($available[$Name]["Status"]) ) 
                    {	if ($available[$Name]["Status"] == true ) 
				        { 
                        if (isset($this->listofOIDs[$profile][$Name])) echo str_pad($this->listofOIDs[$profile][$Name],15); 
                        } 
                    }
			    }
            }
		echo "\n\n";
		}


	/**
	 * @public
	 *
	 * alle ermittelten ROIDs aus dem includefile speichern und ausgeben
	 *
	 *
	 */
	public function get_listofROIDs()
		{
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$this->listofROIDs=ROID_List();
		return ($this->listofROIDs);
		}

	public function write_listofROIDs()
		{
		$print="";
		$list=$this->get_listofROIDs();
		//print_r($list);
		foreach ($list as $server => $entries)
			{
			$print.="   Server: ".$server."\n";
			foreach ($entries as $id => $entry)
				{
				switch ($id)
					{
					case "Adresse":
					case "ArchiveHandler":
						break;
					case "VisRootID":
						$print.="      ".$id."   ".$entry."\n";
						break;						
					default:
						$print.="         ".$id."   ".$entry."\n";
					}
				}	
			}
		return ($print);
		}

    /* RemoteAccess::get_StructureofROID
     * Defaultwert ist Schalter, sonst die Kategprie anfordern
     * alle Werte in listofROIDs
     */
	public function get_StructureofROID($keyword="",$debug=false)
		{
		if ($keyword=="") $keyword="Schalter";
		$status=$this->RemoteAccessServerTable();
        if ($debug) echo "get_StructureofROID($keyword ...) \n";
		/* Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben) */
		$remServer=$this->get_listofROIDs();
        //print_R($remServer);
		$struktur=array();
        $client = IPS_GetName(0);
		foreach ($remServer as $Name => $Server)
			{
    		if ($debug) echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
			if ( $status[$Name]["Status"] == true )
				{
	            $xconfig=$this->getXConfig($Server["Adresse"]);
                if (isset($xconfig[$client]["."][$keyword]))
                    {
                    $struktur[$Name]=array();
                    foreach($xconfig[$client]["."][$keyword] as $key=>$entry)
                        {
                        if ($debug) echo "      ".str_pad($key,30)."   ".json_encode($entry)."\n";
                        $oid=$entry["OID"];
                        $struktur[$Name][$oid]["Name"]=$key;
                        $struktur[$Name][$oid]["OID"]=$oid;
                        }
                    //print_R($struktur);        
                    }
                else
                    {
                    $id=(integer)$Server[$keyword];
                    $rpc = new JSONRPC($Server["Adresse"]);	
                    $children=$rpc->IPS_GetChildrenIDs($id);
                    $struktur[$Name]=array();			
                    foreach ($children as $oid)
                        {
                        $struktur[$Name][$oid]["Name"]=$rpc->IPS_GetName($oid);
                        $struktur[$Name][$oid]["OID"]=$oid;	
                        $struktur[$Name][$oid]["Hide"]=true;							
                        }
                    echo "Warning, no XConfigurator Data availablem, live fetching needs more time.\n";
                    }
				}		
			}		
		return($struktur);
		}

	/**
	 * @public
	 *
	 * alle OIDs der bei addRemoteServer ermittelten Ergebnisse ausgeben
	 *
	 *
	 */
	public function get_listofOIDs()
		{
		return ($this->listofOIDs);
		}

	/**
	 * @public
	 *
	 * von der ursprünglichen function uebernommen, Vereinheitlichung des Anlegens von Remote Variablen
	 * 
     * id           Kategorie OID vom RemoteServer
	 * struktur     wenn nicht übergeben, wird versucht sie aus der Struktur/Children des Remote Server zu ermitteln
     *              oid => name oder oid => array(Name => name)
     * type         0 Boolean usw.
     *
	 */
	function RPC_CreateVariableByName($rpc, $id, $name, $type, $struktur=array())
		{

		/* type steht für 0 Boolean 1 Integer 2 Float 3 String */

		$result="";
		$size=sizeof($struktur);
		if ($size==0)                       // ermitteln der Struktur
			{
			$children=$rpc->IPS_GetChildrenIDs($id);
			foreach ($children as $oid)
				{
				$struktur[$oid]=$rpc->IPS_GetName($oid);
				}		
			//echo "    RPC_CreateVariableByName, Struktur nicht übergeben, wird neu ermitteln.\n";
			//echo "Struktur :\n";
			//print_r($struktur);
			}
		else
			{
			//echo "    RPC_CreateVariableByName, Struktur übergeben.\n";
			//print_r($struktur);
			}					
        /* struktur hat oid => array(Name => name) oder oid => name */
		foreach ($struktur as $oid => $oname)               
			{
			if ( isset($oname["Name"]) )            // struktur hat oid => array(Name => name)
				{
				if ($name==$oname["Name"]) 
					{
					$result=$name;$vid=$oid;
					echo "     RPC_CreateVariableByName, Variable ".$name." bereits als ".$vid." angelegt, keine weiteren Aktivitäten.\n";					
					}
				}
			else
				{
				if ($name==$oname)                  // struktur hat oid => name
					{
					$result=$name;$vid=$oid;
					echo "      RPC_CreateVariableByName, Variable ".$name." bereits als ".$vid." angelegt, keine weiteren Aktivitäten.\n";					
					}
				}			
			//echo "Variable ".$name." bereits angelegt, keine weiteren Aktivitäten.\n";		
			}
		if ($result=="")
			{
            echo "  --> Variable $name mit Typ $type auf Server neu anlegen:\n";
			$vid = $rpc->IPS_CreateVariable($type);
			$rpc->IPS_SetParent($vid, $id);
			$rpc->IPS_SetName($vid, $name);
			$rpc->IPS_SetInfo($vid, "this variable was created by script. ");
			echo "  --> Variable ".$name." auf Server als ".$vid." neu erzeugt.\n";
			}
		//echo "Fertig mit ".$vid."\n";
		return $vid;
		}
		

	/******************************************************************/

	function RPC_CreateCategoryByName($rpc, $id, $name)
		{

		/* erzeugt eine Category am Remote Server */

		$result="";
		$struktur=$rpc->IPS_GetChildrenIDs($id);
		foreach ($struktur as $category)
		   {
		   $oname=$rpc->IPS_GetName($category);
		   //echo str_pad($oname,20)." ".$category."\n";
		   if ($name==$oname) {$result=$name;$vid=$category;}
		   }
		if ($result=="")
		   {
	      $vid = $rpc->IPS_CreateCategory();
    	  $rpc->IPS_SetParent($vid, $id);
	      $rpc->IPS_SetName($vid, $name);
    	  $rpc->IPS_SetInfo($vid, "this category was created by script. ");
	      }
    	return $vid;
		}

    /* für evaluateVariable, die Struktur für zusätzliche Module auf allen remote Logging Servern erstellen
     * ist abhängig ob das Modul geladen wurde
     * nameModule ist zum Beispiel Guthaben, Autosteuerung,OperationCenter
     *
     * gleiche Funktion wie get_StructureofROID nur mit zusätzlichen CreateCategory
     *
     * struktur gibt jetzt oid mit Name und Parent aus
     * auf Ebene oid gibt es ModuleID für die category OID
     *
     */
	function RPC_CreateModuleByName($nameModule)
		{
        $struktur=array();$guthID=array();
		$remServer=$this->get_listofROIDs();       // Liste der ROIDs der Remote Logging Server (mit Status Active und für Logging freigegeben) aus dem Logging File
        $status=$this->RemoteAccessServerTable();
        foreach ($remServer as $Name => $Server)
            {
            echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
            if ( $status[$Name]["Status"] == true )
                {
                $rpc = new JSONRPC($Server["Adresse"]);
                $categoryID=RPC_CreateCategoryByName($rpc, (integer)$Server["ServerName"], $nameModule);
                $children=$rpc->IPS_GetChildrenIDs($categoryID);
                $struktur[$Name]=array();
                $struktur[$Name]["ModuleID"]   = $categoryID;
                foreach ($children as $oid)
                    {
                    $struktur[$Name][$oid]["Name"]   = $rpc->IPS_GetName($oid);
                    $struktur[$Name][$oid]["Parent"] = $categoryID;
                    }		
                }
            }
        return($struktur);
        }

	/*****************************************************************
	 *
	 * Übergabe ist die Homematic Struktur 
	 * das Keyword, derzeit HUMIDITY oder TEMPERATURE, damit wird der richtige Sensor in der Homematic Tabelle gefunden
	 * das Profil für die Remote Variablenerstellung, so wird auf dem RemoteServer formatiert
	 * aus dem Keyword wird der index berechnet, der index ist die Kategorie in der die Visualization abgelegt ist
	 *
	 **********************************************************************/

	function RPC_CreateVariableField($Homematic, $keyword, $profile,$startexec=0,$struktur=array())
		{
		IPSUtils_Include ("EvaluateVariables_ROID.inc.php","IPSLibrary::app::modules::RemoteAccess");
		$remServer=ROID_List();
		if ($startexec==0) {$startexec=microtime(true);}
		
		switch ($keyword)
			{
			case "TEMPERATURE":
				$index="Temperatur";
				break;
			case "HUMIDITY":
				$index="Humidity";
				break;
			default:
				$index=$profile;
				break;
			}	

		echo "===============================================================\n";
		echo "RPC_CreateVariableField für ".$keyword." Visualization Index : ".$index."\n";		
		if ( sizeof($struktur) == 0 ) $struktur=$this->RPC_getExtendedStructure($remServer,$index);
		foreach ($remServer as $Name => $Server)
			{
			echo "Bearbeite Server ".$Name." für Keyword ".$keyword." Index ".$index."  Visualization OID Werte aus vorermittelteter ROID_List():\n";
			print_r($Server);
			if (sizeof($struktur[$Name])>0)
				{
				echo "Struktur Server für Categorie auf Visualization.RemoteAccess.".IPS_GetName(0).".".$index.":\n";
				foreach ($struktur[$Name] as $oid => $entry)
					{
					echo "   OID ".$oid." Name ".$entry["Name"]." \n";
					} 
				}	
			}
		//print_r($struktur);			
		echo "Homematic Variablen der Reihe nach durchgehen:\n";
		foreach ($Homematic as $Key)
			{
			/* alle Feuchtigkeits oder Temperaturwerte ausgeben */
			if (isset($Key["COID"][$keyword])==true)
				{
				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$variabletyp=IPS_GetVariable($oid);
				if ($variabletyp["VariableProfile"]!="")
					{
					echo "   ".str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
					}
				else
					{
					echo "   ".str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       ".number_format((microtime(true)-$startexec),2)." Sekunden\n";
					}
				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					//echo "Bearbeite Server ".$Name."\n";
					//print_r($Server);
					$rpc = new JSONRPC($Server["Adresse"]);
					$result=$this->RPC_CreateVariableByName($rpc, (integer)$Server[$index], $Key["Name"], 2, $struktur[$Name]);	    /* Variablen für Aufruf function RPC_CreateVariableByName($rpc, $id, $name, $type, $struktur=array() */
					$rpc->IPS_SetVariableCustomProfile($result,$profile);
					$rpc->IPS_SetHidden($result,false);					
					$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
					$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
					$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
					$parameter.=$Name.":".$result.";";
					$struktur[$Name][$oid]["Active"]=true;						
					}
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */
				echo "RegisterEvent ".$oid." mit \"OnChange\",\"IPSComponentSensor_Temperatur,".$parameter." IPSModuleSensor_Temperatur,1,2,3\"\n";
				if ($keyword=="TEMPERATURE")
					{
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Temperatur,'.$parameter,'IPSModuleSensor_Temperatur,1,2,3');
					}
				else
					{
					$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Feuchtigkeit,'.$parameter,'IPSModuleSensor_Feuchtigkeit,1,2,3');
					}
				}
			}
		$this->RPC_setHiddenExtendedStructure($remServer,$struktur);			
		}
		
	/*****************************************************************
	 *
	 * get extended Struktur von Visualization der remote Server
	 *
	 **********************************************************************/

	function RPC_getExtendedStructure($remServer,$index)
		{
		$status=RemoteAccessServerTable();
		$struktur=array();
		foreach ($remServer as $Name => $Server)
			{
			$struktur[$Name]=array();
			echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
			if ( $status[$Name]["Status"] == true )
				{
				$id=(integer)$Server[$index];			/*   <=== change here */
				$rpc = new JSONRPC($Server["Adresse"]);	
				$children=$rpc->IPS_GetChildrenIDs($id);
				$struktur[$Name]=array();
				foreach ($children as $oid)
					{
					//$struktur[$Name][$oid]=$rpc->IPS_GetName($oid);
					$struktur[$Name][$oid]["Name"]=$rpc->IPS_GetName($oid);
					$struktur[$Name][$oid]["OID"]=$oid;
					$struktur[$Name][$oid]["Active"]=false;						
					}
				}
			}
		return ($struktur);
		}
		
	/*****************************************************************
	 *
	 * write extended Struktur von Visualization der remote Server, eine Zeile pro Eintrag
	 *
	 **********************************************************************/	
					
	function RPC_writeExtendedStructure($struktur)
		{
		foreach ($struktur as $Name => $Server)
			{
			echo "Bearbeite Server ".$Name." \n";
			foreach ($Server as $oid => $entry)
				{
				echo "   OID ".$oid." Name ".$entry["Name"]." \n";
				} 
			}		
		}

		
	/*****************************************************************
	 *
	 * anhand der Struktur von Visualization für jeden einzelnen remote Server Eintraege die nicht mehr 
	 * benötigt werden, Zustand = false dann hiden (setHidden true)
	 *
	 **********************************************************************/	

	function RPC_setHiddenExtendedStructure($remServer,$struktur)
		{		
		foreach ($struktur as $server => $oids)
			{
			echo "Server ".$server." (".$remServer[$server]["Adresse"].") OIDs die nicht mehr aktuell sind verstecken  :\n";
			$rpc = new JSONRPC($remServer[$server]["Adresse"]);
			foreach ($oids as $oid => $entry)
				{
				if ($entry["Active"] == false)
					{
					$rpc->IPS_SetHidden($oid,true);
					echo "   Hide OID ".$oid." Name ".$entry["Name"]."    \n";
					}
				} 
			}
		}	
				
	/*****************************************************************
	 *
	 * wandelt die Liste der remoteAccess server in eine bessere Tabelle um und hängt den aktuellen Status zur Erreichbarkeit in die Tabell ein
	 * der Status wird alle 60 Minuten von operationCenter ermittelt. Wenn Modul nicht geladen wurde wird einfach true angenommen
	 *
	 *****************************************************************************/

	function RemoteAccessServerTable()
		{
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$result=$moduleManager->GetInstalledModules();
			if (isset ($result["OperationCenter"]))
				{
				$moduleManager_DM = new IPSModuleManager('OperationCenter');     /*   <--- change here */
				$CategoryIdData   = $moduleManager_DM->GetModuleCategoryID('data');
				$Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$CategoryIdData);
				$RemoteServer=array();
	        	//$remServer=RemoteAccess_GetConfiguration();
				//foreach ($remServer as $Name => $UrlAddress)
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
					if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
						{				
						$IPS_UpTimeID = CreateVariableByName($Access_categoryId, $Name."_IPS_UpTime", 1);
						$RemoteServer[$Name]["Url"]=$UrlAddress;
						$RemoteServer[$Name]["Name"]=$Name;
						if (GetValue($IPS_UpTimeID)==0)
							{
							$RemoteServer[$Name]["Status"]=false;
							}
						else
							{
							$RemoteServer[$Name]["Status"]=true;
							}
						}
					}
				}
			else
				{
				$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
				foreach ($remServer as $Name => $Server)
					{
					$UrlAddress=$Server["ADRESSE"];
					if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
						{				
						$RemoteServer[$Name]["Url"]=$UrlAddress;
						$RemoteServer[$Name]["Name"]=$Name;
						$RemoteServer[$Name]["Status"]=true;
						}
					}	
			   }
		return($RemoteServer);
		}

	function writeRemoteAccessServerTable($remServer)
		{
		$print="";
		foreach ($remServer as $Name => $RemoteServer)
			{
			$print.="   ".str_pad($RemoteServer["Name"],16)." ".str_pad($RemoteServer["Url"],88)." ".($RemoteServer["Status"] ? 'Ja' : 'Nein')."\n";
			}
		return($print);
		}

	/*****************************************************************
 	 *
	 * wandelt die Liste der remoteAccess_GetServerConfig  in das alte Format der tabelle RemoteAccess_GetConfiguration um
	 * Neuer Name , damit alte Funktionen keine Fehlermeldung liefern 
	 *
	 *****************************************************************************/
 
	function RemoteAccess_GetConfigurationNew()
		{
		$RemoteServer=array();
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		foreach ($remServer as $Name => $Server)
			{
			$UrlAddress=$Server["ADRESSE"];
			if ( (strtoupper($Server["STATUS"])=="ACTIVE") and (strtoupper($Server["LOGGING"])=="ENABLED") )
				{				
				$RemoteServer[$Name]=$UrlAddress;
				}
			}	
		return($RemoteServer);
		}



	/******************************************************************/

	private function add_variable($variableID,&$includefile,&$count)
		{
		$includefile.='    "'.IPS_GetName($variableID).'" => array('."\n         ".'"OID" => '.$variableID.', ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($variableID).'", ';
		$variabletyp=IPS_GetVariable($variableID);
		//print_r($variabletyp);
		//echo "Typ:".$variabletyp["VariableType"]."\n";
		$includefile.="\n         ".'"Typ" => '.$variabletyp["VariableType"].', ';
		$includefile.="\n         ".'"Order" => "'.$count++.'", ';
		$includefile.="\n             ".'	),'."\n";
		}

	/******************************************************************/

	private function add_variablewithname($variableID,$name,&$includefile,&$count)
		{
		$includefile.='    "'.$name.'" => array('."\n         ".'"OID" => '.$variableID.', ';
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
 * RA_Autosteuerung class
 *
 * Autosteuerungsvariablen finden	
 *
 *
 **********************************************************************************/	

class RA_Autosteuerung extends RemoteAccess
    {
    private $statusAnwesendID;


    public function __construct()
        {
        IPSUtils_Include ('Autosteuerung_Class.inc.php', 'IPSLibrary::app::modules::Autosteuerung');            
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager)) 
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
            }
        $CategoryIdData             = $moduleManager->GetModuleCategoryID('data');
    	$categoryId_Ansteuerung     = IPS_GetObjectIDByIdent("Ansteuerung", $CategoryIdData);
        $category_Anwesenheitserkennung = IPS_GetObjectIDByName("Anwesenheitserkennung",$categoryId_Ansteuerung);
        $this->statusAnwesendID     = @IPS_GetObjectIDByName("StatusAnwesend",$category_Anwesenheitserkennung);
        parent::__construct();
        }

    public function getStatusAnwesendID()
        {
        return($this->statusAnwesendID);
        }

    }

/*****************************************************************************
 *
 * IPSMessageHandlerExtended class
 *
 * IPSMessageHandler verbessern, wenn möglich. Zusätzlich unregisterEvent, deleteEvent
 *
 *
 * uses $eventConfiguration
 *  Get_EventConfigurationAuto
 *  Get_EventConfigurationCust
 *  StoreEventConfiguration
 *  DeleteEvent
 *  RegisterEvent
 *  UnRegisterEvent
 *  UpdateEvent
 *
 * Von der Parent class übrig
 *  Set_EventConfigurationAuto
 *	CreateEvents
 *  CreateEvent
 *  RegisterOnChangeEvent
 *  RegisterOnUpdateEvent
 *  HandleIREvent
 *  HandleEvent
 *  IPSMessageHandler_HandleLibraryEvent
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
	    private static function StoreEventConfiguration($configuration) 
            {
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
		public static function RegisterEvent($variableId, $eventType, $componentParams, $moduleParams, $debug=false) 
            {
            if ($debug) echo "IPSMessageHandlerExtended::RegisterEvent($variableId,...) ";
			$configurationAuto = self::Get_EventConfigurationAuto();
			$configurationCust = self::Get_EventConfigurationCust();

			// Search Configuration
			$found = false;
			if (array_key_exists($variableId, $configurationCust)) {  $found = true; }

			if (!$found) 
                {
				if (array_key_exists($variableId, $configurationAuto)) 
                    {
					$moduleParamsNew = explode(',', $moduleParams);
					$moduleClassNew  = $moduleParamsNew[0];

					$params = $configurationAuto[$variableId];
				   
					for ($i=0; $i<count($params); $i=$i+3) 
                        {
                        if ($debug) echo "registered in configurationAuto : \"$eventType\" == \"$params[0]\", \"$componentParams\" == \"$params[1]\", \"$moduleParams\" == \"$params[2]\" ";
						$moduleParamsCfg = $params[$i+2];
						$moduleParamsCfg = explode(',', $moduleParamsCfg);
						$moduleClassCfg  = $moduleParamsCfg[0];
						// Found Variable and Module --> Update Configuration
						if ($moduleClassCfg=$moduleClassNew) 
                            {
							$found = true;
							$configurationAuto[$variableId][$i]   = $eventType;
							$configurationAuto[$variableId][$i+1] = $componentParams;
							$configurationAuto[$variableId][$i+2] = $moduleParams;
						    }
					    }
				    }


				// Variable NOT found --> Create Configuration
				if (!$found) 
                    {
					$configurationAuto[$variableId][] = $eventType;
					$configurationAuto[$variableId][] = $componentParams;
					$configurationAuto[$variableId][] = $moduleParams;
				    }

				self::StoreEventConfiguration($configurationAuto);
				self::CreateEvent($variableId, $eventType);
			    }
            if ($debug) echo "\n";
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
		
		/**
		 * Methode um autretende Events zu processen. Mit dem fork von HandleEvent wird sichergestellt dass das Event auch wirklich bearbeitet wird
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 */
		public function UpdateEvent($variable, $value, $debug=false) {
			$configurationAuto = self::Get_EventConfigurationAuto();
			$configurationCust = self::Get_EventConfigurationCust();

			if (array_key_exists($variable, $configurationCust)) {
				$params = $configurationCust[$variable];
			} elseif (array_key_exists($variable, $configurationAuto)) {
				$params = $configurationAuto[$variable];
			//} elseif ($variable==IPSMH_IRTRANS_BUTTON_VARIABLE_ID) {
				//$params = '';
				//$this->HandleIREvent($variable, $value);
			} else {
				$params = '';
				IPSLogger_Wrn(__file__, 'Variable '.$variable.' NOT found in IPSMessageHandler Configuration!');
			}
            if ($debug) echo "IPSMessageHandlerExtended::UpdateEvent aufgerufen für Variable ".$variable." : ".json_encode($params)."\n";
            //print_r($params);
            //IPSLogger_Inf(__file__, 'IPSMessageHandler HandleEvent für Component/Module '.json_encode($params));			

			if ($params<>'') {
				if (count($params) < 3) {
					throw new IPSMessageHandlerException('Invalid IPSMessageHandler Configuration, Event Defintion needs 3 parameters');
				}
				if ($debug) echo "Create Component ".$params[1]."\n";
                $component = IPSComponent::CreateObjectByParams($params[1]);
				if ($debug) echo "Create Module ".$params[2]."\n";
				$module    = IPSLibraryModule::CreateObjectByParams($params[2]);

				if (function_exists('IPSMessageHandler_BeforeHandleEvent')) {
					if (IPSMessageHandler_BeforeHandleEvent($variable, $value, $component, $module)) {
                        if (method_exists($component,"UpdateEvent"))
                            {
                            if ($debug) echo "Component->UpdateEvent aufrufen\n";
	    					$component->UpdateEvent($variable, $value, $module, $debug);
                            }
                        else
                            {
                            if ($debug) echo "Component->HandleEvent aufrufen\n";
	    					$component->HandleEvent($variable, $value, $module);
                            }

    					if (function_exists('IPSMessageHandler_AfterHandleEvent')) {
							IPSMessageHandler_AfterHandleEvent($variable, $value, $component, $module);
						}
					}
				} else {
					$component->UpdateEvent($variable, $value, $module,$debug);
					if (function_exists('IPSMessageHandler_AfterHandleEvent')) {
						IPSMessageHandler_AfterHandleEvent($variable, $value, $component, $module);
					}
				}
			}
		}
	


	}  /* Ende class */	
	
/****************************************************************************************************************
 *
 *                                      Functions
 *
 *      installAccess
 *
 ****************************************************************************************************************/

	function installAccess($Elements,$keyword,$identifier,$profile)
		{
		$remServer=ROID_List();
		echo "Fuer Logging aktivierte Remote Server:\n";
		print_r($remServer);
		$status=RemoteAccessServerTable();
		$params=array();		

		echo "Install RemoteAccess für die Stellmotoren (Aktuatoren).\n";
		foreach ($Elements as $Key)
			{
			if ( (isset($Key["COID"][$keyword])==true) )
				{
				/* alle Stellmotoren ausgeben */

				$oid=(integer)$Key["COID"][$keyword]["OID"];
				$variabletyp=IPS_GetVariable($oid);
      		
				if ($variabletyp["VariableProfile"]!="")
			 		{
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}
				else
					{
					echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
					}

				/* check, es sollten auch alle Quellvariablen gelogged werden */
				
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				if (AC_GetLoggingStatus($archiveHandlerID,$oid)==false)
					{
					/* Wenn variable noch nicht gelogged automatisch logging einschalten */
					AC_SetLoggingStatus($archiveHandlerID,$oid,true);
					AC_SetAggregationType($archiveHandlerID,$oid,0);
					IPS_ApplyChanges($archiveHandlerID);
					echo "Variable ".$oid." Archiv logging aktiviert.\n";
					}
				
				/* Install für RemoteAccess */

				$parameter="";
				foreach ($remServer as $Name => $Server)
					{
					echo "   Server : ".$Name." mit Adresse ".$Server["Adresse"]."  Erreichbar : ".($status[$Name]["Status"] ? 'Ja' : 'Nein')."\n";
					if ( $status[$Name]["Status"] == true )
						{				
						$rpc = new JSONRPC($Server["Adresse"]);
						$result=RPC_CreateVariableByName($rpc, (integer)$Server[$identifier], $Key["Name"], 0);
						$rpc->IPS_SetVariableCustomProfile($result,$profile);
						$rpc->AC_SetLoggingStatus((integer)$Server["ArchiveHandler"],$result,true);
						$rpc->AC_SetAggregationType((integer)$Server["ArchiveHandler"],$result,0);
						$rpc->IPS_ApplyChanges((integer)$Server["ArchiveHandler"]);				//print_r($result);
						$parameter.=$Name.":".$result.";";
						}
					}
				$params[$oid]=$parameter;	
				}
			}
		return($params);	
		}




	
	/** @}*/
?>