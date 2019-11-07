var defaultsToolTipSettings = {
    dir: 'bottom',
    offset: [0, 0],
    html: '',
    tooltipClass: 'tooltip'
};

function putPopupTooltip(popOrTool,settings,el){
    popOrTool.attr('class', settings.tooltipClass);
    popOrTool.append(settings.html);
    var offset = el.offset();
    var elX = offset.left + el.outerWidth() / 2;
    var elY = offset.top + el.outerHeight() / 2;

    if (settings.dir === 'left')
        elX -= el.outerWidth() / 2;
    else if (settings.dir === 'right')
        elX += el.outerWidth() / 2;
    else if (settings.dir === 'top')
        elY -= el.outerHeight() / 2;
    else if (settings.dir === 'bottom'){
        elY += el.outerHeight() / 2;
        elX -= el.outerWidth() / 2;
    }
    popOrTool.css({position: 'fixed', left: elX - $(window).scrollLeft() + settings.offset[0], top: elY - $(window).scrollTop() + settings.offset[1]});
    $(document.body).append(popOrTool);
}
(function($) {

    $.fn.tooltip = function(options) {
        var tooltip = $('<div>');
        var settings = $.extend({}, defaultsToolTipSettings, options);

        return this.each(function() {
            var el = $(this);
            el.on('mouseover', function(e) {
                putPopupTooltip(tooltip,settings,el);
            }).on('mouseout', function() {
                tooltip.remove();
            });
        });
    };
    
    $.fn.popup = function(options) {
        var popup = $('<div>');
        var closeArea = $('<div style="position: relative; z-index: 99; float: right; width: 20px; height: 20px; margin-top: 2px; margin-right: 2px; cursor: pointer; ">');
        var close = $('<img src="images/close.svg" >');
        closeArea.append(close);
        var settings = $.extend({}, defaultsToolTipSettings, options);

        return this.each(function() {
            var el = $(this);
            el.on(settings.event, function(e) {
                closeArea.on("click",function(){console.log("clicked close");popup.remove();});
                popup.append(closeArea);
                settings.html.attr("style","margin-top: 22px;");//give space for the close button
                
                putPopupTooltip(popup,settings,el);
            });
        });
    };
}(jQuery));



