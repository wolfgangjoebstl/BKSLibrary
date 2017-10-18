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

	/**@ingroup IPSHeat
	 * @{
	 *
	 * @file          IPSHeat_ActionScript.inc.php
	 * @author        Andreas Brauneis
	 * @version
	 *  Version 2.50.1, 26.07.2012<br/>
	 *
	 * IPSHeat ActionScript 
	 *
	 */

	include_once "IPSHeat.inc.php";
	
	$variableId   = $_IPS['VARIABLE'];
	$value        = $_IPS['VALUE'];
	$categoryName = IPS_GetName(IPS_GetParent($_IPS['VARIABLE']));
	
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($_IPS['SENDER']=='WebFront') {
		switch ($categoryName) {
			case 'Switches':
				IPSHeat_SetValue($variableId, $value);
				break;
			case 'Groups':
				IPSHeat_SetGroup($variableId, $value);
				break;
			case 'Programs':
				IPSHeat_SetProgram($variableId, $value);
				break;
			default:
				trigger_error('Unknown Category '.$categoryName);
		}

	// ----------------------------------------------------------------------------------------------------------------------------
	} else {
	}

    /** @}*/
?>