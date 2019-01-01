<?php
require_once '../newconnect.php';

$weekNo = $_GET['week'];
$yearNo = $_GET['year'];

if (!isset($weekNo)) exit;
if (!isset($yearNo)) $yearNo = 2019;

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
}

//  generate scrambles for this week
?>
<html>
<meta charset="UTF-8"> 
<title>speedsolving scramble generator</title>
<script src="scramble_222.js"></script>
<script src="scramble_333.js"></script>
<script src="scramble_NNN.js"></script>
<script src="scramble_pyram.js"></script>
<script src="scramble_minx.js"></script>
<script src="scramble_sq1.js"></script>
<script src="scramble_clock.js"></script>
<script src="skewb_solver.js"></script>
<script src="mersennetwister.js"></script> <!-- randomness -->

<?php require 'rediscrambler.js'; ?>
<script>
// list of eventId of all events in the database and then also in the forum weekly comps. 
/* 1=222 2=333 3=444 4=555 5=666 6=777
   7=222bld 8=333bld 9=444bld 10=555bld 11=666bld 12=777bld 13=333multi
   14=333oh 15=333feet 16=333mts 17=333fmc
   18=234relay 19=2345relay 20=23456relay 21=234567relay
   22=clock 23=mega 24=pyra 25=sq1 26=skewb 27=kilo 28=MiniGuildford
   29=magic 30=mmagic 31=snake 32=444fmc 33=redi 34=mpyra
 */

// stolen from http://www.ozoneasylum.com/5782
function stripHTML(text) {
   return text.replace(/<[^>]*>/g, "");
} 

function randInt(n)
// generates a random number (int) in [0, n-1]
{
   return Math.floor(Math.random() * n);
}

function masterPyraminxScramble(){
    // generates the text for one Master Pyraminx scramble
    var s = "";
    var len = 40;
    var turns = [["Uw","U"],["Rw","R"],["Lw","L"],["Bw","B"]];
    var suffixes =["","'"];
    var tips = [["", " u", " u'"], ["", " r", " r'"], ["", " l", " l'"], ["", " b", " b'"]];
    var used = [[0, 0], [0, 0], [0, 0], [0, 0]];
    var lastaxis = -1;
    for (j = 0; j < len; j++) {
        var done = 0;
        do {
            var first = Math.floor(Math.random() * turns.length);
            var second = Math.floor(Math.random() * turns[first].length);
            if (first !== lastaxis && second === 0) {
                used[first][0] = 0;
                for (k = 0; k < turns.length; k++) {
                    if (k !== first) {
                        used[first][k] = 0;
                    }
                }
                lastaxis = first;
            }
            if (used[first][second] === 0) {
                used[first][second] = 1;
                s += turns[first][second] + suffixes[Math.floor(Math.random() * suffixes.length)];
                if (j < len - 1) {
                    s += " ";
                }
                done = 1;
            }
        } while (done === 0);
    }
    for (i = 0; i < tips.length; i++) {
        s += tips[i][Math.floor(Math.random() * 3)];
    }
    return s;
}

function kiloScramble(kilo) {
   // generates the text for one Kilominx scramble
   var s = "";

   for (var r=0; r<4; r++) {
      for (var i=0; i<5; i++) {
         s += " R";
         if (Math.random() < 0.5)
            s += "++";
         else
            s += "--";
         s += " D";
         if (Math.random() < 0.5)
            s += "++";
         else
            s += "--";
      }
      s += " U";
      if (Math.random() < 0.5)
         s += "'";
      // sista raden har inget x2
      if (r < 3)
         s += " x2<br>";
   }

   return s;
}

