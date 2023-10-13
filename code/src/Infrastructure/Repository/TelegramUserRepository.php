<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

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
    public function nextId()
    {
//        return new Id(
//            $this->getEntityManager()
//                ->getConnection()
//                ->executeQuery('SELECT nextval(\'users_id_seq\')')
//                ->fetchOne()
//        );
        return 0;
    }
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
    public function create(): void
    {
        $telegramUser = new TelegramUser();
        $telegramUser->login = 'test2';
        $telegramUser->name = 'art';
        $telegramUser->information = 'artur test5';
        $telegramUser->telegram_chat_id = '888812382131';
        $telegramUser->save();
    }

    public function firstById($id): ?TelegramUser
    {
        return TelegramUser::where('id','=', $id)->first();
    }
}