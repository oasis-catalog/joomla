<?php

namespace Joomla\Plugin\System\Oasis\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Plugin\System\Oasis\Extension\Image as OasisImage;
use Joomla\Plugin\System\Oasis\Extension\Oasis;
use Oasiscatalog\Component\Oasis\Administrator\Helper\Config as OasisConfig;

class Oasis extends CMSPlugin
{
    public function onBeforeDisplay(&$a, $b)
    {
        $cf = OasisConfig::instance([
            'init' => true
        ]);

        if($cf->is_cdn_photo) {
            if($b == 'com_virtuemart.productdetails'){
                foreach($a->product->images as $k => $img){
                    if($img instanceof \VmImage){
                        $a->product->images[$k] = new OasisImage($img);
                    }
                }
            }

            // for category
            if($b == 'com_virtuemart.category'){
                foreach($a->products as $t => $ob){
                    if(isset($ob->images)){ // single
                        foreach($ob->images as $k => $img){
                            if($img instanceof \VmImage){
                                $a->products[$t]->images[$k] = new OasisImage($img);
                            }
                        }
                    }
                    else{
                        foreach($ob as $p => $product){
                            foreach($product->images as $k => $img){
                                if($img instanceof \VmImage){
                                    $a->products[$t][$p]->images[$k] = new OasisImage($img);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}