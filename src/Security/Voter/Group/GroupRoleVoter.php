<?php

namespace App\Security\Voter\Group;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class GroupRoleVoter extends Voter
{
    public const DELETE_GROUP = 'delete_group';
    public const MANAGE_MEMBERS = 'manage_members';
    public const POST_CONTENT = 'post_content';
    public const VIEW_GROUP = 'view_group';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::DELETE_GROUP,
                self::MANAGE_MEMBERS,
                self::POST_CONTENT,
                self::VIEW_GROUP,
            ], true) && $subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject) {
            return false;
        }

        return match ($attribute) {
            self::DELETE_GROUP => $subject->canDeleteGroup(),
            self::MANAGE_MEMBERS => $subject->canManageMembers(),
            self::POST_CONTENT => $subject->canPostContent(),
            self::VIEW_GROUP => $subject->canViewGroup(),
            default => false,
        };
    }
}
