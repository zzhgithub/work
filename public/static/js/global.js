$(document).ready(function() {
	$('.inactive').click(function(){
		var len=$(".pief_xla .pief_two").length;
		
		for(var i=0; i<len; i++){
			if($(this).is(":hidden")){
			}else{
				 $(".pief_two").hide();	
			}
		}
		$(".pief_xla .inactives").removeClass('inactives');
		if($(this).siblings('ul').css('display')=='none'){
			
			$(this).parent('li').siblings('li').removeClass('inactives');
			$(this).addClass('inactives');
			$(this).siblings('ul').slideDown(200).children('li');
			if($(this).parents('li').siblings('li').children('ul').css('display')=='block'){
				$(this).parents('li').siblings('li').children('ul').parent('li').children('a').removeClass('inactives');
				$(this).parents('li').siblings('li').children('ul').slideUp(200);
			}
		}else{
			//控制自身变成+号
			$(this).removeClass('inactives');
			//控制自身菜单下子菜单隐藏
			$(this).siblings('ul').slideUp(200);
			//控制自身子菜单变成+号
			$(this).siblings('ul').children('li').children('ul').parent('li').children('a').addClass('inactives');
			//控制自身菜单下子菜单隐藏
			$(this).siblings('ul').children('li').children('ul').slideUp(200);

			//控制同级菜单只保持一个是展开的（-号显示）
			$(this).siblings('ul').children('li').children('a').removeClass('inactives');
		}
})
 $('.nav').click(function(e){
	if($(".pief_xla").is(":hidden")){
		$(".pief_xla").animate({left:'0'});	
		$(".pief_xla").show();
		}else{
		$(".pief_xla").animate({left:'-50%'});
		$(".pief_xla").hide(1000);
	}
 });
$(document).bind("click",function(e){
	var target  = $(e.target);
	if(target.closest(".nav,.pief_xla").length == 0){
	  $(".pief_xla").animate({left:'-50%'});	
	  $(".pief_xla").hide(1000);	
	};
	e.stopPropagation();
})
$(".layer h4 span").click(function(){
	$("#layer").hide();	
});

});