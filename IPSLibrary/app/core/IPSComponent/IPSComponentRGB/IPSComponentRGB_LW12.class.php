<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentRGB_Dummy.class.php
	 * @author        Wolfgang Joebstl inspiriert von Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentRGB_Dummy
    *
    * Definiert ein IPSComponentRGB_Dummy Object, das ein Dummy IPSComponentRGB Object implementiert.
    *
    * @author Wolfgang Joebstl
    * @version
    *   Version 2.50.1, 06.11.2012<br/>
    */

	IPSUtils_Include ('IPSComponentRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');

	class IPSComponentRGB_LW12 extends IPSComponentRGB {

		private $instanceId;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentRGB_Dummy Objektes
		 *
		 * @param integer $instanceId InstanceId des Dummy Devices
		 */
		public function __construct($instanceId) {
			$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
		}

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleRGB $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleRGB $module){
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
		 * @param boolean $power RGB Gerät On/Off
		 * @param integer $color RGB Farben (Hex Codierung)
		 * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
		 */
		public function SetState($power, $color, $level) {
			//echo "Hurrah hier angekommen mit Parameter : ".$power." ".$color." ".$level."\n";
			//print_r($this);
			//echo IPS_GetKernelDir()."scripts/".GetValue(IPS_GetObjectIDByName("!LW12_CLibrary",  IPS_GetParent($this->instanceId))).".ips.php";
			//require(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/LedAnsteuerung/LedAnsteuerung_Library.ips.php");
			include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\LedAnsteuerung\LedAnsteuerung_Library.ips.php");
			LW12_PowerToggle2($this->instanceId,$power);
			LW12_setDecRGB2($this->instanceId,$color);
		}

	}

	/** @}*/
?>