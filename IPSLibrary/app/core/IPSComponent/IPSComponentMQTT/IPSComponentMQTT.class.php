<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentMQTT.class.php
	 * @author        Andreas Brauneis, Wolfgang Jöbstl
	 *
	 *
	 */

   /**
    * @class IPSComponentMQTT
    *
    * Definiert ein IPSComponentMQTT Object, das als Wrapper für MQTT Client Geräte verschiedener Hersteller 
    * verwendet werden kann.
    *
    * @author Andreas Brauneis, Wolfgang Jöbstl
    * @version
    * Version 2.50.1, 31.01.2012<br/>
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	abstract class IPSComponentMQTT extends IPSComponent {

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten. Die Events werden für das MQTT Client Geräte Value registriert.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleRGB $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		abstract public function HandleEvent($variable, $value, IPSModuleRGB $module);

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $power RGB Gerät On/Off
		 * @param integer $color RGB Farben (Hex Codierung)
		 * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
		 */
		abstract public function SetState($power, $color, $level);

	}

	/** @}*/
?>