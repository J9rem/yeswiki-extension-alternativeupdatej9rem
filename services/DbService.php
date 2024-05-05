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
use mysqli;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;
use YesWiki\Core\Service\DbService as CoreDbService;

class DbService extends CoreDbService
{
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->queryLog = [];
        try {
            $this->link = $this->prepareMySqli();
            $this->setSqlSessionConnectTimeout();
            if (!$this->connect()) {
                throw new Exception("Not connected to sql");
            }
            $this->setCharset();
            $this->setSessionTimeout();
        } catch (Throwable $th) {
            if (in_array(php_sapi_name(), ['cli', 'cli-server',' phpdbg'], true)) {
                throw new Exception(_t('DB_CONNECT_FAIL') . " {$th->getMessage()}");
            } else {
                exit(_t('DB_CONNECT_FAIL') . " {$th->getMessage()}");
            }
        }
    }

    /**
     * prepare sqli like in CoreDbService
     * @return mysqli
     * @throws Exception
     */
    protected function prepareMySqli(): mysqli
    {
        /**
         * @var mysqli $link
         */
        $link = mysqli_init();

        if (!$link) {
            throw new Exception("SQL not initiated !");
        }
        return $link;
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
     * set sql session's connect  timeout if required
     * @return void
     */
    protected function setSqlSessionConnectTimeout()
    {
        /**
         * @var mixed $wantedTimeout
         */
        $wantedTimeout = $this->getWantedTimeout();
        if ($wantedTimeout > 0) {
            mysqli_options($this->link, MYSQLI_OPT_CONNECT_TIMEOUT, $wantedTimeout);
            mysqli_options($this->link, MYSQLI_OPT_READ_TIMEOUT, $wantedTimeout);
        }
    }

    /**
     * protected connect
     * @return bool true of ok
     */
    protected function connect(): bool
    {
        return mysqli_real_connect(
            $this->link,
            $this->params->get('mysql_host'),
            $this->params->get('mysql_user'),
            $this->params->get('mysql_password'),
            $this->params->get('mysql_database'),
            $this->params->has('mysql_port') ? $this->params->get('mysql_port') : ini_get("mysqli.default_port")
        );
    }

    /**
     * set charset
     * @return void
     */
    protected function setCharset()
    {
        if ($this->params->has('db_charset') and $this->params->get('db_charset') === 'utf8mb4') {
            // necessaire pour les versions de mysql qui ont un autre encodage par defaut
            mysqli_set_charset($this->link, 'utf8mb4');

            // dans certains cas (ovh), set_charset ne passe pas, il faut faire une requete sql
            $charset = mysqli_character_set_name($this->link);
            if ($charset != 'utf8mb4') {
                mysqli_query($this->link, 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
        }
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