function clockScramble(clock) {
   // generates the text for one clock scramble as of WCA-2017
   var ss = "";
   var clock_rotations=["0+", "1+", "2+", "3+", "4+", "5+", "6+", "5-", "4-", "3-", "2-", "1-"];
   var pins=["UR", "DR", "DL", "UL", "U", "R", "D", "L", "ALL", "U", "R", "D", "L", "ALL"];
   var final_pins=["UR", "DR", "DL", "UL"];
   for(var i=0;i<14;i++){
      ss += pins[i]+clock_rotations[randInt(12)]+"&nbsp;";
      if(i==8) ss += "y2&nbsp;";
   }
   for(var i=0;i<4;i++){
      if (Math.random() > 0.5)
         ss += final_pins[i]+"&nbsp;", "";
   }
   return ss;
}

function bld3scramble()
{
   // generates a 3x3 scramble with wide turns at the end
   var turn = ['U', 'D', 'R', 'L', 'F', 'B'];
   var oppix = [1, 0, 3, 2, 5, 4];
   var suffix = ["", "'", "2"];
   // next turn
   var s = "", scr = "";
   var n = 19+randInt(3); // total number of turns
   var i, i1, i2, r, last1, last2;
   var ok = false;
   // console.log("N: " + n);
   last1 = last2 = -1;
   while (n>0) {
      // generate next ok layer to turn
      ok = false;
      while (!ok) {
         i = randInt(6);
         //  not same layer twice in a row
         if (i == last1)
            continue;
         // not opposing layers more then once
         if (last1 >= 0 && i == oppix[last1] && i == last2)
            continue;
         ok = true;
         // save layers to next turn
         last2 = last1;
         last1 = i;
      }
      // calc turn: half, clockwise or anticlockwise
      s = turn[i];
      r = randInt(3);
      if (r == 1)
         s += "'";
      else if (r == 2)
         s += '2';
      s += ' ';
      scr += s;
      // ready, count down
      n--;
   }
   // insert 0-2 wide turns (F/U or R/U or F|R|U)
   i = randInt(24);
   if (i == 0) // no wide turns
      return scr; 

   if (i < 3) { // one wide turn
      i1 = randInt(2);
      i2 = randInt(3);
      if (last1 < 2) { // U,D
         if (Math.random() > 0.5) 
            s = 'Fw' + suffix[i1];
         else
            s = 'Rw' + suffix[i2];
      }
      else if (last1 < 4) { // R,L
         if (Math.random() > 0.5) 
            s = 'Fw' + suffix[i1];
         else
            s = 'Uw' + suffix[i2];
      }
      else { // F,B
         if (Math.random() > 0.5) 
            s = 'Rw' + suffix[i2];
         else
            s = 'Uw' + suffix[i2];
      }
      scr += s;
      return scr;
   }
           
   // two wide turns
   i1 = randInt(2);
   i2 = randInt(3);
   if (last1 < 2) { // U,D
      if (Math.random() > 0.5) 
         s = 'Fw' + suffix[i1];
      else
         s = 'Rw' + suffix[i2];
      s += " Uw" + suffix[randInt(3)];
   }
   else if (last1 < 4) { // R,L
      s = 'Fw' + suffix[i1] + " Uw" + suffix[i2];
   }
   else { // F,B
      s = 'Rw' + suffix[randInt(3)] + " Uw" + suffix[i2];
   }
   scr += s;
   return scr;
}

