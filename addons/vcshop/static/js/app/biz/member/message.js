define(['core', 'tpl'], function(core, tpl) {
	var modal = {
		page: 1,
		type: 1,
		loaded: false
	};
	modal.init = function(params) {
		if (modal.loaded) {
			return
		}
		modal.loaded = true;
		$('.container').empty();
		modal.type = params.type;
		FoxUI.tab({
			container: $('#tab'),
			handlers: {
				// tab1: function() {
				// 	modal.changeTab(0)
				// },
				// tab2: function() {
				// 	modal.changeTab(1)
				// }
			}
		});
		$('.fui-content').infinite({
			onLoading: function() {
				modal.getList()
			}
		});
		if (modal.page == 1) {
			if ($(".goods-item").length <= 0) {
				$(".container").html('');
				modal.getList()
			}
		}

	};
	modal.changeTab = function(type) {
		$('.container').html(''), $('.infinite-loading').show(), $('.content-empty').hide(), modal.page = 1, modal.type = type, modal.getList()
	};
	modal.getList = function() {
		core.json('member/message/get_list', {
			page: modal.page,
			type: modal.type
		}, function(ret) {
			var result = ret.result;
			if (result.total <= 0) {
				$('.container').hide();
				$('.content-empty').show();
				$('.fui-content').infinite('stop')
			} else {
				$('.container').show();
				$('.content-empty').hide();
				$('.fui-content').infinite('init');
				if (result.list.length <= 0 || result.list.length < result.pagesize) {
					$('.fui-content').infinite('stop')
				}
			}
			core.tpl('.container', 'tpl_member_message_list', result, modal.page > 1);
			if ($('.goods-item').length >= result.pagesize) {
				modal.page++
			}
			// jie 20191126 替换字符
			$('.wra').each(function(){
				var t = $(this).html();
				txts=t.replace("\\n", "<br>"); 
				$(this).html(txts);
			});
			
		})
	};
	return modal
});