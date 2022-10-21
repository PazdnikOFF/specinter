/* user scripts */

window.photoViewerConfig = {
  index: 0,
  initMaximized: true,
  initAnimation: true,
  resizable: true,
  draggable: true,
  movable: true,
  keyboard: true,
  headToolbar: ['close'],
  footToolbar: ['zoomIn', 'zoomOut', 'prev', 'next', 'rotateLeft', 'rotateRight'],
  i18n: {
    minimize: 'Свернуть',
    maximize: 'Развернуть',
    close: 'Закрыть',
    zoomIn: 'Увеличить',
    zoomOut: 'Уменьшить',
    prev: 'Назад',
    next: 'Вперед',
    fullscreen: 'На весь экран',
    actualSize: 'Исходный размер',
    rotateLeft: 'Повернуть влево',
    rotateRight: 'Повернуть вправо',
  },
};

var globalViewer, globalViewerShown;

function viewerInitialize() {
  var container = document.querySelector('.content .card-top');
  if (container) {
    if (globalViewer) {
      globalViewer.destroy();
    }
    globalViewer = new Viewer(container, {
      minZoomRatio: 0.1,
      maxZoomRatio: 100,
      toolbar: {
        zoomIn: 4,
        zoomOut: 4,
        oneToOne: 4,
        reset: 4,
        prev: 1,
        play: 4,
        next: 1,
        rotateLeft: 1,
        rotateRight: 1,
        flipHorizontal: 1,
        flipVertical: 1,
      },
      filter: function (img) {
        var parentClassList = img.parentNode.classList;
        return !img.parentNode.classList.contains('swiper-slide') || parentClassList.contains('swiper-slide-visible');
      },
      show: function () {
        globalViewerShown = true;
      },
      hidden: function () {
        globalViewerShown = false;
      }
    });
  }
}

$(document).ready(function () {
  var card = new Swiper('.card-right-gallery', {
    slidesPerView: 3,
    spaceBetween: 20,
    paginationClickable: true,
    loop: true,
    watchSlidesProgress: true,

    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
    },

    on: {
      slideChange: function (){
        if (!globalViewerShown) {
          viewerInitialize();
        }
      }
    },

    navigation: {
      nextEl: document.querySelector('.card-right-inn .card-button-next'),
      prevEl: document.querySelector('.card-right-inn .card-button-prev'),
    }
  });
  viewerInitialize();
});

