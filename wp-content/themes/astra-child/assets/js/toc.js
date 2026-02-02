jQuery(function ($) {

  /* =====================================================
  * 📱 手機版 TOC 開關控制（浮動按鈕開/關）
  * ===================================================== */
  $('#ph-toc-fab').on('click', function () {
    $('#ph-toc-modal, #ph-toc-overlay').addClass('active');
    $(this).attr('aria-expanded', 'true');
  });
  $('.ph-toc-close, #ph-toc-overlay').on('click', function () {
    $('#ph-toc-modal, #ph-toc-overlay').removeClass('active');
    $('#ph-toc-fab').attr('aria-expanded', 'false');
  });


  /* =====================================================
  * 📌 TOC 主邏輯初始化與變數定義
  * ===================================================== */
  var $tocLists = $('.ph-toc-desk .ez-toc-list, .ph-toc-mobile .ez-toc-list');
  var $links = $tocLists.find('a.ez-toc-link[href^="#"]');
  if (!$links.length) return;

  var triggerOffset = 200;   // 偏移量（決定什麼時候觸發 active）
  var sections = [];         // 儲存目標區塊的位置資訊
  let scrollLock = false;    // 已廢用，保留原註解用


  /* =====================================================
  * 🧯 公用工具：處理有特殊符號的 id（保證能選到 DOM）
  * ===================================================== */
  function escSel(id) {
    return (window.CSS && CSS.escape)
      ? CSS.escape(id)
      : id.replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }


  /* =====================================================
  * ⭐ 套用 active 樣式到目錄（支援多種編碼變形）
  * ===================================================== */
  function setActive(id) {
    $tocLists.find('li').removeClass('active ancestor');
    var enc = id,
        dec = decodeURIComponent(id),
        reenc = encodeURIComponent(dec);

    var $currentLinks = $links.filter(
      '[href="#' + enc + '"], [href="#' + dec + '"], [href="#' + reenc + '"]'
    );

    $currentLinks.each(function () {
      var $li = $(this).closest('li');
      $li.addClass('active');
      $li.parents('li').addClass('ancestor'); // 高亮上層節點
    });
  }


  /* =====================================================
  * 📍 重建所有區塊的位置資訊（含 scrollTop 用）
  * ===================================================== */
  function rebuild() {
    sections = [];
    $links.each(function () {
      var raw = this.hash.slice(1);
      var $h = $('#' + escSel(raw));
      if (!$h.length) $h = $('[id="' + decodeURIComponent(raw) + '"]');
      if ($h.length) sections.push({ id: raw, top: $h.offset().top });
    });
    sections.sort(function (a, b) {
      return a.top - b.top;
    });
  }


  /* =====================================================
  * 🎯 主邏輯：根據 scroll 或 click 判斷目前 active 區塊
  * ===================================================== */
  function onScroll(clickId) {
    var idToUse = clickId || null;

    // 若沒指定 id，則用 scrollTop 判斷當前位置
    if (!idToUse) {
      var y = $(window).scrollTop() + triggerOffset;
      var curr = sections[0] && sections[0].id;
      for (var i = 0; i < sections.length; i++) {
        if (sections[i].top <= y) curr = sections[i].id;
        else break;
      }
      idToUse = curr;
    }

    if (idToUse) setActive(idToUse);
  }


  /* =====================================================
  * 🖱️ 點擊目錄時只傳 id 給 onScroll，不做滾動
  * ===================================================== */
  $links.on('click', function (e) {
    e.preventDefault();

    var id = this.hash.slice(1);
    var $t = $('#' + escSel(id));
    if (!$t.length) $t = $('[id="' + decodeURIComponent(id) + '"]');
    if (!$t.length) return;

    onScroll(id); // 統一交給 onScroll 處理狀態（即時反應）
  });

  
/* =====================================================
 * 桌機版目錄/手機版目錄按鈕：滾到一定高度固定，但遇到下方上/下篇文章(.ph-post-extras) 解除
 * ===================================================== */
function handleStickyTOC() {
  var $toc     = $('.ph-toc-desk');
  var $tocWarp = $('.ph-toc-desk-warp');
  var $extras  = $('.ph-post-extras'); // 滿版區塊
  if (!$toc.length) return;

  // 桌機版目錄顯示/隱藏

  var SAFE_TOP = 110; // 你的原始門檻
  var GAP      = 200;  // 與下方區塊保持的間距

  // 是否達到吸頂高度
  var topDistance = $toc.offset().top - $(window).scrollTop();
  var reachedStick = (topDistance <= SAFE_TOP);

  // 是否會撞到 .ph-post-extras
  var willHitExtras = false;
  if ($extras.length) {
    var tocRect   = $toc[0].getBoundingClientRect();
    var exTop     = $extras[0].getBoundingClientRect().top;
    // 吸頂後的預期 bottom 位置 ≈ SAFE_TOP + 元件高度
    var bottomIfSticky = SAFE_TOP + $toc.outerHeight();
    // 未吸頂時使用實際 bottom
    var bottomNow = tocRect.top + tocRect.height;

    var tocBottom = reachedStick ? bottomIfSticky : bottomNow;
    willHitExtras = (tocBottom + GAP >= exTop);
  }

  if (reachedStick && !willHitExtras) {
    $tocWarp.addClass('active');
  } else {
    $tocWarp.removeClass('active');
  }


  // === 手機幕幕按鈕出現時機(#ph-toc-fab)：進入內容區才顯示(#primary) ===
  var $fab     = $('#ph-toc-fab');
  var $primary = $('#primary');

  if ($fab.length && $primary.length) {
    var pr = $primary[0].getBoundingClientRect();
    var vh = window.innerHeight || document.documentElement.clientHeight;

    // 調這兩個數字：越大 = 越早觸發
    var ENTER_OFFSET_TOP    = 400; // #primary 還在視窗外時就提早出現
    var EXIT_OFFSET_BOTTOM  = 320; // 接近底部時提早關閉（避免「最下面」才消失）

    // 進入判斷：#primary 的頂邊已經「靠近」視窗頂端（<= ENTER_OFFSET_TOP）
    var entered = (pr.top <= ENTER_OFFSET_TOP) && (pr.bottom > 0);

    // 離開判斷：#primary 的底邊已經「靠近」視窗頂端（<= EXIT_OFFSET_BOTTOM）
    var leavingBottom = (pr.bottom <= EXIT_OFFSET_BOTTOM);

    if (entered && !leavingBottom) {
      $fab.addClass('active');
    } else {
      $fab.removeClass('active');
    }
  }


}



  /* =====================================================
  * 📏 桌機版：active 狀態下同步調整 TOC 寬度
  * ===================================================== */
  function updateTOCWidth() {
    var $toc = $('.ph-toc-desk');
    var $tocWarp = $('.ph-toc-desk-warp');
    if (!$toc.length) return;

    if ($(window).width() >= 921) {
      if ($tocWarp.hasClass('active')) {
        $tocWarp.css('width', $toc.outerWidth());
      } else {
        $tocWarp.css('width', '');
      }
    } else {
      $tocWarp.css('width', '');
    }
  }


  /* =====================================================
  * 🚀 初始執行：建構 sections 資料與綁定 scroll
  * ===================================================== */
  rebuild();
  let ticking = false;

  $(window).on('scroll', function () {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(function () {
        onScroll();            // 根據目前位置更新 active
        handleStickyTOC();     // 處理浮動狀態
        updateTOCWidth();      // 更新 warp 寬度
        ticking = false;
      });
    }
  });


  /* =====================================================
  * 🔄 resize / orientationchange 時重新建構邏輯
  * ===================================================== */
  let resizeTimer;
  $(window).on('resize orientationchange', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      rebuild();              // 重新抓每個 section 的位置
      onScroll();             // 重新判斷 active 狀態
      handleStickyTOC();      // 重算浮動條件
      updateTOCWidth();       // 重新調整寬度

      // 若為桌機版，強制關閉手機目錄
      if ($(window).width() > 921) {
        $('#ph-toc-modal, #ph-toc-overlay').removeClass('active');
        $('#ph-toc-fab').attr('aria-expanded', 'false');
      }
    }, 200);
  });

  


});
