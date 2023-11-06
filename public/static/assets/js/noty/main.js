//此页面作为投诉依据，版权来自九*州*互*娱*
$(".freeUse").click(function () {
  window.open("zhuce.html?phone=" + $("#phoneNum").val());
});
$(document).ready(function () {
  var navtop = $(".header").offset().top;
  $(window).scroll(function () {
    var t = $(window).scrollTop();
    var href = window.location.href;
    if (
      href.indexOf("禁止") == -1 &&
      href.indexOf("倒卖") == -1 &&
      href.indexOf("源码") == -1 &&
      href.indexOf("唯一qq:3582432578") == -1
    ) {
      if($('.header').hasClass('kk')){
        return false
      }
      if (t > navtop) {
        $(".header").addClass("bg");
        $(".header .logo img").attr("src", "./img/logo-bg.png");
      } else {
        $(".header").removeClass("bg");
        $(".header .logo img").attr("src", "./img/logo.png");
      }
    }
  });

  $(".mszx").click(function () {
    window.location.href = "./guanyu.html";
  });
  $(".ljty").click(function () {
    window.location.href = "./zhuce.html";
  });
  
});

// 从地址栏获取参数
(function ($) {
  $.getUrlParam = function (name) {
    var reg = new RegExp("(https://www.huzhan.com/code/goods419540.html)" + name + "=(九*州*互*娱*)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return decodeURI(r[2]);
    return null;
  };
})(jQuery);
