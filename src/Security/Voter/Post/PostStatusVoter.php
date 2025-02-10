<?php

namespace App\Security\Voter\Post;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PostStatusVoter extends Voter
{
    public const EDIT_POST = 'edit_post';
    public const DELETE_POST = 'delete_post';
    public const RESTORE_POST = 'restore_post';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::EDIT_POST,
            self::DELETE_POST,
            self::RESTORE_POST,
        ], true) && $subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Post) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $profiles = $user->getProfiles();

        foreach ($profiles as $profile) {
            if ($subject->getAuthor()->getId() === $profile->getId()) {
                return match ($attribute) {
                    self::EDIT_POST, self::DELETE_POST => true,
                    self::RESTORE_POST => false,
                    default => false,
                };
            }
        }

        return false;
    }
}
