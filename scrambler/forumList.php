
<?php
	// Initialize variables
   $weekNo = $_GET['week'];
   $yearNo = $_GET['year'];
   // varaiables for bolding text in the forum
   $patterns = [];
   $patterns[0] = '/OH\./';
   $patterns[1] = '/Cl\./';
   $patterns[2] = '/Me\./';
   $patterns[3] = '/Py\./';
   $patterns[4] = '/Sk\./';
   $patterns[5] = '/Sq\./';
   $numPat = "/\d+\./";
   $nbsp = "&nbsp;&nbsp;&nbsp;";
   $replacements = [];
   $replacements[0] = '[b]OH. [/b]';
   $replacements[1] = '[b]Clock. [/b]';
   $replacements[2] = '[b]Mega. [/b]';
   $replacements[3] = '[b]Pyra. [/b]';
   $replacements[4] = '[b]Skewb. [/b]';
   $replacements[5] = '[b]Square-1. [/b]';
   $numRep = '[b]$0 [/b]';
   
	// Connect to Database
   print "<pre>";
   require_once "../newconnect.php";
   require_once "forumText.txt";
   print "</pre>";
   // read events as a text file to array
   $evLines = file('eventText.txt');
   // var_dump($evLines);
?>
   <title>Showing scrambles for weekly 
   <?php print $weekNo . " - " . $yearNo; ?>
   </title>
<?php
	// Find Scrambles in database
   for ($ei = 1; $ei < 29; $ei++) {
      $query  = "SELECT scramble FROM scrambles WHERE eventid ='$ei' ";
      $query .= " and weekId='$weekNo' AND yearId='$yearNo' ";
      $scramble = $mysqli->query($query)->fetch_row();
      // var_dump($scramble);
      
      // Print Scrambles 
      print $evLines[$ei-1] . "<br>";
      foreach ($scramble as $s) {
         // insert bolds and remove 3x&nbsp;
         $s = preg_replace($numPat, $numRep, $s);
         $s = str_replace($nbsp, "", $s);
         // for Mini G, change ev-names too)
         if ($ei == 28)
            $s = preg_replace($patterns, $replacements, $s);
         print $s . "<br>";
         // var_dump($s);
      }
   }
?>
