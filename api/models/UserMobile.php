<?php

/**
 * This is the model class for table "user_mobile".
 *
 * The followings are the available columns in table 'user_mobile':
 * @property integer $id
 * @property string $created
 * @property string $updated
 * @property string $name
 * @property string $sex
 * @property integer $age
 * @property string $mac_address
 * @property string $token
 */
class UserMobile extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserMobile the static model class
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
		return 'user_mobile';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, sex, age, mac_address', 'required'),
			array('mac_address', 'unique'),
			array('age', 'numerical', 'integerOnly'=>true),
			array('name, mac_address, token', 'length', 'max'=>255),
			array('sex', 'length', 'max'=>6),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, sex, age, mac_address, token, updated, created', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'created' => 'Дата создания',
			'updated' => 'Дата изменения',
			'name' => 'Name',
			'sex' => 'Sex',
			'age' => 'Age',
			'mac_address' => 'Mac Address',
			'token' => 'Token',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('sex',$this->sex,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('updated',$this->updated,true);
		$criteria->compare('age',$this->age);
		$criteria->compare('mac_address',$this->mac_address,true);
		$criteria->compare('token',$this->token,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	public function checkToken($theirToken){
		return $this->token == $theirToken;
	}

	public function generateToken(){
		$now = date('Y-m-d H:i:s');
		return md5($this->mac_address.'123'.$this->id.$now);
	}
	
	public function beforeSave() {
	    parent::beforeSave();
	    if ($this->isNewRecord) {
			$this->created = date("Y-m-d H:i:s");
	    }
		$this->updated = date("Y-m-d H:i:s");
	    return true;
	}

	public static function authenticate($token, $id){
		$user = UserMobile::model()->cache(60*60*24)->find('id = :id and token = :token' ,array(':id'=>$id, ':token'=>$token));
		return $user;
	}

}