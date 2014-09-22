<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Mailchimp.php';

/**
 * MailChimp API integration (version 1.3)
 * @link http://apidocs.mailchimp.com/api/1.3/
 */
class MailChimpApi extends CApplicationComponent
{
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_CLEANED = 'cleaned';
    const STATUS_UPDATED = 'updated';
    
    /**
     * @var string API Key - see http://admin.mailchimp.com/account/api
     */
    public $apikey = '';
    
    
    public $ecommerce360Enabled = false;
    public $devMode = false;
    
    public $mc = null;
    
    
    public function init()
    {
        $this->mc = new MailChimp($this->apikey);
    }

    /**
     * Retrieve all of the lists defined for your user account
     * @return mixed
     */
    public function getLists()
    {
        return $this->mc->lists->getList();
    }

    /**
     * Subscribe the provided email to a list.
     * @param $listId
     * @param $email
     * @param $params
     * @return bool
     */
    public function listSubscribe($listId, $email, $params)
    {
        $retval = $this->mc->lists->subscribe($listId, $email, $params);
        return $this->mc->errorCode === false;
    }

    /**
     * Subscribe a batch of email addresses to a list at once.
     * @param $listId
     * @param $batch
     * @return bool
     */
    public function listBatchSubscribe($listId, $batch)
    {
        $retval = $this->mc->lists->batchSubscribe($listId, $batch);
        return $this->mc->errorCode === false;
    }

    /**
     * Add a new merge tag to a given list
     * @param $listId
     * @param $tag
     * @param $name
     * @param array $options
     * @return bool
     */
    public function listMergeVarAdd($listId, $tag, $name, $options=array())
    {
        $retval = $this->mc->lists->mergeVarAdd($listId, $tag, $name, $options);
        return $this->mc->errorCode === false;
    }

    /**
     * Delete a merge tag from a given list and all its members.
     * @param $id
     * @param $tag
     * @return bool
     */
    public function listMergeVarDel($id, $tag)
    {
        $retval = $this->mc->lists->mergeVarDel($id, $tag);
        return $this->mc->errorCode === false;
    }
}