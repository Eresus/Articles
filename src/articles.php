<?php
/**
 * Статьи
 *
 * Публикация статей
 *
 * @version ${product.version}
 *
 * @copyright 2005, ProCreat Systems, http://procreat.ru/
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
 * @author БерсЪ <bersz@procreat.ru>
 * @author Андрей Афонинский
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
 * @package Articles
 */


/**
 * Класс плагина
 *
 * @package Articles
 */
class Articles extends ContentPlugin
{
<<<<<<< HEAD
	/**
	 * Режим блока: блок отключен
	 * @var int
	 */
	const BLOCK_NONE = 0;

	/**
	 * Режим блока: последние статьи
	 * @var int
	 */
	const BLOCK_LAST = 1;

	/**
	 * Режим блока: избранные статьи
	 * @var int
	 */
	const BLOCK_MANUAL = 2;

	/**
	 * Имя плагина
	 * @var string
	 */
	public $name = 'articles';

	/**
	 * Требуемая версия ядра
	 * @var string
	 */
	public $kernel = '3.00b';

	/**
	 * Тип плагина
	 * @var string
	 */
	public $type = 'client,content,ondemand';

	/**
	 * Название плагина
	 * @var string
	 */
	public $title = 'Статьи';

	/**
	 * Версия плагина
	 * @var string
	 */
	public $version = '3.00';

	/**
	 * Описание плагина
	 * @var string
	 */
	public $description = 'Публикация статей';

	/**
	 * Настройки плагина
	 * @var array
	 */
	public $settings = array(
		'itemsPerPage' => 10,
		'tmplList' => '
			<h1>$(title)</h1>
			$(content)
			$(items)
		',
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
	public $table = array (
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
				'create' => array('caption'=>'Добавить статью', 'name'=>'action', 'value'=>'create'),
				'list' => array('caption' => 'Список статей'),
				'text' => array('caption' => 'Текст на странице', 'name'=>'action', 'value'=>'text'),
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
		) ENGINE=MyISAM COMMENT='Articles';",
	);

	/**
	 * Конструктор
	 *
	 * Производит регистрацию обработчиков событий
	 */
	public function __construct()
	{
		global $Eresus;

		parent::__construct();

		if ($this->settings['blockMode'])
		{
			$Eresus->plugins->events['clientOnPageRender'][] = $this->name;
		}

		$this->table['sortMode'] = $this->settings['listSortMode'];
		$this->table['sortDesc'] = $this->settings['listSortDesc'];

		if ($this->table['sortMode'] == 'position')
		{
			$this->table['controls']['position'] = '';
		}

		if ($this->settings['blockMode'] == self::BLOCK_MANUAL)
		{
			$temp = array_shift($this->table['columns']);
			array_unshift($this->table['columns'], array('name' => 'block', 'align'=>'center',
				'replace' => array(
					0 => '',
					1 => '<span title="Показывается в блоке статей">*</span>'
				)
			), $temp);
		}

	}
	//-----------------------------------------------------------------------------

