/**
 * not needed since 4.4.1
 */

document.addEventListener('DOMContentLoaded',function (){
    var inputTags = $('#ACEditor .yeswiki-input-pagetag')
    const existingTagsInternal = 
        (typeof existingTags === 'undefined' || !Array.isArray(existingTags))
        ? []
        : existingTags;
	inputTags.tagsinput({
		typeahead: {
            afterSelect: function(val) {inputTags.tagsinput('input').val(""); },
			source: existingTagsInternal,
            autoSelect:false,
        },
        trimValue: true,
		confirmKeys: [13, 186, 188],
	});
	
	//bidouille antispam
	$(".antispam").attr('value', '1');
})