<?php

/**
 * This is the model class for table "{{message}}".
 *
 * The followings are the available columns in table '{{message}}':
 * @property integer $id
 * @property integer $mglist_id
 * @property string $subject
 * @property string $body
 * @property integer $status
 * @property string $sent_at
 * @property string $created_at
 * @property string $modified_at
 *
 * The followings are the available model relations:
 * @property Mglist $mglist
 */
class Message extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Message the static model class
	 */
	 public $publish_time;
	 
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{message}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('subject', 'required'),
			array('mglist_id, status', 'numerical', 'integerOnly'=>true),
			array('subject', 'length', 'max'=>255),
			array('publish_time','lessThanThreeDays'),
			array('body, sent_at, created_at', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, mglist_id, subject, body, status, sent_at, created_at, modified_at', 'safe', 'on'=>'search'),
		);
	}

  public function lessThanThreeDays($attribute,$params)
     {
        if (strtotime($this->$attribute)>=(time()+3600*72-180))
           $this->addError($attribute, 'No more than three days in the future.');

   }
	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'mglist' => array(self::BELONGS_TO, 'Mglist', 'mglist_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'mglist_id' => 'Mglist',
			'subject' => 'Subject',
			'body' => 'Body',
			'status' => 'Status',
			'sent_at' => 'Sent At',
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
		$criteria->compare('mglist_id',$this->mglist_id);
		$criteria->compare('subject',$this->subject,true);
		$criteria->compare('body',$this->body,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('sent_at',$this->sent_at,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('modified_at',$this->modified_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}