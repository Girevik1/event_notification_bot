<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

use Art\Code\Domain\Dto\DataEditMessageDto;
use Art\Code\Infrastructure\Repository\TelegramUserRepository;
use CurlHandle;
use Illuminate\Database\Eloquent\Model;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramSender extends Model
{
    public TelegramUserRepository $telegramUserRepository;
    private Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(array $attributes = [])
    {
        $this->telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);
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
            $dataForSend['reply_markup'] = self::getKeyboard($typeBtn);
        }

        $response = $thisObj->telegram->sendMessage($dataForSend);

        return $response->getMessageId();
    }

    /**
     * @throws TelegramSDKException
     */
    public static function editMessageTextSend(DataEditMessageDto $dataEditMessage): void
    {
        $thisObj = new self();
        $thisObj->telegram->editMessageText([
            'chat_id' => $dataEditMessage->chat_id,
            'message_id' => $dataEditMessage->message_id,
            'text' => $dataEditMessage->text,
            'reply_markup' => TelegramSender::getKeyboard($dataEditMessage->keyboard, $dataEditMessage->keyboardData),
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * @param $telegram_chat_id
     * @param $msg_id
     * @return CurlHandle|bool
     */
    public static function deleteMessage($telegram_chat_id, $msg_id): CurlHandle|bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/deleteMessage?chat_id=" . $telegram_chat_id . "&message_id=" . $msg_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
        return $ch;
    }

    /**
     * @param string $type
     * @param mixed|null $keyboardData
     * @return bool|string
     */
    public static function getKeyboard(string $type, $keyboardData = ''): bool|string
    {
        return match ($type) {
            "process_set_event" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔙 назад',
                                'callback_data' => 'to_previous_question',
                            ],
                            [
                                'text' => '🙅 передумал',
                                'callback_data' => 'changed_my_mind',
                            ],
                        ],
                    ],
                ],
            ),
            "confirmation_event" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔙 назад',
                                'callback_data' => 'to_previous_question',
                            ],
                            [
                                'text' => '🙅 передумал',
                                'callback_data' => 'changed_my_mind',
                            ],
                            [
                                'text' => '✅ ok',
                                'callback_data' => 'confirm_event',
                            ],
                        ],
                    ],
                ],
            ),
            "notification_type" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔙 назад',
                                'callback_data' => 'to_previous_question',
                            ],
                            [
                                'text' => '🙅 передумал',
                                'callback_data' => 'changed_my_mind',
                            ]
                        ],
                        [
                            [
                                'text' => '👤 лично',
                                'callback_data' => 'personal_notice',
                            ],
                        ],
                        [
                            [
                                'text' => '👥 в группе (доступных: ' . $keyboardData . ')',
                                'callback_data' => 'group_notice',
                            ],
                        ],
                    ],
                ],
            ),
            "to_the_beginning" => json_encode(
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
            ),
            "to_the_settings_menu" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔙 В личный кабинет',
                                'callback_data' => 'settings_menu',
                            ],
                        ]

                    ],
                ],
            ),
            "main_menu" => json_encode(
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
            ),
            "settings_menu" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '➕ Добавить день рождение',
                                'callback_data' => 'add_birthday',
                            ],
                        ],
                        [
                            [
                                'text' => '➕ Добавить событие',
                                'callback_data' => 'add_event',
                            ],
                        ],
                        [
                            [
                                'text' => ' ➕ Добавить заметку',
                                'callback_data' => 'add_note',
                            ],
                        ],
                        [
                            [
                                'text' => '📝 Список Ваших событий',
                                'callback_data' => 'list_events',
                            ],
                        ],
                        [
                            [
                                'text' => '📋 Список доступных групп',
                                'callback_data' => 'list_groups',
                            ],
                        ],
                        [
                            [
                                'text' => '🔙 В начало',
                                'callback_data' => 'to_the_beginning',
                            ],
                        ]
                    ],
                ],
            ),
            default => false,
        };
    }
}