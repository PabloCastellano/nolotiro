<?php

class AdController extends Zend_Controller_Action {


    public function init() {
        $this->lang = $this->view->lang = $this->_helper->checklang->check();
        $this->location = $this->_helper->checklocation->check();
        $this->check_messages = $this->_helper->checkMessages;
        $this->notifications = $this->_helper->Notifications;
        $this->security = $this->_helper->security->badparams();

        //check if user is locked
        $locked = $this->_helper->checkLockedUser->check();
        if ($locked == 1) {
            $this->_redirect('/' . $this->view->lang . '/auth/logout');
        }
    }


    public function listAction() {
        $this->view->userRole = $this->_helper->checkUserRole->check();

        $this->view->location = $this->_helper->checklocation->check();

        $woeid = $this->_request->getParam('woeid');
        $this->view->ad_type = $ad_type = $this->_request->getParam('ad_type');

        if ($ad_type == 'give') {
            $this->view->page_title .= $this->view->translate('give') . ' ' . $this->view->translate('second hand') . ' ';
            $type = 'give';
        }
        elseif ($ad_type == 'want') {
            $this->view->page_title .= $this->view->translate('want') . ' ' . $this->view->translate('second hand') . ' ';
            $type = 'want';
        }
        else {
            //dont accept other values than give/want
            $this->getResponse()->setHttpResponseCode(404);
            $this->_helper->_flashMessenger->addMessage($this->view->translate('this url does not exist'));
            $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give', array('code'=>301));
        }
        $this->view->page_title .= ' '. $this->view->translate('free') .' ';

        $this->view->status = $status = $this->_request->getParam('status');
        $f = new Zend_Filter();
        $f->addFilter(new Zend_Filter_HtmlEntities());
        $status = $f->filter($status);

        if ($status) {
            $this->view->page_title .= $this->view->translate($status) . ' ';
        } else {
            $status = 'available';
        }

        $model = new Model_Ad();
        $this->view->woeid = $woeid;
        $this->view->ad = $model->getAdList($woeid, $ad_type, $status);
        $this->view->woeidName = $this->_helper->woeid->name($woeid, $this->lang);
        $short = explode(',', $this->view->woeidName);
        $this->view->woeidNameShort = ' ' . $this->view->translate('in') . ' ' . $short[0];

        //add meta description to head
        $this->view->metaDescription = $this->view->translate($type) . ' ' . $this->view->woeidNameShort . '. ' . $this->view->translate('nolotiro.org is a website where you can give away things you no longer want or no longer need to pick them up other people who may serve or be of much use.');

        //add link rel canonical , better seo
        $this->view->canonicalUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $this->lang . '/woeid/' . $woeid . '/' . $ad_type . '/status/' . $status;

        //add the link to the proper rss to layout
        $this->view->headLink()->appendAlternate('http://' . $_SERVER['HTTP_HOST'] . '/' . $this->lang . '/rss/feed/woeid/' . $woeid . '/ad_type/' . $ad_type . '/status/' . $status,
            'application/rss+xml',
            $this->view->woeidName . ' - ' . $this->view->translate((string)$type));

        if (empty($this->view->ad)) {
            $this->view->suggestIP = $this->_helper->getLocationGeoIP->suggest();
            $this->view->similarLocations = $this->_helper->similarLocations->suggest($this->view->woeidName, $this->lang);
        }

        //TODO , this sucks, do a better way to not show invalid woeids or null
        if ((empty($woeid)) || ($woeid < 10) || ($woeid == 29370606)) { //29370606 españa town
            $this->_helper->_flashMessenger->addMessage($this->view->translate('This location is not a valid town. Please, try again.'));
            $this->_redirect('/' . $this->lang . '/location/change',array('code'=>301));
        }

        //set the location name reg var from the woeid helper
        $aNamespace = new Zend_Session_Namespace('Nolotiro');
        $aNamespace->locationName = $this->view->woeidName;
        $this->view->page_title .= $this->view->woeidName;

        //paginator
        $page = $this->_getParam('page');

        if ($page) {
            $this->view->page_title .= ' - ' . $this->view->translate('page') . ' ' . $page;
        }

        $paginator = Zend_Paginator::factory($this->view->ad);
        $paginator->setDefaultScrollingStyle('Elastic');
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);

