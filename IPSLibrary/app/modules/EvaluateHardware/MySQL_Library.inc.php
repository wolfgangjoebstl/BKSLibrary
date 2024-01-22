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

/* MySQL Library
 *
 *	summary of classes, a few functions are at the end of this script
 *
 *
 *      sqlOperate      extends sqlHandle
 *
 *      sql_auditTrail          extends sqlOperate
 *      sql_componentModules    extends sqlOperate
 *      sql_serverGateways      extends sqlOperate
 *      sql_topologies          extends sqlOperate
 *      sql_deviceList          extends sqlOperate
 *      sql_instances           extends sqlOperate
 *      sql_channels            extends sqlOperate
 *      sql_registers           extends sqlOperate
 *      sql_valuesOnRegs        extends sqlOperate
 * 
 *      sqlHandle
 *      sqlReturn
 *
 * verschiedene functions:
 *      getfromDatabase
 *      ...........     
 */

    IPSUtils_Include ("EvaluateHardware_Configuration.inc.php","IPSLibrary::config::modules::EvaluateHardware");
    IPSUtils_Include ("MySQL_Configuration.inc.php","IPSLibrary::app::modules::EvaluateHardware");


/******************************************************************************************************/

/* sqlOperate extends sqlHandle
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 *      kein eigenes __construct
 *      syncTableConfig             ruft die beiden nächsten functions auf um eine Datenbank Strutur aufzubauen
 *          updateTableConfig
 *          createTableConfig
 *      updateTableEntriesValues
 *      getFullSelectforShow
 *      compareSizeArrayTable
 */

class sqlOperate extends sqlHandle
    {

    /* same as in class sqlHandle
    public function getDatabaseConfig()
        {
        return $this->getDatabaseConfiguration();   
        }
    */

    /* syncTableConfig
     * vergleicht die SOLL Konfiguration in MySQL_Configuration (getDatabaseConfig) mit der aus der Datenbank ausgelesenen IST Konfiguration
     * Unterschiede werden identifiziert und wenn relevant korrigiert, die SOLL Konfiguration durchgehen
     *
     * erster Parameter kann false sein - dann werden alle Tabellen verglichen oder den Namen ener bestimmten tabelle annehmen
     *
     * Voraussetzung USE database, holt sich mit SHOW TABLES die IST Datenbank Konfiguration für die angelegten Tabellen
     *
     * Wenn Tabelle vorhanden, dann anhand DESCRIBE TABLE die columns herausfinden und die Änderungen identifizieren 
     * Mit der Funktion updateTableConfig wird der Tabellenname, die Istconfig und die Soll Config übergeben
     *
     * Sonst wenn Tabelle nicht vorhanden CREATE mit createTableConfig
     *
     * Zeitvertzögerungen im Durchlauf handelt manb sich bei Konfigurationsunterschieden der Tabelle in. Sowas verzögert die exec time gewaltig, ist wie ein Fehlerfall einzuordnen.
     *
     */

    public function syncTableConfig($table=false,$debug=false)
        {
        $showTime=false;
        if ($table===false) $tablecomp=false;              
        else $tablecomp=true;                               // nur einen bestimmten table vergleichen
        if ($debug>100) 
            {
            $showTime=true;
            $startexec=$debug;
            $debug=false;
            echo "syncTableConfig($table with output of exec time.\n";
            }
        $config=$this->getDatabaseConfig();
        $tables = $this->showTables();     // SHOW TABLES
        echo "Sync database Configuration with MariaDB Configuration:\n";           // config with tables
        echo "   ".str_pad("Tabelle",30).str_pad("Status",20)."\n";
        foreach ($config as $tableName => $entries)         // entries ist der SOLL Zustand
            {
            if ( ($tablecomp===false) || (($tablecomp) && ($table==$tableName)) )
                {
                if ($showTime) echo "--------------------------------------> Exectime Database Handling: ".exectime($startexec)." Seconds.\n";
                echo "   ".str_pad($tableName,30);
                if (isset($tables[$tableName]))         // Datenbank gibt es bereits, stimmen die Spaltenbezeichnungen
                    {
                    $columns=$this->describeTable($tableName);      // IST Zustand
                    echo "available, compare columns ";
                    $this->updateTableConfig($tableName, $columns, $entries, $debug);       // Tabelle tablename wird von config in columns auf config in entries upgedated
                    }
                else                                    // neue Tabellen entsprechend config anlegen
                    {
                    $configSet=$config[$tableName];
                    echo "not available, create table and columns.\n";          // hier kommt nix mehr in der selben Zeile 
                    $this->createTableConfig($tableName, $entries);
                    }   // ende Tabelle noch nicht vorhanden
                echo "\n";
                }
            }
        }

    /* updateTableConfig, vergleicht IST mit SOLL Zustand aus Config
     *  Voraussetzung USE database
     *  Parameter:
     *      tableName       Tabelle tableName in der database
     *      columns         IST : Spalten aus DESCRIBE tableName
     *      configSet       SOLL: gewünschte Konfiguration, ähnlich der die ausgegeben wird
     *
     * Die Ist Config Spalte für Spalte durchgehen und einmal zur Info ausgeben, makeTableConfig ermittelt den IST Zustand für eine Spalte
     * Dann die SOLL Configuration durchgehen, wieder makeTableConfig verwenden, ohne Parameter CREATE ???
     * Wenn die Spalte fehlt ist der Status gleich einmal false
     * sonst compareTableConfig(SOLL,IST) verwenden, diese Routine entscheidet
     *
     * Wenn Unterschied Tabelle anpassen
     *  ALTER TABLE $tableName
     *
     *
     */

    public function updateTableConfig($tableName, $columns, $configSet, $debug=false)
        {
        if ($debug) 
            {
            echo "Configurationen der Tabellen $tableName vergleichen:\n";
            echo "      Check Anzahl der Spalten : Ist : ".sizeof($columns)." Soll : ".sizeof($configSet)."\n";
            }
        else echo "(Ist : ".sizeof($columns)." Soll : ".sizeof($configSet).")\n";

        /* check oberflächlich die Anzahl der Spalten auf zuviel oder zuwenig, nur Fehlermeldung wenn eine Spalte manuell gelöscht werden sollte */
        if ($debug) 
             {
             echo "      ".str_pad("Spalte",30).str_pad("Status Ist Config",20)."\n";
             //print_r($columns);
             }
        foreach ($columns as $name => $column)      // IST Zustand durchgehen
            {
            if ($debug) echo "      ".str_pad($column["Field"],30);
            if (isset($configSet[$column["Field"]]))         // Spaltenbezeichnung gibt es bereits, stimmen die Konfigurationen
                {
                $result = $this->makeTableConfig($column,"CREATE");          // default ist ALTER parameterset ohne den Keys Inline
                if ($debug) echo "available (".$result[$name].")";
                }
            else                                    // neue Datenbank entsprechend config anlegen
                {
                if ($debug) echo "Fehler, not in Configuration. Delete manually if appropriate.\n";
                else echo "     ".$column["Field"].": Fehler, not in Configuration. Delete manually if appropriate.\n";
                }
            if ($debug) echo "\n";
            }
        //print_r($configSet);
        if ($debug) 
            {
            echo "              ------------------------\n";
            echo "      ".str_pad("Spalte",30).str_pad("Status Soll Config",20)."\n";
            }

        /* check die einzelnen Eintraege der SollConfig Spalte für Spalte */
        foreach ($configSet as $name => $column)                                // SOLL Zustand durchgehen
            {
            echo "      ".str_pad($column["Field"],30);
            $result = $this->makeTableConfig($column);
            if (isset($columns[$name])===false)                      // IST Zustand vergleichen
                {
                echo "Spalte $name in der Tabelle unbekannt.\n";
                //print_r($columns);
                $same["Status"]=false;
                }
            else $same = $this->compareTableConfig($column,$columns[$name],false);  // same ist array mit Status, Command, function vergleicht column soll mit column ist
            echo json_encode($same)." ";
            if ($same["Status"]==false)         // Primary Key wird nicht automatisch geändert aber bei neuen Tabellen richtig angelegt 
                {
                //if ($debug) $this->compareTableConfig($column,$columns[$name],true);            // noch einmal mit DEBUG true, kanns nicht glauben
                $sqlCommand="";
                if (isset($result[$name])) 
                    {
                    $sqlCommand.= $result[$name];
                    if (isset($columns[$column["Field"]]))         // Spaltenbezeichnung gibt es bereits, stimmen die Konfigurationen
                        {
                        if (isset($same["Command"]))        // manchmal ist ein zusatzbefehl notwendig um den table abzuändern.
                            {
                            $sqlCommand1 = "ALTER TABLE $tableName ".$same["Command"].";";
                            echo "Zusatzbefehl:      \"$sqlCommand1\"\n";
                            $result=$this->command($sqlCommand1);                        
                            print_r($result);   
                            }
                        //if ($result["KEY"] != "") $sqlCommand = "ALTER TABLE $tableName MODIFY COLUMN ".$sqlCommand.", ".$result["KEY"].";"; else 
                        $sqlCommand = "ALTER TABLE $tableName MODIFY COLUMN ".$sqlCommand.";";
                        //$sqlCommand = "ALTER TABLE $tableName ALTER COLUMN ".$sqlCommand.";";
                        echo "Änderungsbefehl:  \"$sqlCommand\"";
                        $result=$this->command($sqlCommand);
                        print_r($result);
                        }
                    else                                    // neue Datenbank entsprechend config anlegen
                        {
                        if ($result["KEY"] != "") $sqlCommand = "ALTER TABLE $tableName ADD ".$sqlCommand.", ".$result["KEY"].";";
                        else $sqlCommand = "ALTER TABLE $tableName ADD ".$sqlCommand.";";                    
                        echo "NeuAnlegenBefehl:  \"$sqlCommand\"\n";
                        $result=$this->command($sqlCommand);
                        }
                    }
                else echo "Fehler, $column not Field Name.\n";
                }
            else 
                {
                echo "is equal ".$result[$name];
                }
            echo "\n";
            }       // ende foreach
        echo "    ";
        if ($debug) $this->showIndex($tableName);
        }
        
    /* wenn es die Tabelle noch nicht gibt, diese erzeugen
     *
     * CREATE TABLE $tableName ... makeTableConfig($entry) 
     *
     */

    public function createTableConfig($tableName, $entries, $debug=false)
        {
        if ($debug)
            {
            echo " createTableConfig für $tableName : ";
            print_r($entries);
            echo "\n";
            }
        //echo "     CREATE TABLE $tableName ("."\n";
        $sqlCommand="CREATE TABLE $tableName (\n";
        //$sqlCommand="CREATE TABLE deviceList (deviceID  INT UNSIGNED   NOT NULL, PRIMARY KEY (deviceID)  );";
        /* die einzelnen Spalten anlegen */
        $primaryKey=""; $primaryKeyMariaDB="";
        $next=false;
        foreach ($entries as $column => $entry)
            {
            if ($next) $sqlCommand.=",\n";            // wenn neue Spalte, vorher noch ein Komma und einen Zeilenvorschub machen
            else $next=true;  
            $result = $this->makeTableConfig($entry,"CREATE",$debug);           // liefert zB     [topologyID] =>  topologyID int NOT NULL   auto_increment und [KEY] => ADD PRIMARY KEY (topologyID)
            if (isset($result[$column])) 
                {
                echo "     ".str_pad($column,30)."CREATE ".$result[$column]."\n";
                $sqlCommand.= $result[$column];        // übernimmt dann wie im Beispiel [topologyID] in den sqlCommand
                }
            else echo "Fehler, $column not Field Name.\n";
            /*
            $type = "varchar(255);"; $noNull = ""; $unique=""; $extra="";
            if (isset($entry["Field"]) )
                {
                $name = $entry["Field"];
                if ($name != $column) echo "Fehler Spaltenbezeichnung unterschiedlich zum Index !\n";
                }
            if (isset($entry["Type"])) $type = $entry["Type"];
            if ( (isset($entry["Null"])) && (strtoupper($entry["Null"])=="NO") ) $noNull = "NOT NULL";
            if (isset($entry["Key"]))
                {
                if (strtoupper($entry["Key"])=="PRI") 
                    {
                    $primaryKey="PRIMARY KEY ($name),";
                    $primaryKeyMariaDB="PRIMARY KEY";
                    }
                if (strtoupper($entry["Key"])=="UNI") $unique="UNIQUE";   
                }
            if (isset($entry["Type"])) $extra = $entry["Extra"];
            //echo "          $name $type $noNull $unique $extra,"."\n";
            //$sqlCommand.="          $name $type $noNull $unique $extra";
            $sqlCommand.="          $name $type $noNull $primaryKeyMariaDB $unique $extra";
            $primaryKeyMariaDB="";
            */
            }
        //echo "          $primaryKey"."\n";
        //echo "     );"."\n";
        //$sqlCommand.="$primaryKey);";
        $sqlCommand.="\n      );";
        $result=$this->command($sqlCommand);            
        return $result;
        }


    /* sqlOperate, Update Werte der Tabelle, wird von den übergeordneten Klassen aufgerufen.
     * es wird jeweils nur eine Zeile upgedated, die Werte im Array für die in advise[key] gespeicherten Spalten werden wenn erforderlich geändert
     * 
     * Übergeordnete Klassen sind jeweils einer Tabelle zugeordnet und machen den Tabellen spezifischen Teil
     * das ist die Tabellen unabhägige Funktion, die mit dem Array advise gesteuert wird 
     *
     *    $table                        zB ="deviceList", Datenbank Tabelle in die gespeichert wird
     *    deviceList                    Werte im Array mit denen die Tabelle upgedatet werden sollen
     *    advise                        Steuerung durch Erklärung welche Spalte welche Funktion hat
     *    $config                       Konfiguration dieser Tabelle, ist auch in der Class gespeichert
     *
     *    $columnValue                  wird nicht mehr übergeben, ist zB "Name" oder "deviceID,portID", ist der Key aus dem Array und die Spalte aus der Datenbank Tabelle
     *    advise array
     *        index   => "registerID" oder "*"   typischerweise der PRIMARY Key
     *        key     => "deviceID,portID,TYPEREG"   das sind die Indexes, als Keys, nur wenn diese Unterschiedlich sind wird eine neue Eintrag erzeugt, sonst geändert
     *        ident   => "";
     *        unique  => string of several entries seperated by comma
     *        change  => "Update";
     *        history => "";
     *
     *    $name = Key/Index             Wert für den Key, muss jetzt in der deviceList drinnen sein
     *
     *  Beispiele:  advise[index]="registerID", [key]=registerID für WHERE registerId=devicelist[registerID]
     *
     * Zuerst Anzahl der vorhandenen Eintraege in der Tabelle table rausfinden
     * abhängig wie advise[key] definiert ist:  empty, wert => devicelist[wert]  zB count (Name) from Table personen where Name = Wolfgang AND Surname = Joebstl
     *      SELECT COUNT(*) FROM $table;                 wenn column nicht definiert ist
     *      SELECT COUNT(*) As $column FROM $table;      wenn column als Wert definiert ist und needle default oder * ist
     *      SELECT COUNT($column) FROM $table WHERE $column='$needle';
     *      SELECT COUNT($column) FROM $table WHERE $column[0]='$needle[0]' AND|OR $column[1]='$needle[1]';
     * wenn Count 0 ist einen neuen Eintrag machen
     * nach key die anderen Filter ident und unique testen
     * wenn keine Eintraege vorhanden
     *
     */

    public function updateTableEntriesValues($table,$deviceList,$advise,$config=false,$updated=true,$debug=false)
        {
        //if ( ($debug>1) && ($table=="registers"))  echo "\nsqlOperate:updateTableEntriesValue('$table', ...), ".json_encode($advise)."\n"; 
        if ($debug>1)   echo "\nsqlOperate::updateTableEntriesValue('$table', ...), Advise : ".json_encode($advise)." expecting \"index\" \"unique\" \"key\" otherwise string set as empty\n"; 
        //print_r($deviceList);
        $text="";           // illustrates the sql command 

        /* Konfiguration rausfinden und überprüfen */
        if ($config === false) $config=$this->getDatabaseConfig()[$table];
        if (isset($advise["index"])===false)    $advise["index"] = "*";
        if (isset($advise["unique"])===false)   $advise["unique"]="";
        if (isset($advise["key"])===false)      $advise["key"]="";                       // Fehlerbehandlung weiter unten bereits vorgesehen
        if (isset($advise["ident"])===false)    $advise["ident"]=""; 

        /* mehrere uniques möglich, auspacken */
        $uniques=explode(",",$advise["unique"]);
        $countUniques=sizeof($uniques);
        $unis=array();
        $text .= "Uniques: ";
        if ( ($countUniques<1) || ($advise["unique"]=="") )           // advise[key] nicht definiert oder leer
            {
            if ($debug) echo "advise[unique] leer oder nicht definiert,";
            $text .= "null ";
            } 
        else
            {
            foreach ($uniques as $indexVal => $entry)               // Pärchen aus Unique zB WHERE Name = Value AND Parent = Value
                {
                $text .= $entry."=".$deviceList[$entry]."   ";
                $unis[$indexVal]=$deviceList[$entry];    
                }    
            }

        /* mehrere keys möglich, auspacken */
        $columnValue = $advise["key"];          
        $keys=explode(",",$columnValue);
        $countKeys=sizeof($keys);
        $vars=array();
        if ( ($countKeys<1) || ($advise["key"]=="") )           // advise[key] nicht definiert oder leer
            {
            if ($debug) echo "advise[key] leer oder nicht definiert,";
            if ( ($advise["index"]=="*") || ($advise["index"]=="") )
                {
                echo "updateTableEntriesValue: kein Index/Key definiert. Sollte ".$advise["index"]." sein.\n"; 
                return (false);
                }
            else 
                {
                //print_r($advise);
                if ($debug) echo " neuer Index: ".$advise["index"]."\n";
                $keys[0]=$advise["index"];
                } 
            } 
        //print_r($keys);
        $text .= "Keys: ";                                // die Keys der Reihe nach als Text mit ihrem Wert ausgeben und parallel dasselbe auch im array vars den passenden Wert aus der Tabellenspalte speichern
        foreach ($keys as $indexVal => $entry) 
            {
            $text .= $entry."=".$deviceList[$entry]."   ";
            $vars[$indexVal]=$deviceList[$entry];    
            }
        //print_R($keys); print_R($vars);         // 0 => key, 0 => var 
        //if ($debug>1) echo "whereStatement ".json_encode($keys)." ".json_encode($vars)."\n";    
        $sql = $this->whereStatement($keys,$vars, true, $debug);          // text ist die Auflistung der Keys und Werte, sql die Abfrage dazu, Default ist AND Verknüpfung
        //print_r($deviceList);

        /***** Anzahl der vorhandenen Eintraege in der Tabelle table für advise[key] rausfinden */
        $count = $this->countTable($table,$keys,$vars,true,$debug);           // SELECT COUNT(column) FROM deviceList WHERE column='$var'; WHERE statement wird aus keys und vars abgeleitet, column ist der erste Key, true für AND Verknüpfung
        if ($debug) echo "Command : $text, Result: gefundene Anzahl : $count.\n";

        $update=false;
        if ($count == 0)            // neuer Eintrag
            {
            if ($debug) 
                {
                echo "      updateTableEntriesValue: Eintrag in Tabelle '$table' für $text noch nicht gefunden. Neuer Eintrag:\n";
                //print_r($keys); print_r($advise); 
                print_r($deviceList);
                }
            $countIdent=0;
            if ($advise["ident"] != "")             // Filter aus [ident] = [identTgt]
                {
                if ($debug>1) { echo "Filter aus ".$advise["ident"]."\n"; print_r($deviceList[$advise["identTgt"]]); }
                //function countTable($table,$column,$needle="*",$and=true,$debug=false)
                $countIdent = $this->countTable($table,$advise["ident"],$deviceList[$advise["identTgt"]],true,$debug);           // SELECT COUNT(column) FROM table WHERE column='$var'; WHERE statement wird aus keys und vars abgeleitet, column ist der erste Key
                if ($debug>1) echo "whereStatement ".json_encode($advise["ident"])." ".json_encode($deviceList[$advise["identTgt"]])."\n"; 
                $sql = $this->whereStatement($advise["ident"],$deviceList[$advise["identTgt"]]);          // text ist die Auflistung der Keys und Werte, sql die Abfrage dazu, Default ist AND Verknüpfung
                }
            if ($countIdent==0) // create
                {
                $countUnique=0;
                if ($advise["unique"] != "")             // Filter
                    {
                    $countUnique = $this->countTable($table,$uniques,$unis,false,$debug);           // SELECT COUNT(column) FROM table WHERE column='$var'; WHERE statement wird aus keys und vars abgeleitet, column ist der erste Key, OR Statement  
                    echo "Unique Count: $countUnique \n";                  
                    }
                if ($countUnique==0)
                    {
                    echo "      CreateTableEntry Table : $table Command : $text , \n";
                    $this->createTableEntryValues($table, $keys, $vars, $deviceList, $advise, $config, $debug);

                    }
                else            // doppelten Eintrag gefunden, suchen und löschen
                    {
                    if ($debug>1) echo "doubles found with whereStatement uniques : ".json_encode($uniques)." unis : ".json_encode($unis)."\n"; 
                    $sql = $this->whereStatement($uniques,$unis,false);
                    $sqlCommand = "SELECT * FROM $table ".$sql;
                    echo " >SQL Command : $sqlCommand\n";                     
                    $result1=$this->query($sqlCommand);
                    $doubles = $result1->fetch();
                    $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !                    
                    print_r($doubles);
                    $sqlCommand = "DELETE FROM $table ".$sql;
                    echo " >SQL Command : $sqlCommand\n";    
                    $result=$this->command($sqlCommand);                                     
                    $this->createTableEntryValues($table, $keys, $vars, $deviceList, $advise, $config, true);           // mit Debug
                    }
                }
            else
                {
                echo "      updateTableEntry, Tabelle hat einen weiteren Identifier, der nur einmal in der Tabelle vorkommen darf: ".$advise["ident"]." mit Wert ".$deviceList[$advise["identTgt"]]." Kommt bereits $countIdent mal vor, update erforderlich.\n";
                $update = $this->updateTableEntryValues($table, $sql, $text, $deviceList, $advise, $config, $updated, $debug);
                }
            $update=true;
            }   
        elseif ($count > 1)         // mehrere Eintraege
            {
            if ($debug) echo "updateTableEntriesValue: Eintrag ".vars[0]." mit $count Duplicates. Delete younger ones !\n";
            $sqlCommand = "DELETE u1 FROM $table u1, $table u2 WHERE u1.deviceID > u2.deviceID AND u1.$column = u2.column;";
            echo " >SQL Command : $sqlCommand\n";    
            $result=$this->command($sqlCommand);
            }
        else        // count == 1, ein eintrag vorhanden, also Updaten
            {
            //if ($debug) echo "updateTableEntriesValue: Eintrag in Tabelle '$table' vorhanden, update machen:\n";                
            $update = $this->updateTableEntryValues($table, $sql, $text, $deviceList, $advise, $config, $updated, $debug);
            }

        $result=array();
        $result["Status"]=true;
        $sqlCommand = "SELECT ".$advise["index"]." FROM $table $sql;";
        $result1=$this->query($sqlCommand);
        $columns = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        //echo "Index $sqlCommand\n"; print_r($columns);
        if (isset($columns[0][$advise["index"]])) $result["Index"]=$columns[0][$advise["index"]];
        else $result["Status"]=false;
        $result["Update"]=$update;
        return $result;
        }

    /* für die Darstellung als Tabelle, return array */

    public function getFullSelectforShow($config)
        {
        $result=array();
        foreach ($config as $column => $entry) $result[$column]=true;
        return $result;
        }

    /* convert array of columns to list */

    public function convertColumnArraytoList($result,$table="")
        {
        $select=""; $next=false;
        foreach ($result as $column => $active)
            {
            if ($active)
                {
                if ($next) $select .= ",";
                else $next=true;
                $select .= "$table".$column; 
                }
            }
        return $select;
        }

    /* compare size of array with table 
     * generisch geschrieben, ermittelt aus dem class name den Namen des Tables, nach dem _ 
     * es werden die Einträge im Array mit den Eintraegen in der Tabelle für die benannte Spalte verglichen
     */

    public function compareSizeArrayTable($deviceList,$columnValue,$debug=false)
        {
        //$debug=true;                  // debug override
        $getClass=get_class($this);
        $table=explode("_",$getClass)[1];           // zweiter Teil des class Names ist der Name der Tabelle
        if ($debug) echo "compareSizeArrayTable,".get_class($this).": Table $table \n";
        $totalSoll=sizeof($deviceList);
        $totalIst = $this->countTable($table,$columnValue);         // keine needle, eine Spalte
        if ( ($debug) || ($totalSoll != $totalIst) ) echo "   --> Tabellengroesse, Soll : $totalSoll und Ist : $totalIst Einträge.\n";
        }

    }

