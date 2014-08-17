<?
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


	function set_gartenpumpe($value)
	   {
		$gartenpumpeID=35462;
		$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",$value);
		return $failure;
	   }

	function get_raincounterID()
		{ return 15620; }

	function get_AussenTemperaturGesternMax()
		{
		return GetValue(54386);
		}

	function AussenTemperaturGestern()
		{
		return GetValue(13320);
		}
		
	function RegenGestern()
		{
		return GetValue(21609);
		}

	function LetzterRegen()
		{
		return GetValue(27703);
		}

	 
	 

	/** @}*/
?>
