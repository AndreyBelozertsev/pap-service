<?php
namespace Services\AmoCRM\WebHooks;

use AmoCRM\Models\TagModel;
use AmoCRM\Models\LeadModel;
use Illuminate\Http\Request;
use AmoCRM\Filters\TagsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\ContactModel;
use Domain\Client\Models\Client;
use AmoCRM\Filters\ContactsFilter;
use Illuminate\Support\Facades\Log;
use Services\AmoCRM\Facades\AmoCrm;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;


class NewTelegramUser 
{

    protected $customer;

    public function __construct(Client $customer){
        $this->customer = $customer;
    }

    public function getAmoClient()
    {
        return AmoCrm::getAuthClient();
    }

    public function index()
    {
            $contact = $this->getContact();
            if(!$contact){
                return false;
            }
            $this->updateContactInfo($contact);
            $lead = $this->getLead($contact);
            $this->leadStatusUpdate($lead);
            return true;

    }


    protected function getLeadTag()
    {
        $tagfilter = new TagsFilter();
        $tagfilter->setQuery(config('constant.amo_crm_lead_tag_name'))->setLimit(1);
                    
        try {
            return $this->getAmoClient()->tags('leads')->get($tagfilter)->first(); 
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }
        return $this->createLeadTag();
    }

    protected function createLeadTag()
    {
        $tagsCollection = new TagsCollection();
        $tag = new TagModel();
        $tag->setName(config('constant.amo_crm_lead_tag_name'));
        $tagsCollection->add($tag);
        $tagsService = $this->getAmoClient()->tags(EntityTypesInterface::LEADS);

        try {
            $tagsService->add($tagsCollection);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

        return $tag;
    
    }

    protected function getContactTag()
    {
        $tagfilter = new TagsFilter();
        $tagfilter->setQuery(config('constant.amo_crm_contact_tag_name'))->setLimit(1);
                    
        try {
            return $this->getAmoClient()->tags('contacts')->get($tagfilter)->first(); 
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }
        return $this->createContactTag();
    }

    protected function createContactTag()
    {
        $tagsCollection = new TagsCollection();
        $tag = new TagModel();
        $tag->setName(config('constant.amo_crm_contact_tag_name'));
        $tagsCollection->add($tag);

        $tagsService = $this->getAmoClient()->tags(EntityTypesInterface::CONTACTS);

        try {
            $tagsService->add($tagsCollection);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

        return $tag;
    }

    protected function getContact()
    {
        $contactFilter = new ContactsFilter();
        $contactFilter->setQuery($this->customer->email)->setLimit(1);

        try {
            $contact = $this->getAmoClient()->contacts()->get($contactFilter)->first();
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

        if(!isset($contact)){
            $contactFilter->setQuery(substr($this->customer->phone, -10));
            try {
                $contact = $this->getAmoClient()->contacts()->get($contactFilter)->first(); 
            } catch (AmoCRMApiException $e) {
                Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
            }
        }

        if(isset($contact)){
            return $contact;
        }

        return $this->contactCreate();
    
    }

    protected function contactCreate()
    {
        $contactModel = new ContactModel();
        $contactModel->setName($this->generateContactName());

        try {
            $contact = $this->getAmoClient()->contacts()->addOne($contactModel);
            $this->setContactTag($contact);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }
        if(isset($contact)){
            return $contact;
        }
        return false;
    }

    protected function updateContactInfo($contact)
    {
        //Получим коллекцию значений полей контакта
        $customFields = $contact->getCustomFieldsValues();
   
        if(!$customFields){
            $this->setContactInfo($contact);  
            return;
        }
        //Получим значение поля по его коду
        $phoneField = $customFields->getBy('fieldCode', 'PHONE');
        $emailField = $customFields->getBy('fieldCode', 'EMAIL');
        $telegramIdField = $customFields->getBy('fieldId', config('constant.amo_crm_telegram_id_field_id'));

        //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
        if (empty($phoneField)) {
            $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
            $customFields->add($phoneField);
        }

        if (empty($emailField)) {
            $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
            $customFields->add($emailField);
        }

        if (empty($telegramIdField)) {
            $telegramIdField = (new TextCustomFieldValuesModel())->setFieldId(config('constant.amo_crm_telegram_id_field_id'));
            $customFields->add($telegramIdField);
        }


        //Установим значение поля
        $phoneField->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORK')
                        ->setValue($this->customer->phone)
                )
        );
        
        $emailField->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORK')
                        ->setValue($this->customer->email)
                )
        );

