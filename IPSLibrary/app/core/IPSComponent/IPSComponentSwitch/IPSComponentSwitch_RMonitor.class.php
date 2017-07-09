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
	 
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_RMonitor.class.php
	 * @author        Wolfgang Jöbstl, inspiriert durch Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentSwitch_RMonitor
    *
    * Definiert eine IPSComponentSwitch_RMonitor Klasse, die ein IPSComponentSwitch Object für die Remote Steuerung des PC auf einem anderen Server implementiert.
	* Aufruf erfolgt von zB IPS-Light mit folgendem Parameter:
	*
	* IPSComponentSwitch_RMonitor,12345,http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/ 
	*
	* Teil von zusaetzlichen selbst definierten Klassen für IPS Component:
	*
	*   IPSComponentSensor:
	*   IPSModuleSensor_Remote.class.php, IPSComponentSensor_Remote.class.php"
	*   IPSModuleSensor_Counter.class.php, IPSComponentSensor_Counter.class.php"
	*   IPSModuleSensor_Feuchtigkeit.class.php, IPSComponentSensor_Feuchtigkeit.class.php"
	*   IPSModuleSensor_Motion.class.php, IPSComponentSensor_Motion.class.php"
	*   IPSModuleSensor_Temperatur.class.php, IPSComponentSensor_Temperatur.class.php"
	*
	*   IPSComponentDimmer
	*	IPSComponentDimmer_RHomematic.class.php"
	*   IPSComponentDimmer_RFS20.class.php"
	*
	*	IPSComponentRGB
	*	IPSComponentRGB_LW12.class.php"
	*
	*	IPSComponentShutter
	*	IPSComponentShutter_XHomematic.class.php"
	*
	*	IPSComponentSwitch:
	*   -------------------
	*	IPSComponentSwitch_RHomematic.class.php			local Homematic schreiben mit remote logging => obsolete, ersetzt durch Switch_Remote
	*	IPSComponentSwitch_XHomematic.class.php			Remote Homematic schreiben mit rOID und Server adresse
	*	IPSComponentSwitch_RFS20.class.php 				Remote FS20 schreiben mit rOID und Server adresse
	*	IPSComponentSwitch_XValue.class.php"
	*	IPSComponentSwitch_Value.class.php"
	*	IPSComponentSwitch_Remote.class.php"			local nichts schreiben aber remote logging des Schaltzustandes, aber alte Implementierung, homematic noch loeschen
	*	IPSComponentSwitch_Monitor.class.php			switch Monitor locally
	*	IPSComponentSwitch_RMonitor.class.php"			switch Monitor remote mit rOID und Server adresse
	*
	*	IPSComponentLogger
	*	IPSComponentLogger.class.php"
	*
	*	IPSComponentTuner
	*	IPSModuleTuner_Denon.class.php,IPSComponentTuner_Denon.class.php"
	*
	*	IPSComponentHeatControl
	*	IPSModuleHeatControl_All.class.php, IPSModuleHeatControl.class.php
	*	IPSComponentHeatControl_FS20.class.php"
	*	IPSComponentHeatControl_Homematic.class.php"
	*	IPSComponentHeatControl_HomematicIP.class.php"
	*	IPSComponentHeatControl.class.php"
    *
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	

	class IPSComponentSwitch_RMonitor extends IPSComponentSwitch {

		private $instanceId;
		private $remoteAdr;
		private $remoteOID;
		private $supportsOnTime;
		private $remServer;
		private $installedmodules;				
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_RMonitor Objektes
		 *
		 * @param zumindest eine object ID (script Adresse, var1) am Remote Server und die Server Adresse (instanceId)
		 * die Instance ID könnte auch automatisch herausgefunden werden, da die Routine zur Steuerung des monitors bei der Startpage Steuerung abgelegt ist
		 *
		 */
		public function __construct($var1=0, $instanceId=0, $supportsOnTime=true)
			{
			//echo "Remote Monitor bearbeiten. Aufruf mit ".$var1." und ".$instanceId."\n";
			//$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);  remote Adresse keine Instanz
			$this->remoteOID     = (integer)$var1;
			$this->remoteAdr     = $instanceId;
			$this->supportsOnTime = $supportsOnTime;
			
			/* für remote Logging die verfügbaren Remote Server einlesen */
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
		 * @param IPSModuleSwitch $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSwitch $module)
			{
			//echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			$module->SyncState($value, $this);
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
			return get_class($this).','.$this->instanceId;
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false)
			{
			//echo "Server ".$this->remoteAdr." ansprechen und Skript ".$this->remoteOID." aufrufen.\n";
			$rpc = new JSONRPC($this->remoteAdr);
			if ($value==true)
				{
			   	/* Monitor einschalten */
				//echo "Monitor ausschalten.\n";
				$monitor=array("Monitor" => "on");
				$rpc->IPS_RunScriptEx($this->remoteOID,$monitor);
			   }
			else
				{
			   	/* Monitor ausschalten */
			   	//echo "Monitor ausschalten.\n";
				$monitor=array("Monitor" => "off");
				$rpc->IPS_RunScriptEx($this->remoteOID,$monitor);
			   	}
			}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {

		}

	}

	/** @}*/
?>