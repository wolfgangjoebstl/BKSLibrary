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
	 
    /**
     * @class IPSComponentSensor_Feuchtigkeit
     *
     * Definiert ein IPSComponentSensor_Feuchtigkeit Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
     *
	 * Events werden im Event Handler des IPSMEssageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Feuchtigkeit,','IPSModuleSensor_Feuchtigkeit,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
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
	 * auch den Mittelwert aus mehreren Variablen für Gruppen herausrechnen

     * @class IPSComponentSensor_Feuchtigkeit
     *
     * Definiert ein IPSComponentSensor_Feuchtigkeit Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
     *
     * @author Wolfgang Jöbstl
     * @version
     *   Version 2.50.1, 09.06.2012<br/>
     */

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');


	class IPSComponentSensor_Feuchtigkeit extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		private $remServer;
		
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSensor_Feuchtigkeit Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
		   //echo "Build Humidity Sensor with ".$var1.".\n";						
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
			echo "IPSComponentSensor_Feuchtigkeit:HandleEvent, Feuchtigkeit Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
            $startexec=microtime(true);
            $log=new Feuchtigkeit_Logging($variable);        // es wird kein Variablenname übergeben
            $mirrorValue=$log->updateMirorVariableValue($value);
            if ( ($value != $mirrorValue)  || (GetValue($variable) != $value) )     // nur durch Vergleich GetValue kann es nicht festgestellt werden, da der Wert in value bereits die Änderung auslöst. Dazu Spiegelvariable verwenden
                {            
                IPSLogger_Dbg(__file__, 'HandleEvent: Feuchtigkeit Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);
			    echo "  IPSComponentSensor_Feuchtigkeit:HandleEvent mit VariableID $variable (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value."\n";
                            
                echo "Aktuelle Laufzeit nach construct Logging ".exectime($startexec)." Sekunden.\n"; 
                $result=$log->Feuchtigkeit_LogValue();
			    $this->SetValueROID($value);
                }
            else 
                {
                IPSLogger_Dbg(__file__, 'HandleEvent: Unchanged -> Feuchtigkeit Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			    echo "  IPSComponentSensor_Feuchtigkeit:HandleEvent: Unchanged -> für VariableID $variable (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value."\n";
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
		public function GetComponentParams() 
            {
			return get_class($this);
		    }

        /* return Logging class, shall be stored */

		public function GetComponentLogger() 
			{
            return "Feuchtigkeit_Logging";
            }

        /*
         * Wert auf die konfigurierten remoteServer laden
         */

        public function SetValueROID($value)
            {
			//print_r($this->RemoteOID);
			//print_r($this->remServer);
			
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
							//echo "Server : ".$Server." Name ".$para[0]." Remote OID: ".$roid."\n";
							$rpc->SetValue($roid, $value);
							}
						}
					}
				}
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

	class Feuchtigkeit_Logging extends Logging
		{
		protected $variable;
        protected $variableProfile, $variableType;        // Eigenschaften der input Variable auf die anderen Register clonen

		protected $HumidityAuswertungID, $HumidityNachrichtenID;

		// $configuration, $variablename, $CategoryIdData       wird in parent class als protected geführt

		public $variableLogID;		/* ID der entsprechenden lokalen Spiegelvariable */

		/* Unter Klassen */
		
		protected $installedmodules;              /* installierte Module */
        protected $DetectHandler;		        /* Unterklasse */
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */           

        /* construct wird bereit mit der zu loggenden Variable ID aufgerufen, 
         * optional kann ein Variablennamen mitgegeben werden, sonst wird der
         * Variablennamen nach einem einfachen Algorithmus berechnet (Instanz oder Variablenname der ID)
         * oder aus der Config von DetectMovement übernommen
         *
         */

		function __construct($variable,$variablename=Null,$variableTypeReg="unknown",$debug=false)
			{
            if ( ($this->GetDebugInstance()) && ($this->GetDebugInstance()==$variable) ) $this->debug=true;
            else $this->debug=$debug;
            if ($this->debug) echo "   Feuchtigkeit_Logging, construct : ($variable,$variablename,$variableTypeReg).\n";

            $this->constructFirst();        // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()

            /* bereits in constructFirst
            $this->startexecute=microtime(true);                   
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
			//**************** installierte Module und verfügbare Konfigurationen herausfinden 
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			//echo "  Kategorien im Datenverzeichnis:".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);                
            */

            $this->configuration=$this->set_IPSComponentLoggerConfig();             /* configuration verifizieren und vervollstaendigen, muss vorher erfolgen */

            if (IPS_GetVariable($variable)["VariableType"]==2) $variableTypeReg = "TEMPERATURE";            // kann STATE auch sein, tut aber nichts zur Sache
            else $variableTypeReg = "HUMIDITY";
            $NachrichtenID=$this->do_init($variable,$variablename,null, $variableTypeReg, $this->debug);              // $typedev ist $variableTypeReg, $value wird normalerweise auch übergeben, $variable kann auch false sein

            /* abgelöst durch do_init und do_init_humidity 
            $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
            if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
            $this->variableType=IPS_GetVariable($variable)["VariableType"];

            $rows=getfromDatabase("COID",$variable);
            if ( ($rows === false) || (sizeof($rows) != 1) )
                {
                $this->variableTypeReg=$variableTypeReg;
                }
            else    // getfromDatabase
                {
                print_r($rows);   
                $this->variableTypeReg = $rows[0]["TypeRegKey"];    
                }
			//***************** Variablennamen für Spiegelregister von DetectMovement übernehmen oder selbst berechnen 
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				// Detect Movement kann auch Temperaturen agreggieren 
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$this->DetectHandler = new DetectHumidityHandler();
                }
            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovemet Config oder selber bestimmen



            $dosOps = new dosOps();
		
            $name="HumidityMirror_".$this->variablename;
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       // 2 float ~Temperature

            // Create Category to store the Feuchtigkeit-LogNachrichten 
			$name="Feuchtigkeit-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $this->CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script. ");
				}
			$this->HumidityNachrichtenID=$vid;

			// Create Category to store the Temperature-Spiegelregister 	
			$name="Feuchtigkeit-Auswertung";
			$TempAuswertungID=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($TempAuswertungID==false)
				{
				$TempAuswertungID = IPS_CreateCategory();
				IPS_SetParent($TempAuswertungID, $this->CategoryIdData);
				IPS_SetName($TempAuswertungID, $name);
				IPS_SetInfo($TempAuswertungID, "this category was created by script. ");
				}
			$this->HumidityAuswertungID=$TempAuswertungID;

    		// lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 
			if ($variable<>null)
				{
                $this->variable=$variable; 
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->HumidityAuswertungID,1,"~Humidity");                   // $this->variableLogID schreiben
                IPS_SetHidden($this->variableLogID,false);                
				echo "      Lokales Spiegelregister \"".$this->variablename."\" (".$this->variableLogID.") mit Typ Integer unter Kategorie ".$this->HumidityAuswertungID." ".IPS_GetName($this->HumidityAuswertungID)." anlegen.\n";
				}

            print_r($this->getVariableOIDLogging());    // Debug, Übersicht der angelegten Variablen 

			// Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden 
			//echo "Uebergeordnete Variable : ".$this->variablename."\n";   

		    $directory = $this->configuration["LogDirectories"]["HumidityLog"];
		    $dosOps->mkdirtree($directory);
		    $filename=$directory.$this->variablename."_Feuchtigkeit.csv";   */

            if ($this->debug) echo "    ermittelt wurden  Variablename \"".$this->variablename."\" MirrorNameID ".$this->mirrorNameID." (".IPS_GetName($this->mirrorNameID).") und Log Filename \"".$this->filename."\" mit NachrichtenID  ".$NachrichtenID." (".IPS_GetName($NachrichtenID)."/".IPS_GetName(IPS_GetParent($NachrichtenID)).")\n";
    	    parent::__construct($this->filename,$NachrichtenID);
	   	    }


        /* do_setVariableLogID, nutzt setVariableLogId aus der Logging class 
        * kannnicht diesselbe class sein, da this verwendet wird
        */

        private function do_setVariableLogID($variable)
            {
            if ($variable<>Null)
                {
                $this->variable=$variable;
                //echo "Aufruf setVariableLogId(".$this->variable.",".$this->variablename.",".$this->MoveAuswertungID.")\n";
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->variableType,$this->variableProfile);                   // $this->variableLogID schreiben
				//echo "      Lokales Spiegelregister \"".$this->variablename."\" (".$this->variableLogID.") mit Typ Integer unter Kategorie ".$this->AuswertungID." ".IPS_GetName($this->AuswertungID)." anlegen.\n";
                IPS_SetHidden($this->variableLogID,false);
                }
            }


        /*** get protectet variables
         *
         */

		public function GetComponent() {
			return ($this);
			}

		public function GetDetectHandler() {
			return ($this->DetectHandler);
			}

        public function getVariableNameLogging()   
            {
            return $this->variablename;      
            }

        public function getConfigurationLogging()
            {
            return $this->configuration;      
            }

        /* Detect Movement unterstützt Aggregationen, die werden zur Runtime installiert, keine AUsgabe hier, nur Minimalfunktion */

        public function getVariableOIDLogging()
            {
            $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "mirrorID" => $this->mirrorNameID, "variableLogID" => $this->variableLogID, "variablename" => $this->variablename,];
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

		function Feuchtigkeit_LogValue()
			{
			// result formatieren für Ausgabe in den LogNachrichten
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
			echo "      Feuchtigkeit_LogValue: Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." %. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
			IPSLogger_Dbg(__file__, 'CustomComponent Feuchtigkeit_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename);
                
			/*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				$groups=$this->DetectHandler->ListGroups("Feuchtigkeit",$this->variable);      // nur die Gruppen für dieses Event updaten, mit Angabe Feuchtigkeit können auch mehrere Gruppen pro Event ausgegeben werden 
                //print_r($groups);
				foreach($groups as $group=>$name)
				    {
				    echo "       --> Gruppe ".$group." behandeln.\n";
					$config=$this->DetectHandler->ListEvents($group);
                    //print_r($config);
					$status=(float)0;
					$count=0;
					foreach ($config as $oid=>$params)
						{
						$status+=GetValue($oid);
						$count++;
						//echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
						echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)),50)."Status: ".GetValue($oid)." ".$status."\n";
						}
					if ($count>0) { $status=$status/$count; }
                    else echo "Gruppe ".$group." hat keine eigenen Eintraege.\n";
					$statusint=(integer)$status;
				    echo "Gruppe ".$group." hat neuen Status : ".$statusint."\n";

					//$log=new Feuchtigkeit_Logging($oid);
					//$class=$log->GetComponent($oid);
					// Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann 
					$statusID=CreateVariableByName($this->AuswertungID, "Gesamtauswertung_".$group,1,'~Humidity',null,1000,null);
                    $oldstatus=GetValue($statusID);
					if ($oldstatus != $statusint) 
                        {
    					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $statusint.\n";
                        SetValue($statusID,$statusint);     // Vermeidung von Update oder Change Events
                        }
			   	    }
				}  

			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
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