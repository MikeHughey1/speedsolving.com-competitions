<?php   
class Corner
{
   var $col1, $col2, $col3, $place;

   function __construct($a, $b, $c, $d)
   {
      $this->col1  = $a;
      $this->col2  = $b;
      $this->col3  = $c;
      $this->place = $d;
   }
}

class Edge
{
   var $col1, $col2, $place;

   function __construct($a, $b, $d)
   {
      $this->col1  = $a;
      $this->col2  = $b;
      $this->place = $d;
   }
}

class Center
{
   var $place;

   function __construct($a)
   {
      $this->place = $a;
   }
}

class Cube
{
   var $edges, $corners, $centers;

   function __construct()
   {
      $this->edges   = [];
      $corners = [];
      $centers = [];
      $this->corners[] = new Corner(0, 1, 2, 0);
      $this->corners[] = new Corner(0, 2, 3, 3);
      $this->corners[] = new Corner(0, 3, 4, 6);
      $this->corners[] = new Corner(0, 4, 1, 9);
      $this->corners[] = new Corner(5, 2, 1, 12);
      $this->corners[] = new Corner(5, 3, 2, 15);
      $this->corners[] = new Corner(5, 4, 3, 18);
      $this->corners[] = new Corner(5, 1, 4, 21);
      $this->edges[] = new Edge(0, 1, 0);
      $this->edges[] = new Edge(0, 2, 2);
      $this->edges[] = new Edge(0, 3, 4);
      $this->edges[] = new Edge(0, 4, 6);
      $this->edges[] = new Edge(1, 2, 8);
      $this->edges[] = new Edge(3, 2, 10);
      $this->edges[] = new Edge(3, 4, 12);
      $this->edges[] = new Edge(1, 4, 14);
      $this->edges[] = new Edge(5, 1, 16);
      $this->edges[] = new Edge(5, 2, 18);
      $this->edges[] = new Edge(5, 3, 20);
      $this->edges[] = new Edge(5, 4, 22);
      // finally six centers
      $this->centers[] = new Center(0);
      $this->centers[] = new Center(1);
      $this->centers[] = new Center(2);
      $this->centers[] = new Center(3);
      $this->centers[] = new Center(4);
      $this->centers[] = new Center(5);
      // edge permutation arrays
      $this->eF = [15, 14, 2, 3, 4, 5, 6, 7, 1, 0, 10, 11, 12, 13, 17, 16, 9, 8, 18, 19, 20, 21, 22, 23]; 
      $this->eB = [0, 1, 2, 3, 11, 10, 6, 7, 8, 9, 21, 20, 5, 4, 14, 15, 16, 17, 18, 19, 13, 12, 22, 23]; 
      $this->eR = [0, 1, 2, 3, 4, 5, 12, 13, 8, 9, 10, 11, 22, 23, 6, 7, 16, 17, 18, 19, 20, 21, 14, 15]; 
      $this->eU = [2, 3, 4, 5, 6, 7, 0, 1, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23]; 
      $this->eL = [0, 1, 8, 9, 4, 5, 6, 7, 18, 19, 2, 3, 12, 13, 14, 15, 16, 17, 10, 11, 20, 21, 22, 23];
      $this->eD = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 22, 23, 16, 17, 18, 19, 20, 21];
      // for corners
      $this->cU = [3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23];
      $this->cR = [0, 1, 2, 3, 4, 5, 20, 18, 19, 7, 8, 6, 12, 13, 14, 15, 16, 17, 22, 23, 21, 11, 9, 10];
      $this->cF = [10, 11, 9, 3, 4, 5, 6, 7, 8, 23, 21, 22, 2, 0, 1, 15, 16, 17, 18, 19, 20, 13, 14, 12];
      $this->cD = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 21, 22, 23, 12, 13, 14, 15, 16, 17, 18, 19, 20];
      $this->cL = [14, 12, 13, 1, 2, 0, 6, 7, 8, 9, 10, 11, 16, 17, 15, 5, 3, 4, 18, 19, 20, 21, 22, 23];
      $this->cB = [0, 1, 2, 17, 15, 16, 4, 5, 3, 9, 10, 11, 12, 13, 14, 19, 20, 18, 8, 6, 7, 21, 22, 23];
      // wide moves = normal move + middle slice edges + centers
      $this->eFw = [15, 14, 7, 6, 4, 5, 23, 22, 1, 0, 10, 11, 12, 13, 17, 16, 9, 8, 3, 2, 20, 21, 19, 18]; 
      $this->eBw = [0, 1, 19, 18, 11, 10, 3, 2, 8, 9, 21, 20, 5, 4, 14, 15, 16, 17, 23, 22, 13, 12, 7, 6]; 
      $this->eRw = [5, 4, 2, 3, 21, 20, 12, 13, 8, 9, 10, 11, 22, 23, 6, 7, 1, 0, 18, 19, 17, 16, 14, 15]; 
      $this->eUw = [2, 3, 4, 5, 6, 7, 0, 1, 11, 10, 13, 12, 15, 14, 9, 8, 16, 17, 18, 19, 20, 21, 22, 23]; 
      $this->eLw = [17, 16, 8, 9, 1, 0, 6, 7, 18, 19, 2, 3, 12, 13, 14, 15, 21, 20, 10, 11, 5, 4, 22, 23];
      $this->eDw = [0, 1, 2, 3, 4, 5, 6, 7, 15, 14, 9, 8, 11, 10, 13, 12, 22, 23, 16, 17, 18, 19, 20, 21];
      // wide moves for centers 
      $this->cenLw = [1, 5, 2, 0, 4, 3];
      $this->cenDw = [0, 2, 3, 4, 1, 5];
      $this->cenFw = [2, 1, 5, 3, 0, 4];
      $this->cenRw = [3, 0, 2, 5, 4, 1];
      $this->cenUw = [0, 4, 1, 2, 3, 5];
      $this->cenBw = [4, 1, 0, 3, 5, 2];
   }

   function move($mv)
   // updates edges and $corners (centers) with the move mv
   // returns 1 for real moves, 0 for rotations
   {
      $len = strlen($mv);
      if ($len == 0)
         return 0;

      // wide move?
      $wide = (stripos($mv, 'w') == 1);
      // what move? (R/L/U/D/F/B)
      $c1 = $mv[0];
      // echo "move: ".$mv."<br>";
      switch ($c1) {
         case 'R': 
            $arrC = $this->cR; 
            $arrE = $wide ? $this->eRw : $this->eR; 
            $arrCen = $this->cenRw;
            break;
         case 'U': 
            $arrC = $this->cU; 
            $arrE = $wide ? $this->eUw : $this->eU; 
            $arrCen = $this->cenUw;
            break;
         case 'F': 
            $arrC = $this->cF; 
            $arrE = $wide ? $this->eFw : $this->eF; 
            $arrCen = $this->cenFw;
            break;
         case 'L': 
            $arrC = $this->cL; 
            $arrE = $wide ? $this->eLw : $this->eL; 
            $arrCen = $this->cenLw;
            break;
         case 'B': 
            $arrC = $this->cB; 
            $arrE = $wide ? $this->eBw : $this->eB; 
            $arrCen = $this->cenBw;
            break;
         case 'D': 
            $arrC = $this->cD; 
            $arrE = $wide ? $this->eDw : $this->eD; 
            $arrCen = $this->cenDw;
            break;
         default: 
            // print "WEIRD MOVE: $mv $len $c1 $wide ".ord($mv[0]).",".ord($mv[1]).",".ord($mv[2]).",".ord($mv[3])."<br>";
            return 0;
      }
      $c2 = $mv[strlen($mv)-1];
      // print "; MV: $mv $len $c1 $c2 $wide<br>";

      // clockwise, anti clockwise or half turn?
      // just do one, two or three turns (three = anti clockwise)
      switch ($c2) {
         case '2':  $iter = 2; break;
         case '\'': $iter = 3; break;
         default:   $iter = 1; break;
      }

      // permute edges according to the move in arrE 
      for ($i = 0; $i < $iter; $i++) {
         for ($e = 0; $e < 12; $e++) 
            $this->edges[$e]->place = $arrE[$this->edges[$e]->place];
      }
      // permute corners according to the move in arrC 
      for ($i = 0; $i < $iter; $i++) {
         for ($c = 0; $c < 8; $c++) {
            $this->corners[$c]->place = $arrC[$this->corners[$c]->place];

         }
      }
      // permute centers (only if wide move)
      if ($wide) {
         for ($i = 0; $i < $iter; $i++) {
            for ($c = 0; $c < 6; $c++) {
               $cen = $this->centers[$c];
               $cen->place = $arrCen[$cen->place];
            }
         }
      }

      // return value. Cube solved = number of moves. Not solved => false.

      // count as one move
      return 1; 
   }

   function printstate()
   {
      print "c: ";
      for ($i=0; $i<8; $i++)
         print $this->corners[$i]->place . " ";
      print ".   e: ";
      for ($i=0; $i<12; $i++)
         print $this->edges[$i]->place . " ";
      print ".\n";
   }

   function orient()
   {
      $u = $this->centers[0]->place;
      switch ($u) {
         case 0: break;
         case 1: $this->rotation("x"); break;
         case 2: $this->rotation("z'"); break;
         case 3: $this->rotation("x'"); break;
         case 4: $this->rotation("z"); break;
         case 5: $this->rotation("x2"); break;
      }
      $f = $this->centers[1]->place;
      switch ($f) { // cannot be 0 or 5
         case 2: $this->rotation("y"); break;
         case 3: $this->rotation("y2"); break;
         case 4: $this->rotation("y'"); break;
         default: ; // do nothing, already oriented
      }
   }


   function rotation($rot)
   {
      // move rotations without move count
      if ($rot == "x") {
         $this->move("Rw");
         $this->move("L'");
      }
      else if ($rot == "x'") {
         $this->move("Rw'");
         $this->move("L");
      }
      else if ($rot == "x2") {
         $this->move("Rw'"); $this->move("Rw'"); 
         $this->move("L"); $this->move("L");
      }
      else if ($rot == "y") {
         $this->move("Uw");
         $this->move("D'");
      }
      else if ($rot == "y2") {
         $this->move("Uw"); $this->move("Uw");
         $this->move("D'"); $this->move("D'");
      }
      else if ($rot == "y'") {
         $this->move("Uw'");
         $this->move("D");
      }
      else if ($rot == "z") {
         $this->move("Fw");
         $this->move("B'");
      }
      else if ($rot == "z2") {
         $this->move("Fw"); $this->move("Fw");
         $this->move("B'"); $this->move("B'");
      }
      else if ($rot == "z'") {
         $this->move("Fw'");
         $this->move("B");
      }
   }

   function checkSolved() {
      // checks if solved on an already oriented cube

      $result = true;
      for ($c = 0; $c < 8; $c++)
         if ($this->corners[$c]->place != $c*3) {
            // echo "corner ".$c.": ".$this->corners[$c]->place."<br>";
            $result = false;
         }
      for ($e = 0; $e < 12; $e++)
         if ($this->edges[$e]->place != $e*2) {
            // echo "edge ".$e.": ".$this->edges[$e]->place."<br>";
            $result = false;
         }

      // no errors 
      return $result;
   }

   function setSolved() {
      // reset to solved cube before next scramble
      for ($c = 0; $c < 8; $c++)
         $this->corners[$c]->place = $c * 3;
      for ($e = 0; $e < 12; $e++)
         $this->edges[$e]->place = $e * 2;
      for ($c = 0; $c < 6; $c++)
         $this->centers[$c]->place = $c;
   }
}

