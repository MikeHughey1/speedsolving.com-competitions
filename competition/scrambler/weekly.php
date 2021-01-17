<!DOCTYPE html>
<html>
<head>
<title>Competition Scramble Importer</title>
</head>

<?php
require_once 'head.php';

$weekNo = $_GET['week'];
$yearNo = $_GET['year'];

if (!isset($weekNo) || !isset($yearNo)) {
    echo "Must specify week and year explicitly!";
    exit;
}

if (false) { // Skip check; only do this if uploadScrambles is disabled!
// check if there already are scrambles for this week
// if there are print them and quit
$query = "select e.id, e.eventName, s.scramble from events e, scrambles s " .
      " where e.id = s.eventId and weekId = $weekNo and yearId = $yearNo " .
      " order by e.id" ;

$result = $mysqli->query($query);
$row = $result->num_rows;

if ($row > 10) {
   print "<h1><b>Contest already generated for week $weekNo!!</h1><br>" .
   "Events and scrambles:</b><br><br>";
   if ($result) 
      while ($row = $result->fetch_array(MYSQLI_NUM)) {
         print "[B]" . $row[1] . "[/B]<br>";
         print $row[2] . "<br>";
      }
   else 
      print "SQL failed<br>";
   return;
}}
?>

<body>
<h1>Competition Scramble Importer</h1>

<div> <!-- display:none -->
<br><br>
<form method='post' id='scrambles' action='uploadScrambles.php'>
<button onclick="run();">Generate and Upload</button><br>
<textarea id="wcaScrambles" style="height:400px;width:1500px"></textarea>
<div id='output'><div id='status'></div></div>
<input id='week' name='week' value='<?php print $weekNo; ?>' />
<input id='year' name='year' value='<?php print $yearNo; ?>' />
</form>
</div>
<div id='showScrambles'></div>
<hr />

//  generate scrambles for this week
<script src="scramble_kilo.js?version=1"></script>
<script src="scramble_redi.js?version=1"></script>
<script src="scramble_mpyram.js?version=1"></script>
<script src="scramble_fto.js?version=1"></script>
<script src="mersennetwister.js"></script> <!-- randomness -->
<script>
// list of eventId of all events in the database and then also in the forum weekly comps. 
/* 1=222 2=333 3=444 4=555 5=666 6=777
   7=222bld 8=333bld 9=444bld 10=555bld 11=666bld 12=777bld
   13=333multi 14=333oh 15=333feet 16=333mts 17=333fmc
   18=234relay 19=2345relay 20=23456relay 21=234567relay
   22=clock 23=mega 24=pyra 25=sq1 26=skewb 27=kilo 28=MiniGuildford
   29=magic 30=mmagic 31=snake 32=444fmc 33=redi 34=mpyra
   35=fifteen 36=speedfmc 37=mirror 38=curvy 39=fto
 */

function run() {
   randomness = new MersenneTwisterObject(new Date().getTime());
   
   //generate output boxes for each event
   for(i=1; i <= 39; i++) {
       if (i > 28 && i < 33) {
           continue;
       }
      evId = "evId" + i;
      document.getElementById("output").innerHTML += 
         "<input id='" + evId + "' name='" + evId + "'  />";
   } 
   // console.log("created ouput boxes");
   // console.log(document.getElementById("output").innerHTML);

   // Read in the WCA scrambles
   var compName = "<?php echo $yearNo."-".$weekNo."-"; ?>";
   var wcaScrambles = document.getElementById("wcaScrambles").value.split("\n");
   for (var i = 0; i < wcaScrambles.length; i += 2) {
       if (wcaScrambles[i].substr(0,compName.length) != compName) {
           return;
       }
       evId = "evId" + wcaScrambles[i].substr(compName.length);
       document.getElementById(evId).value = wcaScrambles[i + 1];
   }
   
   // Generate the javascript scrambles
   document.getElementById("evId27").value = "1.&nbsp;&nbsp;&nbsp;" + scramblers["kilo"].getRandomScramble().scramble + "\<br \/\>"
                                           + "2.&nbsp;&nbsp;&nbsp;" + scramblers["kilo"].getRandomScramble().scramble + "\<br \/\>"
                                           + "3.&nbsp;&nbsp;&nbsp;" + scramblers["kilo"].getRandomScramble().scramble + "\<br \/\>"
                                           + "4.&nbsp;&nbsp;&nbsp;" + scramblers["kilo"].getRandomScramble().scramble + "\<br \/\>"
                                           + "5.&nbsp;&nbsp;&nbsp;" + scramblers["kilo"].getRandomScramble().scramble + "\<br \/\>\n";
   document.getElementById("evId33").value = "1.&nbsp;&nbsp;&nbsp;" + scramblers["redi"].getRandomScramble().scramble + "\<br \/\>"
                                           + "2.&nbsp;&nbsp;&nbsp;" + scramblers["redi"].getRandomScramble().scramble + "\<br \/\>"
                                           + "3.&nbsp;&nbsp;&nbsp;" + scramblers["redi"].getRandomScramble().scramble + "\<br \/\>"
                                           + "4.&nbsp;&nbsp;&nbsp;" + scramblers["redi"].getRandomScramble().scramble + "\<br \/\>"
                                           + "5.&nbsp;&nbsp;&nbsp;" + scramblers["redi"].getRandomScramble().scramble + "\<br \/\>\n";
   document.getElementById("evId34").value = "1.&nbsp;&nbsp;&nbsp;" + scramblers["mpyra"].getRandomScramble().scramble + "\<br \/\>"
                                           + "2.&nbsp;&nbsp;&nbsp;" + scramblers["mpyra"].getRandomScramble().scramble + "\<br \/\>"
                                           + "3.&nbsp;&nbsp;&nbsp;" + scramblers["mpyra"].getRandomScramble().scramble + "\<br \/\>"
                                           + "4.&nbsp;&nbsp;&nbsp;" + scramblers["mpyra"].getRandomScramble().scramble + "\<br \/\>"
                                           + "5.&nbsp;&nbsp;&nbsp;" + scramblers["mpyra"].getRandomScramble().scramble + "\<br \/\>\n";
   document.getElementById("evId39").value = "1.&nbsp;&nbsp;&nbsp;" + scramblers["fto"].getRandomScramble().scramble + "\<br \/\>"
                                           + "2.&nbsp;&nbsp;&nbsp;" + scramblers["fto"].getRandomScramble().scramble + "\<br \/\>"
                                           + "3.&nbsp;&nbsp;&nbsp;" + scramblers["fto"].getRandomScramble().scramble + "\<br \/\>"
                                           + "4.&nbsp;&nbsp;&nbsp;" + scramblers["fto"].getRandomScramble().scramble + "\<br \/\>"
                                           + "5.&nbsp;&nbsp;&nbsp;" + scramblers["fto"].getRandomScramble().scramble + "\<br \/\>\n";

   //SEND scrambles to server
   document.forms["scrambles"].submit(); // this will send the script to the PHP script that sends it server and checks for duplicates etc.
}

</script>
</body>
</html>
