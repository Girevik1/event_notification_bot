<?php

declare(strict_types=1);

namespace Art\Code\Domain\Entity;

//use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
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
            $dataForSend = self::getKeyboard($dataForSend, $typeBtn);
        }

        $telegram = new Api($_ENV['TELEGRAM_KEY']);
        $response = $telegram->sendMessage($dataForSend);

        return $response->getMessageId();
    }

    public function deleteMessage($login, $msg_id)
    {
//        $user = User::where('login', $login)->first();
        $user = $this->telegramUserRepository->firstByLogin($login);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $_ENV['TELEGRAM_KEY'] . "/deleteMessage?chat_id=" . $user->telegram_chat_id . "&message_id=" . $msg_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
        return "";
    }

    private static function getKeyboard(array $dataForSend, string $type): array
    {
        switch ($type) {
            case "cfo-acception-procedure-continue":
                $dataForSend['reply_markup'] = json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'продолжить',
//                                    'text' => '✅ Продолжить',
                                    'callback_data' => 'ceo-proc-start',
                                ],
                            ]

                        ],
                    ],
                );
                break;

            case "cfo-acception-procedure-agreement":
                $dataForSend['reply_markup'] = json_encode(
                    [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '➖',
                                    'callback_data' => 'ceo-proc-minus',
                                ],
                                [
                                    'text' => 'skip',
                                    'callback_data' => 'ceo-proc-skip',
                                ],
                                [
                                    'text' => '➕',
                                    'callback_data' => 'ceo-proc-plus',
                                ],
                            ],
                            // [
                            //     [
                            //         'text' => '⏩ Skip',
                            //         'callback_data' => 'ceo-proc-skip',
                            //     ],
                            // ]

                        ],
                    ],
                );
                break;
            case "cfo-acception-procedure-agreement-skip-off":
                $dataForSend['reply_markup'] = json_encode(
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

        return $dataForSend;
    }


}