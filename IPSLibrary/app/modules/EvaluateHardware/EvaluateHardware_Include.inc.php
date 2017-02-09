<?
function HomematicInstanzen() { return array(
"Homematic-CCU" => array(
         "CONFIG" => '{"Open":0,"Host":"10.0.0.116","Port":5543,"RFOpen":true,"RFPort":2001,"WROpen":false,"WRPort":2000,"IPOpen":true,"IPPort":2010,"IPAddress":"10.0.0.20"}', 
             	),
"HomeMatic LanAdapter" => array(
         "CONFIG" => '{"Open":0,"Host":"localhost","Port":5544,"RFOpen":true,"RFPort":2001,"WROpen":false,"WRPort":2000,"IPOpen":false,"IPPort":2010,"IPAddress":"10.0.0.20"}', 
             	),
);}

function FHTList() { return array(
"Schlafzimmer FHT80b" => array(
         "OID" => 30074, 
         "Adresse" => "5080", 
         "Name" => "Schlafzimmer FHT80b", 
         "COID" => array(
                "WindowOpen" => array(
                              "OID" => "58793", 
                              "Name" => "Fenster geöffnet", 
                              "Typ" => "2",), 
                "TemeratureVar" => array(
                              "OID" => "33694", 
                              "Name" => "Temperatur", 
                              "Typ" => "2",), 
                "LowBatteryVar" => array(
                              "OID" => "54625", 
                              "Name" => "Batterie", 
                              "Typ" => "2",), 
                "TargetModeVar" => array(
                              "OID" => "35701", 
                              "Name" => "Soll Modus", 
                              "Typ" => "2",), 
                "TargetTempVar" => array(
                              "OID" => "41340", 
                              "Name" => "Soll Temperatur", 
                              "Typ" => "2",), 
                "PositionVar" => array(
                              "OID" => "34186", 
                              "Name" => "Position", 
                              "Typ" => "2",), 
                "TargetIPSTempVar" => array(
                              "OID" => "27662", 
                              "Name" => "Soll Temperatur (Ausstehend)", 
                              "Typ" => "2",), 
                "TargetIPSModeVar" => array(
                              "OID" => "14045", 
                              "Name" => "Soll Modus (Ausstehend)", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer FHT80b" => array(
         "OID" => 34409, 
         "Adresse" => "5095", 
         "Name" => "Badezimmer FHT80b", 
         "COID" => array(
                "TemeratureVar" => array(
                              "OID" => "56634", 
                              "Name" => "Temperatur", 
                              "Typ" => "2",), 
                "TargetTempVar" => array(
                              "OID" => "11472", 
                              "Name" => "Soll Temperatur", 
                              "Typ" => "2",), 
                "TargetModeVar" => array(
                              "OID" => "31428", 
                              "Name" => "Soll Modus", 
                              "Typ" => "2",), 
                "PositionVar" => array(
                              "OID" => "14642", 
                              "Name" => "Position", 
                              "Typ" => "2",), 
                "TargetIPSModeVar" => array(
                              "OID" => "21736", 
                              "Name" => "Soll Modus (Ausstehend)", 
                              "Typ" => "2",), 
                "TargetIPSTempVar" => array(
                              "OID" => "39569", 
                              "Name" => "Soll Temperatur (Ausstehend)", 
                              "Typ" => "2",), 
                "WindowOpen" => array(
                              "OID" => "25921", 
                              "Name" => "Fenster geöffnet", 
                              "Typ" => "2",), 
                "LowBatteryVar" => array(
                              "OID" => "17679", 
                              "Name" => "Batterie", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer FHT80b" => array(
         "OID" => 29698, 
         "Adresse" => "5053", 
         "Name" => "Wohnzimmer FHT80b", 
         "COID" => array(
                "TargetIPSTempVar" => array(
                              "OID" => "52266", 
                              "Name" => "Soll Temperatur (Ausstehend)", 
                              "Typ" => "2",), 
                "LowBatteryVar" => array(
                              "OID" => "57647", 
                              "Name" => "Batterie", 
                              "Typ" => "2",), 
                "TemeratureVar" => array(
                              "OID" => "17554", 
                              "Name" => "Temperatur", 
                              "Typ" => "2",), 
                "TargetModeVar" => array(
                              "OID" => "46337", 
                              "Name" => "Soll Modus", 
                              "Typ" => "2",), 
                "TargetTempVar" => array(
                              "OID" => "40577", 
                              "Name" => "Soll Temperatur", 
                              "Typ" => "2",), 
                "TargetIPSModeVar" => array(
                              "OID" => "23521", 
                              "Name" => "Soll Modus (Ausstehend)", 
                              "Typ" => "2",), 
                "PositionVar" => array(
                              "OID" => "27073", 
                              "Name" => "Position", 
                              "Typ" => "2",), 
                "WindowOpen" => array(
                              "OID" => "30110", 
                              "Name" => "Fenster geöffnet", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer FHT80b" => array(
         "OID" => 17571, 
         "Adresse" => "5032", 
         "Name" => "Arbeitszimmer FHT80b", 
         "COID" => array(
                "PositionVar" => array(
                              "OID" => "54440", 
                              "Name" => "Position", 
                              "Typ" => "2",), 
                "TargetIPSModeVar" => array(
                              "OID" => "40009", 
                              "Name" => "Soll Modus (Ausstehend)", 
                              "Typ" => "2",), 
                "LowBatteryVar" => array(
                              "OID" => "38662", 
                              "Name" => "Batterie", 
                              "Typ" => "2",), 
                "TemeratureVar" => array(
                              "OID" => "39227", 
                              "Name" => "Temperatur", 
                              "Typ" => "2",), 
                "TargetIPSTempVar" => array(
                              "OID" => "38674", 
                              "Name" => "Soll Temperatur (Ausstehend)", 
                              "Typ" => "2",), 
                "WindowOpen" => array(
                              "OID" => "30938", 
                              "Name" => "Fenster geöffnet", 
                              "Typ" => "2",), 
                "TargetModeVar" => array(
                              "OID" => "19491", 
                              "Name" => "Soll Modus", 
                              "Typ" => "2",), 
                "TargetTempVar" => array(
                              "OID" => "12767", 
                              "Name" => "Soll Temperatur", 
                              "Typ" => "2",), 
             	),

      	),
);}
function FS20List() { return array(
"Arbeitszimmer Festplatten FS20 Geraet" => array(
         "OID" => 10020, 
         "Adresse" => "11", 
         "Name" => "Arbeitszimmer Festplatten FS20 Geraet", 
         "COID" => array(
                "IntensityVariable" => array(
                              "OID" => "19290", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "25069", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "47385", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "DataVariable" => array(
                              "OID" => "26577", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
             	),

      	),
"Gaestezimmer FS20 Geraet" => array(
         "OID" => 24122, 
         "Adresse" => "12", 
         "Name" => "Gaestezimmer FS20 Geraet", 
         "COID" => array(
                "DataVariable" => array(
                              "OID" => "51047", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "37609", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "37276", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "26326", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer Beleuchtung" => array(
         "OID" => 40351, 
         "Adresse" => "12", 
         "Name" => "Arbeitszimmer Beleuchtung", 
         "COID" => array(
                "DataVariable" => array(
                              "OID" => "59750", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "52000", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "22271", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "14885", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer Media Verstaerker FS20 Geraet" => array(
         "OID" => 39136, 
         "Adresse" => "11", 
         "Name" => "Arbeitszimmer Media Verstaerker FS20 Geraet", 
         "COID" => array(
                "DataVariable" => array(
                              "OID" => "33400", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "40650", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "22078", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "15320", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer Dekolampe FS20 Geraet" => array(
         "OID" => 38359, 
         "Adresse" => "12", 
         "Name" => "Wohnzimmer Dekolampe FS20 Geraet", 
         "COID" => array(
                "IntensityVariable" => array(
                              "OID" => "57601", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "DataVariable" => array(
                              "OID" => "24842", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "34473", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "11391", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
             	),

      	),
"Schlafzimmer Deckenbeleuchtung FS20 Geraet" => array(
         "OID" => 31970, 
         "Adresse" => "14", 
         "Name" => "Schlafzimmer Deckenbeleuchtung FS20 Geraet", 
         "COID" => array(
                "StatusVariable" => array(
                              "OID" => "13362", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "DataVariable" => array(
                              "OID" => "57030", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "49899", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "29832", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
             	),

      	),
"Eckstehlampe FS20 Geraet" => array(
         "OID" => 12828, 
         "Adresse" => "13", 
         "Name" => "Eckstehlampe FS20 Geraet", 
         "COID" => array(
                "IntensityVariable" => array(
                              "OID" => "51581", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "47099", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "28915", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "DataVariable" => array(
                              "OID" => "13262", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer Server" => array(
         "OID" => 25840, 
         "Adresse" => "11", 
         "Name" => "Arbeitszimmer Server", 
         "COID" => array(
                "DataVariable" => array(
                              "OID" => "58664", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "52704", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "32079", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "21226", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer Kastenventilator FS20 Geraet" => array(
         "OID" => 19599, 
         "Adresse" => "13", 
         "Name" => "Wohnzimmer Kastenventilator FS20 Geraet", 
         "COID" => array(
                "DataVariable" => array(
                              "OID" => "51686", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "45690", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "39453", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "StatusVariable" => array(
                              "OID" => "19833", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
             	),

      	),
"Schlafzimmer Kastenbeleuchtung FS20 Geraet" => array(
         "OID" => 10987, 
         "Adresse" => "14", 
         "Name" => "Schlafzimmer Kastenbeleuchtung FS20 Geraet", 
         "COID" => array(
                "StatusVariable" => array(
                              "OID" => "16712", 
                              "Name" => "Status", 
                              "Typ" => "2",), 
                "IntensityVariable" => array(
                              "OID" => "58200", 
                              "Name" => "Intensität", 
                              "Typ" => "2",), 
                "TimerVariable" => array(
                              "OID" => "25583", 
                              "Name" => "Timer", 
                              "Typ" => "2",), 
                "DataVariable" => array(
                              "OID" => "29676", 
                              "Name" => "Daten", 
                              "Typ" => "2",), 
             	),

      	),
);}
function HomematicList() { return array(
"Gaestezimmer-Stellmotor" => array(
         "OID" => 48718, 
         "Adresse" => "MEQ0806357:4", 
         "Name" => "Gaestezimmer-Stellmotor", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "ACTUAL_TEMPERATURE" => array(
                              "OID" => "57856", 
                              "Name" => "ACTUAL_TEMPERATURE", 
                              "Typ" => "2",), 
                "SET_TEMPERATURE" => array(
                              "OID" => "59433", 
                              "Name" => "SET_TEMPERATURE", 
                              "Typ" => "2",), 
                "PARTY_START_TIME" => array(
                              "OID" => "51240", 
                              "Name" => "PARTY_START_TIME", 
                              "Typ" => "2",), 
                "PARTY_STOP_TIME" => array(
                              "OID" => "58341", 
                              "Name" => "PARTY_STOP_TIME", 
                              "Typ" => "2",), 
                "FAULT_REPORTING" => array(
                              "OID" => "21428", 
                              "Name" => "FAULT_REPORTING", 
                              "Typ" => "2",), 
                "PARTY_STOP_DAY" => array(
                              "OID" => "54456", 
                              "Name" => "PARTY_STOP_DAY", 
                              "Typ" => "2",), 
                "CONTROL_MODE" => array(
                              "OID" => "48381", 
                              "Name" => "CONTROL_MODE", 
                              "Typ" => "2",), 
                "BOOST_STATE" => array(
                              "OID" => "46709", 
                              "Name" => "BOOST_STATE", 
                              "Typ" => "2",), 
                "PARTY_START_DAY" => array(
                              "OID" => "37795", 
                              "Name" => "PARTY_START_DAY", 
                              "Typ" => "2",), 
                "PARTY_TEMPERATURE" => array(
                              "OID" => "34050", 
                              "Name" => "PARTY_TEMPERATURE", 
                              "Typ" => "2",), 
                "BATTERY_STATE" => array(
                              "OID" => "33846", 
                              "Name" => "BATTERY_STATE", 
                              "Typ" => "2",), 
                "PARTY_START_MONTH" => array(
                              "OID" => "22596", 
                              "Name" => "PARTY_START_MONTH", 
                              "Typ" => "2",), 
                "VALVE_STATE" => array(
                              "OID" => "20767", 
                              "Name" => "VALVE_STATE", 
                              "Typ" => "2",), 
                "PARTY_STOP_MONTH" => array(
                              "OID" => "20112", 
                              "Name" => "PARTY_STOP_MONTH", 
                              "Typ" => "2",), 
                "PARTY_STOP_YEAR" => array(
                              "OID" => "15177", 
                              "Name" => "PARTY_STOP_YEAR", 
                              "Typ" => "2",), 
                "PARTY_START_YEAR" => array(
                              "OID" => "14310", 
                              "Name" => "PARTY_START_YEAR", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmertuere Kontakt" => array(
         "OID" => 58763, 
         "Adresse" => "LEQ0501511:1", 
         "Name" => "Wohnzimmertuere Kontakt", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "ERROR" => array(
                              "OID" => "54099", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "22915", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "25906", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "LOWBAT" => array(
                              "OID" => "20268", 
                              "Name" => "LOWBAT", 
                              "Typ" => "2",), 
             	),

      	),
"ArbeitszimmerComputer2" => array(
         "OID" => 55176, 
         "Adresse" => "LEQ0627606:2", 
         "Name" => "ArbeitszimmerComputer2", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "53884", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "47419", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
                "INHIBIT" => array(
                              "OID" => "30276", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
             	),

      	),
"Vorzimmer Bewegung" => array(
         "OID" => 28777, 
         "Adresse" => "IEQ0064942:1", 
         "Name" => "Vorzimmer Bewegung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "MOTION" => array(
                              "OID" => "30700", 
                              "Name" => "MOTION", 
                              "Typ" => "2",), 
                "BRIGHTNESS" => array(
                              "OID" => "59265", 
                              "Name" => "BRIGHTNESS", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "39979", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "34072", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-3" => array(
         "OID" => 26820, 
         "Adresse" => "MEQ1084617:3", 
         "Name" => "Badezimmer-Taster-3", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "26279", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "56653", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "21583", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "30265", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "21973", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-5" => array(
         "OID" => 55981, 
         "Adresse" => "LEQ1059882:5", 
         "Name" => "Wohnzimmer-Taster-5", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INSTALL_TEST" => array(
                              "OID" => "42452", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "48204", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "50990", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "42798", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "18485", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer-Taster-Aus" => array(
         "OID" => 54788, 
         "Adresse" => "JEQ0004086:1", 
         "Name" => "Arbeitszimmer-Taster-Aus", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_SHORT" => array(
                              "OID" => "56742", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "52467", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "37416", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Gaestezimmer-Heizung" => array(
         "OID" => 53984, 
         "Adresse" => "LEQ0591638:2", 
         "Name" => "Gaestezimmer-Heizung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PARTY_START_DAY" => array(
                              "OID" => "57191", 
                              "Name" => "PARTY_START_DAY", 
                              "Typ" => "2",), 
                "LOWBAT_REPORTING" => array(
                              "OID" => "11332", 
                              "Name" => "LOWBAT_REPORTING", 
                              "Typ" => "2",), 
                "PARTY_START_TIME" => array(
                              "OID" => "53680", 
                              "Name" => "PARTY_START_TIME", 
                              "Typ" => "2",), 
                "BOOST_STATE" => array(
                              "OID" => "53618", 
                              "Name" => "BOOST_STATE", 
                              "Typ" => "2",), 
                "PARTY_STOP_TIME" => array(
                              "OID" => "54311", 
                              "Name" => "PARTY_STOP_TIME", 
                              "Typ" => "2",), 
                "BATTERY_STATE" => array(
                              "OID" => "43036", 
                              "Name" => "BATTERY_STATE", 
                              "Typ" => "2",), 
                "PARTY_STOP_DAY" => array(
                              "OID" => "51872", 
                              "Name" => "PARTY_STOP_DAY", 
                              "Typ" => "2",), 
                "PARTY_START_YEAR" => array(
                              "OID" => "47257", 
                              "Name" => "PARTY_START_YEAR", 
                              "Typ" => "2",), 
                "ACTUAL_HUMIDITY" => array(
                              "OID" => "47533", 
                              "Name" => "ACTUAL_HUMIDITY", 
                              "Typ" => "2",), 
                "PARTY_START_MONTH" => array(
                              "OID" => "36630", 
                              "Name" => "PARTY_START_MONTH", 
                              "Typ" => "2",), 
                "ACTUAL_TEMPERATURE" => array(
                              "OID" => "37789", 
                              "Name" => "ACTUAL_TEMPERATURE", 
                              "Typ" => "2",), 
                "PARTY_STOP_MONTH" => array(
                              "OID" => "25698", 
                              "Name" => "PARTY_STOP_MONTH", 
                              "Typ" => "2",), 
                "SET_TEMPERATURE" => array(
                              "OID" => "29713", 
                              "Name" => "SET_TEMPERATURE", 
                              "Typ" => "2",), 
                "CONTROL_MODE" => array(
                              "OID" => "25604", 
                              "Name" => "CONTROL_MODE", 
                              "Typ" => "2",), 
                "COMMUNICATION_REPORTING" => array(
                              "OID" => "16208", 
                              "Name" => "COMMUNICATION_REPORTING", 
                              "Typ" => "2",), 
                "WINDOW_OPEN_REPORTING" => array(
                              "OID" => "13117", 
                              "Name" => "WINDOW_OPEN_REPORTING", 
                              "Typ" => "2",), 
                "PARTY_STOP_YEAR" => array(
                              "OID" => "12391", 
                              "Name" => "PARTY_STOP_YEAR", 
                              "Typ" => "2",), 
                "PARTY_TEMPERATURE" => array(
                              "OID" => "11891", 
                              "Name" => "PARTY_TEMPERATURE", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-1" => array(
         "OID" => 19854, 
         "Adresse" => "MEQ1084617:1", 
         "Name" => "Badezimmer-Taster-1", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "46754", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "14378", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "25309", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "50533", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "33086", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-4" => array(
         "OID" => 46168, 
         "Adresse" => "LEQ1059882:4", 
         "Name" => "Wohnzimmer-Taster-4", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INSTALL_TEST" => array(
                              "OID" => "17313", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "30107", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "46549", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "43405", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "31906", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
             	),

      	),
"Abwesenheits-Schalter-Verriegeln" => array(
         "OID" => 49015, 
         "Adresse" => "IEQ0100238:1", 
         "Name" => "Abwesenheits-Schalter-Verriegeln", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "23432", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "11271", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "34700", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Zentralzimmer Bewegung" => array(
         "OID" => 46830, 
         "Adresse" => "NEQ0046162:1", 
         "Name" => "Zentralzimmer Bewegung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "ERROR" => array(
                              "OID" => "14781", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "BRIGHTNESS" => array(
                              "OID" => "58559", 
                              "Name" => "BRIGHTNESS", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "28750", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "MOTION" => array(
                              "OID" => "50455", 
                              "Name" => "MOTION", 
                              "Typ" => "2",), 
             	),

      	),
"EsstischEffektlicht:Status" => array(
         "OID" => 37361, 
         "Adresse" => "0001D3C996136E:0", 
         "Name" => "EsstischEffektlicht:Status", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "RSSI_DEVICE" => array(
                              "OID" => "38167", 
                              "Name" => "RSSI_DEVICE", 
                              "Typ" => "2",), 
                "UNREACH" => array(
                              "OID" => "27691", 
                              "Name" => "UNREACH", 
                              "Typ" => "2",), 
                "UPDATE_PENDING" => array(
                              "OID" => "41573", 
                              "Name" => "UPDATE_PENDING", 
                              "Typ" => "2",), 
                "DUTY_CYCLE" => array(
                              "OID" => "26757", 
                              "Name" => "DUTY_CYCLE", 
                              "Typ" => "2",), 
                "CONFIG_PENDING" => array(
                              "OID" => "59964", 
                              "Name" => "CONFIG_PENDING", 
                              "Typ" => "2",), 
                "RSSI_PEER" => array(
                              "OID" => "14724", 
                              "Name" => "RSSI_PEER", 
                              "Typ" => "2",), 
                "OPERATING_VOLTAGE" => array(
                              "OID" => "29324", 
                              "Name" => "OPERATING_VOLTAGE", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-2" => array(
         "OID" => 27224, 
         "Adresse" => "MEQ1084617:2", 
         "Name" => "Badezimmer-Taster-2", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "34413", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "38429", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "10555", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "31146", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "26393", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Homematic Dummy Schalter" => array(
         "OID" => 47301, 
         "Adresse" => "JEQ0066960:1", 
         "Name" => "Homematic Dummy Schalter", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INHIBIT" => array(
                              "OID" => "51119", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "40286", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "26398", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
             	),

      	),
"Kueche-Schalter-Ledlicht" => array(
         "OID" => 45358, 
         "Adresse" => "JEQ0295555:1", 
         "Name" => "Kueche-Schalter-Ledlicht", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INHIBIT" => array(
                              "OID" => "28147", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "57969", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "55076", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-1" => array(
         "OID" => 27916, 
         "Adresse" => "LEQ1059882:1", 
         "Name" => "Wohnzimmer-Taster-1", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_CONT" => array(
                              "OID" => "58801", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "52075", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "10812", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "20774", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "20522", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-6" => array(
         "OID" => 29041, 
         "Adresse" => "LEQ1059882:6", 
         "Name" => "Wohnzimmer-Taster-6", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_CONT" => array(
                              "OID" => "56893", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "55258", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "54170", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "19283", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "22660", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
             	),

      	),
"EsstischEffektlicht:Schalter" => array(
         "OID" => 18533, 
         "Adresse" => "0001D3C996136E:3", 
         "Name" => "EsstischEffektlicht:Schalter", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "18511", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "PROCESS" => array(
                              "OID" => "55954", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "21810", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
             	),

      	),
"WohnzimmerEffektlicht:Energie" => array(
         "OID" => 55996, 
         "Adresse" => "0001D3C98DD615:6", 
         "Name" => "WohnzimmerEffektlicht:Energie", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "POWER" => array(
                              "OID" => "12366", 
                              "Name" => "POWER", 
                              "Typ" => "2",), 
                "CURRENT" => array(
                              "OID" => "30790", 
                              "Name" => "CURRENT", 
                              "Typ" => "2",), 
                "FREQUENCY" => array(
                              "OID" => "30834", 
                              "Name" => "FREQUENCY", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER_OVERFLOW" => array(
                              "OID" => "49773", 
                              "Name" => "ENERGY_COUNTER_OVERFLOW", 
                              "Typ" => "2",), 
                "VOLTAGE" => array(
                              "OID" => "35307", 
                              "Name" => "VOLTAGE", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER" => array(
                              "OID" => "36231", 
                              "Name" => "ENERGY_COUNTER", 
                              "Typ" => "2",), 
             	),

      	),
"Kueche Bewegung" => array(
         "OID" => 38387, 
         "Adresse" => "IEQ0538004:1", 
         "Name" => "Kueche Bewegung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "BRIGHTNESS" => array(
                              "OID" => "56737", 
                              "Name" => "BRIGHTNESS", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "31048", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "38516", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "MOTION" => array(
                              "OID" => "11681", 
                              "Name" => "MOTION", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer Bewegung" => array(
         "OID" => 43271, 
         "Adresse" => "LEQ1292430:1", 
         "Name" => "Wohnzimmer Bewegung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "MOTION" => array(
                              "OID" => "50775", 
                              "Name" => "MOTION", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "52063", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "26066", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "BRIGHTNESS" => array(
                              "OID" => "25769", 
                              "Name" => "BRIGHTNESS", 
                              "Typ" => "2",), 
             	),

      	),
"Urlaubs-Schalter-Entriegeln" => array(
         "OID" => 42691, 
         "Adresse" => "IEQ0100238:4", 
         "Name" => "Urlaubs-Schalter-Entriegeln", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_SHORT" => array(
                              "OID" => "18094", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "26427", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "39615", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer Bewegung" => array(
         "OID" => 41242, 
         "Adresse" => "GEQ0127585:1", 
         "Name" => "Arbeitszimmer Bewegung", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "MOTION" => array(
                              "OID" => "54389", 
                              "Name" => "MOTION", 
                              "Typ" => "2",), 
                "STICKY_UNREACH" => array(
                              "OID" => "48636", 
                              "Name" => "STICKY_UNREACH", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "39565", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "UNREACH" => array(
                              "OID" => "34587", 
                              "Name" => "UNREACH", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "32746", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "BRIGHTNESS" => array(
                              "OID" => "21180", 
                              "Name" => "BRIGHTNESS", 
                              "Typ" => "2",), 
                "LOWBAT" => array(
                              "OID" => "16641", 
                              "Name" => "LOWBAT", 
                              "Typ" => "2",), 
                "CONFIG_PENDING" => array(
                              "OID" => "12214", 
                              "Name" => "CONFIG_PENDING", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-3" => array(
         "OID" => 10752, 
         "Adresse" => "LEQ1059882:3", 
         "Name" => "Wohnzimmer-Taster-3", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INSTALL_TEST" => array(
                              "OID" => "36624", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "35711", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "16103", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "16634", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "12094", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
             	),

      	),
"Abwesenheits-Schalter-Entriegeln" => array(
         "OID" => 19720, 
         "Adresse" => "IEQ0100238:2", 
         "Name" => "Abwesenheits-Schalter-Entriegeln", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "40022", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "56637", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "34112", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Kuechenfenster Kontakt" => array(
         "OID" => 38507, 
         "Adresse" => "LEQ0501629:1", 
         "Name" => "Kuechenfenster Kontakt", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "54908", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "LOWBAT" => array(
                              "OID" => "44100", 
                              "Name" => "LOWBAT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "13009", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "12518", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmerfenster-Kontakt" => array(
         "OID" => 30480, 
         "Adresse" => "LEQ1060012:1", 
         "Name" => "Arbeitszimmerfenster-Kontakt", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "ERROR" => array(
                              "OID" => "11727", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "39823", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "41623", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "LOWBAT" => array(
                              "OID" => "32544", 
                              "Name" => "LOWBAT", 
                              "Typ" => "2",), 
             	),

      	),
"ArbeitszimmerSchalter1" => array(
         "OID" => 36225, 
         "Adresse" => "LEQ0627606:1", 
         "Name" => "ArbeitszimmerSchalter1", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "22272", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "INHIBIT" => array(
                              "OID" => "14159", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "14644", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
             	),

      	),
"Gaestezimmer-Messwerte" => array(
         "OID" => 24690, 
         "Adresse" => "LEQ0591638:1", 
         "Name" => "Gaestezimmer-Messwerte", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "TEMPERATURE" => array(
                              "OID" => "55916", 
                              "Name" => "TEMPERATURE", 
                              "Typ" => "2",), 
                "HUMIDITY" => array(
                              "OID" => "22048", 
                              "Name" => "HUMIDITY", 
                              "Typ" => "2",), 
             	),

      	),
"ArbeitszimmerLampe4" => array(
         "OID" => 34651, 
         "Adresse" => "LEQ0627606:4", 
         "Name" => "ArbeitszimmerLampe4", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "36236", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "INHIBIT" => array(
                              "OID" => "24129", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "29387", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
             	),

      	),
"Eingangstürenmelder" => array(
         "OID" => 18635, 
         "Adresse" => "JEQ0068698:1", 
         "Name" => "Eingangstürenmelder", 
         "CCU" => "HomeMatic LanAdapter", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "41275", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "LOWBAT" => array(
                              "OID" => "59282", 
                              "Name" => "LOWBAT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "56865", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "ERROR" => array(
                              "OID" => "45453", 
                              "Name" => "ERROR", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Mediencenter-Leistungswerte" => array(
         "OID" => 31985, 
         "Adresse" => "LEQ0538372:2", 
         "Name" => "Wohnzimmer-Mediencenter-Leistungswerte", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "VOLTAGE" => array(
                              "OID" => "54469", 
                              "Name" => "VOLTAGE", 
                              "Typ" => "2",), 
                "BOOT" => array(
                              "OID" => "44257", 
                              "Name" => "BOOT", 
                              "Typ" => "2",), 
                "CURRENT" => array(
                              "OID" => "45713", 
                              "Name" => "CURRENT", 
                              "Typ" => "2",), 
                "POWER" => array(
                              "OID" => "32622", 
                              "Name" => "POWER", 
                              "Typ" => "2",), 
                "FREQUENCY" => array(
                              "OID" => "31693", 
                              "Name" => "FREQUENCY", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER" => array(
                              "OID" => "24399", 
                              "Name" => "ENERGY_COUNTER", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Kugellampe" => array(
         "OID" => 31625, 
         "Adresse" => "LEQ0145674:1", 
         "Name" => "Wohnzimmer-Kugellampe", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "38447", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "49102", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
                "INHIBIT" => array(
                              "OID" => "29972", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Mediencenter" => array(
         "OID" => 30531, 
         "Adresse" => "LEQ0538372:1", 
         "Name" => "Wohnzimmer-Mediencenter", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "22784", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "INHIBIT" => array(
                              "OID" => "49332", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "17522", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-5" => array(
         "OID" => 53153, 
         "Adresse" => "MEQ1084617:5", 
         "Name" => "Badezimmer-Taster-5", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "18119", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "53190", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "29022", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "54323", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "57543", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
             	),

      	),
"Aussen-Westseite" => array(
         "OID" => 16874, 
         "Adresse" => "JEQ0267840:1", 
         "Name" => "Aussen-Westseite", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "HUMIDITY" => array(
                              "OID" => "51998", 
                              "Name" => "HUMIDITY", 
                              "Typ" => "2",), 
                "TEMPERATURE" => array(
                              "OID" => "22695", 
                              "Name" => "TEMPERATURE", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-Taster-2" => array(
         "OID" => 27044, 
         "Adresse" => "LEQ1059882:2", 
         "Name" => "Wohnzimmer-Taster-2", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_CONT" => array(
                              "OID" => "59957", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "57863", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "56911", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "54251", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "21989", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
             	),

      	),
"WohnzimmerEffektlicht:Schalter" => array(
         "OID" => 16639, 
         "Adresse" => "0001D3C98DD615:3", 
         "Name" => "WohnzimmerEffektlicht:Schalter", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PROCESS" => array(
                              "OID" => "57749", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "26782", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "58640", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
             	),

      	),
"ArbeitszimmerFestplatten3" => array(
         "OID" => 21427, 
         "Adresse" => "LEQ0627606:3", 
         "Name" => "ArbeitszimmerFestplatten3", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INHIBIT" => array(
                              "OID" => "35165", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "49570", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "53087", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
             	),

      	),
"Statusanzeige:Status" => array(
         "OID" => 22275, 
         "Adresse" => "0001D3C9961817:0", 
         "Name" => "Statusanzeige:Status", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "RSSI_DEVICE" => array(
                              "OID" => "33175", 
                              "Name" => "RSSI_DEVICE", 
                              "Typ" => "2",), 
                "OPERATING_VOLTAGE" => array(
                              "OID" => "50568", 
                              "Name" => "OPERATING_VOLTAGE", 
                              "Typ" => "2",), 
                "UPDATE_PENDING" => array(
                              "OID" => "22406", 
                              "Name" => "UPDATE_PENDING", 
                              "Typ" => "2",), 
                "RSSI_PEER" => array(
                              "OID" => "32056", 
                              "Name" => "RSSI_PEER", 
                              "Typ" => "2",), 
                "DUTY_CYCLE" => array(
                              "OID" => "54276", 
                              "Name" => "DUTY_CYCLE", 
                              "Typ" => "2",), 
                "CONFIG_PENDING" => array(
                              "OID" => "46566", 
                              "Name" => "CONFIG_PENDING", 
                              "Typ" => "2",), 
                "UNREACH" => array(
                              "OID" => "12814", 
                              "Name" => "UNREACH", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-4" => array(
         "OID" => 43364, 
         "Adresse" => "MEQ1084617:4", 
         "Name" => "Badezimmer-Taster-4", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INSTALL_TEST" => array(
                              "OID" => "25127", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "58395", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "21384", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "39019", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "34746", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
             	),

      	),
"Urlaubs-Schalter-Verriegeln" => array(
         "OID" => 15535, 
         "Adresse" => "IEQ0100238:3", 
         "Name" => "Urlaubs-Schalter-Verriegeln", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "48646", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "36840", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "13567", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer-Taster-An" => array(
         "OID" => 14662, 
         "Adresse" => "JEQ0004086:2", 
         "Name" => "Arbeitszimmer-Taster-An", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG" => array(
                              "OID" => "24705", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "57060", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "21675", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
             	),

      	),
"Wohnzimmer-ResetWebCam" => array(
         "OID" => 14093, 
         "Adresse" => "MEQ0192030:1", 
         "Name" => "Wohnzimmer-ResetWebCam", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "INHIBIT" => array(
                              "OID" => "14197", 
                              "Name" => "INHIBIT", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "42336", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "WORKING" => array(
                              "OID" => "10071", 
                              "Name" => "WORKING", 
                              "Typ" => "2",), 
             	),

      	),
"Aussen-Ostseite" => array(
         "OID" => 13989, 
         "Adresse" => "IEQ0206685:1", 
         "Name" => "Aussen-Ostseite", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "HUMIDITY" => array(
                              "OID" => "22550", 
                              "Name" => "HUMIDITY", 
                              "Typ" => "2",), 
                "TEMPERATURE" => array(
                              "OID" => "16433", 
                              "Name" => "TEMPERATURE", 
                              "Typ" => "2",), 
             	),

      	),
"Badezimmer-Taster-6" => array(
         "OID" => 34547, 
         "Adresse" => "MEQ1084617:6", 
         "Name" => "Badezimmer-Taster-6", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PRESS_LONG_RELEASE" => array(
                              "OID" => "15006", 
                              "Name" => "PRESS_LONG_RELEASE", 
                              "Typ" => "2",), 
                "PRESS_LONG" => array(
                              "OID" => "55035", 
                              "Name" => "PRESS_LONG", 
                              "Typ" => "2",), 
                "PRESS_SHORT" => array(
                              "OID" => "28998", 
                              "Name" => "PRESS_SHORT", 
                              "Typ" => "2",), 
                "INSTALL_TEST" => array(
                              "OID" => "33242", 
                              "Name" => "INSTALL_TEST", 
                              "Typ" => "2",), 
                "PRESS_CONT" => array(
                              "OID" => "51237", 
                              "Name" => "PRESS_CONT", 
                              "Typ" => "2",), 
             	),

      	),
"Statusanzeige:Energie" => array(
         "OID" => 52272, 
         "Adresse" => "0001D3C9961817:6", 
         "Name" => "Statusanzeige:Energie", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "CURRENT" => array(
                              "OID" => "38087", 
                              "Name" => "CURRENT", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER" => array(
                              "OID" => "38232", 
                              "Name" => "ENERGY_COUNTER", 
                              "Typ" => "2",), 
                "FREQUENCY" => array(
                              "OID" => "50711", 
                              "Name" => "FREQUENCY", 
                              "Typ" => "2",), 
                "POWER" => array(
                              "OID" => "22720", 
                              "Name" => "POWER", 
                              "Typ" => "2",), 
                "VOLTAGE" => array(
                              "OID" => "51461", 
                              "Name" => "VOLTAGE", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER_OVERFLOW" => array(
                              "OID" => "17437", 
                              "Name" => "ENERGY_COUNTER_OVERFLOW", 
                              "Typ" => "2",), 
             	),

      	),
"Arbeitszimmer-Netzwerk-Leistungswerte" => array(
         "OID" => 30642, 
         "Adresse" => "LEQ1346339:2", 
         "Name" => "Arbeitszimmer-Netzwerk-Leistungswerte", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "POWER" => array(
                              "OID" => "20347", 
                              "Name" => "POWER", 
                              "Typ" => "2",), 
                "BOOT" => array(
                              "OID" => "35214", 
                              "Name" => "BOOT", 
                              "Typ" => "2",), 
                "CURRENT" => array(
                              "OID" => "54265", 
                              "Name" => "CURRENT", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER" => array(
                              "OID" => "36433", 
                              "Name" => "ENERGY_COUNTER", 
                              "Typ" => "2",), 
                "FREQUENCY" => array(
                              "OID" => "11825", 
                              "Name" => "FREQUENCY", 
                              "Typ" => "2",), 
                "VOLTAGE" => array(
                              "OID" => "12599", 
                              "Name" => "VOLTAGE", 
                              "Typ" => "2",), 
             	),

      	),
"IPSchalter:4" => array(
         "OID" => 52598, 
         "Adresse" => "0001D3C98DD615:4", 
         "Name" => "IPSchalter:4", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PROCESS" => array(
                              "OID" => "35181", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "51106", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "50064", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
             	),

      	),
"IPSchalter:5" => array(
         "OID" => 54617, 
         "Adresse" => "0001D3C98DD615:5", 
         "Name" => "IPSchalter:5", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "17026", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "11780", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
                "PROCESS" => array(
                              "OID" => "43758", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
             	),

      	),
"IPSchalter:2" => array(
         "OID" => 26967, 
         "Adresse" => "0001D3C98DD615:2", 
         "Name" => "IPSchalter:2", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "PROCESS" => array(
                              "OID" => "16327", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "23359", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
                "STATE" => array(
                              "OID" => "23358", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
             	),

      	),
"WohnzimmerEffektlicht:Status" => array(
         "OID" => 47310, 
         "Adresse" => "0001D3C98DD615:0", 
         "Name" => "WohnzimmerEffektlicht:Status", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "OPERATING_VOLTAGE" => array(
                              "OID" => "51103", 
                              "Name" => "OPERATING_VOLTAGE", 
                              "Typ" => "2",), 
                "RSSI_DEVICE" => array(
                              "OID" => "54859", 
                              "Name" => "RSSI_DEVICE", 
                              "Typ" => "2",), 
                "CONFIG_PENDING" => array(
                              "OID" => "33735", 
                              "Name" => "CONFIG_PENDING", 
                              "Typ" => "2",), 
                "UNREACH" => array(
                              "OID" => "12315", 
                              "Name" => "UNREACH", 
                              "Typ" => "2",), 
                "RSSI_PEER" => array(
                              "OID" => "33127", 
                              "Name" => "RSSI_PEER", 
                              "Typ" => "2",), 
                "UPDATE_PENDING" => array(
                              "OID" => "12923", 
                              "Name" => "UPDATE_PENDING", 
                              "Typ" => "2",), 
                "DUTY_CYCLE" => array(
                              "OID" => "31450", 
                              "Name" => "DUTY_CYCLE", 
                              "Typ" => "2",), 
             	),

      	),
"Statusanzeige:Schalter" => array(
         "OID" => 25056, 
         "Adresse" => "0001D3C9961817:3", 
         "Name" => "Statusanzeige:Schalter", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "STATE" => array(
                              "OID" => "22772", 
                              "Name" => "STATE", 
                              "Typ" => "2",), 
                "SECTION" => array(
                              "OID" => "29735", 
                              "Name" => "SECTION", 
                              "Typ" => "2",), 
                "PROCESS" => array(
                              "OID" => "51851", 
                              "Name" => "PROCESS", 
                              "Typ" => "2",), 
             	),

      	),
"EsstischEffektlicht:Energie" => array(
         "OID" => 14996, 
         "Adresse" => "0001D3C996136E:6", 
         "Name" => "EsstischEffektlicht:Energie", 
         "CCU" => "Homematic-CCU", 
         "COID" => array(
                "FREQUENCY" => array(
                              "OID" => "58528", 
                              "Name" => "FREQUENCY", 
                              "Typ" => "2",), 
                "CURRENT" => array(
                              "OID" => "12703", 
                              "Name" => "CURRENT", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER" => array(
                              "OID" => "57575", 
                              "Name" => "ENERGY_COUNTER", 
                              "Typ" => "2",), 
                "ENERGY_COUNTER_OVERFLOW" => array(
                              "OID" => "56764", 
                              "Name" => "ENERGY_COUNTER_OVERFLOW", 
                              "Typ" => "2",), 
                "POWER" => array(
                              "OID" => "17824", 
                              "Name" => "POWER", 
                              "Typ" => "2",), 
                "VOLTAGE" => array(
                              "OID" => "37392", 
                              "Name" => "VOLTAGE", 
                              "Typ" => "2",), 
             	),

      	),
);}

?>