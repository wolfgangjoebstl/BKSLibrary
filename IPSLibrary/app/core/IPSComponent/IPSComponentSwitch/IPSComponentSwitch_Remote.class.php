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
	
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_Remote.class.php
	 * @author        Wolfgang Jöbstl inspiriert durch Andreas Brauneis
	 *
	 *
	 */

	/******************************
	 *
	 * Für jede Variable die gelogged wird erfolgt ein Eintrag ins config File IPSMessageHandler_Configuration
	 * Es wird ein Event erzeugt dass bei Änderung der Variable HandleEvent mit VariableID udn Wert aufruft.
     *
     * Unterschied zu IPSComponentSwitch_RHomematic:
	 *   __construct
     *      zusaetzliche Variablen für instanceID und SupportsonTime
     *      Verbiegen des DutyCycle Error Handlers um nicht Erreichbarkeits Events etc abzufangen
	 *
	 ****************************************/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	class IPSComponentSwitch_Remote extends IPSComponentSwitch {

		private $installedmodules;
		private $remoteOID;	

		private $remServer;			
			
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Remote Objektes
		 *
		 * @param $var1   OID der STATE Variable des Schalters
		 */
		public function __construct($var1=Null) 
			{
			$this->remoteOID    = $var1;
			//echo "IPSComponentSensor_Remote: Construct Switch with ".$var1.".\n";			
			
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
		 * @param IPSModuleSwitch $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSwitch $module)
			{
			//echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
	   		IPSLogger_Dbg(__file__, 'HandleEvent: Switch Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new Switch_Logging($variable);
			$result=$log->Switch_LogValue();
			
			if ($this->remoteOID != Null)
			   {
				$params= explode(';', $this->remoteOID);
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
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false) {
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {
			GetValue(IPS_GetVariableIDByIdent('STATE', $this->instanceId));
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
	 * in Auswertung wird eine lokale Kopie aller Register angelegt und archiviert. 
	 * in Nachrichten wird jede Änderung als Nachricht mitgeschrieben 
	 *
	 **************************/

	class Switch_Logging extends Logging
		{
		private $variable;
		private $variablename;
		private $variableLogID;

		private $SwitchAuswertungID;

		private $configuration;
		private $installedmodules;
				
		function __construct($variable)
			{
            $dosOps= new dosOps();
			//echo "Construct IPSComponentSswitch_Remote Logging for Variable ID : ".$variable."\n";
			$this->variable=$variable;
			$result=IPS_GetObject($variable);
			$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist immer der Parent Name 
		
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			$name="Switch-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
	    		IPS_SetInfo($vid, "this category was created by script IPSComponentSwitch_Remote. ");
	    		}
			$name="Switch-Auswertung";
			$SwitchAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($SwitchAuswertungID==false)
				{
				$SwitchAuswertungID = IPS_CreateCategory();
				IPS_SetParent($SwitchAuswertungID, $CategoryIdData);
				IPS_SetName($SwitchAuswertungID, $name);
				IPS_SetInfo($SwitchAuswertungID, "this category was created by script IPSComponentSwitch_Remote. ");
	    		}
			$this->SwitchAuswertungID=$SwitchAuswertungID;
			if ($variable<>null)
				{
				/* lokale Spiegelregister aufsetzen */
				echo "Lokales Spiegelregister als Boolean auf ".$this->variablename." ".$SwitchAuswertungID." ".IPS_GetName($SwitchAuswertungID)." anlegen.\n";
				$this->variableLogID=CreateVariable($this->variablename,0,$SwitchAuswertungID, 10, "", null, null );  /* 0 Boolean, 2 steht für Float, alle benötigten Angaben machen, sonst Fehler */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				IPS_SetVariableCustomProfile($this->variableLogID,'~Switch');
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["SwitchLog"]))
		   		 { $directory=$directories["LogDirectories"]["SwitchLog"]; }
			else {$directory="C:/Scripts/Switch/"; }	
			$dosOps->mkdirtree($directory);
			$filename=$directory.$this->variablename."_Switch.csv";
			parent::__construct($filename,$vid);
			}

		function Switch_LogValue()
			{
			$result=GetValueFormatted($this->variable);
			SetValue($this->variableLogID,GetValue($this->variable));
			//echo "Neuer Wert fuer ".$this->variablename." ist ".GetValueFormatted($this->variable)."\n";
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			//echo "done.\n";
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