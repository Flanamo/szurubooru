<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\UserService;

class ActivateAccount extends AbstractUserRoute
{
	private $userService;

	public function __construct(UserService $userService)
	{
		$this->userService = $userService;
	}

	public function getMethods()
	{
		return ['POST', 'PUT'];
	}

	public function getUrl()
	{
		return '/api/activation/:userNameOrEmail';
	}

	public function work()
	{
		$user = $this->userService->getByNameOrEmail($this->getArgument('userNameOrEmail'), true);
		return $this->userService->sendActivationEmail($user);
	}
}
