NelioAB.ga = {};
NelioAB.ga.holdScriptLoad = true;


/**
 * The ga.js that is supposed to be inserted in the DOM.
 */
NelioAB.ga.script = false;


/**
 * This function inserts the ga.js script in the DOM.
 */
NelioAB.ga.unhold = function() {
	NelioAB.ga.holdScriptLoad = false;
	if ( NelioAB.ga.script ) {
		NelioAB.ga.hook.parentNode.doInsertBefore( NelioAB.ga.script, NelioAB.ga.hook );
		NelioAB.ga.script = false;
	}
};


/**
 * This is the first script of the DOM, used to prepend ga.js.
 */
NelioAB.ga.hook = false;

NelioAB.ga.updateHook = function() {
	if ( NelioAB.ga.hook ) {
		NelioAB.ga.hook.parentNode.insertBefore = NelioAB.ga.hook.parentNode.doInsertBefore;
		NelioAB.ga.hook.parentNode.doInsertBefore = undefined;
	}
	NelioAB.ga.hook = document.getElementsByTagName('script')[0];
	NelioAB.ga.hook.parentNode.doInsertBefore = NelioAB.ga.hook.parentNode.insertBefore;
	NelioAB.ga.hook.parentNode.insertBefore = function(newNode, existingNode) {
		if ( NelioAB.ga.holdScriptLoad ) {
			if ( NelioAB.ga.holdScriptLoad && newNode.src.indexOf('google-analytics.com/ga.js') > 0 ) {
				NelioAB.ga.script = newNode;
			}
			else if ( NelioAB.ga.holdScriptLoad && newNode.src.indexOf('google-analytics.com/analytics.js') > 0 ) {
				NelioAB.ga.script = newNode;
			}
			else {
				this.doInsertBefore( newNode, existingNode );
				NelioAB.ga.updateHook();
			}
		}
		else {
			this.doInsertBefore( newNode, existingNode );
		}
	};
}


NelioAB.ga.updateHook();

