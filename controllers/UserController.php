<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-feat-user-controller-delete-own-pages
 */

namespace YesWiki\Alternativeupdatej9rem\Controller;

use Exception;
use YesWiki\Bazar\Controller\EntryController;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Controller\UserController as CoreUserController;
use YesWiki\Core\Entity\User;
use YesWiki\Core\Exception\DeleteUserException;
use YesWiki\Core\Exception\UserEmailAlreadyUsedException;

class UserController extends CoreUserController
{
    /**
     * delete a user but check if possible before
     * @param User $user
     * @throws DeleteUserException
     * @throws Exception
     */
    public function delete(User $user)
    {
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        }
        if (!$this->wiki->UserIsAdmin()) {
            throw new DeleteUserException(_t('USER_MUST_BE_ADMIN_TO_DELETE').'.');
        }
        if ($this->isRunner($user)) {
            throw new DeleteUserException(_t('USER_CANT_DELETE_ONESELF').'.');
        }
        $this->checkIfUserIsNotAloneInEachGroup($user);
        $this->deleteUserFromEveryGroup($user);
        $this->removeOwnershipOrDelete($user);
        $this->userManager->delete($user);
    }
    
    /**
     * check if current user is the user to delete
     * @param User $user
     * @return bool
     */
    private function isRunner(User $user): bool
    {
        $loggedUser = $this->authController->getLoggedUser();
        return (!empty($loggedUser) && ($loggedUser['name'] == $user['name']));
    }

    /**
     * check if user is not alone in each group
     * @param User $user
     * @throws DeleteUserException
     */
    private function checkIfUserIsNotAloneInEachGroup(User $user)
    {
        $grouptab = $this->userManager->groupsWhereIsMember($user, false);
        foreach ($grouptab as $group) {
            $groupmembers = $this->wiki->GetGroupACL($group);
            $groupmembers = str_replace(["\r\n","\r"], "\n", $groupmembers);
            $groupmembers = explode("\n", $groupmembers);
            $groupmembers = array_unique(array_filter(array_map('trim', $groupmembers)));
            if (count($groupmembers) == 1) { // Only one user in (this user then)
                throw new DeleteUserException(_t('USER_DELETE_LONE_MEMBER_OF_GROUP')." ($group).");
            }
        }
    }

    /**
     * remove user from every group
     * @param User $user
     * @throws DeleteUserException
     */
    private function deleteUserFromEveryGroup(User $user)
    {
        // Delete user in every group
        $searchedValue = $this->dbService->escape($user['name']);
        $groups = $this->tripleStore->getMatching(
            GROUP_PREFIX."%",
            "http://www.wikini.net/_vocabulary/acls",
            "%$searchedValue%",
            "LIKE",
            "=",
            "LIKE"
        );
        $error = false;
        if (is_array($groups)) {
            $pregQuoteSearchValue = preg_quote($searchedValue, '/');
            foreach ($groups as $group) {
                $newValue = $group['value'];
                $newValue = preg_replace("/(?<=^|\\n|\\r)$pregQuoteSearchValue(?:\\r\\n|\\n|\\r|$)/", "", $newValue);
                if ($newValue != $group['value'] &&
                    !in_array($this->tripleStore->update(
                        $group['resource'],
                        $group['property'],
                        $group['value'],
                        $newValue,
                        '',
                        ''
                    ), [0,3])) {
                    $error = true;
                }
            }
        }
        if ($error) {
            throw new DeleteUserException(_t('USER_DELETE_QUERY_FAILED').'.');
        }
    }

    /**
     * remove user from every group
     * @param User $user
     * @throws Exception
     */
    private function removeOwnershipOrDelete(User $user)
    {
        $pagesWhereOwner = $this->dbService->loadAll("
            SELECT `tag` FROM {$this->dbService->prefixTable('pages')} 
            WHERE `owner` = \"{$this->dbService->escape($user['name'])}\"
            AND `latest` = \"Y\" ;
        ");
        $pagesWhereOwner = array_map(function ($page) {
            return $page['tag'];
        }, $pagesWhereOwner);

        $firstAdmin = $this->getFirstAdmin();

        if ($this->params->get('deletePagesAndEntriesWithUser') === true) {
            // imported from tools/templates/actions/GerereDroitsAction.php::getFilterAndSearch
            $specialsPagesNames = [
                'BazaR',
                'GererSite',
                'GererDroits',
                'GererThemes',
                'GererMisesAJour',
                'GererUtilisateurs',
                'GererDroitsActions',
                'GererDroitsHandlers',
                'TableauDeBord',
                'PageTitre',
                'PageMenuHaut',
                'PageRapideHaut',
                'PageHeader',
                'PageFooter',
                'PageCSS',
                'PageMenu',
                'PageColonneDroite',
                'MotDePassePerdu',
                'ParametresUtilisateur',
                'GererConfig',
                'ActuYeswiki',
                'LookWiki'
            ];
            $specialsPagesNames[] = $this->params->get('root_page');
            $pagesToDelete = array_filter(
                $pagesWhereOwner,
                function ($tag) use ($specialsPagesNames) {
                    return !in_array($tag, $specialsPagesNames);
                }
            );
            $entryController = $this->getService(EntryController::class);
            $entryManager = $this->getService(EntryManager::class);
            foreach ($pagesToDelete as $tag) {
                // check if not already deleted while deleting other entry
                $page = $this->pageManager->getOne($tag, null, false); // no cache
                $deleted = empty($page)
                    ? true
                    : (
                        $entryManager->isEntry($tag)
                        ? $entryController->delete($tag)
                        : $this->pageManager->deleteOrphaned($tag)
                    );
                if ($deleted) {
                    $pagesWhereOwner = array_filter(
                        $pagesWhereOwner,
                        function ($tagToFilter) use ($tag) {
                            return $tagToFilter != $tag;
                        }
                    );
                }
            }
        }
        foreach ($pagesWhereOwner as $tag) {
            $this->pageManager->setOwner($tag, $firstAdmin);
        }
    }

}
