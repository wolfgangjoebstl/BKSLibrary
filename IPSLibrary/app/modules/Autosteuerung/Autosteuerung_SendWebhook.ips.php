<?php

/* einen Webhook weitersenden
 * Geofency laesst keinen Zugriff auf den eigentlichen Webhook zu, es mit den Variablen versuchen
 * Ein Event auf die Veränderung von Timestamp setzen, die Geofency Daten auswerten und an die anderen Server weitersenden
 * Grundsaetzliches davon bereits in Autosteuerung vorhanden
 *
 * Autosteuerung um Send und GetWebhook erweitern
 *
 */



    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
	IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung");

    echo "Autosteuerung, initiate class AutosteuerungOperator:\n";
    $operate=new AutosteuerungOperator();
    echo "get Geofency Informationen:\n";
    $geofencies=$operate->getGeofencyInformation(true);
    print_R($geofencies);

    //RemoteAccess_GetServerConfig
    $remServer=RemoteAccessServerTable();
    print_R($remServer);

    $variableId = 26208;

    echo "Analyse archivierte Werte :\n";
    $archiveOps = new archiveOps();
    $config=array();
    $config["StartTime"]="14.2.2025";                // relatives Datum
    $configCleanUpData = array();
    $configCleanUpData["range"] = ["max" => 60, "min" => 0,];
    $config["SuppressZero"]=false;                                      // 0 nicht unterdrücken, mit Range wenn erforderlich eliminieren
    $configCleanUpData["deleteSourceOnError"]=true;                     // sonst bringt der Range nix, es werden keine Werte entfernt
    $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
    //$config["ShowTable"]["align"]="minutely";    
    $config["CleanUpData"] = $configCleanUpData;    

    if ($variableId) $result = $archiveOps->getValues($variableId,$config,2);          // true,2 Debug, Werte einlesen, 0/false löschen, ist immer 5 min nach der 1
    $config["CleanUpData"] = false;                                                 // nix bereinigen
    if ($variable2Id) $result = $archiveOps->getValues($variable2Id,$config,2);          // true,2 Debug, Werte einlesen
        $config["StartTime"]="5.2.2025";
    if ($variable3Id) $result = $archiveOps->getValues($variable3Id,$config,2);          // true,2 Debug, Werte einlesen
    if ($variable4Id) $result = $archiveOps->getValues($variable4Id,$config,2);          // true,2 Debug, Werte einlesen, Arbeitszimmer hilft jetzt nicht
    echo "-----------------------------------------------\n";
    $result = $archiveOps->showValues(false,$config);                   // true Debug


    //IPSLogger_Wrn(__file__, "Webhook Send");

