class Home {

    static open(target) {

        qsa('[data-block]:not([data-block="' + target + '"])', element => element.slideUp());

        if(qs('[data-block="' + target + '"]').style.maxHeight !== 'inherit') {

            qs('[data-block="' + target + '"]').slideDown();

        } else {

            qs('[data-block="' + target + '"]').slideUp();

        }

    }

}
