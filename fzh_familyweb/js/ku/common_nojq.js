var path_inter="http://www.datangbole.com/";
// var path_inter="http://60.205.183.98:8001/";
var path_img="http://www.datangbole.com/";
// var path_img="http://60.205.183.98:8001/";

// var path_js="http://60.205.183.98:8001/fzh_familyweb";
var path_js="http://www.datangbole.com/fzh_familyweb";

var familylink_adr='http://zhushou.360.cn/detail/index/soft_id/3953201?recrefer=SE_D_%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90#nogo';
var familylink_ios='https://itunes.apple.com/cn/app/%E5%A4%A7%E5%94%90%E4%BC%AF%E4%B9%90/id1331818287?mt=8';
var familyma_adr=path_js+'/img/images/family_adr.png';
var familyma_ios=path_js+'/img/images/family_ios.png';

//重新加载--用于与app交互用户信息  reloading调用adr的initUserInfo，adr再调用getuser
//ios用reloading这种方式 adr用?token传参的方式
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



//获取变量函数
function Getvariable(name)
{
	var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
	var r = window.location.search.substr(1).match(reg);
	if(r!=null)return decodeURI(r[2]);return null;
}
//处理苹果手机history.go(-1)返回过来，页面不刷新问题
// 参考:https://blog.csdn.net/Bessicxie/article/details/78351503
function ioshistorygoback(){
  var browserRule = /^.*((iPhone)|(iPad)|(Safari))+.*$/;
  if (browserRule.test(navigator.userAgent)) {
    window.onpageshow = function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    }
  }
}

//正则 手机号
function checktel(tel){
	return /^1[3456789]\d{9}$/.test(tel);
}

//开启加载动画 上拉加载用jqweui做的，引入了jq
function load_addanimation(className,obj){
	obj=obj[0];
	if(document.querySelectorAll("."+className).length>0)
	{
		return true;
	}
	else
	{
		var element=document.createElement('div');
		element.className='weui-loadmore weui-loadmorea '+className;
		element.innerHTML='<i class="weui-loading"></i><span class="weui-loadmore__tips">正在加载...</span>';
		obj.appendChild(element);
	}
}
//关闭加载动画
function load_closeanimation(className,datalen){
	if(datalen==0||datalen=="")
	{
		document.querySelector("."+className).innerHTML='已经加载完成！';
	}
	else
	{
		var obj=document.querySelector("."+className);
		if(obj){
			obj.parentElement.removeChild(obj);
		}
	}
}
//初始化加载中
function pre_loading(state_num){//state_num0正在加载，1加载超时，2加载完成
	var loading_pre=document.getElementsByClassName('loading_pre')[0];
	if(loading_pre){
		loading_pre.parentElement.removeChild(loading_pre);
	}

	var element=document.createElement('div');
	element.className='loading_pre fs14';
	var str='<div class="tableblock"><div>';
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
	str+='</div></div>';
	element.innerHTML=str;
	document.body.appendChild(element);
}

//下拉刷新初始化
function xiala_reload(obj){
	if($('.weui-pull-to-refresh__layer').length==0){
		var str='<div class="weui-pull-to-refresh__layer">\
			      <div class="weui-pull-to-refresh__arrow"></div>\
			      <div class="weui-pull-to-refresh__preloader"></div>\
			      <div class="down">下拉刷新</div>\
			      <div class="up">释放刷新</div>\
			      <div class="refresh">正在刷新</div>\
			    </div>';
		obj.prepend(str);
	}
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
		hui.toast(msg);
		return false;
	}
	else if(str==20003)
	{
		hui.toast('登录过期');
		setTimeout(function(){
			go_login();
		},800);
		return false;
	}
	else
	{
		return false;
	}
}
function inter_price(price){
	var str=parseFloat(price).toFixed(2);
	str=str.split(".");
	return {num_int:str[0],num_num:str[1]}
}







