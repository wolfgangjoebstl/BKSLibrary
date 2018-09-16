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

	/**
  	 * @class IPSComponentSensor_Counter
	 *
	 * Definiert ein IPSComponentSensor_Counter Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
	 *
	 * Counter ist zum Beispiel ein Counter eines regensensors. Energieregister sind zwar auch Counter, sollten aber besser ueber das Modul AMIS 
	 * abgewickelt werden.
	 *
	 * Events werden im Event Handler des IPSMEssageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Temperatur,','IPSModuleSensor_Temperatur,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	
	 * wenn das Modul RemoteAccess installiert ist, wird eine Kopie des Registerwertes auf einem externen Log Server gespeichert und bei Veränderung upgedatet
	 *  Beispiel --> config eintrag von BKS-Virt
	 * aaaaa => array('OnChange','IPSComponentSensor_Counter,LBG70-2Virt:rrrrr;','IPSModuleSensor_Counter',), 
	 * bei Veränderung des Counters wird die Remote OID am Server zB LBG70-2Virt upgedatet
	 *
	 * Logging:
	 *
	 * construct erstellt im IPSLibrary.Data.Core.IPSComponent eine Kategorie für die Spiegelregister und eine Kategorie für die Nachrichten
	 * zusaetzlich auch das Verzeichnis im Log Verzeichnis wenn erforderlich
	 *
	 * es erfolgt ein Eintrag im eigenen Nachrichtenspeicher für Counter
	 *
	 * @author Wolfgang Jöbstl
	 * @version
	 *   Version 2.50.1, 09.06.2012<br/>
	 */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	class IPSComponentSensor_Counter extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		
		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_Counter Objektes
		 *
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */

		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
			//echo "IPSComponentSensor_Counter: Construct Counter Sensor with ".$var1.".\n";			

			$this->RemoteOID    = $var1;
			$this->tempObject   = $lightObject;
			$this->tempValue    = $lightValue;

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
		 * @param IPSModuleSensor $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSensor $module)
			{
			//echo "Counter Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: Counter Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			
			$log=new Counter_Logging($variable);
			$result=$log->Counter_LogValue();
			
			if ($this->RemoteOID != Null)
				{
				//print_r($this);
				//print_r($module);
				//echo "-----Hier jetzt alles programmieren was bei Veränderung passieren soll:\n";
				$params= explode(';', $this->RemoteOID);
				//print_r($params);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					//echo "Wert :".$val." Anzahl ",count($para)." \n";
					if (count($para)==2)
						{
						$Server=$this->remServer[$para[0]]["Url"];
						if ($this->remServer[$para[0]]["Status"]==true)
							{
							//echo "Server : ".$Server."\n";
							$rpc = new JSONRPC($Server);
							$roid=(integer)$para[1];
							//echo "Remote OID: ".$roid."\n";
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
			return get_class($this);
		}
	}

	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 * Counter_Auswertung und Counter_Nachrichten
	 *
	 * In der Kategorie Auswertung wird ein Spiegelregister und Bearbeitungsregister für einen kontinuierlichen Anstieg des Counterwertes angelegt
	 * in Offset_Name werden Differenzen zwischen den gelesenen Registerwerten zB bei Spannungsausfall kompensiert
	 * in Name_Counter steht der aktuelle Wert inklusive Offset
	 *
	 **************************/

	class Counter_Logging extends Logging
		{
		
		private $variable;
		private $variablename;

		private $variableLogID;					// nur die Veränderungen werden gespeichert
		private $counterLogID;					// Spiegelregister der eigentlichen Homematic Variable
		private $counter2LogID;					// Spiegelregister erweitert um Offset
		private $counterOffsetLogID;			// Offset für Spiegelregister
		
		private $CounterAuswertungID;			// Kategorie für Register
		private $CounterNachrichtenID;			// Kategorie für Nachrichten
		
		function __construct($variable,$variablename=Null)
			{
			//echo "Construct IPSComponentSensor Counter Logging for Variable ID : ".$variable."\n";
			
			/****************** Variablennamen herausfinden und/oder berechnen */
			$this->variable=$variable;
			if ($variablename==Null)
				{
				$result=IPS_GetObject($variable);
				$ParentId=(integer)$result["ParentID"];
				$object=IPS_GetObject($ParentId);
				if ( $object["ObjectType"] == 1)
					{				
					$this->variablename=IPS_GetName($ParentId);			// Variablenname ist der Parent Name wenn nicht anders angegeben, und der Parent eine Instanz ist.
					}
				else
					{
					$this->variablename=IPS_GetName($variable);			// Variablenname ist der Variablen Name wenn der Parent KEINE Instanz ist.
					}
				} 
			else
				{
				$this->variablename=$variablename;
				}			
			
			/**************** Speicherort für Nachrichten und Spiegelvarianten herausfinden */
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedmodules=$moduleManager->GetInstalledModules();

			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			//echo "Datenverzeichnis:".$CategoryIdData."\n";

			/* Create Category to store the Counter-LogNachrichten */	
			$name="Counter-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script IPSComponentSensor_Counter.");
				}
			$this->CounterNachrichtenID=$vid;

			$name="Counter-Auswertung";
			$CounterAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($CounterAuswertungID==false)
				{
				$CounterAuswertungID = IPS_CreateCategory();
				IPS_SetParent($CounterAuswertungID, $CategoryIdData);
				IPS_SetName($CounterAuswertungID, $name);
				IPS_SetInfo($CounterAuswertungID, "this category was created by script IPSComponentSensor_Counter.");
				}
			$this->CounterAuswertungID=$CounterAuswertungID;
			
			if ($variable<>null)
				{
				/* lokale Spiegelregister als Float aufsetzen */
				$this->variableLogID=CreateVariable($this->variablename,2,$CounterAuswertungID, 10, '', null );
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */

				$this->counterOffsetLogID=CreateVariable("Offset_".$this->variablename,2,$CounterAuswertungID, 100, '', null, null );   // Float Variable anlegen
				$this->counterLogID=CreateVariable($this->variablename."_Counter",2,$CounterAuswertungID, 10, '', null, null );   // Float Variable anlegen
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->counterLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->counterLogID,0);      /* normaler Wwert */

				$this->counter2LogID=CreateVariable($this->variablename."_Counter2",2,$CounterAuswertungID, 20, '', null, null );   // Float Variable anlegen
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->counter2LogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->counter2LogID,0);      /* normaler Wwert */

				IPS_ApplyChanges($archiveHandlerID);
				}

			/* Filenamen für die Log Eintraege herausfunfen und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["CounterLog"]))
		   		 { $directory=$directories["LogDirectories"]["CounterLog"]; }
			else {$directory="C:/Scripts/Counter/"; }	
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_Counter.csv";
			parent::__construct($filename,$vid);
			}

		function Counter_LogValue()
			{
			// result formatieren für Ausgabe in den LogNachrichten
			$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{
				$result=GetValueFormatted($this->variable);
				}
			else
				{
				$result=number_format(GetValue($this->variable),2,',','.');
				}		

			$unchanged=time()-$variabletyp["VariableChanged"];

			$diff=GetValue($this->variable)-GetValue($this->counterLogID);
			if ($diff != 0)
				{
				if ($diff>0)
					{
					SetValue($this->variableLogID,GetValue($this->variable)-GetValue($this->counterLogID));
					SetValue($this->counterLogID,GetValue($this->variable));
					SetValue($this->counterLogID,GetValue($this->variable)+GetValue($this->counterOffsetLogID));
					echo "Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." Änderung auf letzten Wert ".GetValue($this->counterLogID);
					}
				else
					{
					SetValue($this->counterOffsetLogID,GetValue($this->counterOffsetLogID)-$diff);
					}						

			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedmodules=$moduleManager->GetInstalledModules();

				parent::LogMessage($result);
				parent::LogNachrichten($this->variablename." mit Wert ".$result);
				}
				
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