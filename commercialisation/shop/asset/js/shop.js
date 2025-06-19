class Shop {

    static embed() {

        setInterval(() => {

            const height = qs('header').offsetHeight +  qs('main').offsetHeight;

            window.parent.postMessage({
                height: height
            },'*');

        }, 50);

    };

}


document.addEventListener('DOMContentLoaded', () => {

    if(document.body.dataset.template.includes('shop-embed')) {
        Shop.embed();
    }

});