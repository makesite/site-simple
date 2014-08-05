/* DragSortTree
 * BSD license
 */
var DST = {	// global namespace/singleton (all rogue variables are contained within)
	'channels': [ ], //list of different channels for different behaviors

	'reg_channel': function (name, classes) {
		var new_channel = new DST_channel(name, classes);
		this.channels.push(new_channel);
		return new_channel;
	},

	'find_channel': function (container) { // try to find an appropriate channel
		for (var i = 0; i < this.channels.length; i++) {
			for (var j = 0; j < this.channels[i].containers.length; j++) {
				if (this.channels[i].containers[j] == container) return this.channels[i];
			}
		}
		return null;
	},

	'init': function (name, objects, classes) {
		var i = 0, obj;
		var channel = this.reg_channel(name, classes);

		while ((obj = objects[i++])) {
			obj.onmousedown = DST_hold;
			channel.reg_container(obj);
			obj.ondragstart = cancelEvent;
			obj.onselectstart = cancelEvent; //for IE, altho others can benefit too
		}
	},

	'auto_init': function (chan, config, ending) { //chan is both a name and a class
		if (!chan) chan = 'sortable'; //default class
		var i = 0, obj;
		var objs = document.getElementsByTagName('*');
		//DST_en = ending || 0;
		var matches = [ ];
		while ((obj = objs[i++])) {	
			if (obj.className && obj.className.search(chan) != -1) 
				matches.push(obj);			
		}
		this.init(chan, matches, config);
	}
};

//JS-styled class definition
DST_channel.prototype = {
	name: '',
	containers: [ ],
	classes: { },
	regexps: { },
	callback: null,

	'reg_container': function (node, cloned) {
		if (this.classes.ruler && !cloned) {	
			for (var i = 0; i < node.childNodes.length; i++) {
				var kid = node.childNodes[i];
				if (kid.nodeType != 1) continue;
				var prepend = document.createElement('div');
				prepend.className = this.classes.ruler;
				if ( kid.hasChildNodes() )
					kid.insertBefore( prepend, kid.firstChild );
				else
					kid.appendChild( prepend );
			}
		}
		this.containers.push( node );
	},

	'same_factory': function (node1, node2) {
		var classname1 = node1.className;
		var classname2 = node2.className.replace(this.classes.product, this.classes.factory);
		if (classname2 == classname1) return 1;
		return 0;
	}
};

//JS-styled constructor/class definition 
function DST_channel(name, classes) {
	this.name = name;
	this.containers = [ ];
	classes = classes || { };
	this.callback = classes.callback || null;
	classes = {
		'drag': classes.drag || 'drag', // this class is being added to dragged element
		'drop': classes.drop || 'drop',	// this class is being added to all drop containers
		'factory': classes.factory || 'factory', // object creates clones of itself
		'product': classes.product || 'product', // object is a product of factory
		'singleton': classes.singleton || 'singleton', // object is permament
		'glue': classes.glue || 'glue', // object is un-movable
		'swap': classes.swap || 'swap', // object swaps, not sorts
		'trash': classes.trash || 'trash', // object destroys, not sorts
		'ruler': classes.ruler !== undefined ? classes.ruler : 'ruler' // prepender
	};
	this.classes = classes;
	this.regexps = {
		'drag': new RegExp('(\\s|^)'+classes.drag+'(\\s|$)'),
		'drop': new RegExp('(\\s|^)'+classes.drop+'(\\s|$)'),
		'factory_or_singleton': (classes.factory && classes.singleton ? new RegExp(classes.factory + '|' + classes.singleton)
			: (classes.factory ? classes.factory : (classes.singleton ? classes.singleton : '$^') ))
		//,'factory_or_product': (classes.factory && classes.product ? new RegExp(classes.factory + '|' + classes.product)
			//: (classes.factory ? c lasses.factory : (classes.product ? classes.product : '$^') ))
	};
}

