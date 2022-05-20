<?php
/**
 * @package     Oasis
 * @subpackage  Administrator
 *
 * @author      Viktor G. <ever2013@mail.ru>
 * @copyright   Copyright (C) 2021 Oasiscatalog. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.oasiscatalog.com/
 */

defined('_JEXEC') or die;

/**
 * Oasis Json controller.
 *
 * @package     Oasis
 * @subpackage  sub controller
 *
 * @since 2.0
 */
class OasisControllerOasis extends JControllerLegacy
{
    /**
     * Send order oasis
     *
     * @since 2.0
     */
    public function sendOrder()
    {
        if (!JSession::checkToken('get')) {
            echo new JResponseJson(null, JText::_('JINVALID_TOKEN'), true);
        } else {
            try {
                $response = null;
                $input = JFactory::getApplication()->input;
                $orderId = $input->get('orderId');
                $model = $this->getModel();
                $orderProducts = $model->getData('#__virtuemart_order_items', ['*'], ['virtuemart_order_id' => $orderId], false, 'loadAssocList');
                $orderProductItems = [];

                foreach ($orderProducts as $orderProduct) {
                    $orderProductItems[] = [
                        'productId' => $model->getData('#__oasis_product', ['product_id_oasis'], ['article' => $orderProduct['order_item_sku']]),
                        'quantity'  => $orderProduct['product_quantity'],
                    ];
                }

                if ($orderProductItems) {
                    $params = JComponentHelper::getParams('com_oasis');
                    $api_key = $params->get('oasis_api_key');

                    $data = [];
                    $data['userId'] = $params->get('oasis_user_id');
                    $data['items'] = $orderProductItems;

                    $options = [
                        'http' => [
                            'method'  => 'POST',
                            'header'  => 'Content-Type: application/json' . PHP_EOL .
                                'Accept: application/json' . PHP_EOL,
                            'content' => json_encode($data),
                        ],
                    ];

                    $request = json_decode(file_get_contents('https://api.oasiscatalog.com/v4/reserves/?key=' . $api_key, 0, stream_context_create($options)));

                    if (isset($request->queueId)) {
                        $dataOrder = [
                            'order_id' => $orderId,
                            'queue_id' => $request->queueId,
                        ];

                        if (is_null($model->getData('#__oasis_order', ['queue_id'], ['order_id' => $orderId]))) {
                            $response = $model->addData('#__oasis_order', $dataOrder);
                        } else {
                            $response = $model->upData('#__oasis_order', ['order_id' => $orderId], ['queue_id' => $request->queueId]);
                        }

                    }
                }

                if (is_null($response)) {
                    echo new JResponseJson(null, JText::_('COM_OASIS_ORDER_STATUS_ERROR'), true);
                } else {
                    echo new JResponseJson($response);
                }

            } catch (Exception $e) {
                echo new JResponseJson($e);
            }
        }
    }
}