/*****************************************************************************************************************
 *
 * individuelle Klassen für die Manipulation von Tabellen
 *
 * Name der Klasse muss immer xx_tablename sein, sonst funktionieren einige functions nicht
 *
 *******************************************************************************************************/

/* sql_webfrontAccess extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 *      _construct      getDataBaseConfiguration und speichere individuelle Konfiguration
 *      getDatabaseConfig
 *
 *      get_ColumnComponentModule       Register Tabelle mit Konfiguration in IPSDeviceHandler_GetComponentModules() vergleichen, nur Zeilen ohne componentModuleID ausgeben
 *      get_componentModules: Tabelle componentModules updaten
 *      syncTableValues
 *      updateEntriesValues
 *      getSelectforShow
 *
 */

class sql_webfrontAccess extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $tableName;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);          // ruft sqlHandle construct auf
        $this->useDatabase("ipsymcon"); 
        $this->tableName="webfrontAccess";
        $this->getDatabaseConfig($this->tableName);
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="webfrontAccess";

        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_webfrontAccess::updateEntriesValues
     *      values
     *
     * creates advise, default values result into
     *
     */
    public function updateEntriesValues($values, $updated=false, $debug=false)
        {
        $advise=array();  // Index deviceID, Identifier name
        //$advise["index"] = "nameOfID";                   // default is *
        $advise["key"]="nameOfID,eventName,eventDescription";
        //$advise["ident"]="";
        //$advise["change"]="Update";
        //$advise["history"]="";
        return parent::updateTableEntriesValues($this->tableName,$values,$advise,$this->configDB, $updated, $debug);
        }
    }

/* sql_auditTrail extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 *      _construct      getDataBaseConfiguration und speichere individuelle Konfiguration
 *      getDatabaseConfig
 *      get_ColumnComponentModule       Register Tabelle mit Konfiguration in IPSDeviceHandler_GetComponentModules() vergleichen, nur Zeilen ohne componentModuleID ausgeben
 *      get_componentModules: Tabelle componentModules updaten
 *      syncTableValues
 *      updateEntriesValues
 *      getSelectforShow
 *
 */

class sql_auditTrail extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $tableName;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);          // ruft sqlHandle construct auf
        $this->useDatabase("ipsymcon"); 
        $this->tableName="auditTrail";
        $this->getDatabaseConfig($this->tableName);
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="auditTrail";

        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    }

/* sql_componentModules extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 *      _construct      getDataBaseConfiguration und speichere individuelle Konfiguration
 *      getDatabaseConfig
 *      get_ColumnComponentModule       Register Tabelle mit Konfiguration in IPSDeviceHandler_GetComponentModules() vergleichen, nur Zeilen ohne componentModuleID ausgeben
 *      get_componentModules: Tabelle componentModules updaten
 *      syncTableValues
 *      updateEntriesValues
 *      getSelectforShow
 *
 */

