<?

function AmisStromverbrauchList() { return array(
"BKS01_Wirkenergie" => array(
         "OID" => 20157, 
         "Name" => "BKS01_Wirkenergie", 
         "Typ"      => 2, 
         "Profile"  => "~Electricity", 
         "Order"    => "100", 
             	),
"BKS01_Wirkleistung" => array(
         "OID" => 41930, 
         "Name" => "BKS01_Wirkleistung", 
         "Typ"      => 2, 
         "Profile"  => "~Power", 
         "Order"    => "101", 
             	),
"BKS01_StromL1" => array(
         "OID" => 40931, 
         "Name" => "BKS01_StromL1", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "102", 
             	),
"BKS01_StromL2" => array(
         "OID" => 28017, 
         "Name" => "BKS01_StromL2", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "103", 
             	),
"BKS01_StromL3" => array(
         "OID" => 14761, 
         "Name" => "BKS01_StromL3", 
         "Typ"      => 2, 
         "Profile"  => "~Ampere", 
         "Order"    => "104", 
             	),

      );}

function SysInfoList() { return array(
"Hostname" => array(
         "OID" => 15525, 
         "Name" => "Hostname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "200", 
             	),
"Betriebssystemname" => array(
         "OID" => 42885, 
         "Name" => "Betriebssystemname", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "201", 
             	),
"Betriebssystemversion" => array(
         "OID" => 24817, 
         "Name" => "Betriebssystemversion", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "202", 
             	),
"Hotfix" => array(
         "OID" => 13963, 
         "Name" => "Hotfix", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "203", 
             	),
"ExternalIP" => array(
         "OID" => 10165, 
         "Name" => "ExternalIP", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "204", 
             	),
"IPS_UpTime" => array(
         "OID" => 10309, 
         "Name" => "IPS_UpTime", 
         "Typ"      => 3, 
         "Profile"  => "", 
         "Order"    => "205", 
             	),
"IPS_Version" => array(
         "OID" => 22576, 
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
         "ServerName" => "10095", 
         "Temperatur" => "36141", 
         "Schalter" => "19094", 
         "Kontakte" => "47607", 
         "Taster" => "19095", 
         "Bewegung" => "37148", 
         "HeatControl" => "47610", 
         "HeatSet" => "10097", 
         "Humidity" => "36145", 
         "SysInfo" => "19097", 
         "Andere" => "32174", 
         "ArchiveHandler" => "27926", 
             	),
"BKS-VIS" => array(
         "Adresse" => "http://wolfgangjoebstl@yahoo.com:cloudg06@sina73.ddns-instar.com:3778/api/", 
         "VisRootID" => "39666", 
         "WebFront" => "30860", 
         "Administrator" => "42703", 
         "RemoteAccess" => "51433", 
         "ServerName" => "10082", 
         "Temperatur" => "36270", 
         "Schalter" => "19165", 
         "Kontakte" => "47737", 
         "Taster" => "37267", 
         "Bewegung" => "10083", 
         "HeatControl" => "36275", 
         "HeatSet" => "32270", 
         "Humidity" => "26223", 
         "SysInfo" => "51396", 
         "Andere" => "48394", 
         "ArchiveHandler" => "45584", 
             	),
      );}

?>