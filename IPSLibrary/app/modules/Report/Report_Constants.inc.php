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

	/**@ingroup ipspowercontrol
	 * @{
	 *
	 * @file          Report_Constants.inc.php
	 * @author        Wolfgang Jöbstl
	 * @version
	 *  Version 2.50.1, 2.02.2016<br/>
	 *
	 * Definition der Konstanten für Report
	 *
	 */

	// Confguration Property Definition
	define ('IPSRP_PROPERTY_NAME',				'Name');
	define ('IPSRP_PROPERTY_VARWATT',			'VariableWatt');
	define ('IPSRP_PROPERTY_VARKWH',				'VariableKWH');
	define ('IPSRP_PROPERTY_VARM3',				'VariableM3');
	define ('IPSRP_PROPERTY_DISPLAY',			'Display');
	define ('IPSRP_PROPERTY_VALUETYPE',			'ValueType');

	define ('IPSRP_VALUETYPE_TOTAL',				'Total');
	define ('IPSRP_VALUETYPE_DETAIL',			'Detail');
	define ('IPSRP_VALUETYPE_OTHER',				'Other');
	define ('IPSRP_VALUETYPE_WATER',				'Water');
	define ('IPSRP_VALUETYPE_GAS',				'Gas');

	// Storage of calculated Values
	define ('IPSRP_VAR_VALUEKWH',					'ValueKWH_');
	define ('IPSRP_VAR_VALUEWATT',				'ValueWatt_');
	define ('IPSRP_VAR_VALUEM3',					'ValueM3_');
	// Selection
	define ('IPSRP_VAR_SELECTVALUE',				'SelectValue');
	define ('IPSRP_VAR_PERIODCOUNT',				'PeriodAndCount');
	define ('IPSRP_VAR_TYPEOFFSET',				'TypeAndOffset');
	define ('IPSRP_VAR_TIMEOFFSET',				'TimeOffset');
	define ('IPSRP_VAR_TIMECOUNT',				'TimeCount');
	// Visualization
	define ('IPSRP_VAR_CHARTHTML',				'ChartHTML');


	define ('IPSRP_PERIOD_HOUR',				10);
	define ('IPSRP_PERIOD_DAY',				11);
	define ('IPSRP_PERIOD_WEEK',				12);
	define ('IPSRP_PERIOD_MONTH',				13);
	define ('IPSRP_PERIOD_YEAR',				14);

	define ('IPSRP_COUNT_SEPARATOR',			10000);
	define ('IPSRP_COUNT_MINUS',				20001);
	define ('IPSRP_COUNT_VALUE',				20002);
	define ('IPSRP_COUNT_PLUS',				20003);

	define ('IPSRP_TYPE_WATER',				8);
	define ('IPSRP_TYPE_GAS',					9);
	define ('IPSRP_TYPE_WATT',					10);
	define ('IPSRP_TYPE_KWH',					11);
	define ('IPSRP_TYPE_EURO',					12);
	define ('IPSRP_TYPE_STACK',				13);
	define ('IPSRP_TYPE_STACK2',				14);
	define ('IPSRP_TYPE_PIE',					15);
	define ('IPSRP_TYPE_OFF',					16);

	define ('IPSRP_OFFSET_SEPARATOR',		10000);
	define ('IPSRP_OFFSET_PREV',				30000);
	define ('IPSRP_OFFSET_VALUE',				30001);
	define ('IPSRP_OFFSET_NEXT',				30002);



	/** @}*/
?>
