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

	IPSUtils_Include ('IPSModule.class.php', 'IPSLibrary::app::core::IPSComponent');
	
	abstract class IPSModuleHeatControl extends IPSLibraryModule {

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation von Sensorwerten mit Modulen
		 *
		 * @param string $value Sensorwert
		 * @param IPSComponentSensor $component Sensor Komponente
		 */
		abstract public function SyncButton($value, IPSComponentHeatControl $component);

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