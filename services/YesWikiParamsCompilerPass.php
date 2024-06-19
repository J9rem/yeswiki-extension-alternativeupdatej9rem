<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-dont-show-stmp-params
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class YesWikiParamsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter('contact_editable_config_params')) {
            $contactParameter = $container->getParameter('contact_editable_config_params');
            $filteredContactParameter = array_values(array_filter($contactParameter, function ($value) {
                return !in_array($value, [
                    'contact_mail_func',
                    'contact_smtp_host',
                    'contact_smtp_port',
                    'contact_smtp_user',
                    'contact_smtp_pass',
                    'contact_reply_to',
                    'contact_debug'
                ]);
            }));
            $container->setParameter('contact_editable_config_params', $filteredContactParameter);
        }
    }
}
