<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentRGB_Homematic.class.php
	 * @author        Wolfgang Joebstl inspiriert von Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentRGB_Homematic
    *
    * Definiert ein IPSComponentRGB_Homematic Object, das ein Dummy IPSComponentRGB Object implementiert.
    *
    * @author Wolfgang Joebstl
    * @version
    *   Version 2.50.1, 06.11.2012<br/>
    */

	IPSUtils_Include ('IPSComponentRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');

	class IPSComponentRGB_Homematic extends IPSComponentRGB {

		private $instanceId;
			
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentRGB_Homematic Objektes
		 *
		 * @param integer $instanceId InstanceId des Dummy Devices
		 */
		public function __construct($instanceId) {
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
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
		 * @param boolean $power RGB Gerät On/Off
		 * @param integer $color RGB Farben (Hex Codierung)
		 * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
		 */
		public function SetState($power, $color, $level) {			
			if (!$power) {
				HM_WriteValueFloat($this->instanceId, "LEVEL", 0);
			} else {
				$levelHM = $level / 100;
				HM_WriteValueFloat($this->instanceId, "LEVEL", $levelHM);
			}   
            $colorHM=$this->RGBtoHSV($color);                // RGB Umrechnung auf HUE und SATURATION
            //echo "SetState  $color $level \n";
            HM_WriteValueInteger($this->instanceId, "HUE", $colorHM[0]);        
            //HM_WriteValueFloat($this->instanceId, "SATURATION", $colorHM[1]/100);    // nicht gleichzeitig, entweder LEVEL und HUE oder HUE und Saturation     
            //HM_WriteValueFloat($this->instanceId, "LEVEL", $colorHM[2]/100);
 
			//$result=@HM_WriteValueBoolean($this->instanceId, "STATE", $value);
            //if ($result===false) IPSLogger_Err(__file__, 'IPSComponentSwitch_Homematic: failed SetState für InstanceID '.$this->instanceId.' ('.IPS_GetName(IPS_GetParent($this->instanceId)).'.'.IPS_GetName($this->instanceId).') mit Wert '.($value?"Ein":"Aus"));

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


    function RGBtoHSV($color)    // RGB values:    0-255, 0-255, 0-255
        {                            // HSV values:    0-360, 0-100, 0-100
        $rotDec = (($color >> 16) & 0xFF);
		$gruenDec = (($color >> 8) & 0xFF);
		$blauDec = (($color >> 0) & 0xFF); 
        // Convert the RGB byte-values to percentages
        $R = ($rotDec / 255);
        $G = ($gruenDec / 255);
        $B = ($blauDec / 255);

        // Calculate a few basic values, the maximum value of R,G,B, the
        //   minimum value, and the difference of the two (chroma).
        $maxRGB = max($R, $G, $B);
        $minRGB = min($R, $G, $B);
        $chroma = $maxRGB - $minRGB;

        // Value (also called Brightness) is the easiest component to calculate,
        //   and is simply the highest value among the R,G,B components.
        // We multiply by 100 to turn the decimal into a readable percent value.
        $computedV = 100 * $maxRGB;

        // Special case if hueless (equal parts RGB make black, white, or grays)
        // Note that Hue is technically undefined when chroma is zero, as
        //   attempting to calculate it would cause division by zero (see
        //   below), so most applications simply substitute a Hue of zero.
        // Saturation will always be zero in this case, see below for details.
        if ($chroma == 0)
            return array(0, 0, $computedV);

        // Saturation is also simple to compute, and is simply the chroma
        //   over the Value (or Brightness)
        // Again, multiplied by 100 to get a percentage.
        $computedS = 100 * ($chroma / $maxRGB);

        // Calculate Hue component
        // Hue is calculated on the "chromacity plane", which is represented
        //   as a 2D hexagon, divided into six 60-degree sectors. We calculate
        //   the bisecting angle as a value 0 <= x < 6, that represents which
        //   portion of which sector the line falls on.
        if ($R == $minRGB)
            $h = 3 - (($G - $B) / $chroma);
        elseif ($B == $minRGB)
            $h = 1 - (($R - $G) / $chroma);
        else // $G == $minRGB
            $h = 5 - (($B - $R) / $chroma);

        // After we have the sector position, we multiply it by the size of
        //   each sector's arc (60 degrees) to obtain the angle in degrees.
        $computedH = 60 * $h;

        return array($computedH, $computedS, $computedV);
        }

	}

	/** @}*/
?>