<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ConversationRepository
{
    private EntityRepository $repository;

    public function __construct(EntityManager $entityManager)
    {
        $this->repository = $entityManager->getRepository(Conversation::class);
    }

    public function findById(string $id): ?Conversation
    {
        return $this->repository->find($id);
    }

    public function findByUser(User $user): array
    {
        return $this->repository->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->repository->findBy(['user' => $user, 'active' => true], ['updatedAt' => 'DESC']);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }
}