function showScramble() {
   var puzzle  = [];  // the different puzzles 
   var evScrmb = [];  // result: the scrambles for the different events
   var relay   = [];  // matrix for the relays

   // inits for relays (incl.Mini G) s -> space, b -> br
   relay[18] = "2%222_3%333_4%444_";
   relay[18] = relay[18].replace(/%/g, ".&nbsp;&nbsp;&nbsp;");
   evScrmb[18] = relay[18].replace(/_/g, "<br>");
   relay[19] = "2%222_3%333_4%444_5%555_";
   relay[19] = relay[19].replace(/%/g, ".&nbsp;&nbsp;&nbsp;");
   evScrmb[19] = relay[19].replace(/_/g, "<br>");
   relay[20] = "2%222_3%333_4%444_5%555_6%666_";
   relay[20] = relay[20].replace(/%/g, ".&nbsp;&nbsp;&nbsp;");
   evScrmb[20] = relay[20].replace(/_/g, "<br>");
   relay[21] = "2%222_3%333_4%444_5%555_6%666_7%777_";
   relay[21] = relay[21].replace(/%/g, ".&nbsp;&nbsp;&nbsp;");
   evScrmb[21] = relay[21].replace(/_/g, "<br>");
   relay[28] = "2%222_3%333_4%444_5%555_OH%333_Cl%clock_Me%minx_"
             + "Py%pyram_Sq%sq1_Sk%skewb_";
   relay[28] = relay[28].replace(/%/g, ".&nbsp;&nbsp;&nbsp;");
   evScrmb[28] = relay[28].replace(/_/g, "<br>");
   
   // create puzzles
   puzzle.push( { nam: "222", 
                  out: "2scramble|",
                  nScr: [5, 3,  1,  1,  1,  1,  1 ], 
                  evNr: [1, 7, 18, 19, 20, 21, 28 ] } );
   puzzle.push( { nam: "333", 
                  out: "3scramble|",
                  nScr: [5, 3, 60,  5,  5,  5,  3,  1,  1,  1,  1,  2 ],
                  evNr: [2, 8, 13, 14, 15, 16, 17, 18, 19, 20, 21, 28 ] } );
   puzzle.push( { nam: "444", 
                  out: "4scramble|",
                  nScr: [5, 3,  1,  1,  1,  1,  1 ], 
                  evNr: [3, 9, 18, 19, 20, 21, 28 ] } );
   puzzle.push( { nam: "555", 
                  out: "5scramble|",
                  nScr: [5,  3,  1,  1,  1,  1 ], 
                  evNr: [4, 10, 19, 20, 21, 28 ] } );
   puzzle.push( { nam: "666", 
                  out: "6scramble|",
                  nScr: [5,  1,  1,  1 ], 
                  evNr: [5, 11, 20, 21 ] } );
   puzzle.push( { nam: "777", 
                  out: "7scramble|",
                  nScr: [5,  1,  1 ], 
                  evNr: [6, 12, 21 ] } );
   puzzle.push( { nam: "clock", 
                  out: "clockscramble|",
                  nScr: [ 5,  1 ], 
                  evNr: [22, 28 ] } );
   puzzle.push( { nam: "minx", 
                  out: "megaminxscramble|",
                  nScr: [ 5,  1 ], 
                  evNr: [23, 28 ] } );
   puzzle.push( { nam: "pyram", 
                  out: "pyraminxscramble|",
                  nScr: [ 5,  1 ], 
                  evNr: [24, 28 ] } );
   puzzle.push( { nam: "sq1", 
                  out: "square1scramble|",
                  nScr: [ 5,  1 ], 
                  evNr: [25, 28 ] } );
   puzzle.push( { nam: "skewb", 
                  out: "skewbscramble|",
                  nScr: [ 5,  1 ], 
                  evNr: [26, 28 ] } );
   puzzle.push( { nam: "kilo", 
                  out: "kiloscramble|",
                  nScr: [ 5 ], 
                  evNr: [27 ] } );
   puzzle.push( { nam: "redi", 
                  out: "rediscramble|",
                  nScr: [ 5 ], 
                  evNr: [33 ] } );
   puzzle.push( { nam: "mpyra", 
                  out: "mpyrascramble|",
                  nScr: [ 5 ], 
                  evNr: [34 ] } );
   var printScr = mike = mikeTXT = "";
   randomness = new MersenneTwisterObject(new Date().getTime());
   
   //generate output boxes for each event
   for(i=1; i <= 34; i++) {
       if (i > 28 && i < 33) {
           continue;
       }
      evId = "evId" + i;
      document.getElementById("output").innerHTML += 
         "<input id='" + evId + "' name='" + evId + "'  />";
   } 
   // console.log("created ouput boxes");
   // console.log(document.getElementById("output").innerHTML);

   // Initialize scramblers and generate scrambles
    var skewbScrambler = skewbSolver();
    skewbScrambler.init(randomness);
    var rediScrambler = rediSolver();

   // loop over all different puzzles
   for (var p = 0; p < puzzle.length; p++) {
      puzzle[p].output = "";
      var outnam = puzzle[p].out;  // for final output of scrambles
      var len = puzzle[p].nScr.length;
      var ns, ne;
      // loop over the events this puzzle is in
      for (var n = 0; n < len; n++) {
         ns = puzzle[p].nScr[n];
         ne = puzzle[p].evNr[n];
         pz = puzzle[p].nam;
         evId = "evId" + ne;
         document.getElementById("status").innerHTML = "Generating " + evId;
         // generate ns scrambles for event ne
         for (var i = 0; i < ns; i++) {
            // one scramble of puzzle puzz
            if(pz == "clock") {
               scramble = clockScramble();
            } else if(pz == "kilo") {
               scramble = kiloScramble();
            } else if(pz == "mpyra") {
               scramble = masterPyraminxScramble();
            } else if(pz == "skewb") {
               scramble = skewbScrambler.generateScramble();
            } else if(pz == "redi") {
               scramble = rediScrambler.generate_scramble_sequence();
            } else if(pz == "333" && (ne == 8 || ne == 13)) {
               // 3-bld or multi-bld, wide turns
               scramble = bld3scramble();
            } else {
               scramblers[pz].initialize(null, randomness);
               scramble = scramblers[pz].getRandomScramble().scramble;
            }
            if (pz == "333" && ne == 17) {
                // Add R' U' F at start and end; add moves as needed to avoid canceling moves in scramble
                var res = scramble.trim().split(" ");
                var fmcScramble = "R' U' F ";
                if (res[0].charAt(0) == 'F' || (res[0].charAt(0) == 'B' && res[1].charAt(0) == 'F')) {
                    fmcScramble += "D ";
                }
                fmcScramble += scramble;
                if (res[res.length - 1].charAt(0) == 'R' || (res[res.length - 1].charAt(0) == 'L' && res[res.length - 2].charAt(0) == 'R')) {
                    fmcScramble += " D";
                }
                fmcScramble += " R' U' F";
                scramble = fmcScramble;
            }
            // init evscrmb
            if (typeof evScrmb[ne] === 'undefined')
               evScrmb[ne] = "";
            // fill in with scramble number and scramble for normal events
            if (typeof relay[ne] === 'undefined')
               evScrmb[ne] += 
                  i+1 + ".&nbsp;&nbsp;&nbsp;" + scramble + "<br />";
            else {
               // for relays
               evScrmb[ne] = evScrmb[ne].replace(pz, scramble);
               // console.log(evScrmb[ne]);
            }
            // for the forum site
            puzzle[p].output += puzzle[p].out + scramble;
            //This is for Mike Hughey
            mike += outnam + stripHTML(scramble.replace(/<br>/g,","))
               + " <br />";
         }
         document.getElementById(evId).value = evScrmb[ne];
      }
   }

   //Print scrambles
   // document.getElementById('showScrambles').innerHTML = printScr;
   document.getElementById('MikeHughey').value = "<html>" + mike + "</html>";
   // document.getElementById('MikeTXT').value = mikeTXT;


   //SEND scrambles to server
   document.forms["scrambles"].submit(); // this will send the script to the PHP script that sends it server and checks for duplicates etc.
}

window.onload = showScramble;

</script>
<div> <!-- display:none -->
<br /><br />
<form method='post' id='scrambles' action='uploadScrambles.php'>
<div id='output'><div id='status'></div> </div>
<input id='MikeHughey' name='MikeHughey' />
<input id='MikeTXT' name='MikeTXT' />
<input id='week' name='week' value='<?php print $weekNo; ?>' />
<input id='year' name='year' value='<?php print $yearNo; ?>' />

</form>

</div>
<div id='odder'></div>

<div id='showScrambles'></div>
<hr />
</html>
