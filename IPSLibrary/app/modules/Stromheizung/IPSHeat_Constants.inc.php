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

	/**@ingroup ipslight
	 * @{
	 *
	 * @file          IPSHeat_Constants.inc.php
	 * @author        Wolfgang Joebstl based on IPSLight Module from Andreas Brauneis
	 * @version
	 *
	 * Definition der Konstanten für IPSHeat
	 *
	 */

	// Confguration Property Definition
	define ('IPSHEAT_NAME',				0);
	define ('IPSHEAT_GROUPS',				1);
	define ('IPSHEAT_TYPE',				2);
	define ('IPSHEAT_COMPONENT',			3);
	define ('IPSHEAT_POWERCIRCLE',			4);
	define ('IPSHEAT_POWERWATT',			5);
	define ('IPSHEAT_ACTIVATABLE',			6);
	define ('IPSHEAT_PROGRAMON',			7);
	define ('IPSHEAT_PROGRAMOFF',			8);
	define ('IPSHEAT_PROGRAMLEVEL',		9);
	define ('IPSHEAT_PROGRAMRGB',			10);
	define ('IPSHEAT_DESCRIPTION',			99);
		
	define ('IPSHEAT_WFCSPLITPANEL',		'WFCSplitPanel');
	define ('IPSHEAT_WFCCATEGORY',			'WFCCategory');
	define ('IPSHEAT_WFCGROUP',			'WFCGroup');
	define ('IPSHEAT_WFCLINKS',			'WFCLinks');

	// Supported Device Types
	define ('IPSHEAT_TYPE_SWITCH',			'Switch');
	define ('IPSHEAT_TYPE_RGB',			'RGB');
	define ('IPSHEAT_TYPE_DIMMER',			'Dimmer');
	define ('IPSHEAT_TYPE_SET',			'Thermostat');

	// Device specific Properties
	define ('IPSHEAT_DEVICE_COLOR',		'#Color');
	define ('IPSHEAT_DEVICE_LEVEL',		'#Level');


	/** @}*/
?>