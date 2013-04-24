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
                } else if (elementChild.match('div') && typeof divCallback === 'function') {
                    divCallback(elementChild, element);
                }
            });
            liCallback(element);
        }
    });
}
function filterTree(threshold, text, isCaseSensitive, showMatchesDescendants) {
    var re = new RegExp(text, 'mg' + (isCaseSensitive ? '' : 'i'));

    if (text == '' || text != currentText || isCaseSensitive != currentIsCaseSensitive) {
        $$('#treeView li').each(function(element) {
            element.writeAttribute('matched', false);
        });
    }

    if (text == '' && threshold != currentThreshold) {
        // apply only duration filter to list items
        $$('#treeView li').each(function(li) {
            if (parseInt(li.readAttribute('duration')) < threshold) {
                li.addClassName('filtered');
            } else {
                li.removeClassName('filtered');
            }
        });
    } else if (text == currentText && isCaseSensitive == currentIsCaseSensitive && threshold != currentThreshold) {
        // apply duration filter to matched list items
        $$('#treeView li[matched]').each(function(li) {
            if (parseInt(li.readAttribute('duration')) < threshold) {
                li.addClassName('filtered');
            } else {
                li.removeClassName('filtered');
            }
        });

        traverseTreeInDepth($('treeView'), function(li) {
            // hide rows which don't match and with all hidden children
            if (!li.readAttribute('matched')
                && li.select('li').length > 0 && li.select('li:not(.filtered)').length == 0) {
                li.addClassName('filtered');
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
            $$('#treeView li[matched]:not(.filtered)').each(function(li) {
                li.select('li').each(function (descendantLi) {
                    descendantLi.removeClassName('filtered');
                });
            });
        }
    }

    currentThreshold              = threshold;
    currentText                   = text;
    currentIsCaseSensitive        = isCaseSensitive;
    currentShowMatchesDescendants = showMatchesDescendants;
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

var currentThreshold              = undefined;
var currentText                   = undefined;
var currentIsCaseSensitive        = undefined;
var currentShowMatchesDescendants = undefined;

filterTree(parseInt($('duration-filter').value), $('text-filter').value, false, false);

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
    sliderValue: cubicScaleReverse(currentThreshold),
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
