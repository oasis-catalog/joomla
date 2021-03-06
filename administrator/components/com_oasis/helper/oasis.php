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
 * Helper class for the component.
 *
 * @package     Oasis
 * @subpackage  Helper
 *
 * @since 2.0
 */
final class OasisHelper
{
    /**
     * Database connector
     *
     * @var    JDatabase
     *
     * @since 2.0
     */
    protected $db;

    /**
     * Public class constructor
     *
     * @since 2.0
     */
    public function __construct()
    {
        $this->db = JFactory::getDbo();
    }

    /**
     * Search object in array
     *
     * @param $data
     * @param $id
     * @return mixed
     *
     * @since 2.0
     */
    public static function searchObject($data, $id)
    {
        $neededObject = array_filter($data, function ($e) use ($id) {
            return $e->id == $id;
        });

        if (!$neededObject) {
            return false;
        }

        return array_shift($neededObject);
    }

    /**
     * Get oasis products
     *
     * @param array $args
     * @return mixed
     *
     * @since 2.0
     */
    public static function getOasisProducts(array $args = [])
    {
        $params = JComponentHelper::getParams('com_oasis');

        $args['fieldset'] = 'full';

        $data = [
            'currency'         => $params->get('oasis_currency'),
            'no_vat'           => $params->get('oasis_no_vat'),
            'not_on_order'     => $params->get('oasis_not_on_order'),
            'price_from'       => $params->get('oasis_price_from'),
            'price_to'         => $params->get('oasis_price_to'),
            'rating'           => $params->get('oasis_rating'),
            'warehouse_moscow' => $params->get('oasis_warehouse_moscow'),
            'warehouse_europe' => $params->get('oasis_warehouse_europe'),
            'remote_warehouse' => $params->get('oasis_remote_warehouse'),
        ];

        $category = $params->get('oasis_categories');

        if (!$args['category'] && $category) {
            $data['category'] = implode(',', $category);
        } elseif (!$args['category'] && !$category) {
            $categories = OasisHelper::getOasisCategories();

            foreach ($categories as $item) {
                if ($item->level === 1) {
                    $category[] = $item->id;
                }
            }

            $data['category'] = implode(',', $category);
        }

        foreach ($data as $key => $value) {
            if ($value) {
                $args[$key] = $value;
            }
        }

        return OasisHelper::curlQuery('v4/', 'products', $args);
    }

    /**
     * @param     $text
     * @param int $len
     * @return string
     *
     * @since 2.0
     */
    public static function textExcerpt($text, int $len = 15): string
    {
        $limit = $len + 1;
        $excerpt = explode(' ', $text, $limit);
        $num_words = count($excerpt);
        if ($num_words >= $len) {
            $last_item = array_pop($excerpt);
        }

        return implode(" ", $excerpt);
    }

    /**
     * Get oasis currencies
     *
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function getOasisCurrencies()
    {
        return OasisHelper::curlQuery('v4/', 'currencies');
    }

    /**
     * Get oasis categories
     *
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function getOasisCategories()
    {
        return OasisHelper::curlQuery('v4/', 'categories', ['fields' => 'id,parent_id,root,level,slug,name,path']);
    }

    /**
     * Get oasis manufacturers
     *
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function getOasisManufacturers()
    {
        return OasisHelper::curlQuery('v3/', 'brands');
    }

    /**
     * Get oasis stock products
     *
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function getOasisStock()
    {
        return OasisHelper::curlQuery('v4/', 'stock', ['fields' => 'id,stock']);
    }

    /**
     * Get oasis queue id
     *
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function getOasisQueue($queue_id)
    {
        return OasisHelper::curlQuery('v4/', 'reserves/by-queue/' . $queue_id);
    }

    /**
     * @param       $version
     * @param       $type
     * @param array $args
     * @return false|mixed
     *
     * @since 2.0
     */
    public static function curlQuery($version, $type, array $args = [])
    {
        $params = JComponentHelper::getParams('com_oasis');

        $args_pref = [
            'key'    => $params->get('oasis_api_key'),
            'format' => 'json',
        ];
        $args = array_merge($args_pref, $args);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.oasiscatalog.com/' . $version . $type . '?' . http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200 ? $result : false;
    }

