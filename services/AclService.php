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

use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService as CoreAclService;

class AclService extends CoreAclService
{
    /**
     * @var int $checkToBeReplaced save state if check shloud be replace (-1 or 1)
     */
    protected $checkToBeReplaced;
    /**
     * Checks if some $user satisfies the given $acl
     *
     * @param string $acl
     *            The acl to check, in the same format than for pages ACL's
     * @param string $user
     *            The name of the user that must satisfy the ACL. By default
     *            the current remote user.
     * @param bool $adminCheck
     *            Check if user is in admins groups
     *            Default true
     * @param string $tag
     *            The name of the page or form to be tested when $acl contains '%'.
     *            By Default ''
     * @param string $mode
     *            Mode for cases when $acl contains '%'
     *            Default '', standard case. $mode = 'creation', the test returns true
     *            even if the user is connected
     * @param array $formerGroups
     * 		 to avoid loops we keep track of former calls
     * @return bool True if the $user satisfies the $acl, false otherwise
     */
    public function check($acl, $user = null, $adminCheck = true, $tag = '', $mode = '', $formerGroups = [])
    {
        if ($this->checkShouldBeReplaced() == 1) {
            return $this->correctedCheck(
                $acl,
                $user,
                $adminCheck,
                $tag,
                $mode,
                $formerGroups
            );
        } else {
            return parent::check(
                $acl,
                $user,
                $adminCheck,
                $tag,
                $mode,
                $formerGroups
            );
        }
    }

    /**
     * Checks if some $user satisfies the given $acl
     *
     * @param string $acl
     *            The acl to check, in the same format than for pages ACL's
     * @param string $user
     *            The name of the user that must satisfy the ACL. By default
     *            the current remote user.
     * @param bool $adminCheck
     *            Check if user is in admins groups
     *            Default true
     * @param string $tag
     *            The name of the page or form to be tested when $acl contains '%'.
     *            By Default ''
     * @param string $mode
     *            Mode for cases when $acl contains '%'
     *            Default '', standard case. $mode = 'creation', the test returns true
     *            even if the user is connected
     * @param array $formerGroups
     * 		 to avoid loops we keep track of former calls
     * @return bool True if the $user satisfies the $acl, false otherwise
     */
    protected function correctedCheck($acl, $user = null, $adminCheck = true, $tag = '', $mode = '', $formerGroups = [])
    {
        if (!$user) {
            $user = $this->authController->getLoggedUser();
            $username = !empty($user['name']) ? $user['name'] : null;
        } else {
            $username = $user;
        }

        if ($adminCheck && !empty($username) && $this->wiki->UserIsAdmin($username)) {
            return true;
        }

        $acl = is_string($acl) ? trim($acl) : '';
        $result = false ; // result by default , this function is like a big "OR LOGICAL"

        $acl = str_replace(["\r\n","\r"], "\n", $acl);
        foreach (explode("\n", $acl) as $line) {
            $line = trim($line);

            // check for inversion character "!"
            if (preg_match('/^[!](.*)$/', $line, $matches)) {
                $std_response = false ;
                $line = $matches[1];
            } else {
                $std_response = true;
            }

            // if there's still anything left... lines with just a "!" don't count!
            if ($line) {
                switch ($line[0]) {
                    case '#': // comments
                        break;
                    case '*': // everyone
                        $result = $std_response;
                        break;
                    case '+': // registered users
                        $result = (!empty($username) && $this->userManager->getOneByName($username)) ? $std_response : !$std_response ;
                        break;
                    case '%': // owner
                        if ($mode == 'creation') {
                            // in creation mode, even if there is a tag
                            // the current user can access to field
                            $result = $std_response ;
                        } elseif ($tag == '') {
                            // to manage retrocompatibility without usage of CheckACL without $tag
                            // and no management of '%'
                            $result = false;
                        } else {
                            $result = ($this->wiki->UserIsOwner($tag)) ? $std_response : !$std_response ;
                        }
                        break;
                    case '@': // groups
                        $gname = substr($line, 1);
                        // paranoiac: avoid line = '@'
                        if ($gname) {
                            if (in_array($gname, $formerGroups)) {
                                $this->wiki->setMessage('Error group ' . $gname . ' inside same groups, inception was a bad movie');
                                $result = false;
                            } else {
                                if (!empty($username)
                                && $this->userManager->isInGroup(
                                    $gname,
                                    $username,
                                    false/* we have allready checked if user was an admin */,
                                    array_merge($formerGroups, [$gname]) // does not change $formerGroups param
                                )
                                ) {
                                    $result = $std_response ;
                                } else {
                                    $result = ! $std_response ;
                                }
                            }
                        } else {
                            $result = false ; // line '@'
                        }
                        break;
                    default: // simple user entry
                        if (!empty($username) && $line == $username) {
                            $result = $std_response ;
                        } else {
                            $result = ! $std_response ;
                        }
                }
                if ($result) {
                    return true ;
                } // else continue like a big logical OR
            }
        }

        // tough luck.
        return false;
    }

    /**
     * lazy loading of result of test if check should be replaced
     * @return int 1 if OK, -1 if not
     */
    protected function checkShouldBeReplaced(): int
    {
        if (empty($this->checkToBeReplaced)) {
            $this->checkToBeReplaced = (
                $this->wiki->services->get(RevisionChecker::class)->isWantedRevision('doryphore', 4, 4, 3)
                || $this->wiki->services->get(RevisionChecker::class)->isWantedRevision('doryphore', 4, 4, 4)
            )
            ? 1
            : -1;
        }
        return $this->checkToBeReplaced;
    }
}