class sql_componentModules extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $tableName;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);          // ruft sqlHandle construct auf
        $this->useDatabase("ipsymcon"); 
        $this->tableName="componentModules";
        $this->getDatabaseConfig($this->tableName);
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="componentModules";

        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /*  sql_componentModules
     *
    damit richtig funktioniert, sollten die folgenden Programmzeilen davor stehen
        
    echo "alle Registers mit korrekten Zuordnungen finden, componentID egal:\n";        // den inner join mit den componentModules weglassen um die NUL für componentModuleID zu finden 
    $sql = "SELECT registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                        registers.componentModuleID,registers.TYPEREG,registers.Configuration 
                FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                INNER JOIN topologies ON deviceList.placeID=topologies.topologyID
                $filter;";
    $result3=$sqlHandle->query($sql);
    $fetch = $result3->fetch();
    $result3->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
    echo "\n\n";
    echo "Registerabfrage ohne join auf componentModules hat ".sizeof($fetch)." Einträge/Zeilen.\n";
    $componentConfiguration=IPSDeviceHandler_GetComponentModules();

    *
    * aus der grossen Tabelle alle Register heraussuchen, bei denen keine ComponentID gespeichert ist
    */

    public function get_ColumnComponentModule($componentConfiguration,$fetch)
        {
        echo "get_ColumnComponentModule: Register Tabelle mit Konfiguration in IPSDeviceHandler_GetComponentModules() vergleichen, nur Zeilen ohne componentModuleID ausgeben:\n";
        $columnComponentModule=array();
        foreach ($fetch as $singleRow)
            {
            $component=false; $module=false;
            $registerID=$singleRow["registerID"];
            if ( (isset($singleRow["componentModuleID"]))===false)
                {
                echo str_pad($singleRow["Portname"],40).str_pad($singleRow["TYPEREG"],30).str_pad($singleRow["Type"],20).str_pad($singleRow["SubType"],20);
                if (isset($componentConfiguration[$singleRow["TYPEREG"]]))
                    {
                    echo "TYPEREG (ok)";
                    $typereg=$componentConfiguration[$singleRow["TYPEREG"]];
                    if (isset($typereg[$singleRow["Type"]])) $type=$typereg[$singleRow["Type"]];
                    elseif (isset($typereg["*"])) $type=$typereg["*"];
                    else $type=false;
                    if ($type !=false)
                        {
                        echo " Type (ok)";
                        if (isset($type[$singleRow["SubType"]])) $subType=$type[$singleRow["SubType"]];
                        elseif (isset($type["*"])) $subType=$type["*"];
                        else $subType=false;
                        if ($subType !=false)
                            {
                            echo " SubType (ok) ";
                            //print_r($subType); 
                            if (isset($subType["Component"])) { $component=$subType["Component"]; echo "*"; }
                            if (isset($subType["Module"])) { $module=$subType["Module"]; echo "*"; }
                            }
                        }
                    }
                if ( ($component != false) && ($module != false) )
                    {
                    echo "*";
                    $columnComponentModule[$registerID]["Component"]=$component;
                    $columnComponentModule[$registerID]["Module"]=$module;
                    }
                echo "\n";
                }
            }           // ende foreach
        return($columnComponentModule);
        }

    /* sql_componentModules
     *
     * in der evaluateHardware_configuration gibt es jetzt eine Array Configuration um festzulegen welcher Component für welchen Hardware Type und Subtype
     * zu verwenden ist. Diesen auslesen und eventuell aufbereiten:
     *
     * Input Array IPSDeviceHandler_GetComponentModules
     *
     * sowohl type als auch subtype erlauben den Identifier * für die vollständige Auswahl
     *
     */

    public function get_componentModules($componentConfiguration,$debug=false)
        {
        if ($debug) echo "get_componentModules: Tabelle componentModules updaten:\n";
        $componentModules=array();
        foreach ($componentConfiguration as $typereg => $entry1)
            {
            foreach ($entry1 as $type => $entry2)
                {
                if ($debug) echo "  $type ( ";
                $component=""; $module="";
                foreach ($entry2 as $subType => $entry2)    
                    {
                    if ($debug) echo " $subType ";
                    if (isset($entry2["Component"])) $component=$entry2["Component"];
                    if (isset($entry2["Module"])) $module=$entry2["Module"];
                    }
                if ($debug) echo " ) ";
                if ( ($component != "") && ($module != "") )
                    {
                    $componentModules[$component]["componentName"]=$component;
                    $componentModules[$component]["moduleName"]=$module;
                    }
                }
            if ($debug) echo "\n";
            }
        return ($componentModules);
        }

    /* sql_componentModules
     *
     * im reverse Engineering rausfinden welche Components verwendet werden und diese ebenfalls anlegen
     *
     *
     */

    public function get_UsedComponentModules()
        {
        echo "\n\n";
        echo "Die Eventliste des Messagehandler durchgehen (Reverse Engineering !) und den Component und das Module zusätzlich speichern. Damit hat man eine Liste aller bislang verwendeten Components:\n";
        echo "\n";
        $eventConf = IPSMessageHandler_GetEventConfiguration();
        $eventCust = IPSMessageHandler_GetEventConfigurationCust();
        $eventlist = $eventConf + $eventCust;
        echo "Overview of registered Events ".sizeof($eventConf)." + ".sizeof($eventCust)." = ".sizeof($eventlist)." Eintraege : \n";
        $i=0;
        $componentModules=array();
        foreach ($eventlist as $oid => $data)
            {
            if (isset($coid[$oid]))             // nur mehr in der coid Liste die in der Tabelle registers erkannten coids
                {
                echo "****";
                $component=explode(",",$data[1])[0];
                $module=$data[2];
                $regid[$coid[$oid]]["Component"]=$component;
                $regid[$coid[$oid]]["Module"]=$module;
                $componentModules[$component]["componentName"]=$component;
                $componentModules[$component]["moduleName"]=$module;
                }
            echo str_pad($i,4)."Oid: ".$oid." | ".$data[0]." | ".str_pad($data[1],50)." | ".str_pad($data[2],40);
            if (IPS_ObjectExists($oid)) echo " | ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),55)."    | ".GetValue($oid)."\n";
            else echo "  ---> OID nicht verfügbar !\n";
            $i++;
            }
        return ($componentModules);
        }

    /* sql_componentModules:syncTableValues für das componentModules Array
     * Tabelle zeilenweise vergleichen
     *
     * die Tabellen sind immer gleich aufgebaut, Index=Key, Name=Unique, Werte
     * die Arrays sind immer ohne Index, Name => Werte
     * wenn nicht erforderlich nichts daran ändern und zeilenweise updateEntriesValues() aufrufen
     *
     */

    public function syncTableValues($componentModules,$debug=false)
        {
        if ($debug) echo "syncTableValues für das componentModules Array:\n";
        $table="componentModules";        // Datenbank Tabelle in die gespeichert wird
        $columnValue="componentName";        // Key aus dem Array und die Spalte aus der Datenbank Tabelle
        $this->compareSizeArrayTable($componentModules,$columnValue);     // Name der Tabelle aus dem class Name abgeleitet

        $sql = "SELECT componentModuleID,componentName,moduleName FROM $table";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();         // Tabelle in aktueller ISt Version geladen

        $indexTable=array();            // indexTable umbauen auf Key = componentName, Eintrag ist Index
        foreach ($fetch as $entry)
            {
            if (isset($indexTable[$entry[$columnValue]])===false) $indexTable[$entry[$columnValue]]=$entry["componentModuleID"];
            else echo "Fehler, zwei gleiche $columnValue Eintraege in der logischen Anordnung (".$entry[$columnValue].").\n";
            }

        echo "   Tabelle zeilenweise vergleichen:\n";                   // Array hat key componentName
        echo "      componentName                        componentModuleID\n";
        foreach ($componentModules as $componentName => $entry)
            {
            echo "     ".str_pad($componentName,50);            
            $componentModules[$componentName]["componentName"]=$componentName;           // vervollstaendigen, manchmal erforderlich
            $result=$this->updateEntriesValues($componentModules[$componentName]);
            if ($result["Status"] !== false)                // update Geräteeintrag erfolgreich
                {
                $componentModuleID=$result["Index"];
                echo "$componentModuleID\n";
                }
            else echo "\n";
            }
        }

    /* sql_componentModules wird von syncTableValues aufgerufen */

    public function updateEntriesValues($values, $updated=false, $debug=false)
        {
        $advise=array();  // Index deviceID, Identifier name
        $advise["index"] = "componentModuleID";
        $advise["key"]="componentName";
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        return parent::updateTableEntriesValues($this->tableName,$values,$advise,$this->configDB, $updated, $debug);
        }

    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();
        if ($extend) 
            {
            if ($extend===true) $table="topologies.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_serverGateways extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * neben topology für die räumliche hier die Baumstruktur für die logische Abstraktion von Daten
 *
 *      _construct      getDataBaseConfiguration und speichere individuelle Konfiguration
 *      getDatabaseConfig
 *      syncTableValues
 *      updateEntriesValues
 *      getSelectforShow
 *
 */

class sql_serverGateways extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $tableName;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);          // ruft sqlHandle construct auf
        $this->useDatabase("ipsymcon"); 
        $this->tableName="serverGateways";
        $this->getDatabaseConfig($this->tableName);
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="serverGateways";

        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_serverGateways::syncTableValues für das serverGateways Array
     *
     * noch nicht vollstaendig implementiert, da wahrscheinlich kein vollstaendiges Array verfügbar sein wird
     * Name ist der Unique Index, serverGatewayID der primary Index als Int Zahl -> selbe Struktur wie bei allen anderen tables
     * der erste Eintrag ist immer $serverGateways[IPS_GetName(0)]["Name"]=IPS_GetName(0);
     */

    public function syncTableValues($serverGateways,$debug=false)
        {
        if ($debug) echo "syncTableValues für das serverGateways Array:\n";
        $table="serverGateways";        // Datenbank Tabelle in die gespeichert wird
        $columnValue="Name";        // Key aus dem Array und die Spalte aus der Datenbank Tabelle
        $this->compareSizeArrayTable($serverGateways,$columnValue);     // Name der Tabelle aus dem class Name abgeleitet

        $sql = "SELECT serverGatewayID,Name FROM $table";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        if ($debug>1) print_r($fetch);      // index 0,1,2 mit den beiden Eintraegen serverGatewayID und Name
        
        $indexTable=array();
        foreach ($fetch as $entry)      // wenn ein Name gesetzt ist ein array mit index name und gateway id
            {
            if (isset($indexTable[$entry["Name"]])===false) $indexTable[$entry["Name"]]=$entry["serverGatewayID"];
            else echo "Fehler, zwei gleiche Gateways/Server in der logischen Anordnung.\n";
            }
        //print_r($parentTable);
        echo "   Tabelle $table vergleichen, nur Eingabezeilen werden aufgelistet:\n";
        foreach ($serverGateways as $name => $entry)
            {
            echo str_pad($name,30);            
            $serverGateways[$name]["Name"]=$name;           // vervollstaendigen, manchmal erforderlich
            if ( (isset($serverGateways[$name]["Parent"])) && (isset($indexTable[$serverGateways[$name]["Parent"]])) ) $serverGateways[$name]["parentID"] = $indexTable[$serverGateways[$name]["Parent"]];
            $result=$this->updateEntriesValues($serverGateways[$name],false,$debug);
            if ($result["Status"] !== false)                // update Geräteeintrag erfolgreich
                {
                $serverGatewayID=$result["Index"];
                echo " serverGatewayID $serverGatewayID\n";
                }
            else echo "\n";
            }
        }

    /* sql_serverGateways::updateEntriesValues
     * wird von syncTableValues aufgerufen 
     */
    public function updateEntriesValues($values, $updated=false,$debug=false)
        {
        if ($debug) echo "sql_serverGateways::updateEntriesValues aufgerufen:\n";
        $advise=array();  // Index deviceID, Identifier name
        $advise["index"] = "serverGatewayID";
        $advise["key"]="Name";
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        return parent::updateTableEntriesValues($this->tableName,$values,$advise,$this->configDB,$updated, $debug);
        }

    /* ID für einen Namen finden */

    public function getWhere($filter, $debug=false)
        {
        if ($debug) echo "Get serverGatewayID for $filter:\n";    
        $sql = "SELECT * FROM serverGateways WHERE Name = '$filter';";
        $result1=$this->query($sql);
        $serverID = $result1->fetch();
        $result1->result->close();                      // erst am Ende den vielen Speicher freigeben, sonst ist mysqli_result bereits weg !
        if (sizeof($serverID)==1) 
            {
            $serverGatewayID=$serverID[0]["serverGatewayID"];
            if ($debug) echo "gefunden: ".IPS_GetName(0)."=='$serverGatewayID'.\n";
            }
        else $serverGatewayID=false;
        return $serverGatewayID;
        }

    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();
        if ($extend) 
            {
            if ($extend===true) $table="serverGateways.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_topologies extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 *      _construct      getDataBaseConfiguration und speichere individuelle Konfiguration
 *      getDatabaseConfig
 *      updateEntriesValues
 *      getSelectforShow
 *
 */

class sql_topologies extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $tableName;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);          // ruft sqlHandle construct auf
        $this->useDatabase("ipsymcon"); 
        $this->tableName="topologies";
        $this->getDatabaseConfig($this->tableName);
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="topologies";

        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_topologies::syncTableValues für das topology Array
     *
     *
     */

    public function syncTableValues($topology,$debug=false)
        {
        if ($debug) echo "sql_topologies::syncTableValues für das topology Array aufgerufen:\n";
        $table="topologies";        // Datenbank Tabelle in die gespeichert wird
        $columnValue="Name";        // Key aus dem Array und die Spalte aus der Datenbank Tabelle
        $this->compareSizeArrayTable($topology,$columnValue,$debug);     // Name der Tabelle aus dem class Name abgeleitet
        if ($debug>1) { $config = $this->getDatabaseConfig($this->tableName); print_R($config); }
        $sql = "SELECT topologyID,Name FROM $table";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        //print_r($fetch);
        $parentTable=array();
        foreach ($fetch as $entry)          // check ob zwei gleiche Räume in der Tabelle sind
            {
            if (isset($parentTable[$entry["Name"]])===false) $parentTable[$entry["Name"]]=$entry["topologyID"];
            else echo "Fehler, zwei gleiche Räume in der Topologie.\n";
            }
        //print_r($parentTable);

        foreach ($topology as $name => $entry)
            {
            echo str_pad($name,30);            
            $topology[$name]["Name"]=$name;
            if ( (isset($topology[$name]["Parent"])) && (isset($parentTable[$topology[$name]["Parent"]])) ) $topology[$name]["parentID"] = $parentTable[$topology[$name]["Parent"]];
            $result=$this->updateEntriesValues($topology[$name], true, $debug);         // nicht jedesmal die ganze Tabelle löschen
            if ($result["Status"] !== false)                // update Geräteeintrag erfolgreich
                {
                $topologyID=$result["Index"];
                echo " topologyID $topologyID\n";
                }
            else echo "\n";
            }
        }

    /* wird von syncTableValues aufgerufen 
     * advise so zusammenbauen damit es einen Sinn macht     
     */
    public function updateEntriesValues($values, $updated=false,$debug=false)
        {
        $advise=array();  // Index deviceID, Identifier name
        $advise["index"] = "topologyID";
        $advise["key"]="Name";
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        return parent::updateTableEntriesValues($this->tableName,$values,$advise,$this->configDB,$updated, $debug);
        }

    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();        
        if ($extend) 
            {
            if ($extend===true) $table="topologies.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_deviceList extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für die übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * __construct
 * getDatabaseConfig
 * syncTablePlaceID             verwendet syncTableColumnOnOID
 * syncTableProductType
 * syncTableColumnOnOID
 * syncTableValues
 * updateEntriesValues
 * getSelectforShow
 * 
 * Die DeviceList tabelle soll die Geräte mit Ihren Instanzen abspeichern. Jedes Gerät hat mehrere Instanzen. Eine Instanz hat mehrere Register.
 * Hier geht es um einen Eintrag pro Instanz.
 *
 */

class sql_deviceList extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);
        $this->useDatabase("ipsymcon"); 
        echo "sql_deviceList: call this->getDatabaseConfig();\n";
        $this->getDatabaseConfig();
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="deviceList";
        $config = parent::getDatabaseConfiguration()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_deviceList:touchTableOnDevice 
     *
     * Funktion für das deviceList Array, hier wird die wirkliche DeviceList übergeben.
     * Name für Name diese deviceList durchgehen und updateEntriesValues aufrufen, als Key wird der Name verwendet
     * OID funktioniert nicht, da nicht alle Geräte eine OID haben, Homematic sind es nur die Instances mit einer eigenen OID
     * alle auf root Ebene der devicelist liegende Parameter ebenfalls mit synchronisieren  
     *
     * Name ist aber nicht eindeutig, der kann sich jederzeit ändern, besser wäre eine OID auf Geräteebene
     * OID bleibt aber Gerätenamen ändert sich
     *
     */

    public function touchTableOnDevice($deviceList,$debug=false)
        {
        echo "sql_deviceList:touchTableOnDevice aufgerufen.\n";
        //$instances=array();
        //$this->syncTableColumnOnOID("Touch",$instances, $debug); 
        $touch = date("YmdHis");   
        $first=true;
        foreach ($deviceList as $name => $entry)            // alle Geräte aus dem Array durchgehen
            {
            echo "   ".str_pad($name,30);
            $deviceList[$name]["Name"]=$name;
            $deviceList[$name]["Touch"]=$touch;
            // update Geräteeintrag für folgende Spalten
            $columns="";
            foreach ($entry as $column=>$row) 
                {
                switch (strtoupper($column))
                    {
                    case "CHANNELS":
                    case "ACTUATORS":
                    case "INSTANCES":
                        break;
                    default:
                        if ($first) $columns.=$column;
                        else $columns.="|".$column;
                        break;
                    }
                }
            echo str_pad($columns,45);
            //if ($first) 
                {
                $result=$this->updateEntriesValues($deviceList[$name],false,false);          // kann auch die ganze devicelist ohne foreach, als deviceList übergeben da vorher ergänzt wurde, false no update of column changed
                echo json_encode($result);
                }
            echo "\n";
            if ($first) $first=false;
            }
        return ($touch);
        }

    /* sql_deviceList syncTablePlaceID für das deviceList Array
     *
     *  zuerst die Tabelle topologies auslesen, 
     *  dann die einzelnen Zeilen so umbauen:
     *          Name => ID
     *  dann das Input Array instances analysieren   
     *    Format:  topology,place
     *  und eienr OID die dann synchronisiert werden kann zuordnen
     */

    public function syncTablePlaceID($instances,$debug=false)
        {
        if ($debug) echo "syncTablePlaceID: zuerst Table topologies vollständig auslesen.\n"; 
        $sql = "SELECT * FROM topologies;";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        //print_r($fetch);
        $topology=array();
        foreach ($fetch as $singleRow)
            {
            $topology[strtoupper($singleRow["Name"])]=$singleRow["topologyID"];
            }
        //print_r($topology);
        $placeIDs=array();
        foreach ($instances as $oid => $instance)
            {
            if ($debug) echo "    $oid (".str_pad(IPS_GetName($oid).")",45)."  "; 
            if ( (isset($instance[0])) && (strtoupper($instance[0])=="TOPOLOGY") ) 
                {
                if ($debug) echo "Topology  ";
                if (isset($instance[1])) 
                    {
                    if ($debug) echo "Instance   ";
                    if (isset($topology[strtoupper($instance[1])])) 
                        {
                        if ($debug) echo $topology[strtoupper($instance[1])];
                        $placeIDs[$oid]=$topology[strtoupper($instance[1])];   
                        }
                    } 
                }    
            if ($debug) echo "\n";
            }
        if ($debug)
            {
            //foreach ($placeIDs as $oid => $id) echo "    $oid (".str_pad(IPS_GetName($oid).")",45)."   $id\n";
            //print_r($placeIDs);
            }
        $this->syncTableColumnOnOID("placeID",$placeIDs,$debug);    
        }

    /* sql_deviceList syncTableProductType für das deviceList Array
     *
     *   
     *
     */

    public function syncTableProductType($homematicList,$debug=false)
        {
        $instances=array();
        foreach ($homematicList as $instanceHM)
            {
            if ( (isset($instanceHM["OID"])) && (isset($instanceHM["HMDevice"])) )
                {
                //echo $instanceHM["OID"]."  ".$instanceHM["HMDevice"]."\n";
                $instances[$instanceHM["OID"]]=$instanceHM["HMDevice"];
                }
            }
        $this->syncTableColumnOnOID("ProductType",$instances, $debug);    
        }

    /* sql_deviceList syncTableColumnOnOID für den deviceList Table erweitert um die instances wegen portID, OID und Name
     *    columnName    Name der Tabellenspalte
     *    columnData    array
     *
     *      column muss ein auf OID indiziertes array sein
     *
     * es wird die ganze Tabelle devicelist mit instances ausgelesen und dann zeilenweise upgedatet 
     */

    public function syncTableColumnOnOID($columnName,$columnData,$debug=false)
        {
        if ($debug) 
            {
            echo "   syncTableColumnOnOID:für $columnName und folgende insgesamt ".sizeof($columnData)." Werte.\n";
            print_r($columnData);
            }
        $sql = "SELECT deviceList.deviceID,deviceList.Name,deviceList.Type,instances.portID,instances.OID,instances.Name AS Portname 
                    FROM (deviceList RIGHT JOIN instances ON deviceList.deviceID=instances.deviceID)
                    ;";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        if ($debug) echo "      devicelist SELECT liefert ".sizeof($fetch)." Ergebnisse.\n";
        $first=true; 
        $deviceID=array();          // es gibt mehrere Einträge für ein Gerät
        foreach ($fetch as $singleRow)
            {
            if ($first)
                {
                $first=false;
                if ($debug) 
                    {
                    echo "          Erste Zeile ausgeben zur Orientierung :\n";
                    print_r($singleRow);    
                    echo "DeviceID   OID   Name (empty if additional instance)  Type  Portname  newData\n";                    
                    }
                }
            $oid = $singleRow["OID"];       // sync table based on OID, aktuelle OID auslesen
            if ($debug) 
                {
                echo str_pad($singleRow["deviceID"],10)."$oid ";
                }
            if (isset($deviceID[$singleRow["deviceID"]])===false)       // nur beim ersten Mal ueberschreiben, mehrere Instanzen teilen sich die selbe deviceID
                {
                if ($debug) echo " | ".str_pad($singleRow["Name"],30);
                $deviceList=array();                                    // Input Array für Update zusammenstellen: Name, columndata[oid]
                $deviceList["Name"]=$singleRow["Name"];
                $deviceID[$singleRow["deviceID"]]=$singleRow["Portname"];                 // damit beim nächsten Mal nicht mehr dran kommt
                if (isset($columnData[$oid])) 
                    {
                    $deviceList[$columnName]=$columnData[$oid];         // umschlüsseln von Key oid auf Key Name, der Wert bleibt unverändert
                    if ($debug) str_pad($singleRow["Type"],20).str_pad($singleRow["Portname"],40)."   ".$columnData[$oid];
                    $result = $this->updateEntriesValues($deviceList,true);         // true for update of column changed
                    }
                }
            else 
                {
                $deviceID[$singleRow["deviceID"]].="|".$singleRow["Portname"];
                //echo "syncTableColumnOnOID $columnName, ".$singleRow["deviceID"]." : pls see above, double\n";  // kein Fehler
                }
            if ($debug) echo "\n";
            }
        if ($debug) 
            {
            echo "    insgesamt ".sizeof($deviceID)." Geräte behandelt.\n";
            //print_R($deviceID);
            }
        }

    /* sql_deviceList::syncTableValues für das deviceList Array
     *
     *    Die Sub-Tabellen werden ebenfalls gleichzeitig synchronisiert
     *    geht nicht anders weil immer gleich der passende Identifier in die nächste Tabelle/Ebene mitgenommen wird
     * 
     *    Aufgerufen vom Modul UpdateMySql um die Einträge der deviceList (array) abzugleichen
     *    es wird die DeviceList Gerät per Gerät durchgegangen und geprüft ob der Eintrag neu oder geändert werden muss
     *    dazu wird updateEntriesValues verwendet
     *
     */

    public function syncTableValues($deviceList,$debug=false)
        {
        // deviceList ist auch das Array mit den Inputdaten und config die gerade synchronisierte config der Datenbank 
        if ($debug) echo "  sql_deviceList::syncTableValues für das deviceList Array, Debug Mode ein:\n";

        $sql_instances = new sql_instances();
        $sql_channels = new sql_channels();
        $sql_registers = new sql_registers();

        $table="deviceList";        // Datenbank Tabelle in die gespeichert wird
        $columnValue="Name";        // Key aus dem Array und die Spalte aus der Datenbank Tabelle
        $this->compareSizeArrayTable($deviceList,$columnValue,$debug);
        $i=1; $max=100;
        //print_r($deviceList);
        foreach ($deviceList as $name => $entry)            // alle Geräte aus dem Array durchgehen
            {
            echo "   ".str_pad($name,30);
            $deviceList[$name]["Name"]=$name;
            //print_r($deviceList[$name]);
            $result=$this->updateEntriesValues($deviceList[$name],true, $debug);                // true for update of column changed
            /* touch device */
            if ($result["Status"] !== false)                // update Geräteeintrag erfolgreich
                {
                $deviceID=$result["Index"];
                echo " deviceID $deviceID\n";               // index zum verlinken übernehmen
                if ($i <= $max)
                    { 
                    if ($result["Update"]) 
                        {
                        $i++;
                        if ($debug) echo "-->Update Device erfolgt.\n";
                        //print_r($entry);
                        }
                    if (isset($entry["Instances"]))         // die instances machen
                        {
                        foreach ($entry["Instances"] as $portId => $entryInstance)
                            {
                            $entryInstance["portID"]=$portId;
                            $entryInstance["deviceID"]=$deviceID;
                            //if ($debug) echo "  Update Instances erfolgt jetzt, Aufruf sql_instances::updateEntriesValues.\n";
                            $result=$sql_instances->updateEntriesValues($entryInstance,true, $debug);
                            if ($result["Update"]) 
                                {
                                $i++;
                                if ($debug) echo "-->Update Instances erfolgt.\n";
                                //print_r($entry);
                                }                        }
                        }
                    if (isset($entry["Channels"]))         // die channels machen
                        {
                        //print_r($entry["Channels"]);
                        foreach ($entry["Channels"] as $portId => $entryChannel)
                            {
                            $entryChannel["portID"]=$portId;
                            $entryChannel["deviceID"]=$deviceID;
                            $result=$sql_channels->updateEntriesValues($entryChannel,true, $debug);
                            if ($result["Update"]) 
                                {
                                $i++;
                                if ($debug) echo "-->Update Channels erfolgt.\n";
                                //print_r($entry);
                                }                        
                            if (isset($entryChannel["TYPECHAN"]))
                                {
                                //echo "    TYPECHAN: ".$entryChannel["TYPECHAN"]."  Name channel: ".$entry["Instances"][$portId]["NAME"]."\n";
                                $registers=explode(",",$entryChannel["TYPECHAN"]);
                                if (sizeof($registers)>0)
                                    {
                                    foreach ($registers as $register)
                                        {
                                        $entryRegister=array();
                                        $entryRegister["portID"]=$portId;
                                        $entryRegister["deviceID"]=$deviceID;
                                        $entryRegister["Name"]=$entry["Instances"][$portId]["NAME"];
                                        $entryRegister["TYPEREG"]=$register;
                                        if (isset($entry["Channels"][$portId][$register])) $entryRegister["Configuration"]=$entry["Channels"][$portId][$register];
                                        else 
                                            {
                                            if ($debug)
                                                {
                                                echo "Configuration für $portId $register nicht verfügbar. Speichere leeren String.:\n";    
                                                print_R($entry["Channels"]);
                                                } 
                                            $entryRegister["Configuration"]="";
                                            }
                                        $result=$sql_registers->updateEntriesValues($entryRegister,true, $debug);
                                        if ($result["Update"])
                                            {
                                            $i++;
                                            if ($debug) echo "-->Update Register (TYPECHAN) erfolgt.\n";
                                            //print_r($entry);
                                            }
                                        }    
                                    }
                        
                                }
                            }
                        }
                    }
                }
            else echo "\n";
            //$debug=false;
            }       // ende foreach
        }        // ende function

    /* sql_deviceList:updateEntriesValues 
     *
     * Update einer Zeile in der Tabelle, Werte stehen in values
     * für die Keys muss ein Wert da sein für das WHERE statement und dann zumindestens ein Feld für das Update
     * mit advise wird geregelt wie indexiert werden soll
     *
     */

    public function updateEntriesValues($values, $updated=false,$debug=false)
        {
        //print_r($values);
        $advise=array();  // Index deviceID, Identifier name
        $advise["index"] = "deviceID";
        $advise["key"]="Name";
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        return parent::updateTableEntriesValues("deviceList",$values,$advise,$this->configDB,$updated, $debug);
        }

    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();
        if ($extend) 
            {
            if ($extend===true) $table="deviceList.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_instances extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für dei übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * __construct
 * getDatabaseConfig
 * updateEntriesValues
 * getSelectforShow
 *
 */

class sql_instances extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);
        $this->useDatabase("ipsymcon"); 
        $this->getDatabaseConfig();
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="instances";
            
        $config = parent::getDatabaseConfig()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_instances::updateEntriesValues
     * wenn updated true wird auch der Zeitstempel in der Zeile nachgestellt
     */

    public function updateEntriesValues($values, $updated=false, $debug=false)
        {
        $advise=array();                 // Index instanceID, Key deviceID,portID, Identifier Name
        $advise["index"] = "instanceID";                            // <==================  der Index, auch Unique
        $advise["key"]   = "deviceID,portID";                       // <==================  alle relevanten Keys die für die eindeutige Identifikation einer Zeile notwendg sind
        $advise["unique"]= "OID";                                   // gleiche Werte beime Create oder Update beachten, können in einer anderen Zeile sein und zu einem Fehler führen
        $advise["ident"]="Name";          
        $advise["identTgt"] = "NAME";               // in der deviceliste ist es NAME
        $advise["change"]="Update";
        $advise["history"]="";
        if ($debug) echo "   Aufruf updateTableEntriesValues:\n";
        return parent::updateTableEntriesValues("instances",$values,$advise,$this->configDB,$updated,$debug);
        }

    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();
        if ($extend) 
            {
            if ($extend===true) $table="instances.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }


    }

