/* global jQuery, window*/

var FmGui = (function (context, $) {
    'use strict';

    function registerHandlebarTemplates(){
        if (context.fmHandlebarInitialized) {
            return;
        }
        context.Handlebars.registerHelper('fi18n', function(key) {
            var result = context.messages[key] || key;
            return new context.Handlebars.SafeString(result);
        });

        // Pre-compile handlebars templates
        context.tpl = {};

        // Pre-compile handlebars partials
        $('script.handlebars-partial').each(function(k, v) {
            context.Handlebars.registerPartial($(v).attr('id'), $(v).html());
        });

        $('script.handlebars-template').each(function(k, v) {
            context.tpl[$(v).attr('id').substring(3)] = context.Handlebars.compile($(v).html());
        });
        context.fmHandlebarInitialized = 1;
    }

    registerHandlebarTemplates();

    return {
        messages_z_index_counter: 1,

        showCheckUpdate: function () {
            $('.fm-update-check').show();
        },

        hideCheckUpdate: function () {
            $('.fm-update-check').hide();
        },

        show_load_screen: function(callback) {
            var overlay = context.tpl['loading-overlay']({
                paths: context.FmPaths
            });
            $(overlay).hide().prependTo($('body'));
            var attached_overlay = $('.fm-loading-overlay');

            var top = $(document).scrollTop() + 100;
            attached_overlay.find('img').css({'marginTop': top+'px'});

            attached_overlay.fadeIn(300, function() {
                if (callback) {
                    callback();
                }
            });
        },

        hide_load_screen: function(callback) {
            setTimeout(function() {
                $('.fm-loading-overlay').fadeOut(300, function() {
                    $('.fm-loading-overlay').remove();
                    if (callback) {
                        callback();
                    }
                });
            }, 200);
        },

        show_message: function(type, title, message) {
            var overlay = $(context.tpl['message-overlay']({
                'paths': context.FmPaths,
                'type': type,
                'title': title,
                'message': message
            }));

            overlay.hide()
                .css({'z-index': 2999 + FmGui.messages_z_index_counter++})
                .prependTo($('.fm-container'));

            var attached_overlay = $('.fm-message-overlay');
            attached_overlay.slideDown(300);

            attached_overlay.find('.close').bind('click', function() {
                $(this).parent().slideUp(200, function() {
                    $(this).remove();
                });
            });

            setTimeout(function() {
                attached_overlay.find('.close').click();
            }, 12000);
        },

        showUpdateMessage: function(newVersion, currentVersion, downloadURL) {
            if (context.tpl && context.tpl['update-message']) {
                var message = $(context.tpl['update-message']({
                    new_version: newVersion,
                    download_url: downloadURL,
                    current_version: currentVersion
                })).appendTo($('.fm-update-message-container'));
            }
        },
    };
})(window, jQuery);
