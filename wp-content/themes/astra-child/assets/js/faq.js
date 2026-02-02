(function ($) {
  function qzFaqInit() {
    var $root = $('.qz-faq-archive');
    if (!$root.length) return;

    // 模式：all_open | single_first | multi_first_keep
    var mode = ($root.data('accordion-mode') || 'single_first').toString();

    // 是否減少動態效果（A11Y：尊重使用者設定）
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 確保每個 item 有 panel；若你已在 PHP 包了 .qz-faq-panel，這段就會直接吃到
    $root.find('.qz-faq-item').each(function (i) {
      var $item = $(this);
      var $toggle = $item.find('.qz-faq-title > a').first();

      if (!$toggle.length) return;

      // 找/建 panel
      var $panel = $item.find('.qz-faq-panel').first();
      if (!$panel.length) {
        // fallback：把 excerpt + more 包進 panel（避免你沒改 PHP 時失效）
        var $excerpt = $item.find('.qz-faq-excerpt').first();
        var $more = $item.find('.qz-faq-more').first();

        $panel = $('<div class="qz-faq-panel" />');
        if ($excerpt.length) $panel.append($excerpt);
        if ($more.length) $panel.append($more);
        // 插到 title 後面
        $item.find('.qz-faq-title').after($panel);
      }

      // A11Y attributes
      var panelId = 'qz-faq-panel-' + i;
      var toggleId = 'qz-faq-toggle-' + i;

      $toggle.attr({
        id: toggleId,
        role: 'button',
        'aria-controls': panelId,
        'aria-expanded': 'false'
      });

      $panel.attr({
        id: panelId,
        role: 'region',
        'aria-labelledby': toggleId
      });
    });

    function openItem($item) {
      var $toggle = $item.find('.qz-faq-title > a').first();
      var $panel = $item.find('.qz-faq-panel').first();

      $item.addClass('is-open');
      $toggle.attr('aria-expanded', 'true');

      $panel.prop('hidden', false);
      if (reduceMotion) {
        $panel.show();
      } else {
        $panel.stop(true, true).slideDown(180);
      }
    }

    function closeItem($item) {
      var $toggle = $item.find('.qz-faq-title > a').first();
      var $panel = $item.find('.qz-faq-panel').first();

      $item.removeClass('is-open');
      $toggle.attr('aria-expanded', 'false');

      if (reduceMotion) {
        $panel.hide().prop('hidden', true);
      } else {
        $panel.stop(true, true).slideUp(180, function () {
          $panel.prop('hidden', true);
        });
      }
    }

    function setInitialState() {
      var $items = $root.find('.qz-faq-item');
      if (!$items.length) return;

      // 先全部關（可控）
      $items.each(function () {
        var $item = $(this);
        var $panel = $item.find('.qz-faq-panel').first();
        $panel.hide().prop('hidden', true);
        closeItem($item);
      });

      if (mode === 'all_open') {
        $items.each(function () { openItem($(this)); });
        return;
      }

      // single_first / multi_first_keep：預設只開第一筆
      openItem($items.eq(0));
    }

    setInitialState();

    function handleToggle($toggle) {
      var $item = $toggle.closest('.qz-faq-item');
      var isOpen = $item.hasClass('is-open');

      // 點同一筆：就切換開/關（企業級：可逆操作，不強迫單向）
      if (isOpen) {
        closeItem($item);
        return;
      }

      // 要開啟新的一筆
      if (mode === 'single_first') {
        // 關掉所有其他
        $root.find('.qz-faq-item.is-open').each(function () {
          closeItem($(this));
        });
      }

      // multi_first_keep：不關其他（第一筆自然不受影響）
      openItem($item);
    }

    // Click：用 title link 當切換（保留「了解更多」真正導頁）
    $root.on('click', '.qz-faq-title > a', function (e) {
      e.preventDefault();
      handleToggle($(this));
    });

    // Keyboard A11Y：Enter / Space 觸發切換
    $root.on('keydown', '.qz-faq-title > a', function (e) {
      var key = e.key || e.keyCode;
      if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
        e.preventDefault();
        handleToggle($(this));
      }
    });
  }

  $(document).ready(qzFaqInit);
})(jQuery);
