(function () {
    "use strict";
  
    function cacheAllCovers() {
      document.querySelectorAll(".qz-video-player").forEach(function (wrap) {
        if (!wrap.getAttribute("data-cover")) {
          // 初始狀態就是封面，所以直接存起來
          wrap.setAttribute("data-cover", wrap.innerHTML);
        }
      });
    }
  
    // 確保外部檔案不管載入順序如何，都能抓到正確封面
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", cacheAllCovers);
    } else {
      cacheAllCovers();
    }
  
    function stopOtherPlayers(currentWrap) {
      document.querySelectorAll(".qz-video-player").forEach(function (wrap) {
        if (wrap === currentWrap) return;
        if (!wrap.querySelector("iframe")) return;
  
        var cover = wrap.getAttribute("data-cover");
        if (cover) {
          wrap.innerHTML = cover; // 退回封面 = 停止播放
        }
        // ✅ 不要再做 iframe src reload（那就是你 console 累積的來源）
      });
    }
  
    function play(coverEl) {
      var wrap = coverEl.closest(".qz-video-player");
      if (!wrap) return;
  
      // 保底：萬一 DOMContentLoaded 前就點，也能存到 cover
      if (!wrap.getAttribute("data-cover")) {
        wrap.setAttribute("data-cover", wrap.innerHTML);
      }
  
      stopOtherPlayers(wrap);
  
      var html = wrap.getAttribute("data-embed");
      if (!html) return;
      wrap.innerHTML = html;
    }
  
    document.addEventListener("click", function (e) {
      var cover = e.target.closest(".qz-video-cover");
      if (!cover) return;
      play(cover);
    });
  
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Enter" && e.key !== " ") return;
      var cover = e.target.closest(".qz-video-cover");
      if (!cover) return;
      e.preventDefault();
      play(cover);
    });
  })();
  