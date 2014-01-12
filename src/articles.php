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
    public $version = '';//${product.version}';TODO

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
     * Таблица списка объектов
     * @var array
     * @todo устарело, удалить после рефакторинга
     */
    public $table = array (
        'sortMode' => 'posted',
        'sortDesc' => true,
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