//JS-style singleton describing current drag operation
var Drag = {
	channel: null,
	parent: null,
	object: null,
	Drop: {
		to: null,
		target: null
	}
};
function DST_release_wrap(e) {
	if (DST_release(e)
	 && Drag.channel.callback)
		Drag.channel.callback ( Drag );
}
//the magic happens here
function DST_release(e) {
	var ev = e || window.event;
	var Drop = Drag.Drop;
	Drop.target = ev.target || ev.srcElement;
	Drop.to = Drop.target ? Drop.target.parentNode : null;

	//unhook:
	document.onmouseup = null;
	//unstyle:
	Drag.object.className = 
	Drag.object.className.replace(Drag.channel.regexps.drag, "");
	for (var i = 0; i < Drag.channel.containers.length; i++) {
		Drag.channel.containers[i].className =
		Drag.channel.containers[i].className.replace(Drag.channel.regexps.drop, "");
	}

	//drop?
	if (!Drop.target) return false;
	if (Drop.target == Drag.object) return true;

	var drop_tree = 0; // are sub-level drops allowed in general?
	var drop_into = 0; // is *current* drop a sub-level drop?
	if (Drag.channel.classes.ruler != '') {
		drop_tree = 1;
		drop_into = 1;
	 	if (Drop.target.className.search(Drag.channel.classes.ruler) != -1) drop_into = 0;
	}

	var drop_handler; //HACK -- call a specified function instead of doing anything
	drop_handler = Drop.target.getAttribute('data-drop-handler');
	if (drop_handler) {
		var fn = window[drop_handler];
		if (typeof fn === 'function') {
			fn(Drop.target, Drag.object);
			return true;
		}
		alert("undefined function " + drop_handler + ", can't handle drop");
		return false;
	}

	var Drop_channel;
	while ((Drop_channel = DST.find_channel(Drop.to)) == null) {
		Drop.target = Drop.to;
		Drop.to = Drop.target.parentNode;
		if (!Drop.to) return false;
	}

	if (Drag.channel != Drop_channel) return false;
	if (Drop.target == Drag.object) return true;

	var Drop_object = null;

	//dropping to trash
	if (Drag.channel.classes.trash && Drop.target.className.search(
		Drag.channel.classes.trash) != -1) {
		//do not delete singletons
		if (Drag.object.className.search(Drag.channel.regexps.factory_or_singleton) != -1)
			drop_into = 0;

		//do not delete unless dropping INTO trash 
		if (drop_tree == 0 || drop_into == 1) {
			//delete
			Drag.parent.removeChild(Drag.object);
			return;
		}
	}

	//handle the factory ideom
	if (Drag.channel.classes.factory) {
		//dropping TO factory
		if (Drop.target.className.search(Drag.channel.classes.factory) != -1) {
			if ((drop_tree == 0 || drop_into == 1) 
				&& Drag.channel.same_factory(Drop.target, Drag.object)) {
				//delete
				Drag.parent.removeChild(Drag.object);
				return;
			}
			//never insert anything INTO a factory
			drop_into = 0;
		}

		//dropping A factory
		if (Drag.object.className.search(Drag.channel.classes.factory) != -1) {
			//copy
			var new_node = Drag.object.cloneNode(true);
			new_node.className = 
			new_node.className.replace(
				Drag.channel.classes.factory,
				Drag.channel.classes.product );
	
			Drop_object = new_node;
		}
	}

	if (drop_into)
	{
		//if a node is trying to append itself to it's own child, the whole thing is illegal
		if (isChildOf(Drop.target, Drag.object)) return false;

		var found = -1;
		for (var i = 0; i < Drop.target.childNodes.length; i++) {
			if (DST.find_channel(Drop.target.childNodes[i]) != null) {
				found = i;
				break;
			}
		}
		if (found == -1) {
			// Cut	
			if (!Drop_object) Drop_object = Drag.parent.removeChild(Drag.object)
			//create new list
			var new_list = Drop.to.cloneNode(false);
			new_list.appendChild ( Drop_object );
			Drag.channel.reg_container(new_list, true);

			Drop.to = Drop.target;
			Drop.target = null;
			Drop_object = new_list;

		} else {
			//append to existing list
			Drop.to = Drop.target.childNodes[found];
			Drop.target = null;
			if (!Drop_object) Drop_object = Drag.parent.removeChild(Drag.object);
		}

	}

	if (Drag.parent == Drop.to) {
		if (Drag.channel.classes.swap && Drop.to.className.search(
			Drag.channel.classes.swap) != -1) {
			//swap
			if (Drag.object.nextSibling == Drop.target)
				swapNode(Drop.target, Drag.object);
			else
				swapNode(Drag.object, Drop.target);
			return;
		}
	}

	// Cut	
	if (!Drop_object) Drop_object = Drag.parent.removeChild(Drag.object)

	// Paste
	if (!Drop.target) 
		Drop.to.appendChild(Drop_object);
	else
		Drop.to.insertBefore(Drop_object, Drop.target);
	return true;
}

