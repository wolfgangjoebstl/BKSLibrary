<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatControl_Homematic.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatControl_Data
    *
    * Definiert ein IPSComponentHeatControl_Data Object, das ein IPSComponentHeatControl Object für Homematic implementiert.
    *
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatControl.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');

	class IPSComponentHeatControl_Data extends IPSComponentHeatControl 
		{

		protected $tempObject;
		protected $tempValue;
		protected $installedmodules;

		protected $RemoteOID;		/* Liste der RemoteAccess server, Server Kurzname getrennt von OID durch : */
		protected $remServer;		/* Liste der Urls und der Kurznamen */


		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentheatControl_Data Objektes
		 *
		 * legt die Remote Server an, die wenn RemoteAccess Modul installiert ist reported werden müssen
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern
		 * die weiteren Variablen werden nicht benötigt	
		 *
		 * die Module sind eigentlich gleich für alle unterschiedlichen Datenobjekte (Data, FS20, Homematic, HoimematicIP)
		 *	 
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $reverseControl Reverse Ansteuerung des Devices
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null) 
			{
			$this->RemoteOID    = $var1;
			$this->tempObject   = $lightObject;
			$this->tempValue    = $lightValue;
			
			echo "construct IPSComponentHeatControl_Data with parameter ".$this->RemoteOID."  ".$this->tempObject."  ".$this->tempValue."\n";
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
			
		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * hier eigentlich nur das Logging aufrufen und die Speicherung des Wertes auf den remoteAccess Servern durchführen
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IIPSModuleHeatControl $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleHeatControl $module)
			{
			echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatControl Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new HeatControl_Logging($variable,IPS_GetName($variable));
			$result=$log->HeatControl_LogValue($value);
			
			$this->WriteValueRemote($value);
			}

		}

	/** @}*/
?>