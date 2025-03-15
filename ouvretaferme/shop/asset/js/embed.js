const otfIframe = document.createElement("iframe");
let otfIframeHeight = 500;
otfIframe.src = url;
otfIframe.style.width = "1px";
otfIframe.style.minWidth = "100%";
otfIframe.style.border = "none";
otfIframe.style.height = otfIframeHeight +"px";
document.getElementById("otf").appendChild(otfIframe);

window.addEventListener('message', function(e) {

     let message = e.data;

     if (
          message.height &&
          message.height !== otfIframeHeight
     ) {
          otfIframe.style.height = (message.height + 50) +'px';
          otfIframeHeight = message.height;
     }

},false);
