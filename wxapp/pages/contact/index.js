var app = getApp();
var kunwu = require("../../phpok.js");
var WxParse = require("../../wxParse/wxParse.js");
Page({

	/**
	 * 页面的初始数据
	 */
	data: {
		rs:null
	},

	/**
	 * 生命周期函数--监听页面加载
	 */
	onLoad: function (opts) {
		app.load_wxconfig();
		if(!opts.id){
			opts.id = app.okConfig.params.contact;
		}
		var that = this;
		var url = app.api_url('content','index','id='+opts.id);
		app.json(url,function(data){
			if(!data.status){
				kunwu.dialog.alert(data.info,function(){
					app.tohome(true);
				});
				return false;
			}
			wx.setNavigationBarTitle({
				title: data.info.rs.title
			});
			WxParse.wxParse('article', 'html', data.info.rs.content, that, 5);
			that.setData({
				'rs':data.info.rs
			})
		});
	},

	/**
	 * 生命周期函数--监听页面初次渲染完成
	 */
	onReady: function () {

	},

	/**
	 * 生命周期函数--监听页面显示
	 */
	onShow: function () {

	},

	/**
	 * 生命周期函数--监听页面隐藏
	 */
	onHide: function () {

	},

	/**
	 * 生命周期函数--监听页面卸载
	 */
	onUnload: function () {

	},

	/**
	 * 页面相关事件处理函数--监听用户下拉动作
	 */
	onPullDownRefresh: function () {

	},

	/**
	 * 页面上拉触底事件的处理函数
	 */
	onReachBottom: function () {

	},

	/**
	 * 用户点击右上角分享
	 */
	onShareAppMessage: function () {

	}
})