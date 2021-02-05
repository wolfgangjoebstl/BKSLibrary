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
	 * 	xxxx => array('OnChange','IPSComponentSensor_Remote,1,2,3','IPSModuleSensor_Remote,1,2,3',),
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
	 * Automatische Registrierung für:
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


		private $RemoteOID;
		private $tempObject;
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
		public function __construct($instanceId=null, $remoteOID=null, $tempValue=null)
			{
			//echo "IPSComponentSensor_Remote: Construct Sensor with ($instanceId,$remoteOID,$tempValue). --> (".IPS_GetName($instanceId).")\n";	
            //$this->RemoteOID    = instanceID;                // par1 manchmal auch par2		
			$this->RemoteOID    = $remoteOID;           // par2 manchmal auch par1
			$this->tempValue    = $tempValue;           // par3

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
			//echo "HandleEvent, Sensor Remote Message Handler für VariableID : ".$variable." mit Wert : ".$value."   (".IPS_GetName($variable)."/".IPS_GetName(IPS_GetParent($variable)).") \"".$this->tempValue."\"\n";
            //$startexec=microtime(true);    
            $log=new Sensor_Logging($variable,null,$this->tempValue);        // es wird kein Variablenname übergeben, aber der Typ wenn er mitkommt, mirrorNameID wird berechnet
            $mirrorValue=$log->updateMirorVariableValue($value);
    	    //IPSLogger_Not(__file__,"IPSComponentSensor_Remote:HandleEvent mit VariableID $variable (".IPS_GetName($variable)."/".IPS_GetName(IPS_GetParent($variable)).") mit neuem Wert $value und altem Wert $mirrorValue (".$log->getMirorNameID().") bzw. ".GetValue($variable).".");
            if ( ($value != $mirrorValue)  || (GetValue($variable) != $value) )     // gleiche Werte unterdrücken, dazu Spiegelvariable verwenden.
                {
    			//IPSLogger_Inf(__file__, 'IPSComponentSensor_Remote HandleEvent: Sensor Remote Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			    echo "IPSComponentSensor_Remote:HandleEvent Wert != Mirror, VariableID $variable (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value."   \"".$this->tempValue."\"\n";
    			$result=$log->Sensor_LogValue($value);      
                $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID );
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
        protected $debug;

		protected $installedmodules;              /* installierte Module */

		protected $variable, $variableTypeReg;              /* variableType für Untergruppen */
        protected $variableProfile, $variableType;        // Eigenschaften der input Variable auf die anderen Register clonen        

		public $variableLogID;			/* ID der entsprechenden lokalen Spiegelvariable */

		//protected $configuration, $variablename,$CategoryIdData;           // in der parent class definiert
		//protected $mirrorCatID, $mirrorNameID;                            // in der parent class definiert, Spiegelregister in CustomComponent um eine Änderung zu erkennen
		//protected $AuswertungID, $NachrichtenID, $filename;             // in der parent class definiert, Auswertung für Custom Component 
        //protected $DetectHandler,$archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */           



        /* construct wird bereit mit der zu loggenden Variable ID aufgerufen, 
         * optional kann ein Variablennamen mitgegeben werden, sonst wird er nach einem einfachen Algorithmus berechnet (Instanz oder Variablenname der ID)
         * oder aus der Config von DetectMovement übernommen
         *
         */

		function __construct($variable,$variablename=null,$variableTypeReg="unknown",$debug=false)
			{
            $this->startexecute=microtime(true);   
            $this->debug=$debug;              
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
            $this->configuration=$this->set_IPSComponentLoggerConfig();             /* configuration verifizieren und vervollstaendigen, muss vorher erfolgen */

			echo "   Construct IPSComponentSensor Remote Logging for Variable ID : ($variable,$variablename,$variableTypeReg).\n";

            $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
            if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
            $this->variableType=IPS_GetVariable($variable)["VariableType"];

            $rows=getfromDatabase("COID",$variable);
            if ( ($rows === false) || (sizeof($rows) != 1) )
                {
                $this->variableTypeReg = $variableTypeReg;            // nicht aus der Datenbank, vielleicht in der Config als dritter Parameter
                echo "Variable Type from Script is : ".$this->variableTypeReg."\n";
                }
            else    // getfromDatabase
                {
                //print_r($rows);   
                $this->variableTypeReg = $rows[0]["TypeRegKey"];    
                echo "Variable Type from mySQL Database is : ".$this->variableTypeReg."\n";
                }
			if ($this->debug) echo "Construct IPSComponentSensor:Sensor_Logging for Variable ID : ".$variable." \"".$this->variableProfile."\" ".$this->variableType." ".$this->variableTypeReg."\n";

            switch ($this->variableTypeReg) 
                {
                case "CO2":
                case "BAROPRESSURE":
                    $NachrichtenID = $this->do_init_climate($variable, $variablename);
                    break;
                default:
                    $NachrichtenID = $this->do_init_sensor($variable, $variablename);
                    break;
                }
			parent::__construct($this->filename,$NachrichtenID);                                 // Adresse Nachrichten Kategorie wird selbst ermittelt
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

        public function getMirorNameID()
            {
            return ($this->mirrorNameID);
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
         * die Type des Sensorwertes ist egal
         */

		function Sensor_LogValue($value,$debug=false)
			{
            echo "Sensor_Logging::Sensor_LogValue mit $value aufgerufen. ".$this->variableLogID."   (".IPS_GetName($this->variableLogID)."/".IPS_GetName(IPS_GetParent($this->variableLogID)).") = ".GetValue($this->variable)."\n";    
			// result formatieren für Ausgabe in den LogNachrichten, dieser Component wird für verschiedene Datenobjekte verwendet, keine extra Formattierungen hier
			$variabletyp=IPS_GetVariable($this->variable);
    		$result=GetValueIfFormatted($this->variable);
	    	$unchanged=time()-$variabletyp["VariableChanged"];
			$oldvalue=GetValue($this->variableLogID);
		
        	SetValue($this->variableLogID,GetValue($this->variable));
			echo "      Sensor_LogValue: Neuer Wert fuer ".$this->variablename." ist ".GetValueIfFormatted($this->variable).". Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
			if ($this->CheckDebugInstance($this->variable)) IPSLogger_Inf(__file__, 'CustomComponent Sensor_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename.'  TypeReg : '.$this->variableTypeReg);

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
			echo "   Aktuelle Laufzeit nach File Logging in ".$this->variablename." mit Wert ".$result." : ".exectime($this->startexecute)." Sekunden.\n";
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