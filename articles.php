<?php
/**
 * Articles
 *
 * Eresus 2
 *
 * Плагин обеспечивает возможность публикации на сайте статей
 *
 * @version 2.12
 *
 * @copyright   2005-2007, ProCreat Systems, http://procreat.ru/
 * @copyright   2007-2009, Eresus Group, http://eresus.ru/
 * @license     http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author      Mikhail Krasilnikov <mk@procreat.ru>
 * @author      БерсЪ <bersz@procreat.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо по вашему выбору с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package Plugins
 * @subpackage Articles
 */

/**
 *
 * @var int
 */
define('_ARTICLES_BLOCK_NONE', 0);

/**
 *
 * @var int
 */
define('_ARTICLES_BLOCK_LAST', 1);

/**
 *
 * @var int
 */
define('_ARTICLES_BLOCK_MANUAL', 2);

/**
 *
 * @var string
 */
define('_ARTICLES_TMPL_BLOCK', '<img src="'.httpRoot.'core/img/info.gif" width="16" height="16" alt="" title="Показывать в блоке">');



/**
 * Класс плагина
 *
 * @package Plugins
 * @subpackage Articles
 */
class TArticles extends TListContentPlugin {

	/**
	 * Имя плагина
	 * @var string
	 */
	var $name = 'articles';

	/**
	 * Тип плагина
	 * @var string
	 */
	var $type = 'client,content,ondemand';

	/**
	 * Название плагина
	 * @var string
	 */
	var $title = 'Статьи';

	/**
	 * Версия плагина
	 * @var string
	 */
	var $version = '2.12a';

	/**
	 * Описание плагина
	 * @var string
	 */
	var $description = 'Публикация статей';

	/**
	 * Настройки плагина
	 * @var array
	 */
	var $settings = array(
		'itemsPerPage' => 10,
		'tmplListItem' => '
			<div class="ArticlesListItem">
				<h3>$(caption)</h3>
				$(posted)<br />
				<img src="$(thumbnail)" alt="$(caption)" width="$(thumbnailWidth)" height="$(thumbnailHeight)" />
				$(preview)
				<div class="controls">
					<a href="$(link)">Полный текст...</a>
				</div>
			</div>
		',
		'tmplItem' => '
			<div class="ArticlesItem">
				<h1>$(caption)</h1><b>$(posted)</b><br />
				<img src="$(image)" alt="$(caption)" width="$(imageWidth)" height="$(imageHeight)" style="float: left;" />
				$(text)
				<br /><br />
			</div>
		',
		'tmplBlockItem' => '<b>$(posted)</b><br /><a href="$(link)">$(caption)</a><br />',
		'previewMaxSize' => 500,
		'previewSmartSplit' => true,
		'listSortMode' => 'posted',
		'listSortDesc' => true,
		'dateFormatPreview' => DATE_SHORT,
		'dateFormatFullText' => DATE_LONG,
		'blockMode' => 0, # 0 - отключить, 1 - последние, 2 - избранные
		'blockCount' => 5,
		'THimageWidth' => 120,
		'THimageHeight' => 90,
		'imageWidth' => 640,
		'imageHeight' => 480,
		'imageColor' => '#ffffff',
	);

