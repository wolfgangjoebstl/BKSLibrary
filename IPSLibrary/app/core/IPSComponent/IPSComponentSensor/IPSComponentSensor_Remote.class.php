<?

   /**
    * @class IPSComponentSensor_Temperatur
    *
    * Definiert ein IPSComponentSensor_Temperatur Object, das ein IPSComponentSensor Object f�r einen Sensor implementiert.
    *
    * @author Wolfgang J�bstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			
	class IPSComponentSensor_Remote extends IPSComponentSensor {


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
		 * @param string $tempValue Wert f�r Beleuchtungs �nderung
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
		 * @param integer $variable ID der ausl�senden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleSensor $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSensor $module)
			{
			echo "Movement Message Handler f�r VariableID : ".$variable." mit Wert : ".$value." \n";
			if ($this->RemoteOID != Null)
			   {
				//print_r($this);
				//print_r($module);
				//echo "-----Hier jetzt alles programmieren was bei Ver�nderung passieren soll:\n";
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
			}

		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu ben�tzt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this);
		}

	}

	/** @}*/
?>
