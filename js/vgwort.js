/*
* VgWort Javascript for backend form
*
*/

pkp.eventBus.$on('form-success', function (formId, response) {
    document.getElementById("vgwortform-vgWort::pixeltag::assign-description").innerHTML = "Status: " + vgWortPixeltagStatusLabels[response['vgWort::pixeltag::status']];
    document.querySelector("input[name='vgWort::pixeltag::assign'][value='true']").disabled = response['vgWort::pixeltag::assign'];
    document.querySelector("input[name='vgWort::pixeltag::assign'][value='false']").disabled = !response['vgWort::pixeltag::assign'];
});
