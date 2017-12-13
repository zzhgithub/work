/*将默认提示中文化start*/
jQuery.extend(jQuery.validator.messages, {
    required   : "信息不能为空",
	remote     : "请修正该字段",
	email      : "请输入正确格式的电子邮件",
	url        : "请输入有效的网址",
	date       : "请输入有效的日期",
	dateISO    : "请输入有效的日期  (ISO).",
	number     : "请输入有效的数字",
	digits     : "只能输入整数",
	creditcard : "请输入有效的信用卡号码",
	equalTo    : "你的输入不相同",
	accept     : "请输入有效的后缀",
	maxlength  : jQuery.validator.format("最多可以输入 {0} 个字符"),
	minlength  : jQuery.validator.format("最少要输入 {0} 个字符"),
	rangelength: jQuery.validator.format("请输入长度在 {0} 到 {1} 之间的字符串"),
	range      : jQuery.validator.format("请输入范围在 {0} 到 {1} 之间的数值"),
	max        : jQuery.validator.format("请输入不大于 {0} 的数值"),
	min        : jQuery.validator.format("请输入不小于 {0} 的数值")
});
/*验证demo表单start*/
$(function(){
	jQuery.validator.addMethod('tel',function(value,element){
		var telmatch = /^1[0-9]{10}$/;
		return this.optional(element) || (telmatch.test(value));
	},'请输入正确的手机号码');
   
	$('#login').validate({
		errorElement: 'dd',
		errorClass: 'false',
		validClass: 'right',
		onfocusout: function(element){
	        $(element).valid();
	    },
		errorPlacement: function(error,element){
			element.parent().next().before(error);
		},
		rules: {
			name:{
			 required:true,
			 maxlength:20
			},card:{
			  required:true,
			  number:true,
			  maxlength:18,
			  minlength:18
			},mail:{
			  required:true,
			  email:true,
			
			},phone:{
			  required:true,
			  tel:true,
			},industry:{
			  required:true,
		
			},total:{
			  required:true,
			  number:true,
			  maxlength:4
			}
		},
		messages: {
		
		}
	});	
	
})
/*验证demo表单end*/