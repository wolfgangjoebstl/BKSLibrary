<?php

/*erstellt von RemoteAccess::add_Amis() am 21.02.2025 05:05
 */
function AmisStromverbrauchList() { return array(
    "Wohnung-KHG07_Wirkenergie" => array(
         "OID" => 50084, 
         "Name" => "Wohnung-KHG07_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "100", 
             	),
    "Wohnung-KHG07_Wirkleistung" => array(
         "OID" => 49190, 
         "Name" => "Wohnung-KHG07_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "101", 
             	),
    "Arbeitszimmer_Wirkenergie" => array(
         "OID" => 50765, 
         "Name" => "Arbeitszimmer_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "102", 
             	),
    "Arbeitszimmer_Wirkleistung" => array(
         "OID" => 53915, 
         "Name" => "Arbeitszimmer_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "103", 
             	),
    "WohnzimmerMedia_Wirkenergie" => array(
         "OID" => 23217, 
         "Name" => "WohnzimmerMedia_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "104", 
             	),
    "WohnzimmerMedia_Wirkleistung" => array(
         "OID" => 33700, 
         "Name" => "WohnzimmerMedia_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "105", 
             	),

      );}

/*erstellt von RemoteAccess::add_SysInfo() am 21.02.2025 05:05
 */
function SysInfoList() { return array(
    "Hostname" => array(
         "OID" => 59200, 
         "Name" => "Hostname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "200", 
             	),
    "Betriebssystemname" => array(
         "OID" => 37812, 
         "Name" => "Betriebssystemname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "201", 
             	),
    "Betriebssystemversion" => array(
         "OID" => 33638, 
         "Name" => "Betriebssystemversion", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "202", 
             	),
    "Hotfix" => array(
         "OID" => 29643, 
         "Name" => "Hotfix", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "203", 
             	),
    "ExternalIP" => array(
         "OID" => 42460, 
         "Name" => "ExternalIP", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "204", 
             	),
    "IPS_UpTime" => array(
         "OID" => 32406, 
         "Name" => "IPS_UpTime", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "205", 
             	),
    "IPS_Version" => array(
         "OID" => 37794, 
         "Name" => "IPS_Version", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "206", 
             	),

      );}

/*erstellt von RemoteAccess::add_RemoteServer() am 21.02.2025 05:05
 */
function ROID_List() { return array(
    "LBG70-2Virt" => array(
         "Adresse" => "http://wolfgangjoebstl@yahoo.com:Cloudg0606@100.66.204.72:3777/api/", 
         "ServerName" => "19087", 
         "Temperatur" => "56092",        // from xconfig
         "Switch" => "37180",        // from xconfig
         "Kontakt" => "26811",        // from xconfig
         "Taster" => "16024",        // from xconfig
         "Bewegung" => "15211",        // from xconfig
         "HeatControl" => "50496",        // from xconfig
         "Feuchtigkeit" => "20916",        // from xconfig
         "SysInfo" => "11074",        // from xconfig
         "Klima" => "33991",        // from xconfig
         "Helligkeit" => "33246",        // from xconfig
         "Stromverbrauch" => "15259",        // from xconfig
         "Andere" => "59000",        // from xconfig
         "ArchiveHandler" => "27926", 
             	),
      );}

?>