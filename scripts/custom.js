$(document).ready(function () {
    var userDeviceArray = [{
        device: 'Android',
        platform: /Android/
    }, {
        device: 'iPhone',
        platform: /iPhone/
    }, {
        device: 'iPad',
        platform: /iPad/
    }, {
        device: 'Symbian',
        platform: /Symbian/
    }, {
        device: 'Windows Phone',
        platform: /Windows Phone/
    }, {
        device: 'Tablet OS',
        platform: /Tablet OS/
    }, {
        device: 'Linux',
        platform: /Linux/
    }, {
        device: 'Windows',
        platform: /Windows NT/
    }, {
        device: 'Macintosh',
        platform: /Macintosh/
    }];
    var platform = navigator.userAgent;

    function getPlatform() {
        for (var i in userDeviceArray) {
            if (userDeviceArray[i].platform.test(platform)) {
                return userDeviceArray[i].device;
            }
        }
        return 'Неизвестная платформа!' + platform;
    }

    var userplatform = getPlatform();
    switch (userplatform) {
        case 'Windows':
        {
            $('html').addClass('desctopPlatform');
            break
        }
        case 'Macintosh':
        {
            $('html').addClass('desctopPlatform');
            break
        }
        case 'Linux':
        {
            $('html').addClass('desctopPlatform');
            break
        }
        case 'Tablet OS':
        {
            $('html').addClass('mobilePlatform');
            break
        }
        case 'Windows Phone':
        {
            $('html').addClass('mobilePlatform');
            break
        }
        case 'Symbian':
        {
            $('html').addClass('mobilePlatform');
            break
        }
        case 'iPad':
        {
            $('html').addClass('mobilePlatform');
            break
        }
        case 'iPhone':
        {
            $('html').addClass('mobilePlatform');
            break
        }
        case 'Android':
        {
            $('html').addClass('mobilePlatform');
            break
        }
    }
    $('.desctopPlatform').find('input:not("#menu, .hidden-styler"),  select').styler({
        selectSearch: false,
        selectVisibleOptions: 10,
        fileBrowse: 'Загрузить'
    });
    /**/
    $('a.anchor').on("click", function (e) {
        e.preventDefault();
        var $anchor = $(this);
        $('html, body').stop().animate({
            scrollTop: $($anchor.attr('href')).offset().top
        }, 1000);
    });
    $('.js-scroll-pane').jScrollPane({
        autoReinitialise: true,
        showArrows: true,
        horizontalGutter: 15,
        verticalGutter: 15
    });

    // Trunk8
    $(".catalog-name").trunk8({
        lines: 2
    });

    $("#cityOrder").kladr({
        type: $.kladr.type.city,
        select: function (obj) {
            if (obj["name"] == "Екатеринбург") {
                $("#how-delivery_ekb").prop("checked", true).parent().addClass("checked");
                $("#how-delivery_rus").prop("checked", false).parent().removeClass("checked");
				
				//$("#whereDelivery_1").slideDown(100);
                //$("#whereDelivery_2").hide();
                //$("#how_delivery_block").css("display", "none");
                //$(".pickup_method, .ekb_method, .rf_method").hide();
                
				$("#liftOrder").closest(".field-row").show();
                $(".ekb_method").show();
                $("#method_payment_radio").show();
                $(".js-payment-method__item").removeClass("on");
                $("input[name=radiogrouppaymentOrder]").prop("checked", false);
                delete validatorConfig['formOrder'].fields.postindexOrder;
                $(".form-order__result-price").parent().show()
            } else {
                $("#how-delivery_ekb").prop("checked", false).parent().removeClass("checked");
                $("#how-delivery_rus").prop("checked", true).parent().addClass("checked");
                $("#whereDelivery_1").hide();
                $("#whereDelivery_2").slideDown(100);
                $("#how_delivery_block").css("display", "block");
                $("#liftOrder").closest(".field-row").hide();
                $(".pickup_method, .ekb_method, .rf_method").hide();
                $(".rf_method").show();
                $("#method_payment_radio").show();
                $(".js-payment-method__item").removeClass("on");
                $("input[name=radiogrouppaymentOrder]").prop("checked", false);
                validatorConfig['formOrder'].fields.postindexOrder = {
                    noempty: true,
                    caption: "Индекс"
                };
                $(".form-order__result-price").parent().hide()
            }
        }
    });

    $(".cityKladr").kladr({
        type: $.kladr.type.city
    });

    // SLIDER
    $(".b-info-box").slick({
        dots: true,
        autoplay: true,
        slide: ".slider-box",
        fade: false,
        arrows: false
    });

    // Show add params (baget)
    $("#showAddParams").click(function () {
        var $t = $(this),
            $block = $("#addParams"),
            $parentSpan = $t.parent();

        if ($block.is(":visible")) {
            $block.slideUp();
            $t.text("Дополнительные параметры");
            $parentSpan.removeClass("i-minus").addClass("i-pluse");
        }
        else {
            $block.slideDown();
            $t.text("Скрыть дополнительные параметры");
            $parentSpan.removeClass("i-pluse").addClass("i-minus");
        }
    });

    $(".show_notify").bind("click", function(){
        show_notify_function($(this))
    })
});
$(window).load(function () {
});
$(window).resize(function () {
    carouselCounter();
});
$(document).ready(function () {
    bTabs();
    sidebarToggle();
    minHeight100();
    carouselCounter();
    decincfield();
    $(".lightGallery").lightGallery();
    bxFoto();

    $(".image-popup").magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        closeBtnInside: false,
        fixedContentPos: true,
        mainClass: 'mfp-no-margins mfp-with-zoom', // class to remove default margin from left and right side
        image: {
            verticalFit: true
        },
        zoom: {
            enabled: true,
            duration: 500
        }
    });

    $(".magnific-container").each(function () {
        var $t = $(this);
        $t.magnificPopup({
            delegate: "a",
            type: 'image',
            gallery: {
                enabled: true,
                duration: 500
            },
            titleSrc: "title"
        });
    });


});
//Табы
function bTabs() {
    var self = $(this),
        $tabsNav = $('.js-tab-nav'),
        $tabsCont = $('.js-tab-cont');
    $tabsNav.on('click', 'li', function (e) {
        e.preventDefault;
        var $this = $(this),
            thisI = $this.index();
        $tabsNav.find('li').removeClass('on');
        $this.addClass('on');
        $tabsCont.removeClass('on');
        $tabsCont.eq(thisI).addClass('on');
    });
}
//SIDEBAR
function sidebarToggle() {
    var $sidebarWrapp = $('.js-wrapp-sidebar'),
        $sidebar = $('.js-sidebar'),
        $sidebarBtn = $('.js-sidebar').find('.btn'),
        $btnToogle = $('.js-toggle-sidebar'),
        flagToggle = 1;
    var openSidebar = function () {
        $sidebarWrapp.addClass('wrapp-sidebar-on');
        $sidebarWrapp.removeClass('wrapp-sidebar-off');
        $btnToogle.removeClass('js-open');
        $btnToogle.addClass('js-close');

    };
    var closeSidebar = function () {
        $sidebarWrapp.removeClass('wrapp-sidebar-on');
        $sidebarWrapp.addClass('wrapp-sidebar-off');
        $btnToogle.removeClass('js-close');
        $btnToogle.addClass('js-open');

    };
    var resizeSidebar = function () {
        if ($(window).outerWidth() > 1360) {
            flagToggle = 0;
            openSidebar();
        } else {
            flagToggle = 1;
            closeSidebar();
        }
    };
    var loadSidebar = function () {
        if ($(window).outerWidth() > 1360) {
            flagToggle = 0;
            //openSidebar();
        } else {
            flagToggle = 1;
            //closeSidebar();
        }
    };
    loadSidebar();
    /*var hoverSidebar = function(flagToggle) {
     if (flagToggle == 1) {
     $sidebar.hover(function(e) {
     e.stopPropagation;
     openSidebar();
     return false;
     }, function(e) {
     e.stopPropagation;
     closeSidebar();
     return false;
     });
     } else {
     return false;
     }
     }*/
    /*if ($('.js-sidebar-not-hover').length > 0) {
     flagToggle = 0;
     $btnToogle.removeClass('js-open');
     $btnToogle.addClass('js-close');
     } else {
     /*flagToggle = 1;*
     }
     /*hoverSidebar(flagToggle);*/
    $btnToogle.hover(function (e) {
        return false;
    }, function (e) {
        return false;
    });
    /*$btnToogle.on('click', function(e) {
     var $this = $(this);
     if ($this.hasClass('js-open')) {
     openSidebar();
     $sidebar.unbind('mouseenter mouseleave');
     return false;
     }
     if ($this.hasClass('js-close')) {
     closeSidebar();
     /*hoverSidebar(flagToggle);*
     return false;
     }
     }); */

    $sidebar.on('click', function (e) {
        var target = e && e.target || event.srcElement;
        if (target.tagName != 'A') {
            if (flagToggle == 0) {
                flagToggle = 1;
                closeSidebar();
            } else {
                flagToggle = 0;
                openSidebar();
            }
        } else {

        }


    });
    $('#to_cart').on('click', function (e) {
        e.stopPropagation();
    });
    $('.btn.js-stopProp').on('click', function (e) {
        e.stopPropagation();
    });
}
//MIN-HEIGHT 100%
function minHeight100() {
    var windowH = $(window).height(),
        $el = $('.js-minHeight100');
    if ($el.length > 0) {
        $el.css({
            'min-height': windowH
        });
    }
}
//CAROUSEL COUNTER
function carouselCounter() {
    var $carousel = $('.b-carousel');
    var count = function (carousel) {
        var $items = carousel.find('.carousel-item').find('.carousel-item__container'),
            $carouselCont = carousel.find('.b-carousel__cont'),
            $countbox = carousel.find('.js-carousel-count'),
            $spanCount = $countbox.find('.js-count'),
            $summItem = $countbox.find('.js-summItem'),
            scopeWidth = $carouselCont.width(),
            scopeOffsetRight = $carouselCont.offset().left + scopeWidth;
        var viewItemFunc = function () {
            var i = 0;
            $items.each(function () {
                var $this = $(this);
                if ($this.offset().left < scopeOffsetRight) {
                    i++;
                } else {
                    return i;
                }
            });
            $spanCount.html(i);
        };
        $summItem.html($items.length);
        viewItemFunc();
        $carouselCont.scroll(function () {
            viewItemFunc();
        });
    }
    $carousel.each(function () {
        var $this = $(this);
        count($this);
    });
}

