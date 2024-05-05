<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-perf-sql
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;
use YesWiki\Core\Service\DbService as CoreDbService;

class DbService extends CoreDbService
{
    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct($params);
        $this->setSessionTimeout();
    }

    /**
     * get wantedTimeout
     * @return int $wantedTimeout
     */
    protected function getWantedTimeout(): int
    {
        /**
         * @var mixed $wantedTimeout
         */
        $wantedTimeout = $this->params->get('sqlConnectTimeout');
        $wantedTimeout = (!empty($wantedTimeout) && is_scalar($wantedTimeout)) ? intval($wantedTimeout) : 0;
        return $wantedTimeout;
    }

    /**
     * set session timeout
     * @return void
     * @throws Exception
     */
    protected function setSessionTimeout()
    {
        /**
         * @var mixed $wantedTimeout
         */
        $wantedTimeout = $this->getWantedTimeout();
        if ($wantedTimeout > 0) {
            $this->query("SET @@SESSION.wait_timeout=$wantedTimeout;");
        }
    }

    public function query($query)
    {
        try {
            return parent::query($query);
        } catch (Throwable $th) {
            throw new Exception("error executing sql '$query'", 1, $th);
        }
    }
}
