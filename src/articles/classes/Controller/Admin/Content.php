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
     * Возвращает разметку списка статей
     *
     * @param Eresus_CMS_Request $request
     *
     * @return string
     *
     * @since 3.01
     */
    protected function actionIndex(Eresus_CMS_Request $request)
    {
        /** @var Articles $plugin */
        $plugin = $this->getPlugin();

        $table = ORM::getTable($this->getPlugin(), 'Article');
        $provider = new ORM_UI_List_DataProvider($table);
        $provider->filterInclude('section', $request->query->getInt('section'));
        $provider->orderBy($plugin->settings['listSortMode'], $plugin->settings['listSortDesc']);

        $list = new UI_List($this->getPlugin(), $provider);
        $list->setPageSize($this->getPlugin()->settings['itemsPerPage']);

        $currentPage = $request->query->has('page') ? $request->query->getInt('page') : 1;
        $list->setPage($currentPage);

        $tmpl = $this->getPlugin()->templates()->admin('ArticleList.html');
        $html = $tmpl->compile(array('list' => $list, 'settings' => $plugin->settings));
        return $html;
    }

    /**
     * Диалог добавления статьи
     *
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Response|string  ответ или разметка области контента
     */
    protected function actionAdd(Eresus_CMS_Request $request)
    {
        if ($request->getMethod() == 'POST')
        {
            $req = $request->request;
            $article = new Articles_Entity_Article();
            $article->section = $req->getInt('section');
            $article->active = true;
            $article->posted = new DateTime();
            $article->block = (boolean) $req->getInt('block');
            $article->caption = $req->get('caption');
            $article->text = $req->get('text');
            $article->preview = $req->get('preview');
            $article->image = 'image';
            $article->getTable()->persist($article);
            $response = new Eresus_HTTP_Redirect(arg('submitURL'));
            return $response;
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
                    $plugin->settings['blockMode'] == 'manual' ? 'checkbox' : 'hidden',
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
     * Изменение статьи
     *
     * @param Eresus_CMS_Request $request
     *
     * @return string|Eresus_HTTP_Response
     */
    protected function actionEdit(Eresus_CMS_Request $request)
    {
        if ($request->getMethod() == 'POST')
        {
            $args = $request->request;
            $article = $this->findArticle($args->getInt('id'));
            $article->image = 'image';
            $article->section = $args->getInt('section');
            if ($args->has('active'))
            {
                $article->active = (boolean) $args->getInt('active');
            }
            $article->posted = new DateTime($args->get('posted'));
            $article->block = (boolean) $args->getInt('block');
            $article->caption = $args->get('caption');
            $article->text = $args->get('text');
            $article->preview = $args->get('preview');
            if ($args->has('updatePreview'))
            {
                $article->createPreviewFromText();
            }
            $article->getTable()->update($article);
            $response = new Eresus_HTTP_Redirect($args->get('submitURL'));
            return $response;
        }

        $article = $this->findArticle($request->query->getInt('id'));
        /** @var Articles $plugin */
        $plugin = $this->getPlugin();
        $form = array(
            'name' => 'editArticles',
            'caption' => 'Изменить статью',
            'width' => '95%',
            'fields' => array (
                array('type' => 'hidden', 'name' => 'action', 'value' => 'edit'),
                array('type' => 'hidden', 'name' => 'id', 'value' => $article->id),
                array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок',
                    'width' => '100%', 'maxlength' => '255'),
                array ('type' => 'html', 'name' => 'text', 'label' => 'Полный текст',
                    'height' => '200px'),
                array ('type' => 'memo', 'name' => 'preview', 'label' => 'Краткое описание',
                    'height' => '5'),
                array ('type' => 'checkbox', 'name'=>'updatePreview',
                    'label'=>'Обновить краткое описание автоматически', 'value' => false),
                array ('type' => $plugin->settings['blockMode'] == 'manual'
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
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Response
     */
    protected function actionDelImage(Eresus_CMS_Request $request)
    {
        $article = $this->findArticle($request->query->getInt('id'));
        $article->image = null;
        $url = $this->getPage()->url(array('id' => $article->id, 'action' => 'edit'));
        return new Eresus_HTTP_Redirect($url);
    }

    /**
     * Переключает активность статьи
     *
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Response
     */
    protected function actionToggle(Eresus_CMS_Request $request)
    {
        $article = $this->findArticle($request->query->getInt('id'));
        $article->active = !$article->active;
        $article->getTable()->update($article);

        $response = new Eresus_HTTP_Redirect($this->getPage()->url(array('id' => '')));
        return $response;
    }

    /**
     * Удаление статьи из БД
     *
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Redirect
     *
     * @since 3.01
     */
    protected function actionDelete(Eresus_CMS_Request $request)
    {
        $article = $this->findArticle($request->query->getInt('id'));
        $article->getTable()->delete($article);
        return new Eresus_HTTP_Redirect(Eresus_Kernel::app()->getPage()->url(array('id' => '')));
    }

    /**
     * Перемещает статью выше по списку
     *
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Redirect
     *
     * @since 3.01
     */
    protected function actionUp(Eresus_CMS_Request $request)
    {
        $article = $this->findArticle($request->query->getInt('id'));
        $helper = new ORM_Helper_Ordering();
        $helper->groupBy('section');

        /** @var Articles $plugin */
        $plugin = $this->getPlugin();
        if ($plugin->settings['listSortDesc'])
        {
            $helper->moveDown($article);
        }
        else
        {
            $helper->moveUp($article);
        }

        return new Eresus_HTTP_Redirect(Eresus_Kernel::app()->getPage()->url(array('id' => '')));
    }

    /**
     * Перемещает статью ниже по списку
     *
     * @param Eresus_CMS_Request $request
     *
     * @return Eresus_HTTP_Redirect
     *
     * @since 3.01
     */
    protected function actionDown(Eresus_CMS_Request $request)
    {
        $article = $this->findArticle($request->query->getInt('id'));
        $helper = new ORM_Helper_Ordering();
        $helper->groupBy('section');
        /** @var Articles $plugin */
        $plugin = $this->getPlugin();
        if ($plugin->settings['listSortDesc'])
        {
            $helper->moveUp($article);
        }
        else
        {
            $helper->moveDown($article);
        }
        return new Eresus_HTTP_Redirect(Eresus_Kernel::app()->getPage()->url());
    }

    /**
     * Изменение свойств раздела
     *
     * @return string
     *
     * @since 3.01
     */
    protected function actionProperties()
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

