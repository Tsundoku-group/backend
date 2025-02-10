<?php

namespace App\Security\Voter\Group;

use App\Entity\Group;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class GroupVisibilityVoter extends Voter
{
    public const VIEW_GROUP = 'view_group';
    public const EDIT_GROUP = 'edit_group';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::VIEW_GROUP,
                self::EDIT_GROUP,
            ], true) && $subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Group) {
            return false;
        }

        return match ($attribute) {
            self::VIEW_GROUP => $subject->canView(),
            self::EDIT_GROUP => $subject->canEdit(),
            default => false,
        };
    }
}
