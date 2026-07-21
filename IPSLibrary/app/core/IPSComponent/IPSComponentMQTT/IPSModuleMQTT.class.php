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
	 * Definiert ein IPSModuleMQTT Object, das als Wrapper für MQTT Client Geräte in der IPSLibrary
	 * verwendet werden kann.
     *
     * Nur ein Script das referenziert wird
	 *
	 * @author Andreas Brauneis, Wolfgang Joebstl
	 */

	IPSUtils_Include ('IPSModule.class.php', 'IPSLibrary::app::core::IPSComponent');

	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	


	abstract class IPSModuleMQTT extends IPSLibraryModule {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation des aktuellen Zustands. Wird nicht benötigt. Aber gefordert. 
		 *
		 * @param boolean $state aktueller Status des Gerätes
		 */
		abstract public function SyncState($state, IPSComponentMQTT $componentToSync);

	}