/* sql_channels extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für dei übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * __construct
 * getDatabaseConfig
 * updateEntriesValues
 * getSelectforShow
 *
 */

class sql_channels extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);
        $this->useDatabase("ipsymcon"); 
        $this->getDatabaseConfig();
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="channels";
            
        $config = parent::getDatabaseConfig()[$table]; 
        $this->configDB=$config;
        return $config;   
        }

    /* sql_channels::updateEntriesValues
     * wenn updated true wird auch der Zeitstempel in der Zeile nachgestellt
     */

    public function updateEntriesValues($values, $updated=false, $debug=false)
        {
        $advise=array();                 // Index instanceID, Key deviceID,portID, Identifier Name
        $advise["index"] = "channelID";                                                             // <==================
        $advise["key"]="deviceID,portID";                                                           // <==================
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        //echo "sql_channel:updateEntriesValues\n";
        if (isset($values["RegisterAll"])) $values["RegisterAll"] = json_encode($values["RegisterAll"]);
        //print_r($values);
        return parent::updateTableEntriesValues("channels",$values,$advise,$this->configDB,$updated,$debug);                           // <==================
        }


    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();        
        if ($extend) 
            {
            if ($extend===true) $table="channels.";                           // <==================
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_registers extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für dei übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * __construct
 * getDatabaseConfig
 * syncTableColumnOnRegisterID
 * updateEntriesValues
 * getSelectforShow
 *
 */

class sql_registers extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);
        $this->useDatabase("ipsymcon"); 
        $this->getDatabaseConfig();
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="registers";                                    // <==================

        $config = parent::getDatabaseConfig(); 
        $this->configDB=$config[$table];
        return $this->configDB;   
        }

    /* syncTableColumnOnRegisterID für den register Table
     *    columnName    Name der Tabellenspalte, die geändert werden soll
     *    columnData    array
     *
     *      columnData muss ein auf registerID indiziertes array sein
     *
     * es wird die ganze Tabelle registers ausgelesen und dann zeilenweise upgedatet 
     * dazu parent::updateTableEntriesValues("registers",$register,$advise,$this->configDB,$debug) aufrufen
     * damit WHERE registerID=$register["registerID"] für Auswahl der Zeile
     *            und SET $register[columnName] = Wert
     */

    public function syncTableColumnOnRegisterID($columnName,$columnData,$debug=false)
        {
        if ($debug) 
            {
            echo "   syncTableColumnOnRegisterID:für $columnName.\n";
            //print_r($columnData);
            }

        $advise=array();                                // Index instanceID, Key deviceID,portID, Identifier Name
        $advise["index"] = "registerID";                //  <==================
        $advise["key"]="";                              // auch registerID verwenden  <==================
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";

        $sql = "SELECT * FROM registers;";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        if ($debug) echo "      SELECT * from registers liefert ".sizeof($fetch)." Zeilen/Ergebnisse.\n";
        $first=true; $registers=array(); $register=array();
        foreach ($fetch as $singleRow)
            {
            if ($first)
                {
                $first=false;
                if ($debug) print_r($singleRow);    
                }
            $registerID = $singleRow["registerID"];
            if ($debug) 
                {
                echo str_pad($singleRow["registerID"],10);
                }
            if (isset($registers[$singleRow["registerID"]])===false)       // nur beim ersten Mal ueberschreiben
                {
                if (isset($columnData[$registerID]))  
                    {
                    $register[$columnName] = $columnData[$registerID];          // für SET columnName Wert
                    $register["registerID"] = $singleRow["registerID"];         // für WHERE  
                    $result = parent::updateTableEntriesValues("registers",$register,$advise,$this->configDB,$debug);
                    }
                else 
                    {
                    if ($debug) echo "Wert fehlt \n";
                    }
                $registers[$singleRow["registerID"]]=true;   // für Überschreibungserkennung
                }
            else echo "syncTableColumnOnRegisterID $columnName, ".$singleRow["deviceID"]." : pls see above, double\n";
            if ($debug) echo "\n";
            }
        }

    /* sql_registers::updateEntriesValues
     *
     * ändert eine Zeile der Klassenspezifischen Tabelle wenn deviceID,portID und TYPEREG gleich sind
     *
     * $values ist ein array mit den einzelenen Spalten, die Spaltennamen aus dem neuen array advise muessen zumindestens vorhanden sein
     * wird von syncTableValues aufgerufen
     */

    public function updateEntriesValues($values, $updated=false, $debug=false)
        {
        $advise=array();                 // Index instanceID, Key deviceID,portID, Identifier Name
        $advise["index"] = "registerID";
        $advise["key"]="deviceID,portID,TYPEREG";
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
        return parent::updateTableEntriesValues("registers",$values,$advise,$this->configDB,$updated,$debug);
        }


    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();        
        if ($extend) 
            {
            if ($extend===true) $table="registers.";
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }

