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

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use YesWiki\Alternativeupdatej9rem\Service\UserManager;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Entity\User;
use YesWiki\Core\Exception\BadFormatPasswordException;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

class __LostPasswordAction extends YesWikiAction
{
    public const KEY_VOCABULARY = 'http://outils-reseaux.org/_vocabulary/key';

    protected $authController;
    protected $errorType;
    protected $message;
    protected $securityController;
    protected $tripleStore;
    protected $typeOfRendering;
    protected $userManager;

    public function run()
    {
        // get services
        $this->authController = $this->getService(AuthController::class);
        $this->securityController = $this->getService(SecurityController::class);
        $this->tripleStore = $this->getService(TripleStore::class);
        $this->userManager = $this->getService(UserManager::class);

        // init properties
        $this->errorType = null;
        $this->message = '';
        $this->typeOfRendering = 'emailForm';
        $hash = null;
        $user = null;

        if (isset($_POST['subStep']) && !isset($_GET['a'])) {

            $user = $this->manageSubStep(filter_input(INPUT_POST, 'subStep', FILTER_SANITIZE_NUMBER_INT));

            // remove $_POST to deactivate main LostPasswordAction

            unset($_POST['subStep']);
        } elseif (isset($_GET['a']) && $_GET['a'] === 'recover' && !empty($_GET['email'])) {
            $this->typeOfRendering = 'directDangerMessage';
            $this->message = _t('LOGIN_INVALID_KEY');
            $hash = filter_input(INPUT_GET, 'email', FILTER_UNSAFE_RAW);
            $hash = in_array($hash, [false,null], true) ? "" : htmlspecialchars(strip_tags($hash));
            $encodedUser = filter_input(INPUT_GET, 'u', FILTER_UNSAFE_RAW);
            $encodedUser = in_array($encodedUser, [false,null], true) ? "" : htmlspecialchars(strip_tags($encodedUser));
            if (empty($hash)) {
                $this->errorType = 'invalidKey';
            } elseif ($this->userManager->checkEmailKey($hash, base64_decode($encodedUser))) {
                $user = $this->userManager->getOneByName(base64_decode($encodedUser));
                if (empty($user)) {
                    $this->errorType = 'userNotFound';
                    $this->message = _t('LOGIN_UNKNOWN_USER');
                } else {
                    $this->typeOfRendering = 'recoverForm';
                }
            } else {
                $this->errorType = 'invalidKey';
            }
            unset($_GET['a']) ;
            unset($_GET['email']) ;
        }

        return $this->displayMessage(
            $this->typeOfRendering,
            $this->errorType,
            $user,
            $hash
        );
    }

    /**
     * display needed message
     * @param string|null $typeOfRendering
     * @param string|null $errorType
     * @param User|null $user
     * @param string|null $hash
     * @return string message to display
     */
    protected function displayMessage(
        ?string $typeOfRendering,
        ?string $errorType,
        ?User $user,
        ?string $hash
    ): string {
        if (is_string($typeOfRendering)) {
            $renderedTitle = '<h2>' . _t('LOGIN_CHANGE_PASSWORD') . '</h2>';
            switch ($typeOfRendering) {
                case 'userNotFound':
                    return $renderedTitle . $this->render("@templates/alert-message-with-back.twig", [
                        'type' => 'danger',
                        'message' => _t('LOGIN_UNKNOWN_USER')
                    ]);
                case 'successPage':
                    return $renderedTitle . $this->render("@templates/alert-message.twig", [
                        'type' => 'success',
                        'message' => _t('LOGIN_MESSAGE_SENT')
                    ]);
                case 'directDangerMessage':
                    return $renderedTitle . $this->render("@templates/alert-message.twig", [
                        'type' => 'danger',
                        'message' => $this->message
                    ]);
                case 'recoverForm':
                    $key = filter_input(INPUT_POST, 'key', FILTER_UNSAFE_RAW);
                    $key = in_array($key, [false,null], true) ? "" : htmlspecialchars(strip_tags($key));
                    return $this->render("@login/lost-password-recover-form.twig", [
                        'errorType' => $this->errorType,
                        'user' => $user,
                        'message' => $this->message ?? "",
                        'key' => !empty($hash) ? $hash : $key,
                        'inIframe' => (testUrlInIframe() == 'iframe')
                    ]);
                case 'recoverSuccess':
                    return $renderedTitle . $this->render("@templates/alert-message.twig", [
                        'type' => 'success',
                        'message' => _t('LOGIN_PASSWORD_WAS_RESET')
                    ]);

                case 'emailForm':
                default:
                    return $this->render("tools/login/templates/lost-password-email-form.twig", [
                        'errorType' => $errorType,
                    ]);
            }
        }
        return '';
    }


