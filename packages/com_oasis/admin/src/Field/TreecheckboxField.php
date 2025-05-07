<?php
/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2023 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

namespace Oasiscatalog\Component\Oasis\Administrator\Field;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;

defined('_JEXEC') or die;

/**
 * The TreeCheckbox form field class of the Oasis component
 *
 * @since 4.0
 */
class TreeCheckboxField extends FormField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'TreeCheckbox';

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	private $treeCats = '';

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getInput()
	{
		$cf = OasisConfig::instance([
			'init' => true,
			'init_rel' => true,
		]);
		if (!empty($cf->api_key)) {
			$categories = OasisHelper::getOasisCategories();
		}

		$arr_cat = [];
		foreach ($categories as $item) {
			if (empty($arr_cat[(int)$item->parent_id])) {
				$arr_cat[(int)$item->parent_id] = [];
			}
			$arr_cat[(int)$item->parent_id][] = (array)$item;
		}

		return '<div id="tree" class="oa-tree">
				<div class="oa-tree-ctrl">
					<button type="button" class="btn btn-sm btn-light oa-tree-ctrl-m">'.Text::_('COM_OASIS_BTN_COLLAPSE_ALL', true).'</button>
					<button type="button" class="btn btn-sm btn-light oa-tree-ctrl-p">'.Text::_('COM_OASIS_BTN_EXPAND_ALL', true).'</button>
				</div>' . OasisHelper::buildTreeCats($arr_cat, $cf->categories, $cf->categories_rel) . '</div>';
	}
}
