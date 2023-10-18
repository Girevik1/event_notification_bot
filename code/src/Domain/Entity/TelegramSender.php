<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Art\Code\Infrastructure\Repository\TelegramUserRepository;
use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramSender extends Model
{
    public TelegramUserRepository $telegramUserRepository;

//    public function __construct(public TelegramUserRepositoryInterface $telegramUserRepository, array $attributes = [])
    public function __construct(array $attributes = [])
    {
        $this->telegramUserRepository = new TelegramUserRepository();
        parent::__construct($attributes);
    }

    /**
     * @throws TelegramSDKException
     */
    public static function sendMessage($login, $message, string $typeBtn = '', $replyToMessageId = '')
    {
        $thisObj = new self();
        $user = $thisObj->telegramUserRepository->firstByLogin($login);

        $dataForSend = [
            'chat_id' => $user->telegram_chat_id,
            'parse_mode' => 'HTML',
            'text' => $message,
            'reply_to_message_id' => $replyToMessageId
        ];

        if ($typeBtn) {
//            $dataForSend = self::getKeyboard($dataForSend, $typeBtn);
            $dataForSend['reply_markup'] = self::getKeyboard($typeBtn);
        }

        $telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);
        $response = $telegram->sendMessage($dataForSend);

        return $response->getMessageId();
    }

    public function deleteMessage($login, $msg_id): string
    {
        $user = $this->telegramUserRepository->firstByLogin($login);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/deleteMessage?chat_id=" . $user->telegram_chat_id . "&message_id=" . $msg_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
        return "";
    }

    public static function getKeyboard(string $type): bool|string
    {
        switch ($type) {
            case "to_the_beginning":
                $keyboard = json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '🔙 В начало',
                                    'callback_data' => 'to_the_beginning',
                                ],
                            ]

                        ],
                    ],
                );
                break;

            case "main_menu":
                $keyboard = json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '❔О проекте',
                                    'callback_data' => 'about_project',
                                ],
                            ],
                            [
                                [
                                    'text' => '❔Что я могу',
                                    'callback_data' => 'what_can_bot',
                                ],
                            ],
                            [
                                [
                                    'text' => '❔Как меня использовать',
                                    'callback_data' => 'how_use',
                                ],
                            ],
                            [
                                [
                                    'text' => '🏠 Личный кабинет',
                                    'callback_data' => 'private_cabinet',
                                ],
                            ],
                        ],
                    ],
                );
                break;
            case "cfo-acception-procedure-agreement-skip-off":
                $keyboard = json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '➖',
                                    // 'text' => '❌',
                                    'callback_data' => 'ceo-proc-minus',
                                ],
                                [
                                    'text' => '➕',
                                    // 'text' => '✅',
                                    'callback_data' => 'ceo-proc-plus',
                                ],
                            ],
                        ],
                    ],
                );
                break;
            default:
                break;
        }

        return $keyboard;
    }
}