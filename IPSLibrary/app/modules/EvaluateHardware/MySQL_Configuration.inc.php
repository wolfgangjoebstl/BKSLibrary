<?


    /* Die Datenbank Konfiguration der mySQL, MariaDB Datenbank ipsymcon sollte in einem Configfile stehen.
     * hier kann die Deafult Konfiguration überschrieben beziehungsweise ergänzt werden 
     */

    function mySQLDatabase_getConfiguration() {
			$dataBaseConfiguration = array(
                "topologies"    => ["topologyID"   => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "Name"         => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                    "Type"         => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "parentID"     => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Updated"      => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "deviceList"    => ["deviceID"          => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "Name"              => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                    "Type"              => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "placeID"           => ["Type" => "int", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "serverGatewayID"   => ["Type" => "int", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "SubType"           => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "ProductType"       => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Serialnummer"      => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Information"       => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Changed"           => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "0000-00-00 00:00:00", "Extra" => ""],
                                    "Touch"             => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Updated"           => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "instances"     => ["instanceID"   => ["Field" => "instanceID", "Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "deviceID"     => ["Field" => "deviceID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "portID"       => ["Field" => "portID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Name"         => ["Field" => "Name", "Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                    "OID"          => ["Field" => "OID", "Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                    "TYPEDEV"      => ["Field" => "TYPEDEV", "Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "CONFIG"       => ["Field" => "CONFIG", "Type" => "varchar(4095)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Updated"      => ["Field" => "Updated", "Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "channels"      => ["channelID"     => ["Field" => "channelID", "Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "deviceID"     => ["Field" => "deviceID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "portID"       => ["Field" => "portID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Name"         => ["Field" => "Name", "Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                    "TYPECHAN"     => ["Field" => "TYPECHAN", "Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "RegisterAll"  => ["Field" => "RegisterAll", "Type" => "varchar(1024)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],            //jsonencode
                                    "Updated"      => ["Field" => "Updated", "Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "actuators"     => ["actuatorID"     => ["Field" => "actuatorID", "Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "deviceID"     => ["Field" => "deviceID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "portID"       => ["Field" => "portID", "Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Updated"      => ["Field" => "Updated", "Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "registers"     => ["registerID"        => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "deviceID"          => ["Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "portID"            => ["Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Name"              => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "componentModuleID" => ["Type" => "int", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "TYPEREG"           => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Configuration"     => ["Type" => "varchar(1023)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],          //jsonencode
                                    "Updated"           => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "valuesOnRegs"  => ["valueID"           => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "COID"              => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "registerID"        => ["Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "TypeRegKey"        => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Updated"           => ["Type" => "timestamp", "Null" => "NO", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                "serverGateways" =>     ["serverGatewayID"  => ["Type" => "int", "Null" => "", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                         "Name"             => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                         "Type"             => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "parentID"         => ["Type" => "varchar(255)", "Null" => "", "Key" => "", "Default" => "", "Extra" => ""],
                                         "Updated"          => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                        ],
                "componentModules" =>   ["componentModuleID"  => ["Type" => "int", "Null" => "", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                         "componentName"      => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "UNI", "Default" => "", "Extra" => ""],
                                         "moduleName"         => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "Updated"            => ["Type" => "timestamp", "Null" => "NO", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                        ],
                "auditTrail"     =>     ["auditTrailID"     => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                         "deviceID"          => ["Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "nameOfID"          => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "message"           => ["Type" => "varchar(2047)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "SQLStatement"      => ["Type" => "varchar(2047)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                         "Time"              => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => ""],
                                         "Updated"           => ["Type" => "timestamp", "Null" => "NO", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                        ],
                "eventLog"      => ["eventLogID"     => ["Type" => "int", "Null" => "NO", "Key" => "PRI", "Default" => "", "Extra" => "auto_increment"],
                                    "deviceID"          => ["Type" => "int", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "nameOfID"          => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "eventDescription"  => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "eventName"         => ["Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => "", "Extra" => ""],
                                    "Time"              => ["Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "current_timestamp()", "Extra" => ""],
                                    "Updated"           => ["Type" => "timestamp", "Null" => "NO", "Key" => "", "Default" => "current_timestamp()", "Extra" => "on update current_timestamp()"],
                                    ],
                    );
		return $dataBaseConfiguration; 
        }


	function IPSDeviceHandler_GetComponentModules() {
			$componentModules = array(
                "TYPE_MOTION" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Motion',
                                        'Module' => 'IPSModuleSensor_Motion',
                                                    ),
                                        ),
                                ),
                "TYPE_BUTTON" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Remote',
                                        'Module' => 'IPSModuleSensor_Remote',
                                                    ),
                                        ),
                                ),
                "TYPE_METER_CLIMATE" => array(
                        "*" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Remote',
                                        'Module' => 'IPSModuleSensor_Remote',
                                                    ),
                                        ),
                                ),
                "TYPE_CONTACT" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Motion',
                                        'Module' => 'IPSModuleSensor_Motion',
                                                    ),
                                        ),
                                ),
                "TYPE_SWITCH" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSwitch_RHomematic',
                                        'Module' => 'IPSModuleSwitch_IPSHeat',
                                                    ),
                                        ),
                        "FS20Family" => array(                                        
                                    "*" => array(
                                        'Component' => 'IPSComponentSwitch_RFS20',
                                        'Module' => 'IPSModuleSwitch_IPSHeat',
                                                    ),
                                        ),                                        
                                ),
                "TYPE_METER_TEMPERATURE" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Temperatur',
                                        'Module' => 'IPSModuleSensor_Temperatur',
                                                    ),
                                        ),
                        "NetatmoWeather" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Temperatur',
                                        'Module' => 'IPSModuleSensor_Temperatur',
                                                    ),
                                        ),
                        "FHTFamily" => array(                                        
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Temperatur',
                                        'Module' => 'IPSModuleSensor_Temperatur',
                                                    ),
                                        ),                                        
                                ),
                "TYPE_METER_HUMIDITY" => array(
                        "Homematic" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Feuchtigkeit',
                                        'Module' => 'IPSModuleSensor_Feuchtigkeit',
                                                    ),
                                        ),
                        "NetatmoWeather" => array(
                                    "*" => array(
                                        'Component' => 'IPSComponentSensor_Feuchtigkeit',
                                        'Module' => 'IPSModuleSensor_Feuchtigkeit',
                                                    ),
                                        ),
                                ),
                "TYPE_ACTUATOR" => array(
                        "Homematic" => array(
                                    "Funk" => array(
                                        'Component' => 'IPSComponentHeatControl_Homematic',
                                        'Module' => 'IPSModuleHeatControl_All',
                                                    ),
                                    "IP" => array(
                                        'Component' => 'IPSComponentHeatControl_HomematicIP',
                                        'Module' => 'IPSModuleHeatControl_All',
                                                    ),
                                        ),
                                ),
                "TYPE_THERMOSTAT" => array(
                        "Homematic" => array(
                                    "Funk" => array(
                                        'Component' => 'IPSComponentHeatSet_Homematic',
                                        'Module' => 'IPSModuleHeatSet_All',
                                                    ),
                                    "IP" => array(
                                        'Component' => 'IPSComponentHeatSet_HomematicIP',
                                        'Module' => 'IPSModuleHeatSet_All',
                                                    ),
                                        ),
                        "FHTFamily" => array(                                        
                                    "*" => array(
                                        'Component' => 'IPSComponentHeatSet_FS20',
                                        'Module' => 'IPSModuleHeatSet_All',
                                                    ),
                                        ),
                                ),
                    );
		return $componentModules; 
        }                    










?>