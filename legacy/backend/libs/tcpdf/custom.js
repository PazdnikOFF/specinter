$(document).ready(function(){ 
    /**/ // юзер агент определяем с какого устройства зашли.
    var userDeviceArray = [
    {device: 'Android', platform: /Android/},
    {device: 'iPhone', platform: /iPhone/},
    {device: 'iPad', platform: /iPad/},
    {device: 'Symbian', platform: /Symbian/},
    {device: 'Windows Phone', platform: /Windows Phone/},
    {device: 'Tablet OS', platform: /Tablet OS/},
    {device: 'Linux', platform: /Linux/},
    {device: 'Windows', platform: /Windows NT/},
    {device: 'Macintosh', platform: /Macintosh/}
];
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
            case 'Windows': {
                $('html').addClass('desctopPlatform');
                break
            }
            case 'Macintosh': {
                $('html').addClass('desctopPlatform');
                break
            }
             case 'Linux': {
                $('html').addClass('desctopPlatform');
                break
            }
              case 'Tablet OS': {
                $('html').addClass('mobilePlatform');
                break
            }
               case 'Windows Phone': {
                $('html').addClass('mobilePlatform');
                break
            }
               case 'Symbian': {
                $('html').addClass('mobilePlatform');
                break
            }
               case 'iPad': {
                $('html').addClass('mobilePlatform');
                break
            }
               case 'iPhone': {
                $('html').addClass('mobilePlatform');
                break
            }
               case 'Android': {
                $('html').addClass('mobilePlatform');
                break
            }
    }

	
    
  
    $('.desctopPlatform').find('input:not("#menu"),  select').styler({
        selectSearch : false,
        selectVisibleOptions : 10,
		fileBrowse: 'Загрузить'
    });  // инициализация кастомных форм
   
   
        
    
	
    
    /**/
	$('a.anchor').on("click", function(e){ // плавная прокрутка до якоря . якорь задавать через id
		e.preventDefault();
        
		var $anchor = $(this);
      
		$('html, body').stop().animate({
			scrollTop: $($anchor.attr('href')).offset().top
		}, 1000);  
	});
	/*-----------------------------------------*/
	$('.dropdown-menu').on('click', function(e){
		e.preventDefault();
		return false;
	});
    
    /*-----------------------------------------*/
	$('.adaptive-menu').on('click', function(){
		
	});
    
	$('.js-scroll-pane').jScrollPane({
		autoReinitialise: true,
		showArrows: true,
		horizontalGutter: 15,
		verticalGutter: 15
	});
   
});
$(window).load(function(){
	
	
});
$(window).resize(function(){
	carouselCounter();
});
$(document).ready(function(){
	bTabs();
	sidebarToggle();
	minHeight100();
	carouselCounter();
	decincfield();
	$(".lightGallery").lightGallery(); 
	bxFoto();
});



 /*================= /Валидация форм/    =================*/
$.validator.addMethod("phoneRU", function(phone_number, element) {
	phone_number = phone_number.replace(/\(|\)|\s+|-/g, "");
	return this.optional(element) || phone_number.length > 9 &&
		phone_number.match(/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/);
}, "Пожалуйста введите номер телефона");


$(document).ready(function(){
	$('#form-call-me').validate({
		rules:{
			phone:{
				required: true,
				phoneRU: true, 
				maxlength: 11
			},
			login:{
				required: true,
				maxlength:50
			}
		},
		messages:{
			login:{
				maxlength:'Не больше 50 знаков'
			},
			phone:{
				maxlength:'Не больше 11 знаков',
				phoneRU:'Пожалуйста введите номер телефона'
			}
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-call-me').arcticmodal('close');
			$('#modal-call-me-ok').arcticmodal();
		}
	});
	$('#form-autoriz').validate({
		rules:{
			psw:{
				required: true,
				maxlength: 16
			},
			login:{
				required: true,
				maxlength:50
			}
		},
		messages:{
			login:{
				maxlength:'Не больше 50 знаков'
			},
			psw:{
				maxlength:'Не больше 16 знаков',
			}
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-autoriz').arcticmodal('close');
			$('#modal-autoriz-ok').arcticmodal();
		}
	});
	$('#form-registration').validate({
		rules:{
			psw:{
				required: true,
				maxlength: 16
			},
			email:{
				required: true,
				maxlength:50,
				email: true
			}
		},
		messages:{
			login:{
				maxlength:'Не больше 50 знаков'
			},
			psw:{
				maxlength:'Не больше 16 знаков',
			}
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-registration').arcticmodal('close');
			$('#modal-registration-ok').arcticmodal();
		}
	});
	$('#form-order').validate({
		rules:{
			name:{
				required: true
			},
			phone:{
				required: true,
				phoneRU: true, 
				maxlength: 11
			},
			email:{
				required: true,
				email: true
			},
			'radiogroup-payment':{
				required: true
			}
		},
		messages:{
			phone:{
				maxlength:'Не больше 11 знаков',
				phoneRU:'Пожалуйста введите номер телефона'
			},
			'radiogroup-payment':{
				required: 'Выберите способ оплаты'
			}
		},
		errorPlacement: function(error, element) {
			if (element.attr("name") == "radiogroup-payment") {
				error.insertBefore('.js-payment-method');
			} else {
				error.insertAfter(element);
			}
			return true;
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-order-ok').arcticmodal();
		}
	});
	$('#form-remember-psw').validate({
		rules:{
			email:{
				required: true,
				email: true
			}
		},
		messages:{
			
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-remember-psw').arcticmodal('close');
			$('#modal-remember-psw-ok').arcticmodal();
		}
	});
	$('#form-molding').validate({
		rules:{
			name:{
				required: true
			},
			phone:{
				required: true,
				phoneRU: true, 
				maxlength: 11
			},
			email:{
				required: true,
				email: true
			}
		},
		messages:{
			phone:{
				maxlength:'Не больше 11 знаков',
				phoneRU:'Пожалуйста введите номер телефона'
			}
		},
		submitHandler: function(form) {
			form.submit();
			$('#modal-order-molding-ok').arcticmodal();
		}
	});
}); //end of ready