function DST_hold(e) {
	var ev = e || window.event;
	Drag.object = ev.target || ev.srcElement;
	Drag.parent = Drag.object.parentNode;

	/* HACK -- only allow left click */
	if (ev.which) {	if (ev.which != 1) return; } 
	else if (ev.button > 1) return; //for IE

	if (!Drag.object) return;

	/* SUPER HACK -- Forbid dragging of 'input' elements */
	if (Drag.object.nodeName == 'INPUT') return true;
	//if (Drag.object.nodeName == 'FORM') return true;

	while ((Drag.channel = DST.find_channel(Drag.parent)) == null) {
		Drag.object = Drag.parent;
		Drag.parent = Drag.parent.parentNode;
		if (!Drag.parent) break;
	}
	if (Drag.object.nodeName == '#document') return false;

	if (Drag.object.className.search(Drag.channel.classes.glue) != -1) return false;

	//if (DST_en && !Drag.object.nextSibling) return false;

	//hook event(s)
	document.onmouseup = DST_release_wrap;
	//add style
	for (var i = 0; i < Drag.channel.containers.length; i++)
		Drag.channel.containers[i].className += (' ' + Drag.channel.classes.drop);
	Drag.object.className += (' ' + Drag.channel.classes.drag);

	return cancelEvent(ev);
}

/*
 * Usefull, generic helpers 
 */
if (!String.prototype.trim) {
	String.prototype.trim = function() {
		return this.replace(/^\s+/g, "").replace(/\s+$/g, "");
	}
}

/* is node1 child of node2 ? */
function isChildOf(node1, node2) { // Node.prototype.isChildOf
	while (node1.parentNode) {
		if (node1.parentNode == node2) return true;
		node1 = node1.parentNode;
	}
	return false;
}

function swapNode(node1, node2) { // Node.prototype.swapNode
    var nextSibling = node1.nextSibling;
    var parentNode = node1.parentNode;
    node1.parentNode.replaceChild(node1, node2);
    parentNode.insertBefore(node2, nextSibling); 
}

function stopEvent(e) {
	if (e.stopPropagation) e.stopPropagation();
	else e.cancelBubble = true;
	return false;
}

function cancelEvent(e) {
	if (!e) e = window.event;
	if (e.preventDefault) e.preventDefault(); 
	else e.returnValue = false;
	return stopEvent(e);
}

var DOM_onloaded = 0; // very crude version of "dom content loaded", not recommended
function DOM_onLoad(func) {
	var invoker = function() {
		if (DOM_onloaded == 1) return;
		DOM_onloaded = 1;
		func.call();
		if(document.removeEventListener)
			document.removeEventListener("DOMContentLoaded", invoker, false);
		window.onload = null;
	}
	if(document.addEventListener)
		document.addEventListener("DOMContentLoaded", invoker, false);
	window.onload = invoker;
}
