jQuery(document).ready(function ($) {

  /**
   * 商品輪播：.carousel ul.products
   * - 一般橫向 Slick，RWD 調整顯示商品數
   */
  if (typeof $.fn.slick === 'function') {
    $('.carousel ul.products').slick({
      slidesToShow: 3,
      slidesToScroll: 1,
      arrows: true,
      prevArrow: '<div class="slick-prev"><button type="button"><span></span></button></div>',
      nextArrow: '<div class="slick-next"><button type="button"><span></span></button></div>',
      responsive: [
        {
          breakpoint: 921,
          settings: {
            slidesToShow: 2
          }
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 1,
            dots: true,
            arrows: false
          }
        }
      ]
    });
  }

/**
 * 首頁banner輪播
 * wp-content\themes\astra-child\inc\frontend\30-index-carousel-shortcode.php
 */
  var $index_carousel = $('.qz-index-carousel__track');

  if ($index_carousel.length && typeof $.fn.slick === 'function') {
    if (!$index_carousel.hasClass('slick-initialized')) {
      $index_carousel.slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        dots: true,
        autoplay: true,
        autoplaySpeed: 5000,
        speed: 600,
        infinite: true,
        pauseOnHover: false,
        pauseOnFocus: false,
        adaptiveHeight: false,
        prevArrow: '<div class="slick-prev"><button type="button"><span></span></button></div>',
        nextArrow: '<div class="slick-next"><button type="button"><span></span></button></div>',
        responsive: [
          { breakpoint: 768, settings: { arrows: false } }
        ]
      });
    }
  }


  /**
   * 頂部跑馬燈：.js-qz-header-marquee
   * - 垂直 Slick，由下往上輪播
   * - 速度從 .qz-header-marquee 的 data-speed 取得
   */

// 跑馬燈：初始化
var $marquee = null;

function initHeaderMarquee() {
  $marquee = $('.js-qz-header-marquee').first();

  if (!$marquee.length) return;
  if (typeof $.fn.slick === 'undefined') return;
  if ($marquee.hasClass('slick-initialized')) return;

  var $bar = $marquee.closest('.qz-header-marquee');
  var speed = parseInt($bar.data('speed'), 10) || 3000;

  $marquee.off('init.qzMarquee').on('init.qzMarquee', function () {
    // slick 抓完高度、結構都建立好之後再顯示
    $bar.css({'opacity': 1, 'visibility': 'visible', 'height': 'auto'});
    if (typeof updateHeaderStickyOffset === 'function') {
      updateHeaderStickyOffset();
    }
  });

  $marquee.slick({
    vertical: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    arrows: false,
    dots: false,
    infinite: true,
    autoplay: true,
    autoplaySpeed: speed,
    pauseOnHover: true,
    cssEase: 'linear',
    adaptiveHeight: false
  });
}



  // 首次載入跑馬燈
  initHeaderMarquee();

  // 依 .qz-header-marquee.float 的高度，調整 sticky header top
  function updateHeaderStickyOffset() {
    var offset = 0;

    if ($marquee && $marquee.length) {
      var $bar = $marquee.closest('.qz-header-marquee');

      if ($bar.length && $bar.hasClass('float')) {
        offset = $bar.outerHeight(); // 当前實際高度
      }
    }
      
    
    var $sticky = $('.ast-sticky-active');
    // console.log($sticky)

    if ($sticky.length) {
      // 有 sticky header：加在 sticky 那一個 header 上
      $sticky.find('.main-header-bar-wrap')
        .css('margin-top', offset ? offset + 'px' : '0');
    } else {
      // 沒有 sticky header：把所有 header margin-top 清回 0
      $('.main-header-bar-wrap').css('margin-top', '0');
    }
  }
  updateHeaderStickyOffset();


  /**
   * WPLoyalty 金額字串處理
   * - 把 "=(TWD)NT$ 18.31" 改成 "= NT$ 18.31"
   * - 只動第一個文字節點，不碰 span 等其他標籤
   */
  $('.wlr-input-point-title').each(function () {
    var node = this.firstChild;

    // 找到第一個文字節點（跳過空白和標籤）
    while (node && node.nodeType !== Node.TEXT_NODE) {
      node = node.nextSibling;
    }

    if (node && node.nodeType === Node.TEXT_NODE) {
      node.textContent = node.textContent.replace(
        /=\([^)]*\)([^\s]+)\s*/,
        '= $1'
      );
    }
  });

  /**
   * WooCommerce 會員專區選單：手機版改下拉
   * - 寬度 <= 921：產生一顆切換按鈕，原本 ul 改成收合
   * - 寬度 > 921：移除按鈕，恢復原本列表
   */
  var QZ_MYACCOUNT_NAV_BREAKPOINT = 921;
  var $nav  = $('.woocommerce-MyAccount-navigation');
  var $list = $nav.find('> ul');
  var inited = false;

  function enableMobileNav() {
    if (inited) return;
    if (!$nav.length || !$list.length) return;

    inited = true;

    // 取目前頁籤名稱當按鈕文字
    var currentText =
      $list.find('li.is-active a').text().trim() ||
      $list.find('li a').first().text().trim() ||
      '會員選單';

    var $toggle = $('<div class="qz-myaccount-nav-toggle"></div>').text(currentText);

    // 按鈕插在 .ast-wooaccount-user-wrapper 之後
    var $userWrap = $('.ast-wooaccount-user-wrapper').first();
    if ($userWrap.length) {
      $userWrap.after($toggle);
    } else {
      // 找不到就放在 nav 前面（保險）
      $nav.before($toggle);
    }

    $nav.addClass('qz-myaccount-nav--mobile');
    $list.hide();

    $toggle.on('click', function () {
      $list.slideToggle(200);
      $(this).toggleClass('is-open');
    });
  }

  function disableMobileNav() {
    if (!inited) return;
    if (!$nav.length || !$list.length) return;

    inited = false;

    $('.qz-myaccount-nav-toggle').remove();
    $nav.removeClass('qz-myaccount-nav--mobile');
    $list.show();
  }

  function checkLayout() {
    // 沒有會員選單就直接不處理，但不要中止整個 ready
    if (!$nav.length || !$list.length) return;

    if (window.innerWidth <= QZ_MYACCOUNT_NAV_BREAKPOINT) {
      enableMobileNav();
    } else {
      disableMobileNav();
    }
  }

  // 初次載入檢查一次會員選單狀態
  checkLayout();



    /** =====================================================
 *  文章頁 - 下拉選單（921px 以下啟用）
 * ===================================================== */
