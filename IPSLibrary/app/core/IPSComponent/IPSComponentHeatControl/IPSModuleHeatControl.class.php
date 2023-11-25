<?php
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
	 * ACHTUNG Variablennamen muessen nach einem bestimmten System angelegt werden
	 *
	 * abstract class IPSModuleHeatControl in File IPSModuleHeatControl.class.php 
	 * class muss dann einen _ im Filenamen besitzen zB
	 * class IPSModuleHeatControl_All in File IPSModuleHeatControl_All.class.php
	 *
	 * daraus berechnet sich der Component (tausche Module mit Component)
	 * abstract class IPSComponentHeatControl in File IPSComponentHeatControl.class.php
	 * class IPSComponentHeatControl_FS20 in File IPSComponentHeatControl_FS20.class.php
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