<?php
namespace beatrix\middlewares;

use Slim\Http\Request as Req;
use Slim\Middleware;
use Slim\Slim;

/**
 * Automatically redirects after non-ajax method call, if it is not GET
 */
class AfterApiMethod extends Middleware
{
	public function call() {
		$this->handleApiFinished();
		$this->app->hook('slim.after.router', array($this, 'handleApiFinished'));
		$this->next->call();
	}

	public function handleApiFinished() {
		$self = $this;
		$request = $self->app->request;
		if ($self->app->container->get('isApiMethod', false) && !$request->isAjax()) {
			$self->app->redirect($request->getReferer());
		}
	}
}
 