    /**
     * @param     $data
     * @param int $count
     * @return false|string
     *
     * @since 2.0
     */
    public static function saveImg($data, int $count = 0)
    {
        $ext = pathinfo($data['img_url']);

        if (!array_key_exists('extension', $ext) || $ext['extension'] === 'tif') {
            return false;
        }

        $count === 0 ? $postfix = '' : $postfix = '-' . $count;

        if (empty($data['img_name']) || $data['img_name'] === '') {
            $data['img_name'] = $ext['filename'];
        }

        $img = OasisHelper::imgFolder($data['folder_name']) . $data['img_name'] . $postfix . '.' . $ext['extension'];

        if (!file_exists($img)) {
            $pic = file_get_contents($data['img_url'], true, stream_context_create([
                'http' => [
                    'ignore_errors'   => true,
                    'follow_location' => true
                ],
                'ssl'  => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]));

            if (!preg_match("/200|301/", $http_response_header[0])) {
                return false;
            }
            file_put_contents($img, $pic);
        }

        return $data['folder_name'] . '/' . $data['img_name'] . $postfix . '.' . $ext['extension'];
    }

    /**
     * @param $folder
     * @return false|string
     *
     * @since 2.0
     */
    public static function imgFolder($folder)
    {
        $path = JPATH_ROOT . '/' . $folder . '/';

        if (!file_exists($path)) {
            $create = mkdir($path, 0755, true);
            if (!$create) {
                return false;
            }
        }

        return $path;
    }

    /**
     * @param $str
     * @return string
     *
     * @since 2.0
     */
    public static function transliter($str): string
    {
        $arr_trans = [
            '??'  => 'A',
            '??'  => 'B',
            '??'  => 'V',
            '??'  => 'G',
            '??'  => 'D',
            '??'  => 'E',
            '??'  => 'E',
            '??'  => 'J',
            '??'  => 'Z',
            '??'  => 'I',
            '??'  => 'Y',
            '??'  => 'K',
            '??'  => 'L',
            '??'  => 'M',
            '??'  => 'N',
            '??'  => 'O',
            '??'  => 'P',
            '??'  => 'R',
            '??'  => 'S',
            '??'  => 'T',
            '??'  => 'U',
            '??'  => 'F',
            '??'  => 'H',
            '??'  => 'TS',
            '??'  => 'CH',
            '??'  => 'SH',
            '??'  => 'SCH',
            '??'  => '',
            '??'  => 'YI',
            '??'  => '',
            '??'  => 'E',
            '??'  => 'YU',
            '??'  => 'YA',
            '??'  => 'a',
            '??'  => 'b',
            '??'  => 'v',
            '??'  => 'g',
            '??'  => 'd',
            '??'  => 'e',
            '??'  => 'e',
            '??'  => 'j',
            '??'  => 'z',
            '??'  => 'i',
            '??'  => 'y',
            '??'  => 'k',
            '??'  => 'l',
            '??'  => 'm',
            '??'  => 'n',
            '??'  => 'o',
            '??'  => 'p',
            '??'  => 'r',
            '??'  => 's',
            '??'  => 't',
            '??'  => 'u',
            '??'  => 'f',
            '??'  => 'h',
            '??'  => 'ts',
            '??'  => 'ch',
            '??'  => 'sh',
            '??'  => 'sch',
            '??'  => 'y',
            '??'  => 'yi',
            '??'  => '',
            '??'  => 'e',
            '??'  => 'yu',
            '??'  => 'ya',
            '.'  => '-',
            ' '  => '-',
            '?'  => '-',
            '/'  => '-',
            '\\' => '-',
            '*'  => '-',
            ':'  => '-',
            '>'  => '-',
            '|'  => '-',
            '\'' => '',
            '('  => '',
            ')'  => '',
            '!'  => '',
            '@'  => '',
            '%'  => '',
            '`'  => '',
        ];

        $str = str_replace(['-', '+', '.', '?', '/', '\\', '*', ':', '*', '|'], ' ', $str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);
        $pattern = '/[\w\s\d]+/u';
        preg_match_all($pattern, $str, $result);
        $str = implode('', $result[0]);
        $str = preg_replace('/[\s]+/us', ' ', $str);
        $str_trans = preg_replace("/[^0-9a-z-_ ]/i", "", strtr($str, $arr_trans));

        return strtolower($str_trans);
    }

    /**
     * @param $msg
     * @param bool $logFile
     *
     * @since 2.0
     */
    public static function debug($msg, $logFile = false)
    {
//        return;

        if ($logFile) {
            self::saveToLog($msg);
        } else {
            print_r($msg);
        }
    }

    /**
     * @param $msg
     *
     * @since 2.0
     */
    public static function saveToLog($msg)
    {
        $str = date('Y-m-d H:i:s') . ' | ' . $msg . PHP_EOL;
        $filename = JPATH_ROOT . DS . 'oasis_log.txt';
        if (!file_exists($filename)) {
            $fp = fopen($filename, 'wb');
            fwrite($fp, $str);
            fclose($fp);
        } else {
            file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);
        }
    }
}
