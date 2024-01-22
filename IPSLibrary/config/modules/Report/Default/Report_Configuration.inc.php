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


	/**@defgroup RemoetAccess_configuration Report Konfiguration
	 * @ingroup RemoteAccess
	 * @{
	 *
	 * Konfigurations File für Report
	 *
	 * @file          Report_Configuration.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 13.02.2012<br/>
	 *
	 */
/*

function Report_GetConfiguration()
	{
		return array(
			'Temperatur'       		=>	array
				(
				'color'     => 0x000080,
				'title'     => 'Aussentemperaturwerte',
				'series'    => array
				   (
					'Aussen-Temperatur'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TEMPERATURE,
					   'type' => 'line',
						'Unit' => '°C',
						'Id'     => xxxxx,
						),
					'Wintergarten-Temperatur'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TEMPERATURE,
					   'type' => 'line',
						'Unit' => '°C',
						'Id'     => yyyyyyy,
						),
					),
				),
			// Kontrolle der Wetterstation, mit absoluten Werten bei der Regenmenge  
			'Wetter'       		=>	array
				(
				'color'     => 0x008000,
				'title'     => 'Wetterwerte',
				'series'    => array
				   (
					'Wetterstation-Temperatur'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TEMPERATURE,
					   'type' => 'line',
						'Unit' => '°C',
						'Id'     => xxxxx,
						),
					'Wetterstation-Feuchtigkeit'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_HUMIDITY,
					   'type' => 'line',
						'Unit' => '%',
						'Id'     => xxxxx,
						),
					'Wetterstation-Regen'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_LENGTH,
					   'type' => 'line',
						'Unit' => 'mm',
						'Id'     => xxxxxxx,
						),
					),
				),
		// Ausgabe Status
		'Status'      				=>	array
				(
				'color'     => 0x800000,
				'title'     => 'Bewegung',
				'series'    => array
				   (
					'Bewegung-Gesamt'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_STATE,
					   'type' => 'line',
						'Unit' => '$',
						'Id'     => xxxxx,
						),
					'Alarm-Gesamt'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_STATE,
					   'type' => 'line',
						'Unit' => '$',
						'Id'     => xxxxxxx,
						),
					),
				),
	
			// Bewegungswerte zusammengefasst
			'Bewegung'    				=>	array
				(
				'color'     => 0x800080,
				'title'     => 'Bewegungsmelder',
				'series'    => array
				   (
					'Wohnzimmer'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_STATE,
					   'type' => 'line',
						'Unit' => '$',
						'Id'     => xxxxxxx,
						),
					'Wendeltreppe'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_STATE,
					   'type' => 'line',
						'Unit' => '$',
						'Id'     => xxxxxx,
						),
					),
				),
			// Strpomverbrauch
			'StromverbrauchHaus'      				=>	array
				(
				'color'     => 0x800000,
				'title'     => 'StromverbrauchHaus',
				'series'    => array
				   (
					'L1'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_CURRENT,
					   'type' => 'line',
						'Unit' => 'A',
						'Id'     => xxxxxxx,
						),
					'Leistung'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_POWER,
					   'type' => 'line',
						'Unit' => 'kW',
						'Id'     => xxxxx,
						),
					),
				),
			'Energieverbrauch'      				=>	array
				(
				'color'     => 0x800000,
				'title'     => 'Energieverbrauch',
				'series'    => array
				   (
					'Server'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_ENERGY,
					   'type' => 'line',
						'Unit' => 'kWh',
						'Id'     => xxxxxxxxxxxxx,
						),
					),
				),
			'Heizkoerper'      				=>	array
				(
				'color'     => 0x800000,
				'title'     => 'Status der Heizkoerper',
				'series'    => array
				   (
					'Arbeitszimmer'    => array
						(
						IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_STATE,
					   'type' => 'line',
						'Unit' => '$',
						'Id'     => xxxxxxxxxxxxxxxxxxxxxx,
						),
					),
				),

						);
	}
	
	function Report_GetValueConfiguration() {
		return array(
			0    => array(IPSRP_PROPERTY_NAME        => 'Temperatur',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			1    => array(IPSRP_PROPERTY_NAME        => 'Wetter',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			2    => array(IPSRP_PROPERTY_NAME        => 'Wetter2',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			3    => array(IPSRP_PROPERTY_NAME        => 'Luftfeuchtigkeit',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			4    => array(IPSRP_PROPERTY_NAME        => 'Status',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			5    => array(IPSRP_PROPERTY_NAME        => 'Innen-Temperatur',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			6    => array(IPSRP_PROPERTY_NAME        => 'Bewegung',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			7    => array(IPSRP_PROPERTY_NAME        => 'StromverbrauchHaus',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			8    => array(IPSRP_PROPERTY_NAME        => 'Energieverbrauch',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
			9    => array(IPSRP_PROPERTY_NAME        => 'Heizkoerper',
			              IPSRP_PROPERTY_DISPLAY     => true,
			              IPSRP_PROPERTY_VALUETYPE   => IPSRP_VALUETYPE_TOTAL,
			              ),
		);
	}

	// Stromkosten
	define ("IPSRP_ELECTRICITYRATE",    18 );
	
*/

    function Report_GetConfiguration()
        {
            return array(
                            );
        }


	function Report_GetValueConfiguration() 
		{
		return array(

					);
		}						  

	/** @}*/
?>