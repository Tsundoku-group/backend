<?php

namespace App\Security\Voter\Post;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class PostVisibilityVoter extends Voter
{
    public const CHANGE_VISIBILITY = 'change_visibility';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::CHANGE_VISIBILITY && $subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        foreach ($user->getProfiles() as $profile) {
            if ($subject->getAuthor()->getId() === $profile->getId()) {
                return true;
            }
        }

        return true;
    }
}
