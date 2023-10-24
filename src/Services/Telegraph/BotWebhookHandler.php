<?php
namespace Services\Telegraph;


use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\AmoSendClientInfo;
use Domain\Client\Models\Client;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Support\Facades\Storage;
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
        'Введите ваше имя:' => 'setClientName',
        'Введите ваш email адрес:' => 'setClientEmail',
        'Введите ваш телефон:' => 'setClientPhone',
    ];  

    protected array $requireClientFields = [
        'name' => 'getClientName',
        'email' => 'getClientEmail',
        'phone' => 'getClientPhone',
    ]; 

    public function handle(Request $request, \DefStudio\Telegraph\Models\TelegraphBot $bot): void
    {
        Log::build(['driver' => 'single', 'path' => storage_path('logs/telegram-webhook.log')])->info($request);
        parent::handle($request, $bot);

        if ($this->request->has('chat_join_request')) {
            /* @phpstan-ignore-next-line */

            $this->handleChatJoinQuery(ChatJoinQuery::fromArray($this->request->input('chat_join_request')));
            
        }

    }

    protected function handleChatJoinQuery(ChatJoinQuery $chatJoinQuery)
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
        return;

    }

    public function start(): void
    {

        $this->setClient();

        $this->chat->photo('https://test.rakurs82.ru/public/storage/telegram/first_message.png')
        ->html("Мы помогаем творцам <b>легко монетизировать контент</b> в любой точке мира, поддерживая вашу страсть к созданию и распространению уникального контента. 
        \nВсё это часть движения <b>Papaya</b> за экономическую свободу создателей! С помощью блокчейна, мы обеспечиваем <b>мгновенные выплаты с минимальными комиссиями</b>.
        ")
        ->keyboard(Keyboard::make()->buttons([
            Button::make('🌟 Узнать больше о Papaya')->action('info'),
            Button::make('🚀 Начать регистрацию')->action('getAgree'),
        ]))
        ->send();
    }

    public function info()
    {
        $this->chat->photo('')
        ->html("Ты можешь <b>продавать фото и видео</b>, подписку на свой канал, или <b>монетизировать стримы</b> в реальном времени. 
        \nНачать просто — тебе нужен только <b>криптокошелек</b>. 
        \nГотов стать частью этого захватывающего путешествия? <b>Напиши свое имя, email и номер телефона</b> для регистрации, и мы предоставим тебе доступ к нашему закрытому телеграм-каналу. 
        \nТам начнется твое удивительное путешествие в мир <b>Papaya</b>! 🚀
         ")
        ->keyboard(Keyboard::make()->buttons([
            Button::make('🚀 Начать регистрацию')->action('getAgree'),
        ]))
        ->send();
    
    }

    public function getAgree()
    {
        $this->chat->html("📝 Для продолжения, пожалуйста, подтверди свое согласие на обработку персональных данных. Мы уважаем твою конфиденциальность и используем твои данные исключительно для создания лучшего опыта для тебя в рамках нашего сообщества. Подробнее о нашей <a href='https://papaya.land/documents/Papaya%20Privacy%20Policy.pdf'>политике конфиденциальности</a>.
            \nМы ценим твой выбор и готовы поддержать тебя на каждом этапе твоего пути! 🌿")
            ->keyboard(Keyboard::make()->buttons([
                    Button::make('✅ Согласен')->action('agree'),
                    Button::make('❌ Не согласен')->action('disagree'),
            ]))->send();
    }

    public function handleChatMessage(Stringable $text): void
    {
        $reply = $this->message->replyToMessage();
        if ( $reply  && isset ( $this->queryClients[$this->message->replyToMessage()->text()] ) ){
            $this->{$this->queryClients[$this->message->replyToMessage()->text()]}();
        }
    }

    public function agree()
    {
        $this->nextAction();
    }

    public function disagree()
    {
        $this->chat->html("Жаль, что в этот раз Вы не смогли к нам присоединиться.\nМы будем рады видеть у нас вновь")
        ->keyboard(Keyboard::make()->buttons([
                Button::make('Проголосовать еще раз')->action('getAgree'),
        ]))->send();
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

    public function setClientName(): void
    {
        $this->message->text();
        $validator = validator(
            ['name'=>$this->message->text()],
            ['name'=>'required']
        );

        if ($validator->fails()) {
            $this->chat->message("Имя введено некорректно")->send();
            $this->getClientName();
            return;
        }

        $this->chat->client()->update(['name' => $this->message->text()]);

        $this->nextAction();

    }

    public function getClientName(): void
    {
        $this->chat->message("Введите ваше имя:")->forceReply(placeholder: 'Введите имя')->send();
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

    public function success(): void
    {
        $this->chat->html("Спасибо за регистрацию!
            \nОтправляем Вам приглашение на закрытый канал, заявки принимаются автоматически!
            ")
            ->keyboard(function(Keyboard $keyboard){
                return $keyboard
                    ->button('Перейти в канал')->url(config('constant.telegram_group_link'));
            })
            ->send();
    }


}