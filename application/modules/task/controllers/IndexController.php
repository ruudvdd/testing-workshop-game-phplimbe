<?php

class Task_IndexController extends Zend_Controller_Action
{
    protected $_session;

    public function init()
    {
        $this->_session = new Zend_Session_Namespace('task');
    }

    public function indexAction()
    {
        return $this->_helper->redirector('list', 'index', 'task');
    }

    public function listAction()
    {
        $projectId = $this->getRequest()->getParam('projectId', null);

        $auth = Zend_Auth::getInstance();
        if (! $auth->hasIdentity()) {
            return $this->_helper->redirector('login', 'index', 'account');
        }

        $identity = Zend_Auth::getInstance()->getIdentity();
        $account = unserialize($identity);

        $taskCollection = new Task_Model_TaskCollection();
        $taskMapper = new Task_Model_TaskMapper();
        $project = new Project_Model_Project();
        $projectMapper = new Project_Model_ProjectMapper();
        $projectMapper->find($project, $projectId);

        $taskMapper->fetchAll($taskCollection, 'Task_Model_TaskDecorator', [
            'projectId = ?' => $projectId,
            'accountId = ?' => $account->getId(),
        ]);

        $this->view->assign([
            'taskCollection' => $taskCollection,
            'projectId' => $projectId,
            'projectName' => $project->getProjectName(),
        ]);
    }

    public function editAction()
    {
        $projectId = $this->getRequest()->getParam('projectId', null);
        $taskId = $this->getRequest()->getParam('taskId', null);
        if (null === $projectId) {
            return $this->_helper->redirector('list', 'index', 'project');
        }
        $form = new Task_Form_Task([
            'method' => 'post',
            'action' => $this->view->url([
                'module' => 'task',
                'controller' => 'index',
                'action' => 'save',
            ])
        ]);
        $form->getElement('projectId')->setValue($projectId);
        $form->getElement('taskId')->setValue(0);

        if (isset($this->_session->task)) {
            $form = unserialize($this->_session->task);
            unset($this->_session->task);
        }
        if (0 < (int) $taskId) {
            $identity = Zend_Auth::getInstance()->getIdentity();
            $account = unserialize($identity);
            $task = new Task_Model_Task();
            $taskMapper = new Task_Model_TaskMapper();
            $taskMapper->fetchRow($task, [
                'taskId = ?' => $taskId,
                'projectId = ?' => $projectId,
                'accountId = ?' => $account->getId(),
            ]);
            $form->populate($task->toArray());
        }
        $this->view->taskForm = $form;
    }

    public function saveAction()
    {
        if (! $this->getRequest()->isPost()) {
            return $this->_helper->redirector('list', 'index', 'project');
        }

        $form = new Task_Form_Task([
            'method' => 'post',
            'action' => $this->view->url([
                'module' => 'task',
                'controller' => 'index',
                'action' => 'save',
            ])
        ]);

        if (! $form->isValid($this->getRequest()->getPost())) {
            $this->_session->task = serialize($form);
            return $this->_helper->redirector('edit', 'index', 'task', ['projectId' => $this->getRequest()->getParam('projectId')]);
        }
        $identity = Zend_Auth::getInstance()->getIdentity();
        $account = unserialize($identity);

        $task = new Task_Model_Task($form->getValues());
        $task->setAccountId($account->getId());

        $taskMapper = new Task_Model_TaskMapper();
        $taskMapper->save($task);
        return $this->_helper->redirector('list', 'index', 'task', ['projectId' => $task->getProjectId()]);
    }

    public function removeAction()
    {
        // action body
    }
}
