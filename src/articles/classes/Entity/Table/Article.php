<?php
/**
 * Таблица статей
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
 * Таблица статей
 *
 * @since 3.01
 */
class Articles_Entity_Table_Article extends ORM_Table
{
    /**
     * Описание таблицы
     * @since 3.01
     */
    protected function setTableDefinition()
    {
        $this->setTableName('articles');
        $this->hasColumns(array(
            'id' => array(
                'type' => 'integer',
                'unsigned' => true,
                'autoincrement' => true,
            ),
            'section' => array(
                'type' => 'integer',
                'unsigned' => true,
            ),
            'active' => array(
                'type' => 'boolean',
            ),
            'position' => array(
                'type' => 'integer',
                'unsigned' => true,
            ),
            'posted' => array(
                'type' => 'datetime',
            ),
            'block' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'caption' => array(
                'type' => 'string',
                'length' => 255,
            ),
            'preview' => array(
                'type' => 'string',
            ),
            'text' => array(
                'type' => 'string',
            )
        ));
        $this->setOrdering($this->getPlugin()->settings['listSortMode'],
            $this->getPlugin()->settings['listSortDesc']
                ? ezcQuerySelect::DESC
                : ezcQuerySelect::ASC);
        $this->index('admin_idx', array('fields' => array('section', 'position')));
        $this->index('cl_position_idx', array('fields' => array('section', 'active', 'position')));
        $this->index('cl_date_idx', array('fields' => array('section', 'active', 'posted')));
        $this->index('cl_block_idx', array('fields' => array('block', 'active')));
    }

    /**
     * Возвращает список статей в указанном разделе
     *
     * @param int  $sectionId  идентификатор раздела
     * @param int  $limit      ограничение на количество возвращаемых статей
     * @param int  $offset     пропустить указанное количество статей
     * @param bool $inactive   выводить неактивные объекты (true) или только активные (false)
     *
     * @return array
     *
     * @since 3.01
     */
    public function findInSection($sectionId, $limit = null, $offset = 0, $inactive = false)
    {
        $q = $this->createSelectQuery();
        $where = array();
        $where []= $q->expr->eq('section', $q->bindValue($sectionId, null, PDO::PARAM_INT));
        if (true !== $inactive)
        {
            $where []= $q->expr->eq('active', $q->bindValue(true, null, PDO::PARAM_INT));
        }
        $q->where($q->expr->lAnd($where));
        return $this->loadFromQuery($q, $limit, $offset);
    }

    /**
     * Возвращает количество статей в указанном разделе
     *
     * @param int  $sectionId  идентификатор раздела
     * @param bool $inactive   учитывать неактивные объекты (true) или только активные (false)
     *
     * @return int
     *
     * @since 3.01
     */
    public function countInSection($sectionId, $inactive = false)
    {
        $q = $this->createCountQuery();
        $where = array();
        $where []= $q->expr->eq('section', $q->bindValue($sectionId, null, PDO::PARAM_INT));
        if (true !== $inactive)
        {
            $where []= $q->expr->eq('active', $q->bindValue(true, null, PDO::PARAM_INT));
        }
        $q->where($q->expr->lAnd($where));
        return $this->count($q);
    }
}