	/**
	 * Процедура установки плагина
	 *
	 * @return void
	 */
	public function install()
	{
		parent::install();

		$dir = $GLOBALS['Eresus']->fdata . $this->name;
		if (!file_exists($dir))
		{
			$umask = umask(0000);
			mkdir($dir, 0777);
			umask($umask);
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
		$keys = array_keys($this->settings);
		foreach ($keys as $key)
		{
			$this->settings[$key] = isset($Eresus->request['arg'][$key]) ?
				$Eresus->request['arg'][$key] : '';
		}

		if ($this->settings['blockMode'])
		{
			$item['type'] = 'client,content';
		}
		else
		{
			$item['type'] = 'client,content,ondemand';
		}
		$item['settings'] = encodeOptions($this->settings);
		$Eresus->db->updateItem('plugins', $item, "`name`='".$this->name."'");
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавление статьи в БД
	 */
	public function insert()
	{
		global $Eresus;

		$item = array();
		$item['section'] = arg('section', 'int');
		$item['active'] = true;
		// FIXME Не задаётся position. Наверно надо учесть режим сортировки
		$item['posted'] = gettime();
		$item['block'] = arg('block', 'int');
		$item['caption'] = arg('caption', 'dbsafe');
		$item['text'] = arg('text', 'dbsafe');
		$item['preview'] = arg('preview', 'dbsafe');
		if (empty($item['preview'])) $item['preview'] = $this->createPreview($item['text']);
		$item['image'] = '';

		$Eresus->db->insert($this->table['name'], $item);
		$item['id'] = $Eresus->db->getInsertedID();

		if (is_uploaded_file($_FILES['image']['tmp_name']))
		{
			$tmpFile = $Eresus->fdata . $this->name . '/uploaded.bin';
			upload('image', $tmpFile);

			$item['image'] = $item['id'].'_'.time();
			$filename = $Eresus->fdata . 'articles/'.$item['image'];
			useLib('glib');
			thumbnail($tmpFile, $filename.'.jpg', $this->settings['imageWidth'],
				$this->settings['imageHeight'], $this->settings['imageColor']);
			thumbnail($tmpFile, $filename.'-thmb.jpg', $this->settings['THimageWidth'],
				$this->settings['THimageHeight'], $this->settings['imageColor']);
			unlink($tmpFile);

			$Eresus->db->updateItem($this->table['name'], $item, '`id` = "'.$item['id'].'"');
		}

		HTTP::redirect(arg('submitURL'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Изменение статьи в БД
	 */
	public function update()
	{
		global $Eresus;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('update', 'int')."'");
		$image = $item['image'];
		$item['section'] = arg('section', 'int');
		if ( ! is_null(arg('section')) )
		{
			$item['active'] = arg('active', 'int');
		}
		// FIXME Не задаётся position. Наверно надо учесть режим сортировки
		$item['posted'] = arg('posted', 'dbsafe');
		$item['block'] = arg('block', 'int');
		$item['caption'] = arg('caption', 'dbsafe');
		$item['text'] = arg('text', 'dbsafe');
		$item['preview'] = arg('preview', 'dbsafe');
		if (empty($item['preview']) || arg('updatePreview'))
		{
			$item['preview'] = $this->createPreview($item['text']);
		}

		if (is_uploaded_file($_FILES['image']['tmp_name']))
		{
			$tmpFile = $Eresus->fdata . $this->name . '/uploaded.bin';
			upload('image', $tmpFile);

			$filename = $Eresus->fdata . 'articles/'.$image;
			if (($image != '') && (file_exists($filename.'.jpg')))
			{
				unlink($filename.'.jpg');
				unlink($filename.'-thmb.jpg');
			}
			$item['image'] = $item['id'].'_'.time();
			$filename = $Eresus->fdata . 'articles/'.$item['image'];
			useLib('glib');
			thumbnail($tmpFile, $filename.'.jpg', $this->settings['imageWidth'],
				$this->settings['imageHeight'], $this->settings['imageColor']);
			thumbnail($tmpFile, $filename.'-thmb.jpg', $this->settings['THimageWidth'],
				$this->settings['THimageHeight'], $this->settings['imageColor']);
			unlink($tmpFile);
		}
		$Eresus->db->updateItem($this->table['name'], $item, "`id`='".arg('update', 'int')."'");

		HTTP::redirect(arg('submitURL'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаление статьи из БД
	 *
	 * @param int $id  Идентификатор статьи
	 */
	public function delete($id)
	{
		global $Eresus;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('delete', 'int')."'");
		$filename = $Eresus->data . $this->name.'/'.$item['image'];
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
	 * @param string $template  Шаблон
	 * @param array  $item      Массив замен
	 * @return string  HTML
	 */
	function replaceMacros($template, $item)
	{
		if (file_exists($GLOBALS['Eresus']->fdata . 'articles/'.$item['image'].'.jpg'))
		{
			$image = $GLOBALS['Eresus']->data . 'articles/'.$item['image'].'.jpg';
			$thumbnail = $GLOBALS['Eresus']->data . 'articles/'.$item['image'].'-thmb.jpg';
			$width = $this->settings['imageWidth'];
			$height = $this->settings['imageHeight'];
			$THwidth = $this->settings['THimageWidth'];
			$THheight = $this->settings['THimageHeight'];

		}
		else
		{
			$thumbnail = $image = $GLOBALS['Eresus']->style . 'dot.gif';
			$width = $height = $THwidth = $THheight = 1;
		}

		$result = str_replace(
			array(
				'$(caption)',
				'$(preview)',
				'$(text)',
				'$(posted)',
				'$(link)',
				'$(section)',
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
				$item['posted'],
				Eresus_Kernel::app()->getPage()->clientURL($item['section']).$item['id'].'/',
				Eresus_Kernel::app()->getPage()->clientURL($item['section']),
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
	 * Определяет отображаемый раздел АИ
	 *
	 * @return mixed|string
	 */
	function adminRenderContent()
	{
		$result = null;
		if (!is_null(arg('action')) && arg('action') == 'textupdate')
		{
			$this->text();
		}
		elseif (!is_null(arg('action')) && arg('action') == 'text')
		{
			$result = $this->adminRenderText();
		}
		else
		{
			$result = parent::adminRenderContent();
		}

		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Диалог добавления статьи
	 *
	 * @return string
	 */
	public function adminAddItem()
	{
		$form = array(
			'name' => 'newArticles',
			'caption' => 'Добавить статью',
			'width' => '95%',
			'fields' => array (
				array ('type'=>'hidden','name'=>'action', 'value'=>'insert'),
				array ('type' => 'hidden', 'name' => 'section', 'value' => arg('section')),
				array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%',
					'maxlength' => '255'),
				array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст', 'height' => '200px'),
				array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание',
					'height' => '10'),
				array ('type' => ($this->settings['blockMode'] == self::BLOCK_MANUAL)?'checkbox':'hidden',
					'name' => 'block', 'label' => 'Показывать в блоке'),
				array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100'),
			),
			'buttons' => array('ok', 'cancel'),
		);

		$result = Eresus_Kernel::app()->getPage()->renderForm($form);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Диалог изменения статьи
	 *
	 * @return string
	 */
	public function adminEditItem()
	{
		$Eresus = Eresus_CMS::getLegacyKernel();
		$page = Eresus_Kernel::app()->getPage();

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('id', 'int')."'");

		if (file_exists($Eresus->fdata . $this->name.'/'.$item['image'].'-thmb.jpg'))
		{
			$image = 'Изображение: <br /><img src="'. $Eresus->data . $this->name.'/'.$item['image'].
				'-thmb.jpg" alt="" />';
		}
		else
		{
			$image = '';
		}

		if (arg('action', 'word') == 'delimage')
		{
			$filename = $Eresus->fdata . $this->name.'/'.$item['image'];
			if (is_file($filename.'.jpg'))
			{
				unlink($filename.'.jpg');
			}
			if (is_file($filename.'-thmb.jpg'))
			{
				unlink($filename.'-thmb.jpg');
			}
			HTTP::redirect($page->url());
		}

		$form = array(
			'name' => 'editArticles',
			'caption' => 'Изменить статью',
			'width' => '95%',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$item['id']),
				array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%',
					'maxlength' => '255'),
				array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст', 'height' => '200px'),
				array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание',
					'height' => '5'),
				array ('type' => 'checkbox', 'name'=>'updatePreview',
					'label'=>'Обновить краткое описание автоматически', 'value' => false),
				array ('type' => ($this->settings['blockMode'] == self::BLOCK_MANUAL)?'checkbox':'hidden',
					'name' => 'block', 'label' => 'Показывать в блоке'),
				array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100',
					'comment'=>(is_file($Eresus->fdata.$this->name.'/'.$item['image'].'.jpg') ?
						'<a href="'.$page->url(array('action'=>'delimage')).'">Удалить</a>' : '')),
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
	public function settings()
	{
		$page = Eresus_Kernel::app()->getPage();

		$form = array(
			'name' => 'settings',
			'caption' => $this->title.' '.$this->version,
			'width' => '500px',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
				array('type'=>'text','value'=>
				'Для вставки блока статей используйте макрос <b>$(ArticlesBlock)</b><br />'),
				array('type'=>'header','value'=>'Параметры полнотекстового просмотра'),
				array('type'=>'memo','name'=>'tmplItem','label'=>'Шаблон полнотекстового просмотра',
					'height'=>'5'),
				array('type'=>'edit','name'=>'dateFormatFullText','label'=>'Формат даты', 'width'=>'100px'),
				array('type'=>'header', 'value' => 'Параметры списка'),
				array('type'=>'edit','name'=>'itemsPerPage','label'=>'Статей на страницу','width'=>'50px',
					'maxlength'=>'2'),
				array('type'=>'memo','name'=>'tmplList','label'=>'Шаблон списка','height'=>'3'),
				array('type'=>'text','value'=>'
=======
    /**
     * Требуемая версия ядра
     * @var string
     */
    public $kernel = '3.01a';

    /**
     * Название плагина
     * @var string
     */
    public $title = 'Статьи';

    /**
     * Версия плагина
     * @var string
     */
    public $version = '${product.version}';

    /**
     * Описание плагина
     * @var string
     */
    public $description = 'Публикация статей';

    /**
     * Настройки плагина
     * @var array
     */
    public $settings = array(
        'itemsPerPage' => 10,
        'previewMaxSize' => 500,
        'previewSmartSplit' => true,
        'listSortMode' => 'posted',
        'listSortDesc' => true,
        'blockMode' => 'none',
        'blockCount' => 5,
        'THimageWidth' => 120,
        'THimageHeight' => 90,
        'imageWidth' => 640,
        'imageHeight' => 480,
        'imageColor' => '#ffffff',
    );

    /**
     * Конструктор
     *
     * Производит регистрацию обработчиков событий
     */
    public function __construct()
    {
        parent::__construct();

        if ('none' != $this->settings['blockMode'])
        {
            $evd = Eresus_Kernel::app()->getEventDispatcher();
            $evd->addListener('cms.client.render_page', array($this, 'clientOnPageRender'));
        }
    }

    /**
     * Процедура установки плагина
     *
     * @return void
     */
    public function install()
    {
        parent::install();
        $driver = ORM::getManager()->getDriver();
        $driver->createTable(ORM::getTable($this, 'Article'));
        $this->mkdir();
    }

    /**
     * Действия при удалении плагина
     *
     * @return void
     */
    public function uninstall()
    {
        $this->rmdir();
        $driver = ORM::getManager()->getDriver();
        $driver->dropTable(ORM::getTable($this, 'Article'));
        parent::uninstall();
    }

    /**
     * Замена макросов в строке
     *
     * @param string $template  Шаблон
     * @param array  $item      Массив замен
     * @return string  HTML
     */
    public function replaceMacros($template, $item)
    {
        $html = str_replace(
            array(
                '$(imageWidth)',
                '$(imageHeight)',
                '$(thumbWidth)',
                '$(thumbHeight)',
            ),
            array(
                $this->settings['imageWidth'],
                $this->settings['imageHeight'],
                $this->settings['THimageWidth'],
                $this->settings['THimageHeight'],
            ),
            $template
        );
        return parent::replaceMacros($html, $item);
    }

    /**
     * Возвращает разметку области контента АИ модуля
     *
     * @param Eresus_CMS_Request $request
     *
     * @return string
     */
    public function adminRenderContent(Eresus_CMS_Request $request)
    {
        $controller = new Articles_Controller_Admin_Content($this);
        $response = $controller->getHtml($request);

        if (is_string($response))
        {
            /** @var TAdminUI $page */
            $page = Eresus_Kernel::app()->getPage();
            $response = $page->renderTabs(array(
                'width' => '180px',
                'items' => array(
                    'create' => array('caption' => 'Добавить статью', 'name'=>'action',
                        'value' => 'add'),
                    'list' => array('caption' => 'Список статей',
                        'url' => $page->url(array('id' => ''))),
                    'text' => array('caption' => 'Текст на странице', 'name' => 'action',
                        'value' => 'properties'),
                ),
            )) . $response;
        }

        return $response;
    }

    /**
     * Диалог настроек
     * @return string
     */
    public function settings()
    {
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();

        $form = array(
            'name' => 'settings',
            'caption' => $this->title.' '.$this->version,
            'width' => '500px',
            'fields' => array (
                array('type' => 'hidden', 'name' => 'update', 'value' => $this->getName()),
                array('type' => 'text', 'value' =>
                    'Для вставки блока статей используйте макрос <b>$(ArticlesBlock)</b><br>'),
                array('type' => 'header', 'value' => 'Параметры полнотекстового просмотра'),
                array('type' => 'memo', 'name' => 'tmplArticle',
                    'label' => 'Шаблон полнотекстового просмотра', 'height'=>'5',
                    'value' => $this->templates()->clientRead('Article.html')),
                array('type'=>'header', 'value' => 'Параметры списка'),
                array('type'=>'edit','name'=>'itemsPerPage','label'=>'Статей на страницу',
                    'width'=>'50px',
                    'maxlength'=>'2'),
                array('type' => 'memo', 'name' => 'tmplList', 'label' => 'Шаблон списка статей',
                    'height' => '10',
                    'value' => $this->templates()->clientRead('List.html')),
                array('type' => 'text', 'value' => '
>>>>>>> release/v3.01
					Макросы:<br />
					<strong>$(title)</strong> - заголовок страницы,<br />
					<strong>$(content)</strong> - контент страницы,<br />
					<strong>$(items)</strong> - список статей
				'),
                array('type'=>'select','name'=>'listSortMode','label'=>'Сортировка',
                    'values' => array('posted', 'position'),
                    'items' => array('По дате добавления', 'Ручная')),
                array('type'=>'checkbox','name'=>'listSortDesc','label'=>'В обратном порядке'),
                array('type'=>'header', 'value' => 'Блок статей'),
                array('type'=>'select','name'=>'blockMode','label'=>'Режим блока статей',
                    'values' => array('none', 'last', 'manual'),
                    'items' => array('Отключить','Последние статьи','Избранные статьи')),
                array('type'=>'memo','name'=>'tmplBlock','label'=>'Шаблон элемента блока',
                    'height'=>'3', 'value' => $this->templates()->clientRead('Block.html')),
                array('type'=>'edit','name'=>'blockCount','label'=>'Количество', 'width'=>'50px'),
                array('type'=>'header', 'value' => 'Краткое описание'),
                array('type'=>'edit','name'=>'previewMaxSize','label'=>'Макс. размер описания',
                    'width'=>'50px', 'maxlength'=>'4', 'comment'=>'символов'),
                array('type'=>'checkbox','name'=>'previewSmartSplit','label'=>'"Умное" создание описания'),
                array('type'=>'header', 'value' => 'Картинка'),
                array('type'=>'edit','name'=>'imageWidth','label'=>'Ширина', 'width'=>'100px'),
                array('type'=>'edit','name'=>'imageHeight','label'=>'Высота', 'width'=>'100px'),
                array('type'=>'edit','name'=>'THimageWidth','label'=>'Ширина Миниатюры', 'width'=>'100px'),
                array('type'=>'edit','name'=>'THimageHeight','label'=>'Высота Миниатюры', 'width'=>'100px'),
                array('type'=>'edit','name'=>'imageColor','label'=>'Цвета фона', 'width'=>'100px',
                    'comment' => '#RRGGBB'),
                array('type'=>'divider'),
                array('type'=>'text', 'value'=>
                "Для создания шаблонов полнотекстового просмотра, элемента списка и элемента блока " .
                    "можно использовать макросы:<br />\n".
                    "<b>$(caption)</b> - заголовок<br />\n".
                    "<b>$(preview)</b> - краткий текст<br />\n".
                    "<b>$(text)</b> - полный текст<br />\n".
                    "<b>$(posted)</b> - дата публикации<br />\n".
                    "<b>$(clientUrl)</b> - адрес статьи (URL)<br />\n".
                    "<b>$(imageUrl)</b> - адрес картинки (URL)<br />\n".
                    "<b>$(thumbUrl)</b> - адрес миниатюры (URL)<br />\n".
                    "<b>$(imageWidth)</b> - ширина картинки<br />\n".
                    "<b>$(imageHeight)</b> - высота картинки<br />\n".
                    "<b>$(thumbWidth)</b> - ширина миниатюры<br />\n".
                    "<b>$(thumbHeight)</b> - высота миниатюры<br />\n"
                ),
            ),
            'buttons' => array('ok', 'apply', 'cancel'),
        );
        $result = $page->renderForm($form, $this->settings);
        return $result;
    }

    /**
     * Дополнительные действия при сохранении настроек
     */
    public function onSettingsUpdate()
    {
        parent::onSettingsUpdate();
        $this->templates()->clientWrite('Article.html', arg('tmplArticle'));
        $this->templates()->clientWrite('Block.html', arg('tmplBlock'));
        $this->templates()->clientWrite('List.html', arg('tmplList'));
    }

    /**
     * Формирование контента
     *
     * @return string
     */
    public function clientRenderContent()
    {
        $controller = new Articles_Controller_Client_Content($this);
        return $controller->actionContent();
    }

    /**
     * Вставляет блок статей на страницу
     *
     * @param Eresus_Event_Render $event
     */
    public function clientOnPageRender(Eresus_Event_Render $event)
    {
        $articles = $this->renderArticlesBlock();
        $text = str_replace('$(ArticlesBlock)', $articles, $event->getText());
        $event->setText($text);
    }

    /**
     * Удаляет статьи при удалении раздела сайта
     *
     * @param int $sectionId
     *
     * @since 3.01
     */
    public function onSectionDelete($sectionId)
    {
        /** @var Articles_Entity_Table_Article $table */
        $table = ORM::getTable($this, 'Article');
        /** @var Articles_Entity_Article[] $articles */
        $articles = $table->findInSection($sectionId, null, 0, true);
        foreach ($articles as $article)
        {
            $table->delete($article);
        }
    }

    /**
     * Отрисовка блока статей
     *
     * @return string
     */
    private function renderArticlesBlock()
    {
        /** @var Articles_Entity_Table_Article $table */
        $table = ORM::getTable($this, 'Article');
        $q = $table->createSelectQuery();
        if ('manual' == $this->settings['blockMode'])
        {
            $q->where($q->expr->eq('block', $q->bindValue(true, null, PDO::PARAM_BOOL)));
        }
        /** @var Articles_Entity_Article[] $articles */
        $articles = $table->loadFromQuery($q, $this->settings['blockCount']);

        $vars = array(
            'settings' => $this->settings,
            'articles' => $articles,
        );
        $tmpl = $this->templates()->client('Block.html');
        $html = $tmpl->compile($vars);
        return $html;
    }
}

