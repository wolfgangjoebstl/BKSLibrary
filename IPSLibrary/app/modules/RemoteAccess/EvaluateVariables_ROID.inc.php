<?

function AmisStromverbrauchList() { return array(
"Arbeitszimmer_Wirkenergie" => array(
         "OID" => 51276, 
         "Name" => "Arbeitszimmer_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "", 
         "Order"    => "100", 
             	),
"Arbeitszimmer_Wirkleistung" => array(
         "OID" => 13997, 
         "Name" => "Arbeitszimmer_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "101", 
             	),
"Arbeitszimmer_StromL1" => array(
         "OID" => 52526, 
         "Name" => "Arbeitszimmer_StromL1", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "102", 
             	),
"Arbeitszimmer_StromL2" => array(
         "OID" => 52215, 
         "Name" => "Arbeitszimmer_StromL2", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "103", 
             	),
"Arbeitszimmer_StromL3" => array(
         "OID" => 10313, 
         "Name" => "Arbeitszimmer_StromL3", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "104", 
             	),

      );}

function SysInfoList() { return array(
"Hostname" => array(
         "OID" => 55722, 
         "Name" => "Hostname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "200", 
             	),
"Betriebssystemname" => array(
         "OID" => 49086, 
         "Name" => "Betriebssystemname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "201", 
             	),
"Betriebssystemversion" => array(
         "OID" => 22712, 
         "Name" => "Betriebssystemversion", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "202", 
             	),
"Hotfix" => array(
         "OID" => 49121, 
         "Name" => "Hotfix", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "203", 
             	),
"ExternalIP" => array(
         "OID" => 29427, 
         "Name" => "ExternalIP", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "204", 
             	),
"IPS_UpTime" => array(
         "OID" => 55385, 
         "Name" => "IPS_UpTime", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "205", 
             	),
"IPS_Version" => array(
         "OID" => 30730, 
         "Name" => "IPS_Version", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "206", 
             	),

      );}

function ROID_List() { return array(
"LBG70-2Virt" => array(
         "Adresse" => "http://wolfgangjoebstl@yahoo.com:Cloudg06@hupo35.ddns-instar.de:3876/api/", 
         "VisRootID" => "35687", 
         "WebFront" => "36315", 
         "Administrator" => "30569", 
         "RemoteAccess" => "43362", 
         "ServerName" => "20896", 
         "Temperatur" => "24158", 
         "Schalter" => "52743", 
         "Kontakte" => "39385", 
         "Taster" => "55952", 
         "Bewegung" => "46634", 
         "Humidity" => "57710", 
         "SysInfo" => "16174", 
         "Andere" => "25841", 
         "ArchiveHandler" => "27926", 
             	),
      );}

?>