    /**
     * manage subStep
     * imported from /tools/login/actions/LostPasswordAction.php::manageSubStep
     *
     * @param int $subStep
     * @throws Exception
     * @return null|User $user
     */
    private function manageSubStep(int $subStep): ?User
    {
        switch ($subStep) {
            case 1:
                // we just submitted an email or username for verification
                $email = filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW);
                $email = in_array($email, [false,null], true) ? "" : htmlspecialchars(strip_tags($email));
                if (empty($email)) {
                    $this->errorType = 'emptyEmail';
                    $this->typeOfRendering = 'emailForm';
                } else {
                    $user = $this->userManager->getOneByEmail($email);
                    if (!empty($user)) {
                        $this->typeOfRendering = 'successPage';
                        $this->userManager->sendPasswordRecoveryEmail($user, _t('LOGIN_CHANGE_PASSWORD'));
                    } else {
                        $this->errorType = 'userNotFound';
                        $this->typeOfRendering = 'userNotFound';
                    }
                }

                break;
            case 2:
                // we are submitting a new password (only for encrypted)
                if (empty($_POST['userID']) || empty($_POST['key'])) {
                    $this->wiki->Redirect($this->wiki->Href("", $this->params->get('root_page')));
                }
                $userName = filter_input(INPUT_POST, 'userID', FILTER_UNSAFE_RAW);
                $userName = in_array($userName, [false,null], true) ? "" : htmlspecialchars(strip_tags($userName));
                $user = $this->userManager->getOneByName($userName);
                $this->typeOfRendering = 'recoverForm';
                if (empty($_POST['pw0']) || empty($_POST['pw1']) || (strcmp($_POST['pw0'], $_POST['pw1']) != 0) || (trim($_POST['pw0']) == '')) {
                    // No pw0 or different pwd
                    $this->errorType = 'differentPasswords';
                } else {
                    if (!empty($user)) {
                        try {
                            $key = filter_input(INPUT_POST, 'key', FILTER_UNSAFE_RAW);
                            $key = in_array($key, [false,null], true) ? "" : htmlspecialchars(strip_tags($key));
                            $pw0 = filter_input(INPUT_POST, 'pw0', FILTER_UNSAFE_RAW);
                            $pw0 = in_array($pw0, [false,null], true) ? "" : $pw0;
                            $this->resetPassword(
                                $user['name'],
                                $key,
                                $pw0
                            );
                        } catch (BadFormatPasswordException $ex) {
                            $this->errorType = $ex->getMessage();
                            return $user;
                        }
                        $this->typeOfRendering = 'recoverSuccess';
                        // get $user a new time to have the new password
                        $user = $this->userManager->getOneByName($userName);
                        $this->authController->login($user);
                    } else { // Not able to load the user from DB
                        $this->errorType = 'userNotFound';
                    }
                }
                break;
        }

        return $user ?? null;
    }

    /** Part of the Password recovery process: sets the password to a new value if given the the proper recovery key (sent in a recovery email).
     * imported from /tools/login/actions/LostPasswordAction.php::resetPassword
     *
     * In order to update h·er·is password, the user provides a key (sent using sendPasswordRecoveryEmail())
     * The new password is accepted only if the key matches with the value in triples table.
     * The corresponding row is the removed from triples table.
     * See Password recovery process above
     *
     * @param string $userName The user login
     * @param string $key The password recovery key (sent by email)
     * @param string $pwd the new password value
     *
     * @return boolean True if OK or false if any problems
    */
    private function resetPassword(string $userName, string $key, string $password)
    {
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        }
        if ($this->userManager->checkEmailKey($key, $userName) === false) { // The password recovery key does not match
            throw new Exception(_t('USER_INCORRECT_PASSWORD_KEY') . '.');
        }

        $user = $this->userManager->getOneByName($userName);
        if (empty($user)) {
            $this->error = false;
            $this->typeOfRendering = 'userNotFound';
            return null;
        }
        $this->authController->setPassword($user, $password);
        // Was able to update password => Remove the key from triples table
        $this->tripleStore->delete($user['name'], self::KEY_VOCABULARY, null, '', '');
        return true;
    }
}
