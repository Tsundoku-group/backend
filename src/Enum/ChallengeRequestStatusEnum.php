<?php

namespace App\Enum;

enum ChallengeRequestStatusEnum: string
{
    case INVITED = 'invited';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}