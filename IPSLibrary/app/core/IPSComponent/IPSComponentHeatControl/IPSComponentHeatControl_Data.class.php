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

		protected 	$tempValue;
		protected 	$installedmodules;
		protected	$instanceId;			/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */
		
		protected 	$RemoteOID;		/* Liste der RemoteAccess server, Server Kurzname getrennt von OID durch : */
		protected 	$remServer;		/* Liste der Urls und der Kurznamen */
		private 		$rpcADR;			/* mit der Parametrierung übergegebene Server Shortnames und OIDs, getrennt durch : und für jeden Eintrag durch ;


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
		public function __construct($instanceId=null, $rpcADR="", $lightValue=null) 
			{
			if (strpos($instanceId,":") !== false ) 
				{	/* ROID Angabe auf der ersten Position */
				$this->rpcADR 			= $rpcADR;				
				$this->RemoteOID  	= $instanceId;
				$this->instanceId		= null;
				}
			else
				{	/* keine ROID Angabe auf der ersten Position, kommt von IPSHeat */
				$this->instanceId		= $instanceId;
				if (strpos($rpcADR,":") !== false )
					{ 	/* ROID Angabe auf der zweiten Position */			
					$this->RemoteOID  	= $rpcADR;
					$this->rpcADR 			= $rpcADR;
					}
				else
					{	
					$this->RemoteOID  	= null;
					$this->rpcADR 			= $rpcADR;
					}				
				}
			$this->tempValue  	= $lightValue;
			
			echo "construct IPSComponentHeatControl_Data with parameter ".$this->RemoteOID."  ".$this->tempObject."  ".$this->tempValue."\n";
			$this->remoteServerSet();
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