	/**
	 * Таблица списка объектов
	 * @var array
	 */
	var $table = array (
		'name' => 'articles',
		'key'=> 'id',
		'sortMode' => 'posted',
		'sortDesc' => true,
		'columns' => array(
			array('name' => 'caption', 'caption' => 'Заголовок'),
			array('name' => 'posted', 'align'=>'center', 'value'=>templPosted, 'macros' => true),
			array('name' => 'preview', 'caption' => 'Кратко', 'maxlength'=>255, 'striptags' => true),
		),
		'controls' => array (
			'delete' => '',
			'edit' => '',
			'toggle' => '',
		),
		'tabs' => array(
			'width'=>'180px',
			'items'=>array(
			 array('caption'=>'Добавить статью', 'name'=>'action', 'value'=>'create')
			),
		),
		'sql' => "(
			`id` int(10) unsigned NOT NULL auto_increment,
			`section` int(10) unsigned default NULL,
			`active` tinyint(1) unsigned NOT NULL default '1',
			`position` int(10) unsigned default NULL,
			`posted` datetime default NULL,
			`block` tinyint(1) unsigned NOT NULL default '0',
			`caption` varchar(255) NOT NULL default '',
			`preview` text NOT NULL,
			`text` text NOT NULL,
			`image` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`id`),
			KEY `active` (`active`),
			KEY `section` (`section`),
			KEY `position` (`position`),
			KEY `posted` (`posted`),
			KEY `block` (`block`)
		) TYPE=MyISAM COMMENT='Articles';",
	);

	/**
	 * Процедура установки плагина
	 *
	 */
	function install()
	{
		parent::install();

		umask(0000);
		if (!file_exists(filesRoot.'data/'.$this->name))
			mkdir(filesRoot.'data/'.$this->name, 0777);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Конструктор
	 *
	 * Производит регистрацию обработчиков событий
	 */
	function TArticles()
	{
		global $Eresus;

		parent::TListContentPlugin();

		if ($this->settings['blockMode'])
			$Eresus->plugins->events['clientOnPageRender'][] = $this->name;

		$this->table['sortMode'] = $this->settings['listSortMode'];
		$this->table['sortDesc'] = $this->settings['listSortDesc'];

		if ($this->table['sortMode'] == 'position')
			$this->table['controls']['position'] = '';

		if ($this->settings['blockMode'] == _ARTICLES_BLOCK_MANUAL) {

			$temp = array_shift($this->table['columns']);
			array_unshift($this->table['columns'], array('name' => 'block', 'align'=>'center', 'replace'=>array(0 => '', 1 => _ARTICLES_TMPL_BLOCK)), $temp);

		}

	}
	//-----------------------------------------------------------------------------

	/**
	 * Сохранение настроек
	 */
	function updateSettings()
	{
		global $Eresus;

		$item = $Eresus->db->selectItem('`plugins`', "`name`='".$this->name."'");
		$item['settings'] = decodeOptions($item['settings']);

		foreach ($this->settings as $key => $value)
			$this->settings[$key] = $Eresus->request['arg'][$key] ? $Eresus->request['arg'][$key] : '';

		if ($this->settings['blockMode'])
			$item['type'] = 'client,content'; else $item['type'] = 'client,content,ondemand';

		$item['settings'] = encodeOptions($this->settings);
		$Eresus->db->updateItem('plugins', $item, "`name`='".$this->name."'");
	}
	//-----------------------------------------------------------------------------

	/**
	 * Создание краткого текста
	 *
	 * @param string $text
	 * @return string
	 */
	function createPreview($text)
	{
		$text = trim(preg_replace('/<.+>/Us',' ',$text));
		$text = str_replace(array("\n", "\r"), ' ', $text);
		$text = preg_replace('/\s{2,}/', ' ', $text);

		if (!$this->settings['previewMaxSize'])
			$this->settings['previewMaxSize'] = 500;

		if ($this->settings['previewSmartSplit']) {

			preg_match("/\A(.{1,".$this->settings['previewMaxSize']."})(\.\s|\.|\Z)/s", $text, $result);
			$result = $result[1].'...';

		} else {

			$result = substr($text, 0, $this->settings['previewMaxSize']);
			if (strlen($text) > $this->settings['previewMaxSize'])
				$result .= '...';

		}
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавление статьи в БД
	 */
	function insert()
	{
		global $Eresus, $page;

		$item = getArgs($Eresus->db->fields($this->table['name']));
		$item['active'] = true;
		if (empty($item['preview'])) $item['preview'] = $this->createPreview($item['text']);
		$item['posted'] = gettime();
		$Eresus->db->insert($this->table['name'], $item);
		$item['id'] = $Eresus->db->getInsertedID();

		if (is_uploaded_file($_FILES['image']['tmp_name'])) {

			$tmpFile = tempnam($Eresus->fdata, $this->name);
			upload('image', $tmpFile);

			$item['image'] = $item['id'].'_'.time();
			$filename = filesRoot.'data/articles/'.$item['image'];
			useLib('glib');
			thumbnail($tmpFile, $filename.'.jpg', $this->settings['imageWidth'], $this->settings['imageHeight'], $this->settings['imageColor']);
			thumbnail($tmpFile, $filename.'-thmb.jpg', $this->settings['THimageWidth'], $this->settings['THimageHeight'], $this->settings['imageColor']);
			unlink($tmpFile);

			$Eresus->db->updateItem($this->table['name'], $item, '`id` = "'.$item['id'].'"');

		}

		sendNotify(admAdded.': <a href="'.httpRoot.'admin.php?mod=content&section='.$item['section'].'&id='.$item['id'].'">'.$item['caption'].'</a><br />'.$item['text']);
		goto(arg('submitURL'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Изменение статьи в БД
	 */
	function update()
	{
		global $Eresus, $page;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('update', 'int')."'");
		$image = $item['image'];
		$item = GetArgs($item, array('active', 'block'));
		if (empty($item['preview']) || arg('updatePreview')) $item['preview'] = $this->createPreview($item['text']);

		if (is_uploaded_file($_FILES['image']['tmp_name'])) {

			$tmpFile = tempnam($Eresus->fdata, $this->name);
			upload('image', $tmpFile);

			$filename = filesRoot.'data/articles/'.$image;
			if (($image != '') && (file_exists($filename.'.jpg')))
			{
				unlink($filename.'.jpg');
				unlink($filename.'-thmb.jpg');
			}
			$item['image'] = $item['id'].'_'.time();
			$filename = filesRoot.'data/articles/'.$item['image'];
			useLib('glib');
			thumbnail($tmpFile, $filename.'.jpg', $this->settings['imageWidth'], $this->settings['imageHeight'], $this->settings['imageColor']);
			thumbnail($tmpFile, $filename.'-thmb.jpg', $this->settings['THimageWidth'], $this->settings['THimageHeight'], $this->settings['imageColor']);
			unlink($tmpFile);
		}
		$Eresus->db->updateItem($this->table['name'], $item, "`id`='".arg('update', 'int')."'");
		sendNotify(admUpdated.': <a href="'.$page->url().'">'.$item['caption'].'</a><br />'.$item['text']);

		goto(arg('submitURL'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаление статьи из БД
	 *
	 * @param int $id  Идентификатор статьи
	 */
	function delete($id)
	{
		global $Eresus, $page;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('delete', 'int')."'");
		$filename = filesRoot.'data/'.$this->name.'/'.$item['image'];
		if (file_exists($filename.'.jpg'))
		{
			unlink($filename.'.jpg');
			unlink($filename.'-thmb.jpg');
		}

		parent::delete($id);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Замена макросов в строке
	 *
	 * @param string $template    Исходная строка
	 * @param array  $item        Массив замен
	 * @param string $dateFormat  Формат времени
	 * @return string
	 */
	function replaceMacros($template, $item, $dateFormat)
	{
		global $Eresus, $page;

		if (file_exists(filesRoot.'data/articles/'.$item['image'].'.jpg'))
		{
			$image = httpRoot.'data/articles/'.$item['image'].'.jpg';
			$thumbnail = httpRoot.'data/articles/'.$item['image'].'-thmb.jpg';
			$width = $this->settings['imageWidth'];
			$height = $this->settings['imageHeight'];
			$THwidth = $this->settings['THimageWidth'];
			$THheight = $this->settings['THimageHeight'];

		}
		else
		{
			$thumbnail = $image = styleRoot.'dot.gif';
			$width = $height = $THwidth = $THheight = 1;
		}

		$result = str_replace(
			array(
				'$(caption)',
				'$(preview)',
				'$(text)',
				'$(posted)',
				'$(link)',
				'$(image)',
				'$(thumbnail)',
				'$(imageWidth)',
				'$(imageHeight)',
				'$(thumbnailWidth)',
				'$(thumbnailHeight)',
			),
			array(
				strip_tags(htmlspecialchars(StripSlashes($item['caption']))),
				StripSlashes($item['preview']),
				StripSlashes($item['text']),
				FormatDate($item['posted'], $dateFormat),
				$page->clientURL($item['section']).$item['id'].'/',
				$image,
				$thumbnail,
				$width,
				$height,
				$THwidth,
				$THheight,

			),
			$template
		);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Диалог добавления статьи
	 *
	 * @return string
	 */
	function adminAddItem()
	{
		global $page, $Eresus;

		$form = array(
			'name' => 'newArticles',
			'caption' => 'Добавить статью',
			'width' => '95%',
			'fields' => array (
				array ('type'=>'hidden','name'=>'action', 'value'=>'insert'),
				array ('type' => 'hidden', 'name' => 'section', 'value' => arg('section')),
				array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%', 'maxlength' => '100'),
				array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст', 'height' => '200px'),
				array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание', 'height' => '10'),
				array ('type' => ($this->settings['blockMode'] == _ARTICLES_BLOCK_MANUAL)?'checkbox':'hidden', 'name' => 'block', 'label' => 'Показывать в блоке'),
				array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100'),
			),
			'buttons' => array('ok', 'cancel'),
		);

		$result = $page->renderForm($form);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Диалог изменения статьи
	 *
	 * @return string
	 */
	function adminEditItem()
	{
		global $Eresus, $page;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('id', 'int')."'");

		if (file_exists(filesRoot.'data/'.$this->name.'/'.$item['image'].'-thmb.jpg'))
			$image = 'Изображение: <br /><img src="'.httpRoot.'data/'.$this->name.'/'.$item['image'].'-thmb.jpg" alt="" />';
		else $image = '';

		if (arg('action', 'word') == 'delimage') {
			$filename = dataFiles.$this->name.'/'.$item['image'];
			if (is_file($filename.'.jpg')) unlink($filename.'.jpg');
			if (is_file($filename.'-thmb.jpg')) unlink($filename.'-thmb.jpg');
			goto($page->url());
		}

		$form = array(
			'name' => 'editArticles',
			'caption' => 'Изменить статью',
			'width' => '95%',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$item['id']),
				array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%', 'maxlength' => '100'),
				array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст', 'height' => '200px'),
				array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание', 'height' => '5'),
				array ('type' => 'checkbox', 'name'=>'updatePreview', 'label'=>'Обновить краткое описание автоматически', 'value' => false),
				array ('type' => ($this->settings['blockMode'] == _ARTICLES_BLOCK_MANUAL)?'checkbox':'hidden', 'name' => 'block', 'label' => 'Показывать в блоке'),
				array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100', 'comment'=>(is_file(dataFiles.$this->name.'/'.$item['id'].'.jpg')?'<a href="'.$page->url(array('action'=>'delimage')).'">Удалить</a>':'')),
				array ('type' => 'divider'),
				array ('type' => 'edit', 'name' => 'section', 'label' => 'Раздел', 'access'=>ADMIN),
				array ('type' => 'edit', 'name'=>'posted', 'label'=>'Написано'),
				array ('type' => 'checkbox', 'name'=>'active', 'label'=>'Активно'),
				array ('type' => 'text', 'value' => $image),
			),
			'buttons' => array('ok', 'apply', 'cancel'),
		);
		$result = $page->renderForm($form, $item);

		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Диалог настроек
	 * @return string
	 */
	function settings()
	{
		global $page;

		$form = array(
			'name' => 'settings',
			'caption' => $this->title.' '.$this->version,
			'width' => '500px',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
				array('type'=>'memo','name'=>'tmplItem','label'=>'Шаблон полнотекстового просмотра','height'=>'5'),
				array('type'=>'edit','name'=>'dateFormatFullText','label'=>'Формат даты', 'width'=>'100px'),
				array('type'=>'header', 'value' => 'Параметры списка'),
				array('type'=>'edit','name'=>'itemsPerPage','label'=>'Статей на страницу','width'=>'50px', 'maxlength'=>'2'),
				array('type'=>'memo','name'=>'tmplListItem','label'=>'Шаблон элемента','height'=>'5'),
				array('type'=>'edit','name'=>'dateFormatPreview','label'=>'Формат даты', 'width'=>'100px'),
				array('type'=>'select','name'=>'listSortMode','label'=>'Сортировка', 'values' => array('posted', 'position'), 'items' => array('По дате добавления', 'Ручная')),
				array('type'=>'checkbox','name'=>'listSortDesc','label'=>'В обратном порядке'),
				array('type'=>'header', 'value' => 'Блок статей'),
				array('type'=>'select','name'=>'blockMode','label'=>'Режим блока статей', 'values' => array(_ARTICLES_BLOCK_NONE, _ARTICLES_BLOCK_LAST, _ARTICLES_BLOCK_MANUAL), 'items' => array('Отключить','Последние статьи','Ручной выбор статей')),
				array('type'=>'memo','name'=>'tmplBlockItem','label'=>'Шаблон элемента блока','height'=>'3'),
				array('type'=>'edit','name'=>'blockCount','label'=>'Количество', 'width'=>'50px'),
				array('type'=>'header', 'value' => 'Краткое описание'),
				array('type'=>'edit','name'=>'previewMaxSize','label'=>'Макс. размер описания','width'=>'50px', 'maxlength'=>'4', 'comment'=>'симовлов'),
				array('type'=>'checkbox','name'=>'previewSmartSplit','label'=>'"Умное" создание описания'),
				array('type'=>'header', 'value' => 'Картинка'),
				array('type'=>'edit','name'=>'imageWidth','label'=>'Ширина', 'width'=>'100px'),
				array('type'=>'edit','name'=>'imageHeight','label'=>'Высота', 'width'=>'100px'),
				array('type'=>'edit','name'=>'THimageWidth','label'=>'Ширина Миниатюры', 'width'=>'100px'),
				array('type'=>'edit','name'=>'THimageHeight','label'=>'Высота Миниатюры', 'width'=>'100px'),
				array('type'=>'edit','name'=>'imageColor','label'=>'Цвета фона', 'width'=>'100px', 'comment' => '#RRGGBB'),
				array('type'=>'divider'),
				array('type'=>'text', 'value'=>
					"Для создания шаблонов можно использовать макросы:<br />\n".
					"<b>$(caption)</b> - заголовок<br />\n".
					"<b>$(preview)</b> - краткий текст<br />\n".
					"<b>$(text)</b> - полный текст<br />\n".
					"<b>$(posted)</b> - дата публикации<br />\n".
					"<b>$(link)</b> - адрес статьи (URL)<br />\n".
					"<b>$(image)</b> - адрес картинки (URL)<br />\n".
					"<b>$(thumbnail)</b> - адрес миниаютюры (URL)<br />\n".
					"<b>$(imageWidth)</b> - ширина картинки<br />\n".
					"<b>$(imageHeight)</b> - высота картинки<br />\n".
					"<b>$(thumbnailWidth)</b> - ширина миниатюры<br />\n".
					"<b>$(thumbnailHeight)</b> - высота миниатюры<br />\n".

					"Для вставки блока статей используйте макрос <b>$(ArticlesBlock)</b><br />\n"
			 ),
		),
			'buttons' => array('ok', 'apply', 'cancel'),
		);
		$result = $page->renderForm($form, $this->settings);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовка блока статей
	 *
	 * @return string
	 */
	function renderArticlesBlock()
	{
		global $Eresus;

		$result = '';
		$items = $Eresus->db->select($this->table['name'], "`active`='1'".($this->settings['blockMode']==_ARTICLES_BLOCK_MANUAL?" AND `block`='1'":''), $this->table['sortMode'], $this->table['sortDesc'], '', $this->settings['blockCount']);
		if (count($items)) foreach($items as $item)
			$result .= $this->replaceMacros($this->settings['tmplBlockItem'], $item, $this->settings['dateFormatPreview']);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовка статьи в списк
	 *
	 * @param array $item  Свойства статьи
	 * @return string
	 */
	function clientRenderListItem($item)
	{
		$result = $this->replaceMacros($this->settings['tmplListItem'], $item, $this->settings['dateFormatPreview']);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Отрисовка статьи
	 *
	 * @return string
	 */
	function clientRenderItem()
	{
		global $Eresus, $page;

		$item = $Eresus->db->selectItem($this->table['name'], "(`id`='".$page->topic."')AND(`active`='1')");
		if (is_null($item)) {
			$item = $page->httpError(404);
			$result = $item['content'];
		} else {
			$result = $this->replaceMacros($this->settings['tmplItem'], $item, $this->settings['dateFormatFullText']);
		}
		$page->section[] = $item['caption'];
		$item['access'] = $page->access;
		$item['name'] = $item['id'];
		$item['title'] = $item['caption'];
		$item['hint'] = $item['description'] = $item['keywords'] = '';
		$Eresus->plugins->clientOnURLSplit($item, arg('url'));
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Обработчик события clientOnPageRender
	 *
	 * @param string $text  HMTL страницы
	 * @return string
	 */
	function clientOnPageRender($text)
	{
		global $page;

		$articles = $this->renderArticlesBlock();
		$text = str_replace('$(ArticlesBlock)', $articles, $text);
		return $text;
	}
	//-----------------------------------------------------------------------------
}
