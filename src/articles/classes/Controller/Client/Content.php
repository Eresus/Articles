<?php
/**
 * Контроллер контента КИ
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
 * Контроллер контента КИ
 *
 * @since 3.01
 */
class Articles_Controller_Client_Content extends Eresus_Plugin_Controller_Client_Content
{
    /**
     * Возвращает разметку области контента
     *
     * @return string
     * @since 3.01
     */
    public function actionContent()
    {
        $this->checkUrl();

        if (!is_numeric($this->getPlugin()->settings['itemsPerPage']))
        {
            $this->getPlugin()->settings['itemsPerPage'] = 0;
        }
        if ($this->getPage()->topic)
        {
            $html = $this->actionView();
        }
        else
        {
            $html = $this->actionIndex();
        }
        return $html;
    }

    /**
     * Проверяет URL на «существование»
     *
     * @since 3.01
     */
    private function checkUrl()
    {
        $legacyKernel = Eresus_CMS::getLegacyKernel();
        if ($this->getPage()->topic)
        {
            $acceptUrl = $legacyKernel->request['path'] .
                ($this->getPage()->subpage !== 0 ? 'p' . $this->getPage()->subpage . '/' : '') .
                ($this->getPage()->topic !== false ? $this->getPage()->topic . '/' : '');
            if ($acceptUrl != $legacyKernel->request['url'])
            {
                $this->getPage()->httpError(404);
            }
        }
        else
        {
            $acceptUrl = $legacyKernel->request['path'] .
                ($this->getPage()->subpage !== 0 ? 'p' . $this->getPage()->subpage . '/' : '');
            if ($acceptUrl != $legacyKernel->request['url'])
            {
                $this->getPage()->httpError(404);
            }
        }
    }

    /**
     * Отрисовка списка статей
     *
     * @throws Eresus_CMS_Exception_NotFound
     *
     * @return string
     *
     * @since 3.01
     */
    private function actionIndex()
    {
        /** @var Articles_Entity_Table_Article $table */
        $perPage = $this->getPlugin()->settings['itemsPerPage'];
        $table = ORM::getTable($this->getPlugin(), 'Article');
        $totalPageCount = ceil($table->countInSection($this->getPage()->id) / $perPage);
        if (0 == $totalPageCount)
        {
            $totalPageCount = 1;
        }

        if (0 == $this->getPage()->subpage)
        {
            $this->getPage()->subpage = 1;
        }
        if ($this->getPage()->subpage > $totalPageCount)
        {
            throw new Eresus_CMS_Exception_NotFound;
        }

        $articles = $table->findInSection($this->getPage()->id, $perPage,
            ($this->getPage()->subpage - 1) * $perPage);
        if (count($articles) && $totalPageCount > 1)
        {
            $pager = new PaginationHelper($totalPageCount, $this->getPage()->subpage);
        }
        else
        {
            $pager = null;
        }

        $vars = array(
            'settings' => $this->getPlugin()->settings,
            'page' => $this->getPage(),
            'articles' => $articles,
            'pager' => $pager
        );

        $tmpl = $this->getPlugin()->templates()->client('List.html');
        $html = $tmpl->compile($vars);

        return $html;
    }

    /**
     * Отрисовка статьи
     *
     * @return string
     *
     * @since 3.01
     */
    private function actionView()
    {
        /** @var Articles_Entity_Article $article */
        $article = ORM::getTable($this->getPlugin(), 'Article')->find($this->getPage()->topic);
        if (null === $article || false === $article->active)
        {
            $this->getPage()->httpError(404);
        }

        $this->getPage()->section []= $article->caption;

        $this->addToPath($article);

        $vars = array(
            'settings' => $this->getPlugin()->settings,
            'page' => $this->getPage(),
            'article' => $article,
        );
        $tmpl = $this->getPlugin()->templates()->client('Article.html');
        $html = $tmpl->compile($vars);
        return $html;
    }

    /**
     * Добавляет статью в путь плагина Path
     *
     * @param Articles_Entity_Article $article
     *
     * @since 3.01
     */
    private function addToPath($article)
    {
        $pathItem = array(
            'access' => $this->getPage()->access,
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
    }
}

