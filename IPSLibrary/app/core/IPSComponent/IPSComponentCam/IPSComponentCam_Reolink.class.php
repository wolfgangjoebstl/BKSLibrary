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

/**@addtogroup ipscomponent
 * @{
 *
 * @file          IPSComponentCam_Reolink.class.php
 * @author        wolfgangjoebstl
 *
 */

/**
 * @class IPSComponentCam_Reolink
 *
 * Definiert ein IPSComponentCam Object, das die Funktionen einer Cam Componente für eine
 * Reolink rtsp Kamera implementiert
 *
 * @author wolfgangjoebstl
 * 
 */

IPSUtils_Include ('IPSComponentCam.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentCam');

class IPSComponentCam_Reolink extends IPSComponentCam {

    private $ipAddress;
    private $username;
    private $password;

    /**
     * @public
     *
     * Initialisierung eines IPSComponentCam_Reolink Objektes
     *
     * @param string $ipAddress IP Adresse der Kamera
     * @param string $username Username für Kamera Zugriff
     * @param string $password Passwort für Kamera Zugriff
     */
    public function __construct($ipAddress, $username, $password) {
        $this->ipAddress  = $ipAddress;
        $this->username   = $username;
        $this->password   = $password;
    }


    /**
     * @public
     *
     * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event
     * an das entsprechende Module zu leiten.
     *
     * @param integer $variable ID der auslösenden Variable
     * @param string $value Wert der Variable
     * @param IPSModuleCam $module Module Object an das das aufgetretene Event weitergeleitet werden soll
     * @throws IPSComponentException
     */
    public function HandleEvent($variable, $value, IPSModuleCam $module) {
        $name = IPS_GetName($variable);
        throw new IPSComponentException('Event Handling NOT supported for Variable '.$variable.'('.$name.')');
    }

    /**
     * @public
     *
     * Liefert URL des Kamera Live Streams
     *
     *   Reolink                         rtsp://admin:111111@192.168.0.110:554//h264Preview_01_main
     *                      Sub Stream:  rtsp://admin:111111@192.168.0.110:554//h264Preview_01_sub      
     *
     * @param integer $size Größe des Streams, mögliche Werte:
     *                      IPSCOMPONENTCAM_SIZE_SMALL, IPSCOMPONENTCAM_SIZE_MIDDLE oder IPSCOMPONENTCAM_SIZE_LARGE
     * @return string URL des Streams
     */
     
    public function Get_URLLiveStream($size=IPSCOMPONENTCAM_SIZE_MIDDLE) 
        {
        switch ($size) 
            {
            case  IPSCOMPONENTCAM_SIZE_SMALL:
                $url = 'rtsp://'.$this->username.':'.$this->password.'@'.$this->ipAddress.':554/h264Preview_01_sub';
                break;
            case  IPSCOMPONENTCAM_SIZE_MIDDLE:
            case  IPSCOMPONENTCAM_SIZE_LARGE:
                $url = 'rtsp://'.$this->username.':'.$this->password.'@'.$this->ipAddress.':554/h264Preview_01_main';
                break;
            default:
                trigger_error('Unknown Size '.$size);
            }

        IPS_LogMessage	(get_class($this), $url);
        return $url;
        }

    /**
     * @public
     *
     * Liefert URL des Kamera Bildes
     *
     *   Reolink                            /cgi-bin/api.cgi?cmd=Snap&channel=0&rs=(any combination of numbers and letters)&user=admin&password=cloudg06
     *
     * @param integer $size Größe des Bildes, mögliche Werte:
     *                      IPSCOMPONENTCAM_SIZE_SMALL, IPSCOMPONENTCAM_SIZE_MIDDLE oder IPSCOMPONENTCAM_SIZE_LARGE
     * @return string URL des Bildes
     */
    public function Get_URLPicture($size=IPSCOMPONENTCAM_SIZE_MIDDLE) 
        {
        $strRand=$this->str_rand(8);

        switch ($size)
            {
            case IPSCOMPONENTCAM_SIZE_SMALL:
            case IPSCOMPONENTCAM_SIZE_MIDDLE:
            case IPSCOMPONENTCAM_SIZE_LARGE:
                $url = 'http://'.$this->ipAddress.'/cgi-bin/api.cgi?cmd=Snap&channel=0&rs='.$strRand.'&user='.$this->username.'&password='.$this->password;
                break;
            default:
                trigger_error('Unknown Size '.$size);
                break;
            }
        IPS_LogMessage	(get_class($this), $url);
        return $url;
        }

    function str_rand(int $length = 20)
        {
        $ascii_codes = range(48, 57) + range(97, 122);
        $codes_lenght = (count($ascii_codes)-1);
        shuffle($ascii_codes);
        $string = '';
        for($i = 1; $i <= $length; $i++)
            {
            $previous_char = $char ?? '';
            $char = chr($ascii_codes[random_int(0, $codes_lenght)]);
            while($char == $previous_char){
                $char = chr($ascii_codes[random_int(0, $codes_lenght)]);
            }
            $string .= $char;
            }
        return $string;
        }

