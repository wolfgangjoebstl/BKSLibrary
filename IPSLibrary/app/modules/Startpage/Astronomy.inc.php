<?php

/******************************************************************************
 * The following is a PHP implementation of the JavaScript code found at:      *
 * http://bodmas.org/astronomy/riset.html                                      *
 *                                                                             *
 * Original maths and code written by Keith Burnett <bodmas.org>               *
 * PHP port written by Matt "dxprog" Hackmann <dxprog.com>                     *
 *                                                                             *
 * This program is free software: you can redistribute it and/or modify        *
 * it under the terms of the GNU General Public License as published by        *
 * the Free Software Foundation, either version 3 of the License, or           *
 * (at your option) any later version.                                         *
 *                                                                             *
 * This program is distributed in the hope that it will be useful,             *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of              *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               *
 * GNU General Public License for more details.                                *
 *                                                                             *
 * You should have received a copy of the GNU General Public License           *
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.       *
 *
 * Created by PhpStorm.
 * User: Fonzo, fork by wolfgangjoebstl
 * Date: 16.06.2017
 * Time: 22:26
 *
 * Adapted und new comments by wolfgangjoebstl
 *
 *  Trigonometry
 *      DDDegMinSec     Umrechnung float Grad in Grad Minuten Sekunden, return as array
 *      DDDeg
 *      DDMin
 *      DDSec
 *
 *  NasaMoonlight
 *
 *  MoonCalculations
 *      simplifiedMoonCalc
 *
 *      calcModifiedJulianDate
 *      calcJulianDate
 *      calcJulianCentury
 *
 *          roundvariantfix         float - int ergebnis sind die Kommazahlen
 *          modulo                  roundvariantfix mit Wert/Divisor * Divisor    
 *
 *      calcMoonTimes               not protected, calling next one as self::
 *      calculateMoonTimes
 *      quad
 *      sinAlt
 *      degRange
 *      lmst
 *      minimoon
 *      frac
 *      modifiedJulianDate          MJD for 17.Nov 1858 : 0 , MJD for 17.Nov 1859 : 365
 *      convertTime                 skips seconds and returns hours and minutes as array
 *
 *
 *
 * https://astrogreg.com/ liefert nette code snipplets
 *
 *
 ******************************************************************************/

namespace joebstl;


/* selbe Routine einmal für Grad und einmal für Stunden 
 *      DDDegMinSec, DDDeg, DDMin, DDSec
 *      DHHMS, DHHour, DHMin, DHSec
 */
trait Trigonometry
    {


    /* Umrechnung float Grad in Grad Minuten Sekunden, auf hundertsel secs runden
     * DDDeg für die Grad
     */

    public function DDDegMinSec(float $DD)
        {
        $A = abs($DD);
        $B = $A * 3600;                                                 // in secs
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);       // Runden auf 100stel secs

        if ($C == 60) {
            $D = 0;
            $E = $B + 60;                   // die hundertsel Rundung holt auf die volle Minute auf
        } else {
            $D = $C;
            $E = $B;
        }

        if ($DD < 0) {
            $DDDeg = $this->roundvariantfix($E / 3600) * (-1);
        } else {
            $DDDeg = $this->roundvariantfix($E / 3600);
        }
        $DDMin = fmod(floor($E / 60), 60);
        $DDSec = $D;

        return ["deg"=>$DDDeg,"min"=>$DDMin, "sec"=>$DDSec];

        }

    protected function DDDeg(float $DD)
        {
        $A = abs($DD);
        $B = $A * 3600;                                                 // in secs
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);       // Runden auf 100stel secs

        if ($C == 60) {
            //$D = 0;
            $E = $B + 60;                   // die hundertsel Rundung holt auf die volle Minute auf
        } else {
            //$D = $C;
            $E = $B;
        }

        if ($DD < 0) {
            $DDDeg = $this->roundvariantfix($E / 3600) * (-1);
        } else {
            $DDDeg = $this->roundvariantfix($E / 3600);
        }
        return $DDDeg;
        }

    protected function DDMin($DD)
        {
        $A = abs($DD);
        $B = $A * 3600;
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);

        if ($C == 60) {
            //$D = 0;
            $E = $B + 60;
        } else {
            //$D = $C;
            $E = $B;
        }

        $DDMin = fmod(floor($E / 60), 60);
        return $DDMin;
        }

    protected function DDSec($DD)
        {
        $A = abs($DD);
        $B = $A * 3600;
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);
        if ($C == 60) {
            $D = 0;
        } else {
            $D = $C;
        }

        $DDSec = $D;
        return $DDSec;
        }

    /* Umrechnung float hour in hour minutes seconds
     */
    public function DHHMS(float $DH)
        {
        $hours = $this->DHHour($DH);
        $minutes = $this->DHMin($DH);
        $seconds = $this->DHSec($DH);
        $HMS = ['hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds];
        return $HMS;
        }

    /* input is local hour of day as float of hour [0..24]
     * negative values ignored, extend to seconds, subtract exact minutes, result is remaining seconds
     *
     * returns actual hour without minutes or seconds 
     */
    protected function DHHour(float $DH)
        {
        $A = abs($DH);
        $B = $A * 3600;
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);

        if ($C == 60) {
            // $D = 0;
            $E = $B + 60;               
        } else {
            // $D = $C;
            $E = $B;
            }

        if ($DH < 0) {
            $DHHour = $this->roundvariantfix($E / 3600) * (-1);
        } else {
            $DHHour = $this->roundvariantfix($E / 3600);
            }
        return $DHHour;
        }

    protected function DHMin($DH)
        {
        $A = abs($DH);
        $B = $A * 3600;
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);

        if ($C == 60) {
            // $D = 0;
            $E = $B + 60;
        } else {
            // $D = $C;
            $E = $B;
        }

        $DHMin = fmod(floor($E / 60), 60);
        return $DHMin;
        }

    // Converting hours, minutes and seconds to decimal hours

    protected function DHSec($DH)
        {
        $A = abs($DH);
        $B = $A * 3600;
        $C = round($B - 60 * $this->roundvariantfix($B / 60), 2);
        if ($C == 60) {
            $D = 0;
        } else {
            $D = $C;
        }

        $DHSec = $D;
        return $DHSec;
        }

    }

