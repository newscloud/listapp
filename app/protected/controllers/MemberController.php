<?php
class MemberController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('remove'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update','import','index','view','export'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionRemove($id,$mglist_id,$hash)
	{
	  // check hash is correct
	  // remove member from list
	  $member = Member::model()->findByPk($id);
	  $mglist = Mglist::model()->findByPk($mglist_id);
	  $member->removeFromList($id,$mglist_id);
		$this->render('remove',array(
			'member'=>$member,
			'mglist'=>$mglist,
		));
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new member.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($id)
	{
		$model=new Member;
		$mglist_id = $id;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Member']))
		{
			$model->attributes=$_POST['Member'];
      $model->status=1;
			$model->created_at =new CDbExpression('NOW()'); 
      $model->modified_at =new CDbExpression('NOW()');  
      // to do - note member records aren't shared across lists, not unique
      // to add this functionality, removes need to be sensitive to removing entries from members table
      // until not on any lists              
			if($model->save())
			  $model->addToList($model->id,$mglist_id);
			  $lookup_list = Mglist::model()->findByPk($mglist_id);
			  // to do fetch list address
			  $yg = new Yiigun();
			  $yg->memberAdd($lookup_list['address'],$model->address,$model->name);
				$this->redirect('/mglist/'.$mglist_id);
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Member']))
		{
			$model->attributes=$_POST['Member'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('Member');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Member('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Member']))
			$model->attributes=$_GET['Member'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	public function actionExport($id=0)
	{
	  echo 'List id:'.$id;lb();
	  $l = Mglist::model()->findByPk($id);
	  echo $l['name'];lb();
    $m=Membership::model()->findAllByAttributes(array('mglist_id'=>$id));
    foreach ($m as $i) {
        $p = Member::model()->findByPk($i['member_id']);
        echo $p['name'].' &lt;'.$p['address'].'&gt;<br />';
    }
  }
  /**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionImport($id=0)
	{
	      $model = new Import();
    		$model->mglist_id = $id;
    		
    		// Uncomment the following line if AJAX validation is needed
    		$this->performAjaxValidation($model);
    		if(isset($_POST['Import']))
    		{
    			if($model->save()) {
    			  // load PEAR mail lib
            include('Mail/RFC822.php');
            $parser = new Mail_RFC822();
            // load yiigun  lib
            $yg = new Yiigun;            
            // fetch list info
            $list_item = Mglist::model()->findByPk($id);
            // begin processing import of email list
      			$posted_email_list = $_POST['Import']['email_list'];
            // replace the backslash quotes 
            $tolist=str_replace('\\"','"',$posted_email_list);
            // split the elements by line and by comma 
            $to_email_array = preg_split ("(\r|\n|,)", $tolist, -1, PREG_SPLIT_NO_EMPTY);
            $num_emails = count ($to_email_array); 
            $counter =0;
            $uploadStr='';                        
              // to do
              // to strip apostrophes from names
            // fix importer to find emails in first or last name
            // and to split on :::
            $debug = false;
            $count_added =0;
            $count_errors =0;
            $email_list ='';
            $error_text = '';
            $alpha=set_error_handler("parseError");
            foreach($to_email_array as $email) {              
              $toAddress=trim($email);
              // strip out invalid characters
              $toAddress=str_replace('%','',$toAddress);              
              if ($toAddress<>'') {
                if (substr_count ( $toAddress , '@')<>1) {
                  $error_text.=htmlspecialchars($toAddress).', <br />';
                  $count_errors+=1;
                  continue;
                }                
                //echo '<p>'.htmlspecialchars($toAddress).'</p>';
                $addresses = $parser->parseAddressList('my group:'.$toAddress,'yourdomain.com', false,true);
                $count_added+=1;
                foreach ($addresses as $i) {
                  $uploadStr.='{';
                    $m = new Member();
                     if ($i->mailbox<>'' and $i->host<>'') {
                       //echo 'adding to email_list'.$i->mailbox;
                       $email_list.=$i->personal.' ('.$i->mailbox.'@'.$i->host.'), <br />';
                     }
                     if ($i->personal<>'') {
                       $m->name = $i->personal;
                       $uploadStr.='"name": "'.$m->name.'", ';                  
                     }
                     else 
                       $m->name ='';
                     $m->address=$i->mailbox.'@'.$i->host;
                     $uploadStr.='"address": "'.$m->address.'"';                  
                     $m->status=1;
                     $m->created_at = new CDbExpression('NOW()'); 
                     $m->modified_at = new CDbExpression('NOW()');          	                  
                      // echo $m->name.' '.$m->address.'<br />';
      $lookup_item=Member::model()->findByAttributes(array('address'=>$m->address));
                    if (!$debug) {
                   	  if (!is_null($lookup_item)) {
                   	       // member exists
                   	      $m->addToList($lookup_item['id'],$model->mglist_id);
                   	  } else {
                   	    // new member
                         $m->save();
                         $last_id = Yii::app()->db->getLastInsertID();
                         $m->addToList($last_id,$model->mglist_id);
                   	  }                      
                    }
                    $uploadStr.='},';            
                }  // foreach addresses as i
              } // end toaddress <>''
                 $counter+=1;
                 // count by 1000 to manage cloud upload
                   if ($counter>=900) {
                     if (!$debug) {
                     $uploadStr=$yg->wrapJsonStr($uploadStr);
                     $yg->memberBulkAdd($list_item['address'],$uploadStr);      
                      } 
                      $counter =0;
                      $uploadStr='';                
                    }
            } // end foreach toemail array      
            // upload last group to cloud   
            if (!$debug) {
              if ($uploadStr<>'') {
                $uploadStr=$yg->wrapJsonStr($uploadStr);
                $yg->memberBulkAdd($list_item['address'],$uploadStr);      
              }
            }
            $email_list=trim($email_list,',');
            $model->email_list = $email_list;                            
            Yii::app()->user->setFlash('import_success','Thank you! Your messages have been submitted.');
        		$this->render('imported',array(
        			'added'=>$email_list,'errors'=>$error_text,'count_added'=>$count_added,'count_errors'=>$count_errors,
        		));
            } // end if save
    			 // end if post
  			} else {
      		$this->render('import',array(
      			'model'=>$model,'mglist_id'=>$id,
      		));  			  
  			}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Member::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='member-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}

function parseError($errno, $errstr, $errfile, $errline)
{
}