//==============================app方法
//去支付跳转原生 一期的方法，一期我的课程不是4个，只有一个，这个方法现在应该是没用了
//function go_pay(order_sn){
//	if(/android/i.test(navigator.userAgent)){
//		javaObject.pay(order_sn);
//	}
//	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
//		window.webkit.messageHandlers.pay.postMessage(order_sn);
//	}
//}
//拨打电话 调用这个的页面引入了jq
function botel(str){
	str=''+str;
	console.log(str)
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
//拨打电话 在线课堂详情部分web页用 app用这个电话str，调用app的拨打电话弹框
function botel2(str){
	str=''+str;
	console.log(str)
	if(/android/i.test(navigator.userAgent)){
		javaObject.botel2(str);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.botel2.postMessage(str);
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
//判断收藏方法
function iscollect(id,zt){
	console.log(id,zt)
	//zt:-1是为收藏,1是已收藏
	if(/android/i.test(navigator.userAgent)){
		javaObject.iscollect(id,zt);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.isCollect.postMessage({id:id,zt:zt});
	}
}
//咨询
function app_contact(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.chat(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.chat.postMessage(id);
	}
}
//联系机构
function app_contact2(phone){
	console.log(phone)
	if(/android/i.test(navigator.userAgent)){
		javaObject.app_contact2(phone);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.app_contact2.postMessage(phone);
	}
}
//导航
function app_daohang(el,zuobiao){
	console.log(el,zuobiao)
	if(/android/i.test(navigator.userAgent)){
		javaObject.goMap(el);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.goMap.postMessage(el);
	}
}
//=============================二期新增的app方法
//线下活动-活动报名-支付宝支付 sn订单编号
function pay_zfb(sn){
	if(/android/i.test(navigator.userAgent)){
		javaObject.pay_zfb(sn);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.pay_zfb.postMessage(sn);
	}
}
//线下活动-活动报名-微信支付 sn订单编号
function pay_wx(sn){
	if(/android/i.test(navigator.userAgent)){
		javaObject.pay_wx(sn);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.pay_wx.postMessage(sn);
	}
}
//跳转 线上课堂详情    id 线上课堂id
function goOnlineCourseDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.goOnlineCourseDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.goOnlineCourseDet.postMessage(id);
	}
}
//调用 app的分享    id是分享的东西的id
function appshare(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appshare(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appshare.postMessage(id);
	}
}
//调用 app的分享 我的分享页面点击分享按钮调用
function appshare2(){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appshare2();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appshare2.postMessage(null);
	}
}
//跳转到 机构课程支付页面    sn订单编号,title标题,grade年级,class_num课程节数,o_time时间,subject科目 price商品价格减优惠券的剩余价格
function appgoJigouCoursePay(sn,title,grade,class_num,o_time,subject,price){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appgoJigouCoursePay(sn,title,grade,class_num,o_time,subject,price);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appgoJigouCoursePay.postMessage({sn:sn,title:title,grade:grade,class_num:class_num,o_time:o_time,subject:subject,price:price});
	}
}
//跳转 评价课程页面  type(1：机构订单 2:托管订单 3:老师预约订单)  oid 订单id   sn订单编号
function appgotoPingjiaCourse(type,oid,sn){
	console.log(type,oid,sn)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appgotoPingjiaCourse(type,oid,sn);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appgotoPingjiaCourse.postMessage({type:type,oid:oid,sn:sn});
	}
}
//跳转到 搜索结果页   keyword关键字 subject科目筛选
function appgotoSouResult(keyword,subject){
	console.log(keyword,subject)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appgotoSouResult(keyword,subject);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appgotoSouResult.postMessage({keyword:keyword,subject:subject});
	}
}
//跳转到 找老师页面
function appgotoFindTeacher(){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appgotoFindTeacher();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appgotoFindTeacher.postMessage(null);
	}
}
//线下活动 我的课程订单 我的评价三个页面加头部 点击返回按钮执行的app方法
function appbackToApp(){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appbackToApp();
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appbackToApp.postMessage(null);
	}
}
//选中优惠券调用 id红包id price优惠券本身的金额
function appcouponResult(id,price){
	if(/android/i.test(navigator.userAgent)){
		javaObject.couponResult(id,price);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.couponResult.postMessage({id:id,price:price});
	}
}
//跳转到预约订单的支付页面
//sn订单编号 time下单时间 money订单总额 title活动名称 payfrom支付来源
function appPayYuyue(sn,time,money,title,payfrom){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appPayYuyue(sn,time,money,title,payfrom);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appPayYuyue.postMessage({sn:sn,time:time,money:money,title:title,payfrom:payfrom});
	}
}
//跳转到机构订单的支付页面 
//sn订单编号 time下单时间 peopletype会员类型 money订单总额 title活动名称 payfrom支付来源 subject年级科目 classnum总课时
function appPayJigou(sn,time,peopletype,money,title,payfrom,subject,classnum){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appPayJigou(sn,time,peopletype,money,title,payfrom,subject,classnum);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appPayJigou.postMessage({sn:sn,time:time,peopletype:peopletype,money:money,title:title,payfrom:payfrom,subject:subject,classnum:classnum});
	}	
}
//跳转到托管订单的支付页面
function appPayTuoguan(sn,time,peopletype,money,title,payfrom){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appPayTuoguan(sn,time,peopletype,money,title,payfrom);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appPayTuoguan.postMessage({sn:sn,time:time,peopletype:peopletype,money:money,title:title,payfrom:payfrom});
	}
}
//跳转到活动订单的支付页面
//sn订单编号 time下单时间 peopletype会员类型 money订单总额 title活动名称 payfrom支付来源
// function appPayActivity(sn,time,peopletype,money,title,payfrom){
// 	if(/android/i.test(navigator.userAgent)){
// 		javaObject.appPayActivity(sn,time,peopletype,money,title,payfrom);
// 	}
// 	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
// 		window.webkit.messageHandlers.appPayActivity.postMessage({sn:sn,time:time,peopletype:peopletype,money:money,title:title,payfrom:payfrom});
// 	}
// }

