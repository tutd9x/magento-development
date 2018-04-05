//define([
//	'jquery'
//], function(jQuery) {

/*
Project: bCarousel
Author: Brian DiChiara
Version: 1.4
Usage: Add this js file to your site, call bCarousel.init() and simply give any element a class of "bcarousel"
Available Options: 
	wrapper : 'bCarousel-wrapper',	// default: bCarousel-wrapper
	target : 'carousel-target',		// default: carousel-target
	event : 'hover',				// default: click, optional hover
	speed : 600,					// default: 600
	increment: 8,					// default: 8 (for hover)
	easing : 'easeOutQuad'			// default: easeOutQuad, requires easing plugin
*/
var $j = jQuery.noConflict();
var bCarousel = {
	
	$jtarget : '',
	$jwrapper : '',
	is_closing : false,
	
	options : {
		wrapper : 'bCarousel-wrapper',	// default: bCarousel-wrapper
		target : 'carousel-target',		// default: carousel-target
		event : 'hover',				// default: click, optional hover
		speed : 600,					// default: 600
		increment: 5,					// default: 8 (for hover)
		easing : 'easeOutQuad'			// default: easeOutQuad, requires easing plugin
	},

	init : function(selector, opts){
		if(!selector){
			selector = '.bcarousel';
		}
		bCarousel._setopts(opts);
		
		bCarousel.$jtarget = $j(selector);
		bCarousel.$jtarget.addClass(bCarousel.options.target);
		
		bCarousel.setup_wrapper();
		bCarousel.add_controls();
		//bCarousel.add_style();
	},
	
	setup_wrapper : function(){
		bCarousel.$jwrapper = $j('<div />', {
			width : bCarousel.$jtarget.width()
		});
		bCarousel.$jwrapper.addClass(bCarousel.options.wrapper); // Thanks Safari
		bCarousel.$jtarget.wrap(bCarousel.$jwrapper);
		// reset wrapper var
		bCarousel.$jwrapper = bCarousel.$jtarget.parents('.'+bCarousel.options.wrapper+':first');
		
		bCarousel.$jtarget.css('width','5000000px'); // we assume images total width won't be larger than this
		var $jlast_img = $j('li:last', bCarousel.$jtarget);
		var new_width = $jlast_img.offset().left + $jlast_img.width();
		bCarousel.$jtarget.css('width',new_width +'px');
	},
	
	add_controls : function(){
		$jwrapper = bCarousel.$jwrapper;
		
		// build controls
		var $jforward = $j('<a />', {
			html : '&gt',
			href : '#forward'
		});
		$jforward.addClass('forward'); // Thanks Safari
		
		var $jback = $j('<a />', {
			html : '&lt;',
			href : '#back'
		});
		$jback.addClass('back'); // Thanks Safari
		$jwrapper.hover(
			function(){
				$jforward.addClass('show_arrow');
				$jback.addClass('show_arrow');},
			function(){
				$jforward.removeClass('show_arrow');
				$jback.removeClass('show_arrow');}
		);
		
		// attach them to the carousel
		$jwrapper.prepend($jforward).prepend($jback);
		
		/* bind events */
		if(bCarousel.options.event == 'hover'){
			$jforward.hover(function(over){
				bCarousel.move(bCarousel.$jtarget, 'left');
			}, function(out){
				bCarousel.stop(bCarousel.$jtarget, 'left');
			}).click(function(){ return false; });
			
			$jback.hover(function(over){
				bCarousel.move(bCarousel.$jtarget, 'right');
			}, function(out){
				bCarousel.stop(bCarousel.$jtarget, 'right');
			}).click(function(){ return false; });
			
		} else {
			$jforward.click(function(e){
				bCarousel.move(bCarousel.$jtarget, 'left');
				return false;
			});
			
			$jback.click(function(e){
				bCarousel.move(bCarousel.$jtarget, 'right');
				return false;
			});
		}
	},
	
	move : function($jtarget, dir){
		
		var params = bCarousel.calculate($jtarget, dir);
		if(params == false){ return; }
		
		if(bCarousel.options.event == 'hover'){
			if(bCarousel.is_closing){
				$jtarget.stop();
				bCarousel.is_closing = false;
			}
			var op = (dir == 'left') ? '-' : '+';
			var movement = op+'='+params.distance;
			$jtarget.animate({
				marginLeft: movement
			}, 6, function(a){
				bCarousel.move($jtarget, dir);
			});
		} else {
			$jtarget.animate({
				marginLeft: params.placement
			}, {
				queue: false,
				duration: bCarousel.options.speed
			}, bCarousel.options.easing);
		}
	},
	
	calculate : function($jtarget, dir){
		var $jwrapper = $jtarget.parents('.'+bCarousel.options.wrapper+':first');
		var wrapper_width = $jwrapper.outerWidth();
		var target_width = $jtarget.outerWidth();
		var maxleft = (target_width - wrapper_width) * -1;
		
		var marginleft = parseInt($jtarget.css('margin-left'));
		if(!marginleft){ marginleft = 0; }
		
		if(dir == 'right'){
			maxleft = 0;
			if(marginleft > maxleft) return false;
		} else if(marginleft < maxleft) return false;
		
		var distance = bCarousel.options.increment;
		if(bCarousel.options.event == 'click'){
			distance = wrapper_width;
		}
		var placement = (dir == 'left') ? marginleft - distance : marginleft + distance;
		
		if(dir == 'left'){
			if(placement >= maxleft){
				if(placement == marginleft){
					return false;
				}
			} else {
				distance = (maxleft - marginleft) * -1;
				placement = maxleft;
			}
		} else {
			if(placement <= maxleft){
				if(marginleft == placement){
					return false;
				}
			} else {
				distance = 0 - marginleft;
				placement = maxleft;
			}
		}
		
		return {
			maxleft : maxleft,
			marginleft : marginleft,
			distance : distance,
			placement : placement
		};
	},
	
	stop : function($jtarget, dir){
		var do_closeout = false;
		if($jtarget.is(':animated')){
			do_closeout = true;
		}
		
		$jtarget.stop();
		
		if(bCarousel.options.event == 'hover' && do_closeout){
			bCarousel.is_closing = true;
			var tmp = bCarousel.options.increment;
			bCarousel.options.increment = 50;
			var params = bCarousel.calculate($jtarget, dir);
			bCarousel.options.increment = tmp;
			if(params != false){
				var op = (dir == 'left') ? '-' : '+';
				var movement = op+'='+params.distance;
				$jtarget.animate({ marginLeft: movement }, 400, bCarousel.options.easing, function(e){
					bCarousel.is_closing = false;
				});
			} else {
				bCarousel.is_closing = false;
			}
		}
	},
	
	_setopts : function(opts){
		if(typeof(opts) == 'object'){
			for(var opt in opts){
				bCarousel.options[opt] = opts[opt];
			}
		}
	}
}


//});