function aboutBG() {
    var $about = $('.b-about'),
        aboutH = $about.height();
    if (aboutH > 780) {
        $about.addClass('b-about_bg-after');
    }
}
if ($('.b-about').length > 0) {
    aboutBG();
    $(window).resize(function () {
        aboutBG();
    });
}

function decincfield() {
    var self = $(this),
        $field = $('.b-decinc');
    $field.each(function () {
        var $this = $(this),
            $input = $this.find('.amount'),
            $inputVal = $input.val(),
            $decBtn = $this.find('.dec'),
            $incBtn = $this.find('.inc');
        var defaultBtn = function () {
            if ($inputVal <= 1) {
                $decBtn.addClass('default');
            } else {
                $decBtn.removeClass('default');
            }
        }
        defaultBtn();
        $input.keypress(function (e) {
            var symbol = (e.which) ? e.which : e.keyCode;
            if (symbol < 48 || symbol > 57) {
                return false;
            }
        });
        $decBtn.on('click', function () {
            $inputVal--;
            if ($inputVal <= 1) {
                $inputVal = 1;
            }
            $input.val($inputVal);
            $incBtn.removeClass('default');
            defaultBtn();
        });
        $incBtn.on('click', function () {
            $inputVal++;
            if ($input.attr("max_num") < $inputVal) {
                $inputVal--;
                $incBtn.addClass('default');
                return false;
            }
            $input.val($inputVal);
            defaultBtn();
        });
    });
}
//FOTO SLIDER
function bxFoto() {
    $('.bxslider_foto').bxSlider({
        controls: false,
        pagerCustom: '.bx-pager_foto'
    });
    $('.bx-pager_foto').bxSlider({
        mode: 'vertical',
        minSlides: 3,
        maxSlides: 3,
        slideMargin: 10,
        pager: false,
        nextSelector: '#next-foto',
        nextText: 'Еще фото'
    });
    var $itemMinFoto = $('.bx-pager_foto').children('div');
    $itemMinFoto.on('click', function () {
        var $this = $(this);
        $itemMinFoto.removeClass('active');
        $this.addClass('active');
    });
};
//CART TABLE
function carttable() {
    var $table = $('.b-cart-table'),
        $tableTr = $table.find('tr'),
        $removeBtn = $table.find('.js-remove');
    $removeBtn.on('click', function () {
        var $this = $(this),
            $tr = $this.parents('tr');
        $tr.fadeOut();
    });
}
carttable();
//ACCORDION
function accordion() {
    var $accordItem = $('.accordion-item'),
        $accordContainer = $('.accordion-container');
    $accordContainer.hide();
    $accordItem.on('click', function (event) {
		event.preventDefault;
        var t = event.target || event.srcElement,
            $this = $(this);
        /*if (t.className == 'delivery__link') {
            $this.toggleClass('accordion-item_on');
            $this.next($accordContainer).slideToggle();
        }*/
		$this.toggleClass('accordion-item_on');
        $this.next($accordContainer).slideToggle();
        return false;
    });
}
accordion();
//FORM ORDER
function formorder() {
    //CART FORM
    var cartform = function () {
        var $dopphone = $('.js-dop-phone'),
            $dopphoneshow = $('.js-dop-phone-show');
        $dopphoneshow.on('click', function () {
            $dopphone.show();
            $dopphoneshow.hide();
        });
    }
    //PAYMENT METHOD
    var paymentmethod = function () {
        var $boxpaymethod = $('.js-payment-method'),
            $radio = $boxpaymethod.find('input[type="radio"]'),
            $label = $boxpaymethod.find('label');
        $label.on('click', function () {
            var $this = $(this);
            $label.removeClass('on');
            $this.addClass('on');
        });
    }
    //PICKUP ADRESS CHECKBOX
    var checkpickup = function () {
        var $fieldPickupAdress = $('.field_pickup-adress'),
            $checkpickup = $('#check-pickup');
        $fieldPickupAdress.hide();
        $checkpickup.on('change', function () {
            if ($checkpickup.prop('checked') == true) {
                $fieldPickupAdress.slideDown();
            } else {
                $fieldPickupAdress.slideUp();
                $fieldPickupAdress.find('[type="checkbox"]').prop('checked', false);
            }
        });
    }
    cartform();
    paymentmethod();
    checkpickup();
}
formorder();
//
function numberOfRecords() {
    var $ul = $('.js-number-of-records__list'),
        $li = $ul.find('li');
    $li.on('click', function () {
        var $this = $(this);
        $li.removeClass('on');
        $this.addClass('on');
    });
}
numberOfRecords();
//
function formCabinet() {
    var $form = $('.form-cabinet'),
        $telBox = $('.js-form-cabinet-phone-user'),
        $adressBox = $('.form-cabinet-adress-box'),
        $addPhone = $('.js-add-phone'),
        $addAdress = $('.js-add-adress'),
        $removePhone = $('.js-remove-phone'),
        $removeAdress = $('.js-remove-adress');
    var removephone = function () {
        $removePhone = $('.js-remove-phone');
        $removePhone.on('click', function (e) {
            e.preventDefault;
            var $this = $(this);
            $this.parents('.field').remove();
        });
    };
    var removeadress = function () {
        $removeAdress = $('.js-remove-adress');
        $removeAdress.on('click', function (e) {
            e.preventDefault;
            var $this = $(this);
            $this.parents('.form-cabinet-adress-box__item').remove();
        });
    };
    removeadress();
    removephone();
    $addPhone.on('click', function (e) {
        e.preventDefault;
        $telBox.append('<div class="field"><input type="text" name="phoneContacts[]" placeholder="Ваш контактный телефон" required><span class="hint"><a href="javascript:void(0);" class="i-red i-remove js-remove-phone">Удалить</a></span></div>');
        $removePhone.unbind('click');
        removephone();
    });
    $addAdress.on('click', function (e) {
        var $addressItem = $("#address-item"),
            $clone = $addressItem.clone(0, 0);
        $clone.removeAttr("id").removeAttr("style");
        $clone.find("#addressIdAddress").remove();
        $adressBox.append($clone);
        $removeAdress.unbind('click');
        setTimeout(function () {
            $clone.find('.hidden-styler').styler({
                selectSearch: false,
                selectVisibleOptions: 10,
                fileBrowse: 'Загрузить'
            });
        }, 10);
        removeadress();
    });
    $("body").on("change", ".liftAddress", function () {
        var $t = $(this),
            checked = $t.prop("checked"),
            $input = $t.parent().parent().parent().next();
        if (checked) {
            checked = 1;
        } else {
            checked = 0;
        }
        $input.val(checked);
        return false;
    });
}
formCabinet();
// Info filters
$(".info-filter").on("change", function () {
    var $t = $(this),
        href = $t.attr("data-href");
    window.location.href = href;
});
$(document).ready(function () {
    // Ajax load items
    $(".page-next").click(ELGROW.loadMore);
    $("#authSubmit").click(ELGROW.auth);
    $("#logoutButton").click(ELGROW.logout);
    $(".cabinet-out").click(ELGROW.logout);
    $("body").on("click", ".reload-page", function () {
        window.location.href = window.location.href.split('#')[0];
        console.log('trumpumpum');
    });
});

