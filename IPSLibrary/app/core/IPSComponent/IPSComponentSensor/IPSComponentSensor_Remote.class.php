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
     * @class IPSComponentSensor_remote
     *
     * Definiert ein IPSComponentSensor_Remote Object, das ein IPSComponentSensor Object für einen beliebigen Sensor implementiert.
     *
	 * Events werden im Event Handler des IPSMessageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID udn Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_TRemote,','IPSModuleSensor_Remote,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 *
	 * allgemeines Handling, macht kein lokales Logging und keine weitere Verarbeitung
	 *
	 * Wenn RemoteAccess installiert ist:
	 * der erste Zusatzparameter aus der obigen Konfig sind Pärchen von Remoteserver und remoteOIDs
	 * in der RemoteAccessServerTable sind alle erreichbaren Log Remote Server aufgelistet, abgeleitet aus der Server Config und dem Status der Erreichbarkeit
	 * für alle erreichbaren Server wird auch die remote OID mit dem Wert beschrieben 
	 *
	 * Authomatische Registrierung für:
	 *   Energiewerte
	 *   Mobilfunkguthaben
	 *   Taster und Schalter wenn Geber nicht Actuator
	 *	 
     * @author Wolfgang Jöbstl
     * @version
     *   Version 2.50.1, 09.06.2012<br/>
     ****/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

    /* für die Behandlung von MySQL */
	IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');    
	
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
    
	
			
	class IPSComponentSensor_Remote extends IPSComponentSensor {

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
		 * legt die Remote Server aus $var1 an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 *		 
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
			//echo "IPSComponentSensor_Remote: Construct Remote Sensor with ".$var1.".\n";				
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;                // par1 manchmal auch par2
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
			echo "Genereller Sensor Remote Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Inf(__file__, 'IPSComponentSensor_Remote HandleEvent: Sensor Remote Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			

            $log=new Sensor_Logging($variable);        // es wird kein Variablenname übergeben
            $mirrorValue=$log->updateMirorVariableValue($value);
			$result=$log->Sensor_LogValue($value);      // hier könnte man gleiche Werte noch unterdrücken

            $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID );
            /*
			if ($this->RemoteOID != Null)
			   {
				//print_r($this);
				//print_r($module);
				//echo "-----Hier jetzt alles programmieren was bei Veränderung passieren soll:\n";
				$params= explode(';', $this->RemoteOID);
				print_r($params);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					echo "Wert :".$val." Anzahl ",count($para)." \n";
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
				}  */

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

        /* return Logging class, shall be stored */

		public function GetComponentLogger() 
			{
            return "Sensor_Logging";
            }



	}



	/********************************* 
	 *
	 * Klasse schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 **************************/

	class Sensor_Logging extends Logging
		{
		private $variable, $variablename, $variableTypeReg;              /* variableType für Untergruppen */
        private $variableProfile, $variableType;        // Eigenschaften der input Variable auf die anderen Register clonen        
		private $mirrorCatID, $mirrorNameID;            // Spiegelregister in CustomComponent um eine Änderung zu erkennen

		private $AuswertungID, $NachrichtenID, $filename;             /* Auswertung für Custom Component */

		private $configuration;
		private $CategoryIdData;          

		public $variableLogID;			/* ID der entsprechenden lokalen Spiegelvariable */

        private $startexecute;                  /* interne Zeitmessung */

		/* Unter Klassen */
		
		protected $installedmodules;              /* installierte Module */
        protected $DetectHandler;		        /* Unterklasse */
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */           

        /* construct wird bereit mit der zu loggenden Variable ID aufgerufen, 
         * optional kann ein Variablennamen mitgegeben werden, sonst wird er nach einem einfachen Algorithmus berechnet (Instanz oder Variablenname der ID)
         * oder aus der Config von DetectMovement übernommen
         *
         */

		function __construct($variable,$variablename=Null)
			{
            $this->startexecute=microtime(true);   
			echo "   Construct IPSComponentSensor Remote Logging for Variable ID : ".$variable."\n";

            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 

            $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
            if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
            $this->variableType=IPS_GetVariable($variable)["VariableType"];

            $rows=getfromDatabase("COID",$variable);
            if ( ($rows === false) || (sizeof($rows) != 1) )
                {
                $this->variableTypeReg = "unknown";            // 
                }
            else    // getfromDatabase
                {
                //print_r($rows);   
                $this->variableTypeReg = $rows[0]["TypeRegKey"];    
                }

			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();

			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Detect Movement kann auch Sensorwerte agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$this->DetectHandler = new DetectSensorHandler();                            // zum Beispiel für die Evaluierung der Mirror Register
                }

            $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

			/**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
            $name="SensorMirror_".$this->variablename;
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float */
			echo "    Sensor_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).")\n";
			
			/* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
			$this->NachrichtenID=$this->CreateCategoryNachrichten("Sensor",$this->CategoryIdData);
			$this->AuswertungID=$this->CreateCategoryAuswertung("Sensor",$this->CategoryIdData);

    		/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
                $this->variable=$variable;                     
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->variableType,$this->variableProfile);                   // $this->variableLogID schreiben
				//echo "      Lokales Spiegelregister \"".$this->variablename."\" (".$this->variableLogID.") mit Typ Float unter Kategorie ".$this->AuswertungID." ".IPS_GetName($this->AuswertungID)." anlegen.\n";
				}

			/* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["SensorLog"]))
		   		 { $directory=$directories["LogDirectories"]["SensorLog"]; }
			else {$directory="C:/Scripts/Sensor/"; }	
            $dosOps= new dosOps(); 
			$dosOps->mkdirtree($directory);
			$this->filename=$directory.$this->variablename."_Sensor.csv";
			parent::__construct($this->filename,$this->NachrichtenID);                                 // Adresse Nachrichten Kategorie wird selbst ermittelt
			}


        public function getVariableNameLogging()   
            {
            return $this->variablename;      
            }

        public function getConfigurationLogging()
            {
            return $this->configuration;      
            }

        public function getVariableOIDLogging()
            {
            $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "mirrorID" => $this->mirrorNameID, "variableLogID" => $this->variableLogID];

            return $result;
            }


        /* Spiegelregister updaten */

        function updateMirorVariableValue($value)
            {
            $oldvalue=GetValue($this->mirrorNameID);
            SetValue($this->mirrorNameID,$value);
            return($oldvalue);
            }

        /* wird von HandleEvent aus obigem CustomComponent aufgerufen.
         * Speichert den Wert von ID $this->variable im Spiegelregister mit ID $this->variableLogID
         *
         */

		function Sensor_LogValue()
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
			echo "      Sensor_LogValue: Neuer Wert fuer ".$this->variablename." ist ".GetValueIfFormatted($this->variable).". Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
			IPSLogger_Inf(__file__, 'CustomComponent Sensor_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename.'  TypeReg : '.$this->variableTypeReg);

			/*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
                echo "     DetectMovement ist installiert. Aggregation abarbeiten:\n";
				$groups=$this->DetectHandler->ListGroups("Sensor",$this->variable);      // nur die Gruppen für dieses Event updaten
				foreach($groups as $group=>$name)
					{
					echo "      --> Gruppe ".$group." behandeln.\n";
					$config=$this->DetectHandler->ListEvents($group);
					$status=(float)0;
					$count=0;
					foreach ($config as $oid=>$params)
						{
						$status+=GetValue($oid);
						$count++;
						//echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
						echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)),50)."Status: ".GetValue($oid)." ".$status."\n";
						}
					if ($count>0) { $status=round($status/$count,1); }
					//echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
					/* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
					$statusID=CreateVariableByName($this->AuswertungID,"Gesamtauswertung_".$group,2, "~Temperature", null, 1000, null);
                    $oldstatus=GetValue($statusID);
					if ($oldstatus != $status) 
                        {
    					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $status.\n";
                        SetValue($statusID,$status);     // Vermeidung von Update oder Change Events
                        }
			   		}
				}
			//echo "Aktuelle Laufzeit nach Aggregation ".exectime($this->startexecute)." Sekunden.\n";
			
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			echo "Aktuelle Laufzeit nach File Logging in ".$this->variablename." mit Wert ".$result." : ".exectime($this->startexecute)." Sekunden.\n";
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