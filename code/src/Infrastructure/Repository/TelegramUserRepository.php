<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Application\Dto\TelegramUserDto;
use Art\Code\Domain\Contract\TelegramUserRepositoryInterface;
use Art\Code\Domain\Entity\TelegramUser;

class TelegramUserRepository implements TelegramUserRepositoryInterface
{
//    public function __construct(ManagerRegistry $registry)
//    {
//        parent::__construct($registry, User::class);
//    }
//
//    /**
//     * @throws Exception
//     */
//    public function nextId()
//    {
//        return new Id(
//            $this->getEntityManager()
//                ->getConnection()
//                ->executeQuery('SELECT nextval(\'users_id_seq\')')
//                ->fetchOne()
//        );
//        return 0;
//    }
//
//    public function firstByEmail(Email $email): ?User
//    {
//        return $this
//            ->createQueryBuilder('u')
//            ->where('u.email = :email')
//            ->setParameter('email', $email->getValue())
//            ->setMaxResults(1)
//            ->getQuery()
//            ->getOneOrNullResult();
//    }
//
    public function create(TelegramUserDto $telegramUserDto): void
    {
        $telegramUser = new TelegramUser();
        $telegramUser->login = $telegramUserDto->username;
        $telegramUser->name = 'art';
        $telegramUser->information = 'artur test5';
        $telegramUser->telegram_chat_id = $telegramUserDto->chat_id;
        $telegramUser->save();
    }

    public function firstById($id): ?TelegramUser
    {
        return TelegramUser::where('id','=', $id)->first();
    }

    public function firstByChatId($chatId): ?TelegramUser
    {
        return TelegramUser::where('chat_id','=', $chatId)->first();
    }

    public function firstByLogin($login): ?TelegramUser
    {
        return TelegramUser::where('login','=', $login)->first();
    }

    public function isExistByLogin($login): bool
    {
        return TelegramUser::where('login','=', $login)->exist();
    }
}