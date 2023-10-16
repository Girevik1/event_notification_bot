<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

//use Art\Code\Domain\ValueObject\TelegramUser\TelegramUserId;
use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Infrastructure\Repository\TelegramMessageRepository;
use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $table = 'telegram_message';

    protected $guarded = [];

    private TelegramMessageRepository $telegramMessageRepository;

    public function __construct(array $attributes = [])
//    public function __construct(public TelegramMessageRepositoryInterface $telegramMessageRepository, array $attributes = [])
    {
        $this->telegramMessageRepository = new TelegramMessageRepository();
        parent::__construct($attributes);
    }

    public function setDataTestAttribute($value): void
    {
        $this->attributes['data_test'] = json_encode($value);
    }

    public static function newMessage(
        $user,
        $text,
        $command,
        $model = "",
        $model_id = 0,
        $reply_to_message = [],
//        $is_from_bot = 1,
//        $author = 0,
        $typeBtn = ''
    ): void
    {
        $thisObj = new self();
        $text_array = [$text];

        if (mb_strlen($text, '8bit') > 4096) {
            $text_array = [];
            $start = 0;
            do {
                $text_array[] = mb_strcut($text, $start, 4096);
                $start += 4096;
            } while (mb_strlen($text, '8bit') > $start);
        }

        try {
            foreach ($text_array as $textItem) {
                if (
                    $_ENV['APP_ENV'] == 'prod' ||
                    $_ENV['APP_ENV'] == 'dev'
                ) {
                    $msg_id = TelegramSender::sendMessage($user->login, $textItem, $typeBtn);
                } else {
                    $last_message = $thisObj->telegramMessageRepository->getLastMessage();
                    if ($last_message) {
                        $msg_id = 1000001 + $last_message->message_id;
                    } else {
                        $msg_id = 1000000;
                    }
                }
                $message = new TelegramMessage();
                $message->telegram_user_id = $user->id;
//                $message->is_from_bot = $is_from_bot;
                $message->message_id = $msg_id;
                $message->text = $textItem;
                if (count($reply_to_message) > 0) {
                    $message->reply_to = $reply_to_message['message_id'];
                } else {
                    $message->reply_to = 0;
                }
//                $message->author = $author;

                $message->command = $command;
                $message->model = $model;
                $message->is_deleted_from_chat = 0;
                $message->model_id = $model_id;
                $message->data_test = null;
                $message->save();
            }
//            echo '<pre>';
//            echo $user->login;
//            echo '<pre>';
//            echo $textItem;
//            echo '<pre>';
//            echo $typeBtn;
//            echo '<pre>';
        } catch (\Exception $e) {
        }
    }
//    private ?string $data_test;

//    public function __construct(
//        protected  TelegramUserId $telegram_user_id,
//        protected  string         $text,
//        array                     $data_test,
//        protected  string         $telegram_chat_id
//    )
//    {
//        $this->data_test = json_encode($data_test);
//        parent::__construct();
//    }

//    public function getUserId(): TelegramUserId
//    {
//        return $this->telegram_user_id;
//    }
//
//    public function getText(): TelegramUserId
//    {
//        return $this->telegram_user_id;
//    }
}