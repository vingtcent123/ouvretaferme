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
            .setArgument('customize', 1)
            .setArgument('customDesign', form.qs('[name="customDesign"]').value)
            .setArgument('customWidth', form.qs('[name="customWidth"]').value)
            .setArgument('customColor', form.qs('[name="customColor"]').value)
            .setArgument('customLinkColor', form.qs('[name="customLinkColor"]').value)
            .setArgument('customBackground', form.qs('[name="customBackground"]').value)
            .setArgument('customText', form.qs('[name="customText"]').value)
            .setArgument('customFont', form.qs('[name="customFont"]').value)
            .setArgument('customTitleFont', form.qs('[name="customTitleFont"]').value);
        iframe.src = newSrc;
    }

}

document.delegateEventListener('input', '#webpage-create [name="title"]', () => {

    WebsiteManage.updateUrlAuto();

});

document.delegateEventListener('input', '#website-customize [name="customBackground"], #website-customize [name="customColor"], #website-customize [name="customLinkColor"]', () => {

    WebsiteManage.updatePreview();

});

document.delegateEventListener('change',
    '#website-customize [name="customDesign"], #website-customize [name="customWidth"], #website-customize [name="customText"], #website-customize [name="customFont"], #website-customize [name="customTitleFont"]', e => {

    WebsiteManage.updatePreview();

});
