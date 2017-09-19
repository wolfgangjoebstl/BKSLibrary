<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatControl_Homematic.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentShutter_Homematic
    *
    * Definiert ein IPSComponentShutter_Homematic Object, das ein IPSComponentShutter Object für Homematic implementiert.
    *
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatControl.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');

	class IPSComponentHeatControl_Homematic extends IPSComponentHeatControl {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;

		private $remServer;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentheatControl_Homematic Objektes
		 *
		 * legt die Remote Server an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern
		 
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $reverseControl Reverse Ansteuerung des Devices
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null) 
			{
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			
			echo "construct IPSComponentHeatControl_Homematic with parameter ".$this->RemoteOID."  ".$this->tempObject."  ".$this->tempValue."\n";
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
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

		/*
		 * aktueller Status der remote logging server
		 */	
	
		public function remoteServerAvailable()
			{
			return ($this->remServer);			
			}
			


		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IIPSModuleHeatControl $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleHeatControl $module)
			{
			echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
	   		IPSLogger_Dbg(__file__, 'HandleEvent: HeatControl Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new HeatControl_Logging($variable);
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
							//echo "Server : ".$Server." Remote OID: ".$roid."\n";
							
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
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetState($power, $level)
			{
			//echo "Adresse:".$this->rpcADR."und Level ".$level." Power ".$power." \n";
			if ($this->rpcADR=="")
			   {
				if (!$power) {
					HM_WriteValueFloat($this->instanceId, "LEVEL", 0);
					}
				else
					{
					$levelHM = $level / 100;
					HM_WriteValueFloat($this->instanceId, "LEVEL", $levelHM);
					}
			   }
			else
			   {
				$rpc = new JSONRPC($this->rpcADR);
				if (!$power) {
					$rpc->HM_WriteValueFloat($this->instanceId, "LEVEL", 0);
					}
				else
					{
					$levelHM = $level / 100;
					$rpc->HM_WriteValueFloat($this->instanceId, "LEVEL", $levelHM);
					}
				}
			}

		/**
		 * @public
		 *
		 * Hinauffahren der Beschattung
		 */
		public function MoveUp(){
		   if ($this->reverseControl) {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 0);
			} else {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 1);
			}
		}
		
		/**
		 * @public
		 *
		 * Hinunterfahren der Beschattung
		 */
		public function MoveDown(){
		   if ($this->reverseControl) {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 1);
			} else {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 0);
			}
		}
		
		/**
		 * @public
		 *
		 * Stop
		 */
		public function Stop() {
			HM_WriteValueBoolean($this->instanceId , 'STOP', true);
		}

	}

	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 **************************/

	class HeatControl_Logging extends Logging
		{
		private $variable;
		private $variablename;
		public $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
		public $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
				
		private $HeatControlAuswertungID;

		private $configuration;
		private $installedmodules;
				
		function __construct($variable)
			{
			//echo "Construct IPSComponentSensor HeatControl Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			$result=IPS_GetObject($variable);
			$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist immer der Parent Name 
		
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			$name="HeatControl-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
	    		IPS_SetInfo($vid, "this category was created by script IPSComponentHeatControl_Homematic. ");
	    		}
			/* Create Category to store the HeatControl-Spiegelregister */	
			$name="HeatControl-Auswertung";
			$HeatControlAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($HeatControlAuswertungID==false)
				{
				$HeatControlAuswertungID = IPS_CreateCategory();
				IPS_SetParent($HeatControlAuswertungID, $CategoryIdData);
				IPS_SetName($HeatControlAuswertungID, $name);
				IPS_SetInfo($HeatControlAuswertungID, "this category was created by script IPSComponentHeatControl_Homematic. ");
	    		}
			$this->HeatControlAuswertungID=$HeatControlAuswertungID;
			if ($variable<>null)
				{
				/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird der Name des Parent genommen */
				echo "Lokales Spiegelregister als Integer auf ".$this->variablename." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
				/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
				$this->variableLogID=CreateVariable($this->variablename,1,$this->HeatControlAuswertungID, 10, "~Intensity.100", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				if (function_exists('get_IPSComponentHeatControlConfig'))
					{
					$powerConfig=get_IPSComponentHeatControlConfig();
					echo "Look for ".$variable." in Configuration.\n";
					if ( isset($powerConfig["HeatingPower"][$variable]) )
						{
						echo "Lokales Spiegelregister für Energiewert auf ".$this->variablename."_Energy"." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
						/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
						$this->variableEnergyLogID=CreateVariable($this->variablename."_Energy",2,$this->HeatControlAuswertungID, 10, "~Electricity.HM", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
						$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
						AC_SetLoggingStatus($archiveHandlerID,$this->variableEnergyLogID,true);
						AC_SetAggregationType($archiveHandlerID,$this->variableEnergyLogID,0);      /* normaler Wwert */
						IPS_ApplyChanges($archiveHandlerID);						
						
						
						}
					}					
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatControlLog"]))
		   		 { $directory=$directories["LogDirectories"]["HeatControlLog"]; }
			else {$directory="C:/Scripts/HeatControl/"; }	
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_HeatControl.csv";
			parent::__construct($filename,$vid);
			}

		function HeatControl_LogValue()
			{
			$result=number_format(GetValue($this->variable),2,',','.')." %";
	     	$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{
				$result=GetValueFormatted($this->variable);
				}
			else
				{
				$result=number_format(GetValue($this->variable),2,',','.')." %";				
				}
			$unchanged=time()-$variabletyp["VariableChanged"];
			$oldvalue=GetValue($this->variableLogID);
			SetValue($this->variableLogID,GetValue($this->variable));
			echo "Neuer Wert fuer ".$this->variablename."(".$this->variable.") ist ".GetValue($this->variable)." %. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
			if (function_exists('get_IPSComponentHeatControlConfig'))
				{
				$powerConfig=get_IPSComponentHeatControlConfig();
				echo "Look for ".$this->variable." in Configuration.\n";
				if ( isset($powerConfig["HeatingPower"][$this->variable]) )
					{
					SetValue($this->variableEnergyLogID,GetValue($this->variableEnergyLogID)+$oldvalue*$unchanged/60/60*$powerConfig["HeatingPower"][$this->variable]);
					}				
				}				
			
			if ( (isset ($this->installedmodules["DetectMovement"])) && false)
				{
				/* Detect Movement kann auch Temperaturen agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$DetectTemperatureHandler = new DetectTemperatureHandler();
				//print_r($DetectMovementHandler->ListEvents("Motion"));
				//print_r($DetectMovementHandler->ListEvents("Contact"));

				$groups=$DetectTemperatureHandler->ListGroups();
				foreach($groups as $group=>$name)
					{
					echo "Gruppe ".$group." behandeln.\n";
					$config=$DetectTemperatureHandler->ListEvents($group);
					$status=(float)0;
					$count=0;
					foreach ($config as $oid=>$params)
						{
						$status+=GetValue($oid);
						$count++;
						//echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
						echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid),30)."Status: ".GetValue($oid)." ".$status."\n";
						}
					if ($count>0) { $status=$status/$count; }
					echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
					/* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
					$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->TempAuswertungID,100, "~Temperature", null, null);
					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
					SetValue($statusID,$status);
			   		}
				}
			
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			}

		public function GetComponent() {
			return ($this);
			}
			
		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

		public function writeEvents($comp=true,$gesamt=false)
			{

			}
			
	   }





	/** @}*/
?>