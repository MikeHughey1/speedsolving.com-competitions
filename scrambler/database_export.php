<?php

    require_once '../newconnect.php';
    require_once '../statFunctions.php';

    $week = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

    $filename = "SS_".gmdate("omd", strtotime('-1 day')).".sql";
    $exportFile = fopen($filename, "wb");
    fwrite($exportFile, "\xEF\xBB\xBF");
    fwrite($exportFile, "--\n");
    fwrite($exportFile, "-- ".$filename."\n");
    fwrite($exportFile, "-- Also read the README.md\n");
    fwrite($exportFile, "--\n\n");
    
    $tables = $mysqli->query("SHOW TABLES");
    while ($row = $tables->fetch_row()) {
        fwrite($exportFile, "\n");
        foreach ($row as $name => $value) {
            $table = $value;
        }
        if ($table == "weeklyCorrections") {
            // Skip this one; only used for maintenance, and will probably be dropped soon
            continue;
        }
        fwrite($exportFile, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($exportFile, "CREATE TABLE `$table` (\n");
        $descriptions = $mysqli->query("DESCRIBE ".$table);
        $numColumns = $descriptions->num_rows;
        $columnNumber = 0;
        while ($row2 = $descriptions->fetch_assoc()) {
            foreach ($row2 as $name => $value) {
                if ($name == 'Field') {
                    fwrite($exportFile, "  `$value`");
                } elseif ($name == 'Type') {
                    fwrite($exportFile, " $value");
                } elseif ($name == 'Null' && value == 'NO') {
                    fwrite($exportFile, " NOT NULL");
                } elseif ($name == 'Default' && $value != '') {
                    fwrite($exportFile, " DEFAULT '$value'");
                }
            }
            if (++$columnNumber < $numColumns) {
                fwrite($exportFile, ",");
            }
            fwrite($exportFile, "\n");
        }
        fwrite($exportFile, ");\n\n");
        
        $condition = "";
        fwrite($exportFile, "INSERT INTO `$table` VALUES\n");
        if ($table == "weeklyResults") {
            $condition = " WHERE yearId = 2007";
        }
        $values = $mysqli->query("SELECT * FROM ".$table.$condition);
        $numRows = $values->num_rows;
        echo "rows:".$numRows."<br>";
        $rowNumber = 0;
        while ($row3 = $values->fetch_row()) {
            fwrite($exportFile, "(");
            for ($currentColumn = 0; $currentColumn < $numColumns; ++$currentColumn) {
                $value = $mysqli->real_escape_string($row3[$currentColumn]);
                fwrite($exportFile, "'".$value."'");
                if ($currentColumn < $numColumns - 1) {
                    fwrite($exportFile, ", ");
                } else if (++$rowNumber < $numRows) {
                    fwrite($exportFile, "),\n");
                } else if ($table != "weeklyResults") {
                    fwrite($exportFile, ");\n\n");
                } else {
                    fwrite($exportFile, "),\n");
                }
            }
        }
        if ($table == "weeklyResults") {
            for ($year = 2008; $year < 2019; ++$year) {
                $condition = " WHERE yearId = $year";
                $values = $mysqli->query("SELECT * FROM ".$table.$condition);
                $numRows = $values->num_rows;
                echo "rows:".$numRows."<br>";
                $rowNumber = 0;
                while ($row3 = $values->fetch_row()) {
                    fwrite($exportFile, "(");
                    for ($currentColumn = 0; $currentColumn < $numColumns; ++$currentColumn) {
                        $value = $mysqli->real_escape_string($row3[$currentColumn]);
                        fwrite($exportFile, "'".$value."'");
                        if ($currentColumn < $numColumns - 1) {
                            fwrite($exportFile, ", ");
                        } else if (++$rowNumber == $numRows && $year == 2018) {
                            fwrite($exportFile, ");\n\n");
                        } else if ($rowNumber <= $numRows) {
                            fwrite($exportFile, "),\n");
                        }
                    }
                }
            }
        }
     }
    fclose($exportFile);
