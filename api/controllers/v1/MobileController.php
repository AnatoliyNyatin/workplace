<?php

class MobileController extends Controller
{
	const LIMIT = 20;   // Restaraunts per page

	const YANDEX_API_URL = 'http://geocode-maps.yandex.ru/1.x/?results=1&format=json';
	const LONGITUDE_RANGE = 0.018432;
	const LATITUDE_RANGE = 0.008616;

	public function init(){
		header('Content-Type: application/json');
	}

	/**
	 * Creates a new mobile user.
	 */

	public function actionCreate()
	{
		$model=new UserMobile('create');
		$model->attributes = $_POST['UserMobile'];
		if ($model->validate() && $model->save()){
			$model->token = $model->generateToken();
			$model->save();
			$result = array();
			$result['token'] = $model->token;
			$result['id'] = $model->id;
			$this->makeResult($result, 'profile');
		}else{
			$this->makeError($model->getErrors());
		}
	}
	
	/**
	 * Login mobile user by special key and mac address
	 */

	public function actionLogin() {
		if (isset($_POST['key']) && isset($_POST['mac_address']) && isset($_POST['time'])){
			$user = UserMobile::model()->find('mac_address = :mac_address', array(':mac_address' => $_POST['mac_address']));
			if ($this->checkKey($_POST['key'], $user, $_POST['time'])){
				if ($user){
					$user->token = $user->generateToken();
					$user->save();
					$result = array();
					$result['token'] = $user->token;
					$result['id'] = $user->id;
					$result['sex'] = $user->sex;
					$result['age'] = $user->age;
					$result['name'] = $user->name;
					$result['created'] = $user->created;
					$this->makeResult($result, 'profile');
				}else{
					$this->makeError('Invalid mac_address');
				}
			}else{
				$this->makeError('Invalid mac_address');
			}
		}else{
			$this->makeError('Invalid key');
		}
	}

	public function actionUpdate() {
		$this->checkRequest();
		$user = UserMobile::authenticate($_POST['token'], $_POST['user_id']);
		if ($user){
			$user->attributes = $_POST['UserMobile'];
			if ($user->validate()){
				$user->save();
				$this->makeResult($user, 'profile');
			}else{
				$this->makeError($user->getErrors());
			}	
		}else{
			$this->makeError('Invalid token or id');
		}
	}

	public function actionSend(){
		$this->checkRequest();
		$user = UserMobile::authenticate($_POST['token'], $_POST['user_id']);
		if ($user){
			if (isset($_POST['Message'])){
				$model=new Message('create');
				$model->attributes = $_POST['Message'];
				$model->user_id = $user->id;
				$model->incomming = 0;
				$model->read = 0;
				if ($model->validate() && $model->save()){
					if ($model->parent_id == 0){
						$uModel = new MessageUnanswered('create');
						$uModel->message_id = $model->id;
						$uModel->user_id = $model->user_id;
						$uModel->estbl_id = $model->estbl_id;
						$uModel->save();
					}else{
						$uModel = MessageUnanswered::model()->findByPk($model->parent_id);
						if (!$uModel){
							$uModel = new MessageUnanswered('create');
							$uModel->message_id = $model->parent_id;
							$uModel->user_id = $model->user_id;
							$uModel->estbl_id = $model->estbl_id;
							$uModel->save();
						}
					}
					$this->makeResult();
				}else{
					$this->makeError($model->getErrors());
				}
			}else{
				$this->makeError('Invalid request');
			}
		}else{
			$this->makeError('User not exist');
		}
	}

	public function actionGetMessages(){
		$this->checkRequest();
		$user = UserMobile::authenticate($_POST['token'], $_POST['user_id']);
		if ($user){
			$row = Message::model()->getMobileMessages($_POST['estbl_id'], $user->id)->getData();
			$toUpdate = array();
			$result = array();
			$counter = 0;
			foreach ($row as $r) {
				if ($r->read == 0 && $r->incomming == 1){
					$toUpdate[] = $r->id;
				}
				$result[$counter]['incomming'] = $r->incomming;
				$result[$counter]['text'] = $r->text;
				$result[$counter]['created'] = $r->created;
				$result[$counter]['read'] = $r->read;
				$result[$counter]['id'] = $r->id;
				$counter++;
			}
			$updated = Message::model()->updateShowMessages($toUpdate, $_POST['estbl_id'], $user->id);
			$this->makeResult($result, 'messages');
		}else{
			$this->makeError('User not exist');
		}
	}

