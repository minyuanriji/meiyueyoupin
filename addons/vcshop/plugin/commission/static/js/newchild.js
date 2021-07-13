define(['core', 'tpl'], function(core, tpl) {
	var modal = {
		page: 1,
		status: ''
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
	};
	modal.loading = function() {
		modal.page++
	};
	modal.getList = function() {
		core.json('commission/newchild/get_list', {
			page: modal.page,
		}, function(ret) {
			var result = ret.result;
			if (result.list.length <= 0) {
				$('.content-empty').show();
				$('.fui-content').infinite('stop')
			} else {
				$('.content-empty').hide();
				$('.fui-content').infinite('init');
				if (result.list.length <= 0 || result.list.length < result.pagesize) {
					$('.fui-content').infinite('stop')
				}
			}
			modal.page++;
			core.tpl('#container', 'tpl_commission_order_list', result, modal.page > 1);
			FoxUI.according.init()
		})
	};
	return modal
});