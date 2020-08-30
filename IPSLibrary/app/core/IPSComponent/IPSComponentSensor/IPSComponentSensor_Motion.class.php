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
    * @class IPSComponentSensor_Motion
    *
    * Definiert ein IPSComponentSensor_Motion Object, das ein IPSComponentSensor Object für einen Bewegungsmelder implementiert.
	*
	* Eine Veränderung der Variable im Gerät löst ein Event aus und ruft den MessageHandler auf:  IPSMessageHandler::HandleEvent($variable, $value);
	* HandleEvent im IPSMessageHandler sucht sich die passende Konfiguration und ermittelt den richtigen Component und das übergeordnet Modul für mehrere Components
	* für den Component aus der Config wird wieder HandleEvent aufgerufen component::HandleEvent, hier IPSComponentSensor_Motion::HandleEvent
	*
	* wenn es eine Remote OID gibt wird der Wert dort auch hin geschrieben, gespiegelt
	*
	* sonst wird vorher Motion_LogValue aufgerufen Motion_Logging::Motion_LogValue
	* Motion_LogValue liefert entweder ein 1zu1 Spiegelregister oder das Ausschalten wird mittels Timer verzoegert
	* Funktion abhängig von der Einstellung in 
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

	/******************************************************************************************************
	 *
	 *   Class IPSComponentSensor_Motion
	 *
	 ************************************************************************************************************/

	class IPSComponentSensor_Motion extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		private $remServer;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSensor_Monitor Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
		   //echo "Build Motion Sensor with ".$var1.".\n";
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;                    // par1 manchmal auch par2
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
			echo "IPSComponentSensor_Motion, HandleEvent für VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert : ".($value?"Bewegung":"Still")." \n";
			IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);

			$log=new Motion_Logging($variable);
            $mirrorValue=$log->updateMirorVariableValue($value);
            
			$result=$log->Motion_LogValue($value);      // hier könnte man gleiche Werte noch unterdrücken
            
			//$this->SetValueBooleanROID($value);                      // wenn unbeding Boolean
            $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID );
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
            return "Motion_Logging";
            }

        /*
         * Wert auf die konfigurierten remoteServer laden


        public function SetValueBooleanROID($value)
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
							// bei setValueBoolean muss sichergestellt sein dass gegenüberliegender Server auch auf Boolean formattiert ist. 
							$rpc->SetValueBoolean($roid, (boolean)$value);
							}
						}
					}
				}
            }           */

	}

	/******************************************************************************************************
	 *
	 *   Class Motion_Logging
	 *
	 ************************************************************************************************************/
	
	class Motion_Logging extends Logging
		{

		private $variable, $variablename, $variableTypeReg;              /* Untergruppen, hier MOTION oder BRIGHTNESS */
        private $variableProfile, $variableType, $Type;        // Eigenschaften der input Variable auf die anderen Register clonen        
		private $mirrorCatID, $mirrorNameID;            // Spiegelregister in CustomComponent um eine Änderung zu erkennen

		private $AuswertungID, $NachrichtenID, $filename;             /* Auswertung für Custom Component */

		private $configuration;
		private $CategoryIdData;

		/* zusaetzliche Variablen für DetectMovement Funktionen, Detect Movement ergründet Bewegungen im Nachhinein */
		private $EreignisID;
		private $GesamtID;
		private $GesamtCountID;
		private $variableLogID, $variableDelayLogID;

		private $motionDetect_NachrichtenID;            /* zusätzliche Auswertungen */
		private $motionDetect_DataID;
		
        private $startexecute;                  /* interne Zeitmessung */
        
		/* Unter Klassen */
		
		protected $installedmodules;              /* installierte Module */
        protected $DetectHandler;		        /* Unterklasse */
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */        
				
		/**********************************************************************
		 * 
		 * Construct und gleichzeitig eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 * es werden alle notwendigen Variablen erstmalig angelegt, bei Set_logValue werden keine Variablen angelegt, nur die Register gesetzt
         *
         * Die Spiegelregister anlegen:
         *      CustomComonents schreibt Nachrichten und Süiegelregister in der eigenen Data Kategorie mit
         *      DetectMovement macht dasselbe in seiner Kategorie. Es werden mehrere Spiegelregister angelegt.
         *
		 *
		 *************************************************************************/
		 	
		function __construct($variable,$variablename=Null)          // construct ohne variable nicht mehr akzeptieren
			{
            $this->startexecute=microtime(true); 
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
            $this->do_init($variable,$variablename);              // $variable kann auch false sein
			parent::__construct($this->filename);                                       // this->filename wird ion do_init_xxx geschrieben
			}


        private function do_init($variable,$variablename=NULL)
            {
            if ($variable!==false)
                {
                $this->$variable=$variable;
                $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
                if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
                $this->variableType=IPS_GetVariable($variable)["VariableType"];
                                
                $rows=getfromDatabase("COID",$variable);
                if ( ($rows === false) || (sizeof($rows) != 1) )
                    {
                    if (IPS_GetVariable($variable)["VariableType"]==0) $this->variableType = "MOTION";            // kann STATE auch sein, tut aber nichts zur Sache
                    else $this->variableType = "BRIGHTNESS";
                    }
                else    // getfromDatabase
                    {
                    //print_r($rows);   
                    $this->variableType = $rows[0]["TypeRegKey"];    
                    }
                $this->Type=0;      // Motion und Contact ist boolean
                if ($this->variableType =="MOTION") $this->do_init_motion($variable, $variablename);
                elseif ($this->variableType =="CONTACT") $this->do_init_motion($variable, $variablename);
                elseif ($this->variableType =="BRIGHTNESS") 
                    {
                    $this->Type=1;  // Brightness ist Integer
                    $this->do_init_brightness($variable, $variablename);
                    }
                else echo "Fehler, kenne den Variable Typ nicht.\n";
                }
            else $this->do_init_statistics();                
            }

        /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
         * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
         */

        public function do_init_motion($variable, $variablename, $debug=false)
            {
            if ($debug) echo "      Aufruf do_init_motion:\n";
            /**************** installierte Module und verfügbare Konfigurationen herausfinden */
            $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
            $this->installedmodules=$moduleManager->GetInstalledModules();     
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* Detect Movement agreggiert die Bewegungs Ereignisse (oder Verknüpfung) */
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DetectMovement\DetectMovementLib.class.php");
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DetectMovement\DetectMovement_Configuration.inc.php");
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                $this->DetectHandler = new DetectMovementHandler();
                }             

            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

            /* Konfiguration einlesen, ob zusätzliche Spiegelregister mit Delay notwendig sind */ 
            $this->configuration=get_IPSComponentLoggerConfig();

            /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
            $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
            $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
            $name="MotionMirror_".$this->variablename;
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->Type,$this->variableProfile);       /* 2 float ~Temperature*/

            /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
            $this->NachrichtenID=$this->CreateCategoryNachrichten("Bewegung",$this->CategoryIdData);
            $this->AuswertungID=$this->CreateCategoryAuswertung("Bewegung",$this->CategoryIdData);;

            /* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
            $this->do_setVariableLogID($variable);
            $this->variableDelayLogID = $this->variableLogID;                                                                                       // sicherheitshalber, kann später noch überschrieben werden.
            
            /* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
                $this->DetectHandler->Set_MoveAuswertungID($this->AuswertungID);
                $CategoryIdData     = $this->DetectHandler->Get_CategoryData();
                /* DetectMovement Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
                //echo "  Datenverzeichnis Category Data :".$CategoryIdData."\n";
                $name="Motion-Nachrichten";
                $vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
                if ($vid==false)
                    {
                    $vid = IPS_CreateCategory();
                    IPS_SetParent($vid, $CategoryIdData);
                    IPS_SetName($vid, $name);
                    IPS_SetInfo($vid, "this category was created by script. ");
                    }
                $this->motionDetect_NachrichtenID=$vid;

                $name="Motion-Detect";
                $mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
                if ($mdID==false)
                    {
                    echo "Create Motion-Detect Kategorie in $CategoryIdData.\n";
                    $mdID = IPS_CreateCategory();
                    IPS_SetParent($mdID, $CategoryIdData);
                    IPS_SetName($mdID, $name);
                    IPS_SetInfo($mdID, "this category was created by script. ");
                    }
                $this->motionDetect_DataID=$mdID;

                if ($variable<>null)
                    {
                    //echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
                    $directory=$this->configuration["LogDirectories"]["MotionLog"];
                    $dosOps= new dosOps();
                    $dosOps->mkdirtree($directory);
                    $filename=$directory.$this->variablename."_Motion.csv";

                    $variablenameEreignis=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
                    $this->EreignisID=CreateVariableByName($this->motionDetect_DataID,$variablenameEreignis,3,'', null, 100, null );
                    echo "       Ereignisspeicher aufsetzen        : ".$this->EreignisID." \"$variablenameEreignis\"\n";

                    /* Spiegelregister für Bewegung mit Delay, wenn DetectMovement installiert ist */
                    echo '       Spiegelregister (Delay) erstellen : Basis ist '.$variable.' Name "'.$this->variablename.'" in '.$this->motionDetect_DataID." (".IPS_GetName($this->motionDetect_DataID).")\n";
                    $variableDelayLogID=@IPS_GetObjectIDByName($this->variablename,$this->motionDetect_DataID);
                    if ( ($variableDelayLogID===false) || (AC_GetLoggingStatus($this->archiveHandlerID,$variableDelayLogID)==false) || (AC_GetAggregationType($this->archiveHandlerID,$variableDelayLogID) != 0) )
                        {
                        echo "        --> noch nicht vorhanden. Variable Name ".$this->variablename." muss erstellt oder adaptiert werden.\n"; 
                        /* CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0) */
                        $this->variableDelayLogID=CreateVariableByName($this->motionDetect_DataID, $this->variablename,0,'~Motion',null,10,null );
                        AC_SetLoggingStatus($this->archiveHandlerID,$this->variableDelayLogID,true);
                        AC_SetAggregationType($this->archiveHandlerID,$this->variableDelayLogID,0);      /* normaler Wwert */
                        IPS_ApplyChanges($this->archiveHandlerID);
                        }
                    else $this->variableDelayLogID=$variableDelayLogID;    					
                    }
                /* CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0) */
                $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereignisspeicher",3, '', null,10000,null );
                $this->GesamtID=$erID;
                //echo "  Gesamt Ereignisspeicher aufsetzen : ".$erID." \n";
                $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereigniszaehler",1, '', null,10000,null );
                $this->GesamtCountID=$erID;
                //echo "  Gesamt Ereigniszähler aufsetzen   : ".$erID." \n";
                }

			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
            else $directory="C:/Scripts/Switch/";
            $dosOps= new dosOps();
			$dosOps->mkdirtree($directory);
			$this->filename=$directory.$this->variablename."_Bewegung.csv";                
            }

        /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
         * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
         */

        public function do_init_brightness($variable, $variablename, $debug=false)
            {
            if ($debug) echo "      Aufruf do_init_brightness:\n";
            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
            /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
            $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
            $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
            $name="HelligkeitMirror_".$this->variablename;
            echo "CreateVariableByName at ".$this->mirrorCatID." mit Name \"$name\" Type ".$this->Type." Profile ".$this->variableProfile." Variable available : ".(@IPS_GetVariableIDByName($name, $this->mirrorCatID)?"Yes":"No")." \n";
            $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->Type,$this->variableProfile);       /* 2 float ~Temperature*/

            echo "Create Category to store the Move-LogNachrichten und Spiegelregister:\n";	
            $this->NachrichtenID=$this->CreateCategoryNachrichten("Helligkeit",$this->CategoryIdData);
            $this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;

            echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen:\n";
            $this->do_setVariableLogID($variable);

			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
            else $directory="C:/Scripts/Switch/";
            $dosOps= new dosOps();
			$dosOps->mkdirtree($directory);
			$this->filename=$directory.$this->variablename."_Helligkeit.csv";      
            }

        /* wird beim construct aufgerufen, wenn keine Variable übvergeben wurde.
         * Klasse wird für Statistische Auswertungen verwendet.
         */

        public function do_init_statistics()
            {
            echo "      Aufruf do_init_statistics:\n";
            /**************** Speicherort für Nachrichten und Spiegelregister definieren */		
            $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
            $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
            $this->NachrichtenID=$this->CreateCategoryNachrichten("Helligkeit",$this->CategoryIdData);
            $this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
            else $directory="C:/Scripts/Switch/";
            $dosOps= new dosOps();
			$dosOps->mkdirtree($directory);
			$this->filename=$directory."Statistik.csv";      
            }

        /* do_setVariableLogID, nutzt setVariableLogId aus der Logging class 
        * kannnicht diesselbe class sein, da this verwendet wird
        */

        private function do_setVariableLogID($variable)
            {
            if ($variable<>Null)
                {
                $this->variable=$variable;
                //echo "Aufruf setVariableLogId(".$this->variable.",".$this->variablename.",".$this->AuswertungID.")\n";
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->Type,$this->variableProfile);                   // $this->variableLogID schreiben
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

        public function getVariableOIDLogging()
            {
            if ( (isset ($this->installedmodules["DetectMovement"])) && ($this->variableType==0) )
                {
                $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID, "variableDelayLogID" => $this->variableDelayLogID, "Ereignisspeicher" => $this->EreignisID, "Gesamt_Ereignisspeicher" => $this->GesamtID, "Gesamt_Ereigniszaehler" => $this->GesamtCountID];
                }
            elseif ($this->variableType==0) $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID, "variableDelayLogID" => $this->variableDelayLogID];
            else $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID];

            return $result;
            }


        /* Spiegelregister updaten */

        function updateMirorVariableValue($value)
            {
            $oldvalue=GetValue($this->mirrorNameID);
            SetValue($this->mirrorNameID,$value);
            return($oldvalue);
            }


		/**********************************************************************
		 * 
		 * Eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
         * Routine wird verwendet bei der Status Ausgabe für die Events:
         *      $log->Set_LogValue($oid);
		 *		$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
		 *
		 *************************************************************************/

		function Set_LogValue($variable)
			{
			if ( ($variable<>null) && ($variable<>false) )
				{
				echo "Add Variable ID : ".$variable." (".IPS_GetName($variable).") für IPSComponentSensor Motion Logging.\n";
                $this->do_init($variable);                                                                                                  // initialisiserung gleich wie in construct
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->Type,$this->variableProfile);                   // $this->variableLogID schreiben aus do_setVariableLogId
				}
			
			/* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
				$variablename=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
				$erID=CreateVariable($variablename,3, $this->motionDetect_DataID, 10, '', null );
				echo "  Ereignisspeicher aufsetzen        : ".$erID." \n";
				$this->EreignisID=$erID;
				}
	   		}
			
		/**********************************************************************
		 * 
		 * Den Wert einer Variable dem Motion Logging zuführen
		 *
		 * IPSComponents_Sensor wird vom Messagehandler aufgerufen
		 * Die VariableID wird im construct Aufruf übergeben, der neue Wert 
		 * sollte bereits in der Variable gespeichert sein
		 *
		 * ACHTUNG der testweise Wertübertrag führt zu Verwirrung weil ein Ueberschreiben des Wertes gleich wieder einen Trigger ausloest
		 *
		 *************************************************************************/			
	   
		function Motion_LogValue($value)
			{
			$result=GetValue($this->variable);
            if ( ($this->variableType =="MOTION") || ($this->variableType =="CONTACT") )
                {            
                if (true)
                    {
                    //$result=$value;		/* für Testzwecke, der mitgelieferte Wert wird normalerweise nicht geschrieben */
                    //echo "NUR FUER TESTZWECKE WERT UEBERMITTELN.\n";
                    }
                $resultLog=GetValueIfFormatted($this->variable);
                echo "CustomComponent Motion_LogValue Log Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert : $resultLog\n";
                IPSLogger_Inf(__file__, 'DetectMovement Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable)."/".IPS_GetName(IPS_GetParent($this->variable)).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert $resultLog");
                if ( (isset($this->configuration["LogConfigs"]["DelayMotion"])) == true)
                    {
                    if ($result==true)
                        {
                        $delaytime=$this->configuration["LogConfigs"]["DelayMotion"];
                        SetValue($this->variableDelayLogID,$result);
                        echo "   Verzögerung der Events konfiguriert, Timer im selben Verzeichnis wie Script gesetzt : ".$this->variable."_".$this->variablename."_EVENT"."\n";
                        $now = time();
                        $EreignisID = @IPS_GetEventIDByName($this->variable."_".$this->variablename."_EVENT", IPS_GetParent($_IPS['SELF']));
                        if ($EreignisID === false)
                            { //Event nicht gefunden > neu anlegen
                            $EreignisID = IPS_CreateEvent(1);
                            IPS_SetName($EreignisID,$this->variable."_".$this->variablename."_EVENT");
                            IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
                            }
                        IPS_SetEventCyclic($EreignisID,0,1,0,0,1,$delaytime);      /* konfigurierbar, zB alle 30 Minuten, d.h. 30 Minuten kann man still sitzen bevor keine Bewegung mehr erkannt wird */
                        IPS_SetEventCyclicTimeBounds($EreignisID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
                        IPS_SetEventScript($EreignisID,"if (GetValue(".$this->variable.")==false) { SetValue(".$this->variableDelayLogID.",false); IPS_SetEventActive(".$EreignisID.",false);} \n");
                        IPS_SetEventActive($EreignisID,true);
                        }
                    }	
                else
                    {
                    /* Kein Delay konfiguriert, Wert egal ob true oder false einfach übernehmen */
                    SetValue($this->variableLogID,$result);				
                    }
                //print_r($this);
                if (isset ($this->installedmodules["DetectMovement"]))
                    {
                    /* etwas kompliziert, wenn DetectMovement nicht installiert is sind beide Variablen auf dem selben Wert.
                    * wenn installiert, wird Delay abgewickelt, aber es muss noch wer den Wert in CustomComponents setzen
                    */
                    SetValue($this->variableLogID,$result);
                    
                    /* DetectMovement class verwenden */
                    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                                                                            
                    /* Achtung die folgenden Werte haben keine Begrenzung, sicherstellen dass String Variablen nicht zu gross werden. */
                    $EreignisVerlauf=GetValue($this->EreignisID);
                    $GesamtVerlauf=GetValue($this->GesamtID);
                    $GesamtZaehler=GetValue($this->GesamtCountID);
                    if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
                    if (IPS_GetName($this->variable)=="MOTION")
                        {
                        if (GetValue($this->variable))
                            {
                            $resultLog="Bewegung";
                            //$EreignisVerlauf.=date("H:i").";".STAT_Bewegung.";";
                            $Ereignis=time().";".STAT_Bewegung.";";
                            $GesamtZaehler+=1;
                            $EreignisVerlauf.=$Ereignis;
                            $GesamtVerlauf.=$Ereignis;
                            }
                        else
                            {
                            $resultLog="Ruhe";
                            //$EreignisVerlauf.=date("H:i").";".STAT_WenigBewegung.";";
                            $Ereignis=time().";".STAT_WenigBewegung.";";
                            $GesamtZaehler-=1;
                            if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
                            //$GesamtVerlauf.=date("H:i").";".$GesamtZaehler.";";
                            $EreignisVerlauf.=$Ereignis;
                            $GesamtVerlauf.=$Ereignis;
                            }
                        }
                    else
                        {
                        $Ereignis=time().";".STAT_Bewegung.";".time().";".STAT_WenigBewegung.";";
                        if (GetValue($this->variable))
                            {
                            $resultLog="Offen";
                            }
                        else
                            {
                            $resultLog="Geschlossen";
                            }
                        $EreignisVerlauf.=$Ereignis;
                        }
                    echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->EreignisID)." \n";
                    SetValue($this->EreignisID,$this->evaluateEvents($EreignisVerlauf));
                    echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->GesamtID)." \n";
                    SetValue($this->GesamtID,$this->evaluateEvents($GesamtVerlauf,60));
                    SetValue($this->GesamtCountID,$GesamtZaehler);
                
                    //print_r($DetectMovementHandler->ListEvents("Motion"));
                    //print_r($DetectMovementHandler->ListEvents("Contact"));

                    $groups=$this->DetectHandler->ListGroups('Motion',$this->variable);      // nur die Gruppen für dieses Event updaten, wenn Parameter Motion angegeben ist gibt es auch ein Explode der mit Komma getrennten Gruppennamen
                    foreach($groups as $group=>$name)
                        {
                        echo "\nMotion_LogValue Log DetectMovement Gruppe ".$group." behandeln.\n";
                        $config=$this->DetectHandler->ListEvents($group);
                        $status=false; $status1=false;
                        foreach ($config as $oid=>$params)
                            {
                            $status=$status || GetValue($oid);
                            echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
                            $moid=$this->DetectHandler->getMirrorRegister($oid);
                            $status1=$status1 || GetValue($moid);
                            }
                        echo "  Gruppe ".$group." hat neuen Status, Wert ohne Delay: ".(integer)$status."  mit Delay:  ".(integer)$status1."\n";
                        $statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent($this->variableDelayLogID),1000, '~Motion', null,false);
                        $oldstatus1=GetValue($statusID);
                        if ($oldstatus1 != $status1) 
                            {
                            echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus1 auf $status1.\n";
                            SetValue($statusID,$status1);     // Vermeidung von Update oder Change Events
                            }
                        $statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent($this->variableLogID),1000, '~Motion', null,false);
                        $oldstatus=GetValue($statusID);
                        if ($oldstatus != $status1) 
                            {
                            echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $status.\n";
                            SetValue($statusID,$status);     // Vermeidung von Update oder Change Events
                            }
                        
                        $ereignisID=CreateVariable("Gesamtauswertung_".$group."_Ereignisspeicher",3,IPS_GetParent($this->variableDelayLogID),0, '', null);
                        echo "  EreignisID       : ".$ereignisID." (".IPS_GetName($ereignisID).")\n";
                        echo "  Ereignis         : ".$Ereignis."\n";
                        //echo "  Size             : ".strlen(GetValue($ereignisID))."\n";
                        $EreignisVerlauf=GetValue($ereignisID).$Ereignis;
                        //echo "  Ereignis Verlauf : ".$EreignisVerlauf."\n";
                        SetValue($ereignisID,$this->addEvents($EreignisVerlauf));
                        }
                    } /* Ende Detect Motion */
                }
            else
                {       // log Brightness
                $resultLog=GetValueIfFormatted($this->variable);
                echo "CustomComponent Brightness_LogValue Log Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert : $resultLog\n";
                IPSLogger_Inf(__file__, 'CustomComponent Brightness Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert $resultLog");
                SetValue($this->variableLogID,$result);		
                //$this->do_gesamtauswertung("Helligkeit");                		// es gibt keinen DetectMovement Handler für Helligkeit
                }
			parent::LogMessage($resultLog);
			parent::LogNachrichten($this->variablename." mit Status ".$resultLog);
			}

        /* Gesamtauswertung verallgemeinern, die von Motion hab ich extra gelassen da sie auch die Bewegung mit Delays extra aggregiert */

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
		Bearbeiten des Eventspeichers
		hier nur überprüfen ober der Eventspeicher nicht zu lang wird

		*************************************************************************************/

		private function addEvents($value)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Check Eventliste (max 20.000 Eintraege), derzeit Länge Ereignisstring: ".strlen($value)."\n";
			$max=20000;
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>($max*2))
				   {
				   /* events nicht groesser Eintraege werden lassen */
					$indexbefordelete=$max;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				//echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}

		/*************************************************************************************
		Bearbeiten des Eventspeichers


		*************************************************************************************/

		private function evaluateEvents($value, $diftimemax=15)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Evaluate Eventliste (max 20 Eintraege) : ".$value."\n";
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>40)
				   {
				   /* events nicht groesser als 20 Eintraege werden lassen */
					$indexbefordelete=$i-20;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					else
					   {
						$dif_time=(($now_time-$bef_time)/60);
						//echo "Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." und davor ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						echo "      Betrachteter (".$i.") State jetzt ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						switch ($previous_state)
   	  				   {
   	  				   /*****************************************************************************
							 erst einmal Unterscheidung anhand aktuellem Status
							 Bewegung   ->  um so mehr Bewegungssender aktiv sind um so hoeher der Wert
							******************************************************************************/
	 			     		case STAT_Bewegung9:
	 			     		case STAT_Bewegung8:
	 			     		case STAT_Bewegung7:
	 			     		case STAT_Bewegung6:
	 			     		case STAT_Bewegung5:
	 			     		case STAT_Bewegung4:
	 			     		case STAT_Bewegung3:
	 			     		case STAT_Bewegung2:
			   	  	   case STAT_Bewegung:
						      /* Wenn jetzt Bewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
								 	{
	 			     		   	case STAT_Bewegung9:
	 			     		   	case STAT_Bewegung8:
	 			     		   	case STAT_Bewegung7:
	 			     		   	case STAT_Bewegung6:
	 			     		   	case STAT_Bewegung5:
	 			     		   	case STAT_Bewegung4:
	 			     		   	case STAT_Bewegung3:
	 			     		   	case STAT_Bewegung2:
	 			     		   	case STAT_Bewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->Bewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
						 			case STAT_WenigBewegung:
									case STAT_KeineBewegung:
									case STAT_vonzuHauseweg:
									   //echo "Wenig Bewegung: ".$dif_time."\n";
										if (($dif_time<$diftimemax) and ($dif_time>=0))
										   {
										   // Warum mus dif_time >0 sein ????
	  			   						$previous_state=10;    /* default, einen ueberspringen, damit voriger Wert vorerst nicht mehr geloescht werden kann */
		   							 	/* einfach die letzten zwei Einträge loeschen, nachdem Wert kein zweites Mal geloescht werden kann vorerst mit Default Wert arbeiten */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i)." mit ".$EventArray[$i+0]." am ".date("d.m H:i",$EventArray[$i-1])."\n";
   									 	unset($EventArray[$i+0]);
	   								 	unset($EventArray[$i-1]);
				   				 		}
		   					 		else
		   						 	   {
						    				$previous_state=$EventArray[$i];
									      $previous_time=(integer)$EventArray[$i-1];
											}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
								 }
								break;
			   	  	   case STAT_WenigBewegung:
						      /* Wenn jetzt wenigBewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
		      				   {
	 			     		   	case STAT_WenigBewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
									}
			   	  	      break;
			   	   	case STAT_vonzuHauseweg:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    2 eliminiert den vorigen 2 er und lässt aktuelle Zeit
							    */
				   	   	 switch ($EventArray[$i])
								    {
				 					 case STAT_vonzuHauseweg:
				   					 $previous_state=10;    /* default */
				   					 /* einfach von den letzten zwei Einträgen rausloeschen */
			   						 unset($EventArray[$i+0]);
						   			 unset($EventArray[$i-1]);
							 		 break;
						 			 default:
									 	 $previous_state=$EventArray[$i];
						   			 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
							 		 }
								break;
   	  			   	case STAT_Abwesend:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    0 übernimmt die Zeit des Vorgängers und eliminiert 0,1 und 2
							     */
					   	    switch ($EventArray[$i])
								    {
			     	   			 case STAT_Abwesend:
									 case STAT_nichtzuHause:
					 				 case STAT_vonzuHauseweg:
						   			 $previous_state=10;    /* default */
   									 /* einfach von den letzten zwei Einträgen die mittleren Werte rausloeschen */
		   							 unset($EventArray[$i+1]);
   									 unset($EventArray[$i+0]);
								 		 break;
					 				 default:
									    $previous_state=$EventArray[$i];
								   	 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
					 				 }
								break;
							default:
							   $previous_state=$EventArray[$i];
	      					$previous_time=(integer)$EventArray[$i-1];
								break;
							}
						}
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}


		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

        public function writeEvents($comp=true,$gesamt=false)
            {
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                if ($gesamt)
                {
                    $value=GetValue($this->GesamtID);
                    $diftimemax=60;
                    }
                else
                {
                    $value=GetValue($this->EreignisID);
                    $diftimemax=15;
                    }
                /* es erfolgt zwar eine Kompromierung aber keine Speicherung in den Events, das ist nur bei Auftreten eines Events */
                if ($comp)
                    {
                    $value=$this->evaluateEvents($value, $diftimemax);
                    $value=$this->evaluateEvents($value, $diftimemax);
                    }
                $EventArray = explode(";", $value);
                echo "Write Eventliste von ".IPS_GetName($this->EreignisID)." : ".$value."\n";

                /* Umsetzung des kodierten Eventarrays in lesbaren Text */
                $event2="";
                $array_size = count($EventArray);
                for ($k=1; $k<($array_size); $k++ )
                    {
                    $event2=$event2.date("d.m H:i",(integer)$EventArray[$k-1])." : ";
                    //echo "check : ".$EventArray[$k]."\n";
                    switch ($EventArray[$k])
                        {
                        case STAT_KommtnachHause:
                            $event2=$event2."Kommt nach Hause";
                        break;
                    case STAT_Bewegung9:
                        $event2=$event2."Bewegung 9 Sensoren";
                        break;
                        case STAT_Bewegung8:
                        $event2=$event2."Bewegung 8 Sensoren";
                        break;
                    case STAT_Bewegung7:
                        $event2=$event2."Bewegung 7 Sensoren";
                        break;
                        case STAT_Bewegung6:
                        $event2=$event2."Bewegung 6 Sensoren";
                        break;
                    case STAT_Bewegung5:
                        $event2=$event2."Bewegung 5 Sensoren";
                        break;
                        case STAT_Bewegung4:
                        $event2=$event2."Bewegung 4 Sensoren";
                        break;
                    case STAT_Bewegung3:
                        $event2=$event2."Bewegung 3 Sensoren";
                        break;
                        case STAT_Bewegung2:
                        $event2=$event2."Bewegung 2 Sensoren";
                        break;
                    case STAT_Bewegung:
                        $event2=$event2."Bewegung";
                        break;
                        case STAT_WenigBewegung:
                        $event2=$event2."Wenig Bewegung";
                        break;
                        case STAT_KeineBewegung;
                        $event2=$event2."Keine Bewegung";
                        break;
                        case STAT_Unklar:
                        $event2=$event2."Unklar";
                        break;
                        case STAT_Undefiniert:
                        $event2=$event2."Undefiniert";
                        break;
                        case STAT_vonzuHauseweg:
                        $event2=$event2."Von zu Hause weg";
                        break;
                        case STAT_nichtzuHause:
                        $event2=$event2."Nicht zu Hause";
                        break;
                        case STAT_Abwesend:
                        $event2=$event2."Abwesend";
                        break;
                        }
                    $k++;
                $event2=$event2."\n";
                    }
                return ($event2);
                }
            else
                {
                return ("");
                }		
            } /* ende function */
            
        } /* ende class */	

	/******************************************************************************************************
	 *
	 *   Class Motion_LoggingStatistics
     *
     * Erweiterung der Klasse um statistische Auswertungen. Aktuell sind die Routinen noch in der child class
     *
	 *
	 ************************************************************************************************************/

	class Motion_LoggingStatistics extends Motion_Logging
		{

		function __construct()          // construct ohne variable nur für übergeordnete Aufrufe erlauben
			{
            $this->startexecute=microtime(true); 
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 

    		parent::__construct(false);
			}



        }
	/** @}*/
?>