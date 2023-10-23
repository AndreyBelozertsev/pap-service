<?php
namespace Services\Telegraph;


use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\AmoSendClientInfo;
use Domain\Client\Models\Client;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\DTO\InlineQuery;
use DefStudio\Telegraph\Keyboard\Button;
use Services\Telegraph\DTO\ChatJoinQuery;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Services\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use Services\AmoCRM\WebHooks\NewTelegramUser;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Exceptions\TelegramWebhookException;
use Services\Telegraph\Facade\TelegraphCustom as TelegraphCustomFacade;


class BotWebhookHandler extends WebhookHandler
{

    protected array $queryClients = [
        'Введите ваш email адрес:' => 'setClientEmail',
        'Введите ваш телефон:' => 'setClientPhone',
    ];  

    protected array $requireClientFields = [
        'email' => 'getClientEmail',
        'phone' => 'getClientPhone',
    ]; 

    public function handle(Request $request, \DefStudio\Telegraph\Models\TelegraphBot $bot): void
    {
        Log::debug($request);
        parent::handle($request, $bot);

        if ($this->request->has('chat_join_request')) {
            /* @phpstan-ignore-next-line */

            $this->handleChatJoinQuery(ChatJoinQuery::fromArray($this->request->input('chat_join_request')));
        }

    }

    protected function handleChatJoinQuery(ChatJoinQuery $chatJoinQuery): void
    {
        $chat_id = $chatJoinQuery->chat()->id();
        $user_id = $chatJoinQuery->from()->id();
        
        $telegraphChat = TelegraphChat::with('client')->whereHas('client', function($q) use($user_id){
                    $q->where('telegram_id', $user_id);
                })->first();

        if(!$telegraphChat || !($chat_id == config('constant.telegram_group_id'))){
            return;
        }

        TelegraphCustomFacade::approveChatJoin( $chat_id, $user_id )->send();
        $telegraphChat->html("Ваша заявка одобрена!"
        )->keyboard(function(Keyboard $keyboard) use($chatJoinQuery){
            return $keyboard
                ->button('В канал')->url(config('constant.telegram_group_link'));
        })->send();
        //do webHook to AmoCRM
        AmoSendClientInfo::dispatch($telegraphChat->client);

    }
    
    public function start(): void
    {

        $this->setClient();

        $this->chat->html("Добро пожаловать!
                 \nЗдесь будет ваш произвольный текст: 
                 \nТекст может быть на несколько строк!!! 
            ")
            ->send();
    
        $this->nextAction();
    }



    public function testHook(): void
    {
        $telegraphChat = TelegraphChat::where('chat_id', config('constant.telegram_group_id'))->first();

        // $link = $telegraphChat->createInviteLink()    
        //     ->withJoinRequest()      
        //     ->send();
        

        $this->chat->message(config('constant.telegram_group_link'))
            ->send();
    }

    public function success(): void
    {
        $this->chat->html("Спасибо за регистрацию
            \nОтправляем Вам приглашение на закрытый канал, заявки принмаются автоматически!
        ")
            ->keyboard(function(Keyboard $keyboard){
                return $keyboard
                    ->button('Перейти в канал')->url(config('constant.telegram_group_link'));
            })
            ->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        if (  $this->message->replyToMessage()->text() !== null  && isset ( $this->queryClients[$this->message->replyToMessage()->text()] ) ){
            $this->{$this->queryClients[$this->message->replyToMessage()->text()]}();
        }
    }

    protected function setClient()
    {
        $message = Str::of($this->message?->text() ?? '');
        $source = null;
        if($message->contains(config('constant.telegram_source_expression'))){
            $source = (string) trim($message->after(config('constant.telegram_source_expression')) ?? '');
        }

        $this->chat->client()->updateOrCreate([
            'telegraph_chat_id' => $this->chat->id
            ],
            [
                'telegram_id' => $this->message->from()->id(),
                'username' => $this->message->from()->username(),
                'first_name' => $this->message->from()->firstName(),
                'last_name' => $this->message->from()->lastName(),
                'source' => $source,
            ]
        );
    }

    public function setClientEmail(): void
    {
        $this->message->text();
        $validator = validator(
            ['email'=>$this->message->text()],
            ['email'=>'required|email:rfc,dns']
        );

        if ($validator->fails()) {
            $this->chat->message("Email адрес введен некорректно")->send();
            $this->getClientEmail();
            return;
        }

        $this->chat->client()->update(['email' => $this->message->text()]);

        $this->nextAction();

    }

    public function getClientEmail(): void
    {
        $this->chat->message("Введите ваш email адрес:")->forceReply(placeholder: 'Введите email')->send();
    }

    public function setClientPhone(): void
    {
        $phone = preg_replace("/[^0-9]/", '', $this->message->text());
        $this->message->text();
        $validator = validator(
            ['phone'=>$phone],
            ['phone'=> 'required|digits_between:11,13']
        );

        if ($validator->fails()) {
            $this->chat->message("Телефон введен некорректно")->send();
            $this->getClientPhone();
            return;
        }

        $this->chat->client()->update(['phone' => $phone]);

        $this->nextAction();
    }

    public function getClientPhone(): void
    {
        $this->chat->message("Введите ваш телефон:")->forceReply(placeholder: 'Введите номер телефона')->send();
    }

    public function nextAction(): void
    {
        foreach($this->requireClientFields as $field => $action){
            if(empty($this->chat->client->{$field})){
                $this->{$action}();
                return;
            }
        }
        $this->success();
    }
}