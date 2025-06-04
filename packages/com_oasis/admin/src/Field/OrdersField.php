<?php
namespace Oasiscatalog\Component\Oasis\Administrator\Field;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;
use Oasiscatalog\Component\Oasis\Administrator\Model\OasisModel;
use Joomla\CMS\Form\FormField;

defined('_JEXEC') or die;

/**
 * Class JFormFieldOrders component Oasis.
 *
 * @since 4.0
 */
class OrdersField extends FormField
{
    /**
     * Model class
     *
     * @var    null
     *
     * @since 4.0
     */
    private $model = null;

    /**
     * @var string
     *
     * @since 4.0
     */
    protected $type = 'Orders';

    /**
     * Class constructor.
     *
     * @param null $form
     *
     * @since 4.0
     */
    public function __construct($form = null)
    {
        parent::__construct($form);

        $this->model = new OasisModel();
    }

    /**
     * @return string
     *
     * @since 4.0
     */
    public function getInput(): string
    {
        $orders = $this->model->getData('#__virtuemart_orders', ['*'], [], false, 'loadAssoclist');

        $html = '
            <table class="table table-striped" id="styleList">
				<thead>
					<tr>
						<th width="2%" class="nowrap">ID</th>
						<th width="5%" class="nowrap center">№</th>
						<th class="nowrap">Товары</th>
						<th width="5%" class="nowrap">Статус заказа в Oasis</th>
						<th width="5%" class="nowrap">№ заказа в Oasis</th>
						<th width="5%" class="nowrap center">Выгрузить в Oasis</th>
					</tr>
				</thead>
				<tbody>';

        foreach ($orders as $order) {
            $orderItems = $this->model->getData('#__virtuemart_order_items', ['*'], ['virtuemart_order_id' => $order['virtuemart_order_id']], false, 'loadAssocList');
            $productsName = [];

            foreach ($orderItems as $orderItem) {
                $productsName[] = $orderItem['order_item_name'];
            }

            $productsName = implode(', ', $productsName);
            $vmOasisQueueId = $this->model->getData('#__oasis_order', ['queue_id'], ['order_id' => $order['virtuemart_order_id']]);

            $oasisOrder = [
                'status' => Text::_('COM_OASIS_ORDER_NOT_UPLOAD'),
                'number' => Text::_('COM_OASIS_ORDER_NOT_UPLOAD'),
                'btn'    => '
                <input id="order-' . $order['virtuemart_order_id'] . '" type="hidden" name="order-' . $order['virtuemart_order_id'] . '" value="' . $order['virtuemart_order_id'] . '" />
                <button id="btn-' . $order['virtuemart_order_id'] . '" type="button" class="btn btn-small button-apply btn-primary" onclick="sendHere(' . $order['virtuemart_order_id'] . ')" data-complete-text=\'<span class="icon-publish icon-white" aria-hidden="true"></span>\'><span class="icon-upload icon-white" aria-hidden="true"></span></button>',
            ];

            if (!is_null($vmOasisQueueId)) {
                $oasisDataQueue = OasisHelper::getOasisQueue($vmOasisQueueId);

                if (isset($oasisDataQueue->state)) {
                    if ($oasisDataQueue->state == 'created') {
                        $oasisOrder['status'] = $oasisDataQueue->order->statusText;
                        $oasisOrder['number'] = $oasisDataQueue->order->number;
                        $oasisOrder['btn'] = '';
                    } elseif ($oasisDataQueue->state == 'pending') {
                        $oasisOrder['status'] = Text::_('COM_OASIS_ORDER_STATUS_PANDING');
                        $oasisOrder['number'] = '';
                        $oasisOrder['btn'] = '';
                    } elseif ($oasisDataQueue->state == 'error') {
                        $oasisOrder['status'] = Text::_('COM_OASIS_ORDER_STATUS_ERROR');
                    }
                }
            }

            $html .= '<tr>
						<td class="center">' . $order['virtuemart_order_id'] . '</td>
						<td class="center"><a href="/administrator/index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=' . $order['virtuemart_order_id'] . '">' . $order['order_number'] . '</a></td>
						<td>' . $productsName . '</td>
						<td>' . $oasisOrder['status'] . '</td>
						<td>' . $oasisOrder['number'] . '</td>
						<td class="center">' . $oasisOrder['btn'] . '</td>
					</tr>
					';
        }

        $html .= '
                </tbody>
			</table>
			<input id="token" type="hidden" name="' . Session::getFormToken() . '" value="1" />
            ';

        return $html;
    }
}
