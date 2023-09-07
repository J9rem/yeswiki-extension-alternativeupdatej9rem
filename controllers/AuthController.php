<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Controller;

use Exception;
use YesWiki\Core\Controller\AuthController as CoreAuthController;
use YesWiki\Core\Entity\User;
use YesWiki\Core\YesWikiController;

/**
 * not needed since 4.4.1
 */

if (class_exists(CoreAuthController::class,false)){

    class AuthController extends CoreAuthController
    {
        
        public function login($user, $remember = 0)
        {
            if (!($user instanceof User)){
                if (!empty($user['name'])){
                    $user = $this->userManager->getOneByName($user['name']);
                } else {
                    throw new Exception("`\$user['name']` must not be empty when retrieving user from `\$user['name']`");
                }
            }
            parent::login($user,$remember);
        }
    }
    
} else {
    
    class AuthController extends YesWikiController
    {
    }
}
