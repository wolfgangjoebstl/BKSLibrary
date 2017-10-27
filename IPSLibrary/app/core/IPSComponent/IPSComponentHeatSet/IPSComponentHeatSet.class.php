<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatSet.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatSet
    *
    * Definiert ein IPSComponentHeatSet
    *
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	abstract class IPSComponentHeatSet extends IPSComponent {

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleSensor $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		abstract public function HandleEvent($variable, $value, IPSModuleHeatSet $module);

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll (ACHTUNG: wird nicht von
		 *                        allen Hardware Komponenten unterstützt).
		 */
		abstract public function SetState($value, $level);

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand des Dimmers
		 *
		 * @return integer aktueller Dimmer Zustand  
		 */
		abstract public function GetLevel();
		
		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		abstract public function GetState();

		/**
		 * @public
		 *
		 * Hinauffahren der Beschattung
		 */
		abstract public function MoveUp();

		/**
		 * @public
		 *
		 * Hinunterfahren der Beschattung
		 */
		abstract public function MoveDown();

		/**
		 * @public
		 *
		 * Stop
		 */
		abstract public function Stop();
				
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

		/*
		 * aktuellen Status der remote logging server bestimmen
		 */	
	
		public function remoteServerSet()
			{
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
					
		/*****************
		 *
		 * schreibt den Wert value auf die remote Server. Remote Server sind in RemoteOID mit Kurzname Doppelpunkt und Remote OID angelegt
		 * die Zuordnung Kurzname zu url steht im remServer array 
		 *
		 *****************************************/
		
		public function WriteValueRemote($value)
			{
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
							//echo "Server : ".$Server." Remote OID: ".$roid." Value ".$value."\n";
							$rpc->SetValue($roid, $value);
							}
						}
					}
				}			
			}

	}  /* ende class */
	


	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 **************************/

	class HeatSet_Logging extends Logging
		{
		private $variable;
		private $variablename;
		public $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
		
		public $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
		public $variablePowerLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
				
		private $HeatSetAuswertungID;
		private $powerConfig;					/* Powerwerte der einzelnen Heizkoerper, Null wenn Configfile nicht vorhanden */

		private $configuration;
		private $installedmodules;
				
		function __construct($variable,$variablename=Null)
			{
			echo "Construct IPSComponentSensor HeatSet Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			if ($variablename==Null)
				{
				$result=IPS_GetObject($variable);
				$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist der Parent Name wenn nicht anders angegeben
				} 
			else
				{
				$this->variablename=$variablename;
				}
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$CategoryIdData_Lib     = $moduleManager->GetModuleCategoryID('data');
			echo "  Kategorien im aktuellen Datenverzeichnis:".$CategoryIdData_Lib."   ".IPS_GetName($CategoryIdData_Lib)."\n";

			/* Find Data category of IPSComponent Module to store the Data */				
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im CustomComponents Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			
			/* Create Category to store the HeatSet-Nachrichten */				
			$name="HeatSet-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script IPSComponentHeatSet.");
				}
				
			/* Create Category to store the HeatControl-Spiegelregister */	
			$name="HeatSet-Auswertung";
			$HeatSetAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($HeatSetAuswertungID==false)
				{
				$HeatSetAuswertungID = IPS_CreateCategory();
				IPS_SetParent($HeatSetAuswertungID, $CategoryIdData);
				IPS_SetName($HeatSetAuswertungID, $name);
				IPS_SetInfo($HeatSetAuswertungID, "this category was created by script IPSComponentHeatSet_Homematic. ");
	    		}
			$this->HeatSetAuswertungID=$HeatSetAuswertungID;
			
			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
				echo "Lokales Spiegelregister als Float auf ".$this->variablename." unter Kategorie ".$this->HeatSetAuswertungID." ".IPS_GetName($this->HeatSetAuswertungID)." anlegen.\n";
				/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
				$this->variableLogID=CreateVariable($this->variablename,2,$this->HeatSetAuswertungID, 10, "~Temperature.HM", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				if (false)
				{
				$this->powerConfig=Null;
				if (function_exists('get_IPSComponentHeatConfig'))
					{
					$this->powerConfig=get_IPSComponentHeatConfig()["HeatingPower"];
					if ( isset($this->powerConfig[$variable]) )
						{
						$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
						echo "Lokales Spiegelregister für Energie- und Leistungswert unterhalb Variable ID ".$this->variableLogID." und Parent Kategorie ".IPS_GetName($this->HeatSetAuswertungID)." anlegen.\n";
						/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
						$this->variableEnergyLogID=CreateVariable($this->variablename."_Energy",2,$this->variableLogID, 10, "~Electricity", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
						AC_SetLoggingStatus($archiveHandlerID,$this->variableEnergyLogID,true);
						AC_SetAggregationType($archiveHandlerID,$this->variableEnergyLogID,0);      /* normaler Wwert */
						$this->variablePowerLogID=CreateVariable($this->variablename."_Power",2,$this->variableLogID, 10, "~Power", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
						AC_SetLoggingStatus($archiveHandlerID,$this->variablePowerLogID,true);
						AC_SetAggregationType($archiveHandlerID,$this->variablePowerLogID,0);      /* normaler Wwert */
						IPS_ApplyChanges($archiveHandlerID);						
						}
					else 
						{
						echo "Attention, Variable ID ".$variable." (".IPS_GetName($variable).") in get_IPSComponentHeatConfig HeatingPower not available !\n";
						$this->powerConfig=Null;
						}	
					}					
				} }

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatSetLog"]))
		   		 { $directory=$directories["LogDirectories"]["HeatSetLog"]; }
			else {$directory="C:/Scripts/HeatSet/"; }	
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_HeatSet.csv";
			parent::__construct($filename,$vid);
			}

		/* hier wird der Wert gelogged, Wert immer direkt aus der Variable nehmen, der übergebene Wert hat nur für Remote Write aber nicht für das Logging einen EInfluss */

		function HeatSet_LogValue($value=Null)
			{
			// result formatieren
			$variabletyp=IPS_GetVariable($this->variable);
			if ( ($variabletyp["VariableProfile"]!="" && ($value == Null) ))
				{
				$result=GetValueFormatted($this->variable);
				$value=GetValue($this->variable);
				}
			else
				{
				if ($value == Null) { $value=GetValue($this->variable); }
				$result=number_format($value,2,',','.')." %";				
				}
				
			$unchanged=time()-$variabletyp["VariableChanged"];
			$oldvalue=GetValue($this->variableLogID);
			SetValue($this->variableLogID,$value);
			echo "Neuer Wert fuer ".$this->variablename."(".$this->variable.") ist ".$value." °C. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";

			// Leistungs und Energiewerte berechnen
			if ( ($this->powerConfig<>Null) && false)
				{
				/* Werte sind in Integer Prozenten also 0 bis 100, daher Wert zusätzlich durch 100 */
				SetValue($this->variableEnergyLogID,(GetValue($this->variableEnergyLogID)+$oldvalue/100*$unchanged/60/60/1000*$this->powerConfig[$this->variable]));
				SetValue($this->variablePowerLogID,($value/100/1000*$this->powerConfig[$this->variable]));					
				}				
			
			if ( (isset ($this->installedmodules["DetectMovement"])) && false )
				{
				/* Detect Movement kann auch Leistungswerte agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$DetectHeatSetHandler = new DetectHeatSetHandler();
				//print_r($DetectMovementHandler->ListEvents("Motion"));
				//print_r($DetectMovementHandler->ListEvents("Contact"));

				$groups=$DetectHeatSetHandler->ListGroups();
				foreach($groups as $group=>$name)
					{
					echo "Gruppe ".$group." behandeln.\n";
					$config=$DetectHeatSetHandler->ListEvents($group);
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
					//$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->TempAuswertungID,100, "~Temperature", null, null);
					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
					//SetValue($statusID,$status);
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