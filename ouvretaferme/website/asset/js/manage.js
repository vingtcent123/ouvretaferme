class WebsiteManage {

    static changeUrlAuto(fieldAuto) {

        const fieldUrl = fieldAuto.firstParent('.webpage-write-url').qs('[name="url"]');

        if(fieldAuto.checked) {
            fieldUrl.classList.add('disabled');
            this.updateUrlAuto();
        } else {
            fieldUrl.classList.remove('disabled');
        }

    }

    static updateUrlAuto(value) {

        const fieldTitle = qs('#webpage-create [name="title"]');
        const fieldUrl = qs('#webpage-create [name="url"]');

        if(fieldUrl.classList.contains('disabled') === false) {
            return;
        }

        fieldUrl.value = fieldTitle.value.toFqn();

    }
    
    static updatePreview() {
        const form = qs('#website-customize');

        const iframe = qs('#website-preview');
        const newSrc = iframe.src
            .setArgument('customDesign', form.qs('[name="customDesign"]').value)
            .setArgument('customColor', form.qs('[name="customColor"]').value)
            .setArgument('customFont', form.qs('[name="customFont"]').value)
            .setArgument('customTitleFont', form.qs('[name="customTitleFont"]').value);
        iframe.src = newSrc;
    }

}

document.delegateEventListener('input', '#webpage-create [name="title"]', () => {

    WebsiteManage.updateUrlAuto();

});

document.delegateEventListener('input', '#website-customize [name="customColor"]', () => {

    WebsiteManage.updatePreview();

});

document.delegateEventListener('change',
    '#website-customize [name="customDesign"], #website-customize [name="customFont"], #website-customize [name="customTitleFont"]', e => {

    WebsiteManage.updatePreview();

});
