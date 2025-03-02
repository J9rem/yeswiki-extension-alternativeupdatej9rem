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

use YesWiki\Alternativeupdatej9rem\Service\UserManager;
use YesWiki\Core\YesWikiAction;

class __LostPasswordAction extends YesWikiAction
{
    protected $errorType;
    protected $typeOfRendering;
    protected $userManager;

    public function run()
    {
        if (isset($_POST['subStep']) && !isset($_GET['a'])) {

            // get services
            $this->userManager = $this->getService(UserManager::class);

            // init properties
            $this->errorType = null;
            $this->typeOfRendering = 'emailForm';


            $this->manageSubStep(filter_input(INPUT_POST, 'subStep', FILTER_SANITIZE_NUMBER_INT));

            // remove $_POST to deactivate main LostPasswordAction

            unset($_POST['subStep']);

            return $this->displayMessage(
                $this->typeOfRendering,
                $this->errorType
            );
        } elseif (
            !(
                isset($_GET['a'])
                && $_GET['a'] === 'recover'
                && !empty($_GET['email'])
            )) {
            // force usage of core template only in this case (because not displayed in main action)
            return $this->displayMessage(
                'emailForm',
                null
            );
        }
    }

    /**
     * display needed message
     * @param string|null $typeOfRendering
     * @param string|null $errorType
     * @return string message to display
     */
    protected function displayMessage(
        ?string $typeOfRendering,
        ?string $errorType
    ): string {
        if (is_string($typeOfRendering)) {
            $renderedTitle = '<h2>' . _t('LOGIN_CHANGE_PASSWORD') . '</h2>';
            switch ($typeOfRendering) {
                case 'emailForm':
                    return $this->render("tools/login/templates/lost-password-email-form.twig", [
                        'errorType' => $errorType,
                    ]);
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

                default:
                    return '';
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
                        $this->userManager->sendPasswordRecoveryEmail($user);
                    } else {
                        $this->errorType = 'userNotFound';
                        $this->typeOfRendering = 'userNotFound';
                    }
                }
                // remove $_POST to deactivate main LostPasswordAction

                unset($_POST['subStep']);

                break;
        }
        return $user ?? null;
    }
}
