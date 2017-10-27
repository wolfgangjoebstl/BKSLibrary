<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleHeatSet.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModuleHeatSet
	 *
	 * Definiert ein IPSModuleSensor Object, das als Wrapper für Sensoren in der IPSLibrary
	 * verwendet werden kann.
	 *
	 * ACHTUNG Variablennamen muessen nach einem bestimmten System angelegt werden
	 *
	 * abstract class IPSModuleHeatSet in File IPSModuleHeatSet.class.php 
	 * class muss dann einen _ im Filenamen besitzen zB
	 * class IPSModuleHeatSet_All in File IPSModuleHeatSet_All.class.php
	 *
	 * daraus berechnet sich der Component (tausche Module mit Component)
	 * abstract class IPSComponentHeatSet in File IPSComponentHeatSet.class.php
	 * class IPSComponentHeatSet_FS20 in File IPSComponentHeatSet_FS20.class.php
	 */

	IPSUtils_Include ('IPSModule.class.php', 'IPSLibrary::app::core::IPSComponent');
	
	abstract class IPSModuleHeatSet extends IPSLibraryModule {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation von Sensorwerten mit Modulen
		 *
		 * @param string $value Sensorwert
		 * @param IPSComponentSensor $component Sensor Komponente
		 */
		abstract public function SyncButton($value, IPSComponentHeatSet $component);

		/**
		 * @public
		 *
		 * Ermöglicht das Verarbeiten eines Taster Signals
		 *
		 */
		abstract public function ExecuteButton();


	}

	/** @}*/
?>