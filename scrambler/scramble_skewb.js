var skewb = {
	moves:
		{
			all: [0, 1, 2, 3, 4, 5, 6, 7],
			names: ["U", "B", "R", "L", "U'", "B'", "R'", "L'"],
			inverses: ["U' ", "B' ", "R' ", "L' ", "U ", "B ", "R ", "L "],
			toNumbers: {"U": 0, "B": 1, "R": 2, "L": 3, "U'": 4, "B'": 5, "R'": 6, "L'": 7},
			U: 0,
			Up: 4,
			B: 1,
			Bp: 5,
			R: 2,
			Rp: 6,
			L: 3,
			Lp: 7
		},
	states:
		{
			centers: 360,
			corners: 8748
		},

	solved:
		{
			centers: [0, 1, 2, 3, 4, 5],
			corners:
				{
					o: [0, 0, 0, 0, 0, 0, 0],
					p: [0, 1, 2, 3, 4, 5, 6]
				},
			stickers:
				{
					corners: [0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5],
					centers: [0, 1, 2, 3, 4, 5]
				}
		}
};

var initialized = false;

var transitionTableCenters;
var transitionTableCorners;
var pruningTableCenters;
var pruningTableCorners;

//init//
function skewb_init() {
	build_transition_tables();
	build_pruning_tables();
	initialized = true;
}

//can define states if desired
//options: can define certian options for generating:
//	-centerstate: state index to use for the centers, if left undefined random will be used
//	-cornerstate: state index to use for the corners, if left undefined random will be used
//	-minlength: the minimum length of a scramble
function skewb_generate_scramble(options) {

	if (!initialized) {
	skewb_init();
	}
	
	if (options==undefined)
		options = {};

	var random_center_state = Math.floor(Math.random()*skewb.states.centers);
	
	if (options.centerstate!=undefined)
		random_center_state = options.centerstate
	
	//map the randomly chosen indices to valid states
	var i
	
	count = 0
	for (i in pruningTableCenters)
	{
		if (count==random_center_state)
		{
			random_center_state = i;
			break;
		}
		count++;
	}
	
	var random_corner_state = Math.floor(Math.random()*skewb.states.corners);
	
	if (options.cornerstate!=undefined)
		random_corner_state = options.cornerstate
		
	
	count = 0
	for (i in pruningTableCorners)
	{
		if (count==random_corner_state)
		{
			random_corner_state = i;
			break;
		}
		count++;
	}
	

	
	var scramble = ""
	var solutionFound = false
	var maxDepth = options.minlength || 0
	
	
	
	search = function(center_state, corner_state, d, s, last_move)
	{
		if (solutionFound || d < 0)
			return
		if (center_state == 0 && corner_state == 0)
		{
			scramble = s
			solutionFound = true
			return;
		}
		if (pruningTableCenters[center_state] <= d && pruningTableCorners[corner_state] <= d)
		{
			for (var i = 0; i <= 7; i++)
			{
				if (i % 4 == last_move)
					continue;
				search(transitionTableCenters[center_state][i],transitionTableCorners[corner_state][i],d-1,skewb.moves.inverses[i]+s,i);
			}
		}
	}
	
	//search deeper and deeper until a solution is found.
	while (!solutionFound)
	{
		search(random_center_state, random_corner_state, maxDepth, "")
		maxDepth++;
	}
	return scramble;
}

// get_permutation_index
// takes an array of length n containing the integers 0 to n-1 and returns a unique
// index for the permutation.
// get_permutation_index([0,1,2,3]) = 0
// get_permutation_index([0,1,3,2]) = 1
function get_permutation_index(permutation) {
if (permutation.length==0)
	return 0
factorial = function(n){return n<2?1:n*factorial(n-1)};
first = permutation[0]
rest = permutation.slice(1).map(function(n){return n>first?n-1:n});
return first*factorial(permutation.length-1)+get_permutation_index(rest)
}

// cloneArray
// recursively creates a clone of an array, and all arrays within it
function cloneArray(s) {
    if (!(s instanceof Array)) return s;
    return s.map(function(x) {
		return cloneArray(x);
    });
 };



