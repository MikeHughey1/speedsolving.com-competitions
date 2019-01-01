function skewbSolver () {
    return (function() {
        const N_MOVES = 4;
        const MIN_LENGTH = 7;
        const SCRAMBLE_LENGTH = 11;

        const fact = [ 1, 1, 1, 3, 12, 60, 360 ];//fact[x] = x!/2
        var permmv = [];
        var twstmv = [];
        var permprun = [];
        var twstprun = [];

        var cornerpermmv = [
                [ 6, 5, 10, 1 ], [ 9, 7, 4, 2 ], [ 3, 11, 8, 0 ], [ 10, 1, 6, 5 ],
                [ 0, 8, 11, 3 ], [ 7, 9, 2, 4 ], [ 4, 2, 9, 7 ], [ 11, 3, 0, 8 ],
                [ 1, 10, 5, 6 ], [ 8, 0, 3, 11 ], [ 2, 4, 7, 9 ], [ 5, 6, 1, 10 ] ];

        const ori = [ 0, 1, 2, 0, 2, 1, 1, 2, 0, 2, 1, 0 ];
        
        var randomness;

        function getpermmv(idx, move)
        {
            var centerindex = Math.floor(idx / 12);
            var cornerindex = idx % 12;
            var val = 0x543210;
            var parity = 0;
            var centerperm = [];
            for (var i = 0; i < 5; i++) {
                var p = fact[5 - i];
                var v = Math.floor(centerindex / p);
                centerindex -= v * p;
                parity ^= v;
                v <<= 2;
                centerperm[i] = (val >> v) & 0xf;
                var m = (1 << v) - 1;
                val = (val & m) + ((val >> 4) & ~m);
            }
            if ((parity & 1) === 0) {
                centerperm[5] = val;
            } else {
                centerperm[5] = centerperm[4];
                centerperm[4] = val;
            }
            var t;
            if (move === 0) {
                t = centerperm[0];
                centerperm[0] = centerperm[1];
                centerperm[1] = centerperm[3];
                centerperm[3] = t;
            } else if (move === 1) {
                t = centerperm[0];
                centerperm[0] = centerperm[4];
                centerperm[4] = centerperm[2];
                centerperm[2] = t;
            } else if (move === 2) {
                t = centerperm[1];
                centerperm[1] = centerperm[2];
                centerperm[2] = centerperm[5];
                centerperm[5] = t;
            } else if (move === 3) {
                t = centerperm[3];
                centerperm[3] = centerperm[5];
                centerperm[5] = centerperm[4];
                centerperm[4] = t;
            }
            val = 0x543210;
            for (var i = 0; i < 4; i++) {
                var v = centerperm[i] << 2;
                centerindex *= 6 - i;
                centerindex += (val >> v) & 0xf;
                var shifted = 0x111110 << v;
                val -= 0x111110 << v;
            }
            return centerindex * 12 + cornerpermmv[cornerindex][move];
        }

        function gettwstmv(idx, move) {
            var fixedtwst = [];
            var twst = [];
            for (var i = 0; i < 4; i++) {
                fixedtwst[i] = idx % 3;
                idx = Math.floor(idx / 3);
            }
            for (var i = 0; i < 3; i++) {
                twst[i] = idx % 3;
                idx = Math.floor(idx / 3);
            }
            twst[3] = (6 - twst[0] - twst[1] - twst[2]) % 3;
            fixedtwst[move] = (fixedtwst[move] + 1) % 3;
            var t;
            switch (move) {
                case 0:
                    t = twst[0];
                    twst[0] = twst[2] + 2;
                    twst[2] = twst[1] + 2;
                    twst[1] = t + 2;
                    break;
                case 1:
                    t = twst[0];
                    twst[0] = twst[1] + 2;
                    twst[1] = twst[3] + 2;
                    twst[3] = t + 2;
                    break;
                case 2:
                    t = twst[0];
                    twst[0] = twst[3] + 2;
                    twst[3] = twst[2] + 2;
                    twst[2] = t + 2;
                    break;
                case 3:
                    t = twst[1];
                    twst[1] = twst[2] + 2;
                    twst[2] = twst[3] + 2;
                    twst[3] = t + 2;
                    break;
                default:
            }
            for (var i = 2; i >= 0; i--) {
                idx = idx * 3 + twst[i] % 3;
            }
            for (var i = 3; i >= 0; i--) {
                idx = idx * 3 + fixedtwst[i];
            }
            return idx;
        }

        function init(randomGenerator) {
            randomness = randomGenerator;
            for (var i = 0; i < 4320; i++) {
                permprun[i] = -1;
                permmv[i] = [0, 0, 0, 0];
                for (j = 0; j < 4; j++) {
                    permmv[i][j] = getpermmv(i, j);
                }
            }
            for (var i = 0; i < 2187; i++) {
                twstprun[i] = -1;
                twstmv[i] = [0, 0, 0, 0];
                for (var j = 0; j < 4; j++) {
                    twstmv[i][j] = gettwstmv(i, j);
                }
            }
            permprun[0] = 0;
            for (var l = 0; l < 6; l++) {
                for (var p = 0; p < 4320; p++) {
                    if (permprun[p] === l) {
                        for (var m = 0; m < 4; m++) {
                            var q = p;
                            for (var c = 0; c < 2; c++) {
                                q = permmv[q][m];
                                if (permprun[q] === -1) {
                                    permprun[q] = Math.floor((l + 1) % 128);
                                }
                            }
                        }
                    }
                }
            }
            twstprun[0] = 0;
            for (var l = 0; l < 6; l++) {
                for (var p = 0; p < 2187; p++) {
                    if (twstprun[p] === l) {
                        for (var m = 0; m < 4; m++) {
                            var q = p;
                            for (var c = 0; c < 2; c++) {
                                q = twstmv[q][m];
                                if (twstprun[q] === -1) {
                                    twstprun[q] = Math.floor((l + 1) % 128);
                                }
                            }
                        }
                    }
                }
            }
        }

        function search(depth, perm, twst, maxl, lm, sol) {
            if (maxl === 0) {
                if (perm === 0 && twst === 0) {
                    return depth;
                } else {
                    return -1;
                }
            }
            if (permprun[perm] > maxl || twstprun[twst] > maxl) {
                return -1;
            }
            var randomOffset = Math.floor(randomness.random()*N_MOVES);
            for (var m = 0; m < N_MOVES; m++) {
                var randomMove = (m + randomOffset) % N_MOVES;
                if (randomMove !== lm) {
                    var p = perm;
                    var s = twst;
                    for (var a = 0; a < 2; a++) {
                        p = permmv[p][randomMove];
                        s = twstmv[s][randomMove];
                        var searchResult = search(depth + 1, p, s, maxl - 1, randomMove, sol);
                        if (searchResult !== -1) {
                            sol[depth] = randomMove * 2 + a;
                            return searchResult;
                        }
                    }
                }
            }
            return -1;
        }

        function isSolvable(perm, twst) {
            return ori[perm % 12] === (twst + Math.floor(twst / 3) + Math.floor(twst / 9) + Math.floor(twst / 27)) % 3;
        }

        function randomState() {
            perm = Math.floor(randomness.random()*4320);
            do {
                twst = Math.floor(randomness.random()*2187);
            } while (!isSolvable(perm, twst));
            var state = {perm: perm, twst: twst};
            return state;
        }

        function solveIn(state, length) {
            var sol = [];
            var solutionLength = search(0, state.perm, state.twst, length, -1, sol);
            if (solutionLength !== -1) {
                return getSolution(sol, solutionLength);
            } else {
                return null;
            }
        }

        function generateExactly(state, length) {
            var sol = [];
            var solutionLength = search(0, state.perm, state.twst, length, -1, sol);
            if (solutionLength !== -1) {
                return getSolution(sol, solutionLength);
            } else {
                return null;
            }
        }

        /**
         * The solver is written in jaap's notation. Now we're going to convert the result to FCN(fixed corner notation):
         * Step one, the puzzle is rotated by z2, which will bring "R L D B" (in jaap's notation) to "L R F U" (in FCN, F has not
         *     been defined, now we define it as the opposite corner of B)
         * Step two, convert F to B by rotation [F' B]. When an F found in the move sequence, it is replaced immediately by B and other 3 moves
         *     should be swapped. For example, if the next move is R, we should turn U instead. Because the R corner is at U after rotation.
         *     In another word, "F R" is converted to "B U". The correctness can be easily verified and the procedure is recursable.
         */
        function getSolution(sol, solutionLength) {
            var sb = "";
            var move2str = [ "L", "R", "B", "U" ];//RLDB (in jaap's notation) rotated by z2
            for (var i = 0; i < solutionLength; i++) {
                var axis = sol[i] >> 1;
                var pow = sol[i] & 1;
                if (axis === 2) {//step two.
                    for (var p=0; p<=pow; p++) {
                        var temp = move2str[0];
                        move2str[0] = move2str[1];
                        move2str[1] = move2str[3];
                        move2str[3] = temp;
                    }
                }
                sb += (move2str[axis] + ((pow == 1) ? "'" : ""));
                sb += " ";
            }
            var scrambleSequence = sb.toString().trim();
            return scrambleSequence;
        }

        function generateScramble() {
            var state;
            var length;
            do {
                state = randomState();
            } while (solveIn(state, MIN_LENGTH - 1) !== null);
            for (var i = 1; i < 12;) {
                if (solveIn(state, i) !== null) {
                    length = i;
                    break;
                }
                ++i;
            }
            var scramble = generateExactly(state, SCRAMBLE_LENGTH);
            return scramble;
        }

        return {
          init: init,
          generateScramble: generateScramble
        };
    })();
}