/* Zusammenfassung, alles rund um Julianischem Kalender, inklusiver Kalkparameter für astronomische Berechnungen
 *      calcModifiedJulianDate
 *      calcJulianDate
 * JDCD, JDCDay, JDCMonth, JDCYear
 */

trait JulianDates
    {

    /* MJD for 17.Nov 1858 : ".$moon->calcModifiedJulianDate(11, 17, 1858) is zero !!!
     * modified Julian Date faent am 17.11.1858 an, nimmt nur Datum als Input parameter
     */
    public function calcModifiedJulianDate($month, $day, $year) 
        {
        return(self::modifiedJulianDate($month, $day, $year));    
        }

    /* Julianisches Datum als Anzahl der Tage seit -4713, inklusive Uhrzeit
     * siehe https://de.wikipedia.org/wiki/Julianisches_Datum#:~:text=Als%20Jahr%201%20der%20Julianischen,Chr.
     * Ausgabe Tage mit Kommawert als Uhrzeit, Werte zum testen
     * Datum	        Uhrzeit	Julianisches Datum	Anmerkungen
     *  1. Jan. −4712	12:00 UT	        0,000	Epoche des Julianischen Datums
     * 27. Mai −668	    01:59 UT	1.477.217,583	Aufgang der verfinsterten Sonne in Babylon
     *  1. Jan. 1	    00:00 UT	1.721.423,500	
     * 14. Sep. 763	    12:00 UT	2.000.000,000	
     *  4. Okt. 1582jul.	24:00 UT	2.299.160,500	derselbe Zeitpunkt; s. Übernahme des gregorianischen Kalenders
     * 15. Okt. 1582greg.	00:00 UT
     * 17. Nov. 1858	00:00	    2.400.000,500	Epoche des Modifizierten Julianischen Datums, Zeitskala UT oder TT
     * 31. Dez. 1899	19:31:28 TT	2.415.020,31352	Standardepoche B1900.0[26]
     *  1. Jan. 2000	12:00:00 TT	2.451.545,00000	Standardepoche J2000.0[26] (= 1. Jan. 2000, 11:58:55,8 UTC)[27]
     * 28. Feb. 2024	17:09 UTC	2.460.369,215	Aktuelles Datum
     */
    public function calcJulianDate(int $Hour, int $Minute, int $Second, int $DS, float $ZC, int $day, int $month, int $year) 
        {
        $A = abs($Hour) + (abs($Minute) + abs($Second) / 60) / 60;    // Zeit in Stunden mit Komma
        $B = $A - $DS - $ZC;                                            //UT, kann negativ werden
        $day = floatval($day + ($B / 24));                 //G day, kann negativ werden, ok

        if ($month < 3) {                   // Monate nach oben drehen, zugunsten von Jahren  12.1.1966 ist der 12.13.1965
            $Y = $year - 1;
            $M = $month + 12;
        } else {
            $Y = $year;
            $M = $month;
        }

        // weniger Schaltjahre im gregorianischen Kalendar abziehen
        if ($year > 1582) {                                     // sicher gregorianischer Kalendar
            $A = $this->roundvariantfix($Y / 100);              // Schalttage alle 100 Jahre finden nicht statt
            $B = 2 - $A + $this->roundvariantfix($A / 4);       // zusätzliche Schalttage abziehen ausser die alle 400 Jahre
        } else {
            if (($year == 1582) and ($month > 10)) {            // auch gregorianischer Kalendar
                $A = $this->roundvariantfix($Y / 100);
                $B = 2 - $A + $this->roundvariantfix($A / 4);
            } else {
                if (($year == 1582) and ($month == 10) and ($day >= 15)) {          // auch gregorianischer Kalendar, selbe Formel
                    $A = $this->roundvariantfix($Y / 100);
                    $B = 2 - $A + $this->roundvariantfix($A / 4);
                } else {
                    $B = 0;
                }
            }
        }

        if ($Y < 0) { $C = $this->roundvariantfix((365.25 * $Y) - 0.75);            // 0.75 unklar aber richtig
        } else {      $C = $this->roundvariantfix(365.25 * $Y);             }

        $D = $this->roundvariantfix(30.6001 * ($M + 1));
        $JD = $B + $C + $D + $day + 1720994.5;  
        // Runden fehlt      
        return $JD;
        }


    /* Umrechnung auf Julianisches Datum, nach http://www.braeunig.us/space/plntpos.htm
     * Problem, berücksichtigt keine Uhrzeiten !!!
     *
     * Julian Date (JD) is the system of time measurement for scientific use by the astronomy community. 
     * It is the interval of time in days and fractions of a day since 4713 BC January-1 Greenwich noon. 
     * The Julian day begins at Greenwich mean noon, that is at 12:00 Universal Time (UT).
     * To convert a Gregorian calendar date to Julian Date, perform the following steps:
     *  Y = year, M = month, D = day (includes hours, minutes & seconds as fraction of day)
     * If M = 1 or 2, (Januar, Februar) take Y = Y – 1, M = M + 12
     * If the date is equal to or after 1582-Oct-15 (i.e. the date is in the Gregorian calendar), calculate
     *          A = INT( Y / 100 ); B = 2 – A + INT( A / 4 ); 
     * If the date is before 1582-Oct-15 (i.e. the date is in the Julian calendar), it is not necessary to calculate A and B.
     * The Julian date is then
     * JD = INT(365.25 × Y) + INT(30.6001 × (M + 1)) + D + 1720994.5 + B
     * where B is added only if the date is in the Gregorian calendar.
     */
    public function CDJD(float $day, int $month, int $year)
        {
        if ($month < 3) {                   // Monate nach oben drehen, zugunsten von Jahren  12.1.1966 ist der 12.13.1965
            $Y = $year - 1;
            $M = $month + 12;
        } else {
            $Y = $year;
            $M = $month;
        }

        // weniger Schaltjahre im gregorianischen Kalendar abziehen
        if ($year > 1582) {                                     // sicher gregorianischer Kalendar
            $A = $this->roundvariantfix($Y / 100);              // Schalttage alle 100 Jahre finden nicht statt
            $B = 2 - $A + $this->roundvariantfix($A / 4);       // zusätzliche Schalttage abziehen ausser die alle 400 Jahre
        } else {
            if (($year == 1582) and ($month > 10)) {            // auch gregorianischer Kalendar
                $A = $this->roundvariantfix($Y / 100);
                $B = 2 - $A + $this->roundvariantfix($A / 4);
            } else {
                if (($year == 1582) and ($month == 10) and ($day >= 15)) {          // auch gregorianischer Kalendar, selbe Formel
                    $A = $this->roundvariantfix($Y / 100);
                    $B = 2 - $A + $this->roundvariantfix($A / 4);
                } else {
                    $B = 0;
                }
            }
        }

        if ($Y < 0) { $C = $this->roundvariantfix((365.25 * $Y) - 0.75);            // 0.75 unklar
        } else {      $C = $this->roundvariantfix(365.25 * $Y);             }

        $D = $this->roundvariantfix(30.6001 * ($M + 1));
        $JD = $B + $C + $D + $day + 1720994.5;
        return $JD;
        }

    /* calc Julian Century for different starting point B1900 or J2000
     */
    public function calcJulianCentury($JD, $start="B1900")
        {
        switch (strtoupper($start))
            {
            case "B1900":
                $T = (($JD - 2415020) / 36525);           //Julianischer Kalender, nur einfache Schaltjahre, Bezugspunkt 31.12.1899 19:13 UT
                break;
            case "J2000":
                $T = (($JD - 2451545) / 36525);           //Julianischer Kalender, nur einfache Schaltjahre, Bezugspunkt 1.1.2000 00:00 UT
                break;
            default:
                $T = (($JD - 2451545) / 36525);           //Julianischer Kalender, nur einfache Schaltjahre
                break;
            }
        return ($T);
        }


    /* calc Julian Century for different starting point B1900 or J2000
     */
    public function calcJulianDateOnEpoche(int $Hour, int $Minute, int $Second, int $DS, float $ZC, int $day, int $month, int $year, $start="B1900")
        {
        $JD = $this->calcJulianDate($Hour,$Minute,$Second,$DS,$ZC,$day,$month,$year);
        switch (strtoupper($start))
            {
            case "B1900":
                $Q = (($JD - 2415020));           //Julianischer Kalender, nur einfache Schaltjahre, Bezugspunkt 31.12.1899 19:13 UT
                break;
            case "J2000":
                $Q = (($JD - 2451545));           //Julianischer Kalender, nur einfache Schaltjahre, Bezugspunkt 1.1.2000 00:00 UT
                break;
            default:
                $Q = (($JD - 2451545));           //Julianischer Kalender, nur einfache Schaltjahre
                break;
            }
        $T = $Q/36525;
        return (["JD"=>$JD,"Q"=>$Q,"T"=>$T,"Epoche"=>$start]);
        }

    /* Julian Date Umrechnungen, gibts da nix fertiges von php
    *  JDCD, JDCDay, JDCMonth, JDCYear
     */
    public function JDCD(float $JD)
        {
        $day = $this->JDCDay($JD);
        $month = $this->JDCMonth($JD);
        $year = $this->JDCYear($JD);
        $dateCD = ['day' => $day, 'month' => $month, 'year' => $year];
        return $dateCD;
        }

    protected function JDCDay(float $JD)
        {
        $I = $this->roundvariantfix($JD + 0.5);
        $F = $JD + 0.5 - $I;
        $A = $this->roundvariantfix(($I - 1867216.25) / 36524.25);

        if ($I > 2299160) {
            $B = $I + 1 + $A - $this->roundvariantfix($A / 4);
        } else {
            $B = $I;
        }

        $C = $B + 1524;
        $D = $this->roundvariantfix(($C - 122.1) / 365.25);
        $E = $this->roundvariantfix(365.25 * $D);
        $G = $this->roundvariantfix(($C - $E) / 30.6001);
        $JDCDay = $C - $E + $F - $this->roundvariantfix(30.6001 * $G);
        return $JDCDay;
        }

    // Greenwich calendar date to Julian date conversion

    protected function JDCMonth(float $JD)
        {
        $I = $this->roundvariantfix($JD + 0.5);
        // $F = $JD + 0.5 - $I;
        $A = $this->roundvariantfix(($I - 1867216.25) / 36524.25);

        if ($I > 2299160) {
            $B = $I + 1 + $A - $this->roundvariantfix($A / 4);
        } else {
            $B = $I;
        }

        $C = $B + 1524;
        $D = $this->roundvariantfix(($C - 122.1) / 365.25);
        $E = $this->roundvariantfix(365.25 * $D);
        $G = $this->roundvariantfix(($C - $E) / 30.6001);

        if ($G < 13.5) {            $JDCMonth = $G - 1;        } 
        else {             $JDCMonth = $G - 13;        }
        return $JDCMonth;
        }

    /* Julian date to Greenwich calendar date conversion
     * https://de.wikipedia.org/wiki/Julianisches_Datum#:~:text=Als%20Jahr%201%20der%20Julianischen,Chr.
     */

    protected function JDCYear($JD)
        {
        $I = $this->roundvariantfix($JD + 0.5);
        // $F = $JD + 0.5 - $I;
        $A = $this->roundvariantfix(($I - 1867216.25) / 36524.25);

        if ($I > 2299160) {
            $B = $I + 1 + $A - $this->roundvariantfix($A / 4);
        } else {
            $B = $I;
        }

        $C = $B + 1524;
        $D = $this->roundvariantfix(($C - 122.1) / 365.25);
        $E = $this->roundvariantfix(365.25 * $D);
        $G = $this->roundvariantfix(($C - $E) / 30.6001);

        if ($G < 13.5) { $H = $G - 1;     } 
        else {           $H = $G - 13;    }

        if ($H > 2.5) { $JDCYear = $D - 4716; } 
        else {          $JDCYear = $D - 4715; }
        return $JDCYear;
        }

    /* wenn Wert größer 0 floor, wenn kleiner 0 ist ceil nehmen
     * floor macht aus 4.3 -> 4, 9.999 -> 9, aber aus -3.14 -> -4 daher bei neg. Werten ceil und -3.14 -> -3
     * das heisst wir vernichten die Kommastellen, egal ob plus oder minus
     * der Wert bleibt float, geht daher auch für sehr grosse Zahlen
     */
    protected function roundvariantfix($value)
        {
        $roundvalue = 0;
        if ($value >= 0)
            $roundvalue = floor($value);
        elseif ($value < 0)
            $roundvalue = ceil($value);
        return $roundvalue;
        }

    public function modulo($value,$dividend)
        {
        $value = $value/$dividend;
        $modulo = ($value - $this->roundvariantfix($value))*$dividend;
        return $modulo;
        }

    }

