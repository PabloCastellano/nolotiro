<?php

class ApiController extends Zend_Controller_Action {

    public function init() {
        $this->lang = $this->view->lang = $this->_helper->checklang->check();
    }

    public function showgiveAction() {
        $id = $this->_request->getParam('id');
        $model = new Model_Ad();
        $this->view->ad = $model->getAd((int)$id);

        if ($this->view->ad == null) {
            $this->_helper->json(array("id" => $id,
                                       "error_message" => "id not found"));
        } else {
            $this->_helper->json($this->view->ad);
        }
    }
}

?>

