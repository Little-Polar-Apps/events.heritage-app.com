(function($) {
	$.fn.exists = function() {
		if(this.length>0) {
			return $(this);
		} else {
			return this.length>0;
		}
	}
})(jQuery);

(function($) {
    /**
     * Auto-growing textareas; technique ripped from Facebook
     *
     * http://github.com/jaz303/jquery-grab-bag/tree/master/javascripts/jquery.autogrow-textarea.js
     */
    $.fn.autogrow = function(options) {
        return this.filter('textarea').each(function()
        {
            var self         = this;
            var $self        = $(self);
            var minHeight    = $self.height();
            var noFlickerPad = $self.hasClass('autogrow-short') ? 0 : parseInt($self.css('lineHeight')) || 0;

            var shadow = $('<div></div>').css({
                position:    'absolute',
                top:         -10000,
                left:        -10000,
                width:       $self.width(),
                fontSize:    $self.css('fontSize'),
                fontFamily:  $self.css('fontFamily'),
                fontWeight:  $self.css('fontWeight'),
                lineHeight:  $self.css('lineHeight'),
                resize:      'none',
                            'word-wrap': 'break-word'
            }).appendTo(document.body);

            var update = function(event)
            {
                var times = function(string, number)
                {
                    for (var i=0, r=''; i<number; i++) r += string;
                    return r;
                };

                var val = self.value.replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/&/g, '&amp;')
                                    .replace(/\n$/, '<br/>&nbsp;')
                                    .replace(/\n/g, '<br/>')
                                    .replace(/ {2,}/g, function(space){ return times('&nbsp;', space.length - 1) + ' ' });

                                // Did enter get pressed?  Resize in this keydown event so that the flicker doesn't occur.
                                if (event && event.data && event.data.event === 'keydown' && event.keyCode === 13) {
                                        val += '<br />';
                                }

                shadow.css('width', $self.width());
                shadow.html(val + (noFlickerPad === 0 ? '...' : '')); // Append '...' to resize pre-emptively.
                $self.height(Math.max(shadow.height() + noFlickerPad, minHeight));
            }

            $self.change(update).keyup(update).keydown({event:'keydown'},update);
            $(window).resize(update);

            update();
        });
    };
})(jQuery);

$(document).ready(function() {

	function scrollToPage( link_item , target_id ){
		if( link_item || target_id){
			if( link_item && !link_item.hasClass('dropdown-toggle') || target_id){
				if(link_item){
					target_id = link_item.attr('href');
				}else{
					target_id = target_id;
				}
				var the_offset = $(target_id).offset();
				if( the_offset ){
					var scrollSpeed = 0;
					var containerW = $('.container').width();
					var offset_minus_value = 0;

					if( containerW > 940 ){
						scrollSpeed = 1000;
					}

					var menu_collapsed = $('.menu_collapsed').eq(0);
					if( menu_collapsed.size() > 0){
						offset_minus_value = 0;
					}

					var top_offset = the_offset.top;
					top_offset = top_offset - offset_minus_value;
					if(top_offset <= 0 ){
						top_offset = 0;
					}
					$(window).scrollTo( top_offset , scrollSpeed);
				}
			}
		}
	}

	$('.pickDate').datetimepicker({
        pickTime: false,
        format: 'DD/MM/YYYY'
    });

    $('.pickTime').datetimepicker({
        pickDate: false,
        pick12HourFormat: false
    });

	$('[name=":ad"]').change(function() {
		if($(this).prop('checked')) {
			$('.pickTime').hide();
		} else {
			$('.pickTime').show();
		}
	});

	$('#customMessage').autogrow();


	$(document).on("click", ".delete", function(e) {
		e.preventDefault();
		NProgress.start();
		var $this = $(this);
		bootbox.confirm("Are you sure you wish to delete this event. Once deleted you will not be able to retrieve it again.", function(result) {
			if(result === true) {
				var data = {
					id: $this.parent().parent().data('ident'),
					deleting: true
				}
				$.post(window.location.pathname, data, function(r) {
					$this.parent().parent().remove();
					NProgress.done();
				});
			} else {
				console.log('cancel');
				NProgress.done();
			}
		});
	});

	var r = window.location.pathname.split('/');
	
	$(document).on("click", "#saveEvent", function(e) {
		e.preventDefault();
		NProgress.start();
		if(!$('#event-title').val() || !$('#description').val()) {
			alert('Title or Description are empty.');
			NProgress.done();
		}
		var $form = $(this).closest("form");
		var data = $form.serializeArray();
			data.push({ name: $(this).attr('name'), value: $(this).val() });
		$.post(window.location.pathname, data, function(r) {
			var r = window.location.pathname.split('/');
			console.log(r[1]);
			$('#list-events').load(window.location.pathname + ' #list-events');
			$('#event-title').val('');
			$('#description').val('');
			$('#permalink').val('');
			NProgress.done();
		});
	});

});

$(window).load(function() {
	$('body').show();
    $('.version').text(NProgress.version);
    NProgress.start();
    setTimeout(function() { NProgress.done(); $('.fading').removeClass('outing'); }, 1000);
});