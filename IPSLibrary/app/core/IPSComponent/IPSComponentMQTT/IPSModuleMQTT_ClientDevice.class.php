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
	 * @class IPSModuleMQTT
	 *
	 * Definiert ein IPSModuleMQTT Object, das als Wrapper für MQTT Clientgeräte in der IPSLibrary
	 * verwendet werden kann.
     *
     *
     * relevant to sync external data with internal representation
     * an external change will be propagated to the internal variables that are displayed in webfront
     * old style webfron was used to harmonize different ways of controlling devices into one way of displaying them
     * new style tiles Webfront changes the way how it is done and displays the individual devices directly 
     * so as future guide, display the devices directly and sync the changes to the internal representation for interoperability
     *
     *
	 *
	 * @author Andreas Brauneis, Wolfgang Joebstl
	 */


    IPSUtils_Include ('IPSModuleRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');

	class IPSModuleMQTT_ClientDevice extends IPSModuleMQTT {

        protected $installedModules;

        public function __construct()
            {
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedModules = $moduleManager->VersionHandler()->GetInstalledModules();   
            }

		/* IPSModuleMQTT_ClientDevice::SyncState
		 * Ermöglicht die Synchronisation einer RGB basierenden Beleuchtung zu IPSHeat
         * Bedeutet eine externe Änderung der Parameter wird in IPSymcon und im Webfront/Kachel nachgezogen
		 * 
         * prüft ob Modul Stromheizung installiert ist
         * Die Parameter in der Configuration werden ausgelesen, es sind mehrere Parameter, aber nicht alle wie bei RemoteAccess
         * Parameter 1 ist die Instamz, für diesen Parameter wird die Stromheizung Konfiguration durchsucht
		 * Der Index der Konfigiuration, also nicht der Name, oder der Name der Instamz werden für SynchronizeSwitch herangezogen
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($state, IPSComponentMQTT $componentToSync, $debug=false) 
			{
            if ($debug) echo "IPSModuleMQTT_ClientDevice::SyncState aufgerufen.\n";

			}	

	}