function appPayActivity(attr_title,baomin,id,pay_money,start_date,title,order_money,spell_id){
	// console.log("attr_title-->"+attr_title+"baomin-->"+baomin+"id-->"+id+"pay_money-->"+pay_money+"start_date-->"+start_date+"title-->"+title+"order_money-->"+order_money+"spell_id")
	if(/android/i.test(navigator.userAgent)){
		javaObject.appPayActivity(attr_title,baomin,id,pay_money,start_date,title,order_money,spell_id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appPayActivity.postMessage({attr_title:attr_title,baomin:baomin,id:id,pay_money:pay_money,start_date:start_date,title:title,order_money:order_money,spell_id:spell_id});
	}
}

//跳转到app带头部的 我的课程-预约详情    ordersn订单编号
function appOrderdetYuyue(ordersn){
	console.log(ordersn)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appOrderdetYuyue(ordersn);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appOrderdetYuyue.postMessage(ordersn);
	}
}
//跳转到app带头部的 我的课程-活动详情    orderid订单id
function appOrderdetActivity(orderid){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appOrderdetActivity(orderid);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appOrderdetActivity.postMessage(orderid);
	}
}
//跳转到app带头部的 我的课程-机构详情    orderid订单id
function appOrderdetJigou(orderid){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appOrderdetJigou(orderid);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appOrderdetJigou.postMessage(orderid);
	}
}
//跳转到app带头部的 我的课程-托管详情    orderid订单id
function appOrderdetTuoguan(orderid){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appOrderdetTuoguan(orderid);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appOrderdetTuoguan.postMessage(orderid);
	}
}
//跳转到app带头部的 线下活动详情 aid活动id
function appActivitydet(aid){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appActivitydet(aid);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appActivitydet.postMessage(aid);
	}
}
//跳转到app的老师线上课堂视频列表  id 老师id
function appTeacherVideoList(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTeacherVideoList(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTeacherVideoList.postMessage(id);
	}
}
//跳转到 老师视频详情页 id视频id  就是跳goOnlineCourseDet这个
function appTeacherVideoDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.goOnlineCourseDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.goOnlineCourseDet.postMessage(id);
	}
}
//跳转到app的托管视频列表 id 托管id
function appTuoguanVideoList(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTuoguanVideoList(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTuoguanVideoList.postMessage(id);
	}
}
//跳转到 托管视频详情页 id视频id
function appTuoguanVideoDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTuoguanVideoDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTuoguanVideoDet.postMessage(id);
	}
}
//跳转到app的机构视频列表 id 机构id
function appJigouVideoList(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appJigouVideoList(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appJigouVideoList.postMessage(id);
	}
}
//跳转到 机构视频详情页 id视频id
function appJigouVideoDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appJigouVideoDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appJigouVideoDet.postMessage(id);
	}
}
//跳转 机构详情  id机构id
function appJigouDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appJigouDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appJigouDet.postMessage(id);
	}
}
//跳转 托管详情 id托管id
function appTuoguanDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTuoguanDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTuoguanDet.postMessage(id);
	}
}
//跳转 老师详情页 id老师id
function appTeacherDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTeacherDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTeacherDet.postMessage(id);
	}
}
//跳转 机构课程详情 id课程id
function appJigouCourseDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appJigouCourseDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appJigouCourseDet.postMessage(id);
	}
}
//跳转 托管课程详情 id课程id
function appTuoguanCourseDet(id){
	console.log(id)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTuoguanCourseDet(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTuoguanCourseDet.postMessage(id);
	}
}
//跳转 亲子圈/教学圈 个人主页
//option	否	int	1.亲子圈 2.教学圈，默认1
//u_type	是	int	1家长 2.老师 3.商户 4.平台
//uid	是	int	用户id isshoucang是否收藏（true/false）
function appQinziMoreMeIndex(option,u_type,uid,isshoucang){
	console.log(option,u_type,uid)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appQinziMoreMeIndex(option,u_type,uid,isshoucang);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appQinziMoreMeIndex.postMessage({option:option,u_type:u_type,uid:uid,isshoucang:isshoucang});
	}
}
//调用app的播放器
function appDoVideo(videourl){
	console.log(videourl)
	if(/android/i.test(navigator.userAgent)){
		javaObject.appDoVideo(videourl);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appDoVideo.postMessage(videourl);
	}
}
//跳转到app的托管线上课堂视频列表  id 托管id
function appTuoguanCourseOnlineVideoList(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTuoguanCourseOnlineVideoList(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTuoguanCourseOnlineVideoList.postMessage(id);
	}
}
//跳转到app的机构线上课堂视频列表  id 机构id
function appJigouCourseOnlineVideoList(id){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appJigouCourseOnlineVideoList(id);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appJigouCourseOnlineVideoList.postMessage(id);
	}
}
//给app传 老师的名字
function appTeacherName(name){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appTeacherName(name);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appTeacherName.postMessage(name);
	}
}
//跳转到 家长的亲子圈 uid家长的用户id
function appFamilyQinzi(uid){
	if(/android/i.test(navigator.userAgent)){
		javaObject.appFamilyQinzi(uid);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.appFamilyQinzi.postMessage(uid);
	}	
}



// 邀请好友拼团
function friendOpenGroup(spellId,actId){
	// console.log(spellId+"-"+actId)
	if(/android/i.test(navigator.userAgent)){
		javaObject.friendOpenGroup(spellId,actId);
	}
	if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
		window.webkit.messageHandlers.friendOpenGroup.postMessage({spell_id:spellId,pid:actId});
	}	
}


reloading();