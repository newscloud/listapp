<?php
class Mailgun extends CComponent
{

	/*
	 Mailgun Library Functions
	*/	
  private function setup_curl($command = 'messages') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:'.Yii::app()->params['mailgun']['api_key']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/'.Yii::app()->params['mail_domain'].'/'.$command);  
    return $ch;   
  }
  
  private function setup_curl_lists($command = 'lists', $custom_request ='POST') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:'.Yii::app()->params['mailgun']['api_key']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_request);
    return $ch;   
  }
  
  public function mail($from ='support@yourdomain.com', $to ='',$subject='',$message='',$headers='') {
    $ch = $this->setup_curl('messages');
      curl_setopt($ch, CURLOPT_POSTFIELDS, array('from' => $from,
                                                 'to' => $to,
                                                 'subject' => $subject,
                                                 'text' => $message,
                                                 'o:tracking' => false
                                                 ));

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }

	public function php_mail($email ='',$subject='',$message='',$headers='') {
    $ch = $this->setup_curl('messages');
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('from' => Yii::app()->params['supportEmail'],
                                               'to' => $email,
                                               'subject' => $subject,
                                               'text' => $message,
                                               'o:tracking' => false
                                               ));

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }
  
  public function send_simple_message($to='',$subject='',$body='',$from='') {
    if ($from == '') 
      $from = Yii::app()->params['supportEmail'];
    $ch = $this->setup_curl('messages');

    curl_setopt($ch, CURLOPT_POSTFIELDS, array('from' => $from,
                                               'to' => $to,
                                               'subject' => $subject,
                                               'text' => $body,
                                               'o:tracking' => false,
                                               ));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }	

  public function fetchLists() {
    $ch = $this->setup_curl_lists('lists','GET');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists');  
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;    
  }

  public function fetchListMembers($address) {
    $ch = $this->setup_curl_lists('lists','GET');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$address.'/members');  
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;    
  }

  public function listCreate($newlist) {
    $ch = $this->setup_curl_lists('lists');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists');  
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('address' => $newlist->address,
                                                  'name'=>$newlist->name,
                                                 'description' => $newlist->description,
                                                 'access_level' => $newlist->access_level
                                                 ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;    
  }
  
  public function listDelete($address='') {
    $ch = $this->setup_curl('lists');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$address);             
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;    
  }
  
  public function listUpdate($existing_address,$model) {
     $ch = $this->setup_curl('lists');
     curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$existing_address);  
     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
       curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'address'=>$model->address,
        'name' => $model->name,
        'description' => $model->description,
        'access_level' => $model->access_level
       ));  
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;    
   }  

   public function memberBulkAdd($list='',$json_str='') {
     $ch = $this->setup_curl('lists');
     curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$list.'/members.json');  
     curl_setopt($ch, CURLOPT_POSTFIELDS, array('members' => $json_str,
                                                  'subscribed' => true,
                                                  'upsert' => 'yes'
                                                  ));    
      $result = curl_exec($ch);
      curl_close($ch);      
      return $result;    
   }

  
  public function memberAdd($list='',$email='',$name='') {
    $ch = $this->setup_curl_lists('lists');
    curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$list.'/members');
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('address' => $email,
                                                 'name' => $name,
                                                 'subscribed' => true,
                                                 'upsert' => 'yes'
                                                 ));    
     $result = curl_exec($ch);
     curl_close($ch);
     return $result;    
  }
  
  public function memberUpdate($list='',$email='',$propList) {
     $ch = $this->setup_curl('lists');
     curl_setopt($ch, CURLOPT_URL, Yii::app()->params['mailgun']['api_url'].'/lists/'.$list.'@yourdomain.com/members/'.$email);  
     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
       curl_setopt($ch, CURLOPT_POSTFIELDS, $propList);  
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;    
   }
   
   public function memberUnsubscribe($list='',$email='') {
     $propList = array('subscribed'=>false);
     $result=$this->memberUpdate($list,$email,$propList);
   }

   function validate($email='') {
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
     curl_setopt($ch, CURLOPT_USERPWD, 'api:'.Yii::app()->params['mailgun']['public_key']);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     $address_string = urlencode($email);
     curl_setopt($ch,CURLOPT_URL,
         'https://api.mailgun.net/v2/address/validate?address='.$address_string);
     $result = curl_exec($ch);
     curl_close($ch);
     return $result;
   }      
}

?>