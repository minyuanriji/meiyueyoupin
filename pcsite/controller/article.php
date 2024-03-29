<?php
if (!(defined('ES_PATH'))) {
	exit('Access Denied');
}

class ArticleController extends Controller
{
	public function index()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and a.status = 1 ';
		$params = array();

		if (!(empty($_GPC['keyword']))) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and a.title like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}


		if (!(empty($_GPC['cate']))) {
			$cateid = intval($_GPC['cate']);
			$condition .= ' and a.cate = :cate';
			$params[':cate'] = $cateid;
		}


		$articles = pdo_fetchall('SELECT a.* ,c.id as cid,c.name FROM ' . tablename('vcshop_system_article') . ' AS a' . "\n" . '                    LEFT JOIN ' . tablename('vcshop_system_category') . ' AS c ON a.cate = c.id and c.status = 1' . "\n" . '                    WHERE 1 ' . $condition . '  ORDER BY a.displayorder DESC LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(1) FROM ' . tablename('vcshop_system_article') . ' as a WHERE 1 ' . $condition, $params);
		$category = pdo_fetchall('select id,name from ' . tablename('vcshop_system_category') . ' where status = 1 order by displayorder asc ');
		$pager = $this->pagination($total, $pindex, $psize);
		$basicset = $this->basicset();
		$title = '最新文章';
		include $this->template('article/index');
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$article = pdo_fetch('SELECT * FROM ' . tablename('vcshop_system_article') . ' AS a' . "\n" . '                    LEFT JOIN ' . tablename('vcshop_system_category') . ' AS c ON a.cate = c.id' . "\n" . '                    WHERE a.id = ' . $id);
		$articles = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_system_article') . ' AS a' . "\n" . '                    WHERE a.status = 1  ORDER BY RAND() DESC LIMIT 4 ');
		$relevant_top = pdo_fetch('SELECT * FROM ' . tablename('vcshop_system_article') . ' AS a' . "\n" . '                    WHERE a.status = 1  ORDER BY RAND()');
		$relevant = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_system_article') . ' AS a' . "\n" . '                    WHERE a.status = 1 ORDER BY RAND() DESC LIMIT 6 ');
		$basicset = $this->basicset();
		$title = $article['title'];
		include $this->template('article/detail');
	}
}


?>