$(document).ready(function() {
  if ($('.recomended-list').length) {
    var h = 0;
    setTimeout(function() {
      $.each($('.recomended-one'), function() {
      
        if ($(this).height() > h) {
          h = $(this).height();
        }
      });
      $.each($('.recomended-one'), function() {
        $(this).height(h);
      });
    }, 600);
  }
  var scrollTo = function(pos) {
    var pos;
    $('html,body').animate({ scrollTop: pos }, 1000);
    return false;
  };

  $('.j-scroll-to').click(function(event) {
    event.preventDefault();
    var div = $(this).attr('href');
    var toPos = $(div).offset().top;
    scrollTo(toPos);
  });

  /*Модальные окна*/
  var overlay = $('#overlay');
  var open_modal = $('.open-modal');
  var close = $('.modal__close, .modal-close');
  var modal = $('.modal');

  // для открытия модалки нужна ссылка вида <a href="#name"></a> и класс "open_modal"
  // будет открыта модалка с id="name"
  open_modal.click(function(event) {
    modal.fadeOut(200);
    event.preventDefault();
    var div = $(this).attr('href');
    overlay.fadeIn(400);
    $(div).fadeIn(400);
    $('html, body').addClass('j-noScroll');
    baseBoxHeight = $('.mobile-menu__right').height();
  });

  close.click(function() {
    modal.fadeOut(200);
    overlay.fadeOut(200);
    $('html, body').removeClass('j-noScroll');
  });

  overlay.click(function(event) {
    if ($(event.target).attr('id') == 'overlay') {
      $(this).fadeOut(200);
      modal.fadeOut(200);
      $('html, body').removeClass('j-noScroll');
    }
  });

  /*селект*/
  $('.select').click(function(e) {
    if (!$(this).hasClass('j-open')) {
      e.stopPropagation();
      $(this).addClass('j-open');
      $('.select-list').hide();
      $('.select')
        .not(this)
        .removeClass('j-open');
      $(this)
        .find('.select-list')
        .slideDown(200);
    } else {
      $(this)
        .find('.select-list')
        .slideUp(200);
      $(this).removeClass('j-open');
    }
  });

  // подстановка значения по умолчанию
  $('.select').each(function() {
    var val = $(this)
      .find('.select-default')
      .data('name');
    $(this)
      .find('.select-default')
      .addClass('selected');
    $(this)
      .find('input')
      .val(val);
  });

  $('body').click(function() {
    $('.select-list').slideUp(200);
    $('.select').removeClass('j-open');
  });

  $('.select-list__one').click(function(e) {
    e.stopPropagation();
    var val = $(this).data('name');
    $('.select').removeClass('j-open');

    var html = $(this).html();

    $(this)
      .parents('.select')
      .find('input')
      .val(val);
    $(this)
      .parents('.select')
      .find('.garage-selected .garage-item')
      .html(html);
    $(this)
      .parents('.select')
      .find('.select-list')
      .slideUp(200);
    $(this)
      .parents('.select-list')
      .find('.select-list__one')
      .removeClass('selected');
    $(this).addClass('selected');
  });
});
$(document).ready(function() {
  new window.Swiper('.slider-inn', {
	loop: true,
    navigation: {
		nextEl: '.swiper-button-next-st',
		prevEl: '.swiper-button-prev-st',
	},
    pagination: '.swiper-pagination',
    spaceBetween: 10,
    paginationClickable: true,
  });

  $('.detal-list-one').on('click', function(event) {
    window.location = $(this).attr('data-url');
    // if ($(this).hasClass('open')) {
    //     $('.detal-list-one').removeClass('open');
    //     $(this).next('.detal-one').hide();
    // } else {
    //     $('.detal-list-one').removeClass('open');
    //     $('.detal-one').hide();
    //     $(this).addClass('open');
    //     $(this).next('.detal-one').show();
    //
    //     var goods = new Swiper('.detal-one-slider', {
    //         slidesPerView: 1,
    //         spaceBetween: 30,
    //         nextButton: '.swiper-button-next',
    //         prevButton: '.swiper-button-prev'
    //     });
    // }
  });

  $('.good-quant__minus').on('click', function(event) {
    var n = $(this)
      .next('.good-quant__input')
      .val();
    if (n > 1) {
      n--;
      $(this)
        .next('.good-quant__input')
        .val(n);
      updateCart($(this).attr('data-id'), n);
    }
  });

  $('.good-quant__plus').on('click', function(event) {
    var n = $(this)
      .prev('.good-quant__input')
      .val();
    n++;
    updateCart($(this).attr('data-id'), n);
    $(this)
      .prev('.good-quant__input')
      .val(n);
  });

  var updateCart = function(goodId, count) {
    $.ajax({
      url: '/',
      type: 'POST',
      dataType: 'html',
      data: { action: 'updateCart', goodId: goodId, count: count },
    });
  };

  $('.to-cart').on('click', function() {
    good = {
      id: $(this).data('id'),
      img: $(this).data('img'),
      title: $(this).data('title'),
      art: $(this).data('art'),
    };

    $('.good-img img').attr('src', good.img);
    $('.good-title').text(good.title);
    $('.good-art').text(good.art);
  });

  $('.detal-photo')
    .not('.disable')
    .on('mouseenter', function() {
      var url = $(this).data('url');
      $(this).append('<img class="hover-img" src="' + url + '">');
    });

  $('.detal-photo').on('mouseleave', function() {
    $('.hover-img').remove();
  });

  $('.detal-photo.disable').on('mouseenter', function() {
    $(this).append('<div class="tooltip">Нет фото. Товары без фото и цены могут быть в наличии</div>');
  });

  $('.detal-photo.disable').on('mouseleave', function() {
    $('.tooltip').remove();
  });

  $('.tab-menu-one').on('click', function() {
    var idx = $(this).index();
    $('.tab-menu-one').removeClass('active');
    $(this).addClass('active');
    $('.tab-list-one').hide();
    $('.tab-list-one')
      .eq(idx)
      .show();
  });

  $('.garage-add').on('click', function() {
    $(this)
      .hide()
      .next('.garage-new')
      .show();
  });

  $('input[type="checkbox"]').on('change', function() {
    $(this)
      .next('.error')
      .remove();
  });

  /* $(".detals").smoothDivScroll({
        mousewheelScrolling: false,
        manualContinuousScrolling: false,
        hotSpotScrolling: false,
        touchScrolling: true
    });*/

  $('.swiper-pagination').each(function() {
    if ($(this).find('.swiper-pagination-bullet ').length < 2) {
      $(this).hide();
    }
  });

  $('.detal-one__video').each(function() {
    var url = $(this).attr('href');
    var id = url.split('=')[1];
    var link = 'https://img.youtube.com/vi/' + id + '/default.jpg';
    $(this)
      .find('img')
      .attr('src', link);
  });

  $('.detal-one__video').magnificPopup({
    disableOn: 700,
    type: 'iframe',
    mainClass: 'mfp-fade',
    removalDelay: 160,
    preloader: false,

    fixedContentPos: false,
  });

  $('.detal-one-slider').each(function() {
    $(this).magnificPopup({
      delegate: 'a',
      type: 'image',
      closeOnContentClick: false,
      closeBtnInside: false,
      mainClass: 'mfp-with-zoom mfp-img-mobile',
      image: {
        verticalFit: true,
        /*titleSrc: function(item) {
                    return item.el.attr('title') + ' &middot; <a class="image-source-link" href="'+item.el.attr('data-source')+'" target="_blank">image source</a>';
                }*/
      },
      gallery: {
        enabled: true,
      },
      zoom: {
        enabled: true,
        duration: 300, // don't foget to change the duration also in CSS
        opener: function(element) {
          return element.find('img');
        },
      },
    });
  });

 

  var recomended = new Swiper('.recomended-inn', {
    slidesPerView: 5,
    spaceBetween: 50,
	loop:true,

	autoplay: {
			delay: 3000,
			disableOnInteraction: false,
			pauseOnMouseEnter: true,
		},

	navigation: {
		nextEl: '.recomended-button-next',
		prevEl: '.recomended-button-prev',
	},
  });

  var $images = $('.card-right-gallery a, .card-left a').not('.detal-one__video');
  if ($images.length) {
    console.log('count', $images.length);
  }

  /* $('.card-right-gallery a, .card-left a')
    .not('.detal-one__video')
    .magnificPopup({
      type: 'image',
      closeOnContentClick: false,
      closeBtnInside: false,
      mainClass: 'mfp-with-zoom mfp-img-mobile',
      image: {
        verticalFit: true,
 
      },
      gallery: {
        enabled: true,
      },
      zoom: {
        enabled: true,
        duration: 300, // don't foget to change the duration also in CSS
        opener: function(element) {
          return element.find('img');
        },
      },
    }); */

    if($('.content-back_list').length > 0){
      let link = $('.breadcrumbs a:last-of-type').attr('href');
      $('.content-back_list').attr('href',link);
       
    }

    if($('.item-articul').length > 0){
     
      $('.item-articul').each(function(idx,item){
        
        let val = $(item).text();
        if(val.indexOf('/') !== -1){
          console.log($(item).text().indexOf('/'));
          $(item).html($(item).text().replaceAll("/","/<br>"));
        }
      });
    }

    $(document).on('focusin', '.search-input', function(){
      if($('.ajax__searcher a').length > 0){
        $('.ajax__searcher').show();
      }
    });

    if($('.detal-img__slider').length > 0){
      var detalImg = new Swiper('.detal-img__slider .swiper-container', {
        slidesPerView: 1,
		loop:true,

		autoplay: {
			delay: 3000,
			disableOnInteraction: false,
			pauseOnMouseEnter: true,
		},

        navigation: {
          nextEl: '.detal-img__slider .swiper-button-next',
          prevEl: '.detal-img__slider .swiper-button-prev',
        },

        pagination: {
          el: '.detal-img__slider .swiper-pagination',
          type: 'bullets',
          clickable: true,
        },
      });
     
      let gallery = document.querySelector('.detal-img__slider .swiper-wrapper');
   
      
        new Viewer(gallery, {
        minZoomRatio: 0.1,
        maxZoomRatio: 100,
        toolbar: {
          zoomIn: 4,
          zoomOut: 4,
          oneToOne: 4,
          reset: 4,
          prev: 1,
          play: 4,
          next: 1,
          rotateLeft: 1,
          rotateRight: 1,
          flipHorizontal: 1,
          flipVertical: 1,
        },
      });
     
      
    }

    

});
