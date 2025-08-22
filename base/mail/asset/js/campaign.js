document.delegateEventListener('autocompleteUpdate', '#campaign-write', function(e) {

	qs('#campaign-contacts').innerHTML = e.delegateTarget.qsa('input[name="to[]"]').length;

});

class Campaign {

    static changeScheduledAt(farmId, campaignId, date) {

        new Ajax.Query()
          .url('/mail/campaign:getLimits')
          .method('post')
          .body({
              id: farmId,
              campaign: campaignId,
              date: date
          })
          .fetch();

    }

}