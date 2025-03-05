<?php

namespace App\Security\Voter\Group;

use App\Entity\Group;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\GroupProfileRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class GroupRoleVoter extends Voter
{
    public const DELETE_GROUP = 'delete_group';
    public const MANAGE_MEMBERS = 'manage_members';
    public const POST_CONTENT = 'post_content';
    public const VIEW_GROUP = 'view_group';

    private GroupProfileRepository $groupProfileRepository;

    public function __construct(GroupProfileRepository $groupProfileRepository)
    {
        $this->groupProfileRepository = $groupProfileRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::DELETE_GROUP,
                self::MANAGE_MEMBERS,
                self::POST_CONTENT,
                self::VIEW_GROUP,
            ], true) && $subject instanceof Group;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Group) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject->getId() === 1 && $attribute === self::POST_CONTENT) {
            return true;
        }

        if ($attribute === self::VIEW_GROUP && $subject->getVisibility() === 'public') {
            return true;
        }

        $profiles = $user->getProfiles();

        $groupProfile = $this->groupProfileRepository->findOneBy([
            'group' => $subject,
            'profile' => $profiles,
        ]);

        if ($groupProfile) {
            $permissions = [
                self::DELETE_GROUP => $groupProfile->getRole() === 'admin',
                self::MANAGE_MEMBERS => in_array($groupProfile->getRole(), ['admin', 'moderator']),
                self::POST_CONTENT => true,
                self::VIEW_GROUP => true,
            ];

            return $permissions[$attribute] ?? false;
        }

        return false;
    }
}