	public function actionDeleteMessage(){
		$this->checkRequest();
		$user = UserMobile::authenticate($_POST['token'], $_POST['user_id']);
		if ($user){
			$m_id = $_POST['message_id'];
			$message = Message::model()->find('user_id = :user_id and id = :id', array(':user_id'=>$user->id, ':id'=>$m_id));
			if ($message){
				$message->show_user = 0;
				if ($message && $message->save()){
					$this->makeResult();
				}else{
					$this->makeError('unexpected error');
				}
			}else{
				$this->makeError('unexpected error');
			}
		}else{
			$this->makeError('User not exist');
		}
	}

	public function actionGetNearestEstablishment(){
		if (!isset($_POST['latitude']) || !isset($_POST['longitude'])){
			$this->makeError('Bad request');
		}
		$this->getEstablishmentsByCoords($_POST['longitude'], $_POST['latitude']);
	}

	public function actionGetSearchEstablishment(){
		if (!isset($_POST['searchString']) || $_POST['searchString'] == ''){
			$this->makeError('Bad request');
		}
		$jsonPage = file_get_contents(self::YANDEX_API_URL."&geocode=".urlencode($_POST['searchString']));
		$page = CJSON::decode($jsonPage);
		$coords = $this->array_search_recursive($page, 'pos');
		
		if ($coords){
			$arr = explode(" ", $coords);
			$lng = $arr[0];
			$ltd = $arr[1];
			$this->getEstablishmentsByCoords($arr[0], $arr[1]);
		}else{
			$this->makeError('Bad request');
		}
	}

	private function getEstablishmentsByCoords($longitude, $latitude){
		$establishments = Establishment::model()->findAll('lng between :lngLeft and :lngRight and ltd between :ltdUp and :ltdDown', 
													array(
														':lngLeft'=>$longitude - self::LONGITUDE_RANGE,
														':lngRight'=>$longitude + self::LONGITUDE_RANGE,
														':ltdUp'=>$latitude - self::LATITUDE_RANGE,
														':ltdDown'=>$latitude + self::LATITUDE_RANGE,
														));
		$result = array();
		if (!empty($establishments)){
			foreach ($establishments as $e) {
				$result[$e->id]['id'] = $e->id;
				$result[$e->id]['name'] = $e->name;
				$result[$e->id]['address'] = $e->address;
				$result[$e->id]['longitude'] = $e->lng;
				$result[$e->id]['latitude'] = $e->ltd;
			}
		}
		$this->makeResult($result, 'establishments');
	}

	public function array_search_recursive($array, $key){
		foreach ($array as $name=>$val){
			if ($name === $key){
				return $val;
			}else if (is_array($val)){
				if ($found = $this->array_search_recursive($val, $key)){
					return $found;
				}
			}
		}
		return false;
	}

	public function makeResult($data=null, $tag=null){
		$result['result'] = 'ok';
		if ($tag){
			$result[$tag] = $data;
		}
		echo CJSON::encode($result);
		Yii::app()->end();
	}

	public function makeError($message){
		$result = array();
		$result['result'] = 'error';
		$result['message'] = $message;
		echo CJSON::encode($result);
		Yii::app()->end();
	}

	public function checkKey($key, $user, $time){
		if (!isset($user))
			return false;
		$keyToCheck = md5($user->mac_address.'adding'.$time.'125string');
		return $keyToCheck == $key;
	}

	public function checkRequest(){
		if (!empty($_POST['token']) && !empty($_POST['user_id'])){
			return true;
		}else{
			$this->makeError('Unauthorized');
		}
	}
}
