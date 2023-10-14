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
	 * Einleitung:
     *
	 * Counter ist zum Beispiel ein Counter eines regensensors. Energieregister sind zwar auch Counter, sollten aber besser ueber das Modul AMIS 
	 * abgewickelt werden.
	 *
     * Unterschiedliche Gerätetypen (Instanzen) können Sensorwerte (Register) liefern. Darstellung der zu Grunde liegenden information kann unterschieldich sein.
     * Es ist eine Vereinheitlichung notwendig. Auch muss der Tausch auf einen anderen gerätetypen oder eine Namensändeung möglich sein ohne das Endergebnis und dessen
     * Kontinuität in der Erfassung zu verändern.
     *
     * Ablauf:
     *
	 * Events werden im Event Handler des IPSMEssageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Temperatur,','IPSModuleSensor_Temperatur,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 * Dann wird Logging und RemoteAccess abgearbeitet
	 *
	 * Logging:
	 *
	 * construct für class Counter_Logging erstellt im IPSLibrary.Data.Core.IPSComponent eine Kategorie für die Spiegelregister und eine Kategorie für die Nachrichten
	 * zusaetzlich auch das Verzeichnis im Log Verzeichnis wenn erforderlich
     *
     * Speicherorte für Logging:
     *
     * Der Sensorwert selbst wird durch AUfruf von Counter_LogValue archiviert. Name Register, Name Instanz und Art der Information bleiben erhalten.
     * bearbeitete Register werden unter /Program/IPSLibrary/data/core/IPSComponent gespeichert
     * je nach Typ der Information Bewegung,Climate,Counter,Feuchtigkeit,HeatControl,HeatSet,Helligkeit,Kontakt,Sensor,Switch,Temperator
     * wird eine Kategorie mit _Auswertung und _Nachrichten angelegt.
     * Zusätzlich gibt es Statistik_Nachrichten LoggingConf und Mirror
     *
     * Der wert wird unter Mirror gespeichert, das ist der Messwert mit dem Profil und Typ aus dem Sensor, einzig der Name wird bereits standardisiert 
     * Gleicher Name hilft den Sensortausch zu vereinfachen und macht unabhängig von Räumen udn anderen Konverntionen, allerdings hat eine Änderung auf ein anderes Gerät
     * den Verlust der hier gespeicherten Daten zur Folge, sofern sich Variablentyp und Profil ändern.
	 *
	 * es erfolgt ein Eintrag im eigenen Nachrichtenspeicher für Counter
	 *
	 * wenn das Modul RemoteAccess installiert ist, wird eine Kopie des Registerwertes auf einem externen Log Server gespeichert und bei Veränderung upgedatet
	 *  Beispiel --> config eintrag von BKS-Virt
	 * aaaaa => array('OnChange','IPSComponentSensor_Counter,LBG70-2Virt:rrrrr;','IPSModuleSensor_Counter',), 
	 * bei Veränderung des Counters wird die Remote OID am Server zB LBG70-2Virt upgedatet
     *
	 * @author Wolfgang Jöbstl
	 * @version
	 *   Version 2.50.1, 09.06.2012<br/>
	 */

	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    /* Aufruf Component für Counter
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Counter,1,2,3','IPSModuleSensor_Counter,1,2,3',),
     * zB für den Regenmesser 5157? => array('OnUpdate','IPSComponentSensor_Counter,4453?,,RAIN_COUNTER','IPSModuleSensor_Counter,',),
     *
     * 4453*            wird instanceId, hier nicht verwendet
     *                  wird remoteOID für ein Backup auf weitere Server
     * RAIN_COUNTER     wird tempValue
     *
     *
     * HandleEvent wird nach dem construct mit dem Variablenwert aufgerufen, gibt weiter an Logging
     *
     */

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

		public function __construct($instanceId=null, $remoteOID=null, $tempValue=null)
			{
			echo "IPSComponentSensor_Counter: Construct Counter Sensor with ($instanceId,$remoteOID,$tempValue).\n";		
            //$this->RemoteOID    = instanceID;                // par1 manchmal auch par2		
			$this->RemoteOID    = $remoteOID;           // par2 manchmal auch par1
			$this->tempValue    = $tempValue;           // par3                

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
			echo "IPSComponentSensor_Counter:HandleEvent, Counter Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			//IPSLogger_Dbg(__file__, 'HandleEvent: Counter Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			

			$debug=true;
			$log=new Counter_Logging($variable,null,$this->tempValue,$debug);        // es wird kein Variablenname übergeben
			$result=$log->Counter_LogValue($value, $debug);
            $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID,$debug);   

			/*if ($this->RemoteOID != Null)
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
				}*/
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
		
		protected $variable,$variablename;

		protected $variableLogID;					// nur die Veränderungen werden gespeichert
		protected $counterLogID;					// Spiegelregister der eigentlichen Homematic Variable
		protected $counter2LogID;					// Spiegelregister erweitert um Offset
		protected $counterOffsetLogID;			// Offset für Spiegelregister
		
		protected $CounterAuswertungID;			// Kategorie für Register
		protected $CounterNachrichtenID;			// Kategorie für Nachrichten
        
        /* construct verwendet soweit möglich gemeinsame Routinen
         *      constructFirst      sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()
         *      do_init
         */

		function __construct($variable,$variablename=Null,$variableTypeReg="unknown",$debug=false)
			{
            if ( ($this->GetDebugInstance()) && ($this->GetDebugInstance()==$variable) ) $this->debug=true;
            else $this->debug=$debug;
            if ($this->debug) echo "   Counter_Logging, construct : ($variable,$variablename,$variableTypeReg).\n";

            $this->constructFirst();        // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()
            
            /* Initialisisert wird hier - nach constructFirst noch mit do_init und do_init_counter
             * CategoryIdData,mirrorCatID
             * variable,variableProfil,variableType  iD der variable und ausgelesenes profil (entweder standard oder custom), Werte sind Sensor und Gerätespezifisch
             * mirrorType,mirrorProfil  werden von der Variable übernommen
             *
             * Ermittelt wird
             * variableTypeReg
             *
             * und von do_init_counter
             *  DetectHandler       wenn installiert
             *  variablename        abgeleitet aus dem Variablennamen oder aus der Config
             *  mirrorNameID
             *  variable, variableLogID
             *  NachrichtenID, AuswertungID, filename
             *
             *
             */
            $NachrichtenID=$this->do_init($variable,$variablename,null, $variableTypeReg, $this->debug);            // $typedev ist $variableTypeReg, $value wird normalerweise auch übergeben, $variable kann auch false sein

            $dosOps= new dosOps();
            //$this->configuration=$this->set_IPSComponentLoggerConfig();             /* configuration verifizieren und vervollstaendigen, muss vorher erfolgen */
			echo "Construct IPSComponentSensor Counter Logging for Variable ID : ".$variable." with Config ".json_encode($this->configuration)."\n";
			
			/****************** Variablennamen herausfinden und/oder berechnen 
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
				}       */			
			
			/**************** Speicherort für Nachrichten und Spiegelvarianten herausfinden 
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedmodules=$moduleManager->GetInstalledModules();        

			$moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			//echo "Datenverzeichnis:".$CategoryIdData."\n";    */

			/* Create Category to store the Counter-LogNachrichten 
			$name="Counter-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($vid==false)
				{
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $this->CategoryIdData);
				IPS_SetName($vid, $name);
				IPS_SetInfo($vid, "this category was created by script IPSComponentSensor_Counter.");
				}
			$this->NachrichtenID=$vid;

			$name="Counter-Auswertung";
			$CounterAuswertungID=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($CounterAuswertungID==false)
				{
				$CounterAuswertungID = IPS_CreateCategory();
				IPS_SetParent($CounterAuswertungID, $this->CategoryIdData);
				IPS_SetName($CounterAuswertungID, $name);
				IPS_SetInfo($CounterAuswertungID, "this category was created by script IPSComponentSensor_Counter.");
				}
			$this->AuswertungID=$CounterAuswertungID;   */
			
			if ($variable<>null)
				{
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				/* lokale Spiegelregister als Float aufsetzen 
				$this->variableLogID=CreateVariable($this->variablename,2,$CounterAuswertungID, 10, '', null );
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      // normaler Wwert */

				$this->counterOffsetLogID = CreateVariableByName($this->AuswertungID, "Offset_".$this->variablename,  2, "", "", 100 );   // Float Variable anlegen
				$this->counterLogID       = CreateVariableByName($this->AuswertungID, $this->variablename."_Counter", 2, "", "",  10);   // Float Variable anlegen
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->counterLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->counterLogID,0);      /* normaler Wwert */

				$this->counter2LogID = CreateVariableByName($this->AuswertungID, $this->variablename."_Counter2", 2, "", "", 20);   // Float Variable anlegen
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
				AC_SetLoggingStatus($archiveHandlerID,$this->counter2LogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->counter2LogID,0);      /* normaler Wwert */

				IPS_ApplyChanges($archiveHandlerID);
				}

			parent::__construct($this->filename,$NachrichtenID);
			}

        /******************************
         *  Log Counter Value in different formats
         *  es wird nur gelogged wenn der Wert einen Unterschied aufweist, diff
         *
         *  variable            ID vom Input Wert, für Debug Aufgaben ist der Wert auch in value
         *  variableLogID       ID für den diff Wert als Float, $variablename
         *
         *  Zusätzlich wird benötigt:
         *
         *  counterLogID        iD vom Spiegelregister als Float, $variablename."_Counter"
         *  counter2LogID
         *  counterOffsetLogID
         *
         **************/

		function Counter_LogValue($value, $debug)
			{
            // noch ein bisschen genauer anschauen, Vereinheitlichung Homematic und Netatmo Regenmesser
            if ($debug) ; // im Debug Mode falsche Werte mit $value einschleusen  
            else $value=GetValue($this->variable);

			// result formatieren für Ausgabe in den LogNachrichten
			$resultLog=GetValueFormatted($this->variable);
			$variabletyp=IPS_GetVariable($this->variable);
			$unchanged=time()-$variabletyp["VariableChanged"];
            /* rausfinden ob ein Counter oder nur increments erfasst werden, diff dafür berechnen */
            if ($this->variableTypeReg =="RAIN_COUNTER")
                {
                //echo "Rain Counter ".$this->variable."  ".IPS_GetName($this->variable)." \n";
                $instanceID=IPS_GetParent($this->variable);
                if (IPS_GetObject($instanceID)["ObjectType"]==1) 
                    {
                    //echo "ist eine Instanz\n";
                    $moduleInfo=IPS_GetInstance($instanceID)["ModuleInfo"]["ModuleName"];
                    if ($debug)                 
                        {
                        echo "Rain Counter ".$this->variable."  ".IPS_GetName($this->variable)." Parent ist ein Objekt ist eine Instanz und ein Gerät mit dem Modulnamen \"$moduleInfo\".\n";
                        echo "Keine Änderung seit ".round($unchanged/3600,1)." Sekunden.\n";
                        }
                    if ($moduleInfo=="NetatmoWeatherDevice")            // es werden nur die Increments erfasst
                        {
                        $diff=$value;
                        }
                    else            // wirklich ein Counter der nach oben zählt
                        {
                        $value = $value-
                        $diff=$value-GetValue($this->counterLogID);
                        if ($debug)                 // im Debug Mode falsche Werte einschleusen
                            {
                            echo "Counter_LogValue mit $value aufgerufen. Vergleichen mit ".$this->counterLogID." ".IPS_GetName($this->counterLogID)." = ".GetValueIfFormatted($this->counterLogID)."\n";                    
                            }
                        }
                    }
                elseif ($debug) echo "Warnung, Rain Counter ".$this->variable."  ".IPS_GetName($this->variable)." kein ObjectType 1. \n";
                }
			else $diff=GetValue($this->variable)-GetValue($this->counterLogID);

            /* diff behandeln, wir haben 
             *      variableLogID           den zusaetzlichen Niederschlag
             *      counterLogID            die akkumulierten Niederschläge
             *      counterOffsetLogID      ein Offset der akkumulierten Niederschläge, wenn sich das gerät ändert oder der Zähler resetiert wird
             *                              akkumulierter Wert im Gerät ist 149mm, im counter register aber 1634mm, der Offset muss dann 1634 sein, die Differenz ist dann 
             */
			if ($diff != 0)
				{
				if ($diff>0)
					{
                    if ($moduleInfo=="NetatmoWeatherDevice")
                        {
                        SetValue($this->variableLogID,GetValue($this->variable));
                        SetValue($this->counterLogID,GetValue($this->variable)+GetValue($this->counterLogID));
                        $resultLog .= " (".GetValueIfFormatted($this->counterLogID).")";
                        }
                    else    
                        {
                        SetValue($this->variableLogID,GetValue($this->variable)-GetValue($this->counterLogID));
                        SetValue($this->counterLogID,GetValue($this->variable));
                        $resultLog .= " (".GetValueIfFormatted($this->variableLogID).")";
                        //SetValue($this->counterLogID,GetValue($this->variable)+GetValue($this->counterOffsetLogID));
                        }
					echo ">>>>Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." Änderung auf letzten Zählwert ".GetValue($this->counterLogID)." um ".GetValue($this->variableLogID)."\n";
					}
				else
					{
					SetValue($this->counterOffsetLogID,GetValue($this->counterOffsetLogID)-$diff);
					}						
                
                /*$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
                $installedmodules=$moduleManager->GetInstalledModules();*/

				parent::LogMessage($resultLog);
				parent::LogNachrichten($this->variablename." mit Wert ".$resultLog,$debug);
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