function initDropdown() {
  if ($(window).width() <= 921) {
    // 清除舊事件再重綁，避免重複綁定
    $(".dropdown-title").off(".dropdown");
    $(document).off(".dropdown");
    $(".dropdown-list").off(".dropdown");

    // 點擊展開 / 收起
    $(".dropdown-title").on("click.dropdown", function (e) {
      e.stopPropagation();
      const $this = $(this);
      const $dropdown = $this.next(".dropdown-list");

      if ($this.hasClass("active")) {
        $this.removeClass("active");
        $dropdown.removeClass("active");
      } else {
        $(".dropdown-title, .dropdown-list").removeClass("active");
        $this.addClass("active");
        $dropdown.addClass("active");
      }
    });

    // 點擊外部收起
    $(document).on("click.dropdown", function () {
      $(".dropdown-title, .dropdown-list").removeClass("active");
    });

    // 防止點內部關閉
    $(".dropdown-list").on("click.dropdown", function (e) {
      e.stopPropagation();
    });

  } else {
    // 桌機版一律關閉 dropdown 狀態與事件
    $(".dropdown-title, .dropdown-list").removeClass("active");
    $(".dropdown-title").off(".dropdown");
    $(document).off(".dropdown");
    $(".dropdown-list").off(".dropdown");
  }
}

// 初始執行
initDropdown();



  /**
   * resize：
   * - 會員選單：依寬度切換 mobile / desktop
   * - 跑馬燈：unslick 後重新初始化，讓高度跟著字行數更新
   * - 文章頁 - 下拉選單
   */
  $(window).on('resize', function () {
    // 會員選單 RWD
    checkLayout();

    // 跑馬燈：unslick + 重新初始化 
    if ($marquee && $marquee.length) {
      if ($marquee.hasClass('slick-initialized')) {
        $marquee.slick('unslick');
      }
      initHeaderMarquee();
    }

    setTimeout(() => {
      updateHeaderStickyOffset();
      initDropdown();
    })
    
  });

  // scroll：滾動時也更新一次 sticky header 的 top
  $(window).on('scroll', function () {
    setTimeout(() => {

      updateHeaderStickyOffset();
    })
  });

});


