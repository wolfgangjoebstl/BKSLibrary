<?php
    /**@addtogroup ipscomponent
     * @{
     *
      *
     * @file          IPSComponentRGB_PHUE.class.php
     * @author        Wolfgang Joebstl, inspiriert von Andreas Brauneis
     *
     *
     */

	/**
	 * @class IPSComponentRGB_PHUE
	 *
	 * Definiert ein IPSComponentRGB_PHUE Object, das ein IPSComponentRGB Object fuer PhilipsHUE implementiert.
	 *
	 * verwendet nun das IPSModul "Philips HUE" , damit sind keine neue Anpassungen oder Adaptierungen notwendig
     *
     * Versionsgeschichte:
     * Dann sind Anpassungen für das SymconHUE Modul erfolgt, einfachere Ansteuerung der Hue Funktionen über die Bridge und nicht mehr direkt
	 * Routinen vom initialen HUE Modul (direkte Adressierung) sind zwar noch vorhanden, werden aber nicht mehr verwendet
     * cgpoint class wird nicht mehr verwendet, trotzdem in der Funktion beinhaltet, damit keine Überschneidungen mit dem HUE Modul entstehen. umbennannt auf cgpoint2
	 *
	 *  es wird nur construct und setState aufgerufen
     *
     * PHUE_AlertSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich einen Alarm für eine Lampe / Gruppe zu setzen
     * PHUE_CTSet($InstanceID, $Value)              Mit dieser Funktion ist es möglich die Farbtemperatur der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Integer angegeben werden.
     * PHUE_ColorSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich die Farbe der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Hex angegeben werden.
     * PHUE_DimSet($InstanceID, $Value)             Mit dieser Funktion ist es möglich das Gerät bzw. die Gruppe zu dimmen.
     * PHUE_EffectSet($InstanceID, $Value)          Mit dieser Funktion ist es möglich einen Effekt für die Lampe bzw. Gruppe zu aktiveren.
     * PHUE_GetState($InstanceID)                   Mit dieser Funktion ist es möglich den aktuellen Status der Lampe / Gruppe abzufragen.
     * PHUE_SceneSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich eine Szene für die Gruppe zu aktiveren.
     * PHUE_SwitchMode($InstanceID, $Value)         Mit dieser Funktion ist es möglich das Gerät ein- bzw. auszuschalten.
     *
	 *
     * __construct
     * HandleEvent
     * GetComponentParams
     * SetStateHUE
     * SetState
     * SetAlert
     *
     * calculateXY
     * getColorPointsForModel
     *
     *
	 */

	IPSUtils_Include ('IPSComponentRGB.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');
	IPSUtils_Include ("IPSLogger.inc.php", "IPSLibrary::app::core::IPSLogger");

	class IPSComponentRGB_PHUE extends IPSComponentRGB {

		private $lampOID;
			
    
        /**
         * @public
         *
         * Initialisierung eines IPSComponentRGB_PHUE Objektes
         * basiert nun auf dem Philps HUE Modul
         * vorher war es das Symcon HUE Modul und ein proprietäres Modul. Die Bridge bzw. vorher die Schlüssel müssen nicht mehr übergeben werden, sind in der Config ooder in der verbundenen Bridge Instanz 
		 * Bridge ID und alles IP und key spezielle ist damit bereits abgedeckt
		 *
		 * HueBridge ist die I/O Instanz bei der die Parameter der Hue Bridge hinterlegt sind.
		 *
		 *
		 *
         */
		public function __construct($lampOID) 
			{
			$this->lampOID = $lampOID;
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
            //return get_class($this).','.$this->bridgeIP.','.$this->hueKey.','.$this->lampNr.','.$this->modelID;
			return (get_class($this).','.$this->lampOID.',');
        }

        
        /**
         * @public  DEPRICIATED
         *
         * @brief Zustand Setzen 
         *
         * @param boolean $power RGB Gerät On/Off
         * @param integer $color RGB Farben (Hex Codierung)
         * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
         */
        public function SetStateHUE($power, $color, $level) {
            if (!$power) {
                $cmd = '"on":false';  
            } else {

			   $rotDec = (($color >> 16) & 0xFF);
			   $gruenDec = (($color >> 8) & 0xFF);
			   $blauDec = (($color >> 0) & 0xFF); 
			   $color_array = array($rotDec,$gruenDec,$blauDec);
			   
			   $modelID = $this->modelID;
			   
			   //Convert RGB to XY values
			   $values = $this->calculateXY($color_array, $modelID);
			  
			   //IPSLight is using percentage in variable Level, Hue is using [0..255] 
			   $level = round($level * 2.55);
			   $cmd 	= '"bri":'.$level.', "xy":['.$values->x.','.$values->y.'], "on":true'; 
			   
            }
			
			$type	 = 'Lights'; //Type of Command
			$request = 'PUT';	 //Type of Request
            
			//Send command to Hue lamp
			//$this->hue_SendLampCommand($type, $request, $cmd);
			HUE_SetValue($this->lampOID, "STATE",$power);
			HUE_SetValue($this->lampOID, "COLOR",$color);
			HUE_SetValue($this->lampOID, "BRIGHTNESS",$level);
        }

        /**
         * @public
         *
         * @brief Zustand Setzen 
         *
         * mit Ambience=true wird $color zu Farbtemperatur in mired
         * mit 2 Parametern wird es zu einer Philips Dimmer Ledlampe
         *
         *
         * @param boolean $power RGB Gerät On/Off
         * @param integer $color RGB Farben (Hex Codierung)
         * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
         */
		public function SetState($power, $color, $level=512, $ambience=false) 
			{
            $debug=true;
			if (!$power) 
				{
			    if ($debug) echo "IPSComponentRGB_HUE SetState mit Power ".($power?"Ein":"Aus")."\n";
				//HUE_SetValue($this->lampOID, "STATE",$power);
                PHUE_SwitchMode($this->lampOID, $power);
				} 
			elseif ($ambience)                      // als Ambience ELD Lampe aufgerufen
				{
				//IPSLight is using percentage in variable Level, Hue is using [0..255] 
    			if ($debug) echo "IPSComponentRGB_HUE SetState mit Power ".($power?"Ein":"Aus")."  Mired $color  Level $level  Typ ".($ambience?"Ambience":"RGB")."    \n";
				$level = round($level * 2.54);
                PHUE_SwitchMode($this->lampOID, $power);
                PHUE_CTSet($this->lampOID, $color);                     // geht das nicht mehr ?
                PHUE_DimSet($this->lampOID, $level);
				//echo "Level:".$level."\n";				
				}
			elseif ($level==512)	                // als Dimmer aufgerufen
                {
                $level = round($color * 2.54);
    			if ($debug) echo "IPSComponentRGB_HUE SetState mit Power ".($power?"Ein":"Aus")."  Level $color ($level)   \n";
                PHUE_SwitchMode($this->lampOID, $power);
                PHUE_DimSet($this->lampOID, $level);
                }
            else                                    // als RGB aufgerufen
				{
    			if ($debug) echo "IPSComponentRGB_HUE SetState mit Power ".($power?"Ein":"Aus")."  Color ".dechex($color)."   Level ".$level."  Typ ".($ambience?"Ambience":"RGB")."    \n";
				$level = round($level * 2.54);
                PHUE_SwitchMode($this->lampOID, $power);
                PHUE_ColorSet($this->lampOID, $color);
                PHUE_DimSet($this->lampOID, $level);
				//echo "IPSComponentRGB_PHUE SetState mit Power ".($power?"Ein":"Aus")."      \n";
				//echo "IPSComponentRGB_PHUE SetState mit  Color ".dechex($color)." \n";
				//echo "IPSComponentRGB_PHUE SetState mit Level ".$level."      \n";

                /*
				$rotDec = (($color >> 16) & 0xFF);
				$gruenDec = (($color >> 8) & 0xFF);
				$blauDec = (($color >> 0) & 0xFF); 
				$color_array = array($rotDec,$gruenDec,$blauDec);
			   
				$modelID = $this->modelID;
			   
			   //Convert RGB to XY values
				$values = $this->calculateXY($color_array, $modelID);
			  
				//IPSLight is using percentage in variable Level, Hue is using [0..255] 
				$level = round($level * 2.54);
				$cmd 	= '"bri":'.$level.', "xy":['.$values->x.','.$values->y.'], "on":true'; 
				HUE_SetValue($this->lampOID, "STATE",$power);
				HUE_SetValue($this->lampOID, "COLOR",$color);
				HUE_SetValue($this->lampOID, "BRIGHTNESS",$level);
                */
				}
		    }
				

		/**
		 *  @brief Sets the alert state. 'select' blinks once, 'lselect' blinks repeatedly, 'none' turns off blinking
		 *  
		 */
		public function SetAlert( $alert_type = 'select' ) 
            {
            PHUE_AlertSet($this->lampOID, $alert_type);
            /*
			 $type	 	= 'Lights'; //Type of Command
			 $request 	= 'PUT';	 //Type of Request
             $cmd 		= '"alert":"'.$alert_type.'"';
             $this->hue_SendLampCommand($type, $request, $cmd);		//Send command to Hue lamp
             */
		    }
		
		/**
		 *  @brief Converts colour value from RGB to XY 
		 *  
		 *  @param [in] $color Color in RGB
		 *  @param [in] $model Philips lamp model
		 *  @return XY value
		 */
		private function calculateXY($color, $model) {
		
			// Get the RGB values from color object and convert them to be between 0 and 1.
			$red = round($color[0] / 255,2);
			$green = round($color[1] / 255,2);
			$blue = round($color[2] / 255,2);

			// Apply a gamma correction to the RGB values
			$r = ($red > 0.04045) ? pow(($red + 0.055) / (1.0 + 0.055), 2.4) : ($red / 12.92);
			$g = ($green > 0.04045) ? pow(($green + 0.055) / (1.0 + 0.055), 2.4) : ($green / 12.92);
			$b = ($blue > 0.04045) ? pow(($blue + 0.055) / (1.0 + 0.055), 2.4) : ($blue / 12.92);

			// Convert the RGB values to XYZ using the Wide RGB D65 conversion formula
			$X = $r * 0.649926 + $g * 0.103455 + $b * 0.197109;
			$Y = $r * 0.234327 + $g * 0.743075 + $b * 0.022598;
			$Z = $r * 0.0000000 + $g * 0.053077 + $b * 1.035763;
			
			// Calculate the xy values from the XYZ values
			if($X==0 && $Y ==0 && $Z ==0) $Z = 0.1;
			
			$cx  = $X / ($X + $Y + $Z);
			$cy  = $Y / ($X + $Y + $Z);
			if(is_nan($cx)) $cx = 0.0;
			if(is_nan($cy)) $cy = 0.0;

			// Check if the found xy value is within the color gamut of the light
			$xyPoint = new cgpoint2($cx, $cy);
			$colorPoints = $this->getColorPointsForModel($model);
			$inReachOfLamps = $this->checkPointInLampsReach($xyPoint, $colorPoints);

			if(!$inReachOfLamps)
			{
			    // Calculate the closest point on the color gamut triangle and use that as xy value
				$pAB = $this->getClosestPointToPoints($colorPoints[0], $colorPoints[1], $xyPoint);
				$pAC = $this->getClosestPointToPoints($colorPoints[2], $colorPoints[0], $xyPoint);
				$pBC = $this->getClosestPointToPoints($colorPoints[1], $colorPoints[2], $xyPoint);

				$dAB = $this->getDistanceBetweenTwoPoints($xyPoint, $pAB);
				$dAC = $this->getDistanceBetweenTwoPoints($xyPoint, $pAC);
				$dBC = $this->getDistanceBetweenTwoPoints($xyPoint, $pBC);

				$lowest = $dAB;
				$closestPoint = $pAB;

				if($dAC < $lowest)
				{
					$lowest = $dAC;
					$closestPoint = $pAC;
				}
				if($dBC < $lowest)
				{
					$lowest = $dBC;
					$closestPoint = $pBC;
				}

				$cx = $closestPoint->x;
				$cy = $closestPoint->y;
			}
			return new cgpoint2($cx, $cy);
		}
		
        /**
		 *  @brief Returns the color gamut of a specific Philips light model
		 *  
		 *  @param [in] $model ID of the lamp model
		 *  @return Array with color gamut
		 *  
		 *  @details 
		 *  
		 *  Following models are supported:
		 *  
		 *  Hue
         *   "LCT001": Hue A19 
         *   "LCT002": Hue BR30 
         *   "LCT003": Hue GU10
		 *	LivingColors
		 *	 "LLC001": Monet, Renoir, Mondriaan (gen II) 
         *   "LLC005": Bloom (gen II) 
         *   "LLC006": Iris (gen III) 
         *   "LLC007": Bloom, Aura (gen III) 
         *   "LLC011": Hue Bloom 
         *   "LLC012": Hue Bloom 
         *   "LLC013": Storylight 
         *   "LST001": Light Strips 
         * 
		 */
		private function getColorPointsForModel($model) {
			$colorPoints = array();
			$hueBulbs = array("LCT001","LCT002","LCT003");
			$livingColors = array("LLC001","LLC005","LLC006","LLC007","LLC011","LLC012","LLC013","LST001");

			if(in_array($model, $hueBulbs))
			{
				array_push($colorPoints, new cgpoint2(0.674,0.322));
				array_push($colorPoints, new cgpoint2(0.408,0.517));
				array_push($colorPoints, new cgpoint2(0.168,0.041));
			}
			else if(in_array($model, $livingColors))
			{
				array_push($colorPoints, new cgpoint2(0.703,0.296));
				array_push($colorPoints, new cgpoint2(0.214,0.709));
				array_push($colorPoints, new cgpoint2(0.139,0.081));
			}
			else
			{
				array_push($colorPoints, new cgpoint2(1.0,0.0));
				array_push($colorPoints, new cgpoint2(0.0,1.0));
				array_push($colorPoints, new cgpoint2(0.0,0.0));
			}
			
			return $colorPoints;
		}

		/**
		 * @brief Find the distance between two points.
		 *
		 * @param one
		 * @param two
		 * @return the distance between point one and two
		 */
		private function getDistanceBetweenTwoPoints($one, $two) {
		
			$dx = $one->x - $two->x;
			$dy = $one->y - $two->y;
			$dist = sqrt($dx * $dx + $dy * $dy);
			return $dist;
		}
		
		/**
		 *  @brief Find the closest point on a line. This point will be within reach of the lamp.
		 *
		 * @param A the point where the line starts
		 * @param B the point where the line ends
		 * @param P the point which is close to a line.
		 * @return the point which is on the line.
		 */
		private function getClosestPointToPoints($A, $B, $P) {
		
			$AP = new cgpoint2($P->x - $A->x, $P->y - $A->y);
			$AB = new cgpoint2($B->x - $A->x, $B->y - $A->y);
			$ab2 = $AB->x * $AB->x + $AB->y * $AB->y;
			$ap_ab = $AP->x * $AB->x + $AP->y * $AB->y;

			$t = $ap_ab / $ab2;
			if($t < 0.0)
			{
				$t = 0.0;
			}
			else if($t > 1.0)
			{
				$t = 1.0;
			}
			$newPoint = new cgpoint2($A->x + $AB->x * $t, $A->y + $AB->y * $t);
			return $newPoint;
		}

		/**
		 *  @brief Calculates crossProduct of two 2D vectors / points
		 * 
		 * @param p1 first point used as vector
		 * @param p2 second point used as vector
		 * @return crossProduct of vectors
		 *  
		 */
		private function getCrossProduct($p1, $p2) {
			return ($p1->x * $p2->y - $p1->y * $p2->x);
		}

		/**
		 * @brief Method to see if the given XY value is within the reach of the lamps.
		 *
		 * @param p the point containing the X,Y value
		 * @return true if within reach, false otherwise.
		 *  
		 */
		private function checkPointInLampsReach($p, $colorPoints) {
		
			$red = $colorPoints[0];
			$green = $colorPoints[1];
			$blue = $colorPoints[2];
			$grx =$green->x - $red->x;
			$gry =$green->y - $red->y;
			$brx =$blue->x - $red->x;
			$bry =$blue->y -$red->y;
			$prx =$p->x - $red->x;
			$pry =$p->y - $red->y;
			$v1 = new cgpoint2($grx, $gry);
			$v2 = new cgpoint2($brx, $bry);
			$q = new cgpoint2($prx, $pry);

			$s = ($this->getCrossProduct($q, $v2) / $this->getCrossProduct($v1, $v2));
			$t = ($this->getCrossProduct($v1, $q) / $this->getCrossProduct($v1, $v2));

			if(($s > 0.0) && ($t >= 0.0) && ($s + $t <= 1.0))
			{
				return true;
			}
			return false;
		}


    }
	
	// @cond Ignore this class in doxygen
	/**
    *
    * Helper class to ease translation from Philips C-coding
	* Implements CGPoint class from iOS SDK
    *
    */	
	
  class cgpoint2 {
  
	public $x;
	public $y;
	
	function __construct($_x, $_y) {
      $this->x = $_x;
	  $this->y = $_y;
	}
	
	};
	// @endcond
	
    /** @}*/
?>