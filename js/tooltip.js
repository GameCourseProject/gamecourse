(function($) {
    var defaults = {
        dir: 'top',
        offset: [-20, -20],
        html: '',
        tooltipClass: 'tooltip'
    };

    var tooltip = $('<div>');

    $.fn.tooltip = function(options) {
        var settings = $.extend({}, defaults, options);

        return this.each(function() {
            var el = $(this);
            el.on('mouseover', function(e) {
                tooltip.attr('class', settings.tooltipClass);
                tooltip.html(settings.html);

                var offset = el.offset();
                var elX = offset.left + el.outerWidth() / 2;
                var elY = offset.top + el.outerHeight() / 2;

                if (settings.dir == 'left')
                    elX -= el.outerWidth() / 2;
                else if (settings.dir == 'right')
                    elX += el.outerWidth() / 2;
                else if (settings.dir == 'top')
                    elY -= el.outerHeight() / 2;
                else if (settings.dir == 'bottom')
                    elY += el.outerHeight() / 2;

                tooltip.css({position: 'fixed', left: elX - $(window).scrollLeft() + settings.offset[0], top: elY - $(window).scrollTop() + settings.offset[1]});
                $(document.body).append(tooltip);
            }).on('mouseout', function() {
                tooltip.remove();
            });
        });
    };
}(jQuery));