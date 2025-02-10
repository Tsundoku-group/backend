<?php

namespace App\Security\Voter\Post;

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
        if (!$subject) {
            return false;
        }

        return match ($attribute) {
            self::EDIT_POST => $subject->canBeEdited(),
            self::DELETE_POST => $subject->canBeDeleted(),
            self::RESTORE_POST => $subject->canBeRestored(),
            default => false,
        };
    }
}
