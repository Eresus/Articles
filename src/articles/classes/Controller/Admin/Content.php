<?php
/**
 * Контроллер контента АИ
 *
 * @version ${product.version}
 *
 * @copyright 2013, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
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
 */

/**
 * Контроллер контента АИ
 *
 * @since 3.01
 */
class Articles_Controller_Admin_Content extends Eresus_Plugin_Controller_Admin_Content
{
    /**
     * Возвращает разметку области контента
     *
     * @return string
     * @since 3.01
     */
    public function actionContent()
    {
        $html = '';
        switch (true)
        {
            case arg('action') == 'add':
                $html = $this->actionAdd();
                break;
            case !is_null(arg('id')) && arg('action') == 'delimage':
                $this->actionDeleteImage();
                break;
            case !is_null(arg('id')):
                $html = $this->actionEdit();
                break;
            case !is_null(arg('toggle')):
                $this->actionToggle();
                break;
            default:
                $html = $this->actionList();
        }
        return $html;
    }

    /**
     * Возвращает разметку списка статей
     *
     * @return string
     *
     * @since 3.01
     */
    private function actionList()
    {
        /** @var Articles_Entity_Table_Article $table */
        $table = ORM::getTable($this->getPlugin(), 'Article');
        $perPage = $this->getPlugin()->settings['itemsPerPage'];
        $currentPage = arg('pg') ?: 1;
        $articles = $table->findInSection($this->getPage()->id, $perPage,
            ($currentPage - 1) * $perPage, true);

        $pageCount = ceil($table->countInSection($this->getPage()->id, true) / $perPage);
        $urlTemplate = $this->getPage()->url(array('pg' => '%d'));
        $pager = new PaginationHelper($pageCount, $currentPage, $urlTemplate);

        $html =
            $this->getPage()->renderTable($this->getPlugin()->table, $articles) .
            $pager->render();
        return $html;
    }

    /**
     * Диалог добавления статьи
     *
     * @return string  разметка области контента
     */
    private function actionAdd()
    {
        $legacyEresus = Eresus_CMS::getLegacyKernel();

        if ('POST' == $legacyEresus->request['method'])
        {
            $article = new Articles_Entity_Article();
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

        /** @var Articles $plugin */
        $plugin = $this->getPlugin();
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
                    $plugin->settings['blockMode'] == $plugin::BLOCK_MANUAL ? 'checkbox' : 'hidden',
                    'name' => 'block', 'label' => 'Показывать в блоке'),
                array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка',
                    'width' => '100'),
            ),
            'buttons' => array('ok', 'cancel'),
        );

        $html = $this->getPage()->renderForm($form);
        return $html;
    }

    /**
     * Диалог изменения статьи
     *
     * @throws Exception
     *
     * @return string
     */
    private function actionEdit()
    {
        $article = $this->findArticle(arg('id'));

        $legacyEresus = Eresus_CMS::getLegacyKernel();

        if ('POST' == $legacyEresus->request['method'])
        {
            $article->image = 'image';
            $article->section = arg('section', 'int');
            if (!is_null(arg('active')))
            {
                $article->active = (boolean)arg('active', 'int');
            }
            $article->posted = new DateTime(arg('posted'));
            $article->block = (boolean)arg('block', 'int');
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

        /** @var Articles $plugin */
        $plugin = $this->getPlugin();
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
                array ('type' => $plugin->settings['blockMode'] == $plugin::BLOCK_MANUAL
                    ? 'checkbox' : 'hidden', 'name' => 'block', 'label' => 'Показывать в блоке'),
                array ('type' => 'file', 'name' => 'image', 'label' => 'Картинка', 'width' => '100',
                    'comment' => $article->imageUrl ?
                        '<a href="' . $this->getPage()->url(array('action'=>'delimage')) .
                        '">Удалить</a>'  : ''),
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
        $html = $this->getPage()->renderForm($form, $article);

        return $html;
    }

    /**
     * Удаляет картинку статьи
     *
     * @throws Exception
     *
     * @return void
     */
    private function actionDeleteImage()
    {
        $article = $this->findArticle(arg('id'));
        $article->image = null;
        HTTP::redirect($this->getPage()->url());
    }

    /**
     * Переключает активность статьи
     *
     * @throws Exception
     *
     * @return void
     */
    private function actionToggle()
    {
        $article = $this->findArticle(arg('toggle'));
        $article->active = !$article->active;
        $article->getTable()->update($article);

        HTTP::redirect(str_replace('&amp;', '&', $this->getPage()->url()));
    }

    /**
     * Ищет и возвращает статью с указанным идентификатором
     *
     * @param int $id
     *
     * @throws Exception
     * @return Articles_Entity_Article
     */
    private function findArticle($id)
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this->getPlugin(), 'Article')->find($id);
        if (null === $article)
        {
            throw new Exception('Запрошенная статья не найдена');
        }
        return $article;
    }
}

