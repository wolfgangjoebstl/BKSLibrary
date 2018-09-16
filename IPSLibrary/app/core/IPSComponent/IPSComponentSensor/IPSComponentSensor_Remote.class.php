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
     * @class IPSComponentSensor_remote
     *
     * Definiert ein IPSComponentSensor_Remote Object, das ein IPSComponentSensor Object für einen beliebigen Sensor implementiert.
     *
	 * Events werden im Event Handler des IPSMessageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID udn Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_TRemote,','IPSModuleSensor_Remote,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter möglich.
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 *
	 * allgemeines Handling, macht kein lokales Logging und keine weitere Verarbeitung
	 *
	 * Wenn RemoteAccess installiert ist:
	 * der erste Zusatzparameter aus der obigen Konfig sind Pärchen von Remoteserver und remoteOIDs
	 * in der RemoteAccessServerTable sind alle erreichbaren Log Remote Server aufgelistet, abgeleitet aus der Server Config und dem Status der Erreichbarkeit
	 * für alle erreichbaren Server wird auch die remote OID mit dem Wert beschrieben 
	 *
	 * Authomatische Registrierung für:
	 *   Energiewerte
	 *   Mobilfunkguthaben
	 *   Taster und Schalter wenn Geber nicht Actuator
	 *	 
     * @author Wolfgang Jöbstl
     * @version
     *   Version 2.50.1, 09.06.2012<br/>
     ****/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	
	
			
	class IPSComponentSensor_Remote extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;

		private $remServer;
		
		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * legt die Remote Server aus $var1 an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 *		 
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
			//echo "IPSComponentSensor_Remote: Construct Remote Sensor with ".$var1.".\n";				
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;

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
			echo "Genereller Remote Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			if ($this->RemoteOID != Null)
			   {
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
						$Server=$this->remServer[$para[0]]["Url"];
						if ($this->remServer[$para[0]]["Status"]==true)
							{
							$rpc = new JSONRPC($Server);
							$roid=(integer)$para[1];
							//echo "Server : ".$Server." Remote OID: ".$roid."\n";
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

	/** @}*/
?>