//builds the transition tables for centers and corners
function build_transition_tables() {

	//table: the table to populate
	//solved: the solved state for the component of the puzzle
	//states: the number of states in the component
	//moveFunction: function which takes a state and a move and produces a new state
	//indexFunction: function which generates a unique number for every possible state
	build_transition_table = function(table,solved,states,moveFunction,indexFunction)
	{
		var todo = [solved]
		var newStates;
		
		count = 0
		
		while (count < states)
		{
			newStates = []
			for (var s = 0; s < todo.length; s++)
			{
				index = indexFunction(todo[s])
				if (table[index] == undefined)
				{
					count++;
					table[index] = []
					for (i = 0; i <= 7; i++)
					{
						newState = moveFunction(todo[s],i);
						table[index][i] = indexFunction(newState);
						newStates[newStates.length] = newState;
					}
				}
			}
			todo = newStates
		}
	}
	//centers

	transitionTableCenters = {}

	build_transition_table(transitionTableCenters,skewb.solved.centers,skewb.states.centers,move_centers,get_center_index);
	
	//corners

	transitionTableCorners = {}
	
	build_transition_table(transitionTableCorners,skewb.solved.corners,skewb.states.corners,move_corners,get_corner_index);

}


function build_pruning_tables() {
	//floods the pruning table to find god's number for every state
	build_pruning_table = function(state, depth, pruneTable, transitionTable)
	{
		if (depth > 11) return
		if (pruneTable[state]==undefined || pruneTable[state]>depth)
		{
			pruneTable[state] = depth
			for (i in skewb.moves.all)
			{
				build_pruning_table(transitionTable[state][i],depth+1,pruneTable,transitionTable)
			}
		}
	}
	pruningTableCenters = {}
	build_pruning_table(0,0,pruningTableCenters,transitionTableCenters);
	pruningTableCorners = {}
	build_pruning_table(0,0,pruningTableCorners,transitionTableCorners);
}
 
// takes a configuration of centers and returns a unique integer based on it
function get_center_index(state) {
	return get_permutation_index(state);
}
// takes a configuration of corners and returns a unique integer based on it
function get_corner_index(state) {
	var o = 0
	for (var i = 0; i < state.o.length; i++)
	{
		o+=state.o[i]*Math.pow(3,i)
	}
	var p = get_permutation_index(state.p)
	return p*2187+o
} 

// move_corners
// takes a corner state and returns it with a move performed on it
function move_corners(state, move) {
	var newState =
	{
		o: cloneArray(state.o),
		p: cloneArray(state.p)
	};
	// does a turn on the indicated pieces.
	// a -> b -> c -> a
	// direction indicated the direction to twist pieces
	var turn = function(middle, a, b, c, direction)
	{
		newState.o[middle] = (newState.o[middle]+direction+3)%3
		newState.o[a] = (state.o[c]-direction+3)%3;
		newState.o[b] = (state.o[a]-direction+3)%3;
		newState.o[c] = (state.o[b]-direction+3)%3;
		
		newState.p[a] = state.p[c];
		newState.p[b] = state.p[a];
		newState.p[c] = state.p[b];
	};
	switch (move)
	{
		case skewb.moves.U:
			turn(1, 4, 2, 0, 1);
		break;
		case skewb.moves.Up:
			turn(1, 4, 0, 2, -1);
		break;
		case skewb.moves.B:
			turn(4, 1, 3, 5, 1);
		break;
		case skewb.moves.Bp:
			turn(4, 1, 5, 3, -1);
		break;
		case skewb.moves.R:
			turn(5, 4, 6, 2, 1);
		break;
		case skewb.moves.Rp:
			turn(5, 4, 2, 6, -1);
		break;
		case skewb.moves.L:
			turn(3, 0, 6, 4, 1);
		break;
		case skewb.moves.Lp:
			turn(3, 0, 4, 6, -1);
		break;
	}
	return newState;
}

function move_centers(state, move) {
	newState = cloneArray(state);
	//does a turn on the indicated pieces
	// a -> b -> c -> a
	turn = function(a,b,c) {
		newState[a] = state[c]
		newState[b] = state[a]
		newState[c] = state[b]
	};
	switch(move)
	{
		case skewb.moves.U:
			turn(5,0,4);
		break;
		case skewb.moves.Up:
			turn(5,4,0);
		break;
		case skewb.moves.D:
			turn(3,5,4);
		break;
		case skewb.moves.Dp:
			turn(3,4,5);
		break;
		case skewb.moves.R:
			turn(1,5,3);
		break;
		case skewb.moves.Rp:
			turn(1,3,5);
		break;
		case skewb.moves.L:
			turn(4,2,3);
		break;
		case skewb.moves.Lp:
			turn(4,3,2);
		break;
	}
	return newState;
}
