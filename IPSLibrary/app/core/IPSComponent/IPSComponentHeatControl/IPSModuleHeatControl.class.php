<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSensor_HeatControl.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModuleSensor_HeatControl
	 *
	 * Definiert ein IPSModuleSensor Object, das als Wrapper für Sensoren in der IPSLibrary
	 * verwendet werden kann.
	 *
	 */

	abstract class IPSModuleHeatControl extends IPSLibraryModule {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation des aktuellen Zustands 
		 *
		 * @param boolean $state aktueller Status des Gerätes
		 */
		abstract public function SyncState($state, IPSComponentHeatControl $componentToSync);

	}

	/** @}*/
?>