/* sql_valuesOnRegs extends sqlOperate
 *
 * sqlHandle für die Basisfunktionen, die mehr der Datenbank zugeordnet sind
 * sqlOperate für dei übergeordneten Funkionen zur Manipulation der Datenbank
 * sql_xxx für die Tabellen spezifischen Operationen
 *
 * __construct
 * getDatabaseConfig
 * syncTableColumnOnRegisterID
 * updateEntriesValues
 * getSelectforShow
 *
 */

class sql_valuesOnRegs extends sqlOperate
    {
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    private $configDB;          // Konfiguration der  Database, has effect on request default config

    public function __construct($oid=false)
        {
        parent::__construct($oid);
        $this->useDatabase("ipsymcon"); 
        $this->getDatabaseConfig();
        }

    public function getDatabaseConfig(...$tableName)
        {
        if (isset($tableName[0])) $table=$tableName[0];
        else $table="valuesOnRegs";                                     // <===================

        $config = parent::getDatabaseConfig(); 
        $this->configDB=$config[$table];
        return $this->configDB;   
        }

    /* sql_valuesOnRegs:syncTableValues für das valuesOnRegs Array
     *
     *
     */

    public function syncTableValues($valuesOnRegs,$debug=false)
        {
        $advise=array();                 // Index, Key und Identifier Name festlegen
        $advise["index"] = "valueID";
        $advise["key"]="COID,registerID";           // COID kann bei mehreren IP Symcon Servern doppelt vorkommen, registerID nicht
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";
            
        if ($debug) echo "sql_valuesOnRegs::syncTableValues für das valuesOnRegs Array:\n";
        $table="valuesOnRegs";                                                  // Datenbank Tabelle in die gespeichert wird
        $columnValue=$advise["key"]; 
        $indexID=$advise["index"];                                                   // Key aus dem Array und die Spalte aus der Datenbank Tabelle
        $this->compareSizeArrayTable($valuesOnRegs,$columnValue,$debug);     // Name der Tabelle aus dem class Name abgeleitet

        /*   es wird nicht auf sich selber referenziert, kann weggelassen werden 
        $sql = "SELECT $indexID,$columnValue FROM $table";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        //print_r($fetch);      // index 0..n mit allen Eintraegen und jetzt neu sortieren
        
        $indexTable=array();
        foreach ($fetch as $entry)      // wenn ein Name gesetzt ist ein array mit index name und gateway id
            {
            if (isset($indexTable[$entry[$columnValue]])===false) $indexTable[$entry[$columnValue]]=$entry["valueID"];          // auf INDEX COID die Referenz zu valueID speichern
            else echo "Fehler, zwei gleiche $columnValue in der logischen Anordnung.\n";
            }   */

        echo "   Tabelle $table vergleichen, nur Eingabezeilen werden aufgelistet:\n";
        foreach ($valuesOnRegs as $index => $entry)
            {
            echo str_pad($index,10);                                        // valuesOnRegs hat als index die COID           
            $valuesOnRegs[$index][$columnValue]=$index;                     // vervollstaendigen, manchmal erforderlich
            $result=$this->updateEntriesValues($valuesOnRegs[$index],$advise);      // es wird eine Zeile mit den gewünschten neuen Werten übergeben, Index wird selbst gefunden
            if ($result["Status"] !== false)                // update Geräteeintrag erfolgreich
                {
                $valueID=$result["Index"];
                echo " valueID $valueID\n";
                }
            else echo "\n";
            }
        }

    /* sql_valuesOnRegs:updateEntriesValues
     *
     * ändert eine Zeile der Klassenspezifischen Tabelle
     *
     * $values ist ein array mit den einzelenen Spalten, die Spaltennamen aus dem neuen array advise muessen zumindestens vorhanden sein
     * wird von syncTableValues aufgerufen
     */

    public function updateEntriesValues($values, $advise, $updated=false, $debug=false)
        {
        return parent::updateTableEntriesValues("valuesOnRegs",$values,$advise,$this->configDB,$updated,$debug);
        }


    /* syncTableColumnOnValueID für den valuesOnRegs Table
     *    columnName    Name der Tabellenspalte, die geändert werden soll
     *    columnData    array
     *
     *      columnData muss ein auf registerID indiziertes array sein
     *
     * es wird die ganze Tabelle valuesOnRegs ausgelesen und dann zeilenweise upgedatet 
     * dazu parent::updateTableEntriesValues("registers",$register,$advise,$this->configDB,$debug) aufrufen
     * damit WHERE registerID=$register["registerID"] für Auswahl der Zeile
     *            und SET $register[columnName] = Wert
     */

    public function syncTableColumnOnValueID($columnName,$columnData,$debug=false)
        {
        if ($debug) 
            {
            echo "   syncTableColumnOnValueID:für $columnName.\n";
            //print_r($columnData);
            }

        $advise=array();                 // Index instanceID, Key deviceID,portID, Identifier Name
        $advise["index"] = "valueID";                                                                        // <==================
        $advise["key"]="COID";                                                                              // <==================
        $advise["ident"]="";
        $advise["change"]="Update";
        $advise["history"]="";

        $sql = "SELECT * FROM valueOnRegs;";
        $result1=$this->query($sql);
        $fetch = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        if ($debug) echo "      SELECT * from valueOnRegs liefert ".sizeof($fetch)." Zeilen/Ergebnisse.\n";
        $first=true; $values=array(); $value=array();
        foreach ($fetch as $singleRow)
            {
            if ($first)
                {
                $first=false;
                if ($debug) print_r($singleRow);    
                }
            $valueID = $singleRow["valueID"];
            if ($debug) 
                {
                echo str_pad($singleRow["valueID"],10);
                }
            if (isset($values[$singleRow["valueID"]])===false)       // nur beim ersten Mal ueberschreiben
                {
                if (isset($columnData[$valueID]))  
                    {
                    $value[$columnName] = $columnData[$valueID];          // für SET columnName Wert
                    $value["valueID"] = $singleRow["valueID"];         // für WHERE  
                    $result = parent::updateTableEntriesValues("valueOnRegs",$value,$advise,$this->configDB,$debug);
                    }
                else 
                    {
                    if ($debug) echo "Wert fehlt \n";
                    }
                $values[$singleRow["valueID"]]=true;
                }
            else echo "syncTableColumnOnValueID $columnName, ".$singleRow["deviceID"]." : pls see above, double\n";
            if ($debug) echo "\n";
            }
        }


    /* für die Darstellung als Tabelle */

    public function getSelectforShow($extend=false)
        {
        //print_r($this->configDB);
        $select=array();        
        if ($extend) 
            {
            if ($extend===true) $table="valuesOnRegs.";                     // <==================
            else $table="$extend.";
            }
        else $table="";
        $result = $this->getFullSelectforShow($this->configDB);
        $select["Select"] = $this->convertColumnArraytoList($result,$table);
        return $select;
        }

    }


/*************************************************************************
 *
 * sqlHandle  class
 *
 * Vereinfachung des Zugriffs auf die Datenbank
 * new definiert die Datenbank und den Zugriff, zur einfachen Configverwaltung wird eine demel24 Instanz genutzt 
 *
 *  __construct         Class anlegen, oid der MySQL Instanz oder automatische Erkennung
 *  command             SQL Befehl absetzen
 *  query               SQL Abfrage absetzen
 *  close               Variablen wieder freigeben
 *  useDatabase         USE $database
 *  getDatabaseConfig   get Soll Konfiguration
 *  showTables          SHOW TABLES;
 *  describeTable       DESCRIBE tableName;
 *  countTable          SELECT COUNT($column) FROM $table WHERE $column='$needle';
 *
 *
 *
 ***********************************************************************************/

