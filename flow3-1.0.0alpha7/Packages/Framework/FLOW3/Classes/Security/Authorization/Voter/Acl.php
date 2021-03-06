<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Voter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An access decision voter, that asks the FLOW3 ACLService for a decision.
 *
 * @version $Id: Acl.php 3806 2010-02-02 12:12:39Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Acl implements \F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface {

	/**
	 * The policy service
	 * @var \F3\FLOW3\Security\ACL\PolicyService
	 */
	protected $policyService;

	/**
	 * Constructor.
	 *
	 * @param F3\FLOW3\Security\ACL\PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Security\ACL\PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * This is the default Acl voter, it votes for the ACCESS privilege for the given join point
	 *
	 * @param F3\FLOW3\Security\Context $securityContext The current securit context
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 */
	public function voteForJoinPoint(\F3\FLOW3\Security\Context $securityContext, \F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($securityContext->getRoles() as $role) {
			$privileges = $this->policyService->getPrivilegesForJoinPoint($role, $joinPoint, 'ACCESS');
			if (!isset($privileges[0])) continue;

			if ($privileges[0]->isGrant()) $accessGrants++;
			else $accessDenies++;
		}

		if ($accessDenies > 0) return self::VOTE_DENY;
		if ($accessGrants > 0) return self::VOTE_GRANT;

		return self::VOTE_ABSTAIN;
	}

	/**
	 * This is the default Acl voter, it votes for the ACCESS privilege for the given resource
	 *
	 * @param F3\FLOW3\Security\Context $securityContext The current securit context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 */
	public function voteForResource(\F3\FLOW3\Security\Context $securityContext, $resource) {
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($securityContext->getRoles() as $role) {
			$privileges = $this->policyService->getPrivilegesForResource($role, $resource);
			if (!isset($privileges[0])) continue;

			if ($privileges[0]->isGrant()) $accessGrants++;
			else $accessDenies++;
		}

		if ($accessDenies > 0) return self::VOTE_DENY;
		if ($accessGrants > 0) return self::VOTE_GRANT;

		return self::VOTE_ABSTAIN;
	}
}

?>