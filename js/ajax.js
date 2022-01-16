$(document).ready(function () {


    if (location.hash) {
        setTimeout(function () {
            for (i = 0; i < 5; i++) {
                test();
            }
        }, 1500)

        //$(location.hash).addClass('select_item');
    }

    function test() {
        
        $('[data-item="' + location.hash + '"]').toggleClass('select_item', 500);
    }


    if ($('.detal-img img').length && $('.detal-list').height() > $('.detal-img').height()) {

        var max = $('.detal-list').offset().top;

        var boot = $('.detal-list').height() - $('.detal-img').height();


        $(window).scroll(function (e) {

            if (window.pageYOffset > max && (window.pageYOffset < (max + boot))) {

                $('.detal-img').offset({'top': window.pageYOffset});

                //$('.detal-img img').css({'position':'absolute','top':window.pageYOffset - max});

            } else {

                // $('.detal-img img').css({'top':0});

            }


        });

    }


    $('.search-input').focusout(function () {

        setTimeout(function () {

            $('.ajax__searcher').hide();

        }, 500);

    });


    $(document).on('keyup', '.search-input', function () {

        var val = $(this).val();

        if (val.length >= 3) {

            data = {'ajax': 'Y', 'query': val};

            $.ajax({

                url: '/search/',

                type: "POST",

                dataType: "html",

                data: data,

                success: function (data) {

                    $('.ajax__searcher').show();

                    $('.ajax__searcher').html(data);

                }

            });

        }


    });

    $("#fast-order-form").validate({

        rules: {

            name: {

                required: true

            },

            phone: {

                required: true

            },

            personal: {

                required: true

            },

            email: {

                required: true

            },

        },


        messages: {

            name: {

                required: "Поле обязательное для заполнения",

            },

            phone: {

                required: "Поле обязательное для заполнения",

            },

            email: {

                required: "Поле обязательное для заполнения",

            },

            personal: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        }

    });

    $("#order-form").validate({

        rules: {

            name: {

                required: true

            },

            phone: {

                required: true

            },

            personal: {

                required: true

            },

            email: {

                required: true

            },

        },


        messages: {

            name: {

                required: "Поле обязательное для заполнения",

            },

            phone: {

                required: "Поле обязательное для заполнения",

            },

            email: {

                required: "Поле обязательное для заполнения",

            },

            personal: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        }

    });


    $("#order-call").validate({

        rules: {

            name: {

                required: true

            },

            phone: {

                required: true

            },

            personal: {

                required: true

            },

        },


        messages: {

            name: {

                required: "Поле обязательное для заполнения",

            },

            phone: {

                required: "Поле обязательное для заполнения",

            },

            personal: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        }

    });


    $("#personal-reg").validate({

        rules: {

            email: {

                required: true,
                email: true,

            },

            password2: {

                required: true

            },

            password: {

                required: true

            },

            politic: {

                required: true

            }

        },


        messages: {

            email: {

                required: "Поле обязательное для заполнения",
                email: "Проверьте корреткность введенного Email",

            },

            password2: {

                required: "Поле обязательное для заполнения",

            },

            password: {

                required: "Поле обязательное для заполнения",

            },

            politic: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        }

    });


    $("#ul-reg").validate({

        rules: {

            email: {

                required: true,
                email: true,

            },

            password2: {

                required: true

            },

            password: {

                required: true

            },

            politic2: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        },


        messages: {

            email: {

                required: "Поле обязательное для заполнения",
                email: "Проверьте корреткность введенного Email",

            },

            password2: {

                required: "Поле обязательное для заполнения",

            },

            password: {

                required: "Поле обязательное для заполнения",

            },

            politic2: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        }

    });


    $("#login_in").validate({

        rules: {

            login: {

                required: true

            },

            password: {

                required: true

            }

        },


        messages: {

            login: {

                required: "Введите ваш логин",

            },

            password: {

                required: "Введите ваш пароль",

            }

        }

    });


    $("#cart-order").validate({

        rules: {

            name: {

                required: true

            },

            phone: {

                required: true

            },

            personal: {

                required: true

            },

        },


        messages: {

            name: {

                required: "Поле обязательное для заполнения",

            },

            phone: {

                required: "Поле обязательное для заполнения",

            },

            personal: {

                required: "Вы должны дать согласие на обработку персональных данных"

            }

        },

        submitHandler: function (e) {

            //console.log('x');

        }

    });


    $('.cart-send').on('click', function (e) {
        if ($('#cart-order').valid()) {
            $('#cart-order').submit(function (e) {
                if ($('#cart-order').attr('data-send') == 1) {
                    return;
                }
                $('#cart-order').attr('data-send', 1);
                form = $(this);
                //e.preventDefault();
                data = form.serialize();
                $.ajax({

                    url: '/',

                    type: "POST",

                    dataType: "html",

                    data: data,

                    success: function (data) {
                       

                        $('#cart-order').after(data);
                        $('#cart-order').hide();
                        var scrollTop = $('.content__title').offset().top;
                        $(document).scrollTop(scrollTop);

                    }

                });

            });

        }

    });


    //ajax order cart


    $('#garage').submit(function (e) {

        form = $(this);

        e.preventDefault();

        data = form.serialize();

        img = $(this).find('img').attr('src');

        text = $(this).find('#item').val(); //

        data = data + '&img=' + img + '&name=' + text + '&add_garage=Y'

        $.ajax({

            url: '/',

            type: "POST",

            dataType: "html",

            data: data,

            success: function (data) {

                window.location = '/profile/';

            }

        });

    });

    $('.garage-one__remove').click(function (e) {

        var id = $(this).attr('data-id');

        $('#garage' + id).remove();

        data = 'id=' + id + '&remove_garage=1'

        $.ajax({

            url: '/',

            type: "POST",

            dataType: "html",

            data: data,

            success: function (data) {

                //window.location = '/profile/';

            }

        });

    });

    onloadCallback = function() {
        widgetId1 = grecaptcha.render('personal-reg-captcha', {
            'sitekey' : '6LezROsZAAAAACskK-40_i7m_l5KSSvv8NoTSYNn',
            'tabindex' : 100,
            'callback' : verifyCallback,
            'expired-callback' : expiredCallback,
            'error-callback' : errorCallback,
        });
        widgetId2 = grecaptcha.render('ul-reg-captcha', {
            'sitekey' : '6LezROsZAAAAACskK-40_i7m_l5KSSvv8NoTSYNn',
            'tabindex' : 200,
            'callback' : verifyCallback,
            'expired-callback' : expiredCallback,
            'error-callback' : errorCallback,
        });
    };

    function verifyCallback(verifyCallback){
        console.log(verifyCallback);
    }
    function expiredCallback(expiredCallback){

        console.log(expiredCallback);

    }
    function errorCallback(errorCallback){
        console.log(errorCallback);
    }
    $('#personal-reg,#ul-reg').submit(function (e) {
        e.preventDefault();
        form = $(this);
        data = form.serialize();
        console.log(data);
       
        if(form.attr('id') == 'personal-reg'){
            widget = widgetId1;
            captcha = grecaptcha.getResponse(widget);
        }
        else {
            widget = widgetId2;
            captcha = grecaptcha.getResponse(widget);
        }
        console.log(widget);
        console.log(captcha);
        if (!captcha.length) {
          // Выводим сообщение об ошибке
          form.find('.recaptcha-error').text('* Вы не прошли проверку "Я не робот"');
          return false;
        } else {
          // получаем элемент, содержащий капчу
          form.find('.recaptcha-error').text('');
        }
        

        

       
        //data += '&g-recaptcha-response='+captcha;
       
        if (captcha.length) {

            $.ajax({

                url: '/profile/',

                type: "POST",

                //cache: false,
                //dataType: 'json',
                data: data,

                success: function (data) {
                    console.log(data);
                    data = jQuery.parseJSON(data);
                   
                    if(data.error){
                        alert(data.error[0]);
                        
                        console.log(widget);
                        grecaptcha.reset(widget);
                       

                       
                    }
                   
                  
                    if (data.register){
                        //grecaptcha.reset();
                        form.html(data.register);
                    }
                        

                },
                error: function (jqXHR, textStatus, errorThrown) {
                  console.log(jqXHR);
                  console.log(textStatus);
                  console.log(errorThrown);
                }

            });
         
          
        } 
        else {
            //grecaptcha.reset(widget);
        } 

        
       return false;
    });


    $('.logout').click(function (e) {

        data = 'logout=Y';

        $.ajax({

            url: '/profile/',

            type: "POST",

            dataType: "html",

            data: data,

            success: function (data) {

                window.location = data;

            }

        });

    });


    $("#personal").validate({

        rules: {

            company: {

                required: true

            },

            name: {

                required: true

            },

            phone: {

                required: true

            },

            email: {

                required: true,
                email: true,

            }

        },


        messages: {

            company: {

                required: "Поле обязательное для заполнения",

            },

            name: {

                required: "Поле обязательное для заполнения",

            },

            phone: {

                required: "Поле обязательное для заполнения",

            },

            email: {

                required: "Поле обязательное для заполнения",
                email: "Проверьте корреткность введенного Email",

            }

        }

    });


    $('.personal-edit').on('click', function (e) {


    });

    $('.personal-edit').submit(function (e) {

        form = $(this);

        //e.preventDefault();

        data = form.serialize();

        data += '&edit_profile=1';

        $.ajax({

            url: '/',

            type: "POST",

            dataType: "html",

            data: data,

            success: function (data) {

                alert(data);

            }

        });

        return false;

    });


    $("#change-pass").validate({

        rules: {

            'old-password': {

                required: true

            },

            'new-password': {

                required: true

            },

            password: {

                required: true

            }

        },


        messages: {

            'old-password': {

                required: "Поле обязательное для заполнения",

            },

            'new-password': {

                required: "Поле обязательное для заполнения",

            },

            password: {

                required: "Поле обязательное для заполнения",

            }

        }

    });


    $('.change-pass').on('click', function (e) {

        if ($('#change-pass').valid()) {

            $('#change-pass').submit(function (e) {

                form = $(this);

                //e.preventDefault();

                data = form.serialize();

                data += '&change-pass=1';

                $.ajax({

                    url: '/',

                    type: "POST",

                    dataType: "html",

                    data: data,

                    success: function (data) {

                        alert(data);

                    }

                });

            });

        }

    });


    $('#login_in').submit(function (e) {

        form = $(this);

        e.preventDefault();

        data = form.serialize();

        $.ajax({

            url: '/',

            type: "POST",

            dataType: "json",

            data: data,

            success: function (data) {

                if (data.success) {

                    if (window.referer) {

                        window.location = window.referer;

                    } else {

                        window.location = "/catalog/";

                    }

                } else {

                    form.find('.error').html(data.error);

                }

            }

        });

    });


    // ajax form main

    $('#order-form,#order-call').submit(function (e) {

        e.preventDefault();

        form = $(this);

        if (form.valid()) {

            data = form.serialize();

            $.ajax({

                url: '/',

                type: "POST",

                dataType: "html",

                data: data,

                success: function (data) {

                    form.find('.result').html(data);

                }

            });

        }


        return false;

    });

    $('#fast-order-form').submit(function (e) {

        e.preventDefault();

        form = $(this);
        form.find('input[name="page-title"]').val($.trim($('.content__title').text()));
        form.find('input[name="page-url"]').val(window.location.href);

        if (form.valid()) {
            var dataGoods = {},
                result = [],
                data = form.serializeArray();

            $.each($('.table-items .good-quant__input'), function (key, value) {
                dataGoods[$(this).attr('id')] = $(this).val();
            });
           

            dataGoods = JSON.stringify(dataGoods)

            data.push({name: "items", value: dataGoods});

            $.ajax({
                url: '/',
                type: "POST",
                dataType: "html",
                data: data,
                success: function (data) {
                    form.find('.result').html(data);
                }
            });

        }


        return false;

    });


    var cartSumm = function () {

        var summ = 0;

        $('.cart-list-line.variant').each(function () {

            summ = summ + parseInt($(this).find('.good-summ').text(), 10);

        });

        $('.good-total span').text(summ);

    }


    cartSumm();


    $('.good-quant__input, .good-quant__plus, .good-quant__minus').on('click', function () {

        var _this = $(this).parents('.cart-list-line');

        var price = parseInt(_this.find('.good-price').text(), 10);

        var quant = _this.find('.good-quant__input').val();

        _this.find('.good-summ').text(price * quant);

        cartSumm();

    });


    // remove good in cart

    $('.good-remove').click(function () {

        id = $(this).attr('id');

        $(this).parent().parent().remove();


        data = "remove=" + id;

        $.ajax({

            url: '/cart/',

            type: "POST",

            dataType: "html",

            data: data,

            success: function (data) {

                $('.header-cart__qunt').html(data);

                cartSumm();

                location.reload();

            }

        });

    });


    // add cart

    $('.ajax_add_cart').click(function () {

        id = $(this).attr('data-id');

        count = $("#good" + id).val();


        if (!count) {

            count = 1;

        }

        if (count > 0) {

            data = "add_cart=1&id=" + id + "&count=" + count;

            url = '/cart/';

            $.ajax({

                url: url,

                type: "POST",

                dataType: "html",

                data: data,

                success: function (data) {

                    $('.header-cart__qunt span').html(data);

                }

            });


        }


    });


});