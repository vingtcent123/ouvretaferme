document.delegateEventListener('autocompleteUpdate', '#campaign-write', function(e) {

	qs('#campaign-contacts').innerHTML = e.delegateTarget.qsa('input[name="to[]"]').length;

});

class Campaign {

}