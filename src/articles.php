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
     * Требуемая версия ядра
     * @var string
     */
    public $kernel = '3.00b';

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
     * @todo устарело, удалить после рефакторинга
     */
    private $table = array (
        'name' => 'articles',
        'key'=> 'id',
        'sortMode' => 'posted',
        'sortDesc' => true,
        'columns' => array(
            array('name' => 'caption', 'caption' => 'Заголовок'),
            array('name' => 'posted', 'align' => 'center', 'value' => templPosted,
                'macros' => true),
            array('name' => 'preview', 'caption' => 'Кратко', 'maxlength' => 255,
                'striptags' => true),
        ),
        'controls' => array (
            'delete' => '',
            'edit' => '',
            'toggle' => '',
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
            $this->listenEvents('clientOnPageRender');
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
        parent::install();
        ORM::getTable($this, 'Article')->create();
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
        ORM::getTable($this, 'Article')->drop();
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
     * @return string
     */
    public function adminRenderContent()
    {
        $html = '';
        switch (arg('action'))
        {
            case 'properties':
                $html = $this->actionAdminProperties();
                break;
            case 'add':
                $html = $this->actionAdminAdd();
                break;
            default:
                switch (true)
                {
                    case !is_null(arg('id')):
                        $html = $this->actionAdminEdit();
                        break;
                    case !is_null(arg('toggle')):
                        $this->actionAdminToggle(arg('toggle'));
                        break;
                    case !is_null(arg('delete')):
                        $this->actionAdminDelete(arg('delete'));
                        break;
                    case !is_null(arg('up')):
                        $this->table['sortDesc'] ?
                            $this->actionAdminDown(arg('up', 'dbsafe')) :
                            $this->actionAdminUp(arg('up', 'dbsafe'));
                        break;
                    case !is_null(arg('down')):
                        if ($this->table['sortDesc'])
                        {
                            $this->actionAdminUp(arg('down', 'dbsafe'));
                        }
                        else
                        {
                            $this->actionAdminDown(arg('down', 'dbsafe'));
                        }
                        break;
                    default:
                        $html = $this->actionAdminList();
                }
        }
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $html = $page->renderTabs(array(
            'width' => '180px',
            'items' => array(
                'create' => array('caption' => 'Добавить статью', 'name'=>'action',
                    'value' => 'add'),
                'list' => array('caption' => 'Список статей'),
                'text' => array('caption' => 'Текст на странице', 'name' => 'action',
                    'value' => 'properties'),
            ),
        )) . $html;

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
     * @return string
     */
    public function clientRenderContent()
    {
        $this->clientCheckUrl();

        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();

        if (!is_numeric($this->settings['itemsPerPage']))
        {
            $this->settings['itemsPerPage'] = 0;
        }
        if ($page->topic)
        {
            $html = $this->clientRenderItem();
        }
        else
        {
            $html = $this->clientRenderList();
        }
        return $html;
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
        $perPage = $this->settings['itemsPerPage'];
        $table = ORM::getTable($this, 'Article');
        $totalPageCount = ceil($table->countInSection($page->id) / $perPage);

        if (0 == $page->subpage)
        {
            $page->subpage = 1;
        }
        if ($page->subpage > $totalPageCount)
        {
            $page->httpError(404);
        }

        $items = '';
        /** @var Articles_Entity_Article[] $articles */
        $articles = $table->findInSection($page->id, $perPage, ($page->subpage - 1) * $perPage);
        if (count($articles))
        {
            foreach ($articles as $article)
            {
                $items .= $article->render($this->settings['tmplListItem']);
            }
            if ($totalPageCount > 1)
            {
                $pager = new PaginationHelper($totalPageCount, $page->subpage);
                $items .= $pager->render();
            }
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
     * Возвращает разметку списка статей
     *
     * @return string
     *
     * @since 3.01
     */
    private function actionAdminList()
    {
        /** @var Articles_Entity_Table_Article $table */
        $table = ORM::getTable($this, 'Article');
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $perPage = $this->settings['itemsPerPage'];
        $currentPage = arg('pg') ?: 1;
        $articles = $table->findInSection($page->id, $perPage, ($currentPage - 1) * $perPage, true);

        $pageCount = ceil($table->countInSection($page->id, true) / $perPage);
        $urlTemplate = $page->url(array('pg' => '%d'));
        $pager = new PaginationHelper($pageCount, $currentPage, $urlTemplate);

        $html =
            $page->renderTable($this->table, $articles) .
            $pager->render();
        return $html;
    }

    /**
     * Изменение свойств раздела
     *
     * @return string
     *
     * @since 3.01
     */
    private function actionAdminProperties()
    {
        $legacyEresus = Eresus_CMS::getLegacyKernel();
        $sections = $legacyEresus->sections;
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $section = $sections->get($page->id);

        if ('POST' == $legacyEresus->request['method'])
        {
            $section['content'] = arg('content');
            $sections->update($section);

            HTTP::redirect($page->url(array('action' => 'properties')));
        }

        $form = array(
            'name' => 'contentEditor',
            'caption' => 'Текст на странице',
            'width' => '95%',
            'fields' => array(
                array('type' => 'hidden', 'name' => 'action', 'value' => 'properties'),
                array('type' => 'html', 'name' => 'content', 'height' => '400px'),
            ),
            'buttons'=> array('ok' => 'Сохранить'),
        );

        $html = $page->renderForm($form, $section);
        return $html;
    }

    /**
     * Диалог добавления статьи
     *
     * @return string  разметка области контента
     */
    private function actionAdminAdd()
    {
        $legacyEresus = Eresus_CMS::getLegacyKernel();

        if ('POST' == $legacyEresus->request['method'])
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

        $form = array(
            'name' => 'newArticles',
            'caption' => 'Добавить статью',
            'width' => '95%',
            'fields' => array (
                array ('type' => 'hidden', 'name' => 'action', 'value' => 'add'),
                array ('type' => 'hidden', 'name' => 'section', 'value' => arg('section')),
                array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок',
                    'width' => '100%', 'maxlength' => '255'),
                array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст',
                    'height' => '200px'),
                array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание',
                    'height' => '10'),
                array ('type' =>
                $this->settings['blockMode'] == self::BLOCK_MANUAL ? 'checkbox' : 'hidden',
                    'name' => 'block', 'label' => 'Показывать в блоке'),
                array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка',
                    'width' => '100'),
            ),
            'buttons' => array('ok', 'cancel'),
        );

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $html = $page->renderForm($form);
        return $html;
    }

    /**
     * Диалог изменения статьи
     *
     * @throws Exception
     *
     * @return string
     */
    private function actionAdminEdit()
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find(arg('id'));
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }

        $legacyEresus = Eresus_CMS::getLegacyKernel();

        if ('POST' == $legacyEresus->request['method'])
        {
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
            if (arg('updatePreview'))
            {
                $article->createPreviewFromText();
            }
            $article->getTable()->update($article);

            HTTP::redirect(arg('submitURL'));
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
                array('type' => 'hidden', 'name' => 'id', 'value' => $article->id),
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
     * Переключает активность статьи
     *
     * @param int $id  ID статьи
     *
     * @throws Exception
     *
     * @return void
     */
    private function actionAdminToggle($id)
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
     * Удаление статьи из БД
     *
     * @param int $id  Идентификатор статьи
     *
     * @throws Exception
     */
    private function actionAdminDelete($id)
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

    /**
     * Перемещает статью выше по списку
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @since 3.01
     */
    private function actionAdminUp($id)
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find($id);
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }
        $helper = new ORM_Helper_Ordering();
        $helper->groupBy('section');
        $helper->moveUp($article);
        HTTP::redirect(Eresus_Kernel::app()->getPage()->url());
    }

    /**
     * Перемещает статью ниже по списку
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @since 3.01
     */
    private function actionAdminDown($id)
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this, 'Article')->find($id);
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }
        $helper = new ORM_Helper_Ordering();
        $helper->groupBy('section');
        $helper->moveDown($article);
        HTTP::redirect(Eresus_Kernel::app()->getPage()->url());
    }

    /**
     * Проверяет URL на «существование»
     *
     * @since 3.01
     */
    private function clientCheckUrl()
    {
        $legacyKernel = Eresus_CMS::getLegacyKernel();
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
        if ($page->topic)
        {
            $acceptUrl = $legacyKernel->request['path'] .
                ($page->subpage !== 0 ? 'p' . $page->subpage . '/' : '') .
                ($page->topic !== false ? $page->topic . '/' : '');
            if ($acceptUrl != $legacyKernel->request['url'])
            {
                $page->httpError(404);
            }
        }
        else
        {
            $acceptUrl = $legacyKernel->request['path'] .
                ($page->subpage !== 0 ? 'p' . $page->subpage . '/' : '');
            if ($acceptUrl != $legacyKernel->request['url'])
            {
                $page->httpError(404);
            }
        }
    }
}

