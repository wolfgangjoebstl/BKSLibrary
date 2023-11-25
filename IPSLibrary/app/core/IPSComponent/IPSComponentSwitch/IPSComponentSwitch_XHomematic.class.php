<?php
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
     * @class IPSComponentSwitch_Homematic
     *
     * Definiert ein IPSComponentSwitch_XHomematic Object, das ein IPSComponentSwitch Object für Homematic implementiert.
     *
     * Wird von IPSLight oder IPSHeat aufgerufen und schaltet einen Remote Schalter auf einem anderen Server. Die Änderung des Schalters könnte als Event wieder gelogged werden.
     * Wird von IPSComponentSwitch_Remote erledigt. zuordnung erfolgt in remoteAccess Modul:
     *
	 * Events werden im Event Handler des IPSMessageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Remote,','IPSModuleSensor_Remote,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter (1,2,3) möglich, genutzt wenn RemoteAccess installiert ist:
	 * der erste Zusatzparameter aus der obigen Konfig sind Pärchen von Remoteserver und remoteOIDs
	 * in der RemoteAccessServerTable sind alle erreichbaren Log Remote Server aufgelistet, abgeleitet aus der Server Config und dem Status der Erreichbarkeit
	 * für alle erreichbaren Server wird auch die remote OID mit dem Wert beschrieben 
     *
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 *
	 * Hier, da für IPSLight im Einsatz erfolgt ein allgemeines Handling, Klasse macht kein lokales Logging und auch keine weitere Verarbeitung
	 *
     * Es gibt eine normale Homematic Switch Klasse. Damit wird nur das lokale Homematic Objekt geschaltet.
     * Sensoren übertragen den Wert auch auf einen oder mehrere Logging Server 
	 *
     *
     * @author Wolfgang Joebstl, inspiriert von Andreas Brauneis
     * 
     ****/

	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	class IPSComponentSwitch_XHomematic extends IPSComponentSwitch {

		private $instanceId;
		private $supportsOnTime;
		private $rpcADR;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Homematic Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param integer $supportsOnTime spezifiziert ob das Homematic Device eine ONTIME unterstützt
		 */
		public function __construct($instanceId, $rpcADR, $supportsOnTime=true) {
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
			$this->supportsOnTime = $supportsOnTime;
			$this->rpcADR = $rpcADR;
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
		public function HandleEvent($variable, $value, IPSModuleSwitch $module){
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
		public function SetState($value, $onTime=false) {
			//echo "Adresse:".$this->rpcADR."\n";
			$rpc = new JSONRPC($this->rpcADR);

			if ($onTime!==false and $value and $this->supportsOnTime===true) 
				$rpc->HM_WriteValueFloat($this->instanceId, "ON_TIME", $onTime);  
			
			$rpc->HM_WriteValueBoolean($this->instanceId, "STATE", $value);
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {
			GetValue(IPS_GetVariableIDByIdent('STATE', $this->instanceId));
		}

	}

	/** @}*/
?>