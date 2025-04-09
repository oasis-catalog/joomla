<?php
namespace Oasiscatalog\Component\Oasis\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Oasiscatalog\Component\Oasis\Administrator\Helper\OasisHelper;


class Config {
    private SymfonyStyle $ioStyle;

	public bool $is_debug = false;
	public bool $is_debug_log = false;
	public string $upload_path;

	public string $api_key;
	public string $api_user_id;

	public string $currency;

	public array $categories;
	public array $categories_rel;
	private array $categories_easy;
	public ?int $category_rel;
	public string $category_rel_label;

	public array $progress;

	public array $currencies;

	public bool $is_not_up_cat;
	public bool $is_import_anytime;

	public ?int $limit;

	public ?\DateTime $import_date;

	public ?float $price_factor;
	public ?float $price_increase;
	public bool $is_price_dealer;

	public bool $is_no_vat;
	public bool $is_not_on_order;
	public ?float $price_from;
	public ?float $price_to;
	public ?int $rating;
	public bool $is_wh_moscow;
	public bool $is_wh_europe;
	public bool $is_wh_remote;

	public bool $is_up_photo;
	public bool $is_delete_exclude;

	private bool $is_init = false;
	private bool $is_init_rel = false;

	private static $instance;


	public static function instance($opt = []) {
		if (!isset(self::$instance)) {
			self::$instance = new self($opt);
		} else {
			if(!empty($opt['init'])){
				self::$instance->init();
			}
			if(!empty($opt['init_rel'])){
				self::$instance->initRelation();
			}
			if(!empty($opt['load_currencies'])){
				self::$instance->loadCurrencies();
			}
		}

		return self::$instance;
	}

	public function __construct($opt = []) {
        $registry = Factory::getApplication()->getConfig();
		$this->upload_path = $registry->get('tmp_path', JPATH_ROOT . '/tmp');

		$this->is_debug = !empty($opt['debug']);
		$this->is_debug_log = !empty($opt['debug_log']);

		OasisHelper::$cf = $this;

		if(!empty($opt['init'])){
			$this->init();
		}
		if(!empty($opt['init_rel'])){
			$this->initRelation();
		}
		if(!empty($opt['load_currencies'])){
			$this->loadCurrencies();
		}
	}

	public function init() {
		if($this->is_init) {
			return;
		}
		$opt = ComponentHelper::getParams('com_oasis');

		$this->progress = [
			'item' =>		$opt['progress_item'] ?? 0,			// count updated products
			'total' =>		$opt['progress_total'] ?? 0,		// count all products
			'step' =>		$opt['progress_step'] ?? 0,			// step (for limit)
			'step_item' =>	$opt['progress_step_item'] ?? 0,	// count updated products for step
			'step_total' =>	$opt['progress_step_total'] ?? 0,	// count step total products
			'date' =>		$opt['progress_date'] ?? '',		// date end import
			'date_step' =>	$opt['progress_date_step'] ?? ''	// date end import for step
		];

		$this->api_key =		$opt['api_key'] ?? '';
		$this->api_user_id =	$opt['api_user_id'] ?? '';

		$this->currency =		$opt['currency'] ?? 'rub';
		$this->limit =			!empty($opt['limit']) ? intval($opt['limit']) : null;

		$this->categories =		$opt['categories'] ?? [];

		$cat_rel = $opt['categories_rel'] ?? [];
		$this->categories_rel = [];
		foreach($cat_rel as $rel){
			$rel = 	explode('_', $rel);
			$cat_id = (int)$rel[0];
			$rel_id = (int)$rel[1];

			$this->categories_rel[$cat_id] = [
				'id' =>  $rel_id,
				'rel_label' => null
			];
		}

		$this->price_factor =			!empty($opt['price_factor']) ? floatval(str_replace(',', '.', $opt['price_factor'])) : null;
		$this->price_increase =			!empty($opt['price_increase']) ? floatval(str_replace(',', '.', $opt['price_increase'])) : null;
		$this->is_price_dealer =		!empty($opt['is_price_dealer']);

		$this->is_import_anytime =		!empty($opt['is_import_anytime']);
		$dt = null;
		if(!empty($this->progress['date'])){
			$dt = \DateTime::createFromFormat('d.m.Y H:i:s', $this->progress['date']);
		}
		$this->import_date = $dt;

		$this->category_rel = 			!empty($opt['category_rel']) ? intval($opt['category_rel']) : null;
		$this->category_rel_label = 	'';
		$this->is_not_up_cat =			!empty($opt['is_not_up_cat']);

		$this->is_no_vat =				!empty($opt['is_no_vat']);
		$this->is_not_on_order =		!empty($opt['is_not_on_order']);
		$this->price_from =				!empty($opt['price_from']) ? floatval(str_replace(',', '.', $opt['price_from'])) : null;
		$this->price_to =				!empty($opt['price_to']) ? floatval(str_replace(',', '.', $opt['price_to'])) : null;
		$this->rating =					!empty($opt['rating']) ? intval($opt['rating']) : null;
		$this->is_wh_moscow =			!empty($opt['is_wh_moscow']);
		$this->is_wh_europe =			!empty($opt['is_wh_europe']);
		$this->is_wh_remote =			!empty($opt['is_wh_remote']);

		$this->is_init = true;
	}

