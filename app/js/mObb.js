(function(global){
    var mObb = {
        VERSION: '0.0.1',
    };
    
    
    mObb.currentTouch = {};
	
	// initialization of the mObb object
    mObb.init = function(){
        mObb.bindTouches();
        mObb.shape = shape = document.getElementById('shape');
        mObb.createRing();
    };
    
	// all touches in the navigation area must be recognized and processed, we have to move the wheel depending on the current touches
    mObb.bindTouches = function(){
        document.addEventListener('touchmove', function(e){
            e.preventDefault();
        });
        document.addEventListener('touchstart', function(e){
            e.preventDefault();
			
			// we have to process our links via javascript, because the mObb objects binds all touches
			// we try to find out if a (video-)link in the application is clicked and when this is true, we follow the link
			if(e.srcElement.parentNode.toString().substring(0,4)==="http") {
				window.location.href = e.srcElement.parentNode.toString();
			} else if(e.srcElement.toString().substring(0,4)==="http") {
				window.location.href = e.srcElement.toString();
			}
			
            var touch = e.touches[0];
            mObb.currentTouch.startY = touch.screenY;
            mObb.currentTouch.startTime = e.timeStamp;
			
        }, false);
        document.addEventListener('touchend', function(e){
            e.preventDefault();
            var touch = e.changedTouches[0];
            mObb.currentTouch.endY = touch.screenY;
            var time = e.timeStamp - mObb.currentTouch.startTime;
            var speed = (mObb.currentTouch.startY - mObb.currentTouch.endY) / time;
            mObb.spin(speed);
            
        }, false);
    };
	
	// calculates the new positions for the elements in the wheel
    mObb.spin = function(speed){
        var theTransform = window.getComputedStyle(mObb.shape).webkitTransform;
        var matrix = new WebKitCSSMatrix(theTransform);
        var newX = Math.round(speed * 100);
        newX = (newX > 179) ? 179 : ((newX < -179) ? -179 : newX);
        shape.style.webkitTransform = matrix.rotate(newX, 0, 0);
    };
	
	// calculates how to build the wheel (architecture)
    mObb.createRing = function(){
        var items = shape.getElementsByTagName('li');
		
		var numberOfItems = items.length;
        var angle = 360 / numberOfItems, newAngle;
		var translateZ = 360*(numberOfItems/12);
		document.getElementById('container').style.webkitPerspective = numberOfItems*70;
		
        for (var i = 0, l = items.length; i < l; i++) {
            newAngle = (angle * i);
            var matrix = new WebKitCSSMatrix();
			/* translate ist die Ausrichtung der Shapes: rechts/links liegend/stehend, nahe am betrachter/weit weg vom betrachter */
			/* rotate (Rotation): Drehweite, senkrechte kreisachse, kreisrotation  */
            items[i].style.webkitTransform = matrix.rotate(newAngle, 0, 0).translate(95, 0, translateZ);
        }
        shape.style.left = 0;
    }
	// only one instance of the mObb object
    if (global.mObb) {
        throw new Error('mObb has already been defined');
    }
    else {
        global.mObb = mObb;
    }
})(typeof window === 'undefined' ? this : window);