class sqlHandle
    {

    private $sqlHandle;         // object of mysqli object
    private $oid;               // OID of Instance with simple MySql Handler
    private $dataBase;          // Name of used Database, has effect on request default config
    private $table;             // Name of used Table, has effect on request default config
    
    static $configDataBase;          // Konfiguration der  Database, has effect on request default config, static not private static
    public $available;              // Status wenn Datenbank verfügbar ist

    /* oid der MySQL Instanz oder automatische Erkennung
     *
     * false als Rückgabe im construct funktioniert nicht da bei new die structur zurückgegeben wird
     * die tötet den ganzen Ablauf - daher Status available eingeführt 
     */

    public function __construct($oid=false,$debug=false)
        {
        if ($oid === false) 
            {
            //$oid=25763;
           	$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
            $oidResult = $modulhandling->getInstances('MySQL');
            if (sizeof($oidResult)>0) 
                {
                $oid=$oidResult[0];           // ersten treffer newt_checkbox_tree_get_multi_selection
                if ($debug) echo get_class($this).",sqlHandle: new $oid (".IPS_GetName($oid).") for MySQL Database found. ";
                }
            else 
                {
                if ($debug) echo get_class($this).",sqlHandle: OID einer Instance MySQL not found.\n";
                $this->available=false;
                return(false);
                }
            }
        elseif (is_numeric($oid)) 
            {
            if ($debug) echo get_class($this).",sqlHandle: new with $oid (".IPS_GetName($oid).") parameter for MySQL Database. ";
            }
        else            //$oid ist ein String
            {
           	$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
            $oidResult = $modulhandling->getInstances($oid);
            if (sizeof($oidResult)>0) 
                {
                $oid=$oidResult[0];           // ersten treffer newt_checkbox_tree_get_multi_selection
                if ($debug) echo get_class($this).",sqlHandle: new $oid (".IPS_GetName($oid).") for MySQL Database found. ";
                }
            else 
                {
                if ($debug) echo get_class($this).",sqlHandle: OID einer Instance MySQL not found.\n";
                $this->available=false;
                return(false);
                }
            }
        // oid ist eine mySQL Instanz, Statuscheck
        $status=IPS_GetInstance($oid)["InstanceStatus"];
        if ($status != 102) 
            {
            if ($debug) echo get_class($this).",sqlHandle: Instanz Konfiguration noch nicht abgeschlossen, oder Instanz fehlerhaft. Status is $status.\n";
            $this->available=false;
            return(false);
            }
        $this->oid = $oid; 
        $this->sqlHandle = MySQL_Open($this->oid);
        if ($this->sqlHandle->connect_error) 
            {
            echo "   Verbindung fehlgeschlagen " . $this->sqlHandle->connect_error."\n";
            $this->available=false;
            return (false);
            //die("   Verbindung fehlgeschlagen " . $this->sqlHandle->connect_error);
            } 
        else 
            {
            if ($debug) echo " --> Verbindung zu MySQL erfolgreich hergestellt.\n";
            }
        $this->available=true;
        }

    /* sqlHandle::command
    *
    * SQL Befehl absetzen 
    */

    public function command($query, $debug=false)
        {
        $result = mysqli_query($this->sqlHandle, $query);
        if ($result) 
            {
            if ($debug) echo "$query //Eintrag geschrieben\n";
            return $result;
            } 
        else 
            {
            //echo "***sqlHandle::command, Fehler beim Schreiben: $query // " . mysqli_error($this->sqlHandle)."\n";
            echo "***sqlHandle::command, Fehler beim Schreiben:  " . mysqli_error($this->sqlHandle)."\n";
            return (false);
            }
        }

    /* sqlHandle::query
     *
     * SQL Abfrage absetzen 
     */

    public function query($query,$debug=false)
        {
        $result = $this->sqlHandle->query($query);
        if ($result) 
            {
            if ($debug) echo "$query //Eintrag mit ".$result->num_rows." Zeilen Ergebnis gelesen\n";
            //print_r($result);
            return new sqlReturn($result);
            } 
        else 
            {
            echo "$query //Fehler beim Lesen: " . mysqli_error($this->sqlHandle)."\n";
            }
         }

    /* sqlHandle::close
     *
     * Variablen wieder freigeben 
     */

    public function close()
        {
        MySQL_Close($this->oid, $sqlHandle);
        }

    /* sqlHandle::useDatabase
     *
     * USE database aufrufen 
     */

    public function useDatabase($database,$debug=false)
        {
        if ($debug) echo "Aufruf von useDatabase($database,...\n";

        $this->dataBase=$database;
        $sql = "USE $database;";
        $result=$this->command($sql);
        }

    /* sqlHandle::getDatabaseConfig
     */

    public function getDatabaseConfig(...$tableName)
        {
        return $this->getDatabaseConfiguration();   
        }

    /* sqlHandle::getDatabaseConfiguration
     *
     * die Sollkonfiguration für die einzelnen Datenbanken ausgeben 
     *
     * config Formatierungsregeln:
     *      Field wird vom key übernommen
     *      Type,Extra immer in Kleinbuchstaben
     *
     * verwendet checkandrepairDatabaseConfig für die Vereinheitlichung der Konfiguration, hat Auswirkungen auf die Verglechsfunktionen
     */

    public function getDatabaseConfiguration($debug=false)
        {
        if ((isset(static::$configDataBase))==false) 
            {
            if ($debug) echo "getDatabaseConfiguration: return initial Config for Database '".$this->dataBase."' with MariaDB:\n";                
            switch ($this->dataBase)
                {
                case "ipsymcon":
                    $config = mySQLDatabase_getConfiguration();         // das ist die Funktion in der Config Datei
                    break;
                default:
                    $config=array();
                    break;
                }
            //print_r($config);

            /* Konfiguration prüfen und überarbeiten und gleich speichern */
            static::$configDataBase=$this->checkandrepairDatabaseConfig($config, $debug);
            return static::$configDataBase;
            }            
        else 
            {
            if ($debug) echo "getDatabaseConfiguration: return Config for Database ".$this->dataBase." with MariaDB:\n";
            return static::$configDataBase;
            }
        }

    /* sqlHandle::checkandrepairDatabaseConfig
     * 
     * Konfiguration prüfen und überarbeiten, Struktur ist 
     *   tablename => column => configuration
     *
     */

    private function checkandrepairDatabaseConfig($config, $debug=false)
        {
        if ($debug) echo "   check and repair configuration:\n";

        foreach ($config as $tablename => $table)
            {
            foreach ($table as $columnID => $column)
                {
                if ( ! ( (isset($column["Field"])) && ($column["Field"]==$columnID) ) )
                    {
                    //echo "         Änderung: config[$tablename][$columnID][\"Field\"]=$columnID\n";
                    $config[$tablename][$columnID]["Field"]=$columnID;    
                    }
                // autoset NULL to NO, by auto set Funktionen ist automatisch auch gewährleistet dass kieine NUMLL Werte auftreten, daher ist der Parameter auch NULL NO
                $autosetNull=false;   
                if ( (isset($column["Key"])) && ($column["Key"]!="") ) $autosetNull=true;                                 // ein Key ist gesetzt, automatisch NOT NULL überschreiben
                if (isset($column["Extra"])) 
                    {
                    $extraCommand=strtoupper($column["Extra"]);
                    if ($extraCommand=="AUTO_INCREMENT")  $autosetNull=true;    // auto_increment ist gesetzt, automatisch mit NOT NULL überschreiben
                    // on update current_timestamp()
                    $extraOnUpdate = explode(" ",$extraCommand);
                    if (sizeof($extraOnUpdate)>2) 
                        {
                        //if ($debug) echo json_encode($extraOnUpdate)."\n";
                        if (($extraOnUpdate[0]=="ON") && ($extraOnUpdate[1]=="UPDATE")) $autosetNull=true;    // on update ist gesetzt, automatisch mit NOT NULL überschreiben
                        }
                    }
                if (isset($column["Default"])) 
                    {
                    $defaultCommand=strtoupper($column["Default"]);
                    switch ($defaultCommand)
                        {
                        case "CURRENT_TIMESTAMP()":
                            $autosetNull=true;    // default ist gesetzt, automatisch mit NOT NULL überschreiben
                            break;

                        }
                    }
                if ($autosetNull)    
                    {
                    if ($debug) echo "         Änderung: config[$tablename][$columnID][\"Null\"]=\"NO\"  Feld wird automatisch beschrieben, kann nicht Null sein (zB auto_increment, on update, Key etc\n";
                    $config[$tablename][$columnID]["Null"] = "NO";   
                    }
                if (isset($column["Null"])===false) $config[$tablename][$columnID]["Null"]  = "NO";    
                }
            }
        return $config;
        }


    /* SHOW Tables aufrufen */

    public function showTables($debug=false)
        {
        $sql = "SHOW TABLES;";
        $result1=$this->query($sql);
        /*  object(sqlReturn)#31 (1) {
            ["result"]=>
            object(mysqli_result)#29 (5) {
                ["current_field"]=>
                int(0)
                ["field_count"]=>
                int(1)
                ["lengths"]=>
                NULL
                ["num_rows"]=>
                int(2)
                ["type"]=>
                int(0)
                }
            } */
        
        //var_dump($result1->result);
        //var_dump($result1);

        if ($debug) echo "=> Eintrag mit ".$result1->num_rows()." Zeilen Ergebnis gelesen\n";    
        $tables = $result1->fetchShowTables();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        return $tables;
        }

    /* DESCRIBE tableName aufrufen , altuelle Config der MariaDB ausgeben */

    public function describeTable($tableName, $debug=false)
        {
        $sql = "DESCRIBE $tableName;";
        $result1=$this->query($sql);
        $columns = $result1->fetchDescribe();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        return $columns;
        }

    /* ein WHERE statement aus column und needle überlegen
     *          WHERE $column='$needle' 
     *
     * column kann auch ein Array sein. Dann die einzelnen Einträge AND bzw OR verknüpfen.
     * Erster Eintrag ist üblicherweise der Index und die anderen sind Werte die damit verknüpft werden können
     * AND wird für das finden eines Index Eintrages mit bestimmten Werten in den anderen Spalten der Tabelle verwendet
     * mit OR laesst sich untersuchen ob Spalten mit dem Attribut UNIQE bereits einen bestimmten Wert haben
     *
     */

    public function whereStatement($column,$needle,$and=true, $debug=false)
        {
        if ( (is_array($column)) && (is_array($needle)) )
            {
            if ( ((sizeof($column))>0) && (sizeof($column) == sizeof($needle)) )
                {
                $first=true; 
                $sql = "WHERE ";
                foreach ($column as $index => $entry)
                    {
                    //if ($debug>1) { echo "whereStatement Name : $index => $entry Needle : ".$needle[$index]."\n";  }        // print_R($needle);
                    if ($first) 
                        { 
                        $first = false; 
                        $spalte=$entry; 
                        $sql .= "$entry='".$needle[$index]."'";
                        }
                    else 
                        {
                        if ($and) $sql .= " AND $entry='".$needle[$index]."'";
                        else $sql .= " OR $entry='".$needle[$index]."'";
                        }
                    }
                }
            else 
                {
                $sql="";
                echo "ERROR, whereStatement wrong input parameter, arrays differ. \n";
                }
            }
        else $sql = "WHERE $column='$needle'";
        return ($sql);
        }

    /* count gleiche Einträge mit needle oder (*) 
     * table    Tabelle die durchsucht werden soll
     * column   array von mehreren keys oder nur einer
     *
     * SELECT COUNT(".$column[0].") FROM $table WHERE $column[0]==$needle[$column[0]]
     *
     * SELECT COUNT(*) FROM $table;                 wenn column nicht definiert ist
     * SELECT COUNT(*) As $column FROM $table;      wenn column als Wert definiert ist und needle default oder * ist
     * SELECT COUNT($column) FROM $table WHERE $column='$needle';
     * SELECT COUNT($column) FROM $table WHERE $column[0]='$needle[0]' AND|OR $column[1]='$needle[1]';
     *
     */ 
    public function countTable($table,$column,$needle="*",$and=true,$debug=false)
        {
        if ( (is_array($column)) && (sizeof($column)>0) && ($column[0] != "") )           // wenn ein array und auch befüllt und nicht ein empty Eintrag dann auch ein Where Statement reinquetschen
            {
            //if ($debug>1) echo "countTable $table whereStatement ".json_encode($column)." ".json_encode($needle)."\n"; 
            $sql=$this->whereStatement($column,$needle,$and,$debug);
            $sqlCommand = "SELECT COUNT(".$column[0].") FROM $table $sql;";
            //if ($debug) echo "countTable mit sql Statement \"$sqlCommand\"\n";
            } 
        else 
            {
            if (is_array($column)) { $column=""; $needle="*"; }         // definierte Werte schaffen
            if ($debug) echo "countTable('$table','$column','$needle','$and')\n"; 
            if ($column=="")
                {
                $sqlCommand = "SELECT COUNT(*) FROM $table;";
                }
            elseif ($needle=="*")
                {
                $sqlCommand = "SELECT COUNT(*) As $column FROM $table;";        // funktioniert nur so
                }
            else
                {
                $sqlCommand = "SELECT COUNT($column) FROM $table WHERE $column='$needle';";
                }
            }
        //if ($debug) echo "SQL: $sqlCommand\n";    
        $result1=$this->query($sqlCommand);
        $count = $result1->fetchCount();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        return $count;
        }

    /* den Table vor parametrieren, so wie die Datenbank */

    public function useTable($table)
        {
        $this->table=$table;
        }

    /* die Inizes für eine Tabelle auslesen */

    public function showIndex($tableName)
        {
        $sqlCommand = "SHOW INDEX FROM $tableName;";
        $result1=$this->query($sqlCommand);
        $fetch = $result1->fetch();
        $result1->result->close(); 
        echo "Index (";
        $next=false;
        foreach ($fetch as $index) 
            {
            if ($next) echo ",";
            else $next=true;
            echo $index["Column_name"];
            if ($index["Column_name"]==$index["Key_name"]) echo " UNIQUE";
            if ($index["Key_name"]=="PRIMARY") echo " PRIMARY";
            }
        echo ")\n";
        return $fetch;
        }


    /* make Config Parameter for column entry, second Parameter for extra line for Primekey 
     *
     *     "Updated"      => ["Field" => "Updated", "Type" => "timestamp", "Null" => "", "Key" => "", "Default" => "CURRENT_TIMESTAMP()", "Extra" => "on update current_timestamp()"],
     *
     */

    public function makeTableConfig($entry, $mode="ALTER", $debug=false)
        {
        if ($debug) { echo "makeTableConfig: Mode=$mode "; print_r($entry); echo "\n"; }
        /* default if not in entry parameter field */
        $type = "varchar(255);"; $noNull = ""; $unique=""; $extra=""; $default="";
        $primaryKeyInline = "";
        $primaryKeyCmd    = "";
        $sqlCommand="";
        if (isset($entry["Field"]) )
            {
            $name = $entry["Field"];
            }
        else return (false);                    // Fehler Key Field wird immer benötigt
        if (isset($entry["Type"])) $type = $entry["Type"];
        if (isset($entry["Null"])) 
            {
            if (strtoupper($entry["Null"])=="NO")  $noNull = "NOT NULL";
            elseif (strtoupper($entry["Null"])=="YES") $noNull = "NULL";
            else 
                {
                if ($entry["Key"] != "") $noNull = "NOT NULL";
                elseif (strtoupper($extra)=="AUTO_INCREMENT") $nonull="NOT NULL";
                else 
                    {
                    //$noNull = "NULL";      // default
                    $noNull = "";           // besser gar keine Angabe machen
                    }
                }
            }
        if (isset($entry["Key"]))       // Ein Key ist automatisch NOT NULL
            {
            //echo "makeTableConfig: Key ".$entry["Key"]." gefunden.\n";
            if (strtoupper($entry["Key"])=="PRI") 
                {
                if ($primaryKeyCmd == "") $primaryKeyCmd="ADD PRIMARY KEY ($name";
                else $primaryKeyCmd.=$name;
                $primaryKeyInline="PRIMARY KEY";
                $noNull="NOT NULL";
                }
            elseif (strtoupper($entry["Key"])=="UNI") 
                { 
                $unique="UNIQUE"; 
                $primaryKeyInline="UNIQUE KEY"; 
                $noNull="NOT NULL";
                }
            elseif (strtoupper($entry["Key"])=="") 
                {
                $primaryKeyInline="";
                $unique="NOT UNIQUE";               // das ist nicht der Default
                }
            }
        if ( (isset($entry["Default"])) && ($entry["Default"] != "") )  $default = " DEFAULT ".$entry["Default"];
        if (isset($entry["Extra"])) 
            {
            $extra = $entry["Extra"];
            if (strtoupper($extra)=="AUTO_INCREMENT") $noNull="NOT NULL";
            }
        //echo "          $name $type $noNull $unique $extra,"."\n";
        // MariaDB CREATE [OR REPLACE] [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name ( columnName dataType [NOT NULL | NULL] [DEFAULT default_value | (expression)] [AUTO_INCREMENT] [ZEROFILL] [UNIQUE [KEY] | [PRIMARY] KEY],...);
        if ($mode=="ALTER") $sqlCommand.=" $name $type $noNull $default $extra";                        // Updated timestamp NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp()
        else $sqlCommand.=" $name $type $noNull $default $extra $primaryKeyInline";
        //$sqlCommand.=" $name $type $noNull $primaryKeyInline $unique $extra";
        $result=array();
        $result[$name] = $sqlCommand;
        if ($primaryKeyCmd != "") 
            {
            $primaryKeyCmd.=")";
            $result["KEY"] = $primaryKeyCmd; 
            //echo "Primary Key detected: $primaryKeyCmd\n";
            }
        else $result["KEY"] = "";
        if ($debug) { print_r($result); echo "\n"; }
        return ($result);
        }

    /* verkuerzen des Field Eintrags auf einen Standard Typ */

    public function getFieldType($fieldType)
        {
        $type=explode("(",$fieldType);
        $typeCount=sizeof($type);
        if ($typeCount==0) return "getFieldType: Error, wrong input.\n";
        else return ($type[0]);
        /* zweiter Wert von varchar gibt die Länge an, vorerst hier ignorieren */
        }

    /* compare soll und ist Config 
     * meldet mit einem Array den Status zurück, verwendet makeTableConfig für Analyse Soll und Ist Konfiguration
     *   Status
     *
     *
     */

    public function compareTableConfig($columnSoll,$columnIst,$debug=false)
        {
        $name=$columnSoll["Field"];
        $soll = $this->makeTableConfig($columnSoll);
        $ist = $this->makeTableConfig($columnIst);
        if ($debug) 
            {
            echo "compareTableConfig: Compare Soll with Ist Config:\n";
            echo "  Soll: ".$soll[$name]."   ".$soll["KEY"]."\n";
            echo "  Ist : ".$ist[$name]."    ".$ist["KEY"]."\n";
            }
        $same["Status"]=true;
        if ($columnSoll["Field"] != $columnIst["Field"]) 
            {
            $same["Status"]=false;
            echo "compareTableConfig, Field not the same (Soll/Ist): ".$columnSoll["Field"]." != ".$columnIst["Field"]."\n";
            }
        if ($columnSoll["Null"] != $columnIst["Null"])              /* to remove a NOT NULL Statement from database configuration is tricky zB ALTER TABLE deviceList MODIFY COLUMN  Changed timestamp   ; */
            {
            if (!( ($columnSoll["Null"] == "") && (strtoupper($columnIst["Null"]) == "YES") ))
                {
                $same["Status"]=false;
                echo "compareTableConfig, Null not the same (Soll/Ist): ".$columnSoll["Null"]." != ".$columnIst["Null"]."\n";
                echo "  Soll: ".$soll[$name]."   ".$soll["KEY"]."\n";
                echo "  Ist : ".$ist[$name]."    ".$ist["KEY"]."\n"; 
                $same["Command"]=" MODIFY ".$soll[$name]."   ".$soll["KEY"]." NULL";               // Befehl wird zusätzlich abgearbeitet. Vor dem Standard Befehl.
                }
            }
        if ($columnSoll["Key"] != $columnIst["Key"]) 
            {
            $same["Status"]=false;
            echo "compareTableConfig, Key not the same (Soll/Ist): ".$columnSoll["Key"]." != ".$columnIst["Key"].".\n";
            if (($columnIst["Key"]=="UNI") && ($columnSoll["Key"]=="")) $same["Command"]="DROP INDEX $name";        // wird zu ALTER TABLE tableName DROP INDEX name 
            //if (($columnIst["Key"]=="MUL") && ($columnSoll["Key"]=="")) $same["Command"]="DROP INDEX $name";        // aus dem Index soll eine Tabelle entfernt werden 
            if (($columnIst["Key"]=="") && ($columnSoll["Key"]=="UNI")) $same["Command"]="ADD UNIQUE INDEX ($name)";        // wird zu ALTER TABLE tableName DROP INDEX name 
            }
        if ($columnSoll["Default"] != $columnIst["Default"]) 
            {
            $same["Status"]=false;
            echo "compareTableConfig, Default not the same (Soll/Ist): ".$columnSoll["Default"]." != ".$columnIst["Default"].".\n";
            }
        if ($columnSoll["Extra"] != $columnIst["Extra"]) 
            {
            $same["Status"]=false;
            echo "compareTableConfig, Extra not the same (Soll/Ist): ".$columnSoll["Extra"]." != ".$columnIst["Extra"]."\n";
            }
        if ($columnSoll["Type"] != $columnIst["Type"]) 
            {
            $typeSoll=explode("(",$columnSoll["Type"]);
            $typeIst =explode("(",$columnIst["Type"]);
            if ($typeSoll[0] != $typeIst[0]) 
                {
                echo "compareTableConfig, Type not the same (Soll/Ist): ".$typeSoll[0]." mit ".$typeIst[0]." not equal.\n";
                $same["Status"]=false;
                }
            elseif (isset($typeSoll[1]))
                {
                if ( (strtoupper($typeSoll[0])) != "INT")           // INT nicht überprüfen
                    {
                    if ($typeSoll[1] != $typeIst[1]) 
                        {
                        echo "compareTableConfig, Type not the same: ".$typeSoll[0]."(".$typeSoll[1]." with ".$typeIst[0]."(".$typeIst[1]." is not the same.\n";
                        $same["Status"]=false;    
                        }
                    }
                else echo "     Type ".$typeSoll[0]."\n";
                }
            //else echo "dont know, probably equal.\n";
            }
        if ($same["Status"])
            {
            //echo "beide gleich\n";
            }
        else 
            {
            //echo "Compare Soll with Ist:\n"; print_r($columnSoll); print_r($columnIst);
            }
        return $same;
        }


    /* create one Table entry 
     *
     *
     */

    public function createTableEntryValues($table, $keys, $vars, $deviceList, $advise, $config, $debug)
        {
        if ($debug) echo " sqlHandle::createTableEntryValues($table, ... ):\n"; 
        //print_r($config); echo "\n";
        $columns=""; $values=""; $next=false;
        foreach ($config as $column => $entry)
            {
            if ($debug) echo "       ".str_pad($column,25)."    ";
            if ($column==$advise["index"]) 
                {
                if ($debug) echo "Index";              // Index
                }
            elseif ($column == $keys[0])            // Key
                {
                if ($debug) echo "Key (".$keys[0].") : ".$vars[0];
                if ($next) {$columns.=","; $values.=",";} else  $next=true;
                $columns.= $keys[0];            // auch == columnValue, muss aber nicht
                $values.="'".$vars[0]."'";
                }
            else
                {
                if ($debug) echo json_encode($entry)." : ";
                $value=false;
                if (isset($deviceList[$column])) $value=$deviceList[$column];
                if (isset($deviceList[strtoupper($column)])) $value=$deviceList[strtoupper($column)];
                if ($value !== false)
                    {
                    if ($next) {$columns.=","; $values.=",";} else  $next=true;
                    $columns.=$column;
                    if (is_array($value)) $value = json_encode($value);
                    $values.="'".$value."'";
                    if ($debug) echo $value;
                    }
                //echo "\n";
                }
            if ($debug) echo "\n";
            }
        $sqlCommand = "INSERT INTO $table ($columns) VALUES ($values);";
        if ($debug) echo " >SQL Command : $sqlCommand\n";    
        //print_r($entry);
        echo " sqlHandle::createTableEntryValues SQL Command : \"$sqlCommand\"   \n";                       
        $result=$this->command($sqlCommand,$debug); 
        echo " Ergebnis $result   \n";
        }

    /* upate one Table entry row, Zeile wird mit sql WHERE statement eingeschränkt
     * wird nur von sqlOperate:updateTableEntriesValues aufgerufen
     * 
     *      table           Name der Tabelle
     *      sql             WHERE statement: SELECT * FROM table sql
     *      text            die Erläuterung dazu für die Ausgabe als echo
     *      deviceList      Werte, nur die Werte updaten für die eine Spalte vorhanden ist [column]
     *      advise          nicht verwendet, für Audit trail
     *      config          configuartion für dies tabelle mit den einzelnen Spalten
     *      updated
     *
     * zuerst alle Spalten der ersten Zeile mit einem SELECT * WHERE holen.
     * dann die config der Spalten einzeln durchgehen, wenn ein Wert in der devicelist hinterlegt ist
     *
     */

    public function updateTableEntryValues($table, $sql, $text, $deviceList, $advise, $config, $updated=true, $debug)
        {
        $update=false;
        if ( ($debug) && true)         // sonst zuviele Eintraege 
            {
            echo "updateTableEntryValues($table, $sql, $text, ...)\n";
            print_r($deviceList); 
            //print_r($config);
            }

        $sqlCommand = "SELECT * FROM $table $sql;";
        //if ($debug) echo "updateTableEntryValues: Update, einen Wert gefunden. Abfrage SQL : $sqlCommand.\n";       // nur ausgeben, wenn wirklich ein update
        $result1=$this->query($sqlCommand);
        $row = $result1->fetch()[0];
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        //print_r($row);
        $next=false; 
        $sqlCommand = "UPDATE $table SET ";
        $textUpd = "update $text \n";
        //print_r($config);
        $vars="";
        foreach ($config as $column => $entry)          // alle Spalten aus der Configuration durchgehen
            {
            $textUpd .= "   Look for ".str_pad($column,32);
            if (isset($deviceList[$column])) 
                {
                //print_r($config[$column]);         // vor dem Vergleich, check Konfiguration  Type zurückgeben
                $fieldType = $this->getFieldType($config[$column]["Type"]);
                if ($fieldType=="varchar")           // den String vorher von unnötigen und schwierig zu speicherndem Balast befreien
                    {
                    $valueSollComp=$this->escapeChars($deviceList[$column]);            // ein array wird auch gleich mit json encoded
                    $valueIstComp=$this->escapeChars($row[$column]);
                    }
                else 
                    {
                    if (is_array($deviceList[$column])) $valueSollComp=json_encode($deviceList[$column]);           // in der devicelist als array und in der Tabelle als json
                    else $valueSollComp=$deviceList[$column];
                    $valueIstComp=$row[$column];
                    }
                if (is_array($deviceList[$column])) $valueSoll=json_encode($deviceList[$column]);           // in der devicelist als array und in der Tabelle als json
                else $valueSoll=$deviceList[$column];
                if ($valueIstComp == $valueSollComp) $textUpd .= "    available as $fieldType";
                else
                    {
                    $update=true; $vars.="  ".$column;
                    $textUpd .= "    available as $fieldType, update needed. nicht gleich sind:\n";
                    $textUpd .= "IST :".$row[$column]." | $valueIstComp\n";
                    $textUpd .= "SOLL:".$valueSoll." | $valueSollComp\n"; 
                    if ($next) $sqlCommand .= ",";
                    else $next=true; 
                    $sqlCommand .= "$column = '".$valueSoll."' ";
                    }
                }
            $textUpd .= "\n";
            }
        if ($debug>1)
            {
            if ($update) echo "updateTableValue: Update, einen Wert gefunden. Abfrage SQL : $sqlCommand.\n";
            echo $textUpd;
            }

        if ($update)        // nur wenn Update wirklich erforderlich
            {
            if ($updated) $sqlCommand .= ",changed = '".date("Y-m-d H:i:s")."' ";
            $sqlCommand .= $sql;        // add WHERE statement für richtige Zeile
            
            //print_r($advise);                         // wenn ein Audit trail gefordert wird, hier programmieren
            //if (isset($advise["change"])) $sqlCommand .= ",".$advise["change"]." = 'CURRENT_TIMESTAMP()'";

            $sqlCommand .= ";";
            if ($debug) 
                {
                echo "     Unterschiedlicher Eintrag in den Spalten '$vars' gefunden. Update notwendig:\n";
                echo $textUpd." >SQL Command : $sqlCommand\n";    
                }
            $result=$this->command($sqlCommand);
            }
        return ($update);
        }

    /* Varchar Sonderzeichen entfernen, escapen */

    private function escapeChars($input)
        {
        if (is_array($input)) $input=json_encode($input); 
        //if (strpos($input,'\\') !== false)  echo "Sonderzeichen entfernen, sonst kein Vergleich möglich: $input.\n";
        $valueSoll=str_replace('\"','"',$input);
        $valueSoll=str_replace('\n','',$valueSoll);
        $valueSoll=str_replace('\u','u',$valueSoll);
        return ($valueSoll);
        }


    }   // ende class 