    /**
     * @public
     *
     * Bewegen der Kamera
     *
     * @param integer $urlType Type der URL die geliefert werden soll.
     *                         mögliche Werte: IPSCOMPONENTCAM_URL_MOVEHOME
    IPSCOMPONENTCAM_URL_MOVELEFT
    IPSCOMPONENTCAM_URL_MOVERIGHT
    IPSCOMPONENTCAM_URL_MOVEUP
    IPSCOMPONENTCAM_URL_MOVEDOWN
    IPSCOMPONENTCAM_URL_PREDEFPOS1
    IPSCOMPONENTCAM_URL_PREDEFPOS2
    IPSCOMPONENTCAM_URL_PREDEFPOS3
    IPSCOMPONENTCAM_URL_PREDEFPOS4
    IPSCOMPONENTCAM_URL_PREDEFPOS5
     * @return string URL der Position
     */
    public function Get_URL($urlType) {
        $url = 'http://'.$this->ipAddress;

        switch ($urlType) {
            case IPSCOMPONENTCAM_URL_MOVELEFT:
                $url = $url.'/param.cgi?cmd=ptzctrl&-step=1&-act=left&-speed=40';
                break;
            case IPSCOMPONENTCAM_URL_MOVERIGHT:
                $url = $url.'/param.cgi?cmd=ptzctrl&-step=1&-act=right&-speed=40';
                break;
            case IPSCOMPONENTCAM_URL_MOVEUP:
                $url = $url.'/param.cgi?cmd=ptzctrl&-step=1&-act=up&-speed=40';
                break;
            case IPSCOMPONENTCAM_URL_MOVEDOWN:
                $url = $url.'/param.cgi?cmd=ptzctrl&-step=1&-act=down&-speed=40';
                break;
            case IPSCOMPONENTCAM_URL_MOVEHOME:
                $url = $url.'/param.cgi?cmd=ptzctrl&-step=1&-act=home&-speed=40';
                break;
            case IPSCOMPONENTCAM_URL_PREDEFPOS1:
                $url = $url.'/param.cgi?cmd=preset&-act=goto&-number=0';
                break;
            case IPSCOMPONENTCAM_URL_PREDEFPOS2:
                $url = $url.'/param.cgi?cmd=preset&-act=goto&-number=1';
                break;
            case IPSCOMPONENTCAM_URL_PREDEFPOS3:
                $url = $url.'/param.cgi?cmd=preset&-act=goto&-number=2';
                break;
            case IPSCOMPONENTCAM_URL_PREDEFPOS4:
                $url = $url.'/param.cgi?cmd=preset&-act=goto&-number=3';
                break;

            default:
                trigger_error('Die Funktion '.$urlType.'ist f�r eine Instar1080pSeries Kamera noch NICHT implementiert !!!');
        }

        $url = $url.'&usr='.$this->username.'&pwd='.$this->password ;

        IPS_LogMessage	(get_class($this), $url);
        return $url;

    }

    /**
     * @public
     *
     * Liefert Breite des Kamera Bildes
     *
     * @param integer $size Größe des Bildes, mögliche Werte:
     *                      IPSCOMPONENTCAM_SIZE_SMALL, IPSCOMPONENTCAM_SIZE_MIDDLE oder IPSCOMPONENTCAM_SIZE_LARGE
     * @return integer Breite des Bildes in Pixel
     */
    public function Get_Width($size=IPSCOMPONENTCAM_SIZE_MIDDLE) {
        $return = false;

        switch ($size) {
            case  IPSCOMPONENTCAM_SIZE_SMALL:
            case  IPSCOMPONENTCAM_SIZE_MIDDLE:
            case  IPSCOMPONENTCAM_SIZE_LARGE:
                $return = 2340;
                break;

            default:
                trigger_error('Unknown Size '.$size);
        }
        return $return;
    }

    /**
     * @public
     *
     * Liefert Höhe des Kamera Bildes
     *
     * @param integer $size Größe des Bildes, mögliche Werte:
     *                      IPSCOMPONENTCAM_SIZE_SMALL, IPSCOMPONENTCAM_SIZE_MIDDLE oder IPSCOMPONENTCAM_SIZE_LARGE
     * @return integer Höhe des Bildes in Pixel
     */
    public function Get_Height($size=IPSCOMPONENTCAM_SIZE_MIDDLE) {
        $return = false;

        switch ($size) {
            case  IPSCOMPONENTCAM_SIZE_SMALL:
            case  IPSCOMPONENTCAM_SIZE_MIDDLE:
            case  IPSCOMPONENTCAM_SIZE_LARGE:
                $return = 1080;
                break;
            default:
                trigger_error('Unknown Size '.$size);
        }
        return $return;
    }
}

/** @}*/
?>