function solution($cube, $scr)
{
   $mvN = 0;
   $marr = explode(' ', $scr);
   foreach ($marr as $m) {
      // treat rotations as two moves which do not affect move count
      if (stripos("xyz", $m[0]) === false) {
         $mvN += $cube->move($m);
      }
      else 
         $cube->rotation($m);
   }
   return $mvN;
}

function FMCsolve($scramble, $solve)
{
   // checks FMC solutions and count moves. /Mats 2018-03
   // returns number of moves (false if not a solution)
   // define all the single layer moves for edges




   // change odd apostrophe to usual
   $cube = new Cube();
   // $cube->printstate();
   solution($cube, $scramble);
   // $cube->printstate();
   $moves = solution($cube, $solve);
   // $cube->printstate();
   $cube->orient();
   if ($cube->checkSolved())
      return $moves;
   else 
      return false;
}

function correct_lowercase_move($move)
{
    return strtoupper($move[0])."w";
}

function correct_solution($solve)
{
    // Replace any whitespace with a single space
    $solve = preg_replace("/\s+/", ' ', $solve);

    // Replace unusual quotes or i's (inverse) with the normal quote
    $solve = str_replace(array("’", "‘", "´", "i"), "'", $solve);
    
    // Convert lowercase moves to uppercase wide moves - this is an experiment
    $solve = preg_replace_callback("/[lrudfb]/", correct_lowercase_move, $solve);

    // Convert slice moves to 2 non-slice moves
    $solve = str_replace("M2", "Lw2 L2", $solve);
    $solve = str_replace("M'", "Rw R'", $solve);
    $solve = str_replace("M", "Lw L'", $solve);
    $solve = str_replace("E2", "Uw2 U2", $solve);
    $solve = str_replace("E'", "Dw' D", $solve);
    $solve = str_replace("E", "Uw' U", $solve);
    $solve = str_replace("S2", "Fw2 F2", $solve);
    $solve = str_replace("S'", "Bw B'", $solve);
    $solve = str_replace("S", "Fw F'", $solve);

    // Convert improperly uppercased letters to lowercase
    $solve = str_replace("W", "w", $solve);
    $solve = str_replace("X", "x", $solve);
    $solve = str_replace("Y", "y", $solve);
    $solve = str_replace("Z", "z", $solve);
    
    // Remove unnecessary quotes after half turns
    $solve = str_replace("2'", "2", $solve);

    // Add spaces where they are missing between moves
    if ($solve != "DNF") {
        // Need to do it twice in case $2 also needs to function as $1
        $solve = preg_replace("/([A-Za-z2\'])([LRUDFBxyz])/", "$1 $2", $solve);
        $solve = preg_replace("/([A-Za-z2\'])([LRUDFBxyz])/", "$1 $2", $solve);
    }

    // Return the corrected solution
    return $solve;
}
?>
