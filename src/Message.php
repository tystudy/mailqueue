<?php
namespace wilson\mailerqueue;

use Yii;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config.');
        }
        // 0 - 15  select 0 select 1
        // db => 1
        $mailer = Yii::$app->mailer;//实例化mailer
        if (empty($mailer) || !$redis->select($mailer->db)) {//使用配置中的redis 1库
            throw new \yii\base\InvalidConfigException('db not defined.');
        }
        $message = [];
        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
       // $message['cc'] = array_keys($this->getCc());
       // $message['bcc'] = array_keys($this->getBcc());
       // $message['reply_to'] = array_keys($this->getReplyTo());
       // $message['charset'] = array_keys($this->getCharset());
       // $message['subject'] = array_keys($this->getSubject());
		
       $message['subject'] ='诚泽知识产权：专利申请';
        $parts = $this->getSwiftMessage()->getChildren();//获取邮件信息子信息，回复的层阶关系
        if (!is_array($parts) || !sizeof($parts)) {//没有任何回复的
            $parts = [$this->getSwiftMessage()];
        }
        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {//如果不是这个类的子集，说明是内容
                switch($part->getContentType()) {//发送内容类型
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain'://纯文本
                        $message['text_body'] = $part->getBody();
                        break;
                }
      //          if (!$message['charset']) {//如果charset没拿到，可以用属性
      //              $message['charset'] = $part->getCharset();
      //          }
            }
        }
        return $redis->rpush($mailer->key, json_encode($message));
    }
}
