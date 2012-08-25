$$("#profiler .toggle").each(function(element) {
    element.observe("click", function(event) {
        Event.element(event).up("li").toggleClassName("collapsed");
        event.stop();
    })
});
$('expand-all').observe('click', function(event) {
    $$("#profiler .has-children").each(function(element) {
        element.removeClassName("collapsed");
    });
    event.stop();
});
$('collapse-all').observe('click', function(event) {
    $$("#profiler .has-children").each(function(element) {
        element.addClassName("collapsed");
    });
    event.stop();
});
function filterTree(threshold) {
    $$('#treeView li').each(function(element) {
        if (parseInt(element.readAttribute('duration')) < threshold) {
            element.addClassName('filtered');
        } else {
            element.removeClassName('filtered');
        }
    })
}
$('duration-filter-form').observe('submit', function(event) {
    var value = parseInt($('duration-filter').value);
    filterTree(value);
    profilerslider.setValue(value);
    event.stop();
});

var initSliderValue = parseInt($('duration-filter').value);

filterTree(initSliderValue);

var profilerslider = new Control.Slider($('p-handle'), $('p-track'), {
    axis: 'horizontal',
    range: $R(0,1000),
    sliderValue: initSliderValue,
    onSlide: function(param) {
        $('duration-filter').value = param.round();
    },
    onChange: function(param) {
        filterTree(param)
    }
});
$$("#profiler .caption").each(function(element) {
    element.observe("click", function(event) {
        var liElement = Event.element(event).up("li");
        liElement.toggleClassName("selected");
        $$('#treeView li').each(function(element) {
            if (element != liElement) {
                element.removeClassName('selected');
            }
        });
        event.stop();
    })
});