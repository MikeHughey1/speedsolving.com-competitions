<?php
    require_once 'head.php';

    $week = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

    $filename = "SS_".date("omd").".sql";
    
    $exportFile = fopen($filename, "wb");
    fwrite($exportFile, "\xEF\xBB\xBF");
    fwrite($exportFile, "-- ".date(DATE_ATOM)."\n");
    fwrite($exportFile, "-- ".$filename."\n");
    fwrite($exportFile, "-- Also read the README.md\n");
    fwrite($exportFile, "--\n\n");
    
    $tables = $mysqli->query("SHOW TABLES");
    while ($row = $tables->fetch_row()) {
        fwrite($exportFile, "\n");
        foreach ($row as $name => $value) {
            $table = $value;
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
                } elseif ($name == 'Null' && $value == 'NO') {
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
        fwrite($exportFile, ") COLLATE utf8mb4_unicode_ci;\n\n");
        
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
                    fwrite($exportFile, ",");
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
            for ($year = 2008; $year <= get_current_year(); ++$year) {
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
                            fwrite($exportFile, ",");
                        } else if (++$rowNumber == $numRows && $year == get_current_year()) {
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

    /*
     * Decided not to zip on the server because it takes extra processing time - it's less stressful on the system to download the whole file to my server, zip it there, then upload it back.
    $zip = new ZipArchive();
    if (($code = $zip->open("./".$filename.".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== TRUE) {
        exit("cannot open ./".$filename.".zip\ncode".$code."\n");
    }
    $zip->addFile($filename);
    $zip->close();
    */