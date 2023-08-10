<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\alternativeupdatej9rem\Field;


use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Field\CheckboxEntryField;
use YesWiki\Bazar\Field\SelectEntryField;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\Performer;
use YesWiki\Bazar\Service\EntryManager;


/**
 * @Field({"video"})
 */
class VideoField extends BazarField
{
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        
    }

    protected function renderInput($entry)
    {
        // Display the linked entries only on update
    return $this->render("@bazar/inputs/link.twig", [
            'value' => $this->getValue($entry)
        ]);
    }

    protected function renderStatic($entry)
    {
        // Display the linked entries only if id_fiche and id_typeannonce
		$value = $this->getValue($entry);
		$type = 'peertube'; // type par defaut peertube car le format de l'url ne permet pas de detecter peertube 
		$instance = 'https://videos.yeswiki.net/';

		
		$yt_pattern = '/youtu\.?be/';
		if (preg_match($yt_pattern, $value, $matches)){
			$type='youtube';
			
			$urlpart = explode("watch?v=", $value);
			if (!empty($urlpart[1])){
				$id_video = $urlpart[1]; 
			}
			else {
				$urlpart = explode("youtu.be/", $value);
				if (!empty($urlpart[1])){
					$id_video = $urlpart[1]; 
				}
			}
			
		}
		elseif (preg_match("/vimeo/", $value, $matches)){
				$type='vimeo';
				$urlpart = explode("vimeo.com/", $value);
				if (!empty($urlpart[1])){
					$id_video = $urlpart[1]; 
				}
			}
			elseif (preg_match("/dai\.?ly/", $value, $matches)){
				$type='dailymotion';
				$urlpart = explode("/video/", $value);
				if (!empty($urlpart[1])){
					$id_video = $urlpart[1]; 
				}
				else {
					$urlpart = explode("dai.ly/", $value); // url court https://dai.ly/x4oyxhd
					if (!empty($urlpart[1])){
					$id_video = $urlpart[1]; 
				}
				}
			}
			else {
					// PeerTube
					$urlpart = explode("/videos/embed/", $value);
					if (!empty($urlpart[1])){
						$id_video = $urlpart[1]; 
						$instance = $urlpart[0];
					}
					else {
						$urlpart = explode("/w/", $value);
						if (!empty($urlpart[1])){
							$id_video = $urlpart[1]; 
							$instance = $urlpart[0];
						}
						$value = str_replace("/w/","/videos/embed/",$value);
					}
				}
			
		
	
        return ($value) ? $this->render("@bazar/fields/video.twig", [
            'value' => $this->getValue($entry),
			'type'=> $type,
			'id'=> $id_video,
			'instance' => $instance
        ]) : '';
    }
	
	// Format input values before save
	public function formatValuesBeforeSave($entry)
	{
    // to prevent creation of empty keys
    return empty($this->propertyName) ? [] : [$this->propertyName => $this->getValue($entry)];
	}
}
