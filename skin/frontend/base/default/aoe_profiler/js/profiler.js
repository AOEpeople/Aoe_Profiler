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
function traverseTreeInDepth(ul, liCallback, divCallback)
{
    var children = ul.childElements();
    children.each(function(element) {
        if (element.match('li')) {
            var elementChildren = element.childElements();
            elementChildren.each(function(elementChild) {
                if (elementChild.match('ul')) {
                    traverseTreeInDepth(elementChild, liCallback, divCallback);
                } else if (elementChild.match('div')) {
                    divCallback(elementChild, element);
                }
            });
            liCallback(element);
        }
    });
}
function filterTree(threshold, text, isCaseSensitive, showMatchesDescendants) {
    var re = new RegExp(text, 'mg' + (isCaseSensitive ? '' : 'i'));

    $$('#treeView li').each(function(element) {
        element.writeAttribute('matched', false);
    });

    if (text == '') {
        // apply duration filter only to list items
        $$('#treeView li').each(function(element) {
            if (parseInt(element.readAttribute('duration')) < threshold) {
                element.addClassName('filtered');
            } else {
                element.removeClassName('filtered');
            }
        });
    } else {
        // highlight all found items first
        traverseTreeInDepth($('treeView'), function(li) {
            // hide all rows which doesn't match AND duration < threshold
            if (parseInt(li.readAttribute('duration')) >= threshold && li.select('font').length > 0) {
                li.removeClassName('filtered');
            } else {
                li.addClassName('filtered');
            }

            // hide rows which don't match and with all hidden children
            if (!li.readAttribute('matched')
                && li.select('li').length > 0 && li.select('li:not(.filtered)').length == 0) {
                li.addClassName('filtered');
            }
        }, function (div, parentLi) {
            // highlight all found matches
            var spanElement = div.down('span');
            spanElement.select('font').each(function(element) {
                element.replace(element.innerHTML);
            });

            var spanInnerHTML = spanElement.innerHTML;
            var newInnerHTML = spanInnerHTML.replace(re, function (m) {return '<font>' + m  + '</font>';});
            if (newInnerHTML != spanInnerHTML) {
                spanElement.update(newInnerHTML);
                // mark closest parent li as row which matches search pattern
                parentLi.writeAttribute('matched', true);
            }
        });

        if (showMatchesDescendants) {
            $$('#treeView li[matched]:not(.filtered)').each(function(element) {
                element.select('li').each(function (descendant) {
                    descendant.removeClassName('filtered');
                });
            });
            //TODO: implement
        }
    }
}
$('filter-form').observe('submit', function (event) {
    var threshold              = parseInt($('duration-filter').value);
    var text                   = $('text-filter').value;
    var isCaseSensitive        = $('text-filter-case-sensitivity').checked;
    var showMatchesDescendants = $('show-matches-descendants').checked;

    filterTree(threshold, text, isCaseSensitive, showMatchesDescendants);
    threshold = Math.sqrt(threshold * 1000);
    profilerslider.setValue(threshold);

    event.stop();
});

var initSliderValue = parseInt($('duration-filter').value);
var initText = $('text-filter').value;

filterTree(initSliderValue, initText, false, false);

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
        var text                   = $('text-filter').value;
        var isCaseSensitive        = $('text-filter-case-sensitivity').checked;
        var showMatchesDescendants = $('show-matches-descendants').checked;
        filterTree(cubicScale(param), text, isCaseSensitive, showMatchesDescendants);
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
