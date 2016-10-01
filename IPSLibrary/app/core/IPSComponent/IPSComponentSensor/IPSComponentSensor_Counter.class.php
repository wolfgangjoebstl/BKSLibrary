<?

   /**
    * @class IPSComponentSensor_Counter
    *
    * Definiert ein IPSComponentSensor_Counter Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");


	class IPSComponentSensor_Counter extends IPSComponentSensor {


		private $tempObject;
		private $RemoteOID;
		private $tempValue;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_Counter Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			$this->remServer	  = RemoteAccessServerTable();
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
		public function HandleEvent($variable, $value, IPSModuleSensor $module){
			echo "Counter Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			
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

	class Counter_Logging extends Logging
	   {
	   private $variable;
	   private $variablename;
		private $variableLogID;
		private $counterLogID;

		private $CounterAuswertungID;
		
	   function __construct($variable)
		   {
		   echo "Construct Counter Logging for Variable ID : ".$variable."\n";
		   $this->variable=$variable;
		   $result=IPS_GetObject($variable);
		   $this->variablename=IPS_GetName((integer)$result["ParentID"]);
		   
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedmodules=$moduleManager->GetInstalledModules();

			$moduleManager_DM = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
			//echo "Datenverzeichnis:".$CategoryIdData."\n";
			$name="Counter-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==false)
			   {
				$vid = IPS_CreateCategory();
				IPS_SetParent($vid, $CategoryIdData);
      			IPS_SetName($vid, $name);
	      		IPS_SetInfo($vid, "this category was created by script. ");
	      		}
			$name="Counter-Auswertung";
			$CounterAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($CounterAuswertungID==false)
			   {
				$CounterAuswertungID = IPS_CreateCategory();
				IPS_SetParent($CounterAuswertungID, $CategoryIdData);
      			IPS_SetName($CounterAuswertungID, $name);
	      		IPS_SetInfo($CounterAuswertungID, "this category was created by script. ");
	      		}
			$this->CounterAuswertungID=$CounterAuswertungID;
			if ($variable<>null)
			   {
			   /* lokale Spiegelregister aufsetzen */
				$this->variableLogID=CreateVariable($this->variablename,2,$CounterAuswertungID, 10, '', null );
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
	      		AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				$this->counterLogID=CreateVariable($this->variablename."_Counter",2,$CounterAuswertungID, 10, '', null, null );   // Float Variable anlegen
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				//IPS_SetVariableCustomProfile($this->variableLogID,'~Temperature');
	      		AC_SetLoggingStatus($archiveHandlerID,$this->counterLogID,true);
				AC_SetAggregationType($archiveHandlerID,$this->counterLogID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
				
			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			$directory=$directories["LogDirectories"]["CounterLog"];
			mkdirtree($directory);
			$filename=$directory.$this->variablename."_Counter.csv";
			parent::__construct($filename,$vid);
			}

		function Counter_LogValue()
			{
			$result=number_format(GetValue($this->variable),2,',','.');
			SetValue($this->variableLogID,GetValue($this->variable)-GetValue($this->counterLogID));
			SetValue($this->counterLogID,GetValue($this->variable));

			echo "Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." Änderung auf letzten Wert ".GetValue($this->counterLogID);
			
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$installedmodules=$moduleManager->GetInstalledModules();

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
