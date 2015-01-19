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
			
	class IPSComponentSensor_Motion extends IPSComponentSensor {


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
		public function __construct($var1=null, $lightObject=null, $lightValue=null) {
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
			echo "Bewegungs Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			$log=new Motion_Logging($variable);
			$result=$log->Motion_LogValue();
			
			foreach ($this->remServer as $Server)
				{
				echo "Server : ".$Server."\n";
				$rpc = new JSONRPC($Server);
				echo "Remote OID: ".$this->RemoteOID."\n";
				$roid=(integer)$this->RemoteOID;
				$rpc->SetValue($roid, $value);
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
	
	class Motion_Logging extends Logging
	   {
	   
	   function __construct($variable)
		   {

			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$result=$moduleManager->GetInstalledModules();
			if (isset ($result["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				$moduleManager_DM = new IPSModuleManager('CustomComponent');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				echo "Datenverzeichnis:".$CategoryIdData."\n";
				$name="Motion-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($vid==false)
				   {
					$vid = IPS_CreateCategory();
   	   		IPS_SetParent($vid, $CategoryIdData);
      			IPS_SetName($vid, $name);
	      		IPS_SetInfo($vid, "this category was created by script. ");
	      		}
				$name="Motion-Detect";
				$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($mdID==false)
				   {
					$mdID = IPS_CreateCategory();
   	   		IPS_SetParent($mdID, $CategoryIdData);
      			IPS_SetName($mdID, $name);
	      		IPS_SetInfo($mdID, "this category was created by script. ");
	      		}
	      		
			   echo "Construct Motion.\n";
		   	$this->variable=$variable;
			   $result=IPS_GetObject($variable);
			   $this->variablename=IPS_GetName((integer)$result["ParentID"]);
			   echo "Uebergeordnete Variable : ".$this->variablename."\n";
		   	$directories=get_IPSComponentLoggerConfig();
			   $directory=$directories["MotionLog"];
		   	mkdirtree($directory);
			   $filename=$directory.$this->variablename."_Motion.csv";
			   
  	      	echo "Ereignisspeicher aufsetzen \n";
	      	CreateVariable($this->variablename."Ereignisspeicher",3,$mdID, 10 );
				$this->variablename."Ereignisspeicher"=$mdID;
		   	parent::__construct($filename,$vid);
				}
	   	}
	   
		function Motion_LogValue()
			{
			if (IPS_GetName($this->variable)=="MOTION")
				{
				if (GetValue($this->variable))
					{
					$result="Bewegung";
					}
				else
					{
					$result="Ruhe";
					}
				}
			else
				{
				if (GetValue($this->variable))
					{
					$result="Offen";
					}
				else
					{
					$result="Geschlossen";
					}
				}

			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Status ".$result);
			}
	   
	   }


	/** @}*/
?>
