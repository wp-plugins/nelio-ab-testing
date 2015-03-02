/**
 * Preparing the code for delayed operations.
 */
NelioAB.delayed = {};

/**
 * This attribute holds the functions that should be delayed.
 */
NelioAB.delayed.functions = [];

/**
 * This attribute indicates whether delayed functions have to be held or should
 * be automatically executed right after they're added for "delayed" exec.
 */
NelioAB.delayed.hold = true;

/**
 * This function executes all delayed functions.
 */
NelioAB.delayed.release = function() {
	NelioAB.delayed.hold = false;
	for ( var i = 0; i < NelioAB.delayed.functions.length; ++i )
		NelioAB.delayed.functions[i]();
	NelioAB.delayed.functions = [];
};

/**
 * This function adds the f function to the delayed functions list.
 *
 * @param f a function whose execution will be delayed.
 */
NelioAB.delay = function(f) {
	if ( NelioAB.delayed.hold )
		NelioAB.delayed.functions.push(f);
	else
		f();
};
