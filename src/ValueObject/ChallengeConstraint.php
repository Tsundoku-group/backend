<?php 

namespace App\ValueObject;

use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeFrequencyEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Embeddable]
class ChallengeConstraint
{
    #[ORM\Column(type: 'string', enumType: ChallengeActionTypeEnum::class)]
    private ChallengeActionTypeEnum $action;

    #[ORM\Column(type: 'string', enumType: ChallengeContentTypeEnum::class)]
    private ChallengeContentTypeEnum $contentType;

    #[ORM\Column(type: 'string', enumType: ChallengeFrequencyEnum::class)]
    private ChallengeFrequencyEnum $frequency;

    #[ORM\Column(type: 'integer')]
    private int $targetCount;

    public function __construct(
        ChallengeActionTypeEnum $action,
        ChallengeContentTypeEnum $contentType,
        ChallengeFrequencyEnum $frequency,
        int $targetCount
    ) {
        $this->action      = $action;
        $this->contentType = $contentType;
        $this->frequency   = $frequency;
        $this->targetCount = $targetCount;
    }

    public function getAction(): ChallengeActionTypeEnum
    {
        return $this->action;
    }

    public function getContentType(): ChallengeContentTypeEnum
    {
        return $this->contentType;
    }

    public function getFrequency(): ChallengeFrequencyEnum
    {
        return $this->frequency;
    }

    public function getTargetCount(): int
    {
        return $this->targetCount;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function validateContentForAction(ExecutionContextInterface $context): void
    {
        $allowed = [
            ChallengeActionTypeEnum::WRITE => [
                ChallengeContentTypeEnum::ARTICLE,
                ChallengeContentTypeEnum::BOOK_REVIEW,
                ChallengeContentTypeEnum::BOOK_DESCRIPTION,
            ],
            ChallengeActionTypeEnum::READ => [
                ChallengeContentTypeEnum::BOOK,
                ChallengeContentTypeEnum::PAGE,
                ChallengeContentTypeEnum::CHAPTER,
            ],
        ];

        $action = $this->getAction();
        $content = $this->getContentType();

        if (isset($allowed[$action]) && !in_array($content, $allowed[$action], true)) {
            $context
                ->buildViolation('For the « ' . $action->value . ' » action, you can only choose « '
                    . implode(' », « ', array_map(fn($c) => $c->value, $allowed[$action]))
                    . ' ».')
                ->atPath('contentType')
                ->addViolation();
        }
    }
}