<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Entity\User;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\PasswordHasherFactory;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\Service\UserManager as CoreUserManager;
use YesWiki\Security\Controller\SecurityController;
use YesWiki\Wiki;

if (!function_exists('send_mail')) {
    require_once('includes/email.inc.php');
}

class UserManager extends CoreUserManager
{
    public const KEY_VOCABULARY = 'http://outils-reseaux.org/_vocabulary/key';

    protected $tripleStore;
    protected $userlink;

    public function __construct(
        Wiki $wiki,
        DbService $dbService,
        ParameterBagInterface $params,
        PasswordHasherFactory $passwordHasherFactory,
        SecurityController $securityController,
        TripleStore $tripleStore
    ) {
        $this->tripleStore = $tripleStore;
        $this->userlink  = '';
        parent::__construct($wiki, $dbService, $params, $passwordHasherFactory, $securityController);
    }

    /*
     * Password recovery process (AKA reset password)
     * 1. A key is generated using name, email alongside with other stuff.
     * 2. The triple (user's name, specific key "vocabulary",key) is stored in triples table.
     * 3. In order to update h·er·is password, the user must provided that key.
     * 4. The new password is accepted only if the key matches with the value in triples table.
     * 5. The corresponding row is removed from triples table.
     */

    protected function generateUserLink($user)
    {
        if (!($user instanceof User)) {
            throw new Exception('$user should be instance of User !');
        }
        // Generate the password recovery key
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
        $plainKey = $user['name'] . '_' . $user['email'] . random_bytes(16) . date('Y-m-d H:i:s');
        $hashedKey = $passwordHasher->hash($plainKey);
        // Erase the previous triples in the trible table
        $this->tripleStore->delete($user['name'], self::KEY_VOCABULARY, null, '', '');
        // Store the (name, vocabulary, key) triple in triples table
        $this->tripleStore->create($user['name'], self::KEY_VOCABULARY, $hashedKey, '', '');

        // Generate the recovery email
        $this->saveLink($hashedKey, $user['name']);
    }

    /**
     * save userLink
     * @param string $hashedKey
     * @param string $userName
     */
    protected function saveLink(string $hashedKey, string $userName)
    {
        $this->userlink = $this->wiki->Href('', 'MotDePassePerdu', [
            'a' => 'recover',
            'email' => $hashedKey,
            'u' => base64_encode($userName),
        ], false);

    }

    /**
     * Part of the Password recovery process: Handles the password recovery email process.
     *
     * Generates the password recovery key
     * Stores the (name, vocabulary, key) triple in triples table
     * Generates the recovery email
     * Sends it
     *
     * @return bool True if OK or false if any problems
     */
    public function sendPasswordRecoveryEmail(User $user, string $title): bool
    {
        $this->generateUserLink($user);
        $pieces = parse_url($this->params->get('base_url'));
        $domain = isset($pieces['host']) ? $pieces['host'] : '';

        $message = _t('LOGIN_DEAR') . ' ' . $user['name'] . ",\n";
        $message .= _t('LOGIN_CLICK_FOLLOWING_LINK') . ' :' . "\n";
        $message .= '-----------------------' . "\n";
        $message .= $this->userlink . "\n";
        $message .= '-----------------------' . "\n";
        $message .= _t('LOGIN_THE_TEAM') . ' ' . $domain . "\n";

        $subject = $title . ' ' . $domain;
        // Send the email
        return send_mail($this->params->get('BAZ_ADRESSE_MAIL_ADMIN'), $this->params->get('BAZ_ADRESSE_MAIL_ADMIN'), $user['email'], $subject, $message);
    }

    /**
     * Assessor for userlink field.
     */
    public function getUserLink(): string
    {
        return $this->userlink;
    }

    /**
     * Assessor for userlink field.
     */
    public function getLastUserLink(User $user): string
    {
        // regenerate because not possible to know the hashedKey
        $this->generateUserLink($user);

        return $this->userlink;
    }


    /** Part of the Password recovery process: Checks the provided key against the value stored for the provided user in triples table
     *
     * @param string $hashedKey The key to check
     * @param string $userName The user for whom we check the key
     *
     * @return boolean True if success and false otherwise.
    */
    public function checkEmailKey(string $hashedKey, string $userName): bool
    {
        $triples = $this->tripleStore->getAll($userName, self::KEY_VOCABULARY, '', '');
        if (count($triples) > 1) {
            // delete triples because it is an error
            $this->tripleStore->delete($userName, self::KEY_VOCABULARY, null, '', '');
        } elseif (count($triples) === 1) {
            $storedHashedKey = $triples[0]['value'] ?? '';
            if (empty($storedHashedKey)) {
                // delete triples because it is an error
                $this->tripleStore->delete($userName, self::KEY_VOCABULARY, null, '', '');
                return false;
            }
            return strval($storedHashedKey) == $hashedKey;
        }
        return false;
    }
}
