<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Telegram;

use Art\Code\Domain\Contract\TelegramHandlerInterface;
use Art\Code\Domain\Contract\TelegramMessageRepositoryInterface;
use Art\Code\Domain\Dto\DataEditMessageDto;
use Art\Code\Domain\Dto\TelegramMessageDto;
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
    private function newMessage($text, $chatId, $typeBtn, $telegramMessageRepository): void
    {
            $textArray = [];
            $start = 0;
            do {
                $textArray[] = mb_substr($text, $start, 4096);
//                $textArray[] = mb_strcut($text, $start, 7300);
                $start += 4096;
            } while (mb_strlen($text, 'UTF-8') > $start);
//            } while (mb_strlen($text, '8bit') > $start);

//        do {
//            //Chop off and send the first message
//            $data['text'] = mb_substr($text, 0, 4096);
//            $response     = self::send('sendMessage', $data);
//
//            //Prepare the next message
//            $text = mb_substr($text, 4096);
//        } while (mb_strlen($text, 'UTF-8') > 0);

//        $textArray[] = (string)mb_strlen($text, '8bit');

        foreach ($textArray as $textItem) {
            $typeBtnForLastMsg = '';
            $command = 'new_message';
            if ($textItem == end($textArray)) {
                $typeBtnForLastMsg = $typeBtn;
                $command = 'list_events';
            }

            $msg_id = self::sendMessage($chatId, $textItem, $typeBtnForLastMsg);

            $telegramMessage = new TelegramMessageDto();
            $telegramMessage->chat_id = $chatId;
            $telegramMessage->message_id = $msg_id;
            $telegramMessage->text = $textItem;
            $telegramMessage->command = $command;
            $telegramMessage->reply_to = 0;

            $telegramMessageRepository->create($telegramMessage);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    public static function editMessageTextSend(
        DataEditMessageDto                 $dataEditMessage,
        TelegramMessageRepositoryInterface $telegramMessageRepository = null
    ): void
    {
        $thisObj = new self();

//        if (mb_strlen($dataEditMessage->text, 'UTF-8') > 4096) {
//            self::deleteMessage($dataEditMessage->chat_id, $dataEditMessage->message_id);
//
//            $thisObj->newMessage(
//                $dataEditMessage->text,
//                $dataEditMessage->chat_id,
//                $dataEditMessage->keyboard,
//                $telegramMessageRepository
//            );
//
//        } else {

            $thisObj->telegram->editMessageText([
                'chat_id' => $dataEditMessage->chat_id,
                'message_id' => $dataEditMessage->message_id,
                'text' => $dataEditMessage->text,
                'reply_markup' => self::getKeyboard($dataEditMessage->keyboard, $dataEditMessage->keyboardData),
                'parse_mode' => 'HTML',
            ]);
//        }
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
                                'text' => '👥 в группе (доступны: ' . $keyboardData['count_group'] . ')',
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
            "to_the_next_page" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🙅 отмена',
                                'callback_data' => 'settings_menu',
                            ],
                            [
                                'text' => '⏩',
                                'callback_data' => 'next_event_' . $keyboardData['next'],
                            ],
                        ],
                    ],
                ],
            ),
            "to_the_back_page" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '⏪',
                                'callback_data' => 'back_event_' . $keyboardData['back'],
                            ],
                            [
                                'text' => '🙅 отмена',
                                'callback_data' => 'settings_menu',
                            ],
                        ],
                    ],
                ],
            ),
            "to_the_next_back_page" => json_encode(
                [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '⏪',
                                'callback_data' => 'back_event_' . $keyboardData['back'],
                            ],
                            [
                                'text' => '🙅 отмена',
                                'callback_data' => 'settings_menu',
                            ],
                            [
                                'text' => '⏩',
                                'callback_data' => 'next_event_' . $keyboardData['next'],
                            ],
                        ],
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
                                'text' => '➕ Добавить день рождения',
                                'callback_data' => 'add_birthday',
                            ],
                        ],
                        [
                            [
                                'text' => '➕ Добавить годовщину',
                                'callback_data' => 'add_anniversary',
                            ],
                        ],
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