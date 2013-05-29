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
class TArticles extends TListContentPlugin
{
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
        'tmplList' => '
			<h1>$(title)</h1>
			$(content)
			$(items)
		',
        'tmplListItem' => '
			<div class="ArticlesListItem">
				<h3>$(caption)</h3>
				$(posted)<br />
				<img src="$(thumbUrl)" alt="$(caption)" width="$(thumbWidth)" height="$(thumbHeight)" />
				$(preview)
				<div class="controls">
					<a href="$(clientUrl)">Полный текст...</a>
				</div>
			</div>
		',
        'tmplItem' => '
			<div class="ArticlesItem">
				<h1>$(caption)</h1><b>$(posted)</b><br />
				<img src="$(imageUrl)" alt="$(caption)" width="$(imageWidth)" height="$(imageHeight)" style="float: left;" />
				$(text)
				<br /><br />
			</div>
		',
        'tmplBlockItem' => '<b>$(posted)</b><br /><a href="$(link)">$(caption)</a><br />',
        'previewMaxSize' => 500,
        'previewSmartSplit' => true,
        'listSortMode' => 'posted',
        'listSortDesc' => true,
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
        )
    );

    /**
     * Конструктор
     *
     * Производит регистрацию обработчиков событий
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->settings['blockMode'])
        {
            $Eresus = Eresus_Kernel::app()->getLegacyKernel();
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

    /**
     * Процедура установки плагина
     *
     * @return void
     */
    public function install()
    {
        // parent::install(); TODO Раскомментировать после перехода на Eresus_Plugin

        ORM::getTable($this, 'Article')->create();

        $legacyKernel = Eresus_Kernel::app()->getLegacyKernel();
        $dir = $legacyKernel->fdata . $this->name;
        if (!file_exists($dir))
        {
            $umask = umask(0000);
            mkdir($dir, 0777);
            umask($umask);
        }
    }

    /**
     * Дейтвия при удалении плагина
     *
     * @return void
     */
    public function uninstall()
    {
        ORM::getTable($this, 'Article')->drop();
        parent::uninstall();
    }

    /**
     * Сохранение настроек
     */
    public function updateSettings()
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
        $article = new Articles_Entity_Article($this);
        $article->section = arg('section', 'int');
        $article->active = true;
        $article->posted = new DateTime();
        $article->block = (boolean) arg('block', 'int');
        $article->caption = arg('caption');
        $article->text = arg('text');
        $article->preview = arg('preview');
        $article->image = 'image';
        $article->getTable()->persist($article);
        HTTP::redirect(arg('submitURL'));
    }

    /**
     * Изменение статьи в БД
     */
    public function update()
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find(arg('update'));
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }

        $article->image = 'image';
        $article->section = arg('section', 'int');
        if (!is_null(arg('active')))
        {
            $article->active = (boolean) arg('active', 'int');
        }
        $article->posted = new DateTime(arg('posted'));
        $article->block = (boolean) arg('block', 'int');
        $article->caption = arg('caption');
        $article->text = arg('text');
        $article->preview = arg('preview');
        if ('' == $article->preview || arg('updatePreview'))
        {
            $article->createPreviewFromText();
        }
        $article->getTable()->update($article);

        HTTP::redirect(arg('submitURL'));
    }

    /**
     * Удаление статьи из БД
     *
     * @param int $id  Идентификатор статьи
     *
     * @throws Exception
     */
    public function delete($id)
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find($id);
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }
        $article->getTable()->delete($article);

        HTTP::redirect(Eresus_Kernel::app()->getPage()->url());
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
     * Определяет отображаемый раздел АИ
     *
     * @return mixed|string
     */
    public function adminRenderContent()
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

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $result = $page->renderForm($form);
        return $result;
    }

    /**
     * Диалог изменения статьи
     *
     * @throws Exception
     *
     * @return string
     */
    public function adminEditItem()
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find(arg('id'));
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();

        if (arg('action', 'word') == 'delimage')
        {
            $article->image = null;
            HTTP::redirect($page->url());
        }

        $form = array(
            'name' => 'editArticles',
            'caption' => 'Изменить статью',
            'width' => '95%',
            'fields' => array (
                array('type' => 'hidden', 'name' => 'update', 'value' => $article->id),
                array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок',
                    'width' => '100%', 'maxlength' => '255'),
                array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст',
                    'height' => '200px'),
                array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание',
                    'height' => '5'),
                array ('type' => 'checkbox', 'name'=>'updatePreview',
                    'label'=>'Обновить краткое описание автоматически', 'value' => false),
                array ('type' => $this->settings['blockMode'] == self::BLOCK_MANUAL
                    ? 'checkbox' : 'hidden', 'name' => 'block', 'label' => 'Показывать в блоке'),
                array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100',
                    'comment' => $article->imageUrl ?
                    '<a href="' . $page->url(array('action'=>'delimage')) . '">Удалить</a>'  : ''),
                array ('type' => 'divider'),
                array ('type' => 'edit', 'name' => 'section', 'label' => 'Раздел',
                    'access' => ADMIN),
                array ('type' => 'edit', 'name'=>'posted', 'label'=>'Написано'),
                array ('type' => 'checkbox', 'name'=>'active', 'label'=>'Активно'),
                array ('type' => 'text', 'value' => $article->imageUrl
                    ? 'Изображение: <br><img src="' . $article->thumbUrl . '" alt="">' : ''),
            ),
            'buttons' => array('ok', 'apply', 'cancel'),
        );
        /** @var array $article */
        $html = $page->renderForm($form, $article);

        return $html;
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
                array('type'=>'hidden','name'=>'update', 'value'=>$this->name),
                array('type'=>'text','value'=>
                'Для вставки блока статей используйте макрос <b>$(ArticlesBlock)</b><br />'),
                array('type'=>'header','value'=>'Параметры полнотекстового просмотра'),
                array('type'=>'memo','name'=>'tmplItem','label'=>'Шаблон полнотекстового просмотра',
                    'height'=>'5'),
                array('type'=>'header', 'value' => 'Параметры списка'),
                array('type'=>'edit','name'=>'itemsPerPage','label'=>'Статей на страницу','width'=>'50px',
                    'maxlength'=>'2'),
                array('type'=>'memo','name'=>'tmplList','label'=>'Шаблон списка','height'=>'3'),
                array('type'=>'text','value'=>'
					Макросы:<br />
					<strong>$(title)</strong> - заголовок страницы,<br />
					<strong>$(content)</strong> - контент страницы,<br />
					<strong>$(items)</strong> - список статей
				'),
                array('type'=>'memo','name'=>'tmplListItem','label'=>'Шаблон элемента списка',
                    'height'=>'5'),
                array('type'=>'select','name'=>'listSortMode','label'=>'Сортировка',
                    'values' => array('posted', 'position'),
                    'items' => array('По дате добавления', 'Ручная')),
                array('type'=>'checkbox','name'=>'listSortDesc','label'=>'В обратном порядке'),
                array('type'=>'header', 'value' => 'Блок статей'),
                array('type'=>'select','name'=>'blockMode','label'=>'Режим блока статей',
                    'values' => array(self::BLOCK_NONE, self::BLOCK_LAST, self::BLOCK_MANUAL),
                    'items' => array('Отключить','Последние статьи','Избранные статьи')),
                array('type'=>'memo','name'=>'tmplBlockItem','label'=>'Шаблон элемента блока',
                    'height'=>'3'),
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
     * Формирование контента
     *
     * @return string|mixed
     */
    public function clientRenderContent()
    {
        $Eresus = Eresus_CMS::getLegacyKernel();
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();

        if ($page->topic)
        {
            $acceptUrl = $Eresus->request['path'] .
                ($page->subpage !== 0 ? 'p' . $page->subpage . '/' : '') .
                ($page->topic !== false ? $page->topic . '/' : '');
            if ($acceptUrl != $Eresus->request['url'])
            {
                $page->httpError(404);
            }
        }
        else
        {
            $acceptUrl = $Eresus->request['path'] .
                ($page->subpage !== 0 ? 'p' . $page->subpage . '/' : '');
            if ($acceptUrl != $Eresus->request['url'])
            {
                $page->httpError(404);
            }
        }

        return parent::clientRenderContent();
    }

    /**
     * Отрисовка списка статей
     *
     * @return string
     */
    public function clientRenderList()
    {
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
        /** @var Articles_Entity_Table_Article $table */
        $table = ORM::getTable($this, 'Article');
        /** @var Articles_Entity_Article[] $articles */
        $articles = $table->findInSection($page->id, $this->settings['itemsPerPage'],
            ($page->subpage - 1) * $this->settings['itemsPerPage']);
        $items = '';
        if (count($articles))
        {
            foreach ($articles as $article)
            {
                $items .= $article->render($this->settings['tmplListItem']);
            }
            $items .= $this->clientRenderPages();
        }

        $vars = array(
            'items' => $items,
            'title' => $page->title,
            'content' => $page->content,
        );

        $html = parent::replaceMacros($this->settings['tmplList'], $vars);

        return $html;
    }

    /**
     * Отрисовка статьи
     *
     * @return string
     */
    public function clientRenderItem()
    {
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find($page->topic);
        if (null === $article || false === $article->active)
        {
            $page->httpError(404);
        }

        $page->section []= $article->caption;

        $pathItem = array(
            'access' => $page->access,
            'name' => $article->id,
            'title' => $article->caption,
            'hint' => '',
            'description' => '',
            'keywords' => '',
        );
        $address = explode('/', Eresus_Kernel::app()->getLegacyKernel()->request['path']);
        $address = array_slice($address, 3);
        $url = implode('/', $address);
        $url .= $pathItem['name'] . '/';        
        Eresus_Kernel::app()->getLegacyKernel()->plugins->clientOnURLSplit($pathItem, $url);

        $html = $article->render($this->settings['tmplItem']);
        $html = $this->replaceMacros($html, array());
        return $html;
    }

    /**
     * Обработчик события clientOnPageRender
     *
     * @param string $text  HTML страницы
     * @return string
     */
    public function clientOnPageRender($text)
    {
        $articles = $this->renderArticlesBlock();
        $text = str_replace('$(ArticlesBlock)', $articles, $text);
        return $text;
    }

    /**
     * Обрабатывает запрос на переключение активности статьи
     *
     * @param int $id  ID статьи
     *
     * @throws Exception
     *
     * @return void
     */
    public function toggle($id)
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find($id);
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }
        $article->active = !$article->active;
        $article->getTable()->update($article);

        $page = Eresus_Kernel::app()->getPage();
        HTTP::redirect(str_replace('&amp;', '&', $page->url()));
    }

    /**
     * Изменение текста на странице
     */
    private function text()
    {
        $Eresus = Eresus_CMS::getLegacyKernel();
        $page = Eresus_Kernel::app()->getPage();

        $item = $Eresus->db->selectItem('pages', '`id`="' . arg('section', 'int') . '"');
        $item['content'] = $Eresus->db->escape($Eresus->request['arg']['content']);
        $item = array('id' => $item['id'], 'content' => $item['content']);
        $Eresus->db->updateItem('pages', $item, '`id`="' . arg('section', 'int') . '"');

        HTTP::redirect(str_replace('&amp;', '&', $page->url(array('action' => 'text'))));
    }

    /**
     * Диалог редактирования текста на странице
     *
     * @return string
     */
    private function adminRenderText()
    {
        $Eresus = Eresus_CMS::getLegacyKernel();
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();

        $item = $Eresus->db->selectItem('pages', '`id`="' . arg('section', 'int') . '"');
        $form = array(
            'name' => 'contentEditor',
            'caption' => 'Текст на странице',
            'width' => '95%',
            'fields' => array(
                array('type' => 'hidden', 'name' => 'action', 'value' => 'textupdate'),
                array('type' => 'html', 'name' => 'content', 'height' => '400px',
                    'value' => $item['content']),
            ),
            'buttons'=> array('ok' => 'Сохранить'),
        );

        $result = $page->renderForm($form);
        return $page->renderTabs($this->table['tabs']) . $result;
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
        if (self::BLOCK_MANUAL == $this->settings['blockMode'])
        {
            $q->where($q->expr->eq('block', $q->bindValue(true, null, PDO::PARAM_BOOL)));
        }
        /** @var Articles_Entity_Article[] $articles */
        $articles = $table->loadFromQuery($q, $this->settings['blockCount']);
         $html = '';
        foreach ($articles as $article)
        {
            $html .= $article->render($this->settings['tmplBlockItem']);
        }
        return $html;
    }
}

