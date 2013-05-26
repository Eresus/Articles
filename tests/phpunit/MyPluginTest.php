<?php
/**
 * Тесты класса MyPlugin
 *
 * @version ${product.version}
 *
 * @copyright [год], [владелец], [адрес, если нужен]
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author [Автор1 <E-mail автора1>]
 * @author [АвторN <E-mail автораN>]
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
 *
 * @package [Имя пакета]
 * @subpackage Tests
 */


require_once __DIR__ . '/bootstrap.php';
require_once TESTS_SRC_DIR . '/myplugin.php';

/**
 * Тесты класса MyPlugin
 * @package [Имя пакета]
 * @subpackage Tests
 */
class MyPluginTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers MyPlugin::__construct
     */
    public function testConstruct()
    {
    }
}

