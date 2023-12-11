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


	/**@defgroup ipstwilight_configuration IPSTwilight Konfiguration
	 * @ingroup ipstwilight
	 * @{
	 *
	 * Konfigurations File für IPSTwilight
	 *
	 * @file          Gartensteuerung_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */

	function getGartensteuerungConfiguration()
	   {
		$Configuration = array(
			"KREISE" => 6,
			"TEMPERATUR-MITTEL" => 19,    /* Wenn Aussentemperatur im Mittel ueber diesen Wert UND Niederschlag kleiner REGN48H dann Giessen */
			"TEMPERATUR-MAX" => 28,			/* wenn es in den letzten  10 Tage weniger als REGEN10T geregnet hat ODER die Maiximaltemperatur den Wert TEMPERATUR-MAX ueberschreitet doppelt so lange giessen */
			"REGEN48H" => 3,              /* Wenn Aussentemperatur im Mittel ueber TEMPERATUR-MITTEL UND Niederschlag kleiner diesen Wert dann Giessen */
			"REGEN10T" => 20,             /* wenn es in den letzten  10 Tage weniger als REGEN10T geregnet hat ODER die Maximaltemperatur den Wert TEMPERATUR-MAX ueberschreitet doppelt so lange giessen */
			"DEBUG" => false,
			"PAUSE" => 2,					/* Pause zwischen den Beregnungszyklen, um dem Gardena Umschalter Zeit zur Entspannung zu geben */
			"KREIS1" => "Kreis1:Einfahrt",
			"KREIS2" => "Kreis2:Nord",
			"KREIS3" => "Kreis3:Sued",
			"KREIS4" => "Kreis4:Ost",
			"KREIS5" => "Kreis5:Brunnen",
			"KREIS6" => "Kreis6:West"
			);
		return $Configuration;
		}

	function set_gartenpumpe($value)
	   {
		$gartenpumpeID=37228 /*[Hardware\Homematic\Gartenpumpe]*/;
		$Server=RemoteAccess_Address();
		//echo "Server : ".$Server."\n\n";
		If ($Server=="")
		   {
			$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",$value);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$failure=$rpc->HM_WriteValueBoolean($gartenpumpeID,"STATE",$value);
			}
		return $failure;
	   }

    /* direkt das Register aus dem Homematic Gerät holen */

	function get_raincounterID()
	   /* Regenzaehler mit Vorwerten je nach RemoteAccess_Adress lokal oder Remote*/
		{ return 47194 /*[Hardware\Homematic\Wetterstation\RAIN_COUNTER]*/; }

	function get_aussentempID()
	   /* Aussentemperatur mit Vorwerten je nach RemoteAccess_Adress lokal oder Remote */
		{ return 41941 /*[Program\IPSLibrary\data\core\IPSComponent\Temperatur-Auswertung\Gesamtauswertung_Temp_Aussen]*/; }

	function RemoteAccess_Address()
	   /* Liest Werte von einem entfernten Gerät ein, empty wenn nicht */
		//{ return 'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/';	}
		{ return "";	}



	 
	 

	/** @}*/
?>