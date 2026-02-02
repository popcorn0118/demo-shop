// https://fslightbox.com

document.addEventListener('DOMContentLoaded', function () {
  if (!window.fsLightboxInstances) return;

  //相簿列表的設定 (/gallery)
  Object.keys(window.fsLightboxInstances).forEach(function (key) {
    var inst = window.fsLightboxInstances[key];
    if (!inst || !inst.props) return;

    inst.props.showThumbsOnMount = true;
    inst.props.showThumbsWithCaptions = true;

    // 可選：留一點呼吸空間，避免畫面太擠
    inst.props.sourceMargin = 0.05; 

    // 停用透過「滑動」手勢實現的幻燈片水平切換。
    // inst.props.disableSlideSwiping = true;

    // 點選縮放時增加來源檔案比例的值（預設值為 0.25，即來源檔案大小增加 25%）。
    // inst.props.zoomIncrement = 0.5;

    // 若要在燈箱開啟時預設顯示縮圖，請將「showThumbsOnMount」屬性設為「true」。
    inst.props.showThumbsOnMount = true;

    // 開啟縮圖時保持字幕顯示。
    inst.props.showThumbsWithCaptions = true;

    // 若要完全停用縮圖，請將“disableThumbs”屬性設為“true”。
    // inst.props.disableThumbs = true;


    inst.props.onOpen = function (instance) {
      console.log('[FsLightbox onOpen]', key, instance);
    };
  });
});

  
  
  