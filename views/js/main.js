/**
 * Projet Gam
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
;(function($){

    var NiTube;
    //ready document loaded
    $(function(){

        NiTube = {
            Params:{},
            Initialize:function() {

                NiTube.Params.GloableAjax = false;
                NiTube.Params.Head = $('head');
                NiTube.Params.MainLinks = $('a');
                NiTube.Params.MainContent = $('body .main-page > main');
                NiTube.Params.MainForm = $('form', NiTube.Params.MainContent);

                // Enable Gloable Request Ajax Mode
                if (NiTube.Params.GloableAjax) {
                    NiTube.InitialiseAjax();
                }
            },
            InitialiseAjax: function() {

                //Ajaxing Main Menu
                NiTube.Params.MainLinks.click(NiTube.AjaxMainContent);

                //Ajaxing Main Form
                NiTube.Params.MainForm.submit(NiTube.AjaxMainForm);

            },
            RedirectPage: function(url) {
                window.location.href = url;
            },
            AjaxMainContent: function(url, data) {
                var _href = this.href;

                if (typeof url == 'string') {
                    _href = url;
                }

                if (typeof data == 'undefined') {
                    var data = {};
                }

                if (_href.match('logout') || _href.match('documentation')){
                    return true;
                }

                data.isAjax = true;

                $.post(_href, data, function(response){

                    // ajax resquest update content
                    NiTube.AjaxPageUpdate(response);

                }, 'json');
                return false;
            },
            AjaxMainForm: function(event)
            {
                var form = this;
                var formMain = $(this);

                var formData = formMain.serialize() + '&isAjax=true';
                var formAction = formMain.attr('action');

                if (formAction.match('profil')) {
                    return true;
                } else {
                    event.preventDefault();
                    event.stopPropagation();
                }

                $.post(formAction, formData, function(response){

                    if (response.message) {
                        var alertClass = response.success ? 'alert-success' : 'alert-danger';

                         $('.alert.'+alertClass).remove();

                          formMain.prepend('<div class="alert '+alertClass+'">'+response.message+'</div>');

                          //NiTube.Params.FormErrorFiled.removeClass(alertClass).addClass(addClass).html(response.message);

                        if (response.fields) {
                            var ErrorFileds = response.fields.split('|');

                            for( var name in ErrorFileds) {

                                var Input = $('input[name*="'+ErrorFileds[name]+'"');

                                Input.addClass('border-error-s1');
                            }
                        }
                    }

                    var optinos = {};
                    if (response.email) {
                        optinos.email = response.email;
                    }

                    if (response.username) {
                        optinos.username = response.username;
                    }

                    if (response.current_user) {
                        optinos.message = response.current_user;
                    }

                    if (response.success) {
                        optinos.message = response.success;
                    }

                    if (response.message) {
                        optinos.message = response.message;
                    }

                    if (formAction.match('login') && response.redirect_url) {
                        NiTube.RedirectPage(response.redirect_url);
                        return false;
                    }

                    if (response.redirect_url) {
                        NiTube.AjaxMainContent(response.redirect_url, optinos);
                    }

                    if (response.callback) {
                        eval(response.callback);
                    }

                }, 'json');
                return false;
            },
            AjaxPageUpdate: function(response) {

                // Update Page Title
                if (response.title) {
                    $('head > title').html(response.title);
                }

                // Load Css Styles
                if (response.styles) {
                    for( var link in response.styles) {
                        $('<link type="text/css" href="'+response.styles[link]+'" rel="stylesheet">').appendTo('head');
                    }
                }

                 // Load Js Files
                if (response.javascripts) {
                    for( var link in response.javascripts) {
                        $('<script type="text/javascript" src="'+response.javascripts[link]+'"></script>').appendTo('head');
                    }
                }

                // Update Page Class
                if (response.class) {
                    $('body').attr('class', response.class);
                }

                // Update Main Content
                if (response.content) {
                    NiTube.Params.MainContent.html(response.content);
                }

                //Ajaxing callback
                $('a', $(NiTube.Params.MainContent)).click(NiTube.AjaxMainContent);
                $('form', $(NiTube.Params.MainContent)).submit(NiTube.AjaxMainForm);
            }
        };

         NiTube.Initialize();

    });

})(jQuery);

