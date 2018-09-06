<?php

   for ($i=0; $i<5; $i++) {
      print $i+1 . ".";
      for ($j = 0; $j<4; $j++) {
         for ($k = 0; $k<5; $k++) {
            print " R";
            if (rand(0, 1) == 0)
               print "++";
            else
               print "--";
            print " D";
            if (rand(0, 1) == 0)
               print "++";
            else
               print "--";
          }
          print " U";
          if (rand(0, 1) == 0)
             print "'";
          if ($j < 3)
             print " x2";
          print "<br>";
       }
   }

?>
