document.addEventListener("DOMContentLoaded", function () {
	// lazy load

	setTimeout(function () {

		$(".js-bg").each(function () {

			$(this).css('background-image', 'url(' + $(this).data("bg") + ')');

		});

		$(".js-img").each(function () {

			$(this).attr('src', $(this).data("src"));

		});

	}, 200);

	// loader

	setTimeout(function () {

		$('body').removeClass('loaded');

	}, 400);

});



/* viewport width */

function viewport() {

	var e = window,

		a = 'inner';

	if (!('innerWidth' in window)) {

		a = 'client';

		e = document.documentElement || document.body;

	}

	return { width: e[a + 'Width'], height: e[a + 'Height'] }

};

/* viewport width */

function toggleFullScreen(){
	const methodMap = [
		[
			'requestFullscreen',
			'exitFullscreen',
			'fullscreenElement',
			'fullscreenEnabled',
			'fullscreenchange',
			'fullscreenerror',
		],
		// New WebKit
		[
			'webkitRequestFullscreen',
			'webkitExitFullscreen',
			'webkitFullscreenElement',
			'webkitFullscreenEnabled',
			'webkitfullscreenchange',
			'webkitfullscreenerror',
	
		],
		// Old WebKit
		[
			'webkitRequestFullScreen',
			'webkitCancelFullScreen',
			'webkitCurrentFullScreenElement',
			'webkitCancelFullScreen',
			'webkitfullscreenchange',
			'webkitfullscreenerror',
	
		],
		[
			'mozRequestFullScreen',
			'mozCancelFullScreen',
			'mozFullScreenElement',
			'mozFullScreenEnabled',
			'mozfullscreenchange',
			'mozfullscreenerror',
		],
		[
			'msRequestFullscreen',
			'msExitFullscreen',
			'msFullscreenElement',
			'msFullscreenEnabled',
			'MSFullscreenChange',
			'MSFullscreenError',
		],
	];
	
	const nativeAPI = (() => {
		if (typeof document === 'undefined') {
			return false;
		}
	
		const unprefixedMethods = methodMap[0];
		const returnValue = {};
	
		for (const methodList of methodMap) {
			const exitFullscreenMethod = methodList?.[1];
			if (exitFullscreenMethod in document) {
				for (const [index, method] of methodList.entries()) {
					returnValue[unprefixedMethods[index]] = method;
				}
	
				return returnValue;
			}
		}
	
		return false;
	})();
	
	const eventNameMap = {
		change: nativeAPI.fullscreenchange,
		error: nativeAPI.fullscreenerror,
	};
	
	// eslint-disable-next-line import/no-mutable-exports
	let screenfull = {
		// eslint-disable-next-line default-param-last
		request(element = document.documentElement, options) {
			return new Promise((resolve, reject) => {
				const onFullScreenEntered = () => {
					screenfull.off('change', onFullScreenEntered);
					resolve();
				};
	
				screenfull.on('change', onFullScreenEntered);
	
				const returnPromise = element[nativeAPI.requestFullscreen](options);
	
				if (returnPromise instanceof Promise) {
					returnPromise.then(onFullScreenEntered).catch(reject);
				}
			});
		},
		exit() {
			return new Promise((resolve, reject) => {
				if (!screenfull.isFullscreen) {
					resolve();
					return;
				}
	
				const onFullScreenExit = () => {
					screenfull.off('change', onFullScreenExit);
					resolve();
				};
	
				screenfull.on('change', onFullScreenExit);
	
				const returnPromise = document[nativeAPI.exitFullscreen]();
	
				if (returnPromise instanceof Promise) {
					returnPromise.then(onFullScreenExit).catch(reject);
				}
			});
		},
		toggle(element, options) {
			return screenfull.isFullscreen ? screenfull.exit() : screenfull.request(element, options);
		},
		onchange(callback) {
			screenfull.on('change', callback);
		},
		onerror(callback) {
			screenfull.on('error', callback);
		},
		on(event, callback) {
			const eventName = eventNameMap[event];
			if (eventName) {
				document.addEventListener(eventName, callback, false);
			}
		},
		off(event, callback) {
			const eventName = eventNameMap[event];
			if (eventName) {
				document.removeEventListener(eventName, callback, false);
			}
		},
		raw: nativeAPI,
	};
	
	Object.defineProperties(screenfull, {
		isFullscreen: {
			get: () => Boolean(document[nativeAPI.fullscreenElement]),
		},
		element: {
			enumerable: true,
			get: () => document[nativeAPI.fullscreenElement] ?? undefined,
		},
		isEnabled: {
			enumerable: true,
			// Coerce to boolean in case of old WebKit.
			get: () => Boolean(document[nativeAPI.fullscreenEnabled]),
		},
	});
	
	if (!nativeAPI) {
		screenfull = {isEnabled: false};
	}
	if (screenfull.isEnabled) {
		screenfull.request();
	}
	$('.play-box').hide();
}

