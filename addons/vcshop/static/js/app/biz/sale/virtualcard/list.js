define(['core', 'tpl'], function(core, tpl) {
	var modal = {
		page: 1,
		status: 0
	};
	modal.init = function() {
		modal.page = 1;
		$('.fui-content').infinite({
			onLoading: function() {
				modal.getList()
			}
		});
		if (modal.page == 1) {
			modal.getList()
		}
		FoxUI.tab({
			container: $('#tab'),
			handlers: {
				tab0: function() {
					modal.changeTab(0)
				},
				tab1: function() {
					modal.changeTab(1)
				},
				tab2: function() {
					modal.changeTab(2)
				}
			}
		})

		require(['../dist/clipboard.min'], function(Clipboard){
		    var clipboard = new Clipboard('.js-clip', {
		        text: function(e) {
		            return $(e).data('url')||$(e).data('href');
		        }
		    });
		    clipboard.on('success', function(e) {
		        //tip.msgbox.suc('复制成功');
		        alert('复制成功')
		    });
		})

		// $(document).on('click', '.fui-list-group .share-link', function(event) {
		// 	event.preventDefault();
		// 	var id = $(this).data('cid');
		// 	alert(11);
		// 	modal.weixinSendAppMessage(id);
		// });
	};
	modal.changeTab = function(status) {
		$('.fui-content').infinite('init');
		$('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
		modal.page = 1, modal.status = status, modal.getList()
	};
	modal.loading = function() {
		modal.page++
	};
	modal.getList = function() {
		core.json('sale/virtualcard/list/get_list', {
			page: modal.page,
			status: modal.status
		}, function(ret) {
			var result = ret.result;
			if (result.list.length <= 0) {
				$('.content-empty').show();
				$('.fui-content').infinite('stop')
			} else {
				$('.content-empty').hide();
				$('.fui-content').infinite('init');
				if (result.list.length <= 0 || result.list.length < result.pagesize) {
					$('.fui-content').infinite('stop');
				}
			}
			modal.page++;
			core.tpl('#container', 'tpl_order_index_list', result, modal.page > 1);
			FoxUI.according.init()
		})
	};

	modal.weixinSendAppMessage = function(id){
		WeixinJSBridge.invoke('sendAppMessage',{
		//"appid":appId,
		"img_url":'http://www.meiyueyoupin.com/attachment/images/1/2020/07/JMz6r3j91qRYyryR1G16Kg5MqM111Y.png',
		//"img_width":"640",
		//"img_height":"640",
		"link":core.getUrl('sale/virtualcard/exchange', {id: id}),
		"desc":'',
		"title":'积分兑换卡'
		});
	}
	return modal
});