trait DateandTime
    {

    /* diese Funktion berechnet aus der aktuellen Uhrzeit, plus Sommerzeit, plus Zeitzone, plus Datum
     * Zeitzone Wien (UTC+1) ist Default bei Aufruf von calculateMoonCoordinates
     * Stunde als Float - Sommerzeit - Zeitzone ergibt wieder die UTC, kann auch negativ werden
     * zum aktuellen Tag diesen Korrekturfaktor hinzuzählen, daraus ein julianisches Datum generieren
     * den Tag richtig aufrunden, Korrektur 12 Stunden ?
     *
     */
    public function LctUT(int $Hour, int $Minute, int $Second, int $DS, float $ZC, int $day, int $month, int $year)
        {
        $A = $this->HMSDH(floatval($Hour), $Minute, $Second);     //LCT
        $B = $A - $DS - $ZC;                   //UT
        $C = floatval($day + ($B / 24));                 //G day
        $D = $this->CDJD(floatval($C), intval($month), intval($year));  //JD
        $GD = $this->JDCDay($D);                       //G day
        $GM = $this->JDCMonth($D);                    //G month
        $GY = $this->JDCYear($D);                      //G year
        $GDfix = $this->roundvariantfix($GD);
        $UTDec = 24 * ($GD - $GDfix);
        return ['UTDec' => $UTDec, 'GD' => $GD, 'GM' => $GM, 'GY' => $GY];
        }

    protected function LctGDay($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
        {
        $A = $this->HMSDH(floatval($LCH), intval($LCM), intval($LCS));
        $B = $A - $DS - $ZC;
        $C = floatval($LD + ($B / 24));
        $D = $this->CDJD(floatval($C), intval($LM), intval($LY));
        $E = $this->JDCDay($D);
        $LctGDay = $this->roundvariantfix($E);
        return $LctGDay;
    }

    protected function LctGMonth($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
    {
        $A = $this->HMSDH(floatval($LCH), intval($LCM), intval($LCS));
        $B = $A - $DS - $ZC;
        $C = floatval($LD + ($B / 24));
        $D = $this->CDJD(floatval($C), intval($LM), intval($LY));
        $LctGMonth = $this->JDCMonth($D);
        return $LctGMonth;
    }

    protected function LctGYear($LCH, $LCM, $LCS, $DS, $ZC, $LD, $LM, $LY)
        {
        $A = $this->HMSDH(floatval($LCH), intval($LCM), intval($LCS));
        $B = $A - $DS - $ZC;
        $C = floatval($LD + ($B / 24));
        $D = $this->CDJD(floatval($C), intval($LM), intval($LY));
        $LctGYear = $this->JDCYear($D);
        return $LctGYear;
        }

    }

trait NasaMoonlight
    {

    }


trait MoonCalculations
    {

    /* liefert geozentrische Koordinaten des Mondesfür das Julianisch Jahrhundert, die müssen erst umgerechnet werden
     */
    public function simplifiedMoonCalc($t) 
        {
        return(self::minimoon($t));
        }








    public function calcMoonTimes($month, $day, $year, $lat, $lon)
        {
        return(self:: calculateMoonTimes($month, $day, $year, $lat, $lon));
        }

    /**
     * Calculates the moon rise/set for a given location and day of year, 
     * timezone correct ? summer/wintertime check with Apple
     */
    public static function calculateMoonTimes($month, $day, $year, $lat, $lon) {

        $utrise = $utset = 0;

        $timezone = (int)($lon / 15);
        $date = self::modifiedJulianDate($month, $day, $year);          // am 17.11.1858 ist MJD=0
        $date -= $timezone / 24;
        $latRad = deg2rad($lat);        // from deg to rad (Bogenmass), 360grad ist der Umfang 2 mal pi mal rad also 6.3
        $sinho = 0.0023271056;
        $sglat = sin($latRad);
        $cglat = cos($latRad);

        $rise = false;
        $set = false;
        $hour = 1;
        $ym = self::sinAlt($date, $hour - 1, $lon, $cglat, $sglat) - $sinho;

        while ($hour < 25 && (false == $set || false == $rise)) {

            $yz = self::sinAlt($date, $hour, $lon, $cglat, $sglat) - $sinho;
            $yp = self::sinAlt($date, $hour + 1, $lon, $cglat, $sglat) - $sinho;

            $quadout = self::quad($ym, $yz, $yp);
            $nz = $quadout[0];
            $z1 = $quadout[1];
            $z2 = $quadout[2];
            // $xe = $quadout[3];
            $ye = $quadout[4];

            if ($nz == 1) {
                if ($ym < 0) {
                    $utrise = $hour + $z1;
                    $rise = true;
                } else {
                    $utset = $hour + $z1;
                    $set = true;
                }
            }

            if ($nz == 2) {
                if ($ye < 0) {
                    $utrise = $hour + $z2;
                    $utset = $hour + $z1;
                } else {
                    $utrise = $hour + $z1;
                    $utset = $hour + $z2;
                }
            }

            $ym = $yp;
            $hour += 2.0;

        }
        // Convert to unix timestamps and return as an object
        $retVal = new \stdClass();
        $utrise = self::convertTime($utrise);
        $utset = self::convertTime($utset);
        $summertime = date("I");
        if($summertime == 0){
            $retVal->moonrise = $rise ? mktime($utrise['hrs'], $utrise['min'], 0+3600, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
            $retVal->moonset = $set ? mktime($utset['hrs'], $utset['min'], 0+3600, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
        }
        else{
            $retVal->moonrise = $rise ? mktime($utrise['hrs'], $utrise['min'], 0+7200, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
            $retVal->moonset = $set ? mktime($utset['hrs'], $utset['min'], 0+7200, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
        }
        return $retVal;

    }

    /* used in calculateMoonTimes
     *
     *  finds the parabola throuh the three points (-1,ym), (0,yz), (1, yp)
     *  and returns the coordinates of the max/min (if any) xe, ye
     *  the values of x where the parabola crosses zero (roots of the self::quadratic)
     *  and the number of roots (0, 1 or 2) within the interval [-1, 1]
     *
     *    well, this routine is producing sensible answers
     *     a = (ym+yp)/2 - yz     b = (yp-ym)/2       c=yz
     *    Scheitelpunkt [xe,ye]
     *     xe = -b/(2a)           ye = (a*xe+b)*xe+c  oder vereinfacht   ye = c-b*b/4a   
     *    Nulldurchgang der Parabel 0,1,2: 
     *      y=0 :  dis = b*b-4a*c , Wurzelterm aus der Mitternachtsformel, lösen für plus und minus
     *    if dis<=0 [0,0,0,xe,ye]
     *    else  dx= sqrt(dis)/abs(a)/2 z1=ex+dx z2 = xe+dx 
     *          if abs(z1)<1 nz++ sonst nz
     *          if abs(z2)<1 nz++ sonst nz
     *          if z1<1 z1=z2
     *
     *  results passed as array [nz, z1, z2, xe, ye]
     *
     * parabola   y = ax2 + bx + c      ym = a-b+c     yz = c    yp =a+b+c 
     * ym + yp = 2(a+c)   a = (ym+yp)/2-yz
     * yp - ym = 2b       b = (yp-ym)/2
     *
     * 2ax-b = 0 
     *
     */
    private static function quad($ym, $yz, $yp) {

        $nz = $z1 = $z2 = 0;
        $a = 0.5 * ($ym + $yp) - $yz;
        $b = 0.5 * ($yp - $ym);
        $c = $yz;
        $xe = -$b / (2 * $a);
        $ye = ($a * $xe + $b) * $xe + $c;           // quadratic formula y für ax2+bx+c
        $dis = $b * $b - 4 * $a * $c;
        if ($dis > 0) {
            $dx = 0.5 * sqrt($dis) / abs($a);
            $z1 = $xe - $dx;
            $z2 = $xe + $dx;
            $nz = abs($z1) < 1 ? $nz + 1 : $nz;
            $nz = abs($z2) < 1 ? $nz + 1 : $nz;
            $z1 = $z1 < -1 ? $z2 : $z1;
        }

        return array($nz, $z1, $z2, $xe, $ye);

    }

    /**
     *    this rather mickey mouse function takes a lot of
     *  arguments and then returns the sine of the altitude of the moon
     */
    private static function sinAlt($mjd, $hour, $glon, $cglat, $sglat) 
        {
        $mjd += $hour / 24;
        $t = ($mjd - 51544.5) / 36525;
        $objpos = self::minimoon($t);

        $ra = $objpos[1];
        $dec = $objpos[0];
        $decRad = deg2rad($dec);                                //php function
        $tau = 15 * (self::lmst($mjd, $glon) - $ra);

        return $sglat * sin($decRad) + $cglat * cos($decRad) * cos(deg2rad($tau));
        }

    /**
     *    returns an angle in degrees in the range 0 to 360
     */
    private static function degRange($x) {
        $b = $x / 360;
        $a = 360 * ($b - (int)$b);
        $retVal = $a < 0 ? $a + 360 : $a;
        return $retVal;
    }

    /*  LMST - Local Mean Sidereal Time
     *  GMST - Greenwich Mean Sidereal Time
     *  GAST - Greenwich Apparent Sidereal Time
     *
     *  Local Mean Sidereal time is GMST plus the observer's longitude measured positive to the east of Greenwich. This is the time commonly displayed on an observatory's sidereal clock.
	 *       LMST = GMST + (observer's east longitude)
     *
     * GMST - Greenwich Mean Sidereal Time 
     * https://www2.mps.mpg.de/homes/fraenz/systems/systems3art/node10.html#:~:text=The%20Greenwich%20mean%20sidereal%20time,Time%20(UT1)%20from%20J2000.
     * in seconds of a day of 86400s UT1, where $T_U$ is the time difference in Julian centuries of Universal Time (UT1) from J2000.0.
     * In conformance with IAU conventions for the motion of the earth's equator and equinox [ref 7] GMST is linked directly to UT1 through the equation
     * GMST (in seconds at UT1=0) = 24110.54841 + 8640184.812866 * T + 0.093104 * T^2 - 0.0000062 * T^3
     * where T is in Julian centuries from 2000 Jan. 1 12h UT1 , T = d / 36525    d = JD - 2451545.0
     *
     * greenwich star time explanation, funny https://24hourtime.info/2017/03/18/star-time-a-brief-history-of-sidereal-time/
     * The correct answer (which I hope you knew) is currently 23 hours, 56 minutes, and 4.1 seconds. The earth takes one (=) sidereal day to complete a single rotation
     * There are 365.2422 solar days and 366.2422 sidereal days in a (solar) year.
     * the sun appears to move slowly eastwards against the stellar background scenery. By the 20th or so, it crosses the celestial equator, die Schräglage der Erde
     * As with timekeeping on the earth, there are two versions of star time. The first is a single universal sidereal time for the earth that’s defined for a global reference point, 
     * in the same way that universal solar time was once relative to Greenwich in London.
     *
     * The other is your local sidereal time, which may be hours ahead or behind, depending on how far away you and your longitude are from Greenwich. 
     */
    private static function lmst($mjd, $glon) {
        $d = $mjd - 51544.5;
        $t = $d / 36525;
        $lst = self::degRange(280.46061839 + 360.98564736629 * $d + 0.000387933 * $t * $t - $t * $t * $t / 38710000);
        return ($lst / 15 + $glon / 15);        // Umrechnen auf Stunden 360/15=24
    }

    
    public function lmstwt($t, $glon) {
        $lst = self::degRange(280.46061839 + 360.98564736629 * $d + 0.000387933 * $t * $t - $t * $t * $t / 38710000);
        return ($lst / 15 + $glon / 15);
    }

    /**
     * takes t and returns the geocentric ra and dec in an array mooneq
     * claimed good to 5' (angle) in ra and 1' in dec
     * tallies with another approximate method and with ICE for a couple of dates
     */
    private static function minimoon($t) {

        $p2 = 6.283185307;
        $arc = 206264.8062;
        $coseps = 0.91748;
        $sineps = 0.39778;

        $lo = self::frac(0.606433 + 1336.855225 * $t);
        $l = $p2 * self::frac(0.374897 + 1325.552410 * $t);
        $l2 = $l * 2;
        $ls = $p2 * self::frac(0.993133 + 99.997361 * $t);
        $d = $p2 * self::frac(0.827361 + 1236.853086 * $t);
        $d2 = $d * 2;
        $f = $p2 * self::frac(0.259086 + 1342.227825 * $t);
        $f2 = $f * 2;

        $sinls = sin($ls);
        $sinf2 = sin($f2);

        $dl = 22640 /*[Visualization\Mobile\Beschattung\Beschattungselemente\Kueche\Programme]*/ * sin($l);
        $dl += -4586 * sin($l - $d2);
        $dl += 2370 * sin($d2);
        $dl += 769 * sin($l2);
        $dl += -668 * $sinls;
        $dl += -412 * $sinf2;
        $dl += -212 * sin($l2 - $d2);
        $dl += -206 * sin ($l + $ls - $d2);
        $dl += 192 * sin($l + $d2);
        $dl += -165 * sin($ls - $d2);
        $dl += -125 * sin($d);
        $dl += -110 * sin($l + $ls);
        $dl += 148 * sin($l - $ls);
        $dl += -55 * sin($f2 - $d2);

        $s = $f + ($dl + 412 * $sinf2 + 541 * $sinls) / $arc;
        $h = $f - $d2;
        $n = -526 * sin($h);
        $n += 44 * sin($l + $h);
        $n += -31 * sin(-$l + $h);
        $n += -23 * sin($ls + $h);
        $n += 11 * sin(-$ls + $h);
        $n += -25 * sin(-$l2 + $f);
        $n += 21 * sin(-$l + $f);

        $L_moon = $p2 * self::frac($lo + $dl / 1296000);
        $B_moon = (18520.0 * sin($s) + $n) / $arc;

        $cb = cos($B_moon);
        $x = $cb * cos($L_moon);
        $v = $cb * sin($L_moon);
        $w = sin($B_moon);
        $y = $coseps * $v - $sineps * $w;
        $z = $sineps * $v + $coseps * $w;
        $rho = sqrt(1 - $z * $z);
        $dec = (360 / $p2) * atan($z / $rho);
        $ra = (48 / $p2) * atan($y / ($x + $rho));
        $ra = $ra < 0 ? $ra + 24 : $ra;

        return array($dec, $ra);

    }

    /**
     *    returns the self::fractional part of x as used in self::minimoon and minisun
     */
    private static function frac($x) {
        $x -= (int)$x;
        return $x < 0 ? $x + 1 : $x;
    }

    /**
     * Takes the day, month, year and hours in the day and returns the
     * modified julian day number defined as mjd = jd - 2400000.5
     * checked OK for Greg era dates - 26th Dec 02
     *
     * Definitions by NASA
     * https://core2.gsfc.nasa.gov/time/
     *
     * Julian Day Number is an integer counter of the days beginning at noon 1 January 4713 B.C., which is Julian Day Number 0. 
     * The Julian Date is simply the extension of the Day Number to include a real fraction of day, allowing a continuous time unit.
     *
     * MJD modifies this Julian Date in two ways. The MJD begins at midnight rather than noon, in keeping with more standard conventions. 
     * Secondly, for simplicity, the first two digits of the Julian Date are removed. 
     * This is because, for some three centuries following 17 November 1858, the Julian day lies between 2400000 and 2500000. 
     * The MJD drops those first "24" digits. Thus, we have MJD = JD - 2400000.5
     *
     * Seine Epoche (Nullpunkt) ist damit am 17. November 1858 um 00:00 Uhr UT
     * 
     * Die Gregorianische Kalenderreform
     * Im Februar 1582 befand Papst Gregor XIII. die Reform für gut und verkündete sie in einer Bulle. 
     * Laut päpstlicher Anordnung folgte auf den 4. Oktober 1582 unmittelbar der 15. Oktober 1582 – zehn Tage wurden einfach übersprungen.
     *
     * formulae for date calculations always involve the mystery number 30.6001
     * quick recap. problems involving date arithmetic are solved by converting a d/m/y date into a julian day number (# of days since 4716BC)
     * or some offset absolute day# eg from 1/1/1900, adding & subtracting from that number and then converting back to d/m/y. thus two subroutines are needed date->julian and julian->date.
     * Magic number 30.6001 is the days per month approximation (365-31-28)/10 (ie av month days without jan & feb)
     *
     * oder astronomisch berechnet:  Erdumlaufdauer: 365,2425 Tage 
     * am 1.1.4716BC:  -4716+4716 1179/365,2425 = 
     * am 17.11.1858 : 18581117, a=1858*365=678170-679004=-834  => b = 4-18+464= 450 Schalttage  (0,2425*1858=450,565)  return 12*30.6001=367,2012+17=384,2012 = 384 + b = 834 + a = 0
     *
     * month, day starten bei 1      
     */

    private static function modifiedJulianDate($month, $day, $year) 
        {
        if ($month <= 2) {                 // Monate Jänner und Februar hinten anreihen, +12 - 1 year  (offset)
            $month += 12;
            $year--;
        }

        $a = 10000 * $year + 100 * $month + $day;           //YYYYMMDD, MM ist 3-14, 13,14 is YYYY-1
        $b = 0;
        if ($a <= 15821004.1) {                             // vor dem 4.10.1582 
            $b = -2 * (int)(($year + 4716) / 4) - 1179;                             // b zieht ab (yyyy+4716)/4  3 Jahre und ca. 83 Tage
        } else {                                            // nach dem 4.10.1582
            $b = (int)($year / 400) - (int)($year / 100) + (int)($year / 4);        // b addiert die Schalttage
        }

        $a = 365 * $year - 679004;                                          // Tage 365*yyyy - (1858*365,2425 = 678 620,565 + 383,435 = 366,435+17)
        return $a + $b + (int)(30.6001 * ($month + 1)) + $day;              // Jänner ist 2, Dezember ist 13
        }

    /**
     * Converts an hours decimal to hours and minutes, returns as array
     * only needed for utrise und utset
     */
    private static function convertTime($hours) 
        {
        $hrs = (int)($hours * 60 + 0.5) / 60.0;         // Auf Minuten aufrunden, Stunden und nach dem Komma Minuten
        $h = (int)($hrs);                               // die Stunden rausnehmen
        $m = (int)(60 * ($hrs - $h) + 0.5);             // die Minuten rausnehmen
        return array('hrs'=>$h, 'min'=>$m);
        }
    }
