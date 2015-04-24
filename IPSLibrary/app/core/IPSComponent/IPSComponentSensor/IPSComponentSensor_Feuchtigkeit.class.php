<?

   /**
    * @class IPSComponentSensor_Temperatur
    *
    * Definiert ein IPSComponentSensor_Temperatur Object, das ein IPSComponentSensor Object für einen Sensor implementiert.
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


	class IPSComponentSensor_Feuchtigkeit extends IPSComponentSensor {


		private $tempObject;
		private $RemoteOID;
		private $tempValue;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1, $lightObject=null, $lightValue=null) {
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			$this->remServer    = RemoteAccess_GetConfiguration();
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
			echo "Temperatur Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			
			$log=new Feuchtigkeit_Logging($variable);
			$result=$log->Feuchtigkeit_LogValue();
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
					$Server=$this->remServer[$para[0]];
					echo "Server : ".$Server."\n";
					$rpc = new JSONRPC($Server);
					$roid=(integer)$para[1];
					echo "Remote OID: ".$roid."\n";
					$rpc->SetValue($roid, $value);
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

	class Feuchtigkeit_Logging extends Logging
	   {
	   private $variable;
	   private $variablename;
		private $variableLogID;
		
	   function __construct($variable)
		   {
		   //echo "Construct Motion.\n";
		   $this->variable=$variable;
		   $result=IPS_GetObject($variable);
		   $this->variablename=IPS_GetName((integer)$result["ParentID"]);
		   
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$result=$moduleManager->GetInstalledModules();

			if (isset ($result["DetectMovement"]))
				{
				$moduleManager_DM = new IPSModuleManager('CustomComponent');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				//echo "Datenverzeichnis:".$CategoryIdData."\n";
				$name="Feuchtigkeit-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($vid==false)
				   {
					$vid = IPS_CreateCategory();
   	   		IPS_SetParent($vid, $CategoryIdData);
      			IPS_SetName($vid, $name);
	      		IPS_SetInfo($vid, "this category was created by script. ");
	      		}
				$name="Feuchtigkeit-Auswertung";
				$TempAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($TempAuswertungID==false)
				   {
					$TempAuswertungID = IPS_CreateCategory();
   	   		IPS_SetParent($TempAuswertungID, $CategoryIdData);
      			IPS_SetName($TempAuswertungID, $name);
	      		IPS_SetInfo($TempAuswertungID, "this category was created by script. ");
	      		}
				if ($variable<>null)
				   {
				   /* lokale Spiegelregister aufsetzen */
	   	   	$this->variableLogID=CreateVariable($this->variablename,1,$TempAuswertungID, 10 );
	   	   	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
	   	   	IPS_SetVariableCustomProfile($this->variableLogID,'~Humidity.F');
	      		AC_SetLoggingStatus($archiveHandlerID,$this->variableLogID,true);
					AC_SetAggregationType($archiveHandlerID,$this->variableLogID,0);      /* normaler Wwert */
					IPS_ApplyChanges($archiveHandlerID);
					}
				}

		   //echo "Uebergeordnete Variable : ".$this->variablename."\n";
		   $directories=get_IPSComponentLoggerConfig();
		   $directory=$directories["HumidityLog"];
	   	mkdirtree($directory);
		   $filename=$directory.$this->variablename."_Feuchtigkeit.csv";
		   parent::__construct($filename,$vid);
	   	}

		function Feuchtigkeit_LogValue()
			{
			$result=number_format(GetValue($this->variable),2,',','.')." °C";
			SetValue($this->variableLogID,GetValue($this->variable));
			echo "Neuer Wert fuer ".$this->variablename." ist ".GetValue($this->variable)." %\n";
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			}

	   }


	/** @}*/
?>