        $telegramIdField->setValues(
            (new TextCustomFieldValueCollection())
                ->add(
                    (new TextCustomFieldValueModel())
                    ->setValue($this->customer->telegram_id)
                )
        );


        $customFields->add($phoneField)->add($emailField)->add($telegramIdField);

        $contact->setCustomFieldsValues($customFields);

        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }   
    }

    protected function setContactInfo($contact)
    {
        $customFieldsValuesCollection = new CustomFieldsValuesCollection();

        $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
        $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
        $telegramIdField = (new TextCustomFieldValuesModel())->setFieldID(config('constant.amo_crm_telegram_id_field_id'));

        $phoneCustomFieldValueCollection = new MultitextCustomFieldValueCollection();
        $emailFieldValueCollection = new MultitextCustomFieldValueCollection();
        $telegramIdFieldValueCollection = new MultitextCustomFieldValueCollection();

        $phoneCustomFieldModel = new MultitextCustomFieldValueModel();
        $phoneCustomFieldModel->setValue($this->customer->phone)->setEnum('WORK');
        $phoneCustomFieldValueCollection->add($phoneCustomFieldModel);

        $emailCustomFieldModel = new MultitextCustomFieldValueModel();
        $emailCustomFieldModel->setValue($this->customer->email)->setEnum('WORK');
        $emailFieldValueCollection->add($emailCustomFieldModel);

        $telegramIdCustomFieldModel = new TextCustomFieldValueModel();
        $telegramIdCustomFieldModel->setValue($this->customer->telegram_id);
        $telegramIdFieldValueCollection->add($telegramIdCustomFieldModel);

        $phoneField->setValues($phoneCustomFieldValueCollection);
        $emailField->setValues($emailFieldValueCollection);
        $telegramIdField->setValues($telegramIdFieldValueCollection);
        $customFieldsValuesCollection->add($phoneField)->add($emailField)->add($telegramIdField);
       
        $contact->setCustomFieldsValues($customFieldsValuesCollection);
        
        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

    }

    protected function getLead($contact)
    {

        $leadFilter = new LeadsFilter();
        $leadFilter->setQuery($contact->getId())->setLimit(250);

        try {
            $leads = $this->getAmoClient()->leads()->get($leadFilter,['tags']); 
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }
        if(! isset($leads)){
            return $this->leadCreate($contact);
        }


        foreach($leads as $lead){
            if(!$lead->getTags()){
                continue;
            }
            if(!$lead->getTags()->getBy('name', config('constant.amo_crm_lead_tag_name'))){
                continue;
            }
            return $lead;
        }

        return $this->leadCreate($contact);
    }

    protected function leadCreate($contact)
    {
        $lead = new LeadModel();
        $lead->setName('Веб Papaya');
        $lead->setPipelineId(config('constant.amo_crm_pipeline_id'));
        $lead->setStatusId(config('constant.amo_crm_lead_status'));

        try {
            $lead = $this->getAmoClient()->leads()->addOne($lead);
            $this->setLeadTag($lead);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

        $links = new LinksCollection();
        $links->add($contact);
        try {
            $this->getAmoClient()->leads()->link($lead, $links);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }

        return $lead;
    
    }


    protected function leadStatusUpdate($lead)
    {
        $status = $this->getAmoClient()->statuses($lead->getPipelineId())->getOne($lead->getStatusId());
        if($status->getSort() < config('constant.amo_crm_lead_status_sort')){
            try {
                $this->getAmoClient()->leads()->updateOne($lead->setStatusId(config('constant.amo_crm_lead_status')));
            } catch (AmoCRMApiException $e) {
                Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
            } 
        }
    }

    protected function setContactTag($contact)
    {
        $tagsCollection = new TagsCollection;
        $tagsCollection->add($this->getContactTag());
        $contact->setTags($tagsCollection);

        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }
    }

    protected function setLeadTag($lead)
    {
        $tagsCollection = new TagsCollection;
        $tagsCollection->add($this->getLeadTag());
        $lead->setTags($tagsCollection);

        try {
            $this->getAmoClient()->leads()->updateOne($lead);
        } catch (AmoCRMApiException $e) {
            Log::build(['driver' => 'single', 'path' => storage_path('logs/amoCRM-webhook.log')])->debug($e);
        }  
    }

    protected function generateContactName():string
    {
        if($this->customer->first_name || $this->customer->last_name ){
            return trim("{$this->customer->first_name} {$this->customer->last_name}");
        }
        if($this->customer->username){
            return $this->customer->username;
        }
        return  $this->customer->telegram_id;
    }
}