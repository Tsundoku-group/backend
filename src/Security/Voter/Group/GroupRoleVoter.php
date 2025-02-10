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

        $profiles = $user->getProfiles();

        foreach ($profiles as $profile) {
            if ($this->isGroupAdmin($profile, $subject)) {
                return match ($attribute) {
                    self::DELETE_GROUP, self::MANAGE_MEMBERS => true,
                    self::POST_CONTENT => true,
                    self::VIEW_GROUP => true,
                    default => false,
                };
            }

            if ($this->isGroupMember($profile, $subject)) {
                return match ($attribute) {
                    self::POST_CONTENT => true,
                    self::VIEW_GROUP => true,
                    default => false,
                };
            }
        }

        return false;
    }

    private function isGroupAdmin(Profile $profile, Group $group): bool
    {
        $groupProfile = $this->groupProfileRepository->findOneBy([
            'group' => $group,
            'profile' => $profile,
        ]);

        return $groupProfile && 'admin' === $groupProfile->getRole();
    }

    private function isGroupMember(Profile $profile, Group $group): bool
    {
        return (bool) $this->groupProfileRepository->findOneBy([
            'group' => $group,
            'profile' => $profile,
        ]);
    }
}
