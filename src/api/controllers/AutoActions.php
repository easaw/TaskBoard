<?php
use RedBeanPHP\R;

class AutoActions extends BaseController {

    public function getAllActions($request, $response, $args) {
        $status = $this->secureRoute($request, $response,
            SecurityLevel::User);
        if ($status !== 200) {
            return $this->jsonResponse($response, $status);
        }

        $actionBeans = R::findAll('auto_action');

        // TODO: Filter by boards user has access to

        if(count($actionBeans)) {
            $this->apiJson->setSuccess();

            foreach($actionBeans as $bean) {
                $action = new AutoAction($this->container);
                $action->loadFromBean($bean);

                $this->apiJson->addData($action);
            }
        } else {
            $this->logger->addInfo('No automatic actions in database.');
            $this->apiJson->addAlert('info',
                'No automatic actions in database.');
        }

        return $this->jsonResponse($response);
    }

    public function addAction($request, $response, $args) {
        $status = $this->secureRoute($request, $response,
            SecurityLevel::BoardAdmin);
        if ($status !== 200) {
            return $this->jsonResponse($response, $status);
        }

        $action = new AutoAction($this->container);
        $action->loadFromJson($request->getBody());

        $actor = new User($this->container, Auth::GetUserId($request));
        // TODO: Verify BoardAdmin has board access

        if (!$action->save()) {
            $this->logger->addError('Add Action: ', [$action]);
            $this->apiJson->addAlert('error',
                'Error adding automatic action. ' .
                'Please check your entries and try again.');

            return $this->jsonResponse($response);
        }

        $this->dbLogger->logChange($this->container, $actor->id,
            $actor->username . ' added automatic action.',
            '', json_encode($action), 'action', $action->id);

        $this->apiJson->setSuccess();
        $this->apiJson->addAlert('success', 'Automatic action added.');

        return $this->jsonResponse($response);
    }

    public function removeAction($request, $response, $args) {
        $status = $this->secureRoute($request, $response,
            SecurityLevel::BoardAdmin);
        if ($status !== 200) {
            return $this->jsonResponse($response, $status);
        }

        $id = (int)$args['id'];
        $action = new AutoAction($this->container, $id);

        $actor = new User($this->container, Auth::GetUserId($request));
        // TODO: Verify BoardAdmin has board access

        if($action->id !== $id) {
            $this->logger->addError('Remove Action: ', [$action]);
            $this->apiJson->addAlert('error', 'Error removing action. ' .
                'No action found for ID ' . $id . '.');

            return $this->jsonResponse($response);
        }

        $before = $action;
        $action->delete();

        $this->dbLogger->logChange($this->container, $actor->id,
            $actor->username .' removed action ' . $before->id . '.',
            json_encode($before), '', 'action', $id);

        $this->apiJson->setSuccess();
        $this->apiJson->addAlert('success', 'Automatic action removed.');

        return $this->jsonResponse($response);
    }
}

