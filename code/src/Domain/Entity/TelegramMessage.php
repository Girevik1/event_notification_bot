<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

//use Art\Code\Domain\ValueObject\TelegramUser\TelegramUserId;
use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $table = 'telegram_message';

    protected $guarded = [];

    public function setDataTestAttribute($value): void
    {
        $this->attributes['data_test'] = json_encode($value);
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