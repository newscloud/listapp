<?php

/**
 * This is the model class for table "{{mglist}}".
 *
 * The followings are the available columns in table '{{mglist}}':
 * @property integer $id
 * @property string $address
 * @property string $name
 * @property string $description
 * @property string $access_level
 * @property string $created_at
 * @property string $modified_at
 *
 * The followings are the available model relations:
 * @property Membership[] $memberships
 */
class Mglist extends CActiveRecord
{
  
  private $_api_key;
  private $_api_url;
  public $output_str;
  
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Mglist the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{mglist}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('address, name', 'required'),
			array('address, name,access_level', 'length', 'max'=>255),
			array('address, name', 'unique'),
			array('description', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, address, name, description, access_level, created_at, modified_at', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'memberships' => array(self::HAS_MANY, 'Membership', 'mglist_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'address' => 'Address',
			'name' => 'Name',
			'description' => 'Description',
			'access_level' => 'Access Level',
			'created_at' => 'Created At',
			'modified_at' => 'Modified At',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('access_level',$this->access_level);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('modified_at',$this->modified_at,true);
    $criteria->order = Yii::app()->request->getParam('sort');;

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	public function sync() {
	  // Sync all lists and their members
	  $this->output_str = '';
	  $yg = new Yiigun();
	  $my_lists = $yg->fetchLists();
  foreach ($my_lists->items as $item) {
  	  $this->output_str.='<p>Synchronizing list: '.$item->name.'<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  	  // add to local db
        $this->upsert($item);    	          $lookup_item=$this->findByAttributes(array('address'=>$item->address));        
    	  $this->output_str.='<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;fetching members... <br />';  	  
    	  $this->output_str.=$this->syncListMembers($lookup_item['id'],true);
    	  $this->output_str.='</p>';
    }
	  return $this->output_str;
	}
	
	public function syncListMembers($id=0,$in_batch=false) {
	  // fetch list members from Mailgun.com
	  // don't build membership detail report for batch list sync
	  if (is_null($id)) return false;
	  $output_str = '';
    $mglist = $this->findByPk($id);    
	  $yg = new Yiigun();
    $skip = 0;
    $limit = 100;
    $item_count = 0;
    $total_count = 10000;
    while ($item_count < $total_count ) {
      $output_str.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Requesting records beginning at index: '.$skip.'<br />'; 
  	  $my_members = $yg->fetchListMembers($mglist['address'],$skip,$limit);
  	  $total_count = $my_members->total_count;
  	  $skip+=$limit;
    	// fetch list address based on $id
      foreach ($my_members->items as $member) {
    	    $output_str.='<p>Upserting member: '.$member->name.' &lt;'.$member->address.'&gt;<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    	    $m = new Member();
    	    // add to local db
          $temp_str=$m->upsert($member); 
      	  $output_str.=$temp_str;
          // add to join table
          $member=Member::model()->findByAttributes(array('address'=>$member->address));
      	  Member::model()->addToList($member['id'],$id);
      	  $output_str.='</p>';
    	  }
  	  if (count($my_members->items)<$limit)
  	    break;    
    }	  
    $output_str.='Total count: '.$total_count.'<br />';
	  return $output_str;
	}
	
	public function upsert($item) {
	  // create if new, otherwise update fields
	  $lookup_item=$this->findByAttributes(array('address'=>$item->address));
	  if (!is_null($lookup_item)) {
	    $this->updateProperties($lookup_item,$item);
	  } else {
  	  $this->create($item);	    
	  }
	}

  public function updateProperties($mgl,$list) {
    $this->output_str.='updating properties from Mailgun';
    $mgl->name = $list->name;
    $mgl->access_level = $list->access_level;
    $mgl->address = $list->address;
    $mgl->description = $list->description;
    $mgl->modified_at =new CDbExpression('NOW()');
    $mgl->update();
  }
  
  public function create($list) {
    $this->output_str.='creating list...';
    $mgl = new Mglist();
    $mgl->name = $list->name;
    $mgl->access_level = $list->access_level;
    $mgl->address = $list->address;
    $mgl->description = $list->description;
    $mgl->created_at =new CDbExpression('NOW()'); 
    $mgl->modified_at =new CDbExpression('NOW()');          
    //$list->members_count
    $this->output_str.='Saving...'.$mgl->name.'<br />';
    if ($mgl->save()) {
      $this->output_str.='successful';
    } else {
      $this->output_str.='failed to save list - might be duplicate naming';
    }
    $this->output_str.='<br />';
  }	
  
  public function duplicate($id) {
    $yg = new Yiigun();
		$list=$this->findByPk($id);
		// create copy of 
		$suffix = rand(0,999);
		// create local copy of list
		$mgl = new Mglist();
    $mgl->name = 'Copy of '.$list->name.' - '.$suffix;
    $mgl->access_level = $list->access_level;
    $mgl->address = 'copy-'.$suffix.'-'.$list->address;
    $mgl->description = $list->description;
    $mgl->created_at =new CDbExpression('NOW()'); 
    $mgl->modified_at =new CDbExpression('NOW()');          
		$mgl->save();
		// create copy of list at mailgun
    $yg->listCreate($mgl);
		// create local copy of members and upload them to mailgun
		$counter=0;
		$uploadStr='';
    $members = Yii::app()->db->createCommand()
             ->select('p.id,p.member_id,q.*')
             ->from(Yii::app()->getDb()->tablePrefix.'membership p')
             ->join(Yii::app()->getDb()->tablePrefix.'member q', 'q.id=p.member_id')
             ->where('p.mglist_id=:id', array(':id'=>$id))
             ->queryAll();
     foreach ($members as $member) {
         Member::model()->addToList($member['member_id'],$mgl->id);
         $uploadStr.=$yg->createJsonMember($member['name'],$member['address']);
         $counter+=1;
         // count by 1000 to manage cloud upload
         if ($counter>=900) {
           $uploadStr=$yg->wrapJsonStr($uploadStr);
           // add to combined list in the cloud with bulk import
           $yg->memberBulkAdd($mgl->address,$uploadStr);
           $counter =0;
           $uploadStr='';
         }
     } 
     // upload last group to cloud   
     if ($uploadStr<>'') {
       $uploadStr=$yg->wrapJsonStr($uploadStr);
       $yg->memberBulkAdd($mgl->address,$uploadStr);      
     }
  }
  
  public function uniqify($id) {
    set_time_limit(1200);
    $yg = new Yiigun();
		$list=$this->findByPk($id); 
		// members of all other lists
		// note: this is a join of all members NOT in this list
    $members = Yii::app()->db->createCommand()
             ->select('p.id,p.member_id,q.*')
             ->from(Yii::app()->getDb()->tablePrefix.'membership p')
             ->join(Yii::app()->getDb()->tablePrefix.'member q', 'q.id=p.member_id')
             ->where('p.mglist_id<>:id', array(':id'=>$id))
             ->queryAll();
		foreach ($members as $member) {
		   // echo 'checking'.$member['address'].'...';lb();
		  // if member is in our list, remove it
  		// note: this is a join of all matching email addresses in THIS list
	    $exist_in_list = Yii::app()->db->createCommand()
               ->select('m.address,p.id,p.mglist_id,p.member_id')
               ->from(Yii::app()->getDb()->tablePrefix.'member m')
               ->join(Yii::app()->getDb()->tablePrefix.'membership p', 'm.id=p.member_id')
               ->where('p.mglist_id=:id and m.address=:address', array(':id'=>$id,':address'=>$member['address']))
               ->queryRow();
		  if ($exist_in_list!==false) {		   
		    // var_dump($exist_in_list);lb();
  		  // delete from table locally
  		  // deletes specific membership record
        Membership::model()->deleteAll('id=:id', array('id'=>$exist_in_list['id']));
  		  // remove member in the cloud
  		  $yg->memberDelete($list['address'],$member['address']);
		  }
    }    
  }
  
  public function listMembers($id) {
    $membership = Yii::app()->db->createCommand()
             ->select('j.member_id,m.name,m.address')
             ->from(Yii::app()->getDb()->tablePrefix.'membership j')
             ->join(Yii::app()->getDb()->tablePrefix.'member m', 'm.id=j.member_id')
             ->where('j.mglist_id=:id', array(':id'=>$id))
             ->queryAll();
    return $membership;
  }
  
   public function getListOptions()
   {
     $listsArray = CHtml::listData(Mglist::model()->findAll(), 'id', 'name');
     return $listsArray;
  }	

  public function member_count($id) {    
    return Membership::model()->count('mglist_id=:id',array('id'=>$id));
  }

  // builds a master list with all members in your system
  public function createAll() {
    // assumption: that you run sync lists first to fully update your local db
	  $yg = new Yiigun();    
    // check if master list exists at mailgun
    $exists = false;
    $cloud_lists = $yg->fetchLists();
    foreach ($cloud_lists->items as $item) {
      if ($item->address == 'all_members@'.Yii::app()->params['mail_domain']) {
        $exists=true;
        break;
      }
    }    
    $newlist = new stdClass;
    $newlist->address = 'all_members@'.Yii::app()->params['mail_domain'];      
    $newlist->name = 'All members';
    $newlist->description = 'An automatically created list with all of your members.';
    $newlist->access_level = 'readonly';      
    if (!$exists) {
      // create list in the cloud
  	  $result = $yg->listCreate($newlist);
    }    
    // check if master list exists locally
    $lookup_item=$this->findByAttributes(array('address'=>$newlist->address));        
    if (is_null($lookup_item)) {
      // if not create it locally
      $mgl = new Mglist();
      $mgl->name = $newlist->name;
      $mgl->access_level = $newlist->access_level;
      $mgl->address = $newlist->address;
      $mgl->description = $newlist->description;
      $mgl->created_at =new CDbExpression('NOW()'); 
      $mgl->modified_at =new CDbExpression('NOW()');          
      $mgl->save();
      $all_list_id = $mgl->id;
    } else {
      $all_list_id = $lookup_item->id;
    }
    // loop through all unique local members by 1000     
//$members=Membership::model()->findAll(array('select'=>'member_id','distinct'=>true,'condition'=>'mglist_id<>:mglist_id','params'=>array(':mglist_id' => $all_list_id),'order'=>'member_id asc'));     
     
     // find all distinct email addresses
     $members=Member::model()->findAll(array('select'=>'address','distinct'=>true,'order'=>'address asc'));     
     
    $counter = 0;
    $uploadStr='';
    // add to combined list locally
    foreach ($members as $member) {
        $m=Member::model()->findByAttributes(array('address'=>$member['address']));
        Member::model()->addToList($m['id'],$all_list_id);
        $uploadStr.=$yg->createJsonMember($m['name'],$m['address']);
        $counter+=1;
        // count by 1000 to manage cloud upload
        if ($counter>=900) {
          $uploadStr=$yg->wrapJsonStr($uploadStr);
          // add to combined list in the cloud with bulk import
          $yg->memberBulkAdd($newlist->address,$uploadStr);
          $counter =0;
          $uploadStr='';
        }
    } 
    // upload last group to cloud   
    if ($uploadStr<>'') {
      $uploadStr=$yg->wrapJsonStr($uploadStr);
      $yg->memberBulkAdd($newlist->address,$uploadStr);      
    }
  }
}