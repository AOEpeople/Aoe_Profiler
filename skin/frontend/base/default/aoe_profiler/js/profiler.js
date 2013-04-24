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
function filterTreeByDuration(threshold) {
    $$('#treeView li').each(function(element) {
        if (parseInt(element.readAttribute('duration')) < threshold) {
            element.addClassName('filtered-by-duration');
        } else {
            element.removeClassName('filtered-by-duration');
        }
    })
}
function filterTreeByText(text, isCaseInsensitive) {
    var re = new RegExp(text, 'g' + (isCaseInsensitive ? 'i' : ''));

    $$('#treeView font').each(function(element) {
        element.replace(element.innerHTML);
    });

    if (text == '') {
        $$('#treeView li').each(function(element) {
            element.removeClassName('filtered-by-text');
        });
    } else {
        $$('#treeView li').each(function(element) {
            var subStringFound = false;
            element.select('span').each(function(spanElement) {
                var elementText = spanElement.innerHTML;
                if (re.test(elementText)) {
                    if (spanElement.select('font').length == 0) {
                        var newElementText = elementText.replace(re, function (m) {return '<font>' + m  + '</font>';});
                        spanElement.update(newElementText);
                    }
                    subStringFound = true;
                } else if (elementText.indexOf('<font>') != -1) {
                    subStringFound = true;
                }
            });
            if (subStringFound) {
                element.removeClassName('filtered-by-text');
            } else {
                element.addClassName('filtered-by-text');
            }
        });
    }
}
$('duration-filter-form').observe('submit', function(event) {
    var value = parseInt($('duration-filter').value);
    filterTreeByDuration(value);
    value = Math.sqrt(value * 1000);
    profilerslider.setValue(value);
    event.stop();
});
$('text-filter-form').observe('submit', function(event) {
    var value = $('text-filter').value;
    var isCaseInsensitive = $('text-filter-case-sensitivity').checked;
    filterTreeByText(value, isCaseInsensitive);
    event.stop();
});

var initSliderValue = parseInt($('duration-filter').value);
var initText = $('text-filter').value;

filterTreeByDuration(initSliderValue);
filterTreeByText(initText);

function cubicScale(x) {
    return 1/1000 * x * x;
}
function cubicScaleReverse(y) {
    return Math.sqrt(y * 1000);
}

var profilerslider = new Control.Slider($('p-handle'), $('p-track'), {
    axis: 'horizontal',
    range: $R(0,1000),
    alignX: 3,
    sliderValue: cubicScaleReverse(initSliderValue),
    onSlide: function(param) {
        $('duration-filter').value = cubicScale(param).round();
    },
    onChange: function(param) {
        filterTreeByDuration(cubicScale(param));
    }
});
$$("#profiler .info").each(function(element) {
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
