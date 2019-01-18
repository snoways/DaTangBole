//老师详情页 日历用到
var xingqiText = ['日', '一', '二', '三', '四', '五', '六'];
var timer = [{day: "2018-06-14", end: "14:45:00", start: "14:28:00"}, {
	day: "2017-12-22",
	end: "14:45:00",
	start: "14:28:00"
}];

function rili(year, month) {
	var myDate = new Date();
	var n_year = myDate.getFullYear();
	var n_month = myDate.getMonth() + 1;
	var n_day = myDate.getDate();

	myDate.setFullYear(year, month - 1, 1);
	var month_oneday_zhou = myDate.getDay();
	console.log(month_oneday_zhou);
	var all_day = 30;
	if (month == 1 || month == 3 || month == 5 || month == 7 || month == 8 || month == 10 || month == 12) {
		all_day = 31;
	}
	else if (month == 2) {
		if (year % 4 == 0) {
			all_day = 28;
		}
		else {
			all_day = 29;
		}
	}
	data.month_day=[];

	if (month_oneday_zhou != 0) {
		for (var i = 0; i < month_oneday_zhou; i++) {
			data.month_day.push({day: "&nbsp;", isclick: false, now_day: false, check: false});
		}
	}

	if (year < n_year) {
		console.log(1)
		for (var i = 1; i < (all_day + 1); i++) {
			data.month_day.push({day: i, isclick: false, now_day: false, check: false});
		}
	}
	else if (year == n_year) {
		console.log(2)
		if (month < n_month) {
			for (var i = 1; i < (all_day + 1); i++) {
				data.month_day.push({day: i, isclick: false, now_day: false, check: false});
			}
		}
		else if (month == n_month) {
			for (var i = 1; i < (all_day + 1); i++) {
				if (i < n_day) {
					data.month_day.push({day: i, isclick: false, now_day: false, check: false});
				}
				else {
					//判断当前时间是否有空闲时间
					var i_day = i;
					if (i < 10) {
						i_day = "0" + i_day;
					}
					var kx_day = year + "-" + month + "-" + i_day;
					var kx_zt = false;
					for (var j = 0; j < data.shuju.time.length; j++) {
						if (data.shuju.time[j].day == kx_day) {
							kx_zt = true;
							break;
						}
					}
					data.month_day.push({day: i, isclick: kx_zt, now_day: kx_zt, check: false});
//					if(i==n_day)
//					{
//						data.month_day.push({day:i,isclick:kx_zt,now_day:true,check:false});
//					}
//					else
//					{
//						data.month_day.push({day:i,isclick:kx_zt,now_day:true,check:false});
//					}
				}
			}
		}
		else {
			for (var i = 1; i < (all_day + 1); i++) {
				//判断当前时间是否有空闲时间
				var i_day = i;
				if (i < 10) {
					i_day = "0" + i_day;
				}
				var kx_day = year + "-" + month + "-" + i_day;
				var kx_zt = false;
				for (var j = 0; j < data.shuju.time.length; j++) {
					if (data.shuju.time[j].day == kx_day) {
						kx_zt = true;
						break;
					}
				}
				data.month_day.push({day: i, isclick: kx_zt, now_day: kx_zt, check: false});
			}
		}
	}
	else {
		//console.log(3)
		if (parseInt(month) < 10) {
			month = "0" + parseInt(month);
		}
		for (var i = 1; i < (all_day + 1); i++) {
			//判断当前时间是否有空闲时间
			var i_day = i;
			if (i < 10) {
				i_day = "0" + i_day;
			}
			var kx_day = year + "-" + month + "-" + i_day;
			console.log(kx_day)
			var kx_zt = false;
			for (var j = 0; j < data.shuju.time.length; j++) {
				if (data.shuju.time[j].day == kx_day) {
					kx_zt = true;
					break;
				}
			}
			data.month_day.push({day: i, isclick: kx_zt, now_day: false});
		}
	}
//	for(var i=1;i<(all_day+1);i++)
//	{
//		if(year<n_year)
//		{
//			data.month_day.push({day:i,isclick:false});
//		}
//		else if(year==n_year)
//		{
//			if(month<n_month)
//			{
//				data.month_day.push({day:i,isclick:false});
//			}
//		}
//
//	}


//	console.log(data.month_day)
	renderhtml();
}

function time_zh() {
	str = new Date();
	var year = str.getFullYear();
	var month = str.getMonth() + 1;
	var day = str.getDate();
	var hour = str.getHours();
	var minute = str.getMinutes();
	var second = str.getSeconds();
	var zhouji = str.getDay();
	if (month < 10) {
		month = "0" + month;
	}
	if (day < 10) {
		day = "0" + day;
	}
	if (hour < 10) {
		hour = "0" + hour;
	}
	if (minute < 10) {
		minute = "0" + minute;
	}
	if (second < 10) {
		second = "0" + second;
	}
	return {riqi: year + "-" + month + "-" + day, time: hour + ":" + minute + ":" + second, zhou: zhouji};
}

function chushi() {
	var xingqi = time_zh().zhou;
	var local_tome = time_zh().riqi;
	data.selectDay = local_tome + ' 星期' + xingqiText[xingqi];
	data.slect_date = local_tome;
	data.xingqi = "星期" + xingqiText[xingqi];
	var kongxianTimesArr = [];
//			console.log(data.selectDay)
	for (var i = 0; i < data.shuju.time.length; i++) {
		if (data.shuju.time[i].day == local_tome) {
			var start = data.shuju.time[i].start;
			start = start.substring(0, start.lastIndexOf(':'));
			var end = data.shuju.time[i].end;
			end = end.substring(0, end.lastIndexOf(':'));
			kongxianTimesArr.push({text: start + '-' + end, id: data.shuju.time[i].id});
		}
	}
	data.kongxianTimes = kongxianTimesArr;
}