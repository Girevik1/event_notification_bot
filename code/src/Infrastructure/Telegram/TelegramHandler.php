<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Telegram;

use Art\Code\Domain\Contract\TelegramHandlerInterface;
use Art\Code\Domain\Dto\DataEditMessageDto;
use CurlHandle;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class TelegramHandler implements TelegramHandlerInterface
{
    public Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $telegramConfig = require '../config/telegram.php';
        $this->telegram = new Api($telegramConfig['TELEGRAM_BOT_TOKEN']);
    }

    /**
     * @param string $telegramChatId
     * @param string $text
     * @param string $typeBtn
     * @param int $replyToMessageId
     * @return mixed
     * @throws TelegramSDKException
     */
    public static function sendMessage(
        string $telegramChatId,
        string $text,
        string $typeBtn = '',
        int    $replyToMessageId = 0): mixed
    {
        $thisObj = new self();

        $dataForSend = [
            'chat_id' => $telegramChatId,
            'parse_mode' => 'HTML',
            'text' => $text,
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
        $textArray = [$dataEditMessage->text];

        if (mb_strlen($dataEditMessage->text, '8bit') > 4096) {
            $textArray = [];
            $start = 0;
            do {
                $textArray[] = mb_strcut($dataEditMessage->text, $start, 4096);
                $start += 4096;
            } while (mb_strlen($dataEditMessage->text, '8bit') > $start);
        }
        $thisObj = new self();

        foreach ($textArray as $textItem) {

            if ($textItem == end($textArray)) {

                $thisObj->telegram->editMessageText([
                    'chat_id' => $dataEditMessage->chat_id,
                    'message_id' => $dataEditMessage->message_id,
                    'text' => $dataEditMessage->text,
                    'reply_markup' => self::getKeyboard($dataEditMessage->keyboard, $dataEditMessage->keyboardData),
                    'parse_mode' => 'HTML',
                ]);
            }

            self::sendMessage($dataEditMessage->chat_id, $textItem);
        }
    }

    /**
     * @param string $telegram_chat_id
     * @param int $msg_id
     * @return CurlHandle|bool
     */
    public static function deleteMessage(string $telegram_chat_id, int $msg_id): CurlHandle|bool
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
     * @param mixed|string $keyboardData
     * @return bool|string
     */
    public static function getKeyboard(string $type, mixed $keyboardData = ''): bool|string
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
                                'text' => '👥 в группе (доступны: ' . $keyboardData . ')',
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
//                        [
//                            [
//                                'text' => '➕ Добавить годовщину',
//                                'callback_data' => 'add_anniversary',
//                            ],
//                        ],
//                        [
//                            [
//                                'text' => ' ➕ Добавить заметку',
//                                'callback_data' => 'add_note',
//                            ],
//                        ],
                        [
                            [
                                'text' => '📝 Список ваших событий',
                                'callback_data' => 'list_events',
                            ],
                        ],
                        [
                            [
                                'text' => '📋 Список добавленных групп',
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