$(document).ready(function () {
     $("#search-form__button").click(function () { 
		value = $('#quick-search').val();

        if (!value) {
            return false;
        }
		document.location.href = '/catalog/_aall?nameProduct='+value;
		return false;
    });
});

var ELGROW = {
    loadMore: function () {
        var $t = $(this),
            url = $t.attr("href"),
            itemSelector = $t.attr("data-item"),
            containerSelector = $t.attr("data-container"),
            $container = $(containerSelector);
        $.get(url, function (data) {
            var $items = $(containerSelector + " " + itemSelector, $(data)),
                $next = $(".page-next", $(data));
            // Ставим новую ссылку на кнопку загрузки элементов
            if (typeof $next.attr("data-item") !== "undefined") {
                $t.attr("href", $next.attr("href"));
            } else {
                $t.hide();
            }
            $items.appendTo($container);
        });
        return false;
    },
    logout: function () {
        $.post("/users/", {
            mode: "logout"
        }, function (data) {
            document.location.href = '/';
        });
        return false;
    },
    auth: function () {
        var login = $("#loginAuth").val(),
            password = $("#passwordAuth").val();
        if (login == "" || password == "") {
            $("#showErrorsAuth").slideDown();
            return false;
        }
        $("#showErrorsAuth").hide();
        $("#preloaderAuth").show();
        $.post("/users/", {
            login: login,
            password: password,
            mode: "login"
        }, function (data) {
            if (data == "ok") {
                $.arcticmodal("close");
                $("#successSendAuth").arcticmodal();
            } else {
                $("#showErrorsAuth").slideDown().html(data);
            }
            $("#preloaderAuth").hide();
        });
        return false;
    }
};
var successSend = function (param) {
    var id = "#successSend" + param;
    
	if (param == "Faq" || param == "Service" || param == "Baget") {
        if (param == "Baget"){
            //save baget parameters
                var $parent_form =$("#formBaget");
                advanced_settings = "&" + $parent_form.serialize();
                $('#modal-add-parameters').arcticmodal();
        }else {
            $("#send" + param).hide();
        }
        if (param =="BagetParameters"){
            $("#sendBaget").hide();
        }
    }
    if (param != "Baget") {
        $.arcticmodal("close");
        $(id).arcticmodal();
        window.location.hash = param + "?form_send";
    }
};
var successSendNotify = function (param) {
    $.arcticmodal("close");
    $("#show_notify_success").arcticmodal();
};

function show_notify_function(element){
    var data_id=element.attr("data-id"),
        data_type=element.attr("data-type");

    $("#show_notify").find("#typeNotify").val(data_type);
    $("#show_notify").find("#productNotify").val(data_id);
    $("#show_notify").arcticmodal();

    return;
}