<?php
/**
 * Модель статьи
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
 * Модель статьи
 *
 * @property       int      $id         идентификатор
 * @property       int      $section    идентифкатор раздела
 * @property       bool     $active     вкл/выкл
 * @property       int      $position   порядковый номер
 * @property       DateTime $posted     дата публикации
 * @property       bool     $block      показывать в блоке
 * @property       string   $caption    заголовок
 * @property       string   $preview    краткий текст
 * @property       string   $text       полный текст
 * @property-write string   $image      задаёт картинку по имени элемента массива $_FILES
 * @property-read  string   $imageUrl   адрес картинки
 * @property-read  string   $thumbUrl   адрес миниатюры
 * @property-read  string   $clientUrl  адрес статьи
 *
 * @since 3.01
 */
class Articles_Entity_Article extends ORM_Entity implements ArrayAccess
{
    /**
     * Временный файл для добавляемой картинки
     * @var null|string
     * @since 3.01
     */
    private $tmpFile = null;

    /**
     * Действия перед сохранением объекта в БД
     *
     * @param ezcQuery $query
     *
     * @since 3.01
     */
    public function beforeSave(ezcQuery $query)
    {
        parent::beforeSave($query);
        if ('' === $this->preview)
        {
            $this->createPreviewFromText();
            /** @var ezcQueryInsert|ezcQueryUpdate $query */
            $query->set('preview', $query->bindValue($this->preview, ":preview", PDO::PARAM_STR));
        }
    }

    /**
     * Действия после сохренения объекта в БД
     *
     * @since 3.01
     */
    public function afterSave()
    {
        if ($this->tmpFile)
        /* Если был загружен новый файл… */
        {
            $filename = Eresus_Kernel::app()->getFsRoot() . '/data/' . $this->plugin->name .
                '/' . $this->id;
            useLib('glib');
            thumbnail($this->tmpFile, $filename . '.jpg', $this->plugin->settings['imageWidth'],
                $this->plugin->settings['imageHeight'], $this->plugin->settings['imageColor']);
            thumbnail($this->tmpFile, $filename . '-thmb.jpg',
                $this->plugin->settings['THimageWidth'], $this->plugin->settings['THimageHeight'],
                $this->plugin->settings['imageColor']);
            unlink($this->tmpFile);
        }
    }

    /**
     * Дополнительные действия при удалении
     *
     * @since 3.01
     */
    public function afterDelete()
    {
        $filename = Eresus_Kernel::app()->getFsRoot() . '/data/' . $this->plugin->name .
            '/' . $this->id;
        unlink($filename . '.jpg');
        unlink($filename . '-thmb.jpg');
    }

    /**
     * Автоматически заполняет свойство $preview на основе свойства $text
     *
     * @since 3.01
     */
    public function createPreviewFromText()
    {
        $preview = $this->text;
        $preview = trim(preg_replace('/<.+>/Us', ' ', $preview));
        $preview = str_replace(array("\n", "\r"), ' ', $preview);
        $preview = preg_replace('/\s{2,}/U', ' ', $preview);

        if (!$this->plugin->settings['previewMaxSize'])
        {
            $this->plugin->settings['previewMaxSize'] = 500;
        }

        if ($this->plugin->settings['previewSmartSplit'])
        {
            preg_match("/\A(.{1," . $this->plugin->settings['previewMaxSize'] .
                "})(\.\s|\.|\Z)/Us", $preview, $result);
            $preview = $result[1];
        }
        else
        {
            $preview = mb_substr($preview, 0, $this->plugin->settings['previewMaxSize']);
        }
        if (mb_strlen($preview) < mb_strlen($this->text))
        {
            $preview .= '…';
        }
        $this->preview = strval($preview);
    }

    /**
     * @see {@link ArrayAccess}
     * @since 3.01
     */
    public function offsetExists($offset)
    {
        return $this->__get($offset) !== null;
    }

    /**
     * @see {@link ArrayAccess}
     * @since 3.01
     */
    public function offsetGet($offset)
    {
        $value = $this->{$offset};
        if ($value instanceof DateTime)
        {
            $value = $value->format('Y-m-d H:i:s');
        }
        return $value;
    }

    /**
     * @see {@link ArrayAccess}
     * @since 3.01
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * @see {@link ArrayAccess}
     * @since 3.01
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }

    /**
     * Отрисовывает сатью, используя шаблон
     *
     * @param string $template
     *
     * @return string  HTML
     *
     * @since 3.01
     * @todo удалить после перехода на Dwoo
     */
    public function render($template)
    {
        $html = str_replace(
            array(
                '$(caption)',
                '$(preview)',
                '$(text)',
                '$(posted)',
                '$(clientUrl)',
                '$(imageUrl)',
                '$(thumbUrl)',
            ),
            array(
                $this->caption,
                $this->preview,
                $this->text,
                $this->posted->format('d.m.y'),
                $this->clientUrl,
                $this->imageUrl,
                $this->thumbUrl,
            ),
            $template
        );
        return $html;
    }

    /**
     * Задаёт заголовок статьи
     *
     * @param string $caption
     * @since 3.01
     */
    protected function setCaption($caption)
    {
        $this->setProperty('caption', strip_tags(htmlspecialchars($caption)));
    }

    /**
     * Задаёт изображение
     *
     * Если $value — null, изобаржение будет удалено
     *
     * @param string|null $value
     *
     * @since 3.01
     */
    protected function setImage($value)
    {
        if (null === $value && $this->imageUrl)
        {
            $root = Eresus_Kernel::app()->getLegacyKernel()->fdata;
            @unlink($root . $this->plugin->name . '/' . $this->id . '.jpg');
            @unlink($root . $this->plugin->name . '/' . $this->id . '-thmb.jpg');
        }
        elseif (is_uploaded_file($_FILES[$value]['tmp_name']))
        {
            $this->tmpFile = upload('image',
                tempnam(Eresus_Kernel::app()->getFsRoot() . 'var', $this->plugin->name));
        }
    }

    /**
     * Возвращает адрес картинки
     *
     * @return string|null
     *
     * @since 3.01
     */
    protected function getImageUrl()
    {
        $localPart = $this->plugin->name . '/' . $this->id . '.jpg';
        if (file_exists(Eresus_Kernel::app()->getLegacyKernel()->fdata . $localPart))
        {
            return Eresus_Kernel::app()->getLegacyKernel()->data . $localPart;
        }
        return null;
    }

    /**
     * Возвращает адрес миниатюры
     *
     * @return string|null
     *
     * @since 3.01
     */
    protected function getThumbUrl()
    {
        $localPart = $this->plugin->name . '/' . $this->id . '-thmb.jpg';
        if (file_exists(Eresus_Kernel::app()->getLegacyKernel()->fdata . $localPart))
        {
            return Eresus_Kernel::app()->getLegacyKernel()->data . $localPart;
        }
        return null;
    }

    /**
     * Возвращает адрес статьи
     *
     * @return string
     *
     * @since 3.01
     */
    protected function getClientUrl()
    {
        return Eresus_Kernel::app()->getPage()->clientURL($this->section) . $this->id . '/';
    }
}

