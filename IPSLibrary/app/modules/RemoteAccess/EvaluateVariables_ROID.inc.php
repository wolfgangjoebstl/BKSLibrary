<?php
/*erstellt von RemoteAccess::add_Guthabensteuerung() am 09.09.2025 18:35
 */
function GuthabensteuerungList() { return array(
    "Phone_Cost" => array(
         "OID" => 17479, 
         "Name" => "Phone_Cost", 
         "Typ" => 2, 
         "Order" => "500", 
             	),
    "StartImacro" => array(
         "OID" => 14828, 
         "Name" => "StartImacro", 
         "Typ" => 1, 
         "Order" => "501", 
             	),
    "Phone_Load" => array(
         "OID" => 15501, 
         "Name" => "Phone_Load", 
         "Typ" => 2, 
         "Order" => "502", 
             	),
    "ScriptTimer" => array(
         "OID" => 28552, 
         "Name" => "ScriptTimer", 
         "Typ" => 3, 
         "Order" => "503", 
             	),
    "Phone_CL_Change" => array(
         "OID" => 22337, 
         "Name" => "Phone_CL_Change", 
         "Typ" => 2, 
         "Order" => "504", 
             	),
    "checkScriptCounter" => array(
         "OID" => 31530, 
         "Name" => "checkScriptCounter", 
         "Typ" => 1, 
         "Order" => "505", 
             	),
    "ScriptCounter" => array(
         "OID" => 40928, 
         "Name" => "ScriptCounter", 
         "Typ" => 1, 
         "Order" => "506", 
             	),
    "StatusWebread" => array(
         "OID" => 43116, 
         "Name" => "StatusWebread", 
         "Typ" => 3, 
         "Order" => "507", 
             	),

      );}

/*erstellt von RemoteAccess::add_Amis() am 09.09.2025 18:35
 */
function AmisStromverbrauchList() { return array(
    "Wohnung-LBG70_Wirkenergie" => array(
         "OID" => 46646, 
         "Name" => "Wohnung-LBG70_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "100", 
             	),
    "Wohnung-LBG70_Wirkleistung" => array(
         "OID" => 35207, 
         "Name" => "Wohnung-LBG70_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "101", 
             	),
    "Arbeitszimmer_Wirkenergie" => array(
         "OID" => 27977, 
         "Name" => "Arbeitszimmer_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "102", 
             	),
    "Arbeitszimmer_Wirkleistung" => array(
         "OID" => 29750, 
         "Name" => "Arbeitszimmer_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "103", 
             	),
    "ArbeitszimmerLedLicht_Wirkenergie" => array(
         "OID" => 35979, 
         "Name" => "ArbeitszimmerLedLicht_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "104", 
             	),
    "ArbeitszimmerLedLicht_Wirkleistung" => array(
         "OID" => 50645, 
         "Name" => "ArbeitszimmerLedLicht_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "105", 
             	),
    "ArbeitszimmerMedia_Wirkenergie" => array(
         "OID" => 32005, 
         "Name" => "ArbeitszimmerMedia_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "106", 
             	),
    "ArbeitszimmerMedia_Wirkleistung" => array(
         "OID" => 53651, 
         "Name" => "ArbeitszimmerMedia_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "107", 
             	),
    "ArbeitszimmerUSVSynologies_Wirkenergie" => array(
         "OID" => 12905, 
         "Name" => "ArbeitszimmerUSVSynologies_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "108", 
             	),
    "ArbeitszimmerUSVSynologies_Wirkleistung" => array(
         "OID" => 53988, 
         "Name" => "ArbeitszimmerUSVSynologies_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "109", 
             	),
    "ArbeitszimmerLadestation_Wirkenergie" => array(
         "OID" => 10931, 
         "Name" => "ArbeitszimmerLadestation_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "110", 
             	),
    "ArbeitszimmerLadestation_Wirkleistung" => array(
         "OID" => 30720, 
         "Name" => "ArbeitszimmerLadestation_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "111", 
             	),
    "Gaestezimmer_Wirkenergie" => array(
         "OID" => 39648, 
         "Name" => "Gaestezimmer_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "112", 
             	),
    "Gaestezimmer_Wirkleistung" => array(
         "OID" => 55215, 
         "Name" => "Gaestezimmer_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "113", 
             	),
    "GaestezimmerLampe_Wirkenergie" => array(
         "OID" => 29554, 
         "Name" => "GaestezimmerLampe_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "114", 
             	),
    "GaestezimmerLampe_Wirkleistung" => array(
         "OID" => 43618, 
         "Name" => "GaestezimmerLampe_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "115", 
             	),
    "Kueche_Wirkenergie" => array(
         "OID" => 14873, 
         "Name" => "Kueche_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "116", 
             	),
    "Kueche_Wirkleistung" => array(
         "OID" => 31087, 
         "Name" => "Kueche_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "117", 
             	),
    "Weinkuehler_Wirkenergie" => array(
         "OID" => 40334, 
         "Name" => "Weinkuehler_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "118", 
             	),
    "Weinkuehler_Wirkleistung" => array(
         "OID" => 21004, 
         "Name" => "Weinkuehler_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "119", 
             	),
    "Wohnzimmer_Wirkenergie" => array(
         "OID" => 20807, 
         "Name" => "Wohnzimmer_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "120", 
             	),
    "Wohnzimmer_Wirkleistung" => array(
         "OID" => 56504, 
         "Name" => "Wohnzimmer_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "121", 
             	),
    "Wohnzimmer-MeshedRouter_Wirkenergie" => array(
         "OID" => 57417, 
         "Name" => "Wohnzimmer-MeshedRouter_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "122", 
             	),
    "Wohnzimmer-MeshedRouter_Wirkleistung" => array(
         "OID" => 30415, 
         "Name" => "Wohnzimmer-MeshedRouter_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "123", 
             	),
    "Statusanzeige_Wirkenergie" => array(
         "OID" => 22647, 
         "Name" => "Statusanzeige_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "124", 
             	),
    "Statusanzeige_Wirkleistung" => array(
         "OID" => 47717, 
         "Name" => "Statusanzeige_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "125", 
             	),

      );}

/*erstellt von RemoteAccess::add_SysInfo() am 09.09.2025 18:35
 */
function SysInfoList() { return array(
    "Hostname" => array(
         "OID" => 54221, 
         "Name" => "Hostname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "200", 
             	),
    "Betriebssystemname" => array(
         "OID" => 52163, 
         "Name" => "Betriebssystemname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "201", 
             	),
    "Betriebssystemversion" => array(
         "OID" => 33438, 
         "Name" => "Betriebssystemversion", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "202", 
             	),
    "Hotfix" => array(
         "OID" => 29291, 
         "Name" => "Hotfix", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "203", 
             	),
    "ExternalIP" => array(
         "OID" => 38967, 
         "Name" => "ExternalIP", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "204", 
             	),
    "IPS_UpTime" => array(
         "OID" => 50802, 
         "Name" => "IPS_UpTime", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "205", 
             	),
    "IPS_Version" => array(
         "OID" => 47824, 
         "Name" => "IPS_Version", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "206", 
             	),

      );}

/*erstellt von RemoteAccess::add_RemoteServer() am 09.09.2025 18:36
 */
function ROID_List() { return array(
      );}

?>