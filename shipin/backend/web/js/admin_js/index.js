$(function () {

  function wSize() {
    w = $(window).width()
    h = $(window).height()
    $("#openClose,#Main,#Scroll").height(h-80)
    $("#rightMain").height(h - 120)
    $("#Main").width(w - $("#Content .subNav").width() - 5)
  }
  wSize();

  $(window).resize(function () {
    wSize();
  })

  //左侧开关
  $("#openClose").toggle(function () {
    $("#Content .subNav").width(10)
    $(this).removeClass('open').addClass('close');
    $('body').removeClass('body_on').css('background', '#E2E9EA')
    wSize();
  }, function () {
    $("#Content .subNav").width(150)
    $(this).removeClass('close').addClass('open');
    $('body').addClass('body_on')
    wSize();

  });

  $(".nav li").click(function () {
    var i = $(".nav li").index(this)
    $(this).addClass('nav_on').siblings("li").removeClass('nav_on')
    $('#current_pos').html($(this).html() + " > <span></span>")
    $("#Scroll div").hide().eq(i).show()
  })
  $("#Scroll div dl dd").click(function () {
    $("#Scroll div dl dd").removeClass("on")
    $(this).addClass("on")
    $('#current_pos span').html($(this).parent().find('dt').html() + " > " + $(this).html())
  })



})