//Табы
function bTabs() {
    var self = $(this), 
        $tabsNav = $('.js-tab-nav'),
        $tabsCont = $('.js-tab-cont');
	
        
	$tabsNav.on('click', 'li', function(e){
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
		$btnToogle = $('.js-toggle-sidebar'),
		flagToggle = 0;
	var openSidebar = function(){
		$sidebarWrapp.addClass('wrapp-sidebar-on');
		$btnToogle.removeClass('js-open');
		$btnToogle.addClass('js-close');
	};
	var closeSidebar = function(){
		$sidebarWrapp.removeClass('wrapp-sidebar-on');
		$btnToogle.removeClass('js-close');
		$btnToogle.addClass('js-open');
	};
	var hoverSidebar = function(flagToggle){
		if (flagToggle == 1) {
			$sidebar.hover(
				function (e) {
					e.stopPropagation;
					openSidebar();
					return false;
				},
				function (e) {
					e.stopPropagation;
					closeSidebar();
					return false;
				}	
			);
		} else {
			return false;
		}
	}
	if ($('.js-sidebar-not-hover').length > 0) {
		flagToggle = 0;
		$btnToogle.removeClass('js-open');
		$btnToogle.addClass('js-close');
	} else {
		flagToggle = 1;
	}
	hoverSidebar(flagToggle);
	$btnToogle.hover(
		function(e){
			return false;
		},
		function(e){
			return false;
		}
	);
	$btnToogle.on('click', function(e){
		var $this = $(this);
		if($this.hasClass('js-open')) {
			openSidebar();
			$sidebar.unbind('mouseenter mouseleave');
			
			return false;
		}
		if ($this.hasClass('js-close')) {
			closeSidebar();
			hoverSidebar(flagToggle);
			return false;
		}
	});
}

//MIN-HEIGHT 100%
function minHeight100(){
	var windowH = $(window).height(),
		$el = $('.js-minHeight100');
	if ($el.length > 0) {
		$el.css({
			'min-height' : windowH
		});
	}
}

//CAROUSEL COUNTER
function carouselCounter() {
	var $carousel = $('.b-carousel');
	
	var count = function(carousel){
		var $items = carousel.find('.carousel-item').find('.carousel-item__container'),
			$carouselCont = carousel.find('.b-carousel__cont'),
			$countbox = carousel.find('.js-carousel-count'),
			$spanCount = $countbox.find('.js-count'),
			$summItem = $countbox.find('.js-summItem'),
			scopeWidth = $carouselCont.width(),
			scopeOffsetRight = $carouselCont.offset().left + scopeWidth;
		var viewItemFunc = function(){
			var i = 0;
			$items.each(function(){
				var $this = $(this);
				if ($this.offset().left < scopeOffsetRight) {
					i++;
				} else {
					return i;
				}
			});
			$spanCount.html(i);
		};
		
		$summItem.html( $items.length);
		viewItemFunc();
		
		$carouselCont.scroll(function(){
			viewItemFunc();
		});
	}
	
	$carousel.each(function(){
		var $this = $(this);
			count($this);
	});

}

function aboutBG (){
	var $about = $('.b-about'),
		aboutH = $about.height();
	if (aboutH > 780) {
		$about.addClass('b-about_bg-after');
	}
}
if ($('.b-about').length > 0){
	aboutBG();
	$(window).resize(function(){
		aboutBG();
	});
}

function decincfield() {
	var self = $(this),
		$field = $('.b-decinc');
		
	$field.each(function(){
		var $this = $(this),
			$input = $this.find('.amount'),
			$inputVal = $input.val(),
			$decBtn = $this.find('.dec'),
			$incBtn = $this.find('.inc');
		var defaultBtn = function(){
			if ($inputVal <= 1) {
				$decBtn.addClass('default');
			} else {
				$decBtn.removeClass('default');
			}
		}
		defaultBtn();
		
		$input.keypress(function(e){
			var symbol = (e.which) ? e.which : e.keyCode;
			if (symbol < 48 || symbol > 57) { return false;}
		});
		
		$decBtn.on('click', function(){
			$inputVal--;
			if ($inputVal <= 1) {
				$inputVal = 1;
			}
			$input.val($inputVal);
			defaultBtn();
		});
		$incBtn.on('click', function(){
			$inputVal++;
			$input.val($inputVal);
			defaultBtn();
		});
	
	});
	
}


//FOTO SLIDER
function bxFoto(){
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
		nextText:'Еще фото'
	});
	
	var $itemMinFoto = $('.bx-pager_foto').children('div');
	$itemMinFoto.on('click', function(){
		var $this = $(this);
		$itemMinFoto.removeClass('active');
		$this.addClass('active');
	});
};

