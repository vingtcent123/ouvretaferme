class Main {

    static checkBrevo(initialize) {
        if(initialize === true) {
            Main.initBrevo();
        } else {
            Main.removeBrevo();
        }
    }

    static initBrevo() {

        (function(d, w, c) {
            w.BrevoConversationsID = "66b5b5d960b3534e0a061524";
            w[c] = w[c] || function() {
                (w[c].q = w[c].q || []).push(arguments);
            };
            var s = d.createElement("script");
            s.async = true;
            s.src = "https://conversations-widget.brevo.com/brevo-conversations.js";
            if (d.head) d.head.appendChild(s);
        })(document, window, "BrevoConversations");

    }
    static removeBrevo() {

        qs('#brevo-conversations', (element) => element.remove());

    }
}
