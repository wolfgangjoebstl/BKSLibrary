<?php

/*
	 * @defgroup Startpage Update
	 *
	 * Script zur Darstellung von besonderen graphischen Aufmachern
     * siehe auch   https://www.timeanddate.de/mond/oesterreich/wien oder
     *              https://www.timeanddate.de/sonne/oesterreich/wien
	 *              https://www.timeanddate.de/astronomie/nachthimmel/
	 *
	 * @file          Startpage_Update.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

	IPSUtils_Include ('Astronomy.inc.php', 'IPSLibrary::app::modules::Startpage');
	IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

/* Erweiterung auf basis Astronomy Modul von fonzo. war mir etwas zu kompliziert und Bild konnte
 * nicht einfch integriert werden. daher der halbe Fork
 *
 *  construct
 *
 *  getMoonPictureInfos         ask Nasa about the position of the moon and actual name of moon picture
 *  getMoonPictureNasa
 *  setMoonPictureDir
 *  getMoonPicturesNasa
 *
 *  getMoonPicture              passendes Mondbild raussuchen, sind so kleine 100x100 pics
 *      rescale
 *  getMoonPhasePercent
 *  getMoon_CurrentFullmoon
 *  Moon_Fullmoon
 *
 *  calculateSunCoordinates             der Einstieg für die Sonnenkoordinaten aus dem Blickwinkel des Beobachters zur aktuellen Uhrzeit
 *  calculateMoonCoordinates            der Einstieg für die Mondkoordinaten aus dem Blickwinkel des Beobachters zur aktuellen Uhrzeit
 *
 *  MoonLongSeries              ruft MoonLong auf, für verscheidene Positionen
 *
 *  getSunMoonView
 *  GetFrameType
 *
 *
 * Wichtige Funktionen, Sonnen und Mond Koordinaten am Himmel und eine nette Zeichnung davon
 * calculateSunCoordinate, calculateMoonCoordinates, $geo->getSunMoonView
 *
 *
 * In celestial navigation, the convention is to measure in degrees westward from the prime meridian (Greenwich hour angle, GHA), 
 * from the local meridian (local hour angle, LHA) or from the first point of Aries (sidereal hour angle, SHA).
 *
 * Abbreviations, used in formulars or tables:
 *      GHA     Greenwich hour angle  degrees westwards from Greenwich Meridian
 *      LHA     local hour angle, degrees westward from the local meridian
 *      SHA     sidereal hour angle, degrees westward from the first point of aries (Frühlingspunkt)
 *
 *      LHA = SHA + GHA - lon     GHA = GST - rasc      SHA is similar to rasc with different directions
 *
 * The instants of the equinoxes are currently defined to be when the apparent geocentric longitude of the Sun is 0° and 180°
 * On the day of an equinox, daytime and nighttime are of approximately equal duration all over the planet. 
 *
 *      PZ is the angular distance from the Celestial North Pole to the zenith of the observer and is equal to 90o – Lat.
 *      PX is the angular distance from the Celestial North Pole to the celestial body and is equal to 90o – Dec.
 *      ZX is the Zenith Distance and is equal to 90o – altitude.
 *
 *      The angle ZPX is equal to the Local Hour Angle of the Celestial Body with respect to the observer’s meridian.
 *      The angle PZX is the azimuth of the body with respect to the observer’s meridian (eg Vienna )
 *
 * The observer is on latitude / longitude, the celestial body has declination / right ascension
 * altitude / azimuth (PZX) is the celestial body measured as been viewed from the observers point
 *
 * azimuth is LHA is ZPX
 *
 *
 *
 * Umrechnungen, Routinen
 *
 *
 *
 */