	public function initRelation() {
		if($this->is_init_rel) {
			return;
		}

		foreach($this->categories_rel as $cat_id => $rel) {
			$this->categories_rel[$cat_id]['rel_label'] = $this->getRelLabel($rel['id']);
		}
		if(isset($this->category_rel)) {
			$this->category_rel_label = $this->getRelLabel($this->category_rel);
		}

		$this->is_init_rel = true;
	}

    public function configureSymfonyIO(InputInterface $input, OutputInterface $output) {
        $this->ioStyle = new SymfonyStyle($input, $output);
    }

	private function getRelLabel(int $cat_id) {
		$list = [];
		if (!class_exists('vmCustomPlugin')){
            require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
            if(class_exists('VmConfig')) \VmConfig::loadConfig();
        }
        $categoryModel = \VmModel::getModel('Category');

		while($cat_id != 0){
	        $cat = $categoryModel->getCategory($cat_id, false, false);
			$list []= $cat->category_name;
			$cat_id = $cat->category_parent_id;
		}
		return implode(' / ', array_reverse($list));
	}

	public function progressStart(int $total, int $step_total) {
		$this->progress['total'] = $total;
		$this->progress['step_total'] = $step_total;
		$this->progress['step_item'] = 0;
		$this->updateSettingProgress();
	}

	public function progressUp() {
		$this->progress['step_item']++;
		$this->updateSettingProgress();
	}

	public function progressEnd() {
		$dt = (new \DateTime())->format('d.m.Y H:i:s');
		$this->progress['date_step'] = $dt;

		if($this->limit > 0){
			$this->progress['item'] += $this->progress['step_item'];

			if(($this->limit * ($this->progress['step'] + 1)) > $this->progress['total']){
				$this->progress['step'] = 0;
				$this->progress['item'] = 0;
				$this->progress['date'] = $dt;
			}
			else{
				$this->progress['step']++;
			}
		}
		else{
			$this->progress['date'] = $dt;
			$this->progress['item'] = 0;
		}

		$this->progress['step_item'] = 0;
		$this->progress['step_total'] = 0;

		$this->updateSettingProgress();
	}

	public function progressClear() {
		$this->progress = [
			'item' => 0,			// count updated products
			'total' => 0,			// count all products
			'step' => 0,			// step (for limit)
			'step_item' => 0,		// count updated products for step
			'step_total' => 0,		// count step total products
			'date' => '',			// date end import
			'date_step' => ''		// date end import for step
		];
		$this->updateSettingProgress();
	}

