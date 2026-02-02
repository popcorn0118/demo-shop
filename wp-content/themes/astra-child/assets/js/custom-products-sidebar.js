jQuery(function ($) {
  var BREAKPOINT = 921;

  $('.qz-prodcat').each(function () {
    var $nav  = $(this);
    var $btn  = $nav.find('.qz-prodcat-toggle');
    var $list = $nav.find('.qz-prodcat-list');
    var isMobile = null; // tri-state，避免重複觸發

    // === 把原本的「產品分類」文字記起來 ===
    var defaultLabel = $.trim($btn.text());

    // ===== 目前分類（只用來算 label，不再預設展開）=====
    var $current = $list.find('.current').first();

    // ===== 初始化父層 toggle（+ / -）的 aria / 符號 =====
    $list.find('.cat-item-parent.has-children').each(function () {
      var $li  = $(this);
      var $tgl = $li.children('.toggle');
      if (!$tgl.length) return;

      var open = $li.hasClass('open');
      $tgl.attr('aria-expanded', String(open));
      // $tgl.find('.toggle-sign').text(open ? '-' : '+');
    });

    // === 依裝置模式更新按鈕文字 ===
    function updateToggleLabel() {
      var label = defaultLabel;

      if (isMobile && $current.length) {
        // 取目前分類 li 底下 a 的文字，沒有 a 再退回 li 文字
        var currentText = $.trim(
          $current.children('a').first().text() || $current.text()
        );
        if (currentText) {
          label = currentText;
        }
      }

      $btn.text(label);
    }

    // ===== 模式切換：手機(折疊) / 桌面(展開) =====
    function setMode() {
      var nowMobile = window.innerWidth < BREAKPOINT;
      if (nowMobile === isMobile) return; // 沒變就不處理
      isMobile = nowMobile;
    
      if (isMobile) {
        // 手機：整體預設關閉
        $nav.addClass('is-mobile').removeClass('open');
        $btn.attr('aria-expanded', 'false');
        $list.removeClass('is-open');
      } else {
        // 桌機：列表顯示，但子分類是否展開由 .open 控制
        $nav.removeClass('is-mobile open');
        $btn.attr('aria-expanded', 'false');
        $list.addClass('is-open');
      }

      updateToggleLabel();
    }
    
    setMode();

    var resizeTimer;
    $(window).on('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setMode, 120);
    });

    // ===== 手機：整體開關（下拉）=====
    $btn.on('click', function () {
      if (!isMobile) return;
      $nav.toggleClass('open');
      var open = $nav.hasClass('open');
      $btn.attr('aria-expanded', String(open));
      $list.toggleClass('is-open', open);
    });

    // ===== 父層展開/收合（手機 + 桌機 都可用）=====
    $list.on('click', '.cat-item-parent > .toggle', function (e) {
      e.preventDefault();

      var $tgl = $(this);
      var $li  = $tgl.closest('.cat-item-parent');
      var open = !$li.hasClass('open');

      $li.toggleClass('open', open);
      $tgl.attr('aria-expanded', String(open));
      // $tgl.find('.toggle-sign').text(open ? '-' : '+');
    });

    // （可選）若想點父層名稱也切換，可打開下列三行
    // $list.on('click', '.cat-item-parent.has-children > a', function (e) {
    //   e.preventDefault();
    //   $(this).siblings('.toggle').trigger('click');
    // });

    // ===== 手機：點外部關閉 =====
    $(document).on('click.qzprodcat', function (e) {
      if (!isMobile) return;
      if (!$(e.target).closest($nav).length) {
        $nav.removeClass('open');
        $btn.attr('aria-expanded', 'false');
        $list.removeClass('is-open');
      }
    });
  });
});