class geoOps
    {
    
    use joebstl\MoonCalculations;
    use joebstl\Trigonometry;
    use joebstl\JulianDates;
    use joebstl\DateandTime;
    use joebstl\NasaMoonlight;


    var $sunstarsign, $moonstarsign;
    var $zeropointx, $zeropointy;
    var $canvaswidth, $canvasheight;
    var $canvasbackground;
    var $filename;
    var $moondir;                       // dir for moon pictures from Nasa
    var $curlOps, $dosOps;

    function __construct()
        {
        $this->curlOps = new curlOps();             
        $this->dosOps = new dosOps();            
        $this->zeropointx = 50;      //Nullpunkt x-achse
        $this->zeropointy = 50;       //Nullpunkt y-achse
        $this->canvaswidth = 500;               //canvas width, originally 800
        $this->canvasheight = 200;                  //canvas height
        $this->canvasbackground = 0x123456;                 // canvas background
        $this->filename =  DIRECTORY_SEPARATOR.'StartPage'.DIRECTORY_SEPARATOR.'sunmoonline.php';
        }

    /* ask Nasa about the position of the moon and actual name of moon picture
     */
    public function getMoonPictureInfos($time=false)
        {
        if ($time !== false) $akt_date = $time;
        else $akt_date = time(); 

        $index = "https://svs.gsfc.nasa.gov/api/dialamoon/".date("Y-m-d",$akt_date)."T".date("H:i",$akt_date);
        $config=$this->curlOps->getJsonConfig($index);
        $filename = basename($config["image"]["filename"]);
        $config["filename"]=$filename;
        $config["url"]=$config["image"]["url"];
        unset($config["image"]);
        unset($config["image_highres"]);
        unset($config["su_image"]);
        unset($config["su_image_highres"]);
        return ($config);
        }

    /* von der Nasa Homepage spezielle realitätsnahe Mondbilder laden
     * gespeichert wird in dirWeb bzw default C:/Scripts/moonPics/
     */
    public function getMoonPictureNasa($time=false, $dirWeb=false, $debug=false)
        {
        if ($time !== false) $akt_date = $time;
        else $akt_date = time(); 

        if ($dirWeb !== false) $dir = $dirWeb;
        else $dir="C:/Scripts/moonPics/";
        $this->dosOps->mkdirtree($dir);

        $index = "https://svs.gsfc.nasa.gov/api/dialamoon/".date("Y-m-d",$akt_date)."T".date("H:i",$akt_date);
        $config=$this->curlOps->getJsonConfig($index);
        if ($debug) print_R($config);
        $picUrl=$config["image"]["url"];
        $filename = basename($config["image"]["filename"]);
        if ($debug) echo "Filename : $dir$filename ";
        if (file_exists($dir.$filename)) { if ($debug) echo ", no download, file already downloaded.\n"; }
        else $this->curlOps->downloadFile($picUrl, $dir);
        if ($debug) echo "\n";
        return ($dir.$filename);
        }

    public function setMoonPictureDir($dirWeb) 
        {
        $this->dirWeb=$dirWeb;    
        $dir  = $this->dosOps->readDirToArray($this->dirWeb);
        //print_R($dir);
        return ($dir);
        }


    public function getMoonPicturesNasa($time=false, $count=1, $shift=-1, $debug=false)   
        {
        // Datum/Zeit für das Mondbild bestimmen
        if ($time !== false) $akt_date = $time;
        else $akt_date = time(); 

        //  für die erste Zeit mal nur die Info auslesen
        $info = $this->getMoonPictureInfos($akt_date);

        $dir  = $this->dosOps->readDirToArray($this->dirWeb);
        //print_R($dir);

        // Shift ist in Stunden, selbe Abstufung/Einheit auch auf dem NASA Server
        $shift = $shift*60*60;         // Angabe in Stunden, Umrechnung in Sekunden
        echo "getMoonPicturesNasa, starting from ".date("d.m.Y H:i",$akt_date).", reading $count items, interval $shift seconds.\n";
        for ($i=0;$i<$count;$i++)
            {
            $filename = $this->getMoonPictureNasa($akt_date,false);
            echo "     ".date("d.m.Y H:i",$akt_date)."   $filename  \n";
            $akt_date = $akt_date+$shift; 
            }
        } 

    /* das Mondbild aus der Mondphase in Prozent berechnen
     * Bei Vollomnd 
     *      99% bis 100% auf die Bilder 172 bis 177 mappen
     *      00% bis  01% auf die Bilder 178 bis 182 
     * Variable anlegen / löschen
     */
    function getMoonPicture(float $mondphase, $language = 1)
        {
                 // 1 für deutsch, alle anderen englisch
        $firstfullmoonpic = 172;
        $lastfullmoonpic = 182;
        $firstincreasingmoonpic = 183;
        $lastincreasingmoonpic = 352;
        $firstnewmoonpic = 353;
        $lastnewmoonpic = 362;
        $firstdecreasingmoonpic = 8;
        $lastdecreasingmoonpic = 171;
        if ($mondphase <= 1 || $mondphase >= 99)  //--Vollmond
            {
            if ($language == 1) { $phase_text = 'Vollmond';
            } else { $phase_text = 'full moon';  }
                if ($mondphase >= 99) {
                    $pic = $this->rescale([99, 100], [172, 177]); // ([Mondphasen von,bis],[Bildnummern von,bis])
                } else {
                    $pic = $this->rescale([0, 1], [178, 182]);
                }
            $pic_n = floor($pic($mondphase));
            if ($pic_n < 10) {
                $pic_n = '00' . $pic_n;
            } elseif ($pic_n < 100) {
                $pic_n = '0' . $pic_n;
            }
        } elseif ($mondphase > 1 && $mondphase < 49) {  //--abnehmender Mond
            if ($language == 1) {
                $phase_text = 'abnehmender Mond';
            } else {
                $phase_text = 'decreasing moon';
            }
            $pic = $this->rescale([2, 48], [$firstincreasingmoonpic, $lastincreasingmoonpic]);
            $pic_n = floor($pic($mondphase));
            if ($pic_n < 10) {
                $pic_n = '00' . $pic_n;
            } elseif ($pic_n < 100) {
                $pic_n = '0' . $pic_n;
            }
        } elseif ($mondphase >= 49 && $mondphase <= 51) {  //--Neumond
            if ($language == 1) {
                $phase_text = 'Neumond';
            } else {
                $phase_text = 'new moon';
            }
            $pic = $this->rescale([49, 51], [$firstnewmoonpic, $lastnewmoonpic]);           // $pic ist eine function, ein return
            $pic_n = floor($pic($mondphase));
            if ($pic_n < 10) {
                $pic_n = '00' . $pic_n;
            } elseif ($pic_n < 100) {
                $pic_n = '0' . $pic_n;
            }
        } else {  //--zunehmender Mond
            if ($language == 1) {
                $phase_text = 'zunehmender Mond';
            } else {
                $phase_text = 'increasing moon';
            }
            $pic = $this->rescale([52, 98], [$firstdecreasingmoonpic, $lastdecreasingmoonpic]);
            $pic_n = floor($pic($mondphase));
            if ($pic_n < 10) {
                $pic_n = '00' . $pic_n;
            } elseif ($pic_n < 100) {
                $pic_n = '0' . $pic_n;
            }
        }

        $picture = ['picid' => $pic_n, 'phase' => $phase_text];
        return $picture;
        }

    /* 
     * Funktion zum anpassen der Mondphase 0-100 an Bildnummer 001-362 (Bilder der Seite http://www.avgoe.de)
     * aufgerufen von getMoonPicture
     */
    function rescale($ab, $cd) 
        {
        list($a1, $b1) = $ab;
        list($c1, $d1) = $cd;
        if ($a1 == $b1) 
            {
            trigger_error('Invalid scale', E_USER_WARNING);
            return false;
            }
        $o = ($b1 * $c1 - $a1 * $d1) / ($b1 - $a1);
        $s = ($d1 - $c1) / ($b1 - $a1);
        return function ($x) use ($o, $s) 
            {
            return $s * $x + $o;
            };
        }

    /* Profil anlegen, Ursprung für Formel ist mktime($hour,$minute,$second,$month,$day,$year)
     * Differenz in Sekunden von heute auf Ursprung wird berechnet, Mondumlaufbahn in Tagen * (24*60*60) Sekunden eines Tages
     * Anzahl Mondphases seit damals minus floor Anzahl Mondphases seit damals ergibt die aktuelle Mondphase in Prozent 
     * Formel nach http://www.die-seite.eu/wm-mondphasen.php
     *
     * dauer = zeit-vollmond [s]; mondumlauf in Sekunden; dauer/mondumlauf-floor(dauer/mondumlauf) ist [0..1] von mondumlauf, mall 100 ist Prozent
     * floor()
     *
     * Montag, 22. Februar 2016 um ca. 18:20 Uhr laut österreichischer Seite, vielleicht doch UTC oder Sommer/Winterzeit vertauscht
     * time() is timezone independent while mktime() is not, date_default_timezone_set('UTC');
     *
     */
    function getMoonPhasePercent($time=false)
        {
        if ($time !== false) $akt_date = $time;
        else $akt_date = time(); 

        $ursprung = mktime(19, 19, 54, 02, 22, 2016);           //19:19:54 am 22.2.2016 
        $mondphase = round( ( (($akt_date - $ursprung) / (floor(29.530588861 * 86400))) - floor(($akt_date - $ursprung) / (floor(29.530588861 * 86400))) ) * 100, 0);
        return $mondphase;
        }

    /*
     * Equatorial to Horizon coordinate conversion (Alt)
    */
    public function getMoon_CurrentFullmoon()
        {
        // aktuelles Datum in Jahre umrechnen
        $year = ((((((date('s') / 60) + date('i')) / 60) + date('G')) / 24) + date('z') - 1) / (365 + (date('L'))) + date('Y');
        $moonphase = $this->calculateMoonphase($year);
        print_r($moonphase);
        $fullmoon = $moonphase['fullmoon'];
        $fullmoondate = $moonphase['moondate'][2]['date'];
        $fullmoontime = $moonphase['moondate'][2]['time'];
        return ['fullmoon' => $fullmoon, 'fullmoondate' => $fullmoondate, 'fullmoontime' => $fullmoontime];
        }

    /* wie oben
     * switch aber automatisch auf nächsten Fullmond
     */
    public function Moon_Fullmoon()
        {
        // Datum in Jahre umrechnen
        $year = ((((((date('s') / 60) + date('i')) / 60) + date('G')) / 24) + date('z') - 1) / (365 + (date('L'))) + date('Y');
        $moonphase = $this->CalculateMoonphase($year);
        $fullmoon = $moonphase['fullmoon'];
        $fullmoondate = $moonphase['moondate'][2]['date'];
        $fullmoontime = $moonphase['moondate'][2]['time'];

        $ispast = $this->compareDateWithToday($fullmoondate);
        if ($ispast) {
            $year = $this->GetNextPhase();
            $nextmoonphase = $this->CalculateMoonphase($year);
            $nextfullmoon = $nextmoonphase['fullmoon'];
            $nextfullmoondate = $nextmoonphase['moondate'][2]['date'];
            $nextfullmoontime = $nextmoonphase['moondate'][2]['time'];
            return ['fullmoon' => $nextfullmoon, 'fullmoondate' => $nextfullmoondate, 'fullmoontime' => $nextfullmoontime];
        } else 
            {
            return ['fullmoon' => $fullmoon, 'fullmoondate' => $fullmoondate, 'fullmoontime' => $fullmoontime];
            }        
        }

    function calculateSunCoordinates()
        {
        $location = $this->getlocation();
        $P = $location['Latitude'];
        $L = $location['Longitude'];

        $day = intval(date('d'));
        $month = intval(date('m'));
        $year = intval(date('Y'));
        $Hour = intval(date('H'));
        $Minute = intval(date('i'));
        $Second = intval(date('s'));
        $summer = intval(date('I'));

        $DS = $summer;      // Summertime, $DS = Daylight Saving
        $ZC = 1;            // Zone Correction to Greenwich: 1 = UTC+1

        $HMSDec = $this->HMSDH(floatval($Hour), $Minute, $Second); //Local Time HMS in Decimal Hours
        // $UTDec = $this->LctUT($Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year)["UTDec"];
        $GD = floatval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GD']);
        $GM = intval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GM']);
        $GY = intval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GY']);
        $JD = $this->CDJD($GD, $GM, $GY);  //UT Julian Date

        $LCH = $this->DHHour($HMSDec);  //LCT Hour --> Local Time
        $LCM = $this->DHMin($HMSDec);   //LCT Minute
        $LCS = $this->DHSec($HMSDec);   //LCT Second
        //Universal Time
        // $UH = $this->DHHour($UTDec);      //UT Hour
        // $UM = $this->DHMin($UTDec);    //UT Minute
        // $US = $this->DHSec($UTDec);    //UT Second
        // $UT_value = $UH.":".$UM.":".$US;
        // $UDate_value = $GD.":".$GM.":".$GY;

        //Calculation Sun---------------------------------------------------------------

        //Sun's ecliptic longitude in decimal degrees
        $Sunlong = $this->SunLong($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year);
        $this->sunstarsign = floor($Sunlong / 30) + 1;    
        //SendDebug('Astronomy:', "Sun's ecliptic longitude " . $Sunlong, 0);

        $SunlongDeg = $this->DDDeg($Sunlong);
        $SunlongMin = $this->DDMin($Sunlong);
        $SunlongSec = $this->DDSec($Sunlong);

        //Sun's RA
        $SunRAh = $this->DDDH($this->ECRA($SunlongDeg, $SunlongMin, $SunlongSec, 0, 0, 0, $GD, $GM, $GY));    //returns RA in hours
        $SunRAhour = $this->DHHour($SunRAh);
        $SunRAm = $this->DHmin($SunRAh);
        $SunRAs = $this->DHSec($SunRAh);
        // $SunRAhms = $SunRAhour.":".$SunRAm.":".$SunRAs;

        $season = '';
        if (($SunRAh >= 0) and ($SunRAh < 6)) {
            $season = 1;
        }        //Frühling
        if (($SunRAh >= 6) and ($SunRAh < 12)) {
            $season = 2;
        }        //Sommer
        if (($SunRAh >= 12) and ($SunRAh < 18)) {
            $season = 3;
        }        //Herbst
        if (($SunRAh >= 18) and ($SunRAh < 24)) {
            $season = 4;
        }        //Winter

        //Sun's Dec
        $SunDec = $this->ECDec($SunlongDeg, $SunlongMin, $SunlongSec, 0, 0, 0, $GD, $GM, $GY);            //returns declination in decimal degrees
        $SunDecd = $this->DDDeg($SunDec);
        $SunDecm = $this->DDmin($SunDec);
        $SunDecs = $this->DDSec($SunDec);
        $SunDecdms = $SunDecd . ':' . $SunDecm . ':' . $SunDecs;
        //SendDebug('Astronomy:', 'Sun decimal degrees ' . $SunDecdms, 0);

        //RH Right Ascension in HMS, LH Local Civil Time in HMS, DS Daylight saving, ZC Zonecorrection,
        //LD Local Calender Date in DMY, L geographical Longitude in Degrees
        $SunH = $this->RAHA($SunRAhour, $SunRAm, $SunRAs, $Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year, $L); //Hour Angle H
        $SunHh = $this->DHHour($SunH);
        $SunHm = $this->DHMin($SunH);
        $SunHs = $this->DHSec($SunH);

        //Equatorial to Horizon coordinate conversion (Az)
        //HH HourAngle in HMS, DD Declination in DMS, P Latitude in decimal Degrees
        $sunazimut = $this->EQAz($SunHh, $SunHm, $SunHs, $SunDecd, $SunDecm, $SunDecs, $P);
        $sunaltitude = $this->EQAlt($SunHh, $SunHm, $SunHs, $SunDecd, $SunDecm, $SunDecs, $P);
        $SunDazimut = $this->direction($sunazimut);

        // $SunDist = $this->SunDist($Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year);
        $SunTA = $this->Radians($this->SunTrueAnomaly($Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year));
        $SunEcc = $this->SunEcc($GD, $GM, $GY);
        $fSun = (1 + $SunEcc * cos($SunTA)) / (1 - $SunEcc * $SunEcc);
        $rSun = round(149598500 / $fSun, -2);

        //(radiant_power = $this->Radiant_Power();                      // hat ein paar interne Werte zur verwendung

        return (['sunazimut' => $sunazimut,'sundirection' => $SunDazimut,'sunaltitude' => $sunaltitude, 'sundistance' => $rSun]);
        }

    /* ecliptic coordinates of the moon
     *
     * http://jgiesen.de/moonmotion/index.html
     * die Mond Koordinaten für longitude ausrechnen, Formeln zurückrechnen
     * aus der aktuellen Zeit die Stunden als float Wert ausrechnen
     *
     * Erklärung Koordinatensystem für Sterne mit Bezugspunkt Erde/Sonne d.h. Sonne hat zum Frühlingsbeginn longitude=0 und right ascension 0
     * https://en.wikipedia.org/wiki/Ecliptic_coordinate_system
     *
     * Umrechnung Ecliptic to Equatorial Coordinate System (Decimal Degrees)
     *
     * The geocentric ecliptic system was the principal coordinate system for ancient astronomy and is still useful for 
     * computing the apparent motions of the Sun, Moon, and planets.[3] It was used to define the twelve astrological signs of the zodiac, for instance.
     *
     * https://doncarona.tamu.edu/cgi-bin/moon?current=0&jd=
     * https://theskylive.com/quickaccess?objects=moon-sun&data=equatorial-horizontal-magnitude-riseset-time
     * https://heavens-above.com/Moon.aspx
     *
     *
     * obwohl so genau gerechnet wird stimmt es trotzdem nicht, wo ist der Fehler ?
     */
    public function calculateMoonCoordinates($debug=false)
        {
        $location = $this->getlocation();
        $P = $location['Latitude'];
        $L = $location['Longitude'];
        if ($debug) echo "calculateMoonCoordinates für folgende Koordinaten : $P $L \n";

        $day = intval(date('d'));               // get local date d.m.Y H:i:s plus Summertime
        $month = intval(date('m'));
        $year = intval(date('Y'));
        $Hour = intval(date('H'));
        $Minute = intval(date('i'));
        $Second = intval(date('s'));
        $summer = intval(date('I'));
        $timezone = intval(date('O'))/100;          // +0100
        
        $DS = $summer;      // Summertime, $DS = Daylight Saving
        $ZC = $timezone;            // Zone Correction to Greenwich: 1 = UTC+1


        // $UTDec = $this->LctUT($Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year)["UTDec"];
        $GD = floatval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GD']);         // Lct to UT , Local time to Unified Time, umstellen auf Julianischen Kalender Tag als float, mit Komma
        $GM = intval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GM']);           // Julianischer Monat
        $GY = intval($this->LctUT(intval($Hour), $Minute, $Second, $DS, $ZC, $day, $month, $year)['GY']);           // Julianisches Jahr


        $HMSDec = $this->HMSDH(floatval($Hour), $Minute, $Second);                                                  //Local Time HMS in Decimal Hours [0..24]
        $LCH = $this->DHHour($HMSDec);  //LCT Hour --> Local Time, gerundet auf hunderstel Sekunden
        $LCM = $this->DHMin($HMSDec);   //LCT Minute
        $LCS = $this->DHSec($HMSDec);   //LCT Second

        if ($debug) 
            {
            echo "                     und für folgende Uhrzeit $LCH:$LCM:$LCS, W/S $DS, UTC+$ZC, $day.$month.$year  \n";
            $JD = $this->CDJD($GD, $GM, $GY);                                                                         //UT Julian Date ? vom Julian Date ?
            $days=$this->roundvariantfix($GD);
            $hours = $this->roundvariantfix(($GD-$days)*24);
            $minutes = $this->roundvariantfix((($GD-$days)*24 - $this->roundvariantfix(($GD-$days)*24))*60);
            echo "                     Julian Date ist $JD, check heute UT $hours:$minutes $days.$GM.$GY   \n";
            }
        //Calculation Moon--------------------------------------------------------------
        $MoonLong = $this->MoonLong($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year, $debug);       //Moon ecliptic longitude (degrees), input local time (h:m:s), summertime, timezone, Date (d.m.Y)
        //SendDebug('Astronomy:', 'Moon ecliptic longitude ' . $MoonLong, 0);
        if ($debug) echo 'Astronomy MoonLong: Moon ecliptic longitude : ' . $MoonLong." Degrees\n";
        $MoonLat = $this->MoonLat($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year);         //Moon elciptic latitude (degrees)
        //SendDebug('Astronomy:', 'Moon elciptic latitude ' . $MoonLat, 0);
        if ($debug) echo 'Astronomy MoonLong: Moon ecliptic latitude  : ' . $MoonLat." Degrees\n";
        $this->moonstarsign = floor($MoonLong / 30) + 1;

        $Nutation = $this->NutatLong($GD, $GM, $GY);                                        //nutation in longitude (degrees) based on juliandate (d.m.Y)
        //SendDebug('Astronomy:', 'nutation in longitude ' . $Nutation, 0);
        $Moonlongcorr = $MoonLong + $Nutation; //corrected longitude (degrees)
        if ($debug) echo 'Astronomy MoonLong: Nutation in longitude : ' . $Nutation." Degrees\n";
        $MoonHP = $this->MoonHP($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year);           //Moon's horizontal parallax (degrees)
        //SendDebug('Astronomy:', "Moon's horizontal parallax " . $MoonHP, 0);
        $MoonDist = $this->MoonDist($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year);   //Moon Distance to Earth
        //SendDebug('Astronomy:', 'Moon Distance to Earth ' . $MoonDist, 0);
        $Moonphase = $this->MoonPhase($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year); //Moonphase in %
        //SendDebug('Astronomy:', 'Moonphase ' . $Moonphase . '%', 0);
        $MoonBrightLimbAngle = $this->MoonPABL($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year);   //Moon Bright Limb Angle (degrees)
        //SendDebug('Astronomy:', 'Moon Bright Limb Angle ' . $MoonBrightLimbAngle, 0);

        if ($MoonBrightLimbAngle < 0) {
            $MoonBrightLimbAngle = $MoonBrightLimbAngle + 360;
        }

        $EcLonDeg = $this->DDDeg($Moonlongcorr); // Ecliptic Longitude Moon - geographische Länge (Längengrad)
        $EcLonMin = $this->DDMin($Moonlongcorr);
        $EcLonSec = $this->DDSec($Moonlongcorr);

        $EcLatDeg = $this->DDDeg($MoonLat); // Ecliptic Latitude Moon - geographische Breite (Breitengrad)
        $EcLatMin = $this->DDMin($MoonLat);
        $EcLatSec = $this->DDSec($MoonLat);

        //Ecliptic to Equatorial Coordinate Conversion (Decimal Degrees)
        //ELD Ecliptic Longitude in DMS, BD Ecliptic Latitude in DMS, GD Greenwich Calendar Date in DMY
        $MoonRA = $this->DDDH($this->ECRA($EcLonDeg, $EcLonMin, $EcLonSec, $EcLatDeg, $EcLatMin, $EcLatSec, $GD, $GM, $GY));
        $MoonDec = $this->ECDec($EcLonDeg, $EcLonMin, $EcLonSec, $EcLatDeg, $EcLatMin, $EcLatSec, $GD, $GM, $GY);
        $MoonRAh = $this->DHHour($MoonRA);    //Right Ascension Hours
        $MoonRAm = $this->DHMin($MoonRA);
        $MoonRAs = $this->DHSec($MoonRA);
        if ($debug) echo "Astronomy MoonLong: Right Ascension : $MoonRAh:$MoonRAm:$MoonRAs   ".($MoonRA*15)." Degrees    RA +5min wrong\n";
        //SendDebug('Astronomy:', 'Moon Right Ascension Hours ' . $MoonRAh . ':' . $MoonRAm . ':' . $MoonRAs, 0);
        $MoonDECd = $this->DDDeg($MoonDec);  //Declination Degrees
        $MoonDECm = $this->DDMin($MoonDec);
        $MoonDECs = $this->DDSec($MoonDec);
        if ($debug) echo "Astronomy MoonLong: Declination     : $MoonDECd:$MoonDECm:$MoonDECs  $MoonDec Degrees   1,5 Degrees wrong\n";
        //SendDebug('Astronomy:', 'Moon Declination Degrees ' . $MoonDECd . ':' . $MoonDECm . ':' . $MoonDECs, 0);

        //RH Right Ascension in HMS, LH Local Civil Time in HMS, DS Daylight saving, ZC Zonecorrection,
        //LD Local Calender Date in DMY, L geographical Longitude in Degrees
        $MoonH = $this->RAHA($MoonRAh, $MoonRAm, $MoonRAs, $Hour, $Minute, $Second, $DS, $ZC, $day, $month, $year, $L); //Hour Angle H
        $MoonHh = $this->DHHour($MoonH);
        $MoonHm = $this->DHMin($MoonH);
        $MoonHs = $this->DHSec($MoonH);

        $moonazimut = $this->EQAz($MoonHh, $MoonHm, $MoonHs, $MoonDECd, $MoonDECm, $MoonDECs, $P);
        $moonaltitude = $this->EQAlt($MoonHh, $MoonHm, $MoonHs, $MoonDECd, $MoonDECm, $MoonDECs, $P);

        $dazimut = $this->direction($moonazimut);
        
        //$this->SunMoonView($sunazimut, $sunaltitude, $moonazimut, $moonaltitude);

        return (['moonazimut' => $moonazimut,'moonaltitude' => $moonaltitude,'moondirection' => $dazimut, 'moondistance' => $MoonDist,'moonvisibility' => $Moonphase, 'moonbrightlimbangle' => $MoonBrightLimbAngle]);
        
        /*$moonrisedate = $moonrise['moonrisedate'];
        $moonrisetime = $moonrise['moonrisetime'];

        $astronomyinfo = ['IsDay' => $isday, 'Sunrise' => $sunrise, 'Sunset' => $sunset, 'moonsetdate' => $moonsetdate, 'moonsettime' => $moonsettime, 'moonrisedate' => $moonrisedate, 'moonrisetime' => $moonrisetime, 'CivilTwilightStart' => $civiltwilightstart, 'CivilTwilightEnd' => $civiltwilightend, 'NauticTwilightStart' => $nautictwilightstart, 'NauticTwilightEnd' => $nautictwilightend, 'AstronomicTwilightStart' => $astronomictwilightstart, 'AstronomicTwilightEnd' => $astronomictwilightend,
            'latitude' => $Latitude, 'longitude' => $Longitude, 'juliandate' => $JD, 'season' => $season, 'sunazimut' => $sunazimut, 'sundirection' => $SunDazimut, 'sunaltitude' => $sunaltitude, 'sundistance' => $rSun, 'moonazimut' => $moonazimut, 'moonaltitude' => $moonaltitude, 'moondirection' => $dazimut, 'moondistance' => $MoonDist, 'moonvisibility' => $Moonphase, 'moonbrightlimbangle' => $MoonBrightLimbAngle,
            'newmoon' => $currentnewmoonstring, 'firstquarter' => $currentfirstquarterstring, 'fullmoon' => $currentfullmoonstring, 'lastquarter' => $currentlastquarterstring, 'moonphasetext' => $moonphasetext, 'moonphasepercent' => $moonphasepercent, 'picid' => $picture['picid'], 'mediaid_twilight_year' => $mediaid_twilight_year, 'twilight_year_image_path' => $twilight_year_image_path, 'mediaid_twilight_day' => $mediaid_twilight_day, 'twilight_day_image_path' => $twilight_day_image_path];

        return $astronomyinfo;  */          
        }

    public function MoonSeries($LCH, $LCM, $LCS, $DS, $ZC, $day, $month, $year, $count=1,$debug=false)
        {
        
        for ($i=0;$i<$count;$i++)
            {
            $long[$i]["lon"] = round($this->MoonLong($LCH+$i, $LCM, $LCS, $DS, $ZC, $day, $month, $year,$debug),2);
            $long[$i]["lat"] = round($this->MoonLat($LCH+$i, $LCM, $LCS, $DS, $ZC, $day, $month, $year,$debug),2);
            }
        return($long);
        }

    /* das ist die Routine wegen der der ganze Aufwand  ensteht
     * zeichnet Sonne/Mond Diagramm, zwar etwas spärlich aber immerhin
     *
     *
     * Darstellung als iFrame mit den Abmessungen als
     *      width:' . $framewidth . $framewidthtype . '; height:' . $frameheight . $frameheighttype
     * type ist jeweils die passende Einheit px, % ...
     *
     * Canvas ist aktuell 800, auf 50 fangt der Horizont an
     * Horizontlinie ist nur 360 lang, Text Horizont steht auf 368
     * Bei 420 wäre dann schon Platz für was neues ?
     *
     */
    public function getSunMoonView($sunazimut, $sunaltitude, $moonazimut, $moonaltitude)
        {
        // Anzeige der Position von Sonne und Mond im WF
        // Erstellung der Grafik mit "Canvas" (HTML-Element)
        // siehe https://de.wikipedia.org/wiki/Canvas_(HTML-Element)
        // 2016-04-25 Bernd Hoffmann

        //Daten für Nullpunkt usw.------------------------------------------------------
        $npx = $this->zeropointx; //Nullpunkt x-achse
        $npy = $this->zeropointy; //Nullpunkt y-achse
        //$npx=50; $npy=50;

        $canvaswidth = $this->canvaswidth; //canvas width
        $canvasheight = $this->canvasheight; //canvas height
        //$canvaswidth = 800; $canvasheight=200;
        $hexcolor_int = $this->canvasbackground;
        
        if ($hexcolor_int == 0)
            {
            $red   = 255;
            $blue  = 255;
            $green = 255;
            $alpha = 0;
            }
        else
            {
            $red   = floor($hexcolor_int/65536);
            $blue  = floor(($hexcolor_int-($red*65536))/256);
            $green = $hexcolor_int-($blue*256)-($red*65536);
            //$canvasbackgroundtransparency = $this->canvasbackgroundtransparency / 100; // canvas background transparency
            $canvasbackgroundtransparency = 0.3;
            $alpha = str_replace(',', '.', strval($canvasbackgroundtransparency));
            }

        $z = 40;           //Offset y-achse

        $lWt = 2;         //Linienstärke Teilstriche
        $lWh = 2;         //Linienstärke Horizontlinie

        //Waagerechte Linie-------------------------------------------------------------
        $l1 = 360;        //Länge der Horizontlinie

        $x1 = $npx;            //Nullpunkt waagerecht
        $y1 = $npy + $z;        //Nullpunkt senkrecht
        $x2 = $x1 + $l1;        //Nullpunkt + Länge = waagerechte Linie
        $y2 = $npy + $z;

        //Teilstriche-------------------------------------------------------------------
        $l2 = 10;         //Länge der Teilstriche
        //N 0°
        $x3 = $npx;           //Nullpunkt waagerecht
        $y3 = $y1 - $l2 / 2;    //Nullpunkt senkrecht
        $x4 = $x3;
        $y4 = $y3 + $l2;        //Nullpunkt + Länge = senkrechte Linie
        //O
        $x5 = $npx + 90;
        $y5 = $y1 - $l2 / 2;
        $x6 = $x5;
        $y6 = $y5 + $l2;
        //S
        $x7 = $npx + 180;
        $y7 = $y1 - $l2 / 2;
        $x8 = $x7;
        $y8 = $y7 + $l2;
        //W
        $x9 = $npx + 270;
        $y9 = $y1 - $l2 / 2;
        $x10 = $x9;
        $y10 = $y9 + $l2;
        //N 360°
        $x11 = $npx + 360;
        $y11 = $y1 - $l2 / 2;
        $x12 = $x11;
        $y12 = $y11 + $l2;

        //Daten von Sonne und Mond holen------------------------------------------------
        $xsun = round($npx + $sunazimut);
        $ysun = round($npy + $z - $sunaltitude);

        $xmoon = round($npx + $moonazimut);
        $ymoon = round($npy + $z - $moonaltitude);


        //Erstellung der Html Datei-----------------------------------------------------
        $html =
            '<html lang="de">
		<head>
        <style>
            body {
            background-color: rgba(' . $red . ', ' . $green . ', ' . $blue . ', ' . $alpha . ');
        }
        </style>
		<script type="text/javascript">

		function draw(){
		var canvas = document.getElementById("canvas1");
		if(canvas.getContext){
			var ctx = canvas.getContext("2d");

			ctx.lineWidth = ' . $lWt . '; //Teilstriche
			ctx.strokeStyle = "rgb(51,102,255)";
			ctx.beginPath();
			ctx.moveTo(' . $x3 . ',' . $y3 . ');
			ctx.lineTo(' . $x4 . ',' . $y4 . ');
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(' . $x5 . ',' . $y5 . ');
			ctx.lineTo(' . $x6 . ',' . $y6 . ');
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(' . $x7 . ',' . $y7 . ');
			ctx.lineTo(' . $x8 . ',' . $y8 . ');
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(' . $x9 . ',' . $y9 . ');
			ctx.lineTo(' . $x10 . ',' . $y10 . ');
			ctx.stroke();
			ctx.beginPath();
			ctx.moveTo(' . $x11 . ',' . $y11 . ');
			ctx.lineTo(' . $x12 . ',' . $y12 . ');
			ctx.stroke();
			
			ctx.lineWidth = 2; //Text
			ctx.fillStyle = "rgb(139,115,85)";
			ctx.beginPath();
			ctx.font = "18px calibri";
		   ctx.fillText("N", ' . $x4 . '-6,' . $y4 . '+15);
		   ctx.fillText("O", ' . $x6 . '-6,' . $y6 . '+15);
		   ctx.fillText("S", ' . $x8 . '-5,' . $y8 . '+15);
		   ctx.fillText("W", ' . $x10 . '-8,' . $y10 . '+15);
		   ctx.fillText("N", ' . $x12 . '-6,' . $y12 . '+15);
		   ctx.font = "16px calibri";
		   ctx.fillText("Horizont", ' . $x1 . '+368,' . $y1 . '+5);
		   
			ctx.lineWidth = ' . $lWh . '; //Horizontlinie
			ctx.strokeStyle = "rgb(51,102,255)";
			ctx.beginPath();
			ctx.moveTo(' . $x1 . ',' . $y1 . ');
			ctx.lineTo(' . $x2 . ',' . $y2 . ');
			ctx.stroke();
			
			ctx.lineWidth = 1; //Mond
			ctx.fillStyle = "rgb(255,255,255)";
			ctx.beginPath();
		   ctx.arc(' . $xmoon . ',' . $ymoon . ',10,0,Math.PI*2,true);
		   ctx.fill();
		   
		   ctx.lineWidth = 1; //Sonne
			ctx.fillStyle = "rgb(255,255,102)";
			ctx.beginPath();
		   ctx.arc(' . $xsun . ',' . $ysun . ',18,0,Math.PI*2,true);
		   ctx.fill();
			}
		}

		</script><title>sun and moon</title>
		</head>

		<body onload="draw()">
		<canvas id="canvas1" width="' . $canvaswidth . '" height="' . $canvasheight . '">
		</canvas>
		</body>

		</html>';

        //Erstellen des Dateinamens, abspeichern und Aufruf in <iframe>-----------------
        //$frameheight = $this->frameheight;
    	$frameheight = 290;
        //$frameheighttypevalue = $this->frameheighttype;
        $frameheighttypevalue=1;
        $frameheighttype = $this->GetFrameType($frameheighttypevalue);
        //$framewidth = $this->framewidth;
        //$framewidth = 100;
        //$framewidthtypevalue = $this->framewidthtype;
        //$framewidthtypevalue = 2;
        $framewidthtypevalue = 1;
        $framewidth = $canvaswidth;
        $framewidthtype = $this->GetFrameType($framewidthtypevalue);

		$fullFilename = IPS_GetKernelDir();
        if (IPS_GetKernelVersion() < 7.0) {             $fullFilename .= 'webfront' . DIRECTORY_SEPARATOR;         }
        $fullFilename .= 'user' . DIRECTORY_SEPARATOR . $this->filename;
        $handle = fopen($fullFilename, 'w');
        fwrite($handle, $html);
        fclose($handle);
        //$HTMLData = '<iframe src="user'.DIRECTORY_SEPARATOR .$this->filename.'" border="0" frameborder="0" scrolling="no" style= "width:' . $framewidth . $framewidthtype . '; height:' . $frameheight . $frameheighttype . ';"/></iframe>';
        $HTMLData = '<iframe src="sunmoonline.php" border="0" frameborder="0" scrolling="no" style= "width:' . $framewidth . $framewidthtype . '; height:' . $frameheight . $frameheighttype . ';"/></iframe>';
        //$HTMLData .= '<p>width:' . $framewidth . $framewidthtype . '; height:' . $frameheight . $frameheighttype.'</p>';
        //$HTMLData .= '<p>width:' . $canvaswidth . '; height:' . $canvasheight.'</p>';
        $this->sunmoonview = $HTMLData;
        return ($HTMLData);
        }
    

    protected function GetFrameType($value)
        {
        $type = 'px';
        if ($value == 1) {
            $type = 'px';
        } elseif ($value == 2) {
            $type = '%';
        }
        return $type;
        }


    /* Sternengeometrie
     *  HARA            from Hour Angle of celestial body, Local Time + DS + ZC, and date, longitude of observer calculate Azmiuth at observer
     *
     *
     *
     *
     * $Y2 = altitude of the star in degrees, $W = 'true' or all strings without 'true', $PR = Atmospheric Pressure, $TR = Temperature
     *
     * HARA uses LctUT, LctGDay, LctGMonth, LctGYear, UTGST, GSTLST, HMSDH
     * HA = LST - RA ,  $G = RA in H Hour:min:sec, LST berechnet aus der lokalen Zeit LC Hour:min:sec +DS + TZ , Local day month year , longitude of observer
     * zuerst UT -> GST dann GST -> LST
     */
    protected function HARA($HH, $HM, $HS, $LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY, $L)
        {
        $A = $this->LctUT(intval($LCH), intval($LCM), intval($LCS), intval($DS), floatval($ZC), intval($LD), intval($LM), intval($LY))['UTDec'];        // verschiebt auf Greenwich time  - Sommerzeit minus Timezone
        $B = $this->LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $C = $this->LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $D = $this->LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $E = $this->UTGST(floatval($A), 0, 0, intval($B), intval($C), intval($D));      // Stunden als Komma
        $F = $this->GSTLST($E, 0, 0, $L);                                       // Longitude vom Standort berücksichtigen
        $G = $this->HMSDH(floatval($HH), intval($HM), intval($HS));             // right ascensor
        $H = $F - $G;
        if ($H < 0) {  $HARA = 24 + $H;        } 
        else {             $HARA = $H;        }
        return $HARA;
        }
    /* LST Local sidereal time für eigene Position
     *
     * check with https://www.localsiderealtime.com/
     */
    public function getLST()
        {
        $location = $this->getlocation();
        $P = $location['Latitude'];
        $L = $location['Longitude'];

        $day = intval(date('d'));
        $month = intval(date('m'));
        $year = intval(date('Y'));
        $Hour = intval(date('H'));
        $Minute = intval(date('i'));
        $Second = intval(date('s'));
        $summer = intval(date('I'));
        $timezone = intval(date('O'))/100;          // +0100
        echo "Use Local Time with Timezone is $timezone Summertime is $summer and convert it to UT.\n";    
        $ut  = $this->LctUT($Hour,$Minute,$Second, $summer, $timezone, $day,$month,$year);
        $gst = $this->UTGST($ut['UTDec'],0,0,$day,$month,$year);                                                // das ist die intelligente Formel
        $F = $this->GSTLST($gst, 0, 0, $L); 
        $result = $this->DHHMS($F);
        $result["hoursangle"]=$F;
        return ($result);
        }
        

    /* convertiert UT (unified time) to GST (Greenwich sidereal time) 
     * aktuelle Uhrzeit H:M:s d.m.Y als UT , keine Sommerzeit, keine Zeitverschiebung, müssen vorher berücksichtigt werden
     *
     *
     * helpfull on avoiding and calculating GST
     * https://astronomy.stackexchange.com/questions/21002/how-to-find-greenwich-mean-sideral-time
     *
     * GMST (in seconds at UT1=0) = 24110.54841 + 8640184.812866 * T + 0.093104 * T^2 - 0.0000062 * T^3  T = d / 36525,  d = JD - 2451545.0 (J2000.0)
     * GMST in hours = GMSTs/3600  =>  6.697374558 +  2400,051336907 * T + 0.000025862 * T * T
     * for degrees (*15) and instead of centuries: 100,460618 + 0.985647 * d
     */
    public function UTGST(float $UH, float $UM, float $US, int $GD, int $GM, int $GY)
        {
        $A = $this->CDJD(floatval($GD), intval($GM), intval($GY));                      // umrechnen auf julian date
        $B = $A - 2451545;
        $C = $B / 36525;
        $D = 6.697374558 + (2400.051336 * $C) + (0.000025862 * $C * $C);                // GMST at midnight in hours  
        $E = $D - (24 * $this->roundvariantint($D / 24));                               // reduziert auf [0..24]
        $F = $this->HMSDH($UH, intval($UM), intval($US));                               // Uhrzeit UT unified time (local time minus Summertime - timezone)
        $G = $F * 1.002737909;                                                          // is the Earth's sidereal rotation rate, in sidereal seconds/UT second
        $H = $E + $G;
        $UTGST = $H - (24 * $this->roundvariantint($H / 24));
        return $UTGST;
        }
    /* convertiert GST (Greenwich sidereal time) mit longitude [Degrees] of observer to LST (local sidereal time)
     */
    public function GSTLST(float $GH, int $GM, int $GS, float $L)
        {
        $A = $this->HMSDH($GH, $GM, $GS);
        $B = $L / 15;                                               // degrees to hours, by 15
        $C = $A + $B;                                               // LST = GST + long in hours
        $GSTLST = $C - (24 * $this->roundvariantint($C / 24));      // auf [0..24] verkürzen
        return $GSTLST;
        }

    /*  Geometrie, erster Teil
     */
    protected function HORDec($AZD, $AZM, $AZS, $ALD, $ALM, $ALS, $P)
        {
        $A = $this->DMSDD(floatval($AZD), intval($AZM), intval($AZS));
        $B = $this->DMSDD(floatval($ALD), intval($ALM), intval($ALS));
        $C = $this->Radians($A);
        $D = $this->Radians($B);
        $E = $this->Radians($P);
        $F = sin($D) * sin($E) + cos($D) * cos($E) * cos($C);
        $HORDec = $this->Degrees(asin($F));
        return $HORDec;
    }

    // umrechnen D:M:S aud DD.DD

    public function DMSDD(float $D, int $M, int $S)
    {
        $A = abs($S) / 60;
        $B = (abs($M) + $A) / 60;
        $C = abs($D) + $B;

        if (($D < 0) or ($M < 0) or ($S < 0)) {
            $DMSDD = $C * (-1);
        } else {
            $DMSDD = $C;
        }
        return $DMSDD;
    }


    /* Umrechnung grad auf rad /360*2PI
     */
    protected function Radians($W)
        {
        $Radians = $W * 0.01745329252;
        return $Radians;
        }

    /* Umrechnung rad auf grad *360/2PI
     */
    protected function Degrees($W)
        {
        $Degrees = $W * 57.29577951;
        return $Degrees;
        }

    protected function HORHa($AZD, $AZM, $AZS, $ALD, $ALM, $ALS, $P)
    {
        $A = $this->DMSDD(floatval($AZD), intval($AZM), intval($AZS));
        $B = $this->DMSDD(floatval($ALD), intval($ALM), intval($ALS));
        $C = $this->Radians($A);
        $D = $this->Radians($B);
        $E = $this->Radians($P);
        $F = sin($D) * sin($E) + cos($D) * cos($E) * cos($C);
        $G = -cos($D) * cos($E) * sin($C);
        $H = sin($D) - sin($E) * $F;
        $I = $this->DDDH($this->Degrees(atan2($G, $H)));
        $HORHa = $I - 24 * $this->roundvariantint($I / 24);
        return $HORHa;
    }

    protected function DDDH($DD)
    {
        $DDDH = $DD / 15;
        return $DDDH;
    }

    protected function EQElat($RAH, $RAM, $RAS, $DD, $DM, $DS, $GD, $GM, $GY)
    {
        $A = $this->Radians($this->DHDD($this->HMSDH(floatval($RAH), intval($RAM), intval($RAS))));
        $B = $this->Radians($this->DMSDD(floatval($DD), intval($DM), intval($DS)));
        $C = $this->Radians($this->Obliq($GD, $GM, $GY));
        $D = sin($B) * cos($C) - cos($B) * sin($C) * sin($A);
        $EQElat = $this->Degrees(asin($D));
        return $EQElat;
    }

    protected function DHDD($DH)
    {
        $DHDD = $DH * 15;
        return $DHDD;
    }

    protected function Obliq($GD, $GM, $GY)
    {
        $A = $this->CDJD(floatval($GD), intval($GM), intval($GY));
        $B = $A - 2415020;
        $C = ($B / 36525) - 1;
        $D = $C * (46.815 + $C * (0.0006 - ($C * 0.00181)));
        $E = $D / 3600;
        $Obliq = 23.43929167 - $E + $this->NutatObl($GD, $GM, $GY);
        return $Obliq;
    }

    protected function NutatObl($GD, $GM, $GY)
    {
        $DJ = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020;
        $T = $DJ / 36525;
        $T2 = $T * $T;
        $A = 100.0021358 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $L1 = 279.6967 + 0.000303 * $T2 + $B;
        $l2 = 2 * $this->Radians($L1);
        $A = 1336.855231 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $D1 = 270.4342 - 0.001133 * $T2 + $B;
        $D2 = 2 * $this->Radians($D1);
        $A = 99.99736056 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M1 = 358.4758 - 0.00015 * $T2 + $B;
        $M1 = $this->Radians($M1);
        $A = 1325.552359 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M2 = 296.1046 + 0.009192 * $T2 + $B;
        $M2 = $this->Radians($M2);
        $A = 5.372616667 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $N1 = 259.1833 + 0.002078 * $T2 - $B;
        $N1 = $this->Radians($N1);
        $N2 = 2 * $N1;

        $DDO = (9.21 + 0.00091 * $T) * cos($N1);
        $DDO = $DDO + (0.5522 - 0.00029 * $T) * cos($l2) - 0.0904 * cos($N2);
        $DDO = $DDO + 0.0884 * cos($D2) + 0.0216 * cos($l2 + $M1);
        $DDO = $DDO + 0.0183 * cos($D2 - $N1) + 0.0113 * cos($D2 + $M2);
        $DDO = $DDO - 0.0093 * cos($l2 - $M1) - 0.0066 * cos($l2 - $N1);

        $NutatObl = $DDO / 3600;
        return $NutatObl;
    }

    protected function EQElong($RAH, $RAM, $RAS, $DD, $DM, $DS, $GD, $GM, $GY)
    {
        $A = $this->Radians($this->DHDD($this->HMSDH(floatval($RAH), intval($RAM), intval($RAS))));
        $B = $this->Radians($this->DMSDD(floatval($DD), intval($DM), intval($DS)));
        $C = $this->Radians($this->Obliq($GD, $GM, $GY));
        $D = sin($A) * cos($C) + tan($B) * sin($C);
        $E = cos($A);
        $F = $this->Degrees(atan2($D, $E));
        $EQElong = $F - 360 * $this->roundvariantint($F / 360);
        return $EQElong;
    }

    protected function EQGlong($RAH, $RAM, $RAS, $DD, $DM, $DS)
    {
        $A = $this->Radians($this->DHDD($this->HMSDH(floatval($RAH), intval($RAM), intval($RAS))));
        $B = $this->Radians($this->DMSDD(floatval($DD), intval($DM), intval($DS)));
        $C = cos($this->Radians(27.4));
        $D = sin($this->Radians(27.4));
        $E = $this->Radians(192.25);
        $F = cos($B) * $C * cos($A - $E) + sin($B) * $D;
        $G = sin($B) - $F * $D;
        $H = cos($B) * sin($A - $E) * $C;
        $I = $this->Degrees(atan2($G, $H)) + 33;
        $EQGlong = $I - 360 * $this->roundvariantint($I / 360);
        return $EQGlong;
    }

    protected function EQGlat($RAH, $RAM, $RAS, $DD, $DM, $DS)
    {
        $A = $this->Radians($this->DHDD($this->HMSDH(floatval($RAH), intval($RAM), intval($RAS))));
        $B = $this->Radians($this->DMSDD(floatval($DD), intval($DM), intval($DS)));
        $C = cos($this->Radians(27.4));
        $D = sin($this->Radians(27.4));
        $E = $this->Radians(192.25);
        $F = cos($B) * $C * cos($A - $E) + sin($B) * $D;
        $EQGlat = $this->Degrees(asin($F));
        return $EQGlat;
    }

    protected function EqOfTime($GD, $GM, $GY)
    {
        $A = $this->SunLong(12, 0, 0, 0, 0, $GD, $GM, $GY);
        $B = $this->DDDH($this->ECRA($A, 0, 0, 0, 0, 0, $GD, $GM, $GY));
        $C = $this->GSTUT($B, 0, 0, $GD, $GM, $GY)[0];
        $EqOfTime = $C - 12;
        return $EqOfTime;
    }

    protected function SunLong($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
    {
        $AA = $this->LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $BB = $this->LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $CC = $this->LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $UT = $this->LctUT(intval($LCH), intval($LCM), intval($LCS), intval($DS), floatval($ZC), intval($LD), intval($LM), intval($LY))['UTDec'];
        $DJ = $this->CDJD(floatval($AA), intval($BB), intval($CC)) - 2415020;
        $T = ($DJ / 36525) + ($UT / 876600);
        $T2 = $T * $T;
        $A = 100.0021359 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $L = 279.69668 + 0.0003025 * $T2 + $B;
        $A = 99.99736042 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M1 = 358.47583 - (0.00015 + 0.0000033 * $T) * $T2 + $B;
        $EC = 0.01675104 - 0.0000418 * $T - 0.000000126 * $T2;

        $AM = $this->Radians($M1);
        $AT = $this->TrueAnomaly($AM, $EC);
        //  $AE = $this->EccentricAnomaly($AM, $EC);

        $A = 62.55209472 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $A1 = $this->Radians(153.23 + $B);
        $A = 125.1041894 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $B1 = $this->Radians(216.57 + $B);
        $A = 91.56766028 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $C1 = $this->Radians(312.69 + $B);
        $A = 1236.853095 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $D1 = $this->Radians(350.74 - 0.00144 * $T2 + $B);
        $E1 = $this->Radians(231.19 + 20.2 * $T);
        // $A = 183.1353208 * $T;
        // $B = 360 * ($A - $this->roundvariantint($A));
        // $H1 = $this->Radians(353.4 + $B);

        $D2 = 0.00134 * cos($A1) + 0.00154 * cos($B1) + 0.002 * cos($C1);
        $D2 = $D2 + 0.00179 * sin($D1) + 0.00178 * sin($E1);
        // $D3 = 0.00000543 * sin($A1) + 0.00001575 * sin($B1);
        // $D3 = $D3 + 0.00001627 * sin($C1) + 0.00003076 * cos($D1);
        // $D3 = $D3 + 0.00000927 * sin($H1);

        $SR = $AT + $this->Radians($L - $M1 + $D2);
        $TP = 6.283185308;
        $SR = $SR - $TP * $this->roundvariantint($SR / $TP);
        $SunLong = $this->Degrees($SR);
        return $SunLong;
    }

    protected function TrueAnomaly($AM, $EC)
    {
        $TP = 6.283185308;
        $M = $AM - $TP * $this->roundvariantint($AM / $TP);
        $AE = $M;
        step1: //3305
        $D = $AE - ($EC * sin($AE)) - $M;

        if (abs($D) < 0.000001) {
            goto step2; //3320
        }

        $D = $D / (1 - ($EC * cos($AE)));
        $AE = $AE - $D;
        goto step1; //3305

        step2: //3320
        $A = sqrt((1 + $EC) / (1 - $EC)) * tan($AE / 2);
        $AT = 2 * atan($A);
        $TrueAnomaly = $AT;
        return $TrueAnomaly;
    }

    /* from ecliptic lon/lat to right azimut, declination
     */
    protected function ECRA($ELD, $ELM, $ELS, $BD, $BM, $BS, $GD, $GM, $GY)
        {
        $A = $this->Radians($this->DMSDD(floatval($ELD), intval($ELM), intval($ELS)));       //eclon
        $B = $this->Radians($this->DMSDD(floatval($BD), intval($BM), intval($BS)));          //eclat
        $C = $this->Radians($this->Obliq($GD, $GM, $GY));          //obliq
        $D = sin($A) * cos($C) - tan($B) * sin($C); //y
        $E = cos($A);                                //x
        $F = $this->Degrees(atan2($D, $E));                //RA Deg
        $ECRA = $F - 360 * $this->roundvariantint($F / 360);   //RA Deg
        return $ECRA;
        }

    public function GSTUT(float $GSH, int $GSM, int $GSS, int $GD, int $GM, int $GY)
    {
        $A = $this->CDJD(floatval($GD), intval($GM), intval($GY));
        $B = $A - 2451545;                                              // Bezugspunkt J2000
        $C = $B / 36525;                                                // als Century
        $D = 6.697374558 + (2400.051336 * $C) + (0.000025862 * $C * $C);        // als Tag 
        $E = $D - (24 * $this->roundvariantint($D / 24));
        $F = $this->HMSDH($GSH, $GSM, $GSS);
        $G = $F - $E;
        $H = $G - (24 * $this->roundvariantint($G / 24));
        $GSTUT = $H * 0.9972695663;
        if ($GSTUT < (4 / 60)) {
            $eGSTUT = 'Warning';
        } else {
            $eGSTUT = 'OK';
        }
        return [$GSTUT, $eGSTUT];
    }

    protected function MoonSize($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $HP = $this->Radians($this->MoonHP($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR));
        $R = 6378.14 / sin($HP);
        $TH = 384401 * 0.5181 / $R;
        $MoonSize = $TH;
        return $MoonSize;
    }

    protected function MoonHP($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
        {
        $JD = $this->calcJulianDate($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);

        $UT = $this->LctUT(intval($LH), intval($LM), intval($LS), intval($DS), floatval($ZC), intval($DY), intval($MN), intval($YR))['UTDec'];
        $GD = $this->LctGDay($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GM = $this->LctGMonth($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GY = $this->LctGYear($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $T = (($this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020) / 36525) + ($UT / 876600);
        $T2 = $T * $T;

        // $M1 = 27.32158213;
        $M2 = 365.2596407;
        $M3 = 27.55455094;
        $M4 = 29.53058868;
        $M5 = 27.21222039;
        $M6 = 6798.363307;
        $Q = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020 + ($UT / 24);
        // $M1 = $Q / $M1;
        $M2 = $Q / $M2;
        $M3 = $Q / $M3;
        $M4 = $Q / $M4;
        $M5 = $Q / $M5;
        $M6 = $Q / $M6;
        // $M1 = 360 * ($M1 - $this->roundvariantint($M1));
        $M2 = 360 * ($M2 - $this->roundvariantint($M2));
        $M3 = 360 * ($M3 - $this->roundvariantint($M3));
        $M4 = 360 * ($M4 - $this->roundvariantint($M4));
        $M5 = 360 * ($M5 - $this->roundvariantint($M5));
        $M6 = 360 * ($M6 - $this->roundvariantint($M6));

        // $ML = 270.434164 + $M1 - (0.001133 - 0.0000019 * $T) * $T2;
        $MS = 358.475833 + $M2 - (0.00015 + 0.0000033 * $T) * $T2;
        $MD = 296.104608 + $M3 + (0.009192 + 0.0000144 * $T) * $T2;
        $ME1 = 350.737486 + $M4 - (0.001436 - 0.0000019 * $T) * $T2;
        $MF = 11.250889 + $M5 - (0.003211 + 0.0000003 * $T) * $T2;
        $NA = 259.183275 - $M6 + (0.002078 + 0.0000022 * $T) * $T2;
        $A = $this->Radians(51.2 + 20.2 * $T);
        $S1 = sin($A);
        $S2 = sin($this->Radians($NA));
        $B = 346.56 + (132.87 - 0.0091731 * $T) * $T;
        $S3 = 0.003964 * sin($this->Radians($B));
        $C = $this->Radians($NA + 275.05 - 2.3 * $T);
        $S4 = sin($C);
        // $ML = $ML + 0.000233 * $S1 + $S3 + 0.001964 * $S2;
        $MS = $MS - 0.001778 * $S1;
        $MD = $MD + 0.000817 * $S1 + $S3 + 0.002541 * $S2;
        $MF = $MF + $S3 - 0.024691 * $S2 - 0.004328 * $S4;
        $ME1 = $ME1 + 0.002011 * $S1 + $S3 + 0.001964 * $S2;
        $E = 1 - (0.002495 + 0.00000752 * $T) * $T;
        $E2 = $E * $E;
        // $ML = $this->Radians($ML);
        $MS = $this->Radians($MS);
        // $NA = $this->Radians($NA);
        $ME1 = $this->Radians($ME1);
        $MF = $this->Radians($MF);
        $MD = $this->Radians($MD);

        $PM = 0.950724 + 0.051818 * cos($MD) + 0.009531 * cos(2 * $ME1 - $MD);
        $PM = $PM + 0.007843 * cos(2 * $ME1) + 0.002824 * cos(2 * $MD);
        $PM = $PM + 0.000857 * cos(2 * $ME1 + $MD) + $E * 0.000533 * cos(2 * $ME1 - $MS);
        $PM = $PM + $E * 0.000401 * cos(2 * $ME1 - $MD - $MS);
        $PM = $PM + $E * 0.00032 * cos($MD - $MS) - 0.000271 * cos($ME1);
        $PM = $PM - $E * 0.000264 * cos($MS + $MD) - 0.000198 * cos(2 * $MF - $MD);
        $PM = $PM + 0.000173 * cos(3 * $MD) + 0.000167 * cos(4 * $ME1 - $MD);
        $PM = $PM - $E * 0.000111 * cos($MS) + 0.000103 * cos(4 * $ME1 - 2 * $MD);
        $PM = $PM - 0.000084 * cos(2 * $MD - 2 * $ME1) - $E * 0.000083 * cos(2 * $ME1 + $MS);
        $PM = $PM + 0.000079 * cos(2 * $ME1 + 2 * $MD) + 0.000072 * cos(4 * $ME1);
        $PM = $PM + $E * 0.000064 * cos(2 * $ME1 - $MS + $MD) - $E * 0.000063 * cos(2 * $ME1 + $MS - $MD);
        $PM = $PM + $E * 0.000041 * cos($MS + $ME1) + $E * 0.000035 * cos(2 * $MD - $MS);
        $PM = $PM - 0.000033 * cos(3 * $MD - 2 * $ME1) - 0.00003 * cos($MD + $ME1);
        $PM = $PM - 0.000029 * cos(2 * ($MF - $ME1)) - $E * 0.000029 * cos(2 * $MD + $MS);
        $PM = $PM + $E2 * 0.000026 * cos(2 * ($ME1 - $MS)) - 0.000023 * cos(2 * ($MF - $ME1) + $MD);
        $PM = $PM + $E * 0.000019 * cos(4 * $ME1 - $MS - $MD);

        $MoonHP = $PM;
        return $MoonHP;
    }

    protected function FullMoon($DS, $ZC, $DY, $MN, $YR)
    {
        $D0 = $this->LctGDay(12, 0, 0, $DS, $ZC, $DY, $MN, $YR);
        $M0 = $this->LctGMonth(12, 0, 0, $DS, $ZC, $DY, $MN, $YR);
        $Y0 = $this->LctGYear(12, 0, 0, $DS, $ZC, $DY, $MN, $YR);

        if ($Y0 < 0) {
            $Y0 = $Y0 + 1;
        }

        $J0 = $this->CDJD(0, 1, intval($Y0)) - 2415020;
        $DJ = $this->CDJD(floatval($D0), intval($M0), intval($Y0)) - 2415020;
        $K = $this->LINT((($Y0 - 1900 + (($DJ - $J0) / 365)) * 12.3685) + 0.5);
        // $TN = $K / 1236.85;
        $TF = ($K + 0.5) / 1236.85;

        // $E1 = $this->roundvariantint($E);
        // $B = $B + $DD + ($E - $E1);
        // $B1 = $this->roundvariantint($B);
        // $A = $E1 + $B1;
        // $B = $B - $B1;
        //$NI = $A;
        //$NF = $B;
        //$NB = $F;
        $T = $TF;
        $K = $K + 0.5;
        $T2 = $T * $T;
        $E = 29.53 * $K;
        $C = 166.56 + (132.87 - 0.009173 * $T) * $T;
        $C = $this->Radians($C);
        $B = 0.00058868 * $K + (0.0001178 - 0.000000155 * $T) * $T2;
        $B = $B + 0.00033 * sin($C) + 0.75933;
        $A = $K / 12.36886;
        $A1 = 359.2242 + 360 * $this->FRACT($A) - (0.0000333 + 0.00000347 * $T) * $T2;
        $A2 = 306.0253 + 360 * $this->FRACT($K / 0.9330851);
        $A2 = $A2 + (0.0107306 + 0.00001236 * $T) * $T2;
        $A = $K / 0.9214926;
        $F = 21.2964 + 360 * $this->FRACT($A) - (0.0016528 + 0.00000239 * $T) * $T2;
        $A1 = $this->UnwindDeg($A1);
        $A2 = $this->UnwindDeg($A2);
        $F = $this->UnwindDeg($F);
        $A1 = $this->Radians($A1);
        $A2 = $this->Radians($A2);
        $F = $this->Radians($F);

        $DD = (0.1734 - 0.000393 * $T) * sin($A1) + 0.0021 * sin(2 * $A1);
        $DD = $DD - 0.4068 * sin($A2) + 0.0161 * sin(2 * $A2) - 0.0004 * sin(3 * $A2);
        $DD = $DD + 0.0104 * sin(2 * $F) - 0.0051 * sin($A1 + $A2);
        $DD = $DD - 0.0074 * sin($A1 - $A2) + 0.0004 * sin(2 * $F + $A1);
        $DD = $DD - 0.0004 * sin(2 * $F - $A1) - 0.0006 * sin(2 * $F + $A2) + 0.001 * sin(2 * $F - $A2);
        $DD = $DD + 0.0005 * sin($A1 + 2 * $A2);
        $E1 = $this->roundvariantint($E);
        $B = $B + $DD + ($E - $E1);
        $B1 = $this->roundvariantint($B);
        $A = $E1 + $B1;
        $B = $B - $B1;
        $FI = $A;
        $FF = $B;
        // $FB = $F;
        $FullMoon = $FI + 2415020 + $FF;
        return $FullMoon;
    }

    protected function LINT($W)
    {
        $LINT = $this->IINT($W) + $this->IINT(((1 * sign($W)) - 1) / 2);
        return $LINT;
    }

    protected function IINT($W)
    {
        $IINT = $this->sign($W) * $this->roundvariantint(abs($W));
        return $IINT;
    }

    // Berechnung der Mondauf/untergangs Zeiten

    protected function sign($number)
    {
        return ($number > 0) ? 1 : (($number < 0) ? -1 : 0);
    }

    protected function FRACT($W)
    {
        $FRACT = $W - $this->LINT($W);
        return $FRACT;
    }



    // Converting decimal hours to hours, minutes and seconds

    public function UTLct(float $UH, float $UM, float $US, int $DS, float $ZC, int $GD, int $GM, int $GY)
    {
        $A = $this->HMSDH($UH, intval($UM), intval($US));
        $B = $A + $ZC;
        $C = $B + $DS;
        $D = $this->CDJD(floatval($GD), intval($GM), intval($GY)) + ($C / 24);
        $E = $this->JDCDay($D);
        $F = $this->JDCMonth($D);
        $G = $this->JDCYear($D);
        $E1 = $this->roundvariantfix($E);
        $UTLct = 24 * ($E - $E1);
        return [$UTLct, $E1, $F, $G];
    }

    /* Decimal Hours, Minute, Second to Hours as float, nur positive Werte zusammenzählen
     * one negative value leads to negative hour
     */
    public function HMSDH(float $Hour, int $Minute, int $Second)
        {
        $A = abs($Second) / 60;             // Wert float in Minuten, nur positive Werte zusammenzählen
        $B = (abs($Minute) + $A) / 60;      // Wert float in Stunden
        $C = abs($Hour) + $B;

        if (($Hour < 0) or ($Minute < 0) or ($Second < 0)) { $HMSDH = $C * (-1); } 
        else {    $HMSDH = $C;        }

        return $HMSDH;
        }


    public function LSTGST(float $LH, int $LM, int $LS, float $L)
    {
        $A = $this->HMSDH($LH, $LM, $LS);
        $B = $L / 15;
        $C = $A - $B;
        $LSTGST = $C - (24 * $this->roundvariantint($C / 24));
        return $LSTGST;
    }

    // Conversion of Local Civil Time to UT (Universal Time) --- Achtung: hier wird ein Array ausgegeben !!!

    protected function roundvariantint($value)
    {
        $roundvalue = floor($value);
        return $roundvalue;
    }

    // Conversion of UT (Universal Time) to Local Civil Time --- Achtung: hier wird ein Array ausgegeben !!!

    public function UTDayAdjust(int $UT, int $G1)
    {
        $UTDayAdjust = $UT;

        if (($UT - $G1) < -6) {
            $UTDayAdjust = $UT + 24;
        }

        if (($UT - $G1) > 6) {
            $UTDayAdjust = $UT - 24;
        }
        return $UTDayAdjust;
    }

    protected function ECDec($ELD, $ELM, $ELS, $BD, $BM, $BS, $GD, $GM, $GY)
    {
        $A = $this->Radians($this->DMSDD(floatval($ELD), intval($ELM), intval($ELS)));                      //eclon
        $B = $this->Radians($this->DMSDD(floatval($BD), intval($BM), intval($BS)));                         //eclat
        $C = $this->Radians($this->Obliq($GD, $GM, $GY));                         //obliq
        $D = sin($B) * cos($C) + cos($B) * sin($C) * sin($A);   //sin Dec
        $ECDec = $this->Degrees(asin($D));                             //Dec Deg
        return $ECDec;
    }

    protected function RAHA($RH, $RM, $RS, $LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY, $L)
    {
        $A = $this->LctUT(intval($LCH), intval($LCM), intval($LCS), intval($DS), floatval($ZC), intval($LD), intval($LM), intval($LY))['UTDec'];
        $B = $this->LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $C = $this->LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $D = $this->LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $E = $this->UTGST(floatval($A), 0, 0, intval($B), intval($C), intval($D));
        $F = $this->GSTLST($E, 0, 0, $L);
        $G = $this->HMSDH(floatval($RH), intval($RM), intval($RS));
        $H = $F - $G;
        if ($H < 0) {
            $RAHA = 24 + $H;
        } else {
            $RAHA = $H;
        }
        return $RAHA;
    }

    /* Calculate Azimut, works for moon and sun :
     * Input ist local hour angle (LHA), celestial bodies declination , observers latitude
     * Input ist H [h:m:s],D[g:m:s],P[g.gg] => [C,E,F] 
     *
     * https://en.wikipedia.org/wiki/Solar_azimuth_angle
     *
     * The solar/moon azimuth angle is the azimuth (horizontal angle with respect to north) of the Sun/moon's position.
     * This horizontal coordinate defines the Sun/moon's relative direction along the local horizon, 
     * whereas the solar zenith angle (or its complementary angle solar elevation) defines the Sun's apparent altitude.
     *
     * A 2021 publication presents a method that uses a solar azimuth formula based on the subsolar point and the atan2 function, 
     * as defined in Fortran 90, that gives an unambiguous solution without the need for circumstantial treatment
     * 
     * Sz = G = sin E * sin F + cos E * cos F * cos C       (Altitude)
     * Sy = H =- cos E * cos F * cos C
     * Sx = I = sin E - (sin (F) * sin alt)  
     * atan2(H,I)                               // Arkustangens mit y,x
     *
     * C = ZPX = LHA,  PX = 90o – Dec., PZ = 90o – Lat.
     * Cos PZX = [Cos(PX) – Cos(ZX) . Cos(PZ)]  /  [Sin(ZX) . Sin(PZ)]
     * azimuth = PZX
     * cos az = [sin dec - sin alt * sin lat] / [cos alt * cos lat]
     * Input ist H [h:m:s],D[g:m:s],P[g.gg] => [C,E,F]
     * F => P ist the latidude of the viewer, eg rad(48.2455) for Vienna
     * E lat Moon, F lat Observer C lon diff Moon and Observer
     *
     * Converting RA and DEC to ALT and AZ
     * http://www.stargazing.net/kepler/altaz.html
     * calculate the Azimuth (AZ) and Altitude (ALT) of an object in the sky from the date, time (UT) and the location of your observing site 
     * together with the Right Ascension (RA) and Declination (DEC) of the celestial object
     *
     * As a concrete example, I shall calculate the ALT and AZ of the Messier object M13 for 10th August 1998 at 2310 hrs UT, for Birmingham UK. 
     * The RA and DEC of M13 are given as  RA  = 16 h 41.7 min  DEC = 36 d 28 min, RA = 16.695 hrs = (*15) 250.425 degs, Dec = 36.466667 degs
     * The latitude and longitude of Birmingham UK as LAT = 52 d 30 min North LONG = 1 d 55 min West, N 52.5 degs, W -1.9166667 degs Time 23.166667 hrs UT
     * Date Day = Time hrs/24 convert to J2000.0 : I got -508.53472 days from J2000.0 of for 15:30 UT, 4th April 2008 => 3016.1458 days since J2000.0
     *
     * LST = 100.46 + 0.985647 * d + long + 15*UT    d is the J2000 number of days including the fraction of the day, UT in hours
     * LST = 100.46 + 0.985647 * -508.53472 - 1.9166667 + 15 * 23.166667 = -55.192383 degrees = 304.80762 degrees
     *
     * HA = LST - RA
     */
    protected function EQAz($HH, $HM, $HS, $DD, $DM, $DS, $P)
    {
        $A = $this->HMSDH(floatval($HH), intval($HM), intval($HS));         // HH,HM,HS => HH.HH => A
        $B = $A * 15;                                                       // B = HH.HH * 15  Degrees [0..360]
        $C = $this->Radians($B);                                            // C = rad B
        $D = $this->DMSDD(floatval($DD), intval($DM), intval($DS));         // DD,DM,DS => DD.DD => D
        $E = $this->Radians($D);                                            // E = rad D
        $F = $this->Radians($P);
        $G = sin($E) * sin($F) + cos($E) * cos($F) * cos($C);                // sin alt
        $H = -cos($E) * cos($F) * sin($C);
        $I = sin($E) - (sin($F) * $G);
        $J = $this->Degrees(atan2($H, $I));
        $EQAz = $J - 360 * $this->roundvariantint($J / 360);

        // Alternative
        $K = (sin($E) - (sin($G)*sin($F)))/(cos($G)*cos($F));
        $EQAz1 = $this->Degrees(acos($K));
        echo "EQAz existing $EQAz  and alternative $EQAz1  calculation.\n";
        return $EQAz;
    }

    /* Altidude sollte einfacher zu rechnen sein
     * Input ist local hour angle (LHA), celestial bodies declination , observers latitude
     *
     * https://astronavigationdemystified.com/calculating-azimuth-and-altitude-at-the-assumed-position-by-spherical-trigonometry/ 
     *
    * Input ist H [h:m:s],D[g:m:s],P[g.gg] => [C,E,F]
    * 
    * Sz = G = sin alt = sin E * sin F + cos E * cos F * cos C 
    * alt = asin G 
    *
    * Cos (ZX) =  [Cos(PZ) . Cos(PX)] + [Sin(PZ) . Sin(PX) . Cos(ZPX)]
    * sin(90°−α)=cos(α), cos(90°−α)=sin(α),  ZX = 90o – Alt, C = ZPX = LHA, PX = 90o – Dec., PZ = 90o – Lat.
    * sin alt = [sin dec * sin lat] + [cos dec * cos lat * cos ZPX]
    */
    protected function EQAlt($HH, $HM, $HS, $DD, $DM, $DS, $P)
    {
        $A = $this->HMSDH(floatval($HH), intval($HM), intval($HS));
        $B = $A * 15;
        $C = $this->Radians($B);
        $D = $this->DMSDD(floatval($DD), intval($DM), intval($DS));
        $E = $this->Radians($D);
        $F = $this->Radians($P);
        $G = sin($E) * sin($F) + cos($E) * cos($F) * cos($C);
        $EQAlt = $this->Degrees(asin($G));
        return $EQAlt;
    }

    protected function direction($degree)
    {
        $direction = 0;
        if (($degree >= 0) and ($degree < 22.5)) {
            $direction = 0;
        }
        if (($degree >= 22.5) and ($degree < 45)) {
            $direction = 1;
        }
        if (($degree >= 45) and ($degree < 67.5)) {
            $direction = 2;
        }
        if (($degree >= 67.5) and ($degree < 90)) {
            $direction = 3;
        }
        if (($degree >= 90) and ($degree < 112.5)) {
            $direction = 4;
        }
        if (($degree >= 112.5) and ($degree < 135)) {
            $direction = 5;
        }
        if (($degree >= 135) and ($degree < 157.5)) {
            $direction = 6;
        }
        if (($degree >= 157.5) and ($degree < 180)) {
            $direction = 7;
        }
        if (($degree >= 180) and ($degree < 202.5)) {
            $direction = 8;
        }
        if (($degree >= 202.5) and ($degree < 225)) {
            $direction = 9;
        }
        if (($degree >= 225) and ($degree < 247.5)) {
            $direction = 10;
        }
        if (($degree >= 247.5) and ($degree < 270)) {
            $direction = 11;
        }
        if (($degree >= 270) and ($degree < 292.5)) {
            $direction = 12;
        }
        if (($degree >= 292.5) and ($degree < 315)) {
            $direction = 13;
        }
        if (($degree >= 315) and ($degree < 337.5)) {
            $direction = 14;
        }
        if (($degree >= 337.5) and ($degree <= 360)) {
            $direction = 15;
        }
        return $direction;
    }

    protected function SunTrueAnomaly($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
    {
        $AA = $this->LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $BB = $this->LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $CC = $this->LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $UT = $this->LctUT(intval($LCH), intval($LCM), intval($LCS), intval($DS), floatval($ZC), intval($LD), intval($LM), intval($LY))['UTDec'];
        $DJ = $this->CDJD(floatval($AA), intval($BB), intval($CC)) - 2415020;
        $T = ($DJ / 36525) + ($UT / 876600);
        $T2 = $T * $T;
        // $A = 100.0021359 * $T;
        // $B = 360 * ($A - $this->roundvariantint($A));
        // $L = 279.69668 + 0.0003025 * $T2 + $B;
        $A = 99.99736042 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M1 = 358.47583 - (0.00015 + 0.0000033 * $T) * $T2 + $B;
        $EC = 0.01675104 - 0.0000418 * $T - 0.000000126 * $T2;

        $AM = $this->Radians($M1);
        $SunTrueAnomaly = $this->Degrees($this->TrueAnomaly($AM, $EC));
        return $SunTrueAnomaly;
    }

    protected function SunEcc($GD, $GM, $GY)
    {
        $T = ($this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020) / 36525;
        $T2 = $T * $T;
        $SunEcc = 0.01675104 - 0.0000418 * $T - 0.000000126 * $T2;
        return $SunEcc;
    }

    public function Radiant_Power()
    {
        $nfac = $this->ReadAttributeInteger('nfac'); // atmospheric turbidity (2=clear, 4-5=smoggy)
        // BRAS
        $el = $this->ReadAttributeFloat('sunaltitude'); // Elevation angle
        $R = $this->ReadAttributeFloat('sundistance') / 1.496e+8; // distance
        $radiant_power = 0;
        if($R != 0)
        {
            $sinel = sin(deg2rad($el));
            $io = $sinel * self::NREL / (pow($R, 2));

            if ($sinel >= 0) {
                # optical air mass (bras eqn 2.22)
                $m = 1.0 / ($sinel + 0.15 * pow($el + 3.885, -1.253));
                # molecular scattering coefficient (bras eqn 2.26)
                $a1 = 0.128 - 0.054 * log($m) / log(10.0);
                # clear-sky radiation at earth surface W / m^2 (bras eqn 2.25)
                $sr = $io * exp(-$nfac * $a1 * $m);
                $radiant_power = round($sr);
            }

            // RS
            //def solar_rad_RS(lat, lon, altitude_m, ts=None, atc=0.8):
            $calculate_rs = false;
            if($calculate_rs)
            {
                $atc = 0.8;
                $z = 0;
                $sinal = sin(deg2rad($el));
                if ($sinal >= 0){
                    $rm = pow((288.0-0.0065*$z)/288.0,5.256)/($sinal+0.15*pow($el+3.885,-1.253));
                    $toa = self::NREL * $sinal / (pow($R,2));
                    $radiant_power = $toa * pow($atc, $rm);
                }
            }
        }
        else{
            $radiant_power = 0;
        }
        return $radiant_power;
    }

    /* die Mondformel, bekommt die lokale Zeit (zB Wien UTC+1) gerundet auf hundertsel Sekunden (LH:LM:LS) plus Info Sommerzeit (DS) und lokale Zeitzone (ZC) plus Datum DY.MD.YR
     *
     *
     * Berechnungen beziehen sich auf ein Äqinoktium, bestimmter Bezugspunkt für Sternberechnungen https://de.wikipedia.org/wiki/%C3%84quinoktium
     * B1900	Julianischer Kalender 2415020,313	Gregorianischer Kalender 31. Dez. 1899, 19:31 UT 
     * Zwischen jeder Standardepoche sind es 25 julianische Jahre:
     *
     * http://www.geoastro.de/moonmotion/index.html , http://www.geoastro.de/moon_motion/index.html , http://www.geoastro.de/moonpos/index.html
     * True longitude of Moon = mean longitude + major inequality + evection + variation + annual inequality + reduction to ecliptic + parallactic inequality + more terms
     * mean longitude of the Moon, measured from the mean position of the perigee, 
     *          Time T = (JD - 2451545)/36525   Bezugspunkt J2000.0 statt wie in den Formeln unten B1900
     *          L0 = 218.31617 + 481267.88088*T - 4.06*T*T/3600.0
     *          MD = 134.96292 + 477198.86753*T + 33.25*T*T/3600.0
     *
     *  T   = (JD - 2451545)/36525 // JD = Julian Day
     *  LST = 280.46061837 + 360.98564736629*(JD-2451545) + 0.000387933*T^2 - T^3/38710000 + LONG
     *  LHA = local hour angle = LST - 15*RA // LST = mean local sidereal time
     *
     *          Q = JD  - 2415020 + UT/24    Bezugspunkt B1900
     *          Time T = Q / 36525           100 Erdenjahre, julianisch   
     *          M1 = 27.32158213   Rest Q/M1 * 360            $M1 = 360 * ($M1 - $this->roundvariantint($M1));    27.32158213
                481267.8831/36525*Q
     *          $ML = 270.434164 + $M1 - (0.001133 - 0.0000019 * $T) * $T^2;
     *
     * You have JD and T. Then calculate the angles L', M, M', D and F by means of the following formulae, in which the various constants are expressed in degrees and decimals.
     * Moon's mean longitude:                           L' = 270.434164 + 481267.8831 × T
     * Sun's mean anomaly:                              M = 358.475833 + 35999.0498 × T
     * Moon's mean anomaly:                             M' = 296.104608 + 477198.8491 × T
     * Moon's mean elongation:                          D = 350.737486 + 445267.1142 × T
     * Mean distance of Moon from its ascending node:   F = 11.250889 + 483202.0251 × T
     * 
     * https://doncarona.tamu.edu/apps/moon/now.html
     *
     * http://www.planetaryorbits.com/tutorial-javascript-orbit-simulation-calculate-moon-right-ascension-and-declination.html
     *
     * http://www.geoastro.de/elevazmoon/basics/index.htm
     */
    protected function MoonLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR, $debug=false)
        {
        $JD = $this->calcJulianDateOnEpoche($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR,"J2000");
        if ($debug) echo " MoonLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR) Julian Date is ".json_encode($JD)." \n";
        $T= $JD["T"];
        $T2 = $T * $T;
        $ML0 = 218.31617 + 481267.88088*$T - 4.06*$T2/3600.0;               // 481267.88088 ist eine Konstante, siderealische Umlaufzeit Mond, 218.31617 Konstante auf J2000 oder 270.434164 auf B1900 
        $ML0 = $ML0 / 360;
        $ML0 = 360 * ($ML0 - $this->roundvariantint($ML0));

        $UT = $this->LctUT(intval($LH), intval($LM), intval($LS), intval($DS), floatval($ZC), intval($DY), intval($MN), intval($YR))['UTDec'];      // Tagesuhrzeit UTC Stunden mit Komma
        $GD = $this->LctGDay($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GM = $this->LctGMonth($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GY = $this->LctGYear($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $T = (($this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020) / 36525) + ($UT / 876600);           //Julianischer Kalender B1900, in Centuries, nur einfache Schaltjahre
        $T2 = $T * $T;

        $M1 = 27.32158213;          // Tage, Mondumlaufzeit, Sidirische Umlaufszeit (Im Bezug auf die Sonne) 27.321 661
        $M2 = 365.2596407;          // Erdumlaufzeit
        $M3 = 27.55455094;          // Mondumlaufzeit von Erde aus
        $M4 = 29.53058868;          // Synodischer Monat (von Neumond zu Neumond)
        $M5 = 27.21222039;
        $M6 = 6798.363307;
        $Q = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020 + ($UT / 24);
        $M1 = $Q / $M1;
        $M2 = $Q / $M2;
        $M3 = $Q / $M3;
        $M4 = $Q / $M4;
        $M5 = $Q / $M5;
        $M6 = $Q / $M6;
        $M1 = 360 * ($M1 - $this->roundvariantint($M1));
        $M2 = 360 * ($M2 - $this->roundvariantint($M2));
        $M3 = 360 * ($M3 - $this->roundvariantint($M3));
        $M4 = 360 * ($M4 - $this->roundvariantint($M4));
        $M5 = 360 * ($M5 - $this->roundvariantint($M5));
        $M6 = 360 * ($M6 - $this->roundvariantint($M6));

        $ML = 270.434164 + $M1 - (0.001133 - 0.0000019 * $T) * $T2;         // Moon's mean longitude L' = 270.434164 + 481267.8831 × T, hier genauer T2 is T*T als T^2
        $MS = 358.475833 + $M2 - (0.00015 + 0.0000033 * $T) * $T2;          // Sun's mean anomaly:   M = 358.475833 + 35999.0498 × T
        $MD = 296.104608 + $M3 + (0.009192 + 0.0000144 * $T) * $T2;         // Moons mean anomaly
        $ME1 = 350.737486 + $M4 - (0.001436 - 0.0000019 * $T) * $T2;        // Moons mean elogation
        $MF = 11.250889 + $M5 - (0.003211 + 0.0000003 * $T) * $T2;          // Moons mean distance 
        $NA = 259.183275 - $M6 + (0.002078 + 0.0000022 * $T) * $T2;

        $MLalt  = $this->modulo(270.434164 + 481267.8831 * $T,360);
        $MSalt  = $this->modulo(358.475833 + 35999.0498  * $T,360);
        $MDalt  = $this->modulo(296.104608 + 477198.8491 * $T,360);
        $ME1alt = $this->modulo(350.737486 + 445267.1142 * $T,360);
        $MFalt  = $this->modulo(11.250889 + 483202.0251  * $T,360);

        if ($debug) 
            {
            // http://www.braeunig.us/space/plntpos.htm  , rechnet auch mit B1900    
            echo "      MoonLong Julianisches B1900 Datum : $Q century $T , check ".($T*36525+2415020)." ".($Q/36525)."\n";
            echo "      calc  270.434164 + 481267.8831 * T oder ".(481267.8831/36525)." * Q , check ".((1/$M1)*360)." \n";
            if ($ML>360) $MLd = ($ML-360); else $MLd = $ML;
            echo "               compare results simple $MLalt  detailed $MLd  on J2000: $ML0 \n";
            }

        $MDrad  = $this->Radians($MDalt);
        $ME1rad = $this->Radians($ME1alt);
        $MSrad  = $this->Radians($MSalt);
        $MFrad  = $this->Radians($MFalt);

        if ($debug) 
            {
            echo "L  Moon mean longitude , quick guess is $ML here or acc Braeunig $MLalt \n";
            echo "M  Sun  mean anomaly   , quick guess is $MS here or acc Braeunig $MSalt \n";
            echo "MD Moon  mean anomaly  , quick guess is $MD here or acc Braeunig $MDalt $MDrad\n";
            echo "D  Moon mean elongation, quick guess is $ME1 here or acc Braeunig $ME1alt \n";
            echo "F  Moon mean distance  , quick guess is $MF here or acc Braeunig $MFalt \n";
            }

        $MLdeg =  6.288750 * sin($MDrad) ;
        $MLdeg += 1.274018 * sin( (2*$ME1rad) - $MDrad);
        $MLdeg += 0.658309 * sin(2*$ME1rad);
        $MLdeg += 0.213616 * sin(2*$MDrad);
        $MLdeg += (-0.185596) * sin($MSrad); 
        $MLdeg += (-0.114336) * sin(2*$MFrad) ;

        $MLnew = $this->modulo($MLalt + $MLdeg,360);

        //$A1 = (6.288750 * sin($MDrad));
        if ($debug) echo "Moon longitude is  acc Braeunig $MLalt + $MLdeg = $MLnew\n";

        $A = $this->Radians(51.2 + 20.2 * $T);
        $S1 = sin($A);
        $S2 = sin($this->Radians($NA));
        $B = 346.56 + (132.87 - 0.0091731 * $T) * $T;
        $S3 = 0.003964 * sin($this->Radians($B));
        $C = $this->Radians($NA + 275.05 - 2.3 * $T);
        $S4 = sin($C);
        $ML = $ML + 0.000233 * $S1 + $S3 + 0.001964 * $S2;
        $MS = $MS - 0.001778 * $S1;
        $MD = $MD + 0.000817 * $S1 + $S3 + 0.002541 * $S2;
        $MF = $MF + $S3 - 0.024691 * $S2 - 0.004328 * $S4;
        $ME1 = $ME1 + 0.002011 * $S1 + $S3 + 0.001964 * $S2;
        $E = 1 - (0.002495 + 0.00000752 * $T) * $T;
        $E2 = $E * $E;
        $ML = $this->Radians($ML);
        $MS = $this->Radians($MS);
        // $NA = $this->Radians($NA);
        $ME1 = $this->Radians($ME1);
        $MF = $this->Radians($MF);
        $MD = $this->Radians($MD);

        /*  Moon's mean longitude:                          L' = 270.434164 + 481267.8831 × T
            Sun's mean anomaly:                             M = 358.475833 + 35999.0498 × T
            Moon's mean anomaly:                            M' = 296.104608 + 477198.8491 × T
            Moon's mean elongation:                         D = 350.737486 + 445267.1142 × T
            Mean distance of Moon from its ascending node:  F = 11.250889 + 483202.0251 × T

            l = L' + 6.288750 sin M' + 1.274018 sin(2D–M') + 0.658309 sin 2D + 0.213616 sin 2M' – 0.185596 sin M – 0.114336 sin 2F 
         */
        $L = 6.28875 * sin($MD) + 1.274018 * sin(2 * $ME1 - $MD);
        $L = $L + 0.658309 * sin(2 * $ME1) + 0.213616 * sin(2 * $MD);
        $L = $L - $E * 0.185596 * sin($MS) - 0.114336 * sin(2 * $MF);
        $L = $L + 0.058793 * sin(2 * ($ME1 - $MD));
        $L = $L + 0.057212 * $E * sin(2 * $ME1 - $MS - $MD) + 0.05332 * sin(2 * $ME1 + $MD);
        $L = $L + 0.045874 * $E * sin(2 * $ME1 - $MS) + 0.041024 * $E * sin($MD - $MS);
        $L = $L - 0.034718 * sin($ME1) - $E * 0.030465 * sin($MS + $MD);
        $L = $L + 0.015326 * sin(2 * ($ME1 - $MF)) - 0.012528 * sin(2 * $MF + $MD);
        $L = $L - 0.01098 * sin(2 * $MF - $MD) + 0.010674 * sin(4 * $ME1 - $MD);
        $L = $L + 0.010034 * sin(3 * $MD) + 0.008548 * sin(4 * $ME1 - 2 * $MD);
        $L = $L - $E * 0.00791 * sin($MS - $MD + 2 * $ME1) - $E * 0.006783 * sin(2 * $ME1 + $MS);
        $L = $L + 0.005162 * sin($MD - $ME1) + $E * 0.005 * sin($MS + $ME1);
        $L = $L + 0.003862 * sin(4 * $ME1) + $E * 0.004049 * sin($MD - $MS + 2 * $ME1);
        $L = $L + 0.003996 * sin(2 * ($MD + $ME1)) + 0.003665 * sin(2 * $ME1 - 3 * $MD);
        $L = $L + $E * 0.002695 * sin(2 * $MD - $MS) + 0.002602 * sin($MD - 2 * ($MF + $ME1));
        $L = $L + $E * 0.002396 * sin(2 * ($ME1 - $MD) - $MS) - 0.002349 * sin($MD + $ME1);
        $L = $L + $E2 * 0.002249 * sin(2 * ($ME1 - $MS)) - $E * 0.002125 * sin(2 * $MD + $MS);
        $L = $L - $E2 * 0.002079 * sin(2 * $MS) + $E2 * 0.002059 * sin(2 * ($ME1 - $MS) - $MD);
        $L = $L - 0.001773 * sin($MD + 2 * ($ME1 - $MF)) - 0.001595 * sin(2 * ($MF + $ME1));
        $L = $L + $E * 0.00122 * sin(4 * $ME1 - $MS - $MD) - 0.00111 * sin(2 * ($MD + $MF));
        $L = $L + 0.000892 * sin($MD - 3 * $ME1) - $E * 0.000811 * sin($MS + $MD + 2 * $ME1);
        $L = $L + $E * 0.000761 * sin(4 * $ME1 - $MS - 2 * $MD);
        $L = $L + $E2 * 0.000704 * sin($MD - 2 * ($MS + $ME1));
        $L = $L + $E * 0.000693 * sin($MS - 2 * ($MD - $ME1));
        $L = $L + $E * 0.000598 * sin(2 * ($ME1 - $MF) - $MS);
        $L = $L + 0.00055 * sin($MD + 4 * $ME1) + 0.000538 * sin(4 * $MD);
        $L = $L + $E * 0.000521 * sin(4 * $ME1 - $MS) + 0.000486 * sin(2 * $MD - $ME1);
        $L = $L + $E2 * 0.000717 * sin($MD - 2 * $MS);
        
        $MM = $this->Unwind($ML + $this->Radians($L));

        $MoonLong = $this->Degrees($MM);                // rad auf Grad umrechnen
        if ($debug) echo "Add $L in degrees to get $MoonLong $MM\n";
        return $MoonLong;
        }

    /* 2PI abziehen wenn möglich W - 2PI*(int(W/2PI) */
    protected function Unwind($W)
        {
        $Unwind = $W - 6.283185308 * $this->roundvariantint($W / 6.283185308);
        return $Unwind;
        }

    protected function MoonLat($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $UT = $this->LctUT(intval($LH), intval($LM), intval($LS), intval($DS), floatval($ZC), intval($DY), intval($MN), intval($YR))['UTDec'];
        $GD = $this->LctGDay($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GM = $this->LctGMonth($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GY = $this->LctGYear($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $T = (($this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020) / 36525) + ($UT / 876600);
        $T2 = $T * $T;

        // $M1 = 27.32158213;
        $M2 = 365.2596407;
        $M3 = 27.55455094;
        $M4 = 29.53058868;
        $M5 = 27.21222039;
        $M6 = 6798.363307;
        $Q = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020 + ($UT / 24);
        // $M1 = $Q / $M1;
        $M2 = $Q / $M2;
        $M3 = $Q / $M3;
        $M4 = $Q / $M4;
        $M5 = $Q / $M5;
        $M6 = $Q / $M6;
        //$M1 = 360 * ($M1 - $this->roundvariantint($M1));
        $M2 = 360 * ($M2 - $this->roundvariantint($M2));
        $M3 = 360 * ($M3 - $this->roundvariantint($M3));
        $M4 = 360 * ($M4 - $this->roundvariantint($M4));
        $M5 = 360 * ($M5 - $this->roundvariantint($M5));
        $M6 = 360 * ($M6 - $this->roundvariantint($M6));

        // $ML = 270.434164 + $M1 - (0.001133 - 0.0000019 * $T) * $T2;
        $MS = 358.475833 + $M2 - (0.00015 + 0.0000033 * $T) * $T2;
        $MD = 296.104608 + $M3 + (0.009192 + 0.0000144 * $T) * $T2;
        $ME1 = 350.737486 + $M4 - (0.001436 - 0.0000019 * $T) * $T2;
        $MF = 11.250889 + $M5 - (0.003211 + 0.0000003 * $T) * $T2;
        $NA = 259.183275 - $M6 + (0.002078 + 0.0000022 * $T) * $T2;
        $A = $this->Radians(51.2 + 20.2 * $T);
        $S1 = sin($A);
        $S2 = sin($this->Radians($NA));
        $B = 346.56 + (132.87 - 0.0091731 * $T) * $T;
        $S3 = 0.003964 * sin($this->Radians($B));
        $C = $this->Radians($NA + 275.05 - 2.3 * $T);
        $S4 = sin($C);
        // $ML = $ML + 0.000233 * $S1 + $S3 + 0.001964 * $S2;
        $MS = $MS - 0.001778 * $S1;
        $MD = $MD + 0.000817 * $S1 + $S3 + 0.002541 * $S2;
        $MF = $MF + $S3 - 0.024691 * $S2 - 0.004328 * $S4;
        $ME1 = $ME1 + 0.002011 * $S1 + $S3 + 0.001964 * $S2;
        $E = 1 - (0.002495 + 0.00000752 * $T) * $T;
        $E2 = $E * $E;
        // $ML = $this->Radians($ML);
        $MS = $this->Radians($MS);
        $NA = $this->Radians($NA);
        $ME1 = $this->Radians($ME1);
        $MF = $this->Radians($MF);
        $MD = $this->Radians($MD);

        $G = 5.128189 * sin($MF) + 0.280606 * sin($MD + $MF);
        $G = $G + 0.277693 * sin($MD - $MF) + 0.173238 * sin(2 * $ME1 - $MF);
        $G = $G + 0.055413 * sin(2 * $ME1 + $MF - $MD) + 0.046272 * sin(2 * $ME1 - $MF - $MD);
        $G = $G + 0.032573 * sin(2 * $ME1 + $MF) + 0.017198 * sin(2 * $MD + $MF);
        $G = $G + 0.009267 * sin(2 * $ME1 + $MD - $MF) + 0.008823 * sin(2 * $MD - $MF);
        $G = $G + $E * 0.008247 * sin(2 * $ME1 - $MS - $MF) + 0.004323 * sin(2 * ($ME1 - $MD) - $MF);
        $G = $G + 0.0042 * sin(2 * $ME1 + $MF + $MD) + $E * 0.003372 * sin($MF - $MS - 2 * $ME1);
        $G = $G + $E * 0.002472 * sin(2 * $ME1 + $MF - $MS - $MD);
        $G = $G + $E * 0.002222 * sin(2 * $ME1 + $MF - $MS);
        $G = $G + $E * 0.002072 * sin(2 * $ME1 - $MF - $MS - $MD);
        $G = $G + $E * 0.001877 * sin($MF - $MS + $MD) + 0.001828 * sin(4 * $ME1 - $MF - $MD);
        $G = $G - $E * 0.001803 * sin($MF + $MS) - 0.00175 * sin(3 * $MF);
        $G = $G + $E * 0.00157 * sin($MD - $MS - $MF) - 0.001487 * sin($MF + $ME1);
        $G = $G - $E * 0.001481 * sin($MF + $MS + $MD) + $E * 0.001417 * sin($MF - $MS - $MD);
        $G = $G + $E * 0.00135 * sin($MF - $MS) + 0.00133 * sin($MF - $ME1);
        $G = $G + 0.001106 * sin($MF + 3 * $MD) + 0.00102 * sin(4 * $ME1 - $MF);
        $G = $G + 0.000833 * sin($MF + 4 * $ME1 - $MD) + 0.000781 * sin($MD - 3 * $MF);
        $G = $G + 0.00067 * sin($MF + 4 * $ME1 - 2 * $MD) + 0.000606 * sin(2 * $ME1 - 3 * $MF);
        $G = $G + 0.000597 * sin(2 * ($ME1 + $MD) - $MF);
        $G = $G + $E * 0.000492 * sin(2 * $ME1 + $MD - $MS - $MF) + 0.00045 * sin(2 * ($MD - $ME1) - $MF);
        $G = $G + 0.000439 * sin(3 * $MD - $MF) + 0.000423 * sin($MF + 2 * ($ME1 + $MD));
        $G = $G + 0.000422 * sin(2 * $ME1 - $MF - 3 * $MD) - $E * 0.000367 * sin($MS + $MF + 2 * $ME1 - $MD);
        $G = $G - $E * 0.000353 * sin($MS + $MF + 2 * $ME1) + 0.000331 * sin($MF + 4 * $ME1);
        $G = $G + $E * 0.000317 * sin(2 * $ME1 + $MF - $MS + $MD);
        $G = $G + $E2 * 0.000306 * sin(2 * ($ME1 - $MS) - $MF) - 0.000283 * sin($MD + 3 * $MF);
        $W1 = 0.0004664 * cos($NA);
        $W2 = 0.0000754 * cos($C);
        $BM = $this->Radians($G) * (1 - $W1 - $W2);

        $MoonLat = $this->Degrees($BM);
        return $MoonLat;
    }

    protected function NutatLong($GD, $GM, $GY)
    {
        $DJ = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020;
        $T = $DJ / 36525;
        $T2 = $T * $T;
        $A = 100.0021358 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $L1 = 279.6967 + 0.000303 * $T2 + $B;
        $l2 = 2 * $this->Radians($L1);
        $A = 1336.855231 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $D1 = 270.4342 - 0.001133 * $T2 + $B;
        $D2 = 2 * $this->Radians($D1);
        $A = 99.99736056 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M1 = 358.4758 - 0.00015 * $T2 + $B;
        $M1 = $this->Radians($M1);
        $A = 1325.552359 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M2 = 296.1046 + 0.009192 * $T2 + $B;
        $M2 = $this->Radians($M2);
        $A = 5.372616667 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $N1 = 259.1833 + 0.002078 * $T2 - $B;
        $N1 = $this->Radians($N1);
        $N2 = 2 * $N1;

        $DP = (-17.2327 - 0.01737 * $T) * sin($N1);
        $DP = $DP + (-1.2729 - 0.00013 * $T) * sin($l2) + 0.2088 * sin($N2);
        $DP = $DP - 0.2037 * sin($D2) + (0.1261 - 0.00031 * $T) * sin($M1);
        $DP = $DP + 0.0675 * sin($M2) - (0.0497 - 0.00012 * $T) * sin($l2 + $M1);
        $DP = $DP - 0.0342 * sin($D2 - $N1) - 0.0261 * sin($D2 + $M2);
        $DP = $DP + 0.0214 * sin($l2 - $M1) - 0.0149 * sin($l2 - $D2 + $M2);
        $DP = $DP + 0.0124 * sin($l2 - $N1) + 0.0114 * sin($D2 - $M2);

        $NutatLong = $DP / 3600;
        return $NutatLong;
    }

    protected function MoonDist($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $HP = $this->Radians($this->MoonHP($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR));
        $R = 6378.14 / sin($HP);
        $MoonDist = $R;
        return $MoonDist;
    }

    protected function MoonPhase($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $CD = cos($this->Radians($this->MoonLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR) - $this->SunLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR))) * cos($this->Radians($this->MoonLat($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)));
        $D = acos($CD);
        $SD = sin($D);
        $I = 0.1468 * $SD * (1 - 0.0549 * sin($this->MoonMeanAnomaly($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)));
        $I = $I / (1 - 0.0167 * sin($this->SunMeanAnomaly($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)));
        $I = 3.141592654 - $D - $this->Radians($I);
        $K = (1 + cos($I)) / 2;
        $MoonPhase = number_format($K * 100, 1, ',', ''); //*100 is %
        return $MoonPhase;
    }

    protected function MoonMeanAnomaly($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $UT = $this->LctUT(intval($LH), intval($LM), intval($LS), intval($DS), floatval($ZC), intval($DY), intval($MN), intval($YR))['UTDec'];
        $GD = $this->LctGDay($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GM = $this->LctGMonth($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GY = $this->LctGYear($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $T = (($this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020) / 36525) + ($UT / 876600);
        $T2 = $T * $T;

        // $M1 = 27.32158213;
        // $M2 = 365.2596407;
        $M3 = 27.55455094;
        // $M4 = 29.53058868;
        // $M5 = 27.21222039;
        $M6 = 6798.363307;
        $Q = $this->CDJD(floatval($GD), intval($GM), intval($GY)) - 2415020 + ($UT / 24);
        // $M1 = $Q / $M1;
        // $M2 = $Q / $M2;
        $M3 = $Q / $M3;
        // $M4 = $Q / $M4;
        // $M5 = $Q / $M5;
        $M6 = $Q / $M6;
        // $M1 = 360 * ($M1 - $this->roundvariantint($M1));
        // $M2 = 360 * ($M2 - $this->roundvariantint($M2));
        $M3 = 360 * ($M3 - $this->roundvariantint($M3));
        // $M4 = 360 * ($M4 - $this->roundvariantint($M4));
        // $M5 = 360 * ($M5 - $this->roundvariantint($M5));
        $M6 = 360 * ($M6 - $this->roundvariantint($M6));

        // $ML = 270.434164 + $M1 - (0.001133 - 0.0000019 * $T) * $T2;
        // $MS = 358.475833 + $M2 - (0.00015 + 0.0000033 * $T) * $T2;
        $MD = 296.104608 + $M3 + (0.009192 + 0.0000144 * $T) * $T2;
        // $ME1 = 350.737486 + $M4 - (0.001436 - 0.0000019 * $T) * $T2;
        // $MF = 11.250889 + $M5 - (0.003211 + 0.0000003 * $T) * $T2;
        $NA = 259.183275 - $M6 + (0.002078 + 0.0000022 * $T) * $T2;
        $A = $this->Radians(51.2 + 20.2 * $T);
        $S1 = sin($A);
        $S2 = sin($this->Radians($NA));
        $B = 346.56 + (132.87 - 0.0091731 * $T) * $T;
        $S3 = 0.003964 * sin($this->Radians($B));
        // $C = $this->Radians($NA + 275.05 - 2.3 * $T);
        // $S4 = sin($C);
        // $ML = $ML + 0.000233 * $S1 + $S3 + 0.001964 * $S2;
        // $MS = $MS - 0.001778 * $S1;
        $MD = $MD + 0.000817 * $S1 + $S3 + 0.002541 * $S2;

        $MoonMeanAnomaly = $this->Radians($MD);
        return $MoonMeanAnomaly;
    }

    protected function SunMeanAnomaly($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
    {
        $AA = $this->LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $BB = $this->LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $CC = $this->LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY);
        $UT = $this->LctUT(intval($LCH), intval($LCM), intval($LCS), intval($DS), floatval($ZC), intval($LD), intval($LM), intval($LY))['UTDec'];
        $DJ = $this->CDJD(floatval($AA), intval($BB), intval($CC)) - 2415020;
        $T = ($DJ / 36525) + ($UT / 876600);
        $T2 = $T * $T;
        $A = 100.0021359 * $T;
        $B = 360 * ($A - $this->roundvariantint($A));
        $M1 = 358.47583 - (0.00015 + 0.0000033 * $T) * $T2 + $B;
        $AM = $this->Unwind($this->Radians($M1));
        $SunMeanAnomaly = $AM;
        return $SunMeanAnomaly;
    }

    protected function MoonPABL($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR)
    {
        $GD = $this->LctGDay($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GM = $this->LctGMonth($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $GY = $this->LctGYear($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $LLS = $this->SunLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $LLM = $this->MoonLong($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $BM = $this->MoonLat($LH, $LM, $LS, $DS, $ZC, $DY, $MN, $YR);
        $RAS = $this->Radians($this->ECRA($LLS, 0, 0, 0, 0, 0, $GD, $GM, $GY));
        $RAM = $this->Radians($this->ECRA($LLM, 0, 0, $BM, 0, 0, $GD, $GM, $GY));
        $DDS = $this->Radians($this->ECDec($LLS, 0, 0, 0, 0, 0, $GD, $GM, $GY));
        $DM = $this->Radians($this->ECDec($LLM, 0, 0, $BM, 0, 0, $GD, $GM, $GY));
        $Y = cos($DDS) * sin($RAS - $RAM);
        $X = cos($DM) * sin($DDS) - sin($DM) * cos($DDS) * cos($RAS - $RAM);
        $CHI = atan2($Y, $X);

        $MoonPABL = $this->Degrees($CHI);
        return $MoonPABL;
    }




    /*
     * Conversion of GST to UT --- Achtung: hier wird ein Array ausgegeben !!!
     */
    protected function calculateMoonphase($year)
    {
        // ============================================================
        //
        // Phasen:    phase = 0 für Neumond
        //            phase = 0.25 für erstes Viertel
        //            phase = 0.5 für Vollmond
        //            phase = 0.75 für letztes Viertel
        //            Für Werte anders als 0, 0.25, 0.5 oder 0.75 ist nachstehendes Script ungültig.
        // Angabe des Zeitpunktes als Fließkomma-Jahreszahl
        // Bsp.: 1.8.2006 = ca. 2006.581
        //
        // Ergebnis: $JDE
        // ============================================================

        $rads = 3.14159265359 / 180;

        $moondate = [];
        $i = 0;

        for ($phase = 0; $phase < 1; $phase += 0.25) {
            // Anzahl der Mondphasen seit 2000
            $k = floor(($year - 2000) * 12.36853087) + $phase;
            // Mittlerer JDE Wert des Ereignisses
            $JDE = 2451550.09766 + 29.530588861 * $k;
            // Relevante Winkelwerte in [Radiant]
            $M = (2.5534 + 29.10535670 * $k) * $rads;
            $Ms = (201.5643 + 385.81693528 * $k) * $rads;
            $F = (160.7108 + 390.67050284 * $k) * $rads;

            if ($phase == 0) {
                // Korrekturterme JDE für Neumond
                $JDE += -0.40720 * sin($Ms);
                $JDE += 0.17241 * sin($M);
                $JDE += 0.01608 * sin(2 * $Ms);
                $JDE += 0.01039 * sin(2 * $F);
                $JDE += 0.00739 * sin($Ms - $M);
                $JDE += -0.00514 * sin($Ms + $M);
                $JDE += 0.00208 * sin(2 * $M);
                $JDE += -0.00111 * sin($Ms - 2 * $F);
            } elseif ($phase == 0.5) {
                // Korrekturterme JDE für Vollmond
                $JDE += -0.40614 * sin($Ms);
                $JDE += 0.17302 * sin($M);
                $JDE += 0.01614 * sin(2 * $Ms);
                $JDE += 0.01043 * sin(2 * $F);
                $JDE += 0.00734 * sin($Ms - $M);
                $JDE += -0.00515 * sin($Ms + $M);
                $JDE += 0.00209 * sin(2 * $M);
                $JDE += -0.00111 * sin($Ms - 2 * $F);
            }

            if ($phase == 0.25 || $phase == 0.75) {
                // Korrekturterme für JDE für das  1. bzw. letzte Viertel
                $JDE += -0.62801 * sin($Ms);
                $JDE += 0.17172 * sin($M);
                $JDE += -0.01183 * sin($Ms + $M);
                $JDE += 0.00862 * sin(2 * $Ms);
                $JDE += 0.00804 * sin(2 * $F);
                $JDE += 0.00454 * sin($Ms - $M);
                $JDE += 0.00204 * sin(2 * $M);
                $JDE += -0.00180 * sin($Ms - 2 * $F);

                // Weiterer Korrekturterm für Viertelphasen
                if ($phase == 0.25) {
                    $JDE += 0.00306;
                } else {
                    $JDE += -0.00306;
                }
            }

            // Konvertierung von Julianischem Datum auf Gregorianisches Datum
            $z = floor($JDE + 0.5);
            $f = ($JDE + 0.5) - floor($JDE + 0.5);
            if ($z < 2299161) {
                $a = $z;
            } else {
                $g = floor(($z - 1867216.25) / 36524.25);
                $a = $z + 1 + $g - floor($g / 4);
            }
            $b = $a + 1524;
            $c = floor(($b - 122.1) / 365.25);
            $d = floor(365.25 * $c);
            $e = floor(($b - $d) / 30.6001);

            $tag_temp = $b - $d - floor(30.6001 * $e) + $f; //Tag incl. Tagesbruchteilen
            $stunde_temp = ($tag_temp - floor($tag_temp)) * 24;
            $minute_temp = ($stunde_temp - floor($stunde_temp)) * 60;

            $stunde = floor($stunde_temp);
            $minute = floor($minute_temp);
            $sekunde = round(($minute_temp - floor($minute_temp)) * 60);

            $tag = floor($tag_temp);

            if ($e < 14) {
                $monat = $e - 1;
            } else {
                $monat = $e - 13;
            }
            if ($monat > 2) {
                $jahr = $c - 4716;
            } else {
                $jahr = $c - 4715;
            }

            $sommerzeit = date('I');
            if ($sommerzeit == 0) {
                $datum = mktime(intval($stunde), intval($minute), intval($sekunde + 3600), intval($monat), intval($tag), intval($jahr));
            } else {
                $datum = mktime(intval($stunde), intval($minute), intval($sekunde + 7200), intval($monat), intval($tag), intval($jahr));
            }

            switch ($phase) {
                case 0:
                    $phasename = 'Neumond';
                    break;
                case 0.25:
                    $phasename = 'erstes Viertel';
                    break;
                case 0.5:
                    $phasename = 'Vollmond';
                    break;
                case 0.75:
                    $phasename = 'letztes Viertel';
                    break;
                default:
                    $phasename = 'Neumond';
                    break;
            }

            $date = date('D', ($datum));
            $wt = 'Mo';
            if ($date == 'Mon') {
                $wt = 'Mo';
            } elseif ($date == 'Tue') {
                $wt = 'Di';
            } elseif ($date == 'Wed') {
                $wt = 'Mi';
            } elseif ($date == 'Thu') {
                $wt = 'Do';
            } elseif ($date == 'Fri') {
                $wt = 'Fr';
            } elseif ($date == 'Sat') {
                $wt = 'Sa';
            } elseif ($date == 'Sun') {
                $wt = 'So';
            }

            $moontime = date('H:i', $datum);
            $date = date('d.m.Y', $datum);
            $moondate[$i] = ['name' => $phasename, 'date' => $date, 'weekday' => $wt, 'time' => $moontime];
            $i++;
        }

        $newmoonstring = $moondate[0]['weekday'] . ', ' . $moondate[0]['date'] . ' ' . $moondate[0]['time'];
        $firstquarterstring = $moondate[1]['weekday'] . ', ' . $moondate[1]['date'] . ' ' . $moondate[1]['time'];
        $fullmoonstring = $moondate[2]['weekday'] . ', ' . $moondate[2]['date'] . ' ' . $moondate[2]['time'];
        $lastquarterstring = $moondate[3]['weekday'] . ', ' . $moondate[3]['date'] . ' ' . $moondate[3]['time'];

        $moonphase = ['newmoon' => $newmoonstring, 'firstquarter' => $firstquarterstring, 'fullmoon' => $fullmoonstring, 'lastquarter' => $lastquarterstring, 'moondate' => $moondate];
        return $moonphase;
        }

    protected function compareDateWithToday($datetocompare)
        {
        $datetimetoday = new DateTime(date('d.m.Y', time()));
        $datetimecompare = new DateTime($datetocompare);
        $interval = $datetimetoday->diff($datetimecompare);
        $daydifference = intval($interval->format('%R%a')); // int
        if ($daydifference >= 0) // present or future
            {
            return false;
            } 
        else // past
            {
            return true;
            }
        }

    /* Location auslesen
     */
    public function getlocation()
        {
        $LocationID = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
        $ipsversion = $this->GetIPSVersion();
        if ($ipsversion >= 5)           // Version 5.x oder später
            {
            $Location = json_decode(IPS_GetProperty($LocationID, 'Location'));
            $Latitude = $Location->latitude;
            $Longitude = $Location->longitude;
            } 
        else 
            {
            $Latitude = IPS_GetProperty($LocationID, 'Latitude');
            $Longitude = IPS_GetProperty($LocationID, 'Longitude');
            }
        $location = ['Latitude' => $Latitude, 'Longitude' => $Longitude];
        return $location;
        }

   /* Location Info auslesen, alles schon im IP Symcon enthalten
     */
    function getlocationinfo()
        {
        $LocationID = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
        //echo "Daten von $LocationID holen:\n";
        $isday = GetValue(IPS_GetObjectIDByIdent('IsDay', $LocationID));
        $sunrise = GetValue(IPS_GetObjectIDByIdent('Sunrise', $LocationID));
        $sunset = GetValue(IPS_GetObjectIDByIdent('Sunset', $LocationID));
        $civiltwilightstart = GetValue(IPS_GetObjectIDByIdent('CivilTwilightStart', $LocationID));
        $civiltwilightend = GetValue(IPS_GetObjectIDByIdent('CivilTwilightEnd', $LocationID));
        $nautictwilightstart = GetValue(IPS_GetObjectIDByIdent('NauticTwilightStart', $LocationID));
        $nautictwilightend = GetValue(IPS_GetObjectIDByIdent('NauticTwilightEnd', $LocationID));
        $astronomictwilightstart = GetValue(IPS_GetObjectIDByIdent('AstronomicTwilightStart', $LocationID));
        $astronomictwilightend = GetValue(IPS_GetObjectIDByIdent('AstronomicTwilightEnd', $LocationID));
        $locationinfo = ['IsDay' => $isday, 'Sunrise' => $sunrise, 'Sunset' => $sunset, 'CivilTwilightStart' => $civiltwilightstart, 'CivilTwilightEnd' => $civiltwilightend, 'NauticTwilightStart' => $nautictwilightstart, 'NauticTwilightEnd' => $nautictwilightend, 'AstronomicTwilightStart' => $astronomictwilightstart, 'AstronomicTwilightEnd' => $astronomictwilightend];
        return $locationinfo;
        }

    function GetIPSVersion()
        {
        $ipsversion = floatval(IPS_GetKernelVersion());
        if ($ipsversion < 4.1) // 4.0
            {
            $ipsversion = 0;
            } 
        elseif ($ipsversion >= 4.1 && $ipsversion < 4.2) // 4.1
            {
            $ipsversion = 1;
            } 
        elseif ($ipsversion >= 4.2 && $ipsversion < 4.3) // 4.2
            {
            $ipsversion = 2;
            } 
        elseif ($ipsversion >= 4.3 && $ipsversion < 4.4) // 4.3
            {
            $ipsversion = 3;
            } 
        elseif ($ipsversion >= 4.4 && $ipsversion < 5) // 4.4
            {
            $ipsversion = 4;
            } 
        else   // 5
            {
            $ipsversion = 5;
            }
        return $ipsversion;
        }
    }


/**
 * Solaris PHP Moon Phase. Calculate the phases of the Moon in PHP.
 * Adapted for PHP from Moontool for Windows (http://www.fourmilab.ch/moontoolw).
 *
 * @author Samir Shah <http://rayofsolaris.net>
 * @author Tobias Köngeter <https://www.bitandblack.com>
 * @copyright Copyright © Bit&Black
 * @link https://www.bitandblack.com
 * @license MIT
 */

//namespace Solaris;

//use DateTime;

/**
 * @see \Solaris\Tests\MoonPhaseTest
 */
class MoonPhase
    {
    protected int $timestamp;
    protected float $phase;
    protected float $illumination;
    protected float $age;
    protected float $distance;
    protected float $diameter;
    protected float $sunDistance;
    protected float $sunDiameter;
    protected float $synmonth;

    /**
     * @var array<int, float>|null
     */
    protected ?array $quarters = null;

    protected float $ageDegrees;

    /**
     * @param DateTime|null $date
     */
    public function __construct(DateTime $date = null)
    {
        $date = null !== $date
            ? $date->getTimestamp()
            : time()
        ;

        $this->timestamp = $date;
        echo "Class MoonPhase Use Date ".date("H:i:s d.m.Y", $date)."\n";
        // Astronomical constants. 1980 January 0.0, 
        //                        January 0.9235 TT : B1950.0 = JDE 2 433 282.4235 = 1950 
        $epoch = 2_444_238.5;
        echo "Astronomical constants used for 1980 January 0.0 : JD : $epoch\n";

        // Constants defining the Sun's apparent orbit
        
        $elonge = 278.833540;               // Ecliptic longitude of the Sun at epoch 1980.0
        $elongp = 282.596403;               // Ecliptic longitude of the Sun at perigee
        $eccent = 0.016718;                 // Eccentricity of Earth's orbit
        $sunsmax = 1.495985e8;              // Semi-major axis of Earth's orbit, km
        $sunangsiz = 0.533128;              // Sun's angular size, degrees, at semi-major axis distance


        // Elements of the Moon's orbit, epoch 1980.0

        // Moon's mean longitude at the epoch
        $mmlong = 64.975464;

        // Mean longitude of the perigee at the epoch
        $mmlongp = 349.383063;

        // Mean longitude of the node at the epoch
        // $mlnode = 151.950429;

        // Inclination of the Moon's orbit
        // $minc = 5.145396;

        // Eccentricity of the Moon's orbit
        $mecc = 0.054900;

        // Moon's angular size at distance a from Earth
        $mangsiz = 0.5181;

        // Semi-major axis of Moon's orbit in km
        $msmax = 384401;

        // Parallax at distance a from Earth
        // $mparallax = 0.9507;

        // Synodic month (new Moon to new Moon)
        $synmonth = 29.53058868;

        $this->synmonth = $synmonth;

        // date is coming in as a UNIX timstamp, so convert it to Julian
        $date = $date / 86400 + 2_440_587.5;


        // Calculation of the Sun's position

        // Date within epoch
        $day = $date - $epoch;

        // Mean anomaly of the Sun
        $n = $this->fixAngle((360 / 365.2422) * $day);

        // Convert from perigee co-ordinates to epoch 1980.0
        $m = $this->fixAngle($n + $elonge - $elongp);

        // Solve equation of Kepler
        $ec = $this->kepler($m, $eccent);
        $ec = sqrt((1 + $eccent) / (1 - $eccent)) * tan($ec / 2);

        // True anomaly
        $ec = 2 * rad2deg(atan($ec));

        // Sun's geocentric ecliptic longitude
        $lambdaSun = $this->fixAngle($ec + $elongp);

        // Orbital distance factor
        $f = ((1 + $eccent * cos(deg2rad($ec))) / (1 - $eccent * $eccent));

        // Distance to Sun in km
        $sunDist = $sunsmax / $f;

        // Sun's angular size in degrees
        $sunAng = $f * $sunangsiz;


        // Calculation of the Moon's position

        // Moon's mean longitude
        $ml = $this->fixAngle(13.1763966 * $day + $mmlong);

        // Moon's mean anomaly
        $mm = $this->fixAngle($ml - 0.1114041 * $day - $mmlongp);

        // Moon's ascending node mean longitude
        // $MN = $this->fixangle($mlnode - 0.0529539 * $day);

        $evection = 1.2739 * sin(deg2rad(2 * ($ml - $lambdaSun) - $mm));

        $annualEquation = 0.1858 * sin(deg2rad($m));

        // Correction term
        $a3 = 0.37 * sin(deg2rad($m));

        // Corrected anomaly
        $mmp = $mm + $evection - $annualEquation - $a3;

        // Correction for the equation of the centre
        $mEc = 6.2886 * sin(deg2rad($mmp));

        // Another correction term
        $a4 = 0.214 * sin(deg2rad(2 * $mmp));

        // Corrected longitude
        $lP = $ml + $evection + $mEc - $annualEquation + $a4;

        $variation = 0.6583 * sin(deg2rad(2 * ($lP - $lambdaSun)));

        // True longitude
        $lPP = $lP + $variation;

        // Corrected longitude of the node
        // $NP = $MN - 0.16 * sin(deg2rad($m));

        // Y inclination coordinate
        // $y = sin(deg2rad($lPP - $NP)) * cos(deg2rad($minc));

        // X inclination coordinate
        // $x = cos(deg2rad($lPP - $NP));

        // Ecliptic longitude
        // $Lambdamoon = rad2deg(atan2($y, $x)) + $NP;

        // Ecliptic latitude
        // $BetaM = rad2deg(asin(sin(deg2rad($lPP - $NP)) * sin(deg2rad($minc))));


        // Calculation of the phase of the Moon

        // Age of the Moon in degrees
        $moonAge = $lPP - $lambdaSun;

        // Phase of the Moon
        $moonPhase = (1 - cos(deg2rad($moonAge))) / 2;

        // Distance of moon from the centre of the Earth
        $moonDist = ($msmax * (1 - $mecc * $mecc)) / (1 + $mecc * cos(deg2rad($mmp + $mEc)));

        $moonDFrac = $moonDist / $msmax;

        // Moon's angular diameter
        $moonAng = $mangsiz / $moonDFrac;

        // Moon's parallax
        // $MoonPar = $mparallax / $moonDFrac;


        // Store results

        // Phase (0 to 1)
        $this->phase = $this->fixAngle($moonAge) / 360;

        // Illuminated fraction (0 to 1)
        $this->illumination = $moonPhase;

        // Age of moon (days)
        $this->age = $synmonth * $this->phase;

        // Distance (kilometres)
        $this->distance = $moonDist;

        // Angular diameter (degrees)
        $this->diameter = $moonAng;

        // Age of the Moon in degrees
        $this->ageDegrees = $moonAge;

        // Distance to Sun (kilometres)
        $this->sunDistance = $sunDist;

        // Sun's angular diameter (degrees)
        $this->sunDiameter = $sunAng;
    }

    /**
     * Fix angle
     */
    protected function fixAngle(float $angle): float
    {
        return $angle - 360 * floor($angle / 360);
    }

    /**
     * Kepler
     */
    protected function kepler(float $m, float $ecc): float
    {
        // 1E-6
        $epsilon = 0.000001;
        $e = $m = deg2rad($m);

        do {
            $delta = $e - $ecc * sin($e) - $m;
            $e -= $delta / (1 - $ecc * cos($e));
        } while (abs($delta) > $epsilon);

        return $e;
    }

    /**
     * Calculates time  of the mean new Moon for a given base date.
     * This argument K to this function is the precomputed synodic month index, given by:
     * K = (year - 1900) * 12.3685
     * where year is expressed as a year and fractional year.
     */
    protected function meanPhase(int $date, float $k): float
    {
        // Time in Julian centuries from 1900 January 0.5
        $jt = ($date - 2_415_020.0) / 36525;
        $t2 = $jt * $jt;
        $t3 = $t2 * $jt;

        $nt1 = 2_415_020.75933 + $this->synmonth * $k
            + 0.0001178 * $t2
            - 0.000000155 * $t3
            + 0.00033 * sin(deg2rad(166.56 + 132.87 * $jt - 0.009173 * $t2))
        ;

        return $nt1;
    }

    /**
     * Given a K value used to determine the mean phase of the new moon and a
     * phase selector (0.0, 0.25, 0.5, 0.75), obtain the true, corrected phase time.
     */
    protected function truePhase(float $k, float $phase): ?float
    {
        $apcor = false;

        // Add phase to new moon time
        $k += $phase;

        // Time in Julian centuries from 1900 January 0.5
        $t = $k / 1236.85;

        // Square for frequent use
        $t2 = $t * $t;

        // Cube for frequent use
        $t3 = $t2 * $t;

        // Mean time of phase
        $pt = 2_415_020.75933
            + $this->synmonth * $k
            + 0.0001178 * $t2
            - 0.000000155 * $t3
            + 0.00033 * sin(deg2rad(166.56 + 132.87 * $t - 0.009173 * $t2))
        ;

        // Sun's mean anomaly
        $m = 359.2242 + 29.10535608 * $k - 0.0000333 * $t2 - 0.00000347 * $t3;

        // Moon's mean anomaly
        $mprime = 306.0253 + 385.81691806 * $k + 0.0107306 * $t2 + 0.00001236 * $t3;

        // Moon's argument of latitude
        $f = 21.2964 + 390.67050646 * $k - 0.0016528 * $t2 - 0.00000239 * $t3;

        if ($phase < 0.01 || abs($phase - 0.5) < 0.01) {
            // Corrections for New and Full Moon
            $pt += (0.1734 - 0.000393 * $t) * sin(deg2rad($m))
                + 0.0021 * sin(deg2rad(2 * $m))
                - 0.4068 * sin(deg2rad($mprime))
                + 0.0161 * sin(deg2rad(2 * $mprime))
                - 0.0004 * sin(deg2rad(3 * $mprime))
                + 0.0104 * sin(deg2rad(2 * $f))
                - 0.0051 * sin(deg2rad($m + $mprime))
                - 0.0074 * sin(deg2rad($m - $mprime))
                + 0.0004 * sin(deg2rad(2 * $f + $m))
                - 0.0004 * sin(deg2rad(2 * $f - $m))
                - 0.0006 * sin(deg2rad(2 * $f + $mprime))
                + 0.0010 * sin(deg2rad(2 * $f - $mprime))
                + 0.0005 * sin(deg2rad($m + 2 * $mprime))
            ;

            $apcor = true;
        } elseif (abs($phase - 0.25) < 0.01 || abs($phase - 0.75) < 0.01) {
            $pt += (0.1721 - 0.0004 * $t) * sin(deg2rad($m))
                + 0.0021 * sin(deg2rad(2 * $m))
                - 0.6280 * sin(deg2rad($mprime))
                + 0.0089 * sin(deg2rad(2 * $mprime))
                - 0.0004 * sin(deg2rad(3 * $mprime))
                + 0.0079 * sin(deg2rad(2 * $f))
                - 0.0119 * sin(deg2rad($m + $mprime))
                - 0.0047 * sin(deg2rad($m - $mprime))
                + 0.0003 * sin(deg2rad(2 * $f + $m))
                - 0.0004 * sin(deg2rad(2 * $f - $m))
                - 0.0006 * sin(deg2rad(2 * $f + $mprime))
                + 0.0021 * sin(deg2rad(2 * $f - $mprime))
                + 0.0003 * sin(deg2rad($m + 2 * $mprime))
                + 0.0004 * sin(deg2rad($m - 2 * $mprime))
                - 0.0003 * sin(deg2rad(2 * $m + $mprime))
            ;

            // First and last quarter corrections
            if ($phase < 0.5) {
                $pt += 0.0028 - 0.0004 * cos(deg2rad($m)) + 0.0003 * cos(deg2rad($mprime));
            } else {
                $pt += -0.0028 + 0.0004 * cos(deg2rad($m)) - 0.0003 * cos(deg2rad($mprime));
            }

            $apcor = true;
        }

        return $apcor ? $pt : null;
    }

    /**
     * Find time of phases of the moon which surround the current date. Five phases are found, starting and
     * ending with the new moons which bound the current lunation.
     */
    protected function phaseHunt(): void
    {
        $sdate = $this->getJulianFromUTC($this->timestamp);
        $adate = $sdate - 45;
        $ats = $this->timestamp - 86400 * 45;
        $yy = (int) gmdate('Y', $ats);
        $mm = (int) gmdate('n', $ats);

        $k1 = floor(($yy + (($mm - 1) * (1 / 12)) - 1900) * 12.3685);
        $adate = $nt1 = $this->meanPhase((int) $adate, $k1);

        while (true) {
            $adate += $this->synmonth;
            $k2 = $k1 + 1;
            $nt2 = $this->meanPhase((int) $adate, $k2);

            // If nt2 is close to sdate, then mean phase isn't good enough, we have to be more accurate
            if (abs($nt2 - $sdate) < 0.75) {
                $nt2 = $this->truePhase($k2, 0.0);
            }

            if ($nt1 <= $sdate && $nt2 > $sdate) {
                break;
            }

            $nt1 = $nt2;
            $k1 = $k2;
        }

        // Results in Julian dates
        $dates = [
            $this->truePhase($k1, 0.0),
            $this->truePhase($k1, 0.25),
            $this->truePhase($k1, 0.5),
            $this->truePhase($k1, 0.75),
            $this->truePhase($k2, 0.0),
            $this->truePhase($k2, 0.25),
            $this->truePhase($k2, 0.5),
            $this->truePhase($k2, 0.75),
        ];

        $this->quarters = [];

        foreach ($dates as $jdate) {
            // Convert to UNIX time
            $this->quarters[] = ($jdate - 2_440_587.5) * 86400;
        }
    }

    /**
     * UTC to Julian
     */
    protected function getJulianFromUTC(int $timestamp): float
    {
        return $timestamp / 86400 + 2_440_587.5;
    }

    /**
     * Returns the moon phase.
     */
    public function getPhase(): float
    {
        return $this->phase;
    }

    public function getIllumination(): float
    {
        return $this->illumination;
    }

    public function getAge(): float
    {
        return $this->age;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function getDiameter(): float
    {
        return $this->diameter;
    }

    public function getSunDistance(): float
    {
        return $this->sunDistance;
    }

    public function getSunDiameter(): float
    {
        return $this->sunDiameter;
    }

    /**
     * Get moon phase data
     */
    public function getPhaseByName(string $name): ?float
    {
        $phases = [
            'new_moon',
            'first_quarter',
            'full_moon',
            'last_quarter',
            'next_new_moon',
            'next_first_quarter',
            'next_full_moon',
            'next_last_quarter',
        ];

        if (null === $this->quarters) {
            $this->phaseHunt();
        }

        return $this->quarters[array_flip($phases)[$name]] ?? null;
    }

    /**
     * Get current phase name. There are eight phases, evenly split.
     * A "New Moon" occupies the 1/16th phases either side of phase = 0, and the rest follow from that.
     */
    public function getPhaseName(): string
    {
        $names = [
            'New Moon',
            'Waxing Crescent',
            'First Quarter',
            'Waxing Gibbous',
            'Full Moon',
            'Waning Gibbous',
            'Third Quarter',
            'Waning Crescent',
            'New Moon',
        ];

        return $names[floor(($this->phase + 0.0625) * 8)];
    }

    public function getPhaseNewMoon(): ?float
    {
        return $this->getPhaseByName('new_moon');
    }

    public function getPhaseFirstQuarter(): ?float
    {
        return $this->getPhaseByName('first_quarter');
    }

    public function getPhaseFullMoon(): ?float
    {
        return $this->getPhaseByName('full_moon');
    }

    public function getPhaseLastQuarter(): ?float
    {
        return $this->getPhaseByName('last_quarter');
    }

    public function getPhaseNextNewMoon(): ?float
    {
        return $this->getPhaseByName('next_new_moon');
    }

    public function getPhaseNextFirstQuarter(): ?float
    {
        return $this->getPhaseByName('next_first_quarter');
    }

    public function getPhaseNextFullMoon(): ?float
    {
        return $this->getPhaseByName('next_full_moon');
    }

    public function getPhaseNextLastQuarter(): ?float
    {
        return $this->getPhaseByName('next_last_quarter');
    }
}



?>