	private function updateSettingProgress () {
		$params = ComponentHelper::getParams('com_oasis');

        foreach ($this->progress as $key => $value) {
            $params->set('progress_'.$key, $value);
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'));
        $query->set($db->quoteName('params') . ' = ' . $db->quote((string)$params));
        $query->where($db->quoteName('element') . ' = ' . $db->quote('com_oasis'));
        $query->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $db->execute();
	}

	public function getOptBar() {
		$is_process = $this->checkLockProcess();

		$opt = $this->progress;
		$p_total = 0;
		$p_step = 0;

		if (!empty($opt['step_item']) && !empty($opt['step_total'])) {
			$p_step = round(($opt['step_item'] / $opt['step_total']) * 100, 2, PHP_ROUND_HALF_DOWN );
			$p_step = min($p_step, 100);
		}

		if (!(empty($opt['item']) && empty($opt['step_item'])) && !empty($opt['total'])) {
			$p_total = round((($opt['item'] + $opt['step_item']) / $opt['total']) * 100, 2, PHP_ROUND_HALF_DOWN );
			$p_total = min($p_total, 100);
		}

		return [
			'is_process' =>	$is_process,
			'p_total' =>	$p_total,
			'p_step' =>		$p_step,
			'step' =>		$opt['step'] ?? 0,
			'steps' =>		($this->limit > 0 && !empty($opt['total'])) ? (ceil($opt['total'] / $this->limit)) : 0,
			'date' =>		$opt['date_step'] ?? ''
		];
	}

	public function checkCronKey(string $cron_key): bool {
		return $cron_key === md5($this->api_key);
	}

	public function getCronKey(): string {
		return md5($this->api_key);
	}

	public function checkApi(): bool {
		return !empty(OasisHelper::getOasisCurrencies());
	}

	public function lock($fn, $fn_error) {
		$lock = fopen($this->upload_path . '/com_oasis_start.lock', 'w');
		if ($lock && flock($lock, LOCK_EX | LOCK_NB)) {
			$fn();
		}
		else{
			$fn_error();
		}
	}

	public function checkLockProcess(): bool {
		$lock = fopen($this->upload_path . '/com_oasis_start.lock', 'w');
		if (!($lock && flock( $lock, LOCK_EX | LOCK_NB ))) {
			return true;
		}
		return false;
	}

	public function checkPermissionImport(): bool {
		if(!$this->is_import_anytime && 
			$this->import_date &&
			$this->import_date->format("Y-m-d") == (new \DateTime())->format("Y-m-d")){
				return false;
		}
		return true;
	}

	public function log($str, $type = 'info') {
		if ($this->is_debug || $this->is_debug_log) {
			$str = date('H:i:s').' '.$str;

			if ($this->is_debug_log) {
				file_put_contents($this->upload_path . '/com_oasis_'.date('Y-m-d').'.log', $str . "\n", FILE_APPEND);
			} else {
				if(!empty($this->ioStyle)){
					switch($type){
						case 'info':
							$this->ioStyle->writeln($str);
							break;
						case 'warn':
							$this->ioStyle->warning($str);
							break;
						case 'success':
							$this->ioStyle->success($str);
							break;
					}
				}
			}
		}
	}

	public function getRelCategoryId($oasis_cat_id) {
		if(isset($this->categories_rel[$oasis_cat_id])){
			return $this->categories_rel[$oasis_cat_id]['id'];
		}
		if(isset($this->category_rel)){
			return $this->category_rel;
		}
		return null;
	}

	public function getEasyCategories() {
		
	}

	public function loadCurrencies(): bool {
		$data = Api::getCurrenciesOasis();
		if(empty($data))
			return false;

		$currencies = [];
		foreach ($data as $currency) {
			$currencies[] = [
				'code' => $currency->code,
				'name' => $currency->full_name
			];
		}
		$this->currencies = $currencies;
		return true;
	}
}