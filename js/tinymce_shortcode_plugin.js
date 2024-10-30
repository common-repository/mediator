jQuery(document).ready(function($) {

    tinymce.create('tinymce.plugins.mediator_tech_button_plugin', {
	    
        init : function(ed, url) {

                ed.addCommand('mediator_tech_insert_shortcode', function() {
	                
                    selected = tinyMCE.activeEditor.selection.getContent();

                    if (selected) {
                        
                        content =  selected + '[mediator_tech]';
                        
                    } else {
	                    
                        content =  '[mediator_tech]';
                        
                    }

                    tinymce.execCommand('mceInsertContent', false, content);
                    
                });

            ed.addButton('mediator_tech_button', {title : 'Insert shortcode', cmd : 'mediator_tech_insert_shortcode', image: url + '/shortcode_button.png' });
            
        },
         
    });

    tinymce.PluginManager.add('mediator_tech_button', tinymce.plugins.mediator_tech_button_plugin);
    
});