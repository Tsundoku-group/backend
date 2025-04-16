<?php

namespace App\Security\Voter\Group;

use App\Entity\Group;
use App\Entity\User;
use App\Repository\GroupProfileRepository;
use InvalidArgumentException;
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

        if ($subject->getId() === 1 && $attribute === self::POST_CONTENT) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (1 === $subject->getId() && self::POST_CONTENT === $attribute) {
            return true;
        }

        if (self::VIEW_GROUP === $attribute && 'public' === $subject->getVisibility()) {
            return true;
        }

        foreach ($user->getProfiles() as $profile) {
            $groupProfile = $this->groupProfileRepository->findOneBy([
                'group' => $subject,
                'profile' => $profile,
            ]);

            if ($groupProfile) {
                $permissions = [
                    self::DELETE_GROUP => 'admin' === $groupProfile->getRole(),
                    self::MANAGE_MEMBERS => in_array($groupProfile->getRole(), ['admin', 'moderator']),
                    self::POST_CONTENT => true,
                    self::VIEW_GROUP => true,
                ];

                if ($permissions[$attribute] ?? false) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function fromString(string $role): string
    {
        $validRoles = ['admin', 'moderator', 'member'];

        if (!in_array($role, $validRoles, true)) {
            throw new InvalidArgumentException('Rôle invalide.');
        }

        return $role;
    }
}