/* das erste Mal eine Class als Return Variable/Struktur zu verwenden
 * Vorteil. es können auch Routinen zur weiteren Verarbeitung mitgegeben werden.
 * das Ergebnis wird bei >>return new sqlReturn(Ergebnis) zurück gegeben.
 * Aufruf in query:
 *        $result = $this->sqlHandle->query($query);
 *        return new sqlReturn($result);
 *
 *  __construct     Ergebnis in der Klasse speichern und ausgeben
 *  result          gespeichertes Ergebnis genauso ausgeben 
 *  num_rows        Anzahl Zeilen der Query, nur als Ergebnis
 *
 *
 */
    
class sqlReturn
    {

    public $result;

    /* Ergebnis abspeichern */

    public function __construct($result)
        {
        $this->result = $result;
        return ($this->result);
        }

    /* Ergebnis unverändert ausgeben */

    public function result()
        {
        return ($this->result);
        }

    /* php sql Funktion num_rows verwenden */

    public function num_rows()
        {
        return $this->result->num_rows;
        }

    /* fetch, alle Ergebnisse, es können sehr sehr viel sein, in einem Array abspeichern und übergeben */

    public function fetch()
        {    
        $user_arr = array();      
        while ($row = $this->result->fetch_assoc())                         // Cycle through results
            {
            $user_arr[] = $row;
            }
        //print_r($user_arr);
        return $user_arr;
        }

    /* besonderes fetch für showTables */

    public function fetchShowTables($debug=false)
        {
        $result = array();
        $input = $this->fetch();
        $i=0;
        foreach ($input as $entry) 
            {
            if ($debug) echo str_pad($i++,3).$entry["Tables_in_ipsymcon"]."\n";
            $result[$entry["Tables_in_ipsymcon"]]=true;
            }
        return $result;
        }

    /* besonderes fetch für describe Tables, die Struktur des tables wird als array rekonstrukturiert */

    public function fetchDescribe($debug=false)
        {
        //$debug=true;
        $result = array();
        $input = $this->fetch();
        $i=0;
        if ($debug) echo "fetchDeschribe:\n";
        foreach ($input as $entry) 
            {
            if ($debug) echo str_pad($i++,3).$entry["Field"]."=>".json_encode($entry)."\n";
            foreach ($entry as $index => $field) 
                {
                switch (strtoupper($index))
                    {
                    case "DEFAULT":
                        if ( (strlen($field)>1) && (is_numeric(substr($field,0,1))) ) $entry[$index]="\"$field\"";
                        break;
                    default:
                        break;
                    }
                if ($debug) echo "       $index=>".$entry[$index]."\n";
                }
            $result[$entry["Field"]]=$entry;
            }
        return $result;
        }

    /* besonderes fetch für count */

    public function fetchCount($debug=false)
        {
        $input = $this->fetch();
        $i=0;
        foreach ($input as $entry) 
            {
            foreach ($entry as $value)
                {
                if ($debug) echo str_pad($i++,3).$value."\n";
                }
            }
        return $value;              // letzter gefundener Wert
        }

   /* besonderes fetch für query select
    * alle Ergebnisse, es können sehr sehr viel sein, nicht zusätzlich abspeichern 
    * sondern als Tabelle übergeben 
    */

    public function fetchSelect($format=false,$background="")
        { 
        $id=random_int(10,99);       
        $user_arr = array();      
        $columns=array();
        $printHtml="";
        $printHtml.="<style>\n"; 
        if ($background=="") $printHtml.=".fetchS$id table,td {align:center;border:1px solid white;border-collapse:collapse;background-color:darkblue;color:white}\n";
        else $printHtml.=".fetchS$id table,td {align:center;border:1px solid white;border-collapse:collapse;background-color:$background;color:white}\n";
        $printHtml.=".fetchS$id table    {table-layout: fixed; width: 100%; }\n";
        //$printHtml.='.sturdy td:nth-child(1) { width: 70%; }';        // fixe Breite der Zellen in %
        //$printHtml.='.sturdy td:nth-child(2) { width: 10%; }';
        //$printHtml.='.sturdy td:nth-child(3) { width: 20%; }';
        $printHtml.="</style>\n";        
        $printHtml.='<table class="fetchS'.$id.'">';
        $next=false;
        while ($row = $this->result->fetch_assoc())                         // Cycle through results
            {
            if ($format === false) $user_arr[] = $row;
            if (strtoupper($format)=="HTML")
                {
                if ($next)
                    {
                    $printHtml.='<tr>';
                    foreach ($columns as $column)
                        {
                        $printHtml.="<td>".$row[$column]."</td>";    
                        }
                    $printHtml.='</tr>';
                    }
                else    // erste Zeile
                    {
                    $next = true;
                    $printHtml.='<tr>';
                    foreach ($row as $column => $value) 
                        {
                        $printHtml.="<td>$column</td>";    
                        $columns[]=$column;
                        }
                    $printHtml.='</tr><tr>';
                    foreach ($columns as $column)
                        {
                        $printHtml.="<td>".$row[$column]."</td>";    
                        }
                    $printHtml.='</tr>';
                    //print_r($columns);
                    }
                }
            }
        //print_r($user_arr);
        if ($format === false) return $user_arr;
        if (strtoupper($format)=="HTML") 
            {
            $printHtml.='</table>';
            return $printHtml;
            }
        }

    }