(function () {



	/* components */



	if ($('.poker__chat-messages').length) {

		$(".poker__chat-messages").mCustomScrollbar({

			scrollInertia: 500,

			mouseWheelPixels: 100

		});

		setTimeout(function () {

			$(".poker__chat-messages").mCustomScrollbar("scrollTo", "bottom", { scrollInertia: 0 });

		}, 500);

	};



	if ($('.poker__chat-logs').length) {

		$(".poker__chat-logs").mCustomScrollbar({

			scrollInertia: 500,

			mouseWheelPixels: 100

		});

	};







	if ($('.poker__chat-head-tab').length) {

		$chat_head_tab = $('.poker__chat-head-tab');

		$chat_content_tab = $('.poker__chat-content-tab');



		$('.poker__chat-head-tab[data-tabname="chat"]').addClass('active');

		// $('.poker__chat-content-tab[data-tabcontent="chat"]').addClass('active');
		$('.poker__chat-content-tab[data-tabcontent="chat"]').show();



		$chat_head_tab.on('click', function () {
			
			var this_data = $(this).data('tabname');

			if (this_data == 'chat') {

				$chat_head_tab.removeClass('active');

				// $chat_content_tab.removeClass('active');
				$chat_content_tab.hide();

				$chat_head_tab.filter('[data-tabname="chat"]').addClass('active');

				// $chat_content_tab.filter('[data-tabcontent="chat"]').addClass('active');
				$chat_content_tab.filter('[data-tabcontent="chat"]').show();

				$(".poker__chat-messages").mCustomScrollbar("scrollTo", "bottom", { scrollInertia: 0 });

			}

			if (this_data == 'logs') {

				$chat_head_tab.removeClass('active');

				// $chat_content_tab.removeClass('active');
				$chat_content_tab.hide();

				$chat_head_tab.filter('[data-tabname="logs"]').addClass('active');

				$chat_content_tab.filter('[data-tabcontent="logs"]').show();

				if (typeof viewport_wid !== 'undefined' && viewport_wid > 850) {

					$(".poker__chat-logs").mCustomScrollbar({

						scrollInertia: 500,

						mouseWheelPixels: 100

					});

				}

			}

		});

	}





	if ($('.poker__chat-btn').length) {

		var $poker_chat_btn = $('.poker__chat-btn');

		var $poker_chat = $('.poker__chat');

		$poker_chat_btn.on('click', function () {

			if (!$(this).hasClass('active')) {

				$(this).addClass('active');

				$poker_chat.addClass('active');

			} else {

				$(this).removeClass('active');

				$poker_chat.removeClass('active');

			}

		});

		$('.poker__chat-head-close').click(function () {

			$poker_chat_btn.removeClass('active');

			$poker_chat.removeClass('active');

		});

		// $('.poker__chat-close button').bind('click', function() {

		//         	$('.poker__chat').attr('style', 'width:200px !important');

        // });

		// $('.poker__chat-open button').bind('click', function() {

		//         	$('.poker__chat').attr('style', 'bottom:0');

        // });

	}





	// Poker Bet/Raise Button

	var $poker_bet_btn = '.poker__bet-btn';

	$(document).on('click', $poker_bet_btn, function () {

		var $poker_betpopup = $('.poker__betpopup');



		if (!$(this).hasClass('active')) {

			$(this).addClass('active');

			$poker_betpopup.addClass('active');

		} else {

			$(this).removeClass('active');

			$poker_betpopup.removeClass('active');

			// temp start

			$('.poker__bet-btn-summa').hide();

			$('.poker__bet-btn-name').html('Bet');

			// temp end

		}

	});











	/*

	if($('.styled').length) {

		$('.styled').styler();

	};

	if($('.fancybox').length) {

		$('.fancybox').fancybox({

			margin  : 10,

			padding  : 10

		});

	};

	if($('.slick-slider').length) {

		$('.slick-slider').slick({

			dots: true,

			infinite: false,

			speed: 300,

			slidesToShow: 4,

			slidesToScroll: 4,

			responsive: [

				{

				  breakpoint: 1024,

				  settings: {

					slidesToShow: 3,

					slidesToScroll: 3,

					infinite: true,

					dots: true

				  }

				},

				{

				  breakpoint: 600,

				  settings: "unslick"

				}

			]

		});

	};

	

	*/



	/* components */



	//отмена перетаскивания картинок

	$("img, a").on("dragstart", function (e) {

		e.preventDefault();

	});



}());



var handler = function () {

	var viewport_wid = viewport().width;

	var viewport_height = viewport().height;



	if (viewport_wid <= 850) {

		$(".poker__chat-logs, .poker__chat-messages").mCustomScrollbar('destroy');

	} else {

		if ($('.poker__chat-messages').length) {

			$(".poker__chat-messages").mCustomScrollbar({

				scrollInertia: 500,

				mouseWheelPixels: 100

			});

			setTimeout(function () {

				$(".poker__chat-messages").mCustomScrollbar("scrollTo", "bottom", { scrollInertia: 0 });

			}, 500);

		};



		if ($('.poker__chat-logs').length) {

			$(".poker__chat-logs").mCustomScrollbar({

				scrollInertia: 500,

				mouseWheelPixels: 100

			});

		};

	}



}



$(window).bind('load', handler);

$(window).bind('resize', handler);

