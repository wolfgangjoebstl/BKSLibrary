<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentheatControl.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatControl
    *
    * Definiert ein IPSComponentHeatControl
    *
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


	class IPSComponentHeatControl extends IPSComponent {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		private $remServer;
		
		private $instanceId;    /* generelle Instanz mit der das Obkekt erkannt werden kann */
		private $TunerName;
		private $ZoneName;
		private $ChannelName;
		private $DataCatID;
		private $log_Denon;
		private $DenonSocketID;
		
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentHeatControl Objektes
		 *
		 * @param integer $instanceId InstanceId des Dummy Devices
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
		   //echo "Build Motion Sensor with ".$var1.".\n";
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			if (isset ($this->installedmodules["RemoteAccess"]))
				{
				IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
				$this->remServer	  = RemoteAccessServerTable();
				}
			else
				{								
				$this->remServer	  = array();
				}
			}

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleRGB $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSensor $module)
			{
			echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatControl Message Handler für VariableID '.$variable.' mit Wert '.$value);

			$log=new heatControl_Logging($variable);
			$result=$log->HeatControl_LogValue();
			
			if ($this->RemoteOID != Null)
				{
				$params= explode(';', $this->RemoteOID);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					//echo "Wert :".$val." Anzahl ",count($para)." \n";
            		if (count($para)==2)
               			{
						$Server=$this->remServer[$para[0]]["Url"];
						if ($this->remServer[$para[0]]["Status"]==true)
							{
							$rpc = new JSONRPC($Server);
							$roid=(integer)$para[1];
							$rpc->SetValue($roid, $value);
							}
						}
					}
				}
			}

		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu benützt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this).','.$this->instanceId;
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $power RGB Gerät On/Off
		 * @param integer $color RGB Farben (Hex Codierung)
		 * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
		 */
		public function SetState($power, $level) {
			//echo "Hurrah hier angekommen mit Parameter : ".$power."  ".$level."\n";
			$this->log_Denon->LogMessage("Script wurde über IPSLight aufgerufen.".$power." ".$level);
			$this->log_Denon->LogNachrichten("Script wurde über IPSLight aufgerufen.".$power." ".$level." ".$this->DenonSocketID);
			include (IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENON.Functions.ips.php");
			$volumeID=IPS_GetObjectIDByName("MasterVolume",$this->DataCatID);
			$MainZoneID=IPS_GetObjectIDByName("MainZonePower",$this->DataCatID);
			$InputSourceID=IPS_GetObjectIDByName("InputSource",$this->DataCatID);
			$PowerID=IPS_GetObjectIDByName("Power",$this->DataCatID);
			//echo "DataCatID :".$this->DataCatID."   ".$powerID." ".$volumeID."\n";
			if ($power == false)
				{
				DENON_Power($this->DenonSocketID, "STANDBY");
				SetValue($PowerID,false);
				DENON_MainZonePower($this->DenonSocketID, false);
				SetValue($MainZoneID,false);
				}
			else
				{
				DENON_Power($this->DenonSocketID, "ON");
				SetValue($PowerID,true);
				sleep(1);
				DENON_MainZonePower($this->DenonSocketID, true);
				SetValue($MainZoneID,true);
				sleep(1);
				$inputsource=$this->GetChannels();
				//print_r($inputsource);
				//echo "   |".$this->ChannelName."|";
				//echo "   ".$inputsource[$this->ChannelName]."\n";
				if (isset($inputsource[$this->ChannelName]))
				   {
					sleep(1);
				   //echo "Set Denon Input Source to ".$inputsource[$this->ChannelName]."\n";
					DENON_InputSource($this->DenonSocketID, $this->ChannelName);
					SetValue($InputSourceID,$inputsource[$this->ChannelName]);
					}
				}
			DENON_MasterVolumeFix($this->DenonSocketID, $level-80);
			SetValue($volumeID,$level-80);
			//DENON_MainZonePower($this->DenonSocketID, (string)$level."%");
			//print_r($this);
			//echo IPS_GetKernelDir()."scripts/".GetValue(IPS_GetObjectIDByName("!LW12_CLibrary",  IPS_GetParent($this->instanceId))).".ips.php";
			//require(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php");
			//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\LedAnsteuerung\LedAnsteuerung_Library.ips.php");
			//LW12_PowerToggle2($this->instanceId,$power);
			//LW12_setDecRGB2($this->instanceId,$color);
		}
		
		/**
		 * @public
		 *
		 * Aus dem Profil die installierten Channels herausfinden
		 *
		 * 
		 * 
		 * 
		 */
		public function GetChannels()
			{
			$result=array();
			$Profile = IPS_GetVariableProfileList();
			//print_r($Profile);
			$i=0; $found=false;
			foreach ($Profile as $profil)
			   {
			   $i++;
			   if ($profil=="DENON.InputSource") $found=true;
			   }
			if ($found)
			   {
				$ChannelList=IPS_GetVariableProfile("DENON.InputSource");
				//print_r( $ChannelList );
				foreach ($ChannelList["Associations"] as $channel)
				   {
				   //echo "    ".$channel["Value"]." : ".$channel["Name"]."  \n";
				   $result[$channel["Name"]]=$channel["Value"];
				   }
				}
			if (isset($result[$this->ChannelName]))
			   {
			   //echo "   ".$this->ChannelName." vorhanden, druecken Sie ".$result[$this->ChannelName]."  \n";
				}
			return ($result);
			}

		/**
		 * @public
		 *
		 * Aus dem Profil die installierten Channels herausfinden
		 *
		 *
		 *
		 *
		 */
		public function SetChannels($channel)
			{
			include (IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENON.Functions.ips.php");
			DENON_InputSource($this->DenonSocketID, $channel);
			
			}
	}  /* ende class */
	
	
	
	
	/******************************************************************************************************
	 *
	 *   Class Motion_Logging
	 *
	 ************************************************************************************************************/
	
	class HeatControl_Logging extends Logging
		{

		private $variable;
		private $variablename;
		private $MoveAuswertungID;
		
		private $configuration;
		
		/* zusaetzliche Variablen für DetectMovement Funktionen, Detect Movement ergründet Bewegungen im Nachhinein */
		private $EreignisID;
		private $GesamtID;
		private $GesamtCountID;
		private $variableLogID;
		private $motionDetect_NachrichtenID;
		private $motionDetect_DataID;
				
		/**********************************************************************
		 * 
		 * Construct und gleichzeitig eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 * es werden alle notwendigen Variablen erstmalig angelegt, bei Set_logValue werden keine Variablen angelegt, nur die Register gesetzt
		 *
		 *************************************************************************/
		 	
		function __construct($variable=null)
			{
			echo "Construct IPSComponentHeatControl Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			$result=IPS_GetObject($variable);
			$resultParent=IPS_GetObject((integer)$result["ParentID"]);
			if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
				{
				$this->variablename=IPS_GetName((integer)$result["ParentID"]);
				}
			else
				{
				$this->variablename=IPS_GetName($variable);
				}
			
			IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
			$this->configuration=get_IPSComponentLoggerConfig();

			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis : ".$CategoryIdData." (".IPS_GetName($CategoryIdData).").\n";
			$name="HeatControl-Nachrichten";
			$vid1=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid1==false)
				{
				$vid1 = IPS_CreateCategory();
				IPS_SetParent($vid1, $CategoryIdData);
				IPS_SetName($vid1, $name);
				IPS_SetInfo($vid1, "this category was created by script. ");
				}
			$name="HeatControl-Auswertung";
			$MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($MoveAuswertungID==false)
				{
				$MoveAuswertungID = IPS_CreateCategory();
				IPS_SetParent($MoveAuswertungID, $CategoryIdData);
				IPS_SetName($MoveAuswertungID, $name);
				IPS_SetInfo($MoveAuswertungID, "this category was created by script. ");
				}
			$this->MoveAuswertungID=$MoveAuswertungID;
	
			   //echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			$directory=$directories["LogDirectories"]["HeizungLog"];
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_Stromheizung.csv";
			parent::__construct($filename);
			}

		/**********************************************************************
		 * 
		 * Eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 *
		 *************************************************************************/

		function Set_LogValue($variable)
			{
			if ($variable<>null)
				{
				echo "Add Variable ID : ".$variable." (".IPS_GetName($variable).") für IPSComponentHeatControl Stromheizung Logging.\n";
				$this->variable=$variable;
				$result=IPS_GetObject($variable);
				$resultParent=IPS_GetObject((integer)$result["ParentID"]);
				if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
					{
					$this->variablename=IPS_GetName((integer)$result["ParentID"]);
					}
				else
					{
					$this->variablename=IPS_GetName($variable);
					}
				/* lokale Spiegelregister aufsetzen */
				echo 'DetectMovement Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID;
				$variabletyp=IPS_GetVariable($variable);
				if ($variabletyp["VariableProfile"]!="")
					{  /* Formattierung vorhanden */
					echo " mit Wert ".GetValueFormatted($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID." mit Wert ".GetValueFormatted($variable));
					}
				else
					{
					echo " mit Wert ".GetValue($variable)."\n";
					IPSLogger_Dbg(__file__, 'CustomComponent Construct: Variable erstellen, Basis ist '.$variable.' Parent '.$this->variablename.' in '.$this->MoveAuswertungID." mit Wert ".GetValue($variable));
					}				
				$this->variableLogID=CreateVariable($this->variablename,0,$this->MoveAuswertungID, 10,'~Motion',null,null );
				}
			}
	   
		function HeatControl_LogValue()
			{
			echo "Lets log motion, Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") ";
			$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{  /* Formattierung vorhanden */
				$resultLog=GetValueFormatted($this->variable);
				echo " mit formattiertem Wert : ".GetValueFormatted($this->variable)."\n";
				IPSLogger_Dbg(__file__, 'DetectMovement Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert ".GetValueFormatted($this->variable));
				}
			else
				{
				$resultLog=GetValue($this->variable);				
				echo " mit Wert : ".GetValue($this->variable)."\n";
				IPSLogger_Dbg(__file__, 'DetectMovement Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert ".GetValue($this->variable));
				}
			$result=GetValue($this->variable);
			$delaytime=$this->configuration["LogConfigs"]["DelayMotion"];
			if ($result==true)
				{
				SetValue($this->variableLogID,$result);
				echo "Jetzt wird der Timer im selben verzeichnis wie Script gesetzt : ".$this->variable."_EVENT"."\n";
		     	$now = time();
				$EreignisID = @IPS_GetEventIDByName($this->variable."_EVENT", IPS_GetParent($_IPS['SELF']));
				if ($EreignisID === false)
					{ //Event nicht gefunden > neu anlegen
					$EreignisID = IPS_CreateEvent(1);
   	         		IPS_SetName($EreignisID,$this->variable."_EVENT");
      	      		IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
         	   		}
        		IPS_SetEventCyclic($EreignisID,0,1,0,0,1,$delaytime);      /* konfigurierbar, zB alle 30 Minuten, d.h. 30 Minuten kann man still sitzen bevor keine Bewegung mehr erkannt wird */
				IPS_SetEventCyclicTimeBounds($EreignisID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
				IPS_SetEventScript($EreignisID,"if (GetValue(".$this->variable.")==false) { SetValue(".$this->variableLogID.",false); IPS_SetEventActive(".$EreignisID.",false);} \n");
	   			IPS_SetEventActive($EreignisID,true);
				}
	
			parent::LogMessage($resultLog);
			parent::LogNachrichten($this->variablename." mit Status ".$resultLog);
			}
			
		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu benützt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this);
			}

		public function GetComponent() {
			return ($this);
			}

	} /* ende class */	
	

	/** @}*/
?>