'use strict';
( function() {
    CKEDITOR.plugins.add( 'dynamic', {
        requires: 'widget,richcombo',
        icons: 'dynamic',
        onLoad: function() {
            // Register styles for placeholder widget frame.
            CKEDITOR.addCss( '.cke_placeholder { background-color:#ff0; padding-left:.1em; padding-right:.1em; } ' +
                '.cke_logic { background-color:#CC66FF; padding-left:.1em; padding-right:.1em; }' +
                '.cke_hide, .head-wrap { display: none; }'
            );
        },
        init: function( editor ) {
            editor.widgets.add( 'placeholder', {
                // Widget code.
                template: '<span class="cke_placeholder">{{}}</span>'
            } );


            editor.widgets.add( 'logic', {
                // Widget code.
                template: '<span class="cke_logic">{%%}</span>'
            } );

            var dynamicVariables = $('form[name=form]').data('dynamic');

            if( dynamicVariables === undefined ) {
                $('#'+editor.name).each(function() {
                    var dynamic = $(this).data('dynamic');
                    if( dynamic !== undefined ) {
                        dynamicVariables = dynamic;
                    }
                });
                if( dynamicVariables === undefined ) {
                    editor.config.removeButtons += ',dsynamic';
                    return;
                }
            }
            var keys = Object.keys(dynamicVariables);

            editor.ui.addRichCombo('Dynamic', {
                label: 'Dynamic Content',
                title: 'Insert Dynamic Content',
                voiceLabel: 'Insert Dynamic Content',
                className: 'cke_format',
                multiSelect: false,
                panel: {
                    css: [editor.config.contentsCss, CKEDITOR.skin.getPath('editor')],
                    voiceLabel: editor.lang.panelVoiceLabel
                },
                init: function () {
                    this.startGroup("Insert Dynamic Content");
                    for (var i in keys) {
                        this.add(keys[i]);
                    }
                },

                onClick: function (key) {
                    editor.focus();
                    editor.fire('saveSnapshot');

                    var $textarea =  $( 'textarea#' + editor.filter.editor.name );
                    // We need to strip html tags from any raw html values for plain text templates
                    if($textarea.hasClass('plain-text') && dynamicVariables[key].indexOf("|raw") > -1){
                        editor.insertHtml(dynamicVariables[key].replace('|raw', '|raw|striptags'));
                    } else {
                        editor.insertHtml(dynamicVariables[key]);
                    }

                    editor.fire('saveSnapshot');
                }
            });
        },

        afterInit: function( editor ) {


            $( '.cke_button__bold', $( 'textarea.plain-text' ).closest( '.form-group' ) ).parent().hide();
            $( 'iframe', $( 'textarea.single-line' ).closest( '.form-group' ) ).parent().css('height', '50px');

            $('.cke_combo_button').click(
                function(){
                    $('.cke_combopanel').addClass('dynamic');
                });

            var placeholderReplaceRegex = /\{\{([^\}]+\{[^\{\}]+\}|[^\}])+[^\{]+\}\}/g;

            var logicReplaceRegex =/\{\%([^\}]+\{[^\{\}]+\}|[^\}])+[^\{]+\%\}/g;

            editor.dataProcessor.dataFilter.addRules( {
                text: function( text, node ) {
                    var dtd = node.parent && CKEDITOR.dtd[ node.parent.name ];

                    // Skip the case when placeholder is in elements like <title> or <textarea>
                    // but upcast placeholder in custom elements (no DTD).
                    if ( dtd && !dtd.span )
                        return;

                    text = text.replace( placeholderReplaceRegex, function( match ) {
                        // Creating widget code.

                        var widgetWrapper = null,
                            innerElement = new CKEDITOR.htmlParser.element( 'span', {
                                'class': 'cke_placeholder'
                            } );

                        // Adds placeholder identifier as innertext.
                        innerElement.add( new CKEDITOR.htmlParser.text( match ) );
                        widgetWrapper = editor.widgets.wrapElement( innerElement, 'placeholder' );

                        if( match.indexOf("include(") > -1 ){
                            widgetWrapper.addClass('cke_hide');
                        }

                        // Return outerhtml of widget wrapper so it will be placed
                        // as replacement.
                        return widgetWrapper.getOuterHtml();
                    } );

                    return text.replace( logicReplaceRegex, function( match ) {
                        // Creating widget code.

                        var widgetWrapper = null,
                            innerElement = new CKEDITOR.htmlParser.element( 'span', {
                                'class': 'cke_logic'
                            } );

                        // Adds placeholder identifier as innertext.
                        innerElement.add( new CKEDITOR.htmlParser.text( match ) );
                        widgetWrapper = editor.widgets.wrapElement( innerElement, 'logic' );


                        // Return outerhtml of widget wrapper so it will be placed
                        // as replacement.
                        return widgetWrapper.getOuterHtml();
                    } );


                }
            } );
        }
    } );

} )();
