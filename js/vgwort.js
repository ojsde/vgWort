/*
* VgWort Javascript for backend form 
*
*/

pkp.eventBus.$on('form-success', function (formId, response) {
    //console.log("formId", formId);
    //console.log("response", response);
    document.getElementById("publicationIdentifiers-vgWort::pixeltag::assign-description").innerHTML = "Status: " + vgWortPixeltagStatusLabels[response['vgWort::pixeltag::status']];
    document.querySelector("input[name='vgWort::pixeltag::assign'][value='true']").disabled = response['vgWort::pixeltag::assign'];
    document.querySelector("input[name='vgWort::pixeltag::assign'][value='false']").disabled = !response['vgWort::pixeltag::assign'];
});
