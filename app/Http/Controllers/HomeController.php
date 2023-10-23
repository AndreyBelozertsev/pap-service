<?php

namespace App\Http\Controllers;

use AmoCRM\Models\TagModel;
use Illuminate\Http\Request;
use AmoCRM\Filters\TagsFilter;
use AmoCRM\Models\ContactModel;
use Domain\Client\Models\Client;
use AmoCRM\Filters\ContactsFilter;
use Illuminate\Support\Facades\Log;
use Services\AmoCRM\Facades\AmoCrm;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;


class HomeController extends Controller
{
    public function index(Request $request)
    {
        $contact = $this->getContact();
        if(!$contact){
            return false;
        }
        $this->updateContactInfo($contact);

        return view('welcome');
    }

    protected $customer;

    public function __construct(){
        $this->customer = Client::first();
    }

    public function getAmoClient()
    {
        return AmoCrm::getAuthClient();
    }



    protected function getLeadTag()
    {
        $tagfilter = new TagsFilter();
        $tagfilter->setQuery(config('constant.amo_crm_lead_tag_name'))->setLimit(1);
                    
        try {
            return $this->getAmoClient()->tags('leads')->get($tagfilter)->first(); 
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
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
            $tag = $tagsService->add($tagsCollection);
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
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
            Log::debug($e);
        }
        return $this->createContactTag();
    }

    protected function createContactTag()
    {
        $tagsCollection = new TagsCollection();
        $tag = new TagModel();
        $tag->setName(config('constant.amo_crm_contact_tag_name'));
        $tagsCollection->add($tag);


        try {
            $tagsService = $this->getAmoClient()->tags(EntityTypesInterface::CONTACTS);
            $tagsService->add($tagsCollection);    
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
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
            Log::debug($e);
        }

        if(!isset($contact)){
            $contactFilter->setQuery(substr($this->customer->phone, -10));
            try {
                $contact = $this->getAmoClient()->contacts()->get($contactFilter)->first(); 
            } catch (AmoCRMApiException $e) {
                Log::debug($e);
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
            Log::debug($e);
        }
        if(isset($contact)){
            return $contact;
        }
        return false;
    }

    protected function setContactTag($contact)
    {
        $tagsCollection = new TagsCollection;
        $tagsCollection->add($this->getContactTag());
        $contact->setTags($tagsCollection);

        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
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

    protected function updateContactInfo($contact)
    {
        //Получим коллекцию значений полей контакта
        $customFields = $contact->getCustomFieldsValues();
        if(!$customFields){
            $this->setContactInfo($contact, $this->customer->email);  
            return;
        }
        //Получим значение поля по его коду
        $phoneField = $customFields->getBy('fieldCode', 'PHONE');
        $emailField = $customFields->getBy('fieldCode', 'EMAIL');
        $telegramIdField = $customFields->getBy('fieldName', 'TelegramID');

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
            $telegramIdField = (new MultitextCustomFieldValuesModel())->setFieldName('TelegramID');
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
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                    ->setValue($this->customer->telegram_id)
                )
        );

        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
        }   
    }

    protected function setContactInfo($contact)
    {
        $customFieldsValuesCollection = new CustomFieldsValuesCollection();

        $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
        $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');

        $phoneCustomFieldValueCollection = new MultitextCustomFieldValueCollection();
        $emailFieldValueCollection = new MultitextCustomFieldValueCollection();

        $phoneCustomFieldModel = new MultitextCustomFieldValueModel();
        $phoneCustomFieldModel->setValue($this->customer->phone)->setEnum('WORK');
        $phoneCustomFieldValueCollection->add($phoneCustomFieldModel);

        $emailCustomFieldModel = new MultitextCustomFieldValueModel();
        $emailCustomFieldModel->setValue($this->customer->email)->setEnum('WORK');
        $emailFieldValueCollection->add($emailCustomFieldModel);

        $phoneField->setValues($phoneCustomFieldValueCollection);
        $emailField->setValues($emailFieldValueCollection);
        $customFieldsValuesCollection->add($phoneField)->add($emailField);

        $contact->setCustomFieldsValues($customFieldsValuesCollection);

        try {
            $this->getAmoClient()->contacts()->updateOne($contact);
        } catch (AmoCRMApiException $e) {
            Log::debug($e);
        }
    
    }
}