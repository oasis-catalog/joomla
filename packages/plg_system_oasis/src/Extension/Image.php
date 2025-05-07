<?php

namespace Joomla\Plugin\System\Oasis\Extension;

defined('_JEXEC') or die();

use VmConfig;
use JFile;
use vRequest;
use JURI;
use vmText;
use VmConnector;


class Image  {
	private $_original;

	function __construct($original){
		$this->_original = $original;
	}

	public function __call($method, $args) {
		echo $name;
		return call_user_func_array([$this->_original, $method], $args);
	}

	public function __get($name)
	{
		return $this->_original->$name;
	}


	/**
	 * override administrator/components/com_virtuemart/helpers/image.php
	 */
	function displayMediaFull($imageArgs='',$lightbox=true,$effect ="class='modal'",$description = true ){

		if(!$this->file_is_forSale){
			// Remote image URL
			if( substr( $this->file_url, 0, 2) == "//" ) {
				$file_url = $this->file_url;
				$file_alt = $this->file_title;
			} else {

				$fullSizeFilenamePath = vRequest::filterPath(VMPATH_ROOT.'/'.$this->file_url_folder.$this->file_name.'.'.$this->file_extension);
				if (!file_exists($fullSizeFilenamePath)) {

					$this->setNoImageSet();
					$file_url = $this->file_url;
					$file_alt = $this->file_meta;
				} else {
					$file_url = $this->file_url;
					$file_alt = $this->file_meta;
				}
			}
			$postText = false;
			if($description) $postText = $this->file_description;


			$imageArgs = $this->imageArgsToArray($imageArgs);


			return $this->displayIt($file_url, $file_alt, $imageArgs,$lightbox,$effect,$postText);
		} else {
			//Media which should be sold, show them only as thumb (works as preview)
			return $this->displayMediaThumb(array('id'=>'vm_display_image'),false);
		}


	}