//CART TABLE
function carttable(){
	var $table = $('.b-cart-table'),
		$tableTr = $table.find('tr'),
		$removeBtn = $table.find('.js-remove');
	$removeBtn.on('click', function(){
		var $this = $(this),
			$tr = $this.parents('tr');
		$tr.fadeOut();
	});
}
carttable();






//FORM ORDER
function formorder(){
	//CART FORM
	var cartform = function(){
		var $dopphone = $('.js-dop-phone'),
			$dopphoneshow = $('.js-dop-phone-show');
		$dopphoneshow.on('click', function(){
			$dopphone.show();
			$dopphoneshow.hide();
		});
	}
	//PAYMENT METHOD
	var paymentmethod = function(){
		var $boxpaymethod = $('.js-payment-method'),
			$radio = $boxpaymethod.find('input[type="radio"]'),
			$label = $boxpaymethod.find('label');
		$label.on('click', function(){
			var $this = $(this);
			$label.removeClass('on');
			$this.addClass('on');
		});
	}
	//PICKUP ADRESS CHECKBOX
	var checkpickup = function(){
		var $fieldPickupAdress = $('.field_pickup-adress'),
			$checkpickup = $('#check-pickup');
		$fieldPickupAdress.hide();
		$checkpickup.on('change', function(){
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
	$li.on('click', function(){
		var $this = $(this);
		$li.removeClass('on');
		$this.addClass('on');
	});
}
numberOfRecords();


//
function formCabinet(){
	var $form = $('.form-cabinet'),
		$telBox = $('.js-form-cabinet-phone-user'),
		$adressBox = $('.form-cabinet-adress-box'),
		$addPhone = $('.js-add-phone'),
		$addAdress = $('.js-add-adress'),
		$removePhone = $('.js-remove-phone'),
		$removeAdress = $('.js-remove-adress');
	var removephone = function(){
		$removePhone = $('.js-remove-phone');
		$removePhone.on('click',function(e){
			e.preventDefault;
			var $this = $(this);
			$this.parents('.field').remove();
		});
	};
	
	var removeadress = function(){
		$removeAdress = $('.js-remove-adress');
		$removeAdress.on('click',function(e){
			e.preventDefault;
			var $this = $(this);
			$this.parents('.form-cabinet-adress-box__item').remove();
		});
	};
	removeadress();
	removephone();
	
	$addPhone.on('click' , function(e){
		e.preventDefault;
		$telBox.append('<div class="field"><input type="text" name="phone" placeholder="Ваш контактный телефон"><span class="hint"><a href="javascript:void(0);" class="i-red i-remove js-remove-phone">Удалить</a></span></div>');
		$removePhone.unbind('click');
		removephone();
	});
	
	$addAdress.on('click' , function(e){
		e.preventDefault;
		$adressBox.append('<div class="form-cabinet-adress-box__item"><div class="field-row field-row_inline"><div class="field field_select"><select name="" id=""><option value="0">Выберите Ваш город</option><option value="1">Екатеринбург</option><option value="2">Нижний Тагил</option></select></div><div class="field field_mid"><input type="text" name="postindex" placeholder="Введите Ваш индекс"></div></div><div class="field-row field-row_adress field-row_just"><div class="field field_long"><input type="text" name="street" placeholder="Улица"></div> 					<div class="field field_small"><input type="text" name="house" placeholder="Дом"></div> <div class="field field_small">	<input type="text" name="porch" placeholder="Подъезд"></div> <div class="field field_small"><input type="text" name="floor" placeholder="Этаж"></div> <div class="field field_small"><input type="text" name="apartment" placeholder="Квартира"></div> </div><div class="field-row"><div class="field field_select">							<select name="" id=""><option value="0">Лифт</option><option value="1">Пассажирский</option><option value="2">Грузовой</option><option value="3">Нет лифта</option></select></div><div class="field"><label for="check-well"><input type="checkbox" id="check-well"> <span>Лифт работает</span></label></div></div><div class="field"><span class="hint"><a href="javascript:void(0);" class="i-red i-remove js-remove-adress">Удалить</a></span></div><hr>			</div>');
		$removeAdress.unbind('click');
		
		 setTimeout(function() {  
		  $('input[type="checkbox"], select').styler();  
		}, 1); 
		removeadress();
	});
	
}
formCabinet();