/*************************************************************************************************************
 *
 *
 *
 ********************************************************************************/

/* getfromDatabase
 *
 *  useDatabase ipsymcon, getServerGatewayID als filter
 *  registers extended with devicelist,instances,topologies
 *
 *  als Return die Zeilen der Datenbank auf die der Filter zutrifft
 *  wenn return false dann ist diue Datenbank nicht erreichbar/vorhanden
 *
 * mit Alternative gesetzt eine abgeänderte SQL Abfrage starten
 *          ohne INNER JOIN componentModules ON registers.componentModuleID=componentModules.componentModuleID
 *          dafür Abfrage welche registers.componentModuleID noch NULL sind
 *
 * Sonderfälle für typereg
 *  OID
 *
 *
 *  COID
 *
 *
 *  false       nur Filter auf ServerGatewayID
 *
 *
 ********/
 
function getfromDatabase($typereg=false,$register=false,$alternative=false,$debug=false)
    {
    if ($debug) echo "\n<br>getfromDatabase($typereg,$register,$alternative,$debug) aufgerufen, ";

    $sqlHandle = new sqlHandle();           // default MySQL Instanz
    if ($sqlHandle->available) 
        {
        $sqlHandle->useDatabase("ipsymcon");    // USE DATABASE ipsymcon
        $sql_serverGateways = new sql_serverGateways();
        $myServerGatewayID=$sql_serverGateways->getWhere(IPS_GetName(0));
        /*
        $sql = "SELECT deviceList.Name,deviceList.Type,instances.portID,instances.Name AS Portname,instances.TYPEDEV 
                    FROM deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID";
        $sql = "SELECT deviceList.Name,deviceList.Information,instances.portID,instances.OID,instances.Name AS Portname,registers.TYPEREG,registers.Configuration 
                    FROM deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    WHERE TYPEREG='TYPE_METER_TEMPERATURE';";
        */

        //$filter="WHERE TYPEREG='TYPE_METER_TEMPERATURE'";
        //$filter="WHERE TYPEREG='TYPE_METER_HUMIDITY'";
        //$filter="";
        //$register="HUMIDITY";
        $regfilter=true;
        if ($typereg != false) 
            {
            if ( (strtoupper($typereg)=="OID") && ($register !== false) )
                {
                $regfilter=false;
                $filter="WHERE instances.OID='$register' AND serverGatewayID='$myServerGatewayID'";
                }
            elseif ( (strtoupper($typereg)=="COID") && ($register !== false) )
                {
                $regfilter=false;
                $filter="WHERE valuesOnRegs.COID='$register' AND serverGatewayID='$myServerGatewayID'";
                }                
            else $filter="WHERE TYPEREG='$typereg' AND serverGatewayID='$myServerGatewayID'";
            }
        else $filter="WHERE serverGatewayID='$myServerGatewayID'";
        //$filter="WHERE Name='ArbeitszimmerThermostat'";

        if ($alternative)       // look for IS NULL
            {
            $sql = "SELECT registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                            registers.componentModuleID,registers.TYPEREG,registers.Configuration 
                    FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    INNER JOIN topologies ON deviceList.placeID=topologies.topologyID
                    WHERE registers.componentModuleID IS NULL AND serverGatewayID='$myServerGatewayID';";
            $sql1=$sql;
            }
        elseif (strtoupper($typereg)=="COID")           // COID sucht in valuesOnRegs
            {
            $sql = "SELECT valuesOnRegs.COID,valuesOnRegs.TypeRegKey,registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                        registers.componentModuleID,
                        registers.TYPEREG,registers.Configuration,deviceList.serverGatewayID 
                 FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                 INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                 INNER JOIN topologies ON deviceList.placeID=topologies.topologyID
                 INNER JOIN valuesOnRegs ON registers.registerID=valuesOnRegs.registerID";
            $sql1=$sql.";";     
            $sql=$sql." ".$filter.";";                
            }
        elseif ($typereg===false)                            // Generalabfrage
            {
            $sql = "SELECT registers.registerID,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                            registers.TYPEREG,registers.Configuration 
                    FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    $filter;";
            $sql1=$sql;
            }
        else // alle anderen in registers
            {
            $sql = "SELECT registers.registerID,topologies.Name AS Ort,deviceList.Name,instances.portID,instances.OID,deviceList.Type,deviceList.SubType,instances.Name AS Portname,
                            componentModules.componentName,componentModules.moduleName,registers.TYPEREG,registers.Configuration 
                    FROM (deviceList INNER JOIN instances ON deviceList.deviceID=instances.deviceID)
                    INNER JOIN registers ON deviceList.deviceID=registers.deviceID AND instances.portID=registers.portID
                    INNER JOIN topologies ON deviceList.placeID=topologies.topologyID
                    INNER JOIN componentModules ON registers.componentModuleID=componentModules.componentModuleID
                    $filter;";
            $sql1=$sql;
            }
                    
        if ($debug) echo "Values from MariaDB Database registers extended with devicelist,instances,topologies:\nSQL Abfrage: $sql\n<br>"; 
        $result1=$sqlHandle->query($sql);
        $fetch = $result1->fetch();
        $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
        //print_r($fetch);
        if ($debug)
            {                               // fetch zusaetzlich um die Daten zu dursuchen
            $result1=$sqlHandle->query($sql1);
            $tableHTML = $result1->fetchSelect("html","darkblue");
            $result1->result->close();                      // erst am Ende den vielen Speicher freigeben, sonst ist mysqli_result bereits weg !
            echo $tableHTML;
            }
        if ($regfilter)         // Suche nach COID oder OID verwendet den WHERE Filter
            {
            $singleRows=array();
            if ($debug) echo "\nZusaetzlicher Filter für Register, damit kann die OID des Registers festgelegt werden.:\n";
            if ($register != false)
                {
                foreach ($fetch as $singleRow)
                    {
                    if (getCOIDforRegisterID($singleRow,$register)) $singleRows[]=$singleRow;
                    }
                //return $filter;
                return $singleRows;
                }
            else return ($fetch);           // alles zurückgeben, kein filter angefragt
            }
        else return ($fetch);           // alles zurückgeben, kein filter angefragt
        }
    else return (false);        // keine MySQL Datenbank angelegt
    }

/* Übergabe einer Tabellenzeile mit zumindest den Spalten "OID" und "Configuration"
 * Input ist der OID Wert der Instanz, damit kann die Configuration ausgelesen werden und die Index Value Pärchen ausgelesen werden
 * Für die Ausgabe der Zeile ist zusätzlich Name portID Portname TYPEREG erforderlich
 * Es muss kein Register übergeben werden, dann alle Register in der Konfiguration bearbeiten und zurückgeben
 *
 * Ergebnis ist die erweiterte singlerow und als return die gesammelten singlerows
 *
 */

function getCOIDforRegisterID(&$singleRow, $register=false, $debug=false)
    {
    if ($debug) echo "getCOIDforRegisterID(".json_encode($singleRow)."\n";
    $found=false;
    $filter=array();
    $singleRows=array();
    $childrens=@IPS_GetChildrenIDs($singleRow["OID"]);
    if ($childrens!==false)
        {
        //print_R($childrens); 
        $configuration=json_decode($singleRow["Configuration"],true);
        if ($register !== false)
            {
            //echo "      $register in ".$singleRow["Configuration"]."\n";print_r($configuration);
            if (isset($configuration[$register]))
                {
                $needle = $configuration[$register];
                if ($debug) echo str_pad($singleRow["Name"],30).str_pad($singleRow["portID"],8).str_pad($singleRow["OID"],8).str_pad($singleRow["Portname"],50).str_pad($singleRow["TYPEREG"],20).str_pad($singleRow["Configuration"],70); 
                foreach ($childrens as $children) 
                    {
                    if (IPS_GetName($children)==$needle) 
                        {
                        //echo "Temperatur Register ist : $children (".IPS_GetName($children).") = ".GetValueFormatted($children).".\n";     
                        if ($debug) echo "$children ".GetValueIfFormatted($children);
                        $filter[$children]=1;
                        $singleRow["COID"]=$children;
                        //$singleRow["TypeRegKey"]=$needle;         // needle kenn ich eh
                        $singleRow["TypeRegKey"]=$register;
                        $found=true;
                        $singleRows[$children]=$singleRow;                        
                        }
                    }
                if ($debug) echo "\n";
                }
            return $found;
            }
        else                    //register ist false, alle zurückgeben
            {
            //echo "getCOIDforRegisterID, alle KEYS bearbeiten:\n"; print_R($configuration);
            echo "      ".str_pad($singleRow["Name"],30).str_pad($singleRow["portID"],8).str_pad($singleRow["OID"],8).str_pad($singleRow["Portname"],50).str_pad($singleRow["TYPEREG"],20).str_pad($singleRow["Configuration"],70); 
            foreach ($configuration as $key => $needle)         // die Keys der Reihe nach suchen
                {
                foreach ($childrens as $children) 
                    {
                    if (IPS_GetName($children)==$needle) 
                        {
                        //echo "Temperatur Register ist : $children (".IPS_GetName($children).") = ".GetValueFormatted($children).".\n";     
                        if ($debug) echo str_pad("$children ".GetValueIfFormatted($children),30);
                        $filter[$children]=1;
                        if ($found==false)                 // nur den ersten Wert übernehmen
                            {
                            $singleRow["COID"]=$children;
                            //$singleRow["TypeRegKey"]=$needle;
                            $singleRow["TypeRegKey"]=$key;              // wie das Register heisst ist eigentlich egal, der Key soll gleich sein
                            $found=true;
                            }
                        $singleRows[$children]=$singleRow;
                        $singleRows[$children]["COID"]=$children;
                        //$singleRows[$children]["TypeRegKey"]=$needle;                        
                        $singleRows[$children]["TypeRegKey"]=$key;                        
                        }
                    }
                }
            echo "\n";
            }
        //echo "fertig\n";
        }
    else
        {
        echo "Fehler, ".$singleRow["OID"]." (".$singleRow["Name"].") aus der Datenbank nicht mehr als Objekt vorhanden:\n";
        //if ($debug) print_R($singleRow);
        }
    if ($found) 
        {
        if ($debug) echo "\n";
        return $singleRows;
        }
    else return $found;        
    }


?>