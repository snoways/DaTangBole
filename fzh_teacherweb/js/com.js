var path_inter="http://60.205.183.98:8001/";
var path_img="http://60.205.183.98:8001/";
var path_js="http://60.205.183.98:8001/fzh_teacherweb";
// var path_inter="http://www.datangbole.com/";
// var path_img="http://www.datangbole.com/";
// var path_js="http://www.datangbole.com/fzh_teacherweb";

var familylink_adr='http://zhushou.360.cn/detail/index/soft_id/3953201?recrefer=SE_D_%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90#nogo';
var familylink_ios='https://itunes.apple.com/cn/app/%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90/id1331818287?mt=8';
var familyma_adr=path_js+'/img/images/family_adr.png';
var familyma_ios=path_js+'/img/images/family_ios.png';

var teacherlink_adr='http://zhushou.360.cn/detail/index/soft_id/3953463?recrefer=SE_D_%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90';
var teacherlink_ios='https://itunes.apple.com/cn/app/%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90%E8%80%81%E5%B8%88/id1331836291?mt=8';
var teacherma_adr=path_js+'/img/images/teacher_adr.png';
var teacherma_ios=path_js+'/img/images/teacher_ios.png';

var shangjialink_adr='http://zhushou.360.cn/detail/index/soft_id/3995055?recrefer=SE_D_%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90';
var shangjialink_ios='';
var shangjiama_adr=path_js+'/img/images/shangjia_adr.png';
var shangjiama_ios=path_js+'/img/images/shangjia_ios.png';

//ios用reloading这种方式 adr用?token传参的方式
//重新加载--用于与app交互用户信息-reloading调adr的initUserInfo,adr再调getuser
function reloading()
{
	if(/android/i.test(navigator.userAgent)){
		javaObject.initUserInfo();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.postParam.postMessage(null);
	}
}
function getuser(phone_type,user_token){
	localStorage.setItem("phone_type",phone_type);//ios,1,andrio:2
	if(user_token==0)
	{
		localStorage.removeItem("user_token");
	}
	else
	{
		localStorage.setItem("user_token",user_token);
	}
}

//正则 手机号
function checktel(tel){
	return /^1[3456789]\d{9}$/.test(tel);
}
function inter_price(price){
	var str=parseFloat(price).toFixed(2);
	str=str.split(".");
	console.log(str);
	return {num_int:str[0],num_num:str[1]}
}
function goback(){
	window.location.href="javascript:history.go(-1);";
}
//获取变量函数
function Getvariable(name)
{
	var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
	var r = window.location.search.substr(1).match(reg);
	if(r!=null)return  decodeURI(r[2]); return null;
}

//开启加载动画-上拉加载
function load_addanimation(className,obj){
	if($("."+className).length>0)
	{
		return true;
	}
	else
	{
		var str='<div class="weui-loadmore weui-loadmorea '+className+'"><i class="weui-loading"></i><span class="weui-loadmore__tips">正在加载...</span></div>';
		obj.append(str);
	}
	
}
//关闭加载动画-上拉加载
function load_closeanimation(className,datalen){
	if(datalen==0||datalen=="")
	{
		$("."+className).html("已经加载完成！");		
	}
	else
	{
		$("."+className).remove();
	}
}
//下拉刷新初始化
function xiala_reload(obj){
	var str='<div class="weui-pull-to-refresh__layer">\
			      <div class="weui-pull-to-refresh__arrow"></div>\
			      <div class="weui-pull-to-refresh__preloader"></div>\
			      <div class="down">下拉刷新</div>\
			      <div class="up">释放刷新</div>\
			      <div class="refresh">正在刷新</div>\
			    </div>';
	obj.prepend(str);
}

//初始化加载中
function pre_loading(state_num){//state_num0正在加载，1加载超时，2加载完成
	$(".loading_pre").remove();
	
	var str='<div class="loading_pre font14"><div class="tableblock"><div>';
	if(state_num==0)
	{
		//str+='数据正在加载...';
		str+='<img src="'+path_js+'/img/loading.gif" style="width:16.5%;" />';
	}
	else if(state_num==1)
	{
		str+='网络连接超时<br>请检查您的联网情况<br><a href="javascript:location.reload();" class="colorzt">单击重新加载</a>';
	}
	else if(state_num==2)
	{
		str+='该商品不存在或已下架';
	}
	else if(state_num==3)
	{
		str+='页面错误，请检查链接';
	}
	else if(state_num==4)
	{
		str+='活动未开启，敬请期待！';
	}
	else if(state_num==5)
	{
		str+='登录过期，请重新登录';
	}
	else if(state_num==100)
	{
		return false;
	}
	str+='</div></div><div>';
	$(document.body).append(str);
}

//判断登录是否失效
function user_overdue(str,msg){
	if(str==0)
	{
		return true;
	}
	else if(str==1)
	{
		return true;
	}
	else if(str==-1)
	{
		$.toast(msg,"text");
		return false;
	}
	else if(str==20003)
	{
		$.toast("登录过期","text");
		setTimeout(function(){
			//跳登录
			go_login();
		},800);
		
		return false;
	}
	else
	{
		return false;
	}
}





//========================app方法
//拨打电话
function botel(str){
	str=''+str;
	if(/android/i.test(navigator.userAgent)){
		$.modal({
          title: str,
          text: "",
          buttons: [
            { text: "呼叫", onClick: function(){
            	 javaObject.callServicePhone(str);
            } },
            { text: "取消", className: "default"}
          ]
        });
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.callPhone.postMessage(str);
	}
}
//去登陆
function go_login(){
	if(/android/i.test(navigator.userAgent)){
		javaObject.gotoLogin();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		 window.webkit.messageHandlers.goLogin.postMessage(null);
	}
}
//在线咨询
function app_contact(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.chat(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.chat.postMessage(id);
	}
}
//导航
function app_daohang(el,zuobiao){
	console.log(el)
	if(/android/i.test(navigator.userAgent)){
		javaObject.goMap(el);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.goMap.postMessage(el);
	}
}
//点击分享给好友 跳的app方法
function app_share(){
	if(/android/i.test(navigator.userAgent)){
		javaObject.app_share();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.app_share.postMessage(null);
	}
}

reloading();