/**
 * "textAssist"
 * 
 * @version: 1.0, 12.12.2014
 * 
 * @author: Hemn Chawroka
 *		  http://iprodev.com
 * 
 * @example: $('selector').textAssist();
 * 
 */

(function($) {
	var namespace = "textAssist",
		$win = $(window),
		$doc = $(document);

	// Begin the textAssist plugin
	var textAssist = function(element, settings) {

		// Set variables
		var self = this;

		// Settings
		self.settings = jQuery.extend({
			minLength: '3', // Minimum length of selected text to show textAssist.
			delay: '10', // Delay until start assist. Microsecond.
			items: [] // Items via JSON Hash.
		}, settings);

		// Set variables
		self.$el = $(element);
		self.$assist = $("<div class='textassist'><ul></ul></div>");
		self.$container = self.$assist.children('ul');
		self.selection = '';
		self.showable = true;

		// Set mousedown action
		$doc.on('mousedown', function(e) {
			var $target = $(e.target);

			if($target[0] === self.$assist[0] || $target.parents('.textassist')[0]) { return; }
			self.closeMenu();

			// check the element for password
			if($target.is('input:password')) { self.showable = false; }
			else { self.showable = true; }
		});

		// Set mouseup action
		self.$el.on('mouseup', function(e) {
			if (self.$assist.is(':visible')) {
				return;
			}

			setTimeout(function(){
				self.selection = $.trim(String(getSelected()).replace('false', ''));

				if(self.selection.length >= self.settings.minLength && self.showable) {
					self.appendCont();

					var th = self.$assist.outerHeight();
					var wh = $win.height();
					var sp = $doc.scrollTop();

					var top = e.pageY;
					var left = e.pageX + 16;

					if((sp + wh) < (th + e.pageY)) {
						top = e.pageY - th;
					}

					self.$assist.css({
						top: top,
						left: left
					}).show();
				}
			}, self.settings.delay);
		});
	};

	textAssist.prototype = {
		// attempt to close textAssist
		closeMenu: function() {
			this.$container.find('li a').off('click');
			this.$container.empty();
			this.$assist.hide().remove();
		},

		// attempt to append textAssist items
		appendCont: function(){
			var self = this;

			// Append search suggests container
			$('body').append(self.$assist);

			$.each(self.settings.items, function(i, value){
				var t = value,
					$item;

				if(t.divider) {
					$item = $('<li rel="' + self.settings.assistID + '" class="divider"></li>');
				}
				else {
					var title = replaceText(t.title, self.selection),
						classN = (t.classN) ? ' class="' + t.classN + '"' : '',
						href = (t.href) ? ' href="' + replaceText(t.href, self.selection, 1) + '"' : '',
						target = (t.target) ? ' target="' + replaceText(t.target, self.selection) + '"' : '';

					$item = $('<li><a ' + href + classN + target + '><span>' + title + '</span></a></li>');

					if(t.onClick) {
						$item.children('a').on('click', function(){
							t.onClick.call(self, self.selection, $item);
						});
					}

					if(t.onShow) {
						t.onShow.call(self, self.selection, $item);
					}
				}

				self.$container.append($item);
			});

			var $visibles = self.$container.find('li:visible');
			$visibles.addClass('visible');
			$visibles.first().addClass('first');
			$visibles.last().addClass('last');
		}
	};


	// attempt to find a text selection
	function getSelected() {
		if(window.getSelection) { return window.getSelection(); }
		else if(document.getSelection) { return document.getSelection(); }
		else {
			var selection = document.selection && document.selection.createRange();
			if(selection.text) { return selection.text; }
			return false;
		}
	}

	function replaceText(from, to, urlencode) {
		return (urlencode) ? from.replace(/{%s}/ig, encodeURIComponent(to)) : from.replace(/{%s}/ig, to);
	};

	$.fn.textAssist = function(settings) {
		// Apply to all elements
		return this.each(function (i, element) {
			// Call with prevention against multiple instantiations
			$.data(element, namespace, new textAssist(element, settings));
		});
	};
})(jQuery);