        $this->view->paginator = $paginator;
    }


    /**
     * List all ads
     */
    public function listallAction() {
        $this->view->userRole = $this->_helper->checkUserRole->check();

        $model = new Model_Ad();

        $ad_type = $this->view->ad_type = $this->_request->getParam('ad_type');

        if ($ad_type == 'give') {
            $this->view->page_title .= $this->view->translate('give') . ' ';
            $type = 'give';
        }
        elseif ($ad_type == 'want') {
            $this->view->page_title .= $this->view->translate('want') . ' ';
            $type = 'want';
        }
        else {
            //dont accept other values than give/want
            $this->_helper->_flashMessenger->addMessage($this->view->translate('this url does not exist'));
            $this->_redirect('/' . $this->lang . '/ad/listall/ad_type/give', array('code'=>301));
        }

        $status = $this->_request->getParam('status');
        $f = new Zend_Filter();
        $f->addFilter(new Zend_Filter_HtmlEntities());
        $status = $f->filter($status);

        if ($status) {
            $this->view->page_title .= $this->view->translate($status) . ' ';
        }

        $this->view->ad = $model->getAdListAll($ad_type, $status);
        $this->view->page_title .= $this->view->translate('All the ads') . ' ' . $this->view->translate('second hand and new') ;

        //paginator
        $page = $this->_getParam('page');
        if ($page) {
            $this->view->page_title .= ' ' . $this->view->translate('page') . ' ' . $page;
        }

        //add meta description to head
        $this->view->metaDescription = $this->view->page_title . '. ' . $this->view->translate('nolotiro.org is a website where you can give away things you no longer want or no longer need to pick them up other people who may serve or be of much use.');

        $paginator = Zend_Paginator::factory($this->view->ad);
        $paginator->setDefaultScrollingStyle('Elastic');
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);

        $this->view->paginator = $paginator;
    }


    /**
     * List all ads belonging to user $id
     *
     */
    public function listuserAction() {

        $id = $this->_request->getParam('id');
        if (!$id) {
            $this->_helper->_flashMessenger->addMessage($this->view->translate('This url does not exist'));
            $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
        }

        $auth = Zend_Auth::getInstance ();
        if ($auth->hasIdentity())
            $this->view->myUserId = $auth->getIdentity()->id;

        $modelUser = new Model_User();
        $user = $modelUser->fetchUser($id);
        if (!$user) {
            $this->_helper->_flashMessenger->addMessage($this->view->translate('This user does not exist'));
            $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
        }
        $this->view->userId = $id;
        $this->view->userName = $user['username'];

        $model = new Model_Ad();
        $this->view->ad = $model->getAdUserlist($id);

        //paginator
        $page = $this->_getParam('page');

        $paginator = Zend_Paginator::factory($this->view->ad);
        $paginator->setDefaultScrollingStyle('Elastic');
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);

        $this->view->paginator = $paginator;

        $page = $this->_request->getParam('page');

        if ($page) {
            $this->view->page_title .= ' - ' . $this->view->translate('page') . ' ' . $page;
        }
    }


    public function showAction() {
        $this->view->userRole = $this->_helper->checkUserRole->check();
        $id = $this->_request->getParam('id');
        $model = new Model_Ad();

        //check if the ad exists in memcached
        $oBackend = new Zend_Cache_Backend_Memcached(
            array(
                'servers' => array(array(
                    'host' => '127.0.0.1',
                    'port' => '11211'
                )),
                'compression' => true
            ));

        // configure caching frontend strategy
        $oFrontend = new Zend_Cache_Core(
            array(
                // cache for 7 days
                'lifetime' => 3600 * 24 * 7,
                'caching' => true,
                'cache_id_prefix' => 'singleAd',
                'logging' => false,
                'write_control' => true,
                'automatic_serialization' => true,
                'ignore_user_abort' => true
            ));

        // build a caching object
        $cacheAd = Zend_Cache::factory($oFrontend, $oBackend);
        $cacheTest = $cacheAd->test((int)$id);

        if ($cacheTest == false) { //if not exists in cache lets query to db
            $this->view->ad = $model->getAd((int)$id);
            $cacheAd->save($this->view->ad, (int)$id);

        } else {
            //load ad from memcached
            $this->view->ad = $cacheAd->load((int)$id);
        }

        //lets count the comments number and update
        $modelComments = new Model_Comment();
        $this->view->checkCountAd = $count = $modelComments->countCommentsAd((int)$id);
        //let's increment +1 the ad view counter
        $model->updateReadedAd($id);
        $this->view->countReadedAd = $model->countReadedAd($id);

        if ($this->view->checkCountAd > 0) {
            $modelComments->updateCommentsAd($id, $count);
        }

        if ($this->view->ad != null) { // if the id ad exists then render the ad and comments
            if ($this->view->ad['type'] == 1) {
                $this->view->page_title .= $this->view->translate('give') . ' ' . $this->view->translate('second hand') . ' ';
            }

            if ($this->view->ad['type'] == 2) {
                $this->view->page_title .= $this->view->translate('want') . ' ' . $this->view->translate('second hand') . ' ';
            }

            $this->view->comments = $model->getComments($id);
            $this->view->woeidName = $this->_helper->woeid->name($this->view->ad['woeid_code'], $this->lang);


            $this->view->page_title .= $this->view->ad['title'] ;
            $this->view->page_title .= ' '. $this->view->translate('free') .' ';
            $this->view->page_title .= ' '. $this->view->woeidName;

            //add meta description to head
            $this->view->metaDescription = $this->view->page_title . '. ' . $this->view->ad['body'];

            //add link rel canonical , better seo
            $this->view->canonicalUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $this->lang . '/ad/' . $id . '/' .
                $this->view->slugTitle($this->view->ad['title']);

            //if user logged in, show the comment form, if not show the login link
            $auth = Zend_Auth::getInstance();
            if (!$auth->hasIdentity()) {

                $this->view->createcomment = '<a href="/' . $this->lang . '/auth/login">' . $this->view->translate('login to post a comment') . '</a> ';
            } else {
                require_once APPLICATION_PATH . '/forms/Comment.php';
                $form = new Form_Comment();
                $form->setAction('/' . $this->lang . '/comment/create/ad_id/' . $id);

                $this->view->createcomment = $form;
            }
        } else {
            $urlChunks = explode('/', $_SERVER['REQUEST_URI']);
            $urlChunks = str_replace('.html', '', $urlChunks);
            $urlChunks = str_replace('-', ' ', $urlChunks);
            //redir to search
            $this->_redirect('/' . $this->lang . '/search/?q=' . $urlChunks[sizeof($urlChunks) - 1] . '&ad_type=1&woeid=' . $this->location , array('code'=>301));
        }
    }


    public function createAction() {
        //first we check if user is logged, if not redir to login
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) {
            //keep this url in zend session to redir after login
            $aNamespace = new Zend_Session_Namespace('Nolotiro');
            $aNamespace->redir = $this->lang . '/ad/create';
            $this->_redirect($this->lang . '/auth/login');
        } else {
            $request = $this->getRequest();
            require_once APPLICATION_PATH . '/forms/AdCreate.php';
            $form = new Form_AdCreate();
            $this->view->form = $form;
            $this->view->woeidName = $this->_helper->woeid->name($this->location, $this->lang);

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $formulario = $form->getValues();

                    //create thumbnail if image exists
                    if (!empty($formulario['photo'])) {
                        $photobrut = $formulario['photo'];
                        $formulario['photo'] = $this->_createThumbnail($photobrut, '100', '90');
                    }

                    // Create a filter chain and add filters to title and body against xss, etc
                    $f = new Zend_Filter();
                    $f->addFilter(new Zend_Filter_StripTags());
                    //->addFilter(new Zend_Filter_HtmlEntities());

                    $formulario['title'] = $f->filter($formulario['title']);
                    $formulario['body'] = $f->filter($formulario['body']);

                    //anti HOYGAN to title
                    //dont use strtolower because dont convert utf8 properly . ej: á é ó ...
                    $formulario['title'] = ucfirst(mb_convert_case($formulario['title'], MB_CASE_LOWER, "UTF-8"));

                    //anti hoygan to body
                    $split = explode(". ", $formulario['body']);

                    foreach ($split as $sentence) {
                        $sentencegood = ucfirst(mb_convert_case($sentence, MB_CASE_LOWER, "UTF-8"));
                        $formulario['body'] = str_replace($sentence, $sentencegood, $formulario['body']);
                    }

                    //get the ip of the ad publisher
                    if (getenv(HTTP_X_FORWARDED_FOR)) {
                        $ip = getenv(HTTP_X_FORWARDED_FOR);
                    } else {
                        $ip = getenv(REMOTE_ADDR);
                    }

                    $formulario['ip'] = $ip;

                    //get this ad user owner
                    $formulario ['user_owner'] = $auth->getIdentity()->id;

                    //get date created
                    //TODO use the Zend Date object to fetch the user locale time zone
                    $datenow = date("Y-m-d H:i:s", time());
                    $formulario ['date_created'] = $datenow;

                    //get woeid to assign to this ad
                    //the location its stored at session location value
                    //(setted by default on bootstrap to Madrid woeid number)
                    $formulario ['woeid_code'] = $this->location;
                    $modelAd = new Model_Ad();

                    //chek if this user has 5 or more quieros published
                    if ($formulario['type'] == '2') {
                        $countQuieros = $modelAd->getCountAdWantUser($auth->getIdentity()->id);

                        if ($countQuieros >= 5) {
                            $this->_helper->_flashMessenger->addMessage($this->view->translate('Sorry, you have 5 or more want ads, delete olders!'));
                            $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
                        }
                    }

                    //if ok, create the form
                    $modelAd->createAd($formulario);

                    $this->_helper->_flashMessenger->addMessage($this->view->translate('Ad published succesfully!'));
                    $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
                }
            }
        }
    }


    public function editAction() {
        //check if user logged in
        $auth = Zend_Auth::getInstance();
        $user = new Model_User;
        $ad = new Model_Ad();

        $id = (int)$this->getRequest()->getParam('id');
        $ad_user_owner = $ad->getAd($id);

        if ($auth->hasIdentity()) {

            $this->view->userRole = $this->_helper->checkUserRole->check();

            //if user owner allow edit and show delete ad link , if not redir not allowed
            if ($this->view->userRole == 1) {
                //bazinga!!
            }
            elseif ($user->fetchUser($auth->getIdentity()->id)->id != $ad_user_owner['user_owner']) {
                $this->_helper->_flashMessenger->addMessage($this->view->translate('You are not allowed to view this page'));
                $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
            }

        } else {

            $this->_helper->_flashMessenger->addMessage($this->view->translate('You are not allowed to view this page'));
            $this->_redirect('/' . $this->lang . '/woeid/' . $this->location . '/give');
            return;
        }

        $this->view->deletead = '<img src="/images/delete_ad.png" />
                    <a href="/' . $this->view->lang . '/ad/delete/id/' . $this->_getParam('id') . ' ">' . $this->view->translate('delete this ad') . '</a>';


        $request = $this->getRequest();
        require_once APPLICATION_PATH . '/forms/AdEdit.php';
        $form = new Form_AdEdit ();


        $form->addElement('select', 'status', array(
            'order' => '1',
            'label' => 'Status:', 'required' => true,
            'multioptions' => array('available' => 'available', 'booked' => 'booked', 'delivered' => 'delivered')));


        $this->view->page_title .= $this->view->translate('Edit your ad');
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {

            $formData = $this->getRequest()->getPost();


            if ($form->isValid($formData)) {
                $formulario = $form->getValues();

                //anti HOYGAN to title
                //dont use strtolower because dont convert utf8 properly . ej: á é ó ...
                $formulario['title'] = ucfirst(mb_convert_case($formulario['title'], MB_CASE_LOWER, "UTF-8"));

                //anti hoygan to body
                $split = explode(". ", $formulario['body']);

                foreach ($split as $sentence) {
                    $sentencegood = ucfirst(mb_convert_case($sentence, MB_CASE_LOWER, "UTF-8"));
                    $formulario['body'] = str_replace($sentence, $sentencegood, $formulario['body']);
                }

                //var_dump($form);
                //set filter againts xss and nasty things
                $f = new Zend_Filter();
                $f->addFilter(new Zend_Filter_StripTags());

                $data['title'] = $f->filter($formulario['title']);
                $data['body'] = $f->filter($formulario['body']);
                $data['type'] = $f->filter($formulario['type']);

                //create thumbnail if image exists
                if ($formulario['photo']) {
                    $photobrut = $formulario['photo'];
                    $data['photo'] = $this->_createThumbnail($photobrut, '100', '90');
                }


                $data['status'] = $formulario['status'];
                $data['comments_enabled'] = $formulario['comments_enabled'];

                $model = new Model_Ad();
                $model->updateAd($data, (int)$id);

                //delete memcached ad if exists
                //check if the ad exists in memcached
                $oBackend = new Zend_Cache_Backend_Memcached(
                    array(
                        'servers' => array(array(
                            'host' => '127.0.0.1',
                            'port' => '11211'
                        )),
                        'compression' => true
                    ));

                // configure caching frontend strategy
                $oFrontend = new Zend_Cache_Core(
                    array(
                        // cache for 7 days
                        'lifetime' => 3600 * 24 * 7,
                        'caching' => true,
                        'cache_id_prefix' => 'singleAd',
                        'logging' => false,
                        'write_control' => true,
                        'automatic_serialization' => true,
                        'ignore_user_abort' => true
                    ));

                // build a caching object
                $cacheAd = Zend_Cache::factory($oFrontend, $oBackend);

                $cacheAd->remove((int)$id);


                $this->_helper->_flashMessenger->addMessage($this->view->translate('Ad edited succesfully!'));
                $this->_redirect('/' . $this->lang . '/ad/' . $id);
            } else {

                $id = $this->_getParam('id');
                $ad = new Model_Ad();

                $advalues = $ad->getAd($id);
                // if photo not empty then show and let change it
                $current_photo = $advalues['photo'];
                if ($current_photo) {
                    $this->view->current_photo = ' <img src="/images/uploads/ads/100/' . $current_photo . '" />';
                }

                $form->populate($formData);
            }
        } else {
            $id = $this->_getParam('id');
            if ($id > 0) {
                $ad = new Model_Ad();

                $advalues = $ad->getAd($id);
                // if photo not empty then show and let change it

                $current_photo = $advalues['photo'];

                if ($current_photo) {
                    $this->view->current_photo = ' <img  src="/images/uploads/ads/100/' . $current_photo . '" />';
                }
                $form->populate($ad->getAd($id));
            }
        }
    }


    public function deleteAction() {
        $this->userRole = $this->_helper->checkUserRole->check();
        $this->view->headTitle()->append($this->view->translate('Delete your profile'));

        $id = (int)$this->getRequest()->getParam('id');
        $auth = Zend_Auth::getInstance();
        //check if user is auth
        if ($auth->hasIdentity() == FALSE) {
            $this->_helper->_flashMessenger->addMessage($this->view->translate('You are not allowed to view this page'));
            $this->_redirect('/' . $this->view->lang . '/woeid/' . $this->location . '/give');
            return;
        }

        $admodel = new Model_Ad();
        $ad = $admodel->getAd($id);


        if (($auth->getIdentity()->id == $ad['user_owner']) || ($this->userRole == 1)) {
            //if is the user owner owner lets delete it
            if ($this->getRequest()->isPost()) {
                $del = $this->getRequest()->getPost('del');

                if ($del == 'Yes') {
                    //delete ad
                    $admodel->deleteAd($id);


                    //delete from memcached if exists in memory
                    //check if the ad exists in memcached
                    $oBackend = new Zend_Cache_Backend_Memcached(
                        array(
                            'servers' => array(array(
                                'host' => '127.0.0.1',
                                'port' => '11211'
                            )),
                            'compression' => true
                        ));

                    // configure caching frontend strategy
                    $oFrontend = new Zend_Cache_Core(
                        array(
                            // cache for 7 days
                            'lifetime' => 3600 * 24 * 7,
                            'caching' => true,
                            'cache_id_prefix' => 'singleAd',
                            'logging' => false,
                            'write_control' => true,
                            'automatic_serialization' => true,
                            'ignore_user_abort' => true
                        ));

                    // build a caching object
                    $cacheAd = Zend_Cache::factory($oFrontend, $oBackend);
                    $cacheTest = $cacheAd->test($id);

                    if ($cacheTest == true) {
                        $cacheAd->remove($id);
                    }


                    /////

                    $this->_helper->_flashMessenger->addMessage($this->view->translate('Ad deleted successfully.'));
                    $this->_redirect('/' . $this->view->lang . '/woeid/' . $this->location . '/give');
                    return;
                } else {
                    $this->_helper->_flashMessenger->addMessage($this->view->translate('Nice to hear that :-)'));
                    $this->_redirect('/' . $this->view->lang . '/woeid/' . $this->location . '/give');
                    return;
                }
            } else {
                $id = $this->_getParam('id', 0);
            }
        } else {

            $this->_helper->_flashMessenger->addMessage($this->view->translate('You are not allowed to view this page'));
            $this->_redirect('/' . $this->view->lang . '/woeid/' . $this->location . '/give');
            return;
        }
    }


    /*
    * _createThumbnail uses resize class
    *
    */
    protected function _createThumbnail($file, $x, $y) {

        require_once (NOLOTIRO_PATH . '/library/SimpleImage.php');

        $file_ext = substr(strrchr($file, '.'), 1);
        $fileuniquename = md5(uniqid(mktime())) . '.' . $file_ext;

        $image = new SimpleImage();
        $image->load('/tmp/' . $file);

        //save original to right place
        $widthmax = 900;
        $heightmax = 1000;
        $image->resizeToHeightMax($heightmax);
        $image->resizeToWidthMax($widthmax);
        $image->save(NOLOTIRO_PATH . '/www/images/uploads/ads/original/' . $fileuniquename);

        //save thumb
        $image->resizeToHeight($y);
        $image->resizeToWidth($x);

        $image->save(NOLOTIRO_PATH . '/www/images/uploads/ads/100/' . $fileuniquename);

        return $fileuniquename;
    }

}
