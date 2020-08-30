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

	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

	class IPSComponentSensor_Temperatur extends IPSComponentSensor {

		private $tempObject;

		private $RemoteOID, $tempValue;         // Übergabewerte
		private $installedmodules;
        private $log;                       // log class
		private $remServer;
				
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSensor_Temperatur Objektes
		 *
		 * legt die Remote Server  aus $var1 an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($instanceId=Null, $remoteOID=null, $tempValue=null)
			{
			//echo "IPSComponentSensor_Temperatur: Construct Temperature Sensor with ".$instanceId.".\n";			
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
			//echo "Temperatur Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			/* aussuchen ob IPSLogger_Dbg oder IPSLogger_Inf der richtige Level für die Analyse, produziert viele Daten ! */
            $startexec=microtime(true);            
            $log=new Temperature_Logging($variable);        // es wird kein Variablenname übergeben
            $mirrorValue=$log->updateMirorVariableValue($value);
            if ( ($value != $mirrorValue)  || (GetValue($variable) != $value) )     // kann so nicht festgetsellt werden, da der Wert in value bereits die Änderung auslöst. Dazu Spiegelvariable verwenden.
                {
			    IPSLogger_Inf(__file__, 'IPSComponentSensor_Temperatur:HandleEvent mit VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			    echo "  IPSComponentSensor_Temperatur:HandleEvent mit VariableID $variable (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value."\n";
                //echo "Aktuelle Laufzeit nach construct Logging ".exectime($startexec)." Sekunden.\n"; 
                $result=$log->Temperature_LogValue();
                //echo "Aktuelle Laufzeit nach Logging ".exectime($startexec)." Sekunden.\n"; 
   			    $this->SetValueROID($value);
                //echo "Aktuelle Laufzeit nach Remote Server Update ".exectime($startexec)." Sekunden.\n"; 
                }
            else 
                {
                IPSLogger_Inf(__file__, 'IPSComponentSensor_Temperatur:HandleEvent: Unchanged -> Temperature Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			    echo "  IPSComponentSensor_Temperatur:HandleEvent: Unchanged -> für VariableID $variable (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value."\n";
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
            return "Temperature_Logging";
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
                            $rpc = quickServerPing($Server);
							//$rpc = new JSONRPC($Server);
                            if ($rpc !== false)
                                {
                                $roid=(integer)$para[1];
                                //echo "Server : ".$Server." Name ".$para[0]." Remote OID: ".$roid."\n";
                                $rpc->SetValue($roid, $value);
                                }
                            else IPSLogger_Inf(__file__, "SetValueROID: Server $Server offline");			
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

	class Temperature_Logging extends Logging
		{
		private $variable, $variablename, $variableTypeReg;
        private $variableProfile, $variableType;        // Eigenschaften der input Variable auf die anderen Register clonen
		private $mirrorCatID, $mirrorNameID;            // Spiegelregister in CustomComponent um eine Änderung zu erkennen

		private $AuswertungID, $NachrichtenID, $filename;

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
			//echo "Construct IPSComponentSensor Temperature Logging for Variable ID : ".$variable."\n";
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 

            $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
            if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
            $this->variableType=IPS_GetVariable($variable)["VariableType"];

            $rows=getfromDatabase("COID",$variable);
            if ( ($rows === false) || (sizeof($rows) != 1) )
                {
                if (IPS_GetVariable($variable)["VariableType"]==2) $this->variableTypeReg = "TEMPERATURE";            // kann STATE auch sein, tut aber nichts zur Sache
                else $this->variableTypeReg = "HUMIDITY";
                }
            else    // getfromDatabase
                {
                //print_r($rows);   
                $this->variableTypeReg = $rows[0]["TypeRegKey"];    
                }

            if ($this->variableTypeReg == "TEMPERATURE")  $this->do_init_temperature($variable, $this->variablename);
            elseif ($this->variableTypeReg == "HUMIDITY") $this->do_init_humidity($variable, $this->variablename);
            else IPSLogger_Err(__file__, 'IPSComponentSensor_Temperatur:Logging mit VariableID '.$variable.' Variablename '.$this->variablename.' kennt folgenden TypeReg nicht '.$this->variableTypeReg); 

		    //IPSLogger_Inf(__file__, 'IPSComponentSensor_Temperatur:Construct Logging mit VariableID '.$variable.' Variablename "'.$this->variablename.'" MirrorNameID "'.$this->mirrorNameID.' "und TypeReg "'.$this->variableTypeReg.'"');			
			parent::__construct($this->filename,$this->NachrichtenID);                                 // Adresse Nachrichten Kategorie wird selbst ermittelt
			}

        /* Initialisierung für Temperature */

        private function do_init_temperature($variable, $variablename)
            {
			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();

			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Detect Movement kann auch Temperaturen agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$this->DetectHandler = new DetectTemperatureHandler();
                }

            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovemet Config oder selber bestimmen

			/**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);

            $name="TemperatureMirror_".$this->variablename;
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float ~Temperature*/
			//echo "    Temperatur_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
			
			/* Create Category to store the LogNachrichten und Spiegelregister*/	
			$this->NachrichtenID=$this->CreateCategoryNachrichten("Temperatur",$this->CategoryIdData);
			$this->AuswertungID=$this->CreateCategoryAuswertung("Temperatur",$this->CategoryIdData);
            $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

			/* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
			//echo "Uebergeordnete Variable : ".$variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["TemperatureLog"]))
		   		 { $directory=$directories["LogDirectories"]["TemperatureLog"]; }
			else {$directory="C:/Scripts/Temperature/"; }	
            $dosOps= new dosOps();              
			$dosOps->mkdirtree($directory);
			$this->filename=$directory.$variablename."_Temperature.csv";
            }    


        /* Initialisierung für Feuchtigkeit */

        private function do_init_humidity($variable, $variablename)
            {
			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();

			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Detect Movement kann auch Feuchtigkeiten agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$this->DetectHandler = new DetectHumidityHandler();
                }

            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

			/**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);                
            $name="HumidityMirror_".$this->variablename;
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float ~Temperature*/

			/* Create Category to store the LogNachrichten und Spiegelregister*/	
			$this->NachrichtenID=$this->CreateCategoryNachrichten("Feuchtigkeit",$this->CategoryIdData);
			$this->AuswertungID=$this->CreateCategoryAuswertung("Feuchtigkeit",$this->CategoryIdData);
            $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

			/* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
			//echo "Uebergeordnete Variable : ".$variablename."\n";
		    $directories=get_IPSComponentLoggerConfig();
		    $directory=$directories["LogDirectories"]["HumidityLog"];
            $dosOps= new dosOps();              
		    $dosOps->mkdirtree($directory);
		    $this->filename=$directory.$variablename."_Feuchtigkeit.csv";
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

		function Temperature_LogValue()
			{
			// result formatieren für Ausgabe in den LogNachrichten
			$variabletyp=IPS_GetVariable($this->variable);
            if ($this->variableTypeReg =="TEMPERATURE")
                {
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
                echo "      Temperature_LogValue: Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." °C. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";
                //IPSLogger_Inf(__file__, 'CustomComponent Temperature_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename);

                /*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
                $this->do_gesamtauswertung("Temperatur");
                //echo "Aktuelle Laufzeit nach Aggregation ".exectime($this->startexecute)." Sekunden.\n";
                }
            if ($this->variableTypeReg =="HUMIDITY")
                {
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
                //IPSLogger_Inf(__file__, 'CustomComponent Feuchtigkeit_LogValue: Variable OID : '.$this->variable.' Name : '.$this->variablename);
                    
                /*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
                $this->do_gesamtauswertung("Feuchtigkeit");

                /* if (isset ($this->installedmodules["DetectMovement"]))
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
                    }  */
                }
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			echo "Aktuelle Laufzeit nach File Logging in ".$this->variablename." mit Wert ".$result." : ".exectime($this->startexecute)." Sekunden.\n";
			}


        /* Gesamtauswertung verallgemeinern */

        private function do_gesamtauswertung($aggType)
            {
	                /*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
                if (isset ($this->installedmodules["DetectMovement"]))
                    {
                    echo "     DetectMovement ist installiert. Aggregation abarbeiten:\n";
                    $groups=$this->DetectHandler->ListGroups($aggType,$this->variable);      // nur die Gruppen für dieses Event updaten
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
                        switch ($this->variableType)
                            {
                            case 2:
                                if ($count>0) { $statusResult=round($status/$count,1); }
                                else echo "Gruppe ".$group." hat keine eigenen Eintraege.\n";
                                break;
                            case 1:
                                if ($count>0) { $status=$status/$count; }
                                else echo "Gruppe ".$group." hat keine eigenen Eintraege.\n";
                                $statusResult=(integer)$status;                            
                                break;
                            }
                        //echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
                        /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
                        $statusID=CreateVariableByName($this->AuswertungID,"Gesamtauswertung_".$group,$this->variableType, $this->variableProfile, null, 1000, null);
                        $oldstatus=GetValue($statusID);
                        if ($oldstatus != $statusResult) 
                            {
                            echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $statusResult.\n";
                            SetValue($statusID,$statusResult);     // Vermeidung von Update oder Change Events
                            }
                        }
                    }	
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