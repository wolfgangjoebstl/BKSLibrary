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
    * @class IPSComponentSensor_Temperatur
    *
    * Definiert ein IPSComponentSensor_Temperatur Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
    *
	 * Events werden im Event Handler des IPSMEssageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Temperatur,','IPSModuleSensor_Temperatur,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 * bei Homematic, FS20, FHT80b wird einfach direkt der Temperaturwert registriert. Der Name wird wenn notwendig aus dem Parent abgeleitet.
	 *
	 * Wenn RemoteAccess installiert ist:
	 * der erste Zusatzparameter aus der obigen Konfig sind Pärchen von Remoteserver und remoteOIDs
	 * in der RemoteAccessServerTable sind alle erreichbaren Log Remote Server aufgelistet, abgeleitet aus der Server Config und dem Status der Erreichbarkeit
	 * für alle erreichbaren Server wird auch die remote OID mit dem Wert beschrieben 
	 *
	 * Logging der Variablen:
	 * Alle Wertänderungen werden in einem File und einem Nachrichtenspeicher gelogged.
	 *
	 * wenn DetectMovement installiert ist:
	 * auch den Mittelwert aus mehreren Variablen herausrechnen
	 *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	class IPSComponentSensor_Temperatur extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;

		private $remServer;
				
		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * legt die Remote Server  aus $var1 an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
			//echo "IPSComponentSensor_Temperatur: Construct Temperature Sensor with ".$var1.".\n";			
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
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
			//echo "Temperatur Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: Temperature Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			
			$log=new Temperature_Logging($variable);
			$result=$log->Temperature_LogValue();
			
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
			return get_class($this);
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

	class Temperature_Logging extends Logging
		{
		private $variable;
		private $variablename;
		public $variableLogID;			/* ID der entsprechenden lokalen Spiegelvariable */
		
		private $TempAuswertungID;
		private $TempNachrichtenID;

		private $configuration;
		private $installedmodules;
				
		function __construct($variable,$variablename=Null)
			{
			//echo "Construct IPSComponentSensor Temperature Logging for Variable ID : ".$variable."\n";
			
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
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
			
			/* Create Category to store the Temperature-LogNachrichten */	
			$name="Temperatur-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script IPSComponentSensor_Temperatur. ");
				}
			$this->TempNachrichtenID=$vid;

			/* Create Category to store the Temperature-Spiegelregister */	
			$name="Temperatur-Auswertung";
			$TempAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($TempAuswertungID==false)
				{
				$TempAuswertungID = IPS_CreateCategory();
				IPS_SetParent($TempAuswertungID, $CategoryIdData);
				IPS_SetName($TempAuswertungID, $name);
				IPS_SetInfo($TempAuswertungID, "this category was created by script IPSComponentSensor_Temperatur. ");
				}
			$this->TempAuswertungID=$TempAuswertungID;

			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
				echo "Lokales Spiegelregister als Float auf ".$this->variablename." unter Kategorie ".$this->TempAuswertungID." ".IPS_GetName($this->TempAuswertungID)." anlegen.\n";
				/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
				$this->variableLogID=CreateVariable($this->variablename,2,$this->TempAuswertungID, 10, "~Temperature", null, null );  /* 2 steht für Float, alle benötigten Angaben machen, sonst Fehler */
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}

			/* Filenamen für die Log Eintraege herausfunfen und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["TemperatureLog"]))
		   		 { $directory=$directories["LogDirectories"]["TemperatureLog"]; }
			else {$directory="C:/Scripts/Temperature/"; }	
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_Temperature.csv";
			parent::__construct($filename,$vid);
			}

		function Temperature_LogValue()
			{
			// result formatieren für Ausgabe in den LogNachrichten
			$variabletyp=IPS_GetVariable($this->variable);
			if ($variabletyp["VariableProfile"]!="")
				{
				$result=GetValueFormatted($this->variable);
				}
			else
				{
				$result=number_format(GetValue($this->variable),2,',','.')." °C";
				}		

			$unchanged=time()-$variabletyp["VariableChanged"];
			$oldvalue=GetValue($this->variableLogID);
			SetValue($this->variableLogID,GetValue($this->variable));
			echo "Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." °C. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
			IPSLogger_Dbg(__file__, 'CustomComponent Tempoerature_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename);
			
			/*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
			if (isset ($this->installedmodules["DetectMovement"]))
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