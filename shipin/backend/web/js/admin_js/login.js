
	$(function(){
		//随即背景
		list=['/images/admin_img/login_bg1.jpg','/images/admin_img/login_bg2.jpg','/images/admin_img/login_bg3.jpg']
		var ranNum=parseInt((list.length)*Math.random())
		$("#login_bg img").attr('src',list[ranNum])
		//屏幕自适应
		$(window).resize(function(){
				change()
			})	
		change()	
		function change(){
			
			var w=$(window).width();
			var h=$(window).height();
			$("#login_bg img,#lay").css({
				'width':w,
				'height':h
				})
			$("#login_bg img").animate({opacity:1})
			$("#login_box").css({
				'left':(w-460)/2,
				'top':(h-360)/2
			   })
			}
	  //验证表单
	  $("#login_box .b div input").focus(function(){
			 if($(this).val()==this.defaultValue){
				  $(this).val('')
				 }
		  }).blur(function(){
			  id=$(this).attr('id');
			  if($(this).val()=='' || $(this).val()==this.defaultValue){	  
				 if(id=='user'){
					$('.user').addClass('error') ;
					$(this).val(this.defaultValue);
				 }
				 if(id=='psw'){
					$('.psw').addClass('error') ;
					$(this).val(this.defaultValue);
				}
                                if(id=='verifyCode'){
					$('.verifyCode').addClass('error') ;
					$(this).val(this.defaultValue);
				}
			  }else{
				 if(id=='user'){
					$('.user').removeClass('error') ;
				 }
				 if(id=='psw'){
					$('.psw').removeClass('error') ;
				}
                                if(id=='verifyCode'){
					$('.verifyCode').removeClass('error') ;
				}
			 }
		  })
	})
		function Check(){
			 $("#user,#psw,#verifyCode").trigger('blur');
			n=$(".error").length;
			if(n!=0){return false}
		}

	
	