	/**
	 * override administrator/components/com_virtuemart/helpers/vmmedia.php
	 */
	function displayMediaThumb($imageArgs='',$lightbox=true,$effect="class='modal' rel='group'",$return = true,$withDescr = false,$absUrl = false, $width=0,$height=0){


		$imageArgs = $this->imageArgsToArray($imageArgs);

		//vmdebug('displayMediaThumb ');
		$typelessUrl = '';
		if(empty($this->file_name)){
			$typelessUrl = static::getStoriesFb('typeless').'/';
		}

		if( substr( $this->file_url, 0, 2) == "//" ) {
			$toChk = $this->file_url;
			try {
				$resObj = VmConnector::getHttp(array(), array('curl', 'stream'))->get($toChk);
				//vmdebug('Object per URL',$resObj);
				if($resObj->code!=200){
					vmdebug('VmMedia displayMediaThumb URL does not exists',$toChk,$resObj);
					vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
				};
			} catch (RuntimeException $e) {
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		} else {
			if($this->file_is_forSale){
				$toChk = $this->file_url;
			} else {
				$toChk = VMPATH_ROOT.'/'.$this->file_url;
			}
			if(empty($typelessUrl) and !JFile::exists($toChk)){
				vmdebug('Media file does not exists',$toChk);
				vmError(vmText::sprintf('COM_VIRTUEMART_FILE_NOT_FOUND',$toChk));
			}
		}

		//needs file_url_thumb, or  file_url_folder_thumb and file_name and file_extension
		$file_url_thumb = $this -> getFileUrlThumb($width, $height);
	//vmdebug('displayMediaThumb '.$file_url_thumb);
		$media_path = VMPATH_ROOT.DS.str_replace('/',DS,$file_url_thumb);

		if(empty($this->file_meta)){
			if(!empty($this->file_description)){
				$file_alt = $this->file_description;
			} else if(!empty($this->file_name)) {
				$file_alt = $this->file_name;
			} else {
				$file_alt = '';
			}
		} else {
			$file_alt = $this->file_meta;
		}

		if ((empty($file_url_thumb) || !file_exists($media_path)) && is_a($this,'VmImage')) {
			$file_url_thumb = $this->createThumb($width,$height);
			$media_path = VMPATH_ROOT.DS.str_replace('/',DS,$file_url_thumb);
		}
		//$this->file_url_thumb = $file_url_thumb;

		if($withDescr) $withDescr = $this->file_description;

		// if (empty($file_url_thumb) || !file_exists($media_path)) {
		// 	return $this->getIcon($imageArgs,$lightbox,$return,$withDescr,$absUrl);
		// }

		if($return) return $this->displayIt($file_url_thumb, $file_alt, $imageArgs,$lightbox,$effect,$withDescr,$absUrl);
	}


	/**
	 * override administrator/components/com_virtuemart/helpers/mediahandler.php
	 */
	function displayIt($file_url, $file_alt, $imageArgs, $lightbox, $effect ="class='modal'",$withDesc=false,$absUrl = false){

		if ($withDesc) $desc='<span class="vm-img-desc">'.$withDesc.'</span>';
		else $desc='';
		$root='';

		if( substr( $this->file_url, 0, 2) == "//" ) {
			$file_url = $this->file_url;
			$root = '';//JURI::root(true).'/';;
		} else if($absUrl){
			$root = JURI::root(false);
		} else {
			$root = JURI::root(true).'/';
		}

		if(!isset(VmConfig::$lazyLoad)){
			if(VmConfig::get('lazyLoad',false)){
				VmConfig::$lazyLoad = true;//'loading="lazy"';
			} else {
				VmConfig::$lazyLoad = false;
			}
		}

		$imageArgs = $this->imageArgsToArray($imageArgs);

		if(VmConfig::$lazyLoad) {
			$imageArgs['loading'] = 'lazy';
		}

		if(!isset($imageArgs['src'])) {
			$imageArgs['src'] = $root.$file_url;
		} else if($imageArgs['src']) {
			$imageArgs[$imageArgs['src']] = $root.$file_url;
			unset($imageArgs['src']);
			unset($imageArgs['loading']);
		}

		if(empty($imageArgs['alt']) and !empty($file_alt)){
			$imageArgs['alt'] = $file_alt;
		}

		if ($this->setRealImageSize and (empty($imageArgs['width']) or empty($imageArgs['height'])) and $this->isImage($this->file_url)) { // Spiros
			//vmStartTimer('thumbs2');
			if (file_exists($this->file_url) and !is_dir($this->file_url) ){
				$sizeAttr = getimagesize($this->file_url);
				if(isset($sizeAttr[0])){
					$imageArgs['width'] = $sizeAttr[0];
				}
				if(isset($sizeAttr[1])){
					$imageArgs['height'] = $sizeAttr[1];
				}
			}
			//vmTime('Thumping2','thumbs2');
		}

		$args = '';
		if(!empty($imageArgs)){
			foreach($imageArgs as $k=>$v){
				if(!empty($k) and !empty($v)){

					$args .= ' '.$k.'="'.$v.'" ';
				}
				else if($k===0){
					$args .= ' '.$v.' ';
				}
			}
		}

		$image = '<img ' . $args . ' />';

		if($lightbox){

			if ($file_alt ) $file_alt = 'title="'.$file_alt.'"';
			if ($this->file_url and pathinfo($this->file_url, PATHINFO_EXTENSION) and (substr( $this->file_url, 0, 4) != "http" or substr( $this->file_url, 0, 2) == "//")) {
				if($this->file_is_forSale ){
					$href = $this->file_url ;
				} else {
					$href = $this->file_url ;
				}

			} else {
				$href = $root.$file_url ;
			}

			/*if ($this->file_is_downloadable) {
				$lightboxImage = '<a '.$file_alt.' '.$effect.' href="'.$href.'">'.$image.$desc.'</a>';
			} else {*/
				$lightboxImage = '<a '.$file_alt.' '.$effect.' href="'.$href.'">'.$image.'</a>';
				$lightboxImage = $lightboxImage.$desc;
			//}

			return $lightboxImage;
		} else {

			return $image . $desc;
		}
	}

	/**
	 * override administrator/components/com_virtuemart/helpers/vmmedia.php
	 */
	function isImage($file_url){

		$file_extension = strtolower(JFile::getExt($file_url));

		if($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif' || $file_extension == 'webp'){
			$isImage = TRUE;

		} else {
			$isImage = FALSE;
			vmdebug('isImage is no image '.$file_url);
		}

		return $isImage;
	}
}